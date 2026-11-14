<?php

//db-update con carpetas temporales propias (nunca databases/actualizaciones/ ni dumps/): listar, rechazar rutas, simular,
//aplicar con y sin volcado reciente. Crea zz_db_update_probe y la borra; last-restore.json no se toca.
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
        $carpetasPrevias = DbUpdateTask::swapForTesting($actualizaciones, $volcados);
        $database->exec("DROP TABLE IF EXISTS `{$tabla}`");

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

            //─── c5 · con volcado de ahora ──────────────────────────────────────────────────────────────
            $ahora = touch($volcados . '/zz-ahora.sql');
            $aplicada = DbUpdateTask::apply($ruta, time());
            $check($ahora && $aplicada['refused'] === null && $aplicada['applied'] === 2 && $aplicada['failed'] === 0 && $existe(), 'c5 con un volcado de ahora: aplicada (2 aplicadas, 0 fallidas) y la tabla existe', (string) json_encode($aplicada, \JSON_UNESCAPED_UNICODE));

            //─── c6 · repetir ───────────────────────────────────────────────────────────────────────────
            $repetida = DbUpdateTask::apply($ruta, time());
            $estados = array_column($repetida['lines'], 'status');
            $check($repetida['applied'] === 1 && $repetida['failed'] === 1 && $estados === ['OK', 'FALLO'] && $existe(), 'c6 repetir: el CREATE … IF NOT EXISTS pasa, el ALTER falla y la tabla sigue', (string) json_encode($repetida, \JSON_UNESCAPED_UNICODE));
        }

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    } finally {
        if ($carpetasPrevias !== null) {
            DbUpdateTask::swapForTesting($carpetasPrevias[0], $carpetasPrevias[1]);
        }
        try {
            $database->exec("DROP TABLE IF EXISTS `{$tabla}`");
        } catch (\Throwable) {
        }
        foreach (['/actualizaciones/zz-prueba.sql', '/actualizaciones/a/b.sql', '/actualizaciones/a\\b.sql', '/x.sql', '/volcados/zz-viejo.sql', '/volcados/zz-ahora.sql'] as $archivo) {
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
    $check(!is_dir($raiz) && DbUpdateTask::updatesDirectory() !== $actualizaciones, 'c8 las carpetas temporales se borran y las del producto vuelven', $raiz);

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('db-update (P328): listar, rechazar rutas (../, /, \\, absolutas, inexistentes), simular sin tocar la base, aplicar con y sin volcado reciente, repetir (el ALTER falla sin daño) y no tocar last-restore.json. Con carpetas temporales propias.')->setEffects([CliActions::EFFECT_DATABASE])->register();
