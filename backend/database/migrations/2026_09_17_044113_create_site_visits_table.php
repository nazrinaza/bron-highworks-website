<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_visits', function (Blueprint $table): void {
            $table->id();
            $table->uuid('reference')->unique();
            $table->string('name');
            $table->string('company')->nullable();
            $table->string('email');
            $table->string('phone', 50);
            $table->string('service');
            $table->text('address');
            $table->string('area')->nullable();
            $table->date('preferred_date')->nullable();
            $table->text('access_details')->nullable();
            $table->text('requirements')->nullable();
            $table->string('status')->default('new')->index();
            $table->dateTime('scheduled_at')->nullable();
            $table->string('assigned_to')->nullable();
            $table->text('internal_notes')->nullable();
            $table->text('assessment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_visits');
    }
};
