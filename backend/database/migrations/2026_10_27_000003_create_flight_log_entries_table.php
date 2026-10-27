<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flight_log_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('drone_operation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pilot_id')->constrained('users');
            $table->date('flight_date');
            $table->string('task');
            $table->string('approval_category')->nullable();
            $table->string('client')->nullable();
            $table->string('uas_model');
            $table->string('uas_serial');
            $table->string('location');
            $table->string('observer')->nullable();
            $table->dateTime('takeoff_at');
            $table->dateTime('landing_at');
            $table->unsignedInteger('total_minutes');
            $table->dateTime('duty_started_at')->nullable();
            $table->dateTime('duty_finished_at')->nullable();
            $table->text('additional_equipment')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flight_log_entries');
    }
};
