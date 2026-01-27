<?php
declare(strict_types=1);

namespace ISP\Mikrotik\Api;

use ISP\Mikrotik\Connection;
use ISP\Mikrotik\Commands\Ppp\{
    ListSecrets,
    CreateSecret,
    DisableSecret,
    EnableSecret,
    ListActive,
    FirewallCut // Importado para limpieza
};
use ISP\Mikrotik\Adapters\AdapterInterface;

class PppApi
{
    public function __construct(
        private Connection $connection,
        private AdapterInterface $adapter // Inyectamos el adapter
    ) {}

    public function list(): array
    {
        return (new ListSecrets($this->connection))->execute();
    }

    /**
     * Muestra las conexiones activas actuales
     */
    public function active(): array
    {
        return (new ListActive($this->connection))->execute();
    }

    public function create(
        string $name,
        string $password,
        string $profile,
        string $service = 'pppoe',
        ?string $remoteAddress = null,
        ?string $localAddress = null,
        ?string $comment = null,
        bool $disabled = false
    ): bool {
        return (new CreateSecret(
            $this->connection,
            $name,
            $password,
            $profile,
            $service,
            $remoteAddress,
            $localAddress,
            $comment,
            $disabled
        ))->execute();
    }

    public function disable(string $name): bool
    {
        return (new DisableSecret($this->connection, $name))->execute();
    }

    public function enable(string $name): bool
    {
        return (new EnableSecret($this->connection, $name))->execute();
    }

    public function profiles(): PppProfileApi
    {
        // Pasamos el adaptador ($this->adapter) como segundo argumento
        return new PppProfileApi($this->connection, $this->adapter);
    }

    public function suspend(string $name, string $method = 'secret'): bool
    {
        if ($method === 'address_list') {
            // Pasamos $this->adapter al comando FirewallCut
            return (new \ISP\Mikrotik\Commands\Ppp\FirewallCut(
                $this->connection, 
                $this->adapter, 
                $name, 
                true
            ))->execute();
        }
        
        return $this->disable($name);
    }

    public function reactivate(string $name, string $method = 'secret'): bool
    {
        if ($method === 'address_list') {
            return (new \ISP\Mikrotik\Commands\Ppp\FirewallCut($this->connection, $this->adapter, $name, false))->execute();
        }
        return $this->enable($name);
    }
}