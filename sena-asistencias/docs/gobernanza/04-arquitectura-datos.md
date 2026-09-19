# GOB-04: Arquitectura de Datos y MySQL
**Última revisión:** 2026-09-18 | **Estado:** VIGENTE

---

## 1. Esquema Actual del Proyecto (Referencia)

```
admins ──────────────────────────────────────────────────
  id | nombre | usuario (UNIQUE) | password_hash | creado_en

fichas ───────────────────────────────────────────────────
  id | numero (UNIQUE) | programa | jornada | activa

aprendices ───────────────────────────────────────────────
  id | documento (UNIQUE) | nombre | email
  ficha_id → fichas.id (SET NULL on delete)
  qr_token (UNIQUE) | activo | creado_en

sesiones_asistencia ──────────────────────────────────────
  id | token (UNIQUE) | codigo (UNIQUE)
  ficha_id → fichas.id (SET NULL on delete)
  creada_por | inicia_en | vence_en | activa | tolerancia_minutos

asistencias ──────────────────────────────────────────────
  id | aprendiz_id → aprendices.id
  sesion_id → sesiones_asistencia.id (NULL = escáner global legacy)
  fecha_hora | ip | user_agent | estado_red | detalle_red
  latitud | longitud | precision_gps
  tarde_minutos | verificado_gps | consentimiento_datos
  UNIQUE KEY (aprendiz_id, sesion_id)
```

---

## 2. Integridad Relacional

### 2.1 Motor y Codificación (Obligatorio)
- Todas las tablas **deben** usar motor `InnoDB` para soporte de transacciones y llaves foráneas.
- Charset obligatorio: `utf8mb4` con collation `utf8mb4_unicode_ci` para soporte completo de caracteres Unicode.

```sql
-- Plantilla para nuevas tablas
CREATE TABLE nueva_entidad (
    id INT AUTO_INCREMENT PRIMARY KEY,
    -- columnas...
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2.2 Política de Borrado
| Entidad | Política | Razón |
| :--- | :--- | :--- |
| `aprendices` | Borrado **lógico** (`activo = 0`) | Preservar historial de asistencias |
| `fichas` | Borrado **lógico** (`activa = 0`) | Preservar relaciones con aprendices |
| `sesiones_asistencia` | Borrado **lógico** (`activa = 0`) | Preservar registros de asistencia asociados |
| `admins` | Borrado **físico** permitido | Sin dependencias críticas |

**Regla:** Nunca usar `ON DELETE CASCADE` en tablas con datos académicos históricos.

---

## 3. Índices y Rendimiento

### 3.1 Índices Actuales
| Tabla | Columna(s) | Tipo | Propósito |
| :--- | :--- | :--- | :--- |
| `admins` | `usuario` | UNIQUE | Búsqueda de login |
| `fichas` | `numero` | UNIQUE | Identificación de ficha |
| `aprendices` | `documento` | UNIQUE | Búsqueda por cédula |
| `aprendices` | `qr_token` | UNIQUE | Validación de QR |
| `sesiones_asistencia` | `token` | UNIQUE + INDEX | Lookup de sesión |
| `sesiones_asistencia` | `codigo` | UNIQUE + INDEX | Ingreso manual de código |
| `asistencias` | `(aprendiz_id, sesion_id)` | UNIQUE KEY | Prevención de duplicados |
| `asistencias` | `fecha_hora` | INDEX | Filtros por fecha |

### 3.2 Regla para Nuevas Columnas
Toda columna que sea usada en cláusulas `WHERE`, `JOIN ON` u `ORDER BY` frecuentes debe tener un `INDEX` definido en el `CREATE TABLE` o mediante `ALTER TABLE ADD INDEX`.

---

## 4. Auditoría y Trazabilidad

- Las tablas transaccionales deben incluir `fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP`.
- Los registros de `asistencias` deben capturar: IP, User-Agent, estado de red y coordenadas GPS cuando aplique.
- Toda migración de esquema debe registrarse en un archivo `update-vX.sql` numerado secuencialmente.

### 4.1 Agregar Nuevas Columnas (Protocolo)
```sql
-- 1. Crear el archivo update-v5.sql
USE sena_asistencias;
ALTER TABLE asistencias
    ADD COLUMN nueva_columna VARCHAR(100) NULL AFTER columna_anterior;

-- 2. Actualizar database.sql para reflejar el esquema completo actualizado.
```
