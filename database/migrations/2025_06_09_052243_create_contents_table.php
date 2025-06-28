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
        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('profileImg')->nullable();
            $table->string('coverImg')->nullable();
            $table->string("duration")->nullable();
            $table->json('links')->nullable();
            $table->text('content');
            $table->json('tags')->nullable();
            $table->string('category')->nullable();
            $table->json("casts")->nullable();
            $table->json('files')->nullable();
            $table->boolean('isvip')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contents');
    }
};
