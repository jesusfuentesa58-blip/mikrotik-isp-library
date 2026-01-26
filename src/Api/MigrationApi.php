<?php
declare(strict_types=1);

namespace ISP\Mikrotik\Api;

use ISP\Mikrotik\Connection;
use ISP\Mikrotik\Commands\Migration\UpsertPppUser;

class MigrationApi
{
    public function __construct(private Connection $connection) {}

    /**
     * Migra (upsert) un usuario PPP
     * NO habilita, solo prepara
     */
    public function upsertPpp(array $data): bool
    {
        return (new UpsertPppUser(
            $this->connection,
            $data
        ))->execute();
    }
}
