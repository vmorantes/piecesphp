-- Retira la tabla `interest_research_area` (B10 de la v9.0.0).
--
-- La v8.0.0 retiró el módulo de áreas de interés de investigación y anunció que la tabla se iba, pero
-- `databases/piecesphp_structure.sql` la siguió creando: ningún código del framework la usa desde entonces.
--
-- Para TODA instalación que la tenga. Antes, un respaldo: si un módulo propio la usaba, sus filas se pierden.
-- Es UN paso; no hay tarea que ejecutar después. En una instalación que no la tenga no hace nada.

DROP TABLE IF EXISTS `interest_research_area`;
