<?php

namespace App\Models;

use App\Enums\ProcessingProfile;
use App\Enums\ProcessingRunStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'profile',
    'status',
    'profile_snapshot',
    'total_pages',
    'total_chunks',
    'vector_count',
    'vector_dimension',
    'stage_timings_ms',
    'warnings',
    'error_code',
    'failure_reason',
    'comparison_report',
    'temporary_artifact_ref',
    'temporary_expires_at',
    'qdrant_collection',
    'indexed_at',
    'selected_at',
    'discarded_at',
    'expired_at',
])]
class ProcessingRun extends Model
{
    protected $table = 'document_processing_runs';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'profile' => ProcessingProfile::class,
            'status' => ProcessingRunStatus::class,
            'profile_snapshot' => 'array',
            'stage_timings_ms' => 'array',
            'warnings' => 'array',
            'comparison_report' => 'array',
            'temporary_expires_at' => 'datetime',
            'indexed_at' => 'datetime',
            'selected_at' => 'datetime',
            'discarded_at' => 'datetime',
            'expired_at' => 'datetime',
        ];
    }

    /**
     * Get the document that owns this processing run.
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
