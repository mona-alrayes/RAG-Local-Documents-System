<?php

use App\Enums\ProcessingRunKind;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_processing_runs', function (Blueprint $table) {
            $table->string('kind', 32)
                ->default(ProcessingRunKind::Initial->value)
                ->after('status');
            $table->timestamp('started_at')->nullable()->after('kind');
            $table->timestamp('indexing_started_at')
                ->nullable()
                ->after('started_at');
            $table->timestamp('failed_at')->nullable()->after('indexed_at');
        });

        $seenDocuments = [];

        DB::table('document_processing_runs')
            ->select(['id', 'document_id'])
            ->orderBy('id')
            ->chunkById(500, function ($runs) use (&$seenDocuments): void {
                foreach ($runs as $run) {
                    $documentId = (int) $run->document_id;

                    if (isset($seenDocuments[$documentId])) {
                        DB::table('document_processing_runs')
                            ->where('id', $run->id)
                            ->update([
                                'kind' => ProcessingRunKind::Reprocessing->value,
                            ]);
                    }

                    $seenDocuments[$documentId] = true;
                }
            });
    }

    public function down(): void
    {
        Schema::table('document_processing_runs', function (Blueprint $table) {
            $table->dropColumn([
                'kind',
                'started_at',
                'indexing_started_at',
                'failed_at',
            ]);
        });
    }
};
