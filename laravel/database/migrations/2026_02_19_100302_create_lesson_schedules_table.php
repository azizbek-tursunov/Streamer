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
        Schema::create('lesson_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('hemis_id')->nullable()->index();
            $table->unsignedInteger('auditorium_code')->index();
            $table->date('lesson_date');
            $table->string('subject_name');
            $table->string('employee_name');
            $table->string('group_name');
            $table->string('training_type_name')->nullable();
            $table->string('lesson_pair_name')->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamp('start_timestamp');
            $table->timestamp('end_timestamp');
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->unique(['hemis_id', 'lesson_date', 'auditorium_code'], 'lesson_schedule_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_schedules');
    }
};
