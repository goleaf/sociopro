<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feeling_and_activities extends Model
{
    use HasFactory;

    public $timestamps = false;

    /** @var string */
    protected $primaryKey = 'feeling_and_activity_id';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'type', 'title', 'icon', 'created_at', 'updated_at',
    ];
}
