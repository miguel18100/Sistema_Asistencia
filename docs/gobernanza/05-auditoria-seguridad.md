# GOB-05: Matriz de Seguridad y Vectores de Ataque
**Última revisión:** 2026-09-18 | **Estado:** VIGENTE

---

## 1. Matriz de Riesgos

| ID | Vector | Severidad | Estado | Mitigación implementada |
| :- | :--- | :---: | :---: | :--- |
| SEC-01 | Inyección SQL | 🔴 Crítica | ✅ Mitigado | PDO + sentencias preparadas nativas. |
| SEC-02 | XSS | 🔴 Crítica | ✅ Mitigado | Función `e()` en toda salida HTML. |
| SEC-03 | CSRF | 🟠 Alta | ✅ Mitigado | Token CSRF en formularios POST; validado con `check_csrf()` / `hash_equals()`. |
| SEC-04 | Session Hijacking | 🟠 Alta | ✅ Mitigado | Cookies `HttpOnly`, `SameSite=Lax`. |
| SEC-06 | GPS Spoofing | 🟡 Media | ⚠️ Parcial | Validación de precisión GPS y geocerca activable en `config.php`. |

---

## 2. Plan de Mitigación para Riesgos Pendientes

### SEC-09 — Protección del directorio `/docs/`
**Acción requerida:** Crear un archivo `.htaccess` específico dentro de `docs/` con la directiva:
```apache
# docs/.htaccess
Require all denied
Options -Indexes
```
