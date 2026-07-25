<?php

namespace App\Enums;

enum ContactInfoRequestStatus: string
{
    case Pending = 'pending';
    case Granted = 'granted';
    case Rejected = 'rejected';
}
