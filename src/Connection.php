<?php
declare(strict_types=1);

namespace ISP\Mikrotik;

use RouterOS\Client;
use RouterOS\Exceptions\ConnectException;

class Connection
{
    private ?Client $client = null;

    public function __construct(
        private string $host,
        private string $user,
        private string $password,
        private int $port = 8728,
        private int $timeout = 30 // Aumentado a 30s como base segura
    ) {
    }

    /**
     * Permite definir un timeout específico para operaciones pesadas
     * (Ej: Listar miles de conexiones activas)
     */
    public function withTimeout(int $seconds): self
    {
        $this->timeout = $seconds;
        return $this;
    }

    public function raw(): Client
    {
        if ($this->client !== null) {
            return $this->client;
        }

        try {
            $this->client = new Client([
                'host'    => $this->host,
                'user'    => $this->user,
                'pass'    => $this->password,
                'port'    => $this->port,
                'timeout' => $this->timeout,
            ]);

            return $this->client;

        } catch (ConnectException $e) {
            throw $e;
        }
    }
}