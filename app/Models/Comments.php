<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comments extends Model
{
    use HasFactory;

    public $timestamps = false;

    /** @var string */
    protected $primaryKey = 'comment_id';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'comment_id', 'user_id', 'parent_id', 'is_type', 'id_of_type', 'description', 'user_reacts', 'created_at', 'updated_at',
    ];

    /**
     * @return BelongsTo<User, Comments>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
