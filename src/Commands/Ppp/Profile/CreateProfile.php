<?php

namespace ISP\Mikrotik\Commands\Ppp\Profile;

use ISP\Mikrotik\Connection;
use ISP\Mikrotik\Contracts\CommandInterface;
use ISP\Mikrotik\Exceptions\{
    RouterOfflineException,
    CommandFailedException
};
use RouterOS\Exceptions\ConnectException;
use RouterOS\Query;

class CreateProfile implements CommandInterface
{
    public function __construct(
        private Connection $connection,
        private string $name,
        private string $rateLimit,
        private ?string $localAddress = null,
        private ?string $remotePool = null
    ) {}

    public function execute(): bool
    {
        try {
            $api = $this->connection->raw();

            $query = (new Query('/ppp/profile/add'))
                ->equal('name', $this->name)
                ->equal('rate-limit', $this->rateLimit);

            if ($this->localAddress) {
                $query->equal('local-address', $this->localAddress);
            }

            if ($this->remotePool) {
                $query->equal('remote-address', $this->remotePool);
            }

            $api->query($query)->read();
            return true;

        } catch (ConnectException $e) {
            throw new RouterOfflineException('Router offline creando profile', 0, $e);
        } catch (\Throwable $e) {
            throw new CommandFailedException('Error creando PPP profile', 0, $e);
        }
    }
}
