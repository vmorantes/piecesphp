<?php

/**
 * MessagesController.php
 */

namespace App\Controller;

use App\Model\MessagesModel;
use App\Model\UsersModel;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Validation\Validator;
use \Slim\Http\Request as Request;
use \Slim\Http\Response as Response;

/**
 * MessagesController.
 *
 * Controlador de Mensajeria
 *
 * @package     App\Controller
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class MessagesController extends AdminPanelController
{

    const COMPONENT_EXTERNAL_EDITOR = 'COMPONENT_EXTERNAL_EDITOR';
    const COMPONENT_EXTERNAL_EDITOR_WITH_SELECT_DESTINE = 'COMPONENT_EXTERNAL_EDITOR_WITH_SELECT_DESTINE';
    const VIEW_MESSAGES_WITHOUT_TO = 'VIEW_MESSAGES_WITHOUT_TO';

    const ALLOWEDS_CAPABILITIES_BY_TYPE_USER = [
        UsersModel::TYPE_USER_GENERAL => [
            self::COMPONENT_EXTERNAL_EDITOR,
        ],
        UsersModel::TYPE_USER_ADMIN => [
            self::VIEW_MESSAGES_WITHOUT_TO,
            self::COMPONENT_EXTERNAL_EDITOR_WITH_SELECT_DESTINE,
        ],
        UsersModel::TYPE_USER_ROOT => [
            self::VIEW_MESSAGES_WITHOUT_TO,
            self::COMPONENT_EXTERNAL_EDITOR_WITH_SELECT_DESTINE,
        ],
    ];

    /**
     * __construct
     *
     * @return static
     */
    public function __construct()
    {
        parent::__construct(false); //No cargar ningún modelo automáticamente.
        add_global_asset(MESSAGES_PATH_JS . '/component.js', 'js');
        add_global_asset(MESSAGES_PATH_JS . '/main.js', 'js');
    }

    /**
     * inbox
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function inbox(Request $request, Response $response, array $args)
    {
        if ($request->isXhr()) {

            $page = $request->getQueryParam('page', 1);
            $perPage = $request->getQueryParam('per_page', 2);

            $user_id = isset($args['user_id']) ? $args['user_id'] : '';

            $messages = [];
            $pages = 1;
            $total = 0;

            $queryMessages = null;

            if (ctype_digit($user_id)) {

                $user = new UsersModel($user_id);
                $queryBuilder = (new MessagesModel)->getModel();

                $where = self::getWhereByUserType($user);

                $unreadValue = MessagesModel::UNREAD;

                $subQuery = "(SELECT MAX(messages_responses.date) FROM messages_responses WHERE messages_responses.message_id = messages.id AND readed = {$unreadValue} AND messages_responses.message_from != {$user->id})";

                $queryBuilder->select([
                    'id',
                    'message_from',
                    'message_to',
                    'date',
                    'subject',
                    'message',
                    'attachment',
                    'readed',
                ])->where($where)->orderBy("{$subQuery} DESC, readed ASC, date DESC");

                $queryMessages = clone $queryBuilder;
                $queryTotalMessages = clone $queryBuilder->select("COUNT(id) AS total");

                $queryMessages->execute(false, $page, $perPage);

                $messagesResult = MessagesModel::processMessages($queryMessages->result(), true);
                $messagesCount = $queryTotalMessages->row();

                if ($messagesCount instanceof \stdClass) {
                    $messagesCount = (int) $messagesCount->total;
                } else {
                    $messagesCount = 0;
                }

                $messages = $messagesResult;
                $total = $messagesCount;
                $pages = ceil($total / $perPage);
            }

            return $response->withJson([
                'messages' => $messages,
                'pages' => $pages,
                'total' => $total,
                'queryMessages' => !is_null($queryMessages) ? $queryMessages->getLastSQLExecuted() : '',
            ]);

        } else {

            $usersModel = (new UsersModel())->getModel();

            $where = [
                'id' => [
                    '!=' => $this->user->id,
                ],
            ];

            $query = $usersModel->select()->where($where);

            $query->execute();

            $result = $query->result();

            $data = [];
            $data['destinatarios'] = $result;

            $this->render(ADMIN_PATH_VIEWS . '/layout/header');
            $this->render(MESSAGES_PATH_VIEWS . '/inbox', $data);
            $this->render(ADMIN_PATH_VIEWS . '/layout/footer');

            return $response;

        }
    }

    /**
     * sendMessage
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function sendMessage(Request $request, Response $response, array $args)
    {

        $from = $request->getParsedBodyParam('from', null);
        $to = $request->getParsedBodyParam('to', null);
        $subject = $request->getParsedBodyParam('subject', null);
        $message = $request->getParsedBodyParam('message', null);

        $params_verify = !in_array(null, [$from, $subject, $message]);

        $json_response = [
            'success' => false,
            'message' => '',
        ];

        if ($params_verify) {

            $messageObject = new MessagesModel();
            $messageObject->message_from = $from;
            $messageObject->message_to = $to;
            $messageObject->subject = $subject;
            $messageObject->message = strip_tags($message);
            $saved = $messageObject->save();

            $json_response['success'] = $saved;
            if (!$saved) {
                $json_response['message'] = __('messenger', 'No ha podido enviarse el mensaje, intente más tarde.');
            } else {
                $json_response['message'] = __('messenger', 'El mensaje ha sido enviado.');
            }

        } else {
            $json_response['message'] = __('messenger', 'Los parámetros recibidos no son correctos.');
        }

        return $response->withJson($json_response);
    }

    /**
     * sendResponse
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function sendResponse(Request $request, Response $response, array $args)
    {

        $message_id = $request->getParsedBodyParam('message_id', null);
        $message_from = $request->getParsedBodyParam('message_from', null);
        $message = $request->getParsedBodyParam('message', null);

        $params_verify = !in_array(null, [$message_from, $message]);

        $json_response = [
            'success' => false,
            'message' => '',
        ];

        if ($params_verify) {

            $messageResponseObject = MessagesModel::messagesResponseMapper();
            $messageResponseObject->message_id = $message_id;
            $messageResponseObject->message_from = $message_from;
            $messageResponseObject->message = strip_tags($message);
            $saved = $messageResponseObject->save();

            $json_response['success'] = $saved;
            if (!$saved) {
                $json_response['message'] = __('messenger', 'No ha podido enviarse el mensaje, intente más tarde.');
            } else {
                $json_response['message'] = __('messenger', 'El mensaje ha sido enviado.');
            }

        } else {
            $json_response['message'] = __('messenger', 'Los parámetros recibidos no son correctos.');
        }

        return $response->withJson($json_response);
    }

    /**
     * markAsRead
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function markAsRead(Request $request, Response $response, array $args)
    {

        $type = isset($args['type']) ? $args['type'] : null;
        $id = isset($args['id']) ? $args['id'] : null;

        $mapper = null;

        $structureResponse = [
            'success' => false,
            'message' => '',
        ];

        if (Validator::isString($type) && Validator::isInteger($id)) {

            $id = (int) $id;

            switch ($type) {
                case MessagesModel::TYPE_NORMAL_MESSAGE:
                    $mapper = new MessagesModel($id, 'primary_key');
                    break;
                case MessagesModel::TYPE_RESPONSE_MESSAGE:
                    $mapper = MessagesModel::messagesResponseMapper($id, 'primary_key');
                    break;
            }

            if (!is_null($mapper->id)) {
                $mapper->readed = MessagesModel::READ;
                $updated = $mapper->update();

                if ($updated) {
                    $structureResponse['success'] = $updated;
                    $structureResponse['message'] = __('messenger', 'El mensaje ha sido marcado como leído.');
                } else {
                    $structureResponse['message'] = __('messenger', 'Ha ocurrido un error, intente luego.');
                }
            } else {
                $structureResponse['message'] = __('messenger', 'El mensaje que intenta modificar no existe.');
            }

        } else {
            $structureResponse['message'] = __('messenger', 'Parámetros incompatibles.');
        }

        return $response->withJson($structureResponse);
    }

    /**
     * verirfyThreadsStatus
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function verirfyThreadsStatus(Request $request, Response $response, array $args)
    {

        $user_id = $this->user->id;

        $structureResponse = [
            'success' => false,
            'statusMessages' => null,
            'message' => '',
        ];

        if (Validator::isInteger($user_id)) {

            $user = new UsersModel($user_id);

            if (!is_null($user->id)) {

                $where = self::getWhereByUserType($user);
                $statusConversation = MessagesModel::verirfyThreadStatus($where, $user);
                $structureResponse['statusMessages'] = $statusConversation;
                $structureResponse['success'] = true;

            } else {

                $structureResponse['message'] = __('messenger', 'El usuario no existe.');

            }

        } else {

            $structureResponse['message'] = __('messenger', 'Parámetros incompatibles.');

        }

        return $response->withJson($structureResponse);
    }

    /**
     * isAllowed
     *
     * @param int $type
     * @param string $typeRequest
     * @return bool
     */
    public static function isAllowed(int $type, string $typeRequest)
    {
        return array_key_exists($type, self::ALLOWEDS_CAPABILITIES_BY_TYPE_USER) && in_array($typeRequest, self::ALLOWEDS_CAPABILITIES_BY_TYPE_USER[$type]);
    }

    /**
     * getWhereByUserType
     *
     * @param UsersModel $user
     * @return string|array
     */
    private static function getWhereByUserType(UsersModel $user)
    {
        $where = '';

        if (self::isAllowed($user->type, self::VIEW_MESSAGES_WITHOUT_TO)) {

            $where = "message_from = $user->id OR message_to = $user->id OR message_to IS NULL";

        } else {

            $where = [
                'message_from' => [
                    '=' => $user->id,
                    'and_or' => 'OR',
                ],
                'message_to' => [
                    '=' => $user->id,
                    'and_or' => 'OR',
                ],
            ];

        }

        return $where;
    }

    /**
     * routes
     *
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {
        $groupSegmentURL = $group->getGroupSegment();
        $lastIsBar = last_char($groupSegmentURL) == '/';
        $startRoute = $lastIsBar ? '' : '/';
        $classname = MessagesController::class;
        $routes = [];
        $all_roles = array_keys(UsersModel::TYPES_USERS);

        //──── GET ─────────────────────────────────────────────────────────────────────────
        $routes[] = new Route(
            "{$startRoute}[{user_id}[/]]",
            $classname . ':inbox',
            'messages-inbox',
            'GET',
            true,
            null,
            $all_roles
        );

        //──── POST ─────────────────────────────────────────────────────────────────────────
        $routes[] = new Route(
            "{$startRoute}send[/]",
            $classname . ':sendMessage',
            'messages-send-message',
            'POST',
            true,
            null,
            $all_roles
        );
        $routes[] = new Route(
            "{$startRoute}send/response[/]",
            $classname . ':sendResponse',
            'messages-send-response',
            'POST',
            true,
            null,
            $all_roles
        );
        $routes[] = new Route(
            "{$startRoute}mark-as-read/{type}/{id}[/]",
            $classname . ':markAsRead', 'messages-mark-read',
            'POST',
            true,
            null,
            $all_roles
        );
        $routes[] = new Route(
            "{$startRoute}thread-status[/]",
            $classname . ':verirfyThreadsStatus',
            'messages-threads-status',
            'POST',
            true,
            null,
            $all_roles
        );

        $group->active(MESSAGES_ENABLED);
        $group->register($routes);

        return $group;
    }
}
