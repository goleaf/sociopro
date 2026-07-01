<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum PostType: string
{
    use HasValues;

    case General = 'general';
    case Event = 'event';
    case LiveStreaming = 'live_streaming';
    case Share = 'share';
    case ProfilePicture = 'profile_picture';
    case CoverPhoto = 'cover_photo';
    case Fundraiser = 'fundraiser';
}
