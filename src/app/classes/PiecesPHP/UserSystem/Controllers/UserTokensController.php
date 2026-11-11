<?php

/**
 * UserTokensController.php
 */

namespace PiecesPHP\UserSystem\Controllers;

use EventsLog\LogsRoutes;
use EventsLog\Mappers\LogsMapper;
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Core\BaseModel;
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
use PiecesPHP\Logs\LogTab;
use PiecesPHP\Logs\LogTabRegistry;
use PiecesPHP\Tokens\ORM\TokenModel;
use PiecesPHP\UserSystem\Authentication\Impersonation;
use PiecesPHP\UserSystem\Authentication\TokenEvents;
use PiecesPHP\UserSystem\ORM\OTPSecretsUsersMapper;
use PiecesPHP\UserSystem\ORM\RecoveryPasswordModel;
use PiecesPHP\UserSystem\ORM\UserProblemsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\UserDataPackage;
use PiecesPHP\UserSystem\UserHierarchy;
use PiecesPHP\UserSystem\UserSystemFeaturesLang;
use PiecesPHP\UserSystem\UserSystemFeaturesRoutes;

/**
 * UserTokensController - Registros › Tokens y códigos: los códigos vigentes y su historia.
 *
 * Ver: los mismos roles que las sesiones, con la jerarquía de P133 (UserHierarchy). Tocar (ver el valor, invalidar,
 * restablecer el doble factor): solo root, nunca suplantando, y cada acción deja su evento. El secreto TOTP no se
 * enseña nunca, ni el JWT de un token ni su dato.
 *
 * @package     PiecesPHP\UserSystem\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class UserTokensController extends AdminPanelController
{
    use ControllerRoutingTrait;

    const LANG_GROUP = UserSystemFeaturesLang::LANG_GROUP;

    /**
     * La key de su pestaña en «Registros».
     */
    const TOKENS_TAB = 'tokens';

    /**
     * Los tipos del registro de acciones que forman la historia de la pestaña.
     */
    const HISTORY_TYPES = [
        LogsMapper::MSG_TOKEN_ISSUED,
        LogsMapper::MSG_TOKEN_USED,
        LogsMapper::MSG_TOKEN_REJECTED,
        LogsMapper::MSG_TOKEN_VALUE_VIEWED,
        LogsMapper::MSG_TOKEN_INVALIDATED,
        LogsMapper::MSG_TWO_FACTOR_RESET,
    ];

    /**
     * @var string
     */
    protected static $URLDirectory = 'tokens';

    /**
     * @var string
     */
    protected static $baseRouteName = 'user-tokens';

    /**
     * Los códigos y tokens vigentes que quien mira puede ver.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function dataTables(Request $request, Response $response)
    {
        $actor = getLoggedFrameworkUserOrFail();
        $alias = 'tokensQuery';
        $isRoot = (int) $actor->type === UsersModel::TYPE_USER_ROOT;

        DataTablesHelper::setTablePrefixOnOrder(false);
        DataTablesHelper::setTablePrefixOnSearch(false);

        $result = DataTablesHelper::processFromQuery([
            'tableName' => $alias,
            'fakeTable' => self::liveSQL(),
            'having_string' => UserHierarchy::visibilitySQL($actor, 'userID', 'targetRank', 'userOrganization'),
            'select_fields' => [
                'kind',
                'rowID',
                'userID',
                'username',
                'email',
                'userOrganization',
                'createdAt',
                'expiresAt',
                'tokenType',
                'token',
                UserHierarchy::rankSQL("{$alias}.userType") . ' AS targetRank',
            ],
            'columns_order' => ['kind', 'username', 'createdAt', 'expiresAt', 'rowID'],
            'custom_order' => ['expiresAt' => 'ASC'],
            'request' => $request,
            'on_set_data' => function ($e) use ($actor, $isRoot): array {
                $row = (array) $e;
                $kind = (string) $row['kind'];
                $createdAt = $row['createdAt'] !== null ? self::date((string) $row['createdAt']) : '';
                $expiresAt = $row['expiresAt'] !== null ? self::date((string) $row['expiresAt']) : '';
                $type = TokenEvents::label($kind);
                if ($kind === TokenEvents::KIND_TOKEN) {
                    //Del JWT solo su carga en claro (iat, exp); el dato va sellado y no se toca.
                    $claims = self::jwtClaims((string) ($row['token'] ?? ''));
                    $createdAt = isset($claims['iat']) ? date('d-m-Y H:i', $claims['iat']) : '';
                    $expiresAt = isset($claims['exp']) ? date('d-m-Y H:i', $claims['exp']) : '';
                    $type .= ' · ' . (string) ($row['tokenType'] ?? '');
                }
                $who = (string) ($row['username'] ?? '');
                if ($who === '') {
                    $who = (string) ($row['email'] ?? '');
                }
                return [
                    escape_html($type),
                    escape_html($who !== '' ? $who : '—'),
                    escape_html($createdAt !== '' ? $createdAt : '—'),
                    escape_html($expiresAt !== '' ? $expiresAt : '—'),
                    $isRoot ? self::actionsCell($actor, $kind, (int) $row['rowID'], $row['userID'] !== null ? (int) $row['userID'] : null) : '',
                ];
            },
        ]);

        return $response->withJson($result->getValues());
    }

    /**
     * La historia: los eventos de tokens del registro de acciones, de los usuarios que quien mira puede ver.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function history(Request $request, Response $response)
    {
        $actor = getLoggedFrameworkUserOrFail();
        $table = LogsMapper::TABLE;
        $users = UsersModel::TABLE;
        $source = UsersModel::TABLE;
        //El usuario del código es la referencia del evento; sin referencia (un código que no existía), nadie salvo root.
        $target = fn(string $column): string => "(SELECT {$users}.{$column} FROM {$users} WHERE {$table}.referenceSource = '{$source}' AND {$users}.id = {$table}.referenceValue)";
        $selectFields = array_merge(LogsMapper::fieldsToSelect(), [
            $target('id') . ' AS targetID',
            $target('username') . ' AS targetUser',
            $target('organization') . ' AS targetOrganization',
            UserHierarchy::rankSQL($target('type')) . ' AS targetRank',
        ]);

        $texts = [];
        $last = count(self::HISTORY_TYPES) - 1;
        foreach (self::HISTORY_TYPES as $index => $type) {
            $texts[] = WhereItem::isEqual("{$table}.textMessage", LogsMapper::MESSAGES[$type], $index < $last ? WhereItem::OR_OPERATOR : WhereItem::AND_OPERATOR);
        }
        $whereSegment = new WhereSegment();
        $whereSegment->addGroup(new WhereItemGroup($texts));

        $havingSegment = null;
        $visibility = UserHierarchy::visibilityGroup($actor, 'targetID', 'targetRank', 'targetOrganization');
        if ($visibility !== null) {
            $havingSegment = new HavingSegment();
            $havingSegment->addGroup($visibility);
        }

        DataTablesHelper::setTablePrefixOnOrder(false);
        DataTablesHelper::setTablePrefixOnSearch(false);

        $result = DataTablesHelper::process([
            'where_segment' => $whereSegment,
            'having_segment' => $havingSegment,
            'select_fields' => $selectFields,
            'columns_order' => ["{$table}.createdAt", 'textMessageReplacement', 'targetUser', 'createdByUser'],
            'custom_order' => ["{$table}.id" => 'DESC'],
            'mapper' => new LogsMapper(),
            'request' => $request,
            'on_set_data' => function ($e): array {
                //Como Registros › Todos: el texto con sus variables, escapado, y quién actuaba de verdad.
                return [
                    escape_html(self::date((string) $e->createdAt)),
                    escape_html((string) $e->textMessageReplacement),
                    escape_html(isset($e->targetUser) && is_string($e->targetUser) ? $e->targetUser : '—'),
                    escape_html(LogsMapper::actorLabel(
                        isset($e->actorKind) && is_string($e->actorKind) ? $e->actorKind : null,
                        isset($e->actorUser) && is_string($e->actorUser) ? $e->actorUser : null,
                        isset($e->createdByUser) && is_string($e->createdByUser) ? $e->createdByUser : null
                    )),
                ];
            },
        ]);

        return $response->withJson($result->getValues());
    }

    /**
     * Devuelve el valor de un código de un solo uso, de recuperación o de usuario olvidado o bloqueado, y lo anota.
     *
     * @param Request $request
     * @param Response $response
     * @param array<string,mixed> $args
     * @return Response
     */
    public function reveal(Request $request, Response $response, array $args = [])
    {
        $denied = self::denied($response);
        if ($denied !== null) {
            return $denied;
        }
        $kind = (string) ($args['kind'] ?? '');
        $row = in_array($kind, TokenEvents::REVEALABLE, true) ? self::liveRow($kind, $args['id'] ?? null) : null;
        if ($row === null || $row['value'] === '') {
            return self::notFound($response);
        }
        $actor = getLoggedFrameworkUserOrFail();
        TokenEvents::log(LogsMapper::MSG_TOKEN_VALUE_VIEWED, $kind, $row['userID'], $row['target'], ['%username%' => (string) $actor->username]);
        return $response->withJson([
            'success' => true,
            'name' => TokenEvents::label($kind),
            'message' => sprintf(__(self::LANG_GROUP, 'Este código permite actuar como %s mientras no caduque.'), $row['target']),
            'values' => ['value' => $row['value']],
        ]);
    }

    /**
     * Deja un código sin valor YA: caduca el de un solo uso y borra los demás.
     *
     * @param Request $request
     * @param Response $response
     * @param array<string,mixed> $args
     * @return Response
     */
    public function invalidate(Request $request, Response $response, array $args = [])
    {
        $denied = self::denied($response);
        if ($denied !== null) {
            return $denied;
        }
        $kind = (string) ($args['kind'] ?? '');
        $invalidable = [TokenEvents::KIND_ONE_USE_CODE, TokenEvents::KIND_RECOVERY, TokenEvents::KIND_FORGOTTEN_USER, TokenEvents::KIND_BLOCKED_USER, TokenEvents::KIND_TOKEN];
        $row = in_array($kind, $invalidable, true) ? self::liveRow($kind, $args['id'] ?? null) : null;
        $database = (new BaseModel())->getDatabase();
        if ($row === null || $database === null) {
            return self::notFound($response);
        }
        $statement = match ($kind) {
            TokenEvents::KIND_ONE_USE_CODE => $database->prepare('UPDATE `' . OTPSecretsUsersMapper::TABLE . "` SET oneUseCode = '', maxDate = '2000-01-01 00:00:00' WHERE id = ?"),
            TokenEvents::KIND_RECOVERY => $database->prepare('DELETE FROM `' . (new RecoveryPasswordModel())->getModel()->getTable() . '` WHERE id = ?'),
            TokenEvents::KIND_TOKEN => $database->prepare('DELETE FROM `' . (new TokenModel())->getTable() . '` WHERE id = ?'),
            default => $database->prepare('DELETE FROM `' . (new UserProblemsModel())->getModel()->getTable() . '` WHERE id = ?'),
        };
        //RETORNO-IGNORADO: PDO va en ERRMODE_EXCEPTION: o hace su trabajo o lanza.
        $statement->execute([$row['id']]);
        $actor = getLoggedFrameworkUserOrFail();
        TokenEvents::log(LogsMapper::MSG_TOKEN_INVALIDATED, $kind, $row['userID'], $row['target'], ['%username%' => (string) $actor->username]);
        return $response->withJson([
            'success' => true,
            'name' => TokenEvents::label($kind),
            'message' => __(self::LANG_GROUP, 'El código ya no sirve.'),
        ]);
    }

    /**
     * Apaga el doble factor de otro usuario (quien perdió su aplicación). Sobre uno mismo, no: para eso está Mi espacio.
     *
     * @param Request $request
     * @param Response $response
     * @param array<string,mixed> $args
     * @return Response
     */
    public function resetTwoFactor(Request $request, Response $response, array $args = [])
    {
        $denied = self::denied($response);
        if ($denied !== null) {
            return $denied;
        }
        $row = self::liveRow(TokenEvents::KIND_TWO_FACTOR, $args['id'] ?? null);
        if ($row === null || $row['userID'] === null) {
            return self::notFound($response);
        }
        $actor = getLoggedFrameworkUserOrFail();
        if ($row['userID'] === (int) $actor->id) {
            return $response->withStatus(400)->withJson([
                'success' => false,
                'name' => TokenEvents::label(TokenEvents::KIND_TWO_FACTOR),
                'message' => __(self::LANG_GROUP, 'Su propio doble factor se gestiona desde Mi espacio.'),
            ]);
        }
        if (!OTPSecretsUsersMapper::disable2FA($row['userID'])) {
            return self::notFound($response);
        }
        TokenEvents::log(LogsMapper::MSG_TWO_FACTOR_RESET, TokenEvents::KIND_TWO_FACTOR, $row['userID'], $row['target'], ['%username%' => (string) $actor->username]);
        return $response->withJson([
            'success' => true,
            'name' => TokenEvents::label(TokenEvents::KIND_TWO_FACTOR),
            'message' => sprintf(__(self::LANG_GROUP, 'El doble factor de %s está apagado: entrará solo con su contraseña hasta que lo active de nuevo.'), $row['target']),
        ]);
    }

    /**
     * La pestaña «Tokens y códigos» de «Registros».
     *
     * @param Request $request
     * @param string $tabURL
     * @return string
     */
    public static function tokensTab(Request $request, string $tabURL): string
    {
        add_global_asset(UserSystemFeaturesRoutes::staticRoute('js/tokens.js'), 'js');
        $actor = getLoggedFrameworkUser();
        return (string) (new HelperController())->setInstanceViewDir(__DIR__ . '/../Views/')->render('panel/pages/tokens/tab', [
            'langGroup' => self::LANG_GROUP,
            'processTableLink' => self::routeName('datatables'),
            'historyTableLink' => LogsRoutes::ENABLE ? self::routeName('history') : '',
            'isRoot' => $actor !== null && (int) $actor->type === UsersModel::TYPE_USER_ROOT,
        ], false);
    }

    /**
     * @return void
     */
    public static function registerLogTab(): void
    {
        LogTabRegistry::register(new LogTab(
            self::TOKENS_TAB,
            __(self::LANG_GROUP, 'Tokens y códigos'),
            'key',
            self::$baseRouteName . '-datatables',
            fn(Request $request, string $tabURL): string => self::tokensTab($request, $tabURL),
            50,
            __(self::LANG_GROUP, 'Los códigos, enlaces y secretos de acceso que siguen vigentes, y la historia de lo que pasó con ellos.')
        ));
    }

    /**
     * Los vigentes de las cinco fuentes, con su usuario. Solo constantes: nada de la petición entra aquí.
     *
     * @return string
     */
    protected static function liveSQL(): string
    {
        $users = UsersModel::TABLE;
        $otp = OTPSecretsUsersMapper::TABLE;
        $recovery = (new RecoveryPasswordModel())->getModel()->getTable();
        $problems = (new UserProblemsModel())->getModel()->getTable();
        $tokens = (new TokenModel())->getTable();
        $system = UsersModel::SYSTEM_USER_ID;
        $oneUse = OTPSecretsUsersMapper::METHOD_ONE_USE_CODE;
        $totp = OTPSecretsUsersMapper::METHOD_TOTP;
        $enabled = OTPSecretsUsersMapper::TWOAF_STATUS_ENABLED;
        $forget = UserProblemsController::TYPE_USER_FORGET;
        $kinds = [
            'oneUse' => TokenEvents::KIND_ONE_USE_CODE,
            'twoFactor' => TokenEvents::KIND_TWO_FACTOR,
            'recovery' => TokenEvents::KIND_RECOVERY,
            'forgotten' => TokenEvents::KIND_FORGOTTEN_USER,
            'blocked' => TokenEvents::KIND_BLOCKED_USER,
            'token' => TokenEvents::KIND_TOKEN,
        ];
        $user = "u.id AS userID, u.username, u.type AS userType, u.organization AS userOrganization";
        return implode(' UNION ALL ', [
            "SELECT '{$kinds['oneUse']}' AS kind, x.id AS rowID, {$user}, u.email, NULL AS createdAt, x.maxDate AS expiresAt, NULL AS tokenType, NULL AS token FROM {$otp} x LEFT JOIN {$users} u ON u.id = x.user AND u.id != {$system} WHERE x.method = '{$oneUse}' AND x.oneUseCode != '' AND x.maxDate > NOW()",
            "SELECT '{$kinds['twoFactor']}' AS kind, x.id AS rowID, {$user}, u.email, NULL AS createdAt, NULL AS expiresAt, NULL AS tokenType, NULL AS token FROM {$otp} x LEFT JOIN {$users} u ON u.id = x.user AND u.id != {$system} WHERE x.method = '{$totp}' AND x.twoAuthFactor = '{$enabled}'",
            "SELECT '{$kinds['recovery']}' AS kind, x.id AS rowID, {$user}, x.email, x.created AS createdAt, x.expired AS expiresAt, NULL AS tokenType, NULL AS token FROM {$recovery} x LEFT JOIN {$users} u ON u.email = x.email AND u.id != {$system} WHERE x.expired > NOW()",
            "SELECT IF(x.type = '{$forget}', '{$kinds['forgotten']}', '{$kinds['blocked']}') AS kind, x.id AS rowID, {$user}, x.email, x.created AS createdAt, x.expired AS expiresAt, NULL AS tokenType, NULL AS token FROM {$problems} x LEFT JOIN {$users} u ON u.email = x.email AND u.id != {$system} WHERE x.expired > NOW()",
            "SELECT '{$kinds['token']}' AS kind, x.id AS rowID, NULL AS userID, NULL AS username, NULL AS userType, NULL AS userOrganization, NULL AS email, NULL AS createdAt, NULL AS expiresAt, x.type AS tokenType, x.token FROM {$tokens} x",
        ]);
    }

    /**
     * La fila VIGENTE de esa clase y ese id, con su valor y su usuario; null si no existe o ya no vale.
     *
     * @param string $kind
     * @param mixed $id
     * @return array{id:int,value:string,userID:int|null,target:string}|null
     */
    protected static function liveRow(string $kind, $id): ?array
    {
        if (!is_string($id) || preg_match('/^[1-9][0-9]{0,9}$/', $id) !== 1) {
            return null;
        }
        $database = (new BaseModel())->getDatabase();
        if ($database === null) {
            return null;
        }
        $users = UsersModel::TABLE;
        $system = UsersModel::SYSTEM_USER_ID;
        $sql = match ($kind) {
            TokenEvents::KIND_ONE_USE_CODE => 'SELECT x.id, x.oneUseCode AS value, u.id AS userID, u.username FROM `' . OTPSecretsUsersMapper::TABLE . "` x LEFT JOIN {$users} u ON u.id = x.user AND u.id != {$system} WHERE x.id = ? AND x.method = '" . OTPSecretsUsersMapper::METHOD_ONE_USE_CODE . "' AND x.oneUseCode != '' AND x.maxDate > NOW()",
            TokenEvents::KIND_TWO_FACTOR => "SELECT x.id, '' AS value, u.id AS userID, u.username FROM `" . OTPSecretsUsersMapper::TABLE . "` x LEFT JOIN {$users} u ON u.id = x.user AND u.id != {$system} WHERE x.id = ? AND x.method = '" . OTPSecretsUsersMapper::METHOD_TOTP . "' AND x.twoAuthFactor = '" . OTPSecretsUsersMapper::TWOAF_STATUS_ENABLED . "'",
            TokenEvents::KIND_RECOVERY => 'SELECT x.id, x.code AS value, u.id AS userID, COALESCE(u.username, x.email) AS username FROM `' . (new RecoveryPasswordModel())->getModel()->getTable() . "` x LEFT JOIN {$users} u ON u.email = x.email AND u.id != {$system} WHERE x.id = ? AND x.expired > NOW()",
            TokenEvents::KIND_FORGOTTEN_USER, TokenEvents::KIND_BLOCKED_USER => 'SELECT x.id, x.code AS value, u.id AS userID, COALESCE(u.username, x.email) AS username FROM `' . (new UserProblemsModel())->getModel()->getTable() . "` x LEFT JOIN {$users} u ON u.email = x.email AND u.id != {$system} WHERE x.id = ? AND x.expired > NOW() AND x.type = '" . ($kind === TokenEvents::KIND_FORGOTTEN_USER ? UserProblemsController::TYPE_USER_FORGET : UserProblemsController::TYPE_USER_BLOCKED) . "'",
            TokenEvents::KIND_TOKEN => "SELECT x.id, '' AS value, NULL AS userID, x.type AS username FROM `" . (new TokenModel())->getTable() . '` x WHERE x.id = ?',
            default => null,
        };
        if ($sql === null) {
            return null;
        }
        $statement = $database->prepare($sql);
        //RETORNO-IGNORADO: PDO va en ERRMODE_EXCEPTION: o hace su trabajo o lanza. Lo que cuenta es la fila que sigue.
        $statement->execute([(int) $id]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }
        return [
            'id' => (int) $row['id'],
            'value' => (string) $row['value'],
            'userID' => $row['userID'] !== null ? (int) $row['userID'] : null,
            'target' => (string) ($row['username'] ?? ''),
        ];
    }

    /**
     * Las acciones de una fila; solo para root, que es quien puede usarlas.
     *
     * @param UserDataPackage $actor
     * @param string $kind
     * @param int $id
     * @param int|null $userID
     * @return string
     */
    protected static function actionsCell(UserDataPackage $actor, string $kind, int $id, ?int $userID): string
    {
        $params = ['kind' => $kind, 'id' => $id];
        $textReset = __(self::LANG_GROUP, 'Restablecer doble factor');
        $textShow = __(self::LANG_GROUP, 'Mostrar');
        $textInvalidate = __(self::LANG_GROUP, 'Invalidar ahora');
        //Siempre las tres y en el mismo orden (design.md §3.28): la que no aplica va inactiva.
        //Restablecer e invalidar son destructivas: en rojo cuando aplican (vistas-referencia.md §3.28).
        $reset = RowActions::inactive($textReset, 'user shield');
        if ($kind === TokenEvents::KIND_TWO_FACTOR && $userID !== null && $userID !== (int) $actor->id) {
            $reset = RowActions::negative($textReset, '#', 'user shield', [
                'data-token-action' => self::routeName('reset-two-factor', ['id' => $id]),
                'data-confirmation-message' => __(self::LANG_GROUP, 'El usuario entrará solo con su contraseña hasta que active de nuevo el doble factor. ¿Restablecerlo?'),
            ]);
        }
        $reveal = RowActions::inactive($textShow, 'eye');
        if (in_array($kind, TokenEvents::REVEALABLE, true)) {
            $reveal = RowActions::action($textShow, '#', 'eye', [
                'data-token-reveal' => self::routeName('reveal', $params),
                'data-confirmation-message' => __(self::LANG_GROUP, 'Ver el valor de un código queda anotado en el registro. ¿Mostrarlo?'),
            ]);
        }
        $invalidate = RowActions::inactive($textInvalidate, 'ban');
        if ($kind !== TokenEvents::KIND_TWO_FACTOR) {
            $invalidate = RowActions::negative($textInvalidate, '#', 'ban', [
                'data-token-action' => self::routeName('invalidate', $params),
                'data-confirmation-message' => __(self::LANG_GROUP, 'El código dejará de servir ahora mismo. ¿Invalidarlo?'),
            ]);
        }
        return RowActions::cell($reset, [$reveal, $invalidate]);
    }

    /**
     * Solo root, y nunca suplantando: ver un código ajeno o apagar un doble factor con el rol de otro no se permite.
     *
     * @param Response $response
     * @return Response|null null si puede seguir
     */
    protected static function denied(Response $response): ?Response
    {
        $actor = getLoggedFrameworkUserOrFail();
        $impersonating = Impersonation::isImpersonating();
        if ($impersonating || (int) $actor->type !== UsersModel::TYPE_USER_ROOT) {
            return $response->withStatus(403)->withJson([
                'success' => false,
                'name' => __(self::LANG_GROUP, 'Tokens y códigos'),
                'message' => $impersonating
                    ? __(self::LANG_GROUP, 'No se pueden gestionar códigos mientras se actúa como otro usuario.')
                    : __(self::LANG_GROUP, 'Solo el usuario principal puede gestionar los códigos.'),
            ]);
        }
        return null;
    }

    /**
     * @param Response $response
     * @return Response
     */
    protected static function notFound(Response $response): Response
    {
        return $response->withStatus(404)->withJson([
            'success' => false,
            'name' => __(self::LANG_GROUP, 'Tokens y códigos'),
            'message' => __(self::LANG_GROUP, 'Ese código no existe o ya no está vigente.'),
        ]);
    }

    /**
     * La carga de un JWT, que va en claro: solo iat y exp, como enteros. El dato, sellado, no se lee.
     *
     * @param string $jwt
     * @return array{iat?:int,exp?:int}
     */
    protected static function jwtClaims(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return [];
        }
        $payload = json_decode((string) base64_decode(strtr($parts[1], '-_', '+/'), true), true);
        if (!is_array($payload)) {
            return [];
        }
        $claims = [];
        foreach (['iat', 'exp'] as $claim) {
            if (isset($payload[$claim]) && is_int($payload[$claim])) {
                $claims[$claim] = $payload[$claim];
            }
        }
        return $claims;
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
        $listing = [UsersModel::TYPE_USER_ROOT, UsersModel::TYPE_USER_ADMIN_GRAL, UsersModel::TYPE_USER_ADMIN_ORG];
        $root = [UsersModel::TYPE_USER_ROOT];

        $group->register([
            new Route("{$startRoute}/datatables[/]", self::class . ':dataTables', self::$baseRouteName . '-datatables', 'GET', true, null, $listing),
            new Route("{$startRoute}/history[/]", self::class . ':history', self::$baseRouteName . '-history', 'GET', true, null, $listing),
            //Cada acción se comprueba además a sí misma (denied()): solo root y sin suplantar.
            new Route("{$startRoute}/reveal/{kind}/{id}[/]", self::class . ':reveal', self::$baseRouteName . '-reveal', 'POST', true, null, $root),
            new Route("{$startRoute}/invalidate/{kind}/{id}[/]", self::class . ':invalidate', self::$baseRouteName . '-invalidate', 'POST', true, null, $root),
            new Route("{$startRoute}/reset-two-factor/{id}[/]", self::class . ':resetTwoFactor', self::$baseRouteName . '-reset-two-factor', 'POST', true, null, $root),
        ]);

        return $group;
    }
}
