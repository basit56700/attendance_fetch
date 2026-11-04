<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = SystemSetting::all()->pluck('value', 'key');

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->except(['_token', '_method']);

        foreach ($data as $key => $value) {
            SystemSetting::set($key, (string) $value);
        }

        AuditLog::log('SETTINGS_UPDATE', 'System configuration updated.', 'info');

        return back()->with('success', 'System settings updated successfully.');
    }
}
