<?php

namespace Tests\Feature\Documents;

use App\Enums\ProcessingProfile;
use App\Enums\ProcessingRunKind;
use App\Enums\ProcessingRunStatus;
use App\Models\ProcessingRun;
use App\Models\User;
use App\Services\Documents\DocumentStorageService;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProcessingRunSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_progress_columns_and_model_casts_are_available(): void
    {
        Storage::fake('documents');

        $document = app(DocumentStorageService::class)->storePermanent(
            User::factory()->create(),
            UploadedFile::fake()->createWithContent(
                'trusted-notes.txt',
                "Trusted document content.\n",
            ),
        );

        $processingRun = $document->processingRuns()->create([
            'profile' => ProcessingProfile::Cloud,
            'status' => ProcessingRunStatus::Processing,
            'kind' => ProcessingRunKind::Initial,
            'profile_snapshot' => [],
            'stage_timings_ms' => [],
            'started_at' => now(),
            'indexing_started_at' => now(),
            'failed_at' => now(),
        ])->fresh();

        $this->assertTrue(Schema::hasColumns('document_processing_runs', [
            'kind',
            'started_at',
            'indexing_started_at',
            'failed_at',
        ]));
        $this->assertSame(ProcessingRunKind::Initial, $processingRun->kind);
        $this->assertInstanceOf(CarbonInterface::class, $processingRun->started_at);
        $this->assertInstanceOf(
            CarbonInterface::class,
            $processingRun->indexing_started_at,
        );
        $this->assertInstanceOf(CarbonInterface::class, $processingRun->failed_at);
        $this->assertInstanceOf(ProcessingRun::class, $processingRun);
    }

    public function test_existing_runs_are_backfilled_by_document_in_id_order(): void
    {
        Storage::fake('documents');

        $user = User::factory()->create();
        $storage = app(DocumentStorageService::class);
        $documents = collect([
            $storage->storePermanent(
                $user,
                UploadedFile::fake()->createWithContent(
                    'first-document.txt',
                    "First document content.\n",
                ),
            ),
            $storage->storePermanent(
                $user,
                UploadedFile::fake()->createWithContent(
                    'second-document.txt',
                    "Second document content.\n",
                ),
            ),
        ]);

        foreach ([3, 2] as $documentIndex => $runCount) {
            for ($runIndex = 0; $runIndex < $runCount; $runIndex++) {
                $documents[$documentIndex]->processingRuns()->create([
                    'profile' => ProcessingProfile::Cloud,
                    'status' => ProcessingRunStatus::Processing,
                    'kind' => ProcessingRunKind::Initial,
                    'profile_snapshot' => [],
                    'stage_timings_ms' => [],
                ]);
            }
        }

        $migration = require database_path(
            'migrations/2026_09_03_060000_add_progress_fields_to_document_processing_runs_table.php',
        );
        $migration->down();
        $migration->up();

        foreach ($documents as $document) {
            $runs = ProcessingRun::query()
                ->where('document_id', $document->id)
                ->orderBy('id')
                ->get();

            $this->assertSame(ProcessingRunKind::Initial, $runs->first()->kind);

            $runs->skip(1)->each(function (ProcessingRun $run): void {
                $this->assertSame(ProcessingRunKind::Reprocessing, $run->kind);
            });
        }
    }
}
