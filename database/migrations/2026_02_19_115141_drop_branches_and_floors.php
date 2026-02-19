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
        Schema::table('cameras', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['floor_id']);
            $table->dropColumn(['branch_id', 'floor_id']);
        });

        Schema::dropIfExists('floors');
        Schema::dropIfExists('branches');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address')->nullable();
            $table->timestamps();
        });

        Schema::create('floors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::table('cameras', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('floor_id')->nullable()->constrained()->nullOnDelete();
        });
    }
};
