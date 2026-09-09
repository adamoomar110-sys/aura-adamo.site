<?php
// ============================================================
// REGISTRO Y CONSULTA DE SIMULACIONES - ROLPLAY.AI
// ============================================================

require_once __DIR__ . '/config.php';

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $stmt = $pdo->query("SELECT id, timestamp, user_name, area, scenario, score, feedback FROM history_v2 ORDER BY id DESC LIMIT 50");
        $history = $stmt->fetchAll();

        sendResponse([
            'success' => true,
            'count' => count($history),
            'history' => $history
        ]);
    } catch (PDOException $e) {
        sendResponse(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

if ($method === 'POST') {
    $data = getJsonInput();

    $user_name = trim($data['user_name'] ?? 'Usuario Anónimo');
    $area = trim($data['area'] ?? 'General');
    $scenario = trim($data['scenario'] ?? 'Simulación General');
    $chat_history = is_array($data['chat_history'] ?? null) ? json_encode($data['chat_history'], JSON_UNESCAPED_UNICODE) : ($data['chat_history'] ?? '');
    $score = (int)($data['score'] ?? 0);
    $feedback = trim($data['feedback'] ?? '');

    try {
        $stmt = $pdo->prepare("
            INSERT INTO history_v2 (user_name, area, scenario, chat_history, score, feedback)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_name, $area, $scenario, $chat_history, $score, $feedback]);
        $newId = $pdo->lastInsertId();

        sendResponse([
            'success' => true,
            'id' => $newId,
            'message' => 'Simulación de entrenamiento guardada con éxito en a0170001_rolplay'
        ], 201);
    } catch (PDOException $e) {
        sendResponse(['success' => false, 'error' => $e->getMessage()], 500);
    }
}
