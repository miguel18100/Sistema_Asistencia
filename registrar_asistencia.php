<?php

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

if (!logged()) {

    http_response_code(401);

    echo json_encode([
        'ok' => false,
        'error' => 'Sesión expirada.'
    ]);

    exit;
}

$data = json_decode(
    file_get_contents('php://input'),
    true
) ?: [];

$token = trim($data['token'] ?? '');
$sesion_id = (int)($data['sesion_id'] ?? 0);

if (!$sesion_id) {
    echo json_encode([
        'ok' => false,
        'error' => 'Se requiere enviar un ID de sesión válido.'
    ]);
    exit;
}

if (!$token) {

    echo json_encode([
        'ok' => false,
        'error' => 'Código QR vacío.'
    ]);

    exit;
}

$pdo = db();

$q = $pdo->prepare("
    SELECT *
    FROM aprendices
    WHERE qr_token = ?
    AND activo = 1
");

$q->execute([$token]);

$p = $q->fetch();

if (!$p) {

    echo json_encode([
        'ok' => false,
        'error' => 'QR no reconocido o aprendiz inactivo.'
    ]);

    exit;
}

$q_sesion = $pdo->prepare("
    SELECT id, ficha_id, inicia_en, tolerancia_minutos
    FROM sesiones_asistencia
    WHERE id = ?
    AND activa = 1
    AND vence_en > NOW()
");
$q_sesion->execute([$sesion_id]);
$sesion = $q_sesion->fetch();

if (!$sesion) {
    echo json_encode([
        'ok' => false,
        'error' => 'La sesión indicada no existe, está cerrada o ya venció.'
    ]);
    exit;
}

// Validación de pertenencia a la ficha si la sesión está ligada a una ficha específica
if ($sesion['ficha_id'] && $p['ficha_id'] != $sesion['ficha_id']) {
    echo json_encode([
        'ok' => false,
        'error' => 'El aprendiz no pertenece a la ficha de esta sesión.'
    ]);
    exit;
}

// Extracción y sanitización estricta de coordenadas GPS
$lat = filter_var($data['latitud'] ?? $data['gps']['latitud'] ?? null, FILTER_VALIDATE_FLOAT);
$lng = filter_var($data['longitud'] ?? $data['gps']['longitud'] ?? null, FILTER_VALIDATE_FLOAT);
$precision = filter_var($data['precision_gps'] ?? $data['gps']['precision'] ?? $data['precision'] ?? null, FILTER_VALIDATE_FLOAT);

$verificado_gps = 0;

// AC-4.5: Control de precisión GPS deficiente (> 100 metros)
if ($precision !== false && $precision !== null && $precision > 100) {
    echo json_encode([
        'ok' => false,
        'error' => 'La precisión GPS es insuficiente.'
    ]);
    exit;
}

// AC-4.4: Validación de Geocerca mediante Haversine (distancia_metros)
if (SENA_LAT != 0.0 && SENA_LNG != 0.0) {
    if ($lat === false || $lat === null || $lng === false || $lng === null || $precision === false || $precision === null) {
        echo json_encode([
            'ok' => false,
            'error' => 'Debes permitir la ubicación GPS para registrar la asistencia.'
        ]);
        exit;
    }

    $distancia = distancia_metros(SENA_LAT, SENA_LNG, (float)$lat, (float)$lng);

    if ($distancia > RADIO_MAX_METROS) {
        echo json_encode([
            'ok' => false,
            'error' => 'Estás fuera del perímetro autorizado.'
        ]);
        exit;
    }

    $verificado_gps = 1;
} elseif ($lat !== false && $lat !== null && $lng !== false && $lng !== null) {
    $verificado_gps = 1;
}

// Cálculo de retraso en minutos con base en la tolerancia oficial
$late = max(
    0,
    (int)floor((time() - strtotime($sesion['inicia_en'])) / 60) - (int)$sesion['tolerancia_minutos']
);

/*
|--------------------------------------------------------------------------
| La decisión de red se toma en el servidor.
| Bloquea IP privadas y proxies explícitos.
|--------------------------------------------------------------------------
*/

$clientIp = ip();

$headers = [
    'HTTP_VIA',
    'HTTP_X_FORWARDED_FOR',
    'HTTP_X_REAL_IP',
    'HTTP_FORWARDED'
];

$proxy = [];

foreach ($headers as $h) {
    if (!empty($_SERVER[$h])) {
        $proxy[] = $h;
    }
}

$local = in_array(
    $clientIp,
    ['127.0.0.1', '::1'],
    true
);

$private =
    !$local &&
    !filter_var(
        $clientIp,
        FILTER_VALIDATE_IP,
        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
    );

$status = $private
    ? 'bloqueada'
    : ($proxy ? 'sospechosa' : 'permitida');

$detail = $local
    ? 'Entorno local de pruebas'
    : (
        $private
            ? 'IP privada/no verificable'
            : (
                $proxy
                    ? 'Posible proxy: ' . implode(', ', $proxy)
                    : 'Sin indicadores de proxy'
            )
    );

if ($status === 'bloqueada') {
    echo json_encode([
        'ok' => false,
        'error' => 'Registro bloqueado: la IP de origen es privada o no verificable.',
        'status' => $status
    ]);
    exit;
}

$s = $pdo->prepare("
    INSERT INTO asistencias
    (
        aprendiz_id,
        sesion_id,
        ip,
        user_agent,
        estado_red,
        detalle_red,
        latitud,
        longitud,
        precision_gps,
        tarde_minutos,
        verificado_gps
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

try {
    $s->execute([
        $p['id'],
        $sesion['id'],
        $clientIp,
        substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        $status,
        $detail,
        $lat !== false ? $lat : null,
        $lng !== false ? $lng : null,
        $precision !== false ? $precision : null,
        $late,
        $verificado_gps
    ]);
} catch (PDOException $e) {
    // SQLSTATE 23000: Violación de índice único (registro duplicado)
    if ($e->getCode() == 23000) {
        echo json_encode([
            'ok' => false,
            'error' => 'El aprendiz ya tiene su asistencia registrada en esta sesión.'
        ]);
        exit;
    }
    throw $e;
}

echo json_encode([
    'ok' => true,
    'nombre' => $p['nombre'],
    'message' => $status === 'sospechosa'
        ? 'Asistencia registrada con alerta de red.'
        : ($late > 0 ? "Asistencia registrada · Llegada tarde: {$late} min." : 'Asistencia registrada correctamente.'),
    'status' => $status,
    'tarde_minutos' => $late,
    'verificado_gps' => $verificado_gps
]);