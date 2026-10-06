<?php
// ============================================================
// SITE CONFIGURATION API - AURA GARAGE (DONWEB)
// Gestión dinámica de módulos activos del sistema
// Startup Aura - Omar Horacio Adamo
// ============================================================
require_once __DIR__ . '/config.php';

$defaultModules = [
    ['id' => '1', 'module_name' => 'postulantes', 'is_enabled' => 1],
    ['id' => '2', 'module_name' => 'avisos', 'is_enabled' => 1],
    ['id' => '3', 'module_name' => 'taller', 'is_enabled' => 1],
    ['id' => '4', 'module_name' => 'lubricentro', 'is_enabled' => 1],
    ['id' => '5', 'module_name' => 'lavadero', 'is_enabled' => 1],
    ['id' => '6', 'module_name' => 'informes', 'is_enabled' => 1],
    ['id' => '7', 'module_name' => 'monitoreo', 'is_enabled' => 1],
    ['id' => '8', 'module_name' => 'reportes', 'is_enabled' => 1],
    ['id' => '9', 'module_name' => 'flota', 'is_enabled' => 1],
    ['id' => '10', 'module_name' => 'usuarios', 'is_enabled' => 1],
    ['id' => '11', 'module_name' => 'cobros', 'is_enabled' => 1],
    ['id' => '12', 'module_name' => 'beneficios', 'is_enabled' => 1],
    ['id' => '13', 'module_name' => 'configuracion', 'is_enabled' => 1]
];

// Asegurar tabla en base de datos si no existe
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `garage_site_config` (
            `id` VARCHAR(64) PRIMARY KEY,
            `module_name` VARCHAR(100) NOT NULL UNIQUE,
            `is_enabled` TINYINT(1) DEFAULT 1,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Verificar si está vacía e insertar módulos por defecto
    $check = $pdo->query("SELECT COUNT(*) FROM `garage_site_config`")->fetchColumn();
    if ($check == 0) {
        $stmt = $pdo->prepare("INSERT INTO `garage_site_config` (`id`, `module_name`, `is_enabled`) VALUES (:id, :module_name, :is_enabled)");
        foreach ($defaultModules as $mod) {
            $stmt->execute($mod);
        }
    }
} catch (Exception $e) {
    // Si la DB falla temporalmente, continuará con los defaults
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $stmt = $pdo->query("SELECT `id`, `module_name`, `is_enabled`, `updated_at` FROM `garage_site_config` ORDER BY `module_name` ASC");
        $modules = $stmt->fetchAll();
        if (empty($modules)) {
            $modules = $defaultModules;
        } else {
            foreach ($modules as &$m) {
                $m['is_enabled'] = (bool)$m['is_enabled'];
            }
        }
        sendResponse(['success' => true, 'site_config' => $modules]);
    } catch (Exception $e) {
        sendResponse(['success' => true, 'site_config' => $defaultModules]);
    }
}

if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
    $input = getJsonInput();
    $id = $input['id'] ?? $_GET['id'] ?? '';
    $isEnabled = isset($input['is_enabled']) ? ($input['is_enabled'] ? 1 : 0) : null;
    $moduleName = $input['module_name'] ?? null;

    if (empty($id) && empty($moduleName)) {
        sendResponse(['error' => 'Identificador o nombre de módulo requerido'], 400);
    }

    try {
        if (!empty($id)) {
            $stmt = $pdo->prepare("UPDATE `garage_site_config` SET `is_enabled` = :enabled, `updated_at` = NOW() WHERE `id` = :id");
            $stmt->execute([':enabled' => $isEnabled, ':id' => $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE `garage_site_config` SET `is_enabled` = :enabled, `updated_at` = NOW() WHERE `module_name` = :module_name");
            $stmt->execute([':enabled' => $isEnabled, ':module_name' => $moduleName]);
        }
        sendResponse(['success' => true, 'message' => 'Módulo actualizado correctamente']);
    } catch (Exception $e) {
        sendResponse(['error' => $e->getMessage()], 500);
    }
}

sendResponse(['error' => 'Método no soportado'], 405);
