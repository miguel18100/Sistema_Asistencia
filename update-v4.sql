-- Importa este archivo UNA vez en phpMyAdmin si ya tenías el sistema instalado.
USE sena_asistencias;
ALTER TABLE asistencias ADD COLUMN sesion_id INT NULL AFTER aprendiz_id, ADD UNIQUE KEY un_registro_sesion(aprendiz_id,sesion_id);
CREATE TABLE IF NOT EXISTS sesiones_asistencia (id INT AUTO_INCREMENT PRIMARY KEY, token VARCHAR(64) NOT NULL UNIQUE, codigo VARCHAR(8) NOT NULL UNIQUE, ficha_id INT NULL, creada_por VARCHAR(100) NOT NULL, inicia_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, vence_en DATETIME NOT NULL, activa TINYINT(1) DEFAULT 1, FOREIGN KEY(ficha_id) REFERENCES fichas(id) ON DELETE SET NULL, INDEX(token), INDEX(codigo));
