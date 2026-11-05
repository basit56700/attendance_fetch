<?php

namespace Tests\Feature;

use App\Models\AttendanceLog;
use App\Models\AttendanceMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceApiTest extends TestCase
{
    use RefreshDatabase;

    private AttendanceMachine $machineA;

    private AttendanceMachine $machineB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->machineA = AttendanceMachine::create([
            'name' => 'HQ Entrance',
            'ip_address' => '192.168.1.50',
            'port' => 4370,
            'protocol' => 'standalone',
            'serial_number' => 'HQ-SN-001',
            'location' => 'HQ Ground Floor',
            'status' => 'active',
        ]);

        $this->machineB = AttendanceMachine::create([
            'name' => 'F22 Terminal',
            'ip_address' => '172.16.100.191',
            'port' => 80,
            'protocol' => 'adms',
            'serial_number' => 'JJA1261301086',
            'location' => 'Main Gate',
            'status' => 'active',
        ]);

        // Create logs for Machine A
        AttendanceLog::create([
            'attendance_machine_id' => $this->machineA->id,
            'erp_id' => '1001',
            'attendance_time' => '2026-11-05 08:30:00',
            'punch_type' => 0,
            'type_remarks' => 'IN',
            'verification_mode' => 'Fingerprint',
            'sync_status' => 'synced',
        ]);

        AttendanceLog::create([
            'attendance_machine_id' => $this->machineA->id,
            'erp_id' => '1002',
            'attendance_time' => '2026-11-05 08:45:00',
            'punch_type' => 0,
            'type_remarks' => 'IN',
            'verification_mode' => 'Face',
            'sync_status' => 'synced',
        ]);

        // Create log for Machine B
        AttendanceLog::create([
            'attendance_machine_id' => $this->machineB->id,
            'erp_id' => '2001',
            'attendance_time' => '2026-11-05 09:00:00',
            'punch_type' => 0,
            'type_remarks' => 'IN',
            'verification_mode' => 'Fingerprint',
            'sync_status' => 'synced',
        ]);
    }

    public function test_api_returns_all_data_when_no_machine_id_is_provided(): void
    {
        $response = $this->getJson('/api/v1/attendance');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('machine', null)
            ->assertJsonPath('count', 3)
            ->assertJsonCount(3, 'data');
    }

    public function test_api_returns_filtered_data_when_machine_id_route_param_is_provided(): void
    {
        $response = $this->getJson("/api/v1/attendance/{$this->machineA->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('machine.id', $this->machineA->id)
            ->assertJsonPath('machine.name', 'HQ Entrance')
            ->assertJsonPath('count', 2)
            ->assertJsonCount(2, 'data');

        $this->assertEquals('1002', $response->json('data.0.erp_id'));
        $this->assertEquals('1001', $response->json('data.1.erp_id'));
    }

    public function test_api_returns_filtered_data_when_machine_id_query_param_is_provided(): void
    {
        $response = $this->getJson("/api/attendance?machine_id={$this->machineB->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('machine.id', $this->machineB->id)
            ->assertJsonPath('machine.serial_number', 'JJA1261301086')
            ->assertJsonPath('count', 1)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.erp_id', '2001');
    }

    public function test_api_returns_filtered_data_when_id_query_param_is_provided(): void
    {
        $response = $this->getJson("/api/v1/attendance?id={$this->machineA->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('machine.id', $this->machineA->id)
            ->assertJsonPath('count', 2)
            ->assertJsonCount(2, 'data');
    }

    public function test_api_resolves_machine_by_ip_or_serial_identifier(): void
    {
        $response = $this->getJson('/api/v1/attendance?machine=JJA1261301086');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('machine.id', $this->machineB->id)
            ->assertJsonPath('count', 1)
            ->assertJsonPath('data.0.erp_id', '2001');
    }

    public function test_api_returns_404_when_machine_id_not_found(): void
    {
        $response = $this->getJson('/api/v1/attendance/99999');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonCount(0, 'data');
    }

    public function test_api_supports_date_and_limit_filters(): void
    {
        $response = $this->getJson('/api/v1/attendance?limit=1');

        $response->assertStatus(200)
            ->assertJsonPath('count', 1)
            ->assertJsonCount(1, 'data');
    }
}
