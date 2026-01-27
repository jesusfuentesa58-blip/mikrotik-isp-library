<?php
require 'vendor/autoload.php';

use ISP\Mikrotik\Connection;
use ISP\Mikrotik\MikrotikClient;

$connection = new Connection('172.0.0.1', 'ManagerISP', '123456789', 8730);
$client = new MikrotikClient($connection);

try {
    echo "Consultando perfil...\n";
    $perfil = $client->ppp()->profiles()->getProfile('test');
    print_r($perfil);
} catch (\Throwable $e) {
    echo "ERROR DETECTADO: " . $e->getMessage() . "\n";
    echo "Linea: " . $e->getLine() . "\n";
}