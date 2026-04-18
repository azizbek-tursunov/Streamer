<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anomaly_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anomaly_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['anomaly_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anomaly_events');
    }
};
