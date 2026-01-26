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

class DisableSecret implements CommandInterface
{
    public function __construct(
        private Connection $connection,
        private string $name
    ) {}

    public function execute(): bool
    {
        try {
            $api = $this->connection->raw();

            $api->query(
                (new Query('/ppp/secret/set'))
                    ->equal('numbers', $this->name)
                    ->equal('disabled', 'yes')
            )->read();

            return true;

        } catch (ConnectException $e) {
            throw new RouterOfflineException(
                "Router offline al deshabilitar {$this->name}", 0, $e
            );
        } catch (\Throwable $e) {
            throw new CommandFailedException(
                "Error deshabilitando PPP secret {$this->name}", 0, $e
            );
        }
    }
}
