<?php
// ============================================================
// VERIFICACIÓN DE ESTADO API - ROLPLAY.AI
// ============================================================

require_once __DIR__ . '/config.php';

try {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM information_schema.tables WHERE table_schema = '{$db_name}'");
    $count = $stmt->fetchColumn();

    sendResponse([
        'success' => true,
        'app' => 'RolPlay.ai API',
        'status' => 'online',
        'database' => $db_name,
        'user' => $connected_user,
        'tables_count' => (int)$count,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    sendResponse([
        'success' => false,
        'error' => $e->getMessage()
    ], 500);
}
