<?php
declare(strict_types=1);

namespace ISP\Mikrotik\Adapters;

use ISP\Mikrotik\Connection;
use ISP\Mikrotik\Api\PppApi;
use ISP\Mikrotik\Api\MigrationApi;
use ISP\Mikrotik\Api\Queues\SimpleQueueApi;
use RouterOS\Query;

class V7Adapter implements AdapterInterface
{
    public function __construct(private Connection $connection) {}

    /**
     * =================================
     * API FUNCIONAL (OBLIGATORIO)
     * =================================
     */
    public function ppp(): PppApi
    {
        return new PppApi($this->connection, $this);
    }

    public function queues(): SimpleQueueApi
    {
        return new SimpleQueueApi($this->connection);
    }

    public function migrations(): MigrationApi
    {
        return new MigrationApi($this->connection);
    }

    /**
     * =================================
     * PING OPTIONS (v7)
     * =================================
     */
    public function externalPingOptions(array $resource): array
    {
        return ['count' => 0, 'extra' => []];
    }

    public function gatewayPingOptions(array $resource): array
    {
        return ['count' => 1, 'extra' => []];
    }

    /**
     * En v7 el ping NO es ms
     * es semántico (hay ruta / no hay ruta)
     */
    public function parsePing(array $rows): float
    {
        return count($rows) > 0 ? 1.0 : 0.0;
    }

    /**
     * Detecta internet por default route
     */
    public function hasInternet(): bool
    {
        $api = $this->connection->raw();

        $routes = $api->query(
            (new Query('/ip/route/print'))
                ->where('dst-address', '0.0.0.0/0')
                ->where('active', 'true')
        )->read();

        return !empty($routes);
    }

    /**
     * =================================
     * HARDWARE PARSERS
     * =================================
     */
    public function parseTemperature(array $health): ?float
    {
        return null; // v7 no siempre reporta temp
    }

    public function parseCpu(array $resource): int
    {
        return (int)($resource['cpu-load'] ?? 0);
    }

    public function provisionFirewall(string $listName): bool
    {
        $api = $this->connection->raw();
        $exists = $api->query((new \RouterOS\Query('/ip/firewall/filter/print'))->where('comment', 'SISTEMA_GESTION_CORTE'))->read();
        
        if (!empty($exists)) return true;

        // En v7, agregamos y luego el router posiciona. 
        // Si necesitas que sea la primera, podrías usar un comando 'move' posterior.
        $api->query(
            (new \RouterOS\Query('/ip/firewall/filter/add'))
                ->equal('action', 'drop')
                ->equal('chain', 'forward')
                ->equal('src-address-list', $listName)
                ->equal('comment', 'SISTEMA_GESTION_CORTE')
        )->read();

        return true;
    }

    public function toggleAddressList(string $address, string $listName, bool $add, ?string $comment = null): bool
    {
        $api = $this->connection->raw();
        
        if ($add) {
            // AGREGAR A LA LISTA
            $api->query(
                (new \RouterOS\Query('/ip/firewall/address-list/add'))
                    ->equal('list', $listName)
                    ->equal('address', $address)
                    ->equal('comment', $comment ?? 'Corte Sistema')
            )->read();
        } else {
            // REMOVER DE LA LISTA
            $items = $api->query(
                (new \RouterOS\Query('/ip/firewall/address-list/print'))
                    ->where('address', $address)
                    ->where('list', $listName)
            )->read();

            foreach ($items as $item) {
                $api->query(
                    (new \RouterOS\Query('/ip/firewall/address-list/remove'))
                        ->equal('.id', $item['.id'])
                )->read();
            }
        }
        return true;
    }
}
