<?php

/**
 * SystemApprovalsController.php
 */

namespace SystemApprovals\Controllers;

use ApplicationCalls\Mappers\ApplicationCallsMapper;
use App\Controller\AdminPanelController;
use App\Model\AvatarModel;
use App\Model\UsersModel;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\ConfigHelpers\MailConfig;
use PiecesPHP\Core\Forms\FileUpload;
use PiecesPHP\Core\Forms\FileValidator;
use PiecesPHP\Core\Mailer;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Routing\Slim3Compatibility\Exception\NotFoundException;
use PiecesPHP\Core\Utilities\Helpers\DataTablesHelper;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use PiecesPHP\Core\Validation\Parameters\Exceptions\InvalidParameterValueException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\MissingRequiredParamaterException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\ParsedValueException;
use PiecesPHP\Core\Validation\Parameters\Parameter;
use PiecesPHP\Core\Validation\Parameters\Parameters;
use PiecesPHP\Core\Validation\Validator;
use PiecesPHP\RoutingUtils\DefaultAccessControlModules;
use Publications\Mappers\PublicationMapper;
use SystemApprovals\Exceptions\DuplicateException;
use SystemApprovals\Exceptions\SafeException;
use SystemApprovals\Mappers\SystemApprovalsMapper;
use SystemApprovals\SystemApprovalsLang;
use SystemApprovals\SystemApprovalsRoutes;
use SystemApprovals\Util\SystemApprovalManager;

/**
 * SystemApprovalsController.
 *
 * @package     SystemApprovals\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class SystemApprovalsController extends AdminPanelController
{

    /**
     * @var string
     */
    protected static $URLDirectory = 'system-approval';
    /**
     * @var string
     */
    protected static $baseRouteName = 'system-approval-admin';
    /**
     * @var string
     */
    protected static $title = 'Aprobación';

    /**
     * @var string
     */
    protected $uploadDir = '';
    /**
     * @var string
     */
    protected $uploadTmpDir = '';
    /**
     * @var string
     */
    protected $uploadDirURL = '';
    /**
     * @var string
     */
    protected $uploadDirTmpURL = '';
    /**
     * @var HelperController
     */
    protected $helpController = null;

    const BASE_VIEW_DIR = '';
    const BASE_JS_DIR = 'js';
    const BASE_CSS_DIR = 'css';
    const UPLOAD_DIR = 'system-approval';
    const UPLOAD_DIR_TMP = 'system-approval/tmp';
    const LANG_GROUP = SystemApprovalsLang::LANG_GROUP;

    const RESPONSE_SOURCE_STATIC_CACHE = 'STATIC_CACHE';
    const RESPONSE_SOURCE_NORMAL_RESULT = 'NORMAL_RESULT';
    const ENABLE_CACHE = true;

    public function __construct()
    {
        parent::__construct();

        $this->model = (new SystemApprovalsMapper())->getModel();

        $baseURL = base_url();
        $pcsUploadDir = get_config('upload_dir');
        $pcsUploadDirURL = get_config('upload_dir_url');

        $this->uploadDir = append_to_path_system($pcsUploadDir, self::UPLOAD_DIR);
        $this->uploadTmpDir = append_to_path_system($pcsUploadDir, self::UPLOAD_DIR_TMP);
        $this->uploadDirURL = str_replace($baseURL, '', append_to_url($pcsUploadDirURL, self::UPLOAD_DIR));
        $this->uploadDirTmpURL = str_replace($baseURL, '', append_to_url($pcsUploadDirURL, self::UPLOAD_DIR_TMP));

        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());

        $this->setInstanceViewDir(__DIR__ . '/../Views/');

        add_global_asset(SystemApprovalsRoutes::staticRoute('globals-vars.css'), 'css');
        add_global_asset(SystemApprovalsRoutes::staticRoute(self::BASE_CSS_DIR . '/system-approval.css'), 'css');

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function approvalForm(Request $request, Response $response)
    {

        $elementID = $request->getAttribute('id', -1);
        $elementID = Validator::isInteger($elementID) ? (int) $elementID : -1;
        $approvalMapper = new SystemApprovalsMapper($elementID);
        $referenceMapper = SystemApprovalManager::getInstance()->getMapperInstance($approvalMapper->referenceTable, $approvalMapper->referenceValue);
        $approvalHandler = SystemApprovalManager::getInstance()->getHandler($approvalMapper->referenceTable);
        $currentUser = getLoggedFrameworkUser();
        $currentUserID = $currentUser->id;
        $currenUserType = $currentUser->type;

        $approvalMapperExists = $approvalMapper->id !== null;
        $hasApprovalHandler = $approvalHandler !== null;
        $hasReferenceMapper = $referenceMapper !== null && $referenceMapper->id !== null;
        $contactUser = $approvalHandler::getContactUser($referenceMapper);
        $isSameUser = $contactUser !== null && $contactUser->id == $currentUserID && $currenUserType != UsersModel::TYPE_USER_ROOT;

        if ($approvalMapperExists && $hasApprovalHandler && $hasReferenceMapper && !$isSameUser) {

            set_custom_assets([
                SystemApprovalsRoutes::staticRoute(self::BASE_JS_DIR . '/approval-form.js'),
            ], 'js');

            $action = self::routeName('actions-approval', ['id' => $approvalMapper->id]);
            $backLink = self::routeName('list');

            $title = strReplaceTemplate(__(self::LANG_GROUP, 'Aprobación de %1'), [
                '%1' => $approvalMapper->referenceAliasLangSensitive(),
            ]);
            $description = '';

            set_title($title . (mb_strlen($description) > 0 ? " - {$description}" : ''));

            $data = [];
            $data['action'] = $action;
            $data['langGroup'] = self::LANG_GROUP;
            $data['approvalMapper'] = $approvalMapper;
            $data['title'] = $title;
            $data['description'] = $description;
            $data['breadcrumbs'] = get_breadcrumbs([
                __(self::LANG_GROUP, 'Inicio') => [
                    'url' => get_route('admin'),
                ],
                __(self::LANG_GROUP, 'Aprobaciones') => [
                    'url' => $backLink,
                ],
                $title,
            ]);
            $formByType = [
                ApplicationCallsMapper::TABLE => 'forms/approval-applications-calls',
                UsersModel::TABLE => 'forms/approval-profile-user',
                OrganizationMapper::TABLE => 'forms/approval-profile-organization',
                PublicationMapper::TABLE => 'forms/approval-publications',
            ];

            $this->helpController->render('panel/layout/header');
            if (array_key_exists($approvalMapper->referenceTable, $formByType)) {
                $this->render($formByType[$approvalMapper->referenceTable], $data);
            } else {
                echo $approvalMapper->referenceTable;
            }
            $this->helpController->render('panel/layout/footer');
        } else {
            throw new NotFoundException($request, $response);
        }

        return $response;

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function listView(Request $request, Response $response)
    {

        $processTableLink = self::routeName('datatables');

        $title = __(self::LANG_GROUP, 'Aprobaciones');
        $description = '';

        set_title($title . (mb_strlen($description) > 0 ? " - {$description}" : ''));
        $referencesAliasesOptions = array_to_html_options(SystemApprovalsMapper::getReferencesAliasesForSelect('', '', true));
        $elapsepDaysOptions = array_to_html_options(SystemApprovalsMapper::getElapsepDaysExistentsForSelect('', '', true));
        $data = [];
        $data['processTableLink'] = $processTableLink;
        $data['referencesAliasesOptions'] = $referencesAliasesOptions;
        $data['elapsepDaysOptions'] = $elapsepDaysOptions;
        $data['langGroup'] = self::LANG_GROUP;
        $data['title'] = $title;
        $data['description'] = $description;
        $data['breadcrumbs'] = get_breadcrumbs([
            __(self::LANG_GROUP, 'Inicio') => [
                'url' => get_route('admin'),
            ],
            $title,
        ]);

        set_custom_assets([
            SystemApprovalsRoutes::staticRoute(self::BASE_JS_DIR . '/list.js'),
        ], 'js');

        $this->helpController->render('panel/layout/header');
        $this->render('list', $data);
        $this->helpController->render('panel/layout/footer');

    }

    /**
     * Aprobación/Rechazo
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function approvalAction(Request $request, Response $response)
    {

        //──── Entrada ───────────────────────────────────────────────────────────────────────────

        //Definición de validaciones y procesamiento
        $expectedParameters = new Parameters([
            new Parameter(
                'approvalStatus',
                null,
                function ($value) {
                    $valid = is_string($value) && mb_strlen(trim($value)) > 0;
                    $allowedStatuses = array_keys(SystemApprovalsMapper::statuses());
                    if ($valid) {
                        $valid = in_array($value, $allowedStatuses);
                        if (!$valid) {
                            throw new SafeException(__(self::LANG_GROUP, 'El estado que intenta asignar no es válido.'));
                        }
                    }
                    return $valid;
                },
                true,
                function ($value) {
                    return is_string($value) && mb_strlen(trim($value)) > 0 ? $value : Config::get_default_lang();
                }
            ),
            new Parameter(
                'reason',
                '',
                function ($value) {
                    return is_string($value) || is_null($value);
                },
                true,
                function ($value) {
                    return is_string($value) ? clean_string($value) : '';
                }
            ),
        ]);

        //Obtención de datos
        $inputData = $request->getParsedBody();

        //Asignación de datos para procesar
        $expectedParameters->setInputValues(is_array($inputData) ? $inputData : []);

        //──── Estructura de respuesta ───────────────────────────────────────────────────────────

        $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'Aprobación'));
        $resultOperation->setSingleOperation(true); //Se define que es de una única operación

        //Valores iniciales de la respuesta
        $resultOperation->setSuccessOnSingleOperation(false);
        $resultOperation->setValue('redirect', false);
        $resultOperation->setValue('redirect_to', null);
        $resultOperation->setValue('reload', false);

        //Mensajes de respuesta
        $notExistsMessage = __(self::LANG_GROUP, 'No existe el elemento que intenta modificar.');
        $successEditMessage = __(self::LANG_GROUP, 'Contenido actualizado');
        $unknowErrorMessage = __(self::LANG_GROUP, 'Ha ocurrido un error desconocido.');
        $unknowErrorWithValuesMessage = __(self::LANG_GROUP, 'Ha ocurrido un error desconocido al procesar los valores ingresados.');

        //──── Acciones ──────────────────────────────────────────────────────────────────────────
        try {

            //Intenta validar, si todo sale bien el código continúa
            $expectedParameters->validate();

            //Información del formulario
            /**
             * @var int $id
             * @var string $approvalStatus
             * @var string $reason
             */
            $id = Validator::isInteger($request->getAttribute('id', null)) ? (int) $request->getAttribute('id', null) : -1;
            $approvalStatus = $expectedParameters->getValue('approvalStatus');
            $reason = $expectedParameters->getValue('reason');

            try {

                $mapper = new SystemApprovalsMapper((int) $id);
                $exists = !is_null($mapper->id);

                if ($exists) {

                    $currentUser = getLoggedFrameworkUser();
                    $previousStatus = $mapper->status;
                    $contactUser = SystemApprovalManager::getInstance()->getContactUser($mapper);

                    if ($previousStatus != $approvalStatus) {
                        $mapper->approvalBy = $currentUser->id;
                        $mapper->approvalAt = new \DateTime();
                        $mapper->status = $approvalStatus;
                        $mapper->reason = $reason;
                        SystemApprovalManager::getInstance()->updateStatus($mapper);
                    }

                    $updated = $mapper->update();
                    $resultOperation->setSuccessOnSingleOperation($updated);

                    if ($updated) {
                        //Envío de correo - INICIO
                        if ($contactUser !== null) {
                            $message = '';
                            $contentName = __(self::LANG_GROUP, $mapper->referenceAlias);
                            if ($mapper->status == SystemApprovalsMapper::STATUS_APPROVED) {
                                $message = strReplaceTemplate(__(self::LANG_GROUP, "Sr(a). {NAME}, le informamos que su contenido \"{CONTENT_NAME}\" ha sido aprobado"), [
                                    '{NAME}' => $contactUser->getFullName(),
                                    '{CONTENT_NAME}' => $contentName,
                                ]);
                            } elseif ($mapper->status == SystemApprovalsMapper::STATUS_REJECTED) {
                                $message = strReplaceTemplate(__(self::LANG_GROUP, "Sr(a). {NAME}, le informamos que su contenido \"{CONTENT_NAME}\" ha sido rechazado"), [
                                    '{NAME}' => $contactUser->getFullName(),
                                    '{CONTENT_NAME}' => $contentName,
                                ]);
                            }
                            $mailer = new Mailer();
                            $mailConfig = new MailConfig;
                            $subject = __(self::LANG_GROUP, 'Aprobaciones');
                            set_title($subject);
                            $subject = get_title(true);
                            $mailer->setFrom($mailConfig->user(), $mailConfig->name());
                            $mailer->addAddress($contactUser->email, $contactUser->getFullName());
                            $mailer->isHTML(true);
                            $mailer->Subject = mb_convert_encoding($subject, 'UTF-8');
                            $data = [];
                            $data['text'] = mb_convert_encoding($message, 'UTF-8');
                            $data['reason'] = mb_convert_encoding($reason, 'UTF-8');
                            $mailer->Body = $this->render('mailing/template_base_no_style', $data, false, false);
                            if (!$mailer->checkSettedSMTP()) {
                                $mailer->asGoDaddy();
                            }
                            $mailer->send();
                        }
                        //Envío de correo - FIN

                        $resultOperation
                            ->setMessage($successEditMessage)
                            ->setValue('reload', false)
                            ->setValue('redirect', true)
                            ->setValue('redirect_to', self::routeName('list'));

                    } else {

                        $resultOperation->setMessage($unknowErrorMessage);

                    }

                } else {

                    $resultOperation->setMessage($notExistsMessage);

                }

            } catch (SafeException | DuplicateException $e) {

                $resultOperation->setMessage($e->getMessage());

            } catch (\Exception $e) {

                $resultOperation->setMessage($e->getMessage());
                log_exception($e);

            }

        } catch (SafeException $e) {

            $resultOperation->setMessage($e->getMessage());

        } catch (ParsedValueException $e) {

            $resultOperation->setMessage($unknowErrorWithValuesMessage);
            log_exception($e);

        } catch (MissingRequiredParamaterException | InvalidParameterValueException | \Exception $e) {

            $resultOperation->setMessage($e->getMessage());
            log_exception($e);

        }

        return $response->withJson($resultOperation);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function dataTables(Request $request, Response $response)
    {
        $referenceAliasFilter = $request->getQueryParam('referenceAlias', null);
        $referenceAliasFilter = is_string($referenceAliasFilter) && mb_strlen(trim($referenceAliasFilter)) > 0 ? trim($referenceAliasFilter) : null;
        $elapsedDaysFilter = $request->getQueryParam('elapsedDays', null);
        $elapsedDaysFilter = is_string($elapsedDaysFilter) && mb_strlen(trim($elapsedDaysFilter)) > 0 ? trim($elapsedDaysFilter) : null;

        $currentUser = getLoggedFrameworkUser();
        $currentUserID = $currentUser->id;
        $currentUserType = $currentUser->type;
        $currentOrganizationID = $currentUser->organization;
        $whereString = null;
        $havingString = null;
        $and = 'AND';
        $table = SystemApprovalsMapper::TABLE;
        $tableUsers = UsersModel::TABLE;
        $pending = SystemApprovalsMapper::STATUS_PENDING;
        $approved = SystemApprovalsMapper::STATUS_APPROVED;
        $baseOrgID = OrganizationMapper::INITIAL_ID_GLOBAL;
        $userTypesThatCanApprovalSelf = implode(',', SystemApprovalsMapper::CAN_APPROVAL_SELF);
        $where = [
            "{$table}.status = '{$pending}'",
        ];
        $having = [
            //Verifica que la referencia se considere "activa"
            "referenceIsActive IS NULL OR referenceIsActive = 1",
            //Verifica que exista la referencia
            "AND referenceCreatedBy IS NOT NULL",
            //Oculta lo que sea del mismo usuario que está viendo (a menos que sea que se incluya en SystemApprovalsMapper::CAN_APPROVAL_SELF)
            "AND (referenceCreatedBy != {$currentUserID} OR {$currentUserType} IN ({$userTypesThatCanApprovalSelf}))",
            //Oculta los perfiles que sean de organizaciones ya aprobadas
            "AND ( ( {$table}.referenceTable != '{$tableUsers}' OR referenceOrganization IS NULL OR referenceOrganization = {$baseOrgID} ) OR (referenceOrtanizationApprovalValue != '{$approved}') )",
        ];

        //Verificar permisos sobre organization
        if ($currentUser !== null) {
            $currentOrganizationID = $currentOrganizationID !== null ? $currentOrganizationID : -1;
            $canModifyOrganizations = OrganizationMapper::canModifyAnyOrganization($currentUserType);
            $canApprovalAll = in_array($currentUserType, SystemApprovalsMapper::CAN_APPROVAL_ALL);
            if (!$canModifyOrganizations && !$canApprovalAll) {
                $beforeOperator = !empty($having) ? $and : '';
                $critery = "referenceOrganization = {$currentOrganizationID} AND referenceOrganizationAdministrator = {$currentUser->id}";
                $having[] = "{$beforeOperator} ({$critery})";
            }
        }

        if ($referenceAliasFilter !== null && $referenceAliasFilter != '-1') {
            $beforeOperator = !empty($where) ? $and : '';
            $critery = "{$table}.referenceAlias = '{$referenceAliasFilter}'";
            $where[] = "{$beforeOperator} ({$critery})";
        }

        if ($elapsedDaysFilter !== null && $elapsedDaysFilter != '-1') {
            $beforeOperator = !empty($having) ? $and : '';
            $critery = "elapsedDays >= {$elapsedDaysFilter}";
            $having[] = "{$beforeOperator} ({$critery})";
        }

        if (!empty($where)) {
            $whereString = trim(implode(' ', $where));
        }

        if (!empty($having)) {
            $havingString = trim(implode(' ', $having));
        }

        $selectFields = SystemApprovalsMapper::fieldsToSelect('%Y-%m-%d %h:%i:%s %p');

        $columnsOrder = [
            'elapsedDays',
            'referenceAlias',
            'referenceDateFormat',
            'referenceUserFullName',
        ];

        $customOrder = [
            'referenceDate' => 'ASC',
        ];

        DataTablesHelper::setTablePrefixOnOrder(false);
        DataTablesHelper::setTablePrefixOnSearch(false);

        $result = DataTablesHelper::process([

            'where_string' => $whereString,
            'having_string' => $havingString,
            'select_fields' => $selectFields,
            'columns_order' => $columnsOrder,
            'custom_order' => $customOrder,
            'mapper' => new SystemApprovalsMapper(),
            'request' => $request,
            'on_set_data' => function ($e) {

                $mapper = SystemApprovalsMapper::objectToMapper($e, true);

                $buttons = [];
                $hasApprovalForm = self::allowedRoute('forms-approval', ['id' => $e->id]);

                if ($hasApprovalForm) {
                    $approvalLink = self::routeName('forms-approval', ['id' => $e->id]);
                    $approvalText = __(self::LANG_GROUP, 'Ir');
                    $approvalButton = "<a href='{$approvalLink}' class='ui button brand-color icon'>&nbsp;{$approvalText}&nbsp;</a>";
                    $buttons[] = $approvalButton;
                }

                $buttons = implode('', $buttons);
                $columns = [];

                $avatar = AvatarModel::getAvatar($e->referenceCreatedBy);
                $avatar = !is_null($avatar) ? $avatar : baseurl('statics/images/default-avatar.png');
                $avatar = "<div class='avatar'><img src='{$avatar}' /></div>";
                $userName = "<div class='name'>{$e->referenceUserFullName}</div>";

                $columns[] = $mapper->getTimeTag();
                $columns[] = __(self::LANG_GROUP, $e->referenceAlias);
                $columns[] = $e->referenceDateFormat;
                $columns[] = "<div class='user-info'>{$avatar} {$userName}</div>";
                $columns[] = $buttons;
                return $columns;
            },

        ]);

        return $response->withJson($result->getValues());
    }

    /**
     * @inheritDoc
     */
    public function render(string $name = "index", array $data = [], bool $mode = true, bool $format = false)
    {
        $name = mb_strlen(self::BASE_VIEW_DIR) > 0 ? self::BASE_VIEW_DIR . '/' . trim($name, '/') : trim($name, '/');
        return parent::render($name, $data, $mode, $format);
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

            $currentUser = getLoggedFrameworkUser();

            if ($currentUser !== null) {

                $currentUserType = $currentUser->type;
                $currentUserID = $currentUser->id;
                $modificationViews = [
                    'forms-approval',
                    'actions-approval',
                ];

                if (in_array($name, $modificationViews)) {

                    $id = ($getParam)('id');
                    if ($currentUserID == $id) {
                        $allow = false;
                    }

                }

            }

        }

        return $allow;
    }

    /**
     * @param string $nameOnFiles
     * @param string $folder
     * @param string $currentRoute
     * @param array $allowedTypes
     * @param bool $setNameByInput
     * @param string $name
     * @param string $suffixName
     * @return string
     * @throws \Exception
     */
    protected static function handlerUpload(string $nameOnFiles, string $folder, string $currentRoute = null, array $allowedTypes = null, bool $setNameByInput = true, string $name = null, string $suffixName = '')
    {
        if ($allowedTypes === null) {
            $allowedTypes = [
                FileValidator::TYPE_ALL_IMAGES,
            ];
        }
        $handler = new FileUpload($nameOnFiles, $allowedTypes);
        $valid = false;
        $relativeURL = '';

        $name = $name !== null ? $name : 'file_' . uniqid();
        $oldFile = null;

        if ($handler->hasInput()) {

            try {

                $valid = $handler->validate();

                $instance = new SystemApprovalsController;
                $uploadDirPath = $instance->uploadDir;
                $uploadDirRelativeURL = $instance->uploadDirURL;

                if ($setNameByInput && $valid) {

                    $name = $_FILES[$nameOnFiles]['name'];
                    $lastPointIndex = mb_strrpos($name, '.');

                    if ($lastPointIndex !== false) {
                        $name = mb_substr($name, 0, $lastPointIndex);
                    }

                }

                if (!is_null($currentRoute)) {
                    //Si ya existe
                    $oldFile = append_to_url(basepath(), $currentRoute);
                    $oldFile = file_exists($oldFile) ? $oldFile : null;

                    if (mb_strlen(trim($folder)) < 1) {
                        //Si folder está vacío
                        $folder = str_replace($uploadDirRelativeURL, '', $currentRoute);
                        $folder = str_replace(basename($currentRoute), '', $folder);
                        $folder = trim($folder, '/');
                    }

                }

                $uploadDirPath = append_to_path_system($uploadDirPath, $folder);
                $uploadDirRelativeURL = append_to_url($uploadDirRelativeURL, $folder);
                if (mb_strlen($suffixName) > 0) {
                    $name .= "_{$suffixName}";
                }

                if ($valid) {

                    $locations = $handler->moveTo($uploadDirPath, $name, null, false, true);

                    if (!empty($locations)) {

                        $url = $locations[0];
                        $nameCurrent = basename($url);
                        $relativeURL = trim(append_to_url($uploadDirRelativeURL, $nameCurrent), '/');

                        //Eliminar archivo anterior
                        if (!is_null($oldFile)) {

                            if (basename($oldFile) != $nameCurrent) {
                                unlink($oldFile);
                            }

                        }

                        //Se elimina cualquier otro archivo
                        foreach ($locations as $file) {
                            if ($url != $file) {
                                if (is_string($file) && file_exists($file)) {
                                    unlink($file);
                                }
                            }
                        }

                    }

                } else {
                    throw new \Exception(implode('<br>', $handler->getErrorMessages()));
                }

            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }

        }

        return $relativeURL;
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

        $simpleName = !is_null($name) ? $name : '';

        if (!is_null($name)) {
            $name = trim($name);
            $name = strlen($name) > 0 ? "-{$name}" : '';
        }

        $name = !is_null($name) ? self::$baseRouteName . $name : self::$baseRouteName;

        $allowed = false;
        $current_user = getLoggedFrameworkUser();

        if ($current_user !== null) {
            $allowed = Roles::hasPermissions($name, $current_user->type);
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

        /**
         * @var array<string>
         */
        $allRoles = array_keys(UsersModel::TYPES_USERS);

        //Permisos
        $list = $allRoles;
        $approval = [
            UsersModel::TYPE_USER_ROOT,
            UsersModel::TYPE_USER_ADMIN_GRAL,
            UsersModel::TYPE_USER_INSTITUCIONAL,
        ];
        $routes = [

            //──── GET ───────────────────────────────────────────────────────────────────────────────
            //HTML
            new Route( //Vista del listado
                "{$startRoute}/list[/]",
                $classname . ':listView',
                self::$baseRouteName . '-list',
                'GET',
                true,
                null,
                $approval
            ),
            new Route( //Formulario de crear
                "{$startRoute}/forms/approval/{id}[/]",
                $classname . ':approvalForm',
                self::$baseRouteName . '-forms-approval',
                'GET',
                true,
                null,
                $approval
            ),

            //JSON
            new Route( //Datos para datatables
                "{$startRoute}/datatables[/]",
                $classname . ':dataTables',
                self::$baseRouteName . '-datatables',
                'GET',
                true,
                null,
                $approval
            ),

            //──── POST ──────────────────────────────────────────────────────────────────────────────

            new Route( //Acción de crear
                "{$startRoute}/action/approval/{id}[/]",
                $classname . ':approvalAction',
                self::$baseRouteName . '-actions-approval',
                'POST',
                true,
                null,
                $approval
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
