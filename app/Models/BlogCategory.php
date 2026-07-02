<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlogCategory extends Model
{
    use HasFactory;

    protected $guarded = ['*'];

    protected $table = 'blogcategories';

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('id');
    }

    public function scopeForSelect(Builder $query): Builder
    {
        return $query
            ->select(['id', 'name'])
            ->ordered();
    }
}
