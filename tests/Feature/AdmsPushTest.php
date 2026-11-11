<?php

namespace Tests\Feature;

use App\Models\AttendanceLog;
use App\Models\AttendanceMachine;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdmsPushTest extends TestCase
{
    use RefreshDatabase;

    public function test_adms_handshake_returns_device_options(): void
    {
        $response = $this->get('/iclock/cdata?SN=F22TEST1234&options=all&pushver=2.4.1');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertStringContainsString('GET OPTION FROM: F22TEST1234', $response->getContent());
        $this->assertStringContainsString('Stamp=0', $response->getContent());

        $this->assertDatabaseHas('attendance_machines', [
            'serial_number' => 'F22TEST1234',
            'protocol' => 'adms',
            'status' => 'active',
        ]);
    }

    public function test_adms_attlog_push_stores_punches_safely(): void
    {
        SystemSetting::set('punch_sequence_mode', 'device');

        $machine = AttendanceMachine::create([
            'name' => 'F22 Test Device',
            'ip_address' => '172.16.100.191',
            'port' => 4370,
            'protocol' => 'adms',
            'serial_number' => 'F22SN999',
            'location' => 'Main Gate',
            'status' => 'active',
        ]);

        $payload = "101\t2026-11-05 18:30:00\t0\t1\t0\t0\r\n102\t2026-11-05 18:35:00\t1\t1\t0\t0\r\n";

        $response = $this->call(
            'POST',
            '/iclock/cdata?SN=F22SN999&table=ATTLOG',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'text/plain'],
            $payload
        );

        $response->assertStatus(200);
        $this->assertEquals('OK: 2', trim($response->getContent()));

        $this->assertDatabaseHas('attendance_logs', [
            'attendance_machine_id' => $machine->id,
            'erp_id' => '101',
            'attendance_time' => '2026-11-05 18:30:00',
            'punch_type' => 0,
            'type_remarks' => 'IN',
            'verification_mode' => 'Fingerprint',
        ]);

        $this->assertDatabaseHas('attendance_logs', [
            'attendance_machine_id' => $machine->id,
            'erp_id' => '102',
            'attendance_time' => '2026-11-05 18:35:00',
            'punch_type' => 1,
            'type_remarks' => 'OUT',
            'verification_mode' => 'Fingerprint',
        ]);
    }

    public function test_adms_attlog_duplicate_punches_are_ignored(): void
    {
        $machine = AttendanceMachine::create([
            'name' => 'F22 Test Device',
            'ip_address' => '172.16.100.191',
            'port' => 4370,
            'protocol' => 'adms',
            'serial_number' => 'F22SN888',
            'location' => 'Main Gate',
            'status' => 'active',
        ]);

        $payload = "201\t2026-11-05 18:40:00\t0\t1\t0\t0\r\n";

        // First push
        $res1 = $this->call('POST', '/iclock/cdata?SN=F22SN888&table=ATTLOG', [], [], [], ['CONTENT_TYPE' => 'text/plain'], $payload);
        $this->assertEquals('OK: 1', trim($res1->getContent()));

        // Second push of exact same record
        $res2 = $this->call('POST', '/iclock/cdata?SN=F22SN888&table=ATTLOG', [], [], [], ['CONTENT_TYPE' => 'text/plain'], $payload);
        $this->assertEquals('OK: 0', trim($res2->getContent()));

        $this->assertEquals(1, AttendanceLog::where('erp_id', '201')->count());
    }

    public function test_adms_getrequest_returns_ok(): void
    {
        $response = $this->get('/iclock/getrequest?SN=F22TEST1234');
        $response->assertStatus(200);
        $this->assertEquals('OK', trim($response->getContent()));
    }

    public function test_adms_ping_updates_machine_and_returns_ok(): void
    {
        $machine = AttendanceMachine::create([
            'name' => 'F22 Ping Device',
            'ip_address' => '172.16.100.191',
            'port' => 4370,
            'protocol' => 'auto',
            'serial_number' => 'JJA1261301086',
            'status' => 'active',
        ]);

        $response = $this->get('/iclock/ping?SN=JJA1261301086');
        $response->assertStatus(200);
        $this->assertEquals('OK', trim($response->getContent()));

        $machine->refresh();
        $this->assertEquals('adms', $machine->protocol);
        $this->assertNotNull($machine->last_connected_at);
    }

    public function test_adms_options_updates_firmware_version_and_rtstate_does_not_create_logs(): void
    {
        $machine = AttendanceMachine::create([
            'name' => 'F22 Terminal',
            'ip_address' => '172.16.100.191',
            'port' => 4370,
            'protocol' => 'adms',
            'serial_number' => 'F22OPT123',
            'status' => 'active',
        ]);

        $optionsBody = '~DeviceName=F22,FWVersion=Ver 8.0.4.3-20240116,PushVersion=Ver 2.0.33S-20220623';
        $optRes = $this->call('POST', '/iclock/cdata?SN=F22OPT123&table=options', [], [], [], ['CONTENT_TYPE' => 'text/plain'], $optionsBody);
        $optRes->assertStatus(200);
        $this->assertEquals('OK', trim($optRes->getContent()));

        $machine->refresh();
        $this->assertEquals('Ver 8.0.4.3-20240116', $machine->firmware_version);

        $rtstateBody = "time=2026-11-11 13:51:35\tsensor=01\trelay=00\tdoor=01";
        $stateRes = $this->call('POST', '/iclock/cdata?SN=F22OPT123&table=rtstate', [], [], [], ['CONTENT_TYPE' => 'text/plain'], $rtstateBody);
        $stateRes->assertStatus(200);
        $this->assertEquals('OK', trim($stateRes->getContent()));

        $this->assertEquals(0, AttendanceLog::count());
    }

    public function test_adms_rtlog_ignores_pin_zero_tamper_alarms_and_stores_valid_punches(): void
    {
        $machine = AttendanceMachine::create([
            'name' => 'F22 Terminal',
            'ip_address' => '172.16.100.191',
            'port' => 4370,
            'protocol' => 'adms',
            'serial_number' => 'F22RTLOG1',
            'status' => 'active',
        ]);

        $payload = "time=2026-11-11 14:00:00\tpin=0\tevent=27\tinoutstatus=0\tverifytype=1\r\n"
            ."time=2026-11-11 14:01:00\tpin=5\tevent=0\tinoutstatus=0\tverifytype=1\r\n";

        $response = $this->call('POST', '/iclock/cdata?SN=F22RTLOG1&table=rtlog', [], [], [], ['CONTENT_TYPE' => 'text/plain'], $payload);
        $response->assertStatus(200);
        $this->assertEquals('OK: 1', trim($response->getContent()));

        $this->assertEquals(1, AttendanceLog::count());
        $this->assertDatabaseHas('attendance_logs', [
            'attendance_machine_id' => $machine->id,
            'erp_id' => '5',
            'attendance_time' => '2026-11-11 14:01:00',
        ]);
    }

    public function test_machine_store_and_update_allow_adms_protocol(): void
    {
        $user = User::factory()->create();

        $storeRes = $this->actingAs($user)->post('/machines', [
            'name' => 'F22 Door',
            'ip_address' => '172.16.100.191',
            'port' => 4370,
            'protocol' => 'adms',
            'comm_key' => 0,
            'location' => 'Lobby',
            'status' => 'active',
        ]);
        $storeRes->assertRedirect('/machines');
        $this->assertDatabaseHas('attendance_machines', [
            'name' => 'F22 Door',
            'protocol' => 'adms',
        ]);

        $machine = AttendanceMachine::where('name', 'F22 Door')->firstOrFail();
        $machine->update(['protocol' => 'auto', 'last_error' => 'Timed out on 4370', 'firmware_version' => null]);

        $updateRes = $this->actingAs($user)->put("/machines/{$machine->id}", [
            'name' => '172.16.100.191',
            'ip_address' => '172.16.100.191',
            'port' => 4370,
            'protocol' => 'adms',
            'comm_key' => 0,
            'serial_number' => 'SN17216100191',
            'location' => '172.16.100.191',
            'status' => 'active',
        ]);
        $updateRes->assertRedirect('/machines');
        $this->assertDatabaseHas('attendance_machines', [
            'id' => $machine->id,
            'name' => '172.16.100.191',
            'protocol' => 'adms',
            'serial_number' => 'SN17216100191',
            'last_error' => null,
            'firmware_version' => 'ADMS / Push Terminal',
        ]);

        // Polling/pull endpoints should gracefully return info without overwriting last_error
        $this->actingAs($user)->post('/machines/fetch-all')->assertSessionHas('info');
        $this->actingAs($user)->post("/machines/{$machine->id}/fetch")->assertSessionHas('info');
        $this->actingAs($user)->post("/machines/{$machine->id}/sync-time")->assertSessionHas('info');
        $this->actingAs($user)->post("/machines/{$machine->id}/clear")->assertSessionHas('info');
    }
}
