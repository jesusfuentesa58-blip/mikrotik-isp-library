<?php
declare(strict_types=1);

namespace Tests\Commands\Ppp\Profile;

use ISP\Mikrotik\Commands\Ppp\Profile\DeleteProfile;
use ISP\Mikrotik\Connection;
use PHPUnit\Framework\TestCase;
use RouterOS\Client;

class DeleteProfileTest extends TestCase
{
    public function test_delete_profile_successfully(): void
    {
        $client = $this->createMock(Client::class);

        $client->method('query')->willReturnSelf();
        $client->method('read')->willReturnOnConsecutiveCalls(
            [['.id' => '*5']], // print
            []                  // remove
        );

        $connection = $this->createMock(Connection::class);
        $connection->method('raw')->willReturn($client);

        $cmd = new DeleteProfile($connection, 'gold');
        $this->assertTrue($cmd->execute());
    }

    public function test_delete_profile_when_not_exists(): void
    {
        $client = $this->createMock(Client::class);

        $client->method('query')->willReturnSelf();
        $client->method('read')->willReturn([]);

        $connection = $this->createMock(Connection::class);
        $connection->method('raw')->willReturn($client);

        $cmd = new DeleteProfile($connection, 'no-existe');
        $this->assertTrue($cmd->execute());
    }
}
