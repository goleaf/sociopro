<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message_thrade extends Model
{
    use HasFactory;

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
