<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chat extends Model
{
    use HasFactory;

    public const LEGACY_MESSAGE_THREAD_ID_COLUMN = 'message_thrade';

    public const MESSAGE_THREAD_ID_COLUMN = 'message_thread_id';

    public const SENDER_ID_COLUMN = 'sender_id';

    public const LEGACY_RECEIVER_ID_COLUMN = 'reciver_id';

    public const RECEIVER_ID_COLUMN = 'receiver_id';

    public const LEGACY_CHAT_CENTER_COLUMN = 'chatcenter';

    public const CHAT_CENTER_COLUMN = 'chat_center';

    public const READ_STATUS_COLUMN = 'read_status';

    protected $guarded = ['*'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            self::LEGACY_MESSAGE_THREAD_ID_COLUMN => 'integer',
            self::MESSAGE_THREAD_ID_COLUMN => 'integer',
            self::LEGACY_RECEIVER_ID_COLUMN => 'integer',
            self::RECEIVER_ID_COLUMN => 'integer',
            self::SENDER_ID_COLUMN => 'integer',
            'thumbsup' => 'integer',
            'reply_id' => 'integer',
            self::READ_STATUS_COLUMN => 'integer',
        ];
    }

    public function scopeForThread(Builder $query, int $messageThreadId): Builder
    {
        return $query->where(function (Builder $query) use ($messageThreadId): void {
            $query->where(self::MESSAGE_THREAD_ID_COLUMN, $messageThreadId)
                ->orWhere(self::LEGACY_MESSAGE_THREAD_ID_COLUMN, $messageThreadId);
        });
    }

    public function scopeForMessageThread(Builder $query, int $messageThreadId): Builder
    {
        return $query->forThread($messageThreadId);
    }

    public function scopeUnreadForReceiver(Builder $query, int $receiverId): Builder
    {
        return $query->where(function (Builder $query) use ($receiverId): void {
            $query->where(self::RECEIVER_ID_COLUMN, $receiverId)
                ->orWhere(self::LEGACY_RECEIVER_ID_COLUMN, $receiverId);
        })
            ->where(self::READ_STATUS_COLUMN, 0);
    }

    public function scopeBetweenUsers(Builder $query, int $firstUserId, int $secondUserId): Builder
    {
        return $query->where(function (Builder $query) use ($firstUserId, $secondUserId): void {
            $query->where(function (Builder $query) use ($firstUserId, $secondUserId): void {
                $query->where(self::SENDER_ID_COLUMN, $firstUserId)
                    ->where(function (Builder $query) use ($secondUserId): void {
                        $query->where(self::RECEIVER_ID_COLUMN, $secondUserId)
                            ->orWhere(self::LEGACY_RECEIVER_ID_COLUMN, $secondUserId);
                    });
            })->orWhere(function (Builder $query) use ($firstUserId, $secondUserId): void {
                $query->where(self::SENDER_ID_COLUMN, $secondUserId)
                    ->where(function (Builder $query) use ($firstUserId): void {
                        $query->where(self::RECEIVER_ID_COLUMN, $firstUserId)
                            ->orWhere(self::LEGACY_RECEIVER_ID_COLUMN, $firstUserId);
                    });
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
                ->orWhere(self::RECEIVER_ID_COLUMN, $userId)
                ->orWhere(self::LEGACY_RECEIVER_ID_COLUMN, $userId);
        });
    }

    public function isParticipant(int $userId): bool
    {
        return (int) $this->sender_id === $userId
            || (int) $this->receiver_id === $userId
            || (int) $this->reciver_id === $userId;
    }

    /**
     * @return BelongsTo<MessageThread, Chat>
     */
    public function messageThread(): BelongsTo
    {
        return $this->belongsTo(MessageThread::class, self::LEGACY_MESSAGE_THREAD_ID_COLUMN);
    }

    /**
     * @return BelongsTo<User, Chat>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, self::SENDER_ID_COLUMN);
    }

    /**
     * @return BelongsTo<User, Chat>
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, self::LEGACY_RECEIVER_ID_COLUMN);
    }

    /**
     * @return HasMany<MediaFile, Chat>
     */
    public function mediaFiles(): HasMany
    {
        return $this->hasMany(MediaFile::class, 'chat_id');
    }

    public function setAttribute($key, $value)
    {
        if ($key === self::LEGACY_CHAT_CENTER_COLUMN) {
            $this->attributes[self::LEGACY_CHAT_CENTER_COLUMN] = $value;
            $this->attributes[self::CHAT_CENTER_COLUMN] = $value;

            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    protected function messageThreadId(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): ?int => isset($attributes[self::MESSAGE_THREAD_ID_COLUMN])
                ? (int) $attributes[self::MESSAGE_THREAD_ID_COLUMN]
                : (isset($attributes[self::LEGACY_MESSAGE_THREAD_ID_COLUMN])
                    ? (int) $attributes[self::LEGACY_MESSAGE_THREAD_ID_COLUMN]
                    : null),
            set: fn (mixed $value): array => [
                self::MESSAGE_THREAD_ID_COLUMN => $value,
                self::LEGACY_MESSAGE_THREAD_ID_COLUMN => $value,
            ],
        );
    }

    protected function messageThrade(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): ?int => isset($attributes[self::LEGACY_MESSAGE_THREAD_ID_COLUMN])
                ? (int) $attributes[self::LEGACY_MESSAGE_THREAD_ID_COLUMN]
                : (isset($attributes[self::MESSAGE_THREAD_ID_COLUMN])
                    ? (int) $attributes[self::MESSAGE_THREAD_ID_COLUMN]
                    : null),
            set: fn (mixed $value): array => [
                self::LEGACY_MESSAGE_THREAD_ID_COLUMN => $value,
                self::MESSAGE_THREAD_ID_COLUMN => $value,
            ],
        );
    }

    protected function receiverId(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): ?int => isset($attributes[self::RECEIVER_ID_COLUMN])
                ? (int) $attributes[self::RECEIVER_ID_COLUMN]
                : (isset($attributes[self::LEGACY_RECEIVER_ID_COLUMN])
                    ? (int) $attributes[self::LEGACY_RECEIVER_ID_COLUMN]
                    : null),
            set: fn (mixed $value): array => [
                self::RECEIVER_ID_COLUMN => $value,
                self::LEGACY_RECEIVER_ID_COLUMN => $value,
            ],
        );
    }

    protected function reciverId(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): ?int => isset($attributes[self::LEGACY_RECEIVER_ID_COLUMN])
                ? (int) $attributes[self::LEGACY_RECEIVER_ID_COLUMN]
                : (isset($attributes[self::RECEIVER_ID_COLUMN])
                    ? (int) $attributes[self::RECEIVER_ID_COLUMN]
                    : null),
            set: fn (mixed $value): array => [
                self::LEGACY_RECEIVER_ID_COLUMN => $value,
                self::RECEIVER_ID_COLUMN => $value,
            ],
        );
    }

    protected function chatCenter(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): ?string => $attributes[self::CHAT_CENTER_COLUMN]
                ?? $attributes[self::LEGACY_CHAT_CENTER_COLUMN]
                ?? null,
            set: fn (mixed $value): array => [
                self::CHAT_CENTER_COLUMN => $value,
                self::LEGACY_CHAT_CENTER_COLUMN => $value,
            ],
        );
    }
}
