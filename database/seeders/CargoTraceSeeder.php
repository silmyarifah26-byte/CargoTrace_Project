<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Carrier;
use App\Models\Location;
use App\Models\Notification;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\ShipmentItem;
use App\Models\TrackingEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CargoTraceSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Users (Admin, Logistics Operator, Customers)
        $admin = User::updateOrCreate(
            ['email' => 'admin@cargotrace.test'],
            [
                'name' => 'Admin CargoTrace',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        $operator = User::updateOrCreate(
            ['email' => 'operator@cargotrace.test'],
            [
                'name' => 'Raka Pratama (Operator)',
                'password' => Hash::make('password123'),
                'role' => 'operator',
            ]
        );

        $customer1 = User::updateOrCreate(
            ['email' => 'customer1@cargotrace.test'],
            [
                'name' => 'PT Nusantara Ekspor',
                'password' => Hash::make('password123'),
                'role' => 'customer',
            ]
        );

        $customer2 = User::updateOrCreate(
            ['email' => 'customer2@cargotrace.test'],
            [
                'name' => 'CV Samudra Niaga',
                'password' => Hash::make('password123'),
                'role' => 'customer',
            ]
        );

        // 2. Carriers (Maritime Vessel Operators)
        $maersk = Carrier::updateOrCreate(
            ['code' => 'MAEU'],
            [
                'name' => 'Maersk Line Global',
                'contact_email' => 'ops@maersk-demo.test',
                'contact_phone' => '+65-6323-8323',
                'is_active' => true,
            ]
        );

        $msc = Carrier::updateOrCreate(
            ['code' => 'MSCU'],
            [
                'name' => 'Mediterranean Shipping Co.',
                'contact_email' => 'support@msc-demo.test',
                'contact_phone' => '+41-22-703-8888',
                'is_active' => true,
            ]
        );

        $pil = Carrier::updateOrCreate(
            ['code' => 'PILU'],
            [
                'name' => 'Pacific International Lines',
                'contact_email' => 'tracking@pil-demo.test',
                'contact_phone' => '+65-6221-8133',
                'is_active' => true,
            ]
        );

        // 3. Seaports — Indonesia & International Ports
        $ports = [
            // Indonesia Seaports
            'jakarta' => [
                'code' => 'IDJKT',
                'name' => 'Port of Tanjung Priok',
                'city' => 'Jakarta',
                'country' => 'Indonesia',
                'latitude' => -6.1025000,
                'longitude' => 106.8794000,
            ],
            'surabaya' => [
                'code' => 'IDSUB',
                'name' => 'Port of Tanjung Perak',
                'city' => 'Surabaya',
                'country' => 'Indonesia',
                'latitude' => -7.2028000,
                'longitude' => 112.7364000,
            ],
            'belawan' => [
                'code' => 'IDBWN',
                'name' => 'Port of Belawan',
                'city' => 'Medan',
                'country' => 'Indonesia',
                'latitude' => 3.7847000,
                'longitude' => 98.6856000,
            ],
            'makassar' => [
                'code' => 'IDUPG',
                'name' => 'Port of Soekarno-Hatta Makassar',
                'city' => 'Makassar',
                'country' => 'Indonesia',
                'latitude' => -5.1256000,
                'longitude' => 119.4067000,
            ],

            // International Seaports
            'singapore' => [
                'code' => 'SGSIN',
                'name' => 'Port of Singapore (PSA)',
                'city' => 'Singapore',
                'country' => 'Singapore',
                'latitude' => 1.2644000,
                'longitude' => 103.8223000,
            ],
            'rotterdam' => [
                'code' => 'NLRTM',
                'name' => 'Port of Rotterdam',
                'city' => 'Rotterdam',
                'country' => 'Netherlands',
                'latitude' => 51.9496000,
                'longitude' => 4.1453000,
            ],
            'losAngeles' => [
                'code' => 'USLAX',
                'name' => 'Port of Los Angeles',
                'city' => 'Los Angeles',
                'country' => 'United States',
                'latitude' => 33.7405000,
                'longitude' => -118.2720000,
            ],
            'sydney' => [
                'code' => 'AUSYD',
                'name' => 'Port Botany Sydney',
                'city' => 'Sydney',
                'country' => 'Australia',
                'latitude' => -33.9722000,
                'longitude' => 151.2241000,
            ],
            'busan' => [
                'code' => 'KRPUS',
                'name' => 'Port of Busan',
                'city' => 'Busan',
                'country' => 'South Korea',
                'latitude' => 35.1028000,
                'longitude' => 129.0403000,
            ],
            'tokyo' => [
                'code' => 'JPTYO',
                'name' => 'Port of Tokyo',
                'city' => 'Tokyo',
                'country' => 'Japan',
                'latitude' => 35.6197000,
                'longitude' => 139.7764000,
            ],
        ];

        $loc = [];
        foreach ($ports as $key => $portData) {
            $loc[$key] = Location::updateOrCreate(
                ['code' => $portData['code']],
                $portData
            );
        }

        $now = Carbon::now();

        // 4. Shipment Group A (Indonesia -> Europe via Singapore | Threshold: 12h | Customs Hold Delay)
        $shipmentA = Shipment::updateOrCreate(
            ['shipment_number' => 'CT-2026-A001'],
            [
                'customer_id' => $customer1->id,
                'carrier_id' => $maersk->id,
                'origin_location_id' => $loc['jakarta']->id,
                'destination_location_id' => $loc['rotterdam']->id,
                'group_code' => 'A',
                'delay_threshold_hours' => 12,
                'cargo_type' => 'Coffee & Spices Container (20ft)',
                'weight_kg' => 14500.00,
                'volume_m3' => 28.500,
                'expected_departure_at' => $now->copy()->subDays(12),
                'expected_arrival_at' => $now->copy()->addDays(2),
                'estimated_arrival_at' => $now->copy()->addDays(2)->addHours(16),
                'current_status' => 'IN_TRANSIT',
                'is_delayed' => true,
                'delay_hours' => 16,
                'delay_severity' => 'MEDIUM',
                'delay_reason' => 'Estimated arrival exceeds Group A 12h threshold by 16h due to customs inspection.',
            ]
        );

        ShipmentItem::updateOrCreate(
            ['shipment_id' => $shipmentA->id, 'sku' => 'GAYO-ARABICA-01'],
            [
                'item_name' => 'Aceh Gayo Green Coffee Beans (250 Bags)',
                'quantity' => 250,
                'weight_kg' => 14500.00,
                'declared_value' => 58000.00,
            ]
        );

        if ($shipmentA->trackingEvents()->count() === 0) {
            TrackingEvent::create([
                'shipment_id' => $shipmentA->id,
                'location_id' => $loc['jakarta']->id,
                'created_by' => $operator->id,
                'status' => 'CREATED',
                'event_type' => 'NORMAL',
                'location_label' => 'Port of Tanjung Priok, Jakarta',
                'latitude' => $loc['jakarta']->latitude,
                'longitude' => $loc['jakarta']->longitude,
                'description' => 'Shipment order created and export manifest registered.',
                'occurred_at' => $now->copy()->subDays(13),
            ]);

            TrackingEvent::create([
                'shipment_id' => $shipmentA->id,
                'location_id' => $loc['singapore']->id,
                'created_by' => $operator->id,
                'status' => 'IN_TRANSIT',
                'event_type' => 'EXCEPTION',
                'location_label' => 'PSA Transshipment Hub, Singapore',
                'latitude' => $loc['singapore']->latitude,
                'longitude' => $loc['singapore']->longitude,
                'description' => 'Container held for secondary customs documentation clearance.',
                'occurred_at' => $now->copy()->subDays(5),
            ]);
        }

        if ($shipmentA->exceptions()->count() === 0) {
            ShipmentException::create([
                'shipment_id' => $shipmentA->id,
                'reported_by' => $operator->id,
                'exception_type' => 'CUSTOMS_HOLD',
                'severity' => 'MEDIUM',
                'status' => 'INVESTIGATING',
                'notes' => 'Phytosanitary certificate verification requested at transshipment port.',
                'detected_at' => $now->copy()->subDays(5),
            ]);
        }

        // 5. Shipment Group B (Asia -> North America | Threshold: 18h | Weather Delay)
        $shipmentB = Shipment::updateOrCreate(
            ['shipment_number' => 'CT-2026-B001'],
            [
                'customer_id' => $customer2->id,
                'carrier_id' => $msc->id,
                'origin_location_id' => $loc['singapore']->id,
                'destination_location_id' => $loc['losAngeles']->id,
                'group_code' => 'B',
                'delay_threshold_hours' => 18,
                'cargo_type' => 'Electronics & Semiconductor Parts',
                'weight_kg' => 9800.00,
                'volume_m3' => 19.200,
                'expected_departure_at' => $now->copy()->subDays(10),
                'expected_arrival_at' => $now->copy()->addDays(4),
                'estimated_arrival_at' => $now->copy()->addDays(5)->addHours(4),
                'current_status' => 'IN_TRANSIT',
                'is_delayed' => true,
                'delay_hours' => 28,
                'delay_severity' => 'HIGH',
                'delay_reason' => 'Estimated arrival exceeds Group B 18h threshold by 28h due to North Pacific storm rerouting.',
            ]
        );

        ShipmentItem::updateOrCreate(
            ['shipment_id' => $shipmentB->id, 'sku' => 'ELEC-PCB-88'],
            [
                'item_name' => 'Industrial Control Boards (80 Pallets)',
                'quantity' => 80,
                'weight_kg' => 9800.00,
                'declared_value' => 142000.00,
            ]
        );

        if ($shipmentB->trackingEvents()->count() === 0) {
            TrackingEvent::create([
                'shipment_id' => $shipmentB->id,
                'location_id' => $loc['singapore']->id,
                'created_by' => $operator->id,
                'status' => 'PICKED_UP',
                'event_type' => 'NORMAL',
                'location_label' => 'Port of Singapore (PSA)',
                'latitude' => $loc['singapore']->latitude,
                'longitude' => $loc['singapore']->longitude,
                'description' => 'Loaded onto trans-Pacific container vessel MSC Aurora.',
                'occurred_at' => $now->copy()->subDays(10),
            ]);

            TrackingEvent::create([
                'shipment_id' => $shipmentB->id,
                'location_id' => null,
                'created_by' => $operator->id,
                'status' => 'IN_TRANSIT',
                'event_type' => 'EXCEPTION',
                'location_label' => 'North Pacific Ocean Waypoint',
                'latitude' => 28.4500000,
                'longitude' => 165.3000000,
                'description' => 'Vessel reduced speed and adjusted course due to severe storm front.',
                'occurred_at' => $now->copy()->subDays(2),
            ]);
        }

        if ($shipmentB->exceptions()->count() === 0) {
            ShipmentException::create([
                'shipment_id' => $shipmentB->id,
                'reported_by' => $operator->id,
                'exception_type' => 'WEATHER_DELAY',
                'severity' => 'HIGH',
                'status' => 'OPEN',
                'notes' => 'Heavy swell conditions in North Pacific corridor.',
                'detected_at' => $now->copy()->subDays(2),
            ]);
        }

        // 6. Shipment Group C (Indonesia -> Australia | Threshold: 24h | On-Time Delivered + Resolved Exception)
        $shipmentC = Shipment::updateOrCreate(
            ['shipment_number' => 'CT-2026-C001'],
            [
                'customer_id' => $customer1->id,
                'carrier_id' => $pil->id,
                'origin_location_id' => $loc['jakarta']->id,
                'destination_location_id' => $loc['sydney']->id,
                'group_code' => 'C',
                'delay_threshold_hours' => 24,
                'cargo_type' => 'Textiles & Apparel Garments',
                'weight_kg' => 7200.00,
                'volume_m3' => 22.000,
                'expected_departure_at' => $now->copy()->subDays(9),
                'expected_arrival_at' => $now->copy()->subDays(1),
                'estimated_arrival_at' => $now->copy()->subDays(1)->addHours(6),
                'delivered_at' => $now->copy()->subDays(1)->addHours(5),
                'current_status' => 'DELIVERED',
                'is_delayed' => false,
                'delay_hours' => 0,
                'delay_severity' => null,
                'delay_reason' => null,
            ]
        );

        ShipmentItem::updateOrCreate(
            ['shipment_id' => $shipmentC->id, 'sku' => 'TEX-BATIK-12'],
            [
                'item_name' => 'Export Cotton Garment Cartons',
                'quantity' => 180,
                'weight_kg' => 7200.00,
                'declared_value' => 41500.00,
            ]
        );

        if ($shipmentC->trackingEvents()->count() === 0) {
            TrackingEvent::create([
                'shipment_id' => $shipmentC->id,
                'location_id' => $loc['jakarta']->id,
                'created_by' => $operator->id,
                'status' => 'BOOKED',
                'event_type' => 'NORMAL',
                'location_label' => 'Port of Tanjung Priok, Jakarta',
                'latitude' => $loc['jakarta']->latitude,
                'longitude' => $loc['jakarta']->longitude,
                'description' => 'Vessel slot confirmed with PIL.',
                'occurred_at' => $now->copy()->subDays(9),
            ]);

            TrackingEvent::create([
                'shipment_id' => $shipmentC->id,
                'location_id' => $loc['sydney']->id,
                'created_by' => $operator->id,
                'status' => 'DELIVERED',
                'event_type' => 'NORMAL',
                'location_label' => 'Port Botany Sydney, Australia',
                'latitude' => $loc['sydney']->latitude,
                'longitude' => $loc['sydney']->longitude,
                'description' => 'Cargo cleared and delivered to consignee warehouse within Group C 24h SLA.',
                'occurred_at' => $now->copy()->subDays(1)->addHours(5),
            ]);
        }

        if ($shipmentC->exceptions()->count() === 0) {
            ShipmentException::create([
                'shipment_id' => $shipmentC->id,
                'reported_by' => $operator->id,
                'resolved_by' => $admin->id,
                'exception_type' => 'DOCUMENTATION_ISSUE',
                'severity' => 'LOW',
                'status' => 'RESOLVED',
                'notes' => 'Bill of Lading consignee tax ID corrected prior to arrival.',
                'detected_at' => $now->copy()->subDays(6),
                'resolved_at' => $now->copy()->subDays(5),
            ]);
        }

        // 7. Initial Notifications & Audit Logs
        Notification::firstOrCreate(
            [
                'user_id' => $customer1->id,
                'shipment_id' => $shipmentA->id,
                'title' => 'Delay Alert: CT-2026-A001 (Group A)',
            ],
            [
                'message' => 'Shipment CT-2026-A001 exceeded the 12h delay threshold due to CUSTOMS_HOLD in Singapore.',
                'type' => 'WARNING',
            ]
        );

        Notification::firstOrCreate(
            [
                'user_id' => $customer2->id,
                'shipment_id' => $shipmentB->id,
                'title' => 'High Severity Weather Delay: CT-2026-B001',
            ],
            [
                'message' => 'Shipment CT-2026-B001 is delayed by 28h (Group B threshold: 18h) due to WEATHER_DELAY.',
                'type' => 'DANGER',
            ]
        );

        AuditLog::firstOrCreate(
            [
                'action' => 'SEED_DEMO_DATA',
                'entity_type' => 'Shipment',
                'entity_id' => $shipmentA->id,
            ],
            [
                'actor_id' => $admin->id,
                'metadata' => ['groups' => ['A (12h)', 'B (18h)', 'C (24h)'], 'mode' => 'seaport'],
                'created_at' => $now,
            ]
        );
    }
}