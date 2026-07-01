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
        'user_id', 'parent_id', 'is_type', 'id_of_type', 'description', 'user_reacts', 'created_at', 'updated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'parent_id' => 'integer',
            'user_id' => 'integer',
            'id_of_type' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, Comments>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
