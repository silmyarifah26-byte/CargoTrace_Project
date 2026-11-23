<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CargoTraceApiAndLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function createMasterContext(): array
    {
        $admin = User::create([
            'name' => 'Admin CargoTrace',
            'email' => 'admin@cargotrace.test',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $customerA = User::create([
            'name' => 'Customer Alpha',
            'email' => 'alpha@cargotrace.test',
            'password' => Hash::make('password123'),
            'role' => 'customer',
        ]);

        $customerB = User::create([
            'name' => 'Customer Beta',
            'email' => 'beta@cargotrace.test',
            'password' => Hash::make('password123'),
            'role' => 'customer',
        ]);

        $carrier = Carrier::create([
            'code' => 'MSK',
            'name' => 'Maersk Line Global',
            'is_active' => true,
        ]);

        $origin = Location::create([
            'code' => 'IDBLW',
            'name' => 'Port of Belawan',
            'city' => 'Medan',
            'country' => 'Indonesia',
            'latitude' => 3.7889,
            'longitude' => 98.6833,
        ]);

        $destination = Location::create([
            'code' => 'SGSIN',
            'name' => 'Port of Singapore',
            'city' => 'Singapore',
            'country' => 'Singapore',
            'latitude' => 1.2644,
            'longitude' => 103.8220,
        ]);

        return compact('admin', 'customerA', 'customerB', 'carrier', 'origin', 'destination');
    }

    public function test_api_login_and_role_scoped_shipment_access(): void
    {
        $ctx = $this->createMasterContext();

        $this->getJson('/api/shipments')->assertStatus(401);

        $shipmentA = Shipment::create([
            'shipment_number' => 'CT-2026-A001',
            'customer_id' => $ctx['customerA']->id,
            'carrier_id' => $ctx['carrier']->id,
            'origin_location_id' => $ctx['origin']->id,
            'destination_location_id' => $ctx['destination']->id,
            'group_code' => 'A',
            'delay_threshold_hours' => 12,
            'current_status' => 'CREATED',
            'cargo_type' => 'Electronics Cargo',
            'weight_kg' => 1500,
            'expected_arrival_at' => now()->addDays(3),
        ]);

        $shipmentB = Shipment::create([
            'shipment_number' => 'CT-2026-B001',
            'customer_id' => $ctx['customerB']->id,
            'carrier_id' => $ctx['carrier']->id,
            'origin_location_id' => $ctx['origin']->id,
            'destination_location_id' => $ctx['destination']->id,
            'group_code' => 'B',
            'delay_threshold_hours' => 18,
            'current_status' => 'CREATED',
            'cargo_type' => 'Textiles Cargo',
            'weight_kg' => 2200,
            'expected_arrival_at' => now()->addDays(4),
        ]);

        $loginRes = $this->postJson('/api/login', [
            'email' => 'alpha@cargotrace.test',
            'password' => 'password123',
        ]);

        $loginRes->assertStatus(200)
            ->assertJsonStructure(['token', 'user']);

        $token = $loginRes->json('token');

        $listRes = $this->withToken($token)->getJson('/api/shipments');
        $listRes->assertStatus(200);

        $numbers = collect($listRes->json('data'))->pluck('shipment_number')->all();
        $this->assertContains('CT-2026-A001', $numbers);
        $this->assertNotContains('CT-2026-B001', $numbers);

        $this->withToken($token)
            ->getJson("/api/shipments/{$shipmentB->id}")
            ->assertStatus(403);

        $this->withToken($token)
            ->getJson("/api/shipments/{$shipmentA->id}")
            ->assertStatus(200);

        $this->withToken($token)
            ->postJson("/api/shipments/{$shipmentA->id}/tracking", [
                'status' => 'IN_TRANSIT',
                'location_id' => $ctx['destination']->id,
                'description' => 'Customer should not record tracking event.',
            ])
            ->assertStatus(403);
    }

    public function test_lifecycle_transition_and_sla_breach_creates_exception(): void
    {
        $ctx = $this->createMasterContext();

        $shipment = Shipment::create([
            'shipment_number' => 'CT-2026-A900',
            'customer_id' => $ctx['customerA']->id,
            'carrier_id' => $ctx['carrier']->id,
            'origin_location_id' => $ctx['origin']->id,
            'destination_location_id' => $ctx['destination']->id,
            'group_code' => 'A',
            'delay_threshold_hours' => 12,
            'current_status' => 'CREATED',
            'cargo_type' => 'Industrial Parts',
            'weight_kg' => 3100,
            'expected_arrival_at' => now()->subHours(20),
        ]);

        $adminLogin = $this->postJson('/api/login', [
            'email' => 'admin@cargotrace.test',
            'password' => 'password123',
        ])->assertStatus(200);

        $token = $adminLogin->json('token');

        $eventRes = $this->withToken($token)->postJson("/api/shipments/{$shipment->id}/tracking", [
            'status' => 'IN_TRANSIT',
            'location_id' => $ctx['destination']->id,
            'description' => 'Vessel arrived late near port approach.',
        ]);

        $eventRes->assertStatus(201);

        $shipment->refresh();
        $this->assertSame('IN_TRANSIT', $shipment->current_status);
        $this->assertTrue((bool) $shipment->is_delayed);
        $this->assertGreaterThan(0, (int) $shipment->delay_hours);
        $this->assertDatabaseHas('shipment_exceptions', [
            'shipment_id' => $shipment->id,
            'status' => 'open',
        ]);
    }
}