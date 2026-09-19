# GOB-03: Criterios de Aceptación del Dominio (Asistencias)
**Última revisión:** 2026-09-18 | **Estado:** VIGENTE

Para garantizar la integridad académica, los módulos core deben cumplir los siguientes criterios verificables.

---

## Módulo 1: Motor de Autenticación (`index.php`)

| AC | Criterio | Verificación |
| :- | :--- | :--- |
| AC-1.1 | Las contraseñas usan BCrypt vía `password_hash()`. | `SELECT password_hash FROM admins` → inicia con `$2y$`. |
| AC-1.2 | Instructor sin sesión válida no accede a rutas protegidas. | Acceder a `/dashboard.php` → redirige a `index.php`. |
| AC-1.3 | El token CSRF está presente en el login y es validado en POST. | Eliminar campo `csrf` del POST → retorna HTTP 419. |

---

## Módulo 2: Lógica del Escáner QR (`scanner.php` + `registrar_asistencia.php`)

| AC | Criterio | Verificación |
| :- | :--- | :--- |
| AC-2.1 | El escáner **no funciona** sin `?id=` en URL. | Acceder a `scanner.php` sin `?id=` → muestra error. |
| AC-2.2 | JSON al backend incluye `qr_token` y `sesion_id`. | Inspeccionar body del POST. |
| AC-2.3 | QR duplicado en misma sesión devuelve JSON de error. | Escanear 2 veces → `{"ok":false,"error":"Ya registrado..."}`. |

---

## Módulo 4: Registro por QR del Aprendiz (`registro.php`)

| AC | Criterio | Verificación |
| :- | :--- | :--- |
| AC-4.1 | Token vencido (`vence_en < NOW()`) muestra "QR vencido". | Acceder con token expirado → bloque de error. |
| AC-4.2 | Requiere aceptar política de datos antes de enviar. | Formulario bloqueado por HTML5 `required`. |
| AC-4.3 | Documento no encontrado devuelve error amigable. | Ingresar cédula ficticia → "Documento no encontrado". |
| AC-4.4 | Coordenadas fuera del radio autorizado (`RADIO_MAX_METROS`) son bloqueadas cuando `SENA_LAT != 0.0`. | Enviar POST con lat/lng distantes → debe mostrar "Estás fuera del perímetro autorizado". |
| AC-4.5 | Lecturas con precisión GPS deficiente (`precision_gps > 100`) son rechazadas. | Enviar POST con `precision_gps = 150` → debe mostrar "La precisión GPS es insuficiente". |
