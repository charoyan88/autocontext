<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'status',
        'default_region',
        'user_id',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    /**
     * Get the API keys for the project.
     */
    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    /**
     * Get the owner of the project.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the deployments for the project.
     */
    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }

    /**
     * Get the downstream endpoints for the project.
     */
    public function downstreamEndpoints(): HasMany
    {
        return $this->hasMany(DownstreamEndpoint::class);
    }

    /**
     * Get the primary downstream endpoint for the project.
     */
    public function downstreamEndpoint(): HasOne
    {
        return $this->hasOne(DownstreamEndpoint::class)->latestOfMany();
    }

    /**
     * Get the aggregated errors for the project.
     */
    public function aggregatedErrors(): HasMany
    {
        return $this->hasMany(AggregatedError::class);
    }

    /**
     * Get the hourly stats for the project.
     */
    public function hourlyStats(): HasMany
    {
        return $this->hasMany(ProjectHourlyStat::class);
    }

    /**
     * Check if the project is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
