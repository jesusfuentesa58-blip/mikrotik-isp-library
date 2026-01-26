<?php

namespace ISP\Mikrotik\Commands\System;

use ISP\Mikrotik\Connection;
use ISP\Mikrotik\Contracts\CommandInterface;
use RouterOS\Query;

class ProvisionRouter implements CommandInterface
{
    // EL 'private' ES OBLIGATORIO PARA LA PROMOCIÓN DE PROPIEDADES
    public function __construct(private Connection $connection) {}

    public function execute(): bool
    {
        $api = $this->connection->raw();

        // 1. Verificamos si ya existe (Evitar duplicados)
        $exists = $api->query((new Query('/ip/firewall/filter/print'))
            ->where('comment', 'SISTEMA_GESTION_CORTE'))->read();
        
        if (!empty($exists)) {
            return true; 
        }

        // 2. Crear la regla de drop
        $query = (new Query('/ip/firewall/filter/add'))
            ->equal('action', 'drop')
            ->equal('chain', 'forward')
            ->equal('src-address-list', 'corte_internet')
            ->equal('comment', 'SISTEMA_GESTION_CORTE');

        $api->query($query)->read();

        return true;
    }
}