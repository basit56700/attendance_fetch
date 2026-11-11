<?php

namespace Tests\Feature;

use App\Models\AttendanceLog;
use App\Models\AttendanceMachine;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveRefreshAndPunchSequenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_daily_entry_is_check_in_and_second_is_check_out_across_table_and_json(): void
    {
        $user = User::factory()->create();
        $machine = AttendanceMachine::create([
            'name' => 'F22 Main Gate',
            'ip_address' => '172.16.100.191',
            'port' => 4370,
            'protocol' => 'adms',
            'serial_number' => 'JJA1261301086',
            'status' => 'active',
        ]);

        // Both punches arrive with hardware status 0 (typical F22 default when user just scans finger)
        $payload = "time=2026-11-11 09:00:00\tpin=55\tinoutstatus=0\tverifytype=1\r\n"
            ."time=2026-11-11 18:00:00\tpin=55\tinoutstatus=0\tverifytype=1\r\n"
            ."time=2026-11-11 20:00:00\tpin=55\tinoutstatus=0\tverifytype=1\r\n"
            ."time=2026-11-12 09:00:00\tpin=55\tinoutstatus=0\tverifytype=1\r\n";

        $this->call('POST', '/iclock/cdata?SN=JJA1261301086&table=rtlog', [], [], [], ['CONTENT_TYPE' => 'text/plain'], $payload)
            ->assertStatus(200);

        $this->assertDatabaseHas('attendance_logs', [
            'attendance_machine_id' => $machine->id,
            'erp_id' => '55',
            'attendance_time' => '2026-11-11 09:00:00',
            'punch_type' => 0,
            'type_remarks' => 'IN',
        ]);

        $this->assertDatabaseHas('attendance_logs', [
            'attendance_machine_id' => $machine->id,
            'erp_id' => '55',
            'attendance_time' => '2026-11-11 18:00:00',
            'punch_type' => 1,
            'type_remarks' => 'OUT',
        ]);

        $this->assertDatabaseHas('attendance_logs', [
            'attendance_machine_id' => $machine->id,
            'erp_id' => '55',
            'attendance_time' => '2026-11-11 20:00:00',
            'punch_type' => 0,
            'type_remarks' => 'IN',
        ]);

        // Next day's first entry resets back to Check-In (0 / IN)
        $this->assertDatabaseHas('attendance_logs', [
            'attendance_machine_id' => $machine->id,
            'erp_id' => '55',
            'attendance_time' => '2026-11-12 09:00:00',
            'punch_type' => 0,
            'type_remarks' => 'IN',
        ]);

        // Reflected in HTML table
        $tableRes = $this->actingAs($user)->get('/attendance');
        $tableRes->assertStatus(200)
            ->assertSee('Check-In (IN)', false)
            ->assertSee('Check-Out (OUT)', false);

        // Reflected in JSON REST API
        $apiRes = $this->getJson('/api/v1/attendance?erp_id=55&date=2026-11-11');
        $apiRes->assertStatus(200)
            ->assertJsonPath('count', 3)
            ->assertJsonPath('data.0.attendance_time', '2026-11-11 20:00:00')
            ->assertJsonPath('data.0.punch_type', 0)
            ->assertJsonPath('data.0.type_remarks', 'IN')
            ->assertJsonPath('data.0.punch_label', 'Check-In')
            ->assertJsonPath('data.1.attendance_time', '2026-11-11 18:00:00')
            ->assertJsonPath('data.1.punch_type', 1)
            ->assertJsonPath('data.1.type_remarks', 'OUT')
            ->assertJsonPath('data.1.punch_label', 'Check-Out')
            ->assertJsonPath('data.2.attendance_time', '2026-11-11 09:00:00')
            ->assertJsonPath('data.2.punch_type', 0)
            ->assertJsonPath('data.2.type_remarks', 'IN')
            ->assertJsonPath('data.2.punch_label', 'Check-In');
    }

    public function test_out_of_order_punch_arrival_resequences_chronologically(): void
    {
        $machine = AttendanceMachine::create([
            'name' => 'F22 Gate',
            'ip_address' => '172.16.100.191',
            'port' => 4370,
            'protocol' => 'adms',
            'serial_number' => 'F22SEQ99',
            'status' => 'active',
        ]);

        // Later evening punch arrives first
        $this->call('POST', '/iclock/cdata?SN=F22SEQ99&table=rtlog', [], [], [], ['CONTENT_TYPE' => 'text/plain'], "time=2026-11-11 17:30:00\tpin=77\tinoutstatus=0\tverifytype=1\r\n")
            ->assertStatus(200);

        $this->assertDatabaseHas('attendance_logs', [
            'attendance_machine_id' => $machine->id,
            'erp_id' => '77',
            'attendance_time' => '2026-11-11 17:30:00',
            'punch_type' => 0,
            'type_remarks' => 'IN',
        ]);

        // Earlier morning punch syncs afterwards -> morning becomes IN, evening flips to OUT
        $this->call('POST', '/iclock/cdata?SN=F22SEQ99&table=rtlog', [], [], [], ['CONTENT_TYPE' => 'text/plain'], "time=2026-11-11 08:45:00\tpin=77\tinoutstatus=0\tverifytype=1\r\n")
            ->assertStatus(200);

        $this->assertDatabaseHas('attendance_logs', [
            'erp_id' => '77',
            'attendance_time' => '2026-11-11 08:45:00',
            'punch_type' => 0,
            'type_remarks' => 'IN',
        ]);
        $this->assertDatabaseHas('attendance_logs', [
            'erp_id' => '77',
            'attendance_time' => '2026-11-11 17:30:00',
            'punch_type' => 1,
            'type_remarks' => 'OUT',
        ]);
    }

    public function test_live_endpoints_return_expected_json_for_dashboard_attendance_and_machines(): void
    {
        $user = User::factory()->create();
        $machine = AttendanceMachine::create([
            'name' => 'F22 Live',
            'ip_address' => '172.16.100.191',
            'port' => 4370,
            'protocol' => 'adms',
            'serial_number' => 'F22LIVE1',
            'status' => 'active',
        ]);

        $this->call(
            'POST',
            '/iclock/cdata?SN=F22LIVE1&table=rtlog',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'text/plain'],
            "time=2026-11-11 09:00:00\tpin=12\tinoutstatus=0\tverifytype=1\r\ntime=2026-11-11 17:00:00\tpin=12\tinoutstatus=0\tverifytype=1\r\n"
        )->assertStatus(200);

        $dashRes = $this->actingAs($user)->getJson('/?live=1');
        $dashRes->assertStatus(200)
            ->assertJsonPath('stats.total_punches', 2)
            ->assertJsonPath('recent_logs.0.punch_label', 'Check-Out')
            ->assertJsonPath('recent_logs.1.punch_label', 'Check-In');

        $attRes = $this->actingAs($user)->getJson('/attendance?live=1&erp_id=12');
        $attRes->assertStatus(200)
            ->assertJsonPath('total', 2)
            ->assertJsonPath('logs.0.punch_type', 1)
            ->assertJsonPath('logs.0.type_remarks', 'OUT')
            ->assertJsonPath('logs.1.punch_type', 0)
            ->assertJsonPath('logs.1.type_remarks', 'IN');

        $machRes = $this->actingAs($user)->getJson('/machines?live=1');
        $machRes->assertStatus(200)
            ->assertJsonPath('machines.0.id', $machine->id)
            ->assertJsonPath('machines.0.attendance_logs_count', 2);
    }

    public function test_settings_update_saves_ui_refresh_interval_and_resequences_historical_logs(): void
    {
        $user = User::factory()->create();
        $machine = AttendanceMachine::create([
            'name' => 'Test Mach',
            'ip_address' => '192.168.1.90',
            'port' => 4370,
            'protocol' => 'auto',
            'status' => 'active',
        ]);

        AttendanceLog::create([
            'attendance_machine_id' => $machine->id,
            'erp_id' => '99',
            'attendance_time' => '2026-11-11 08:00:00',
            'punch_type' => 0,
            'type_remarks' => 'IN',
            'verification_mode' => 'Fingerprint',
        ]);
        AttendanceLog::create([
            'attendance_machine_id' => $machine->id,
            'erp_id' => '99',
            'attendance_time' => '2026-11-11 16:00:00',
            'punch_type' => 0,
            'type_remarks' => 'IN',
            'verification_mode' => 'Fingerprint',
        ]);

        $res = $this->actingAs($user)->post('/settings', [
            'app_title' => 'GCS Attendance ERP',
            'ui_refresh_interval' => '10',
            'auto_fetch_enabled' => '1',
            'auto_fetch_interval' => '5',
            'punch_sequence_mode' => 'alternating',
            'resequence_now' => '1',
            'clear_device_logs' => '0',
            'grace_period_minutes' => '15',
        ]);

        $res->assertRedirect();
        $this->assertEquals('10', SystemSetting::get('ui_refresh_interval'));
        $this->assertEquals('alternating', SystemSetting::get('punch_sequence_mode'));

        $this->assertDatabaseHas('attendance_logs', [
            'erp_id' => '99',
            'attendance_time' => '2026-11-11 16:00:00',
            'punch_type' => 1,
            'type_remarks' => 'OUT',
        ]);
    }
}
