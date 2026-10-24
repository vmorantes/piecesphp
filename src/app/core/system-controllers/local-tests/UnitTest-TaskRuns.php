<?php

//El libro de tareas programadas y el cerrojo de las tareas sin franja (G10). Tareas de prueba zz-agente-fr-* que no se
//registran; su estado, en un directorio propio de /tmp; el tope, en una tabla propia que se borra al final.
//@areas src/app/classes/API/
//@areas src/app/classes/PiecesPHP/SystemStatus/

use API\Controllers\APIController;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\SystemStatus\Controllers\SystemStatusController;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\Terminal\CronJobTask;
use PiecesPHP\Terminal\TaskRunMapper;

CliActions::make('unit-tests:core/task-runs', function ($args) {

    //PAPEL HIJO: uno de los dos procesos de la prueba del cerrojo. Ejecuta la tarea una vez e imprime su estado.
    if (($args['papel'] ?? '') === 'hijo') {
        $nombre = (string) ($args['tarea'] ?? '');
        $directorio = (string) ($args['estado'] ?? '');
        $contador = (string) ($args['contador'] ?? '');
        if (!str_starts_with($nombre, 'zz-agente-fr-') || !str_starts_with($directorio, sys_get_temp_dir() . '/zz-agente-fr-')) {
            return ['success' => false, 'message' => 'papel hijo sin una tarea ni un directorio de prueba'];
        }
        $tarea = CronJobTask::make($nombre, function () use ($contador): array {
            //RETORNO-IGNORADO: el padre cuenta las líneas: una escritura que falla se ve como una ejecución de menos.
            file_put_contents($contador, "1\n", \FILE_APPEND | \LOCK_EX);
            sleep(2);
            return ['success' => true, 'message' => 'contada'];
        })->when(fn(): bool => true)->setStateDirectory($directorio);
        $resultado = $tarea->run(new \DateTime());
        echoTerminal('RESULTADO-HIJO ' . $resultado['status']);
        return ['success' => true, 'message' => $resultado['status']];
    }

    echoTerminal("\e[33m[TEST:TaskRuns] El libro de tareas y el cerrojo de las tareas sin franja\e[39m");
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

    $database = (new BaseModel())->getDatabase();
    if (!$check($database !== null, 'c1 hay conexión a la base')) {
        return $balance();
    }
    if (!$check(TaskRunMapper::tableExists(), 'c2 la tabla del libro existe', 'aplica ' . TaskRunMapper::UPDATE_FILE)) {
        return $balance();
    }

    $marca = bin2hex(random_bytes(3));
    $prefijo = "zz-agente-fr-{$marca}";
    $directorio = sys_get_temp_dir() . "/{$prefijo}-estado";
    $tabla = TaskRunMapper::TABLE;
    $tablaTope = TaskRunMapper::TABLE . "_zz_{$marca}";
    $filas = function (string $nombre) use ($database, $tabla): array {
        $consulta = $database->prepare("SELECT triggeredBy, status, attempt, durationMs, message, finishedAt FROM `{$tabla}` WHERE taskName = ? ORDER BY id");
        //RETORNO-IGNORADO: lo que cuenta son las filas que siguen, y se comprueban.
        $consulta->execute([$nombre]);
        return $consulta->fetchAll(\PDO::FETCH_ASSOC);
    };
    $tarea = function (string $sufijo, callable $handler) use ($prefijo, $directorio): CronJobTask {
        return CronJobTask::make("{$prefijo}-{$sufijo}", $handler)->setStateDirectory($directorio);
    };

    try {

        //─── a · Una línea por ejecución real ─────────────────────────────────────────────────────
        echoTerminal('[a] Lo que se ejecuta deja su línea');
        $ok = $tarea('ok', fn(): array => ['success' => true, 'message' => 'hecho'])->when(fn(): bool => true);
        $r = $ok->run(new \DateTime());
        $f = $filas("{$prefijo}-ok");
        $check($r['status'] === CronJobTask::STATUS_EXECUTED && count($f) === 1, 'a1 una tarea sin franja que se ejecuta deja UNA línea', json_encode($f) ?: '');
        $check(($f[0]['triggeredBy'] ?? '') === TaskRunMapper::TRIGGER_CLI && ($f[0]['status'] ?? '') === CronJobTask::STATUS_EXECUTED && (int) ($f[0]['attempt'] ?? 0) === 1
            && is_numeric($f[0]['durationMs'] ?? null) && ($f[0]['finishedAt'] ?? null) !== null, 'a2 con su origen (cli por omisión), su resultado, su intento, su duración y su fin');
        $ok->setTriggeredBy(TaskRunMapper::TRIGGER_HTTP)->run(new \DateTime());
        $f = $filas("{$prefijo}-ok");
        $check(($f[1]['triggeredBy'] ?? '') === TaskRunMapper::TRIGGER_HTTP, 'a3 disparada por HTTP, lo dice');
        $falla = $tarea('falla', function (): array {
            throw new \RuntimeException('zz fallo de prueba');
        })->when(fn(): bool => true);
        $falla->run(new \DateTime());
        $f = $filas("{$prefijo}-falla");
        $check(count($f) === 1 && ($f[0]['status'] ?? '') === CronJobTask::STATUS_FAILED && str_contains((string) ($f[0]['message'] ?? ''), 'zz fallo de prueba'), 'a4 una que falla deja failed con su mensaje');
        $larga = $tarea('larga', fn(): array => ['success' => true, 'message' => str_repeat('x', 900)])->when(fn(): bool => true);
        $larga->run(new \DateTime());
        $check(mb_strlen((string) ($filas("{$prefijo}-larga")[0]['message'] ?? '')) === TaskRunMapper::MESSAGE_LENGTH, 'a5 el mensaje se recorta a ' . TaskRunMapper::MESSAGE_LENGTH);

        //─── b · Lo que no se ejecuta no deja nada ────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[b] Lo que no se ejecuta no ocupa el libro');
        $noToca = $tarea('no-toca', fn(): array => ['success' => true, 'message' => ''])->when(fn(): bool => false);
        $check($noToca->run(new \DateTime())['status'] === CronJobTask::STATUS_NOT_DUE && $filas("{$prefijo}-no-toca") === [], 'b1 «no toca» no deja línea');
        $ocupada = $tarea('ocupada', fn(): array => ['success' => true, 'message' => ''])->when(fn(): bool => true);
        $ocupada->run(new \DateTime());
        $antes = count($filas("{$prefijo}-ocupada"));
        //flock() es por descriptor abierto, no por proceso: otro descriptor de este mismo proceso ya excluye (medido aquí).
        $cerrojo = fopen($ocupada->getLockPath(), 'c');
        $tomado = $cerrojo !== false && flock($cerrojo, \LOCK_EX | \LOCK_NB);
        $mientras = $ocupada->run(new \DateTime());
        if ($cerrojo !== false) {
            //RETORNO-IGNORADO: soltar un cerrojo propio; fclose() lo suelta igual.
            flock($cerrojo, \LOCK_UN);
            //RETORNO-IGNORADO: cerrar un descriptor propio.
            fclose($cerrojo);
        }
        $check($tomado && $mientras['status'] === CronJobTask::STATUS_LOCKED && count($filas("{$prefijo}-ocupada")) === $antes, 'b2 con el cerrojo tomado: «en curso», sin ejecutar y sin línea', $mientras['status']);
        $conFranja = $tarea('franja', fn(): array => ['success' => true, 'message' => 'diaria'])->dailyAt('03:30');
        $alas = new \DateTime('today 03:31');
        $primera = $conFranja->run($alas)['status'];
        $segunda = $conFranja->run($alas)['status'];
        $check($primera === CronJobTask::STATUS_EXECUTED && $segunda === CronJobTask::STATUS_ALREADY_DONE
            && count($filas("{$prefijo}-franja")) === 1, 'b3 con franja: una línea por la ejecución, ninguna por «ya hecha»', "{$primera}, {$segunda}");

        //─── c · Dos procesos a la vez, una sola ejecución ────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[c] El cerrojo de las tareas sin franja, con dos procesos de verdad');
        $contador = sys_get_temp_dir() . "/{$prefijo}-contador";
        $cli = dirname(rtrim(str_replace('\\', '/', basepath('')), '/')) . '/bin/cli';
        $hijos = [];
        foreach ([1, 2] as $n) {
            $proceso = proc_open([$cli, 'unit-tests:core/task-runs', 'papel=hijo', "tarea={$prefijo}-doble", "estado={$directorio}", "contador={$contador}"], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $tubos);
            if (is_resource($proceso)) {
                $hijos[] = [$proceso, $tubos];
            }
        }
        $salidas = [];
        foreach ($hijos as [$proceso, $tubos]) {
            $salidas[] = (string) stream_get_contents($tubos[1]);
            //RETORNO-IGNORADO: cerrar una tubería propia ya leída.
            fclose($tubos[1]);
            //RETORNO-IGNORADO: cerrar una tubería propia.
            fclose($tubos[2]);
            //RETORNO-IGNORADO: el código de salida del hijo no dice nada; lo que cuenta es su línea RESULTADO-HIJO.
            proc_close($proceso);
        }
        $estados = [];
        foreach ($salidas as $salida) {
            $estados[] = preg_match('/RESULTADO-HIJO (\S+)/', $salida, $m) === 1 ? $m[1] : '?';
        }
        sort($estados);
        $veces = is_file($contador) ? count(file($contador) ?: []) : 0;
        $check($estados === [CronJobTask::STATUS_EXECUTED, CronJobTask::STATUS_LOCKED], 'c1 de dos procesos a la vez, uno ejecuta y el otro queda «en curso»', implode(', ', $estados));
        $check($veces === 1 && count($filas("{$prefijo}-doble")) === 1, 'c2 la tarea corrió UNA vez y el libro tiene UNA línea', "veces {$veces}");

        //─── d · El tope y la tabla que falta ─────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[d] El tope de líneas y la instalación sin la tabla');
        //RETORNO-IGNORADO: si no se crea, d1 cuenta en una tabla que no existe y falla.
        $database->exec("CREATE TABLE `{$tablaTope}` LIKE `{$tabla}`");
        try {
            TaskRunMapper::useTableForTesting($tablaTope);
            TaskRunMapper::setMaxRows(3);
            $tope = $tarea('tope', fn(): array => ['success' => true, 'message' => ''])->when(fn(): bool => true);
            for ($i = 0; $i < 5; $i++) {
                $tope->run(new \DateTime());
            }
            $check((int) $database->query("SELECT COUNT(*) FROM `{$tablaTope}`")->fetchColumn() === 3, 'd1 con un tope de 3, cinco ejecuciones dejan las 3 más recientes');
        } finally {
            TaskRunMapper::setMaxRows(null);
            TaskRunMapper::useTableForTesting(null);
            //RETORNO-IGNORADO: limpieza de una tabla propia; z1 lo comprueba.
            $database->exec("DROP TABLE IF EXISTS `{$tablaTope}`");
        }
        TaskRunMapper::useTableForTesting(TaskRunMapper::TABLE . "_zz_falta_{$marca}");
        try {
            $sinTabla = $tarea('sin-tabla', fn(): array => ['success' => true, 'message' => 'corrió'])->when(fn(): bool => true);
            $resultado = $sinTabla->run(new \DateTime());
            $check(!TaskRunMapper::tableExists() && $resultado['status'] === CronJobTask::STATUS_EXECUTED && $resultado['message'] === 'corrió', 'd2 sin la tabla, la tarea corre igual (una tabla de prueba que no existe; la real no se toca)');
        } finally {
            TaskRunMapper::useTableForTesting(null);
        }
        $check($filas("{$prefijo}-sin-tabla") === [], 'd3 y no anota nada en la real');

        //─── e · La chuleta del cron, con lo de ESTA instalación y sin la llave ───────────────────
        echoTerminal(' ');
        echoTerminal('[e] La chuleta del cron: la ruta real, la URL de la ruta y nunca la llave');
        $llavePrevia = get_config('CronJobKey');
        $llave = 'zz-llave-cron-' . bin2hex(random_bytes(8));
        set_config('CronJobKey', $llave);
        try {
            $peticion = new RequestRoute('GET', (new UriFactory())->createUri('http://localhost/zz'), new Headers(), [], [], (new StreamFactory())->createStream(''));
            $html = SystemStatusController::taskRunsTab($peticion, 'https://zz.test/tab');
            $chuleta = SystemStatusController::cronCheatsheet([], new \DateTime());
            $check(str_contains($html, escape_html('php8.5 ' . basepath('index.php') . ' cli run-cronjobs run')), 'e1 la línea lleva la ruta real del index.php de esta instalación', basepath('index.php'));
            $url = APIController::routeName('cron-jobs', ['actionType' => 'run'], true);
            $check($url !== '' && $chuleta['url'] === $url && str_contains($html, escape_html($url)), 'e2 la URL sale de la ruta api-admin-cron-jobs', $url);
            $check(!str_contains($html, $llave) && $chuleta['keyConfigured'] === true, 'e3 la llave configurada NO aparece en el HTML, y dice que hay llave');
            $check($chuleta['open'] === true && str_contains($html, 'data-task-runs-cron'), 'e4 sin ejecuciones anotadas, la chuleta nace abierta');
            $haceUnRato = SystemStatusController::cronCheatsheet(['zz' => ['status' => 'executed', 'startedAt' => date('Y-m-d H:i:s', time() - 300), 'message' => null]], new \DateTime());
            $check($haceUnRato['open'] === false && $haceUnRato['lastRunAgo'] !== null, 'e5 con una ejecución de hace 5 min, nace plegada y lo dice', (string) $haceUnRato['lastRunAgo']);
            $registradas = CronJobTask::getCronJobs();
            $registrada = $registradas !== [] ? $registradas[array_key_first($registradas)]->getName() : '';
            $ajena = 'zz-tarea-no-registrada-' . bin2hex(random_bytes(3));
            $fila = ['status' => 'executed', 'startedAt' => date('Y-m-d H:i:s', time() - 300), 'message' => null];
            $filtradas = SystemStatusController::registeredRuns([$registrada => $fila, $ajena => $fila]);
            $check($registrada !== '' && array_keys($filtradas) === [$registrada], 'e6 registeredRuns() deja solo la tarea registrada, no la ajena', implode(', ', array_keys($filtradas)));
            $soloAjena = SystemStatusController::cronCheatsheet(SystemStatusController::registeredRuns([$ajena => $fila]), new \DateTime());
            $check($soloAjena['open'] === true && $soloAjena['lastRunAgo'] === null, 'e7 una ejecución de hace 5 min de una tarea no registrada no pliega la ayuda ni cuenta como última', (string) $soloAjena['lastRunAgo']);
        } finally {
            set_config('CronJobKey', $llavePrevia);
        }

    } catch (\Throwable $e) {
        $check(false, 'sin excepciones', get_class($e) . ': ' . $e->getMessage());
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        $database->prepare("DELETE FROM `{$tabla}` WHERE taskName LIKE ?")->execute(["{$prefijo}%"]);
        //RETORNO-IGNORADO: limpieza de una tabla propia; z1 lo comprueba.
        $database->exec("DROP TABLE IF EXISTS `{$tablaTope}`");
        foreach (glob("{$directorio}/*") ?: [] as $archivo) {
            //RETORNO-IGNORADO: limpieza de un temporal propio; z1 lo comprueba.
            unlink($archivo);
        }
        if (is_dir($directorio)) {
            //RETORNO-IGNORADO: limpieza de un temporal propio; z1 lo comprueba.
            rmdir($directorio);
        }
        if (is_file(sys_get_temp_dir() . "/{$prefijo}-contador")) {
            //RETORNO-IGNORADO: limpieza de un temporal propio.
            unlink(sys_get_temp_dir() . "/{$prefijo}-contador");
        }
        $quedan = (int) $database->query("SELECT COUNT(*) FROM `{$tabla}` WHERE taskName LIKE '{$prefijo}%'")->fetchColumn();
        $check($quedan === 0 && !is_dir($directorio), 'z1 no queda ninguna línea, tabla ni archivo de la prueba', (string) $quedan);
    }

    return $balance();

})->setDescription('El libro de tareas programadas (G10): cada ejecución real deja una línea con su origen, resultado, intento, duración y mensaje recortado; «no toca», «en curso» y «ya hecha» no; dos procesos a la vez ejecutan una tarea sin franja UNA vez; el tope recorta; y sin la tabla la tarea corre igual. Tareas de prueba sin registrar, estado en /tmp, tope en una tabla propia que se borra.')->setEffects([CliActions::EFFECT_DATABASE])->register();
