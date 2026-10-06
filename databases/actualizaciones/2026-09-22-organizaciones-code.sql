-- Código público de las organizaciones (P63 · lote 11d).
--
-- Para INSTALACIONES QUE YA EXISTEN. Una instalación nueva no necesita este archivo: la columna y su índice
-- ya vienen en `piecesphp_structure.sql`, y la organización global trae su código en `piecesphp_data.sql`.
--
-- Son TRES pasos y el orden importa: entre el primero y el tercero hay que ejecutar la tarea que reparte los
-- códigos. No se pueden juntar, porque la columna nace vacía y `NOT NULL` la rechazaría.
--
-- SE PUEDE HACER CON LA APLICACIÓN EN MARCHA. Entre el paso 1 y el 2 las organizaciones que ya existían tienen
-- el código vacío: se cargan y se ven con normalidad, y lo único que les falta es el código, hasta que la tarea
-- del paso 2 pase. Las que se creen mientras tanto nacen ya con el suyo.

-- PASO 1. La columna y su índice único.
-- Nace admitiendo NULL A PROPÓSITO: las filas que ya están no tienen código todavía, y un índice único de MySQL
-- admite tantos NULL como haga falta. Así la unicidad está puesta ANTES de repartir ningún código.
ALTER TABLE `organizations_elements`
  ADD COLUMN `code` varchar(10) DEFAULT NULL AFTER `preferSlug`,
  ADD UNIQUE KEY `code` (`code`);

-- PASO 2. Repartir los códigos. NO es SQL: se ejecuta desde la terminal del proyecto.
--
--     bin/cli organizations-assign-codes
--
-- Es idempotente: solo toca las filas sin código, y dice cuántas tocó y cuántas ya lo tenían. La organización
-- global (id -10) recibe el código reservado ORG0000000.

-- PASO 3. Cerrar la columna, cuando la tarea del paso 2 diga que no queda ninguna sin código.
-- Si esto falla con «Invalid use of NULL value», es que quedan filas sin repartir: vuelve al paso 2.
ALTER TABLE `organizations_elements`
  MODIFY `code` varchar(10) NOT NULL;
