<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Page_like extends Model
{
    use HasFactory;

    protected $guarded = ['*'];

    public function pageData(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'page_id');
    }
}
