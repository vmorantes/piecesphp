<?php

//Una fila sin declarar (ADR 0055) se avisa UNA vez y NO se aplica; config-options la lista sin su valor. Por HTTP y por
//un arranque en otro proceso, con la marca de la corrida (ADR 0063); escribe una fila zz- y la retira.
//@areas nucleo

use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\ConfigHelpers\ConfigOptions;
use PiecesPHP\Core\CustomErrorsHandlers\GenericHandler;
use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/config-options-warning', function ($args) {

    //PAPEL SONDA: un arranque entero en otro proceso. Dice si la opción llega a get_config(), con la huella del valor y
    //nunca el valor; title_app va de canario (declarada: tiene que llegar).
    if (($args['papel'] ?? '') === 'sonda') {
        $sonda = (string) ($args['nombre'] ?? '');
        if (!str_starts_with($sonda, 'zz-config-options-aviso-')) {
            return ['success' => false, 'message' => 'papel sonda sin una opción de prueba'];
        }
        foreach ([$sonda, 'title_app'] as $leida) {
            $leido = Config::get_config($leida);
            echoTerminal('SONDA ' . $leida . ' ' . ($leido === false ? 'NO-LLEGA' : 'LLEGA ' . sha1(serialize($leido))));
        }
        return ['success' => true, 'message' => 'sonda'];
    }

    echoTerminal("\e[33m[TEST:ConfigOptionsWarning] Una fila sin declarar se avisa una vez y no se aplica\e[39m");
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

    //`base_url` en el terminal es `http://localhost`: no sirve. Igual que la pantalla de respaldos.
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    $database = (new BaseModel())->getDatabase();
    $run = GenericHandler::testRun();
    if (!$check($base !== '' && $database !== null && $run !== null, 'p1 hay base HTTP, conexión y una corrida de prueba que marque lo que se registre', "{$base} · corrida " . var_export($run, true))) {
        return $balance();
    }
    $pedir = function () use ($base): int {
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . '/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 60,
        ]);
        curl_exec($handle);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        return (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    };
    //Las entradas del registro estructurado que nombran la opción entre «»: el aviso la cita así.
    $avisos = function (string $name): array {
        $file = rtrim((string) constant('LOG_ERRORS_PATH'), '/\\') . '/' . GenericHandler::FILE_ENTRIES;
        $lines = is_file($file) ? (array) file($file, \FILE_IGNORE_NEW_LINES | \FILE_SKIP_EMPTY_LINES) : [];
        $found = [];
        foreach ($lines as $line) {
            if (is_string($line) && str_contains($line, "«{$name}»") && str_contains($line, 'ConfigOptions')) {
                $entry = json_decode($line, true);
                $found[] = is_array($entry) ? $entry : [];
            }
        }
        return $found;
    };

    $nombre = 'zz-config-options-aviso-' . bin2hex(random_bytes(3));
    $valor = 'zz-valor-que-no-se-imprime-' . bin2hex(random_bytes(4));
    $declarada = 'title_app';

    try {
        $check(SettingsModel::setConfigValue($nombre, $valor), 'p2 la fila sin declarar se escribe en la tabla');
        $check(!ConfigOptions::isDeclared($nombre) && ConfigOptions::isDeclared($declarada), "p3 CANARIO: la de la prueba no está declarada y «{$declarada}» sí");
        $declaradaAntes = count($avisos($declarada));

        //─── a · El aviso, una vez ────────────────────────────────────────────────────────────────────
        echoTerminal('[a] Una petición avisa una vez; la siguiente, no');
        $estado = $pedir();
        $primera = $avisos($nombre);
        $check($estado === 200 && count($primera) === 1, 'a1 la primera petición deja UNA línea que la nombra', "HTTP {$estado}, líneas " . count($primera));
        $check(($primera[0]['context']['test'] ?? null) === $run, 'a2 y lleva la marca de esta corrida (se poda con ella)', var_export($primera[0]['context']['test'] ?? null, true));
        $check(count($primera) === 1 && !str_contains((string) json_encode($primera[0]), $valor), 'a3 y no lleva el valor de la fila');
        //Lo de una corrida de prueba vive en la memoria del proceso (H55): dentro de este, el segundo aviso no sale.
        $enProceso = 'zz-config-options-aviso-' . bin2hex(random_bytes(3));
        ConfigOptions::warnUndeclared([$enProceso]);
        ConfigOptions::warnUndeclared([$enProceso]);
        $check(count($avisos($enProceso)) === 1, 'a4 en el mismo proceso, dos avisos del mismo nombre dentro de una corrida dejan UNA línea', 'líneas ' . count($avisos($enProceso)));
        ConfigOptions::forgetWarned($enProceso);
        //Lo real sí se recuerda entre procesos: se siembra en el archivo y otra petición no lo vuelve a avisar.
        $archivo = basepath(ConfigOptions::WARNED_FILE);
        $original = is_file($archivo) ? (string) file_get_contents($archivo) : null;
        $escribe = function (string $contenido) use ($archivo): bool {
            //umask 0002 en app/cache (setgid): 0664 del grupo del servidor, que sigue pudiendo escribirlo.
            $anterior = umask(0002);
            try {
                if (is_file($archivo) && !is_writable($archivo)) {
                    //RETORNO-IGNORADO: si no se puede retirar, el file_put_contents de abajo falla y la prueba lo dice.
                    @unlink($archivo);
                }
                return file_put_contents($archivo, $contenido) !== false;
            } finally {
                umask($anterior);
            }
        };
        try {
            $sembrado = json_decode($original ?? '{}', true);
            $sembrado = is_array($sembrado) ? $sembrado : [];
            $sembrado[$nombre] = null;
            if ($check($escribe((string) json_encode((object) $sembrado, \JSON_UNESCAPED_UNICODE)), 'a4b0 se siembra en el archivo una entrada REAL con el nombre de la prueba')) {
                $pedir();
                $check(count($avisos($nombre)) === 1, 'a4b una entrada real del archivo no se vuelve a avisar en otro proceso', 'líneas ' . count($avisos($nombre)));
            }
        } finally {
            if ($original !== null) {
                $check($escribe($original) && sha1((string) file_get_contents($archivo)) === sha1($original), 'a4c el archivo de avisos vuelve a su contenido');
            } elseif (is_file($archivo)) {
                //RETORNO-IGNORADO: no existía antes de la prueba; si no se puede retirar, clean-cache lo hace.
                @unlink($archivo);
            }
        }
        $check(count($avisos($declarada)) === $declaradaAntes, "a5 una opción declarada («{$declarada}») no deja ninguna", 'antes ' . $declaradaAntes . ', después ' . count($avisos($declarada)));
        echoTerminal(' ');

        //─── b · bin/cli config-options ───────────────────────────────────────────────────────────────
        echoTerminal('[b] config-options la lista por su nombre, sin su valor');
        //En otro proceso, como lo corre quien lo usa: echoTerminal() no pasa por el búfer de este.
        $salida = (string) shell_exec('cd ' . escapeshellarg(basepath('')) . ' && ' . escapeshellarg(PHP_BINARY) . ' index.php cli --local config-options 2>&1');
        $check(str_contains($salida, $nombre), 'b1 la nombra entre las filas sin declarar');
        $check(!str_contains($salida, $valor), 'b2 y no imprime su valor');
        echoTerminal(' ');

        //─── c · applyDeclared, en proceso ────────────────────────────────────────────────────────────
        echoTerminal('[c] applyDeclared aplica lo declarado y devuelve lo que no aplicó');
        $titulo = Config::get_config($declarada);
        $prueba = 'zz-titulo-' . bin2hex(random_bytes(3));
        try {
            $omitidas = ConfigOptions::applyDeclared([$declarada => $prueba, $nombre => $valor]);
            $check(Config::get_config($declarada) === $prueba, "c1 aplica la declarada («{$declarada}»)");
            $check(Config::get_config($nombre) === false, 'c2 no aplica la que nadie declaró');
            $check($omitidas === [$nombre], 'c3 y devuelve esa, sola', (string) json_encode($omitidas));
        } finally {
            set_config($declarada, $titulo);
        }
        echoTerminal(' ');

        //─── d · Un arranque entero, en otro proceso ──────────────────────────────────────────────────
        echoTerminal('[d] Tras un arranque entero, la fila sin declarar no llega a get_config() y la declarada sí');
        $sondaSalida = (string) shell_exec('cd ' . escapeshellarg(basepath('')) . ' && ' . escapeshellarg(PHP_BINARY) . ' index.php cli --local unit-tests:core/config-options-warning papel=sonda ' . escapeshellarg('nombre=' . $nombre) . ' 2>&1');
        $check(str_contains($sondaSalida, "SONDA {$nombre} NO-LLEGA"), 'd1 la fila sin declarar no llega a get_config()', mb_substr(trim($sondaSalida), -200));
        $huella = sha1(serialize(SettingsModel::getConfigurations()[$declarada] ?? null));
        $check(str_contains($sondaSalida, "SONDA {$declarada} LLEGA {$huella}"), "d2 CANARIO: «{$declarada}» llega con el valor de la base", mb_substr(trim($sondaSalida), -200));
        $check(!str_contains($sondaSalida, $valor), 'd3 y la sonda no imprime el valor');
        echoTerminal(' ');

        //─── e · El archivo de avisos (H52, H55): lo de prueba no entra, nace 0664 y descarta lo viejo de pruebas ─────
        echoTerminal('[e] El archivo de avisos guarda solo lo real y nace con escritura de grupo');
        $archivoE = basepath(ConfigOptions::WARNED_FILE);
        $originalE = is_file($archivoE) ? (string) file_get_contents($archivoE) : null;
        $leeE = fn(): array => is_array($d = json_decode(is_file($archivoE) ? (string) file_get_contents($archivoE) : '', true)) ? $d : [];
        $escribeE = function (string $contenido) use ($archivoE): bool {
            $anterior = umask(0002);
            try {
                if (is_file($archivoE) && !is_writable($archivoE)) {
                    //RETORNO-IGNORADO: si no se puede retirar, el file_put_contents de abajo falla y la prueba lo dice.
                    @unlink($archivoE);
                }
                return file_put_contents($archivoE, $contenido) !== false;
            } finally {
                umask($anterior);
            }
        };
        $nombresE = [];
        try {
            $real = 'zz-config-options-aviso-' . bin2hex(random_bytes(3));
            $viejo = 'zz-config-options-aviso-' . bin2hex(random_bytes(3));
            $check($escribeE((string) json_encode([$real => null, $viejo => 'zz-corrida-vieja'])), 'e0 se siembra una entrada real y una vieja de otra corrida');
            $nuevo = 'zz-config-options-aviso-' . bin2hex(random_bytes(3));
            $nombresE = [$real, $viejo, $nuevo];
            ConfigOptions::warnUndeclared([$nuevo]);
            $tras = $leeE();
            $check(!array_key_exists($nuevo, $tras), 'e1 lo avisado en una corrida de prueba no queda en el archivo', (string) json_encode($tras));
            $check(array_key_exists($real, $tras) && $tras[$real] === null, 'e2 la entrada real se conserva');
            $check(!array_key_exists($viejo, $tras), 'e3 la entrada vieja de otra corrida se descarta al guardar');
            //Nace con el umask del servidor (0022), no con el de esta terminal: el 0664 tiene que ponerlo saveWarned().
            //RETORNO-IGNORADO: se crea de nuevo justo abajo; si no se pudo retirar, e4 lo dice.
            @unlink($archivoE);
            $anterior = umask(0022);
            try {
                $otro = 'zz-config-options-aviso-' . bin2hex(random_bytes(3));
                $nombresE[] = $otro;
                ConfigOptions::warnUndeclared([$otro]);
            } finally {
                umask($anterior);
            }
            $modo = is_file($archivoE) ? (fileperms($archivoE) & 0777) : null;
            $check($modo === 0664, 'e4 el archivo nuevo nace 0664 aunque el proceso tenga umask 0022', $modo === null ? 'no existe' : sprintf('%o', $modo));
        } finally {
            foreach ($nombresE as $nombreE) {
                ConfigOptions::forgetWarned($nombreE);
            }
            if ($originalE !== null) {
                $check($escribeE($originalE) && sha1((string) file_get_contents($archivoE)) === sha1($originalE), 'e5 el archivo de avisos vuelve a su contenido');
            } elseif (is_file($archivoE)) {
                //RETORNO-IGNORADO: no existía antes de la prueba; si no se puede retirar, clean-cache lo hace.
                @unlink($archivoE);
            }
        }
    } catch (\Throwable $e) {
        $check(false, 'la prueba corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        $database->prepare('DELETE FROM `pcsphp_app_config` WHERE name = ?')->execute([$nombre]);
        ConfigOptions::forgetWarned($nombre);
        $quedan = (int) $database->query('SELECT COUNT(*) FROM `pcsphp_app_config` WHERE name LIKE ' . $database->quote('zz-config-options-aviso-%'))->fetchColumn();
        $check($quedan === 0, 'z1 no queda la fila de la prueba', "filas {$quedan}");
    }

    return $balance();

})->setDescription('Una fila de la tabla de configuración sin declarar (ADR 0055): un aviso por nombre, con la marca de la corrida, no se aplica, y config-options la lista sin su valor. Por HTTP y por un arranque en otro proceso.')->setEffects([CliActions::EFFECT_DATABASE, CliActions::EFFECT_FILES])->register();
