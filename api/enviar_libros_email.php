<?php
// ============================================================
// ENVÍO AUTOMÁTICO DE LIBROS POR EMAIL - AURA EDICIONES
// ============================================================

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (empty($input)) {
    $input = $_POST;
}

$email = filter_var(trim($input['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$pack  = trim($input['pack'] ?? 'individual');
$nombre = trim($input['nombre'] ?? 'Lector');
$tituloLibro = trim($input['titulo'] ?? 'Libro Digital');
$archivoLibro = trim($input['archivo'] ?? '');

if (!$email) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Por favor ingresá un correo electrónico válido.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Mapeo de archivos según título si viene individual
$mapaLibros = [
    'dropshipping' => ['titulo' => 'El Manual del Dropshipping', 'file' => '01_El_Manual_del_Dropshipping.pdf'],
    'ia' => ['titulo' => 'Inteligencia Artificial para Negocios', 'file' => '02_Inteligencia_Artificial_para_Negocios.pdf'],
    'ecommerce' => ['titulo' => 'E-commerce Avanzado', 'file' => '03_Ecommerce_Avanzado.pdf'],
    'iot' => ['titulo' => 'Hardware, IoT y Telemetría', 'file' => '04_Hardware_IoT_Telemetria.pdf'],
    'branding' => ['titulo' => 'Branding y Marca Digital', 'file' => '05_Branding_y_Marca_Digital.pdf'],
    'finanzas' => ['titulo' => 'Finanzas para Startups', 'file' => '06_Finanzas_para_Startups.pdf'],
    'logistica' => ['titulo' => 'Logística y Cadena de Suministro', 'file' => '07_Logistica_y_Cadena_de_Suministro.pdf'],
    'ventas' => ['titulo' => 'Ventas B2B y Negociación', 'file' => '08_Ventas_B2B_y_Negociacion.pdf'],
    'equipos' => ['titulo' => 'Gestión de Equipos y Delegación', 'file' => '09_Gestion_de_Equipos_y_Delegacion.pdf'],
    'ecosistema' => ['titulo' => 'El Ecosistema Aura', 'file' => '10_El_Ecosistema_Aura.pdf'],
    'keto' => ['titulo' => 'La Dieta Cetogénica Práctica', 'file' => '01_La_Dieta_Cetogenica_Practica.pdf'],
    'ayuno' => ['titulo' => 'El Poder del Ayuno Intermitente', 'file' => '02_El_Poder_del_Ayuno_Intermitente.pdf'],
    'antiinflamatoria' => ['titulo' => 'Dieta Antiinflamatoria', 'file' => '03_Dieta_Antiinflamatoria.pdf'],
    'deportiva' => ['titulo' => 'Nutrición Deportiva y Fuerza', 'file' => '04_Nutricion_Deportiva_y_Fuerza.pdf'],
    'mediterranea' => ['titulo' => 'La Dieta Mediterránea Moderna', 'file' => '05_La_Dieta_Mediterranea_Moderna.pdf'],
    'microbiota' => ['titulo' => 'Microbiota y Salud Intestinal', 'file' => '06_Microbiota_y_Salud_Intestinal.pdf'],
    'plantas' => ['titulo' => 'Alimentación Basada en Plantas', 'file' => '07_Alimentacion_Basada_en_Plantas.pdf'],
    'glucosa' => ['titulo' => 'Control de Glucosa e Insulina', 'file' => '08_Control_de_Glucosa_e_Insulina.pdf'],
    'ansiedad' => ['titulo' => 'Comer sin Ansiedad (Mindful Eating)', 'file' => '09_Comer_sin_Ansiedad_Mindful_Eating.pdf'],
    'longevidad' => ['titulo' => 'Longevidad y Biohacking Nutricional', 'file' => '10_Longevidad_y_Biohacking_Nutricional.pdf']
];

$asunto = "Entrega de tu compra en Aura Ediciones";
$bloqueDescarga = "";

if ($pack === 'pack20') {
    $asunto = "Tu Mega Pack de 20 Libros - Aura Ediciones";
    $bloqueDescarga = "
        <h3 style='color: #fff; margin-bottom: 12px;'>Mega Pack 20 Libros (Colección Completa):</h3>
        <p style='margin-bottom: 15px;'><a href='https://aura-adamo.site/descargas/Mega_Pack_20_Libros_Completo_Aura.zip' class='btn btn-green'>🚀 Descargar Mega Pack 20 Libros (ZIP)</a></p>
        <p style='font-size: 13px; color: #94a3b8;'>Acceso al portal para ver libro por libro: <a href='https://aura-adamo.site/descarga-exitosa.html?pago=aprobado&tipo=pack20' style='color:#38bdf8;'>Abrir Portal</a></p>
    ";
} elseif ($pack === 'pack_negocios') {
    $asunto = "Tu Pack de 10 Libros de Negocios & IA - Aura Ediciones";
    $bloqueDescarga = "
        <h3 style='color: #fff; margin-bottom: 12px;'>Pack 10 Libros Negocios, IA & Tecnología:</h3>
        <p style='margin-bottom: 15px;'><a href='https://aura-adamo.site/descargas/Pack_10_Libros_Negocios_Aura.zip' class='btn btn-blue'>💼 Descargar Pack Negocios (ZIP)</a></p>
        <p style='font-size: 13px; color: #94a3b8;'>Acceso a la colección: <a href='https://aura-adamo.site/descarga-exitosa.html?pago=aprobado&tipo=pack_negocios' style='color:#38bdf8;'>Abrir Portal</a></p>
    ";
} elseif ($pack === 'pack_salud') {
    $asunto = "Tu Pack de 10 Libros de Salud & Dietas - Aura Ediciones";
    $bloqueDescarga = "
        <h3 style='color: #fff; margin-bottom: 12px;'>Pack 10 Libros Salud & Nutrición:</h3>
        <p style='margin-bottom: 15px;'><a href='https://aura-adamo.site/descargas/Pack_10_Libros_Salud_Aura.zip' class='btn btn-green'>🥗 Descargar Pack Salud (ZIP)</a></p>
        <p style='font-size: 13px; color: #94a3b8;'>Acceso a la colección: <a href='https://aura-adamo.site/descarga-exitosa.html?pago=aprobado&tipo=pack_salud' style='color:#38bdf8;'>Abrir Portal</a></p>
    ";
} else {
    // Libro individual ($2.500)
    $asunto = "Tu Libro Digital: {$tituloLibro} - Aura Ediciones";
    
    // Determinar archivo exacto si no vino
    $archivoFinal = $archivoLibro;
    if (empty($archivoFinal)) {
        foreach ($mapaLibros as $key => $val) {
            if (stripos($tituloLibro, $key) !== false || stripos($val['titulo'], $tituloLibro) !== false) {
                $archivoFinal = $val['file'];
                $tituloLibro = $val['titulo'];
                break;
            }
        }
    }
    if (empty($archivoFinal)) {
        $archivoFinal = '01_El_Manual_del_Dropshipping.pdf'; // Fallback seguro
    }

    $bloqueDescarga = "
        <h3 style='color: #fff; margin-bottom: 8px;'>Tu Libro Adquirido:</h3>
        <p style='color: #38bdf8; font-weight: bold; font-size: 17px; margin-bottom: 14px;'>{$tituloLibro}</p>
        <p style='margin-bottom: 18px;'><a href='https://aura-adamo.site/descargas/{$archivoFinal}' class='btn btn-blue'>📥 Descargar Mi Libro en PDF</a></p>
        <div style='background: rgba(99,102,241,0.15); border: 1px solid rgba(99,102,241,0.3); border-radius: 8px; padding: 12px; margin-top: 15px;'>
            <p style='font-size: 13px; color: #cbd5e1; margin: 0;'>🔥 <strong>¿Querés la colección completa de 20 libros?</strong><br>Aprovechá la oferta especial y llevate los 19 restantes por solo $12.400 en <a href='https://aura-adamo.site/libros.html' style='color:#818cf8; font-weight:bold;'>nuestra tienda</a>.</p>
        </div>
    ";
}

$htmlMessage = "
<!DOCTYPE html>
<html lang='es'>
<head>
<meta charset='UTF-8'>
<style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #0b0e14; color: #f1f5f9; padding: 20px; }
    .card { max-width: 600px; margin: 0 auto; background: #131a26; border: 1px solid #2d3748; border-radius: 16px; padding: 32px; }
    h1 { color: #ffffff; font-size: 24px; margin-bottom: 8px; }
    p { color: #94a3b8; font-size: 15px; line-height: 1.6; }
    .btn { display: inline-block; background: #6366f1; color: #ffffff !important; padding: 14px 28px; text-decoration: none; border-radius: 8px; font-weight: bold; margin: 16px 0; font-size: 16px; }
    .btn-green { background: #10b981; }
    .btn-blue { background: #009ee3; }
    .box { background: rgba(0,0,0,0.3); border-radius: 10px; padding: 16px; margin: 20px 0; border: 1px solid rgba(255,255,255,0.06); text-align: center; }
    .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #64748b; border-top: 1px solid #1e293b; padding-top: 20px; }
</style>
</head>
<body>
<div class='card'>
    <div style='text-align: center; margin-bottom: 24px;'>
        <span style='background: rgba(99,102,241,0.2); color: #818cf8; padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: bold;'>AURA EDICIONES · ENTREGA OFICIAL</span>
        <h1>¡Gracias por tu compra, {$nombre}!</h1>
        <p>Tu material digital oficial de <strong>Omar Horacio Adamo</strong> ya está listo para leer en tu celular, tablet o PC.</p>
    </div>

    <div class='box'>
        {$bloqueDescarga}
    </div>

    <div style='background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.25); border-radius: 10px; padding: 14px; text-align: left;'>
        <h4 style='color: #34d399; margin: 0 0 6px 0;'>¿Dudas o preferís tener el archivo en tu WhatsApp?</h4>
        <p style='font-size: 13px; margin: 0;'>Podés escribirle directamente al autor Omar Horacio Adamo: <a href='https://wa.me/5491178295317?text=Hola%20Omar,%20compr%C3%A9%20el%20libro%20con%20el%20email%20" . urlencode($email) . "' style='color: #10b981; font-weight: bold;'>💬 Hablar por WhatsApp (+54 9 11 7829-5317)</a></p>
    </div>

    <div class='footer'>
        <p>&copy; 2026 Aura. Todos los derechos reservados. Startup fundada por Omar Horacio Adamo.<br>
        Dirección Editorial: Aura Ediciones · https://aura-adamo.site</p>
    </div>
</div>
</body>
</html>
";

$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=UTF-8\r\n";
$headers .= "From: Aura Ediciones <contacto@aura-adamo.site>\r\n";
$headers .= "Reply-To: contacto@aura-adamo.site\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

$mailSent = @mail($email, $asunto, $htmlMessage, $headers);

$logLine = date('Y-m-d H:i:s') . ",{$email},{$pack},{$tituloLibro}," . ($mailSent ? 'ENVIADO' : 'FALLO') . ",{$_SERVER['REMOTE_ADDR']}\n";
@file_put_contents(__DIR__ . '/ventas_libros.csv', $logLine, FILE_APPEND);

echo json_encode([
    'success' => true,
    'message' => '¡Correo enviado con éxito! Revisá tu bandeja de entrada o spam.',
    'email' => $email,
    'mail_sent' => $mailSent,
    'pack' => $pack,
    'titulo' => $tituloLibro
], JSON_UNESCAPED_UNICODE);
