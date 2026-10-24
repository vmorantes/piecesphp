-- Índice en la referencia de cada aprobación (P188 a, paso 1).
--
-- Para INSTALACIONES QUE YA EXISTEN. Una instalación nueva no necesita este archivo: los tipos y el índice ya vienen en
-- `piecesphp_structure.sql`.
--
-- Es UN paso y no hay tarea que ejecutar después. SE PUEDE HACER CON LA APLICACIÓN EN MARCHA: cambiar `text` por
-- `varchar(191)` no cambia ningún dato si ninguna referencia pasa de 191 caracteres. Compruébalo antes; si alguna pasa,
-- el ALTER la truncaría o fallaría según el modo SQL del servidor, y no se aplica:
--
--   SELECT MAX(CHAR_LENGTH(referenceTable)), MAX(CHAR_LENGTH(referenceValue)) FROM system_approvals_elements;
--
-- POR QUÉ: cada arranque busca, por cada tipo de contenido con aprobaciones, lo que aún no tiene su fila, y cada listado
-- de usuarios, organizaciones o publicaciones lee el estado de aprobación con una subconsulta por fila. Las dos buscan
-- por (`referenceTable`, `referenceValue`). Sin índice, cada búsqueda recorre la tabla entera: con 10.000 perfiles,
-- cada página del panel tardaba de 12 a 25 s. Un `text` no admite un índice sin longitud de prefijo; 191 es lo que cabe
-- en utf8mb4 dentro del límite de 767 bytes de las instalaciones viejas.

ALTER TABLE `system_approvals_elements`
  MODIFY `referenceValue` varchar(191) NOT NULL,
  MODIFY `referenceTable` varchar(191) NOT NULL,
  ADD KEY `reference` (`referenceTable`, `referenceValue`);
