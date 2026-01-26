<?php
declare(strict_types=1);

namespace ISP\Mikrotik\Commands\Ppp;

use ISP\Mikrotik\Connection;
use ISP\Mikrotik\Contracts\CommandInterface;
use ISP\Mikrotik\DTO\Ppp\PppUser;
use RouterOS\Query;

class ListSecrets implements CommandInterface
{
    public function __construct(private Connection $connection) {}

    /**
     * Ejecuta y devuelve array de PppUser DTO
     * */
    public function execute(): array
    {
        $api = $this->connection->raw();

        $rows = $api->query(
            (new Query('/ppp/secret/print'))
        )->read();

        $users = [];

        foreach ($rows as $r) {
            if (!is_array($r)) continue;

            $name = $r['name'] ?? null;
            if ($name === null) continue; // no name -> ignorar

            // normalizar service y profile con valores por defecto
            $service = $r['service'] ?? ($r['type'] ?? 'pppoe'); // fallback 'pppoe' o 'any'
            $profile = $r['profile'] ?? 'default';

            // normalizar disabled: RouterOS puede retornar '' / 'true' / 'false' / 'yes' / 'no' / 1 / 0
            $disabledRaw = $r['disabled'] ?? null;
            $disabled = false;
            if ($disabledRaw === null) {
                $disabled = false;
            } elseif (is_numeric($disabledRaw)) {
                $disabled = ((int)$disabledRaw) !== 0;
            } else {
                $str = strtolower((string)$disabledRaw);
                $disabled = in_array($str, ['yes', 'true', '1'], true);
            }

            $users[] = new PppUser(
                name: (string)$name,
                service: (string)$service,
                profile: (string)$profile,
                disabled: $disabled
            );
        }

        return $users;
    }
}
