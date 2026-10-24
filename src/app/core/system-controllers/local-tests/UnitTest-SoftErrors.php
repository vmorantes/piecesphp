<?php

//ADR 0060: graves y suaves, el registro que solo crece y el aviso de errores nuevos. a1, a3 y [e] escriben en el registro
//de la instalación (se quedan y se enumeran); lo demás, en un temporal. Crea dos usuarios zz-agente-errores-* y los borra.

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\CustomErrorsHandlers\GenericHandler;
use PiecesPHP\Core\CustomErrorsHandlers\PhpErrorHandler;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Logs\Controllers\LogsController;
use PiecesPHP\Logs\ErrorLogReader;
use PiecesPHP\Logs\NewErrorsAlert;
use PiecesPHP\SystemStatus\SystemAlertRegistry;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/soft-errors', function ($args) {

    echoTerminal("\e[33m[TEST:SoftErrors] Errores graves y suaves, el registro que solo crece y el aviso de errores nuevos\e[39m");
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

    $referencePattern = '/^ERR-\d{8}-[0-9A-F]{6}$/';
    $marca = 'zz-soft-errors-' . bin2hex(random_bytes(4));
    $escritas = [];
    $logDir = rtrim((string) constant('LOG_ERRORS_PATH'), '/\\');
    //Las entradas de un registro, una por línea; null si alguna línea no es JSON.
    //Con $withRotated, también los rotados de olds/.
    $lineas = function (string $directory, bool $withRotated = false): ?array {
        $files = [$directory . '/' . GenericHandler::FILE_ENTRIES];
        if ($withRotated) {
            $files = array_merge($files, glob($directory . '/olds/error.log.*.jsonl') ?: []);
        }
        $entries = [];
        foreach ($files as $file) {
            $content = @file_get_contents($file);
            if (!is_string($content) || trim($content) === '') {
                continue;
            }
            foreach (explode("\n", rtrim($content, "\n")) as $line) {
                $entry = json_decode($line, true);
                if (!is_array($entry)) {
                    return null;
                }
                $entries[] = $entry;
            }
        }
        return $entries;
    };
    $temporales = [];
    $temporal = function () use (&$temporales): string {
        $directory = sys_get_temp_dir() . '/zz-soft-errors-' . bin2hex(random_bytes(4));
        if (!mkdir($directory, 0775, true)) {
            throw new \RuntimeException("No se pudo crear el temporal {$directory}");
        }
        $temporales[] = $directory;
        return $directory;
    };
    $noExiste = sys_get_temp_dir() . "/{$marca}-no-existe";

    try {
        //─── a · Graves y suaves ──────────────────────────────────────────────────────────────────────
        echoTerminal('[a] Graves y suaves');
        $siguio = false;
        $lineaAviso = 0;
        //Se agrupa por firma (tipo, archivo, línea y, si corre dentro de una corrida de pruebas, su marca: ADR 0063 §4) en
        //el día: en la segunda corrida del día no hay entrada nueva, sube la cuenta. Por eso se mide el contador.
        $corrida = GenericHandler::testRun();
        $firmaAviso = fn(int $line): string => date('Ymd') . '/' . hash('sha256', 'E_WARNING|' . __FILE__ . '|' . $line . ($corrida !== null ? '|' . $corrida : ''));
        set_error_handler(fn(int $type, string $message, string $file, int $line): bool => PhpErrorHandler::handle($type, $message, $file, $line, false));
        try {
            //Un E_WARNING de verdad: el archivo no existe. Su línea es la siguiente a esta, para la firma.
            $lineaAviso = __LINE__ + 2;
            $cuentaAntes = GenericHandler::softCounter($logDir, $firmaAviso($lineaAviso))['count'] ?? 0;
            $leido = file_get_contents($noExiste);
            $siguio = $leido === false;
        } catch (\Throwable $e) {
            $siguio = false;
        } finally {
            restore_error_handler();
        }
        $claveAviso = $firmaAviso($lineaAviso);
        $real = array_values(array_filter($lineas($logDir) ?? [], fn(array $entry) => ($entry['softKey'] ?? null) === $claveAviso));
        $aviso = $real[0] ?? [];
        $cuentaDespues = GenericHandler::softCounter($logDir, $claveAviso)['count'] ?? 0;
        if (isset($aviso['reference'])) {
            $escritas[] = (string) $aviso['reference'];
        }
        $check(
            $siguio && count($real) === 1 && $cuentaDespues === $cuentaAntes + 1 && ($aviso['level'] ?? null) === 'soft' && ($aviso['type'] ?? null) === 'E_WARNING' && preg_match($referencePattern, (string) ($aviso['reference'] ?? '')) === 1,
            'a1 fuera de local un E_WARNING NO aborta: la ejecución sigue y su entrada soft (con referencia) cuenta una vez más',
            json_encode([$siguio, count($real), $cuentaAntes, $cuentaDespues, $aviso['level'] ?? null, $aviso['type'] ?? null]) ?: ''
        );

        $abortoEnLocal = false;
        set_error_handler(fn(int $type, string $message, string $file, int $line): bool => PhpErrorHandler::handle($type, $message, $file, $line, true));
        try {
            //RETORNO-IGNORADO: lo que se mide es si lanza.
            file_get_contents($noExiste);
        } catch (\ErrorException $e) {
            $abortoEnLocal = $e->getSeverity() === E_WARNING;
        } finally {
            restore_error_handler();
        }
        $check($abortoEnLocal, 'a2 en local el mismo E_WARNING aborta con ErrorException, como antes');

        $abortoSuaveDeclarado = false;
        $referenciaDeclarada = '';
        set_error_handler(fn(int $type, string $message, string $file, int $line): bool => PhpErrorHandler::handle($type, $message, $file, $line, true));
        try {
            $referenciaDeclarada = log_soft_error("{$marca} declarado", ['zz' => 'contexto']);
        } catch (\Throwable $e) {
            $abortoSuaveDeclarado = true;
        } finally {
            restore_error_handler();
        }
        $escritas[] = $referenciaDeclarada;
        $declarado = array_values(array_filter($lineas($logDir) ?? [], fn(array $entry) => ($entry['reference'] ?? null) === $referenciaDeclarada));
        $check(
            !$abortoSuaveDeclarado && preg_match($referencePattern, $referenciaDeclarada) === 1 && ($declarado[0]['level'] ?? null) === 'soft' && ($declarado[0]['extraData']['zz'] ?? null) === 'contexto',
            'a3 log_soft_error() no aborta en local: devuelve la referencia y su entrada es soft, con el contexto',
            $referenciaDeclarada
        );
        echoTerminal(' ');

        //─── b · Sin inundar el registro ───────────────────────────────────────────────────────────────
        echoTerminal('[b] Sin inundar el registro');
        $directorio = $temporal();
        $repetido = [];
        for ($i = 0; $i < 2; $i++) {
            $repetido[] = GenericHandler::logSoft(new \ErrorException("{$marca} repetido {$i}", 0, E_WARNING, '/zz/archivo.php', 7), 'E_WARNING', [], $directorio);
        }
        $entradas = $lineas($directorio) ?? [];
        $lector = new ErrorLogReader($directorio, $directorio . '/olds');
        $encontrado = $lector->findByReference($repetido[0]);
        $check(
            count($entradas) === 1 && $repetido[0] === $repetido[1] && ($encontrado['entry']['count'] ?? null) === 2 && ($encontrado['entry']['lastAt'] ?? 0) > 0,
            'b1 dos avisos iguales el mismo día: UNA entrada, la misma referencia y count 2 con su última hora',
            json_encode([count($entradas), $repetido, $encontrado['entry']['count'] ?? null]) ?: ''
        );
        $distinto = GenericHandler::logSoft(new \ErrorException("{$marca} otra línea", 0, E_WARNING, '/zz/archivo.php', 8), 'E_WARNING', [], $directorio);
        $check($distinto !== $repetido[0] && count($lineas($directorio) ?? []) === 2, 'b2 otra línea es otra firma: otra entrada');
        $graves = [];
        for ($i = 0; $i < 2; $i++) {
            $grave = new GenericHandler(new \RuntimeException("{$marca} grave"), $directorio);
            $grave->logging(false);
            $graves[] = $grave->reference();
        }
        $check($graves[0] !== $graves[1] && count($lineas($directorio) ?? []) === 4, 'b3 un grave crea siempre su entrada, aunque se repita');
        echoTerminal(' ');

        //─── c · Dos procesos a la vez ─────────────────────────────────────────────────────────────────
        echoTerminal('[c] Dos procesos escriben a la vez');
        $directorio = $temporal();
        $porProceso = 150;
        $script = $directorio . '/zz-escritor.php';
        $escrito = file_put_contents($script, '<?php
require ' . var_export(basepath('app/core/psr4/PiecesPHP/Core/CustomErrorsHandlers/ErrorSeverity.php'), true) . ';
require ' . var_export(basepath('app/core/psr4/PiecesPHP/Core/CustomErrorsHandlers/GenericHandler.php'), true) . ';
[, $directory, $who, $count] = $argv;
$relleno = str_repeat("x", 20000);
for ($i = 0; $i < (int) $count; $i++) {
    (new \PiecesPHP\Core\CustomErrorsHandlers\GenericHandler(new \RuntimeException("zz-concurrente-{$who}-{$i}-{$relleno}"), $directory))->logging(false);
}
');
        if ($escrito === false) {
            throw new \RuntimeException("No se pudo escribir {$script}");
        }
        $procesos = [];
        $procesosTuberias = [];
        foreach (['uno', 'dos'] as $quien) {
            $procesos[] = proc_open([PHP_BINARY, $script, $directorio, $quien, (string) $porProceso], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $tuberias);
            $procesosTuberias[] = $tuberias;
        }
        $salidas = '';
        $codigos = [];
        foreach ($procesos as $indice => $proceso) {
            if (is_resource($proceso)) {
                $salidas .= (string) stream_get_contents($procesosTuberias[$indice][1]) . (string) stream_get_contents($procesosTuberias[$indice][2]);
                $codigos[] = proc_close($proceso);
            }
        }
        //Pasan de 1 MB: rotan a mitad, así que se cuentan también los rotados.
        $concurrentes = $lineas($directorio, true);
        $rotados = count(glob($directorio . '/olds/error.log.*.jsonl') ?: []);
        $mensajes = $concurrentes !== null ? array_map(fn(array $entry) => preg_replace('/-x+$/', '', (string) ($entry['message'] ?? '')), $concurrentes) : [];
        $esperados = [];
        foreach (['uno', 'dos'] as $quien) {
            for ($i = 0; $i < $porProceso; $i++) {
                $esperados[] = "zz-concurrente-{$quien}-{$i}";
            }
        }
        sort($mensajes);
        sort($esperados);
        $check(
            $codigos === [0, 0] && $concurrentes !== null && count($concurrentes) === 2 * $porProceso && $mensajes === $esperados && $rotados > 0,
            'c1 dos procesos, ' . (2 * $porProceso) . ' errores de 20 KB: todas las entradas, íntegras y ninguna perdida (con rotaciones por medio)',
            ($concurrentes === null ? 'una línea no es JSON' : count($concurrentes) . " entradas, {$rotados} rotados") . ($salidas !== '' ? ' · ' . mb_substr($salidas, 0, 200) : '')
        );
        echoTerminal(' ');

        //─── d · Trazas, previas y contexto ────────────────────────────────────────────────────────────
        echoTerminal('[d] Trazas, previas y contexto');
        $directorio = $temporal();
        $excepciones = [];
        for ($i = 0; $i < 2; $i++) {
            $excepciones[] = new \RuntimeException("{$marca} misma traza");
        }
        $primera = new GenericHandler($excepciones[0], $directorio);
        $primera->logging(false);
        $trazas = glob($directorio . '/' . GenericHandler::DIRECTORY_TRACES . '/*.json') ?: [];
        //Un centinela en la traza guardada: si la segunda escritura la reescribiera, desaparecería.
        $centinela = '["zz-centinela"]';
        if (count($trazas) === 1) {
            if (file_put_contents($trazas[0], $centinela) === false) {
                throw new \RuntimeException('No se pudo poner el centinela');
            }
        }
        $segunda = new GenericHandler($excepciones[1], $directorio);
        $segunda->logging(false);
        $trazasDespues = glob($directorio . '/' . GenericHandler::DIRECTORY_TRACES . '/*.json') ?: [];
        $entradasTraza = $lineas($directorio) ?? [];
        $check(
            count($trazas) === 1 && count($trazasDespues) === 1 && file_get_contents($trazasDespues[0]) === $centinela && count($entradasTraza) === 2 && ($entradasTraza[0]['traceFingerprint'] ?? 'a') === ($entradasTraza[1]['traceFingerprint'] ?? 'b'),
            'd1 la misma traza se guarda UNA vez: dos entradas con la misma huella y el archivo no se reescribe',
            count($trazas) . ' → ' . count($trazasDespues)
        );
        //RETORNO-IGNORADO: devuelve la traza verdadera tras el centinela; si no se escribe, d2 falla y lo dice.
        file_put_contents($trazasDespues[0] ?? ($directorio . '/zz'), (string) json_encode(GenericHandler::cleanTrace($excepciones[0]->getTrace()), \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE));
        $reconstruida = (new ErrorLogReader($directorio, $directorio . '/olds'))->findByReference($segunda->reference());
        $check(
            ($reconstruida['entry']['trace'] ?? null) === GenericHandler::cleanTrace($excepciones[1]->getTrace()) && count(GenericHandler::cleanTrace($excepciones[1]->getTrace())) > 0,
            'd2 y el lector la reconstruye entera para cada entrada'
        );

        $conPrevia = new GenericHandler(new \RuntimeException("{$marca} fuera", 0, new \LogicException("{$marca} dentro")), $directorio);
        $conPrevia->logging(false);
        $leida = (new ErrorLogReader($directorio, $directorio . '/olds'))->findByReference($conPrevia->reference());
        $previa = $leida['entry']['previous'][0] ?? [];
        $check(
            ($leida['entry']['message'] ?? null) === "{$marca} fuera" && ($previa['type'] ?? null) === \LogicException::class && ($previa['message'] ?? null) === "{$marca} dentro" && is_array($previa['trace'] ?? null) && $previa['trace'] !== [],
            'd3 una excepción con previa guarda las dos, cada una con su traza'
        );

        $servidorPrevio = $_SERVER;
        try {
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_SERVER['HTTP_HOST'] = 'zz.localhost';
            $_SERVER['REQUEST_URI'] = '/zz/ruta?token=zz-secreto-de-consulta#zz-fragmento';
            $_SERVER['HTTPS'] = 'on';
            GenericHandler::setRouteName('zz-ruta-de-prueba');
            $conContexto = new GenericHandler(new \RuntimeException("{$marca} contexto"), $directorio);
            $conContexto->logging(false);
        } finally {
            $_SERVER = $servidorPrevio;
            GenericHandler::setRouteName(null);
        }
        $crudo = (string) file_get_contents($directorio . '/' . GenericHandler::FILE_ENTRIES);
        $contexto = (new ErrorLogReader($directorio, $directorio . '/olds'))->findByReference($conContexto->reference())['entry']['context'] ?? [];
        $check(
            !str_contains($crudo, 'zz-secreto-de-consulta') && !str_contains($crudo, 'zz-fragmento') && ($contexto['url'] ?? null) === 'https://zz.localhost/zz/ruta' && ($contexto['method'] ?? null) === 'POST' && ($contexto['route'] ?? null) === 'zz-ruta-de-prueba',
            'd4 el contexto lleva método, ruta y dirección SIN la cadena de consulta (ni el fragmento)',
            (string) json_encode($contexto)
        );
        echoTerminal(' ');

        //─── e · El aviso de errores nuevos ────────────────────────────────────────────────────────────
        echoTerminal('[e] El aviso de errores nuevos, solo para root');
        $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
        $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
        if ($base === '') {
            $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
            $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
        }
        $base = rtrim($base, '/');
        $alerta = SystemAlertRegistry::get(NewErrorsAlert::KEY);
        $check($alerta !== null && $alerta->audience() === [UsersModel::TYPE_USER_ROOT] && $alerta->showAsNag() && $alerta->isDismissible(), 'e1 el aviso está registrado, es flotante, ocultable y su audiencia es SOLO root');

        $prefijo = 'zz-agente-errores-' . bin2hex(random_bytes(3));
        $usuarios = [];
        $crear = function (string $sufijo, int $tipo) use ($prefijo, &$usuarios): int {
            $u = new UsersModel();
            $u->username = "{$prefijo}-{$sufijo}";
            $u->email = "{$prefijo}-{$sufijo}@example.com";
            //Nadie entra con contraseña: el token lo fabrica la prueba.
            $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
            $u->firstname = 'Zz';
            $u->secondname = '';
            $u->firstLastname = 'Agente';
            $u->secondLastname = '';
            $u->type = $tipo;
            $u->status = UsersModel::STATUS_USER_ACTIVE;
            $u->failedAttempts = 0;
            $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
            $u->createdAt = new \DateTime();
            $u->modifiedAt = $u->createdAt;
            $u->save();
            $usuarios[$sufijo] = (int) $u->id;
            return (int) $u->id;
        };
        $database = (new BaseModel())->getDatabase();
        try {
            $idRoot = $crear('root', UsersModel::TYPE_USER_ROOT);
            $idGeneral = $crear('general', UsersModel::TYPE_USER_ADMIN_GRAL);
            $antes = time() - 5;
            NewErrorsAlert::markVisited($idRoot, $antes);
            NewErrorsAlert::markVisited($idGeneral, $antes);
            $escritas[] = log_soft_error("{$marca} aviso nuevo");
            NewErrorsAlert::useReaderForTesting(null);
            $deRoot = NewErrorsAlert::state($idRoot, UsersModel::TYPE_USER_ROOT);
            $deGeneral = NewErrorsAlert::state($idGeneral, UsersModel::TYPE_USER_ADMIN_GRAL);
            $check($deRoot['count'] >= 1 && $deRoot['newest'] >= $antes, 'e2 con un error posterior a su última visita, root tiene errores nuevos', (string) json_encode($deRoot));
            $check($deGeneral['count'] === 0, 'e3 el administrador general, nunca: la pestaña no es suya');

            $registros = LogsController::routeName('index', [], true);
            $registros = '/' . ltrim(str_contains($registros, '://') ? (string) parse_url($registros, \PHP_URL_PATH) : $registros, '/');
            $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
            $registros = $prefijoBase !== '' && str_starts_with($registros, $prefijoBase) ? substr($registros, strlen($prefijoBase)) : $registros;
            $pedir = function (string $path, string $jwt, ?string &$cuerpo = null) use ($base): int {
                $handle = \Terminal\TestRun::curlInit();
                \Terminal\TestRun::curlSetoptArray($handle, [
                    CURLOPT_URL => $base . '/' . ltrim($path, '/'),
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_FOLLOWLOCATION => false,
                    CURLOPT_TIMEOUT => 60,
                    CURLOPT_HTTPHEADER => [SessionToken::tokenName() . ": {$jwt}"],
                ]);
                $cuerpo = (string) curl_exec($handle);
                //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
                return (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
            };
            //e5 antes que e4: abrir la pestaña Errores marca la visita y apaga el aviso.
            $inicio = AdminPanelController::routeName(null, [], true);
            $inicio = '/' . ltrim(str_contains($inicio, '://') ? (string) parse_url($inicio, \PHP_URL_PATH) : $inicio, '/');
            $inicio = $prefijoBase !== '' && str_starts_with($inicio, $prefijoBase) ? substr($inicio, strlen($prefijoBase)) : $inicio;
            $pagina = '';
            $estadoInicio = $base !== '' ? $pedir($inicio, UserSessions::issueToken($idRoot), $pagina) : 0;
            $texto = preg_match('/data-key="' . preg_quote(NewErrorsAlert::KEY, '/') . '"[^>]*>\s*<span class="title">([^<]*)<\/span>/', $pagina, $aviso) === 1 ? html_entity_decode($aviso[1]) : null;
            $check(
                $estadoInicio === 200 && $texto !== null && preg_match('/Hay(.*?)errores/u', $texto, $entre) === 1 && preg_match('/\d/', $entre[1]) !== 1,
                'e5 por HTTP, root ve el aviso y su texto no dice cuántos: ningún dígito entre «Hay» y «errores» (ADR 0064)',
                "HTTP {$estadoInicio}, aviso: " . json_encode($texto, \JSON_UNESCAPED_UNICODE)
            );

            $estado = $base !== '' ? $pedir($registros . '?' . http_build_query(['tab' => LogsController::ERRORS_TAB]), UserSessions::issueToken($idRoot)) : 0;
            NewErrorsAlert::useReaderForTesting(null);
            $despues = NewErrorsAlert::state($idRoot, UsersModel::TYPE_USER_ROOT);
            $check($estado === 200 && NewErrorsAlert::lastVisit($idRoot) > $antes && $despues['count'] === 0, 'e4 al abrir la pestaña Errores, su visita se guarda y el aviso se apaga', "HTTP {$estado}, " . json_encode($despues));
        } finally {
            NewErrorsAlert::useReaderForTesting(null);
            $aprobaciones = SystemApprovalsMapper::model();
            foreach ($usuarios as $id) {
                $aprobaciones->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $aprobaciones->delete(new WhereSegment([
                    new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, UsersModel::TABLE),
                    new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
                ]))->execute();
                $perfiles = UserProfileMapper::model();
                $perfiles->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $perfiles->delete(['belongsTo' => $id])->execute();
            }
            $modeloUsuarios = UsersModel::model();
            $modeloUsuarios->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $modeloUsuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
            $consulta = $database->prepare('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username LIKE ?');
            //RETORNO-IGNORADO: PDO va con ERRMODE_EXCEPTION; un fallo lanza y lo cuenta «la prueba corre entera».
            $consulta->execute(["{$prefijo}%"]);
            $check((int) $consulta->fetchColumn() === 0, 'z1 no queda ningún usuario de la prueba');
        }

        //─── f · La retención (G8) ──────────────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[f] Los suaves se guardan ' . GenericHandler::SOFT_RETENTION_DAYS . ' días y las trazas ' . GenericHandler::TRACE_RETENTION_DAYS);
        //Un temporal propio y un «ahora» fijo: los dos lados de cada límite, para que mover un día lo ponga en rojo.
        $retencion = $temporal();
        $ahora = new \DateTime('2026-06-30 12:00:00');
        $suaves = $retencion . '/' . GenericHandler::DIRECTORY_SOFT;
        $trazas = $retencion . '/' . GenericHandler::DIRECTORY_TRACES;
        $huella = fn(string $semilla): string => hash('sha256', $semilla);
        foreach (['20260530', '20260531', '20260615', 'zz-cebo'] as $dia) {
            //RETORNO-IGNORADO: un temporal propio; si no se crea, las comprobaciones de abajo fallan.
            @mkdir("{$suaves}/{$dia}", 0775, true);
            //RETORNO-IGNORADO: un temporal propio; si no se escribe, f1 falla.
            @file_put_contents("{$suaves}/{$dia}/" . $huella($dia), 'ERR-20260101-ZZZZZZ' . "\n");
        }
        //RETORNO-IGNORADO: un temporal propio; si no se crea, f2 falla.
        @mkdir($trazas, 0775, true);
        $base = $ahora->getTimestamp();
        //90, escrito: la prueba fija la política, no la copia de la constante.
        $dias = 90 * 86400;
        $vieja = "{$trazas}/" . $huella('vieja') . '.json';
        $justo = "{$trazas}/" . $huella('justo') . '.json';
        $ceboTraza = "{$trazas}/zz-cebo.json";
        $ceboAlLado = "{$retencion}/zz-cebo.json";
        foreach ([$vieja => $base - $dias - 3600, $justo => $base - $dias + 3600, $ceboTraza => $base - $dias - 3600, $ceboAlLado => $base - $dias - 3600] as $ruta => $cuando) {
            //RETORNO-IGNORADO: un temporal propio; si no se escribe, f2 y f3 fallan.
            @file_put_contents($ruta, '[]');
            //RETORNO-IGNORADO: un temporal propio; si no se fecha, f2 falla.
            @touch($ruta, $cuando);
        }
        $borrados = GenericHandler::pruneStorage($ahora, $retencion);
        $check(!is_dir("{$suaves}/20260530") && is_dir("{$suaves}/20260531") && is_dir("{$suaves}/20260615"),
            'f1 el día de hace 31 días se borra; el de hace 30 y el de hace 15 se quedan');
        $check(!is_file($vieja) && is_file($justo), 'f2 la traza de hace 90 días y una hora se borra; la de 90 días menos una hora se queda');
        $check(is_dir("{$suaves}/zz-cebo") && is_file($ceboTraza) && is_file($ceboAlLado), 'f3 lo que no tiene su forma no se toca: una carpeta que no es un día, una traza sin huella y un archivo al lado');
        $check($borrados === ['soft' => 1, 'traces' => 1], 'f4 y dice lo que borró: un contador y una traza', json_encode($borrados) ?: '');
        $purga = array_values(array_filter(\PiecesPHP\Terminal\CronJobTask::getCronJobs(), fn($tarea) => $tarea->getName() === 'Purgar registros viejos'));
        $check(count($purga) === 1 && $purga[0]->getSchedule() === ['kind' => 'daily', 'time' => '03:30', 'period' => 'P1D'], 'f5 la tarea diaria «Purgar registros viejos» está registrada a las 03:30');
    } catch (\Throwable $e) {
        $check(false, 'la prueba corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        foreach ($temporales as $directorio) {
            foreach (array_merge(glob($directorio . '/*/*/*') ?: [], glob($directorio . '/*/*') ?: [], glob($directorio . '/*') ?: []) as $ruta) {
                //RETORNO-IGNORADO: un temporal propio; lo que no se borre se queda en sys_get_temp_dir().
                is_dir($ruta) ? @rmdir($ruta) : @unlink($ruta);
            }
            //RETORNO-IGNORADO: un temporal propio; lo que no se borre se queda en sys_get_temp_dir().
            @rmdir($directorio);
        }
        echoTerminal(' ');
        echoTerminal('Entradas en el registro de la instalación: ' . implode(', ', array_filter($escritas)));
    }

    return $balance();

})->setDescription('Errores graves y suaves (ADR 0060): fuera de local un aviso no aborta y queda como suave con su referencia; en local aborta; log_soft_error() nunca aborta; un suave repetido el mismo día es una entrada con su cuenta; dos procesos a la vez no pierden entradas; cada traza se guarda una vez y se reconstruye; se guardan las previas y el contexto sin la cadena de consulta; el aviso de errores nuevos es solo de root y se apaga al abrir la pestaña; y la retención borra los suaves de más de 30 días y las trazas de más de 90, y nada más. Por HTTP en el aviso. Crea y borra dos usuarios.')->setEffects([CliActions::EFFECT_DATABASE])->register();
