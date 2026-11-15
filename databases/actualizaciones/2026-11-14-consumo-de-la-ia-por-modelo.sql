-- El consumo de la IA con tokens de entrada, de salida y el modelo (ADR 0077).
--
-- Para INSTALACIONES QUE YA EXISTEN, con `2026-11-13-consumo-de-la-ia` aplicada: una instalación nueva no necesita este
-- archivo, las columnas y el índice ya vienen en `piecesphp_structure.sql`.
--
-- Es UN paso (una sola sentencia: o se aplica entera o nada) y no hay tarea que ejecutar después. Las filas que ya están
-- conservan sus tokens y sus peticiones, con 0 de entrada y de salida y el modelo vacío. El índice único pasa a contar el
-- modelo: (capability, provider, model, periodStart).
--
-- No se puede repetir: si ya está aplicada, falla (MariaDB dice «Can't DROP INDEX `capabilityProviderPeriod`») y no
-- pasa nada. Sin ella, el framework sigue funcionando: `AIGateway::recordUsage()` anota el fallo en el registro de errores
-- y sigue sumando el acumulado.

ALTER TABLE `pcsphp_ai_usage`
  ADD COLUMN `model` varchar(128) COLLATE utf8mb4_bin NOT NULL DEFAULT '' AFTER `provider`,
  ADD COLUMN `inputTokens` bigint NOT NULL DEFAULT 0 AFTER `tokens`,
  ADD COLUMN `outputTokens` bigint NOT NULL DEFAULT 0 AFTER `inputTokens`,
  DROP INDEX `capabilityProviderPeriod`,
  ADD UNIQUE KEY `capabilityProviderModelPeriod` (`capability`, `provider`, `model`, `periodStart`);
