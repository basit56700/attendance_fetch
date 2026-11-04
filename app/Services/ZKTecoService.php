<?php

namespace App\Services;

use App\Support\ZKLibrary;
use Exception;

class ZKTecoService
{
    const CMD_CONNECT = 1000;

    const CMD_EXIT = 1001;

    const CMD_ENABLEDEVICE = 1002;

    const CMD_DISABLEDEVICE = 1003;

    const CMD_VERSION = 1100;

    const CMD_DEVICE = 11;

    const CMD_GET_TIME = 201;

    const CMD_SET_TIME = 202;

    const CMD_ATTLOG_RRQ = 13;

    const CMD_CLEAR_ATTLOG = 15;

    const CMD_ACK_OK = 2000;

    const CMD_ACK_ERROR = 2001;

    const CMD_ACK_DATA = 2002;

    const CMD_PREPARE_DATA = 1500;

    const CMD_DATA = 1501;

    const CMD_ACK_UNAUTH = 2005;

    const USHRT_MAX = 65535;

    protected $socket = null;

    protected $sessionId = 0;

    protected $replyNumber = 65534;

    protected $protocol = 'udp';

    protected $isConnected = false;

    /**
     * Runs pre-connection diagnostics on a biometric machine.
     */
    public function runDiagnostics(string $ip, int $port = 4370, string $protocol = 'auto', int $commKey = 0): array
    {
        $startTime = microtime(true);
        $diag = [
            'success' => false,
            'ip' => $ip,
            'port' => $port,
            'protocol' => $protocol,
            'latency_ms' => null,
            'firmware' => null,
            'serial_number' => null,
            'device_time' => null,
            'time_drift_seconds' => null,
            'error_code' => null,
            'error_summary' => null,
            'root_cause' => null,
            'resolution_steps' => [],
        ];

        // 1. IP Validation
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            $diag['error_code'] = 'ERR_INVALID_IP';
            $diag['error_summary'] = "The IP address '{$ip}' is invalid.";
            $diag['root_cause'] = 'Syntactically invalid IPv4 address format.';
            $diag['resolution_steps'] = [
                'Verify the IP format (e.g. 192.168.1.201).',
                'Check the device screen: Menu -> Comm. -> Ethernet -> IP Address.',
            ];

            return $diag;
        }

        // 2. Pre-flight Port Socket Probe (TCP ping with fast 2.5s timeout)
        $probeStart = microtime(true);
        $errNo = 0;
        $errStr = '';
        $fp = @fsockopen($ip, $port, $errNo, $errStr, 2.5);
        $isSocketReachable = ($fp !== false);
        if ($fp) {
            fclose($fp);
            $probeLatency = round((microtime(true) - $probeStart) * 1000, 1);
        } else {
            $probeLatency = null;
        }

        // 3. Try Proven ZKLibrary (UDP) first if protocol is 'auto' or 'udp'
        if ($protocol === 'auto' || $protocol === 'udp') {
            try {
                $zk = new ZKLibrary($ip, $port, $commKey);
                $zk->setTimeout(3, 0);
                if ($zk->connect($ip, $port, $commKey)) {
                    $latency = round((microtime(true) - $startTime) * 1000, 1);
                    $version = $zk->getVersion() ?: $zk->getFirmwareVersion() ?: 'ZKTeco TFT / B&W';
                    $devTime = null;
                    try {
                        $devTime = $zk->getTime();
                    } catch (Exception $te) {
                    }

                    $zk->disconnect();

                    $diag['success'] = true;
                    $diag['protocol'] = 'UDP (Standard ZK)';
                    $diag['latency_ms'] = $latency;
                    $diag['firmware'] = is_string($version) ? trim($version) : 'ZKTeco Device';
                    $diag['device_time'] = $devTime;
                    if ($devTime) {
                        $diag['time_drift_seconds'] = abs(strtotime($devTime) - time());
                    }
                    $diag['error_summary'] = 'Device is online, healthy, and communicating normally via UDP.';

                    return $diag;
                }
            } catch (Exception $ze) {
            }
        }

        // 4. Try TCP fallback or explicit TCP
        try {
            $connected = $this->connect($ip, $port, $protocol, $commKey, 3.5);

            if ($connected) {
                $diag['latency_ms'] = round((microtime(true) - $startTime) * 1000, 1);
                $diag['protocol'] = strtoupper($this->protocol);
                $diag['firmware'] = $this->getFirmwareVersion();
                $diag['device_time'] = $this->getDeviceTime();

                if ($diag['device_time']) {
                    $devTs = strtotime($diag['device_time']);
                    $localTs = time();
                    $diag['time_drift_seconds'] = $devTs - $localTs;
                }

                $this->disconnect();

                $diag['success'] = true;
                $diag['error_summary'] = 'Device is online, healthy, and communicating normally.';

                return $diag;
            } else {
                return $this->diagnoseFailure($ip, $port, $errNo, $errStr, $startTime, $isSocketReachable);
            }
        } catch (Exception $e) {
            $this->disconnect();

            return $this->diagnoseFailure($ip, $port, $errNo, $e->getMessage(), $startTime, $isSocketReachable);
        }
    }

    protected function diagnoseFailure(string $ip, int $port, int $errNo, string $msg, float $startTime, bool $isSocketReachable = false): array
    {
        $latency = round((microtime(true) - $startTime) * 1000, 1);
        $diag = [
            'success' => false,
            'ip' => $ip,
            'port' => $port,
            'latency_ms' => $latency,
            'firmware' => null,
            'serial_number' => null,
            'device_time' => null,
        ];

        $localIp = gethostbyname(gethostname());
        $isDifferentSubnet = false;
        $ipParts = explode('.', $ip);
        $localParts = explode('.', $localIp);
        if (count($ipParts) === 4 && count($localParts) === 4) {
            if ($ipParts[0] === $localParts[0] && $ipParts[1] === $localParts[1] && $ipParts[2] !== $localParts[2]) {
                $isDifferentSubnet = true;
            }
        }

        if (! $isSocketReachable || $errNo === 10060 || stripos($msg, 'timed out') !== false || stripos($msg, 'not properly respond') !== false || stripos($msg, 'Resource temporarily unavailable') !== false) {
            $diag['error_code'] = 'ERR_HOST_UNREACHABLE';
            $diag['error_summary'] = "Cannot reach {$ip}:{$port}. No response from host across your local network.";
            if ($isDifferentSubnet) {
                $diag['root_cause'] = "Subnet boundary: This computer (IP: {$localIp}) is on subnet {$localParts[0]}.{$localParts[1]}.{$localParts[2]}.x, while the machine ({$ip}) is on subnet {$ipParts[0]}.{$ipParts[1]}.{$ipParts[2]}.x. Network packets are not routed between these subnets from this workstation.";
                $diag['resolution_steps'] = [
                    "1. Testing on Workstation vs Production: If you are running on your local laptop, you can still click 'Save Biometric Machine' below! When deployed on your production server (where your original /var/www/html/ script ran), the server will connect directly.",
                    "2. Network Routing: If you want this laptop to reach {$ip}, configure your router to allow routing between subnet {$localParts[0]}.{$localParts[1]}.{$localParts[2]}.x and {$ipParts[0]}.{$ipParts[1]}.{$ipParts[2]}.x.",
                    '3. Device Status: Ensure the machine is powered ON and Ethernet cable is connected with active link lights.',
                ];
            } else {
                $diag['root_cause'] = "The biometric machine did not respond. Check power, cable connection, or firewall rules on port {$port}.";
                $diag['resolution_steps'] = [
                    '1. Check that the biometric machine is powered ON.',
                    '2. Verify the Ethernet cable is connected and link LEDs are blinking.',
                    "3. In machine Menu -> Comm. -> Ethernet, confirm IP is {$ip} and Gateway is correct.",
                    "4. Try 'ping {$ip}' in terminal to verify basic network connectivity.",
                ];
            }

            return $diag;
        }

        if ($errNo === 10061 || stripos($msg, 'Connection refused') !== false) {
            $diag['error_code'] = 'ERR_CONNECTION_REFUSED (10061)';
            $diag['error_summary'] = "Target host {$ip} actively refused the connection on Port {$port}.";
            $diag['root_cause'] = "The IP address exists on your network, but Port {$port} is closed, or another device is occupying that IP.";
            $diag['resolution_steps'] = [
                "1. Confirm no other device is conflicting on {$ip}.",
                "2. Verify Machine Port: In ZKTeco Menu -> Comm. -> PC Connect, verify Port is {$port} (4370).",
                '3. Protocol: Switch to Auto or UDP Only.',
            ];

            return $diag;
        }

        $diag['error_code'] = 'ERR_HANDSHAKE_FAILED';
        $diag['error_summary'] = "Could not complete ZKTeco binary protocol handshake with {$ip}:{$port}. ".($msg ?: 'No response');
        $diag['root_cause'] = 'The device is reachable at the network layer, but failed the proprietary ZKTeco protocol initialization.';
        $diag['resolution_steps'] = [
            "1. Switch Protocol: In machine settings, change protocol to 'Auto (UDP & TCP)' or 'UDP Only'. Your original PHP code uses UDP.",
            '2. Power cycle the biometric device (turn OFF, wait 5 seconds, turn ON).',
            '3. Check whether another software (such as ZKAccess or ZKBioTime) is holding an exclusive socket lock on this machine.',
        ];

        return $diag;
    }

    public function connect(string $ip, int $port = 4370, string $protocol = 'auto', int $commKey = 0, float $timeout = 4.0): bool
    {
        $this->disconnect();
        $protocolsToTry = ($protocol === 'auto') ? ['udp', 'tcp'] : [strtolower($protocol)];

        foreach ($protocolsToTry as $proto) {
            $this->protocol = $proto;
            $sockType = ($proto === 'tcp') ? SOCK_STREAM : SOCK_DGRAM;
            $protoType = ($proto === 'tcp') ? SOL_TCP : SOL_UDP;

            $this->socket = @socket_create(AF_INET, $sockType, $protoType);
            if (! $this->socket) {
                continue;
            }

            $sec = (int) floor($timeout);
            $usec = (int) (($timeout - $sec) * 1000000);
            socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => $sec, 'usec' => $usec]);
            socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, ['sec' => $sec, 'usec' => $usec]);

            if ($proto === 'tcp') {
                if (! @socket_connect($this->socket, $ip, $port)) {
                    @socket_close($this->socket);
                    $this->socket = null;

                    continue;
                }
            }

            $this->sessionId = 0;
            $this->replyNumber = self::USHRT_MAX - 1;

            // Send CMD_CONNECT
            $this->sendPacket($ip, $port, self::CMD_CONNECT);
            $response = $this->receivePacket();

            if ($response && $response['command'] === self::CMD_ACK_OK) {
                $this->sessionId = $response['session_id'];
                $this->isConnected = true;

                return true;
            }

            @socket_close($this->socket);
            $this->socket = null;
        }

        $this->isConnected = false;

        return false;
    }

    public function disconnect(): void
    {
        if ($this->isConnected && $this->socket) {
            try {
                $this->sendPacket('', 0, self::CMD_EXIT);
            } catch (Exception $e) {
            }
        }
        if ($this->socket) {
            @socket_close($this->socket);
            $this->socket = null;
        }
        $this->isConnected = false;
        $this->sessionId = 0;
    }

    /**
     * Downloads attendance punch logs from the machine.
     * Uses battle-tested ZKLibrary UDP first, falling back to TCP socket.
     */
    public function getAttendance(string $ip, int $port = 4370, string $protocol = 'auto', int $commKey = 0): array
    {
        // 1. Try Proven ZKLibrary (UDP) first
        if ($protocol === 'auto' || $protocol === 'udp') {
            try {
                $zk = new ZKLibrary($ip, $port, $commKey);
                $zk->setTimeout(6, 0);
                if ($zk->connect($ip, $port, $commKey)) {
                    $rawLogs = $zk->getAttendance();
                    $zk->disconnect();

                    if (is_array($rawLogs)) {
                        $records = [];
                        foreach ($rawLogs as $at) {
                            $state = (int) $at[2];
                            $type_remarks = match ($state) {
                                0 => 'IN',
                                1 => 'OUT',
                                2 => 'Break OUT',
                                3 => 'Break IN',
                                4 => 'Overtime IN',
                                5 => 'Overtime OUT',
                                default => "Status {$state}",
                            };

                            $records[] = [
                                'uid' => $at[0],
                                'erp_id' => (string) trim($at[1]),
                                'punch_type' => $state,
                                'type_remarks' => $type_remarks,
                                'attendance_time' => $at[3],
                                'verification_mode' => 'Fingerprint',
                                'work_code' => '0',
                            ];
                        }

                        return $records;
                    }
                }
            } catch (Exception $ze) {
            }
        }

        // 2. Fallback to native socket protocol
        if (! $this->isConnected) {
            if (! $this->connect($ip, $port, $protocol, $commKey)) {
                throw new Exception("Unable to connect to biometric machine at {$ip}:{$port}");
            }
        }

        $records = [];
        try {
            $this->sendPacket($ip, $port, self::CMD_DISABLEDEVICE);
            $this->receivePacket();

            $this->sendPacket($ip, $port, self::CMD_ATTLOG_RRQ);
            $response = $this->receivePacket();

            $dataBuffer = '';

            if ($response && $response['command'] === self::CMD_PREPARE_DATA) {
                $totalBytes = 0;
                if (strlen($response['payload']) >= 4) {
                    $unpacked = unpack('Vlen', substr($response['payload'], 0, 4));
                    $totalBytes = $unpacked['len'];
                }

                while (strlen($dataBuffer) < $totalBytes) {
                    $pkt = $this->receivePacket();
                    if (! $pkt) {
                        break;
                    }
                    if ($pkt['command'] === self::CMD_DATA) {
                        $dataBuffer .= $pkt['payload'];
                    } elseif ($pkt['command'] === self::CMD_ACK_OK) {
                        break;
                    }
                }
            } elseif ($response && ($response['command'] === self::CMD_ACK_OK || $response['command'] === self::CMD_ACK_DATA)) {
                $dataBuffer .= $response['payload'];
            }

            $records = $this->parseAttendanceData($dataBuffer);

            $this->sendPacket($ip, $port, self::CMD_ENABLEDEVICE);
            $this->receivePacket();
        } finally {
            $this->disconnect();
        }

        return $records;
    }

    /**
     * Clears attendance logs on the machine.
     */
    public function clearAttendance(string $ip, int $port = 4370, string $protocol = 'auto', int $commKey = 0): bool
    {
        if ($protocol === 'auto' || $protocol === 'udp') {
            try {
                $zk = new ZKLibrary($ip, $port, $commKey);
                $zk->setTimeout(5, 0);
                if ($zk->connect($ip, $port, $commKey)) {
                    $ok = $zk->clearAttendance();
                    $zk->disconnect();

                    return (bool) $ok;
                }
            } catch (Exception $ze) {
            }
        }

        if (! $this->isConnected) {
            if (! $this->connect($ip, $port, $protocol, $commKey)) {
                return false;
            }
        }

        try {
            $this->sendPacket($ip, $port, self::CMD_DISABLEDEVICE);
            $this->receivePacket();

            $this->sendPacket($ip, $port, self::CMD_CLEAR_ATTLOG);
            $resp = $this->receivePacket();

            $this->sendPacket($ip, $port, self::CMD_ENABLEDEVICE);
            $this->receivePacket();

            return $resp && $resp['command'] === self::CMD_ACK_OK;
        } finally {
            $this->disconnect();
        }
    }

    public function getFirmwareVersion(): string
    {
        if (! $this->isConnected) {
            return 'Unknown';
        }
        try {
            $this->sendPacket('', 0, self::CMD_VERSION);
            $resp = $this->receivePacket();
            if ($resp && $resp['command'] === self::CMD_ACK_OK) {
                return trim($resp['payload']);
            }
        } catch (Exception $e) {
        }

        return 'Unknown';
    }

    public function getDeviceTime(): ?string
    {
        if (! $this->isConnected) {
            return null;
        }
        try {
            $this->sendPacket('', 0, self::CMD_GET_TIME);
            $resp = $this->receivePacket();
            if ($resp && $resp['command'] === self::CMD_ACK_OK && strlen($resp['payload']) >= 4) {
                $unpacked = unpack('Vt', substr($resp['payload'], 0, 4));

                return $this->decodeZKTime($unpacked['t']);
            }
        } catch (Exception $e) {
        }

        return null;
    }

    public function syncDeviceTime(): bool
    {
        if (! $this->isConnected) {
            return false;
        }
        try {
            $packed = $this->encodeZKTime(time());
            $this->sendPacket('', 0, self::CMD_SET_TIME, pack('V', $packed));
            $resp = $this->receivePacket();

            return $resp && $resp['command'] === self::CMD_ACK_OK;
        } catch (Exception $e) {
            return false;
        }
    }

    // ==========================================
    // PROTOCOL HELPERS
    // ==========================================

    protected function createHeader(int $command, string $data = ''): string
    {
        $buf = pack('vvvv', $command, 0, $this->sessionId, $this->replyNumber).$data;
        $checksum = $this->calculateChecksum($buf);

        return pack('vvvv', $command, $checksum, $this->sessionId, $this->replyNumber).$data;
    }

    protected function sendPacket(string $ip, int $port, int $command, string $data = ''): void
    {
        if (! $this->socket) {
            return;
        }
        $packet = $this->createHeader($command, $data);

        if ($this->protocol === 'tcp') {
            $prefix = pack('VV', 0x5050827D, strlen($packet));
            @socket_write($this->socket, $prefix.$packet);
        } else {
            @socket_sendto($this->socket, $packet, strlen($packet), 0, $ip, $port);
        }
    }

    protected function receivePacket(): ?array
    {
        if (! $this->socket) {
            return null;
        }
        $raw = '';

        if ($this->protocol === 'tcp') {
            $prefix = @socket_read($this->socket, 8);
            if (! $prefix || strlen($prefix) < 8) {
                return null;
            }
            $lenData = unpack('Vlen', substr($prefix, 4, 4));
            $targetLen = $lenData['len'];

            while (strlen($raw) < $targetLen) {
                $chunk = @socket_read($this->socket, min($targetLen - strlen($raw), 4096));
                if (! $chunk) {
                    break;
                }
                $raw .= $chunk;
            }
        } else {
            $from = '';
            $port = 0;
            @socket_recvfrom($this->socket, $raw, 65535, 0, $from, $port);
        }

        if (strlen($raw) < 8) {
            return null;
        }

        $header = unpack('vcommand/vchecksum/vsession_id/vreply_id', substr($raw, 0, 8));
        $payload = substr($raw, 8);

        return [
            'command' => $header['command'],
            'session_id' => $header['session_id'],
            'reply_id' => $header['reply_id'],
            'payload' => $payload,
        ];
    }

    protected function calculateChecksum(string $data): int
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

    protected function decodeZKTime(int $t): string
    {
        $second = $t % 60;
        $t = (int) ($t / 60);
        $minute = $t % 60;
        $t = (int) ($t / 60);
        $hour = $t % 24;
        $t = (int) ($t / 24);
        $day = ($t % 31) + 1;
        $t = (int) ($t / 31);
        $month = ($t % 12) + 1;
        $t = (int) ($t / 12);
        $year = $t + 2000;

        return sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $minute, $second);
    }

    protected function encodeZKTime(int $timestamp): int
    {
        $d = getdate($timestamp);
        $year = $d['year'] % 100;
        $month = $d['mon'] - 1;
        $day = $d['mday'] - 1;
        $hour = $d['hours'];
        $minute = $d['minutes'];
        $second = $d['seconds'];

        return (($year * 12 * 31 + $month * 31 + $day) * 86400) + ($hour * 3600 + $minute * 60 + $second);
    }

    protected function parseAttendanceData(string $data): array
    {
        $records = [];
        if (empty($data)) {
            return $records;
        }

        // Check for tab-separated text format
        if (strpos(substr($data, 0, 100), "\t") !== false) {
            $lines = explode("\n", trim($data));
            foreach ($lines as $line) {
                $parts = explode("\t", trim($line));
                if (count($parts) >= 2) {
                    $userId = trim($parts[0]);
                    $time = trim($parts[1]);
                    $state = isset($parts[2]) ? (int) $parts[2] : 0;
                    $verify = isset($parts[3]) ? (int) $parts[3] : 1;
                    $records[] = $this->formatRecord($userId, $time, $state, $verify);
                }
            }

            return $records;
        }

        // TFT 40-byte format
        if (strlen($data) >= 40 && strlen($data) % 40 === 0) {
            for ($i = 0; $i < strlen($data); $i += 40) {
                $chunk = substr($data, $i, 40);
                $userId = trim(strtok(substr($chunk, 0, 24), "\x00"));
                if (empty($userId)) {
                    continue;
                }
                $verify = ord($chunk[24]);
                $state = ord($chunk[25]);
                $t = unpack('Vt', substr($chunk, 26, 4))['t'];
                $time = $this->decodeZKTime($t);
                $records[] = $this->formatRecord($userId, $time, $state, $verify);
            }

            return $records;
        }

        // B&W 8-byte format
        if (strlen($data) >= 8 && strlen($data) % 8 === 0) {
            for ($i = 0; $i < strlen($data); $i += 8) {
                $chunk = substr($data, $i, 8);
                $userId = (string) unpack('vuid', substr($chunk, 0, 2))['uid'];
                $state = ord($chunk[2]);
                $t = unpack('Vt', substr($chunk, 3, 4))['t'];
                $verify = ord($chunk[7]);
                $time = $this->decodeZKTime($t);
                $records[] = $this->formatRecord($userId, $time, $state, $verify);
            }

            return $records;
        }

        return $records;
    }

    protected function formatRecord(string $userId, string $time, int $state, int $verify): array
    {
        $remarks = match ($state) {
            0 => 'IN',
            1 => 'OUT',
            2 => 'Break OUT',
            3 => 'Break IN',
            4 => 'Overtime IN',
            5 => 'Overtime OUT',
            default => "Status {$state}",
        };

        $modes = [
            1 => 'Fingerprint',
            2 => 'Password',
            3 => 'Card (RFID)',
            15 => 'Face',
            25 => 'Palm',
        ];
        $verifyMode = $modes[$verify] ?? 'Fingerprint';

        return [
            'uid' => $userId,
            'erp_id' => $userId,
            'punch_type' => $state,
            'type_remarks' => $remarks,
            'attendance_time' => $time,
            'verification_mode' => $verifyMode,
            'work_code' => '0',
        ];
    }
}
