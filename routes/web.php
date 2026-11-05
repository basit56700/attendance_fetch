<?php

use App\Http\Controllers\AdmsPushController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MachineController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Public Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ZKTeco ADMS Cloud Push Endpoints (Real-time biometric push for F22, SpeedFace, TimeCloud)
Route::match(['GET', 'POST'], '/iclock/cdata', [AdmsPushController::class, 'cdata'])->name('adms.cdata');
Route::match(['GET', 'POST'], '/iclock/push', [AdmsPushController::class, 'push'])->name('adms.push');
Route::match(['GET', 'POST'], '/iclock/getrequest', [AdmsPushController::class, 'getRequest'])->name('adms.getrequest');
Route::match(['GET', 'POST'], '/iclock/devicecmd', [AdmsPushController::class, 'deviceCmd'])->name('adms.devicecmd');
Route::match(['GET', 'POST'], '/iclock/fdata', [AdmsPushController::class, 'fdata'])->name('adms.fdata');
Route::match(['GET', 'POST'], '/iclock/registry', [AdmsPushController::class, 'registry'])->name('adms.registry');

// Public JSON REST API Endpoints for integrations
Route::get('/api/attendance/{machine_id?}', [AttendanceController::class, 'apiIndex'])->name('api.attendance.legacy');
Route::get('/api/v1/attendance/{machine_id?}', [AttendanceController::class, 'apiIndex'])->name('api.attendance');
Route::get('/api/machines/{machine_id}/attendance', [AttendanceController::class, 'apiIndex'])->name('api.machines.attendance');

// Authenticated Application Routes
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Biometric Machines
    Route::post('/machines/test-connection', [MachineController::class, 'testConnectivity'])->name('machines.test-connection');
    Route::post('/machines/fetch-all', [MachineController::class, 'fetchAll'])->name('machines.fetch-all');
    Route::post('/machines/{machine}/fetch', [MachineController::class, 'fetchLogs'])->name('machines.fetch');
    Route::post('/machines/{machine}/sync-time', [MachineController::class, 'syncTime'])->name('machines.sync-time');
    Route::post('/machines/{machine}/clear', [MachineController::class, 'clearLogs'])->name('machines.clear');
    Route::resource('machines', MachineController::class);

    // Attendance Records & Exports
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/export/json', [AttendanceController::class, 'exportJson'])->name('attendance.export.json');
    Route::get('/attendance/export/csv', [AttendanceController::class, 'exportCsv'])->name('attendance.export.csv');

    // Complete User Management
    Route::post('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::resource('users', UserController::class);

    // Profile Management
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Comprehensive Audit Trail
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::post('/audit-logs/clear', [AuditLogController::class, 'clear'])->name('audit-logs.clear');

    // System Settings
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
});
