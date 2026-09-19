<?php

require_once __DIR__ . '/layout.php';

require_login();

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    check_csrf();

    $token = bin2hex(random_bytes(20));

    do {

        $code = str_pad(
            (string) random_int(0, 999999),
            6,
            '0',
            STR_PAD_LEFT
        );

        $q = $pdo->prepare("
            SELECT id
            FROM sesiones_asistencia
            WHERE codigo = ?
            AND activa = 1
        ");

        $q->execute([
            $code
        ]);

    } while ($q->fetch());

    $inicio = $_POST['inicio']
        ? str_replace('T', ' ', $_POST['inicio']) . ':00'
        : date('Y-m-d H:i:s');

    $s = $pdo->prepare("
        INSERT INTO sesiones_asistencia
        (
            token,
            codigo,
            ficha_id,
            creada_por,
            inicia_en,
            vence_en,
            tolerancia_minutos
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            DATE_ADD(NOW(), INTERVAL 20 MINUTE),
            ?
        )
    ");

    $s->execute([
        $token,
        $code,
        $_POST['ficha_id'] ?: null,
        $_SESSION['admin'],
        $inicio,
        max(0, (int) $_POST['tolerancia'])
    ]);

    header('Location: sesion.php?t=' . $token);

    exit;
}

$fichas = $pdo->query("
    SELECT *
    FROM fichas
    WHERE activa = 1
    ORDER BY numero
")->fetchAll();

head('Tomar asistencia');

?>

<div
    class="card"
    style="max-width:600px;margin:auto"
>

    <h1>
        Iniciar toma de asistencia
    </h1>

    <p class="sub">
        El QR rota cada 30 segundos y solicita ubicación GPS al aprendiz.
    </p>

    <form
        method="post"
        class="fields"
    >

        <input
            type="hidden"
            name="csrf"
            value="<?= csrf() ?>"
        >

        <div class="wide">

            <label>
                Ficha de la clase
            </label>

            <select
                name="ficha_id"
                required
            >

                <?php foreach ($fichas as $f): ?>

                    <option value="<?= $f['id'] ?>">
                        <?= e($f['numero'] . ' - ' . $f['programa']) ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>

        <div>

            <label>
                Hora oficial de inicio
            </label>

            <input
                name="inicio"
                type="datetime-local"
                value="<?= date('Y-m-d\TH:i') ?>"
                required
            >

        </div>

        <div>

            <label>
                Tolerancia (minutos)
            </label>

            <input
                name="tolerancia"
                type="number"
                min="0"
                max="120"
                value="10"
                required
            >

        </div>

        <div class="wide">

            <button>
                Generar QR y código
            </button>

        </div>

    </form>

</div>

<?php

foot();

?>