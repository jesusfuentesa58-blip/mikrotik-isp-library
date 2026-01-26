<?php
declare(strict_types=1);

namespace ISP\Mikrotik\Adapters;

use ISP\Mikrotik\Connection;
use ISP\Mikrotik\Api\PppApi;
use RouterOS\Query;

class V6Adapter implements AdapterInterface
{
    public function __construct(private Connection $connection)
    {
    }

    public function ppp(): PppApi
    {
        return new PppApi($this->connection, $this);
    }

    // ==========================
    // PING OPTIONS (v6 simple)
    // ==========================

    public function externalPingOptions(array $resource): array
    {
        return [
            'count' => 3,
            'extra' => []
        ];
    }

    public function gatewayPingOptions(array $resource): array
    {
        return [
            'count' => 2,
            'extra' => []
        ];
    }

    // ==========================
    // PARSERS (tu lógica v6 intacta)
    // ==========================

    public function parsePing(array $rows): float
    {
        if (empty($rows)) return 0.0;

        $times = [];

        foreach ($rows as $row) {
            if (isset($row['time'])) {
                $t = $row['time'];

                if (str_ends_with($t, 'ms')) {
                    $times[] = (float)str_replace('ms', '', $t);
                } elseif (is_numeric($t)) {
                    $times[] = (float)$t;
                }
            }
        }

        return count($times)
            ? round(array_sum($times) / count($times), 2)
            : 0.0;
    }

    public function parseTemperature(array $health): ?float
    {
        return $health[0]['temperature'] ?? null;
    }

    public function provisionFirewall(string $listName): bool
    {
        $api = $this->connection->raw();
        // Lógica v6: place-before=0 es vital
        $api->query(
            (new \RouterOS\Query('/ip/firewall/filter/add'))
                ->equal('action', 'drop')
                ->equal('chain', 'forward')
                ->equal('src-address-list', $listName)
                ->equal('comment', 'SISTEMA_GESTION_CORTE')
                ->equal('place-before', '0')
        )->read();
        return true;
    }

    public function toggleAddressList(string $address, string $listName, bool $add, ?string $comment = null): bool
    {
        $api = $this->connection->raw();
        
        if ($add) {
            $api->query((new Query('/ip/firewall/address-list/add'))
                ->equal('list', $listName)
                ->equal('address', $address)
                ->equal('comment', $comment ?? 'Corte Sistema'))->read();
        } else {
            $items = $api->query((new Query('/ip/firewall/address-list/print'))
                ->where('address', $address)
                ->where('list', $listName))->read();

            foreach ($items as $item) {
                $api->query((new Query('/ip/firewall/address-list/remove'))
                    ->equal('.id', $item['.id']))->read();
            }
        }
        return true;
    }
}
