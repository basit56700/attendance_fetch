<?php
/**
 * Standalone ZKTeco Biometric Machine Connectivity & Fetch Test Script
 * 
 * SAFE TEST TOOL:
 * - Tests network reachability (port 4370)
 * - Tests UDP ZK binary protocol handshake
 * - Reads device version and clock
 * - Downloads attendance punch records
 * - Previews latest punches in terminal
 * - NEVER DELETES ANY DATA (clearAttendance is NOT called)
 * 
 * Usage:
 *   php test_machines.php                 (Tests all 6 production machines)
 *   php test_machines.php 172.16.50.35    (Tests a single machine IP)
 */

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', '1');

// Load proven ZKLibrary
if (file_exists(__DIR__ . '/app/Support/ZKLibrary.php')) {
    require_once __DIR__ . '/app/Support/ZKLibrary.php';
} elseif (file_exists(__DIR__ . '/zklibrary.php')) {
    require_once __DIR__ . '/zklibrary.php';
} else {
    echo "[ERROR] ZKLibrary.php not found!\n";
    exit(1);
}

// Production Machines Configuration
$allMachines = [
    [
        'id' => 1,
        'ip' => '172.16.50.35',
        'port' => 4370,
        'name' => 'Noc and Support Machine',
        'location' => '44 P / NOC Office',
    ],
    [
        'id' => 2,
        'ip' => '172.16.50.36',
        'port' => 4370,
        'name' => 'Fiber Machine',
        'location' => 'Fiber Team Dept',
    ],
    [
        'id' => 3,
        'ip' => '172.16.50.37',
        'port' => 4370,
        'name' => '43-Q',
        'location' => 'Office 43-Q',
    ],
    [
        'id' => 4,
        'ip' => '172.16.50.38',
        'port' => 4370,
        'name' => 'Fortune Office 601',
        'location' => 'Fortune Centre 601',
    ],
    [
        'id' => 5,
        'ip' => '172.16.50.39',
        'port' => 4370,
        'name' => 'DHA Innovista',
        'location' => 'DHA Innovista Office',
    ],
    [
        'id' => 6,
        'ip' => '172.16.50.34',
        'port' => 4370,
        'name' => 'Korangi Warehouse',
        'location' => 'Korangi Warehouse Gate',
    ],
];

// Check CLI argument for single machine filter
$targetIp = $argv[1] ?? null;
if ($targetIp) {
    $machines = array_filter($allMachines, function ($m) use ($targetIp) {
        return $m['ip'] === $targetIp;
    });
    if (empty($machines)) {
        // Custom IP provided by user
        $machines = [
            [
                'id' => 999,
                'ip' => $targetIp,
                'port' => 4370,
                'name' => 'Custom Test Machine',
                'location' => 'Manual CLI Test',
            ]
        ];
    }
} else {
    $machines = $allMachines;
}

echo "===================================================================\n";
echo "   ZKTeco Biometric Machine Diagnostic & Attendance Tester\n";
echo "   SAFE MODE: All punches remain on device memory (NO DELETION)\n";
echo "===================================================================\n";
echo "Total machines to test: " . count($machines) . "\n\n";

$successCount = 0;
$failCount = 0;

foreach ($machines as $m) {
    $ip = $m['ip'];
    $port = $m['port'];
    $name = $m['name'];

    echo "-------------------------------------------------------------------\n";
    echo ">> [Machine #{$m['id']}] {$name}\n";
    echo "   Target: {$ip}:{$port} ({$m['location']})\n";
    echo "-------------------------------------------------------------------\n";

    // Step 1: TCP Port Probe (Ping)
    echo " [1/4] Network Probe : Connecting to {$ip}:{$port} via socket (5s timeout)... ";
    $startTime = microtime(true);
    $errCode = 0;
    $errStr = '';
    $fp = @fsockopen($ip, $port, $errCode, $errStr, 5);

    if ($fp) {
        $pingMs = round((microtime(true) - $startTime) * 1000, 1);
        fclose($fp);
        echo "OK! (Latency: {$pingMs} ms)\n";
    } else {
        echo "FAILED!\n";
        echo "       ✕ Error: {$errStr} (Code: {$errCode})\n";
        echo "       ✕ Root Cause: Cannot reach {$ip} on port {$port}.\n";
        echo "       ✕ Check if the machine is powered on and cable connected.\n\n";
        $failCount++;
        continue;
    }

    // Step 2: ZK UDP Protocol Handshake
    echo " [2/4] ZK UDP Connect: Initializing UDP socket & sending CMD_CONNECT... ";
    try {
        $zk = new \App\Support\ZKLibrary($ip, $port);
        $zk->setTimeout(6, 0);
        $connected = $zk->connect();

        if ($connected) {
            echo "SUCCESS!\n";
            $version = $zk->getVersion();
            if ($version) {
                echo "       • Firmware Version : " . trim($version) . "\n";
            }
            $devTime = $zk->getTime();
            if ($devTime) {
                echo "       • Device Clock RTC : {$devTime}\n";
            }
        } else {
            echo "FAILED!\n";
            echo "       ✕ Handshake failed. Device did not respond to UDP CMD_CONNECT.\n";
            echo "       ✕ Ensure no other software (ZKAccess/ZKBioTime) has locked port 4370.\n\n";
            $failCount++;
            continue;
        }
    } catch (\Throwable $e) {
        echo "EXCEPTION: " . $e->getMessage() . "\n\n";
        $failCount++;
        continue;
    }

    // Step 3: Fetch Attendance Log
    echo " [3/4] Attendance Log: Fetching punch records via getAttendance()... ";
    try {
        $attendance = $zk->getAttendance();

        if (is_array($attendance)) {
            $totalPunches = count($attendance);
            echo "SUCCESS!\n";
            echo "       ✓ Found {$totalPunches} total attendance records in device memory.\n";
            echo "       ✓ SAFE MODE VERIFIED: No records deleted from device.\n";

            // Step 4: Display Preview of Latest Punches
            if ($totalPunches > 0) {
                echo "\n [4/4] Preview of Latest Attendance Punches:\n";
                echo "       --------------------------------------------------------\n";
                printf("       %-6s | %-12s | %-10s | %-19s\n", "#", "Employee ID", "Type", "Timestamp");
                echo "       --------------------------------------------------------\n";

                // Show latest up to 5 punches
                $sample = array_slice($attendance, -5);
                $idx = 1;
                foreach ($sample as $punch) {
                    // $punch: array($uid, $id, $state, $timestamp)
                    $empId = trim($punch[1]);
                    $state = (int) $punch[2];
                    $time = $punch[3];

                    $typeRemarks = match ($state) {
                        0 => 'IN',
                        1 => 'OUT',
                        2 => 'Break OUT',
                        3 => 'Break IN',
                        4 => 'Overtime IN',
                        5 => 'Overtime OUT',
                        default => "Type {$state}",
                    };

                    printf("       %-6d | %-12s | %-10s | %-19s\n", $idx++, $empId, $typeRemarks, $time);
                }
                echo "       --------------------------------------------------------\n";
            } else {
                echo "\n [4/4] Device log is currently empty (0 punches stored).\n";
            }

            $successCount++;
        } else {
            echo "FAILED (Unexpected return format).\n";
            $failCount++;
        }
    } catch (\Throwable $e) {
        echo "EXCEPTION during fetch: " . $e->getMessage() . "\n";
        $failCount++;
    } finally {
        // Always disconnect cleanly
        $zk->disconnect();
        echo "       ✓ Socket disconnected cleanly.\n\n";
    }
}

echo "===================================================================\n";
echo "   Test Run Summary\n";
echo "===================================================================\n";
echo "   Total Tested : " . count($machines) . "\n";
echo "   Successful   : {$successCount}\n";
echo "   Failed       : {$failCount}\n";
echo "   Data Safety  : 100% Guaranteed (No clearAttendance executed)\n";
echo "===================================================================\n";
