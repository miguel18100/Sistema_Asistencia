<?php

require_once __DIR__ . '/layout.php';

require_login();

$sesion_id = (int)($_GET['id'] ?? 0);

if (!$sesion_id) {
    exit('<div style="padding: 20px; font-family: sans-serif;"><h2>Error</h2><p>Para usar el escáner debes especificar una sesión en la URL (ejemplo: scanner.php?id=123).</p></div>');
}

head('Escáner QR');

?>

<div class="toolbar">

    <div>

        <h1>
            Registrar asistencia por QR
        </h1>

        <p class="sub">
            El navegador solicitará acceso a la cámara.
        </p>

    </div>

</div>

<section class="scanner">

    <div class="card">

        <video
            id="video"
            class="video"
            autoplay
            playsinline
            muted
        ></video>

        <p
            id="cameraStatus"
            class="sub"
        >
            Preparando cámara…
        </p>

        <button id="start">
            Activar cámara
        </button>

    </div>

    <div class="card">

        <h2>
            Resultado
        </h2>

        <div
            class="result"
            id="result"
        >
            Escanea el código QR del aprendiz o ingresa el token manualmente.
        </div>

        <hr>

        <label>
            Token QR
        </label>

        <input
            id="token"
            placeholder="SENA-..."
        >

        <button
            style="margin-top:10px"
            id="manual"
        >
            Registrar token
        </button>

        <p
            class="sub"
            style="font-size:12px"
        >
            Detección VPN: se valida IP del servidor, indicadores de proxy
            enviados por el servidor y el contexto de red del navegador.
            Un navegador no puede identificar todas las VPN por diseño
            de privacidad.
        </p>

    </div>

</section>

<script>

let stream;
let timer;
let busy = false;

const v = document.querySelector('#video');
const out = document.querySelector('#result');
const status = document.querySelector('#cameraStatus');

async function submit(token) {

    if (busy || !token) return;

    busy = true;

    out.innerHTML = 'Validando registro…';

    try {

        const r = await fetch(
            'registrar_asistencia.php',
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    token,
                    sesion_id: <?= $sesion_id ?>,
                    network: {
                        online: navigator.onLine,
                        connection:
                            navigator.connection?.effectiveType ||
                            'desconocida',
                        language: navigator.language
                    }
                })
            }
        );

        const j = await r.json();

        out.innerHTML = j.ok
            ? '<div class="notice"><b>Registro exitoso</b><br>' +
              j.nombre +
              '<br><small>' +
              j.message +
              '</small></div>'
            : '<div class="notice error">' +
              j.error +
              '</div>';

    } catch (e) {

        out.innerHTML =
            '<div class="notice error">' +
            'No fue posible conectar con el servidor.' +
            '</div>';

    }

    busy = false;

}

document.querySelector('#manual').onclick = () => {

    submit(
        document
            .querySelector('#token')
            .value
            .trim()
    );

};

document.querySelector('#start').onclick = async () => {

    try {

        stream = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: 'environment'
            }
        });

        v.srcObject = stream;

        status.textContent =
            'Cámara activa. Enfoca el QR.';

        if (!('BarcodeDetector' in window)) {

            status.textContent =
                'Cámara activa. Tu navegador no ofrece lector QR nativo; usa el token manual.';

            return;

        }

        const det = new BarcodeDetector({
            formats: ['qr_code']
        });

        timer = setInterval(async () => {

            if (v.readyState < 2 || busy) {
                return;
            }

            const codes = await det.detect(v);

            if (codes[0]) {

                document.querySelector('#token').value =
                    codes[0].rawValue;

                submit(codes[0].rawValue);

            }

        }, 650);

    } catch (e) {

        status.textContent =
            'No se pudo abrir la cámara. Verifica los permisos y usa HTTPS o localhost.';

    }

};

</script>

<?php

foot();

?>