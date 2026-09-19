# GOB-02: Flujo de Trabajo y Control de Versiones
**Última revisión:** 2026-09-18 | **Estado:** VIGENTE

---

## 1. Estrategia de Ramas (Trunk-Based Development modificado)

`main` ──────────────────────────────────────────── (producción estable)
  │
  ├── `feature/exportar-excel` ──── merge → `main`
  ├── `fix/duplicado-scanner`  ──── merge → `main`
  └── `sec/csrf-registro`       ──── merge → `main`

| Rama | Propósito | Política de merge |
| :--- | :--- | :--- |
| `main` | Código estable, probado en XAMPP | En desarrollo local individual: merge vía `git merge --no-ff`. En equipo: vía PR revisado. |
| `feature/[nombre]` | Nuevas funcionalidades | Requiere DoD completo antes del merge |
| `fix/[nombre]` | Corrección de bugs o vulnerabilidades | Requiere prueba del caso que causó el bug |
| `sec/[nombre]` | Parches de seguridad críticos | Prioridad máxima. Merge expedito con revisión. |

---

## 2. Convención de Commits (Semantic Commits)

### Formato
<tipo>(<alcance opcional>): <descripción en imperativo>

### Tipos permitidos
| Tipo | Cuándo usarlo |
| :--- | :--- |
| `feat` | Nueva funcionalidad visible para el usuario |
| `fix` | Corrección de un error funcional |
| `sec` | Cambio cuyo único propósito es mejorar seguridad |
| `refactor` | Mejora de código sin cambio de comportamiento externo |
| `docs` | Cambios en gobernanza o comentarios de código |

---

## 3. Definition of Done (DoD) — Lista de verificación obligatoria

Antes de hacer merge a `main`, el desarrollador debe confirmar:

### Calidad de Código
- [ ] El código no arroja Warnings, Notices ni Fatal Errors en PHP 8.x.
- [ ] Ninguna consulta SQL concatena variables directamente.
- [ ] Todo output HTML usa `e()` para escapar valores.

### Funcional
- [ ] El flujo completo fue probado manualmente.
- [ ] El registro manual por documento también fue verificado.
- [ ] Un intento de registro duplicado devuelve un JSON de error controlado.

### Seguridad
- [ ] No se introducen nuevos parámetros `$_GET` / `$_POST` sin validación.
- [ ] Los nuevos endpoints `POST` incluyen verificación CSRF (`check_csrf()`).
- [ ] Las nuevas rutas protegidas tienen `require_login()` al inicio.
