<?php
declare(strict_types=1);

namespace ISP\Mikrotik\Commands\System;

use ISP\Mikrotik\Connection;
use ISP\Mikrotik\DTO\SystemStatus;
use ISP\Mikrotik\Enums\RouterStatus;
use ISP\Mikrotik\Exceptions\RouterOfflineException;
use ISP\Mikrotik\Adapters\AdapterInterface;
use ISP\Mikrotik\Services\PingService;
use RouterOS\Query;

class HealthCheck
{
    public function __construct(
        private Connection $connection,
        private AdapterInterface $adapter
    ) {}

    public function run(): SystemStatus
    {
        try {
            $api = $this->connection->raw();

            // =====================
            // BASIC INFO (si esto falla → router muerto)
            // =====================
            $resource = $api->query(new Query('/system/resource/print'))->read()[0];
            $identity = $api->query(new Query('/system/identity/print'))->read()[0]['name'];

            // =====================
            // INTERNET CHECK
            // =====================
            if (method_exists($this->adapter, 'hasInternet')) {
                // v7 (CORRECTO)
                $hasInternet = $this->adapter->hasInternet();
                $extPing = $hasInternet ? 1.0 : 0.0;
            } else {
                // v6 (PING REAL)
                $extOpt = $this->adapter->externalPingOptions($resource);
                $extRows = (new PingService($this->connection))
                    ->run('1.1.1.1', $extOpt['count'], $extOpt['extra']);
                $extPing = $this->adapter->parsePing($extRows);
            }

            // =====================
            // TEMPERATURE
            // =====================
            $temp = null;
            try {
                $health = $api->query(new Query('/system/health/print'))->read();
                $temp = $this->adapter->parseTemperature($health);
            } catch (\Throwable $e) {}

            // =====================
            // STATUS (SIMPLE Y CORRECTO)
            // =====================
            if ($extPing === 0.0) {
                $status = RouterStatus::DEGRADED;
            } else {
                $status = RouterStatus::OK;
            }

            return new SystemStatus(
                status: $status,
                online: true,
                identity: $identity,
                model: $resource['board-name'] ?? 'unknown',
                version: $resource['version'],
                uptime: $resource['uptime'],
                cpuLoad: method_exists($this->adapter, 'parseCpu')
                    ? $this->adapter->parseCpu($resource)
                    : (int)($resource['cpu-load'] ?? 0),
                ramTotal: (int)$resource['total-memory'],
                ramFree: (int)$resource['free-memory'],
                temperature: $resource['temperature'] ?? $temp,
                pingMs: $extPing
            );

        } catch (\Throwable $e) {
            throw new RouterOfflineException('Router no responde a healthcheck', 0, $e);
        }
    }
}
