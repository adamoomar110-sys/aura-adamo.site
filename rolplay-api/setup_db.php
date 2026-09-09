<?php
// ============================================================
// INICIALIZADOR DE TABLAS - BASE DE DATOS a0170001_rolplay
// ============================================================

require_once __DIR__ . '/config.php';

$pdo = getDB();
$results = [];

try {
    // 1. Tabla de historial de simulaciones y evaluaciones IA
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `history_v2` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `timestamp` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `user_name` VARCHAR(255) NOT NULL,
            `area` VARCHAR(100) DEFAULT 'General',
            `scenario` VARCHAR(255) NOT NULL,
            `chat_history` LONGTEXT,
            `score` INT DEFAULT 0,
            `feedback` TEXT,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $results['history_v2'] = 'Tabla creada o ya existente.';

    // 2. Tabla de empleados y roles de entrenamiento (empleado, encargado, supervisor)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `empleados_entrenamiento` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `nombre` VARCHAR(255) NOT NULL,
            `rol` ENUM('empleado', 'encargado', 'supervisor') DEFAULT 'empleado',
            `area` VARCHAR(100) DEFAULT 'Atención al Cliente',
            `nivel_experiencia` INT DEFAULT 1,
            `score_promedio` FLOAT DEFAULT 0,
            `sesiones_completadas` INT DEFAULT 0,
            `ultimo_entrenamiento` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `fecha_creacion` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $results['empleados_entrenamiento'] = 'Tabla creada o ya existente.';

    // 3. Tabla de métricas generales
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `metricas_rolplay` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `total_simulaciones` INT DEFAULT 0,
            `promedio_general` FLOAT DEFAULT 0,
            `ultima_actualizacion` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $results['metricas_rolplay'] = 'Tabla creada o ya existente.';

    sendResponse([
        'success' => true,
        'database' => $db_name,
        'user' => $connected_user,
        'message' => 'Base de datos a0170001_rolplay configurada e inicializada con éxito en DonWeb.',
        'tables' => $results,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (PDOException $e) {
    sendResponse([
        'success' => false,
        'error' => 'Error al crear tablas en a0170001_rolplay: ' . $e->getMessage()
    ], 500);
}
