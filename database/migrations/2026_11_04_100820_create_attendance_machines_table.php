<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_machines', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('ip_address', 50);
            $table->unsignedInteger('port')->default(4370);
            $table->string('protocol', 10)->default('auto'); // auto, udp, tcp
            $table->unsignedInteger('comm_key')->default(0);
            $table->string('serial_number', 100)->nullable();
            $table->string('firmware_version', 100)->nullable();
            $table->string('location', 150)->nullable();
            $table->string('status', 20)->default('active'); // active, inactive
            $table->timestamp('last_connected_at')->nullable();
            $table->unsignedInteger('last_fetch_count')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_machines');
    }
};
