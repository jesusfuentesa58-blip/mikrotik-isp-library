<?php
declare(strict_types=1);

namespace ISP\Mikrotik\DTO\Ppp;

class PppUser
{
    public function __construct(
        public string $name,
        public string $service,
        public string $profile,
        public bool $disabled,
        public bool $isFirewallBlocked = false // <--- Nueva propiedad
    ) {}
}
