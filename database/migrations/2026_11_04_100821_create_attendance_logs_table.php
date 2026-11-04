<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_machine_id')->constrained('attendance_machines')->cascadeOnDelete();
            $table->string('erp_id', 50)->index();
            $table->dateTime('attendance_time')->index();
            $table->tinyInteger('punch_type')->default(0)->index(); // 0=IN, 1=OUT, 2=Break OUT, 3=Break IN, 4=OT IN, 5=OT OUT
            $table->string('type_remarks', 50)->default('IN');
            $table->string('verification_mode', 50)->default('Fingerprint');
            $table->string('work_code', 20)->default('0');
            $table->json('raw_data')->nullable();
            $table->string('sync_status', 20)->default('synced')->index(); // synced, pending, failed
            $table->timestamps();

            // Unique constraint to guarantee no duplicate punches from the same machine
            $table->unique(['attendance_machine_id', 'erp_id', 'attendance_time'], 'uniq_machine_punch');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
