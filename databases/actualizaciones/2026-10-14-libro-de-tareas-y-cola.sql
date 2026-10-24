-- DOS PASOS (G10): el libro de las tareas programadas y la cola de trabajos con tipos e índices.
--
-- 1. El libro de las tareas programadas: una línea por cada ejecución real de una tarea, con quién la disparó
-- (`http` o `cli`), su franja si la tiene, su intento, cuánto tardó y su mensaje. Solo se anota lo que ejecutó la tarea
-- (executed, failed); «no toca», «en curso» y «ya hecha» no, para no llenar la tabla cada minuto. Se queda con las
-- 20.000 líneas más recientes.
--
-- Para INSTALACIONES QUE YA EXISTEN. Una instalación nueva no necesita este archivo: la tabla ya viene en
-- `piecesphp_structure.sql`. Es del núcleo: no tiene generador.
--
-- Se puede aplicar con la aplicación en marcha. Sin la tabla, las tareas corren igual y queda un aviso suave por proceso
-- en el registro de errores, y el aviso del sistema `task-runs-missing` lo dice a root.

CREATE TABLE IF NOT EXISTS `pcsphp_task_runs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `taskName` varchar(191) NOT NULL,
  `slot` datetime DEFAULT NULL,
  `triggeredBy` varchar(16) NOT NULL,
  `status` varchar(24) NOT NULL,
  `attempt` int(11) NOT NULL DEFAULT 1,
  `startedAt` datetime NOT NULL,
  `finishedAt` datetime DEFAULT NULL,
  `durationMs` int(11) DEFAULT NULL,
  `message` varchar(500) DEFAULT NULL,
  `host` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `taskNameStartedAt` (`taskName`,`startedAt`),
  KEY `startedAt` (`startedAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- 2. La cola de trabajos: `name` y `status` pasan de TEXT a VARCHAR para poder indexarlas, y ganan los dos índices
-- con los que el trabajador busca lo pendiente y lo abandonado. Antes de aplicarlo, compruebe que nada pasa del
-- largo nuevo (si pasa, NO lo aplique: se recortaría):
--
--   SELECT MAX(CHAR_LENGTH(`name`)), MAX(CHAR_LENGTH(`status`)) FROM `pcsphp_jobs_queue`;   -- hasta 191 y 16
--
-- Se aplica UNA vez (los índices no se pueden añadir dos veces). Se puede con la aplicación en marcha: la tabla se
-- reconstruye y queda bloqueada lo que tarde, poco con una cola normal; un trabajador que coincida reintenta en el
-- siguiente minuto.

ALTER TABLE `pcsphp_jobs_queue`
  MODIFY `name` varchar(191) NOT NULL,
  MODIFY `status` varchar(16) NOT NULL,
  ADD KEY `statusScheduledAtCreatedAt` (`status`,`scheduledAt`,`createdAt`),
  ADD KEY `statusStartedAt` (`status`,`startedAt`);
