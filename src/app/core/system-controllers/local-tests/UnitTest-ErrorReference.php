<?php

//P56: cada error registrado lleva un código de referencia, y fuera de local la respuesta no enseña nada de la excepción.
//Escribe entradas de prueba en logs/error.log.json y error.plain.log (el log es de la máquina): se quedan y se enumeran.

use PiecesPHP\Core\CustomErrorsHandlers\CustomSlimErrorHandler;
use PiecesPHP\Core\CustomErrorsHandlers\GenericHandler;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Terminal\CliActions;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;

CliActions::make('unit-tests:core/error-reference', function ($args) {

    echoTerminal("\e[33m[TEST:ErrorReference] El código de referencia y la respuesta sin detalle fuera de local\e[39m");
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

    $pattern = '/^ERR-\d{8}-[0-9A-F]{6}$/';
    $written = [];
    $request = function (): RequestRoute {
        return new RequestRoute('GET', (new UriFactory())->createUri('http://localhost/zz-error-reference'), new Headers(), [], [], (new StreamFactory())->createStream(''));
    };
    $logDir = rtrim((string) constant('LOG_ERRORS_PATH'), '/\\');

    try {
        //─── a · La referencia ──────────────────────────────────────────────────────────────────────────
        echoTerminal('[a] La referencia');
        $one = new GenericHandler(new \RuntimeException('zz-error-reference a1'));
        $two = new GenericHandler(new \RuntimeException('zz-error-reference a2'));
        $check(preg_match($pattern, $one->reference()) === 1, 'a1 GenericHandler::reference() tiene la forma ERR-AAAAMMDD-XXXXXX', $one->reference());
        $check($one->reference() !== $two->reference(), 'a2 dos handlers seguidos → referencias distintas');

        $reference = log_exception(new \RuntimeException('zz-error-reference a3'));
        $written[] = $reference;
        $json = json_decode((string) file_get_contents("{$logDir}/error.log.json"), true);
        $today = date('d-m-Y');
        $entries = is_array($json) && isset($json[$today]) && is_array($json[$today]) ? $json[$today] : [];
        $inJSON = count(array_filter($entries, fn($e) => is_array($e) && ($e['reference'] ?? null) === $reference && ($e['message'] ?? null) === 'zz-error-reference a3')) === 1;
        $plain = (string) file_get_contents("{$logDir}/error.plain.log");
        $check(preg_match($pattern, $reference) === 1, 'a3 log_exception() devuelve una referencia con esa forma', $reference);
        $check($inJSON, 'a4 la referencia está en error.log.json, en la entrada del día');
        $check(str_contains($plain, "[ref {$reference}]") && str_contains($plain, 'zz-error-reference a3'), 'a5 y en error.plain.log');
        echoTerminal(' ');

        //─── b · La respuesta ───────────────────────────────────────────────────────────────────────────
        echoTerminal('[b] La respuesta');
        $handler = new CustomSlimErrorHandler(new \RuntimeException('zz-secreto-interno'), 'zz-error-reference');
        $written[] = $handler->reference();
        $production = $handler->getResponse($request(), false);
        $body = (string) $production->getBody();
        $values = json_decode($body, true);
        $keys = is_array($values) ? array_keys($values) : [];
        sort($keys);
        $check(
            $production->getStatusCode() === 500 && $keys === ['message', 'reference', 'success'] && !str_contains($body, 'zz-secreto-interno') && ($values['reference'] ?? null) === $handler->reference(),
            'b1 fuera de local: 500, solo success/message/reference, sin el mensaje interno, con la referencia del log',
            mb_substr($body, 0, 240)
        );
        $local = $handler->getResponse($request(), true);
        $localBody = (string) $local->getBody();
        $localValues = json_decode($localBody, true);
        $check(
            str_contains($localBody, 'zz-secreto-interno') && is_array($localValues) && isset($localValues['detail']) && ($localValues['reference'] ?? null) === $handler->reference(),
            'b2 en local: el detalle y el mensaje interno siguen, y también la referencia'
        );
        //b3 (HTML de producción): desde la terminal la respuesta es SIEMPRE JSON (TerminalData::isTerminal()); se
        //prueba con curl en la prueba real de la ronda, no aquí.
        echoTerminal("   · b3 omitida: la terminal fuerza JSON en getResponse(); la rama HTML se prueba por HTTP");
        echoTerminal(' ');

        //─── c · Un error interno real de un controlador ────────────────────────────────────────────────
        //Un username más largo que su columna (SQLSTATE 22001), en una transacción que se revierte.
        echoTerminal('[c] Un error interno de un controlador responde con su referencia');
        $pdo = \PiecesPHP\UserSystem\ORM\UsersModel::model()::getDb(\PiecesPHP\Core\Config::app_db('default')['db']);
        $previousUser = get_config('current_user');
        $previousStored = get_config('pcsphp_current_user_stored');
        $pdo->beginTransaction();
        try {
            $root = \PiecesPHP\UserSystem\ORM\UsersModel::model();
            $root->resetAll();
            $root->select()->where(['type' => \PiecesPHP\UserSystem\ORM\UsersModel::TYPE_USER_ROOT])->execute();
            $rootRows = (array) $root->result();
            set_config('current_user', (object) ['id' => isset($rootRows[0]->id) ? (int) $rootRows[0]->id : 0]);
            set_config('pcsphp_current_user_stored', null);
            $username = 'zz-p56-' . str_repeat('x', 300);
            $signup = (new RequestRoute('POST', (new UriFactory())->createUri('http://localhost/zz-error-reference'), new Headers(), [], [], (new StreamFactory())->createStream('')))->withParsedBody([
                'username' => $username,
                'email' => 'zz-p56@example.com',
                'password' => 'zz-Clave-1',
                'password2' => 'zz-Clave-1',
                'firstname' => 'Zz',
                'secondname' => '',
                'first_lastname' => 'Prueba',
                'second_lastname' => '',
                'type' => (string) \PiecesPHP\UserSystem\ORM\UsersModel::TYPE_USER_GENERAL,
                'status' => (string) \PiecesPHP\UserSystem\ORM\UsersModel::STATUS_USER_ACTIVE,
                'organization' => (string) \Organizations\Mappers\OrganizationMapper::INITIAL_ID_GLOBAL,
            ]);
            $body = (string) (new \PiecesPHP\UserSystem\Controllers\UsersController())->register($signup, new \PiecesPHP\Core\Routing\ResponseRoute())->getBody();
            $found = preg_match('/ERR-\d{8}-[0-9A-F]{6}/', $body, $match) === 1 ? $match[0] : null;
            if ($found !== null) {
                $written[] = $found;
            }
            $plainLog = (string) file_get_contents("{$logDir}/error.plain.log");
            $check(
                $found !== null && str_contains($body, 'Ocurri') && !str_contains($body, 'SQLSTATE') && !str_contains($body, 'zz-p56-xxx') && str_contains($plainLog, "[ref {$found}]") && str_contains($plainLog, 'SQLSTATE'),
                'c1 el alta con un username demasiado largo responde el mensaje genérico con una referencia que está en el log (con el SQLSTATE)',
                mb_substr($body, 0, 200)
            );
        } finally {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            set_config('current_user', $previousUser);
            set_config('pcsphp_current_user_stored', $previousStored);
        }
        echoTerminal(' ');

        //─── d · Un error de parámetro dice qué parámetro falla ─────────────────────────────────────────
        echoTerminal('[d] Un error de parámetro sigue diciendo qué parámetro falla');
        $previousUserD = get_config('current_user');
        $previousStoredD = get_config('pcsphp_current_user_stored');
        try {
            $rootD = \PiecesPHP\UserSystem\ORM\UsersModel::model();
            $rootD->resetAll();
            $rootD->select()->where(['type' => \PiecesPHP\UserSystem\ORM\UsersModel::TYPE_USER_ROOT])->execute();
            $rootRowsD = (array) $rootD->result();
            set_config('current_user', (object) ['id' => isset($rootRowsD[0]->id) ? (int) $rootRowsD[0]->id : 0]);
            set_config('pcsphp_current_user_stored', null);
            //Sin username: la validación de Parameters falla antes de tocar la base.
            $missing = (new RequestRoute('POST', (new UriFactory())->createUri('http://localhost/zz-error-reference'), new Headers(), [], [], (new StreamFactory())->createStream('')))->withParsedBody([
                'email' => 'zz-p56@example.com',
                'password' => 'zz-Clave-1',
                'password2' => 'zz-Clave-1',
                'firstname' => 'Zz',
                'first_lastname' => 'Prueba',
                'type' => (string) \PiecesPHP\UserSystem\ORM\UsersModel::TYPE_USER_GENERAL,
                'status' => (string) \PiecesPHP\UserSystem\ORM\UsersModel::STATUS_USER_ACTIVE,
            ]);
            $bodyD = (string) (new \PiecesPHP\UserSystem\Controllers\UsersController())->register($missing, new \PiecesPHP\Core\Routing\ResponseRoute())->getBody();
            $check(
                str_contains($bodyD, 'username') && !str_contains($bodyD, 'Ocurri') && preg_match('/ERR-\d{8}-[0-9A-F]{6}/', $bodyD) !== 1,
                'd1 un alta sin username responde qué parámetro falta, no el genérico',
                mb_substr($bodyD, 0, 200)
            );
        } finally {
            set_config('current_user', $previousUserD);
            set_config('pcsphp_current_user_stored', $previousStoredD);
        }
        echoTerminal(' ');

        //Un token con forma no válida no llega al throw: lo para la validación del parámetro.
        $badToken = (new RequestRoute('POST', (new UriFactory())->createUri('http://localhost/zz-error-reference'), new Headers(), [], [], (new StreamFactory())->createStream('')))->withParsedBody([
            'email' => 'zz-p56@example.com',
            'subject' => 'zz-p56',
            'message' => 'zz-p56',
            'token' => 'zz-token-con-forma-no-valida',
        ]);
        $bodyD2 = (string) (new \PiecesPHP\Tokens\Controllers\GenericTokenController())->commentary($badToken, new \PiecesPHP\Core\Routing\ResponseRoute())->getBody();
        $check(
            str_contains($bodyD2, 'token') && !str_contains($bodyD2, 'Ocurri') && preg_match('/ERR-\d{8}-[0-9A-F]{6}/', $bodyD2) !== 1,
            'd2 un token con forma no válida responde qué parámetro falla, no el genérico',
            mb_substr($bodyD2, 0, 200)
        );
        echoTerminal(' ');

        //─── e · Un mensaje para el usuario sale como SafeException ─────────────────────────────────────
        echoTerminal('[e] Un mensaje para el usuario sale tal cual');
        $commentary = (new RequestRoute('POST', (new UriFactory())->createUri('http://localhost/zz-error-reference'), new Headers(), [], [], (new StreamFactory())->createStream('')))->withParsedBody([
            'email' => 'zz-p56@example.com',
            'subject' => 'zz-p56',
            'message' => 'zz-p56',
            'token' => str_repeat('0', 32),
        ]);
        $bodyE = (string) (new \PiecesPHP\Tokens\Controllers\GenericTokenController())->commentary($commentary, new \PiecesPHP\Core\Routing\ResponseRoute())->getBody();
        $expected = __(\PiecesPHP\Tokens\Controllers\GenericTokenController::LANG_GROUP, 'El recurso al que intenta acceder ha expirado o ya ha sido utilizado.');
        $check(
            str_contains($bodyE, json_encode($expected, \JSON_UNESCAPED_SLASHES) !== false ? trim((string) json_encode($expected, \JSON_UNESCAPED_SLASHES), '"') : $expected) && preg_match('/ERR-\d{8}-[0-9A-F]{6}/', $bodyE) !== 1,
            'e1 un token inexistente en commentary responde el texto para el usuario, sin referencia',
            mb_substr($bodyE, 0, 200)
        );
        echoTerminal(' ');

        //─── z · Lo que queda en el log ─────────────────────────────────────────────────────────────────
        echoTerminal('[z] Entradas de prueba que quedan en el log de la máquina');
        foreach (array_merge([$one->reference(), $two->reference()], $written) as $ref) {
            echoTerminal("   · {$ref}");
        }
        $check(count($written) === 3, 'z1 enumeradas (a1 y a2 no escriben: sin logging())');

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('P56: los errores llevan un código de referencia y fuera de local no enseñan el detalle.')->setEffects([CliActions::EFFECT_FILES])->register();
