<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum VideoCategory: string
{
    use HasValues;

    case Video = 'video';
    case Shorts = 'shorts';
}
