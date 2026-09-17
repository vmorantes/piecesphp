<?php

/**
 * DataTransferController.php
 */

namespace DataImportExportUtility\Controllers;

use App\Controller\AdminPanelController;
use App\Model\UsersModel;
use DataImportExportUtility\DataImportExportUtilityLang;
use DataImportExportUtility\DataImportExportUtilityRoutes;
use PiecesPHP\Core\DataTransfer\Export\ExportColumn;
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
        $importers = [];
        foreach (DataImportExportUtilityRoutes::importers() as $key => $definitionClass) {
            if (!self::allowedRoute("import-{$key}")) {
                continue;
            }
            $definition = new $definitionClass();
            $importers[] = [
                'title' => $definition->title(),
                'link' => self::routeName("import-{$key}"),
            ];
        }

        $title = __(self::LANG_GROUP, 'Importar y exportar');
        set_title($title);

        $data = [
            'langGroup' => self::LANG_GROUP,
            'title' => $title,
            'importers' => $importers,
            'breadcrumbs' => get_breadcrumbs([
                __(self::LANG_GROUP, 'Inicio') => [
                    'url' => get_route('admin'),
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
            ];
        }

        $data = [
            'langGroup' => self::LANG_GROUP,
            'title' => $title,
            'columns' => $columns,
            'extensions' => $definition->acceptedExtensions(),
            'maxSizeMB' => $definition->maxSizeMB(),
            'maxRows' => $definition->maxRows(),
            'action' => self::routeName("import-{$key}-action"),
            'template' => self::routeName("import-{$key}-template"),
            'breadcrumbs' => get_breadcrumbs([
                __(self::LANG_GROUP, 'Inicio') => [
                    'url' => get_route('admin'),
                ],
                __(self::LANG_GROUP, 'Importar y exportar') => [
                    'url' => self::routeName('hub'),
                ],
                $title,
            ]),
        ];

        $this->helpController->render('panel/layout/header');
        $this->render('data-transfer/import-form', $data);
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
        $extensions = array_map(fn($e) => mb_strtolower((string) $e), $definition->acceptedExtensions());

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

        $report = (new ImportRunner())->run($definition, SpreadsheetRowSource::fromFile($path, $extension));

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

            public function columns(): array
            {
                return $this->templateColumns;
            }

            public function rows(): iterable
            {
                return [];
            }
        };

        $path = (string) tempnam(sys_get_temp_dir(), 'pcsphp-template-');
        try {
            (new SpreadsheetExportWriter())->toCsv($template, $path);
            $content = (string) file_get_contents($path);
        } finally {
            if (is_file($path)) {
                //RETORNO-IGNORADO: temporal propio de la plantilla, ya leído; si queda, lo limpia el sistema.
                @unlink($path);
            }
        }

        return $response
            ->write($content)
            ->withHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->withHeader('Content-Disposition', 'attachment; filename="plantilla-' . $definition->key() . '.csv"')
            ->withHeader('Cache-Control', 'no-store');
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
        ]);

        $group->addMiddleware(function (Request $request, $handler) {
            return (new DefaultAccessControlModules(self::$baseRouteName . '-', function (string $name, array $params) {
                return self::routeName($name, $params);
            }))->getResponse($request, $handler);
        });

        return $group;
    }
}
