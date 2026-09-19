<?php

require_once __DIR__ . '/config.php';


function head(
    string $title = 'Sistema de Asistencia',
    bool $showNav = true
): void {

    $pageTitle = e($title);

    ?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= $pageTitle ?>
    </title>


    <style>

        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background: #f4f6f8;

            color: #1f2937;

        }


        /* =====================================
           BARRA SUPERIOR
        ===================================== */

        .topbar {

            width: 100%;

            background: #39a900;

            color: white;

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 10px 30px;

            box-shadow:
                0 2px 8px
                rgba(0, 0, 0, 0.12);

        }


        .brand {

            display: flex;

            align-items: center;

            gap: 12px;

            text-decoration: none;

            color: white;

        }


        /* LOGO DEL SENA */

        .brand-logo {

            width: 52px;

            height: 52px;

            object-fit: contain;

        }


        .brand-text {

            display: flex;

            flex-direction: column;

        }


        .brand-text strong {

            font-size: 18px;

        }


        .brand-text span {

            font-size: 12px;

            opacity: 0.9;

        }


        /* =====================================
           NAVEGACIÓN
        ===================================== */

        .nav {

            display: flex;

            align-items: center;

            gap: 8px;

            flex-wrap: wrap;

        }


        .nav a {

            color: white;

            text-decoration: none;

            padding: 9px 12px;

            border-radius: 6px;

            font-size: 14px;

            transition: background 0.2s ease;

        }


        .nav a:hover {

            background:
                rgba(255, 255, 255, 0.18);

        }


        .nav .logout {

            background:
                rgba(0, 0, 0, 0.18);

        }


        .nav .logout:hover {

            background:
                rgba(0, 0, 0, 0.30);

        }


        /* =====================================
           CONTENEDOR PRINCIPAL
        ===================================== */

        .container {

            width: min(1200px, 92%);

            margin: 30px auto;

        }


        /* =====================================
           TARJETAS
        ===================================== */

        .card {

            background: white;

            border-radius: 12px;

            padding: 22px;

            box-shadow:
                0 4px 14px
                rgba(0, 0, 0, 0.08);

        }


        /* =====================================
           GRID
        ===================================== */

        .grid {

            display: grid;

            grid-template-columns:
                repeat(
                    auto-fit,
                    minmax(200px, 1fr)
                );

            gap: 18px;

        }


        /* =====================================
           ESTADÍSTICAS
        ===================================== */

        .stat {

            display: flex;

            flex-direction: column;

            gap: 8px;

        }


        .stat b {

            font-size: 32px;

            color: #39a900;

        }


        .stat span {

            color: #6b7280;

            font-size: 14px;

        }


        /* =====================================
           TEXTOS
        ===================================== */

        h1 {

            margin-top: 0;

        }


        h2 {

            margin-top: 0;

        }


        .sub {

            color: #6b7280;

            margin-bottom: 25px;

        }


        /* =====================================
           TOOLBAR
        ===================================== */

        .toolbar {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            margin-bottom: 18px;

        }


        /* =====================================
           BOTONES
        ===================================== */

        button,
        .btn {

            display: inline-block;

            border: none;

            border-radius: 7px;

            background: #39a900;

            color: white;

            padding: 11px 16px;

            cursor: pointer;

            text-decoration: none;

            font-size: 14px;

            transition:
                opacity 0.2s ease,
                transform 0.2s ease;

        }


        button:hover,
        .btn:hover {

            opacity: 0.9;

            transform:
                translateY(-1px);

        }


        /* =====================================
           FORMULARIOS
        ===================================== */

        form {

            display: grid;

            gap: 16px;

        }


        label {

            display: block;

            margin-bottom: 6px;

            font-weight: bold;

        }


        input,
        select {

            width: 100%;

            padding: 11px 12px;

            border: 1px solid #d1d5db;

            border-radius: 7px;

            font-size: 14px;

        }


        input:focus,
        select:focus {

            outline: none;

            border-color: #39a900;

            box-shadow:
                0 0 0 3px
                rgba(57, 169, 0, 0.12);

        }


        /* =====================================
           TABLAS
        ===================================== */

        table {

            width: 100%;

            border-collapse: collapse;

        }


        th {

            text-align: left;

            background: #f3f4f6;

        }


        th,
        td {

            padding: 13px;

            border-bottom:
                1px solid #e5e7eb;

        }


        /* =====================================
           BADGES
        ===================================== */

        .badge {

            display: inline-block;

            padding: 5px 9px;

            border-radius: 999px;

            background: #e8f7df;

            color: #237000;

            font-size: 12px;

            font-weight: bold;

        }


        /* =====================================
           MENSAJES
        ===================================== */

        .notice {

            padding: 12px;

            border-radius: 7px;

            margin-bottom: 15px;

        }


        .notice.error {

            background: #fee2e2;

            color: #991b1b;

        }


        .notice.success {

            background: #dcfce7;

            color: #166534;

        }


        /* =====================================
           PIE DE PÁGINA
        ===================================== */

        footer {

            text-align: center;

            color: #6b7280;

            padding: 25px;

            font-size: 13px;

        }


        /* =====================================
           RESPONSIVE
        ===================================== */

        @media (
            max-width: 850px
        ) {

            .topbar {

                flex-direction: column;

                align-items: flex-start;

                gap: 15px;

            }


            .nav {

                width: 100%;

            }


            .toolbar {

                flex-direction: column;

                align-items: flex-start;

            }

        }


        @media (
            max-width: 550px
        ) {

            .topbar {

                padding: 12px 18px;

            }


            .container {

                width: 94%;

                margin: 20px auto;

            }


            .brand-logo {

                width: 45px;

                height: 45px;

            }


            .nav {

                gap: 4px;

            }


            .nav a {

                padding: 7px 9px;

                font-size: 12px;

            }


            .card {

                padding: 16px;

            }


            table {

                font-size: 13px;

            }


            th,
            td {

                padding: 9px;

            }

        }

    </style>

</head>


<body>


<?php if ($showNav && logged()): ?>


    <!-- =================================
         BARRA SUPERIOR
    ================================== -->

    <header class="topbar">


        <!-- LOGO Y NOMBRE -->

        <a
            href="dashboard.php"
            class="brand"
        >

            <img
                src="img/sena.png"
                alt="Logo SENA"
                class="brand-logo"
            >


            <div class="brand-text">

                <strong>
                    Control de Asistencia
                </strong>

                <span>
                    SENA
                </span>

            </div>

        </a>


        <!-- MENÚ -->

        <nav class="nav">

            <a href="dashboard.php">
                Inicio
            </a>


            <a href="tomar_asistencia.php">
                Tomar asistencia
            </a>


            <a href="aprendices.php">
                Aprendices
            </a>


            <a href="fichas.php">
                Fichas
            </a>


            <a href="reportes.php">
                Reportes
            </a>


            <a
                href="logout.php"
                class="logout"
            >
                Cerrar sesión
            </a>

        </nav>


    </header>


<?php endif; ?>


<main class="container">

<?php

}


function foot(): void
{

    ?>

</main>


<footer>

    © <?= date('Y') ?>
    Sistema de Control de Asistencia SENA

</footer>


</body>

</html>

<?php

}

?>