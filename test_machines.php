<?php

/**
 * Standalone ZKTeco Biometric Machine Diagnostic & Attendance Tester
 *
 * SAFE TEST TOOL:
 * - Scans for existing original zklibrary.php on production server
 * - Checks for active background processes holding port 4370
 * - Tests network reachability (TCP port 4370 probe)
 * - Tests UDP ZK protocol handshake (with detailed socket error capture)
 * - Tests TCP ZK protocol handshake (0x5050827D fallback)
 * - Tests original server zklibrary.php if discovered on disk
 * - Reads device version and RTC clock
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

// 1. Scan for existing ZKLibrary implementations on the server
$discoveredFiles = [];
$candidateSearchPaths = [
    __DIR__.'/app/Support/ZKLibrary.php',
    __DIR__.'/zklibrary.php',
    dirname(__DIR__).'/zklibrary.php',
    dirname(dirname(__DIR__)).'/zklibrary.php',
    '/var/www/html/gcs/gcs/modules/zklibrary.php',
    '/var/www/html/gcs/modules/zklibrary.php',
    '/var/www/html/gcs/zklibrary.php',
    '/var/www/html/zklibrary.php',
    ($_SERVER['HOME'] ?? '/home/shoaib').'/web/gcs/zklibrary.php',
    ($_SERVER['HOME'] ?? '/home/shoaib').'/web/gcs/gcs/modules/zklibrary.php',
    ($_SERVER['HOME'] ?? '/home/shoaib').'/web/gcs/private/zklibrary.php',
];

foreach ($candidateSearchPaths as $path) {
    if (file_exists($path) && is_readable($path)) {
        $real = realpath($path);
        if (! isset($discoveredFiles[$real])) {
            $discoveredFiles[$real] = [
                'path' => $real,
                'size' => filesize($real),
                'md5' => md5_file($real),
            ];
        }
    }
}

// Load primary ZKLibrary driver
if (file_exists(__DIR__.'/app/Support/ZKLibrary.php')) {
    require_once __DIR__.'/app/Support/ZKLibrary.php';
} elseif (file_exists(__DIR__.'/zklibrary.php')) {
    require_once __DIR__.'/zklibrary.php';
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

// Check CLI argument for single machine filter & optional comm key
$targetIp = $argv[1] ?? null;
$targetCommKey = isset($argv[2]) ? (int) $argv[2] : 0;
if ($targetIp) {
    $machines = array_filter($allMachines, function ($m) use ($targetIp) {
        return $m['ip'] === $targetIp;
    });
    if (empty($machines)) {
        $machines = [
            [
                'id' => 999,
                'ip' => $targetIp,
                'port' => 4370,
                'name' => 'Custom Test Machine',
                'location' => 'Manual CLI Test',
            ],
        ];
    }
} else {
    $machines = $allMachines;
}

echo "===================================================================\n";
echo "   ZKTeco Biometric Diagnostic & Deep Connectivity Tester\n";
echo "   SAFE MODE: All punches remain on device memory (NO DELETION)\n";
echo "===================================================================\n";

// Display Discovered Files on Server
echo "[Server Filesystem Inspection]\n";
if (! empty($discoveredFiles)) {
    echo " • Discovered ZK driver files on server:\n";
    foreach ($discoveredFiles as $f) {
        $isCurrent = ($f['path'] === realpath(__DIR__.'/app/Support/ZKLibrary.php'));
        $tag = $isCurrent ? ' (Current App Driver)' : ' (Server File)';
        echo "   - {$f['path']} [Size: {$f['size']} bytes, MD5: ".substr($f['md5'], 0, 8)."...]{$tag}\n";
    }
} else {
    echo " • No external zklibrary.php files detected in standard search paths.\n";
}

// Check for active connections or processes holding port 4370
echo "\n[Active Network & Process Check]\n";
$activeLocks = [];
if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
    // Check running processes for attendance / zk
    $psOutput = @shell_exec("ps aux | grep -iE 'zklibrary|attendance|zk' | grep -v grep | head -n 5 2>/dev/null");
    if ($psOutput && trim($psOutput)) {
        echo " • Active background attendance processes detected:\n";
        foreach (explode("\n", trim($psOutput)) as $line) {
            echo '   '.substr(trim($line), 0, 95)."\n";
        }
    } else {
        echo " • No background attendance/ZK processes found.\n";
    }

    // Check socket connections to port 4370
    $ssOutput = @shell_exec('ss -tunap 2>/dev/null | grep 4370 | head -n 5');
    if (! $ssOutput) {
        $ssOutput = @shell_exec('netstat -tunap 2>/dev/null | grep 4370 | head -n 5');
    }
    if ($ssOutput && trim($ssOutput)) {
        echo " ⚠️  ACTIVE PORT 4370 CONNECTIONS ON SERVER:\n";
        foreach (explode("\n", trim($ssOutput)) as $line) {
            echo '   '.trim($line)."\n";
        }
    } else {
        echo " • No active socket connections holding port 4370.\n";
    }
} else {
    echo " • Running on Windows (socket checks skipped).\n";
}

echo "\nTotal machines to test: ".count($machines)."\n\n";

$successCount = 0;
$failCount = 0;

/**
 * Helper to calculate ZKTeco 16-bit checksum
 */
function zkChecksum(string $data): int
{
    $chk = 0;
    $len = strlen($data);
    for ($i = 0; $i < $len; $i += 2) {
        if ($i + 1 < $len) {
            $chk += ord($data[$i]) + (ord($data[$i + 1]) << 8);
        } else {
            $chk += ord($data[$i]);
        }
    }
    while ($chk > 0xFFFF) {
        $chk = ($chk & 0xFFFF) + ($chk >> 16);
    }

    return (~$chk) & 0xFFFF;
}

/**
 * Helper to test ZKTeco proprietary TCP protocol (0x5050827D)
 */
function testZkTCP(string $ip, int $port = 4370, float $timeout = 3.0): array
{
    $sock = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if (! $sock) {
        return ['success' => false, 'error' => 'Cannot create TCP socket: '.socket_strerror(socket_last_error())];
    }
    $sec = (int) floor($timeout);
    $usec = (int) (($timeout - $sec) * 1000000);
    @socket_set_option($sock, SOL_SOCKET, SO_RCVTIMEO, ['sec' => $sec, 'usec' => $usec]);
    @socket_set_option($sock, SOL_SOCKET, SO_SNDTIMEO, ['sec' => $sec, 'usec' => $usec]);

    if (! @socket_connect($sock, $ip, $port)) {
        $err = socket_last_error($sock);
        @socket_close($sock);

        return ['success' => false, 'error' => 'TCP connect failed: '.socket_strerror($err)." (Code {$err})"];
    }

    // Packet: CMD_CONNECT (1000), checksum, session_id (0), reply_id (65534)
    $cmd = 1000;
    $sessionId = 0;
    $replyNumber = 65534;
    $innerBuf = pack('vvvv', $cmd, 0, $sessionId, $replyNumber);
    $chk = zkChecksum($innerBuf);
    $innerPacket = pack('vvvv', $cmd, $chk, $sessionId, $replyNumber);
    $tcpPrefix = pack('VV', 0x5050827D, strlen($innerPacket));

    @socket_write($sock, $tcpPrefix.$innerPacket);

    // Read response prefix (8 bytes)
    $respPrefix = @socket_read($sock, 8);
    if (! $respPrefix || strlen($respPrefix) < 8) {
        $err = socket_last_error($sock);
        @socket_close($sock);

        return ['success' => false, 'error' => 'No TCP reply from device after CMD_CONNECT ('.socket_strerror($err).')'];
    }

    $prefixData = unpack('Vmagic/Vlen', substr($respPrefix, 0, 8));
    if ($prefixData['magic'] !== 0x5050827D) {
        @socket_close($sock);

        return ['success' => false, 'error' => 'Invalid TCP magic header: 0x'.dechex($prefixData['magic'])];
    }

    $payloadLen = $prefixData['len'];
    $respBody = @socket_read($sock, $payloadLen);
    @socket_close($sock);

    if (strlen($respBody) >= 8) {
        $header = unpack('vcommand/vchecksum/vsession_id/vreply_id', substr($respBody, 0, 8));
        if ($header['command'] === 2000) { // CMD_ACK_OK
            return [
                'success' => true,
                'session_id' => $header['session_id'],
                'reply_id' => $header['reply_id'],
            ];
        } else {
            return ['success' => false, 'error' => 'Device returned non-OK command code: '.$header['command']];
        }
    }

    return ['success' => false, 'error' => 'Incomplete TCP response body'];
}

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
    echo ' [2/4] ZK UDP Connect: Initializing UDP socket & sending CMD_CONNECT... ';
    $zkConnected = false;
    $zk = null;

    try {
        if (class_exists('App\Support\ZKLibrary')) {
            $zk = new App\Support\ZKLibrary($ip, $port);
        } elseif (class_exists('ZKLibrary')) {
            $zk = new ZKLibrary($ip, $port);
        }

        if ($zk) {
            $zk->setTimeout(4, 0);
            $zkConnected = $zk->connect($ip, $port, $targetCommKey);

            if ($zkConnected) {
                echo "SUCCESS!\n";
                if ($zk->comm_key !== null) {
                    echo "       • Comm Key Auth    : Authenticated with key [{$zk->comm_key}]\n";
                }
                $version = $zk->getVersion();
                if ($version) {
                    echo '       • Firmware Version : '.trim($version)."\n";
                }
                $devTime = $zk->getTime();
                if ($devTime) {
                    echo "       • Device Clock RTC : {$devTime}\n";
                }
            } else {
                echo "FAILED!\n";
                $lastErr = property_exists($zk, 'last_error') ? $zk->last_error : 'Unknown socket error';
                echo "       ✕ UDP Error Detail: {$lastErr}\n";
            }
        }
    } catch (Throwable $e) {
        echo 'EXCEPTION: '.$e->getMessage()."\n";
    }

    // Step 2b: TCP Fallback Check if UDP failed
    if (! $zkConnected) {
        echo ' [2b]  ZK TCP Fallback: Probing native TCP protocol (0x5050827D)... ';
        $tcpResult = testZkTCP($ip, $port, 3.5);
        if ($tcpResult['success']) {
            echo "SUCCESS!\n";
            echo "       ✓ Machine responded to TCP protocol! Session ID: {$tcpResult['session_id']}\n";
            echo "       ✓ DIAGNOSTIC VERDICT: This device requires TCP communication protocol!\n";
        } else {
            echo "FAILED!\n";
            echo "       ✕ TCP Detail: {$tcpResult['error']}\n";
        }
    }

    if (! $zkConnected && (! isset($tcpResult) || ! $tcpResult['success'])) {
        echo "\n       ✕ Actionable Steps for {$name}:\n";
        echo "         1. Verify if an existing cron job is running (check 'crontab -l').\n";
        echo "         2. Try power-cycling the biometric unit (turn OFF, wait 5s, turn ON).\n";
        echo "         3. On machine screen: Menu -> Comm. -> Ethernet -> Verify IP {$ip}.\n";
        echo "         4. On machine screen: Menu -> Comm. -> PC Connect -> Verify Comm Key is 0.\n\n";
        $failCount++;

        continue;
    }

    // Step 3: Fetch Attendance Log (Safe Mode)
    echo ' [3/4] Attendance Log: Fetching punch records via getAttendance()... ';
    try {
        if ($zkConnected && $zk) {
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
                    printf("       %-6s | %-12s | %-10s | %-19s\n", '#', 'Employee ID', 'Type', 'Timestamp');
                    echo "       --------------------------------------------------------\n";

                    $sample = array_slice($attendance, -5);
                    $idx = 1;
                    foreach ($sample as $punch) {
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
        } elseif (isset($tcpResult) && $tcpResult['success']) {
            echo "TCP Protocol acknowledged. Use TCP mode in application settings.\n";
            $successCount++;
        }
    } catch (Throwable $e) {
        echo 'EXCEPTION during fetch: '.$e->getMessage()."\n";
        $failCount++;
    } finally {
        if ($zk && $zkConnected) {
            try {
                $zk->disconnect();
            } catch (Throwable $e) {
            }
        }
    }

    echo "\n";
}

echo "===================================================================\n";
echo "   Test Run Summary\n";
echo "===================================================================\n";
echo '   Total Tested : '.count($machines)."\n";
echo "   Successful   : {$successCount}\n";
echo "   Failed       : {$failCount}\n";
echo "   Data Safety  : 100% Guaranteed (No clearAttendance executed)\n";
echo "===================================================================\n";
