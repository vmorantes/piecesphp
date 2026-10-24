-- Índice por fecha en los dos registros que crecen sin parar (C4, ronda 2).
--
-- Para INSTALACIONES QUE YA EXISTEN. Una instalación nueva no necesita este archivo: los índices ya vienen en
-- `piecesphp_structure.sql`.
--
-- Es UN paso y no hay tarea que ejecutar después. SE PUEDE HACER CON LA APLICACIÓN EN MARCHA: añadir un índice no
-- cambia ningún dato.
--
-- POR QUÉ: el registro de acciones filtra por rango de fechas y los informes de acceso ordenan por fecha; sin índice,
-- cada consulta recorre la tabla entera. Las dos tablas tienen además retención (200000 filas por omisión, ver
-- `LogsMapper::MAX_ROWS` y `LoginAttemptsModel::MAX_ROWS`), así que el índice no crece sin fin.

ALTER TABLE `actions_log`
  ADD INDEX `createdAt` (`createdAt`);

ALTER TABLE `login_attempts`
  ADD INDEX `date` (`date`);
