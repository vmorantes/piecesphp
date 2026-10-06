<?php

//`clean-logs` vacía también el buzón en disco del correo retenido: cada .eml lleva el cuerpo en claro.
//Se prueba sobre un directorio temporal: el buzón real y los logs reales no se tocan.

use PiecesPHP\Terminal\CliActions;
use Terminal\Tasks\CleanLogsTask;

CliActions::make('unit-tests:core/clean-logs-outbox', function ($args) {

    echoTerminal("\e[33m[TEST:CleanLogsOutbox] clean-logs vacía el buzón en disco\e[39m");
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

    $temporal = sys_get_temp_dir() . '/zz-buzon-' . bin2hex(random_bytes(4));

    try {
        //RETORNO-IGNORADO: si no se creara, a0 lo diría.
        @mkdir($temporal, 0775, true);
        foreach (['uno', 'dos', 'tres'] as $nombre) {
            //RETORNO-IGNORADO: a0 cuenta lo que hay.
            @file_put_contents("{$temporal}/zz-{$nombre}.eml", "Subject: zz\r\n\r\nzz cuerpo\r\n");
        }
        //RETORNO-IGNORADO: a0 cuenta lo que hay.
        @file_put_contents("{$temporal}/.htaccess", "Require all denied\n");
        $check(count((array) glob("{$temporal}/*.eml")) === 3 && is_file("{$temporal}/.htaccess"), 'a0 CANARIO: tres .eml y un archivo que no lo es, sembrados');

        echoTerminal('[a] El vaciado');
        $borrados = CleanLogsTask::emptyOutbox($temporal);
        $check($borrados === 3, 'a1 borra los tres .eml y lo dice', (string) $borrados);
        $check(count((array) glob("{$temporal}/*.eml")) === 0, 'a2 no queda ninguno');
        $check(is_dir($temporal) && is_file("{$temporal}/.htaccess"), 'a3 y deja el directorio y lo que no es un .eml');
        $check(CleanLogsTask::emptyOutbox($temporal) === 0, 'a4 vacío, borra cero y no falla');

        //─── b · Que main() lo use ──────────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[b] clean-logs lo llama sobre el buzón real');
        $fuente = (string) file_get_contents((string) (new \ReflectionClass(CleanLogsTask::class))->getFileName());
        $inicio = (int) strpos($fuente, 'public static function main(');
        $main = $inicio > 0 ? substr($fuente, $inicio) : '';
        $check(preg_match('/\$borradosEml\s*=\s*self::emptyOutbox\(\)\s*;/', $main) === 1, 'b1 main() vacía el buzón por defecto, sin directorio de prueba');
        $check(str_contains($main, 'mail-outbox vaciado ({$borradosEml} .eml)'), 'b2 y dice cuántos .eml retiró');
    } catch (\Throwable $e) {
        $check(false, 'la prueba corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        foreach (array_merge((array) glob("{$temporal}/*"), ["{$temporal}/.htaccess"]) as $sobrante) {
            //RETORNO-IGNORADO: lo comprueba z1.
            @unlink((string) $sobrante);
        }
        //RETORNO-IGNORADO: lo comprueba z1.
        @rmdir($temporal);
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        $check(!is_dir($temporal), 'z1 el directorio temporal se retira');
    }

    return $balance();

})->setDescription('clean-logs vacía el buzón en disco del correo retenido: borra los .eml y dice cuántos, y deja el directorio y lo que no es un .eml. Sobre un directorio temporal: el buzón real no se toca.')->setEffects([CliActions::EFFECT_FILES])->register();
