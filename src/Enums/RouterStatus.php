<?php
declare(strict_types=1);

namespace ISP\Mikrotik\Enums;

enum RouterStatus: string
{
    case OK = 'ok';
    case DEGRADED = 'degraded';
    case OFFLINE = 'offline';
}
