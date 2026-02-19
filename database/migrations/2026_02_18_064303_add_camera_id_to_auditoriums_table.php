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
        Schema::table('auditoriums', function (Blueprint $table) {
            $table->foreignId('camera_id')->nullable()->after('active')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auditoriums', function (Blueprint $table) {
            $table->dropForeign(['camera_id']);
            $table->dropColumn('camera_id');
        });
    }
};
