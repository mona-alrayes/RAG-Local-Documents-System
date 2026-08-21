<?php

namespace App\Models;

use App\Enums\DocumentProcessingComparisonStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Relationship identifiers are intentionally excluded from mass assignment,
 * including document_id, user_id, and all Processing Run IDs.
 *
 * These identifiers must be assigned through explicit relationships or
 * Domain/Orchestration logic after validating ownership and ensuring that
 * all referenced Runs belong to the same document and user.
 */
#[Fillable([
    'status',
    'trial_question',
    'decided_at',
    'expires_at',
])]
class DocumentProcessingComparison extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DocumentProcessingComparisonStatus::class,
            'decided_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Get the document being compared.
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Get the user who owns this comparison decision.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the cloud processing run.
     */
    public function cloudRun(): BelongsTo
    {
        return $this->belongsTo(
            ProcessingRun::class,
            'cloud_run_id',
        );
    }

    /**
     * Get the hybrid-local processing run.
     */
    public function hybridLocalRun(): BelongsTo
    {
        return $this->belongsTo(
            ProcessingRun::class,
            'hybrid_local_run_id',
        );
    }

    /**
     * Get the processing run selected as the winner.
     */
    public function selectedRun(): BelongsTo
    {
        return $this->belongsTo(
            ProcessingRun::class,
            'selected_run_id',
        );
    }
}
