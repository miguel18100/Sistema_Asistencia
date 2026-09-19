<?php

require_once __DIR__ . '/config.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: tomar_asistencia.php');
    exit;
}

check_csrf();

$pdo = db();
$token = trim($_POST['t'] ?? '');

$q = $pdo->prepare("
    SELECT *
    FROM sesiones_asistencia
    WHERE token = ?
    AND creada_por = ?
    AND activa = 1
    AND vence_en > NOW()
");

$q->execute([
    $token,
    $_SESSION['admin'] ?? ''
]);

$s = $q->fetch();

if (!$s) {
    header('Location: sesion.php?t=' . urlencode($token) . '&error=no_autorizado');
    exit;
}

$q = $pdo->prepare("
    SELECT *
    FROM aprendices
    WHERE documento = ?
    AND activo = 1
");

$q->execute([
    trim($_POST['documento'] ?? '')
]);

$p = $q->fetch();

if (
    !$p ||
    (
        $s['ficha_id'] &&
        $p['ficha_id'] != $s['ficha_id']
    )
) {
    header('Location: sesion.php?t=' . urlencode($s['token']) . '&error=aprendiz');
    exit;
}

try {
    $i = $pdo->prepare("
        INSERT INTO asistencias
        (
            aprendiz_id,
            sesion_id,
            ip,
            user_agent,
            estado_red,
            detalle_red
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            'permitida',
            'Registro manual por instructor'
        )
    ");

    $i->execute([
        $p['id'],
        $s['id'],
        ip(),
        substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
    ]);

    header('Location: sesion.php?t=' . urlencode($s['token']) . '&msg=registrado');
    exit;

} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        header('Location: sesion.php?t=' . urlencode($s['token']) . '&error=duplicado');
        exit;
    }
    throw $e;
}
