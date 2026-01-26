<?php

require __DIR__ . '/vendor/autoload.php';

use ISP\Mikrotik\Connection;
use ISP\Mikrotik\MikrotikClient;
use ISP\Mikrotik\Exceptions\{
    RouterOfflineException,
    CommandFailedException
};

/**
 * =====================================
 * ROUTERS (como vendrán desde DB Laravel)
 * =====================================
 */
$routers = [
    [
        'name' => 'RB-V7',
        'host' => '10.10.10.1',
        'user' => 'pulse_isp',
        'password' => '123456789',
        'port' => 8000,
    ],
    [
        'name' => 'RB-V6',
        'host' => '172.0.0.1',
        'user' => 'ManagerISP',
        'password' => '123456789',
        'port' => 8730,
    ],
];

/**
 * =====================================
 * CLIENTES (como vendrán desde DB)
 * =====================================
 */
$clientes = [
    [
        'name' => 'cliente1',
        'password' => '1234',
        'profile' => '10M',
        'remoteAddress' => '192.168.10.2',
        'comment' => 'Juan Perez',
        'disabled' => false,
    ],
    [
        'name' => 'cliente2',
        'password' => '1234',
        'profile' => '10M',
        'remoteAddress' => '192.168.10.3',
        'comment' => 'Maria Gomez',
        'disabled' => false,
    ],
    [
        'name' => 'cliente3',
        'password' => '1234',
        'profile' => '10M',
        'remoteAddress' => '192.168.10.4',
        'comment' => 'Carlos Ruiz',
        'disabled' => true,
    ],
];

/**
 * =====================================
 * LOOP MULTI ROUTER
 * =====================================
 */
foreach ($routers as $router) {

    echo "\n==============================\n";
    echo " ROUTER: {$router['name']}\n";
    echo "==============================\n";

    try {

        $connection = new Connection(
            host: $router['host'],
            user: $router['user'],
            password: $router['password'],
            port: $router['port']
        );

        $mt = new MikrotikClient($connection);

        /**
         * 1. HEALTH CHECK
         */
        echo "\n=== HEALTH CHECK ===\n";
        $status = $mt->system()->run();
        print_r($status);

        /**
         * 2. ENSURE PROFILES
         */
        echo "\n=== ENSURE PROFILES ===\n";
        $mt->ppp()->profiles()->create('10M', '10M/10M');
        echo "✔ Profiles OK\n";

        /**
         * 3. MIGRATION / UPSERT
         */
        echo "\n=== MIGRATION / SYNC ===\n";
        foreach ($clientes as $c) {
            $mt->migrations()->upsertPpp($c);
            echo "✔ {$c['name']} sincronizado\n";
        }

        /**
         * 4. APPLY STATE
         */
        echo "\n=== APPLY STATE FROM DB ===\n";
        foreach ($clientes as $c) {
            if ($c['disabled']) {
                $mt->ppp()->disable($c['name']);
                echo "⛔ {$c['name']} suspendido\n";
            } else {
                $mt->ppp()->enable($c['name']);
                echo "✅ {$c['name']} activo\n";
            }
        }

        /**
         * 5. FINAL STATE
         */
        echo "\n=== FINAL STATE ===\n";
        $final = $mt->ppp()->list();
        print_r($final);

        echo "\n✔ DONE ({$router['name']})\n";

    } catch (RouterOfflineException $e) {
        echo "❌ Router offline ({$router['name']}): {$e->getMessage()}\n";

    } catch (CommandFailedException $e) {
        echo "❌ Error SDK ({$router['name']}): {$e->getMessage()}\n";
        echo $e->getPrevious()?->getMessage() . "\n";

    } catch (\Throwable $e) {
        echo "❌ Error inesperado ({$router['name']}): {$e->getMessage()}\n";
    }
}

echo "\n=== ALL ROUTERS DONE ===\n";
