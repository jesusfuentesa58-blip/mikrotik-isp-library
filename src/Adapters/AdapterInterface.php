<?php
declare(strict_types=1);

namespace ISP\Mikrotik\Adapters;

interface AdapterInterface
{
    /** Opciones de ping externo (internet) */
    public function externalPingOptions(array $resource): array;

    /** Opciones de ping local (router vivo) */
    public function gatewayPingOptions(array $resource): array;

    /** Convierte respuesta /ping a ms */
    public function parsePing(array $rows): float;

    /** Temperatura (si existe) */
    public function parseTemperature(array $health): ?float;

    /** Provisión de la regla de corte inicial */
    public function provisionFirewall(string $listName): bool;

    /** Gestión de IPs en la lista de firewall */
    public function toggleAddressList(string $address, string $listName, bool $add, ?string $comment = null): bool;

    /** Obtiene un perfil por nombre */
    public function getProfile(string $name): ?array;
    public function updateProfile(string $id, array $data): bool;
}

