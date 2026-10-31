<?php

//bin/cli session-name (P158): propone sin escribir, escribe solo con escribir=yes y en el lugar de la línea de la plantilla,
//rechaza un nombre inválido y, sin la plantilla, no edita a ciegas. Escribe solo sobre COPIAS en un temporal.
//@areas nucleo

use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use Terminal\Tasks\SessionNameTask;

CliActions::make('unit-tests:core/session-name', function ($args) {

    echoTerminal("\e[33m[TEST:SessionName] bin/cli session-name propone, escribe solo lo pedido y nunca a ciegas\e[39m");
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

    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    $real = basepath('app/config/config.php');
    $huellaReal = sha1_file($real);
    $tarea = function (array $argumentos) use ($proyecto): array {
        $comando = escapeshellarg("{$proyecto}/bin/cli") . ' session-name';
        foreach ($argumentos as $argumento) {
            $comando .= ' ' . escapeshellarg($argumento);
        }
        $salida = [];
        $codigo = -1;
        //RETORNO-IGNORADO: el resultado va en $salida y $codigo; el retorno es solo la última línea.
        exec($comando . ' 2>&1', $salida, $codigo);
        return [$codigo, (string) preg_replace('/\e\[[0-9;]*m/', '', implode("\n", $salida))];
    };
    $temporal = rtrim(sys_get_temp_dir(), '/') . '/zz-session-name-' . bin2hex(random_bytes(4));
    //RETORNO-IGNORADO: si la carpeta no se crea, el copy() de abajo falla y b0 lo dice.
    @mkdir($temporal, 0700, true);
    $copia = "{$temporal}/config.php";

    try {
        $check(substr_count((string) file_get_contents($real), SessionNameTask::TEMPLATE_LINE) === 1, 'banco: el config.php real trae la línea de la plantilla, una vez');

        echoTerminal('[a] Sin escribir=yes, propone y no toca nada');
        [$codigo, $salida] = $tarea([]);
        $propuesto = SessionNameTask::proposedName();
        $check($codigo === 0 && str_contains($salida, "«{$propuesto}»") && str_contains($salida, 'No se ha escrito nada'), 'a1 propone un nombre y dice que no escribió', $salida);
        $check(SessionToken::tokenNameIsValid($propuesto), 'a2 el nombre propuesto es válido', $propuesto);
        $check(str_contains($salida, 'cierra TODAS las sesiones'), 'a3 avisa de que se cierran todas las sesiones');
        $check(sha1_file($real) === $huellaReal, 'a4 el config.php real no cambió');
        echoTerminal(' ');

        echoTerminal('[b] Con escribir=yes, sobre una COPIA');
        $check(copy($real, $copia), 'b0 copia del config.php en un temporal', $copia);
        [$codigo, $salida] = $tarea(['escribir=yes', 'nombre=zzSesionPrueba', "archivo={$copia}"]);
        $escrito = (string) file_get_contents($copia);
        $check($codigo === 0 && str_contains($escrito, "\$config['session_token_name'] = 'zzSesionPrueba';") && !str_contains($escrito, SessionNameTask::TEMPLATE_LINE), 'b1 la copia lleva la línea en el lugar de la comentada', $salida);
        $check(substr_count($escrito, "\n") === substr_count((string) file_get_contents($real), "\n"), 'b2 y nada más cambia: el mismo número de líneas');
        $check(sha1_file($real) === $huellaReal, 'b3 el config.php real no cambió');
        echoTerminal(' ');

        echoTerminal('[c] Lo que se rechaza');
        //RETORNO-IGNORADO: si la copia falla, c1 compara contra lo que haya y lo delata.
        copy($real, $copia);
        $antes = sha1_file($copia);
        [$codigo, $salida] = $tarea(['escribir=yes', 'nombre=zz-con-guion', "archivo={$copia}"]);
        $check($codigo !== 0 && str_contains($salida, 'no vale como nombre') && sha1_file($copia) === $antes, 'c1 un nombre con guion se rechaza y no escribe', $salida);
        //Ya escrita una vez, la plantilla no está: no se adivina dónde va, se para y dice qué pegar.
        [$codigo] = $tarea(['escribir=yes', 'nombre=zzPrimero', "archivo={$copia}"]);
        $escritaUnaVez = sha1_file($copia);
        [$codigo, $salida] = $tarea(['escribir=yes', 'nombre=zzSegundo', "archivo={$copia}"]);
        $check($codigo === 2 && str_contains($salida, 'PARA') && str_contains($salida, "\$config['session_token_name'] = 'zzSegundo';") && sha1_file($copia) === $escritaUnaVez, 'c2 sin la línea de la plantilla PARA, dice la línea que hay que pegar y no edita', $salida);
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        //RETORNO-IGNORADO: lo comprueba z1.
        @unlink($copia);
        //RETORNO-IGNORADO: lo comprueba z1.
        @rmdir($temporal);
        $check(!is_dir($temporal) && sha1_file($real) === $huellaReal, 'z1 el temporal se retira y el config.php real sigue igual');
    }

    return $balance();

})->setDescription('bin/cli session-name: propone sin escribir, escribe solo con escribir=yes en el lugar de la línea de la plantilla, rechaza un nombre inválido y sin plantilla no edita. Solo sobre copias en un temporal.')->setEffects([CliActions::EFFECT_FILES])->register();
