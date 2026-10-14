<?php

//El {group} de provide() acaba en un require. Se llama al controlador directamente, sin Apache ni Slim delante: lo que
//se mide es la defensa del código, no la del servidor (pendientes.md 446).

use PiecesPHP\Components\Controllers\ComponentProvider;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Core\Routing\Slim3Compatibility\Exception\NotFoundException;
use PiecesPHP\Terminal\CliActions;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;

CliActions::make('unit-tests:core/component-provider-group', function ($args) {

    echoTerminal("\e[33m[TEST:ComponentProviderGroup] Solo se sirve un grupo que exista en Views/, y lo demás da 404 sin llegar al require\e[39m");
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

    //Devuelve 'NOT_FOUND', o el cuerpo si provide() respondió. Cualquier otra excepción sale con su clase.
    $pedir = function (string $group): string {
        $peticion = (new RequestRoute('GET', (new UriFactory())->createUri('http://localhost/components-provider/provide/zz/'), new Headers(), [], [], (new StreamFactory())->createStream('')))
            //Sin Slim delante no hay RouteContext: con 'route' presente, NotFoundException no lo pide.
            ->withAttribute('route', null)
            ->withAttribute('group', $group);
        try {
            $respuesta = (new ComponentProvider())->provide($peticion, new ResponseRoute());
            return (string) $respuesta->getBody();
        } catch (NotFoundException $e) {
            return 'NOT_FOUND';
        } catch (\Throwable $e) {
            return 'EXCEPCION ' . get_class($e) . ': ' . $e->getMessage();
        }
    };

    echoTerminal('[canario] El universo de la puerta');
    $grupos = (new ComponentProvider())->availableGroups();
    sort($grupos);
    $check($grupos === ['publications', 'sample'], 'c1 los grupos servibles son los directorios de Views/ con components.php', implode(', ', $grupos) ?: 'ninguno');
    echoTerminal(' ');

    echoTerminal('[1] Un grupo que existe sigue sirviendo su plantilla');
    foreach (['publications', 'sample'] as $grupo) {
        $cuerpo = $pedir($grupo);
        $check(str_contains($cuerpo, '<components name='), "1 «{$grupo}» responde con su plantilla", mb_substr($cuerpo, 0, 80));
    }
    echoTerminal(' ');

    echoTerminal('[2] Lo demás da 404 antes del require');
    //Si alguno llegara al require, el archivo no existiría y saldría el aviso de PHP, no la NotFoundException.
    foreach (['..', '../x', '../../sample', 'a/b', '....//', 'sample/../publications', 'zz-no-existe', '', 'SAMPLE', 'sample.php'] as $grupo) {
        $resultado = $pedir($grupo);
        $check($resultado === 'NOT_FOUND', "2 «{$grupo}» da 404", mb_substr($resultado, 0, 80));
    }
    echoTerminal(' ');

    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('El {group} de components-provider-provide se valida contra los grupos que existen y con forma de nombre, sin depender del servidor.')->setEffects([CliActions::EFFECT_NONE])->register();
