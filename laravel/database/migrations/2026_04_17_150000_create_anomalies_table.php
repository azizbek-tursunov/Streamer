<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anomalies', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('status')->default('open');
            $table->foreignId('auditorium_id')->constrained('auditoriums')->cascadeOnDelete();
            $table->foreignId('camera_id')->nullable()->constrained('cameras')->nullOnDelete();
            $table->foreignId('lesson_schedule_id')->nullable()->constrained('lesson_schedules')->nullOnDelete();
            $table->timestamp('detected_at');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['status', 'type']);
            $table->index(['auditorium_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anomalies');
    }
};
