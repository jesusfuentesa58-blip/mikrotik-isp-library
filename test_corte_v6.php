<?php

require __DIR__ . '/vendor/autoload.php';

use ISP\Mikrotik\Connection;
use ISP\Mikrotik\MikrotikClient;
use RouterOS\Query;

$routerV6 = [
    'name' => 'RB-V6-TEST',
    'host' => '172.0.0.1',
    'user' => 'ManagerISP',
    'password' => '123456789',
    'port' => 8730,
];

$clientePrueba = '19583814'; // Tu usuario de prueba

try {
    echo "====================================================\n";
    echo " TEST DE CICLO COMPLETO (V6): CORTE Y REACTIVACIÓN\n";
    echo "====================================================\n\n";

    $connection = new Connection(
        host: $routerV6['host'],
        user: $routerV6['user'],
        password: $routerV6['password'],
        port: $routerV6['port']
    );

    $mt = new MikrotikClient($connection);

    /**
     * PASO 1: PROVISIÓN
     * Crea la regla de drop en Filter Rules si no existe.
     */
    echo "[PASO 1] Asegurando provisión de firewall...\n";
    $mt->provision();
    echo "✅ Provisión verificada.\n";

    /**
     * PASO 2: SUSPENSIÓN (CORTE)
     */
    echo "\n[PASO 2] Aplicando CORTE por Firewall a: $clientePrueba...\n";
    if ($mt->ppp()->suspend($clientePrueba, 'address_list')) {
        echo "✅ Secret actualizado e IP enviada a la lista de bloqueo.\n";
    }

    echo "\n... Esperando 4 segundos para que verifiques en Winbox ...\n";
    sleep(4);

    /**
     * PASO 3: REACTIVACIÓN
     * Proceso inverso: limpia el Secret y borra la IP del address-list.
     */
    echo "\n[PASO 3] Aplicando REACTIVACIÓN a: $clientePrueba...\n";
    if ($mt->ppp()->reactivate($clientePrueba, 'address_list')) {
        echo "✅ Usuario reactivado con éxito.\n";
    }

    /**
     * PASO 4: VERIFICACIÓN FINAL
     */
    echo "\n[PASO 4] Verificando estado final en el router...\n";
    $api = $connection->raw();
    
    // 1. Verificar que el Secret ya no tiene la lista asignada
    $secret = $api->query((new Query('/ppp/secret/print'))->where('name', $clientePrueba))->read();
    $remoteList = $secret[0]['remote-address-list'] ?? 'none';
    echo "   - Estado Secret (remote-list): " . ($remoteList ?: 'none') . ($remoteList === 'none' ? " ✔" : " ❌") . "\n";

    // 2. Verificar que la IP ya no está en el Address List
    $list = $api->query((new Query('/ip/firewall/address-list/print'))
                ->where('list', 'corte_internet'))->read();
    
    $encontrado = false;
    foreach ($list as $entry) {
        if (str_contains($entry['comment'] ?? '', $clientePrueba)) {
            $encontrado = true;
            break;
        }
    }

    if (!$encontrado) {
        echo "   - Estado Firewall (address-list): Limpio ✔\n";
    } else {
        echo "   - Estado Firewall (address-list): ERROR, la IP sigue bloqueada ❌\n";
    }

} catch (\Throwable $e) {
    echo "\n❌ ERROR DURANTE EL TEST: " . $e->getMessage() . "\n";
    if ($e->getPrevious()) {
        echo "   Detalle técnico: " . $e->getPrevious()->getMessage() . "\n";
    }
}

echo "\n====================================================\n";
echo " TEST FINALIZADO EN RB-V6\n";