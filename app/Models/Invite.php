<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invite extends Model
{
    use HasFactory;

    protected $guarded = ['*'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'invite_sender_id' => 'integer',
            'invite_reciver_id' => 'integer',
            'is_accepted' => 'integer',
            'event_id' => 'integer',
            'page_id' => 'integer',
            'group_id' => 'integer',
            'post_id' => 'integer',
        ];
    }
}
