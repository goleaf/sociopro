<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedProduct extends Model
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
            'product_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, SavedProduct>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Marketplace, SavedProduct>
     */
    public function productData(): BelongsTo
    {
        return $this->product();
    }

    /**
     * @return BelongsTo<Marketplace, SavedProduct>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class, 'product_id');
    }
}
