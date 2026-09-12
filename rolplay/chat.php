<?php
/**
 * RolPlay.ai - Motor de Simulación Autónomo v2.0
 * Desarrollado para la suite Aura
 * Copyright (c) 2026 Aura. Todos los derechos reservados.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Configuración opcional de clave de Groq (Gratis en console.groq.com)
// Si está vacía o no responde, el motor pedagógico contextual autónomo toma el control de forma transparente.
$GROQ_API_KEY = getenv('GROQ_API_KEY') ?: '';

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data || !isset($data['messages']) || empty($data['messages'])) {
    echo json_encode([
        'status' => 'error',
        'response' => 'No se recibieron mensajes para procesar.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$messages = $data['messages'];
$area = $data['area'] ?? 'Atención al Cliente';
$scenario = $data['scenario'] ?? 'Simulación General';
$difficulty = $data['difficulty'] ?? 'realista';
$userName = !empty($data['userName']) ? trim($data['userName']) : 'Usuario';
$userCompany = !empty($data['userCompany']) ? trim($data['userCompany']) : 'Aura Talent';

// Obtener el último mensaje del usuario
$lastUserMessage = '';
for ($i = count($messages) - 1; $i >= 0; $i--) {
    if ($messages[$i]['role'] === 'user') {
        $lastUserMessage = trim($messages[$i]['content']);
        break;
    }
}

// 1. INTENTO CON GROQ SI LA CLAVE ESTÁ CONFIGURADA
if (!empty($GROQ_API_KEY) && str_starts_with($GROQ_API_KEY, 'gsk_')) {
    $groqResponse = callGroqApi($GROQ_API_KEY, $messages, $area, $scenario, $userName, $userCompany, $difficulty);
    if ($groqResponse) {
        echo json_encode([
            'status' => 'success',
            'engine' => 'groq-cloud',
            'response' => $groqResponse
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// 2. MOTOR DE IA CONTEXTUAL AUTÓNOMO (100% GRATUITO Y AUTOCONFIGURADO)
$response = generateContextualRoleplayResponse($lastUserMessage, $messages, $area, $scenario, $userName, $difficulty);

echo json_encode([
    'status' => 'success',
    'engine' => 'aura-pedagogical-v2',
    'response' => $response
], JSON_UNESCAPED_UNICODE);
exit;

/**
 * Llamada a la API de Groq (Llama 3.3 70B / 8B gratuito)
 */
function callGroqApi($apiKey, $messages, $area, $scenario, $userName, $userCompany, $difficulty) {
    $toneGuide = match ($difficulty) {
        'exigente' => 'Estás muy exigente, a la defensiva, escéptico y desafiante. No cedes fácilmente y pides garantías concretas.',
        'colaborativo' => 'Estás dispuesto a cooperar si te explican las cosas de forma clara, amable y profesional.',
        default => 'Reacciona de manera realista y equilibrada, valorando la empatía y la claridad técnica.'
    };

    $systemPrompt = "Eres un personaje en un simulador de rol interactivo de habilidades blandas llamado RolPlay.ai de la plataforma Aura. "
        . "Área: {$area}. Escenario: {$scenario}. Tu interlocutor se llama {$userName} de la empresa {$userCompany}. "
        . "Tono y actitud requerida: {$toneGuide} "
        . "Instrucciones críticas: Responde en español, en un único párrafo conciso y directo (máximo 70 palabras). "
        . "Mantén estrictamente el personaje y tu rol. Reacciona coherentemente a lo que {$userName} acaba de decir.";

    $formattedMessages = [
        ['role' => 'system', 'content' => $systemPrompt]
    ];
    foreach ($messages as $m) {
        if (in_array($m['role'], ['user', 'assistant'])) {
            $formattedMessages[] = [
                'role' => $m['role'],
                'content' => $m['content']
            ];
        }
    }

    $ch = curl_init("https://api.groq.com/openai/v1/chat/completions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer {$apiKey}"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => 'llama-3.3-70b-versatile',
        'messages' => $formattedMessages,
        'max_tokens' => 200,
        'temperature' => 0.7
    ]));

    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $res) {
        $json = json_decode($res, true);
        if (!empty($json['choices'][0]['message']['content'])) {
            return trim($json['choices'][0]['message']['content']);
        }
    }
    return null;
}

/**
 * Motor pedagógico de simulación psicológica y conversacional
 * Genera respuestas contextuales altamente humanas y coherentes con cada escenario.
 */
function generateContextualRoleplayResponse($userText, $history, $area, $scenario, $userName, $difficulty) {
    $textLower = mb_strtolower($userText, 'UTF-8');
    $userTurnCount = 0;
    foreach ($history as $m) {
        if ($m['role'] === 'user') $userTurnCount++;
    }

    // Análisis de rasgos comunicativos del usuario
    $hasApology = preg_match('/(disculp|perdon|lament|sentim)/u', $textLower);
    $hasEmpathy = preg_match('/(entiend|comprend|tranquil|te escucho|puedo ver|preocup)/u', $textLower);
    $hasTimeCommitment = preg_match('/(minuto|hora|hoy|inmediat|ahora|plazo|tiempo|dia|mañana)/u', $textLower);
    $hasSolution = preg_match('/(solucion|vamos a|podemos|haremos|reemplaz|devoluc|cambi|enviar|gestion|bonif|descuent)/u', $textLower);
    $hasQuestion = (str_contains($userText, '?') || preg_match('/(como|cuando|cual|que opinas|te parece)/u', $textLower));
    $isVeryShort = (mb_strlen(trim($userText)) < 25);

    // Personalidad por escenario
    return match (true) {
        // --- ATENCIÓN AL CLIENTE: Marta (Mayor, preocupada, amable) ---
        str_contains($scenario, 'Retraso Menor') => handleMartaScenario($hasEmpathy, $hasSolution, $hasTimeCommitment, $isVeryShort, $userTurnCount, $userName),

        // --- ATENCIÓN AL CLIENTE: Jorge (Cafetera rota, cumpleaños esposa) ---
        str_contains($scenario, 'Producto Defectuoso') => handleJorgeScenario($hasApology, $hasEmpathy, $hasSolution, $hasTimeCommitment, $userTurnCount, $userName, $difficulty),

        // --- ATENCIÓN AL CLIENTE: Carlos (B2B furioso, producción parada) ---
        str_contains($scenario, 'Entrega Crítica') => handleCarlosScenario($hasEmpathy, $hasSolution, $hasTimeCommitment, $userTurnCount, $userName, $difficulty),

        // --- VENTAS B2B: Ana (Dueña tienda, poco tiempo) ---
        str_contains($scenario, 'Primer Contacto') => handleAnaScenario($hasSolution, $hasTimeCommitment, $hasQuestion, $userTurnCount, $userName),

        // --- VENTAS B2B: Luis (Director de Compras escéptico) ---
        str_contains($scenario, 'Cliente Escéptico') => handleLuisScenario($hasSolution, $hasQuestion, $userTurnCount, $userName, $difficulty),

        // --- VENTAS B2B: Paula (CEO agresiva, ultimátum) ---
        str_contains($scenario, 'Negociación Agresiva') => handlePaulaScenario($hasSolution, $hasTimeCommitment, $userTurnCount, $userName, $difficulty),

        // --- RECURSOS HUMANOS: Kevin (Junior nervioso) ---
        str_contains($scenario, 'Candidato Junior') => handleKevinScenario($hasEmpathy, $hasQuestion, $userTurnCount, $userName),

        // --- RECURSOS HUMANOS: Fernando (Evade preguntas) ---
        str_contains($scenario, 'Entrevista Difícil') => handleFernandoScenario($hasQuestion, $userTurnCount, $userName),

        // --- RECURSOS HUMANOS: Roberto (Despido defensivo) ---
        str_contains($scenario, 'Despido') => handleRobertoScenario($hasEmpathy, $hasSolution, $userTurnCount, $userName),

        // --- HOSTELERÍA: Sonia (Sin reserva en recepción) ---
        str_contains($scenario, 'Check-in') => handleSoniaScenario($hasEmpathy, $hasSolution, $hasTimeCommitment, $userTurnCount, $userName),

        // --- HOSTELERÍA: Lucía (Suite 5 estrellas con problemas) ---
        str_contains($scenario, 'Huésped Molesto') => handleLuciaScenario($hasApology, $hasSolution, $userTurnCount, $userName),

        // --- SOPORTE IT: Abel (Ratón desenchufado) ---
        str_contains($scenario, 'Usuario') => handleAbelScenario($hasEmpathy, $hasSolution, $userTurnCount, $userName),

        // --- CADENA DE MANDO (ENTRELAZADA: EMPLEADO, ENCARGADO, SUPERVISOR) ---
        str_contains($scenario, 'Empleado') || str_contains($scenario, 'Traspaso Limpio') => handleCadenaEmpleadoScenario($hasEmpathy, $hasSolution, $hasTimeCommitment, $userTurnCount, $userName, $difficulty),

        str_contains($scenario, 'Encargado') || str_contains($scenario, 'Intervención Táctica') => handleCadenaEncargadoScenario($hasEmpathy, $hasSolution, $hasTimeCommitment, $userTurnCount, $userName, $difficulty),

        str_contains($scenario, 'Supervisor') || str_contains($scenario, 'Coaching 1 a 1') => handleCadenaSupervisorScenario($hasEmpathy, $hasSolution, $hasQuestion, $userTurnCount, $userName, $difficulty),

        // --- GENÉRICO / DEFAULT ---
        default => handleGenericRoleplay($hasEmpathy, $hasSolution, $userTurnCount, $userName, $difficulty)
    };
}

// -------------------------------------------------------------
// Controladores de personajes individuales
// -------------------------------------------------------------

function handleMartaScenario($empathy, $solution, $time, $short, $turn, $name) {
    if ($short) {
        return "Disculpe, pero me dejó con la misma duda... ¿me podría explicar despacito qué significa ese número que me mandaron? De verdad me da miedo no recibir nada.";
    }
    if ($solution && $empathy) {
        return "¡Ay, qué alivio me da escucharlo, {$name}! Su amabilidad me tranquiliza muchísimo. Si usted me dice que viene en camino y me ayuda a verificarlo, me quedo mucho más en paz. ¿Cuánto calcula que llegará?";
    }
    if ($empathy) {
        return "Muchas gracias por comprenderme, querido {$name}. Una a mi edad ya se marea con tanta tecnología. Entonces, ¿el paquete no está perdido en ningún lado?";
    }
    return "Entiendo lo que me explica, pero sigo sin tener muy claro si tengo que esperar al cartero en casa hoy o si debo hacer clic en algún lado. ¿Usted me puede guiar paso a paso?";
}

function handleJorgeScenario($apology, $empathy, $solution, $time, $turn, $name, $diff) {
    if ($solution && ($time || $apology)) {
        return "Mire {$name}, le agradezco la rápida reacción y el tono respetuoso. Lo del cumpleaños de mi esposa me tenía muy angustiado. Si realmente me envían el reemplazo express como dice o me dan la solución hoy, acepto la propuesta. Manténgame al tanto del número de guía.";
    }
    if ($empathy && !$solution) {
        return "Agradezco que entienda mi frustración, pero las palabras bonitas no van a hacer que mi esposa tenga su regalo esta noche. Necesito una acción concreta: ¿me cambian la cafetera hoy mismo o me devuelven el dinero?";
    }
    if ($diff === 'exigente') {
        return "No me alcanza con un 'vamos a revisarlo'. Es un producto de primera línea que vino roto de fábrica. Exijo una solución urgente antes de las 18 hs o cancelo la compra y dejo constancia en defensa del consumidor.";
    }
    return "De acuerdo, {$name}, veo su buena voluntad. Pero dígame con exactitud: ¿cuándo llega el nuevo tanque o qué alternativa me ofrece para que no pase el día en blanco?";
}

function handleCarlosScenario($empathy, $solution, $time, $turn, $name, $diff) {
    if ($turn >= 3 && $solution && $time) {
        return "Bueno... al menos veo que se están haciendo cargo con seriedad. Si ese camión de contingencia llega mañana a primera hora como se compromete, podemos salvar el turno de la tarde. Envíeme ahora mismo la confirmación por escrito a mi correo corporativo.";
    }
    if ($solution) {
        return "Eso suena razonable en los papeles, {$name}, pero cada hora que pasa me cuesta miles de dólares. Quiero garantías firmes: si hay alguna penalidad o retraso extra, ¿quién responde? Necesito ese compromiso ya.";
    }
    if ($empathy && !$solution) {
        return "No me sirve que 'comprenda mi situación', {$name}. Mi planta está a punto de frenar 40 operarios. O me consiguen un transporte alternativo o mañana mismo rescindo el contrato con ustedes.";
    }
    return "¡Basta de rodeos! Dígame concretamente qué van a hacer para que mi mercadería llegue a destino antes de que perdamos la producción completa.";
}

function handleAnaScenario($solution, $time, $question, $turn, $name) {
    if ($question || $solution) {
        return "Me parece un planteo inteligente, {$name}. Justamente esa es una de las mayores dificultades que tengo con el inventario diario. Si tu solución se implementa sin interrumpir la atención de mi tienda, me interesa agendar una demo de 15 minutos la semana próxima. ¿Tienes disponibilidad el martes?";
    }
    return "Suena interesante, pero el día a día no me deja tiempo para experimentos. ¿En qué se diferencia concretamente tu propuesta de lo que ya ofrece el mercado?";
}

function handleLuisScenario($solution, $question, $turn, $name, $diff) {
    if ($turn >= 2 && $solution) {
        return "Interesante enfoque, {$name}. Veo que entendió que mi principal dolor de cabeza no es el costo de licencia, sino las horas que mi equipo perdería migrando datos. Si me demuestra ese ROI en un caso similar a nuestra industria, consideraría armar una prueba de concepto.";
    }
    if ($question) {
        return "Le respondo claro: hoy gastamos unos 3.000 dólares mensuales en la solución actual. No es perfecta, pero no se cae. Para hacerme cambiar, tendría que darme un salto de eficiencia de al menos 30% con soporte garantizado.";
    }
    return "Mire {$name}, todos los proveedores me dicen lo mismo en la primera llamada. ¿Qué métricas reales puede respaldar para convencerme de que no voy a arriesgar mi cuello cambiando de proveedor?";
}

function handlePaulaScenario($solution, $time, $turn, $name, $diff) {
    if ($solution && $turn >= 2) {
        return "Mmm... veo que sabe defender su propuesta de valor sin regalar el trabajo pero dándome un beneficio estratégico tangible. Me gusta la gente que negocia con firmeza. Hagamos esto: prepáreme la adenda con ese acuerdo de nivel de servicio mejorado y lo firmo al regresar de mi viaje.";
    }
    return "Le quedan 2 minutos, {$name}. No me justifique sus costos, dígame por qué debería pagarles 20% más que a su competidor directo si el entregable parece el mismo.";
}

function handleKevinScenario($empathy, $question, $turn, $name) {
    return "Muchas gracias por hacerme sentir cómodo, {$name}... de verdad estaba muy nervioso. Respecto a lo que me pregunta, en la universidad lideré el proyecto final del equipo y aunque tuvimos diferencias con los tiempos de entrega, logramos sacar la mejor calificación aprendiendo a organizarnos.";
}

function handleFernandoScenario($question, $turn, $name) {
    if ($question) {
        return "Es una pregunta muy directa, {$name}, y la valoro. En mi anterior posición hubo cambios en la dirección que no compartían mi visión ágil, por lo que acordamos una transición ordenada. Aprendí que la alineación cultural previa es fundamental para rendir al máximo.";
    }
    return "Como le decía, mi enfoque es siempre hacia los resultados futuros más que hacia el pasado. Pero dígame, en esta posición, ¿cuál sería el desafío prioritario para los primeros 90 días?";
}

function handleRobertoScenario($empathy, $solution, $turn, $name) {
    if ($empathy) {
        return "Le agradezco el tono humano, {$name}. He dejado muchos años en esta empresa y me duele sentir que la culpa recae solo sobre mí. Si la decisión está tomada, espero que la desvinculación sea justa y contemple todo el esfuerzo realizado.";
    }
    return "No estoy de acuerdo con la evaluación que hicieron. Si van a tomar esta medida, exijo que me den el detalle liquidado y las condiciones exactas antes de firmar cualquier documento.";
}

function handleSoniaScenario($empathy, $solution, $time, $turn, $name) {
    if ($empathy || $solution) {
        return "¡Ay, mil gracias {$name}! No sabe el alivio que me da. Si puede ofrecerme aunque sea una habitación provisional o un café mientras verifican la reserva, se lo voy a agradecer de corazón.";
    }
    return "Por favor, llevo más de 14 horas de viaje y apenas puedo tenerme en pie. Necesito que me confirmen una cama ahora mismo.";
}

function handleLuciaScenario($apology, $solution, $turn, $name) {
    if ($apology && $solution) {
        return "Valoro mucho su disculpa y la rapidez para cambiarme de habitación, {$name}. Un hotel de esta categoría debe responder así ante un imprevisto. Espero las nuevas llaves en recepción.";
    }
    return "No me alcanza con disculpas protocolares. Pagué una tarifa premium y merezco un servicio impecable. Espero que me trasladen de suite de inmediato.";
}

function handleAbelScenario($empathy, $solution, $turn, $name) {
    if ($empathy || $solution) {
        return "¡Tenías razón, {$name}! Estaba medio flojo el cable atrás de la caja esa... ¡Ya se mueve la flechita! Muchísimas gracias por la paciencia, sos un sol.";
    }
    return "Mirá, no entiendo mucho esto de los cables, hijo. ¿Me decís cuál tengo que tocar sin que se me borre la planilla?";
}

function handleGenericRoleplay($empathy, $solution, $turn, $name, $diff) {
    if ($turn >= 3 && $solution) {
        return "De acuerdo, {$name}, me parece que estamos llegando a un punto de entendimiento claro. Si formalizamos estos pasos, podemos dar por resuelto el asunto de manera satisfactoria.";
    }
    if ($empathy) {
        return "Aprecio que me escuche con atención, {$name}. Sin embargo, todavía me queda la inquietud sobre cómo van a garantizar que se cumplan los tiempos previstos.";
    }
    return "Comprendo lo que me plantea, {$name}, pero necesito precisiones. ¿Qué medidas concretas vamos a tomar a partir de ahora para avanzar?";
}

// -------------------------------------------------------------
// CONTROLADORES: CADENA DE MANDO ENTRELAZADA
// (Empleado -> Encargado -> Supervisor)
// -------------------------------------------------------------

function handleCadenaEmpleadoScenario($empathy, $solution, $time, $turn, $name, $diff) {
    if ($turn >= 2 && ($solution || $empathy)) {
        return "Mire {$name}, valoro mucho que no se lave las manos y me responda con tanta educación. Si usted me dice que su encargado Esteban ya está al tanto y me va a atender para resolverlo en 2 minutos, espero aquí tranquilo.";
    }
    if ($solution && $empathy) {
        return "Agradezco que me hable con calma, {$name}. Si usted tiene la facultad para cambiarme el producto ahora mismo sin hacerme dar vueltas, adelante, hágalo ya y dejamos el reclamo acá.";
    }
    if ($empathy) {
        return "Entiendo que no haya sido su intención, {$name}, pero tengo prisa y necesito una respuesta rápida. ¿Puede resolverlo usted o me pasa con quien esté a cargo del turno?";
    }
    return "¡No me diga que 'usted solo sigue órdenes'! Si usted no tiene autorización para darme una solución, no me haga perder tiempo y llame de inmediato a su encargado.";
}

function handleCadenaEncargadoScenario($empathy, $solution, $time, $turn, $name, $diff) {
    if ($turn >= 2 && $solution) {
        return "Le agradezco, {$name}. Veo que como encargado tiene la predisposición y la autoridad para resolver las cosas como corresponde. Acepto el reemplazo con la bonificación y valoro que respalde a su equipo con altura.";
    }
    if ($solution && ($empathy || $time)) {
        return "Buenas tardes {$name}. Su empleado me atendió con amabilidad pero me dijo que usted debía autorizar el reemplazo. Si me confirma esa solución ahora mismo, por mí el tema queda resuelto.";
    }
    if ($empathy) {
        return "Aprecio el tono, {$name}, pero como responsable del local necesito que me dé una solución concreta antes de irme. ¿Qué medida van a tomar con mi orden?";
    }
    return "Mire {$name}, espero que usted como encargado me dé una respuesta profesional y no me ponga más excusas como las que me dieron recién.";
}

function handleCadenaSupervisorScenario($empathy, $solution, $question, $turn, $name, $diff) {
    if ($turn >= 2 && ($question || $solution || $empathy)) {
        return "La verdad, {$name}, te agradezco mucho esta charla. Venía sintiendo que estaba solo apagando incendios todos los días y que la culpa siempre caía sobre mí. Me parece excelente armar ese checklist de verificación en el pase de turno para que los chicos no se confundan de nuevo. Me da mucha tranquilidad saber que tengo tu respaldo.";
    }
    if ($question || $empathy) {
        return "La verdad, {$name}, me sentí bastante desbordado. El cliente estaba furioso y siento que los chicos en el mostrador todavía no tienen la seguridad para contener esos reclamos. ¿Cómo crees que podríamos capacitarlos para que no me llamen por cualquier tontería?";
    }
    if ($solution) {
        return "Tiene sentido lo que propones, {$name}. Si definimos un protocolo claro de hasta qué monto pueden resolver ellos directamente en mostrador, me liberarías muchísimo tiempo para controlar la operación general.";
    }
    return "Sé que el incidente de hoy fue delicado, {$name}, pero hice lo mejor que pude para salvar la cuenta del cliente. ¿Qué puntos consideras prioritarios que ajustemos con el equipo?";
}

