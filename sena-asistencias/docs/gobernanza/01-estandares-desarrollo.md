# GOB-01: Estándares de Desarrollo y Seguridad
**Proyecto:** sena-asistencias | **Entorno:** PHP 8.x / MySQL (XAMPP)  
**Última revisión:** 2026-09-18 | **Estado:** VIGENTE

---

## 1. Reglas de Acceso a Datos (Tolerancia Cero a SQLi)

### 1.1 Prohibición Absoluta
Ninguna consulta SQL puede concatenar variables de PHP directamente en el string SQL.

```php
// ❌ PROHIBIDO — vulnerable a Inyección SQL
$q = "SELECT * FROM admins WHERE usuario = '$_POST[u]'";

// ✅ OBLIGATORIO — PDO con sentencia preparada nativa
$stmt = db()->prepare("SELECT * FROM admins WHERE usuario = ?");
$stmt->execute([trim($_POST['u'] ?? '')]);
```

### 1.2 Patrón Estándar Completo
Todo acceso a la base de datos debe seguir el patrón: `prepare()` → `execute([...])` → `fetch()` / `fetchAll()`.

```php
// Patrón completo para lectura
$stmt = db()->prepare("SELECT id, nombre FROM aprendices WHERE documento = ? AND activo = 1");
$stmt->execute([trim($documento)]);
$aprendiz = $stmt->fetch(); // null si no existe

// Patrón completo para escritura con manejo de duplicado
try {
    $stmt = db()->prepare("INSERT INTO asistencias (aprendiz_id, sesion_id, ip) VALUES (?, ?, ?)");
    $stmt->execute([$aprendizId, $sesionId, ip()]);
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        // Violación de UNIQUE KEY — duplicado controlado
        return ['ok' => false, 'error' => 'Ya registrado en esta sesión.'];
    }
    throw $e; // Re-lanzar errores inesperados
}
```

### 1.3 Manejo de Duplicados
Las validaciones de unicidad deben delegarse a restricciones `UNIQUE KEY` en MySQL (no validaciones manuales con `SELECT` previo) y capturarse en PHP evaluando el código SQLSTATE `23000`.

---

## 2. Gestión de Sesiones y Estado

### 2.1 Punto Único de Sesión
`config.php` es el **único** archivo autorizado para iniciar y configurar sesiones PHP.  
Ningún otro archivo debe llamar `session_start()` directamente.

### 2.2 Configuración Mínima Obligatoria
```php
session_set_cookie_params([
    'lifetime' => 0,           // Expira al cerrar el navegador
    'path'     => '/',
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,        // Bloquea acceso desde JavaScript (XSS)
    'samesite' => 'Lax'        // Mitiga CSRF en navegación normal
]);
session_start();

// Previene Session Fixation en el primer acceso
if (!isset($_SESSION['iniciada'])) {
    session_regenerate_id(true);
    $_SESSION['iniciada'] = true;
}
```

### 2.3 Protección de Rutas
Toda página protegida debe invocar `require_login()` en la primera línea de lógica PHP.

```php
require_once __DIR__ . '/layout.php'; // Incluye config.php → inicia sesión
require_login();                       // Redirige a index.php si no está autenticado
```

---

## 3. Arquitectura Frontend / Backend

### 3.1 Separación de Responsabilidades
| Capa | Responsabilidad | Prohibición |
| :--- | :--- | :--- |
| Vista (`.php` HTML) | Renderizar datos ya procesados | Consultas SQL directas |
| Backend (lógica PHP) | Validar, consultar y transformar datos | Salida HTML directa sin `e()` |
| Base de Datos | Garantizar integridad referencial | Lógica de negocio en procedimientos almacenados |

### 3.2 Sanitización de Salida (Anti-XSS)
Todo valor de origen externo impreso en HTML debe pasar por la función `e()`:

```php
// e() es el wrapper seguro definido en config.php:
// htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')

// ❌ PROHIBIDO
echo $_GET['nombre'];
echo $row['programa'];

// ✅ OBLIGATORIO
echo e($_GET['nombre']);
echo e($row['programa']);

// En plantillas PHP-HTML
<h1><?= e($aprendiz['nombre']) ?></h1>
<td><?= e($row['documento']) ?></td>
```

### 3.3 Excepciones a la Sanitización
Los valores numéricos provenientes de la BD (enteros, floats) que NO son de entrada del usuario pueden imprimirse directamente. Los strings siempre deben usar `e()`.
