<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum MediaFileType: string
{
    use HasValues;

    case Image = 'image';
    case Video = 'video';
}
