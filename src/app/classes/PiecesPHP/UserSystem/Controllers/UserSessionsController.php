<?php

/**
 * UserSessionsController.php
 */

namespace PiecesPHP\UserSystem\Controllers;

use EventsLog\LogsRoutes;
use EventsLog\Mappers\LogsMapper;
use MySpace\Controllers\MySpaceController;
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\AdminPanel\DashboardCard;
use PiecesPHP\AdminPanel\DashboardCardBody;
use PiecesPHP\AdminPanel\DashboardCardRegistry;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\HavingItem;
use PiecesPHP\Core\Database\ORM\Statements\Critery\HavingItemGroup;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItemGroup;
use PiecesPHP\Core\Database\ORM\Statements\HavingSegment;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Utilities\Helpers\DataTablesHelper;
use PiecesPHP\Core\Utilities\Helpers\RowActions;
use PiecesPHP\Core\Utilities\Helpers\StatusLabel;
use PiecesPHP\Core\Validation\Validator;
use PiecesPHP\Logs\LogTab;
use PiecesPHP\Logs\LogTabRegistry;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UserSessionsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\UserDataPackage;
use PiecesPHP\UserSystem\UserHierarchy;
use PiecesPHP\UserSystem\UserSystemFeaturesLang;
use PiecesPHP\UserSystem\UserSystemFeaturesRoutes;

/**
 * UserSessionsController - Las sesiones abiertas: cerrar sesión en el servidor, cerrar una, cerrar las demás, extender, y
 * su listado (ADR 0026, fases B y C).
 *
 * Quién actúa sobre quién (P133): cada uno, sobre las suyas; root, sobre todas; el administrador general, sobre rangos
 * estrictamente menores; el de organización, sobre rangos estrictamente menores de su organización. Lo que no se puede
 * ver responde 404, igual que lo que no existe.
 *
 * @package     PiecesPHP\UserSystem\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class UserSessionsController extends AdminPanelController
{
    use ControllerRoutingTrait;

    const LANG_GROUP = UserSystemFeaturesLang::LANG_GROUP;

    /**
     * La key de su pestaña en «Registros».
     */
    const SESSIONS_TAB = 'sessions';

    /**
     * Cuántos días, como mucho, se piden al extender (el techo real es el del token de cada sesión).
     */
    const MAX_EXTEND_DAYS = 365;

    /**
     * @var string
     */
    protected static $URLDirectory = 'sessions';

    /**
     * @var string
     */
    protected static $baseRouteName = 'user-sessions';

    /**
     * Cierra la sesión de esta petición en el servidor. El navegador borra su cookie después, salga bien o mal.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function logout(Request $request, Response $response)
    {
        $current = UserSessions::current();
        $actor = getLoggedFrameworkUserOrFail();
        if ($current !== null) {
            UserSessions::close((int) $current['id'], self::realActorID($actor), UserSessions::REASON_LOGOUT);
        }
        return $response->withJson(['success' => true]);
    }

    /**
     * Cierra UNA sesión: una propia, o una de alguien a quien se puede ver.
     *
     * @param Request $request
     * @param Response $response
     * @param array<string,mixed> $args
     * @return Response
     */
    public function close(Request $request, Response $response, array $args = [])
    {
        $actor = getLoggedFrameworkUserOrFail();
        if (self::isImpersonating($actor)) {
            return self::impersonatingResponse($response);
        }
        $row = self::visibleRow($args['id'] ?? null, $actor);
        if ($row === null || !UserSessions::isOpen($row)) {
            return self::notFound($response);
        }
        $own = (int) $row['userID'] === (int) $actor->id;
        $reason = $own ? UserSessions::REASON_CLOSED_BY_USER : UserSessions::REASON_CLOSED_BY_ADMIN;
        if (!UserSessions::close((int) $row['id'], (int) $actor->id, $reason)) {
            return self::notFound($response);
        }
        self::logAction(LogsMapper::MSG_SESSION_CLOSED, [
            '%username%' => (string) $actor->username,
            '%target%' => self::username((int) $row['userID']),
            '%reason%' => $reason,
        ], (int) $row['userID']);
        $isCurrent = (string) $row['sessionHash'] === UserSessions::currentHash();
        return $response->withJson([
            'success' => true,
            'name' => __(self::LANG_GROUP, 'Cerrar sesión'),
            'message' => __(self::LANG_GROUP, 'La sesión se cerró.'),
            'values' => ['current' => $isCurrent],
        ]);
    }

    /**
     * Cierra todas las sesiones propias menos la de esta petición.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function closeOthers(Request $request, Response $response)
    {
        $actor = getLoggedFrameworkUserOrFail();
        if (self::isImpersonating($actor)) {
            return self::impersonatingResponse($response);
        }
        $closed = UserSessions::closeAllOfUser((int) $actor->id, (int) $actor->id, UserSessions::REASON_CLOSED_BY_USER, UserSessions::currentHash());
        self::logAction(LogsMapper::MSG_OTHER_SESSIONS_CLOSED, [
            '%username%' => (string) $actor->username,
            '%count%' => (string) $closed,
        ], (int) $actor->id);
        return $response->withJson([
            'success' => true,
            'name' => __(self::LANG_GROUP, 'Cerrar las demás sesiones'),
            'message' => sprintf(__(self::LANG_GROUP, 'Se cerraron %d sesión(es). Esta sigue abierta.'), $closed),
        ]);
    }

    /**
     * Extiende una sesión abierta: `days` días desde ahora, sin pasar de la caducidad de su token.
     *
     * @param Request $request
     * @param Response $response
     * @param array<string,mixed> $args
     * @return Response
     */
    public function extend(Request $request, Response $response, array $args = [])
    {
        $actor = getLoggedFrameworkUserOrFail();
        if (self::isImpersonating($actor)) {
            return self::impersonatingResponse($response);
        }
        $row = self::visibleRow($args['id'] ?? null, $actor);
        if ($row === null || !UserSessions::isOpen($row) || !self::canExtend($actor, $row)) {
            return self::notFound($response);
        }
        $body = $request->getParsedBody();
        $days = is_array($body) && isset($body['days']) && Validator::isInteger($body['days']) ? (int) $body['days'] : 0;
        if ($days < 1 || $days > self::MAX_EXTEND_DAYS) {
            return $response->withStatus(400)->withJson([
                'success' => false,
                'name' => __(self::LANG_GROUP, 'Extender sesión'),
                'message' => sprintf(__(self::LANG_GROUP, 'Indique un número de días entre 1 y %d.'), self::MAX_EXTEND_DAYS),
            ]);
        }
        $until = UserSessions::extend((int) $row['id'], $days);
        if ($until === null) {
            return $response->withStatus(400)->withJson([
                'success' => false,
                'name' => __(self::LANG_GROUP, 'Extender sesión'),
                'message' => sprintf(
                    __(self::LANG_GROUP, 'No se puede extender más allá del %s, la caducidad de su token.'),
                    date('d-m-Y H:i', UserSessions::ceiling($row))
                ),
            ]);
        }
        self::logAction(LogsMapper::MSG_SESSION_EXTENDED, [
            '%username%' => (string) $actor->username,
            '%target%' => self::username((int) $row['userID']),
            '%until%' => $until->format('d-m-Y H:i'),
        ], (int) $row['userID']);
        return $response->withJson([
            'success' => true,
            'name' => __(self::LANG_GROUP, 'Extender sesión'),
            'message' => sprintf(__(self::LANG_GROUP, 'La sesión caduca ahora el %s.'), $until->format('d-m-Y H:i')),
        ]);
    }

    /**
     * Las sesiones abiertas que quien mira puede ver: las suyas y las de rangos estrictamente menores (root, todas).
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function dataTables(Request $request, Response $response)
    {
        $actor = getLoggedFrameworkUserOrFail();
        $table = UserSessionsModel::TABLE;
        $users = UsersModel::TABLE;
        $userColumn = fn(string $column): string => "(SELECT {$users}.{$column} FROM {$users} WHERE {$users}.id = {$table}.userID)";
        $selectFields = [
            "{$table}.id",
            "{$table}.userID",
            "{$table}.sessionHash",
            "{$table}.createdAt",
            "{$table}.lastActivityAt",
            "{$table}.expiresAt",
            "{$table}.ip",
            "{$table}.userAgent",
            $userColumn('username') . ' AS username',
            $userColumn('email') . ' AS email',
            $userColumn('type') . ' AS userType',
            $userColumn('organization') . ' AS userOrganization',
            UserHierarchy::rankSQL($userColumn('type')) . ' AS targetRank',
        ];

        $where = [
            new WhereItem("{$table}.closedAt", WhereItem::IS_NULL_OPERATOR, '', WhereItem::AND_OPERATOR),
            new WhereItem("{$table}.expiresAt", WhereItem::GREATER_THAN_OPERATOR, date('Y-m-d H:i:s'), WhereItem::AND_OPERATOR),
        ];
        $from = $request->getQueryParam('from', '');
        $fromDate = is_string($from) ? \DateTime::createFromFormat('!d-m-Y', trim($from)) : false;
        if ($fromDate !== false && $fromDate->format('d-m-Y') === trim((string) $from)) {
            $where[] = new WhereItem("{$table}.createdAt", WhereItem::GREATER_OR_EQUAL_OPERATOR, $fromDate->format('Y-m-d 00:00:00'), WhereItem::AND_OPERATOR);
        }
        $whereSegment = new WhereSegment();
        $whereSegment->addGroup(new WhereItemGroup($where));

        //La visibilidad (P133), en HAVING sobre los alias.
        $havingSegment = new HavingSegment();
        $hasHaving = false;
        $visibility = UserHierarchy::visibilityGroup($actor, 'userID', 'targetRank', 'userOrganization');
        if ($visibility !== null) {
            $havingSegment->addGroup($visibility);
            $hasHaving = true;
        }
        $user = $request->getQueryParam('user', '');
        if (is_string($user) && trim($user) !== '') {
            $havingSegment->addGroup(new HavingItemGroup([
                new HavingItem('username', HavingItem::LIKE_OPERATOR, '%' . mb_substr(trim($user), 0, 100) . '%', HavingItem::AND_OPERATOR),
            ]));
            $hasHaving = true;
        }

        DataTablesHelper::setTablePrefixOnOrder(false);
        DataTablesHelper::setTablePrefixOnSearch(false);

        $currentHash = UserSessions::currentHash();
        $result = DataTablesHelper::process([
            'where_segment' => $whereSegment,
            'having_segment' => $hasHaving ? $havingSegment : null,
            'select_fields' => $selectFields,
            'columns_order' => ['username', 'userAgent', 'ip', 'createdAt', 'lastActivityAt', 'expiresAt', "{$table}.id"],
            'custom_order' => ['lastActivityAt' => 'DESC'],
            'mapper' => new UserSessionsModel(),
            'request' => $request,
            'on_set_data' => function ($e) use ($actor, $currentHash): array {
                $row = (array) $e;
                $own = (int) $row['userID'] === (int) $actor->id;
                $marks = '';
                if ((string) $row['sessionHash'] === $currentHash) {
                    $marks = ' ' . StatusLabel::html(__(self::LANG_GROUP, 'Esta sesión'), StatusLabel::POSITIVE);
                } elseif ($own) {
                    $marks = ' ' . StatusLabel::neutral(__(self::LANG_GROUP, 'Tuya'));
                }
                return [
                    '<div class="ui tight flex">' . escape_html((string) ($row['username'] ?? '')) . $marks . '</div><small class="ui muted text">' . escape_html((string) ($row['email'] ?? '')) . '</small>',
                    escape_html(UserSessions::describeUserAgent(isset($row['userAgent']) ? (string) $row['userAgent'] : null)),
                    escape_html((string) ($row['ip'] ?? '')),
                    escape_html(self::date((string) $row['createdAt'])),
                    escape_html(self::date((string) $row['lastActivityAt'])),
                    escape_html(self::date((string) $row['expiresAt'])),
                    self::actionsCell($actor, $row),
                ];
            },
        ]);

        return $response->withJson($result->getValues());
    }

    /**
     * La pestaña «Sesiones abiertas» de «Registros».
     *
     * @param Request $request
     * @param string $tabURL
     * @return string
     */
    public static function sessionsTab(Request $request, string $tabURL): string
    {
        add_global_asset(UserSystemFeaturesRoutes::staticRoute('js/sessions.js'), 'js');
        return (string) (new HelperController())->setInstanceViewDir(__DIR__ . '/../Views/')->render('panel/pages/sessions/tab', [
            'langGroup' => self::LANG_GROUP,
            'processTableLink' => self::routeName('datatables'),
            'pieces' => new HelperController(),
        ], false);
    }

    /**
     * La lista de las sesiones abiertas de un usuario, para «Mis sesiones» y para su ficha. Solo lo que quien mira puede
     * ver; vacía si no puede ver ninguna.
     *
     * @param int $userID
     * @param bool $mine «Mis sesiones»: con «Cerrar las demás sesiones»
     * @return string
     */
    public static function userSessionsHTML(int $userID, bool $mine): string
    {
        $actor = getLoggedFrameworkUser();
        if ($actor === null) {
            return '';
        }
        $rows = [];
        foreach (UserSessions::openOfUser($userID) as $row) {
            if (self::canSee($actor, $row)) {
                $rows[] = $row;
            }
        }
        add_global_asset(UserSystemFeaturesRoutes::staticRoute('js/sessions.js'), 'js');
        return (string) (new HelperController())->setInstanceViewDir(__DIR__ . '/../Views/')->render('panel/pages/sessions/user-list', [
            'langGroup' => self::LANG_GROUP,
            'rows' => array_map(fn(array $row): array => [
                'browser' => UserSessions::describeUserAgent(isset($row['userAgent']) ? (string) $row['userAgent'] : null),
                'ip' => (string) ($row['ip'] ?? ''),
                'createdAt' => self::date((string) $row['createdAt']),
                'lastActivityAt' => self::date((string) $row['lastActivityAt']),
                'expiresAt' => self::date((string) $row['expiresAt']),
                'current' => (string) $row['sessionHash'] === UserSessions::currentHash(),
                'actions' => self::actionsCell($actor, $row),
            ], $rows),
            'mine' => $mine,
            'closeOthersURL' => $mine ? self::routeName('close-others') : '',
        ], false);
    }

    /**
     * La tarjeta «Mis sesiones» del inicio: cuántas tiene abiertas el usuario y el enlace a Mis sesiones.
     *
     * @return void
     */
    public static function registerDashboardCard(): void
    {
        DashboardCardRegistry::register(new DashboardCard(
            'my-sessions',
            __(self::LANG_GROUP, 'Mis sesiones'),
            'desktop',
            //El permiso es el de Mis sesiones: sin ese módulo, la ruta no existe y la tarjeta no se ve.
            'my-space-admin-user-security',
            fn(UserDataPackage $user): DashboardCardBody => DashboardCardBody::numbers(
                [
                    ['value' => count(UserSessions::openOfUser((int) $user->id)), 'concept' => __(self::LANG_GROUP, 'sesiones abiertas'), 'icon' => 'desktop'],
                ],
                __(self::LANG_GROUP, 'Ver mis sesiones'),
                MySpaceController::routeName('user-security')
            ),
            10,
            help: __(self::LANG_GROUP, 'Las sesiones abiertas con tu usuario en este y en otros dispositivos.')
        ));
    }

    /**
     * @return void
     */
    public static function registerLogTab(): void
    {
        LogTabRegistry::register(new LogTab(
            self::SESSIONS_TAB,
            __(self::LANG_GROUP, 'Sesiones abiertas'),
            'desktop',
            self::$baseRouteName . '-datatables',
            fn(Request $request, string $tabURL): string => self::sessionsTab($request, $tabURL),
            40
        ));
    }

    /**
     * Las acciones de una fila: cerrar siempre (es visible); extender si el rol puede.
     *
     * @param UserDataPackage $actor
     * @param array<string,mixed> $row
     * @return string
     */
    protected static function actionsCell(UserDataPackage $actor, array $row): string
    {
        $id = (int) $row['id'];
        $close = RowActions::action(__(self::LANG_GROUP, 'Cerrar'), '#', 'sign out alternate', [
            'data-session-close' => self::routeName('close', ['id' => $id]),
            'data-confirmation-message' => __(self::LANG_GROUP, '¿Cerrar esta sesión?'),
        ]);
        $menu = [];
        if (self::canExtend($actor, $row)) {
            $menu[] = RowActions::action(__(self::LANG_GROUP, 'Extender'), '#', 'clock outline', [
                'data-session-extend' => self::routeName('extend', ['id' => $id]),
                'data-session-ceiling' => date('d-m-Y H:i', UserSessions::ceiling($row)),
            ]);
        }
        return RowActions::cell($close, $menu);
    }

    /**
     * @param UserDataPackage $actor
     * @param array<string,mixed> $row
     * @return bool
     */
    protected static function canSee(UserDataPackage $actor, array $row): bool
    {
        if ((int) $row['userID'] === (int) $actor->id) {
            return true;
        }
        $target = self::targetOf((int) $row['userID']);
        $actorOrganization = $actor->organization ?? null;
        return $target !== null && UserSessions::canActOn(
            (int) $actor->type,
            is_numeric($actorOrganization) ? (int) $actorOrganization : null,
            $target['type'],
            $target['organization']
        );
    }

    /**
     * @param UserDataPackage $actor
     * @param array<string,mixed> $row
     * @return bool
     */
    protected static function canExtend(UserDataPackage $actor, array $row): bool
    {
        $actorType = (int) $actor->type;
        if ((int) $row['userID'] === (int) $actor->id) {
            return in_array($actorType, [UsersModel::TYPE_USER_ROOT, UsersModel::TYPE_USER_ADMIN_GRAL], true);
        }
        $target = self::targetOf((int) $row['userID']);
        $actorOrganization = $actor->organization ?? null;
        return $target !== null && UserSessions::canExtendOn(
            $actorType,
            is_numeric($actorOrganization) ? (int) $actorOrganization : null,
            $target['type'],
            $target['organization']
        );
    }

    /**
     * La fila de ese id si quien mira puede verla; null si no existe o no puede (que responden igual).
     *
     * @param mixed $id
     * @param UserDataPackage $actor
     * @return array<string,mixed>|null
     */
    protected static function visibleRow($id, UserDataPackage $actor): ?array
    {
        if (!is_string($id) || preg_match('/^[1-9][0-9]{0,18}$/', $id) !== 1) {
            return null;
        }
        $row = UserSessions::byID((int) $id);
        return $row !== null && self::canSee($actor, $row) ? $row : null;
    }

    /**
     * @param int $userID
     * @return array{type:int,organization:int|null,username:string}|null
     */
    protected static function targetOf(int $userID): ?array
    {
        $database = (new BaseModel())->getDatabase();
        if ($database === null) {
            return null;
        }
        $statement = $database->prepare('SELECT type, organization, username FROM `' . UsersModel::TABLE . '` WHERE id = ? LIMIT 1');
        $statement->execute([$userID]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }
        return [
            'type' => (int) $row['type'],
            'organization' => $row['organization'] !== null ? (int) $row['organization'] : null,
            'username' => (string) $row['username'],
        ];
    }

    /**
     * @param int $userID
     * @return string
     */
    protected static function username(int $userID): string
    {
        return self::targetOf($userID)['username'] ?? (string) $userID;
    }

    /**
     * Root actuando como otro: las sesiones no se tocan (cerrarían las del suplantado con el rol de otro).
     *
     * @param UserDataPackage $actor
     * @return bool
     */
    protected static function isImpersonating(UserDataPackage $actor): bool
    {
        $rootOriginalID = get_config(ROOT_ORIGINAL_ID_CONFIG_NAME);
        return Validator::isInteger($rootOriginalID) && (int) $rootOriginalID !== (int) $actor->id;
    }

    /**
     * Quien actúa de verdad: con suplantación, el root original.
     *
     * @param UserDataPackage $actor
     * @return int
     */
    protected static function realActorID(UserDataPackage $actor): int
    {
        $rootOriginalID = get_config(ROOT_ORIGINAL_ID_CONFIG_NAME);
        return Validator::isInteger($rootOriginalID) ? (int) $rootOriginalID : (int) $actor->id;
    }

    /**
     * @param Response $response
     * @return Response
     */
    protected static function impersonatingResponse(Response $response): Response
    {
        return $response->withStatus(403)->withJson([
            'success' => false,
            'name' => __(self::LANG_GROUP, 'Sesiones'),
            'message' => __(self::LANG_GROUP, 'No se pueden gestionar sesiones mientras se actúa como otro usuario.'),
        ]);
    }

    /**
     * @param Response $response
     * @return Response
     */
    protected static function notFound(Response $response): Response
    {
        return $response->withStatus(404)->withJson([
            'success' => false,
            'name' => __(self::LANG_GROUP, 'Sesiones'),
            'message' => __(self::LANG_GROUP, 'No existe esa sesión.'),
        ]);
    }

    /**
     * Sin la IP: en el registro de acciones no va (#1313).
     *
     * @param string $type
     * @param array<string,string> $variables
     * @param int $targetUserID
     * @return void
     */
    protected static function logAction(string $type, array $variables, int $targetUserID): void
    {
        if (LogsRoutes::ENABLE) {
            LogsMapper::addLog($type, $variables, 'id', (string) $targetUserID, UsersModel::TABLE);
        }
    }

    /**
     * @param string $date
     * @return string
     */
    protected static function date(string $date): string
    {
        $time = strtotime($date);
        return $time !== false ? date('d-m-Y H:i', $time) : '';
    }

    /**
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {
        $startRoute = (last_char($group->getGroupSegment()) == '/' ? '' : '/') . self::$URLDirectory;
        $allRoles = array_keys(UsersModel::TYPES_USERS);
        $listing = [UsersModel::TYPE_USER_ROOT, UsersModel::TYPE_USER_ADMIN_GRAL, UsersModel::TYPE_USER_ADMIN_ORG];

        $group->register([
            //Cada método se comprueba a sí mismo: de quién es la sesión y si quien actúa puede tocarla.
            new Route("{$startRoute}/logout[/]", self::class . ':logout', self::$baseRouteName . '-logout', 'POST', true, null, $allRoles),
            new Route("{$startRoute}/close/{id}[/]", self::class . ':close', self::$baseRouteName . '-close', 'POST', true, null, $allRoles),
            new Route("{$startRoute}/close-others[/]", self::class . ':closeOthers', self::$baseRouteName . '-close-others', 'POST', true, null, $allRoles),
            new Route("{$startRoute}/extend/{id}[/]", self::class . ':extend', self::$baseRouteName . '-extend', 'POST', true, null, [UsersModel::TYPE_USER_ROOT, UsersModel::TYPE_USER_ADMIN_GRAL]),
            new Route("{$startRoute}/datatables[/]", self::class . ':dataTables', self::$baseRouteName . '-datatables', 'GET', true, null, $listing),
        ]);

        return $group;
    }
}
