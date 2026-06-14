<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    public const SYNC_IDLE = 'idle';

    public const SYNC_PENDING = 'pending';

    public const SYNC_PROCESSING = 'processing';

    public const SYNC_COMPLETED = 'completed';

    public const SYNC_FAILED = 'failed';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'yandex_url',
        'yandex_org_id',
        'name',
        'avg_rating',
        'ratings_count',
        'reviews_count',
        'last_synced_at',
        'sync_status',
        'sync_error',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'avg_rating' => 'float',
            'ratings_count' => 'integer',
            'reviews_count' => 'integer',
            'last_synced_at' => 'datetime',
        ];
    }

    public function isSyncing(): bool
    {
        return in_array($this->sync_status, [self::SYNC_PENDING, self::SYNC_PROCESSING], true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
