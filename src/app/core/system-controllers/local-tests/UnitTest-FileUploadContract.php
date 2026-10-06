<?php

//El contrato del que dependen nueve accesos a `$_FILES` sin isset() delante. Ver T135.

use PiecesPHP\Core\Forms\FileUpload;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/file-upload-contract', function ($args) {

    echoTerminal("\e[33m[TEST:FileUploadContract] Si validate() dice true, la clave de \$_FILES existe\e[39m");
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

    $originales = $_FILES;

    //─── 1/3 · La clave que no está ────────────────────────────────────────────────────
    echoTerminal('[1/4] Sin la clave en $_FILES, validate() NO puede decir que sí');

    $_FILES = [];
    $ausente = new FileUpload('no_existe_esta_clave');
    $check($ausente->validate() === false, 'con $_FILES vacío, validate() devuelve false',
        var_export($ausente->validate(), true));

    $_FILES = ['otra_cosa' => ['name' => 'x.txt', 'type' => 'text/plain', 'size' => 1,
        'tmp_name' => '/tmp/x', 'error' => \UPLOAD_ERR_OK]];
    $otra = new FileUpload('no_existe_esta_clave');
    $check($otra->validate() === false, 'y con otra clave presente, tampoco',
        var_export($otra->validate(), true));

    //Sin esto, la rama del FAKE_ERROR y el `else` final se cubren la una a la otra: quitando
    //cualquiera de las dos la suite seguia en verde, y solo una da el mensaje correcto.
    $_FILES = [];
    $mensajes = (new FileUpload('sigue_sin_existir'));
    $mensajes->validate();
    $check(in_array('No se ha subido ningún archivo.', $mensajes->getErrorMessages(), true),
        'y lo dice por su nombre, no con el mensaje generico',
        implode(' | ', $mensajes->getErrorMessages()));
    echoTerminal(' ');

    //─── 2/3 · Y no lo hace lanzando ───────────────────────────────────────────────────
    echoTerminal('[2/4] Y responde false, no revienta: el llamador puede ramificar');

    //Sin esto, «devuelve false» y «lanza» se confundirían: los nueve accesos van detrás
    //de un `if ($valid)`, que una excepción nunca alcanzaría.
    $lanzo = false;
    try {
        $_FILES = [];
        (new FileUpload('tampoco_existe'))->validate();
    } catch (\Throwable $e) {
        $lanzo = true;
    }
    $check($lanzo === false, 'una clave ausente no lanza excepción');
    echoTerminal(' ');

    //─── 3/3 · El sitio donde el contrato se consume ───────────────────────────────────
    echoTerminal('[3/4] La clave sí presente llega a `error` sin inventarse nada');

    $_FILES = ['un_campo' => ['name' => 'documento.pdf', 'type' => 'application/pdf',
        'size' => 10, 'tmp_name' => FileUpload::NOT_UPLOAD_FAKE_TMP_NAME, 'error' => \UPLOAD_ERR_OK]];
    $presente = new FileUpload('un_campo');
    //`tmp_name` falso: `is_uploaded_file()` dice que no, y esa rama tambien es un false honesto.
    $check($presente->validate() === false, 'un tmp_name que no es una subida real también da false');
    $check(count($presente->getErrorMessages()) > 0, 'y deja dicho por qué',
        implode(' | ', $presente->getErrorMessages()));

    echoTerminal(' ');

    //─── 4/4 · Un codigo de error que no conocemos tampoco pasa por bueno ──────────────
    echoTerminal('[4/4] Un código de error desconocido no se cuela');

    //Sin esta comprobacion el `else` final de validate() no lo prueba NADIE: quitarlo dejaba
    //la suite en verde, porque la rama del FAKE_ERROR lo tapaba. LEY 24.
    $_FILES = ['un_campo' => ['name' => 'x.pdf', 'type' => 'application/pdf', 'size' => 10,
        'tmp_name' => '/tmp/x', 'error' => 99]];
    $desconocido = new FileUpload('un_campo');
    $check($desconocido->validate() === false, 'un `error` que no es ningún UPLOAD_ERR_* da false',
        var_export($desconocido->validate(), true));

    $_FILES = $originales;

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Si FileUpload::validate() devuelve true, la clave existe en $_FILES.')->setEffects([CliActions::EFFECT_NONE])->register();
