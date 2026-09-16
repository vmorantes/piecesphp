<?php

//Caracterización de 4f: lo que devolvían FileUpload::validate() y UploadedFileAdapter::validate() ANTES de unificarlas.
//Los esperados se midieron contra el código de hoy. Si una unificación cambia uno solo, esta suite cae.

use PiecesPHP\Core\Forms\FileUpload;
use PiecesPHP\Core\Forms\FileValidator;
use PiecesPHP\Core\Forms\UploadedFileAdapter;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/upload-validation', function ($args) {

    echoTerminal("\e[33m[TEST:UploadValidation] Las dos validaciones de subidas, congeladas antes de unificarlas\e[39m");
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

    $filesOriginal = $_FILES;
    $postOriginal = $_POST;

    try {

        $entrada = function ($tmp, $error, string $name = 'archivo.png'): array {
            return ['name' => $name, 'type' => 'image/png', 'tmp_name' => $tmp, 'error' => $error, 'size' => 10];
        };
        $ejecutar = function (callable $crear, callable $validar): array {
            $resultado = ['devuelve' => null, 'mensajes' => null, 'excepcion' => null];
            $objeto = $crear();
            try {
                $resultado['devuelve'] = $validar($objeto);
            } catch (\Throwable $e) {
                $resultado['excepcion'] = $e->getMessage();
            }
            $resultado['mensajes'] = $objeto->getErrorMessages();
            return $resultado;
        };
        $comparar = function (string $caso, array $obtenido, $devuelve, array $mensajes, ?string $excepcion) use ($check): void {
            $check($obtenido['devuelve'] === $devuelve, "{$caso}: validate() devuelve " . var_export($devuelve, true), var_export($obtenido['devuelve'], true));
            $check($obtenido['mensajes'] === $mensajes, "{$caso}: getErrorMessages() exacto", json_encode($obtenido['mensajes'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            $check($obtenido['excepcion'] === $excepcion, "{$caso}: excepción " . var_export($excepcion, true), var_export($obtenido['excepcion'], true));
        };

        $png = realpath(__DIR__ . '/../../../../statics/images/404.png');
        $php = __FILE__;
        $noSubido = 'No se ha subido ningún archivo.';

        //Caso => [tmp_name, error, MAX_FILE_SIZE, [devuelve, mensajes, excepción] de FileUpload, ídem del adaptador]
        $casos = [
            'K2' => [FileUpload::NOT_UPLOAD_FAKE_TMP_NAME, 0, null, [false, [$noSubido], null], [false, [$noSubido], null]],
            'K3' => [$php, 0, null, [null, [], 'Los archivos deben ser subidos mediante POST.'], [null, [], 'Los archivos deben ser subidos mediante POST.']],
            'K4' => ['/tmp/zz-4f', \UPLOAD_ERR_INI_SIZE, null, [false, ['El archivo excede el peso máximo permitido por el servidor. (500MB)'], null], [false, ['El archivo excede el peso máximo permitido por el servidor. (500MB)'], null]],
            'K5' => ['/tmp/zz-4f', \UPLOAD_ERR_FORM_SIZE, null, [false, ['El archivo excede el peso máximo permitido.'], null], [false, ['El archivo excede el peso máximo permitido.'], null]],
            'K6' => ['/tmp/zz-4f', \UPLOAD_ERR_FORM_SIZE, '5000000', [false, ['El archivo excede el peso máximo permitido. (5MB)'], null], [false, ['El archivo excede el peso máximo permitido. (5MB)'], null]],
            'K7' => ['/tmp/zz-4f', \UPLOAD_ERR_PARTIAL, null, [false, ['El archivo no se subió completamente.'], null], [false, ['El archivo no se subió completamente.'], null]],
            //FileUpload convierte UPLOAD_ERR_NO_FILE en la entrada falsa en su constructor: su mensaje es el de «no se ha subido».
            'K8' => ['/tmp/zz-4f', \UPLOAD_ERR_NO_FILE, null, [false, [$noSubido], null], [false, ['No ha subido ningún archivo.'], null]],
            'K9' => ['/tmp/zz-4f', \UPLOAD_ERR_NO_TMP_DIR, null, [false, ['No se ha subido el archivo. Problema con el directorio temporal.'], null], [false, ['No se ha subido el archivo. Problema con el directorio temporal.'], null]],
            'K10' => ['/tmp/zz-4f', \UPLOAD_ERR_CANT_WRITE, null, [false, ['No se ha subido ningún archivo. Problema al escribir en disco.'], null], [false, ['No se ha subido ningún archivo. Problema al escribir en disco.'], null]],
            'K11' => ['/tmp/zz-4f', \UPLOAD_ERR_EXTENSION, null, [false, ['No se ha subido ningún archivo. Problema con alguna extensión.'], null], [false, ['No se ha subido ningún archivo. Problema con alguna extensión.'], null]],
            'K12' => ['/tmp/zz-4f', 99, null, [false, ['No se ha podido validar el archivo subido.'], null], [false, ['No se ha podido validar el archivo subido.'], null]],
        ];

        echoTerminal('[1/4] K1 · sin la clave');
        $_POST = [];
        $_FILES = [];
        $comparar('K1 FileUpload', $ejecutar(fn() => new FileUpload('zz-4f', [FileValidator::TYPE_PNG]), fn($o) => $o->validate()), false, [$noSubido], null);
        $comparar('K1 UploadedFileAdapter', $ejecutar(fn() => new UploadedFileAdapter(['zz-4f'], [FileValidator::TYPE_PNG], null, []), fn($o) => $o->validate()), false, [$noSubido], null);
        echoTerminal(' ');

        echoTerminal('[2/4] K2-K12 · cada código de error, en las dos clases');
        foreach ($casos as $caso => [$tmp, $error, $maxFileSize, $enFileUpload, $enAdaptador]) {
            $_POST = $maxFileSize === null ? [] : ['MAX_FILE_SIZE' => $maxFileSize];
            $archivo = $entrada($tmp, $error);
            $_FILES = ['zz-4f' => $archivo];
            $comparar("{$caso} FileUpload", $ejecutar(fn() => new FileUpload('zz-4f', [FileValidator::TYPE_PNG]), fn($o) => $o->validate()), ...$enFileUpload);
            $comparar("{$caso} UploadedFileAdapter", $ejecutar(fn() => new UploadedFileAdapter(['zz-4f'], [FileValidator::TYPE_PNG], null, ['zz-4f' => $archivo]), fn($o) => $o->validate()), ...$enAdaptador);
        }
        echoTerminal(' ');

        echoTerminal('[3/4] K13-K14 · el adaptador con validate(true)');
        $_POST = [];
        $check($png !== false, 'K13: existe src/statics/images/404.png');
        $comparar('K13 UploadedFileAdapter', $ejecutar(fn() => new UploadedFileAdapter(['zz-4f'], [FileValidator::TYPE_PNG], null, ['zz-4f' => $entrada($png, 0, '404.png')]), fn($o) => $o->validate(true)), true, [], null);
        $comparar('K14 UploadedFileAdapter', $ejecutar(fn() => new UploadedFileAdapter(['zz-4f'], [FileValidator::TYPE_PNG], null, ['zz-4f' => $entrada($php, 0, 'x.php')]), fn($o) => $o->validate(true)), false, ["Tipo de archivo inválido.\r\n"], null);
        echoTerminal(' ');

        echoTerminal('[4/4] K15 · FileUpload múltiple, dos archivos con error');
        $_FILES = ['zz-4f' => [
            'name' => ['a.png', 'b.png'],
            'type' => ['image/png', 'image/png'],
            'tmp_name' => ['/tmp/zz-4f-a', '/tmp/zz-4f-b'],
            'error' => [\UPLOAD_ERR_NO_TMP_DIR, \UPLOAD_ERR_NO_FILE],
            'size' => [10, 10],
        ]];
        $comparar('K15 FileUpload', $ejecutar(fn() => new FileUpload('zz-4f', [FileValidator::TYPE_PNG], null, true), fn($o) => $o->validate()), false, ['No se ha subido el archivo. Problema con el directorio temporal.', $noSubido], null);

    } finally {
        $_FILES = $filesOriginal;
        $_POST = $postOriginal;
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Lo que devuelven FileUpload::validate() y UploadedFileAdapter::validate(), congelado antes de unificarlas.')->setEffects([CliActions::EFFECT_NONE])->register();
