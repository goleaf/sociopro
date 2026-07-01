<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $guarded = ['*'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sender_user_id' => 'integer',
            'reciver_user_id' => 'integer',
            'event_id' => 'integer',
            'page_id' => 'integer',
            'group_id' => 'integer',
            'fundraiser_id' => 'integer',
            'status' => 'integer',
            'view' => 'integer',
        ];
    }

    public function getUserData(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function getEventData(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function getGroupData(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function getPageData(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'page_id');
    }

    public function getFundraiserData(): BelongsTo
    {
        return $this->belongsTo(Fundraiser::class, 'fundraiser_id');
    }
}
