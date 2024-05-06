<?php

/**
 * DataImportExportUtilityController.php
 */

namespace DataImportExportUtility\Controllers;

use App\Controller\AdminPanelController;
use App\Model\UsersModel;
use DataImportExportUtility\DataImportExportUtilityLang;
use DataImportExportUtility\DataImportExportUtilityRoutes;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Utilities\Helpers\MetaTags;
use PiecesPHP\Core\Validation\Validator;
use PiecesPHP\RoutingUtils\DefaultAccessControlModules;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;

/**
 * DataImportExportUtilityController.
 *
 * Importa los datos iniciales usando la estructura prevista en los archivos SQL:
 * - project://databases/Utilidades_Datos_iniciales/Tablas.sql
 *
 * @package     DataImportExportUtility\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2022
 */
class DataImportExportUtilityController extends AdminPanelController
{

    /**
     * @var string
     */
    protected static $URLDirectory = 'data-import-export-utility';
    /**
     * @var string
     */
    protected static $baseRouteName = 'data-import-export-utility-admin';

    /**
     * @var HelperController
     */
    protected $helpController = null;

    const LANG_GROUP = DataImportExportUtilityLang::LANG_GROUP;

    public function __construct()
    {
        parent::__construct(false); //No cargar ningún modelo automáticamente.
        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());
        $this->setInstanceViewDir(__DIR__ . '/../Views/');

    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function showRoutes(Request $request, Response $response, array $args)
    {

        $parseParams = function ($params) {
            $paramsString = [];
            foreach ($params as $paramName => $paramValue) {
                $paramsString[] = "{$paramName}={$paramValue}";
            }
            return "?" . implode('&', $paramsString);
        };

        $urlsImports = [
            [
                'text' => 'Importar usuarios',
                'link' => self::allowedRoute('import-users') ? self::routeName('import-users') . ($parseParams)([
                    "force" => 'no',
                    "update" => 'no',
                ]) : '',
            ],
        ];
        foreach ($urlsImports as $urlImport) {
            $link = $urlImport['link'];
            $text = $urlImport['text'];
            if (mb_strlen($link) == 0) {
                continue;
            }
            echo "<strong><a target='_blank' href='$link'>$text</a></strong><br>";
        }

        echo "<br>";

        $urlsExports = [
            [
                'text' => 'Exportar usuarios',
                'link' => self::allowedRoute('export-users') ? self::routeName('export-users') . ($parseParams)([
                    "force" => 'no',
                ]) : '',
            ],
        ];
        foreach ($urlsExports as $urlExport) {
            $link = $urlExport['link'];
            $text = $urlExport['text'];
            if (mb_strlen($link) == 0) {
                continue;
            }
            echo "<strong><a target='_blank' href='$link'>$text</a></strong><br>";
        }

        echo "<br>";

        $urlsOthers = [
            [
                'text' => 'Registro de importaciones (usuarios)',
                'link' => self::allowedRoute('show-imported-generated') ? self::routeName('show-imported-generated') . ($parseParams)([]) : '',
            ],
        ];
        foreach ($urlsOthers as $urlOther) {
            $link = $urlOther['link'];
            $text = $urlOther['text'];
            if (mb_strlen($link) == 0) {
                continue;
            }
            echo "<strong><a target='_blank' href='$link'>$text</a></strong><br>";
        }

        return $response;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function showImportedGenerated(Request $request, Response $response, array $args)
    {

        set_custom_assets([
            DataImportExportUtilityRoutes::staticRoute('css/print-view.css'),
        ], 'css');

        $checkPassword = $request->getQueryParam('checkPassword', 'no') === 'yes';
        $dataSelected = $request->getQueryParam('dataSelected', '');
        $dataSelected = mb_strlen($dataSelected) > 0 ? url_safe_base64_decode($dataSelected) : '';
        $dataSelectedElements = file_exists($dataSelected) ? json_decode(file_get_contents($dataSelected)) : [];

        $data = [];

        if (file_exists($dataSelected)) {
            $data['title'] = str_replace(['.json'], '', basename($dataSelected));
            MetaTags::setTitle($data['title']);
        }

        $iteratedElements = 0;
        $page = 1;
        $perPage = 6;
        $dataPaginated = [];
        foreach ($dataSelectedElements as $dataSelectedElement) {

            if ($iteratedElements < $perPage) {
                $dataPaginated[$page][] = $dataSelectedElement;
            } else {
                $page++;
                $dataPaginated[$page][] = $dataSelectedElement;
                $iteratedElements = 0;
            }

            $iteratedElements++;

        }

        $data['dataOptions'] = self::getUsersImportGeneratedFiles();
        $data['dataPaginated'] = $dataPaginated;
        $data['checkPassword'] = $checkPassword;

        self::view('imported-generated', $data);

        return $response;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function usersImport(Request $request, Response $response, array $args)
    {
        ini_set('max_execution_time', '300');

        $update = $request->getQueryParam('update', null) === 'yes';
        $force = $request->getQueryParam('force', null) === 'yes' || $update;

        $currentUser = get_config('current_user');
        $currentUserID = (int) $currentUser->id;

        $baseModel = new BaseModel();
        $baseModel->setTable('ODS_Usuarios');
        $baseModel->select()->execute();
        $elements = $baseModel->result();

        echo "INICIO DE IMPORTACIÓN.\r\n\r\n";

        $model = UsersModel::model();
        $model->select('id')->execute();
        $hasData = !empty($model->result());

        if (!$hasData || $force) {

            $addedUsers = [];
            $validateString = function ($str, bool $withLen = false) {
                return $str !== null && is_string($str) && ($withLen ? mb_strlen(trim($str)) > 0 : true);
            };

            foreach ($elements as $element) {

                $ID = $element->ID !== null && Validator::isInteger($element->ID) ? (int) $element->ID : null;
                $Email = ($validateString)($element->Email, true) ? clean_string($element->Email) : null;
                $Usuario = ($validateString)($element->Usuario, true) ? clean_string($element->Usuario) : null;
                $Contraseña = ($validateString)($element->Contraseña, true) ? clean_string($element->Contraseña) : null;
                $PrimerNombre = clean_string($element->PrimerNombre);
                $SegundoNombre = ($validateString)($element->SegundoNombre, true) ? clean_string($element->SegundoNombre) : '';
                $PrimerApellido = clean_string($element->PrimerApellido);
                $SegundoApellido = ($validateString)($element->SegundoApellido, true) ? clean_string($element->SegundoApellido) : '';
                $elementByID = (new UsersModel())->getByID($ID);
                $username = $Usuario === null ? (function ($a, $b, $c, $d) {

                    $option1 = mb_strtolower(clean_string(str_replace(' ', '', clean_string($a . $c))));
                    $option2 = mb_strtolower(clean_string(str_replace(' ', '', clean_string($b . $c))));
                    $option3 = mb_strtolower(clean_string(str_replace(' ', '', clean_string($a . $b . $c))));
                    $option4 = mb_strtolower(clean_string(str_replace(' ', '', clean_string($a . $b . $c . $d))));

                    $result = '';
                    $number = 0;
                    while (mb_strlen($result) == 0) {

                        $baseOption1 = $option1;
                        $baseOption2 = $option2;
                        $baseOption3 = $option3;
                        $baseOption4 = $option4;

                        if ($number > 0) {
                            $baseOption1 = $option1 . $number;
                            $baseOption2 = $option2 . $number;
                            $baseOption3 = $option3 . $number;
                            $baseOption4 = $option4 . $number;
                        }

                        if (!UsersModel::isDuplicateUsername($baseOption1, -1)) {
                            $result = $baseOption1;
                        } elseif (!UsersModel::isDuplicateUsername($baseOption2, -1)) {
                            $result = $baseOption2;
                        } elseif (!UsersModel::isDuplicateUsername($baseOption3, -1)) {
                            $result = $baseOption3;
                        } elseif (!UsersModel::isDuplicateUsername($baseOption4, -1)) {
                            $result = $baseOption4;
                        } else {
                            $number++;
                        }
                    }

                    return $result;

                })($PrimerNombre, $SegundoNombre, $PrimerApellido, $SegundoApellido) : $Usuario;

                if ($elementByID === null) {

                    $validations = true;

                    if ($validations) {

                        $model = UsersModel::model();
                        $password = $Contraseña !== null ? $Contraseña : rand(100000, 999999);
                        $passwordEncrypt = password_hash($password, PASSWORD_DEFAULT);
                        $email = $Email !== null ? $Email : uniqid() . "@localhost";
                        $type = UsersModel::TYPE_USER_GENERAL;
                        $meta = [];

                        $data = [
                            "password" => $passwordEncrypt,
                            "username" => $username,
                            "firstname" => $PrimerNombre,
                            "secondname" => $SegundoNombre,
                            "first_lastname" => $PrimerApellido,
                            "second_lastname" => $SegundoApellido,
                            "email" => $email,
                            "meta" => json_encode($meta),
                            "type" => $type,
                            "status" => UsersModel::STATUS_USER_ACTIVE,
                            "failed_attempts" => 0,
                            "created_at" => date('Y-m-d H:i:s'),
                        ];

                        $model->insert($data);

                        if ($model->execute()) {
                            $addedUsers[] = [
                                "username" => $username,
                                "fullname" => trim(implode(' ', [
                                    $PrimerNombre,
                                    $SegundoNombre,
                                    $PrimerApellido,
                                    $SegundoApellido,
                                ])),
                                "email" => $email,
                                "password" => $password,
                            ];
                            echo "El usuario {$ID}:{$username} fue añadido.\r\n";
                        } else {
                            echo "El usuario {$ID}:{$username} no pudo ser añadido.\r\n";
                        }

                    } else {

                        $errorMultipleMessages = clean_string(implode(', ', [
                            true ? "Error ejemplo 1" : '',
                            true ? "Error ejemplo 2" : '',
                        ]));
                        echo "El usuario {$ID}:{$username} tiene los siguientes errores: {$errorMultipleMessages}.\r\n";

                    }

                } elseif ($update) {

                    $model = UsersModel::model();
                    $meta = json_encode([]);
                    $username = $elementByID->username;

                    $data = [
                        "firstname" => $PrimerNombre,
                        "secondname" => $SegundoNombre,
                        "first_lastname" => $PrimerApellido,
                        "second_lastname" => $SegundoApellido,
                        "meta" => $meta,
                        "modified_at" => date('Y-m-d H:i:s'),
                    ];

                    $model->update($data)->where("id = {$elementByID->id}");

                    if ($model->execute()) {
                        echo "El usuario {$ID}:{$username} fue actualizado.\r\n";
                    } else {
                        echo "El usuario {$ID}:{$username} no pudo ser actualizado.\r\n";
                    }

                } else {
                    if ($force) {
                        echo "El usuario {$ID}:{$username} fue ignorado.\r\n";
                    } else {
                        echo "El usuario {$ID}:{$username} ya existe.\r\n";
                    }
                }

            }

            $addedUsersFilename = 'Usuarios añadidos - ' . date('d-m-Y H i s') . '.json';
            file_put_contents(__DIR__ . "/../GeneratedData/{$addedUsersFilename}", json_encode($addedUsers));

        } else {
            echo "NO SE PUDO IMPORTAR PORQUE YA HAY INFORMACIÓN.\r\n";
        }

        echo "\r\nFIN DE IMPORTACIÓN.\r\n";

        return $response->withHeader('Content-Type', 'text/plain');
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function usersExport(Request $request, Response $response, array $args)
    {

        $whereString = null;
        $table = UsersModel::TABLE;
        $where = [
            "{$table}.type = " . UsersModel::TYPE_USER_GENERAL,
        ];

        if (count($where) > 0) {
            $whereString = trim(implode(' ', $where));
        }

        //idPadding
        //fullname
        //names
        //lastNames
        //typeName
        //statusText
        //documentTypeText
        //documentAndType
        //schoolName
        //gradeName
        //groupName
        $selectFields = UsersModel::fieldsToSelect();

        $customOrder = [
            'idPadding ASC',
        ];

        $model = UsersModel::model();

        $model->select($selectFields);

        if ($whereString !== null) {
            $model->having($whereString);
        }

        $model->orderBy($customOrder);

        $model->execute();

        $result = $model->result();

        $columns = [];
        $columns[] = 'ID';
        $columns[] = 'Documento';
        $columns[] = 'Nombre 1';
        $columns[] = 'Nombre 2';
        $columns[] = 'Apellido 1';
        $columns[] = 'Apellido 2';
        $columns[] = 'Usuario';
        $columns[] = 'Email';
        $columns[] = 'Colegio';
        $columns[] = 'Grado';
        $columns[] = 'Grupo';

        $spreadsheet = new Spreadsheet();
        $writer = new Xlsx($spreadsheet);

        $spreadsheet->setActiveSheetIndex(0);
        $activeSheet = $spreadsheet->getActiveSheet();

        foreach ($columns as $index => $column) {
            $activeSheet->setCellValue(excelColumnByIndex($index) . '1', $column);

        }

        $indexColumn = 0;
        $indexRow = 2;

        foreach ($result as $e) {

            $meta = (object) json_decode($e->meta);

            if (false) {
                //Saltar según alguna condición
                continue;
            }

            //Nombre 1
            $indexColumn++;
            $activeSheet->setCellValueExplicit(excelColumnByIndex($indexColumn) . $indexRow, $e->firstname, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING2);

            //Nombre 2
            $indexColumn++;
            $activeSheet->setCellValueExplicit(excelColumnByIndex($indexColumn) . $indexRow, $e->secondname, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING2);

            //Apellido 1
            $indexColumn++;
            $activeSheet->setCellValueExplicit(excelColumnByIndex($indexColumn) . $indexRow, $e->first_lastname, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING2);

            //Apellido 2
            $indexColumn++;
            $activeSheet->setCellValueExplicit(excelColumnByIndex($indexColumn) . $indexRow, $e->second_lastname, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING2);

            //Usuario
            $indexColumn++;
            $activeSheet->setCellValueExplicit(excelColumnByIndex($indexColumn) . $indexRow, $e->username, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING2);

            //Email
            $indexColumn++;
            $activeSheet->setCellValueExplicit(excelColumnByIndex($indexColumn) . $indexRow, $e->email, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING2);

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

        $fileName = "Usuarios - Exportado el " . date('d-m-Y h i A') . '.xlsx';

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
     * @return array
     */
    public static function getUsersImportGeneratedFiles()
    {
        $directoryContents = new RecursiveDirectoryIterator(__DIR__ . "/../GeneratedData");
        $directoryContentsIterator = new RecursiveIteratorIterator($directoryContents);
        $directoryContentsIteratorRegexp = new RegexIterator($directoryContentsIterator, '/Usuarios añadidos .+\.json$/im', RecursiveRegexIterator::ALL_MATCHES);
        $elements = [];
        foreach ($directoryContentsIteratorRegexp as $filePath => $regExpResult) {
            $isOld = mb_strpos($filePath, 'Anteriores') !== false;
            $elements[realpath($filePath)] = !$isOld ? basename($filePath) : '(Anteriores) ' . basename($filePath);
        }
        return $elements;
    }
    /**
     * @param string $name
     * @param array $data
     * @param bool $mode
     * @param bool $format
     * @return void|string
     */
    public static function view(string $name, array $data = [], bool $mode = true, bool $format = true)
    {
        $name = trim(trim($name, '/'), '/');
        return (new DataImportExportUtilityController)->render($name, $data, $mode, $format);
    }

    /**
     * Verificar si una ruta es permitida
     *
     * @param string $name
     * @param array $params
     * @return bool
     */
    public static function allowedRoute(string $name, array $params = [])
    {
        $route = self::routeName($name, $params, true);
        $allow = strlen($route) > 0;
        return $allow;
    }

    /**
     * Verificar si una ruta es permitida y determinar pasos para permitirla o no
     *
     * @param string $name
     * @param string $route
     * @param array $params
     * @return bool
     */
    private static function _allowedRoute(string $name, string $route, array $params = [])
    {

        $getParam = function ($paramName) use ($params) {
            $_POST = isset($_POST) && is_array($_POST) ? $_POST : [];
            $_GET = isset($_GET) && is_array($_GET) ? $_GET : [];
            $paramValue = isset($params[$paramName]) ? $params[$paramName] : null;
            $paramValue = $paramValue !== null ? $paramValue : (isset($_GET[$paramName]) ? $_GET[$paramName] : null);
            $paramValue = $paramValue !== null ? $paramValue : (isset($_POST[$paramName]) ? $_POST[$paramName] : null);
            return $paramValue;
        };

        $allow = strlen($route) > 0;

        if ($allow) {

            $currentUser = get_config('current_user');

            if (is_object($currentUser)) {

                $currentUserType = (int) $currentUser->type;
                $currentUserID = (int) $currentUser->id;

            }

        }

        return $allow;
    }

    /**
     * Obtener URL de una ruta
     *
     * @param string $name
     * @param array $params
     * @param bool $silentOnNotExists
     * @return string
     */
    public static function routeName(string $name = null, array $params = [], bool $silentOnNotExists = false)
    {

        $simpleName = $name;

        if (!is_null($name)) {
            $name = trim($name);
            $name = strlen($name) > 0 ? "-{$name}" : '';
        }

        $name = !is_null($name) ? self::$baseRouteName . $name : self::$baseRouteName;

        $allowed = false;
        $current_user = get_config('current_user');

        if ($current_user != false) {
            $allowed = Roles::hasPermissions($name, (int) $current_user->type);
        } else {
            $allowed = true;
        }

        $route = '';

        if ($allowed) {
            $route = get_route(
                $name,
                $params,
                $silentOnNotExists
            );
            $route = !is_string($route) ? '' : $route;
        }

        $allow = self::_allowedRoute($simpleName, $route, $params);

        return $allow ? $route : '';
    }

    /**
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {
        $routes = [];

        $groupSegmentURL = $group->getGroupSegment();

        $lastIsBar = last_char($groupSegmentURL) == '/';
        $startRoute = ($lastIsBar ? '' : '/') . self::$URLDirectory;

        $classname = self::class;

        $allRoles = array_keys(UsersModel::TYPES_USERS);

        //Permisos
        $accessGeneral = [
            UsersModel::TYPE_USER_ROOT,
        ];
        $accessAdmin = [
            UsersModel::TYPE_USER_ROOT,
            UsersModel::TYPE_USER_ADMIN,
        ];

        $routes = [

            //──── GET ───────────────────────────────────────────────────────────────────────────────
            new Route(
                "{$startRoute}[/]",
                $classname . ':showRoutes',
                self::$baseRouteName . '-show-routes',
                'GET',
                true,
                null,
                $accessGeneral
            ),
            new Route(
                "{$startRoute}/imported-generated[/]",
                $classname . ':showImportedGenerated',
                self::$baseRouteName . '-show-imported-generated',
                'GET',
                true,
                null,
                $accessGeneral
            ),
            new Route(
                "{$startRoute}/import-users[/]",
                $classname . ':usersImport',
                self::$baseRouteName . '-import-users',
                'GET',
                true,
                null,
                $accessGeneral
            ),
            new Route(
                "{$startRoute}/export-users[/]",
                $classname . ':usersExport',
                self::$baseRouteName . '-export-users',
                'GET',
                true,
                null,
                $accessAdmin
            ),
        ];

        $group->register($routes);

        $group->addMiddleware(function (\PiecesPHP\Core\Routing\RequestRoute $request, $handler) {
            return (new DefaultAccessControlModules(self::$baseRouteName . '-', function (string $name, array $params) {
                return self::routeName($name, $params);
            }))->getResponse($request, $handler);
        });

        return $group;
    }
}
