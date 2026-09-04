<?php
// ============================================================
// HEALTH CHECK & STATUS API - AURA ADAMO
// ============================================================

require_once __DIR__ . '/config.php';

$status = [
    'system' => 'Aura Backend Core',
    'status' => 'online',
    'version' => '1.0',
    'domain' => 'aura-adamo.site',
    'copyright' => '© 2026 Aura. Todos los derechos reservados.',
    'database' => [
        'name' => $db_name,
        'connected' => false,
        'user' => null,
        'tables' => []
    ]
];

try {
    $db = getDB();
    $status['database']['connected'] = true;
    $status['database']['user'] = $connected_user;
    
    // Obtener tablas existentes
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $status['database']['tables'] = $tables;
    
} catch (Exception $e) {
    $status['database']['error'] = $e->getMessage();
}

sendResponse($status);
