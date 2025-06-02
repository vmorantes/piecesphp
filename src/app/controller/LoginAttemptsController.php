<?php

/**
 * LoginAttemptsController.php
 */

namespace App\Controller;

use App\Model\LoginAttemptsModel;
use App\Model\TimeOnPlatformModel;
use App\Model\UsersModel;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
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
    /** @ignore */
    public function __construct()
    {
        parent::__construct();
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
                    'url' => get_route('admin'),
                ],
            ];

            $attemptsData = LoginAttemptsModel::all();
            $allUsers = UsersModel::all();
            $allTimeOnPlatform = TimeOnPlatformModel::getAllHoursOnPlatform();

            $data = [];
            $title = '';
            $viewName = '';

            if ($logged) {
                $title = __(LOGIN_REPORT_LANG_GROUP, 'Registro de ingreso');
                $data = array_merge($data, [
                    'totalUsers' => count($allUsers),
                    'allTimeOnPlatform' => $allTimeOnPlatform,
                ]);
                $viewName = 'panel/pages/login-reports/logged';
            } elseif ($notLogged) {
                $title = __(LOGIN_REPORT_LANG_GROUP, 'Usuarios sin Ingreso');
                $data = array_merge($data, [
                    'totalUsers' => count($allUsers),
                ]);
                $viewName = 'panel/pages/login-reports/not-logged';
            } elseif ($attempts) {
                $title = __(LOGIN_REPORT_LANG_GROUP, 'Intentos de Ingresos');
                $data = array_merge($data, [
                    'totalAttempts' => count($attemptsData),
                    'successAttempts' => count(array_filter($attemptsData, function ($e) {return $e->success == 1;})),
                    'errorAttempts' => count(array_filter($attemptsData, function ($e) {return $e->success == 0;})),
                ]);
                $viewName = 'panel/pages/login-reports/attempts';
            }

            set_title($title);

            $breadcrumb[] = $title;
            $data['title'] = $title;
            $data['breadcrumbs'] = get_breadcrumbs($breadcrumb);
            $data['exportUrl'] = get_route('logged-export');

            $this->render('panel/layout/header');
            $this->render($viewName, $data);
            $this->render('panel/layout/footer');
        }

        return $response;
    }

    /**
     * @param Response $response
     * @return Response
     */
    public function attemptsExport(Request $request, Response $response)
    {

        $model = (new LoginAttemptsModel())->getModel();

        $model->select();

        $model->execute();

        $result = (array) $model->result();

        $columns = [
            'Indicador' => [
                'format' => function ($e) {
                    return $e->success ? 'Exitoso' : 'Erróneo';
                },
            ],
            'Usuario Ingresado' => [
                'dataKey' => 'username_attempt',
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

        $logins_table = LoginAttemptsModel::TABLE;
        $users_table = 'pcsphp_users';

        $success = LoginAttemptsModel::SUCCESS_ATTEMPT;

        $on = "$logins_table.user_id = $users_table.id AND $logins_table.success = $success";
        $where = '';

        $where .= "$users_table.id NOT IN ";
        $where .= "(SELECT $users_table.id FROM $users_table INNER JOIN $logins_table ON $on GROUP BY $users_table.id)";

        $model = (new UsersModel())->getModel();

        $model->select()->where($where);

        $model->execute();

        $result = $model->result();

        $columns = [
            'ID' => [
                'dataKey' => 'id',
            ],
            'Nombre' => [
                'format' => function ($e) {
                    return trim("$e->firstname $e->secondname $e->first_lastname $e->second_lastname");
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

        $logins_table = LoginAttemptsModel::TABLE;
        $users_table = 'pcsphp_users';

        $success = LoginAttemptsModel::SUCCESS_ATTEMPT;

        $on = "$logins_table.user_id = $users_table.id AND $logins_table.success = $success";

        $model = (new UsersModel())->getModel();

        $model->select()->innerJoin($logins_table, $on)->groupBy("{$users_table}.id");

        $model->execute();

        $result = $model->result();

        $columns = [
            'ID' => [
                'dataKey' => 'user_id',
            ],
            'Nombre' => [
                'format' => function ($e) {
                    return trim("$e->firstname $e->secondname $e->first_lastname $e->second_lastname");
                },
            ],
            'Último acceso' => [
                'format' => function ($e) {
                    return LoginAttemptsModel::lastLogin($e->user_id)->format('d-m-Y H:i:s');
                },
            ],
            'Tiempo en plataforma' => [
                'format' => function ($e) {
                    $timeOnPlatfom = TimeOnPlatformModel::getRecordByUser($e->user_id);
                    return !is_null($timeOnPlatfom) ? round($timeOnPlatfom->minutes, 0) . ' minuto(s)' : 'Sin registro';
                },
            ],
        ];

        return self::exportExcelFile($response, $columns, $result, 'Registro de ingreso');

    }

    /**
     * Genera un archivo excel
     *
     * @param Response $response
     * @param Array $columns [format|dataKey]
     * @param Array $data
     * @return String $fileName
     */
    public static function exportExcelFile(Response $response, array $columns, array $data, String $fileName)
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

            foreach ($columns as $key => $columnInfo) {

                if (key_exists('format', $columnInfo)) {
                    $activeSheet->setCellValueExplicit(excelColumnByIndex($indexColumn) . $indexRow, $columnInfo['format']($e), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING2);
                } elseif (key_exists('dataKey', $columnInfo)) {
                    $activeSheet->setCellValueExplicit(excelColumnByIndex($indexColumn) . $indexRow, $e->{$columnInfo['dataKey']}, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING2);
                } else {
                    return false;
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
        ];
        $group->register([
            new Route(
                '/reports-access[/]',
                self::class . ':reportsAccess',
                'informes-acceso',
                'GET',
                true,
                null,
                $accessReports
            ),
            new Route(
                '/attempts-export[/]',
                self::class . ':attemptsExport',
                'attempts-export',
                'GET',
                true,
                null,
                $accessReports
            ),
            new Route(
                '/not-logged-export[/]',
                self::class . ':notLoggedExport',
                'not-logged-export',
                'GET',
                true,
                null,
                $accessReports
            ),
            new Route(
                '/logged-export[/]',
                self::class . ':loggedExport',
                'logged-export',
                'GET',
                true,
                null,
                $accessReports
            ),
            (new Route(
                '/reports-access/{type}[/]',
                self::class . ':reportsAccess',
                'informes-acceso-ajax',
                'GET',
                true,
                null,
                $accessReports
            ))->setParameterValue('type', 'not-logged'),
        ]);

        return $group;
    }

}
