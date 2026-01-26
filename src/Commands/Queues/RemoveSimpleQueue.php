<?php
declare(strict_types=1);

namespace ISP\Mikrotik\Commands\Queues;

use ISP\Mikrotik\Connection;
use ISP\Mikrotik\Contracts\CommandInterface;
use RouterOS\Query;

class RemoveSimpleQueue implements CommandInterface
{
    public function __construct(
        private Connection $connection,
        private string $name
    ) {}

    public function execute(): bool
    {
        $api = $this->connection->raw();

        $res = $api->query(
            (new Query('/queue/simple/print'))
                ->where('name', $this->name)
        )->read();

        if (!isset($res[0]['.id'])) {
            return true; // ya no existe
        }

        $api->query(
            (new Query('/queue/simple/remove'))
                ->equal('.id', $res[0]['.id'])
        )->read();

        return true;
    }
}
