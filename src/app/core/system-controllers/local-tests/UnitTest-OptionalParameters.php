<?php

//Un parámetro OPCIONAL con un valor presente e inválido da error en vez de guardarse como vacío.
//Vacío o ausente sigue cayendo al valor por omisión sin ruido. P87, 275.1.

use PiecesPHP\Core\Validation\Parameters\Exceptions\InvalidParameterValueException;
use PiecesPHP\Core\Validation\Parameters\Parameter;
use PiecesPHP\Core\Validation\Validator;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/optional-parameters', function ($args) {

    echoTerminal("\e[33m[TEST:OptionalParameters] Un opcional presente e inválido falla; vacío o ausente cae al por omisión\e[39m");
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

    $entero = fn (): callable => fn ($valor): bool => Validator::isInteger($valor);
    //Un opcional como los de los listados: `page`, por omisión 1.
    $nuevoOpcional = fn (int $omision = 1): Parameter => new Parameter('page', $omision, $entero(), true);
    $nuevoObligatorio = fn (int $omision = 1): Parameter => new Parameter('page', $omision, $entero(), false);

    $lanza = function (callable $accion): bool {
        try {
            $accion();
            return false;
        } catch (\Throwable $e) {
            return true;
        }
    };

    //──── a · Vacío o ausente: por omisión, sin error ───────────────────────────────────────────
    echoTerminal('[a] Vacío o ausente sigue cayendo al valor por omisión, sin error');
    $p = $nuevoOpcional();
    $check($p->validate(null) === true, 'a1 opcional con null: sin error');
    $check($p->getValue() === 1, 'a2 y vale el por omisión: ' . var_export($p->getValue(), true));
    $p = $nuevoOpcional();
    $check($p->validate('') === true, 'a3 opcional con cadena vacía: sin error');
    $check($p->getValue() === 1, 'a4 y vale el por omisión');
    //Nunca validado: `getValue()` tiene que dar el por omisión y no reventar.
    $p = $nuevoOpcional();
    $check($p->getValue() === 1, 'a5 un opcional que nadie validó da el por omisión');

    //──── b · Presente e inválido: ERROR ────────────────────────────────────────────────────────
    echoTerminal('');
    echoTerminal('[b] Presente e inválido ya NO se guarda como vacío: falla');
    //MEDIDO: `onError()` LANZA `InvalidParameterValueException`, igual que con un obligatorio:
    //un opcional inválido no devuelve false, lanza.
    $mensaje = '';
    $p = $nuevoOpcional();
    try {
        $p->validate('abc');
    } catch (InvalidParameterValueException $e) {
        $mensaje = $e->getMessage();
    }
    $check($mensaje !== '', 'b1 opcional con «abc» lanza InvalidParameterValueException', $mensaje);
    $check(str_contains($mensaje, 'page'), 'b2 y el mensaje nombra el parámetro', $mensaje);

    //El valor NO se guarda: el que lee después no recibe basura disfrazada del por omisión.
    $p = $nuevoOpcional();
    $seGuardo = null;
    try {
        $p->validate('abc');
    } catch (InvalidParameterValueException $e) {
        $seGuardo = 'lanzó';
    }
    $check($seGuardo === 'lanzó', 'b3 y el flujo se corta ahí, en vez de seguir con el por omisión');

    //Y lo que la comparación LAXA de isValid() salva sigue salvado, que es lo que evita la ruptura.
    $p = new Parameter('draft', false, fn ($v): bool => $v === 'yes' || $v === true, true);
    $check($p->validate('0') === true, 'b4 un valor laxamente igual al por omisión («0» contra false) sigue pasando');
    $p = new Parameter('id', -1, $entero(), true);
    $check($p->validate('-1') === true, 'b5 y «-1» contra -1 también');

    //──── c · Presente y válido: ese valor, con su parse ────────────────────────────────────────
    echoTerminal('');
    echoTerminal('[c] Presente y válido: ese valor, con su parse aplicado');
    $p = new Parameter('page', 1, $entero(), true, fn ($v): int => (int) $v);
    $check($p->validate('7') === true, 'c1 opcional con «7»: sin error');
    $check($p->getValue() === 7, 'c2 y vale 7, como entero por su parse: ' . var_export($p->getValue(), true));
    //Sin `parse`, el valor sale TAL CUAL llegó: «1» es la cadena, no el entero. Medido aquí.
    $p = $nuevoOpcional();
    $check($p->validate('1') === true && $p->getValue() == 1, 'c3 un valor igual al por omisión no es un error', var_export($p->getValue(), true));

    //──── d · Un obligatorio, igual que antes ───────────────────────────────────────────────────
    echoTerminal('');
    echoTerminal('[d] Un obligatorio se comporta igual que antes');
    $p = $nuevoObligatorio();
    $check($p->validate('9') === true && $p->getValue() == 9, 'd1 obligatorio válido: lo acepta', var_export($p->getValue(), true));
    //Un obligatorio inválido ya lanzaba ANTES de este cambio: es el camino al que ahora se suma el
    //opcional presente e inválido. Por eso d2-d4 esperan excepción, no `false`.
    foreach ([['abc', 'un texto'], [null, 'null'], ['', 'vacío']] as [$valor, $comoSeLlama]) {
        $p = $nuevoObligatorio();
        $check($lanza(fn () => $p->validate($valor)), "d2 obligatorio con {$comoSeLlama}: lanza, como siempre");
    }

    //──── e · El canario de la regla: sin él, un «falla todo» pasaría por bueno ──────────────────
    echoTerminal('');
    echoTerminal('[e] El canario: lo válido sigue siendo válido');
    $p = new Parameter('titulo', '', fn ($v): bool => is_string($v) && mb_strlen(trim($v)) > 0, true);
    $check($p->validate('zz un título') === true && $p->getValue() === 'zz un título', 'e1 un texto opcional válido se acepta y se guarda');
    $p = new Parameter('lista', [], fn ($v): bool => is_array($v), true);
    $check($p->validate(['a', 'b']) === true, 'e2 un array opcional válido se acepta');
    $p = new Parameter('lista', [], fn ($v): bool => is_array($v), true);
    $check($lanza(fn () => $p->validate('no-es-array')), 'e3 y un texto donde se espera array falla');

    return $balance();

})->setDescription('La regla nueva de los parámetros opcionales: vacío o ausente cae al por omisión sin error, y presente e inválido falla nombrando el parámetro.')->setEffects([CliActions::EFFECT_NONE])->register();
