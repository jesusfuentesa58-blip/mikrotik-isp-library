<?php

namespace ISP\Mikrotik\Commands\Ppp;

use ISP\Mikrotik\Connection;
use ISP\Mikrotik\Adapters\AdapterInterface;
use ISP\Mikrotik\Contracts\CommandInterface;
use RouterOS\Query;

class FirewallRestore implements CommandInterface
{
    public function __construct(
        private Connection $connection,
        private AdapterInterface $adapter,
        private string $name
    ) {}

    public function execute(): bool
    {
        $api = $this->connection->raw();
        $listName = 'corte_internet'; // Debe coincidir con el usado en FirewallCut

        // 1. Limpiamos el Secret (Persistencia)
        // Esto asegura que si el router se reinicia o el cliente reconecta,
        // NO vuelva a caer en la lista de corte.
        $api->query(
            (new Query('/ppp/secret/set'))
                ->equal('numbers', $this->name)
                ->equal('remote-address-list', 'none') // 'none' quita la lista
        )->read();

        // 2. Gestionar sesión activa (Inmediatez)
        // Buscamos si el usuario está conectado ahora mismo para sacarlo de la lista ya.
        $active = $api->query(
            (new Query('/ppp/active/print'))->where('name', $this->name)
        )->read();

        if (!empty($active) && isset($active[0]['address'])) {
            $userIp = $active[0]['address'];
            
            // Llamamos al adapter pasando FALSE en $applyBlock
            return $this->adapter->toggleAddressList(
                ip: $userIp, 
                list: $listName, 
                block: false, // <--- FALSE para desbloquear/restaurar
                comment: "Restaurado: " . $this->name
            );
        }

        // Si no está conectado, el paso 1 ya garantizó que al conectar tenga internet.
        return true;
    }
}