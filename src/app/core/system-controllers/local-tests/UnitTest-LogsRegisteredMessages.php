<?php

//H363, H356 y H367: los tipos de acción que declara el proyecto (con nombre y texto único) y anotar sin romper la acción.
//Escribe en el registro de acciones (y borra lo suyo) y una entrada en logs/error.log.jsonl, que poda bin/con-marca.
//@areas src/app/classes/EventsLog/ src/app/classes/API/

use EventsLog\Mappers\LogsMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\CustomErrorsHandlers\GenericHandler;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/logs-registered-messages', function ($args) {

    echoTerminal("\e[33m[TEST:logs-registered-messages] Tipos de acción propios y addLogSafe\e[39m");
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name, string $detail = '') use (&$passed, &$failed): void {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}" . ($detail !== '' ? " — {$detail}" : ''));
        }
    };

    /** Lanza o no, y qué clase. */
    $throws = function (callable $call): string {
        try {
            $call();
        } catch (\Throwable $e) {
            return get_class($e);
        }
        return '';
    };

    /** Código de un archivo sin comentarios: mencionar una forma en un docblock no es usarla. */
    $code = function (string $relative): string {
        $source = (string) file_get_contents(basepath($relative));
        $clean = '';
        foreach (token_get_all($source) as $token) {
            if (is_array($token)) {
                if ($token[0] !== T_COMMENT && $token[0] !== T_DOC_COMMENT) {
                    $clean .= $token[1];
                }
            } else {
                $clean .= $token;
            }
        }
        return $clean;
    };

    $db = (new BaseModel())->getDatabase();
    $written = [];

    try {

        //──── 1. Declarar y anotar un tipo propio ─────────────────────────────────────────────
        echoTerminal('[1] Un tipo propio se declara, se anota y sale entre los tipos');
        $own = 'ZZ_LOGS_OWN_' . strtoupper(bin2hex(random_bytes(3)));
        $frameworkCount = count(LogsMapper::MESSAGES);
        LogsMapper::registerMessage($own, 'zz-logs-registered %who% hizo algo');
        $check(array_key_exists($own, LogsMapper::messages()), '1a messages() trae el tipo declarado');
        $check(count(LogsMapper::MESSAGES) === $frameworkCount && !array_key_exists($own, LogsMapper::MESSAGES), '1b MESSAGES no cambia');

        $safe = LogsMapper::addLogSafe($own, ['%who%' => 'zz-agente'], 'id', '-1', 'zz_logs_registered');
        $check($safe instanceof LogsMapper && $safe->id !== null, '1c addLogSafe() anota un tipo declarado y devuelve el mapper');
        if ($safe instanceof LogsMapper && $safe->id !== null) {
            $written[] = (int) $safe->id;
        }
        $plain = LogsMapper::addLog($own, ['%who%' => 'zz-agente'], 'id', '-1', 'zz_logs_registered');
        $check($plain->id !== null, '1d addLog() también acepta un tipo declarado');
        if ($plain->id !== null) {
            $written[] = (int) $plain->id;
        }
        if ($db !== null && count($written) > 0) {
            $statement = $db->prepare('SELECT COUNT(*) FROM `' . LogsMapper::TABLE . '` WHERE textMessage = ? AND id IN (' . implode(',', array_map('intval', $written)) . ')');
            $statement->execute(['zz-logs-registered %who% hizo algo']);
            $check((int) $statement->fetchColumn() === 2, '1e las dos filas guardan el texto del tipo declarado');
        }
        echoTerminal(' ');

        //──── 2. Lo que no se puede declarar ──────────────────────────────────────────────────
        echoTerminal('[2] Un código repetido o con otra forma lanza');
        $check($throws(fn() => LogsMapper::registerMessage($own, 'otra vez')) === \LogicException::class, '2a el mismo código declarado otra vez lanza LogicException');
        $check($throws(fn() => LogsMapper::registerMessage(LogsMapper::MSG_GENERIC, 'pisar')) === \LogicException::class, '2b un código del framework lanza LogicException');
        $check($throws(fn() => LogsMapper::registerMessage('zz-minusculas', 'x')) === \InvalidArgumentException::class, '2c un código con minúsculas o guiones lanza InvalidArgumentException');
        $check($throws(fn() => LogsMapper::registerMessage('ZZ LOGS', 'x')) === \InvalidArgumentException::class, '2d un código con espacios lanza InvalidArgumentException');
        $check($throws(fn() => LogsMapper::registerMessage('ZZ_LOGS_EMPTY', '  ')) === \InvalidArgumentException::class, '2e un texto vacío lanza InvalidArgumentException');
        $check(!array_key_exists('ZZ_LOGS_EMPTY', LogsMapper::messages()), '2f lo rechazado no queda declarado');
        //H367: el tipo de una fila se reconoce por su texto; dos tipos con el mismo texto se confundirían.
        $check($throws(fn() => LogsMapper::registerMessage('ZZ_LOGS_SAME_OWN', 'zz-logs-registered %who% hizo algo')) === \LogicException::class, '2g un texto que ya tiene un tipo declarado lanza LogicException');
        $check($throws(fn() => LogsMapper::registerMessage('ZZ_LOGS_SAME_CORE', LogsMapper::MESSAGES[LogsMapper::MSG_UPDATE_PROFILE])) === \LogicException::class, '2h un texto que ya tiene un tipo del framework lanza LogicException');
        $check(!array_key_exists('ZZ_LOGS_SAME_OWN', LogsMapper::messages()) && !array_key_exists('ZZ_LOGS_SAME_CORE', LogsMapper::messages()), '2i los de texto repetido no quedan declarados');
        echoTerminal(' ');

        //──── 2bis. El nombre de un tipo declarado ────────────────────────────────────────────
        echoTerminal('[2bis] Un tipo declarado tiene nombre en el filtro: su texto, con «…» en cada hueco');
        $labels = LogsMapper::messageLabels();
        $check(($labels[$own] ?? null) === 'zz-logs-registered … hizo algo', '2bis-a messageLabels() nombra al declarado con su texto y sin %huecos%', 'nombre: ' . var_export($labels[$own] ?? null, true));
        $check(($labels[LogsMapper::MSG_UPDATE_PROFILE] ?? null) === __(LogsMapper::LANG_GROUP, 'Perfil actualizado'), '2bis-b los del framework conservan su nombre');
        echoTerminal(' ');

        //──── 3. addLog sigue lanzando; addLogSafe no ─────────────────────────────────────────
        echoTerminal('[3] Si anotar falla, addLog() lanza y addLogSafe() lo deja en el registro de errores');
        $missing = 'ZZ_LOGS_MISSING_' . strtoupper(bin2hex(random_bytes(3)));
        $check($throws(fn() => LogsMapper::addLog($missing)) !== '', '3a addLog() con un tipo que no existe sigue lanzando');

        $entriesFile = rtrim((string) constant('LOG_ERRORS_PATH'), '/\\') . '/' . GenericHandler::FILE_ENTRIES;
        $countEntries = function () use ($entriesFile, $missing): int {
            $content = is_file($entriesFile) ? (string) file_get_contents($entriesFile) : '';
            return substr_count($content, $missing);
        };
        $before = $countEntries();
        $result = 'sin llamar';
        $error = $throws(function () use ($missing, &$result) {
            $result = LogsMapper::addLogSafe($missing);
        });
        $check($error === '' && $result === null, '3b addLogSafe() con un tipo que no existe no lanza y devuelve null', $error !== '' ? "lanzó {$error}" : 'devolvió ' . gettype($result));
        $check($countEntries() === $before + 1, '3c y deja su entrada en el registro de errores', "entradas con el código: antes {$before}, después " . $countEntries());
        echoTerminal(' ');

        //──── 4. Quien lee los tipos lee también los declarados ───────────────────────────────
        echoTerminal('[4] Los lectores de tipos y las acciones de la API');
        foreach (['app/classes/EventsLog/Controllers/LogsController.php', 'app/classes/PiecesPHP/UserSystem/Controllers/UserTokensController.php'] as $reader) {
            $source = $code($reader);
            $check(!str_contains($source, 'LogsMapper::MESSAGES') && str_contains($source, 'LogsMapper::messages()'), "4a {$reader} lee messages(), no MESSAGES");
        }
        $api = $code('app/classes/API/Controllers/APIController.php');
        $check(substr_count($api, 'LogsMapper::addLog(') === 0, '4b la API no usa addLog(): un fallo al anotar convertía en 500 una acción hecha', substr_count($api, 'LogsMapper::addLog(') . ' llamada(s) a addLog()');
        foreach (['MSG_UPDATE_PROFILE,', 'MSG_UPDATE_PROFILE_IMAGE,', 'MSG_REQUEST_PASSWORD_RECOVERY,', 'MSG_PASSWORD_RECOVERY_BY_CODE,'] as $type) {
            $check(str_contains($api, 'LogsMapper::addLogSafe(LogsMapper::' . $type), '4c la API anota ' . rtrim($type, ',') . ' con addLogSafe()');
        }
        echoTerminal(' ');

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    } finally {
        if ($db !== null && count($written) > 0) {
            $db->exec('DELETE FROM `' . LogsMapper::TABLE . '` WHERE id IN (' . implode(',', array_map('intval', $written)) . ')');
            echoTerminal('   filas de prueba del registro borradas: ' . implode(', ', $written));
        }
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('H363 y H356: los tipos de acción propios del registro y addLogSafe.')->setEffects([CliActions::EFFECT_DATABASE, CliActions::EFFECT_FILES])->register();
