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
        Schema::create('device_referrals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('referrer_device_id');
            $table->unsignedBigInteger('referred_device_id');
            $table->timestamp('referred_at')->useCurrent();
            $table->timestamps();

            $table->foreign('referrer_device_id')->references('id')->on('devices')->onDelete('cascade');
            $table->foreign('referred_device_id')->references('id')->on('devices')->onDelete('cascade');
            $table->unique(['referrer_device_id', 'referred_device_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_referrals');
    }
};
