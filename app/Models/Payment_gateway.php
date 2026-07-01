<?php

namespace App\Models;

use App\Enums\PaymentGatewayIdentifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment_gateway extends Model
{
    use HasFactory;

    protected $fillable = [
        'identifier', 'currency', 'title', 'description', 'keys', 'model_name', 'test_model', 'status', 'is_addon',
    ];

    public function scopeForIdentifier(Builder $query, string|PaymentGatewayIdentifier $identifier): Builder
    {
        $identifier = $identifier instanceof PaymentGatewayIdentifier ? $identifier->value : $identifier;

        return $query->where('identifier', $identifier);
    }
}
