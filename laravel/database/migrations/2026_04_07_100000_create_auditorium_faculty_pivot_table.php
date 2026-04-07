<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create pivot table
        Schema::create('auditorium_faculty', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auditorium_id')->constrained('auditoriums')->cascadeOnDelete();
            $table->foreignId('faculty_id')->constrained('faculties')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['auditorium_id', 'faculty_id']);
        });

        // 2. Migrate existing faculty_id data to pivot table
        DB::statement('
            INSERT INTO auditorium_faculty (auditorium_id, faculty_id, created_at, updated_at)
            SELECT id, faculty_id, NOW(), NOW()
            FROM auditoriums
            WHERE faculty_id IS NOT NULL
        ');

        // 3. Drop the old column
        Schema::table('auditoriums', function (Blueprint $table) {
            $table->dropForeign(['faculty_id']);
            $table->dropColumn('faculty_id');
        });
    }

    public function down(): void
    {
        Schema::table('auditoriums', function (Blueprint $table) {
            $table->foreignId('faculty_id')->nullable()->constrained()->nullOnDelete()->after('camera_id');
        });

        // Restore data (pick first faculty if multiple)
        $pivots = DB::table('auditorium_faculty')
            ->select('auditorium_id', DB::raw('MIN(faculty_id) as faculty_id'))
            ->groupBy('auditorium_id')
            ->get();

        foreach ($pivots as $pivot) {
            DB::table('auditoriums')
                ->where('id', $pivot->auditorium_id)
                ->update(['faculty_id' => $pivot->faculty_id]);
        }

        Schema::dropIfExists('auditorium_faculty');
    }
};
