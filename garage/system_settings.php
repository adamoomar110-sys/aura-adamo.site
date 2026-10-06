<?php
// ============================================================
// SYSTEM SETTINGS API - AURA GARAGE (DONWEB)
// Configuración de Marca y Preferencias del Sistema
// Startup Aura - Omar Horacio Adamo
// ============================================================
require_once __DIR__ . '/config.php';

$defaultSettings = [
    ['key' => 'brand_name', 'value' => 'Aura Garage'],
    ['key' => 'brand_logo', 'value' => ''],
    ['key' => 'primary_color', 'value' => '#EAB308'],
    ['key' => 'contact_email', 'value' => 'adamoomar110@gmail.com'],
    ['key' => 'currency_symbol', 'value' => '$']
];

// Asegurar tabla en base de datos si no existe
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `garage_system_settings` (
            `key` VARCHAR(100) PRIMARY KEY,
            `value` LONGTEXT NOT NULL,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $check = $pdo->query("SELECT COUNT(*) FROM `garage_system_settings`")->fetchColumn();
    if ($check == 0) {
        $stmt = $pdo->prepare("INSERT INTO `garage_system_settings` (`key`, `value`) VALUES (:k, :v)");
        foreach ($defaultSettings as $s) {
            $stmt->execute([':k' => $s['key'], ':v' => $s['value']]);
        }
    }
} catch (Exception $e) {
    // Si la DB falla temporalmente, responderá con defaults
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $stmt = $pdo->query("SELECT `key`, `value` FROM `garage_system_settings`");
        $settings = $stmt->fetchAll();
        if (empty($settings)) {
            $settings = $defaultSettings;
        }
        sendResponse(['success' => true, 'system_settings' => $settings]);
    } catch (Exception $e) {
        sendResponse(['success' => true, 'system_settings' => $defaultSettings]);
    }
}

if ($method === 'POST' || $method === 'PUT') {
    $input = getJsonInput();
    
    // Si es un array de settings o un objeto individual
    $itemsToUpdate = [];
    if (isset($input['key'])) {
        $itemsToUpdate[] = $input;
    } elseif (is_array($input) && isset($input[0]['key'])) {
        $itemsToUpdate = $input;
    } elseif (is_array($input)) {
        foreach ($input as $k => $v) {
            if (is_string($k) && $k !== 'action') {
                $itemsToUpdate[] = ['key' => $k, 'value' => is_string($v) ? $v : json_encode($v)];
            }
        }
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO `garage_system_settings` (`key`, `value`, `updated_at`) 
            VALUES (:k, :v, NOW()) 
            ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `updated_at` = NOW()
        ");

        foreach ($itemsToUpdate as $item) {
            $stmt->execute([':k' => $item['key'], ':v' => $item['value'] ?? '']);
        }

        sendResponse(['success' => true, 'message' => 'Configuraciones guardadas exitosamente']);
    } catch (Exception $e) {
        sendResponse(['error' => $e->getMessage()], 500);
    }
}

sendResponse(['error' => 'Método no soportado'], 405);
