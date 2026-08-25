<?php

//Abrir un formulario no crea filas. Ver T69.

use PiecesPHP\BuiltIn\Helpers\Mappers\GenericContentPseudoMapper as GenericContent;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/generic-content', function ($args) {

    echoTerminal("\e[33m[TEST:GenericContent] Leer no crea; guardar sí\e[39m");
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

    $db = (new BaseModel())->getDatabase();
    if ($db === null) {
        echoTerminal("   \e[31mSin conexión: la suite no puede correr.\e[39m");
        return ['success' => false, 'message' => 'sin conexión'];
    }

    //Se aparta la fila de un contenido REAL y se devuelve intacta al terminar: es la única
    //forma de medir el camino «no hay fila» sobre un contenido que la clase declara.
    $contenido = GenericContent::CONTENT_TOKENS_USED;
    $clave = sha1(GenericContent::class) . "|{$contenido}";
    $filas = fn (): int => (int) $db->query("SELECT COUNT(*) FROM pcsphp_app_config")->fetchColumn();
    $existe = fn (): bool => (bool) $db->query("SELECT COUNT(*) FROM pcsphp_app_config WHERE name = " . $db->quote($clave))->fetchColumn();

    $lectura = $db->prepare("SELECT * FROM pcsphp_app_config WHERE name = ?");
    $lectura->execute([$clave]);
    $original = $lectura->fetch(\PDO::FETCH_ASSOC);
    $original = is_array($original) ? $original : null;

    try {
        if ($original !== null) {
            $db->prepare("DELETE FROM pcsphp_app_config WHERE id = ?")->execute([$original['id']]);
        }
        $check(!$existe(), 'se parte de un contenido declarado SIN fila');

        //──── ABRIR Y NO ENVIAR ─────────────────────────────────────────────────────────
        $antes = $filas();
        $abierto = new GenericContent($contenido);
        $check($filas() === $antes && !$existe(),
            'CONSTRUIR el mapper —abrir el formulario— NO deja fila',
            "antes={$antes} después=" . $filas());

        //Y leerlo tampoco: el valor por defecto vive en la clase, no en la fila.
        $antes = $filas();
        $valor = GenericContent::getContentData($contenido, 'POR-DEFECTO');
        $check($filas() === $antes && !$existe(),
            'LEER el contenido tampoco deja fila',
            "antes={$antes} después=" . $filas());
        $check($valor !== null, 'y devuelve el valor por defecto de la clase', (string) json_encode($valor));

        //──── ENVIAR ────────────────────────────────────────────────────────────────────
        $antes = $filas();
        $abierto->save();
        $check($filas() === $antes + 1 && $existe(),
            'GUARDAR —enviar el formulario— SÍ la deja',
            "antes={$antes} después=" . $filas());

        //Y guardar otra vez no duplica.
        $antes = $filas();
        $otro = new GenericContent($contenido);
        $otro->save();
        $check($filas() === $antes, 'guardar de nuevo NO duplica la fila', "antes={$antes} después=" . $filas());

    } catch (\Throwable $exception) {
        $check(false, 'la suite corre sin excepciones', $exception->getMessage());
    } finally {
        $db->prepare("DELETE FROM pcsphp_app_config WHERE name = ?")->execute([$clave]);
        if ($original !== null) {
            $db->prepare("INSERT INTO pcsphp_app_config (id, name, value) VALUES (?,?,?)")
               ->execute([$original['id'], $original['name'], $original['value']]);
        }
    }

    $total = $passed + $failed;
    echoTerminal('');
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Construir y leer un contenido genérico no crea su fila; guardarlo sí.')->setEffects([CliActions::EFFECT_DATABASE])->register();
