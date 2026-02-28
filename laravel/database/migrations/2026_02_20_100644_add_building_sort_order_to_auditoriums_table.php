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
            $table->unsignedInteger('building_sort_order')->default(0)->after('building_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auditoriums', function (Blueprint $table) {
            $table->dropColumn('building_sort_order');
        });
    }
};
