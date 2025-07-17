<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->unsignedInteger('vip_referred_claimed_count')->default(0)->after('referral_claimed_count');
            $table->unsignedInteger('normal_referred_claimed_count')->default(0)->after('vip_referred_claimed_count');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn(['vip_referred_claimed_count', 'normal_referred_claimed_count']);
        });
    }
};
