<?php

namespace Tests\Feature;

use App\Models\AttendanceLog;
use App\Models\AttendanceMachine;
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
}
