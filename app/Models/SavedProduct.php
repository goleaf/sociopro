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
     * @return BelongsTo<Marketplace, SavedProduct>
     */
    public function productData(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class, 'product_id');
    }
}
