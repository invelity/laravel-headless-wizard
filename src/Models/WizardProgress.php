<?php

declare(strict_types=1);

namespace WebSystem\WizardPackage\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $wizard_id
 * @property int|null $user_id
 * @property string|null $session_id
 * @property string $current_step
 * @property array $completed_steps
 * @property array $step_data
 * @property string $status
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $last_activity_at
 * @property array $metadata
 */
class WizardProgress extends Model
{
    use HasFactory;

    protected $table = 'wizard_progress';

    protected $fillable = [
        'wizard_id',
        'user_id',
        'session_id',
        'current_step_id',
        'current_step',
        'completed_steps',
        'step_data',
        'status',
        'started_at',
        'completed_at',
        'last_activity_at',
        'metadata',
    ];

    protected $casts = [
        'completed_steps' => 'array',
        'step_data' => 'encrypted:array',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    public function isComplete(): bool
    {
        return $this->status === 'completed';
    }

    public function isAbandoned(): bool
    {
        return $this->status === 'abandoned';
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function markAsAbandoned(): void
    {
        $this->update([
            'status' => 'abandoned',
        ]);
    }

    public function updateActivity(): void
    {
        $this->update([
            'last_activity_at' => now(),
        ]);
    }
}
