# SENA · Control de asistencias QR

Aplicación independiente hecha en **PHP 8**, **JavaScript** y **MySQL** para registrar asistencia de aprendices por QR.

## Incluye

- Panel con métricas diarias.
- Gestión de aprendices, ficha y programa.
- Credencial QR individual e imprimible.
- Escáner mediante cámara (Chrome/Edge actuales) y registro manual de respaldo.
- El instructor puede generar un QR temporal; los aprendices registran su asistencia desde el celular.
- Código de seis dígitos de respaldo y registro manual por el instructor.
- Bitácora de IP y estado de seguridad de red.
- Bloqueo de direcciones privadas/no verificables y alerta cuando el servidor informa cabeceras de proxy.

## Instalación en XAMPP / WAMP

1. Extrae la carpeta `sena-asistencias` en `htdocs` (XAMPP) o `www` (WAMP).
2. Entra a phpMyAdmin, importa el archivo `database.sql` completo. Este crea la base `sena_asistencias` y datos iniciales.
3. En `config.php`, ajusta `DB_HOST`, `DB_NAME`, `DB_USER` y `DB_PASS` según tu MySQL.
4. Abre `http://localhost/sena-asistencias/`.

Credenciales iniciales: **admin** / **control1234**. En el primer acceso la contraseña temporal se guarda como hash seguro. Cámbiala antes de usar en producción.

Si ya importaste una versión anterior y no te permite entrar, importa una vez `fix-login.sql` desde phpMyAdmin y vuelve a usar esas credenciales.

Si actualizas desde una versión anterior, importa también `update-v4.sql` una vez antes de usar la toma de asistencia.

## Importante sobre VPN

Los navegadores no exponen si un dispositivo usa una VPN, por seguridad y privacidad. El sistema aplica los controles que sí son fiables desde una app web: registra la IP vista por el servidor, bloquea IP privada/no verificable y marca cabeceras de proxy. Para bloquear VPN comerciales con mayor precisión, conéctalo a un proveedor de reputación de IP (por ejemplo, mediante una API) en `registrar_asistencia.php`; requiere una cuenta/clave de ese proveedor.

El QR se genera con la biblioteca pública `qrcodejs` cargada al abrir la credencial. Para generar QR sin conexión, guarda una copia local de dicha biblioteca y reemplaza la URL del script en `qr.php`.

## Seguridad recomendada

- Publicar con HTTPS (necesario para la cámara fuera de localhost).
- Cambiar la contraseña inicial y restringir el panel a instructores.
- Hacer copias de seguridad periódicas de MySQL.
