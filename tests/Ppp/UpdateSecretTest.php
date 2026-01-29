<?php
declare(strict_types=1);

namespace Tests\Commands\Ppp;

use ISP\Mikrotik\Commands\Ppp\UpdateSecret;
use ISP\Mikrotik\Connection;
use ISP\Mikrotik\Exceptions\CommandFailedException;
use PHPUnit\Framework\TestCase;
use RouterOS\Client;

class UpdateSecretTest extends TestCase
{
    public function test_update_secret_successfully(): void
    {
        $client = $this->createMock(Client::class);

        $client->method('query')->willReturnSelf();
        $client->method('read')->willReturnOnConsecutiveCalls(
            [['.id' => '*1']], // print
            []                 // set
        );

        $connection = $this->createMock(Connection::class);
        $connection->method('raw')->willReturn($client);

        $cmd = new UpdateSecret($connection, 'cliente1', [
            'password' => 'nueva',
            'profile' => 'gold',
            'disabled' => false,
        ]);

        $this->assertTrue($cmd->execute());
    }

    public function test_update_secret_not_found(): void
    {
        $client = $this->createMock(Client::class);

        $client->method('query')->willReturnSelf();
        $client->method('read')->willReturn([]);

        $connection = $this->createMock(Connection::class);
        $connection->method('raw')->willReturn($client);

        $cmd = new UpdateSecret($connection, 'inexistente', []);
        $this->expectException(CommandFailedException::class);
        $cmd->execute();
    }
}
