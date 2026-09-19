<?php

require_once __DIR__ . '/layout.php';

require_login();

$rows = db()->query("
    SELECT
        a.*,
        p.nombre,
        p.documento,
        f.numero ficha
    FROM asistencias a
    JOIN aprendices p
        ON p.id = a.aprendiz_id
    LEFT JOIN fichas f
        ON f.id = p.ficha_id
    ORDER BY a.fecha_hora DESC
    LIMIT 300
")->fetchAll();

head('Asistencias');

?>

<div class="toolbar">

    <div>
        <h1>Historial de asistencias</h1>

        <p class="sub">
            Haz clic en “Ver mapa” para consultar la ubicación registrada.
        </p>
    </div>

    <a class="btn" href="tomar_asistencia.php">
        Tomar asistencia
    </a>

</div>

<section class="card">

    <table>

        <tr>
            <th>Fecha / hora</th>
            <th>Aprendiz</th>
            <th>Ficha</th>
            <th>Tardanza</th>
            <th>Ubicación</th>
            <th>Red</th>
        </tr>

        <?php foreach ($rows as $r): ?>

            <tr>

                <td>
                    <?= e($r['fecha_hora']) ?>
                </td>

                <td>
                    <b><?= e($r['nombre']) ?></b><br>
                    <small><?= e($r['documento']) ?></small>
                </td>

                <td>
                    <?= e($r['ficha'] ?? '—') ?>
                </td>

                <td>
                    <?= $r['tarde_minutos']
                        ? (int)$r['tarde_minutos'] . ' min'
                        : 'A tiempo'
                    ?>
                </td>

                <td>

                    <?php if ($r['latitud'] !== null && $r['longitud'] !== null): ?>

                        <small>
                            <?= e($r['latitud'] . ', ' . $r['longitud']) ?>
                        </small>

                        <br>

                        <a
                            class="btn ghost"
                            target="_blank"
                            href="https://www.google.com/maps?q=<?= urlencode($r['latitud'] . ',' . $r['longitud']) ?>"
                        >
                            Ver mapa
                        </a>

                    <?php else: ?>

                        <small>
                            No registrada
                        </small>

                    <?php endif ?>

                </td>

                <td>
                    <span class="badge">
                        <?= e($r['estado_red']) ?>
                    </span>
                </td>

            </tr>

        <?php endforeach ?>

    </table>

</section>

<?php

foot();

?>