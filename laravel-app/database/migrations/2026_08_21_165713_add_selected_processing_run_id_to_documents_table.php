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
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('selected_processing_run_id')->nullable();

            $table->index('selected_processing_run_id');

            $table->foreign('selected_processing_run_id')
                ->references('id')
                ->on('document_processing_runs')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['selected_processing_run_id']);
            $table->dropIndex(['selected_processing_run_id']);
            $table->dropColumn('selected_processing_run_id');
        });
    }
};
