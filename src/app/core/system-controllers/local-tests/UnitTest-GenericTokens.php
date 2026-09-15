<?php

//Tokens genéricos: selector opaco, solo se borra el suyo, claves derivadas de app_key y el aviso de la de relleno.
//Escribe en pcsphp_tokens: sus filas llevan un token zz-prueba- y se borran siempre en el finally.

use App\Controller\GenericTokenController;
use App\Controller\TokenController;
use App\Model\TokenModel;
use PiecesPHP\Core\BaseHashEncryption;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\BaseToken;
use PiecesPHP\Core\Config;
use PiecesPHP\Terminal\CliActions;
use Terminal\Tasks\GenerateAppKeyTask;

$cliTaskName = 'unit-tests';
$cliTaskFlag = 'core/generic-tokens';
$cliTaskDescription = 'Tokens genéricos: selector opaco, borrado solo del suyo, claves derivadas de app_key y el aviso de la de relleno';

CliActions::make("{$cliTaskName}:{$cliTaskFlag}", function ($args) {

    echoTerminal('[TEST:GenericTokens] Iniciando suite...', true, "\r\n", '33');
    echoTerminal('');

    $passed = 0;
    $failed = 0;

    $check = function (bool $condition, string $name, ?string $detail = null) use (&$passed, &$failed) {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}");
        }
        if ($detail !== null) {
            echoTerminal("      - {$detail}");
        }
        return $condition;
    };

    $database = (new BaseModel())->getDatabase();
    $tabla = (new TokenModel())->getTable();
    $creadas = [];
    //Nunca imprime tokens: las filas de la suite llevan JWT de prueba o un texto zz-prueba-.
    $inserta = function (string $token, string $tipo, string $selector) use ($database, $tabla, &$creadas): int {
        $insertar = $database->prepare("INSERT INTO {$tabla} (token, type, selector) VALUES (?, ?, ?)");
        $insertar->execute([$token, $tipo, $selector]);
        $leer = $database->prepare("SELECT id FROM {$tabla} WHERE selector = ?");
        $leer->execute([$selector]);
        $id = (int) $leer->fetchColumn();
        $creadas[] = $id;
        return $id;
    };
    $existe = function (int $id) use ($database, $tabla): bool {
        $leer = $database->prepare("SELECT COUNT(*) FROM {$tabla} WHERE id = ?");
        $leer->execute([$id]);
        return (int) $leer->fetchColumn() === 1;
    };

    try {

        //──── 1. Selectores ────────────────────────────────────────────────────────────────
        echoTerminal('[1/4] El selector: 16 bytes aleatorios en hexadecimal, y un id no lo es');

        $selectores = [];
        for ($i = 0; $i < 1000; $i++) {
            $selectores[] = GenericTokenController::newSelector();
        }
        $validos = count(array_filter($selectores, fn (string $s): bool => GenericTokenController::isSelector($s)));
        $check(count(array_unique($selectores)) === 1000 && $validos === 1000, 'DISCRIMINANTE: 1000 selectores, 0 repetidos, todos de 32 hexadecimales',
            (1000 - count(array_unique($selectores))) . " repetidos · {$validos} válidos");
        $check(!GenericTokenController::isSelector(BaseHashEncryption::encrypt('42', GenericTokenController::class)) && !GenericTokenController::isSelector('42')
            && GenericTokenController::tokenBySelector('42') === null, 'un id, cifrado o no, no es un selector ni alcanza ninguna fila');
        echoTerminal(' ');

        //──── 2. Solo se borra el suyo ─────────────────────────────────────────────────────
        echoTerminal('[2/4] El controlador solo alcanza y borra tokens de su tipo, y solo el suyo');

        $selectorOtro = GenericTokenController::newSelector();
        $idOtro = $inserta('zz-prueba-token-de-recuperacion', TokenController::TOKEN_PASSWORD_RECOVERY, $selectorOtro);
        $check(GenericTokenController::tokenBySelector($selectorOtro) === null, 'DISCRIMINANTE: un token de otro tipo no se alcanza, aunque se conozca su selector');
        $filaFalsa = (object) ['id' => $idOtro, 'type' => TokenController::TOKEN_PASSWORD_RECOVERY, 'selector' => $selectorOtro];
        $check(GenericTokenController::deleteOwnToken($filaFalsa) === false && $existe($idOtro), 'DISCRIMINANTE: ni pasándole su fila, deleteOwnToken() borra uno de otro tipo: sigue ahí');

        $selectorCaducado = GenericTokenController::newSelector();
        $idCaducado = $inserta(GenericTokenController::createToken(['zz' => 'prueba'], -1), TokenController::TOKEN_GENERIC_CONTROLLER, $selectorCaducado);
        $selectorVigente = GenericTokenController::newSelector();
        $idVigente = $inserta(GenericTokenController::createToken(['zz' => 'prueba'], 60), TokenController::TOKEN_GENERIC_CONTROLLER, $selectorVigente);
        $filaCaducada = GenericTokenController::tokenBySelector($selectorCaducado);
        $check($filaCaducada !== null && (int) $filaCaducada->id === $idCaducado, 'el suyo se alcanza por su selector');
        $check($filaCaducada !== null && BaseToken::isExpire((string) $filaCaducada->token, GenericTokenController::jwtKey(), null), 'y está caducado');
        $check(GenericTokenController::deleteOwnToken($filaCaducada) && !$existe($idCaducado) && $existe($idVigente) && $existe($idOtro),
            'DISCRIMINANTE: el caducado de su tipo borra solo el suyo; el vigente y el de otro tipo siguen');
        $check(GenericTokenController::tokenBySelector(str_repeat('0', 32)) === null && GenericTokenController::deleteOwnToken(null) === false,
            'un selector que no existe no alcanza nada, y sin fila no se borra nada');
        echoTerminal(' ');

        //──── 3. Claves derivadas ──────────────────────────────────────────────────────────
        echoTerminal('[3/4] Las claves de los JWT se derivan de app_key, una por uso, sin literales');

        $claveA = str_repeat('a', 64);
        $claveB = str_repeat('b', 64);
        $check(GenericTokenController::jwtKey($claveA) !== TokenModel::baseJWTKey($claveA), 'DISCRIMINANTE: cada uso tiene su clave, con la misma app_key');
        $check(GenericTokenController::jwtKey($claveA) !== GenericTokenController::jwtKey($claveB) && TokenModel::baseJWTKey($claveA) !== TokenModel::baseJWTKey($claveB),
            'DISCRIMINANTE: y las dos cambian al cambiar app_key');
        $check(GenericTokenController::jwtKey() === GenericTokenController::jwtKey(Config::app_key()) && preg_match('/^[0-9a-f]{64}$/', GenericTokenController::jwtKey()) === 1,
            'sin argumento se deriva de la app_key de la app: 64 hexadecimales');
        echoTerminal(' ');

        //──── 4. El aviso de la app_key de relleno ─────────────────────────────────────────
        echoTerminal('[4/4] El aviso salta con la app_key vacía o de relleno, y no con una buena');

        $check(Config::app_key_is_placeholder('') && Config::app_key_is_placeholder('TODO:secret') && Config::app_key_is_placeholder('   '),
            'DISCRIMINANTE: salta con la vacía y con la de relleno');
        $buena = GenerateAppKeyTask::generate();
        $check(!Config::app_key_is_placeholder($buena), 'no salta con una buena');
        $check(preg_match('/^[0-9a-f]{64}$/', $buena) === 1 && $buena !== GenerateAppKeyTask::generate(), 'generate-app-key: 64 hexadecimales, distinta cada vez');
        echoTerminal(' ');

    } catch (\Throwable $e) {
        $check(false, 'la suite se ejecuta sin excepciones', 'EXCEPCIÓN: ' . mb_substr($e->getMessage(), 0, 200));
    } finally {
        $borrar = $database->prepare("DELETE FROM {$tabla} WHERE id = ?");
        foreach ($creadas as $id) {
            $borrar->execute([$id]);
        }
    }
    $quedan = count(array_filter($creadas, fn (int $id): bool => $existe($id)));
    $check($quedan === 0, 'las filas de la suite se borran al acabar', count($creadas) . " creadas · {$quedan} quedan");

    //──── Balance ───────────────────────────────────────────────────────────────────────────────
    echoTerminal(str_repeat('=', 80));
    echoTerminal(" BALANCE FINAL: {$passed}/" . ($passed + $failed) . " PASADAS ");
    echoTerminal(str_repeat('=', 80));
    echoTerminal('');
    echoTerminal('[TEST:GenericTokens] Suite finalizada.', true, "\r\n", $failed === 0 ? '32' : '31');
    echoTerminal('');

    return [
        'success' => $failed === 0,
        'message' => $failed === 0
            ? "Los tokens genéricos van por selector y solo se borra el suyo ({$passed} comprobaciones)."
            : "{$failed} comprobaciones fallaron.",
    ];

})->setDescription($cliTaskDescription)->setEffects([CliActions::EFFECT_DATABASE])->register();
