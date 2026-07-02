<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Chat extends Model
{
    use HasFactory;

    protected $guarded = ['*'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'message_thrade' => 'integer',
            'reciver_id' => 'integer',
            'sender_id' => 'integer',
            'thumbsup' => 'integer',
            'reply_id' => 'integer',
            'read_status' => 'integer',
        ];
    }

    public function scopeForMessageThread(Builder $query, int $messageThreadId): Builder
    {
        return $query->where('message_thrade', $messageThreadId);
    }

    public function scopeUnreadForReceiver(Builder $query, int $receiverId): Builder
    {
        return $query->where('reciver_id', $receiverId)
            ->where('read_status', 0);
    }

    public function scopeBetweenParticipants(Builder $query, int $firstUserId, int $secondUserId): Builder
    {
        return $query->where(function (Builder $query) use ($firstUserId, $secondUserId): void {
            $query->where(function (Builder $query) use ($firstUserId, $secondUserId): void {
                $query->where('sender_id', $firstUserId)
                    ->where('reciver_id', $secondUserId);
            })->orWhere(function (Builder $query) use ($firstUserId, $secondUserId): void {
                $query->where('sender_id', $secondUserId)
                    ->where('reciver_id', $firstUserId);
            });
        });
    }

    public function scopeForParticipant(Builder $query, int $userId): Builder
    {
        return $query->where(function (Builder $query) use ($userId): void {
            $query->where('sender_id', $userId)
                ->orWhere('reciver_id', $userId);
        });
    }

    public function isParticipant(int $userId): bool
    {
        return (int) $this->sender_id === $userId
            || (int) $this->reciver_id === $userId;
    }

    public function messageThread(): BelongsTo
    {
        return $this->belongsTo(MessageThread::class, 'message_thrade');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reciver_id');
    }

    protected function messageThreadId(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): ?int => isset($attributes['message_thrade'])
                ? (int) $attributes['message_thrade']
                : null,
            set: fn (mixed $value): array => ['message_thrade' => $value],
        );
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
