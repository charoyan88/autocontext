<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectHourlyStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'hour_ts',
        'incoming_count',
        'outgoing_count',
        'filtered_count',
        'deployment_error_count',
        'forward_failed_count',
    ];

    protected $casts = [
        'hour_ts' => 'datetime',
        'incoming_count' => 'integer',
        'outgoing_count' => 'integer',
        'filtered_count' => 'integer',
        'deployment_error_count' => 'integer',
        'forward_failed_count' => 'integer',
    ];

    /**
     * Get the project that owns the stats.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Calculate savings percentage.
     */
    public function savingsPercentage(): float
    {
        if ($this->incoming_count === 0) {
            return 0.0;
        }

        return round((1 - ($this->outgoing_count / $this->incoming_count)) * 100, 2);
    }
}
