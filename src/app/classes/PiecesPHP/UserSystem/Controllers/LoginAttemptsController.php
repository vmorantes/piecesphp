<?php

/**
 * LoginAttemptsController.php
 */

namespace PiecesPHP\UserSystem\Controllers;

use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\AdminPanel\DashboardCard;
use PiecesPHP\AdminPanel\DashboardCardBody;
use PiecesPHP\AdminPanel\DashboardCardRegistry;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
use PiecesPHP\Core\Routing\Slim3Compatibility\Exception\NotFoundException;
use PiecesPHP\Logs\Controllers\LogsController;
use PiecesPHP\Logs\LogTab;
use PiecesPHP\Logs\LogTabRegistry;
use PiecesPHP\UserSystem\ORM\LoginAttemptsModel;
use PiecesPHP\UserSystem\ORM\TimeOnPlatformModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\UserDataPackage;
use PiecesPHP\UserSystem\UserHierarchy;
use PiecesPHP\UserSystem\UserSystemFeaturesLang;
use PiecesPHP\UserSystem\UserSystemFeaturesRoutes;
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

    /**
     * La key de su pestaña en «Registros».
     */
    const ACCESS_TAB = 'access';

    /**
     * Las sub-vistas de la pestaña, en su orden; la primera es la de por omisión.
     */
    const ACCESS_VIEWS = ['attempts', 'logged', 'not-logged'];

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

        }

        //La pantalla vieja: ahora es la pestaña «Accesos» de «Registros», con la misma sub-vista.
        $view = null;
        foreach (self::ACCESS_VIEWS as $candidate) {
            if ($request->getQueryParam($candidate, 'no') === 'yes') {
                $view = $candidate;
                break;
            }
        }
        if ($view === null) {
            throw new NotFoundException($request, $response);
        }
        if (!LogsController::allowedRoute('index')) {
            return throw403($request);
        }
        return $response->withRedirect(LogsController::routeName('index') . '?' . http_build_query(['tab' => self::ACCESS_TAB, 'view' => $view]));
    }

    /**
     * La pestaña «Accesos» de «Registros»: intentos, ingresos y usuarios sin ingreso, recortados por la jerarquía de
     * P133 (UserHierarchy) según quien mira.
     *
     * @param Request $request
     * @param string $tabURL
     * @return string
     */
    public static function accessTab(Request $request, string $tabURL): string
    {
        //Las piezas de estadísticas no traen su hoja (16 §9); las gráficas, con Apache ECharts.
        add_global_asset('statics/core/css/graphs-and-statistics.css', 'css');
        import_echarts();
        add_global_asset('statics/core/js/statistics-chart-legend.js', 'js');
        add_global_asset(UserSystemFeaturesRoutes::staticRoute('js/access-reports.js'), 'js');

        $view = $request->getQueryParam('view', null);
        $view = is_string($view) && in_array($view, self::ACCESS_VIEWS, true) ? $view : self::ACCESS_VIEWS[0];

        $titles = [
            'attempts' => [__(LOGIN_REPORT_LANG_GROUP, 'Intentos de Ingresos'), 'sign in alternate'],
            'logged' => [__(LOGIN_REPORT_LANG_GROUP, 'Registro de ingreso'), 'chart bar'],
            'not-logged' => [__(LOGIN_REPORT_LANG_GROUP, 'Usuarios sin Ingreso'), 'user times'],
        ];

        //Las cifras salen aquí, con la misma jerarquía que el listado (P133); el JavaScript solo dibuja.
        //El periodo va en la URL (`days`) de cada sub-vista: cambiarlo recarga, como los filtros de Errores.
        [$days, $from, $to] = LoginAttemptsModel::periodFromRequest($request);
        $periods = [];
        foreach (LoginAttemptsModel::PERIODS as $period) {
            $periods[] = [
                'days' => $period,
                'url' => $tabURL . '&' . http_build_query(['view' => $view, 'days' => $period]),
                'active' => $period === $days,
            ];
        }
        $data = [
            'days' => $days,
            'periods' => $periods,
            'from' => $from,
            'until' => $to->modify('-1 day'),
        ];
        if ($view === 'attempts') {
            $stats = LoginAttemptsModel::periodStats($from, $to);
            //El anterior, de la misma longitud y pegado a este.
            $previous = LoginAttemptsModel::countsBySuccess(null, $from->modify('-' . $days . ' days'), $from);
            $oldest = LoginAttemptsModel::oldestDate();
            $data += [
                'stats' => $stats,
                'previousTotal' => $previous['total'],
                'oldest' => LoginAttemptsModel::trimmedSince($oldest, $from, LoginAttemptsModel::storedCount(), LoginAttemptsModel::maxRows()),
                'maxRows' => LoginAttemptsModel::maxRows(),
                'tableURL' => self::routeName('reports-ajax', ['type' => 'attempts']) . '?' . http_build_query(['days' => $days]),
            ];
        } else {
            //Las cifras, sobre las cuentas VIVAS visibles: con ingreso = al menos uno correcto dentro del periodo; sin
            //ingreso, el resto. Los Eliminados que entraron salen en la tabla de ingreso (es actividad), no aquí.
            $withLogin = self::usersWithSuccessfulLogin($from);
            $alive = array_values(array_filter(self::visibleUsers(), fn($e) => !self::isDeleted($e)));
            $loggedUsers = count(array_filter($alive, fn($e) => isset($withLogin[(int) $e->id])));
            $data += [
                'totalUsers' => count($alive),
                'loggedUsers' => $loggedUsers,
                'notLoggedUsers' => count($alive) - $loggedUsers,
                'deletedLoggedUsers' => count(array_filter(self::visibleUsers(), fn($e) => self::isDeleted($e) && isset($withLogin[(int) $e->id]))),
                'tableURL' => self::routeName('reports-ajax', ['type' => $view]) . '?' . http_build_query(['days' => $days]),
            ];
            if ($view === 'logged') {
                //El tiempo en la plataforma se guarda acumulado, sin fecha: no admite periodo.
                $data['allTimeOnPlatform'] = self::formatDecimal((float) str_replace(',', '', (string) TimeOnPlatformModel::getAllHoursOnPlatform()));
            }
        }
        $data['exportUrl'] = self::exportURLFor($view) . '?' . http_build_query(['days' => $days]);

        $renderer = (new HelperController())->setInstanceViewDir(__DIR__ . '/../Views/');
        //Sin carpeta propia: pinta las piezas del panel (view/panel/built-in/statistics/).
        $data['pieces'] = new HelperController();
        $views = [];
        foreach (self::ACCESS_VIEWS as $candidate) {
            $views[] = [
                'view' => $candidate,
                'title' => $titles[$candidate][0],
                'icon' => $titles[$candidate][1],
                'url' => $tabURL . '&' . http_build_query(['view' => $candidate]),
                'active' => $candidate === $view,
            ];
        }

        return (string) $renderer->render('panel/pages/login-reports/access-tab', [
            'langGroup' => LOGIN_REPORT_LANG_GROUP,
            'views' => $views,
            'content' => (string) $renderer->render('panel/pages/login-reports/' . $view, $data, false),
        ], false);
    }

    /**
     * La tarjeta «Usuarios y accesos» del inicio, para quien administra usuarios: los activos y los accesos de hoy.
     *
     * @return void
     */
    public static function registerDashboardCard(): void
    {
        DashboardCardRegistry::register(new DashboardCard(
            'users-access',
            __(LOGIN_REPORT_LANG_GROUP, 'Usuarios y accesos'),
            'users',
            'users-list',
            function (UserDataPackage $user): ?DashboardCardBody {
                $database = (new BaseModel())->getDatabase();
                if ($database === null) {
                    return null;
                }
                $active = $database->prepare('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE status = ? AND id != ?');
                $active->execute([UsersModel::STATUS_USER_ACTIVE, UsersModel::SYSTEM_USER_ID]);
                $today = $database->prepare('SELECT COUNT(*) FROM `' . LoginAttemptsModel::TABLE . '` WHERE success = 1 AND date >= ?');
                $today->execute([date('Y-m-d 00:00:00')]);
                return DashboardCardBody::numbers(
                    [
                        ['value' => (int) $active->fetchColumn(), 'concept' => __(LOGIN_REPORT_LANG_GROUP, 'usuarios activos'), 'icon' => 'users'],
                        ['value' => (int) $today->fetchColumn(), 'concept' => __(LOGIN_REPORT_LANG_GROUP, 'accesos hoy'), 'icon' => 'sign in alternate'],
                    ],
                    __(LOGIN_REPORT_LANG_GROUP, 'Ver los accesos'),
                    LogsController::routeName('index') . '?' . http_build_query(['tab' => self::ACCESS_TAB])
                );
            },
            30,
            help: __(LOGIN_REPORT_LANG_GROUP, 'Los usuarios activos de la plataforma y los accesos correctos de hoy.')
        ));
    }

    /**
     * @return void
     */
    public static function registerLogTab(): void
    {
        //El título se traduce AL REGISTRARSE: con un grupo ya inyectado (loginReport llega después, en final-configurations).
        LogTabRegistry::register(new LogTab(
            self::ACCESS_TAB,
            __(UserSystemFeaturesLang::LANG_GROUP, 'Accesos'),
            'sign in alternate',
            self::$baseRouteName . '-reports-ajax',
            fn(Request $request, string $tabURL): string => self::accessTab($request, $tabURL),
            30,
            __(UserSystemFeaturesLang::LANG_GROUP, 'Ingresos e intentos de acceso de los usuarios.')
        ));
    }

    /**
     * @param Response $response
     * @return Response
     */
    public function attemptsExport(Request $request, Response $response)
    {

        //all() filtra con la misma jerarquía que el listado (P133): exportar no puede enseñar más que mirar. El periodo y el
        //resultado, los de la pestaña (`days`, `result`).
        [, $from, $to] = LoginAttemptsModel::periodFromRequest($request);
        $resultFilter = $request->getQueryParam('result', '');
        $result = array_values(array_filter(LoginAttemptsModel::all(), function ($row) use ($from, $to, $resultFilter): bool {
            $time = is_string($row->date ?? null) ? strtotime($row->date) : false;
            if ($time === false || $time < $from->getTimestamp() || $time >= $to->getTimestamp()) {
                return false;
            }
            return match ($resultFilter) {
                'success' => $row->success == LoginAttemptsModel::SUCCESS_ATTEMPT,
                'error' => $row->success != LoginAttemptsModel::SUCCESS_ATTEMPT,
                default => true,
            };
        }));

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

        //La misma jerarquía que el listado (P133): exportar no puede enseñar más que mirar. Y el periodo (`days`).
        [, $from] = LoginAttemptsModel::periodFromRequest($request);
        $logged = self::usersWithSuccessfulLogin($from);
        //Como su tabla: sin los Eliminados (no son cuentas sin ingreso).
        $result = array_values(array_filter(self::visibleUsers(), fn($e) => !self::isDeleted($e) && !isset($logged[(int) $e->id])));

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

        //La misma jerarquía que el listado (P133): exportar no puede enseñar más que mirar. Y el periodo (`days`).
        [, $from] = LoginAttemptsModel::periodFromRequest($request);
        $logged = self::usersWithSuccessfulLogin($from);
        $result = array_values(array_filter(self::visibleUsers(), fn($e) => isset($logged[(int) $e->id])));

        $columns = [
            'ID' => [
                'dataKey' => 'id',
            ],
            'Nombre' => [
                'format' => function ($e) {
                    //Los Eliminados que entraron siguen aquí (es actividad), marcados.
                    return trim("$e->firstname $e->secondname $e->firstLastname $e->secondLastname") . (self::isDeleted($e) ? ' (' . __(LOGIN_REPORT_LANG_GROUP, 'Eliminado') . ')' : '');
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
     * @param object $user Una fila de visibleUsers()
     * @return bool
     */
    private static function isDeleted(object $user): bool
    {
        return (int) ($user->status ?? 0) === UsersModel::STATUS_USER_DELETED;
    }

    /**
     * Un número con un decimal y los separadores del idioma: la traducción de «,» es el decimal y la de «.», el de miles
     * (en español, 1.203,5; en inglés, 1,203.5).
     *
     * @param float $value
     * @return string
     */
    public static function formatDecimal(float $value): string
    {
        return number_format($value, 1, __(LOGIN_REPORT_LANG_GROUP, ','), __(LOGIN_REPORT_LANG_GROUP, '.'));
    }

    /**
     * Los usuarios que quien mira puede ver (P133): lo suyo y lo de rangos menores.
     *
     * @return array<int,\stdClass>
     */
    private static function visibleUsers(): array
    {
        $currentUser = getLoggedFrameworkUser();
        return array_values(array_filter(UsersModel::all(), fn($user) => UserHierarchy::canSee(
            $currentUser,
            (int) $user->id,
            (int) $user->type,
            is_numeric($user->organization ?? null) ? (int) $user->organization : null
        )));
    }

    /**
     * Los usuarios con al menos un ingreso exitoso (desde `$from`, si se da), como claves.
     *
     * @param \DateTimeInterface|null $from
     * @return array<int,true>
     */
    private static function usersWithSuccessfulLogin(?\DateTimeInterface $from = null): array
    {
        $model = LoginAttemptsModel::model();
        $items = [new WhereItem('success', WhereItem::EQUAL_OPERATOR, LoginAttemptsModel::SUCCESS_ATTEMPT, WhereItem::AND_OPERATOR)];
        if ($from !== null) {
            $items[] = new WhereItem('date', WhereItem::GREATER_OR_EQUAL_OPERATOR, $from->format('Y-m-d H:i:s'), WhereItem::AND_OPERATOR);
        }
        $model->select('userID')->where(new WhereSegment($items))->groupBy('userID');
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
