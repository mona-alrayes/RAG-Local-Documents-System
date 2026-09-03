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

        DB::table('document_processing_runs')
            ->select('document_id')
            ->distinct()
            ->chunkById(500, function ($documents): void {
                $documentIds = $documents
                    ->pluck('document_id')
                    ->map(static fn ($id): int => (int) $id)
                    ->all();

                $initialRunIds = DB::table('document_processing_runs')
                    ->whereIn('document_id', $documentIds)
                    ->selectRaw('MIN(id) AS id')
                    ->groupBy('document_id')
                    ->pluck('id')
                    ->all();

                DB::table('document_processing_runs')
                    ->whereIn('document_id', $documentIds)
                    ->whereNotIn('id', $initialRunIds)
                    ->update([
                        'kind' => ProcessingRunKind::Reprocessing->value,
                    ]);
            }, 'document_id');
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
