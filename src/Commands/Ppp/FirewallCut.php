<?php

namespace ISP\Mikrotik\Commands\Ppp;

use ISP\Mikrotik\Connection;
use ISP\Mikrotik\Adapters\AdapterInterface;
use ISP\Mikrotik\Contracts\CommandInterface;
use RouterOS\Query;

class FirewallCut implements CommandInterface
{
    public function __construct(
        private Connection $connection,
        private AdapterInterface $adapter, // <--- Agregamos el adapter aquí
        private string $name,
        private bool $applyBlock = true
    ) {}

    public function execute(): bool
    {
        $api = $this->connection->raw();
        $listName = 'corte_internet';

        // 1. Siempre actualizamos el Secret (para el futuro)
        $api->query(
            (new \RouterOS\Query('/ppp/secret/set'))
                ->equal('numbers', $this->name)
                ->equal('remote-address-list', $this->applyBlock ? $listName : 'none')
        )->read();

        // 2. Intentar corte inmediato si está activo
        $active = $api->query(
            (new \RouterOS\Query('/ppp/active/print'))->where('name', $this->name)
        )->read();

        if (!empty($active)) {
            $userIp = $active[0]['address'];
            // Llamamos al adapter para meter la IP al Firewall
            return $this->adapter->toggleAddressList(
                $userIp, 
                $listName, 
                $this->applyBlock, 
                "Corte: " . $this->name
            );
        }

        // Si llegamos aquí, el Secret se cambió pero no había IP activa
        // En Laravel podrías lanzar un aviso: "Usuario suspendido, se aplicará al conectar"
        return true; 
    }
}