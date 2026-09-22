<?php
// ============================================================
// WEBHOOK / NOTIFICACIÓN INSTANTÁNEA DE MERCADO PAGO - AURA
// ============================================================

header("Content-Type: application/json; charset=UTF-8");

$MP_ACCESS_TOKEN = 'APP_USR-3808115968623959-092210-4de4235b1875e38de24f8139d1ea2509-59490746';

// Recibir datos de la notificación
$rawBody = file_get_contents('php://input');
$body = json_decode($rawBody, true) ?: [];

$paymentId = null;

if (!empty($_GET['id'])) {
    $paymentId = $_GET['id'];
} elseif (!empty($_GET['data_id'])) {
    $paymentId = $_GET['data_id'];
} elseif (!empty($body['data']['id'])) {
    $paymentId = $body['data']['id'];
} elseif (!empty($body['id'])) {
    $paymentId = $body['id'];
}

// Log general de la notificación
$logEntry = date('Y-m-d H:i:s') . " | IP: {$_SERVER['REMOTE_ADDR']} | ID: " . ($paymentId ?: 'NONE') . "\n";
@file_put_contents(__DIR__ . '/webhook_mp.log', $logEntry, FILE_APPEND);

if (!$paymentId) {
    http_response_code(200);
    echo json_encode(['status' => 'ignored', 'message' => 'No payment id received']);
    exit();
}

// Consultar pago a la API de Mercado Pago
$ch = curl_init("https://api.mercadopago.com/v1/payments/{$paymentId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$MP_ACCESS_TOKEN}",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$payment = json_decode($response, true);

if ($httpCode === 200 && !empty($payment['status']) && $payment['status'] === 'approved') {
    $email = filter_var($payment['payer']['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $nombre = $payment['payer']['first_name'] ?? 'Lector';
    $monto = (float)($payment['transaction_amount'] ?? 0);
    $descripcion = $payment['description'] ?? 'Libro Aura';

    // Evitar envíos duplicados comprobando el archivo de ventas procesadas
    $ventasProcesadas = @file_get_contents(__DIR__ . '/pagos_procesados.txt') ?: '';
    if (strpos($ventasProcesadas, (string)$paymentId) !== false) {
        http_response_code(200);
        echo json_encode(['status' => 'already_processed']);
        exit();
    }

    if ($email) {
        // Enviar estrictamente según el monto abonado
        if ($monto >= 14000) {
            // Mega Pack 20
            $asunto = "¡Tu Mega Pack de 20 Libros! - Aura Ediciones";
            $bloqueDescarga = "
                <h3 style='color: #fff; margin-top: 0;'>Mega Pack Completo (20 Libros):</h3>
                <p><a href='https://aura-adamo.site/descargas/Mega_Pack_20_Libros_Completo_Aura.zip' style='display: inline-block; background: #ef4444; color: #fff; font-weight: bold; padding: 14px 28px; border-radius: 8px; text-decoration: none;'>🚀 Descargar Mega Pack 20 Libros (ZIP)</a></p>
                <p style='font-size: 13px; color: #94a3b8;'><a href='https://aura-adamo.site/descarga-exitosa.html?tipo=pack20' style='color:#38bdf8;'>Abrir portal web para ver libro por libro</a></p>
            ";
        } elseif ($monto >= 8000) {
            // Pack 10
            $asunto = "¡Tu Pack de 10 Libros! - Aura Ediciones";
            $zipFile = (stripos($descripcion, 'salud') !== false) ? 'Pack_10_Libros_Salud_Aura.zip' : 'Pack_10_Libros_Negocios_Aura.zip';
            $bloqueDescarga = "
                <h3 style='color: #fff; margin-top: 0;'>Pack de 10 Libros:</h3>
                <p><a href='https://aura-adamo.site/descargas/{$zipFile}' style='display: inline-block; background: #6366f1; color: #fff; font-weight: bold; padding: 14px 28px; border-radius: 8px; text-decoration: none;'>💼 Descargar Pack de 10 Libros (ZIP)</a></p>
            ";
        } else {
            // Libro Individual ($2.500)
            $asunto = "¡Tu Libro Digital Adquirido! - Aura Ediciones";
            $bloqueDescarga = "
                <h3 style='color: #fff; margin-top: 0;'>Tu Libro Individual Adquirido:</h3>
                <p style='color: #38bdf8; font-weight: bold; font-size: 16px;'>{$descripcion}</p>
                <p><a href='https://aura-adamo.site/descarga-exitosa.html?tipo=individual&titulo=" . urlencode($descripcion) . "' style='display: inline-block; background: #10b981; color: #000; font-weight: bold; padding: 14px 28px; border-radius: 8px; text-decoration: none;'>📥 Descargar Mi Libro en PDF</a></p>
                <p style='font-size: 13px; color: #94a3b8; margin-top: 15px;'>¿Querés la colección completa? Podés adquirir los 19 restantes con descuento en <a href='https://aura-adamo.site/libros.html' style='color:#818cf8;'>nuestra tienda</a>.</p>
            ";
        }

        $htmlMessage = "
        <!DOCTYPE html>
        <html lang='es'>
        <head><meta charset='UTF-8'></head>
        <body style='font-family: Arial, sans-serif; background: #07090e; color: #f8fafc; padding: 20px;'>
            <div style='max-width: 600px; margin: 0 auto; background: #10141f; border: 1px solid #1e293b; border-radius: 16px; padding: 30px;'>
                <h2 style='color: #10b981; margin-top: 0;'>¡Pago acreditado con éxito! ($ {$monto} ARS)</h2>
                <p>Hola <strong>{$nombre}</strong>,</p>
                <p>Muchas gracias por tu compra en <strong>Aura Ediciones</strong>. Tu material digital ya está listo para descargar de forma inmediata:</p>
                
                <div style='background: #1e293b; border-radius: 12px; padding: 20px; text-align: center; margin: 25px 0;'>
                    {$bloqueDescarga}
                </div>

                <div style='border-top: 1px solid #334155; padding-top: 20px; font-size: 13px; color: #94a3b8;'>
                    <p>Si tenés alguna duda o querés recibir los archivos directamente por WhatsApp, escribile a Omar Horacio Adamo:</p>
                    <p><a href='https://wa.me/5491178295317?text=Hola%20Omar,%20compr%C3%A9%20el%20libro%20con%20el%20email%20{$email}' style='color: #10b981; font-weight: bold;'>💬 Hablar por WhatsApp con Omar (+54 9 11 7829-5317)</a></p>
                </div>

                <div style='text-align: center; margin-top: 30px; font-size: 12px; color: #64748b;'>
                    &copy; 2026 Aura. Todos los derechos reservados. Plataforma fundada por Omar Horacio Adamo.<br>
                    Aura Ediciones · https://aura-adamo.site
                </div>
            </div>
        </body>
        </html>
        ";

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: Aura Ediciones <contacto@aura-adamo.site>\r\n";
        $headers .= "Reply-To: contacto@aura-adamo.site\r\n";

        @mail($email, $asunto, $htmlMessage, $headers);
    }

    // Registrar ID procesado
    @file_put_contents(__DIR__ . '/pagos_procesados.txt', "{$paymentId},{$email},{$monto}," . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
}

http_response_code(200);
echo json_encode(['status' => 'ok']);
