<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AggregatedError extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'error_hash',
        'last_message',
        'level',
        'last_seen_at',
        'count_total',
        'count_since_last_deploy',
        'last_deployment_id',
        'sample_event',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'count_total' => 'integer',
        'count_since_last_deploy' => 'integer',
        'sample_event' => 'array',
    ];

    /**
     * Get the project that owns the error.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the last deployment associated with this error.
     */
    public function lastDeployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class, 'last_deployment_id');
    }
}
