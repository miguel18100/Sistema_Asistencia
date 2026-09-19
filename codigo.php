<?php

require_once __DIR__ . '/layout.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $code = preg_replace('/\D/', '', $_POST['codigo'] ?? '');

    $q = db()->prepare("
        SELECT token
        FROM sesiones_asistencia
        WHERE codigo = ?
        AND activa = 1
        AND vence_en > NOW()
    ");

    $q->execute([$code]);

    $s = $q->fetch();

    if ($s) {
        header('Location: registro.php?s=' . $s['token']);
        exit;
    }

    $error = 'Código inválido o vencido.';
}

head('Ingresar código', false);

?>

<section
    class="card"
    style="width:min(500px,92vw);margin:8vh auto;text-align:center"
>

    <img
        src="assets/sena-logo.svg"
        alt="SENA"
        style="width:170px"
    >

    <h1>Registro con código</h1>

    <p class="sub">
        Escribe el código de 6 dígitos que muestra tu instructor.
    </p>

    <?php if ($error): ?>

        <div class="notice error">
            <?= e($error) ?>
        </div>

    <?php endif ?>

    <form method="post">

        <label>Código de asistencia</label>

        <input
            name="codigo"
            inputmode="numeric"
            maxlength="6"
            required
            autofocus
        >

        <button style="margin-top:14px">
            Continuar
        </button>

    </form>

</section>

<?php

foot();

?>