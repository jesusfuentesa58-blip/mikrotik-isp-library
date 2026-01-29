<?php
declare(strict_types=1);

namespace ISP\Mikrotik\Commands\Ppp;

use ISP\Mikrotik\Connection;
use ISP\Mikrotik\Contracts\CommandInterface;
use ISP\Mikrotik\Exceptions\{
    RouterOfflineException,
    ResourceNotFoundException
};
use RouterOS\Exceptions\ConnectException;
use RouterOS\Query;

class FindSecretByName implements CommandInterface
{
    public function __construct(
        private Connection $connection,
        private string $name
    ) {}

    public function execute(): array
    {
        try {
            $api = $this->connection->raw();

            $res = $api->query(
                (new Query('/ppp/secret/print'))->where('name', $this->name)
            )->read();

            if (!isset($res[0]['.id'])) {
                throw new ResourceNotFoundException("PPP secret {$this->name} no existe");
            }

            return $res[0];

        } catch (ConnectException $e) {
            throw new RouterOfflineException('Router offline', 0, $e);
        }
    }
}
