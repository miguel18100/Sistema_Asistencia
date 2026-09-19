<?php

require_once __DIR__ . '/layout.php';

$pdo = db();

$token = trim($_GET['s'] ?? $_POST['s'] ?? '');
$r = (int)($_GET['r'] ?? $_POST['r'] ?? 0);

if ($r > 0 && abs((int)floor(time() / 30) - $r) > 1) {
    $token = '';
}

$session = null;

if ($token) {

    $q = $pdo->prepare("
        SELECT
            s.*,
            f.numero,
            f.programa
        FROM sesiones_asistencia s
        LEFT JOIN fichas f
            ON f.id = s.ficha_id
        WHERE s.token = ?
        AND s.activa = 1
        AND s.vence_en > NOW()
    ");

    $q->execute([$token]);

    $session = $q->fetch();
}

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $session) {

    $lat = filter_var($_POST['latitud'] ?? null, FILTER_VALIDATE_FLOAT);
    $lng = filter_var($_POST['longitud'] ?? null, FILTER_VALIDATE_FLOAT);
    $precision = filter_var($_POST['precision_gps'] ?? null, FILTER_VALIDATE_FLOAT);

    if (empty($_POST['consentimiento'])) {

        $error = 'Debes aceptar el tratamiento de datos para registrar la asistencia.';

    } elseif ($lat === false || $lng === false || $precision === false) {

        $error = 'Debes permitir la ubicación GPS para registrar la asistencia.';

    } elseif ($precision > 100) {

        $error = 'La precisión GPS es insuficiente. Acércate a una ventana y vuelve a intentarlo.';

    } elseif (
        SENA_LAT != 0.0 &&
        distancia_metros(
            SENA_LAT,
            SENA_LNG,
            $lat,
            $lng
        ) > RADIO_MAX_METROS
    ) {

        $error = 'Estás fuera del perímetro autorizado de la sede.';

    } else {

        $q = $pdo->prepare("
            SELECT
                id,
                nombre,
                ficha_id
            FROM aprendices
            WHERE documento = ?
            AND activo = 1
        ");

        $q->execute([
            trim($_POST['documento'] ?? '')
        ]);

        $p = $q->fetch();

        if (!$p) {

            $error = 'Documento no encontrado. Pide apoyo al instructor.';

        } elseif (
            $session['ficha_id'] &&
            $p['ficha_id'] != $session['ficha_id']
        ) {

            $error = 'No perteneces a la ficha de esta sesión.';

        } else {

            try {

                $late = max(
                    0,
                    (int)floor(
                        (time() - strtotime($session['inicia_en'])) / 60
                    ) - (int)$session['tolerancia_minutos']
                );

                $i = $pdo->prepare("
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
                        verificado_gps,
                        consentimiento_datos
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        'permitida',
                        'Registro móvil con GPS',
                        ?,
                        ?,
                        ?,
                        ?,
                        1,
                        1
                    )
                ");

                $i->execute([
                    $p['id'],
                    $session['id'],
                    ip(),
                    substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                    $lat,
                    $lng,
                    $precision,
                    $late
                ]);

                $msg =
                    'Asistencia registrada, ' .
                    $p['nombre'] .
                    (
                        $late
                            ? ' · Llegada tarde: ' . $late . ' min.'
                            : ' · A tiempo.'
                    );

            } catch (PDOException $e) {

                $error = 'Tu asistencia ya fue registrada en esta sesión.';

            }
        }
    }
}

head('Registrar asistencia', false);

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

    <h1>
        Registro de asistencia
    </h1>

    <?php if ($session): ?>

        <p class="sub">
            Ficha
            <?= e($session['numero'] . ' · ' . $session['programa']) ?>
            <br>
            Se solicitará tu ubicación solo para validar la asistencia.
        </p>

        <?php if ($msg): ?>

            <div class="notice">
                <?= e($msg) ?>
            </div>

        <?php elseif ($error): ?>

            <div class="notice error">
                <?= e($error) ?>
            </div>

        <?php endif; ?>

        <form method="post" id="form">

            <input
                type="hidden"
                name="s"
                value="<?= e($token) ?>"
            >

            <input
                type="hidden"
                name="r"
                value="<?= e($r) ?>"
            >

            <input
                type="hidden"
                name="latitud"
                id="lat"
            >

            <input
                type="hidden"
                name="longitud"
                id="lng"
            >

            <input
                type="hidden"
                name="precision_gps"
                id="acc"
            >

            <label>
                Documento de identidad
            </label>

            <input
                name="documento"
                inputmode="numeric"
                required
                autofocus
                placeholder="Escribe tu documento"
            >

            <p
                style="text-align:left;font-size:12px"
            >

                <label
                    style="display:flex;gap:8px;align-items:flex-start;font-weight:400"
                >

                    <input
                        type="checkbox"
                        name="consentimiento"
                        value="1"
                        required
                        style="width:auto;margin-top:3px"
                    >

                    Autorizo el tratamiento de mi documento, hora y ubicación
                    para controlar asistencia, conforme a la
                    <a
                        href="politica_datos.php"
                        target="_blank"
                    >
                        política de datos personales
                    </a>.

                </label>

            </p>

            <button style="margin-top:14px">
                Validar ubicación y registrar
            </button>

        </form>

        <p
            class="sub"
            id="gps"
            style="font-size:12px"
        ></p>

        <script>

        document.querySelector('#form').addEventListener('submit', e => {

            if (document.querySelector('#lat').value) {
                return;
            }

            e.preventDefault();

            const t = document.querySelector('#gps');

            t.textContent = 'Solicitando ubicación…';

            navigator.geolocation.getCurrentPosition(

                p => {

                    lat.value = p.coords.latitude;
                    lng.value = p.coords.longitude;
                    acc.value = p.coords.accuracy;

                    t.textContent = 'Ubicación validada. Enviando…';

                    e.target.submit();

                },

                () => {

                    t.textContent =
                        'Debes permitir ubicación GPS en el navegador.';

                },

                {
                    enableHighAccuracy: true,
                    timeout: 12000,
                    maximumAge: 0
                }

            );

        });

        </script>

    <?php else: ?>

        <div class="notice error">
            QR vencido, cerrado o no válido.
            Solicita un QR nuevo al instructor.
        </div>

    <?php endif; ?>

</section>

<?php

foot();

?>