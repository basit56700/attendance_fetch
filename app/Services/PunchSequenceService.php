<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\AttendanceMachine;
use App\Models\SystemSetting;

class PunchSequenceService
{
    /**
     * Resolves whether alternating daily Check-In / Check-Out sequencing is active.
     */
    public function isAlternatingEnabled(): bool
    {
        return SystemSetting::get('punch_sequence_mode', 'alternating') === 'alternating';
    }

    /**
     * Returns the human-readable punch label for tables and JSON payloads.
     */
    public function labelForType(?int $punchType, ?string $typeRemarks = null): string
    {
        return match ($punchType) {
            0 => 'Check-In',
            1 => 'Check-Out',
            2 => 'Break OUT',
            3 => 'Break IN',
            4 => 'Overtime IN',
            5 => 'Overtime OUT',
            default => $typeRemarks ?: 'Unknown',
        };
    }

    /**
     * Persists a batch of pulled machine records with deduplication and punch resequencing.
     *
     * @param  array<int, array<string, mixed>>  $records
     * @return array{total: int, created: int, duplicates: int}
     */
    public function storePulledRecords(AttendanceMachine $machine, array $records): array
    {
        $total = count($records);
        $createdCount = 0;
        $duplicates = 0;
        $affectedPairs = [];

        foreach ($records as $record) {
            $erpId = trim((string) ($record['erp_id'] ?? ''));
            $attTime = (string) ($record['attendance_time'] ?? '');

            if ($erpId === '' || $attTime === '') {
                continue;
            }

            $created = AttendanceLog::firstOrCreate(
                [
                    'attendance_machine_id' => $machine->id,
                    'erp_id' => $erpId,
                    'attendance_time' => $attTime,
                ],
                [
                    'punch_type' => (int) ($record['punch_type'] ?? 0),
                    'type_remarks' => (string) ($record['type_remarks'] ?? 'IN'),
                    'verification_mode' => (string) ($record['verification_mode'] ?? 'Fingerprint'),
                    'work_code' => (string) ($record['work_code'] ?? '0'),
                    'raw_data' => $record,
                    'sync_status' => 'synced',
                ]
            );

            if ($created->wasRecentlyCreated) {
                $createdCount++;
                $affectedPairs[] = ['erp_id' => $erpId, 'date' => substr($attTime, 0, 10)];
            } else {
                $duplicates++;
            }
        }

        if (! empty($affectedPairs)) {
            $this->resequencePairs($affectedPairs);
        }

        return [
            'total' => $total,
            'created' => $createdCount,
            'duplicates' => $duplicates,
        ];
    }

    /**
     * Resequences chronological punches for unique (erp_id, date) pairs.
     *
     * @param  array<int, array{erp_id: string, date: string}>  $pairs
     */
    public function resequencePairs(array $pairs): int
    {
        if (! $this->isAlternatingEnabled()) {
            return 0;
        }

        $unique = [];
        foreach ($pairs as $pair) {
            $erpId = trim((string) ($pair['erp_id'] ?? ''));
            $date = substr((string) ($pair['date'] ?? ''), 0, 10);
            if ($erpId === '' || strlen($date) !== 10) {
                continue;
            }
            $unique["{$erpId}|{$date}"] = ['erp_id' => $erpId, 'date' => $date];
        }

        $updated = 0;
        foreach ($unique as $entry) {
            $updated += $this->resequenceUserDate($entry['erp_id'], $entry['date']);
        }

        return $updated;
    }

    /**
     * Enforces alternating daily punch states for a user on a given date:
     * 1st entry => Check-In (0 / IN), 2nd entry => Check-Out (1 / OUT), etc.
     */
    public function resequenceUserDate(string $erpId, string $date): int
    {
        if (! $this->isAlternatingEnabled()) {
            return 0;
        }

        $logs = AttendanceLog::where('erp_id', $erpId)
            ->whereBetween('attendance_time', ["{$date} 00:00:00", "{$date} 23:59:59"])
            ->orderBy('attendance_time')
            ->orderBy('id')
            ->get();

        $updated = 0;

        foreach ($logs as $index => $log) {
            $isCheckIn = ($index % 2 === 0);
            $targetType = $isCheckIn ? 0 : 1;
            $targetRemarks = $isCheckIn ? 'IN' : 'OUT';

            if ((int) $log->punch_type !== $targetType || $log->type_remarks !== $targetRemarks) {
                $log->update([
                    'punch_type' => $targetType,
                    'type_remarks' => $targetRemarks,
                ]);
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Reclassifies all historical attendance logs across every user and date.
     */
    public function resequenceAll(): int
    {
        if (! $this->isAlternatingEnabled()) {
            return 0;
        }

        $updated = 0;
        $processed = [];

        AttendanceLog::query()
            ->select(['id', 'erp_id', 'attendance_time'])
            ->orderBy('id')
            ->chunkById(500, function ($chunk) use (&$processed, &$updated): void {
                $pairs = [];
                foreach ($chunk as $log) {
                    $date = $log->attendance_time?->format('Y-m-d');
                    if (! $date) {
                        continue;
                    }
                    $key = "{$log->erp_id}|{$date}";
                    if (! isset($processed[$key])) {
                        $processed[$key] = true;
                        $pairs[] = ['erp_id' => (string) $log->erp_id, 'date' => $date];
                    }
                }
                if (! empty($pairs)) {
                    $updated += $this->resequencePairs($pairs);
                }
            });

        return $updated;
    }
}
