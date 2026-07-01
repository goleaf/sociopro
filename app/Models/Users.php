<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Users extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_name', 'email', 'name', 'nickname', 'gender', 'studied_at', 'address', 'profession', 'job', 'marital_status', 'phone', 'date_of_birth', 'about', 'photo', 'cover_photo',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'integer',
        ];
    }
}
