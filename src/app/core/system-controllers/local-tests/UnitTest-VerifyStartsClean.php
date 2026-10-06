<?php

//El punto de partida de la verificación: el barrido, la línea base y la atribución (ADR 0047).
//Nace de un bin/verify que falló por un resto de la noche anterior y de un siguiente que pasó.

use PiecesPHP\Core\Email\MailDelivery;
use PiecesPHP\SystemStatus\Mappers\MailLogMapper;
use PiecesPHP\Terminal\CliActions;
use Terminal\TestLeftovers;

CliActions::make('unit-tests:core/verify-starts-clean', function ($args) {

    echoTerminal("\e[33m[TEST:VerifyStartsClean] El punto de partida se mide, se compara y se atribuye\e[39m");
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

    $marca = TestLeftovers::MARK . 'arranque-' . bin2hex(random_bytes(3));
    $destino = "{$marca}@localhost.test";
    $tabla = MailLogMapper::TABLE;
    $modelo = MailLogMapper::model();
    $modelo->resetAll();
    $base = $modelo->getDatabase();

    //─── a · El barrido mide la base de verdad ──────────────────────────────────────────────────
    echoTerminal('[a] El barrido cuenta lo que hay, y declara lo que no mira');
    $barrido = TestLeftovers::sweep();
    $check($barrido['error'] === null, 'a1 el barrido se pudo hacer', (string) $barrido['error']);
    if ($barrido['error'] !== null) {
        return $balance();
    }
    $check($barrido['columnas'] > 0 && $barrido['tablas'] > 0, "a2 y declara su universo ({$barrido['tablas']} tablas, {$barrido['columnas']} columnas)");
    //LEY 15: lo que el instrumento NO mira tiene que estar contado, o la cobertura queda exagerada.
    $check($barrido['omitidas'] > 0, "a3 y cuántas columnas NO lee por poder guardar un secreto ({$barrido['omitidas']})");
    $sensibles = [];
    foreach (array_keys($barrido['pares']) as $par) {
        $columna = substr((string) $par, (int) strrpos((string) $par, '.') + 1);
        if (preg_match(TestLeftovers::SENSITIVE_PATTERN, $columna) === 1) {
            $sensibles[] = (string) $par;
        }
    }
    $check($sensibles === [], 'a4 y ninguna columna de contraseña, token o clave aparece en lo contado', implode(', ', $sensibles));

    //─── b · La línea base existe y es la referencia ────────────────────────────────────────────
    echoTerminal(' ');
    echoTerminal('[b] La línea base: «limpio» es exactamente lo declarado, no «poco»');
    $lineaBase = TestLeftovers::baseline();
    $check($lineaBase['existe'], 'b1 la línea base existe', TestLeftovers::BASELINE_FILE);
    $check($lineaBase['metodo'] !== '', 'b2 y dice con qué método se midió');
    if (!$lineaBase['existe']) {
        return $balance();
    }

    //CANARIO de dos caras, y va con cifras INVENTADAS a propósito: así no depende del estado real.
    $sintetico = $lineaBase['declarado'];
    $check(TestLeftovers::compareToBaseline($sintetico)['limpio'], 'b3 un barrido igual a la línea base es LIMPIO');

    $conUnoDeMas = $sintetico;
    $conUnoDeMas['zz_tabla_inventada.columna'] = 1;
    $sobra = TestLeftovers::compareToBaseline($conUnoDeMas);
    $check(!$sobra['limpio'], 'b4 CANARIO: uno de más es SUCIO');
    $check(array_key_exists('zz_tabla_inventada.columna', $sobra['sobran']), 'b5 y lo nombra', TestLeftovers::describe($sobra['sobran']));

    $conUnoDeMenos = $sintetico;
    $primera = (string) (array_key_first($conUnoDeMenos) ?? '');
    if ($primera !== '') {
        $conUnoDeMenos[$primera] = max(0, (int) $conUnoDeMenos[$primera] - 1);
        $falta = TestLeftovers::compareToBaseline($conUnoDeMenos);
        $check(!$falta['limpio'], 'b6 CANARIO: uno de MENOS también es sucio: algo se llevó parte del banco de pruebas');
        $check(array_key_exists($primera, $falta['faltan']), 'b7 y lo nombra', TestLeftovers::describe($falta['faltan']));
    }

    //─── c · La atribución: se mide la DIFERENCIA ───────────────────────────────────────────────
    echoTerminal(' ');
    echoTerminal('[c] Lo que crece se atribuye, y lo que no crece no acusa a nadie');
    $check(TestLeftovers::growth($sintetico, $sintetico) === [], 'c1 CANARIO: entre dos barridos iguales no crece nada, así que no se acusa a nadie');
    $crecio = TestLeftovers::growth($sintetico, $conUnoDeMas);
    $check(array_key_exists('zz_tabla_inventada.columna', $crecio), 'c2 y un par que sube se nombra', TestLeftovers::describe($crecio));
    //Que BAJE no es crecer: una suite que limpia de más es otro problema, no un resto.
    $check(TestLeftovers::growth($conUnoDeMas, $sintetico) === [], 'c3 y un par que BAJA no cuenta como resto nuevo');

    //─── d · Y mide la base, no un número guardado ──────────────────────────────────────────────
    echoTerminal(' ');
    echoTerminal('[d] El barrido mira la base cada vez: se le siembra una fila y la ve');
    if (!$check($base !== null && MailLogMapper::tableExists(), 'd1 hay base y la tabla del registro está')) {
        return $balance();
    }
    $clave = "{$tabla}.recipients";
    $antes = (int) (TestLeftovers::sweep()['pares'][$clave] ?? 0);
    $sembrado = MailLogMapper::record(
        [$destino],
        'ZZ arranque limpio',
        'UnitTest-VerifyStartsClean',
        MailDelivery::SINK,
        MailLogMapper::RESULT_FAILED,
        'sembrado por la prueba del ADR 0047'
    );
    $check($sembrado, 'd2 se siembra una fila con la marca');
    $despues = (int) (TestLeftovers::sweep()['pares'][$clave] ?? 0);
    $check($despues === $antes + 1, "d3 y el barrido la VE ({$antes} → {$despues})");
    $check(array_key_exists($clave, TestLeftovers::growth([$clave => $antes], [$clave => $despues])), 'd4 y la atribución la cuenta como crecimiento');

    //─── e · La marca con guion bajo, y el `_` que en LIKE es comodín ──────────────────────────
    echoTerminal(' ');
    echoTerminal('[e] La marca con guion bajo se ve, y «zz» con otro carácter no');
    $marcaGuion = TestLeftovers::MARK_UNDERSCORE . 'arranque_' . bin2hex(random_bytes(3));
    $marcaOtra = 'zzX' . 'arranque' . bin2hex(random_bytes(3));
    $antesE = (int) (TestLeftovers::sweep()['pares'][$clave] ?? 0);
    MailLogMapper::record(["{$marcaGuion}@localhost.test"], 'ZZ arranque con guion bajo', 'UnitTest-VerifyStartsClean', MailDelivery::SINK, MailLogMapper::RESULT_FAILED, 'sembrado por la prueba del ADR 0047');
    $conGuion = (int) (TestLeftovers::sweep()['pares'][$clave] ?? 0);
    $check($conGuion === $antesE + 1, "e1 una fila «zz_…» la ve el barrido ({$antesE} → {$conGuion})");
    MailLogMapper::record(["{$marcaOtra}@localhost.test"], 'ZZ arranque sin marca', 'UnitTest-VerifyStartsClean', MailDelivery::SINK, MailLogMapper::RESULT_FAILED, 'sembrado por la prueba del ADR 0047');
    $conOtra = (int) (TestLeftovers::sweep()['pares'][$clave] ?? 0);
    $check($conOtra === $conGuion, "e2 CANARIO: una fila «zzX…» NO: el guion bajo va escapado, no es comodín ({$conGuion} → {$conOtra})");
    $borrarE = $base->prepare("DELETE FROM `{$tabla}` WHERE `recipients` LIKE ? OR `recipients` LIKE ?");
    $borrarE->execute(['%' . TestLeftovers::likeLiteral($marcaGuion) . '%', '%' . TestLeftovers::likeLiteral($marcaOtra) . '%']);
    $check($borrarE->rowCount() === 2, 'e3 y las dos filas sembradas se retiran', (string) $borrarE->rowCount());

    //─── z · Limpieza, que esta suite tiene que pasar su propia puerta ──────────────────────────
    echoTerminal(' ');
    echoTerminal('[z] Limpieza: esta suite no puede dejar lo que ella misma vigila');
    $borrar = $base->prepare("DELETE FROM `{$tabla}` WHERE `recipients` LIKE ?");
    $borrar->execute(["%{$marca}%"]);
    MailLogMapper::forgetMemo();
    $cuenta = $base->prepare("SELECT COUNT(*) FROM `{$tabla}` WHERE `recipients` LIKE ?");
    $cuenta->execute(["%{$marca}%"]);
    $quedan = (int) $cuenta->fetchColumn();
    $check($quedan === 0, 'z1 no queda ninguna fila de la prueba');
    $final = (int) (TestLeftovers::sweep()['pares'][$clave] ?? 0);
    $check($final === $antes, "z2 y el barrido vuelve a donde estaba ({$despues} → {$final})");
    echoTerminal("   restos tras la limpieza: filas={$quedan}");

    return $balance();

})->setDescription('El punto de partida de la verificación: el barrido de restos con su universo declarado, que «limpio» sea exactamente la línea base —con sus canarios de uno de más y uno de menos—, que la atribución mida la diferencia y no acuse a nadie cuando nada crece, y que el barrido mire la base de verdad y no un número guardado.')->setEffects([CliActions::EFFECT_DATABASE])->register();
