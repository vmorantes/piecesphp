<?php

//`mail-demo` manda correo, así que esta suite NO lo manda: prueba sus dos guardas con el entorno en archivos
//temporales y la entrega desviada en memoria, y que lo que prepara no lleva la marca de las pruebas.

use PiecesPHP\Core\AppEnvironment;
use PiecesPHP\Core\Email\MailDelivery;
use PiecesPHP\Terminal\CliActions;
use Terminal\TestLeftovers;
use Terminal\Tasks\MailDemoTask;

CliActions::make('unit-tests:core/mail-demo', function ($args) {

    echoTerminal("\e[33m[TEST:MailDemo] mail-demo solo manda en local y con la entrega retenida, y sin la marca de las pruebas\e[39m");
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

    $entornoReal = AppEnvironment::get();
    $entregaPrevia = get_config(MailDelivery::CONFIG_NAME);
    $temporal = sys_get_temp_dir() . '/zz-maildemo-entorno-' . bin2hex(random_bytes(4));
    $archivo = fn (string $nombre): string => "{$temporal}-{$nombre}.php";
    $deja = fn (): array => array_map(fn (array $g): bool => $g['ok'], MailDemoTask::guards());

    try {
        //RETORNO-IGNORADO: sin este archivo, a1 fallaría y lo diría.
        @file_put_contents($archivo('local'), "<?php\nreturn 'local';\n");
        //RETORNO-IGNORADO: sin este archivo, a2 fallaría y lo diría.
        @file_put_contents($archivo('production'), "<?php\nreturn 'production';\n");

        //─── a · Las dos guardas ────────────────────────────────────────────────────────────────────
        echoTerminal('[a] Las dos guardas, sin mandar nada');
        AppEnvironment::useForTesting($archivo('local'));
        set_config(MailDelivery::CONFIG_NAME, MailDelivery::SINK);
        $check($deja() === [true, true], 'a1 CANARIO: local y retenida, las dos dejan', json_encode($deja()) ?: '');
        AppEnvironment::useForTesting($archivo('production'));
        $check($deja()[0] === false, 'a2 «production»: la del entorno NO deja');
        AppEnvironment::useForTesting($archivo('nunca-creado'));
        $check($deja()[0] === false, 'a3 sin environment.php: NO deja, cuenta como producción');
        AppEnvironment::useForTesting($archivo('local'));
        set_config(MailDelivery::CONFIG_NAME, MailDelivery::REAL);
        $check(MailDelivery::goesToSink() === false && $deja()[1] === false, 'a4 local con la entrega REAL: la de la entrega NO deja, aunque el entorno sí');
        $lineas = array_map(fn (array $g): string => $g['line'], MailDemoTask::guards());
        $check(str_contains($lineas[1], 'Retenido'), 'a5 y dice cómo se arregla', $lineas[1]);
        set_config(MailDelivery::CONFIG_NAME, MailDelivery::SINK);

        //─── b · main() las consulta antes de pintar y de mandar ────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[b] main() consulta las guardas lo primero, y sale si alguna no deja');
        $fuente = (string) file_get_contents((string) (new \ReflectionClass(MailDemoTask::class))->getFileName());
        $main = substr($fuente, (int) strpos($fuente, 'public static function main('));
        $guardas = strpos($main, 'self::guards()');
        $check($guardas !== false && $guardas < (int) strpos($main, 'self::catalog()') && $guardas < (int) strpos($main, 'new Mailer('), 'b1 antes del catálogo y antes del primer Mailer');
        $check(preg_match('/if\s*\(!\$permitido\)\s*\{.{0,200}?exit\(\s*[1-9]/s', $main) === 1, 'b2 y si alguna no deja, sale con código distinto de cero');

        //─── c · El catálogo ────────────────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[c] El catálogo: una pieza por envío del framework, y nada con la marca de las pruebas');
        $catalogo = MailDemoTask::catalog();
        $check(count($catalogo) === 11, 'c1 once correos: las nueve plantillas que envía el framework, con sus variantes', (string) count($catalogo));
        $vacios = array_filter($catalogo, fn (array $c): bool => trim(strip_tags($c['body'])) === '' || trim($c['subject']) === '');
        $check($vacios === [], 'c2 ninguno sale sin asunto ni sin cuerpo');
        $conMarca = array_filter($catalogo, fn (array $c): bool => str_contains($c['subject'] . $c['body'], TestLeftovers::MARK));
        $check($conMarca === [] && !str_contains(MailDemoTask::RECIPIENT, TestLeftovers::MARK), 'c3 ni el destinatario ni ningún asunto ni cuerpo llevan «' . TestLeftovers::MARK . '»: no cuentan como resto de prueba', implode(', ', array_column($conMarca, 'template')));
        $check(str_starts_with(MailDemoTask::RECIPIENT, 'demo-') && str_ends_with(MailDemoTask::RECIPIENT, '@localhost.test'), 'c4 el destinatario es demo- en localhost.test', MailDemoTask::RECIPIENT);
    } catch (\Throwable $e) {
        $check(false, 'la prueba corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . basename($e->getFile()) . ':' . $e->getLine());
    } finally {
        AppEnvironment::useForTesting(null);
        set_config(MailDelivery::CONFIG_NAME, $entregaPrevia);
        foreach (['local', 'production'] as $nombre) {
            //RETORNO-IGNORADO: lo comprueba z1.
            @unlink($archivo($nombre));
        }
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        $check(!is_file($archivo('local')) && !is_file($archivo('production')) && AppEnvironment::get() === $entornoReal && get_config(MailDelivery::CONFIG_NAME) === $entregaPrevia, 'z1 los entornos temporales retirados, y el entorno y la entrega como estaban');
    }

    return $balance();

})->setDescription('mail-demo solo manda en una instalación local y con la entrega retenida —las dos guardas, probadas sin mandar nada—, las consulta antes de pintar ni enviar, y lo que prepara no lleva la marca de las pruebas.')->setEffects([CliActions::EFFECT_FILES])->register();
