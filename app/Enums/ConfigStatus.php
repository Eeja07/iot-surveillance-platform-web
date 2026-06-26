<?php

namespace App\Enums;

enum ConfigStatus: string
{
    case Pending = 'Pending';
    case Queued = 'Queued';
    case Sending = 'Sending';
    case Applied = 'Applied';
    case Rejected = 'Rejected';
    case Expired = 'Expired';
    case Failed = 'Failed';
    case Timeout = 'Timeout';
    case Cancelled = 'Cancelled';
}
