<?php

require_once __DIR__ . '/layout.php';

require_login();

$s = db()->prepare("
    SELECT
        p.*,
        f.numero ficha,
        f.programa
    FROM aprendices p
    LEFT JOIN fichas f
        ON f.id = p.ficha_id
    WHERE p.id = ?
");

$s->execute([
    (int)($_GET['id'] ?? 0)
]);

$p = $s->fetch();

if (!$p) {

    http_response_code(404);
    exit('Aprendiz no encontrado');

}

head('C¨®digo QR');

?>

<div
    class="card"
    style="max-width:650px;margin:auto;text-align:center"
>

    <h1>
        <?= e($p['nombre']) ?>
    </h1>

    <p class="sub">

        Documento <?= e($p['documento']) ?> ¡¤
        Ficha <?= e($p['ficha'] ?? 'Sin asignar') ?>

    </p>

    <div
        id="qrcode"
        style="display:inline-block;padding:18px;background:#fff;border:1px solid #ddd"
    ></div>

    <p>

        <b>Token:</b>
        <?= e($p['qr_token']) ?>

    </p>

    <p class="sub">
        Presenta este c¨®digo al instructor para registrar tu asistencia.
    </p>

    <button onclick="window.print()">
        Imprimir QR
    </button>

</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

<script>

new QRCode(
    document.getElementById('qrcode'),
    {
        text: <?= json_encode($p['qr_token']) ?>,
        width: 260,
        height: 260,
        colorDark: '#173100',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.H
    }
);

</script>

<?php

foot();

?>