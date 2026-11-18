<?php

//db-update con carpetas y registro temporales (nunca databases/actualizaciones/, dumps/ ni pcsphp_db_updates): listar, simular,
//aplicar, parar en el fallo, from=, again=, mark= y el estado. Crea tablas zz_db_update_* y las borra; last-restore.json no se toca.
//@areas nucleo

use PiecesPHP\Core\BaseModel;
use PiecesPHP\Terminal\CliActions;
use Terminal\Tasks\DbRestoreTask;
use Terminal\Tasks\DbUpdateTask;

CliActions::make('unit-tests:core/db-update', function ($args) {

    echoTerminal("\e[33m[TEST:db-update] aplicar las actualizaciones de la base, con simulacro y volcado reciente\e[39m");
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name, string $detail = '') use (&$passed, &$failed): bool {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}" . ($detail !== '' ? " — {$detail}" : ''));
        }
        return $condition;
    };

    $database = (new BaseModel())->getDatabase();
    if ($database === null) {
        echoTerminal("   \e[31mSin conexión: la suite no puede correr.\e[39m");
        return ['success' => false, 'message' => 'sin conexión'];
    }

    $tabla = 'zz_db_update_probe';
    $registro = 'zz_db_updates_probe';
    $tablasDePrueba = [$tabla, 'zz_db_update_probe2', 'zz_db_update_probe3', 'zz_db_update_probe4', 'zz_db_update_probe5', $registro];
    $existeTabla = function (string $nombre) use ($database): bool {
        $consulta = $database->prepare('SELECT COUNT(*) FROM `information_schema`.`tables` WHERE `table_schema` = DATABASE() AND `table_name` = ?');
        //RETORNO-IGNORADO: PDO va en ERRMODE_EXCEPTION: o hace su trabajo o lanza. Lo que cuenta es el COUNT que sigue.
        $consulta->execute([$nombre]);
        return ((int) $consulta->fetchColumn()) > 0;
    };
    $fila = function (string $nombre) use ($database, $registro, $existeTabla): ?array {
        if (!$existeTabla($registro)) {
            return null;
        }
        $consulta = $database->prepare("SELECT `sha1`, `statements`, `appliedAt`, `mode` FROM `{$registro}` WHERE `name` = ?");
        //RETORNO-IGNORADO: PDO va en ERRMODE_EXCEPTION: o hace su trabajo o lanza. Lo que cuenta es el fetch que sigue.
        $consulta->execute([$nombre]);
        $leida = $consulta->fetch(\PDO::FETCH_ASSOC);
        return is_array($leida) ? $leida : null;
    };
    $existe = function () use ($database, $tabla): bool {
        $consulta = $database->prepare('SELECT COUNT(*) FROM `information_schema`.`tables` WHERE `table_schema` = DATABASE() AND `table_name` = ?');
        //RETORNO-IGNORADO: PDO va en ERRMODE_EXCEPTION: o hace su trabajo o lanza. Lo que cuenta es el COUNT que sigue.
        $consulta->execute([$tabla]);
        return ((int) $consulta->fetchColumn()) > 0;
    };
    $huella = function (string $ruta): array {
        clearstatcache(true, $ruta);
        return is_file($ruta) ? [true, sha1_file($ruta), filemtime($ruta)] : [false, null, null];
    };

    $raiz = rtrim(sys_get_temp_dir(), '/') . '/zz-db-update-' . bin2hex(random_bytes(6));
    $actualizaciones = $raiz . '/actualizaciones';
    $volcados = $raiz . '/volcados';
    $rastro = dirname(rtrim(str_replace('\\', '/', basepath('')), '/')) . '/' . DbRestoreTask::TRACE_RELATIVE_PATH;
    $rastroAntes = $huella($rastro);
    $carpetasPrevias = null;

    try {
        //Señuelos a/b, a\b y ../x: existen de verdad, así que solo la comprobación del nombre los rechaza.
        $preparada = mkdir($actualizaciones . '/a', 0775, true)
            && mkdir($volcados, 0775, true)
            && file_put_contents($actualizaciones . '/zz-prueba.sql', "-- Prueba de db-update: una tabla zz y su índice único.\n--\n-- Segunda línea que no es el resumen.\n\nCREATE TABLE IF NOT EXISTS `{$tabla}` (`id` int NOT NULL AUTO_INCREMENT, `code` varchar(20) NOT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB;\n\nALTER TABLE `{$tabla}` ADD UNIQUE KEY `zzCode` (`code`);\n") !== false
            && file_put_contents($actualizaciones . '/a/b.sql', "-- señuelo\nSELECT 1;\n") !== false
            && file_put_contents($actualizaciones . '/a\\b.sql', "-- señuelo\nSELECT 1;\n") !== false
            && file_put_contents($raiz . '/x.sql', "-- señuelo\nSELECT 1;\n") !== false;
        if (!$check($preparada, 'c0 las carpetas temporales y sus archivos se crean', $raiz)) {
            throw new \RuntimeException('sin carpetas temporales no se prueba nada');
        }
        $carpetasPrevias = DbUpdateTask::swapForTesting($actualizaciones, $volcados, $registro);
        foreach ($tablasDePrueba as $nombre) {
            $database->exec("DROP TABLE IF EXISTS `{$nombre}`");
        }

        //─── c1 · listar ────────────────────────────────────────────────────────────────────────────────
        $lista = DbUpdateTask::listUpdates();
        $check(in_array(['name' => 'zz-prueba', 'summary' => 'Prueba de db-update: una tabla zz y su índice único.'], $lista, true) && array_column($lista, 'name') === ['a\\b', 'zz-prueba'], 'c1 listar ve zz-prueba con su primera línea de comentario', (string) json_encode($lista, \JSON_UNESCAPED_UNICODE));

        //─── c2 · rutas rechazadas ──────────────────────────────────────────────────────────────────────
        $rechazos = ['../x', '../x.sql', 'a/b', 'a\\b', $raiz . '/x.sql', 'no-existe'];
        $resueltos = array_combine($rechazos, array_map(fn(string $nombre): ?string => DbUpdateTask::resolveUpdate($nombre), $rechazos));
        $check(array_filter($resueltos) === [], 'c2 «../x», «a/b», «a\\b», una ruta absoluta y un nombre que no existe: rechazados', (string) json_encode(array_filter($resueltos)));
        $ruta = DbUpdateTask::resolveUpdate('zz-prueba');
        $check($ruta === $actualizaciones . '/zz-prueba.sql' && DbUpdateTask::resolveUpdate('zz-prueba.sql') === $ruta, 'c2b el nombre, con o sin «.sql», sí resuelve', var_export($ruta, true));

        if ($ruta !== null) {
            //─── c3 · simular ───────────────────────────────────────────────────────────────────────────
            $sentencias = DbUpdateTask::simulate($ruta);
            $check(count($sentencias) === 2 && str_starts_with($sentencias[0], 'CREATE TABLE') && str_starts_with($sentencias[1], 'ALTER TABLE') && !$existe(), 'c3 simular devuelve las 2 sentencias y la tabla no existe', (string) json_encode($sentencias));

            //─── c4 · sin volcado reciente ──────────────────────────────────────────────────────────────
            $viejo = touch($volcados . '/zz-viejo.sql', time() - 7200);
            $sinVolcado = DbUpdateTask::apply($ruta, time());
            $check($viejo && $sinVolcado['refused'] !== null && $sinVolcado['applied'] === 0 && !$existe(), 'c4 sin un volcado de la última hora: rechazado y la tabla no existe', (string) json_encode($sinVolcado, \JSON_UNESCAPED_UNICODE));

            //─── c5 · con volcado de ahora: aplica y registra ──────────────────────────────────────────────
            $ahora = touch($volcados . '/zz-ahora.sql');
            $aplicada = DbUpdateTask::apply($ruta, time());
            $registrada = $fila('zz-prueba');
            $check($ahora && $aplicada['refused'] === null && $aplicada['applied'] === 2 && $aplicada['failed'] === 0 && $existe() && $aplicada['registered'] && ($registrada['mode'] ?? null) === DbUpdateTask::MODE_APPLIED && ($registrada['sha1'] ?? null) === sha1_file($ruta) && (int) ($registrada['statements'] ?? 0) === 2, 'c5 con un volcado de ahora: aplicada (2 aplicadas, 0 fallidas), la tabla existe y queda registrada «aplicada» con su sha1', (string) json_encode([$aplicada, $registrada], \JSON_UNESCAPED_UNICODE));

            //─── u3 · ya registrada: se niega; again=yes la vuelve a aplicar ──────────────────────────────
            $repetida = DbUpdateTask::apply($ruta, time());
            $check($repetida['refused'] !== null && str_contains($repetida['refused'], 'again=yes') && $repetida['applied'] === 0, 'u3a una ya registrada se niega y dice again=yes', (string) $repetida['refused']);
            //RETORNO-IGNORADO: un archivo o un paso de la prueba; lo que cuenta lo comprueba la aserción que sigue.
            file_put_contents($actualizaciones . '/zz-idempotente.sql', "-- Prueba: una tabla que se puede crear dos veces.\nCREATE TABLE IF NOT EXISTS `zz_db_update_probe2` (`id` int NOT NULL) ENGINE=InnoDB;\n");
            $idempotente = (string) DbUpdateTask::resolveUpdate('zz-idempotente');
            //RETORNO-IGNORADO: un archivo o un paso de la prueba; lo que cuenta lo comprueba la aserción que sigue.
            DbUpdateTask::apply($idempotente, time());
            $antes = $fila('zz-idempotente');
            $otraVez = DbUpdateTask::apply($idempotente, time() + 120, true);
            $despues = $fila('zz-idempotente');
            $check($otraVez['refused'] === null && $otraVez['registered'] && $antes !== null && $despues !== null && $despues['appliedAt'] !== $antes['appliedAt'], 'u3b again=yes la vuelve a aplicar y el registro se actualiza', (string) json_encode([$antes, $despues]));

            //─── u1 · un fallo en medio: para, no ejecuta lo siguiente y no registra ─────────────────────
            //RETORNO-IGNORADO: un archivo o un paso de la prueba; lo que cuenta lo comprueba la aserción que sigue.
            file_put_contents($actualizaciones . '/zz-falla.sql', "-- Prueba: la segunda sentencia falla.\nCREATE TABLE IF NOT EXISTS `zz_db_update_probe3` (`id` int NOT NULL) ENGINE=InnoDB;\n\nALTER TABLE `zz_db_update_no_existe` ADD COLUMN `x` int;\n\nCREATE TABLE IF NOT EXISTS `zz_db_update_probe4` (`id` int NOT NULL) ENGINE=InnoDB;\n");
            $falla = (string) DbUpdateTask::resolveUpdate('zz-falla');
            $fallida = DbUpdateTask::apply($falla, time());
            $saleConUno = $fallida['failed'] > 0 || !$fallida['registered'];
            $check($fallida['stoppedAt'] === 2 && $fallida['applied'] === 1 && $fallida['failed'] === 1 && $existeTabla('zz_db_update_probe3') && !$existeTabla('zz_db_update_probe4') && !$fallida['registered'] && $fila('zz-falla') === null && $saleConUno, 'u1 DISCRIMINANTE: para en la sentencia 2 que falla, no ejecuta la 3, sale con 1 y no registra', (string) json_encode($fallida, \JSON_UNESCAPED_UNICODE));

            //─── u2 · from=3: sigue desde ahí y registra el archivo entero ───────────────────────────────
            $desde = DbUpdateTask::apply($falla, time(), false, 3);
            $check($desde['skipped'] === 2 && $desde['applied'] === 1 && $desde['failed'] === 0 && $existeTabla('zz_db_update_probe4') && $desde['registered'] && (int) ($fila('zz-falla')['statements'] ?? 0) === 3, 'u2 from=3 salta las dos primeras, aplica la tercera y registra el archivo (3 sentencias)', (string) json_encode($desde, \JSON_UNESCAPED_UNICODE));

            //─── u4 · mark=yes: registra sin ejecutar ─────────────────────────────────────────────────────
            //RETORNO-IGNORADO: un archivo o un paso de la prueba; lo que cuenta lo comprueba la aserción que sigue.
            file_put_contents($actualizaciones . '/zz-marca.sql', "-- Prueba: aplicada a mano antes del registro.\nCREATE TABLE `zz_db_update_probe5` (`id` int NOT NULL) ENGINE=InnoDB;\n");
            $marca = (string) DbUpdateTask::resolveUpdate('zz-marca');
            $marcada = DbUpdateTask::mark($marca, time());
            $check($marcada['refused'] === null && ($fila('zz-marca')['mode'] ?? null) === DbUpdateTask::MODE_MARKED && !$existeTabla('zz_db_update_probe5'), 'u4 DISCRIMINANTE: mark=yes la registra «marcada» y su sentencia NO se ejecuta', (string) json_encode([$marcada, $fila('zz-marca')]));

            //─── u5 · el listado: aplicada, marcada, pendiente y el sha1 cambiado ─────────────────────────
            //RETORNO-IGNORADO: un archivo o un paso de la prueba; lo que cuenta lo comprueba la aserción que sigue.
            file_put_contents($actualizaciones . '/zz-pendiente.sql', "-- Prueba: nunca aplicada.\nSELECT 1;\n");
            //RETORNO-IGNORADO: un archivo o un paso de la prueba; lo que cuenta lo comprueba la aserción que sigue.
            file_put_contents($marca, "\n-- cambiado después de marcarla\n", \FILE_APPEND);
            $estado = array_column(DbUpdateTask::listStatus()['updates'], null, 'name');
            $check(($estado['zz-prueba']['status'] ?? null) === DbUpdateTask::MODE_APPLIED && ($estado['zz-marca']['status'] ?? null) === DbUpdateTask::MODE_MARKED && ($estado['zz-pendiente']['status'] ?? null) === DbUpdateTask::MODE_PENDING && ($estado['zz-marca']['changed'] ?? false) === true && ($estado['zz-prueba']['changed'] ?? true) === false && ($estado['zz-prueba']['appliedAt'] ?? null) !== null, 'u5 el listado: aplicada, marcada, pendiente, y avisa del sha1 cambiado', (string) json_encode(array_map(fn(array $u): array => [$u['status'], $u['changed']], $estado)));
            DbUpdateTask::swapForTesting($actualizaciones, $volcados, 'zz_db_updates_no_existe');
            $sinTabla = DbUpdateTask::listStatus();
            DbUpdateTask::swapForTesting($actualizaciones, $volcados, $registro);
            $check($sinTabla['registry'] === false && array_unique(array_column($sinTabla['updates'], 'status')) === [DbUpdateTask::MODE_PENDING] && !$existeTabla('zz_db_updates_no_existe'), 'u5b sin la tabla: todas pendientes, lo dice, y listar no la crea');
        }

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    } finally {
        if ($carpetasPrevias !== null) {
            DbUpdateTask::swapForTesting($carpetasPrevias[0], $carpetasPrevias[1]);
        }
        try {
            foreach ($tablasDePrueba as $nombre) {
                $database->exec("DROP TABLE IF EXISTS `{$nombre}`");
            }
        } catch (\Throwable) {
        }
        foreach (['/actualizaciones/zz-idempotente.sql', '/actualizaciones/zz-falla.sql', '/actualizaciones/zz-marca.sql', '/actualizaciones/zz-pendiente.sql', '/actualizaciones/zz-prueba.sql', '/actualizaciones/a/b.sql', '/actualizaciones/a\\b.sql', '/x.sql', '/volcados/zz-viejo.sql', '/volcados/zz-ahora.sql'] as $archivo) {
            //RETORNO-IGNORADO: limpieza; lo que no se borre lo caza c8, que comprueba que la carpeta temporal ya no existe.
            @unlink($raiz . $archivo);
        }
        foreach (['/actualizaciones/a', '/actualizaciones', '/volcados', ''] as $carpeta) {
            //RETORNO-IGNORADO: limpieza; lo que no se borre lo caza c8, que comprueba que la carpeta temporal ya no existe.
            @rmdir($raiz . $carpeta);
        }
    }

    //─── c7 · el rastro de db-restore ───────────────────────────────────────────────────────────────────
    $rastroDespues = $huella($rastro);
    $check($rastroAntes === $rastroDespues, 'c7 files/dev/last-restore.json igual antes y después (existencia, sha1 y mtime)', (string) json_encode([$rastroAntes, $rastroDespues]));
    $check(!is_dir($raiz) && DbUpdateTask::updatesDirectory() !== $actualizaciones && DbUpdateTask::registryTable() === DbUpdateTask::REGISTRY_TABLE && array_filter($tablasDePrueba, $existeTabla) === [], 'c8 las carpetas temporales y las tablas zz se borran, y las del producto vuelven', $raiz);

    //─── u6 · la definición del registro, la misma en los tres sitios ──────────────────────────────────
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    $cuerpo = function (string $texto, string $cabecera): ?string {
        $inicio = strpos($texto, $cabecera);
        $fin = $inicio === false ? false : strpos($texto, ';', $inicio);
        return $inicio === false || $fin === false ? null : str_replace("\r\n", "\n", substr($texto, $inicio + strlen($cabecera), $fin - $inicio - strlen($cabecera)));
    };
    $deLaConstante = $cuerpo(DbUpdateTask::REGISTRY_DEFINITION, 'CREATE TABLE IF NOT EXISTS `pcsphp_db_updates` (');
    $delEsquema = $cuerpo((string) file_get_contents("{$proyecto}/databases/piecesphp_structure.sql"), 'CREATE TABLE `pcsphp_db_updates` (');
    $deLaActualizacion = $cuerpo((string) file_get_contents("{$proyecto}/databases/actualizaciones/2026-11-17-registro-de-actualizaciones.sql"), 'CREATE TABLE IF NOT EXISTS `pcsphp_db_updates` (');
    $check($deLaConstante !== null && $deLaConstante === $delEsquema && $deLaConstante === $deLaActualizacion, 'u6 la tabla del registro es la misma en la constante, en piecesphp_structure.sql y en la actualización', (string) json_encode([$deLaConstante, $delEsquema, $deLaActualizacion]));

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('db-update (P328, H298): listar, rechazar rutas (../, /, \\, absolutas, inexistentes), simular sin tocar la base, aplicar con y sin volcado reciente y registrar, negarse con una ya registrada (again=yes la repite), parar en el primer fallo sin registrar, seguir con from=, marcar sin ejecutar, el estado en el listado y la misma definición del registro en sus tres sitios; no toca last-restore.json. Con carpetas y registro temporales.')->setEffects([CliActions::EFFECT_DATABASE])->register();
