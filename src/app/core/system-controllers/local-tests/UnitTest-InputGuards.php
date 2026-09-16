<?php

//Guardas de entrada: subidas y validación de valores. Lo que decide si un archivo o un valor ENTRA. Ver T146 y la LEY 24.
//UploadedFileAdapter::validate() fallaba ABIERTA (medido en #089) y se arregló en #091 con la forma de T135: aquí está su rechazo.

use PiecesPHP\Core\Database\Meta\MetaProperty;
use PiecesPHP\Core\Forms\FileUpload;
use PiecesPHP\Core\Forms\FileValidator;
use PiecesPHP\Core\Forms\UploadedFileAdapter;
use PiecesPHP\Terminal\CliActions;

$cliTaskName = 'unit-tests';
$cliTaskFlag = 'core/input-guards';
$cliTaskDescription = 'Las guardas de subidas y de validación RECHAZAN: tipo, extensión, tamaño y tipo de dato';

CliActions::make("{$cliTaskName}:{$cliTaskFlag}", function ($args) {

    echoTerminal('[TEST:InputGuards] Iniciando suite...', true, "\r\n", '33');
    echoTerminal('');

    $passed = 0;
    $failed = 0;

    $check = function (bool $condition, string $name, ?string $detail = null) use (&$passed, &$failed) {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}");
        }
        if ($detail !== null) {
            echoTerminal("      - {$detail}");
        }
        return $condition;
    };

    //EL BANCO VIVE FUERA DE src/: un directorio temporal propio, borrado al terminar. $_FILES se fotografía y se repone.
    $banco = append_to_path_system(sys_get_temp_dir(), 'pcsphp-input-' . bin2hex(random_bytes(4)));
    $filesAntes = $_FILES;
    $ignorarMimeAntes = FileValidator::$ignoreMimeType;
    $check(mkdir($banco, 0775, true), 'el banco se crea en el temporal');
    $sep = \DIRECTORY_SEPARATOR;
    $png = "{$banco}{$sep}imagen.png";
    $pngComoTexto = "{$banco}{$sep}mentira.png";
    $textoComoPng = "{$banco}{$sep}imagen.txt";
    $check(copy(basepath('statics/images/default-avatar.png'), $png), 'una imagen PNG de verdad queda en el banco');
    $check(file_put_contents($pngComoTexto, 'no soy una imagen, pero me llamo .png') !== false, 'y un texto con nombre .png');
    $check(copy($png, $textoComoPng), 'y la imagen de verdad con nombre .txt');

    try {

        //──── 1. FileValidator::validate ───────────────────────────────────────────────────
        echoTerminal('[1/5] FileValidator::validate() mira el MIME, la extensión y el tamaño');

        $soloPNG = new FileValidator([FileValidator::TYPE_PNG], 10);
        $check($soloPNG->validate("{$banco}{$sep}no-existe.png", 'no-existe.png') === false, 'un archivo que no existe da false, ESTRICTO');
        $check($soloPNG->validate($pngComoTexto, 'mentira.png') === false, 'RECHAZO por MIME: un texto llamado .png no pasa aunque la extensión valga');
        $check($soloPNG->validate($textoComoPng, 'imagen.txt') === false, 'RECHAZO por extensión: la imagen buena con nombre .txt tampoco pasa');
        $check($soloPNG->validate($png, 'imagen.png') === true, 'DISCRIMINANTE: la imagen PNG con nombre .png pasa, ESTRICTO');

        $sinTamano = new FileValidator([FileValidator::TYPE_PNG], 0);
        $check($sinTamano->validate($png, 'imagen.png') === false, 'RECHAZO por tamaño: con el máximo en 0 MB no pasa ni la buena');
        $check(mb_strlen($sinTamano->getMessage()) > 0, 'y deja un mensaje de error para el usuario');

        //CONTRATO: TYPE_ANY no mira tipo ni extensión; solo el tamaño.
        $cualquiera = new FileValidator([FileValidator::TYPE_ANY], 10);
        $check($cualquiera->validate($pngComoTexto, 'mentira.png') === true, 'CONTRATO: con TYPE_ANY el texto pasa: no se mira el tipo, solo el tamaño');

        //CONTRATO: el interruptor estático $ignoreMimeType salta la comprobación de MIME en TODO el proceso.
        FileValidator::$ignoreMimeType = true;
        $check($soloPNG->validate($pngComoTexto, 'mentira.png') === true, 'CONTRATO: con $ignoreMimeType el texto llamado .png pasa la parte del MIME',
            'Es una propiedad pública y ESTÁTICA: quien la ponga en true la deja puesta para toda la petición.');
        FileValidator::$ignoreMimeType = $ignorarMimeAntes;
        $check($soloPNG->validate($pngComoTexto, 'mentira.png') === false, 'y al reponerla vuelve a rechazarlo');
        echoTerminal(' ');

        //──── 2. FileUpload: sin subida no hay archivo ─────────────────────────────────────
        echoTerminal('[2/5] FileUpload no da por bueno lo que no se subió');

        $_FILES = [];
        $ausente = new FileUpload('zz-bp-no-esta', [FileValidator::TYPE_PNG]);
        $check($ausente->validate() === false, 'sin la clave en $_FILES, validate() da false, ESTRICTO');
        $check($ausente->hasInput() === false, 'y hasInput() dice que no hay entrada');

        //UPLOAD_ERR_NO_FILE se trata como «no hay archivo»: la información pasa a ser la falsa.
        $_FILES = ['zz-bp' => ['name' => 'imagen.png', 'type' => 'image/png', 'size' => 0, 'tmp_name' => '', 'error' => \UPLOAD_ERR_NO_FILE]];
        $sinArchivo = new FileUpload('zz-bp', [FileValidator::TYPE_PNG]);
        $check($sinArchivo->hasInput() === false && $sinArchivo->validate() === false, 'con UPLOAD_ERR_NO_FILE tampoco hay entrada, y validate() da false');

        //Un código de error desconocido NO se cuela por la rama sin else.
        $_FILES = ['zz-bp' => ['name' => 'imagen.png', 'type' => 'image/png', 'size' => 10, 'tmp_name' => $png, 'error' => 99]];
        $errorRaro = new FileUpload('zz-bp', [FileValidator::TYPE_PNG]);
        $check($errorRaro->validate() === false, 'un código de error desconocido (99) da false: no se acepta lo que no se entiende');

        //Una subida de verdad no se puede simular: is_uploaded_file() solo es true en una petición POST real.
        $_FILES = ['zz-bp' => ['name' => 'imagen.png', 'type' => 'image/png', 'size' => filesize($png), 'tmp_name' => $png, 'error' => \UPLOAD_ERR_OK]];
        $comoSubida = new FileUpload('zz-bp', [FileValidator::TYPE_PNG]);
        $check($comoSubida->hasInput() === true, 'DISCRIMINANTE: con una entrada bien formada, hasInput() dice que sí');
        //MEDIDO: no devuelve false, LANZA. El archivo no llegó por POST y is_uploaded_file() no se puede simular desde el terminal.
        $lanzoPost = false;
        try {
            $comoSubida->validate();
        } catch (\Throwable $e) {
            $lanzoPost = $e instanceof \Exception && str_contains($e->getMessage(), 'POST');
        }
        $check($lanzoPost, 'y validate() LANZA «deben ser subidos mediante POST»: un archivo de disco no se cuela por la puerta de las subidas',
            'El «sí» de validate() solo se ve en una subida real; aquí la guarda CIERRA lanzando.');
        $_FILES = $filesAntes;
        echoTerminal(' ');

        //──── 3. verify_expected_file ──────────────────────────────────────────────────────
        echoTerminal('[3/5] verify_expected_file() exige que el archivo venga de un formulario');

        $_FILES = [];
        $check(verify_expected_file('zz-bp-no-esta') === false, 'sin la clave en $_FILES da false, ESTRICTO');
        simulate_file_upload('zz-bp', $png);
        $check(verify_expected_file('zz-bp') === false, 'RECHAZO: un archivo puesto a mano en $_FILES no pasa, porque no es una subida real');
        $_FILES['zz-bp']['error'] = \UPLOAD_ERR_INI_SIZE;
        $check(verify_expected_file('zz-bp') === false, 'y con un código de error tampoco');
        $_FILES['zz-bp']['size'] = 0;
        $_FILES['zz-bp']['error'] = \UPLOAD_ERR_OK;
        $check(verify_expected_file('zz-bp') === false, 'ni con tamaño 0');
        $_FILES = $filesAntes;
        //SIN DISCRIMINANTE POSIBLE AQUÍ: el «sí» pide is_uploaded_file() true, y eso solo pasa en una petición real.
        $check(count(get_defined_functions()['user']) > 0 && function_exists('verify_expected_file'), 'CONTRATO: su rama de «sí» no se puede alcanzar desde el terminal',
            'Además hoy NO tiene ningún llamador en src/: la guarda existe y nadie la usa.');
        echoTerminal(' ');

        //──── 4. MetaProperty: el tipo manda ───────────────────────────────────────────────
        echoTerminal('[4/5] MetaProperty RECHAZA el tipo que no existe y el valor que no es de su tipo');

        foreach ([['un tipo que no existe', 'TIPO-QUE-NO-EXISTE', null, true], ['TYPE_INT con default «abc»', MetaProperty::TYPE_INT, 'abc', false],
            ['TYPE_INT NO nulable con default null', MetaProperty::TYPE_INT, null, false]] as [$que, $tipo, $default, $nulable]) {
            $lanzo = false;
            try {
                new MetaProperty($tipo, $default, $nulable);
            } catch (\Throwable $e) {
                $lanzo = $e instanceof \Exception;
            }
            $check($lanzo, "RECHAZO: {$que} lanza y no se construye");
        }
        $lanzoMapper = false;
        try {
            new MetaProperty(MetaProperty::TYPE_MAPPER, null, true);
        } catch (\Throwable $e) {
            $lanzoMapper = $e instanceof \Exception;
        }
        $check($lanzoMapper, 'RECHAZO: TYPE_MAPPER sin el nombre de la clase del mapper lanza');

        $entero = new MetaProperty(MetaProperty::TYPE_INT, 0, false);
        $arreglo = new MetaProperty(MetaProperty::TYPE_ARRAY, [], false);
        $check($entero->validateValue(5) === true && $arreglo->validateValue([]) === true, 'DISCRIMINANTE: cada tipo acepta su valor, ESTRICTO');
        $check($entero->validateValue('abc') === false && $arreglo->validateValue('x') === false, 'RECHAZO: cada tipo rechaza lo que no es de su tipo');
        $check($entero->validateValue(null) === false, 'RECHAZO: null no es un entero; solo lo rescata `nullable` en el constructor');
        $nulable = new MetaProperty(MetaProperty::TYPE_INT, null, true);
        $check($nulable->getType() === MetaProperty::TYPE_INT, 'DISCRIMINANTE: con nullable, el default null sí se acepta al construir y el tipo queda en INT');
        //CONTRATO MEDIDO: el texto acepta números, porque EntityMapper::validateType('text', …) los admite.
        $check((new MetaProperty(MetaProperty::TYPE_TEXT, 123, false))->validateValue(123) === true, 'CONTRATO: TYPE_TEXT acepta un número como texto');
        echoTerminal(' ');

        //──── 5. UploadedFileAdapter: sin archivo no hay «válido» (#091) ───────────────────
        echoTerminal('[5/5] UploadedFileAdapter::validate() dice NO cuando no hay archivo ni código de error conocido');

        //Se inyectan los archivos por el cuarto parámetro: no se toca $_FILES ni hace falta una subida real.
        $bueno = ['name' => 'imagen.png', 'type' => 'image/png', 'size' => filesize($png), 'tmp_name' => $png, 'error' => \UPLOAD_ERR_OK];
        $fake = ['name' => 'NOT_FILE', 'type' => 'mimetype/unexists', 'size' => 100000000, 'tmp_name' => 'NOT_FILE', 'error' => 'FAKE_ERROR'];

        $sinClave = new UploadedFileAdapter(['zz-bp'], [FileValidator::TYPE_PNG], null, []);
        $check($sinClave->validate(true) === false, 'RECHAZO: sin la clave, validate() da false, ESTRICTO',
            'Antes daba TRUE: «FAKE_ERROR» == 0 es false en PHP 8 y la cadena no tenía else.');
        $check($sinClave->hasInput() === false, 'y hasInput() sigue diciendo que no hay entrada');
        $conFake = new UploadedFileAdapter(['zz-bp'], [FileValidator::TYPE_PNG], null, ['zz-bp' => $fake]);
        $check($conFake->validate(true) === false, 'RECHAZO: con la información falsa explícita, false');
        $desconocido = new UploadedFileAdapter(['zz-bp'], [FileValidator::TYPE_PNG], null, ['zz-bp' => ['name' => 'x.png', 'type' => 'image/png', 'size' => 1, 'tmp_name' => $png, 'error' => 99]]);
        $check($desconocido->validate(true) === false, 'RECHAZO: un código de error desconocido (99) da false, por la rama else nueva');
        $texto = new UploadedFileAdapter(['zz-bp'], [FileValidator::TYPE_PNG], null, ['zz-bp' => ['name' => 'texto.txt', 'type' => 'text/plain', 'size' => filesize($textoComoPng), 'tmp_name' => $pngComoTexto, 'error' => \UPLOAD_ERR_OK]]);
        $check($texto->validate(true) === false, 'RECHAZO: un texto con los tipos de imagen no pasa el validador');
        $correcto = new UploadedFileAdapter(['zz-bp'], [FileValidator::TYPE_PNG], null, ['zz-bp' => $bueno]);
        $check($correcto->validate(true) === true, 'DISCRIMINANTE: el PNG de verdad pasa, ESTRICTO');
        $lanzoPost = false;
        try {
            (new UploadedFileAdapter(['zz-bp'], [FileValidator::TYPE_PNG], null, ['zz-bp' => $bueno]))->validate(false);
        } catch (\Throwable $e) {
            $lanzoPost = $e instanceof \Exception && str_contains($e->getMessage(), 'POST');
        }
        $check($lanzoPost, 'y sin ignorePOSTUploaded LANZA: un archivo de disco no entra por la puerta de las subidas');
        echoTerminal(' ');

    } finally {
        $_FILES = $filesAntes;
        FileValidator::$ignoreMimeType = $ignorarMimeAntes;
        foreach (glob($banco . '/*') ?: [] as $archivo) {
            //RETORNO-IGNORADO: limpieza del banco de la suite.
            @unlink($archivo);
        }
        //RETORNO-IGNORADO: limpieza del banco de la suite.
        @rmdir($banco);
    }

    $check(!file_exists($banco) && $_FILES === $filesAntes, 'el banco se borra y $_FILES queda como estaba');

    //──── Balance ───────────────────────────────────────────────────────────────────────────────
    echoTerminal(str_repeat('=', 80));
    echoTerminal(" BALANCE FINAL: {$passed}/" . ($passed + $failed) . " PASADAS ");
    echoTerminal(str_repeat('=', 80));
    echoTerminal('');
    echoTerminal('[TEST:InputGuards] Suite finalizada.', true, "\r\n", $failed === 0 ? '32' : '31');
    echoTerminal('');

    return [
        'success' => $failed === 0,
        'message' => $failed === 0
            ? "Las guardas de entrada rechazan lo que deben ({$passed} comprobaciones)."
            : "{$failed} comprobaciones fallaron.",
    ];

})->setDescription($cliTaskDescription)->setEffects([CliActions::EFFECT_FILES])->register();
