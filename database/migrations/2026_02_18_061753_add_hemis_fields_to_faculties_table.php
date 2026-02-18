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
        Schema::table('faculties', function (Blueprint $table) {
            $table->integer('hemis_id')->nullable()->unique()->after('id');
            $table->string('code')->nullable()->after('name');
            $table->string('structure_type_code')->nullable()->after('code');
            $table->string('structure_type_name')->nullable()->after('structure_type_code');
            $table->string('locality_type_code')->nullable()->after('structure_type_name');
            $table->string('locality_type_name')->nullable()->after('locality_type_code');
            $table->boolean('active')->default(true)->after('locality_type_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('faculties', function (Blueprint $table) {
            $table->dropColumn([
                'hemis_id',
                'code',
                'structure_type_code',
                'structure_type_name',
                'locality_type_code',
                'locality_type_name',
                'active',
            ]);
        });
    }
};
