<?php

require_once __DIR__ . '/layout.php';

require_login();

$pdo = db();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    check_csrf();

    $doc = trim($_POST['documento']);
    $token = 'SENA-' . bin2hex(random_bytes(12));

    try {

        $s = $pdo->prepare("
            INSERT INTO aprendices
            (
                documento,
                nombre,
                email,
                ficha_id,
                qr_token
            )
            VALUES
            (
                ?, ?, ?, ?, ?
            )
        ");

        $s->execute([
            $doc,
            trim($_POST['nombre']),
            trim($_POST['email']) ?: null,
            $_POST['ficha_id'] ?: null,
            $token
        ]);

        $msg = 'Aprendiz creado. Su QR ya est«¡ disponible.';

    } catch (PDOException $e) {

        $msg = 'No se pudo guardar: el documento ya existe.';

    }
}

$fichas = $pdo->query("
    SELECT *
    FROM fichas
    WHERE activa = 1
    ORDER BY numero
")->fetchAll();

$rows = $pdo->query("
    SELECT
        p.*,
        f.numero AS ficha,
        f.programa
    FROM aprendices p
    LEFT JOIN fichas f
        ON f.id = p.ficha_id
    ORDER BY p.id DESC
")->fetchAll();

head('Aprendices');

?>

<div class="toolbar">

    <div>

        <h1>Aprendices</h1>

        <p class="sub">
            Administra los datos y credenciales QR.
        </p>

    </div>

    <a class="btn" href="#nuevo">
        + Nuevo aprendiz
    </a>

</div>

<?php if ($msg): ?>

    <div class="notice">
        <?= e($msg) ?>
    </div>

<?php endif; ?>

<section
    class="card"
    id="nuevo"
    style="margin-bottom:22px"
>

    <h2>Registrar aprendiz</h2>

    <form
        method="post"
        class="fields"
    >

        <input
            type="hidden"
            name="csrf"
            value="<?= csrf() ?>"
        >

        <div>

            <label>Documento</label>

            <input
                name="documento"
                required
            >

        </div>

        <div>

            <label>Nombre completo</label>

            <input
                name="nombre"
                required
            >

        </div>

        <div>

            <label>Correo institucional</label>

            <input
                type="email"
                name="email"
            >

        </div>

        <div>

            <label>Ficha</label>

            <select name="ficha_id">

                <option value="">
                    Sin asignar
                </option>

                <?php foreach ($fichas as $f): ?>

                    <option value="<?= $f['id'] ?>">
                        <?= e($f['numero'] . ' - ' . $f['programa']) ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>

        <div class="wide">

            <button>
                Guardar y generar QR
            </button>

        </div>

    </form>

</section>

<section class="card">

    <table>

        <tr>

            <th>Documento</th>
            <th>Aprendiz</th>
            <th>Ficha / Programa</th>
            <th>QR</th>

        </tr>

        <?php foreach ($rows as $p): ?>

            <tr>

                <td>
                    <?= e($p['documento']) ?>
                </td>

                <td>

                    <b>
                        <?= e($p['nombre']) ?>
                    </b>

                    <br>

                    <small>
                        <?= e($p['email']) ?>
                    </small>

                </td>

                <td>

                    <?= e(
                        ($p['ficha'] ?? 'Sin ficha') .
                        ($p['programa']
                            ? ' - ' . $p['programa']
                            : '')
                    ) ?>

                </td>

                <td>

                    <a
                        class="btn ghost"
                        href="qr.php?id=<?= $p['id'] ?>"
                    >
                        Ver / Imprimir
                    </a>

                </td>

            </tr>

        <?php endforeach; ?>

    </table>

</section>

<?php

foot();

?>