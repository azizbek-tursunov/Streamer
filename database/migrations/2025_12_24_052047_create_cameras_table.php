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
        Schema::create('cameras', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->string('ip_address');
            $table->integer('port')->default(554);
            $table->string('stream_path')->default('/');
            $table->string('youtube_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_streaming_to_youtube')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cameras');
    }
};
