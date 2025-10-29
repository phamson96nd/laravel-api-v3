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
        Schema::table('refresh_tokens', function (Blueprint $table) {
            $table->string('rotated_from')->nullable()->after('token');
            $table->boolean('revoked')->default(false)->after('rotated_from');
            $table->string('device_fingerprint')->nullable()->after('revoked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('refresh_tokens', function (Blueprint $table) {
            $table->dropColumn(['rotated_from', 'revoked', 'device_fingerprint']);
        });
    }
};
