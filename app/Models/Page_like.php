<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page_like extends Model
{
    use HasFactory;

    protected $guarded = ['*'];

    public function pageData()
    {
        return $this->belongsTo(Page::class, 'page_id');
    }
}
