<?php

require_once __DIR__ . '/config.php';

require_login();

db()->prepare("
    UPDATE sesiones_asistencia
    SET activa = 0
    WHERE token = ?
")->execute([
    $_GET['t'] ?? ''
]);

header('Location: tomar_asistencia.php');

?>