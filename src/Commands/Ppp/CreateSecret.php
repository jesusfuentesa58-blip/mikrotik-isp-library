<?php
declare(strict_types=1);

namespace ISP\Mikrotik\Commands\Ppp;

use ISP\Mikrotik\Connection;
use ISP\Mikrotik\Contracts\CommandInterface;
use ISP\Mikrotik\Exceptions\{
    RouterOfflineException,
    CommandFailedException
};
use RouterOS\Exceptions\ConnectException;
use RouterOS\Query;

class CreateSecret implements CommandInterface
{
    public function __construct(
        private Connection $connection,
        private string $name,
        private string $password,
        private string $profile,
        private string $service = 'pppoe',
        private ?string $remoteAddress = null,
        private ?string $localAddress = null,
        private ?string $comment = null,
        private bool $disabled = false
    ) {}

    public function execute(): bool
    {
        // COMMENT OBLIGATORIO (nombre del cliente)
        if ($this->comment === null) {
            throw new CommandFailedException(
                'El comment (nombre del cliente) es obligatorio al crear secret'
            );
        }

        try {
            $api = $this->connection->raw();

            $query = (new Query('/ppp/secret/add'))
                ->equal('name', $this->name)
                ->equal('password', $this->password)
                ->equal('profile', $this->profile)
                ->equal('service', $this->service)
                ->equal('disabled', $this->disabled ? 'yes' : 'no')
                ->equal('comment', $this->comment);

            if ($this->remoteAddress !== null) {
                $query->equal('remote-address', $this->remoteAddress);
            }

            if ($this->localAddress !== null) {
                $query->equal('local-address', $this->localAddress);
            }

            $api->query($query)->read();

            return true;

        } catch (ConnectException $e) {
            throw new RouterOfflineException(
                'Router offline al crear secret ' . $this->name, 0, $e
            );
        } catch (\Throwable $e) {
            throw new CommandFailedException(
                'Error creando PPP secret ' . $this->name, 0, $e
            );
        }
    }
}
