<?php

//El gestor de archivos es elFinder: escribe la subida sin pasar por safe_upload_name(), y su puerta es la opción
//acceptedName de cada volumen. Se mide el patrón y que esté cableado, sin red y sin subir nada (pendientes.md 464).

use PiecesPHP\FileManager\Controllers\FileManagerController;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/file-manager-upload-names', function ($args) {

    echoTerminal("\e[33m[TEST:FileManagerUploadNames] El gestor rechaza por nombre lo que un servidor podría ejecutar, y la opción llega a cada volumen de subida\e[39m");
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

    $pattern = FileManagerController::acceptedNamePattern();

    //El patrón depende de DIRECTORY_SEPARATOR. Para medir las dos ramas en cualquier máquina se rearma aquí con la misma
    //fórmula, y c3 ata la copia al código: la rama de la plataforma en curso tiene que ser idéntica a la del controlador.
    $extensions = implode('|', array_map(fn (string $e): string => preg_quote($e, '/'), FileManagerController::NOT_ACCEPTED_EXTENSIONS));
    $build = function (string $separator) use ($extensions): string {
        $forbidden = $separator === '/' ? '\/\x00' : '\/\x00\\\:*?"<>|';
        return '/^(?![\.' . $forbidden . '])(?!.*\.(?:' . $extensions . ')(?:\.|$))[^' . $forbidden . ']+$/i';
    };
    $platforms = ['Linux' => $build('/'), 'Windows' => $build('\\')];

    echoTerminal('[canario] El instrumento ve');
    $check(count(FileManagerController::NOT_ACCEPTED_EXTENSIONS) > 0, 'c1 NOT_ACCEPTED_EXTENSIONS no está vacía');
    //preg_match devuelve false con un patrón roto, y false no es 0: sin esto, un patrón roto se leería como «no casa».
    $check(preg_match($pattern, '') !== false, 'c2 el patrón del controlador es una expresión que preg_match acepta', $pattern);
    $check($build(DIRECTORY_SEPARATOR) === $pattern, 'c3 la copia de la suite es idéntica al patrón del controlador en esta plataforma', $build(DIRECTORY_SEPARATOR));
    foreach ($platforms as $platform => $platformPattern) {
        $check(preg_match($platformPattern, '') !== false, "c4 el patrón de {$platform} es una expresión que preg_match acepta", $platformPattern);
    }
    echoTerminal(' ');

    $mustPass = [
        'foto.jpg', 'informe.pdf', 'acta 2026.docx', 'plano.svg', 'a.b.c.txt', 'Documento final (v2).odt', 'imagen.PNG',
        'datos.csv', 'video.mp4', 'archivo-sin-extension', 'nombre.con.muchos.puntos.zip', 'mapa.geojson',
        'presupuesto.xlsx', 'audio.mp3',
        //Sin falso positivo por subcadena.
        'php-para-principiantes.pdf', 'asparagus.jpg',
    ];
    $mustFail = [
        'shell.php', 'shell.PHP', 'shell.php5', 'shell.php8', 'shell.phtml', 'x.phar', 'x.pht', 'x.phps', 'x.cgi',
        'x.fcgi', 'x.asp', 'x.aspx', 'x.jsp', 'x.jspx', 'x.shtml', 'x.shtm', '.htaccess', 'foo.htaccess',
        'foo.htpasswd', 'shell.php.jpg', 'shell.phtml.png', 'shell.PHP.JPG', 'a/b.jpg', '.oculto',
        //Un servidor con AddHandler ejecuta la extensión aunque no sea la última.
        'informe.phtml.pdf.txt',
    ];
    foreach ($platforms as $platform => $platformPattern) {
        echoTerminal("[A] {$platform}: el patrón deja pasar lo legítimo");
        foreach ($mustPass as $name) {
            $check(preg_match($platformPattern, $name) === 1, "A {$platform} «{$name}» pasa");
        }
        echoTerminal("[A] {$platform}: el patrón rechaza lo ejecutable, en cualquier posición del nombre");
        foreach ($mustFail as $name) {
            $check(preg_match($platformPattern, $name) === 0, "A {$platform} «{$name}» cae");
        }
        echoTerminal(' ');
    }

    echoTerminal('[A] El juego de caracteres es el del driver de cada plataforma');
    foreach (['a\\b.jpg', 'C:cosa.jpg', 'a*b.jpg', 'a?b.jpg', 'a"b.jpg', 'a<b.jpg', 'a>b.jpg', 'a|b.jpg'] as $name) {
        $check(preg_match($platforms['Linux'], $name) === 1, "A Linux «{$name}» pasa");
        $check(preg_match($platforms['Windows'], $name) === 0, "A Windows «{$name}» cae");
    }
    echoTerminal(' ');

    echoTerminal('[B] La opción está cableada en cada volumen de subida');
    //Las raíces de fileManagerConfiguration(), SIN 'permissions': con ellas structureOptions() hace chmod, y desde el
    //CLI, que corre como dueño de las carpetas, el chmod se aplicaría de verdad. acceptedName no depende de esa clave.
    $dirs = [
        ['relativePath' => 'files'],
        ['relativePath' => 'documents', 'trashHash' => 't2_Lw', 'uploadDeny' => [], 'uploadAllow' => ['all']],
        ['relativePath' => 'statics/uploads', 'withBasePath' => false],
        ['relativePath' => 'tmp', 'withBasePath' => false],
    ];
    $trashes = [
        ['id' => '1', 'relativePath' => '.trash'],
        ['id' => '2', 'relativePath' => '.trash-documents', 'uploadDeny' => [], 'uploadAllow' => ['all']],
    ];
    $volumes = array_values(array_filter(
        FileManagerController::structureOptions($dirs, $trashes),
        fn (array $volume): bool => ($volume['driver'] ?? '') === 'LocalFileSystem'
    ));
    $check(count($volumes) === count($dirs), 'B los volúmenes de subida son las ' . count($dirs) . ' raíces', (string) count($volumes));
    foreach ($volumes as $volume) {
        $path = (string) ($volume['path'] ?? '?');
        $check(($volume['acceptedName'] ?? null) === $pattern, "B «{$path}» trae acceptedName con el patrón");
    }
    echoTerminal(' ');

    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('El gestor de archivos rechaza por nombre las extensiones ejecutables con el juego de caracteres de cada plataforma, y acceptedName está cableada en cada volumen de subida.')->setEffects([CliActions::EFFECT_FILES])->register();
