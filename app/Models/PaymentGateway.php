<?php

namespace App\Models;

use App\Enums\PaymentGatewayIdentifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    use HasFactory;

    protected $fillable = [
        'identifier', 'currency', 'title', 'description',
    ];

    protected $hidden = [
        'keys',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'keys' => 'array',
            'test_mode' => 'boolean',
            'status' => 'boolean',
            'is_addon' => 'boolean',
        ];
    }

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

        if (is_array($keys)) {
            return $keys;
        }

        if (! is_string($keys) || $keys === '') {
            return [];
        }

        $decodedKeys = json_decode($keys, true);

        return is_array($decodedKeys) ? $decodedKeys : [];
    }
}
