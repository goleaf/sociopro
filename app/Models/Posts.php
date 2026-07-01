<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Posts extends Model
{
    use HasFactory;

    public $timestamps = false;

    /** @var string */
    protected $primaryKey = 'post_id';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'post_id', 'user_id', 'publisher', 'publisher_id', 'post_type', 'privacy', 'tagged_user_ids', 'feel_and_activity', 'location', 'description', 'user_reacts', 'status', 'created_at', 'updated_at', 'album_image_id',
    ];

    /**
     * @return BelongsTo<User, Posts>
     */
    public function getUser()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<Media_files, Posts>
     */
    public function media_files()
    {
        return $this->hasMany(Media_files::class, 'post_id', 'post_id');
    }
}
