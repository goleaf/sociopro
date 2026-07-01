<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Group_member extends Model
{
    use HasFactory;

    public function getGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function getUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
