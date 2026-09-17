<?php

//El panel de importación: una ruta por importador, el artefacto solo si se guardó y nada del archivo pintado como HTML.

use DataImportExportUtility\Controllers\DataTransferController;
use DataImportExportUtility\DataImportExportUtilityRoutes;
use PiecesPHP\Core\DataTransfer\Import\Column;
use PiecesPHP\Core\DataTransfer\Import\ImportArtifacts;
use PiecesPHP\Core\DataTransfer\Import\ImportDefinition;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\CliActions;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;

CliActions::make('unit-tests:core/data-transfer-module', function ($args) {

    echoTerminal("\e[33m[TEST:DataTransferModule] Panel de importación sobre DataTransfer\e[39m");
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

    if (!class_exists('ZzDataTransferModuleDefinition', false)) {
        //Definición de prueba: persiste en memoria y devuelve un artefacto.
        class ZzDataTransferModuleDefinition extends ImportDefinition
        {
            /** @var int */
            private static $persistCalls = 0;
            public static function persistCalls(): int { return self::$persistCalls; }
            public static function resetPersistCalls(): void { self::$persistCalls = 0; }
            public function key(): string { return 'zz-prueba'; }
            public function title(): string { return 'Prueba'; }
            public function allowedUserTypes(): array { return [0]; }
            public function columns(): array
            {
                return [new Column('code', 'Código', true), new Column('name', 'Nombre', true)];
            }
            public function persist(array $rows): ?ImportArtifacts
            {
                self::$persistCalls++;
                return new ImportArtifacts('zz-credenciales.csv', 'text/csv', 'zz-contenido-del-artefacto');
            }
        }
    }

    $temporales = [];
    $archivo = function (string $contenido, string $extension) use (&$temporales): string {
        $ruta = sys_get_temp_dir() . '/zz-data-transfer-module-' . bin2hex(random_bytes(4)) . '.' . $extension;
        $temporales[] = $ruta;
        if (file_put_contents($ruta, $contenido) === false) {
            throw new \RuntimeException("No se pudo escribir {$ruta}");
        }
        return $ruta;
    };
    $peticion = fn() => new RequestRoute('POST', (new UriFactory())->createUri('http://localhost/zz-prueba'), new Headers(), [], [], (new StreamFactory())->createStream(''));
    //La acción lee $_FILES; ignorePOSTUploaded porque la CLI no puede hacer una subida HTTP de verdad.
    $importar = function (string $ruta, string $nombre) use ($peticion): array {
        $_FILES = ['file' => ['name' => $nombre, 'type' => 'text/csv', 'size' => (int) filesize($ruta), 'tmp_name' => $ruta, 'error' => UPLOAD_ERR_OK, 'full_path' => $nombre]];
        $respuesta = (new DataTransferController())->importAction($peticion(), new ResponseRoute(), ZzDataTransferModuleDefinition::class, true);
        return [$respuesta->getStatusCode(), json_decode((string) $respuesta->getBody(), true)];
    };
    $filesOriginal = $_FILES;

    try {
        //─── 1/5 · Registro ─────────────────────────────────────────────────────────────────────────────
        echoTerminal('[1/5] DataImportExportUtilityRoutes::importer()');
        $grupo = new RouteGroup('zz-data-transfer-module');
        DataImportExportUtilityRoutes::importer($grupo, ZzDataTransferModuleDefinition::class);
        $rutas = (new \ReflectionProperty($grupo, 'routes'))->getValue($grupo);
        $porNombre = [];
        foreach ($rutas as $ruta) {
            if ($ruta instanceof Route && is_string($ruta->name())) {
                $porNombre[$ruta->name()] = [$ruta->method(), $ruta->routeSegment(), $ruta->rolesAllowed()];
            }
        }
        $esperadas = [
            'data-transfer-import-zz-prueba' => ['GET', '/data-transfer/import/zz-prueba[/]'],
            'data-transfer-import-zz-prueba-action' => ['POST', '/data-transfer/import/zz-prueba/action[/]'],
            'data-transfer-import-zz-prueba-template' => ['GET', '/data-transfer/import/zz-prueba/template[/]'],
        ];
        $bien = count($porNombre) === 3;
        foreach ($esperadas as $nombre => [$metodo, $segmento]) {
            $bien = $bien && isset($porNombre[$nombre]) && $porNombre[$nombre][0] === $metodo && $porNombre[$nombre][1] === $segmento && $porNombre[$nombre][2] === [0];
        }
        $check($bien, 'r1 registra las tres rutas con su nombre, método, URL y los roles de allowedUserTypes()', json_encode($porNombre, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $check(array_key_exists('zz-prueba', DataImportExportUtilityRoutes::importers()), 'r2 el importador queda en el registro del hub');
        foreach ([['r3 una clase que no extiende ImportDefinition', \stdClass::class], ['r4 una key ya registrada', ZzDataTransferModuleDefinition::class]] as [$nombre, $clase]) {
            try {
                DataImportExportUtilityRoutes::importer(new RouteGroup('zz-data-transfer-module-2'), $clase);
                $check(false, "{$nombre} → InvalidArgumentException", 'no lanzó');
            } catch (\InvalidArgumentException $e) {
                $check(true, "{$nombre} → InvalidArgumentException");
            }
        }
        echoTerminal(' ');

        //─── 2/5 · Acción ───────────────────────────────────────────────────────────────────────────────
        echoTerminal('[2/5] Acción');
        ZzDataTransferModuleDefinition::resetPersistCalls();
        [$estado, $json] = $importar($archivo("code,name\nA1,Uno\nA2,Dos\n", 'csv'), 'zz.csv');
        $check($estado === 200 && ($json['persisted'] ?? null) === true && ZzDataTransferModuleDefinition::persistCalls() === 1, 'a1 un CSV válido (text/plain para finfo) se importa: persisted true', json_encode($json, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        $check(base64_decode((string) ($json['artifact']['contentBase64'] ?? ''), true) === 'zz-contenido-del-artefacto' && ($json['artifact']['filename'] ?? null) === 'zz-credenciales.csv', 'a2 el artefacto viaja en base64 y decodifica a su contenido');
        [$estado, $json] = $importar($archivo("code,name\nA1,Uno\n,Dos\n", 'csv'), 'zz.csv');
        $check($estado === 200 && ($json['persisted'] ?? null) === false && !array_key_exists('artifact', $json) && ($json['rows'][0]['position'] ?? null) === 2, 'a3 una fila con code vacío: persisted false y sin artefacto', json_encode($json, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        //El ejecutor solo adjunta artefactos si guardó: un informe construido a mano prueba la condición de la acción.
        $noGuardado = DataTransferController::responseBody(new \PiecesPHP\Core\DataTransfer\Import\ImportReport(1, [], false, [], new ImportArtifacts('zz.csv', 'text/csv', 'zz-no-debe-salir')));
        $check(!array_key_exists('artifact', $noGuardado), 'a3b un informe no guardado con artefacto: la respuesta NO lo lleva', json_encode($noGuardado, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        [$estado, $json] = $importar($archivo("<html><body><table><tr><td>code</td></tr></table><script>alert(1)</script></body></html>\n", 'csv'), 'zz.csv');
        $check($estado === 200 && ($json['persisted'] ?? null) === false && count($json['headerErrors'] ?? []) === 2 && !array_key_exists('artifact', $json), 'a4 un HTML renombrado a .csv se lee como texto: faltan code y name en las cabeceras, nada se ejecuta ni se guarda', json_encode($json, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        [$estado, $json] = $importar($archivo("co\0de,name\nA1,Uno\n", 'csv'), 'zz.csv');
        $check($estado === 400 && ($json['headerErrors'] ?? []) === ['El archivo no es un CSV de texto.'], 'a5 un CSV con un NUL → 400 «El archivo no es un CSV de texto.»', json_encode($json, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        [$estado, $json] = $importar($archivo("code,name\nA1,Uno\n", 'csv'), 'zz.html');
        $check($estado === 400 && ZzDataTransferModuleDefinition::persistCalls() === 1, 'a6 una extensión fuera de acceptedExtensions → 400', json_encode($json, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        echoTerminal(' ');

        //─── 3/5 · Plantilla ────────────────────────────────────────────────────────────────────────────
        echoTerminal('[3/5] Plantilla');
        $respuesta = (new DataTransferController())->importTemplate($peticion(), new ResponseRoute(), ZzDataTransferModuleDefinition::class);
        $cuerpo = (string) $respuesta->getBody();
        $check($cuerpo === "\xEF\xBB\xBFCódigo,Nombre\n" && str_contains($respuesta->getHeaderLine('Content-Disposition'), 'plantilla-zz-prueba.csv'), 't1 la plantilla es un CSV con BOM y la fila de etiquetas', json_encode($cuerpo, JSON_THROW_ON_ERROR));
        echoTerminal(' ');

        //─── 4/5 · CSV de texto ─────────────────────────────────────────────────────────────────────────
        echoTerminal('[4/5] isTextCsv()');
        $largo = str_repeat('a', 8191) . 'é';
        $check(DataTransferController::isTextCsv($archivo("\xEF\xBB\xBFcode,name\n", 'csv')) && DataTransferController::isTextCsv($archivo($largo, 'csv')), 'c1 UTF-8 con BOM, y un carácter multibyte partido en el corte de 8 KB, son texto');
        $check(!DataTransferController::isTextCsv($archivo("code,name\n\xC3\x28", 'csv')), 'c2 UTF-8 inválido no es texto');
        echoTerminal(' ');

        //─── 5/5 · JS ───────────────────────────────────────────────────────────────────────────────────
        echoTerminal('[5/5] El JS del formulario pinta con texto');
        $js = (string) file_get_contents(basepath('app/classes/DataImportExportUtility/Statics/js/data-transfer/import.js'));
        $sinComentarios = (string) preg_replace('#//[^\n]*|/\*.*?\*/#s', '', $js);
        $prohibidos = array_filter(['.html(', '.append(', 'innerHTML', 'outerHTML', 'insertAdjacentHTML', 'document.write'], fn($p) => str_contains($sinComentarios, $p));
        $check(count($prohibidos) === 0 && str_contains($sinComentarios, 'textContent'), 'j1 sin .html(, .append(, innerHTML ni insertAdjacentHTML; usa textContent', implode(', ', $prohibidos));

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        $_FILES = $filesOriginal;
        foreach ($temporales as $ruta) {
            if (is_file($ruta)) {
                //RETORNO-IGNORADO: limpieza del banco de la suite; se comprueba abajo con is_file.
                unlink($ruta);
            }
        }
        $check(count(array_filter($temporales, 'is_file')) === 0, 'z1 limpieza: 0 temporales');
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('El panel de importación registra una ruta por importador y entrega el artefacto solo si se guardó.')->setEffects([CliActions::EFFECT_FILES])->register();
