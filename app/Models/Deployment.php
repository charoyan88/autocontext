<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deployment extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'version',
        'environment',
        'region',
        'started_at',
        'finished_at',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the project that owns the deployment.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Check if the deployment is still in progress.
     */
    public function isInProgress(): bool
    {
        return is_null($this->finished_at);
    }

    /**
     * Check if a given timestamp falls within the deployment window.
     */
    public function containsTimestamp(\DateTimeInterface $timestamp): bool
    {
        $ts = $timestamp->getTimestamp();
        $start = $this->started_at->getTimestamp();

        if ($this->finished_at) {
            $end = $this->finished_at->getTimestamp();
            return $ts >= $start && $ts <= $end;
        }

        return $ts >= $start;
    }
}
