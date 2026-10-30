<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['viewer', 'updater', 'coordinator', 'administrator'])->default('viewer')->after('email');
            $table->foreignId('state_id')->nullable()->constrained()->onDelete('set null')->after('role');
            $table->foreignId('phc_id')->nullable()->constrained()->onDelete('set null')->after('state_id');
            $table->string('phone')->nullable()->after('email');
            $table->boolean('is_active')->default(true)->after('remember_token');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['state_id']);
            $table->dropForeign(['phc_id']);
            $table->dropColumn(['role', 'state_id', 'phc_id', 'phone', 'is_active', 'last_login_at']);
        });
    }
};
