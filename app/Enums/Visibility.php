<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum Visibility: string
{
    use HasValues;

    case Public = 'public';
    case Friends = 'friends';
    case Private = 'private';
}
