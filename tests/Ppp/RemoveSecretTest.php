<?php
declare(strict_types=1);

namespace Tests\Commands\Ppp;

use ISP\Mikrotik\Commands\Ppp\RemoveSecret;
use ISP\Mikrotik\Connection;
use PHPUnit\Framework\TestCase;
use RouterOS\Client;

class RemoveSecretTest extends TestCase
{
    public function test_remove_secret_successfully(): void
    {
        $client = $this->createMock(Client::class);

        $client->method('query')->willReturnSelf();
        $client->method('read')->willReturnOnConsecutiveCalls(
            [['.id' => '*10']], // print
            []                   // remove
        );

        $connection = $this->createMock(Connection::class);
        $connection->method('raw')->willReturn($client);

        $cmd = new RemoveSecret($connection, 'cliente1');
        $this->assertTrue($cmd->execute());
    }

    public function test_remove_secret_when_not_exists(): void
    {
        $client = $this->createMock(Client::class);
        $client->method('query')->willReturnSelf();
        $client->method('read')->willReturn([]);

        $connection = $this->createMock(Connection::class);
        $connection->method('raw')->willReturn($client);

        $cmd = new RemoveSecret($connection, 'no-existe');
        $this->assertTrue($cmd->execute());
    }
}
