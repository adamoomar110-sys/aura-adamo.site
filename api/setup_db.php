<?php
// ============================================================
// INICIALIZADOR DE TABLAS - BASE DE DATOS a0170001_aura
// ============================================================

require_once __DIR__ . '/config.php';

$pdo = getDB();
$results = [];

try {
    // 1. Tabla de contactos y leads
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `contactos` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `nombre` VARCHAR(150) NOT NULL,
            `email` VARCHAR(150) NOT NULL,
            `telefono` VARCHAR(50) DEFAULT NULL,
            `servicio` VARCHAR(100) DEFAULT 'General',
            `presupuesto` VARCHAR(50) DEFAULT NULL,
            `mensaje` TEXT NOT NULL,
            `estado` ENUM('nuevo', 'contactado', 'archivado') DEFAULT 'nuevo',
            `ip` VARCHAR(45) DEFAULT NULL,
            `fecha_creacion` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $results['contactos'] = 'Tabla creada o ya existente.';

    // 2. Tabla de suscriptores / newsletter
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `subscriptores` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `email` VARCHAR(150) NOT NULL UNIQUE,
            `fecha_creacion` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $results['subscriptores'] = 'Tabla creada o ya existente.';

    // 3. Tabla de métricas y visitas
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `metricas_visitas` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `pagina` VARCHAR(100) DEFAULT 'home',
            `ip_hash` VARCHAR(64) DEFAULT NULL,
            `user_agent` TEXT DEFAULT NULL,
            `fecha_creacion` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $results['metricas_visitas'] = 'Tabla creada o ya existente.';

    sendResponse([
        'success' => true,
        'database' => $db_name,
        'user' => $connected_user,
        'message' => 'Base de datos a0170001_aura configurada e inicializada con éxito.',
        'tables' => $results,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (PDOException $e) {
    sendResponse([
        'success' => false,
        'error' => 'Error al crear tablas: ' . $e->getMessage()
    ], 500);
}
