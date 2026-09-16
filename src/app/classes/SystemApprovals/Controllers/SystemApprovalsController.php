<?php

/**
 * SystemApprovalsController.php
 */

namespace SystemApprovals\Controllers;

use App\Controller\AdminPanelController;
use App\Model\AvatarModel;
use App\Model\UsersModel;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\ConfigHelpers\MailConfig;
use PiecesPHP\Core\Database\ORM\Statements\Critery\HavingItem;
use PiecesPHP\Core\Database\ORM\Statements\Critery\HavingItemGroup;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\HavingSegment;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\Mailer;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Routing\Slim3Compatibility\Exception\NotFoundException;
use PiecesPHP\Core\Utilities\Helpers\DataTablesHelper;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use PiecesPHP\Core\Validation\Parameters\Exceptions\InvalidParameterValueException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\MissingRequiredParameterException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\ParsedValueException;
use PiecesPHP\Core\Validation\Parameters\Parameter;
use PiecesPHP\Core\Validation\Parameters\Parameters;
use PiecesPHP\Core\Validation\Validator;
use PiecesPHP\RoutingUtils\DefaultAccessControlModules;
use PiecesPHP\UserSystem\UserDataPackage;
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

    use ControllerRoutingTrait;

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
     * @var HelperController
     */
    protected $helpController = null;

    const BASE_VIEW_DIR = '';
    const BASE_JS_DIR = 'js';
    const BASE_CSS_DIR = 'css';
    const LANG_GROUP = SystemApprovalsLang::LANG_GROUP;

    const RESPONSE_SOURCE_STATIC_CACHE = 'STATIC_CACHE';
    const RESPONSE_SOURCE_NORMAL_RESULT = 'NORMAL_RESULT';
    const ENABLE_CACHE = true;

    public function __construct()
    {
        parent::__construct();

        $this->model = (new SystemApprovalsMapper())->getModel();

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
        //Sin registro, 404 aquí: más abajo getMapperInstance() recibiría null y daría 500.
        if ($approvalMapper->id === null) {
            throw new NotFoundException($request, $response);
        }
        $referenceMapper = SystemApprovalManager::getInstance()->getMapperInstance($approvalMapper->referenceTable, $approvalMapper->referenceValue);
        $approvalHandler = SystemApprovalManager::getInstance()->getHandler($approvalMapper->referenceTable);
        $currentUser = getLoggedFrameworkUserOrFail();
        $currentUserID = $currentUser->id;
        $currenUserType = $currentUser->type;

        $approvalMapperExists = $approvalMapper->id !== null;
        $hasApprovalHandler = $approvalHandler !== null;
        $hasReferenceMapper = $referenceMapper !== null && ($referenceMapper->id ?? null) !== null;
        $contactUser = $approvalHandler::getContactUser($referenceMapper);
        $isSameUser = $contactUser !== null && $contactUser->id == $currentUserID && $currenUserType != UsersModel::TYPE_USER_ROOT;

        if ($approvalMapperExists && $hasApprovalHandler && $hasReferenceMapper && !$isSameUser && self::canManage($approvalMapper, $currentUser)) {

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

        //El alcance, ANTES del try: fuera de él, 404 como el formulario, sin escribir ni enviar correo.
        $approvalID = $request->getAttribute('id', null);
        $approvalElement = new SystemApprovalsMapper(Validator::isInteger($approvalID) ? (int) $approvalID : -1);
        if ($approvalElement->id !== null && !self::canManage($approvalElement, getLoggedFrameworkUserOrFail())) {
            throw new NotFoundException($request, $response);
        }

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

                    $currentUser = getLoggedFrameworkUserOrFail();
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
                        //Solo si el estado cambia: un POST repetido no repite el correo.
                        if ($contactUser !== null && $previousStatus != $approvalStatus) {
                            $message = '';
                            $contentName = __(self::LANG_GROUP, $mapper->referenceAlias);
                            if ($mapper->status == SystemApprovalsMapper::STATUS_APPROVED) {
                                $message = strReplaceTemplate(__(self::LANG_GROUP, "Sr(a). {NAME}, le informamos que su contenido \"{CONTENT_NAME}\" ha sido aprobado"), [
                                    '{NAME}' => htmlspecialchars((string) $contactUser->getFullName(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                                    '{CONTENT_NAME}' => $contentName,
                                ]);
                            } elseif ($mapper->status == SystemApprovalsMapper::STATUS_REJECTED) {
                                $message = strReplaceTemplate(__(self::LANG_GROUP, "Sr(a). {NAME}, le informamos que su contenido \"{CONTENT_NAME}\" ha sido rechazado"), [
                                    '{NAME}' => htmlspecialchars((string) $contactUser->getFullName(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
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
                            $data['reason'] = htmlspecialchars(mb_convert_encoding((string) $reason, 'UTF-8'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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

        } catch (MissingRequiredParameterException | InvalidParameterValueException | \Exception $e) {

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
        //LISTA BLANCA REAL, y sale del CONTRATO de los handlers, no de la base: cada uno declara
        //en `getContentTypes()` todos los textos que puede escribir. Ver T162.
        $referenceAliasFilter = $request->getQueryParam('referenceAlias', null);
        $referenceAliasFilter = is_string($referenceAliasFilter) && mb_strlen(trim($referenceAliasFilter)) > 0 ? trim($referenceAliasFilter) : null;
        $tiposConocidos = SystemApprovalManager::getInstance()->getContentTypes();
        $referenceAliasFilter = in_array($referenceAliasFilter, $tiposConocidos, true) ? $referenceAliasFilter : null;
        //SE VALIDABA COMO CADENA Y SE USABA COMO NÚMERO en `elapsedDays >= {$…}`, sin comillas.
        //Son dos defectos: el de tipo y el de SQL. `isInteger` cierra los dos. Ver T155.
        $elapsedDaysFilter = $request->getQueryParam('elapsedDays', null);
        $elapsedDaysFilter = Validator::isInteger($elapsedDaysFilter) ? (int) $elapsedDaysFilter : null;

        $currentUser = getLoggedFrameworkUserOrFail();
        $currentUserID = $currentUser->id;
        $currentUserType = $currentUser->type;
        $currentOrganizationID = $currentUser->organization;
        $table = SystemApprovalsMapper::TABLE;
        $tableUsers = UsersModel::TABLE;
        $pending = SystemApprovalsMapper::STATUS_PENDING;
        $approved = SystemApprovalsMapper::STATUS_APPROVED;
        $baseOrgID = OrganizationMapper::INITIAL_ID_GLOBAL;
        //POR MARCADOR, con `where_segment`. La lista blanca de arriba cierra el DOMINIO y el
        //marcador cierra el MECANISMO: aquí hacen falta las dos.
        $whereItems = [
            new WhereItem("{$table}.status", WhereItem::EQUAL_OPERATOR, $pending, WhereItem::AND_OPERATOR),
        ];
        //Un grupo por criterio, todos por marcador: HAVING (C1) AND (C2) AND …
        $havingSegment = new HavingSegment();
        //AGRUPADO a propósito: sin paréntesis, el AND de los demás criterios se pegaba solo a `= 1`
        //y un NULL se los saltaba todos. El IS NULL se queda por si el alias llega a poder serlo.
        $havingSegment->addGroup(new HavingItemGroup([
            new HavingItem('referenceIsActive', HavingItem::IS_NULL_OPERATOR, '', HavingItem::OR_OPERATOR),
            new HavingItem('referenceIsActive', HavingItem::EQUAL_OPERATOR, 1, HavingItem::AND_OPERATOR),
        ]));
        //Verifica que exista la referencia
        $havingSegment->addGroup(new HavingItemGroup([
            new HavingItem('referenceCreatedBy', HavingItem::IS_NOT_NULL_OPERATOR, '', HavingItem::AND_OPERATOR),
        ]));
        //Oculta lo propio salvo a CAN_APPROVAL_SELF. El (int) hace falta: la comparación es estricta.
        if (!in_array((int) $currentUserType, SystemApprovalsMapper::CAN_APPROVAL_SELF, true)) {
            $havingSegment->addGroup(new HavingItemGroup([
                new HavingItem('referenceCreatedBy', HavingItem::NOT_EQUAL_OPERATOR, $currentUserID, HavingItem::AND_OPERATOR),
            ]));
        }
        //Oculta los perfiles que sean de organizaciones ya aprobadas
        $havingSegment->addGroup(new HavingItemGroup([
            new HavingItem("{$table}.referenceTable", HavingItem::NOT_EQUAL_OPERATOR, $tableUsers, HavingItem::OR_OPERATOR),
            new HavingItem('referenceOrganization', HavingItem::IS_NULL_OPERATOR, '', HavingItem::OR_OPERATOR),
            new HavingItem('referenceOrganization', HavingItem::EQUAL_OPERATOR, $baseOrgID, HavingItem::OR_OPERATOR),
            new HavingItem('referenceOrtanizationApprovalValue', HavingItem::NOT_EQUAL_OPERATOR, $approved, HavingItem::AND_OPERATOR),
        ]));

        //Verificar permisos sobre organization
        if ($currentUser !== null) {
            $currentOrganizationID ??= -1;
            $canModifyOrganizations = OrganizationMapper::canModifyAnyOrganization($currentUserType);
            $canApprovalAll = in_array($currentUserType, SystemApprovalsMapper::CAN_APPROVAL_ALL);
            if (!$canModifyOrganizations && !$canApprovalAll) {
                $havingSegment->addGroup(new HavingItemGroup([
                    new HavingItem('referenceOrganization', HavingItem::EQUAL_OPERATOR, $currentOrganizationID, HavingItem::AND_OPERATOR),
                    new HavingItem('referenceOrganizationAdministrator', HavingItem::EQUAL_OPERATOR, $currentUser->id, HavingItem::AND_OPERATOR),
                ]));
            }
        }

        if ($referenceAliasFilter !== null) {
            $whereItems[] = new WhereItem("{$table}.referenceAlias", WhereItem::EQUAL_OPERATOR, $referenceAliasFilter, WhereItem::AND_OPERATOR);
        }

        if ($elapsedDaysFilter !== null && $elapsedDaysFilter != '-1') {
            $havingSegment->addGroup(new HavingItemGroup([
                new HavingItem('elapsedDays', HavingItem::GREATER_OR_EQUAL_OPERATOR, $elapsedDaysFilter, HavingItem::AND_OPERATOR),
            ]));
        }

        $whereSegment = new WhereSegment($whereItems);

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

            'where_segment' => $whereSegment,
            'having_segment' => $havingSegment,
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
                $avatar ??= baseurl('statics/images/default-avatar.png');
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
     * Si el usuario puede ver y resolver el elemento: C3 y C5 del listado, aplicados en PHP; a los limitados por C5,
     * además lo pendiente, C1 y C4.
     *
     * Las rutas dejan entrar a más tipos de los que pueden aprobarlo todo: el alcance lo pone esto.
     *
     * @param SystemApprovalsMapper $element Cargado por id, con los campos de fieldsToSelect()
     * @param UserDataPackage $user
     * @return bool
     */
    public static function canManage(SystemApprovalsMapper $element, UserDataPackage $user): bool
    {
        $record = $element->getExtendedElement();
        if ($record === null) {
            return false;
        }
        $userType = (int) $user->type;
        //C3: lo propio, solo CAN_APPROVAL_SELF.
        if (!in_array($userType, SystemApprovalsMapper::CAN_APPROVAL_SELF, true) && (string) $record->referenceCreatedBy === (string) $user->id) {
            return false;
        }
        //C5: sin permiso global, la organización del creador es la suya y él es su administrador.
        $canModifyOrganizations = OrganizationMapper::canModifyAnyOrganization($userType);
        $canApprovalAll = in_array($userType, SystemApprovalsMapper::CAN_APPROVAL_ALL);
        if (!$canModifyOrganizations && !$canApprovalAll) {
            //Y lo que el listado exige a todos: pendiente, C1 (referencia activa) y C4 (perfiles de organizaciones sin aprobar).
            //Los que lo aprueban todo quedan fuera a propósito: pueden volver a resolver.
            $isPending = $record->status == SystemApprovalsMapper::STATUS_PENDING;
            $isActive = $record->referenceIsActive === null || (int) $record->referenceIsActive === 1;
            $passesC4 = $record->referenceTable != UsersModel::TABLE
                || $record->referenceOrganization === null
                || (string) $record->referenceOrganization === (string) OrganizationMapper::INITIAL_ID_GLOBAL
                || ($record->referenceOrtanizationApprovalValue !== null && $record->referenceOrtanizationApprovalValue != SystemApprovalsMapper::STATUS_APPROVED);
            if (!$isPending || !$isActive || !$passesC4) {
                return false;
            }
            //En dos pasos: `??` sobre la propiedad mágica pregunta a __isset, que UserDataPackage no tiene.
            $organizationID = $user->organization;
            $organizationID ??= -1;
            return (string) $record->referenceOrganization === (string) $organizationID
                && (string) $record->referenceOrganizationAdministrator === (string) $user->id;
        }
        return true;
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
     * Verificar si una ruta es permitida y determinar pasos para permitirla o no
     *
     * @param string $name
     * @param string $route
     * @param array $params
     * @return bool
     */
    protected static function _allowedRoute(string $name, string $route, array $params = [])
    {

        $getParam = function ($paramName) use ($params) {
            $_POST = isset($_POST) && is_array($_POST) ? $_POST : [];
            $_GET = isset($_GET) && is_array($_GET) ? $_GET : [];
            $paramValue = $params[$paramName] ?? null;
            $paramValue ??= $_GET[$paramName] ?? null;
            $paramValue ??= $_POST[$paramName] ?? null;
            return $paramValue;
        };

        $allow = $route !== '';

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
        //El administrador de organización entra; su alcance lo pone canManage(), no la ruta.
        $approval = [
            UsersModel::TYPE_USER_ROOT,
            UsersModel::TYPE_USER_ADMIN_GRAL,
            UsersModel::TYPE_USER_INSTITUCIONAL,
            UsersModel::TYPE_USER_ADMIN_ORG,
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
