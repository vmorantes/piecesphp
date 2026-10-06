<?php

//Vigila que siga viva la rama del `BaseModel` genérico CON conexión —de ella dependen las llamadas
//`new BaseController()` sin argumento— y que la deducción por nombre de clase no vuelva.

use PiecesPHP\Core\BaseController;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/generic-model-on-construct', function ($args) {

    echoTerminal("\e[33m[TEST:GenericModelOnConstruct] sin modelo propio hay BaseModel genérico con conexión, y la deducción no vuelve\e[39m");
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

    //`$model` es protegido y no tiene getter: se lee por reflexión, que es exactamente lo que
    //hace una subclase y lo único que puede comprobar lo que recibió.
    $modeloDe = function (BaseController $controlador) {
        //Sin setAccessible(): desde PHP 8.1 no hace falta, y en 8.5 está deprecado —y aquí las
        //deprecaciones abortan—.
        return (new \ReflectionProperty(BaseController::class, 'model'))->getValue($controlador);
    };

    echoTerminal('[1/3] Las llamadas directas `new BaseController()` y su modelo');
    $conModelo = new BaseController();
    $modelo = $modeloDe($conModelo);
    $check($modelo instanceof BaseModel, 'a1 `new BaseController()` sin argumento recibe un BaseModel',
        $modelo === null ? 'recibió null' : 'recibió ' . get_class($modelo));
    //DISCRIMINANTE: si alguien conserva la asignación pero le quita la conexión, esta cae y la
    //anterior no. Es la diferencia entre conservar la rama y conservar su comportamiento.
    $conexion = null;
    try {
        $conexion = $modelo instanceof BaseModel ? $modelo->getDatabase() : null;
    } catch (\Throwable $e) {
        $check(false, 'a2 el modelo genérico trae conexión', get_class($e) . ': ' . $e->getMessage());
    }
    if ($conexion !== null) {
        $check(true, 'a2 el modelo genérico trae conexión (' . (is_object($conexion) ? get_class($conexion) : gettype($conexion)) . ')');
    } elseif ($modelo instanceof BaseModel) {
        $check(false, 'a2 el modelo genérico trae conexión', 'getDatabase() devolvió null');
    }

    echoTerminal(' ');
    echoTerminal('[2/3] Y con `false` NO se asigna nada, que es de lo que dependen los 75 controladores');
    $sinModelo = new BaseController(false);
    $check($modeloDe($sinModelo) === null, 'b1 `new BaseController(false)` deja $model en null',
        'obtenido: ' . gettype($modeloDe($sinModelo)));

    echoTerminal(' ');
    echoTerminal('[3/3] La deducción por nombre de clase no ha vuelto');
    $fuente = (string) @file_get_contents(basepath('app/core/psr4/PiecesPHP/Core/BaseController.php'));
    $check($fuente !== '', 'c1 la fuente de BaseController se puede leer');
    //Componer el nombre de un modelo exige LEER el nombre de la clase: sin esas formas no se puede.
    //En TOKENS, porque el comentario que explica la retirada nombra la forma retirada.
    $constructor = '';
    if ($fuente !== '') {
        $tokens = token_get_all($fuente);
        $dentro = false;
        $profundidad = 0;
        $visto = false;
        foreach ($tokens as $indice => $token) {
            if (!$dentro && is_array($token) && $token[0] === T_STRING && $token[1] === '__construct') {
                $dentro = true;
                continue;
            }
            if (!$dentro) {
                continue;
            }
            if ($token === '{') {
                $profundidad++;
                $visto = true;
                continue;
            }
            if ($token === '}') {
                $profundidad--;
                if ($visto && $profundidad <= 0) {
                    break;
                }
                continue;
            }
            if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            $constructor .= is_array($token) ? $token[1] : $token;
        }
    }
    $check($constructor !== '', 'c2 el constructor se puede aislar en tokens, sin sus comentarios');
    foreach (['$this::class', 'static::class', 'get_class($this)', 'App\\Model\\'] as $forma) {
        $check($constructor !== '' && mb_strpos($constructor, $forma) === false,
            "c3 el constructor no nombra «{$forma}»");
    }
    //Y la firma se conserva aunque el tercer argumento ya no decida nada: un clon puede pasarlo.
    $parametros = (new \ReflectionMethod(BaseController::class, '__construct'))->getParameters();
    $nombres = array_map(fn (\ReflectionParameter $p): string => $p->getName(), $parametros);
    $check($nombres === ['auto_model', 'group_database_model', 'system_models'],
        'c4 la firma del constructor no cambió', implode(', ', $nombres));

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Sin modelo propio hay un BaseModel genérico con conexión, y la deducción por nombre de clase no vuelve.')->setEffects([CliActions::EFFECT_NONE])->register();
