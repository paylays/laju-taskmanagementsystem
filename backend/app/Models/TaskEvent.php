<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'type',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public static function record(string $type, array $payload): self
    {
        return static::create(['type' => $type, 'payload' => $payload]);
    }
}
