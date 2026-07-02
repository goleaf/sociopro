<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'chatcenter',
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
}
