<?php

namespace App\Services;

use App\Models\Shipment;
use Carbon\CarbonImmutable;

class ShipmentSlaService
{
    public const GROUP_THRESHOLDS = [
        'A' => 12,
        'B' => 18,
        'C' => 24,
    ];

    public function thresholdHoursFor(?string $groupCode, ?int $dbThresholdHours = null): int
    {
        $normalized = strtoupper(trim((string) $groupCode));

        if (isset(self::GROUP_THRESHOLDS[$normalized])) {
            return self::GROUP_THRESHOLDS[$normalized];
        }

        if ($dbThresholdHours !== null && $dbThresholdHours > 0) {
            return $dbThresholdHours;
        }

        return self::GROUP_THRESHOLDS['C'];
    }

    public function evaluate(Shipment $shipment): array
    {
        $thresholdHours = $this->thresholdHoursFor(
            $shipment->group_code,
            $shipment->delay_threshold_hours
        );

        $referenceTime = $this->resolveReferenceTime($shipment);
        $now = CarbonImmutable::now();

        $isDelivered = strtolower((string) $shipment->current_status) === 'delivered'
            || $shipment->delivered_at !== null;

        $overdueHours = 0;

        if ($referenceTime !== null) {
            $deadline = CarbonImmutable::instance($referenceTime)->addHours($thresholdHours);
            $comparisonPoint = $isDelivered && $shipment->delivered_at !== null
                ? CarbonImmutable::instance($shipment->delivered_at)
                : $now;

            if ($comparisonPoint->greaterThan($deadline)) {
                $overdueHours = (int) floor($deadline->diffInMinutes($comparisonPoint) / 60);
                if ($overdueHours < 1) {
                    $overdueHours = 1;
                }
            }
        }

        $isBreached = $overdueHours > 0 || (bool) $shipment->is_delayed;
        if ($isBreached && $overdueHours === 0 && (int) $shipment->delay_hours > 0) {
            $overdueHours = (int) $shipment->delay_hours;
        }

        $severity = $this->resolveSeverity($overdueHours, $isBreached, $shipment->delay_severity);

        return [
            'group_code' => strtoupper((string) ($shipment->group_code ?: 'C')),
            'threshold_hours' => $thresholdHours,
            'overdue_hours' => $overdueHours,
            'is_breached' => $isBreached,
            'severity' => $severity,
            'label' => $isBreached ? "Delayed (+{$overdueHours}h)" : 'On Track',
        ];
    }

    public function isBreached(Shipment $shipment): bool
    {
        return $this->evaluate($shipment)['is_breached'];
    }

    public function overdueHours(Shipment $shipment): int
    {
        return $this->evaluate($shipment)['overdue_hours'];
    }

    private function resolveReferenceTime(Shipment $shipment): mixed
    {
        return $shipment->expected_arrival_at
            ?? $shipment->estimated_arrival_at
            ?? $shipment->expected_departure_at
            ?? $shipment->created_at;
    }

    private function resolveSeverity(int $overdueHours, bool $isBreached, ?string $existingSeverity): ?string
    {
        if (! $isBreached) {
            return null;
        }

        if ($overdueHours >= 24) {
            return 'critical';
        }

        if ($overdueHours >= 12) {
            return 'high';
        }

        if ($overdueHours > 0) {
            return 'medium';
        }

        return $existingSeverity ?: 'medium';
    }
}