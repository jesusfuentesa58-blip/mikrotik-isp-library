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

    public function getProfile(string $name): ?array
    {
        // 1. Obtenemos el cliente desde la conexión
        $api = $this->connection->raw();

        // 2. Usamos el objeto Query estándar
        $query = (new \RouterOS\Query('/ppp/profile/print'))
            ->where('name', $name);

        $response = $api->query($query)->read();

        return $response[0] ?? null;
    }

    /**
     * Actualiza un perfil existente en RouterOS v6
     */
    public function updateProfile(string $id, array $data): bool
    {
        $api = $this->connection->raw();
        
        $query = new \RouterOS\Query('/ppp/profile/set');
        $query->equal('.id', $id);
        
        foreach ($data as $key => $value) {
            $query->equal($key, (string) $value);
        }
        
        $api->query($query)->read();
        
        return true; 
    }
}
