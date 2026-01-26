<?php
declare(strict_types=1);

namespace ISP\Mikrotik\Api\Queues;

use ISP\Mikrotik\Connection;
use ISP\Mikrotik\Commands\Queues\AddSimpleQueue;
use ISP\Mikrotik\Commands\Queues\RemoveSimpleQueue;

class SimpleQueueApi
{
    public function __construct(private Connection $connection) {}

    public function add(
        string $name,
        string $target,
        string $maxLimit
    ): bool {
        return (new AddSimpleQueue(
            $this->connection,
            $name,
            $target,
            $maxLimit
        ))->execute();
    }

    public function remove(string $name): bool
    {
        return (new RemoveSimpleQueue(
            $this->connection,
            $name
        ))->execute();
    }
}
