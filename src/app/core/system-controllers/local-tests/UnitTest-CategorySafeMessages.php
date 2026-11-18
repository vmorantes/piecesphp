<?php

//H370: el motivo de no poder borrar una categoría (o de un idioma no permitido) llega a quien lo pidió, no un aviso genérico.
//@areas src/app/classes/Publications/ src/app/classes/News/

use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/category-safe-messages', function ($args) {

    echoTerminal("\e[33m[TEST:category-safe-messages] Los motivos de las categorías llegan al usuario\e[39m");
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

    /** Código sin comentarios y con los espacios juntados: la forma, no el formato. */
    $code = function (string $relative): string {
        $clean = '';
        foreach (token_get_all((string) file_get_contents(basepath($relative))) as $token) {
            if (is_array($token)) {
                if ($token[0] !== T_COMMENT && $token[0] !== T_DOC_COMMENT) {
                    $clean .= $token[1];
                }
            } else {
                $clean .= $token;
            }
        }
        return (string) preg_replace('/\s+/', ' ', $clean);
    };

    //Un \Exception con texto para el usuario acaba en el catch genérico, que lo cambia por «Ocurrió un error interno…».
    $deleteCatch = '} catch (SafeException $e) { $resultOperation->setMessage($e->getMessage()); } catch (\Exception $e) { $reference = log_exception($e); $resultOperation->setMessage(CustomSlimErrorHandler::genericMessage($reference));';
    $controllers = [
        'Publications' => 'app/classes/Publications/Controllers/PublicationsCategoryController.php',
        'News' => 'app/classes/News/Controllers/NewsCategoryController.php',
    ];

    try {
        foreach ($controllers as $module => $file) {
            $source = $code($file);
            $check(str_contains($source, 'throw new SafeException($notAllowedDelete);') && !str_contains($source, 'throw new \Exception($notAllowedDelete);'), "{$module}: no poder borrar la categoría «sin categoría» lanza SafeException");
            $check(str_contains($source, $deleteCatch), "{$module}: el borrado atrapa SafeException con su mensaje antes del catch genérico");
        }
        $check(str_contains($code($controllers['News']), 'throw new SafeException(vsprintf($notAllowedLangMessage, [$lang]));'), 'News: un idioma no permitido lanza SafeException, como en Publications y en las categorías genéricas');
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('H370: los motivos de las categorías llegan al usuario.')->setEffects([CliActions::EFFECT_NONE])->register();
