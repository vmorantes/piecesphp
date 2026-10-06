<?php

/**
 * DataTransferController.php
 */

namespace DataImportExportUtility\Controllers;

use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\UserSystem\ORM\UsersModel;
use DataImportExportUtility\DataImportExportUtilityLang;
use DataImportExportUtility\DataImportExportUtilityRoutes;
use DataImportExportUtility\Presets\UserMetaPresetStore;
use PiecesPHP\Settings\ORM\SettingsModel;
use EventsLog\LogsRoutes;
use EventsLog\Mappers\LogsMapper;
use PiecesPHP\Core\DataTransfer\Export\ExportColumn;
use PiecesPHP\Core\DataTransfer\Export\ExportContext;
use PiecesPHP\Core\DataTransfer\Export\ExportParameterException;
use PiecesPHP\Core\DataTransfer\Export\ExportPresetStore;
use PiecesPHP\Core\DataTransfer\Export\ExportResult;
use PiecesPHP\Core\DataTransfer\Export\ProjectFile;
use PiecesPHP\Core\DataTransfer\Export\ExportDefinition;
use PiecesPHP\Core\DataTransfer\Export\SpreadsheetExportWriter;
use PiecesPHP\Core\DataTransfer\Import\ImportDefinition;
use PiecesPHP\Core\DataTransfer\Import\ImportReport;
use PiecesPHP\Core\DataTransfer\Import\ImportRunner;
use PiecesPHP\Core\DataTransfer\Source\SpreadsheetRowSource;
use PiecesPHP\Core\Forms\FileValidator;
use PiecesPHP\Core\Forms\UploadedFileAdapter;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\RoutingUtils\DefaultAccessControlModules;
use Slim\Psr7\Factory\StreamFactory;

/**
 * DataTransferController - Panel único de importación sobre el motor DataTransfer (ADR 0022).
 *
 * Cada importador registrado tiene sus propias rutas: el nombre de la ruta es su permiso.
 *
 * @package     DataImportExportUtility\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class DataTransferController extends AdminPanelController
{

    use ControllerRoutingTrait;

    /**
     * @var string
     */
    protected static $URLDirectory = 'data-transfer';

    /**
     * @var string
     */
    protected static $baseRouteName = 'data-transfer';

    /**
     * @var HelperController
     */
    protected $helpController = null;

    const LANG_GROUP = DataImportExportUtilityLang::LANG_GROUP;

    /**
     * Prefijo de los temporales de la exportación.
     */
    const EXPORT_TEMP_PREFIX = 'pcsphp-export-';

    /**
     * Filtros guardados: longitud máxima del nombre y cuántos por exportación y usuario.
     */
    const PRESET_NAME_MAX = 60;
    const PRESET_MAX = 20;

    /**
     * Clave de configuración con lo apagado desde la portada: {"import": [keys], "export": [keys]}. Ausente: todo encendido.
     */
    const DISABLED_CONFIG = 'data_transfer_disabled';
    const KIND_IMPORT = 'import';
    const KIND_EXPORT = 'export';

    public function __construct()
    {
        parent::__construct();
        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());
        $this->setInstanceViewDir(__DIR__ . '/../Views/');
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function hub(Request $request, Response $response)
    {
        $importers = self::visibleImporters();

        $exporters = self::visibleExporters();

        $title = __(self::LANG_GROUP, 'Importar y exportar');
        set_title($title);

        set_custom_assets([
            DataImportExportUtilityRoutes::staticRoute('js/data-transfer/hub.js'),
        ], 'js');

        $data = [
            'langGroup' => self::LANG_GROUP,
            'title' => $title,
            'importers' => $importers,
            'exporters' => $exporters,
            'isRoot' => self::isRootUser(),
            'toggleURL' => self::routeName('toggle'),
            'breadcrumbs' => get_breadcrumbs([
                __(self::LANG_GROUP, 'Inicio') => [
                    'url' => \PiecesPHP\AdminPanel\Controllers\AdminPanelController::routeName(''),
                ],
                $title,
            ]),
        ];

        $this->helpController->render('panel/layout/header');
        $this->render('data-transfer/hub', $data);
        $this->helpController->render('panel/layout/footer');

        return $response;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param string $definitionClass
     * @return Response
     */
    public function importForm(Request $request, Response $response, string $definitionClass)
    {
        $definition = self::definition($definitionClass);
        $key = $definition->key();
        if (self::isDisabled(self::KIND_IMPORT, $key)) {
            return throw403($request);
        }

        set_custom_assets([
            DataImportExportUtilityRoutes::staticRoute('js/data-transfer/import.js'),
        ], 'js');

        $title = $definition->title();
        set_title($title);

        $columns = [];
        foreach ($definition->columns() as $column) {
            $columns[] = [
                'label' => $column->label(),
                'required' => $column->required(),
                'aliases' => $column->aliases(),
                'help' => $column->helpText(),
                'example' => $column->exampleValue(),
            ];
        }

        $level = $definition->interfaceLevel();
        $extended = $level >= ImportDefinition::INTERFACE_EXTENDED;
        $data = [
            'langGroup' => self::LANG_GROUP,
            'title' => $title,
            'definition' => $definition,
            'dryRunAllowed' => $extended,
            'partial' => $extended && $definition->formPartial() !== null ? ProjectFile::resolve((string) $definition->formPartial(), ['php']) : null,
            'columns' => $columns,
            'extensions' => $definition->acceptedExtensions(),
            'maxSizeMB' => $definition->maxSizeMB(),
            'maxRows' => $definition->maxRows(),
            'action' => self::routeName("import-{$key}-action"),
            'template' => self::routeName("import-{$key}-template"),
            'breadcrumbs' => get_breadcrumbs([
                __(self::LANG_GROUP, 'Inicio') => [
                    'url' => \PiecesPHP\AdminPanel\Controllers\AdminPanelController::routeName(''),
                ],
                __(self::LANG_GROUP, 'Importar y exportar') => [
                    'url' => self::routeName('hub'),
                ],
                $title,
            ]),
        ];

        $this->helpController->render('panel/layout/header');
        if ($level === ImportDefinition::INTERFACE_CUSTOM) {
            self::renderFile(ProjectFile::resolve((string) $definition->customView(), ['php']), $data);
        } else {
            $this->render('data-transfer/import-form', $data);
        }
        $this->helpController->render('panel/layout/footer');

        return $response;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param string $definitionClass
     * @param bool $ignorePOSTUploaded Solo para las pruebas, que no pueden simular una subida HTTP; la ruta pasa false
     * @return Response
     */
    public function importAction(Request $request, Response $response, string $definitionClass, bool $ignorePOSTUploaded = false)
    {
        $definition = self::definition($definitionClass);
        if (self::isDisabled(self::KIND_IMPORT, $definition->key())) {
            return $response->withJson((new ImportReport(0, [], false, [self::disabledMessage(self::KIND_IMPORT)]))->jsonSerialize(), 403);
        }
        $extensions = array_map(fn($e) => mb_strtolower((string) $e), $definition->acceptedExtensions());

        $body = $request->getParsedBody();
        $dryRun = is_array($body) && ($body['dryRun'] ?? null) === 'yes';
        if ($dryRun && $definition->interfaceLevel() < ImportDefinition::INTERFACE_EXTENDED) {
            return self::rejected($response, [__(self::LANG_GROUP, 'Este importador no permite simular.')]);
        }

        $name = is_array($_FILES['file'] ?? null) && is_string($_FILES['file']['name'] ?? null) ? $_FILES['file']['name'] : '';
        $extension = mb_strtolower(pathinfo($name, \PATHINFO_EXTENSION));
        if (!in_array($extension, $extensions, true)) {
            return self::rejected($response, [
                sprintf(__(self::LANG_GROUP, 'Solo se admiten archivos %s.'), implode(', ', $extensions)),
            ]);
        }

        //finfo ve un CSV como text/plain, que FileValidator no admite para CSV: el tipo se comprueba aquí, por contenido.
        $types = $extension === 'xlsx' ? [FileValidator::TYPE_XLSX] : [FileValidator::TYPE_ANY];
        $upload = new UploadedFileAdapter(['file'], $types, $definition->maxSizeMB());
        if (!$upload->validate($ignorePOSTUploaded)) {
            $messages = [];
            foreach ($upload->getErrorMessages() as $message) {
                foreach (preg_split('/\R/u', (string) $message) ?: [] as $line) {
                    if (trim($line) !== '') {
                        $messages[] = trim($line);
                    }
                }
            }
            return self::rejected($response, $messages);
        }

        $path = $upload->getFileInformation()['tmp_name'];
        if ($extension === 'csv' && !self::isTextCsv($path)) {
            return self::rejected($response, [__(self::LANG_GROUP, 'El archivo no es un CSV de texto.')]);
        }

        $report = (new ImportRunner())->run($definition, SpreadsheetRowSource::fromFile($path, $extension), $dryRun);

        return $response->withJson(self::responseBody($report));
    }

    /**
     * El JSON de la acción. El artefacto (p. ej. credenciales) va solo si se guardó, una vez, y no se escribe en ningún sitio.
     *
     * @param ImportReport $report
     * @return array<string,mixed>
     */
    public static function responseBody(ImportReport $report): array
    {
        $json = $report->jsonSerialize();
        $artifacts = $report->artifacts();
        if ($report->persisted() && $artifacts !== null) {
            $json['artifact'] = [
                'filename' => $artifacts->filename(),
                'mimeType' => $artifacts->mimeType(),
                'contentBase64' => base64_encode($artifacts->content()),
            ];
        }
        return $json;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param string $definitionClass
     * @return Response
     */
    public function importTemplate(Request $request, Response $response, string $definitionClass)
    {
        $definition = self::definition($definitionClass);
        if (self::isDisabled(self::KIND_IMPORT, $definition->key())) {
            return throw403($request);
        }

        $format = $request->getQueryParam('format', 'xlsx');
        $extensions = array_map(fn($e) => mb_strtolower((string) $e), $definition->acceptedExtensions());
        if (!is_string($format) || !in_array($format, ['xlsx', 'csv'], true) || !in_array($format, $extensions, true)) {
            return $response->withJson(['error' => sprintf(__(self::LANG_GROUP, 'Plantilla no disponible en ese formato: usa %s.'), implode(', ', $extensions))], 400);
        }

        $path = (string) tempnam(sys_get_temp_dir(), 'pcsphp-template-');
        try {
            self::writeImportTemplate($definition, $format, $path);
            $content = (string) file_get_contents($path);
        } finally {
            if (is_file($path)) {
                //RETORNO-IGNORADO: temporal propio de la plantilla, ya leído; si queda, lo limpia el sistema.
                @unlink($path);
            }
        }

        return $response
            ->write($content)
            ->withHeader('Content-Type', $format === 'xlsx' ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' : 'text/csv; charset=UTF-8')
            ->withHeader('Content-Disposition', 'attachment; filename="plantilla-' . $definition->key() . '.' . $format . '"')
            ->withHeader('Cache-Control', 'no-store');
    }

    /**
     * La plantilla: solo la fila de encabezados, sin fila de ejemplo (se importaría).
     * En XLSX, las obligatorias en negrita y la ayuda y el ejemplo como comentario de la celda.
     *
     * @param ImportDefinition $definition
     * @param string $format xlsx|csv
     * @param string $path
     * @return void
     */
    public static function writeImportTemplate(ImportDefinition $definition, string $format, string $path): void
    {
        if ($format === 'csv') {
            $columns = array_map(fn($c) => new ExportColumn($c->key(), $c->label()), $definition->columns());
            $template = new class($columns) extends ExportDefinition {
                /**
                 * @param ExportColumn[] $templateColumns
                 */
                public function __construct(private array $templateColumns)
                {
                }

                public function key(): string
                {
                    return 'template';
                }

                public function title(): string
                {
                    return 'template';
                }

                public function allowedUserTypes(): array
                {
                    return [];
                }

                public function columns(ExportContext $context): array
                {
                    return $this->templateColumns;
                }

                public function rows(ExportContext $context): iterable
                {
                    return [];
                }
            };
            (new SpreadsheetExportWriter())->toCsv($template, new ExportContext([], null), $path);
            return;
        }

        $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $book->getActiveSheet();
        foreach (array_values($definition->columns()) as $index => $column) {
            $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1) . '1';
            $sheet->setCellValueExplicit($cell, $column->label(), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING2);
            if ($column->required()) {
                $sheet->getStyle($cell)->getFont()->setBold(true);
            }
            $lines = [];
            if ($column->helpText() !== null) {
                $lines[] = $column->helpText();
            }
            if ($column->exampleValue() !== null) {
                $lines[] = sprintf(__(self::LANG_GROUP, 'Ejemplo: %s'), $column->exampleValue());
            }
            if (count($lines) > 0) {
                $sheet->getComment($cell)->getText()->createTextRun(implode("\n", $lines));
            }
            $sheet->getColumnDimensionByColumn($index + 1)->setAutoSize(true);
        }
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($path);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param string $definitionClass
     * @return Response
     */
    public function exportAction(Request $request, Response $response, string $definitionClass)
    {
        if (!is_subclass_of($definitionClass, ExportDefinition::class)) {
            throw new \InvalidArgumentException("{$definitionClass} no extiende " . ExportDefinition::class . '.');
        }
        /** @var ExportDefinition $definition */
        $definition = new $definitionClass();
        if (self::isDisabled(self::KIND_EXPORT, $definition->key())) {
            return $response->withJson(['error' => self::disabledMessage(self::KIND_EXPORT)], 403);
        }

        $format = $request->getQueryParam('format', 'xlsx');
        $contentTypes = [
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv' => 'text/csv; charset=UTF-8',
        ];
        if (!is_string($format) || !array_key_exists($format, $contentTypes)) {
            return $response->withJson(['error' => __(self::LANG_GROUP, 'Formato no admitido: usa xlsx o csv.')], 400);
        }

        try {
            $context = $definition->buildContext((array) $request->getQueryParams(), getLoggedFrameworkUser());
        } catch (ExportParameterException $e) {
            return $response->withJson(['error' => $e->getMessage(), 'errors' => $e->errors()], 400);
        }

        $path = (string) tempnam(sys_get_temp_dir(), self::EXPORT_TEMP_PREFIX);
        $removeTemp = function () use ($path): void {
            if (is_file($path)) {
                //RETORNO-IGNORADO: temporal propio de la exportación; si queda, lo limpia el sistema.
                @unlink($path);
            }
        };

        //1. El archivo entero; si falla, no hay afterExport y el error sigue su curso.
        try {
            $writer = new SpreadsheetExportWriter();
            $rowCount = $format === 'xlsx'
                ? $writer->toXlsx($definition, $context, $path)
                : $writer->toCsv($definition, $context, $path);
            clearstatcache(true, $path);
            $size = filesize($path);
        } catch (\Throwable $e) {
            $removeTemp();
            throw $e;
        }

        //2. La acción tras exportar: si falla, no se descarga nada.
        $fileName = self::exportFileName($definition->fileName($context), $definition->key() . '-' . date('Ymd-His'));
        $result = new ExportResult($format, "{$fileName}.{$format}", $size !== false ? $size : 0, $rowCount);
        try {
            $definition->afterExport($context, $result);
        } catch (\Throwable $e) {
            $removeTemp();
            log_exception($e);
            return $response->withJson(['error' => __(self::LANG_GROUP, 'No se pudo completar la exportación. No se descargó nada.')], 500);
        }

        //3. La descarga: con el stream abierto se borra la ruta; el descriptor sigue leyéndose y no queda nada en disco.
        try {
            $body = (new StreamFactory())->createStreamFromFile($path, 'rb');
        } finally {
            $removeTemp();
        }

        $response = $response
            ->withBody($body)
            ->withHeader('Content-Type', $contentTypes[$format])
            ->withHeader('Content-Disposition', self::contentDisposition($fileName, $format))
            ->withHeader('Cache-Control', 'no-store');
        return $size !== false ? $response->withHeader('Content-Length', (string) $size) : $response;
    }

    /**
     * Quita controles y / \ : * ? " < > |, recorta y limita a 150 caracteres; vacío → el por defecto.
     *
     * @param string $name
     * @param string $default
     * @return string
     */
    public static function exportFileName(string $name, string $default): string
    {
        $clean = trim((string) preg_replace('/[\x00-\x1F\x7F\/\\\\:*?"<>|]/u', '', $name));
        $clean = trim(mb_substr($clean, 0, 150));
        return $clean !== '' ? $clean : $default;
    }

    /**
     * RFC 6266 / 5987: filename en ASCII para los clientes viejos y filename* en UTF-8 para los demás.
     *
     * @param string $name
     * @param string $extension
     * @return string
     */
    public static function contentDisposition(string $name, string $extension): string
    {
        $ascii = (string) preg_replace('/[^A-Za-z0-9._ -]/u', '_', $name);
        return sprintf('attachment; filename="%s.%s"; filename*=UTF-8\'\'%s.%s', $ascii, $extension, rawurlencode($name), $extension);
    }

    /**
     * Formulario de filtros generado desde parameters(): envía por GET a la ruta de descarga.
     *
     * @param Request $request
     * @param Response $response
     * @param string $definitionClass
     * @return Response
     */
    public function exportForm(Request $request, Response $response, string $definitionClass)
    {
        if (!is_subclass_of($definitionClass, ExportDefinition::class)) {
            throw new \InvalidArgumentException("{$definitionClass} no extiende " . ExportDefinition::class . '.');
        }
        /** @var ExportDefinition $definition */
        $definition = new $definitionClass();
        $key = $definition->key();
        if (self::isDisabled(self::KIND_EXPORT, $key)) {
            return throw403($request);
        }

        set_custom_assets([
            DataImportExportUtilityRoutes::staticRoute('js/data-transfer/export.js'),
        ], 'js');

        $title = $definition->title();
        set_title($title);

        $level = $definition->interfaceLevel();
        $extended = $level >= ExportDefinition::INTERFACE_EXTENDED;
        $downloadURL = self::routeName("export-{$key}");
        $data = [
            'langGroup' => self::LANG_GROUP,
            'title' => $title,
            'definition' => $definition,
            'parameters' => $definition->parameters(),
            'action' => $downloadURL,
            'downloadURL' => $downloadURL,
            'previewURL' => $extended ? self::routeName("export-{$key}-preview") : null,
            'presets' => $extended ? self::currentUserPresets($definition) : null,
            'presetSaveURL' => $extended ? self::routeName("export-{$key}-presets-save") : null,
            'presetDeleteURL' => $extended ? self::routeName("export-{$key}-presets-delete") : null,
            'columns' => $extended ? array_map(fn(ExportColumn $c) => ['key' => $c->key(), 'label' => $c->label()], $definition->columns(new ExportContext([], getLoggedFrameworkUser()))) : [],
            'partial' => $extended && $definition->formPartial() !== null ? ProjectFile::resolve((string) $definition->formPartial(), ['php']) : null,
            'reimport' => self::reimportLink($definition),
            'breadcrumbs' => get_breadcrumbs([
                __(self::LANG_GROUP, 'Inicio') => [
                    'url' => \PiecesPHP\AdminPanel\Controllers\AdminPanelController::routeName(''),
                ],
                __(self::LANG_GROUP, 'Importar y exportar') => [
                    'url' => self::routeName('hub'),
                ],
                $title,
            ]),
        ];

        $this->helpController->render('panel/layout/header');
        if ($level === ExportDefinition::INTERFACE_CUSTOM) {
            self::renderFile(ProjectFile::resolve((string) $definition->customView(), ['php']), $data);
        } else {
            $this->render('data-transfer/export-form', $data);
        }
        $this->helpController->render('panel/layout/footer');

        return $response;
    }

    /**
     * Primeras filas de la hoja principal en JSON, con los mismos filtros y columnas que la descarga. No ejecuta afterExport.
     *
     * @param Request $request
     * @param Response $response
     * @param string $definitionClass
     * @return Response
     */
    public function exportPreview(Request $request, Response $response, string $definitionClass)
    {
        if (!is_subclass_of($definitionClass, ExportDefinition::class)) {
            throw new \InvalidArgumentException("{$definitionClass} no extiende " . ExportDefinition::class . '.');
        }
        /** @var ExportDefinition $definition */
        $definition = new $definitionClass();
        if (self::isDisabled(self::KIND_EXPORT, $definition->key())) {
            return $response->withJson(['error' => self::disabledMessage(self::KIND_EXPORT)], 403);
        }

        try {
            $context = $definition->buildContext((array) $request->getQueryParams(), getLoggedFrameworkUser());
        } catch (ExportParameterException $e) {
            return $response->withJson(['error' => $e->getMessage(), 'errors' => $e->errors()], 400);
        }

        return $response->withJson((new SpreadsheetExportWriter())->firstRows($definition, $context))->withHeader('Cache-Control', 'no-store');
    }

    /**
     * Guarda (o sustituye) un filtro con nombre del usuario de la sesión. El cuerpo: «presetName» y los campos del formulario.
     *
     * @param Request $request
     * @param Response $response
     * @param string $definitionClass
     * @return Response
     */
    public function presetSave(Request $request, Response $response, string $definitionClass)
    {
        [$definition, $userID] = self::presetTarget($definitionClass);
        if (self::isDisabled(self::KIND_EXPORT, $definition->key())) {
            return $response->withJson(['error' => self::disabledMessage(self::KIND_EXPORT)], 403);
        }
        if ($userID === null) {
            return $response->withJson(['error' => __(self::LANG_GROUP, 'Hace falta una sesión iniciada.')], 400);
        }
        $body = (array) $request->getParsedBody();
        $name = self::presetName($body['presetName'] ?? null);
        if ($name === null) {
            return $response->withJson(['error' => sprintf(__(self::LANG_GROUP, 'El nombre debe tener entre 1 y %d caracteres.'), self::PRESET_NAME_MAX)], 400);
        }
        unset($body['presetName']);

        //Solo las claves que la exportación entiende; lo demás del cuerpo (un id, por ejemplo) no se guarda.
        $allowed = ['format', 'columns'];
        foreach ($definition->parameters() as $parameter) {
            $allowed = array_merge($allowed, $parameter->queryKeys());
        }
        $query = array_intersect_key($body, array_flip($allowed));
        if (array_key_exists('format', $query) && !in_array($query['format'], ['xlsx', 'csv'], true)) {
            return $response->withJson(['error' => __(self::LANG_GROUP, 'Formato no admitido: usa xlsx o csv.')], 400);
        }
        try {
            $definition->buildContext($query, getLoggedFrameworkUser());
        } catch (ExportParameterException $e) {
            return $response->withJson(['error' => $e->getMessage(), 'errors' => $e->errors()], 400);
        }

        $store = self::presetStore();
        $existing = $store->all($userID, $definition->key());
        if (!array_key_exists($name, $existing) && count($existing) >= self::PRESET_MAX) {
            return $response->withJson(['error' => sprintf(__(self::LANG_GROUP, 'Como mucho %d filtros guardados por exportación.'), self::PRESET_MAX)], 400);
        }
        $store->save($userID, $definition->key(), $name, $query);

        return $response->withJson(['presets' => $store->all($userID, $definition->key())]);
    }

    /**
     * Borra un filtro con nombre del usuario de la sesión.
     *
     * @param Request $request
     * @param Response $response
     * @param string $definitionClass
     * @return Response
     */
    public function presetDelete(Request $request, Response $response, string $definitionClass)
    {
        [$definition, $userID] = self::presetTarget($definitionClass);
        if (self::isDisabled(self::KIND_EXPORT, $definition->key())) {
            return $response->withJson(['error' => self::disabledMessage(self::KIND_EXPORT)], 403);
        }
        if ($userID === null) {
            return $response->withJson(['error' => __(self::LANG_GROUP, 'Hace falta una sesión iniciada.')], 400);
        }
        $body = (array) $request->getParsedBody();
        $name = self::presetName($body['presetName'] ?? null);
        if ($name === null) {
            return $response->withJson(['error' => sprintf(__(self::LANG_GROUP, 'El nombre debe tener entre 1 y %d caracteres.'), self::PRESET_NAME_MAX)], 400);
        }
        $store = self::presetStore();
        $store->delete($userID, $definition->key(), $name);

        return $response->withJson(['presets' => $store->all($userID, $definition->key())]);
    }

    /**
     * El almacén de filtros guardados.
     *
     * @return ExportPresetStore
     */
    public static function presetStore(): ExportPresetStore
    {
        return new UserMetaPresetStore();
    }

    /**
     * @param ExportDefinition $definition
     * @return array<string,array<string,mixed>>
     */
    private static function currentUserPresets(ExportDefinition $definition): array
    {
        $user = getLoggedFrameworkUser();
        return $user !== null ? self::presetStore()->all((int) $user->id, $definition->key()) : [];
    }

    /**
     * La definición y el id del usuario de la SESIÓN: nunca del cuerpo.
     *
     * @param string $definitionClass
     * @return array{0:ExportDefinition,1:int|null}
     */
    private static function presetTarget(string $definitionClass): array
    {
        if (!is_subclass_of($definitionClass, ExportDefinition::class)) {
            throw new \InvalidArgumentException("{$definitionClass} no extiende " . ExportDefinition::class . '.');
        }
        /** @var ExportDefinition $definition */
        $definition = new $definitionClass();
        $user = getLoggedFrameworkUser();
        return [$definition, $user !== null ? (int) $user->id : null];
    }

    /**
     * @param mixed $raw
     * @return string|null recortado, de 1 a PRESET_NAME_MAX caracteres; null si no
     */
    private static function presetName($raw): ?string
    {
        $name = is_string($raw) ? trim($raw) : '';
        return $name !== '' && mb_strlen($name) <= self::PRESET_NAME_MAX ? $name : null;
    }

    /**
     * Enciende o apaga un importador o exportador (solo root, por la ruta). Cuerpo: kind=import|export, key, enabled=yes|no.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function toggle(Request $request, Response $response)
    {
        $body = (array) $request->getParsedBody();
        $kind = $body['kind'] ?? null;
        $key = $body['key'] ?? null;
        $enabled = $body['enabled'] ?? null;
        $registered = [
            self::KIND_IMPORT => DataImportExportUtilityRoutes::importers(),
            self::KIND_EXPORT => DataImportExportUtilityRoutes::exporters(),
        ];
        if (!is_string($kind) || !array_key_exists($kind, $registered) || !is_string($key) || !array_key_exists($key, $registered[$kind]) || !in_array($enabled, ['yes', 'no'], true)) {
            return $response->withJson(['error' => __(self::LANG_GROUP, 'Petición no válida: kind, key y enabled.')], 400);
        }

        $isEnabled = $enabled === 'yes';
        if (!self::setDisabled($kind, $key, !$isEnabled)) {
            return $response->withJson(['error' => __(self::LANG_GROUP, 'No se pudo guardar el cambio.')], 500);
        }

        //Queda en el registro de acciones del panel (EventsLog): quién y cuándo; el mapper pone el usuario de la sesión y la fecha.
        $user = getLoggedFrameworkUser();
        if (LogsRoutes::ENABLE) {
            LogsMapper::addLog(LogsMapper::MSG_GENERIC, [
                '%message%' => sprintf(
                    __(self::LANG_GROUP, '%s %s el %s «%s» de Importar y exportar.'),
                    $user !== null ? $user->username : '?',
                    $isEnabled ? __(self::LANG_GROUP, 'encendió') : __(self::LANG_GROUP, 'apagó'),
                    $kind === self::KIND_IMPORT ? __(self::LANG_GROUP, 'importador') : __(self::LANG_GROUP, 'exportador'),
                    $key
                ),
            ]);
        }

        return $response->withJson(['kind' => $kind, 'key' => $key, 'enabled' => $isEnabled]);
    }

    /**
     * La única pregunta «¿está apagado?»: la hacen todos los puntos de entrada, web y terminal.
     *
     * @param string $kind import|export
     * @param string $key
     * @return bool
     */
    public static function isDisabled(string $kind, string $key): bool
    {
        return in_array($key, self::disabledKeys()[$kind] ?? [], true);
    }

    /**
     * @return array{import:string[],export:string[]}
     */
    public static function disabledKeys(): array
    {
        $raw = SettingsModel::getConfigValue(self::DISABLED_CONFIG);
        $value = json_decode((string) json_encode($raw), true);
        $value = is_array($value) ? $value : [];
        $keys = [];
        foreach ([self::KIND_IMPORT, self::KIND_EXPORT] as $kind) {
            $list = $value[$kind] ?? [];
            $keys[$kind] = is_array($list) ? array_values(array_filter($list, 'is_string')) : [];
        }
        return $keys;
    }

    /**
     * @param string $kind
     * @return string
     */
    public static function disabledMessage(string $kind): string
    {
        return $kind === self::KIND_IMPORT
            ? __(self::LANG_GROUP, 'Este importador está apagado.')
            : __(self::LANG_GROUP, 'Este exportador está apagado.');
    }

    /**
     * @param string $kind
     * @param string $key
     * @param bool $disabled
     * @return bool
     */
    private static function setDisabled(string $kind, string $key, bool $disabled): bool
    {
        $keys = self::disabledKeys();
        $list = array_values(array_diff($keys[$kind], [$key]));
        if ($disabled) {
            $list[] = $key;
        }
        $keys[$kind] = $list;
        return (bool) SettingsModel::setConfigValue(self::DISABLED_CONFIG, $keys);
    }

    /**
     * Pinta una vista .php del proyecto (la de customView()) con las variables del formulario.
     *
     * @param string $file ruta ya resuelta por ProjectFile
     * @param array<string,mixed> $data
     * @return void
     */
    public static function renderFile(string $file, array $data): void
    {
        (function () use ($file, $data): void {
            extract($data, \EXTR_SKIP);
            include $file;
        })();
    }

    /**
     * Las filas de importación de la portada: nivel 1 o más y con permiso sobre su formulario.
     * Las apagadas solo las ve root, que es quien puede encenderlas.
     *
     * @return array<int,array{key:string,title:string,description:string,link:string,templateXlsx:string,templateCsv:string|null,enabled:bool}>
     */
    public static function visibleImporters(): array
    {
        $isRoot = self::isRootUser();
        $rows = [];
        foreach (DataImportExportUtilityRoutes::importers() as $key => $definitionClass) {
            /** @var ImportDefinition $definition */
            $definition = new $definitionClass();
            //Nivel 0 no tiene rutas web ni fila.
            if ($definition->interfaceLevel() < ImportDefinition::INTERFACE_AUTO || !self::allowedRoute("import-{$key}")) {
                continue;
            }
            $enabled = !self::isDisabled(self::KIND_IMPORT, $key);
            if (!$enabled && !$isRoot) {
                continue;
            }
            $rows[] = self::importerHubRow($key, $definition, $enabled);
        }
        return $rows;
    }

    /**
     * Las filas de exportación de la portada: nivel 1 o más y con permiso sobre su formulario.
     * Descarga directa solo si ningún filtro es obligatorio. Las apagadas solo las ve root.
     *
     * @return array<int,array{key:string,title:string,description:string,link:string,downloadXlsx:string|null,downloadCsv:string|null,enabled:bool}>
     */
    public static function visibleExporters(): array
    {
        $isRoot = self::isRootUser();
        $rows = [];
        foreach (DataImportExportUtilityRoutes::exporters() as $key => $definitionClass) {
            /** @var ExportDefinition $definition */
            $definition = new $definitionClass();
            //Nivel 0 no tiene formulario ni fila.
            if ($definition->interfaceLevel() < ExportDefinition::INTERFACE_AUTO || !self::allowedRoute("export-{$key}-form")) {
                continue;
            }
            $enabled = !self::isDisabled(self::KIND_EXPORT, $key);
            if (!$enabled && !$isRoot) {
                continue;
            }
            $rows[] = self::exporterHubRow($key, $definition, $enabled);
        }
        return $rows;
    }

    /**
     * Una fila de importación de la portada: plantilla CSV solo si el importador la acepta.
     *
     * @param string $key
     * @param ImportDefinition $definition
     * @param bool $enabled
     * @return array{key:string,title:string,description:string,link:string,templateXlsx:string,templateCsv:string|null,enabled:bool}
     */
    public static function importerHubRow(string $key, ImportDefinition $definition, bool $enabled): array
    {
        $template = self::routeName("import-{$key}-template", [], true);
        return [
            'key' => $key,
            'title' => $definition->title(),
            'description' => $definition->description(),
            'link' => self::routeName("import-{$key}", [], true),
            'templateXlsx' => self::withFormat($template, 'xlsx'),
            'templateCsv' => in_array('csv', array_map('mb_strtolower', $definition->acceptedExtensions()), true) ? self::withFormat($template, 'csv') : null,
            'enabled' => $enabled,
        ];
    }

    /**
     * Una fila de exportación de la portada: descarga directa solo si ningún filtro es obligatorio.
     *
     * @param string $key
     * @param ExportDefinition $definition
     * @param bool $enabled
     * @return array{key:string,title:string,description:string,link:string,downloadXlsx:string|null,downloadCsv:string|null,enabled:bool}
     */
    public static function exporterHubRow(string $key, ExportDefinition $definition, bool $enabled): array
    {
        $direct = count(array_filter($definition->parameters(), fn($p) => $p->isRequired())) === 0;
        $download = self::routeName("export-{$key}", [], true);
        return [
            'key' => $key,
            'title' => $definition->title(),
            'description' => $definition->description(),
            'link' => self::routeName("export-{$key}-form", [], true),
            'downloadXlsx' => $direct ? self::withFormat($download, 'xlsx') : null,
            'downloadCsv' => $direct ? self::withFormat($download, 'csv') : null,
            'enabled' => $enabled,
        ];
    }

    /**
     * @param string $url
     * @param string $format
     * @return string
     */
    private static function withFormat(string $url, string $format): string
    {
        return $url . (str_contains($url, '?') ? '&' : '?') . http_build_query(['format' => $format]);
    }

    /**
     * @return bool
     */
    private static function isRootUser(): bool
    {
        $user = getLoggedFrameworkUser();
        return $user !== null && (int) $user->type === UsersModel::TYPE_USER_ROOT;
    }

    /**
     * El importador que puede reabrir este archivo, si la definición lo declara y el usuario puede usarlo.
     *
     * Exige usuario: sin él, routeName() concede y el enlace saldría para cualquiera.
     *
     * @param ExportDefinition $definition
     * @return array{title:string,url:string}|null
     */
    public static function reimportLink(ExportDefinition $definition): ?array
    {
        $importClass = $definition->importDefinition();
        if ($importClass === null || !is_subclass_of($importClass, ImportDefinition::class) || getLoggedFrameworkUser() === null) {
            return null;
        }
        /** @var ImportDefinition $import */
        $import = new $importClass();
        $route = "import-{$import->key()}";
        if (!self::allowedRoute($route)) {
            return null;
        }
        return ['title' => $import->title(), 'url' => self::routeName($route)];
    }

    /**
     * @param string $definitionClass
     * @return ImportDefinition
     */
    private static function definition(string $definitionClass): ImportDefinition
    {
        if (!is_subclass_of($definitionClass, ImportDefinition::class)) {
            throw new \InvalidArgumentException("{$definitionClass} no extiende " . ImportDefinition::class . '.');
        }
        return new $definitionClass();
    }

    /**
     * @param Response $response
     * @param string[] $messages
     * @return Response
     */
    private static function rejected(Response $response, array $messages): Response
    {
        return $response->withJson((new ImportReport(0, [], false, $messages))->jsonSerialize(), 400);
    }

    /**
     * Los primeros 8 KB sin el byte NUL y en UTF-8 válido (con o sin BOM).
     *
     * @param string $path
     * @return bool
     */
    public static function isTextCsv(string $path): bool
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return false;
        }
        $chunk = fread($handle, 8192);
        //RETORNO-IGNORADO: el archivo solo se leyó; cerrarlo no puede perder nada.
        fclose($handle);
        if (!is_string($chunk) || str_contains($chunk, "\0")) {
            return false;
        }
        if (str_starts_with($chunk, "\xEF\xBB\xBF")) {
            $chunk = substr($chunk, 3);
        }
        //El corte de 8 KB puede partir un carácter multibyte al final: se admiten hasta 3 bytes sueltos.
        for ($drop = 0; $drop <= 3 && $drop <= strlen($chunk); $drop++) {
            if (mb_check_encoding(substr($chunk, 0, strlen($chunk) - $drop), 'UTF-8')) {
                return true;
            }
            if (strlen($chunk) < 8189) {
                break;
            }
        }
        return false;
    }

    /**
     * @param RouteGroup $group
     * @param string $definitionClass
     * @param string $key
     * @param int[] $allowedUserTypes
     * @return RouteGroup
     */
    public static function importerRoutes(RouteGroup $group, string $definitionClass, string $key, array $allowedUserTypes): RouteGroup
    {
        $startRoute = (last_char($group->getGroupSegment()) == '/' ? '' : '/') . self::$URLDirectory;

        /** @var ImportDefinition $definition */
        $definition = new $definitionClass();
        //Nivel 0: ninguna ruta web, solo la terminal.
        if ($definition->interfaceLevel() < ImportDefinition::INTERFACE_AUTO) {
            return $group;
        }

        //Route solo admite parámetros escalares del patrón: la definición llega por el closure.
        $group->register([
            new Route(
                "{$startRoute}/import/{$key}[/]",
                fn(Request $request, Response $response) => (new DataTransferController())->importForm($request, $response, $definitionClass),
                self::$baseRouteName . "-import-{$key}",
                'GET',
                true,
                null,
                $allowedUserTypes
            ),
            new Route(
                "{$startRoute}/import/{$key}/action[/]",
                fn(Request $request, Response $response) => (new DataTransferController())->importAction($request, $response, $definitionClass),
                self::$baseRouteName . "-import-{$key}-action",
                'POST',
                true,
                null,
                $allowedUserTypes
            ),
            new Route(
                "{$startRoute}/import/{$key}/template[/]",
                fn(Request $request, Response $response) => (new DataTransferController())->importTemplate($request, $response, $definitionClass),
                self::$baseRouteName . "-import-{$key}-template",
                'GET',
                true,
                null,
                $allowedUserTypes
            ),
        ]);

        return $group;
    }

    /**
     * @param RouteGroup $group
     * @param string $definitionClass
     * @param string $key
     * @param int[] $allowedUserTypes
     * @return RouteGroup
     */
    public static function exporterRoutes(RouteGroup $group, string $definitionClass, string $key, array $allowedUserTypes): RouteGroup
    {
        $startRoute = (last_char($group->getGroupSegment()) == '/' ? '' : '/') . self::$URLDirectory;
        /** @var ExportDefinition $definition */
        $definition = new $definitionClass();
        $level = $definition->interfaceLevel();

        //Nivel 0: solo la descarga. 1: más el formulario. 2 y 3: más la vista previa.
        $routes = [
            new Route(
                "{$startRoute}/export/{$key}[/]",
                fn(Request $request, Response $response) => (new DataTransferController())->exportAction($request, $response, $definitionClass),
                self::$baseRouteName . "-export-{$key}",
                'GET',
                true,
                null,
                $allowedUserTypes
            ),
        ];
        if ($level >= ExportDefinition::INTERFACE_AUTO) {
            $routes[] = new Route(
                "{$startRoute}/export/{$key}/form[/]",
                fn(Request $request, Response $response) => (new DataTransferController())->exportForm($request, $response, $definitionClass),
                self::$baseRouteName . "-export-{$key}-form",
                'GET',
                true,
                null,
                $allowedUserTypes
            );
        }
        if ($level >= ExportDefinition::INTERFACE_EXTENDED) {
            $routes[] = new Route(
                "{$startRoute}/export/{$key}/presets[/]",
                fn(Request $request, Response $response) => (new DataTransferController())->presetSave($request, $response, $definitionClass),
                self::$baseRouteName . "-export-{$key}-presets-save",
                'POST',
                true,
                null,
                $allowedUserTypes
            );
            $routes[] = new Route(
                "{$startRoute}/export/{$key}/presets/delete[/]",
                fn(Request $request, Response $response) => (new DataTransferController())->presetDelete($request, $response, $definitionClass),
                self::$baseRouteName . "-export-{$key}-presets-delete",
                'POST',
                true,
                null,
                $allowedUserTypes
            );
            $routes[] = new Route(
                "{$startRoute}/export/{$key}/preview[/]",
                fn(Request $request, Response $response) => (new DataTransferController())->exportPreview($request, $response, $definitionClass),
                self::$baseRouteName . "-export-{$key}-preview",
                'GET',
                true,
                null,
                $allowedUserTypes
            );
        }
        $group->register($routes);

        return $group;
    }

    /**
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {
        $startRoute = (last_char($group->getGroupSegment()) == '/' ? '' : '/') . self::$URLDirectory;

        $group->register([
            new Route(
                "{$startRoute}[/]",
                self::class . ':hub',
                self::$baseRouteName . '-hub',
                'GET',
                true,
                null,
                [
                    UsersModel::TYPE_USER_ROOT,
                    UsersModel::TYPE_USER_ADMIN_GRAL,
                ]
            ),
            new Route(
                "{$startRoute}/toggle[/]",
                self::class . ':toggle',
                self::$baseRouteName . '-toggle',
                'POST',
                true,
                null,
                [
                    UsersModel::TYPE_USER_ROOT,
                ]
            ),
        ]);

        $group->addMiddleware(function (Request $request, $handler) {
            return (new DefaultAccessControlModules(self::$baseRouteName . '-', function (string $name, array $params) {
                return self::routeName($name, $params);
            }))->getResponse($request, $handler);
        });

        return $group;
    }
}
