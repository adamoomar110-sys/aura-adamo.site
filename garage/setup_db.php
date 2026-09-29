<?php
// ============================================================
// AUTO-INSTALLER DE BASE DE DATOS MYSQL EN DONWEB (AURA GARAGE)
// ============================================================
require_once __DIR__ . '/config.php';

$logs = [];

$sql = "
CREATE TABLE IF NOT EXISTS `garage_profiles` (
  `id` VARCHAR(64) PRIMARY KEY,
  `email` VARCHAR(150) UNIQUE NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(150) NOT NULL,
  `role` VARCHAR(20) DEFAULT 'driver',
  `phone` VARCHAR(50) NULL,
  `dni` VARCHAR(30) NULL,
  `vehicle_id` VARCHAR(64) NULL,
  `metrics` LONGTEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `garage_vehicles` (
  `id` VARCHAR(64) PRIMARY KEY,
  `plate` VARCHAR(30) UNIQUE NOT NULL,
  `brand` VARCHAR(100) NOT NULL,
  `model` VARCHAR(100) NOT NULL,
  `status` VARCHAR(50) DEFAULT 'active',
  `last_lat` DOUBLE NULL,
  `last_lng` DOUBLE NULL,
  `metrics` LONGTEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `garage_applicants` (
  `id` VARCHAR(64) PRIMARY KEY,
  `full_name` VARCHAR(150) NOT NULL,
  `dni` VARCHAR(30) NOT NULL,
  `age` INT NULL,
  `phone` VARCHAR(50) NOT NULL,
  `zone` VARCHAR(100) NULL,
  `app_experience` TEXT NULL,
  `accident_history` TEXT NULL,
  `has_professional_license` TINYINT(1) DEFAULT 0,
  `can_pay_advance` TINYINT(1) DEFAULT 0,
  `dni_front_url` TEXT NULL,
  `dni_back_url` TEXT NULL,
  `license_url` TEXT NULL,
  `selfie_url` TEXT NULL,
  `status` VARCHAR(30) DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `garage_payments` (
  `id` VARCHAR(64) PRIMARY KEY,
  `driver_id` VARCHAR(64) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `type` VARCHAR(30) DEFAULT 'payment',
  `status` VARCHAR(30) DEFAULT 'pending',
  `due_date` DATE NULL,
  `receipt_url` TEXT NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `garage_incidents` (
  `id` VARCHAR(64) PRIMARY KEY,
  `driver_id` VARCHAR(64) NULL,
  `vehicle_id` VARCHAR(64) NULL,
  `description` TEXT NOT NULL,
  `photo_url` TEXT NULL,
  `audio_url` TEXT NULL,
  `status` VARCHAR(30) DEFAULT 'open',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `garage_announcements` (
  `id` VARCHAR(64) PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `garage_daily_reports` (
  `id` VARCHAR(64) PRIMARY KEY,
  `driver_id` VARCHAR(64) NOT NULL,
  `vehicle_id` VARCHAR(64) NULL,
  `start_km` INT NOT NULL,
  `end_km` INT NULL,
  `start_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `end_time` TIMESTAMP NULL,
  `revenue` DECIMAL(10,2) DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `garage_benefits` (
  `id` VARCHAR(64) PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT NOT NULL,
  `location` VARCHAR(150) NULL,
  `icon` VARCHAR(50) NULL,
  `color` VARCHAR(50) NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `garage_service_orders` (
  `id` VARCHAR(64) PRIMARY KEY,
  `vehicle_id` VARCHAR(64) NULL,
  `plate` VARCHAR(30) NOT NULL,
  `type` VARCHAR(50) NOT NULL DEFAULT 'taller', -- 'taller' | 'lubricentro' | 'lavadero'
  `provider_type` VARCHAR(50) NOT NULL DEFAULT 'taller',
  `description` TEXT NOT NULL,
  `status` VARCHAR(30) DEFAULT 'pending', -- 'pending' | 'in_progress' | 'completed'
  `cost` DECIMAL(10,2) DEFAULT 0.00,
  `budget` DECIMAL(10,2) DEFAULT 0.00,
  `appointment_date` VARCHAR(100) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `garage_chat_messages` (
  `id` VARCHAR(64) PRIMARY KEY,
  `channel` VARCHAR(50) NOT NULL,
  `sender` VARCHAR(50) NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `garage_settings` (
  `setting_key` VARCHAR(100) PRIMARY KEY,
  `setting_value` LONGTEXT NOT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

try {
    $pdo->exec($sql);
    $logs[] = "¡Tablas 'garage_*' creadas exitosamente!";

    
    // Migraciones automáticas de columnas existentes
    $alterQueries = [
        "ALTER TABLE `garage_service_orders` ADD COLUMN IF NOT EXISTS `provider_type` VARCHAR(50) NOT NULL DEFAULT 'taller'",
        "ALTER TABLE `garage_service_orders` ADD COLUMN IF NOT EXISTS `budget` DECIMAL(10,2) DEFAULT 0.00",
        "ALTER TABLE `garage_service_orders` ADD COLUMN IF NOT EXISTS `appointment_date` VARCHAR(100) NULL"
    ];
    foreach ($alterQueries as $aq) {
        try {
            $pdo->exec($aq);
        } catch (Exception $ex) {
            // Ignorar si la versión de MySQL no soporta IF NOT EXISTS o ya existe
        }
    }
    
    // Seed Admin Default Users y Chofer Demo (Omar Horacio Adamo - Administrador y Titular)
    $defaultUsers = [
        ['id' => 'u-omar', 'email' => 'omar@programador.com', 'pass' => '123456', 'name' => 'Omar Horacio Adamo', 'role' => 'admin'],
        ['id' => 'u-omar-aura', 'email' => 'adamoomar110@gmail.com', 'pass' => '123456', 'name' => 'Omar Horacio Adamo (Aura)', 'role' => 'admin'],
        ['id' => 'u-chofer-demo', 'email' => 'chofer@aura.com', 'pass' => '123456', 'name' => 'Carlos Chofer Demo', 'role' => 'driver']
    ];
    
    // Eliminar cuentas previas no autorizadas
    $pdo->exec("DELETE FROM `garage_profiles` WHERE `email` = 'claudio@aura-adamo.site'");

    $stmt = $pdo->prepare("INSERT INTO `garage_profiles` (`id`, `email`, `password_hash`, `full_name`, `role`) VALUES (:id, :email, :pass, :name, :role) ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`), `role` = VALUES(`role`)");
    foreach ($defaultUsers as $u) {
        $hash = password_hash($u['pass'], PASSWORD_BCRYPT);
        $stmt->execute([':id' => $u['id'], ':email' => $u['email'], ':pass' => $hash, ':name' => $u['name'], ':role' => $u['role']]);
    }
    $logs[] = "¡Administrador oficial (Omar Horacio Adamo - Startup Aura) y Chofer Demo asegurados!";

    // Seed Vehículos Iniciales para Aura Garage
    $vehicles = [
        ['id' => 'v-cronos-01', 'plate' => 'AF 123 CD', 'brand' => 'Fiat', 'model' => 'Cronos 1.3 Drive 2024', 'status' => 'active', 'metrics' => json_encode(['km' => 28450, 'gnc' => true, 'insurance_due' => '2026-12-15', 'driver_name' => 'Carlos Chofer Demo'])],
        ['id' => 'v-etios-02', 'plate' => 'AE 789 XY', 'brand' => 'Toyota', 'model' => 'Etios Sedan 1.5 XLS 2023', 'status' => 'active', 'metrics' => json_encode(['km' => 42100, 'gnc' => true, 'insurance_due' => '2026-10-30', 'driver_name' => 'Juan Pérez'])],
        ['id' => 'v-onix-03', 'plate' => 'AF 456 ZW', 'brand' => 'Chevrolet', 'model' => 'Onix Plus 1.2 LT 2024', 'status' => 'active', 'metrics' => json_encode(['km' => 19800, 'gnc' => true, 'insurance_due' => '2027-01-10', 'driver_name' => 'Martín Domínguez'])],
        ['id' => 'v-208-04', 'plate' => 'AG 321 JK', 'brand' => 'Peugeot', 'model' => '208 Like 1.2 2024', 'status' => 'maintenance', 'metrics' => json_encode(['km' => 31200, 'gnc' => false, 'insurance_due' => '2026-11-12', 'driver_name' => 'En Taller Preventivo'])]
    ];
    $stmtVeh = $pdo->prepare("INSERT INTO `garage_vehicles` (`id`, `plate`, `brand`, `model`, `status`, `metrics`) VALUES (:id, :plate, :brand, :model, :status, :metrics) ON DUPLICATE KEY UPDATE `status` = VALUES(`status`), `metrics` = VALUES(`metrics`)");
    foreach ($vehicles as $v) {
        $stmtVeh->execute($v);
    }
    $logs[] = "¡Flota inicial de 4 vehículos sincronizada con éxito!";

    // Seed Postulantes de Prueba
    $applicants = [
        ['id' => 'app-01', 'full_name' => 'Matías Ezequiel Gómez', 'dni' => '38945123', 'phone' => '+54 9 11 3456-7890', 'zone' => 'CABA / Zona Oeste', 'app_experience' => '3 años en Uber y Cabify, calificación 4.96 en app', 'status' => 'pending'],
        ['id' => 'app-02', 'full_name' => 'Rodrigo Hernán Silva', 'dni' => '40122987', 'phone' => '+54 9 11 9876-5432', 'zone' => 'Zona Norte / San Isidro', 'app_experience' => '2 años en Didi Flotas, registro profesional vigente', 'status' => 'pending']
    ];
    $stmtApp = $pdo->prepare("INSERT INTO `garage_applicants` (`id`, `full_name`, `dni`, `phone`, `zone`, `app_experience`, `status`) VALUES (:id, :full_name, :dni, :phone, :zone, :app_experience, :status) ON DUPLICATE KEY UPDATE `status` = VALUES(`status`)");
    foreach ($applicants as $a) {
        $stmtApp->execute($a);
    }
    $logs[] = "¡Postulantes iniciales cargados para revisión del panel!";

    // Seed default settings
    $pdo->exec("INSERT INTO `garage_settings` (`setting_key`, `setting_value`) VALUES ('lavado_precio', '5000') ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`");

    sendResponse(['success' => true, 'logs' => $logs]);
} catch (Exception $e) {
    sendResponse(['success' => false, 'error' => $e->getMessage(), 'logs' => $logs], 500);
}
