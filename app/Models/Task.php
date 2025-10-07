<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'status',
        'date',
        'priority',
        'order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'order' => 'integer',
        'priority' => 'string',
    ];

    /**
     * The possible priority values.
     *
     * @var array<string>
     */
    public const PRIORITY_LEVELS = ['low', 'medium', 'high'];

    /**
     * Get the priority levels.
     *
     * @return array<string>
     */
    public static function getPriorityLevels(): array
    {
        return self::PRIORITY_LEVELS;
    }

    /**
     * Get the user that owns the task.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
