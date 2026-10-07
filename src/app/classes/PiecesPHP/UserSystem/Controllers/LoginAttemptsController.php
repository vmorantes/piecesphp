<?php

/**
 * LoginAttemptsController.php
 */

namespace PiecesPHP\UserSystem\Controllers;

use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\UserSystem\ORM\LoginAttemptsModel;
use PiecesPHP\UserSystem\ORM\TimeOnPlatformModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
use PiecesPHP\Core\Routing\Slim3Compatibility\Exception\NotFoundException;
use \PiecesPHP\Core\Routing\RequestRoute as Request;
use \PiecesPHP\Core\Routing\ResponseRoute as Response;

/**
 * LoginAttemptsController.
 *
 * Controlador de informes de intentos de inicio
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class LoginAttemptsController extends AdminPanelController
{

    /**
     * El layout del panel vive en la carpeta global de vistas, no en la del módulo: lo pide una
     * instancia aparte, que es el patrón de SystemStatus y de Publications.
     *
     * @var HelperController
     */
    protected $helpController = null;

    use ControllerRoutingTrait;

    /**
     * @var string
     */
    protected static $baseRouteName = 'login-attempts';

    /** @ignore */
    public function __construct()
    {
        parent::__construct();
        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());
        //Sus tres informes viven en el módulo; el layout lo pide el ayudante, que no lleva este
        //directorio.
        $this->setInstanceViewDir(__DIR__ . '/../Views/');
    }

    /**
     * La exportación de cada informe: registro de ingreso, sin ingreso o intentos.
     *
     * @param string $report logged|not-logged|attempts
     * @return string
     * @throws \InvalidArgumentException con otro informe
     */
    public static function exportURLFor(string $report): string
    {
        $suffixes = [
            'logged' => 'export-logged',
            'not-logged' => 'export-not-logged',
            'attempts' => 'export-attempts',
        ];
        if (!array_key_exists($report, $suffixes)) {
            throw new \InvalidArgumentException("Informe de accesos «{$report}» desconocido.");
        }
        return self::routeName($suffixes[$report]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function reportsAccess(Request $request, Response $response)
    {
        if ($request->isXhr() || $request->getQueryParam('xhr', 'no') === 'yes') {

            $type = $request->getAttribute('type', null);

            if ($type == 'logged') {
                return $response->withJson(LoginAttemptsModel::getLoggedUsers($request)->getValues());
            } elseif ($type == 'not-logged') {
                return $response->withJson(LoginAttemptsModel::getNotLoggedUsers($request)->getValues());
            } elseif ($type == 'attempts') {
                return $response->withJson(LoginAttemptsModel::getAttempts($request)->getValues());
            } else {
                throw new NotFoundException($request, $response);
            }

        } else {

            $logged = $request->getQueryParam('logged', 'no') === 'yes';
            $notLogged = $request->getQueryParam('not-logged', 'no') === 'yes';
            $attempts = $request->getQueryParam('attempts', 'no') === 'yes';

            if ($logged === false && $notLogged === false && $attempts === false) {
                throw new NotFoundException($request, $response);
            }

            set_custom_assets([
                'statics/core/css/registers/attempts.css',
            ], 'css');

            $breadcrumb = [
                __(GENERAL_LANG_GROUP, 'Home') => [
                    'url' => \PiecesPHP\AdminPanel\Controllers\AdminPanelController::routeName(''),
                ],
            ];

            $attemptsData = LoginAttemptsModel::all();
            $allUsers = UsersModel::all();
            $allTimeOnPlatform = TimeOnPlatformModel::getAllHoursOnPlatform();

            $data = [];
            $title = '';
            $viewName = '';
            $report = '';

            if ($logged) {
                $title = __(LOGIN_REPORT_LANG_GROUP, 'Registro de ingreso');
                $data = array_merge($data, [
                    'totalUsers' => count($allUsers),
                    'allTimeOnPlatform' => $allTimeOnPlatform,
                ]);
                $viewName = 'panel/pages/login-reports/logged';
                $report = 'logged';
            } elseif ($notLogged) {
                $title = __(LOGIN_REPORT_LANG_GROUP, 'Usuarios sin Ingreso');
                $data = array_merge($data, [
                    'totalUsers' => count($allUsers),
                ]);
                $viewName = 'panel/pages/login-reports/not-logged';
                $report = 'not-logged';
            } elseif ($attempts) {
                $title = __(LOGIN_REPORT_LANG_GROUP, 'Intentos de Ingresos');
                $data = array_merge($data, [
                    'totalAttempts' => count($attemptsData),
                    'successAttempts' => count(array_filter($attemptsData, function ($e) {return $e->success == 1;})),
                    'errorAttempts' => count(array_filter($attemptsData, function ($e) {return $e->success == 0;})),
                ]);
                $viewName = 'panel/pages/login-reports/attempts';
                $report = 'attempts';
            }

            set_title($title);

            $breadcrumb[] = $title;
            $data['title'] = $title;
            $data['breadcrumbs'] = get_breadcrumbs($breadcrumb);
            $data['exportUrl'] = self::exportURLFor($report);

            $this->helpController->render('panel/layout/header');
            $this->render($viewName, $data);
            $this->helpController->render('panel/layout/footer');
        }

        return $response;
    }

    /**
     * @param Response $response
     * @return Response
     */
    public function attemptsExport(Request $request, Response $response)
    {

        //all() filtra por organización con la misma regla que el listado: exportar no puede enseñar más que mirar.
        $result = LoginAttemptsModel::all();

        $columns = [
            'Indicador' => [
                'format' => function ($e) {
                    return $e->success ? 'Exitoso' : 'Erróneo';
                },
            ],
            'Usuario Ingresado' => [
                'dataKey' => 'usernameAttempt',
            ],
            'Información' => [
                'dataKey' => 'message',
            ],
            'IP' => [
                'dataKey' => 'ip',
            ],
            'Fecha' => [
                'dataKey' => 'date',
            ],
        ];

        return self::exportExcelFile($response, $columns, $result, 'Intentos de Ingresos');

    }

    /**
     * @param Response $response
     * @return Response
     */
    public function notLoggedExport(Request $request, Response $response)
    {

        //UsersModel::all() filtra por organización con la misma regla que el listado: exportar no puede enseñar más que mirar.
        $logged = self::usersWithSuccessfulLogin();
        $result = array_values(array_filter(UsersModel::all(), fn($e) => !isset($logged[(int) $e->id])));

        $columns = [
            'ID' => [
                'dataKey' => 'id',
            ],
            'Nombre' => [
                'format' => function ($e) {
                    return trim("$e->firstname $e->secondname $e->firstLastname $e->secondLastname");
                },
            ],
        ];

        return self::exportExcelFile($response, $columns, $result, 'Usuarios sin Ingreso');

    }

    /**
     * @param Response $response
     * @return Response
     */
    public function loggedExport(Request $request, Response $response)
    {

        //UsersModel::all() filtra por organización con la misma regla que el listado: exportar no puede enseñar más que mirar.
        $logged = self::usersWithSuccessfulLogin();
        $result = array_values(array_filter(UsersModel::all(), fn($e) => isset($logged[(int) $e->id])));

        $columns = [
            'ID' => [
                'dataKey' => 'id',
            ],
            'Nombre' => [
                'format' => function ($e) {
                    return trim("$e->firstname $e->secondname $e->firstLastname $e->secondLastname");
                },
            ],
            'Último acceso' => [
                'format' => function ($e) {
                    $lastLogin = LoginAttemptsModel::lastLogin((int) $e->id);
                    return $lastLogin !== null ? $lastLogin->format('d-m-Y H:i:s') : '-';
                },
            ],
            'Tiempo en plataforma' => [
                'format' => function ($e) {
                    $timeOnPlatfom = TimeOnPlatformModel::getRecordByUser((int) $e->id);
                    return !is_null($timeOnPlatfom) ? round($timeOnPlatfom->minutes, 0) . ' minuto(s)' : 'Sin registro';
                },
            ],
        ];

        return self::exportExcelFile($response, $columns, $result, 'Registro de ingreso');

    }

    /**
     * Los usuarios con al menos un ingreso exitoso, como claves.
     *
     * @return array<int,true>
     */
    private static function usersWithSuccessfulLogin(): array
    {
        $model = LoginAttemptsModel::model();
        $model->select('userID')->where([
            'success' => LoginAttemptsModel::SUCCESS_ATTEMPT,
        ])->groupBy('userID');
        $model->execute();
        $logged = [];
        foreach ((array) $model->result() as $row) {
            if ($row->userID !== null) {
                $logged[(int) $row->userID] = true;
            }
        }
        return $logged;
    }

    /**
     * Genera un archivo excel y lo devuelve como descarga.
     *
     * @param Response $response
     * @param array $columns Cada entrada DEBE traer 'format' o 'dataKey'.
     * @param array $data
     * @param string $fileName
     * @return Response
     */
    public static function exportExcelFile(Response $response, array $columns, array $data, string $fileName)
    {

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        $spreadsheet->setActiveSheetIndex(0);
        $activeSheet = $spreadsheet->getActiveSheet();

        $exelColumnIndex = 0;
        foreach ($columns as $key => $columnInfo) {
            $activeSheet->setCellValue(excelColumnByIndex($exelColumnIndex) . '1', $key);
            $exelColumnIndex++;
        }

        $indexColumn = 0;
        $indexRow = 2;

        foreach ($data as $e) {

            foreach ($columns as $columnInfo) {

                if (key_exists('format', $columnInfo)) {
                    $activeSheet->setCellValueExplicit(excelColumnByIndex($indexColumn) . $indexRow, $columnInfo['format']($e), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING2);
                } elseif (key_exists('dataKey', $columnInfo)) {
                    $activeSheet->setCellValueExplicit(excelColumnByIndex($indexColumn) . $indexRow, $e->{$columnInfo['dataKey']}, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING2);
                } else {
                    //Lanza y no devuelve false: este método declara Response y el false reventaría lejos.
                    throw new \InvalidArgumentException(
                        'Cada columna debe traer \'format\' o \'dataKey\'; falta en la posición ' . $indexColumn . '.'
                    );
                }

                $indexColumn++;
            }

            $indexColumn = 0;
            $indexRow++;
        }

        $firstColumn = 'A';
        $lastRow = $spreadsheet->getActiveSheet()->getHighestRow();
        $lastColumn = $spreadsheet->getActiveSheet()->getHighestColumn();
        $lastColumnIndex = indexByExcelColumn($lastColumn);

        //Definir ancho de columna - INICIO
        $maxColumnWidth = 30;
        for ($indexColumn = 0; $indexColumn <= $lastColumnIndex; $indexColumn++) {
            $activeSheet->getColumnDimension(excelColumnByIndex($indexColumn))->setAutoSize(true);
        }
        $activeSheet->calculateColumnWidths();
        for ($indexColumn = 0; $indexColumn <= $lastColumnIndex; $indexColumn++) {
            $dimensions = $activeSheet->getColumnDimension(excelColumnByIndex($indexColumn));
            if ($dimensions->getWidth() > $maxColumnWidth) {
                $dimensions->setAutoSize(false);
                $dimensions->setWidth($maxColumnWidth);
            }
        }
        //Definir ancho de columna - FIN

        //Envolver texto y centrar vertical/horizontalmente - INICIO
        $activeSheet->getStyle("{$firstColumn}1:{$lastColumn}{$lastRow}")->getAlignment()->setVertical('center');
        $activeSheet->getStyle("{$firstColumn}1:{$lastColumn}{$lastRow}")->getAlignment()->setHorizontal('center');
        $activeSheet->getStyle("{$firstColumn}1:{$lastColumn}{$lastRow}")->getAlignment()->setWrapText(true);
        //Envolver texto y centrar vertical/horizontalmente - FIN

        $fileName .= " - Exportado el " . date('d-m-Y h i A') . '.xlsx';

        ob_start();
        $writer->save('php://output');
        $fileData = ob_get_contents();
        ob_end_clean();

        return $response
            ->write($fileData)
            ->withHeader('Content-Type', 'application/vnd.ms-excel')
            ->withHeader('Content-Disposition', "attachment;filename={$fileName}")
            ->withHeader('Cache-Control', 'max-age=0');
    }

    /**
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {

        $accessReports = [
            UsersModel::TYPE_USER_ROOT,
            UsersModel::TYPE_USER_ADMIN_GRAL,
            UsersModel::TYPE_USER_ADMIN_ORG,
        ];
        $group->register([
            new Route(
                '/reports-access[/]',
                self::class . ':reportsAccess',
                self::$baseRouteName . '-reports',
                'GET',
                true,
                null,
                $accessReports
            ),
            new Route(
                '/attempts-export[/]',
                self::class . ':attemptsExport',
                self::$baseRouteName . '-export-attempts',
                'GET',
                true,
                null,
                $accessReports
            ),
            new Route(
                '/not-logged-export[/]',
                self::class . ':notLoggedExport',
                self::$baseRouteName . '-export-not-logged',
                'GET',
                true,
                null,
                $accessReports
            ),
            new Route(
                '/logged-export[/]',
                self::class . ':loggedExport',
                self::$baseRouteName . '-export-logged',
                'GET',
                true,
                null,
                $accessReports
            ),
            (new Route(
                '/reports-access/{type}[/]',
                self::class . ':reportsAccess',
                self::$baseRouteName . '-reports-ajax',
                'GET',
                true,
                null,
                $accessReports
            ))->setParameterValue('type', 'not-logged'),
        ]);

        return $group;
    }

}
