<?php
// ============================================================
// CREACIÓN OFICIAL DE PREFERENCIA CHECKOUT PRO - AURA EDICIONES
// ============================================================

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Access Token Oficial de Producción - Omar Horacio Adamo (adamoomar@hotmail.com)
$MP_ACCESS_TOKEN = getenv('MP_ACCESS_TOKEN') ?: 'APP_USR-3808115968623959-092210-4de4235b1875e38de24f8139d1ea2509-59490746';

$input = json_decode(file_get_contents('php://input'), true);
if (empty($input)) {
    $input = $_POST;
}

$tipo = $input['tipo'] ?? 'pack20';

$catalog = [
    'pack20' => [
        'title' => 'Mega Pack 20 Libros Aura (Negocios + Salud) - Omar Horacio Adamo',
        'price' => 14900,
        'description' => 'Colección completa de 20 libros en PDF + EPUB con entrega digital inmediata.'
    ],
    'pack_negocios' => [
        'title' => 'Pack 10 Libros Negocios, IA y Tecnología - Aura Ediciones',
        'price' => 9900,
        'description' => '10 libros completos de negocios, IA, e-commerce y finanzas.'
    ],
    'pack_salud' => [
        'title' => 'Pack 10 Libros Salud, Nutrición y Dietas - Aura Ediciones',
        'price' => 9900,
        'description' => '10 libros completos de nutrición y salud con referencias PubMed.'
    ],
    'individual' => [
        'title' => !empty($input['titulo']) ? $input['titulo'] . ' - Aura Ediciones' : 'Libro Digital Individual - Aura Ediciones',
        'price' => 2500,
        'description' => 'Libro digital oficial en PDF de alta resolución + EPUB.'
    ]
];

$itemData = $catalog[$tipo] ?? $catalog['pack20'];

    $successUrl = 'https://aura-adamo.site/descarga-exitosa.html?pago=aprobado&tipo=' . urlencode($tipo);
    if ($tipo === 'individual') {
        $tituloParam = !empty($input['titulo']) ? $input['titulo'] : 'Libro Digital';
        $successUrl .= '&titulo=' . urlencode($tituloParam);
    }

    $preference = [
        'items' => [
            [
                'title' => $itemData['title'],
                'description' => $itemData['description'],
                'quantity' => 1,
                'currency_id' => 'ARS',
                'unit_price' => (float)$itemData['price']
            ]
        ],
        'back_urls' => [
            'success' => $successUrl,
            'failure' => 'https://aura-adamo.site/libros.html?pago=fallo',
            'pending' => $successUrl . '&estado=pendiente'
        ],
    'auto_return' => 'approved',
    'statement_descriptor' => 'AURA LIBROS',
    'external_reference' => 'AURA_' . time()
];

$ch = curl_init('https://api.mercadopago.com/checkout/preferences');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($preference));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $MP_ACCESS_TOKEN,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$resData = json_decode($response, true);

if ($httpCode === 200 || $httpCode === 201) {
    echo json_encode([
        'success' => true,
        'init_point' => $resData['init_point'],
        'sandbox_init_point' => $resData['sandbox_init_point'] ?? $resData['init_point'],
        'id' => $resData['id']
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'No se pudo crear la preferencia de pago en Mercado Pago.',
        'details' => $resData
    ], JSON_UNESCAPED_UNICODE);
}
