<?php

//La pantalla común de importar, pintada en proceso: la descripción del importador, el aviso de los títulos, la plantilla con
//el nombre con que se descarga, «Archivo de la plantilla» con «Examinar», y el input real oculto SIN required. Un temporal.
//@areas src/app/classes/DataImportExportUtility/

use DataImportExportUtility\Controllers\DataTransferController;
use DataImportExportUtility\DataImportExportUtilityLang;
use DataImportExportUtility\Definitions\UsersImportDefinition;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\CliActions;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;

CliActions::make('unit-tests:core/data-transfer-import-form', function ($args) {

    echoTerminal("\e[33m[TEST:DataTransferImportForm] La pantalla común de importar\e[39m");
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name, string $detail = '') use (&$passed, &$failed): bool {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}" . ($detail !== '' ? " — {$detail}" : ''));
        }
        return $condition;
    };
    $balance = function () use (&$passed, &$failed): array {
        $total = $passed + $failed;
        echoTerminal(' ');
        echoTerminal($failed === 0
            ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
            : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");
        return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];
    };

    $langGroup = DataImportExportUtilityLang::LANG_GROUP;
    //Las mismas variables que importForm() le pasa a la vista.
    $pintar = function ($definition) use ($langGroup): string {
        $helpController = new \DataImportExportUtility\Controllers\HelperController();
        $title = $definition->title();
        $breadcrumbs = [];
        $columns = [];
        foreach ($definition->columns() as $column) {
            $columns[] = ['label' => $column->label(), 'required' => $column->required(), 'aliases' => $column->aliases(), 'help' => $column->helpText(), 'example' => $column->exampleValue()];
        }
        $dryRunAllowed = false;
        $partial = null;
        $extensions = $definition->acceptedExtensions();
        $maxSizeMB = $definition->maxSizeMB();
        $maxRows = $definition->maxRows();
        $action = 'https://zz/action';
        $template = 'https://zz/template';
        ob_start();
        include basepath('app/classes/DataImportExportUtility/Views/data-transfer/import-form.php');
        return (string) ob_get_clean();
    };
    $dom = function (string $html): \DOMXPath {
        $documento = new \DOMDocument();
        @$documento->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        return new \DOMXPath($documento);
    };
    $texto = fn(\DOMXPath $x, string $query): string => ($n = $x->query($query)?->item(0)) instanceof \DOMNode ? trim(preg_replace('/\s+/u', ' ', $n->textContent) ?? '') : '';
    $peticion = fn(string $method, array $query = [], array $body = []) => (new RequestRoute($method, (new UriFactory())->createUri('http://localhost/zz'), new Headers(), [], [], (new StreamFactory())->createStream('')))->withQueryParams($query)->withParsedBody($body);

    try {
        $usuarios = new UsersImportDefinition();
        $limites = sprintf(__($langGroup, 'Archivos %s de hasta %d MB y %d filas. Si una fila tiene errores, no se guarda ninguna.'), implode(', ', $usuarios->acceptedExtensions()), $usuarios->maxSizeMB(), $usuarios->maxRows());
        $html = $pintar($usuarios);
        $x = $dom($html);

        //─── c · La cabecera y el aviso ─────────────────────────────────────────────────────────────────
        echoTerminal('[c] La cabecera dice para qué sirve el importador; los límites, bajo el campo; el aviso, antes del formulario');
        $check($texto($x, '//h1//*[contains(@class,"sub header")]') === $usuarios->description(), 'c1 DISCRIMINANTE: con descripción, la cabecera la usa', $texto($x, '//h1//*[contains(@class,"sub header")]'));
        $check($texto($x, '//*[@id="data-transfer-import-file-help"]') === $limites, 'c2 DISCRIMINANTE: los límites, como ayuda bajo el campo del archivo');
        $sinDescripcion = new class extends UsersImportDefinition {
            public function description(): string
            {
                return '';
            }
        };
        $check($texto($dom($pintar($sinDescripcion)), '//h1//*[contains(@class,"sub header")]') === $limites, 'c3 sin descripción, la cabecera lleva los límites (como antes)');
        $aviso = $x->query('//*[contains(@class,"ui warning icon message")]')?->item(0);
        $avisoEn = strpos($html, 'ui warning icon message');
        $formularioEn = strpos($html, '<form');
        $check($aviso instanceof \DOMElement && $texto($x, '//*[contains(@class,"ui warning icon message")]//*[contains(@class,"header")]') === __($langGroup, 'Mantén los títulos de la plantilla') && $x->query('//*[contains(@class,"ui warning icon message")]/i[contains(@class,"icon")]')?->length === 1, 'c4 DISCRIMINANTE: el aviso «Mantén los títulos de la plantilla», con su icono');
        $check($avisoEn !== false && $formularioEn !== false && $avisoEn < $formularioEn, 'c5 el aviso va sobre el formulario');
        echoTerminal(' ');

        //─── p · La plantilla con su nombre ─────────────────────────────────────────────────────────────
        echoTerminal('[p] Paso 1: el nombre del archivo, el mismo con que se descarga');
        $respuesta = (new DataTransferController())->importTemplate($peticion('GET'), new ResponseRoute(), UsersImportDefinition::class);
        $servido = preg_match('/filename="([^"]+)"/', $respuesta->getHeaderLine('Content-Disposition'), $m) === 1 ? $m[1] : '';
        $check($servido !== '' && $texto($x, '//section[@aria-labelledby="import-step-template"]//strong') === $servido, "p1 DISCRIMINANTE: el paso 1 enseña «{$servido}», el nombre que sirve importTemplate()", $texto($x, '//section[@aria-labelledby="import-step-template"]//strong'));
        $enlace = $x->query('//section[@aria-labelledby="import-step-template"]//a[@href="https://zz/template"]')?->item(0);
        $check($enlace instanceof \DOMElement && $enlace->getAttribute('download') === $servido, 'p2 el enlace lleva download con ese nombre');
        $check($texto($x, '//section[@aria-labelledby="import-step-template"]//small') === sprintf(__($langGroup, '%s · Llénala sin cambiar sus títulos'), __($langGroup, 'Excel')), 'p3 la pista: «Excel · Llénala sin cambiar sus títulos»');
        //Un importador solo csv (sin registrarlo): la plantilla, su nombre y su palabra salen de acceptedExtensions().
        $soloCsv = new class extends UsersImportDefinition {
            public function acceptedExtensions(): array
            {
                return ['csv'];
            }
        };
        $xCsv = $dom($pintar($soloCsv));
        $respuestaCsv = (new DataTransferController())->importTemplate($peticion('GET', ['format' => 'csv']), new ResponseRoute(), UsersImportDefinition::class);
        $servidoCsv = preg_match('/filename="([^"]+)"/', $respuestaCsv->getHeaderLine('Content-Disposition'), $m) === 1 ? $m[1] : '';
        $enlaceCsv = $xCsv->query('//section[@aria-labelledby="import-step-template"]//a[@download]')?->item(0);
        $check($servidoCsv === 'plantilla-users.csv' && $texto($xCsv, '//section[@aria-labelledby="import-step-template"]//strong') === $servidoCsv && $enlaceCsv instanceof \DOMElement && $enlaceCsv->getAttribute('download') === $servidoCsv && $enlaceCsv->getAttribute('href') === 'https://zz/template?format=csv', 'p4 DISCRIMINANTE: solo csv, «plantilla-users.csv» (el que sirve importTemplate con format=csv) y el enlace con format=csv', $enlaceCsv instanceof \DOMElement ? $enlaceCsv->getAttribute('href') : '');
        $check($texto($xCsv, '//section[@aria-labelledby="import-step-template"]//small') === sprintf(__($langGroup, '%s · Llénala sin cambiar sus títulos'), __($langGroup, 'CSV')), 'p5 y la pista dice «CSV», no «Excel»');
        $check($x->query('//*[@data-transfer-report][@aria-live="polite"]')?->length === 1, 'p6 el resultado se anuncia: aria-live="polite" en su contenedor');
        echoTerminal(' ');

        //─── a · El campo del archivo ───────────────────────────────────────────────────────────────────
        echoTerminal('[a] Paso 2: «Archivo de la plantilla», «Examinar» y el input real oculto sin required');
        $campo = '//*[@data-transfer-file-field]';
        $etiqueta = $x->query("{$campo}/label")?->item(0);
        $nombre = $x->query("{$campo}//input[@type=\"text\"][@readonly]")?->item(0);
        $check($etiqueta instanceof \DOMElement && trim($etiqueta->textContent) === __($langGroup, 'Archivo de la plantilla') && $nombre instanceof \DOMElement && $etiqueta->getAttribute('for') === $nombre->getAttribute('id'), 'a1 DISCRIMINANTE: «Archivo de la plantilla», asociada al campo con el nombre (de solo lectura)');
        $nodoCampo = $x->query($campo)?->item(0);
        $check($nodoCampo instanceof \DOMElement && str_contains($nodoCampo->getAttribute('class'), 'required field') && $nombre instanceof \DOMElement && $nombre->getAttribute('placeholder') === __($langGroup, 'Ningún archivo elegido'), 'a2 obligatorio (con su asterisco) y «Ningún archivo elegido» al principio');
        $boton = $x->query("{$campo}//*[contains(@class,\"ui action input\")]/button[@type=\"button\"][@data-transfer-file-browse]")?->item(0);
        $check($boton instanceof \DOMElement && trim($boton->textContent) === __($langGroup, 'Examinar') && $x->query("{$campo}//button[@data-transfer-file-browse]/i[contains(@class,\"upload icon\")]")?->length === 1, 'a3 DISCRIMINANTE: «Examinar», con upload icon, dentro del ui action input');
        $real = $x->query("{$campo}//input[@type=\"file\"]")?->item(0);
        $check($real instanceof \DOMElement && $real->getAttribute('name') === 'file' && $real->getAttribute('accept') === '.' . implode(',.', $usuarios->acceptedExtensions()), 'a4 el input real conserva name="file" y accept');
        $check($real instanceof \DOMElement && $real->hasAttribute('hidden') && !$real->hasAttribute('required') && $x->query('//input[@hidden][@required]')?->length === 0, 'a5 DISCRIMINANTE: oculto y SIN required (si no, el navegador frena el envío sin mensaje)');
        echoTerminal(' ');

        //─── s · El servidor rechaza un envío sin archivo ───────────────────────────────────────────────
        echoTerminal('[s] Sin archivo, el servidor rechaza (la validación de import.js no es la única puerta)');
        $filesAntes = $_FILES;
        $_FILES = [];
        try {
            $respuesta = (new DataTransferController())->importAction($peticion('POST'), new ResponseRoute(), UsersImportDefinition::class, true);
        } finally {
            $_FILES = $filesAntes;
        }
        $cuerpo = json_decode((string) $respuesta->getBody(), true);
        $check($respuesta->getStatusCode() === 400 && is_array($cuerpo) && ($cuerpo['persisted'] ?? null) === false && count($cuerpo['headerErrors'] ?? []) > 0, 's1 sin archivo: 400, nada guardado y el motivo en headerErrors', "HTTP {$respuesta->getStatusCode()}");
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    }

    return $balance();

})->setDescription('La pantalla común de importar, pintada en proceso: la descripción del importador en la cabecera y los límites bajo el campo, el aviso de los títulos, la plantilla con el nombre con que se descarga, «Archivo de la plantilla» con «Examinar», el input real oculto sin required, y el rechazo del servidor sin archivo.')->setEffects([CliActions::EFFECT_FILES])->register();
