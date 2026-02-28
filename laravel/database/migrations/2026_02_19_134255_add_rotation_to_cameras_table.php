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
            if (!Schema::hasColumn('cameras', 'rotation')) {
                $table->integer('rotation')->default(0)->after('port');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cameras', function (Blueprint $table) {
            if (Schema::hasColumn('cameras', 'rotation')) {
                $table->dropColumn('rotation');
            }
        });
    }
};
