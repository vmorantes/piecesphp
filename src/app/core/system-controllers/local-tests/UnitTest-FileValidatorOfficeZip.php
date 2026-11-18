<?php

//H347 (4): PPT, PPTX y ZIP en FileValidator, opcionales y por contenido; un .zip que es otra cosa se rechaza.
//Escribe sus muestras en app/cache y las borra.
//@areas nucleo

use PiecesPHP\Core\Forms\FileValidator;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/file-validator-office-zip', function ($args) {

    echoTerminal("\e[33m[TEST:file-validator-office-zip] PPT, PPTX y ZIP en FileValidator\e[39m");
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

    //Una presentación de una diapositiva hecha con LibreOffice (MS PowerPoint 97), en gzip: libmagic la reconoce por sus
    //flujos de resumen, que no se pueden fabricar en pocas líneas.
    $pptSample = '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';

    $directory = basepath('app/cache/zz-file-validator-office-zip-' . bin2hex(random_bytes(4)));
    $samples = [];

    try {
        if (!is_dir($directory) && !mkdir($directory, 0775, true)) {
            throw new \RuntimeException("no se pudo crear {$directory}");
        }
        $write = function (string $name, string $content) use ($directory, &$samples): string {
            $path = "{$directory}/{$name}";
            if (file_put_contents($path, $content) === false) {
                throw new \RuntimeException("no se pudo escribir {$path}");
            }
            $samples[] = $path;
            return $path;
        };
        $zipWith = function (string $name, array $entries) use ($directory, &$samples): string {
            $path = "{$directory}/{$name}";
            $zip = new \ZipArchive();
            $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
            foreach ($entries as $entry => $content) {
                $zip->addFromString($entry, $content);
            }
            $zip->close();
            $samples[] = $path;
            return $path;
        };

        $ppt = $write('muestra.ppt', (string) gzdecode((string) base64_decode($pptSample)));
        $pptx = $zipWith('muestra.pptx', [
            '[Content_Types].xml' => '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Override PartName="/ppt/presentation.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.presentation.main+xml"/></Types>',
            '_rels/.rels' => '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"/>',
            'ppt/presentation.xml' => '<?xml version="1.0"?><p:presentation xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main"/>',
        ]);
        $zip = $zipWith('muestra.zip', ['zz.txt' => 'zz-file-validator']);
        $zipAsPptx = $zipWith('zip-renombrado.pptx', ['zz.txt' => 'zz-file-validator']);
        $phpAsZip = $write('php-renombrado.zip', "<?php echo 'zz';\n");
        //El registro final de un ZIP vacío: con él, libmagic da application/zip a cualquier contenido.
        $eocd = "PK\x05\x06" . str_repeat("\0", 18);
        $phpWithEocd = $write('php-con-final-zip.zip', "<?php echo 'zz';\n" . $eocd);
        $htmlWithEocd = $write('html-con-final-zip.zip', "<html><body>zz</body></html>\n" . $eocd);
        $zipAsPdf = $zipWith('zip-como.pdf', ['zz.txt' => 'zz-file-validator']);

        $accepts = function (array $types, string $file): bool {
            return (new FileValidator($types, 5))->validate($file, basename($file));
        };

        echoTerminal('[1] Aceptados cuando el campo los pide');
        $check($accepts([FileValidator::TYPE_PPT], $ppt), '1a un PPT con TYPE_PPT');
        $check($accepts([FileValidator::TYPE_PPTX], $pptx), '1b un PPTX con TYPE_PPTX');
        $check($accepts([FileValidator::TYPE_ZIP], $zip), '1c un ZIP con TYPE_ZIP');
        echoTerminal(' ');

        echoTerminal('[2] Rechazados cuando el campo no los pide');
        $check(!$accepts([FileValidator::TYPE_PDF, FileValidator::TYPE_DOCX], $ppt), '2a un PPT en un campo de PDF y DOCX');
        $check(!$accepts([FileValidator::TYPE_DOCX, FileValidator::TYPE_XLSX], $pptx), '2b un PPTX en un campo de DOCX y XLSX');
        $check(!$accepts([FileValidator::TYPE_PDF], $zip), '2c un ZIP en un campo de PDF');
        $check(!$accepts([FileValidator::TYPE_PPT], $pptx) && !$accepts([FileValidator::TYPE_PPTX], $ppt), '2d PPT y PPTX no se confunden');
        echoTerminal(' ');

        echoTerminal('[3] Por contenido, no por el nombre');
        $check(!$accepts([FileValidator::TYPE_ZIP], $phpAsZip), '3a un PHP renombrado a .zip se rechaza');
        $check(!$accepts([FileValidator::TYPE_PPTX], $zipAsPptx), '3b un ZIP cualquiera renombrado a .pptx se rechaza');
        $check(!$accepts([FileValidator::TYPE_ZIP], $pptx), '3c un PPTX (que por dentro es un ZIP) no pasa como ZIP: su MIME es el de PowerPoint');
        $check(!$accepts([FileValidator::TYPE_ZIP], $phpWithEocd) && !$accepts([FileValidator::TYPE_ZIP], $htmlWithEocd), '3d un PHP o un HTML con el registro final de un ZIP se rechazan: falta la firma al principio');
        $check(!$accepts([FileValidator::TYPE_ZIP, FileValidator::TYPE_PDF], $zipAsPdf), '3e en un campo de ZIP y PDF, un ZIP llamado .pdf se rechaza: un ZIP lleva la extensión .zip');
        $check($accepts([FileValidator::TYPE_ZIP], $write('vacio.zip', $eocd)), '3f un ZIP vacío (empieza por su registro final) se acepta');
        echoTerminal(' ');

        echoTerminal('[4] Opcionales: ningún campo del framework los acepta solo por existir');
        $users = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(basepath('app'), \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            $path = str_replace('\\', '/', (string) $file);
            if (!str_ends_with($path, '.php') || str_contains($path, '/local-tests/') || str_ends_with($path, '/Forms/FileValidator.php')) {
                continue;
            }
            $source = (string) file_get_contents($path);
            if (preg_match('/FileValidator::(TYPE_PPTX?|TYPE_ZIP|MIME_TYPES|EXTENSIONS)\b/', $source) === 1) {
                $users[] = str_replace(basepath(''), '', $path);
            }
        }
        $check($users === [], '4a ningún archivo del framework pide PPT, PPTX o ZIP ni recorre las listas enteras', implode(', ', $users));
        $validatorSource = (string) file_get_contents(basepath('app/core/psr4/PiecesPHP/Core/Forms/FileValidator.php'));
        $check(!str_contains($validatorSource, 'ZipArchive') && !str_contains($validatorSource, 'zip_open'), '4b FileValidator no abre un ZIP: mira su tipo, no su contenido');
        echoTerminal(' ');

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    } finally {
        foreach ($samples as $path) {
            //RETORNO-IGNORADO: limpieza de muestras propias; lo que no se borre queda en app/cache y no afecta al resultado.
            if (is_file($path)) {
                @unlink($path);
            }
        }
        //RETORNO-IGNORADO: la carpeta de muestras, vacía; si no se borra queda en app/cache.
        if (is_dir($directory)) {
            @rmdir($directory);
        }
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('H347 (4): PPT, PPTX y ZIP en FileValidator, opcionales y por contenido.')->setEffects([CliActions::EFFECT_FILES])->register();
