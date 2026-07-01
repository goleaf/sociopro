<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum PaytmTransactionStatus: int
{
    use HasValues;

    case Failed = 0;
    case Successful = 1;
    case Open = 2;
}
