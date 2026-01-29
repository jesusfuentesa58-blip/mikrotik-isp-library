<?php
declare(strict_types=1);

namespace ISP\Mikrotik\Commands\Ppp;

use ISP\Mikrotik\Connection;
use ISP\Mikrotik\Contracts\CommandInterface;
use ISP\Mikrotik\Exceptions\{
    RouterOfflineException,
    CommandFailedException,
    ResourceNotFoundException
};
use RouterOS\Exceptions\ConnectException;
use RouterOS\Query;

class RemoveSecret implements CommandInterface
{
    public function __construct(
        private Connection $connection,
        private string $name
    ) {}

    public function execute(): bool
    {
        try {
            $api = $this->connection->raw();

            // 1. Buscar secret por name
            $res = $api->query(
                (new Query('/ppp/secret/print'))->where('name', $this->name)
            )->read();

            if (!isset($res[0]['.id'])) {
                // No existe: para delete es OK (idempotente)
                return true;
            }

            $id = $res[0]['.id'];

            // 2. Eliminar por .id
            $api->query(
                (new Query('/ppp/secret/remove'))->equal('.id', $id)
            )->read();

            return true;

        } catch (ConnectException $e) {
            throw new RouterOfflineException(
                "Router offline al eliminar PPP secret '{$this->name}'", 0, $e
            );
        } catch (\Throwable $e) {
            throw new CommandFailedException(
                "Error eliminando PPP secret '{$this->name}'", 0, $e
            );
        }
    }
}
