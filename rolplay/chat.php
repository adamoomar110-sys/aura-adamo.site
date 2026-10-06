<?php
/**
 * RolPlay.ai - Motor de Simulación y Evaluación Pedagógica v3.0 Ultimate Cognitive Engine
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

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data) {
    echo json_encode([
        'status' => 'error',
        'response' => 'Solicitud inválida o cuerpo JSON vacío.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $data['action'] ?? 'chat';
$clientApiKey = !empty($data['apiKey']) ? trim($data['apiKey']) : '';
$envApiKey = getenv('GROQ_API_KEY') ?: '';
$GROQ_API_KEY = !empty($clientApiKey) ? $clientApiKey : $envApiKey;

// =========================================================================
// 1. ENDPOINT DE EVALUACIÓN OFICIAL DE RECURSOS HUMANOS (RRHH)
// =========================================================================
if ($action === 'evaluate') {
    $messages = $data['messages'] ?? [];
    $area = $data['area'] ?? 'Recursos Humanos';
    $scenario = $data['scenario'] ?? 'Evaluación General';
    $userName = !empty($data['userName']) ? trim($data['userName']) : 'Colaborador';
    $userCompany = !empty($data['userCompany']) ? trim($data['userCompany']) : 'Aura Talent';
    $evalType = $data['evalType'] ?? 'general';
    $targetRole = $data['targetRole'] ?? '';
    $currentRole = $data['currentRole'] ?? '';

    // Si Groq está configurado, intentar una evaluación asistida por IA
    if (!empty($GROQ_API_KEY) && str_starts_with($GROQ_API_KEY, 'gsk_')) {
        $aiEval = evaluateWithGroq($GROQ_API_KEY, $messages, $area, $scenario, $userName, $userCompany, $evalType, $targetRole);
        if ($aiEval) {
            echo json_encode([
                'status' => 'success',
                'engine' => 'groq-llama3.3-70b',
                'evaluation' => $aiEval
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // Motor Autónomo de Evaluación Heurística & Pedagógica de Aura v3.0
    $evaluation = evaluateAutonomousHR($messages, $area, $scenario, $userName, $userCompany, $evalType, $targetRole, $currentRole);

    echo json_encode([
        'status' => 'success',
        'engine' => 'aura-cognitive-hr-v3.0',
        'evaluation' => $evaluation
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// =========================================================================
// 2. ENDPOINT DE DIÁLOGO INTERACTIVO (SIMULACIÓN DE ROL)
// =========================================================================
$messages = $data['messages'] ?? [];
if (empty($messages)) {
    echo json_encode([
        'status' => 'error',
        'response' => 'No se recibieron mensajes para procesar el diálogo.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

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

// 2.1 Intento con Groq si está configurado
if (!empty($GROQ_API_KEY) && str_starts_with($GROQ_API_KEY, 'gsk_')) {
    $groqResult = callGroqApi($GROQ_API_KEY, $messages, $area, $scenario, $userName, $userCompany, $difficulty);
    if ($groqResult) {
        echo json_encode([
            'status' => 'success',
            'engine' => 'groq-llama3.3-70b',
            'response' => $groqResult['response'],
            'emotion' => $groqResult['emotion'] ?? 'En Conversación',
            'tension' => $groqResult['tension'] ?? 50,
            'tip' => $groqResult['tip'] ?? 'Continúa validando y estructurando soluciones concretas.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// 2.2 Motor Cognitivo Autónomo Aura v3.0 (Tensión dinámica, citas en contexto y feedback pedagógico)
$result = generateAuraCognitiveResponse($lastUserMessage, $messages, $area, $scenario, $userName, $difficulty);

echo json_encode([
    'status' => 'success',
    'engine' => 'aura-cognitive-v3.0',
    'response' => $result['response'],
    'emotion' => $result['emotion'],
    'tension' => $result['tension'],
    'tip' => $result['tip'],
    'skills_detected' => $result['skills']
], JSON_UNESCAPED_UNICODE);
exit;

// =========================================================================
// 3. MOTOR COGNITIVO AURA V3.0 (AUTÓNOMO, MULTIDIMENSIONAL & ADAPTATIVO)
// =========================================================================

function generateAuraCognitiveResponse($userText, $history, $area, $scenario, $userName, $difficulty) {
    $textLower = mb_strtolower($userText, 'UTF-8');
    
    // Contar cuántos turnos van
    $userTurn = 0;
    foreach ($history as $m) {
        if ($m['role'] === 'user') $userTurn++;
    }
    if ($userTurn === 0) $userTurn = 1;

    // Extracción de Habilidades del Usuario en este turno
    $skills = [];
    $hasEmpathy = preg_match('/(entiend|comprend|lament|disculp|perd[oó]n|veo tu molestia|tienes raz[oó]n|te escucho|s[eé] lo frustrante|tranquil|siento mucho)/u', $textLower);
    if ($hasEmpathy) $skills[] = 'Empatía & Validación Emocional';

    $hasSolution = preg_match('/(vamos a|te propongo|podemos|soluci[oó]n|reemplazo|devoluci[oó]n|bonificaci[oó]n|flete|despacho|revisar|cambio|proceso|procedimiento|plan|alternativa|acuerdo)/u', $textLower);
    if ($hasSolution) $skills[] = 'Propuesta de Solución Concreta';

    $hasTime = preg_match('/(minuto|hora|hoy|inmediat|ahora|antes de|mañana|a primera hora|plazo|fecha|tiempo)/u', $textLower);
    if ($hasTime) $skills[] = 'Compromiso Temporal Preciso';

    $hasQuestion = (str_contains($userText, '?') || str_contains($userText, '¿') || preg_match('/(c[oó]mo|cu[aá]ndo|cu[aá]l|qu[eé] te parece|te sirve|decime|cu[eé]ntame)/u', $textLower));
    if ($hasQuestion) $skills[] = 'Pregunta Calibrada / Indagación';

    $hasLeadership = preg_match('/(asumo|responsabilidad|equipo|coordinar|prioridad|est[aá]ndar|juntos|capacitaci[oó]n|delegar|confianza)/u', $textLower);
    if ($hasLeadership) $skills[] = 'Liderazgo & Accountability';

    $hasDefensive = preg_match('/(no es mi culpa|yo no fui|el sistema fall[oó]|es culpa de|usted no me dijo|no me corresponde|no tengo nada que ver|no es problema m[ií]o)/u', $textLower);
    if ($hasDefensive) $skills[] = 'Alerta: Justificación / Tono Defensivo';

    $isVeryShort = (mb_strlen(trim($userText)) < 22);
    if ($isVeryShort) $skills[] = 'Respuesta Excesivamente Breve';

    // Cálculo de Tensión Dinámica
    $baseTension = match (true) {
        str_contains($scenario, 'Furioso') || str_contains($scenario, 'Entrega Crítica') || str_contains($scenario, 'Error Operativo') => 95,
        str_contains($scenario, 'Producto Defectuoso') || str_contains($scenario, 'Feedback Correctivo') || str_contains($scenario, 'Negociación Agresiva') => 88,
        str_contains($scenario, 'Roce Interpersonal') || str_contains($scenario, 'De Par a Encargado') || str_contains($scenario, 'Cliente Escéptico') => 78,
        str_contains($scenario, 'Retraso Menor') || str_contains($scenario, 'Delegación Táctica') || str_contains($scenario, 'Coaching') => 65,
        default => 70
    };

    if ($difficulty === 'exigente') $baseTension = min(100, $baseTension + 10);
    if ($difficulty === 'colaborativo') $baseTension = max(30, $baseTension - 15);

    // Ajuste acumulado por turno y desempeño
    $delta = 0;
    $delta -= ($hasEmpathy ? 18 : 0);
    $delta -= ($hasSolution ? 24 : 0);
    $delta -= ($hasTime ? 12 : 0);
    $delta -= ($hasQuestion ? 8 : 0);
    $delta += ($hasDefensive ? 28 : 0);
    $delta += ($isVeryShort ? 16 : 0);

    // Con el paso de los turnos, si el usuario construye soluciones, la tensión decae
    $turnBonus = ($userTurn - 1) * 10;
    if (!$hasDefensive && ($hasSolution || $hasEmpathy)) {
        $currentTension = max(12, min(100, $baseTension + $delta - $turnBonus));
    } else {
        $currentTension = max(25, min(100, $baseTension + $delta));
    }

    // Etiqueta de Emoción y Color
    $emotion = match (true) {
        $currentTension >= 85 => '🚨 Furia / Tensión Máxima (' . $currentTension . '%)',
        $currentTension >= 65 => '⚠️ Escéptico / Desafiante (' . $currentTension . '%)',
        $currentTension >= 45 => '🟡 Evaluando Propuesta (' . $currentTension . '%)',
        $currentTension >= 25 => '🟢 Desescalando / Receptivo (' . $currentTension . '%)',
        default => '✨ Satisfecho / Acuerdo Alcanzado (' . $currentTension . '%)'
    };

    // Tip Pedagógico en Tiempo Real
    $tip = match (true) {
        $hasDefensive => '⚠️ Cuidado: Justificarte o culpar al sistema escala la molestia. Aplica Extreme Ownership: asume el control del resultado.',
        $isVeryShort => '💡 Consejo: Las respuestas telegráficas transmiten apatía. Agrega una frase de empatía y propone el siguiente paso.',
        !$hasEmpathy && $userTurn <= 2 => '💡 Consejo: Antes de ir directo al trámite, valida verbalmente la frustración del interlocutor (Método H.E.A.T.).',
        $hasEmpathy && !$hasSolution => '💡 Muy buena empatía: Ahora acompáñala con una acción resolutiva clara para no quedarte solo en palabras.',
        $hasSolution && !$hasTime => '💡 Excelente propuesta: Añade un compromiso de tiempo preciso (ej: "en 20 minutos" o "antes de las 18 hs") para dar certeza.',
        $currentTension <= 30 => '🎯 ¡Gran desescalada! El interlocutor está receptivo. Resume el acuerdo final y agradece cordialmente.',
        default => 'Continúa combinando escucha atenta, tono pausado y alternativas viables.'
    };

    // Generación del Diálogo Específico por Escenario y Turno
    $reply = generateScenarioDialogue($scenario, $userText, $textLower, $userTurn, $currentTension, $hasEmpathy, $hasSolution, $hasTime, $hasQuestion, $hasLeadership, $hasDefensive, $isVeryShort, $userName);

    return [
        'response' => $reply,
        'emotion' => $emotion,
        'tension' => $currentTension,
        'tip' => $tip,
        'skills' => $skills
    ];
}

function generateScenarioDialogue($sc, $raw, $txt, $turn, $tension, $emp, $sol, $time, $q, $lead, $def, $short, $name) {
    // -------------------------------------------------------------
    // 1. LAURA (Feedback Correctivo por Quejas Repetitivas)
    // -------------------------------------------------------------
    if (str_contains($sc, 'Feedback Correctivo')) {
        if ($def) {
            return "Mira {$name}, quejarte del sistema o de tus compañeros es exactamente la actitud que no podemos permitir. Cuando un cliente clave se queja dos veces en una semana, no hay excusa técnica que valga. Lo que estoy evaluando acá es si vas a tomar responsabilidad sobre tu turno o si tenemos que escalar esto a una sanción formal.";
        }
        if ($turn === 1) {
            if ($emp && $sol) {
                return "Valoro que no te pongas a la defensiva y admitas el impacto, {$name}. Me dices que vas a revisar el proceso, pero el cliente se sintió maltratado en el mostrador. ¿Cómo vas a asegurar que tu equipo mantenga la calma cuando la fila llega a la calle?";
            }
            return "Te escucho, {$name}. Pero el informe de la mañana detalla respuestas cortantes y desidia. Antes de que cerremos el acta, quiero saber: ¿qué provocó que perdieras la paciencia con esa cuenta?";
        }
        if ($turn === 2) {
            if ($sol && $time) {
                return "Me parece un plan concreto y con plazos claros. Me deja más tranquila ver que planteas ese seguimiento diario. Si me garantizas que de acá al viernes no hay un solo roce más, respaldaré tu gestión ante la gerencia.";
            }
            return "Eso suena razonable en la teoría, {$name}. Pero necesito medidas prácticas: ¿vas a supervisar personalmente el despacho de esa cuenta o vas a asignar a alguien de tu confianza?";
        }
        // Turno 3+
        return "Quedamos en ese compromiso formal, {$name}. Voy a monitorear las métricas de atención de tu área durante los próximos 15 días. Confío en tu capacidad para revertir esta imagen y demostrar tu madurez profesional.";
    }

    // -------------------------------------------------------------
    // 2. DAMIÁN / ROCE ENTRE PARES
    // -------------------------------------------------------------
    if (str_contains($sc, 'Roce Interpersonal') || str_contains($sc, 'Mediación')) {
        if ($def) {
            return "¡Siempre la misma historia, {$name}! Te escudás en que hiciste tu parte, pero cuando revienta el mediodía el que se queda transpirando en el depósito mientras vos te quedás en la máquina soy yo. ¡Así no se puede hacer equipo!";
        }
        if ($turn === 1) {
            if ($emp) {
                return "Bueno... la verdad me sorprende que me lo digas con esa tranquilidad, {$name}. El otro día me fui con una bronca bárbara porque sentí que me tiraste todo el fardo. Si querés que laburemos bien, tenemos que repartir las tareas más pesadas desde las 10 de la mañana.";
            }
            return "Decime concretamente qué querés que cambie, {$name}, porque yo vengo poniendo el pecho todos los días y siento que hagas lo que hagas nunca te alcanza.";
        }
        if ($turn === 2) {
            if ($sol) {
                return "Trato hecho. Si nos turnamos como decís y vos te encargás de la reposición los días pares, me parece más que justo. Te pido disculpas si levanté la voz el martes, la verdad estaba quemado.";
            }
            return "Está bien, pero que no quede solo en una charla de pasillo. ¿Cómo nos organizamos mañana para no volver a tropezar con lo mismo?";
        }
        return "Impecable, {$name}. Me quedo mucho más tranquilo sabiendo que podemos hablar las cosas de frente y sin mala leche. Cuenta conmigo para sacar el turno adelante.";
    }

    // -------------------------------------------------------------
    // 3. CARLOS (Cliente B2B Furioso por Entrega Fallida)
    // -------------------------------------------------------------
    if (str_contains($sc, 'Furioso por Entrega') || str_contains($sc, 'Entrega Crítica')) {
        if ($def) {
            return "¡¿A mí qué demonios me importa si el camión se rompió o si el chofer no llegó?! ¡Ustedes firmaron un contrato de suministro con penalidades diarias! Si a las 15 hs no tengo los pallets en mi fábrica, doy orden a legales de rescindir el contrato y demandarlos por daños y perjuicios.";
        }
        if ($turn === 1) {
            if ($emp && $sol) {
                return "Las disculpas no pagan los sueldos de mi planta que está parada, {$name}. Me hablas de un flete de contingencia, pero necesito precisión quirúrgica: ¿a qué hora exacta tocan la puerta de mi depósito esos insumos?";
            }
            return "¡No me vengan con formalismos! Tengo a 40 operarios cruzados de brazos esperando los insumos que ustedes prometieron para ayer a la mañana. ¿Quién se hace cargo de las horas extras que tengo que pagar hoy?";
        }
        if ($turn === 2) {
            if ($sol && $time) {
                return "Bien. Si ese flete especial llega antes de las 14:30 con remito sellado y absorben el costo del flete express, podemos salvar el turno de la tarde. Mándame la patente del transporte y el celular del chofer ya mismo.";
            }
            return "Necesito garantías por escrito, {$name}. No me voy a quedar esperando una promesa telefónica. Si no me mandas la confirmación por correo oficial en 10 minutos, paro la línea definitivamente.";
        }
        return "Agradezco la seriedad para capear el temporal, {$name}. El flete ya ingresó a la planta. Demostraste que en los momentos críticos das la cara con soluciones reales. Mantengamos la línea abierta.";
    }

    // -------------------------------------------------------------
    // 4. GONZALO (De Par a Encargado)
    // -------------------------------------------------------------
    if (str_contains($sc, 'De Par a Encargado') || str_contains($sc, 'Primera Conversación')) {
        if ($def || $short) {
            return "Pará un poco, {$name}. No te subas al caballo tan rápido. Hasta el viernes pasado nos quejábamos juntos de los turnos rotativos. No me vengas con tono de jefe militar porque vas a perder al equipo el primer día.";
        }
        if ($turn === 1) {
            if ($emp && $lead) {
                return "Mirá, te soy sincero {$name}: me chocó cuando avisaron que quedabas vos en el puesto, porque yo también me postulé y sentí que no me tuvieron en cuenta. Pero valoro que vengas a hablarme mano a mano y reconozcas mi experiencia en el sector. Decime qué esperás de mí en esta etapa.";
            }
            return "Hola {$name}... sí, todavía me tengo que acostumbrar a que ahora firmes vos las planillas. Espero que sigamos teniendo la misma confianza de siempre y no cambie el trato.";
        }
        if ($turn === 2) {
            if ($lead && $sol) {
                return "Me gusta la propuesta. Si me das autonomía para coordinar el sector de despacho y me tenés en cuenta para las decisiones operativas, tenés mi apoyo incondicional. El equipo te va a responder bien.";
            }
            return "Lo importante es que no te aisles en la oficina, {$name}. Cuando explota el salón necesitamos que estés cerca como siempre.";
        }
        return "Cuenta conmigo al 100%, {$name}. Vamos a demostrarle a la gerencia que este turno es el más eficiente de la empresa. ¡Muchos éxitos en la nueva función!";
    }

    // -------------------------------------------------------------
    // 5. LUCAS (Delegación en Hora Pico)
    // -------------------------------------------------------------
    if (str_contains($sc, 'Delegación Táctica') || str_contains($sc, 'Gestión de Prioridades')) {
        if ($turn === 1) {
            if ($sol) {
                return "¡Qué alivio, {$name}! El orden de prioridades me salvó la vida: me concentro en sacar las 4 comandas calientes y después apoyo en la reposición. ¿A quién le paso el teléfono mientras tanto?";
            }
            return "¡Jefe, de verdad no doy abasto! Tengo 6 pedidos pendientes en pantalla, el timbre de recepción sonando y el proveedor de lácteos en la puerta. ¿A qué le doy prioridad absoluta ya?";
        }
        return "¡Entendido al 100%! Asumo la estación caliente y en 20 minutos te doy el reporte del turno. Gracias por la claridad para bajar el pánico.";
    }

    // -------------------------------------------------------------
    // 6. MARTA (Retraso Menor / Abuela preocupada)
    // -------------------------------------------------------------
    if (str_contains($sc, 'Retraso Menor') || str_contains($sc, 'Marta')) {
        if ($short) {
            return "Disculpe que insista, pero no comprendí bien... ¿tengo que esperar sentada junto a la puerta hoy o puede ser que llegue mañana? Es que me da miedo no escuchar el timbre.";
        }
        if ($turn === 1) {
            if ($emp) {
                return "¡Ay, qué amabilidad la suya, {$name}! Da gusto que lo atiendan con tanta paciencia a uno que ya está grande. Si usted me dice que el paquete no está perdido y que viene en camino, me quedo mucho más tranquila.";
            }
            return "Muchas gracias por atenderme, mi vida. Solo quiero saber si ese código raro significa que el regalo para mi nieta va a llegar antes del fin de semana.";
        }
        return "¡Muchísimas gracias por su calidez, {$name}! Ya me anoté su nombre y el número que me dio. Ojalá en todos lados atendieran con el cariño y el respeto que me brindó usted.";
    }

    // -------------------------------------------------------------
    // 7. JORGE (Producto Defectuoso / Cafetera Rota)
    // -------------------------------------------------------------
    if (str_contains($sc, 'Producto Defectuoso') || str_contains($sc, 'Jorge')) {
        if ($def) {
            return "¡No me digan que tengo que hacer el reclamo en el correo! Yo le pagué a ustedes, no al transporte. Es el cumpleaños de mi esposa hoy y el regalo llegó hecho pedazos. No acepto evasivas.";
        }
        if ($turn === 1) {
            if ($emp && $sol) {
                return "Agradezco el tono y la disculpa, {$name}, pero las palabras no me resuelven la noche de hoy. Si me aseguran que me envían una unidad nueva por mensajería express o me gestionan el retiro hoy mismo, acepto el cambio.";
            }
            return "Pagué una fortuna por esa cafetera de primera línea y la caja venía rota. Díganme cómo lo van a compensar ya mismo.";
        }
        if ($turn === 2) {
            if ($sol && $time) {
                return "Eso sí es servicio postventa de calidad. Acepto el reemplazo inmediato con el voucher de compensación que propone. Mándeme el comprobante al WhatsApp.";
            }
            return "Quiero saber a qué hora me entregan el producto en condiciones. No puedo estar esperando todo el día en casa.";
        }
        return "Excelente gestión, {$name}. Reconozco cuando una empresa sabe resolver un error con rapidez y respeto. Tienen mi recomendación.";
    }

    // -------------------------------------------------------------
    // 8. PAULA (Negociación Agresiva B2B)
    // -------------------------------------------------------------
    if (str_contains($sc, 'Negociación Agresiva') || str_contains($sc, 'Paula')) {
        if ($turn === 1) {
            if ($sol && !str_contains($txt, '20%') && !str_contains($txt, 'descuento inmediato')) {
                return "Me gusta que no te achiques bajando el precio a la primera de cambio, {$name}. Demuestra que valoras tu servicio. Ahora bien: el competidor me ofrece soporte 24/7 sin cargo adicional. ¿Qué tienen ustedes que ellos no puedan darme?";
            }
            if (str_contains($txt, 'descuento') || str_contains($txt, '20%')) {
                return "Sabía que tenían margen inflado. Si me descontaste el 20% tan rápido, significa que me estabas cobrando de más. Quiero un 5% adicional y cerramos el contrato de 12 meses.";
            }
            return "Tengo solo 3 minutos antes de subir a mi vuelo, {$name}. Tu competidor me pasa una propuesta 20% más barata por prestaciones idénticas. Convénceme de no firmar con ellos.";
        }
        return "Bien jugado, {$name}. Me convenció la propuesta de valor y el nivel de integración que ofrecen. Mándame el contrato redactado a mi casilla ejecutiva hoy antes de las 18 hs y lo firmo.";
    }

    // -------------------------------------------------------------
    // 9. ROBERTO (Despido Disciplinario)
    // -------------------------------------------------------------
    if (str_contains($sc, 'Despido Disciplinario') || str_contains($sc, 'Roberto')) {
        if ($emp && $sol) {
            return "Agradezco que me lo digas mirándome a los ojos y con respeto, {$name}. Me duele la situación porque le di 4 años a esta empresa, pero entiendo que el ciclo se terminó. Por favor explícame cómo se liquidan mis días y las recomendaciones laborales.";
        }
        return "¿Por qué siempre la cuerda se corta por el lado más débil? Hubo fallas de todos los sectores y el único citado a Recursos Humanos soy yo.";
    }

    // -------------------------------------------------------------
    // 10. ESCENARIO POR DEFECTO CON NARRATIVA CONTEXTUAL
    // -------------------------------------------------------------
    if ($def) {
        return "{$name}, justificarse o echar culpas afuera no ayuda a resolver el problema que tenemos enfrente. Lo que necesito es que tomemos el control y definamos qué vamos a hacer ahora.";
    }
    if ($turn === 1) {
        if ($emp && $sol) {
            return "Valoro mucho la predisposición y la calma con la que me recibes, {$name}. Me parece bien lo que planteas en principio, pero me gustaría conocer los plazos exactos de ejecución.";
        }
        if ($emp) {
            return "Agradezco que me escuches con tanta atención, {$name}. Ahora bien, más allá de la comprensión mutua, ¿cuál es el paso concreto que vamos a dar hoy?";
        }
        return "Entiendo tu punto inicial, {$name}. Sin embargo, antes de avanzar, quiero tener la seguridad de que estamos alineados en la prioridad del caso.";
    }
    if ($turn >= 3 && ($sol || $time)) {
        return "Me parece un acuerdo sólido y bien estructurado, {$name}. Reconozco el profesionalismo para llevar la conversación a buen puerto. Procedamos con el plan.";
    }
    return "De acuerdo, {$name}. Me parece razonable tu enfoque. Asegurémonos de hacer el seguimiento correspondiente para que los resultados queden consolidados.";
}

// =========================================================================
// 4. EVALUADOR AUTÓNOMO MULTIDIMENSIONAL DE RRHH V3.0
// =========================================================================

function evaluateAutonomousHR($messages, $area, $scenario, $userName, $userCompany, $evalType, $targetRole, $currentRole) {
    $userMessages = [];
    $totalChars = 0;
    foreach ($messages as $m) {
        if ($m['role'] === 'user') {
            $userMessages[] = $m['content'];
            $totalChars += mb_strlen($m['content']);
        }
    }

    $turnCount = count($userMessages);
    if ($turnCount === 0) $turnCount = 1;
    $fullText = mb_strtolower(implode(' ', $userMessages), 'UTF-8');

    // 1. Análisis de Citas Textuales Reales del Usuario
    $sampleQuotes = [];
    foreach ($userMessages as $um) {
        if (mb_strlen($um) >= 25 && count($sampleQuotes) < 3) {
            $sampleQuotes[] = '"' . mb_substr($um, 0, 75) . (mb_strlen($um) > 75 ? '...' : '') . '"';
        }
    }

    // 2. Métricas de Impacto
    $empathyHits = preg_match_all('/(entiend|comprend|lament|disculp|perd[oó]n|veo tu molestia|tienes raz[oó]n|te escucho|s[eé] lo frustrante|tranquil|siento)/u', $fullText);
    $defensiveHits = preg_match_all('/(no es mi culpa|yo no fui|el sistema fall[oó]|es culpa de|usted no me dijo|no me corresponde|no tengo nada que ver)/u', $fullText);
    $solutionHits = preg_match_all('/(vamos a|te propongo|podemos|soluci[oó]n|reemplazo|devoluci[oó]n|bonificaci[oó]n|flete|despacho|revisar|cambio|proceso|plan|alternativa)/u', $fullText);
    $timeHits = preg_match_all('/(minuto|hora|hoy|inmediat|ahora|antes de|mañana|plazo|fecha)/u', $fullText);
    $leadershipHits = preg_match_all('/(asumo|responsabilidad|equipo|coordinar|prioridad|est[aá]ndar|juntos|capacitaci[oó]n|delegar|confianza|procedimiento)/u', $fullText);
    $questionHits = preg_match_all('/?|¿/u', $fullText);

    // 3. Ponderación por Dimensión (0 a 100)
    // Dimensión 1: Inteligencia Emocional & Control del Estrés
    $dimEmotional = 72 + ($empathyHits * 7) - ($defensiveHits * 20);
    $dimEmotional = max(30, min(99, $dimEmotional));

    // Dimensión 2: Comunicación Asertiva & Escucha Activa
    $dimAssertive = 70 + ($questionHits * 5) + ($turnCount >= 3 ? 10 : 0) - ($totalChars < ($turnCount * 25) ? 15 : 0);
    $dimAssertive = max(35, min(98, $dimAssertive));

    // Dimensión 3: Criterio Resolutivo & Sentido de Urgencia
    $dimResolutive = 68 + ($solutionHits * 8) + ($timeHits * 6);
    $dimResolutive = max(35, min(99, $dimResolutive));

    // Dimensión 4: Liderazgo Táctico & Alineación Estratégica
    $dimLeadership = 65 + ($leadershipHits * 8) + ($turnCount >= 4 ? 8 : 0) - ($defensiveHits * 12);
    $dimLeadership = max(30, min(98, $dimLeadership));

    // Global
    if ($evalType === 'promotion') {
        $scoreGlobal = round(($dimEmotional * 0.20) + ($dimAssertive * 0.25) + ($dimResolutive * 0.25) + ($dimLeadership * 0.30));
    } elseif ($evalType === 'diagnostic') {
        $scoreGlobal = round(($dimEmotional * 0.35) + ($dimAssertive * 0.30) + ($dimResolutive * 0.25) + ($dimLeadership * 0.10));
    } else {
        $scoreGlobal = round(($dimEmotional + $dimAssertive + $dimResolutive + $dimLeadership) / 4);
    }

    // Fortalezas
    $strengths = [];
    if ($empathyHits > 0) $strengths[] = "Excelente capacidad de desescalada: validó la emoción del interlocutor sin confrontar en " . $empathyHits . " ocasiones.";
    if ($solutionHits > 0) $strengths[] = "Foco resolutivo sobresaliente: planteó alternativas y soluciones ejecutables en lugar de quedarse en el lamento.";
    if ($timeHits > 0) $strengths[] = "Certeza operativa: brindó plazos y tiempos concretos, reduciendo la ansiedad del interlocutor.";
    if ($leadershipHits > 0) $strengths[] = "Accountability y visión de equipo: asumió la responsabilidad en primera persona sin trasladar culpas.";
    if (empty($strengths)) $strengths[] = "Mantuvo la compostura profesional a lo largo de toda la interacción evaluada.";

    // Alertas
    $alerts = [];
    if ($defensiveHits > 0) $alerts[] = "Riesgo de actitud defensiva: se detectaron justificaciones o transferencias de culpa al sistema o a terceros.";
    if ($solutionHits === 0) $alerts[] = "Falta de iniciativa de cierre: no se formularon propuestas de acción concretas con plazos definidos.";
    if ($totalChars < ($turnCount * 25)) $alerts[] = "Estilo de respuesta telegráfico: se recomienda explayarse más para generar cercanía y contención.";
    if (empty($alerts)) $alerts[] = "No se observaron brechas conductuales ni alertas de riesgo operativo durante la sesión.";

    // Veredicto
    $verdict = [];
    if ($evalType === 'promotion') {
        if ($scoreGlobal >= 85) {
            $verdict['status'] = 'Apto para Promoción Inmediata (Sobresaliente)';
            $verdict['color'] = 'green';
            $verdict['summary'] = "El colaborador {$userName} demuestra madurez de criterio, liderazgo táctico y temple sereno para asumir el cargo de {$targetRole}.";
            $verdict['recommendation'] = "Avanzar con la designación formal y asignar responsabilidades de coordinación a partir del próximo ciclo.";
        } elseif ($scoreGlobal >= 70) {
            $verdict['status'] = 'Apto con Plan de Acompañamiento Táctico';
            $verdict['color'] = 'yellow';
            $verdict['summary'] = "Muestra sólidas habilidades de base, pero requiere consolidar la asertividad y la delegación bajo presión de hora pico.";
            $verdict['recommendation'] = "Promoción condicionada a 30 días de mentoría con un Supervisor Senior en gestión de conflictos.";
        } else {
            $verdict['status'] = 'Requiere Maduración en Rol Actual';
            $verdict['color'] = 'red';
            $verdict['summary'] = "Aún se observan dificultades para liderar sin caer en justificaciones o vacíos resolutivos ante objeciones complejas.";
            $verdict['recommendation'] = "Mantener en funciones actuales y reevaluar en 90 días tras completar el programa de Liderazgo Táctico.";
        }
    } else {
        if ($scoreGlobal >= 80) {
            $verdict['status'] = 'Desempeño Profesional de Élite';
            $verdict['color'] = 'green';
            $verdict['summary'] = "Superó el escenario crítico de {$scenario} con apego riguroso a estándares de calidad y empatía activa.";
            $verdict['recommendation'] = "Cerrar la observación favorablemente y registrar la sesión como caso de éxito formativo.";
        } elseif ($scoreGlobal >= 65) {
            $verdict['status'] = 'En Observación Favorable';
            $verdict['color'] = 'yellow';
            $verdict['summary'] = "Resolución adecuada del incidente, aunque con margen para mejorar la velocidad de respuesta y el compromiso de plazos.";
            $verdict['recommendation'] = "Asignar práctica en el módulo de Desescalada Verbal de la Academia Aura.";
        } else {
            $verdict['status'] = 'Alerta Conductual / Intervención Requerida';
            $verdict['color'] = 'red';
            $verdict['summary'] = "Respuestas que no lograron contener la tensión del interlocutor, exponiendo a la empresa a pérdida de clientes o clima adverso.";
            $verdict['recommendation'] = "Reunión de coaching individual 1 a 1 y seguimiento quincenal de desempeño.";
        }
    }

    return [
        'candidate' => $userName,
        'company' => $userCompany,
        'current_role' => $currentRole ?: 'Colaborador',
        'target_role' => $targetRole ?: 'Puesto Actual',
        'eval_type' => $evalType,
        'scenario' => $scenario,
        'date' => date('Y-m-d H:i:s'),
        'total_turns' => $turnCount,
        'quotes' => $sampleQuotes,
        'scores' => [
            'emotional_regulation' => $dimEmotional,
            'assertive_communication' => $dimAssertive,
            'problem_solving' => $dimResolutive,
            'leadership_alignment' => $dimLeadership,
            'global' => $scoreGlobal
        ],
        'verdict' => $verdict,
        'strengths' => $strengths,
        'alerts' => $alerts,
        'action_plan' => [
            'day30' => ($scoreGlobal >= 80) ? "Liderar una simulación de alta tensión semanal y mentorizar a pares." : "Completar la práctica de Protocolos H.E.A.T. en el Laboratorio de RolPlay.",
            'day60' => ($scoreGlobal >= 80) ? "Auditar 5 casos de servicio al cliente y validar protocolos de contingencia." : "Reunión de seguimiento con jefatura de área para verificar erradicación de respuestas defensivas.",
            'day90' => ($scoreGlobal >= 80) ? "Evaluación 360° para consolidación de nuevo rango profesional." : "Reevaluación formal en RolPlay.ai con caso de crisis nivel 8."
        ]
    ];
}

// =========================================================================
// 5. LLM CLOUD CALLER (GROQ LLAMA 3.3 70B EN CASO DE ESTAR DISPONIBLE)
// =========================================================================

function callGroqApi($apiKey, $messages, $area, $scenario, $userName, $userCompany, $difficulty) {
    $systemPrompt = "Eres un simulador de rol interactivo de élite en español llamado RolPlay.ai (desarrollado por Aura). "
        . "Escenario: {$scenario} ({$area}). Tu interlocutor es {$userName} de la empresa {$userCompany}. Dificultad: {$difficulty}.\n"
        . "INSTRUCCIONES CLAVE:\n"
        . "1. Mantén estrictamente tu personaje en primera persona (cliente enojado, jefe exigente, compañero en conflicto, etc.).\n"
        . "2. Reacciona de forma REALISTA y EMOCIONAL a lo que acaba de decir {$userName}: si muestra empatía genuina y soluciones con plazos, desescala gradualmente; si se justifica o culpa a otros, moléstale más.\n"
        . "3. Responde en 1 o 2 oraciones concisas (máximo 45 palabras).\n"
        . "4. Devuelve un JSON EXACTO con esta estructura:\n"
        . "{\n"
        . "  \"response\": \"(tu respuesta hablada como personaje)\",\n"
        . "  \"emotion\": \"(etiqueta corta de tu emoción actual, ej: '🚨 Muy Furioso' o '🟡 Escéptico')\",\n"
        . "  \"tension\": (número de 0 a 100 de tu nivel de tensión),\n"
        . "  \"tip\": \"(un micro-consejo pedagógico para que {$userName} mejore en su próximo turno)\"\n"
        . "}";

    $formatted = [['role' => 'system', 'content' => $systemPrompt]];
    foreach ($messages as $m) {
        if (in_array($m['role'], ['user', 'assistant'])) {
            $formatted[] = ['role' => $m['role'], 'content' => $m['content']];
        }
    }

    $ch = curl_init("https://api.groq.com/openai/v1/chat/completions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer {$apiKey}"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => 'llama-3.3-70b-versatile',
        'messages' => $formatted,
        'response_format' => ['type' => 'json_object'],
        'temperature' => 0.65
    ]));

    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $res) {
        $json = json_decode($res, true);
        if (!empty($json['choices'][0]['message']['content'])) {
            return json_decode($json['choices'][0]['message']['content'], true);
        }
    }
    return null;
}

function evaluateWithGroq($apiKey, $messages, $area, $scenario, $userName, $userCompany, $evalType, $targetRole) {
    $transcript = "";
    foreach ($messages as $m) {
        $roleName = ($m['role'] === 'user') ? $userName : "Interlocutor";
        $transcript .= "{$roleName}: {$m['content']}\n";
    }

    $prompt = "Evalúa esta conversación de simulación de rol en español para el profesional {$userName} ({$userCompany}) en el escenario '{$scenario}'. Tipo de prueba: {$evalType}. Puesto objetivo: {$targetRole}.\n\nTranscripción:\n{$transcript}\n\nDevuelve JSON con scores (emotional_regulation, assertive_communication, problem_solving, leadership_alignment, global de 0 a 100), verdict (status, color 'green'|'yellow'|'red', summary, recommendation), strengths (array de 3 strings), alerts (array de 2 strings) y action_plan (day30, day60, day90).";

    $ch = curl_init("https://api.groq.com/openai/v1/chat/completions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer {$apiKey}"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => 'llama-3.3-70b-versatile',
        'messages' => [
            ['role' => 'system', 'content' => 'Eres un analista de talento senior y psicólogo laboral. Devuelve solo JSON válido.'],
            ['role' => 'user', 'content' => $prompt]
        ],
        'response_format' => ['type' => 'json_object'],
        'temperature' => 0.3
    ]));

    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $res) {
        $json = json_decode($res, true);
        if (!empty($json['choices'][0]['message']['content'])) {
            $parsed = json_decode($json['choices'][0]['message']['content'], true);
            if ($parsed && isset($parsed['scores'])) {
                $parsed['candidate'] = $userName;
                $parsed['company'] = $userCompany;
                $parsed['eval_type'] = $evalType;
                $parsed['scenario'] = $scenario;
                $parsed['date'] = date('Y-m-d H:i:s');
                $parsed['total_turns'] = count($messages);
                return $parsed;
            }
        }
    }
    return null;
}
