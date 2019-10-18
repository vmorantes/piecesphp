<?php
/**
 * MessagesModel.php
 */
namespace App\Model;

use PiecesPHP\Core\BaseEntityMapper;

/**
 * MessagesModel.
 *
 * Modelo de Mensajeria.
 *
 * @package     App\Model
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class MessagesModel extends BaseEntityMapper
{
    const READ = 1;
    const UNREAD = 0;
    const TYPE_NORMAL_MESSAGE = 'normal';
    const TYPE_RESPONSE_MESSAGE = 'response';
    const MESSAGES_RESPONSES_TABLE = 'messages_responses';
    const MESSAGES_TABLE = 'messages';

    protected static $where = [];

    protected $fields = [
        'id' => [
            'type' => 'int',
            'primary_key' => true,
        ],
        'message_from' => [
            'type' => 'int',
            'reference_table' => 'pcsphp_users',
            'reference_field' => 'id',
            'reference_primary_key' => 'id',
            'human_readable_reference_field' => 'username',
            'mapper' => UsersModel::class,
        ],
        'message_to' => [
            'type' => 'int',
            'null' => true,
            'reference_table' => 'pcsphp_users',
            'reference_field' => 'id',
            'reference_primary_key' => 'id',
            'human_readable_reference_field' => 'username',
            'mapper' => UsersModel::class,
        ],
        'date' => [
            'type' => 'datetime',
            'default' => 'timestamp',
        ],
        'subject' => [
            'type' => 'text',
        ],
        'message' => [
            'type' => 'text',
            'null' => true,
        ],
        'attachment' => [
            'type' => 'text',
            'null' => true,
        ],
        'readed' => [
            'type' => 'int',
            'default' => self::UNREAD,
        ],

    ];

    /**
     * $table
     *
     * @var string
     */
    protected $table = self::MESSAGES_TABLE;

    /**
     * __construct
     *
     * @param int $value
     * @param string $field_compare
     * @return static
     */
    public function __construct(int $value = null, string $field_compare = 'message_from')
    {
        parent::__construct($value, $field_compare);
    }

    /**
     * getMessages
     *
     * @param array|string $where
     * @param bool $humanReadable
     * @param int $page
     * @param int $perPage
     * @return static[]
     */
    public static function getMessages($where, bool $humanReadable = false, int $page = null, int $perPage = null)
    {
        $queryBuilder = (new static())->getModel();
        self::$where = $where;

        $queryBuilder->select()->where(self::$where)->orderBy("readed ASC, date DESC")->execute(false, $page, $perPage);
        $result = $queryBuilder->result();

        return self::processMessages($result, $humanReadable);
    }

    /**
     * getMessagesResponsesByMessage
     *
     * @param int $messageID
     * @param bool $humanReadable
     * @param bool $process
     * @return BaseEntityMapper[]
     */
    public static function getMessagesResponsesByMessage(int $messageID, bool $humanReadable = false, bool $process = true)
    {
        $queryBuilder = (self::messagesResponseMapper())->getModel();

        $queryBuilder->select()->where([
            'message_id' => $messageID,
        ])->orderBy("date ASC")->execute();

        $result = $queryBuilder->result();

        $messages = $process ? self::processMessages($result, $humanReadable, true) : $result;
        return $messages;
    }

    /**
     * getTotalMessages
     *
     * @return int
     */
    public static function getTotalMessages()
    {
        $queryBuilder = (new static())->getModel();
        $queryBuilder->select()->where(self::$where)->execute();
        return count($queryBuilder->result());
    }

    /**
     * messagesResponseMapper
     *
     * @param int $value
     * @param string $field_compare
     * @return BaseEntityMapper
     */
    public static function messagesResponseMapper(int $value = null, string $field_compare = 'message_id')
    {
        return new class($value, $field_compare) extends BaseEntityMapper
        {
            /**
             * __construct
             *
             * @param int $value
             * @param string $field_compare
             * @return static
             */
            public function __construct(int $value = null, string $field_compare = 'message_id')
            {
                parent::__construct($value, $field_compare);
            }
            protected $table = MessagesModel::MESSAGES_RESPONSES_TABLE;
            protected $fields = [
                'id' => [
                    'type' => 'int',
                    'primary_key' => true,
                ],
                'message_from' => [
                    'type' => 'int',
                    'reference_table' => 'pcsphp_users',
                    'reference_field' => 'id',
                    'reference_primary_key' => 'id',
                    'human_readable_reference_field' => 'username',
                    'mapper' => UsersModel::class,
                ],
                'message_id' => [
                    'type' => 'int',
                ],
                'message' => [
                    'type' => 'text',
                    'null' => true,
                ],
                'date' => [
                    'type' => 'datetime',
                    'default' => 'timestamp',
                ],
                'attachment' => [
                    'type' => 'text',
                    'null' => true,
                ],
                'readed' => [
                    'type' => 'int',
                    'default' => MessagesModel::UNREAD,
                ],

            ];

        };
    }

    /**
     * verirfyThreadStatus
     *
     * @param array|string $where
     * @param UsersModel $user
     * @return array
     */
    public static function verirfyThreadStatus($where, $user)
    {
        $hasUnread = false;

        $queryBuilder = (new static())->getModel();
        self::$where = $where;

        $queryBuilder->select()->where(self::$where)->execute();
        $messages = $queryBuilder->result();

        $unreadsThreads = 0;
        $readsThreads = 0;

        foreach ($messages as $message) {

            $messageIsMine = $user->id == $message->message_from;
            $responses = self::getMessagesResponsesByMessage($message->id, false, false);

            if (!$messageIsMine) {

                if ($message->readed == self::UNREAD) {
                    $unreadsThreads++;
                } else {
                    $readsThreads++;
                }
            }

            foreach ($responses as $response) {

                $responseIsMine = $user->id == $response->message_from;

                if (!$responseIsMine) {

                    if ($response->readed == self::UNREAD) {
                        $unreadsThreads++;
                    } else {
                        $readsThreads++;
                    }

                }

            }
        }

        $hasUnread = $unreadsThreads > 0;

        return [
            'hasUnread' => $hasUnread,
            'readed' => $readsThreads,
            'unreaded' => $unreadsThreads,
        ];
    }

    /**
     * hasUnreadMessagesOnThread
     *
     * @param int $message_id
     * @return bool
     */
    public static function hasUnreadMessagesOnThread(int $message_id)
    {
        $queryBuilder = (self::messagesResponseMapper())->getModel();

        $queryBuilder->select()->where([
            'message_id' => $message_id,
            'readed' => self::UNREAD,
        ])->execute();

        $responses = $queryBuilder->result();

        return count($responses) > 0;
    }

    /**
     * processMessages
     *
     * @param array $messages
     * @param bool $humanReadable
     * @param bool $isSubMessage
     * @return array
     */
    public static function processMessages(array $messages, bool $humanReadable = false, bool $isSubMessage = false)
    {
        $elements = [];

        foreach ($messages as $element) {

            if (!$isSubMessage) {

                $mapper = new static($element->id, 'id');
                $message = self::processDataMessage($mapper, $humanReadable);

                $elements[] = [
                    'message' => $message,
                    'responses' => self::getMessagesResponsesByMessage($element->id, $humanReadable),
                ];

            } else {
                $mapper = self::messagesResponseMapper($element->id, 'primary_key');
                $message = self::processDataMessage($mapper, $humanReadable);

                $elements[] = $message;
            }

        }

        return $elements;
    }

    /**
     * processDataMessage
     *
     * @param BaseEntityMapper $mapper
     * @param bool $humanReadable
     * @return mixed
     */
    private static function processDataMessage(BaseEntityMapper $mapper, bool $humanReadable = false)
    {
        $message = null;

        if ($humanReadable) {

            $fields = $mapper->getFields();
            $message = [];

            foreach ($fields as $field => $data) {

                $value_field = $mapper->$field;
                $tableMapper = $mapper->getModel()->getTable();

                if (!isset($message['mark_as_read_url'])) {

                    $mark_as_read_url = '';

                    if ($tableMapper == self::MESSAGES_TABLE) {
                        $mark_as_read_url = get_route('messages-mark-read', [
                            'type' => self::TYPE_NORMAL_MESSAGE,
                            'id' => $mapper->id,
                        ]);
                    } elseif ($tableMapper == self::MESSAGES_RESPONSES_TABLE) {
                        $mark_as_read_url = get_route('messages-mark-read', [
                            'type' => self::TYPE_RESPONSE_MESSAGE,
                            'id' => $mapper->id,
                        ]);
                    }

                    $message['mark_as_read_url'] = $mark_as_read_url;
                }

                if ($field == 'message_from') {
                    $avatar = AvatarModel::getAvatar($value_field->id);
					$message['avatar'] = !is_null($avatar) ? $avatar : baseurl('statics/images/default-avatar.png');
					$message['rol'] = UsersModel::getTypesUser()[$value_field->type];
                }

                if (is_subclass_of($value_field, BaseEntityMapper::class)) {

                    $message[$field] = $value_field->humanReadable();

                } elseif ($value_field instanceof \DateTime) {

                    $day = $value_field->format('j');
                    $dayName = __('day', $value_field->format('w'));
                    $month = __('month', (string) ($value_field->format('n') - 1));
                    $year = $value_field->format('Y');
                    $message[$field] = "$dayName $day de $month, $year";
                    $message['datetime'] = $value_field;

                } elseif ($field == 'readed') {

                    if ($tableMapper == self::MESSAGES_TABLE) {
                        $message['main_readed'] = self::READ == $value_field;
                        $value_field = $message['main_readed'] && !self::hasUnreadMessagesOnThread($mapper->id);
                    } else {
                        $value_field = self::READ == $value_field;
                    }
                    $message[$field] = $value_field;

                } else {

                    $message[$field] = $mapper->humanReadable()[$field];

                }
            }

        } else {
            $message = $mapper;
        }

        return $message;
    }

}
