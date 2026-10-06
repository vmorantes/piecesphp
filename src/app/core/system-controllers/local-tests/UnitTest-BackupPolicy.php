<?php

//La política de respaldos: normalización, intervalo, conservación, ajenos y el fallo que NO rota.
//Corre sobre carpetas temporales propias; de `src/dumps/` solo borra el archivo que ella escribe.

use PiecesPHP\Core\Backups\BackupPolicy;
use PiecesPHP\Core\Backups\BackupRotation;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Terminal\CliActions;
use Terminal\Tasks\DbBackupTask;

CliActions::make('unit-tests:core/backup-policy', function ($args) {

    echoTerminal("\e[33m[TEST:BackupPolicy] La política de respaldos: normalización, intervalo, conservación por niveles y el fallo que no rota\e[39m");
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
    $balance = function () use (&$passed, &$failed): array {
        $total = $passed + $failed;
        echoTerminal(' ');
        echoTerminal($failed === 0
            ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
            : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");
        return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];
    };

    //Una carpeta temporal propia por corrida: nunca `dumps/`.
    $temporaryRoot = rtrim(sys_get_temp_dir(), '/') . '/zz-backup-rotation-' . getmypid() . '-' . bin2hex(random_bytes(4));
    $temporaryDirectories = [];
    $makeDirectory = function (string $suffix = '') use ($temporaryRoot, &$temporaryDirectories): string {
        $path = $temporaryRoot . ($suffix !== '' ? "-{$suffix}" : '');
        if (!is_dir($path)) {
            //RETORNO-IGNORADO: si la carpeta temporal no se crea, las comprobaciones que la usan fallan solas.
            mkdir($path, 0775, true);
        }
        $temporaryDirectories[$path] = true;
        return $path;
    };
    $touchAll = function (string $directory, array $names): void {
        foreach ($names as $name) {
            //RETORNO-IGNORADO: un archivo de prueba que no se escriba lo caza el conteo de la comprobación.
            file_put_contents("{$directory}/{$name}", '');
        }
    };

    //──── a) Normalización ──────────────────────────────────────────────────────────────────────
    echoTerminal("\e[36m a) La política se rechaza entera si cualquier campo no tiene su forma\e[39m");

    $defaults = BackupPolicy::defaults();
    $check(BackupPolicy::normalize($defaults) !== null, 'a1 los valores por omisión pasan la normalización');
    $check(BackupPolicy::normalize($defaults) == $defaults, 'a2 normalizar los valores por omisión los devuelve igual');
    $check(BackupPolicy::normalize(json_encode($defaults)) == $defaults, 'a3 la política también se lee desde su JSON');
    $check(BackupPolicy::normalize((object) $defaults) == $defaults, 'a4 y desde un objeto, como la lee la configuración');

    $outOfRange = [
        'interval_minutes' => [BackupPolicy::MIN_INTERVAL_MINUTES - 1, BackupPolicy::MAX_INTERVAL_MINUTES + 1],
        'keep_recent' => [BackupPolicy::MIN_KEEP_RECENT - 1, BackupPolicy::MAX_KEEP_RECENT + 1],
        'keep_daily' => [BackupPolicy::MIN_KEEP_PERIOD - 1, BackupPolicy::MAX_KEEP_PERIOD + 1],
        'keep_weekly' => [BackupPolicy::MIN_KEEP_PERIOD - 1, BackupPolicy::MAX_KEEP_PERIOD + 1],
        'keep_monthly' => [BackupPolicy::MIN_KEEP_PERIOD - 1, BackupPolicy::MAX_KEEP_PERIOD + 1],
    ];
    foreach ($outOfRange as $field => $values) {
        foreach ($values as $value) {
            $broken = $defaults;
            $broken[$field] = $value;
            $check(BackupPolicy::normalize($broken) === null, "a5 {$field} = {$value} queda fuera de su límite y se rechaza");
        }
    }

    $check(BackupPolicy::normalize(array_merge($defaults, ['keep_recent' => 0])) === null, 'a6 keep_recent = 0 se rechaza: la conservación no puede dejar cero respaldos');
    $check(BackupPolicy::normalize(array_merge($defaults, ['enabled' => 'yes'])) === null, 'a7 enabled con un texto en vez de un booleano se rechaza');
    $check(BackupPolicy::normalize(array_merge($defaults, ['rotate' => 1])) === null, 'a8 rotate con un entero se rechaza');
    $check(BackupPolicy::normalize(array_merge($defaults, ['keep_daily' => 7.5])) === null, 'a9 un decimal en keep_daily se rechaza');
    $check(BackupPolicy::normalize(array_merge($defaults, ['keep_daily' => true])) === null, 'a10 un booleano en keep_daily se rechaza');
    $check(BackupPolicy::normalize(array_merge($defaults, ['data_excluded_tables' => 'users'])) === null, 'a11 data_excluded_tables tiene que ser una lista');
    $check(BackupPolicy::normalize(array_merge($defaults, ['data_excluded_tables' => [123]])) === null, 'a12 una tabla que no es texto se rechaza');
    $check(BackupPolicy::normalize('{roto') === null, 'a13 un JSON roto se rechaza');
    $check(BackupPolicy::normalize(null) === null, 'a14 null se rechaza');
    unset($defaults['enabled']);
    $check(BackupPolicy::normalize($defaults) === null, 'a15 una política sin el campo enabled se rechaza');

    $defaults = BackupPolicy::defaults();
    $existing = ['pcsphp_users', 'pcsphp_jobs_queue'];
    $check(BackupPolicy::normalize(array_merge($defaults, ['data_excluded_tables' => ['pcsphp_users']]), $existing) !== null, 'a16 una tabla que existe se acepta');
    $check(BackupPolicy::normalize(array_merge($defaults, ['data_excluded_tables' => ['no_existe_esta_tabla']]), $existing) === null, 'a17 una tabla que no existe en la base se rechaza');
    $check(BackupPolicy::normalize(array_merge($defaults, ['data_excluded_tables' => ['no_existe_esta_tabla']])) !== null, 'a18 sin la lista de tablas no se comprueba la existencia: eso es del guardado');

    //──── b) La conservación sobre tres años de respaldos ───────────────────────────────────────
    echoTerminal('');
    echoTerminal("\e[36m b) plan() sobre tres años de respaldos diarios, con el conjunto conservado calculado aparte\e[39m");

    $reference = new DateTimeImmutable('2026-06-15 03:00:00');
    $nameOf = static fn (DateTimeImmutable $date): string => $date->format('d-m-Y_H-i-s-A') . '.sql.gz';

    $synthetic = [];
    for ($day = 0; $day < 1095; $day++) {
        $synthetic[] = $nameOf($reference->modify("-{$day} days"));
    }
    //Tres más del último día, para que «los últimos 24» y «uno por día» no coincidan.
    foreach ([1, 2, 3] as $hours) {
        $synthetic[] = $nameOf($reference->modify("-{$hours} hours"));
    }
    $strangers = ['manual.sql', 'x.sql.gz.bak', '01-01-2020.sql'];
    $universe = array_merge($synthetic, $strangers);
    shuffle($universe);

    $policy = BackupPolicy::defaults();
    $plan = BackupRotation::plan($universe, $policy);

    //El conjunto esperado, por un camino distinto al de plan(): se ordena por la fecha del
    //nombre y se cuentan a mano los periodos distintos, sin reutilizar nada de BackupRotation.
    $byDate = [];
    foreach ($synthetic as $name) {
        $stamp = DateTimeImmutable::createFromFormat('!d-m-Y_H-i-s', substr($name, 0, 19));
        $byDate[$name] = $stamp->getTimestamp();
    }
    arsort($byDate);
    $ordered = array_keys($byDate);

    $expected = array_slice($ordered, 0, $policy['keep_recent']);
    foreach (['Y-m-d' => $policy['keep_daily'], 'o-W' => $policy['keep_weekly'], 'Y-m' => $policy['keep_monthly']] as $format => $limit) {
        $periods = [];
        foreach ($ordered as $name) {
            $key = date($format, $byDate[$name]);
            if (!isset($periods[$key])) {
                $periods[$key] = $name;
                if (count($periods) >= $limit) {
                    break;
                }
            }
        }
        $expected = array_merge($expected, array_values($periods));
    }
    $expected = array_values(array_unique($expected));
    sort($expected);

    $check($plan['keep'] === $expected, 'b1 el conjunto conservado es EXACTAMENTE el que sale del cálculo independiente', 'plan ' . count($plan['keep']) . ' vs esperado ' . count($expected) . '; sobran: ' . implode(',', array_slice(array_diff($plan['keep'], $expected), 0, 3)) . '; faltan: ' . implode(',', array_slice(array_diff($expected, $plan['keep']), 0, 3)));
    sort($strangers);
    $check($plan['ignored'] === $strangers, 'b2 los tres archivos ajenos van a ignored', implode(',', $plan['ignored']));
    $check(count(array_intersect($plan['delete'], $strangers)) === 0, 'b3 ningún archivo ajeno entra en delete');
    $check(count($plan['keep']) + count($plan['delete']) === count($synthetic), 'b4 conservados y borrados suman todos los respaldos del framework, sin solaparse');
    $check(in_array($ordered[0], $plan['keep'], true), 'b5 el más nuevo se conserva');
    $check(count($plan['keep']) < 100, 'b6 tres años de respaldos diarios caben en menos de cien archivos: ' . count($plan['keep']));

    //──── c) Periodos CON respaldo, no de calendario ────────────────────────────────────────────
    echoTerminal('');
    echoTerminal("\e[36m c) Los periodos cuentan si tienen respaldo: un hueco no se come la historia\e[39m");

    $withGap = [];
    for ($day = 0; $day < 15; $day++) {
        $withGap[] = $nameOf($reference->modify("-{$day} days"));
    }
    //60 días sin respaldar, y antes otros 15 días seguidos.
    for ($day = 75; $day < 90; $day++) {
        $withGap[] = $nameOf($reference->modify("-{$day} days"));
    }
    $gapPolicy = array_merge(BackupPolicy::defaults(), ['keep_recent' => 1, 'keep_weekly' => 0, 'keep_monthly' => 0, 'keep_daily' => 30]);
    $gapPlan = BackupRotation::plan($withGap, $gapPolicy);
    $distinctDays = [];
    foreach ($gapPlan['keep'] as $name) {
        $distinctDays[substr($name, 0, 10)] = true;
    }
    $check(count($distinctDays) === 30, 'c1 con un hueco de 60 días se conservan los 30 días distintos que SÍ tienen respaldo: ' . count($distinctDays));
    $check(count($gapPlan['delete']) === 0, 'c2 y no se borra nada, porque los 30 días caben enteros');

    //──── d) rotate = false ─────────────────────────────────────────────────────────────────────
    echoTerminal('');
    echoTerminal("\e[36m d) Con la conservación apagada no se borra nada\e[39m");

    $noRotatePlan = BackupRotation::plan($universe, array_merge(BackupPolicy::defaults(), ['rotate' => false]));
    $check($noRotatePlan['delete'] === [], 'd1 rotate = false deja delete vacío');
    $check(count($noRotatePlan['keep']) === count($synthetic), 'd2 y conserva los ' . count($synthetic) . ' respaldos');
    $check($noRotatePlan['ignored'] === $strangers, 'd3 los ajenos siguen siendo ajenos');

    //──── e) apply() sobre una carpeta temporal ─────────────────────────────────────────────────
    echoTerminal('');
    echoTerminal("\e[36m e) apply() borra exactamente lo planeado, y nada más\e[39m");

    $applyDirectory = $makeDirectory('apply');
    $touchAll($applyDirectory, $universe);
    $nestedName = $nameOf($reference->modify('-800 days'));
    //RETORNO-IGNORADO: la subcarpeta y su archivo los comprueba e6, que falla si no están.
    mkdir("{$applyDirectory}/subcarpeta", 0775, true);
    file_put_contents("{$applyDirectory}/subcarpeta/{$nestedName}", '');

    $before = BackupRotation::namesIn($applyDirectory);
    $check(count($before) === count($universe), 'e1 namesIn solo mira la raíz: ' . count($before) . ' archivos, sin los de la subcarpeta');

    $applyPlan = BackupRotation::plan($before, $policy);
    $result = BackupRotation::apply($applyDirectory, $policy);
    $after = BackupRotation::namesIn($applyDirectory);

    sort($applyPlan['delete']);
    $deleted = $result['deleted'];
    sort($deleted);
    $check($deleted === $applyPlan['delete'], 'e2 se borró exactamente lo que el plan decía: ' . count($deleted));
    $check($result['failed'] === [], 'e3 ningún borrado falló');
    $check(count($after) === count($universe) - count($deleted), 'e4 quedan los demás: ' . count($after));
    foreach ($strangers as $stranger) {
        $check(is_file("{$applyDirectory}/{$stranger}"), "e5 el archivo ajeno {$stranger} sigue ahí");
    }
    $check(is_file("{$applyDirectory}/subcarpeta/{$nestedName}"), 'e6 el respaldo de la subcarpeta no se toca');
    $check($result['kept'] === count($applyPlan['keep']), 'e7 kept dice cuántos se conservan');

    //El recién escrito nunca se borra, aunque la política lo dejara fuera.
    $justWrittenDirectory = $makeDirectory('just-written');
    $oldest = $nameOf($reference->modify('-1000 days'));
    $touchAll($justWrittenDirectory, [$oldest, $nameOf($reference)]);
    $forgetful = array_merge(BackupPolicy::defaults(), ['keep_recent' => 1, 'keep_daily' => 0, 'keep_weekly' => 0, 'keep_monthly' => 0]);
    $planWithoutIt = BackupRotation::plan(BackupRotation::namesIn($justWrittenDirectory), $forgetful);
    $check(in_array($oldest, $planWithoutIt['delete'], true), 'e8 con una política mínima el más viejo entraría en delete');
    $resultProtecting = BackupRotation::apply($justWrittenDirectory, $forgetful, "/cualquier/ruta/{$oldest}");
    $check(is_file("{$justWrittenDirectory}/{$oldest}"), 'e9 pero pasado como recién escrito, NO se borra');
    $check($resultProtecting['deleted'] === [], 'e10 y no se borra nada más en su lugar');

    //──── f) El respaldo que falla no rota ──────────────────────────────────────────────────────
    echoTerminal('');
    echoTerminal("\e[36m f) Un respaldo FALLIDO no borra ningún respaldo\e[39m");

    $failureDirectory = $makeDirectory('failure');
    $touchAll($failureDirectory, $universe);
    $beforeFailure = BackupRotation::namesIn($failureDirectory);
    sort($beforeFailure);

    $failureResult = BackupRotation::afterBackup(false, null, $failureDirectory, $policy);
    $afterFailure = BackupRotation::namesIn($failureDirectory);
    sort($afterFailure);

    $check($failureResult['rotated'] === false, 'f1 afterBackup declara que no rotó');
    $check($failureResult['deleted'] === [], 'f2 no dice haber borrado nada');
    $check($afterFailure === $beforeFailure, 'f3 la carpeta queda IGUAL que antes: ' . count($afterFailure) . ' archivos', count($beforeFailure) . ' antes');

    //Y con éxito sí rota, para que f3 signifique algo.
    $successResult = BackupRotation::afterBackup(true, "{$failureDirectory}/" . $nameOf($reference), $failureDirectory, $policy);
    $check($successResult['rotated'] === true && count($successResult['deleted']) > 0, 'f4 con un respaldo correcto SÍ rota: ' . count($successResult['deleted']) . ' borrados');

    //──── g) isDue() ────────────────────────────────────────────────────────────────────────────
    echoTerminal('');
    echoTerminal("\e[36m g) Toca respaldar cuando el más reciente es más viejo que el intervalo\e[39m");

    $previousPolicy = get_config(BackupPolicy::CONFIG_NAME);
    $emptyDirectory = $makeDirectory('empty');
    $now = new DateTimeImmutable('2026-06-15 12:00:00');
    $interval = BackupPolicy::defaults()['interval_minutes'];

    set_config(BackupPolicy::CONFIG_NAME, json_encode(BackupPolicy::defaults()));
    $check(BackupPolicy::isDue($now, $emptyDirectory) === true, 'g1 sin ningún respaldo, toca');

    $freshDirectory = $makeDirectory('fresh');
    $touchAll($freshDirectory, [$nameOf($now->modify('-' . ($interval - 1) . ' minutes'))]);
    $check(BackupPolicy::isDue($now, $freshDirectory) === false, 'g2 con el más reciente de hace intervalo menos un minuto, no toca');

    $staleDirectory = $makeDirectory('stale');
    $touchAll($staleDirectory, [$nameOf($now->modify('-' . ($interval + 1) . ' minutes'))]);
    $check(BackupPolicy::isDue($now, $staleDirectory) === true, 'g3 con el más reciente de hace intervalo más un minuto, toca');

    //Un archivo ajeno no cuenta como respaldo.
    $strangerOnly = $makeDirectory('stranger-only');
    $touchAll($strangerOnly, ['manual.sql']);
    $check(BackupPolicy::isDue($now, $strangerOnly) === true, 'g4 un volcado ajeno no cuenta: sigue tocando');

    set_config(BackupPolicy::CONFIG_NAME, json_encode(array_merge(BackupPolicy::defaults(), ['enabled' => false])));
    $check(BackupPolicy::isDue($now, $staleDirectory) === false, 'g5 con la política apagada no toca nunca');

    //Una política inválida guardada no revienta: rigen los valores por omisión.
    set_config(BackupPolicy::CONFIG_NAME, '{esto no es json');
    $check(BackupPolicy::current() == BackupPolicy::defaults(), 'g6 una política inválida en la configuración cae a los valores por omisión');

    set_config(BackupPolicy::CONFIG_NAME, $previousPolicy);

    //──── h) Una tabla sin filas sale con su estructura ─────────────────────────────────────────
    echoTerminal('');
    echoTerminal("\e[36m h) Una tabla excluida sale con su CREATE TABLE y sin un solo INSERT\e[39m");

    $testTable = 'zz_pcs_backup_policy_rows';
    $writtenDump = null;
    $database = (new BaseModel())->getDatabase();
    if ($database === null) {
        $check(false, 'h0 hay conexión a la base de datos');
    } else {
        try {
            //RETORNO-IGNORADO: la conexión va en ERRMODE_EXCEPTION, así que un DDL que falla LANZA, y h1 cuenta las filas.
            $database->exec("DROP TABLE IF EXISTS `{$testTable}`");
            $database->exec("CREATE TABLE `{$testTable}` (`id` INT NOT NULL AUTO_INCREMENT, `nota` VARCHAR(50) NOT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            //RETORNO-IGNORADO: lo mismo; h1 cuenta las filas que este INSERT deja.
            $database->exec("INSERT INTO `{$testTable}` (`nota`) VALUES ('zz-fila-de-prueba-1'), ('zz-fila-de-prueba-2')");
            $rows = (int) $database->query("SELECT COUNT(*) FROM `{$testTable}`")->fetchColumn();
            $check($rows === 2, 'h1 la tabla de prueba tiene sus 2 filas antes de respaldar');

            BackupPolicy::excludeDataOf($testTable, 'Tabla de la suite UnitTest-BackupPolicy.');
            $check(in_array($testTable, BackupPolicy::dataExcludedTables(), true), 'h2 excludeDataOf la añade a las tablas sin filas');
            $check(array_key_exists($testTable, BackupPolicy::codeExcludedDataTables()), 'h3 y queda con su motivo, para que la pantalla lo enseñe');

            $backupDone = DbBackupTask::main(null, null, [], true);
            $writtenDump = DbBackupTask::lastWrittenFile();
            $check($backupDone === true && $writtenDump !== null, 'h4 el respaldo real termina bien y dice qué archivo escribió');

            if ($writtenDump !== null && is_file($writtenDump)) {
                $content = '';
                if (substr($writtenDump, -3) === '.gz') {
                    $handle = gzopen($writtenDump, 'rb');
                    while ($handle !== false && !gzeof($handle)) {
                        $chunk = gzread($handle, 262144);
                        if ($chunk === false) {
                            break;
                        }
                        $content .= $chunk;
                    }
                    if ($handle !== false) {
                        //RETORNO-IGNORADO: el archivo ya está leído; lo que importe se comprueba sobre el contenido.
                        gzclose($handle);
                    }
                } else {
                    $content = (string) file_get_contents($writtenDump);
                }

                $check(str_contains($content, "CREATE TABLE IF NOT EXISTS `{$testTable}`") || str_contains($content, "CREATE TABLE `{$testTable}`"), 'h5 el volcado trae el CREATE TABLE de la tabla excluida');
                $check(!str_contains($content, "INSERT INTO `{$testTable}`"), 'h6 y NINGÚN INSERT de esa tabla');
                $check(!str_contains($content, 'zz-fila-de-prueba-1'), 'h7 sus filas no están en el archivo');
                $check(preg_match_all('/^INSERT INTO /m', $content) > 0, 'h8 las demás tablas SÍ traen sus filas: el volcado no salió vacío');
            }
        } catch (\Throwable $e) {
            $check(false, 'h9 el apartado h corre entero', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
    }

    //──── z) Limpieza ───────────────────────────────────────────────────────────────────────────
    echoTerminal('');
    echoTerminal("\e[36m z) Limpieza: la base y las carpetas quedan como estaban\e[39m");

    $cleaned = [];
    if ($database !== null) {
        try {
            //RETORNO-IGNORADO: z1 comprueba en information_schema que la tabla ya no está.
            $database->exec("DROP TABLE IF EXISTS `{$testTable}`");
            $stillThere = (int) $database->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = " . $database->quote($testTable))->fetchColumn();
            $check($stillThere === 0, 'z1 la tabla de prueba se retiró de la base');
        } catch (\Throwable $e) {
            $check(false, 'z1 la tabla de prueba se retiró de la base', $e->getMessage());
        }
    }

    if ($writtenDump !== null && is_file($writtenDump)) {
        $cleaned[] = basename($writtenDump);
        //RETORNO-IGNORADO: z2 comprueba que el archivo ya no está, que es lo que importa.
        unlink($writtenDump);
    }
    $check($writtenDump === null || !is_file($writtenDump), 'z2 el respaldo que escribió esta suite se borró: ' . ($cleaned === [] ? 'ninguno' : implode(', ', $cleaned)));

    //RETORNO-IGNORADO: z3 comprueba que no quede ninguna carpeta de la prueba, que es la medida que vale.
    foreach (array_keys($temporaryDirectories) as $directory) {
        foreach (glob("{$directory}/subcarpeta/*") ?: [] as $file) {
            //RETORNO-IGNORADO: lo mismo; la comprobación z3 es la que manda.
            unlink($file);
        }
        if (is_dir("{$directory}/subcarpeta")) {
            //RETORNO-IGNORADO: lo mismo; la comprobación z3 es la que manda.
            rmdir("{$directory}/subcarpeta");
        }
        foreach (glob("{$directory}/*") ?: [] as $file) {
            if (is_file($file)) {
                //RETORNO-IGNORADO: lo mismo; la comprobación z3 es la que manda.
                unlink($file);
            }
        }
        //RETORNO-IGNORADO: lo mismo; la comprobación z3 es la que manda.
        rmdir($directory);
    }
    $remaining = array_filter(array_keys($temporaryDirectories), 'is_dir');
    $check($remaining === [], 'z3 las carpetas temporales de la prueba se borraron', implode(', ', $remaining));

    return $balance();

})->setDescription('La política de respaldos: normalización por campo, isDue por intervalo, la conservación por niveles sobre tres años, los ajenos intactos, el recién escrito que no se borra, el fallo que no rota y una tabla sin filas.')->setEffects([CliActions::EFFECT_DATABASE, CliActions::EFFECT_FILES])->register();
