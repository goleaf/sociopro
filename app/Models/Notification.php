<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $guarded = ['*'];

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

    public function getFundraiserData(): BelongsTo
    {
        return $this->belongsTo(Fundraiser::class, 'fundraiser_id');
    }
}
