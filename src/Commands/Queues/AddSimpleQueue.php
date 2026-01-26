<?php
declare(strict_types=1);

namespace ISP\Mikrotik\Commands\Queues;

use ISP\Mikrotik\Connection;
use ISP\Mikrotik\Contracts\CommandInterface;
use RouterOS\Query;

class AddSimpleQueue implements CommandInterface
{
    public function __construct(
        private Connection $connection,
        private string $name,
        private string $target,
        private string $maxLimit
    ) {}

    public function execute(): bool
    {
        $api = $this->connection->raw();

        $api->query(
            (new Query('/queue/simple/add'))
                ->equal('name', $this->name)
                ->equal('target', $this->target)
                ->equal('max-limit', $this->maxLimit)
        )->read();

        return true;
    }
}
