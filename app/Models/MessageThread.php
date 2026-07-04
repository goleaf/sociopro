<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessageThread extends Model
{
    use HasFactory;

    public const TABLE = 'message_thrades';

    public const SENDER_ID_COLUMN = 'sender_id';

    public const LEGACY_RECEIVER_ID_COLUMN = 'reciver_id';

    public const LEGACY_CHAT_CENTER_COLUMN = 'chatcenter';

    protected $table = self::TABLE;

    /**
     * @var list<string>
     */
    protected $fillable = [
        self::SENDER_ID_COLUMN,
        self::LEGACY_RECEIVER_ID_COLUMN,
        self::LEGACY_CHAT_CENTER_COLUMN,
        'receiver_id',
        'chat_center',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            self::LEGACY_RECEIVER_ID_COLUMN => 'integer',
            self::SENDER_ID_COLUMN => 'integer',
        ];
    }

    public function scopeBetweenUsers(Builder $query, int $firstUserId, int $secondUserId): Builder
    {
        return $query->where(function (Builder $query) use ($firstUserId, $secondUserId): void {
            $query->where(function (Builder $query) use ($firstUserId, $secondUserId): void {
                $query->where(self::SENDER_ID_COLUMN, $firstUserId)
                    ->where(self::LEGACY_RECEIVER_ID_COLUMN, $secondUserId);
            })->orWhere(function (Builder $query) use ($firstUserId, $secondUserId): void {
                $query->where(self::SENDER_ID_COLUMN, $secondUserId)
                    ->where(self::LEGACY_RECEIVER_ID_COLUMN, $firstUserId);
            });
        });
    }

    public function scopeBetweenParticipants(Builder $query, int $firstUserId, int $secondUserId): Builder
    {
        return $query->betweenUsers($firstUserId, $secondUserId);
    }

    public function scopeForParticipant(Builder $query, int $userId): Builder
    {
        return $query->where(function (Builder $query) use ($userId): void {
            $query->where(self::SENDER_ID_COLUMN, $userId)
                ->orWhere(self::LEGACY_RECEIVER_ID_COLUMN, $userId);
        });
    }

    /**
     * @return BelongsTo<User, MessageThread>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, self::SENDER_ID_COLUMN);
    }

    /**
     * @return BelongsTo<User, MessageThread>
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, self::LEGACY_RECEIVER_ID_COLUMN);
    }

    /**
     * @return HasMany<Chat, MessageThread>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Chat::class, Chat::LEGACY_MESSAGE_THREAD_ID_COLUMN);
    }

    protected function receiverId(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): ?int => isset($attributes['reciver_id'])
                ? (int) $attributes['reciver_id']
                : null,
            set: fn (mixed $value): array => ['reciver_id' => $value],
        );
    }

    protected function chatCenter(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): ?string => $attributes['chatcenter'] ?? null,
            set: fn (mixed $value): array => ['chatcenter' => $value],
        );
    }
}
