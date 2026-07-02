<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageThread extends Model
{
    use HasFactory;

    protected $table = 'message_thrades';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'sender_id',
        'reciver_id',
        'receiver_id',
        'chatcenter',
        'chat_center',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reciver_id' => 'integer',
            'sender_id' => 'integer',
        ];
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

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reciver_id');
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
