<?php
declare(strict_types=1);

namespace ISP\Mikrotik\Commands\Migration;

use ISP\Mikrotik\Connection;
use ISP\Mikrotik\Contracts\CommandInterface;
use ISP\Mikrotik\Exceptions\{
    RouterOfflineException,
    CommandFailedException
};
use RouterOS\Exceptions\ConnectException;
use RouterOS\Query;

class UpsertPppUser implements CommandInterface
{
    public function __construct(
        private Connection $connection,
        private array $data
    ) {}

    public function execute(): bool
    {
        // COMMENT OBLIGATORIO (nombre del cliente)
        if (!isset($this->data['comment'])) {
            throw new CommandFailedException(
                'El campo comment (nombre del cliente) es obligatorio en migración'
            );
        }

        try {
            $api = $this->connection->raw();

            // Por defecto: ACTIVO
            $disabled = ($this->data['disabled'] ?? false) ? 'yes' : 'no';

            /**
             * UPDATE directo (idempotente)
             */
            $query = (new Query('/ppp/secret/set'))
                ->equal('numbers', $this->data['name'])
                ->equal('password', $this->data['password'])
                ->equal('profile', $this->data['profile'])
                ->equal('service', 'pppoe')
                ->equal('disabled', $disabled)
                ->equal('comment', $this->data['comment']);

            if (isset($this->data['remoteAddress'])) {
                $query->equal('remote-address', $this->data['remoteAddress']);
            }

            $api->query($query)->read();

            /**
             * CREATE si no existía
             */
            $add = (new Query('/ppp/secret/add'))
                ->equal('name', $this->data['name'])
                ->equal('password', $this->data['password'])
                ->equal('profile', $this->data['profile'])
                ->equal('service', 'pppoe')
                ->equal('disabled', $disabled)
                ->equal('comment', $this->data['comment']);

            if (isset($this->data['remoteAddress'])) {
                $add->equal('remote-address', $this->data['remoteAddress']);
            }

            $api->query($add)->read();

            return true;

        } catch (ConnectException $e) {
            throw new RouterOfflineException(
                'Router offline durante migración PPP', 0, $e
            );
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'already exists')) {
                return true; // idempotente
            }

            throw new CommandFailedException(
                'Error migrando PPP secret ' . $this->data['name'], 0, $e
            );
        }
    }
}
