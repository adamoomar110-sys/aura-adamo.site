<?php
// ============================================================
// ENDPOINT DE CONTACTO Y LEADS - AURA ADAMO
// Base de datos: a0170001_aura
// ============================================================

require_once __DIR__ . '/config.php';

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// Manejo de GET: Listado o resumen de contactos
if ($method === 'GET') {
    $action = $_GET['action'] ?? 'summary';
    
    if ($action === 'list') {
        try {
            $stmt = $pdo->query("SELECT id, nombre, email, telefono, servicio, presupuesto, mensaje, estado, fecha_creacion FROM contactos ORDER BY id DESC LIMIT 50");
            $leads = $stmt->fetchAll();
            sendResponse([
                'success' => true,
                'total' => count($leads),
                'leads' => $leads
            ]);
        } catch (PDOException $e) {
            sendResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    } else {
        // Resumen rápido
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN estado = 'nuevo' THEN 1 ELSE 0 END) as nuevos FROM contactos");
            $resumen = $stmt->fetch();
            sendResponse([
                'success' => true,
                'database' => 'a0170001_aura',
                'resumen' => $resumen
            ]);
        } catch (PDOException $e) {
            sendResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}

// Manejo de POST: Crear nuevo contacto / lead
if ($method === 'POST') {
    $input = getJsonInput();
    
    // Si viene por x-www-form-urlencoded o FormData
    if (empty($input)) {
        $input = $_POST;
    }

    $nombre      = trim($input['nombre'] ?? '');
    $email       = trim($input['email'] ?? '');
    $telefono    = trim($input['telefono'] ?? '');
    $servicio    = trim($input['servicio'] ?? 'Desarrollo de Software');
    $presupuesto = trim($input['presupuesto'] ?? '');
    $mensaje     = trim($input['mensaje'] ?? '');
    $ip          = $_SERVER['REMOTE_ADDR'] ?? '';

    // Validaciones
    if (empty($nombre)) {
        sendResponse(['success' => false, 'error' => 'Por favor ingresá tu nombre.'], 400);
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendResponse(['success' => false, 'error' => 'Por favor ingresá un correo electrónico válido.'], 400);
    }
    if (empty($mensaje)) {
        sendResponse(['success' => false, 'error' => 'Por favor dejanos un mensaje con tu consulta o proyecto.'], 400);
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO contactos (nombre, email, telefono, servicio, presupuesto, mensaje, ip, estado)
            VALUES (:nombre, :email, :telefono, :servicio, :presupuesto, :mensaje, :ip, 'nuevo')
        ");
        
        $stmt->execute([
            ':nombre'      => $nombre,
            ':email'       => $email,
            ':telefono'    => $telefono,
            ':servicio'    => $servicio,
            ':presupuesto' => $presupuesto,
            ':mensaje'     => $mensaje,
            ':ip'          => $ip
        ]);

        $newId = $pdo->lastInsertId();

        sendResponse([
            'success' => true,
            'id' => $newId,
            'message' => '¡Gracias por contactar a Aura! Tu consulta ha sido recibida y te responderemos a la brevedad.'
        ], 201);

    } catch (PDOException $e) {
        sendResponse([
            'success' => false,
            'error' => 'Error al guardar el contacto: ' . $e->getMessage()
        ], 500);
    }
}

sendResponse(['success' => false, 'error' => 'Método no permitido.'], 405);
