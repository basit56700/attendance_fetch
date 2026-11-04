<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('username', 100);
            $table->string('action', 100)->index(); // MACHINE_TEST, MACHINE_FETCH, ATTENDANCE_EXPORT, etc.
            $table->text('details')->nullable();
            $table->string('ip_address', 50)->nullable();
            $table->string('status', 20)->default('success'); // success, failed, info
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
