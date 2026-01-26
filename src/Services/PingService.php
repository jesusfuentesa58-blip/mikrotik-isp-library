<?php

namespace ISP\Mikrotik\Services;

use ISP\Mikrotik\Connection;
use RouterOS\Query;

class PingService
{
    public function __construct(private Connection $connection) {}

    /**
     * Ejecuta ping; para v7 usamos count=5 e intentamos forzar routing-table=main en el caller (HealthCheck).
     */
    public function run(string $address, int $count = 3, array $extra = []): array
    {
        $api = $this->connection->raw();
        $q = (new \RouterOS\Query('/ping'))
            ->equal('address', $address)
            ->equal('count', $count);

        foreach ($extra as $k => $v) {
            $q->equal($k, $v);
        }

        return $api->query($q)->read();
    }
}
