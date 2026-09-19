# GOB-06: Entorno de Ejecución y Despliegue (XAMPP)
**Última revisión:** 2026-09-18 | **Estado:** VIGENTE

---

## 1. Requisitos de Infraestructura Local

| Componente | Requisito Mínimo | Configuración Recomendada |
| :--- | :--- | :--- |
| **Servidor Web** | Apache 2.4 | Módulos `mod_rewrite`, `mod_headers` activos |
| **PHP** | PHP 8.0 o superior | Extensiones: `pdo_mysql`, `json`, `mbstring`, `openssl` |
| **Base de Datos** | MySQL 5.7+ / MariaDB 10.4+ | Motor por defecto `InnoDB`, Charset `utf8mb4` |
| **Entorno de Red** | Localhost / LAN Institucional | Soporte HTTPS recomendado para lector de cámara web |

---

## 2. Configuración y Blindaje del Servidor Apache

### 2.1 Protección de Archivos y Carpetas Sensibles (`.htaccess`)
Se debe colocar un archivo `.htaccess` en la raíz del proyecto para evitar la lectura pública de documentación, scripts y configuraciones:

```apache
# Proteger archivos de configuración y documentación
<FilesMatch "^(config\.php|\.env|\.git.*|database\.sql|.*\.sql)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Bloquear listado de directorios
Options -Indexes
```

### 2.2 Configuración Recomendada de PHP (`php.ini`)
* `upload_max_filesize = 10M`
* `post_max_size = 12M`
* `memory_limit = 128M`
* `date.timezone = "America/Bogota"` (Zona horaria oficial de Colombia)

---

## 3. Protocolo de Instalación Paso a Paso (Setup Inicial)

1. **Ubicación del Código:**
   Copiar el directorio `sena-asistencias` dentro del directorio de publicación web:
   ```
   C:\xampp\htdocs\sena-asistencias
   ```

2. **Inicio de Servicios:**
   Iniciar los módulos de **Apache** y **MySQL** desde el panel de control de XAMPP.

3. **Aprovisionamiento de Base de Datos:**
   * Abrir phpMyAdmin: `http://localhost/phpmyadmin`
   * Importar el archivo `database.sql` ubicado en la raíz del proyecto.
   * Esto creará la base de datos `sena_asistencias`, tablas base, índices y usuario inicial.

4. **Credenciales por Defecto de Prueba:**
   * **Usuario Administrador:** `admin`
   * **Contraseña:** `control1234`
   *(El sistema migrará automáticamente la contraseña al estándar seguro BCrypt en el primer login exitoso).*

---

## 4. Guía de Solución de Problemas (Troubleshooting)

| Síntoma / Error | Causa Raíz | Solución Operativa |
| :--- | :--- | :--- |
| **Error de conexión a MySQL** | Servicio MySQL apagado o credenciales incorrectas en `config.php`. | Verificar que MySQL esté en verde en XAMPP y validar `DB_USER` / `DB_PASS`. |
| **Cámara no inicia en `scanner.php`** | El navegador bloquea la cámara por falta de contexto seguro (no es `localhost` ni `HTTPS`). | Acceder estrictamente vía `http://localhost/...` o configurar certificado SSL local. |
| **Falla en registro con error 419** | Token CSRF expirado o ausente en el envío POST. | Recargar la vista o reenviar el formulario con sesión válida. |
| **Error 23000 en logs** | Intento de registro duplicado de asistencia en la misma sesión. | Comportamiento normal controlado por `try-catch` y la regla `UNIQUE KEY`. |
