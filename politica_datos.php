<?php

require_once __DIR__ . '/layout.php';

head('Tratamiento de datos personales', false);

?>

<section
    class="card"
    style="max-width:850px;margin:40px auto"
>

    <img
        src="assets/sena-logo.svg"
        alt="SENA"
        style="width:170px"
    >

    <h1>
        Política breve de tratamiento de datos personales
    </h1>

    <p class="sub">
        Sistema de control de asistencia · versión operativa
    </p>

    <h2>
        Responsable y finalidad
    </h2>

    <p>
        El responsable es la sede o centro de formación que administra este sistema.
        Se recolectan documento, nombre, ficha, fecha y hora de asistencia,
        dirección IP y, cuando el aprendiz autoriza, latitud, longitud y precisión GPS.
        La finalidad exclusiva es verificar y gestionar la asistencia, calcular
        tardanzas, prevenir suplantación y atender requerimientos académicos o legales.
    </p>

    <h2>
        Autorización y derechos
    </h2>

    <p>
        Antes de registrar la asistencia se solicita autorización previa, expresa e
        informada. El titular puede conocer, actualizar, rectificar y solicitar la
        supresión de sus datos o revocar su autorización, salvo deber legal o
        contractual de conservación. Las solicitudes deben dirigirse al responsable
        de protección de datos de la sede. Para aprendices menores de edad, la
        institución debe gestionar la autorización del representante legal cuando
        corresponda.
    </p>

    <h2>
        Conservación y seguridad
    </h2>

    <p>
        Los datos se conservan solo durante el tiempo necesario para la finalidad
        académica y las obligaciones aplicables. El acceso debe limitarse a
        instructores o administradores autorizados; no deben compartirse ubicaciones
        con terceros ni usarse para fines distintos.
    </p>

    <h2>
        Marco de referencia
    </h2>

    <p>
        Este aviso se basa en la Ley 1581 de 2012 y sus normas reglamentarias.
        Antes de producción, la institución debe completar el nombre del responsable,
        canal de contacto, periodos de retención y su política institucional completa.
    </p>

    <p>

        <a
            class="btn"
            href="javascript:history.back()"
        >
            Volver
        </a>

    </p>

</section>

<?php

foot();

?>