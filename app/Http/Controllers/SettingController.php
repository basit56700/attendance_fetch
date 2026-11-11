<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Services\PunchSequenceService;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function __construct(protected PunchSequenceService $punchSequence) {}

    public function index()
    {
        $settings = SystemSetting::all()->pluck('value', 'key');

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $previousMode = SystemSetting::get('punch_sequence_mode', 'alternating');
        $data = $request->except(['_token', '_method', 'resequence_now']);

        if (isset($data['ui_refresh_interval'])) {
            $seconds = (int) $data['ui_refresh_interval'];
            $data['ui_refresh_interval'] = (string) ($seconds <= 0 ? 0 : max(3, $seconds));
        }

        foreach ($data as $key => $value) {
            SystemSetting::set($key, (string) $value);
        }

        $resequenced = 0;
        $newMode = $data['punch_sequence_mode'] ?? $previousMode;
        if ($newMode === 'alternating' && ($previousMode !== 'alternating' || $request->boolean('resequence_now'))) {
            $resequenced = $this->punchSequence->resequenceAll();
        }

        AuditLog::log('SETTINGS_UPDATE', 'System configuration updated.', 'info');

        $msg = 'System settings updated successfully.';
        if ($resequenced > 0) {
            $msg .= " Reclassified {$resequenced} historical punch record(s) into daily Check-In / Check-Out sequence.";
        }

        return back()->with('success', $msg);
    }
}
