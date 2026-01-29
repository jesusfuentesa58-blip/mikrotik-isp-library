<?php
declare(strict_types=1);

namespace ISP\Mikrotik\Api;

use ISP\Mikrotik\Connection;
use ISP\Mikrotik\Commands\Ppp\Profile\CreateProfile;

class PppProfileApi
{
    /**
     * @param Connection $connection Necesaria para el comando CreateProfile
     * @param mixed $adapter Necesario para las consultas directas (get/update)
     */
    public function __construct(
        private Connection $connection,
        private $adapter // <-- AGREGAMOS EL ADAPTADOR AQUÍ
    ) {}

    public function create(
        string $name,
        string $rateLimit,
        ?string $localAddress = null,
        ?string $remotePool = null
    ): bool {
        return (new CreateProfile(
            $this->connection,
            $name,
            $rateLimit,
            $localAddress,
            $remotePool
        ))->execute();
    }

    /**
     * Busca un perfil por su nombre.
     */
    public function getProfile(string $name): ?array
    {
        // Ahora $this->adapter sí está definido y tiene el método
        return $this->adapter->getProfile($name);
    }

    /**
     * Actualiza los parámetros de un perfil existente.
     */
    public function updateProfile(string $id, array $data): bool
    {
        return $this->adapter->updateProfile($id, $data);
    }

    public function deleteProfile(string $name): bool
    {
        return (new DeleteProfile($this->connection, $name))->execute();
    }
}