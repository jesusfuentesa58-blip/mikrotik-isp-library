<?php
declare(strict_types=1);

namespace ISP\Mikrotik\Api;

use ISP\Mikrotik\Connection;
use ISP\Mikrotik\Commands\Ppp\Profile\CreateProfile;

class PppProfileApi
{
    public function __construct(private Connection $connection) {}

    public function create(
        string $name,
        string $rateLimit,
        ?string $localAddress = null,
        ?string $remotePool = null
    ): bool {
        return (new CreateProfile(
            $this->connection,
            $name,
            $rateLimit,
            $localAddress,
            $remotePool
        ))->execute();
    }
}
