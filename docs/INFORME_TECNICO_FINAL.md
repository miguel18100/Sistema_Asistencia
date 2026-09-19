# INFORME TÉCNICO CONSOLIDADO DE ARQUITECTURA Y SEGURIDAD
**Proyecto:** sena-asistencias | **Entorno:** PHP 8.x / MySQL (XAMPP)  
**Fecha de Certificación:** 2026-09-18 | **Estado:** BLINDADO Y AUDITADO

---

## 1. Resumen Ejecutivo

El sistema de control de asistencia `sena-asistencias` ha sido sometido a una auditoría técnica profunda (Red Team) y refactorización defensiva bajo las normas de la suite de gobernanza oficial (**GOB-01 a GOB-06**). 

Se cerraron brechas críticas en **gestión de sesiones (Session Hijacking / Fixation)**, **integridad relacional e idempotencia (prevención de duplicados)**, **autorización (Ownership RBAC)** y **validación de geocercas matemáticas (Haversine)**.

---

## 2. Estado de la Suite de Gobernanza (`docs/gobernanza/`)

| Código | Documento | Estado | Resumen Normativo |
| :--- | :--- | :---: | :--- |
| **GOB-01** | Estándares de Desarrollo | 🟢 VIGENTE | Tolerancia cero a SQLi vía PDO nativo; manejo obligatorio de `SQLSTATE 23000`; helper `e()`. |
| **GOB-02** | Flujo de Trabajo Git | 🟢 VIGENTE | Trunk-Based Development, Semantic Commits y Definition of Done (DoD) estricto. |
| **GOB-03** | Criterios de Aceptación | 🟢 VIGENTE | 14 criterios de aceptación (`AC-1.1` a `AC-4.5`) con métodos de prueba paso a paso. |
| **GOB-04** | Arquitectura de Datos | 🟢 VIGENTE | Motor `InnoDB`, `utf8mb4`, políticas de borrado lógico y restricciones `UNIQUE KEY`. |
| **GOB-05** | Matriz de Seguridad | 🟢 VIGENTE | Matriz `SEC-01` a `SEC-12` con vectores de ataque, severidad y controles. |
| **GOB-06** | Entorno y Despliegue | 🟢 VIGENTE | Configuración para Apache 2.4 / XAMPP, `php.ini` y troubleshooting de errores. |

---

## 3. Matriz de Auditoría y Refactorizaciones de Módulos

### 3.1 `config.php` (Núcleo de Seguridad)
* **Cookies de Sesión:** Parámetros obligatorios `HttpOnly = true`, `SameSite = Lax`, `Secure = (HTTPS)` implementados antes de `session_start()`.
* **Anti-Fixation:** `session_regenerate_id(true)` en el primer acceso del usuario.
* **Cálculo Geodésico:** Función `distancia_metros()` implementada con la fórmula de Haversine para geocercas de radio métrico.

### 3.2 `registrar_asistencia.php` (Backend API)
* **Geocercas (AC-4.4):** Si `SENA_LAT != 0.0`, valida la presencia de coordenadas y rechaza registros fuera de `RADIO_MAX_METROS`.
* **Precisión GPS (AC-4.5):** Rechaza lecturas con `precision_gps > 100m` con respuesta JSON controlada.
* **Idempotencia:** Inserción defensiva PDO con captura de `PDOException (23000)` para bloquear duplicados en la misma sesión.
* **Tardanza:** Cálculo dinámico de minutos de llegada tarde basado en la tolerancia oficial de la sesión.

### 3.3 `scanner.php` y `sesion.php` (Frontend y UX)
* **Contexto Estricto:** `scanner.php` exige `?id=` de sesión en URL e inyecta `sesion_id` en el payload POST.
* **Integración Visual:** Botón *"📷 Abrir Escáner QR"* añadido en `sesion.php` vinculado a la sesión activa.
* **Feedback Visual:** Bloque de alertas condicionales para errores de duplicidad, aprendiz no encontrado o falta de permisos.

### 3.4 `registro_manual.php` (Contingencia del Instructor)
* **Control de Propiedad (Ownership):** Valida que la sesión pertenezca al instructor autenticado (`creada_por = $_SESSION['admin']`).
* **Aislamiento por Ficha:** Bloquea registros de aprendices pertenecientes a fichas ajenas a la sesión.
* **Manejo de Errores:** Redirecciones explícitas con parámetros `&error=duplicado`, `&error=aprendiz`, `&error=no_autorizado`, `&msg=registrado`.

### 3.5 `codigo.php` (Resolvedor de Código de 6 Dígitos)
* **Validación de Vigencia:** Resuelve tokens únicamente para sesiones con `activa = 1 AND vence_en > NOW()`.
* **Sanitización SQL:** Inmune a SQLi mediante PDO preparado y filtrado regex `preg_replace('/\D/', '', ...)`.

### 3.6 `aprendices.php` (Gestión de Aprendices)
* **Entropía de Tokens:** Generación con CSPRNG nativo (`SENA-` + `bin2hex(random_bytes(12))`).
* **Anti-XSS:** Salida 100% escapada con `e()`.
* **Protección CSRF:** Validada en creación.

### 3.7 `docs/.htaccess` (Protección Web)
* **Regla Apache 2.4:** `Require all denied` y `Options -Indexes` para impedir la exposición de documentación interna vía HTTP.

---

## 4. Matriz de Mitigación de Vectores de Ataque (GOB-05)

| ID | Vector | Severidad | Estado | Mecanismo de Control |
| :- | :--- | :---: | :---: | :--- |
| **SEC-01** | Inyección SQL | 🔴 Crítica | ✅ MITIGADO | PDO con sentencias preparadas nativas en el 100% de consultas. |
| **SEC-02** | XSS | 🔴 Crítica | ✅ MITIGADO | Función `e()` en todas las impresiones dinámicas HTML. |
| **SEC-03** | CSRF | 🟠 Alta | ✅ MITIGADO | Validación criptográfica de tokens con `check_csrf()` / `hash_equals()`. |
| **SEC-04** | Session Hijacking | 🟠 Alta | ✅ MITIGADO | Cookies de sesión configuradas con `HttpOnly` y `SameSite=Lax`. |
| **SEC-05** | Session Fixation | 🟠 Alta | ✅ MITIGADO | Regeneración de identificador con `session_regenerate_id(true)`. |
| **SEC-06** | GPS Spoofing | 🟡 Media | ✅ MITIGADO | Filtro de precisión (>100m) y geocerca Haversine (AC-4.4 / AC-4.5). |
| **SEC-07** | Registro Duplicado | 🟡 Media | ✅ MITIGADO | `UNIQUE KEY (aprendiz_id, sesion_id)` + captura de `SQLSTATE 23000`. |
| **SEC-09** | Acceso a `/docs/` | 🟡 Media | ✅ MITIGADO | Archivo `docs/.htaccess` con `Require all denied`. |

---

## 5. Backlog de Mejoras Futuras

1. **Gestión de Estado en `aprendices.php`:** Implementar acción de borrado lógico (toggle `activo = 0 / 1`) y paginación en el listado.
2. **Rate Limiting en `codigo.php`:** Introducir retardos progresivos para mitigar ataques de fuerza bruta en códigos de 6 dígitos.
3. **Variables de Entorno (`.env`):** Extraer credenciales de base de datos fuera de `config.php`.
