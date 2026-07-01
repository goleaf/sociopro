<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment_gateway extends Model
{
    use HasFactory;

    protected $fillable = [
        'identifier', 'currency', 'title', 'description', 'keys', 'model_name', 'test_model', 'status', 'is_addon'
    ];

    public function scopeForIdentifier(Builder $query, string $identifier): Builder
    {
        return $query->where('identifier', $identifier);
    }
}
