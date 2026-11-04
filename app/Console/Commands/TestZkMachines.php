<?php

namespace App\Console\Commands;

use App\Models\AttendanceMachine;
use App\Support\ZKLibrary;
use Illuminate\Console\Command;

class TestZkMachines extends Command
{
    protected $signature = 'zk:test {ip? : Optional machine IP to test}';

    protected $description = 'Safely tests network connectivity, handshake, and fetches attendance punches without deleting any data';

    public function handle(): int
    {
        $targetIp = $this->argument('ip');

        if ($targetIp) {
            $machines = AttendanceMachine::where('ip_address', $targetIp)->get();
            if ($machines->isEmpty()) {
                $machines = collect([(object) [
                    'id' => 0,
                    'name' => 'Custom Target',
                    'ip_address' => $targetIp,
                    'port' => 4370,
                    'location' => 'CLI Manual Target',
                ]]);
            }
        } else {
            $machines = AttendanceMachine::all();
            if ($machines->isEmpty()) {
                $this->warn('No machines in database. Testing known production defaults...');
                $machines = collect([
                    (object) ['id' => 1, 'name' => 'Noc and Support Machine', 'ip_address' => '172.16.50.35', 'port' => 4370, 'location' => '44 P'],
                    (object) ['id' => 2, 'name' => 'Fiber Machine', 'ip_address' => '172.16.50.36', 'port' => 4370, 'location' => 'Fiber Dept'],
                    (object) ['id' => 3, 'name' => '43-Q', 'ip_address' => '172.16.50.37', 'port' => 4370, 'location' => 'Office 43-Q'],
                    (object) ['id' => 4, 'name' => 'Fortune Office 601', 'ip_address' => '172.16.50.38', 'port' => 4370, 'location' => 'Fortune Centre 601'],
                    (object) ['id' => 5, 'name' => 'DHA Innovista', 'ip_address' => '172.16.50.39', 'port' => 4370, 'location' => 'DHA Innovista'],
                    (object) ['id' => 6, 'name' => 'Korangi Warehouse', 'ip_address' => '172.16.50.34', 'port' => 4370, 'location' => 'Korangi Warehouse'],
                ]);
            }
        }

        $this->info('===================================================================');
        $this->info('   ZKTeco Biometric Machine Safe Diagnostic Tester');
        $this->info('   SAFE MODE: Never clears attendance memory on physical machines');
        $this->info('===================================================================');
        $this->line('Testing '.$machines->count()." machine(s)...\n");

        $success = 0;
        $failed = 0;

        foreach ($machines as $m) {
            $ip = $m->ip_address;
            $port = (int) $m->port;
            $name = $m->name;

            $this->line('-------------------------------------------------------------------');
            $this->comment(">> [Machine #{$m->id}] {$name} ({$ip}:{$port})");
            $this->line('-------------------------------------------------------------------');

            // 1. TCP Port Probe (5s)
            $this->write(' [1/3] Probing TCP port 4370 (5s timeout)... ');
            $start = microtime(true);
            $errNo = 0;
            $errStr = '';
            $fp = @fsockopen($ip, $port, $errNo, $errStr, 5);

            if ($fp) {
                $latency = round((microtime(true) - $start) * 1000, 1);
                fclose($fp);
                $this->info("PORT REACHABLE! ({$latency}ms)");
            } else {
                $this->error("UNREACHABLE ({$errStr})");
                $failed++;

                continue;
            }

            // 2. ZKLibrary UDP Connect
            $this->write(' [2/3] Connecting via ZK UDP protocol... ');
            try {
                $zk = new ZKLibrary($ip, $port);
                $zk->setTimeout(6, 0);
                if ($zk->connect()) {
                    $this->info('CONNECTED!');
                    $version = $zk->getVersion();
                    if ($version) {
                        $this->line('       • Firmware: '.trim($version));
                    }
                    $devTime = $zk->getTime();
                    if ($devTime) {
                        $this->line("       • Device RTC Time: {$devTime}");
                    }
                } else {
                    $err = property_exists($zk, 'last_error') ? $zk->last_error : 'No response from device';
                    $this->error("HANDSHAKE FAILED ({$err})");
                    $failed++;

                    continue;
                }
            } catch (\Throwable $e) {
                $this->error('EXCEPTION: '.$e->getMessage());
                $failed++;

                continue;
            }

            // 3. Fetch Punches Safely
            $this->write(' [3/3] Fetching punches (NO deletion)... ');
            try {
                $attendance = $zk->getAttendance();
                if (is_array($attendance)) {
                    $count = count($attendance);
                    $this->info("SUCCESS! ({$count} punches found)");

                    if ($count > 0) {
                        $sample = array_slice($attendance, -5);
                        $rows = [];
                        foreach ($sample as $idx => $p) {
                            $state = (int) $p[2];
                            $type = match ($state) {
                                0 => 'IN',
                                1 => 'OUT',
                                2 => 'Break OUT',
                                3 => 'Break IN',
                                default => "State {$state}",
                            };
                            $rows[] = [$idx + 1, trim($p[1]), $type, $p[3]];
                        }
                        $this->table(['#', 'Employee ID', 'Punch Type', 'Timestamp'], $rows);
                    }
                    $success++;
                } else {
                    $this->warn('Unexpected data format returned.');
                    $failed++;
                }
            } catch (\Throwable $e) {
                $this->error('Error reading punches: '.$e->getMessage());
                $failed++;
            } finally {
                $zk->disconnect();
                $this->line("       ✓ Disconnected cleanly.\n");
            }
        }

        $this->info('===================================================================');
        $this->info("Summary: {$success} Passed, {$failed} Failed. Data safety guaranteed.");
        $this->info('===================================================================');

        return $failed > 0 ? 1 : 0;
    }
}
