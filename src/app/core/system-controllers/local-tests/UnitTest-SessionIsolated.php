<?php

//P46: el contrato de las sesiones AISLADAS —SessionTokenIsolated y su pareja del navegador,
//PiecesPHPGenericHandlerSession—, que es el mecanismo para sesiones distintas de la de usuarios. Solo lee.

use PiecesPHP\Core\BaseToken;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Core\SessionTokenIsolated;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/session-isolated', function ($args) {

    echoTerminal("\e[33m[TEST:SessionIsolated] Las sesiones aisladas: aislamiento, nombre, carga y fecha mínima\e[39m");
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

    //Todo lo que se toca es del proceso: se apunta para reponerlo.
    $nombreA = 'zzAislada1';
    $nombreB = 'zzAislada2';
    $cabeceraDe = fn(string $nombre): string => 'HTTP_' . mb_strtoupper($nombre);
    $previos = [];
    foreach ([$cabeceraDe($nombreA), $cabeceraDe($nombreB), 'HTTP_' . mb_strtoupper(SessionToken::tokenName()), 'REMOTE_ADDR'] as $clave) {
        $previos[$clave] = $_SERVER[$clave] ?? null;
    }
    $cookiePrevia = $_COOKIE[$nombreA] ?? null;

    $poner = function (string $cabecera, ?string $valor): void {
        if ($valor === null) {
            unset($_SERVER[$cabecera]);
        } else {
            $_SERVER[$cabecera] = $valor;
        }
    };

    try {

        $a = new SessionTokenIsolated($nombreA);
        $b = new SessionTokenIsolated($nombreB);
        $tokenA = $a->generateToken(['zz' => 'de la A'], 60, false);
        $tokenB = $b->generateToken(['zz' => 'de la B'], 60, false);

        //─── a · El aislamiento, que es su razón de ser ──────────────────────────────────────────────────
        echoTerminal('[a] Dos sesiones aisladas no se ven entre sí');
        $poner($cabeceraDe($nombreA), $tokenA);
        $poner($cabeceraDe($nombreB), null);
        $check($a->isActiveSession() === true, 'a1 con su token en su cabecera, la A tiene sesión');
        $check($b->isActiveSession() === false, 'a1 y la B no: no hay nada en la suya');
        $check($a->getJWTReceived() === $tokenA && $b->getJWTReceived() === '', 'a2 cada una recibe por SU nombre y no ve el de la otra');

        $poner($cabeceraDe($nombreB), $tokenB);
        $datosA = $a->getJWTData();
        $datosB = $b->getJWTData();
        $check(is_object($datosA) && ($datosA->zz ?? '') === 'de la A' && is_object($datosB) && ($datosB->zz ?? '') === 'de la B', 'a3 con las dos puestas, cada una lee LO SUYO', (string) json_encode([$datosA, $datosB]));
        echoTerminal(' ');

        //─── b · El nombre manda, y viene por argumento ──────────────────────────────────────────────────
        echoTerminal('[b] El nombre lo recibe al construirse, y vale para la cabecera y para la cookie');
        $poner($cabeceraDe($nombreA), null);
        unset($_COOKIE[$nombreA]);
        $check($a->isActiveSession() === false, 'b1 sin cabecera ni cookie con su nombre, no hay sesión');
        $_COOKIE[$nombreA] = $tokenA;
        $check($a->getJWTReceived() === $tokenA && $a->isActiveSession() === true, 'b2 por la COOKIE con su nombre, sí');
        unset($_COOKIE[$nombreA]);
        $poner($cabeceraDe($nombreA), $tokenA);
        $check($a->getJWTReceived() === $tokenA, 'b3 y por la CABECERA con su nombre, también');
        echoTerminal(' ');

        //─── c · La carga es libre ───────────────────────────────────────────────────────────────────────
        echoTerminal('[c] La carga es libre: lo contrario del contrato de la sesión de usuarios');
        $carga = ['lo que sea' => [1, 2, 3], 'sin id' => true, 'texto' => 'zz'];
        $tokenLibre = $a->generateToken($carga, 60, false);
        $poner($cabeceraDe($nombreA), $tokenLibre);
        $leido = $a->getJWTData();
        $check(is_object($leido) && ($leido->{'sin id'} ?? null) === true && ($leido->texto ?? '') === 'zz', 'c1 devuelve los datos que se le dieron, sin exigir id ni type', (string) json_encode($leido));
        $check($a->isActiveSession() === true, 'c2 y la sesión vale igual: no hay campos obligatorios');
        echoTerminal(' ');

        //─── d · La fecha mínima, que es SUYA y distinta ─────────────────────────────────────────────────
        echoTerminal('[d] Su fecha mínima por defecto no es la de la sesión de usuarios');
        $check(SessionTokenIsolated::DEFAULT_MINIMUM_DATE_CREATED === '2024-12-13', 'd1 la suya es 2024-12-13', SessionTokenIsolated::DEFAULT_MINIMUM_DATE_CREATED);
        $check(SessionToken::DEFAULT_MINIMUM_DATE_CREATED === '1990-01-01', 'd1 y la de la sesión de usuarios es 1990-01-01', SessionToken::DEFAULT_MINIMUM_DATE_CREATED);
        //Un token con fecha de creación ANTERIOR a la suya: se fabrica con BaseToken, que es lo que ella usa.
        $iatViejo = (int) (new \DateTime('2024-01-15'))->format('U');
        $tokenAntiguo = BaseToken::setToken(['zz' => 'antiguo'], Config::app_key(), $iatViejo, time() + 3600, false);
        $poner($cabeceraDe($nombreA), $tokenAntiguo);
        $check(BaseToken::check($tokenAntiguo, Config::app_key()) === true, 'd2 el token antiguo está bien firmado y sin caducar');
        $check($a->isActiveSession() === false, 'd2 y AUN ASÍ no vale: su fecha de creación es anterior a la mínima');
        echoTerminal(' ');

        //─── e · Firma, caducidad y aud, una de cada ─────────────────────────────────────────────────────
        echoTerminal('[e] Firma, caducidad y aud');
        $partes = explode('.', $tokenA);
        $poner($cabeceraDe($nombreA), $partes[0] . '.' . $partes[1] . '.' . strrev($partes[2]));
        $check($a->isActiveSession() === false, 'e1 con la firma del revés, no vale');
        //La duración va en MINUTOS, no en segundos: -1 es «caducó hace un minuto».
        $poner($cabeceraDe($nombreA), $a->generateToken(['zz' => 'caducado'], -1, false));
        $check($a->isActiveSession() === false, 'e2 con la duración en negativo, caducado: la duración va en MINUTOS');
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $conAud = $a->generateToken(['zz' => 'con aud'], 60, true);
        $poner($cabeceraDe($nombreA), $conAud);
        $check($a->isActiveSession() === true, 'e3 con aud, vale desde el mismo cliente');
        $_SERVER['REMOTE_ADDR'] = '10.0.0.99';
        $check($a->isActiveSession() === false, 'e3 y no vale desde otra IP');
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        echoTerminal(' ');

        //─── f · Lo que aísla: la CLAVE ──────────────────────────────────────────────────────────────────
        echoTerminal('[f] El aislamiento es de la clave, y además del canal');
        //ANTES DE P64 esta sección afirmaba lo contrario: las dos clases firmaban con Config::app_key(), una firma
        //valía para la otra y lo único que las separaba era el nombre. Ahora cada canal deriva su propia clave.
        $deUsuarios = SessionToken::generateToken(['id' => 1], null, null, false);
        $poner($cabeceraDe($nombreA), $deUsuarios);
        $check($a->isActiveSession() === false, 'f1 un token de la sesión de USUARIOS, puesto en el canal aislado, NO se acepta', 'app_key contra app_key_derived');
        $cargaAjena = $a->getJWTData();
        $check(!is_object($cargaAjena) || !isset($cargaAjena->id), 'f1 y su carga tampoco se lee desde el canal aislado', var_export($cargaAjena, true));
        //Al revés: antes pasaba la validación de la de usuarios y solo fallaba por no llevar id; ahora falla la firma.
        $check(SessionToken::isActiveSession($tokenA) === false, 'f2 y un token aislado NO pasa la validación de la de usuarios');
        $conLaDeUsuarios = BaseToken::check($tokenA, Config::app_key());
        $check($conLaDeUsuarios === BaseToken::SIGNATURE_VERIFICATION_FAILED, 'f2 y lo que falla es la FIRMA, no el contenido: check() devuelve su código, no false', var_export($conLaDeUsuarios, true));
        $check(BaseToken::check($tokenA, Config::app_key_derived('isolated-session:' . $nombreA)) === true, 'f3 lo que los separa es la clave: con la derivada de SU canal, ese mismo token valida');
        echoTerminal(' ');

        //─── g · Cada canal, su clave ────────────────────────────────────────────────────────────────────
        echoTerminal('[g] Dos canales aislados tampoco se aceptan el token entre sí');
        //Es lo que compra derivar del NOMBRE del canal en vez de una etiqueta fija.
        $poner($cabeceraDe($nombreB), $tokenA);
        $check($b->getJWTReceived() === $tokenA, 'g1 la B recibe el token de la A: el transporte no lo rechaza', $nombreB);
        $check($b->isActiveSession() === false, 'g1 y AUN ASÍ no vale: la clave de la B es otra');
        $check(Config::app_key_derived('isolated-session:' . $nombreA) !== Config::app_key_derived('isolated-session:' . $nombreB), 'g1 porque las dos claves derivadas son distintas');
        $poner($cabeceraDe($nombreB), $tokenB);

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . basename($e->getFile()) . ':' . $e->getLine());
    } finally {
        foreach ($previos as $clave => $valor) {
            if ($valor === null) { unset($_SERVER[$clave]); } else { $_SERVER[$clave] = $valor; }
        }
        if ($cookiePrevia === null) { unset($_COOKIE[$nombreA]); } else { $_COOKIE[$nombreA] = $cookiePrevia; }
        $repuesto = true;
        foreach ($previos as $clave => $valor) {
            $repuesto = $repuesto && (($_SERVER[$clave] ?? null) === $valor);
        }
        $check($repuesto && (($_COOKIE[$nombreA] ?? null) === $cookiePrevia), 'z1 las cabeceras, la cookie y la IP quedan como estaban');
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('P46: el contrato de SessionTokenIsolated, el mecanismo de sesiones aisladas para quien clona.')->setEffects([CliActions::EFFECT_NONE])->register();
