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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_id')->unique();
            $table->string('api_token', 64)->unique();
            $table->string('user_identifier')->nullable()->comment('References users.email');
            $table->boolean('is_vip')->default(false);
            $table->string('device_token')->nullable();
            $table->string('osversion')->nullable();
            $table->string('appversion')->nullable();
            $table->string('subscription_key')->nullable()->comment('References subscriptions.key');
            $table->string('platform')->comment('android, ios, web, desktop');
            $table->timestamp('last_active_at')->nullable();
            $table->timestamp('vip_expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
