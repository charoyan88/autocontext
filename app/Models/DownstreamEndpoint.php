<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DownstreamEndpoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'type',
        'endpoint_url',
        'config',
        'is_active',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the project that owns the endpoint.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Check if the endpoint is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }
}
