<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
