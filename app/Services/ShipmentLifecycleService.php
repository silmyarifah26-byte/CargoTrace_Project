<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Location;
use App\Models\Notification;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\TrackingEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ShipmentLifecycleService
{
    public function __construct(
        private readonly ShipmentSlaService $slaService
    ) {}

    public function recordTrackingEvent(Shipment $shipment, array $data, User $actor): TrackingEvent
    {
        return DB::transaction(function () use ($shipment, $data, $actor) {
            $shipment->refresh();

            $occurredAt = ! empty($data['occurred_at'])
                ? Carbon::parse($data['occurred_at'])
                : now();

            $location = ! empty($data['location_id'])
                ? Location::find($data['location_id'])
                : null;

            $newStatus = trim((string) $data['status']);
            $previousStatus = $shipment->current_status;
            $previousLocationId = $shipment->origin_location_id;

            $event = TrackingEvent::create([
                'shipment_id' => $shipment->id,
                'location_id' => $location?->id,
                'created_by' => $actor->id,
                'status' => $newStatus,
                'event_type' => 'status_update',
                'location_label' => $location?->name,
                'latitude' => $location?->latitude ?? null,
                'longitude' => $location?->longitude ?? null,
                'description' => trim((string) $data['description']),
                'occurred_at' => $occurredAt,
            ]);

            $shipmentUpdates = [
                'current_status' => $newStatus,
                'delay_threshold_hours' => $this->slaService->thresholdHoursFor(
                    $shipment->group_code,
                    $shipment->delay_threshold_hours
                ),
            ];

            if (strtolower($newStatus) === 'delivered' && $shipment->delivered_at === null) {
                $shipmentUpdates['delivered_at'] = $occurredAt;
            }

            $shipment->update($shipmentUpdates);
            $shipment->refresh();

            $slaResult = $this->slaService->evaluate($shipment);
            $delayReason = ! empty($data['delay_reason'])
                ? trim((string) $data['delay_reason'])
                : ($slaResult['is_breached'] ? trim((string) $data['description']) : $shipment->delay_reason);

            $shipment->update([
                'is_delayed' => $slaResult['is_breached'],
                'delay_hours' => $slaResult['overdue_hours'],
                'delay_severity' => $slaResult['severity'],
                'delay_reason' => $delayReason,
            ]);

            if ($slaResult['is_breached']) {
                $existingOpenException = ShipmentException::where('shipment_id', $shipment->id)
                    ->whereIn('status', ['open', 'investigating'])
                    ->latest('detected_at')
                    ->first();

                if ($existingOpenException) {
                    $existingOpenException->update([
                        'severity' => $slaResult['severity'] ?? 'high',
                        'notes' => "SLA breached (+{$slaResult['overdue_hours']}h on Group {$slaResult['group_code']}): ".trim((string) $data['description']),
                    ]);
                } else {
                    ShipmentException::create([
                        'shipment_id' => $shipment->id,
                        'reported_by' => $actor->id,
                        'exception_type' => 'sla_delay',
                        'severity' => $slaResult['severity'] ?? 'high',
                        'status' => 'open',
                        'notes' => "SLA threshold ({$slaResult['threshold_hours']}h) exceeded by {$slaResult['overdue_hours']}h. ".trim((string) $data['description']),
                        'detected_at' => now(),
                    ]);
                }
            }

            $recipientIds = User::whereIn('role', ['admin', 'operator'])
                ->pluck('id')
                ->push($shipment->customer_id)
                ->filter()
                ->unique();

            foreach ($recipientIds as $recipientId) {
                Notification::create([
                    'user_id' => $recipientId,
                    'shipment_id' => $shipment->id,
                    'title' => $slaResult['is_breached']
                        ? "SLA Delay Alert — {$shipment->shipment_number}"
                        : "Shipment Update — {$shipment->shipment_number}",
                    'message' => "Status updated to {$newStatus}"
                        .($location ? " at {$location->name}" : '')
                        .". ".trim((string) $data['description'])
                        .($slaResult['is_breached'] ? " (Overdue +{$slaResult['overdue_hours']}h)" : ''),
                    'type' => $slaResult['is_breached'] ? 'sla_breach' : 'tracking_event',
                    'read_at' => null,
                ]);
            }

            AuditLog::create([
                'actor_id' => $actor->id,
                'action' => 'shipment.tracking_event.recorded',
                'entity_type' => Shipment::class,
                'entity_id' => $shipment->id,
                'metadata' => [
                    'shipment_number' => $shipment->shipment_number,
                    'tracking_event_id' => $event->id,
                    'previous_status' => $previousStatus,
                    'new_status' => $newStatus,
                    'location_id' => $location?->id,
                    'previous_origin_location_id' => $previousLocationId,
                    'occurred_at' => $occurredAt->toIso8601String(),
                    'sla' => $slaResult,
                ],
                'created_at' => now(),
            ]);

            return $event;
        });
    }
}