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

class UpdateSecret implements CommandInterface
{
    public function __construct(
        private Connection $connection,
        private string $name,
        private array $data
    ) {}

    public function execute(): bool
    {
        if (empty($this->data)) {
            return true; // nada que actualizar
        }

        try {
            $api = $this->connection->raw();

            // 1. BUSCAR SECRET POR NAME (lookup correcto)
            $res = $api->query(
                (new Query('/ppp/secret/print'))->where('name', $this->name)
            )->read();

            if (!isset($res[0]['.id'])) {
                throw new ResourceNotFoundException(
                    "PPP secret '{$this->name}' no existe en el router"
                );
            }

            $id = $res[0]['.id'];

            // 2. CONSTRUIR UPDATE
            $q = new Query('/ppp/secret/set');
            $q->equal('.id', $id);

            foreach ($this->data as $k => $v) {
                if ($k === 'disabled') {
                    $q->equal('disabled', $v ? 'yes' : 'no');
                    continue;
                }

                if ($v === null || $v === '') {
                    continue;
                }

                $q->equal($k, (string)$v);
            }

            // 3. EJECUTAR
            $api->query($q)->read();

            return true;

        } catch (ConnectException $e) {
            throw new RouterOfflineException(
                "Router offline al actualizar secret '{$this->name}'", 0, $e
            );
        } catch (ResourceNotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new CommandFailedException(
                "Error actualizando PPP secret '{$this->name}'", 0, $e
            );
        }
    }
}
