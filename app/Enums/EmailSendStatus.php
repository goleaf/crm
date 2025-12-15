<?php

declare(strict_types=1);

namespace App\Enums;

enum EmailSendStatus: string
{
    case PENDING = 'pending';
    case QUEUED = 'queued';
    case SENT = 'sent';
    case DELIVERED = 'delivered';
    case BOUNCED = 'bounced';
    case FAILED = 'failed';
    case UNSUBSCRIBED = 'unsubscribed';
}
