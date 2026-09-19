<?php

require_once __DIR__ . '/layout.php';

require_login();

$pdo = db();

$total = $pdo->query("
    SELECT COUNT(*)
    FROM aprendices
    WHERE activo = 1
")->fetchColumn();

$today = $pdo->query("
    SELECT COUNT(*)
    FROM asistencias
    WHERE DATE(fecha_hora) = CURDATE()
")->fetchColumn();

$fichas = $pdo->query("
    SELECT COUNT(*)
    FROM fichas
    WHERE activa = 1
")->fetchColumn();

$alerts = $pdo->query("
    SELECT COUNT(*)
    FROM asistencias
    WHERE DATE(fecha_hora) = CURDATE()
    AND estado_red != 'permitida'
")->fetchColumn();

$latest = $pdo->query("
    SELECT
        a.*,
        p.nombre,
        p.documento,
        f.numero AS ficha
    FROM asistencias a
    JOIN aprendices p
        ON p.id = a.aprendiz_id
    LEFT JOIN fichas f
        ON f.id = p.ficha_id
    ORDER BY a.fecha_hora DESC
    LIMIT 8
")->fetchAll();

head('Panel principal');

?>

<style>

    /* ENCABEZADO DEL DASHBOARD */
    .dashboard-header {
        display: flex;
        align-items: center;
        gap: 20px;
        margin-bottom: 20px;
    }

    /* LOGO SENA */
    .dashboard-logo {
        width: 90px;
        height: 90px;
        object-fit: contain;
    }

    .dashboard-header h1 {
        margin: 0;
    }

    .dashboard-header .sub {
        margin: 8px 0 0 0;
    }

    /* RESPONSIVE PARA CELULAR */
    @media (max-width: 600px) {

        .dashboard-header {
            flex-direction: column;
            text-align: center;
        }

        .dashboard-logo {
            width: 80px;
            height: 80px;
        }

    }

</style>


<!-- ENCABEZADO CON LOGO -->

<div class="dashboard-header">

    <img
        src="img/sena.png"
        alt="Logo SENA"
        class="dashboard-logo"
    >

    <div>

        <h1>
            Buenos días, <?= e($_SESSION['admin']) ?>
        </h1>

        <p class="sub">
            Resumen operativo de asistencia SENA.
        </p>

    </div>

</div>


<section class="grid">

    <div class="card stat">
        <b><?= $total ?></b>
        <span>Aprendices activos</span>
    </div>

    <div class="card stat">
        <b><?= $today ?></b>
        <span>Registros hoy</span>
    </div>

    <div class="card stat">
        <b><?= $fichas ?></b>
        <span>Fichas activas</span>
    </div>

    <div class="card stat">
        <b><?= $alerts ?></b>
        <span>Alertas de red hoy</span>
    </div>

</section>


<section class="card" style="margin-top:22px">

    <div class="toolbar">

        <h2>Últimos registros</h2>

        <a class="btn" href="tomar_asistencia.php">
            Tomar asistencia
        </a>

    </div>


    <table>

        <tr>
            <th>Aprendiz</th>
            <th>Ficha</th>
            <th>Fecha / hora</th>
            <th>Red</th>
        </tr>


        <?php foreach ($latest as $r): ?>

            <tr>

                <td>
                    <b><?= e($r['nombre']) ?></b><br>
                    <small><?= e($r['documento']) ?></small>
                </td>

                <td>
                    <?= e($r['ficha'] ?? 'Sin ficha') ?>
                </td>

                <td>
                    <?= e($r['fecha_hora']) ?>
                </td>

                <td>
                    <span class="badge">
                        <?= e($r['estado_red']) ?>
                    </span>
                </td>

            </tr>

        <?php endforeach; ?>


        <?php if (!$latest): ?>

            <tr>
                <td colspan="4">
                    Aún no hay asistencias registradas.
                </td>
            </tr>

        <?php endif ?>

    </table>

</section>


<?php

foot();

?>