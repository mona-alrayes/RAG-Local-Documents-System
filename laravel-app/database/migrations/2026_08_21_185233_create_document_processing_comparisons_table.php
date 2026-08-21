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
        Schema::create('document_processing_comparisons', function (Blueprint $table) {
            $table->id();

            $table->foreignId('document_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('cloud_run_id')
                ->constrained('document_processing_runs')
                ->restrictOnDelete();

            $table->foreignId('hybrid_local_run_id')
                ->constrained('document_processing_runs')
                ->restrictOnDelete();

            $table->foreignId('selected_run_id')
                ->nullable()
                ->constrained('document_processing_runs')
                ->restrictOnDelete();

            $table->string('status', 32);

            $table->text('trial_question')->nullable();

            $table->timestamp('decided_at')->nullable();
            $table->timestamp('expires_at');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_processing_comparisons');
    }
};
