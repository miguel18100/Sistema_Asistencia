<?php

// ============================================
// CONFIGURACIÓN DE LA BASE DE DATOS - XAMPP
// ============================================

const DB_HOST = 'localhost';
const DB_NAME = 'sena_asistencias';
const DB_USER = 'root';
const DB_PASS = '';

// ============================================
// CONFIGURACIÓN DE GEOLOCALIZACIÓN
// ============================================

// Coordenadas de la sede.
// 0 desactiva la validación de ubicación.
const SENA_LAT = 0.0;
const SENA_LNG = 0.0;

// Radio máximo permitido en metros.
const RADIO_MAX_METROS = 300;


// ============================================
// INICIAR SESIÓN
// ============================================

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
    
    if (!isset($_SESSION['iniciada'])) {
        session_regenerate_id(true);
        $_SESSION['iniciada'] = true;
    }
}


// ============================================
// CONEXIÓN A LA BASE DE DATOS
// ============================================

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {

        try {

            $pdo = new PDO(
                'mysql:host=' . DB_HOST .
                ';dbname=' . DB_NAME .
                ';charset=utf8mb4',

                DB_USER,
                DB_PASS,

                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );

            // Crear o actualizar las tablas necesarias.
            ensure_attendance_schema($pdo);

        } catch (PDOException $e) {

            exit(
                '<h2>Error de conexión a MySQL</h2>' .
                '<p>' . htmlspecialchars(
                    $e->getMessage(),
                    ENT_QUOTES,
                    'UTF-8'
                ) . '</p>'
            );

        }
    }

    return $pdo;
}


// ============================================
// CREAR / ACTUALIZAR ESTRUCTURA DE ASISTENCIA
// ============================================

function ensure_attendance_schema(PDO $pdo): void
{

    // ----------------------------------------
    // TABLA SESIONES DE ASISTENCIA
    // ----------------------------------------

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS sesiones_asistencia (

            id INT AUTO_INCREMENT PRIMARY KEY,

            token VARCHAR(64) NOT NULL UNIQUE,

            codigo VARCHAR(8) NOT NULL UNIQUE,

            ficha_id INT NULL,

            creada_por VARCHAR(100) NOT NULL,

            inicia_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

            vence_en DATETIME NOT NULL,

            activa TINYINT(1) NOT NULL DEFAULT 1,

            tolerancia_minutos INT NOT NULL DEFAULT 10,

            INDEX idx_token (token),

            INDEX idx_codigo (codigo),

            INDEX idx_ficha_id (ficha_id)

        )
        ENGINE=InnoDB
        DEFAULT CHARSET=utf8mb4
    ");


    // ----------------------------------------
    // VERIFICAR SI EXISTE LA TABLA ASISTENCIAS
    // ----------------------------------------

    $tablaExiste = $pdo->query("
        SHOW TABLES LIKE 'asistencias'
    ")->fetchColumn();

    /*
     * Si todavía no existe la tabla asistencias,
     * no intentamos modificarla.
     */
    if (!$tablaExiste) {
        return;
    }


    // ========================================
    // AGREGAR COLUMNA sesion_id
    // ========================================

    $column = $pdo->query("
        SHOW COLUMNS
        FROM asistencias
        LIKE 'sesion_id'
    ")->fetch();

    if (!$column) {

        try {

            $pdo->exec("
                ALTER TABLE asistencias
                ADD COLUMN sesion_id INT NULL
                AFTER aprendiz_id
            ");

        } catch (PDOException $e) {

            // Si ya existe por alguna razón,
            // evitamos detener el sistema.
        }
    }


    // ========================================
    // AGREGAR COLUMNAS DE GEOLOCALIZACIÓN
    // ========================================

    $columnasAsistencia = [

        'latitud' =>
            'DECIMAL(10,7) NULL',

        'longitud' =>
            'DECIMAL(10,7) NULL',

        'precision_gps' =>
            'DECIMAL(8,2) NULL',

        'tarde_minutos' =>
            'INT NOT NULL DEFAULT 0',

        'verificado_gps' =>
            'TINYINT(1) NOT NULL DEFAULT 0',

        'consentimiento_datos' =>
            'TINYINT(1) NOT NULL DEFAULT 0'
    ];


    foreach ($columnasAsistencia as $name => $type) {

        $exists = $pdo->query("
            SHOW COLUMNS
            FROM asistencias
            LIKE '$name'
        ")->fetch();

        if (!$exists) {

            try {

                $pdo->exec("
                    ALTER TABLE asistencias
                    ADD COLUMN $name $type
                ");

            } catch (PDOException $e) {

                // Evita que falle si la columna
                // fue creada simultáneamente.
            }
        }
    }


    // ========================================
    // ÍNDICE ÚNICO POR SESIÓN Y APRENDIZ
    // ========================================

    try {

        $pdo->exec("
            ALTER TABLE asistencias
            ADD UNIQUE KEY un_registro_sesion (
                aprendiz_id,
                sesion_id
            )
        ");

    } catch (PDOException $e) {

        // El índice probablemente ya existe.
    }
}


// ============================================
// CALCULAR DISTANCIA ENTRE DOS COORDENADAS
// Fórmula de Haversine
// ============================================

function distancia_metros(
    float $lat1,
    float $lng1,
    float $lat2,
    float $lng2
): float {

    $r = 6371000;

    $dlat = deg2rad($lat2 - $lat1);
    $dlng = deg2rad($lng2 - $lng1);

    $a =
        sin($dlat / 2) ** 2 +

        cos(deg2rad($lat1)) *
        cos(deg2rad($lat2)) *

        sin($dlng / 2) ** 2;

    return $r * 2 * atan2(
        sqrt($a),
        sqrt(1 - $a)
    );
}


// ============================================
// VERIFICAR SI EL USUARIO ESTÁ LOGUEADO
// ============================================

function logged(): bool
{
    return !empty($_SESSION['admin']);
}


// ============================================
// PROTEGER PÁGINAS
// ============================================

function require_login(): void
{
    if (!logged()) {

        header('Location: index.php');
        exit;
    }
}


// ============================================
// ESCAPAR HTML
// ============================================

function e($v): string
{
    return htmlspecialchars(
        (string)$v,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}


// ============================================
// GENERAR TOKEN CSRF
// ============================================

function csrf(): string
{
    if (empty($_SESSION['csrf'])) {

        $_SESSION['csrf'] = bin2hex(
            random_bytes(32)
        );
    }

    return $_SESSION['csrf'];
}


// ============================================
// VALIDAR TOKEN CSRF
// ============================================

function check_csrf(): void
{
    $csrfSession = $_SESSION['csrf'] ?? '';
    $csrfPost = $_POST['csrf'] ?? '';

    if (
        empty($csrfSession) ||
        empty($csrfPost) ||
        !hash_equals(
            $csrfSession,
            $csrfPost
        )
    ) {

        http_response_code(419);

        exit('Solicitud no válida.');
    }
}


// ============================================
// OBTENER IP DEL CLIENTE
// ============================================

function ip(): string
{
    return $_SERVER['REMOTE_ADDR']
        ?? 'desconocida';
}

?>