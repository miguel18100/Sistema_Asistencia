-- Ejecuta este archivo una sola vez en phpMyAdmin si ya habías importado database.sql.
USE sena_asistencias;
UPDATE admins SET password_hash = 'TEMP:control1234' WHERE usuario = 'admin';
