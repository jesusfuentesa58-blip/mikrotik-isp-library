<?php
declare(strict_types=1);

namespace ISP\Mikrotik\Commands\Ppp\Profile;

use ISP\Mikrotik\Connection;
use ISP\Mikrotik\Contracts\CommandInterface;
use ISP\Mikrotik\Exceptions\{
    RouterOfflineException,
    CommandFailedException
};
use RouterOS\Exceptions\ConnectException;
use RouterOS\Query;

class DeleteProfile implements CommandInterface
{
    public function __construct(
        private Connection $connection,
        private string $name
    ) {}

    /**
     * Elimina un PPP profile por nombre. Devuelve true si se eliminó o ya no existe.
     *
     * @return bool
     * @throws RouterOfflineException
     * @throws CommandFailedException
     */
    public function execute(): bool
    {
        try {
            $api = $this->connection->raw();

            // Buscar profile por nombre
            $res = $api->query(
                (new Query('/ppp/profile/print'))
                    ->where('name', $this->name)
            )->read();

            if (!isset($res[0]['.id'])) {
                // ya no existe
                return true;
            }

            // Eliminar por .id
            $api->query(
                (new Query('/ppp/profile/remove'))
                    ->equal('.id', $res[0]['.id'])
            )->read();

            return true;
        } catch (ConnectException $e) {
            throw new RouterOfflineException(
                "Router offline al eliminar profile {$this->name}", 0, $e
            );
        } catch (\Throwable $e) {
            throw new CommandFailedException(
                "Error eliminando PPP profile {$this->name}", 0, $e
            );
        }
    }
}
