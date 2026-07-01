<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    use HasFactory;

    protected $table = 'jobs';

    protected $fillable = [
        'user_id',
        'title',
        'category_id',
        'starting_salary_range',
        'ending_salary_range',
        'company',
        'type',
        'location',
        'description',
        'status',
        'start_date',
        'end_date',
        'is_published',
        'thumbnail',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
        ];
    }
}
