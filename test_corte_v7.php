<?php

require __DIR__ . '/vendor/autoload.php';

use ISP\Mikrotik\Connection;
use ISP\Mikrotik\MikrotikClient;
use ISP\Mikrotik\Commands\System\ProvisionRouter;
use ISP\Mikrotik\Exceptions\{RouterOfflineException, CommandFailedException};

/**
 * SIMULACIÓN DE DATOS DESDE LA DB DE LARAVEL
 */
$routerDb = [
    'name' => 'RB-LAB',
    'host' => '10.10.10.1', // Cambia por tu IP (v6 o v7)
    'user' => 'pulse_isp',
    'password' => '123456789',
    'port' => 8000,
    'cut_method' => 'address_list', 
    'is_provisioned' => false       
];

$clientePrueba = 'usernamecliente1';

try {
    echo "====================================================\n";
    echo " SIMULADOR DE FLUJO COMPLETO: CORTE Y REACTIVACIÓN\n";
    echo "====================================================\n\n";

    $connection = new Connection(
        host: $routerDb['host'],
        user: $routerDb['user'],
        password: $routerDb['password'],
        port: $routerDb['port']
    );
    $mt = new MikrotikClient($connection);

    /**
     * PASO 1: PROVISIÓN
     */
    if (!$routerDb['is_provisioned']) {
        echo "[STEP 1] Provisionando firewall...\n";
        if ($mt->provision()) { // Usando el método que agregamos al Client
            echo "✅ Regla 'corte_internet' lista.\n";
        }
    }

    /**
     * PASO 2: SUSPENSIÓN (CORTE)
     */
    echo "\n[STEP 2] Aplicando CORTE a: $clientePrueba...\n";
    if ($mt->ppp()->suspend($clientePrueba, $routerDb['cut_method'])) {
        echo "✅ Usuario cortado en Secret y Firewall.\n";
    }

    // Pequeña pausa para observar en Winbox
    echo "... Esperando 3 segundos antes de reactivar ...\n";
    sleep(3);

    /**
     * PASO 3: REACTIVACIÓN
     */
    echo "\n[STEP 3] Aplicando REACTIVACIÓN a: $clientePrueba...\n";
    if ($mt->ppp()->reactivate($clientePrueba, $routerDb['cut_method'])) {
        echo "✅ Usuario reactivado.\n";
        echo "   - Secret limpio (remote-address-list: none).\n";
        echo "   - IP eliminada de 'corte_internet'.\n";
    }

    /**
     * PASO 4: VERIFICACIÓN FINAL
     */
    echo "\n[STEP 4] Verificación final en el router...\n";
    $api = $connection->raw();
    
    // 1. Verificar Secret
    $secret = $api->query((new \RouterOS\Query('/ppp/secret/print'))
                ->where('name', $clientePrueba))->read();
    $listAssigned = $secret[0]['remote-address-list'] ?? 'none';
    echo "   - Secret list: " . ($listAssigned ?: 'none') . ($listAssigned === 'none' ? " ✔" : " ❌") . "\n";

    // 2. Verificar Address-list
    $list = $api->query((new \RouterOS\Query('/ip/firewall/address-list/print'))
                ->where('list', 'corte_internet'))->read();
    
    $encontrado = false;
    foreach ($list as $entry) {
        if (str_contains($entry['comment'] ?? '', $clientePrueba)) {
            $encontrado = true;
        }
    }

    if (!$encontrado) {
        echo "   - Address-list: Limpio para este usuario ✔\n";
    } else {
        echo "   - Address-list: ERROR, la IP sigue bloqueada ❌\n";
    }

} catch (\Throwable $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n====================================================\n";
echo " TEST FINALIZADO\n";