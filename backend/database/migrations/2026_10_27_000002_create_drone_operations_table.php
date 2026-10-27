<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drone_operations', function (Blueprint $table): void {
            $table->id();
            $table->string('reference')->unique();
            $table->string('title');
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('site_address');
            $table->dateTime('scheduled_start')->nullable();
            $table->dateTime('scheduled_end')->nullable();
            $table->foreignId('assigned_pilot_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_id')->constrained('users');
            $table->string('status')->default('planning');
            $table->json('risk_assessment')->nullable();
            $table->string('risk_status')->default('draft');
            $table->timestamp('risk_submitted_at')->nullable();
            $table->timestamp('risk_approved_at')->nullable();
            $table->foreignId('risk_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('flight_plan')->nullable();
            $table->string('flight_plan_status')->default('draft');
            $table->timestamp('flight_plan_submitted_at')->nullable();
            $table->timestamp('flight_plan_approved_at')->nullable();
            $table->foreignId('flight_plan_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('preflight_checklist')->nullable();
            $table->string('preflight_status')->default('pending');
            $table->timestamp('preflight_completed_at')->nullable();
            $table->foreignId('preflight_completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('authorisation_details')->nullable();
            $table->string('authorisation_status')->default('pending');
            $table->string('agreement_original_name')->nullable();
            $table->string('agreement_path')->nullable();
            $table->timestamp('agreement_signed_at')->nullable();
            $table->foreignId('agreement_uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drone_operations');
    }
};
