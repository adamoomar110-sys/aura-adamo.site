<?php
// ============================================================
// DONWEB MYSQL PDO CONNECTION & API CONFIGURATION - AURA ADAMO
// Base de datos oficial: a0170001_aura
// ============================================================

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$db_host = 'localhost';
$db_name = 'a0170001_aura';

// Pool de credenciales válidas en el servidor DonWeb / Ferozo
$credentials = [
    ['user' => 'a0170001_aura', 'pass' => '@Peloymago110Peloymago110'],
    ['user' => 'a0170001_aura', 'pass' => 'AuraFTP2025@aura'],
    ['user' => 'a0170001',      'pass' => '@Peloymago110Peloymago110'],
    ['user' => 'a0170001',      'pass' => 'AuraFTP2025@aura']
];

$pdo = null;
$connected_user = null;

foreach ($credentials as $cred) {
    try {
        $testPdo = new PDO(
            "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4",
            $cred['user'],
            $cred['pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        $pdo = $testPdo;
        $connected_user = $cred['user'];
        break;
    } catch (PDOException $e) {
        continue;
    }
}

function getDB() {
    global $pdo;
    if (!$pdo) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'No se pudo conectar a la base de datos oficial a0170001_aura en DonWeb.'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    return $pdo;
}

function getJsonInput() {
    $input = file_get_contents("php://input");
    return json_decode($input, true) ?: [];
}

function sendResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}
