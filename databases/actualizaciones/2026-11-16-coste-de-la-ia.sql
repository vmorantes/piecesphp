-- El coste que da el proveedor de IA en su respuesta, sumado en cada fila de consumo (ADR 0077 §8).

ALTER TABLE `pcsphp_ai_usage`
  ADD COLUMN `reportedCostUsd` double NOT NULL DEFAULT 0 AFTER `outputTokens`;
