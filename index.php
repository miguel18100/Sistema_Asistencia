<?php 

require_once __DIR__ . '/layout.php'; 

if (logged()) { 
    header('Location: dashboard.php'); 
    exit; 
} 

$error = ''; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') { 

    check_csrf(); 

    $q = db()->prepare(" 
        SELECT * 
        FROM admins 
        WHERE usuario = ? 
    "); 

    $q->execute([ 
        trim($_POST['usuario'] ?? '') 
    ]); 

    $a = $q->fetch(); 

    $pass = $_POST['password'] ?? ''; 
    $stored = $a['password_hash'] ?? ''; 

    $legacy = 
        ($a['usuario'] ?? '') === 'admin' && 
        hash_equals('control1234', $pass) && 
        ( 
            hash_equals('TEMP:control1234', $stored) || 
            hash_equals( 
                '$2y$10$BGeLUgNVdixNXFQYE/lMVubzOwwpTRleAReXIYxlP2nf0iuGipm2W', 
                $stored 
            ) 
        ); 

    if ( 
        $a && 
        ( 
            $legacy || 
            password_verify($pass, $stored) 
        ) 
    ) { 

        if ($legacy) { 

            db()->prepare(" 
                UPDATE admins 
                SET password_hash = ? 
                WHERE id = ? 
            ")->execute([ 
                password_hash($pass, PASSWORD_DEFAULT), 
                $a['id'] 
            ]); 

        } 

        $_SESSION['admin'] = $a['nombre']; 

        header('Location: dashboard.php'); 
        exit; 
    } 

    $error = 'Usuario o contraseña incorrectos.'; 
} 

head('Iniciar sesión', false); 

?>

<style>

    /* LOGO DEL SENA */
    .sena-logo {
        display: block;
        width: 180px;
        height: 180px;
        object-fit: contain;
        margin: 0 auto 25px auto;
    }

    /* TÍTULO */
    .card h1 {
        text-align: center;
        margin-top: 0;
    }

    /* SUBTÍTULO */
    .card > p {
        text-align: center;
    }

</style>

<section class="card">

    <!-- LOGO SENA -->
    <img 
        src="img/sena.png"
        alt="Logo SENA"
        class="sena-logo"
    >

    <h1>
        Control de asistencia
    </h1>

    <p>
        Acceso para instructores y administradores.
    </p>

    <?php if ($error): ?>

        <div class="notice error">
            <?= e($error) ?>
        </div>

    <?php endif ?>

    <form method="post">

        <input 
            type="hidden" 
            name="csrf" 
            value="<?= csrf() ?>"
        >

        <div>

            <label>
                Usuario
            </label>

            <input 
                name="usuario" 
                required 
                autofocus 
                placeholder="admin"
            >

        </div>

        <div>

            <label>
                Contraseña
            </label>

            <input 
                type="password" 
                name="password" 
                required 
                placeholder="••••••••"
            >

        </div>

        <button type="submit">
            Ingresar al sistema
        </button>

    </form>

    <p style="font-size:12px;margin-top:20px">

        Acceso inicial: 
        <b>admin</b> / 
        <b>control1234</b>

    </p>

</section>

<?php 

foot(); 

?>