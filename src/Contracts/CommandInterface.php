<?php
declare(strict_types=1);

namespace ISP\Mikrotik\Contracts;

interface CommandInterface
{
    public function execute(): mixed;
}
