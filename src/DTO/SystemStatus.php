<?php
declare(strict_types=1);

namespace ISP\Mikrotik\DTO;

use ISP\Mikrotik\Enums\RouterStatus;

class SystemStatus
{
    public function __construct(
        public RouterStatus $status,
        public bool $online,
        public string $identity,
        public string $model,
        public string $version,
        public string $uptime,
        public int $cpuLoad,
        public int $ramTotal,
        public int $ramFree,
        public ?float $temperature,
        public float $pingMs
    ) {}
}
