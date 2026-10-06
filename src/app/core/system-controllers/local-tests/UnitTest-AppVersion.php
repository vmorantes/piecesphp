<?php

//El commit de la instalación: sello de despliegue antes que git, solo HEAD, refs y packed-refs, y todo hash validado.
//Monta repositorios .git FALSOS en un temporal propio (el .git real no se toca) y los borra en el finally.

use PiecesPHP\Core\AppVersion;
use PiecesPHP\Terminal\CliActions;
use Terminal\Tasks\VersionStampTask;

CliActions::make('unit-tests:core/app-version', function ($args) {

    echoTerminal("\e[33m[TEST:AppVersion] Versión y commit de la instalación\e[39m");
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

    $base = sys_get_temp_dir() . '/zz-app-version-' . bin2hex(random_bytes(4));
    $escribir = function (string $path, string $contenido): void {
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
            throw new \RuntimeException("No se puede crear «{$dir}».");
        }
        if (file_put_contents($path, $contenido) === false) {
            throw new \RuntimeException("No se puede escribir «{$path}».");
        }
    };
    $hash = fn(string $semilla) => sha1($semilla);
    $sinSello = "{$base}/sin-sello.json";
    $leer = function (string $raiz, ?string $sello = null) use ($sinSello): array {
        AppVersion::useForTesting($raiz, $sello ?? $sinSello);
        return [AppVersion::commit(), AppVersion::commitSource()];
    };

    try {
        //─── a · Repositorio ────────────────────────────────────────────────────────────────────────────
        echoTerminal('[a] Lectura del repositorio');
        $escribir("{$base}/suelta/.git/HEAD", "ref: refs/heads/main\n");
        $escribir("{$base}/suelta/.git/refs/heads/main", $hash('suelta') . "\n");
        $check($leer("{$base}/suelta") === [$hash('suelta'), 'git'], 'a1 rama con ref suelta', (string) json_encode($leer("{$base}/suelta")));

        $escribir("{$base}/empaquetada/.git/HEAD", "ref: refs/heads/dev\n");
        $escribir("{$base}/empaquetada/.git/packed-refs", "# pack-refs with: peeled fully-peeled sorted\n" . $hash('otra') . " refs/heads/main\n" . $hash('empaquetada') . " refs/heads/dev\n");
        $check($leer("{$base}/empaquetada") === [$hash('empaquetada'), 'git'], 'a2 rama en packed-refs');

        $escribir("{$base}/separado/.git/HEAD", $hash('separado') . "\n");
        $check($leer("{$base}/separado") === [$hash('separado'), 'git'], 'a3 HEAD separado');

        $escribir("{$base}/worktree/.git", "gitdir: gitreal\n");
        $escribir("{$base}/worktree/gitreal/HEAD", $hash('worktree') . "\n");
        $check($leer("{$base}/worktree") === [$hash('worktree'), 'git'], 'a4 .git como archivo que apunta dentro del repositorio');

        $escribir("{$base}/fuera-destino/HEAD", $hash('fuera') . "\n");
        $escribir("{$base}/fuera/.git", "gitdir: {$base}/fuera-destino\n");
        $check($leer("{$base}/fuera") === [null, null], 'a5 .git como archivo que apunta FUERA → null');

        $escribir("{$base}/basura/.git/HEAD", "ref: refs/heads/main\n");
        $escribir("{$base}/basura/.git/refs/heads/main", "esto-no-es-un-hash\n");
        $escribir("{$base}/basura-head/.git/HEAD", "123abc\n");
        $escribir("{$base}/basura-ref/.git/HEAD", "ref: refs/heads/../../config\n");
        $check($leer("{$base}/basura") === [null, null] && $leer("{$base}/basura-head") === [null, null] && $leer("{$base}/basura-ref") === [null, null], 'a6 basura en la ref, en HEAD o una ref que sale de refs/ → null');
        $check($leer("{$base}/no-existe") === [null, null], 'a7 sin .git → null');
        echoTerminal(' ');

        //─── b · Sello ──────────────────────────────────────────────────────────────────────────────────
        echoTerminal('[b] Sello de despliegue');
        $sello = "{$base}/sello.json";
        $escribir($sello, (string) json_encode(['commit' => $hash('sello'), 'stampedAt' => '2026-09-18T12:00:00+00:00']));
        $check($leer("{$base}/suelta", $sello) === [$hash('sello'), 'stamp'], 'b1 el sello manda sobre git');
        $escribir($sello, (string) json_encode(['commit' => 'ABC', 'stampedAt' => 'x']));
        $check($leer("{$base}/suelta", $sello) === [$hash('suelta'), 'git'], 'b2 sello con hash inválido → se ignora y responde git');
        $escribir($sello, 'no es json');
        $check($leer("{$base}/no-existe", $sello) === [null, null], 'b3 sello roto y sin git → null, sin excepción');
        echoTerminal(' ');

        //─── c · version-stamp ──────────────────────────────────────────────────────────────────────────
        echoTerminal('[c] Tarea version-stamp');
        $destino = "{$base}/sellado/version-stamp.json";
        $escribir("{$base}/sellado/.keep", '');
        AppVersion::useForTesting("{$base}/empaquetada", $destino);
        $check(VersionStampTask::stamp('NO-ES-HASH') !== null && !is_file($destino), 'c1 un hash inválido no escribe nada');
        $check(VersionStampTask::stamp(null) === null && AppVersion::commit() === $hash('empaquetada') && AppVersion::commitSource() === 'stamp', 'c2 sin commit=, sella el del repositorio');
        $datos = json_decode((string) file_get_contents($destino), true);
        $check(is_array($datos) && ($datos['commit'] ?? null) === $hash('empaquetada') && is_string($datos['stampedAt'] ?? null) && (fileperms($destino) & 0777) === 0644, 'c3 escribe commit y stampedAt con permisos 0644', sprintf('%o', fileperms($destino) & 0777));
        $check(VersionStampTask::stamp($hash('dado')) === null && AppVersion::commit() === $hash('dado'), 'c4 con commit=, sella el dado');
        AppVersion::useForTesting("{$base}/no-existe", "{$base}/sellado/otro.json");
        $check(VersionStampTask::stamp(null) !== null, 'c5 sin commit= y sin repositorio → error');
        echoTerminal(' ');

        //─── d · Esta instalación ───────────────────────────────────────────────────────────────────────
        AppVersion::useForTesting(null, null);
        echoTerminal('[d] Esta instalación');
        $check(AppVersion::version() === APP_VERSION && AppVersion::date() === APP_VERSION_DATE && is_string(AppVersion::commit()) && AppVersion::isCommitHash((string) AppVersion::commit()), 'd1 versión, fecha y un commit de 40 hex', (string) AppVersion::commit() . ' ' . (string) AppVersion::commitSource());

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    } finally {
        AppVersion::useForTesting(null, null);
        if (is_dir($base)) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($it as $f) {
                //RETORNO-IGNORADO: limpieza del temporal propio de la prueba.
                $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
            }
            //RETORNO-IGNORADO: limpieza del temporal propio de la prueba.
            @rmdir($base);
        }
        $check(!is_dir($base), 'z1 limpieza del temporal propio');
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('El commit de la instalación: sello antes que git, solo HEAD/refs/packed-refs, y todo hash validado.')->setEffects([CliActions::EFFECT_FILES])->register();
