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

    protected $hidden = [
        'keys',
    ];

    public function scopeForIdentifier(Builder $query, string|PaymentGatewayIdentifier $identifier): Builder
    {
        $identifier = $identifier instanceof PaymentGatewayIdentifier ? $identifier->value : $identifier;

        return $query->where('identifier', $identifier);
    }

    public function isEnabled(): bool
    {
        return (int) $this->getAttribute('status') === 1;
    }

    public function isInTestMode(): bool
    {
        return (int) $this->getAttribute('test_mode') === 1;
    }

    /**
     * @return array<string, mixed>
     */
    public function decodedKeys(): array
    {
        $keys = $this->getAttribute('keys');

        return json_decode(is_string($keys) ? $keys : '', true) ?: [];
    }
}
