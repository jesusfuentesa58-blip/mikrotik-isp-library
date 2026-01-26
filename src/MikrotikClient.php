<?php
declare(strict_types=1);

namespace ISP\Mikrotik;

use ISP\Mikrotik\Adapters\AdapterInterface;
use ISP\Mikrotik\Adapters\V6Adapter;
use ISP\Mikrotik\Adapters\V7Adapter;
use ISP\Mikrotik\Commands\System\HealthCheck;
use ISP\Mikrotik\Api\MigrationApi;
use RouterOS\Query;

class MikrotikClient
{
    private ?AdapterInterface $adapter = null;

    public function __construct(private Connection $connection)
    {
    }

    public function system(): HealthCheck
    {
        return new HealthCheck(
            $this->connection,
            $this->getAdapter()
        );
    }

    public function ppp()
    {
        // Esto ahora llamará al ppp() del adapter (v6 o v7)
        return $this->getAdapter()->ppp();
    }

    public function queues()
    {
        return new \ISP\Mikrotik\Api\Queues\SimpleQueueApi($this->connection);
    }

    public function migrations(): MigrationApi
    {
        return new MigrationApi($this->connection);
    }

    // ==========================
    // INTERNALS
    // ==========================

    protected function getAdapter(): AdapterInterface
    {
        if ($this->adapter !== null) {
            return $this->adapter;
        }

        $api = $this->connection->raw();
        $res = $api->query(new Query('/system/resource/print'))->read();
        $version = $res[0]['version'] ?? '6.0';

        $this->adapter = str_starts_with($version, '6.')
            ? new V6Adapter($this->connection)
            : new V7Adapter($this->connection);

        return $this->adapter;
    }

    public function provision(): bool
    {
        return $this->getAdapter()->provisionFirewall('corte_internet');
    }
}
