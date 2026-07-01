<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Marketplace extends Model
{
    use HasFactory;

    protected $guarded = ['*'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'currency_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, Marketplace>
     */
    public function getUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Category, Marketplace>
     */
    public function getCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category');
    }

    /**
     * @return BelongsTo<Brand, Marketplace>
     */
    public function getBrand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand');
    }

    /**
     * @return BelongsTo<Currency, Marketplace>
     */
    public function getCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }
}
