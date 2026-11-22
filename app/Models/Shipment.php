<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shipment extends Model
{
    protected $fillable = [
        'shipment_number',
        'customer_id',
        'carrier_id',
        'origin_location_id',
        'destination_location_id',
        'group_code',
        'delay_threshold_hours',
        'cargo_type',
        'weight_kg',
        'volume_m3',
        'expected_departure_at',
        'expected_arrival_at',
        'estimated_arrival_at',
        'delivered_at',
        'current_status',
        'is_delayed',
        'delay_hours',
        'delay_severity',
        'delay_reason',
    ];

    protected function casts(): array
    {
        return [
            'delay_threshold_hours' => 'integer',
            'weight_kg' => 'decimal:2',
            'volume_m3' => 'decimal:3',
            'expected_departure_at' => 'datetime',
            'expected_arrival_at' => 'datetime',
            'estimated_arrival_at' => 'datetime',
            'delivered_at' => 'datetime',
            'is_delayed' => 'boolean',
            'delay_hours' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    public function originLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'origin_location_id');
    }

    public function destinationLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'destination_location_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShipmentItem::class);
    }

    public function trackingEvents(): HasMany
    {
        return $this->hasMany(TrackingEvent::class);
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(ShipmentException::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }
}