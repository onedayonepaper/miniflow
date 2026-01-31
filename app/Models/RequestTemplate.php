<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RequestTemplate extends Model
{
    protected $fillable = [
        'name',
        'type',
        'description',
        'schema',
        'default_approval_line',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'schema' => 'array',
        'default_approval_line' => 'array',
        'is_active' => 'boolean',
    ];

    // Relations
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(ApprovalRequest::class, 'template_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // Helpers
    public function getFieldsAttribute(): array
    {
        return $this->schema['fields'] ?? [];
    }

    public function getDefaultStepsAttribute(): array
    {
        return $this->default_approval_line['steps'] ?? [];
    }
}
