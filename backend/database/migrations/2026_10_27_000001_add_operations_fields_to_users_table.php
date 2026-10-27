<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('user')->after('is_admin');
            $table->string('status')->default('active')->after('role');
            $table->string('phone')->nullable()->after('email');
            $table->string('pilot_licence_no')->nullable()->after('status');
            $table->date('pilot_licence_expires_at')->nullable()->after('pilot_licence_no');
            $table->softDeletes();
        });

        DB::table('users')->where('is_admin', true)->update(['role' => 'admin']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['role', 'status', 'phone', 'pilot_licence_no', 'pilot_licence_expires_at', 'deleted_at']);
        });
    }
};
