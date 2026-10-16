<?php

namespace App\Models;

use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    public const STATUSES = ['todo', 'in_progress', 'done'];
    public const PRIORITIES = ['low', 'medium', 'high'];
    public const SORTABLE = ['created_at', 'updated_at', 'due_date', 'priority', 'status', 'title'];

    // Mirror the column defaults so a freshly created model serializes them without a reload.
    protected $attributes = [
        'status' => 'todo',
        'priority' => 'medium',
    ];

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'assigned_user_id',
        'created_by',
        'due_date',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date:Y-m-d',
        ];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    /**
     * Applies the list endpoint's filters. Shared by the API and the CSV export job
     * so an export always matches what the user was looking at.
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->whereIn('status', (array) $v))
            ->when($filters['priority'] ?? null, fn ($q, $v) => $q->whereIn('priority', (array) $v))
            ->when($filters['assigned_user_id'] ?? null, fn ($q, $v) => $q->where('assigned_user_id', $v))
            ->when($filters['created_by'] ?? null, fn ($q, $v) => $q->where('created_by', $v))
            ->when($filters['due_from'] ?? null, fn ($q, $v) => $q->whereDate('due_date', '>=', $v))
            ->when($filters['due_to'] ?? null, fn ($q, $v) => $q->whereDate('due_date', '<=', $v))
            ->when($filters['search'] ?? null, function ($q, $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $q->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('description', 'like', $like));
            });
    }

    /**
     * Accepts "field" or "-field" (descending). Priority sorts by weight, not alphabetically.
     */
    public function scopeSortBy(Builder $query, ?string $sort): Builder
    {
        $sort = $sort ?: '-created_at';
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim($sort, '-');

        if (! in_array($field, self::SORTABLE, true)) {
            $field = 'created_at';
        }

        if ($field === 'priority') {
            return $query->orderByRaw("FIELD(priority, 'low', 'medium', 'high') {$direction}")->orderBy('id', 'desc');
        }

        return $query->orderBy($field, $direction)->orderBy('id', 'desc');
    }
}
