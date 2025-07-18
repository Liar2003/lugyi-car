<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_subscription_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('device_id');
            $table->string('subscription_key');
            $table->timestamp('used_at');
            $table->boolean('reward_granted')->default(false);
            $table->timestamps();

            $table->foreign('device_id')->references('id')->on('devices')->onDelete('cascade');
            $table->foreign('subscription_key')->references('key')->on('subscriptions')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_subscription_histories');
    }
};
