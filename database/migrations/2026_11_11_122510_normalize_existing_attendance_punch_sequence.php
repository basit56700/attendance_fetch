<?php

use App\Models\SystemSetting;
use App\Services\PunchSequenceService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        SystemSetting::firstOrCreate(['key' => 'ui_refresh_interval'], ['value' => '5']);
        SystemSetting::firstOrCreate(['key' => 'punch_sequence_mode'], ['value' => 'alternating']);

        app(PunchSequenceService::class)->resequenceAll();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Leave normalized punch classifications intact on rollback.
    }
};
