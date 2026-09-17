<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_documents', function (Blueprint $table): void {
            $table->id();
            $table->string('number')->nullable()->unique();
            $table->string('type')->index();
            $table->foreignId('site_visit_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('business_documents')->restrictOnDelete();
            $table->string('status')->default('draft');
            $table->string('party_name');
            $table->text('party_address');
            $table->string('party_email')->nullable();
            $table->string('external_reference')->nullable();
            $table->date('issued_on');
            $table->date('due_on')->nullable();
            $table->json('items');
            $table->unsignedBigInteger('subtotal_cents');
            $table->unsignedInteger('tax_basis_points')->default(0);
            $table->unsignedBigInteger('tax_cents')->default(0);
            $table->unsignedBigInteger('total_cents');
            $table->text('notes')->nullable();
            $table->unsignedInteger('revision')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_documents');
    }
};
