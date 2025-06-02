<?php

/**
 * APIController.php
 */

namespace API\Controllers;

use API\Adapters\APIExternalAdapterExample;
use API\Adapters\MistralHandlerAdapter;
use API\Adapters\OpenAIHandlerAdapter;
use API\APILang;
use API\APIRoutes;
use App\Controller\AdminPanelController;
use App\Controller\AvatarController;
use App\Controller\RecoveryPasswordController;
use App\Controller\UserProblemsController;
use App\Controller\UsersController;
use App\Model\AppConfigModel;
use App\Model\AvatarModel;
use App\Model\RecoveryPasswordModel;
use App\Model\TicketsLogModel;
use App\Model\UsersModel;
use EventsLog\Mappers\LogsMapper;
use News\Controllers\NewsCategoryController;
use News\Controllers\NewsController;
use News\Mappers\NewsMapper;
use Organizations\Controllers\OrganizationsController;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseHashEncryption;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\ConfigHelpers\MailConfig;
use PiecesPHP\Core\Mailer;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\RequestRouteFactory;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Routing\Slim3Compatibility\Exception\NotFoundException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\MissingRequiredParamaterException;
use PiecesPHP\Core\Validation\Parameters\Parameter;
use PiecesPHP\Core\Validation\Parameters\Parameters;
use PiecesPHP\Core\Validation\Validator;
use PiecesPHP\RoutingUtils\DefaultAccessControlModules;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use PiecesPHP\UserSystem\UserDataPackage;
use Publications\Controllers\PublicationsCategoryController;
use Publications\Controllers\PublicationsController;
use Publications\Mappers\PublicationCategoryMapper;
use Publications\Mappers\PublicationMapper;

/**
 * APIController.
 *
 * @package     API\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2023
 */
class APIController extends AdminPanelController
{

    /**
     * @var string
     */
    protected static $URLDirectory = 'api';
    /**
     * @var string
     */
    protected static $baseRouteName = 'api-admin';
    /**
     * @var string
     */
    protected static $title = 'API';

    /**
     * @var HelperController
     */
    protected $helpController = null;

    const LANG_GROUP = APILang::LANG_GROUP;

    public function __construct()
    {
        parent::__construct();

        self::$title = __(self::LANG_GROUP, self::$title);

        set_title(self::$title);

        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function publications(Request $request, Response $response)
    {

        $context = $request->getAttribute('context');
        $actionType = $request->getAttribute('actionType');
        $method = mb_strtoupper($request->getMethod());

        if ($context == 'publications') {

            if ($actionType == 'list') {

                if ($method !== 'GET') {
                    throw new NotFoundException($request, $response);
                }

                $expectedParameters = new Parameters([
                    new Parameter(
                        'page',
                        1,
                        function ($value) {
                            return Validator::isInteger($value);
                        },
                        true,
                        function ($value) {
                            return (int) $value;
                        }
                    ),
                    new Parameter(
                        'perPage',
                        10,
                        function ($value) {
                            return Validator::isInteger($value);
                        },
                        true,
                        function ($value) {
                            return (int) $value;
                        }
                    ),
                    new Parameter(
                        'category',
                        null,
                        function ($value) {
                            return Validator::isInteger($value) || $value == PublicationCategoryMapper::UNCATEGORIZED_ID;
                        },
                        true,
                        function ($value) {
                            return (int) $value;
                        }
                    ),
                    new Parameter(
                        'title',
                        null,
                        function ($value) {
                            return is_scalar($value) && mb_strlen((string) $value) > 0;
                        },
                        true,
                        function ($value) {
                            return (string) $value;
                        }
                    ),
                ]);

                $expectedParameters->setInputValues($request->getQueryParams());
                $expectedParameters->validate();

                /**
                 * @var int $page
                 * @var int $perPage
                 * @var int $category
                 * @var string $title
                 */
                $page = $expectedParameters->getValue('page');
                $perPage = $expectedParameters->getValue('perPage');
                $category = $expectedParameters->getValue('category');
                $title = $expectedParameters->getValue('title');

                $controller = new PublicationsController();
                $all = $controller->_all($page, $perPage, $category, null, null, $title);

                $response = $response->withJson($all);

            } elseif ($actionType == 'detail') {

                if ($method !== 'GET') {
                    throw new NotFoundException($request, $response);
                }

                $expectedParameters = new Parameters([
                    new Parameter(
                        'id',
                        null,
                        function ($value) {
                            return Validator::isInteger($value);
                        },
                        false,
                        function ($value) {
                            return (int) $value;
                        }
                    ),
                ]);

                $expectedParameters->setInputValues($request->getQueryParams());
                $expectedParameters->validate();

                /**
                 * @var int $page
                 */
                $id = $expectedParameters->getValue('id');

                $elementData = [];

                $publicationMapper = new PublicationMapper($id);

                if ($publicationMapper->id !== null) {

                    $elementData = $publicationMapper->humanReadable();
                    $elementData['mainImage'] = baseurl($publicationMapper->mainImage);
                    $elementData['thumbImage'] = baseurl($publicationMapper->thumbImage);
                    $elementData['createdBy'] = $publicationMapper->createdBy->id;
                    $elementData['modifiedBy'] = $publicationMapper->modifiedBy !== null ? $publicationMapper->modifiedBy->id : null;
                    unset($elementData['category']['meta']);
                    unset($elementData['category']['META:langData']);
                    unset($elementData['meta']);
                    unset($elementData['META:langData']);
                } else {
                    $elementData = null;
                }

                $response = $response->withJson([
                    'publicationData' => $elementData,
                ]);
            } else {
                throw new NotFoundException($request, $response);
            }

        } else if ($context == 'categories') {

            if ($actionType == 'list') {

                if ($method !== 'GET') {
                    throw new NotFoundException($request, $response);
                }

                $expectedParameters = new Parameters([
                    new Parameter(
                        'page',
                        1,
                        function ($value) {
                            return Validator::isInteger($value);
                        },
                        true,
                        function ($value) {
                            return (int) $value;
                        }
                    ),
                    new Parameter(
                        'perPage',
                        10,
                        function ($value) {
                            return Validator::isInteger($value);
                        },
                        true,
                        function ($value) {
                            return (int) $value;
                        }
                    ),
                ]);

                $expectedParameters->setInputValues($request->getQueryParams());
                $expectedParameters->validate();

                /**
                 * @var int $page
                 * @var int $perPage
                 */
                $page = $expectedParameters->getValue('page');
                $perPage = $expectedParameters->getValue('perPage');

                $controller = new PublicationsCategoryController();
                $all = $controller->_all($page, $perPage);

                $response = $response->withJson($all);

                return $response;
            } else {
                throw new NotFoundException($request, $response);
            }
        } else {
            throw new NotFoundException($request, $response);
        }

        return $response;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function news(Request $request, Response $response)
    {
        $actionType = $request->getAttribute('actionType');
        $method = mb_strtoupper($request->getMethod());

        if ($actionType == 'list') {

            if ($method !== 'GET') {
                throw new NotFoundException($request, $response);
            }

            $expectedParameters = new Parameters([
                new Parameter(
                    'page',
                    1,
                    function ($value) {
                        return Validator::isInteger($value);
                    },
                    true,
                    function ($value) {
                        return (int) $value;
                    }
                ),
                new Parameter(
                    'perPage',
                    10,
                    function ($value) {
                        return Validator::isInteger($value);
                    },
                    true,
                    function ($value) {
                        return (int) $value;
                    }
                ),
                new Parameter(
                    'category',
                    null,
                    function ($value) {
                        return Validator::isInteger($value) || $value == PublicationCategoryMapper::UNCATEGORIZED_ID;
                    },
                    true,
                    function ($value) {
                        return (int) $value;
                    }
                ),
            ]);

            $expectedParameters->setInputValues($request->getQueryParams());
            $expectedParameters->validate();

            /**
             * @var int $page
             * @var int $perPage
             * @var int $category
             */
            $page = $expectedParameters->getValue('page');
            $perPage = $expectedParameters->getValue('perPage');
            $category = $expectedParameters->getValue('category');

            $controller = new NewsController();
            $all = $controller->_all($page, $perPage, $category);

            $response = $response->withJson($all);

        } elseif ($actionType == 'detail') {

            if ($method !== 'GET') {
                throw new NotFoundException($request, $response);
            }

            $expectedParameters = new Parameters([
                new Parameter(
                    'id',
                    null,
                    function ($value) {
                        return Validator::isInteger($value);
                    },
                    false,
                    function ($value) {
                        return (int) $value;
                    }
                ),
            ]);

            $expectedParameters->setInputValues($request->getQueryParams());
            $expectedParameters->validate();

            /**
             * @var int $page
             */
            $id = $expectedParameters->getValue('id');

            $elementData = [];

            $newsMapper = new NewsMapper($id);

            if ($newsMapper->id !== null) {

                $elementData = $newsMapper->humanReadable();
                $elementData['profilesTarget'] = (array) $newsMapper->profilesTarget;
                $elementData['createdBy'] = $newsMapper->createdBy->id;
                $elementData['modifiedBy'] = $newsMapper->modifiedBy !== null ? $newsMapper->modifiedBy->id : null;
                $elementData['category']['iconImage'] = baseurl($elementData['category']['iconImage']);
                unset($elementData['category']['meta']);
                unset($elementData['category']['META:langData']);
                unset($elementData['meta']);
                unset($elementData['META:langData']);

            } else {
                $elementData = null;
            }

            $response = $response->withJson([
                'newsData' => $elementData,
            ]);

        } elseif ($actionType == 'categories') {

            if ($method !== 'GET') {
                throw new NotFoundException($request, $response);
            }

            $expectedParameters = new Parameters([
                new Parameter(
                    'page',
                    1,
                    function ($value) {
                        return Validator::isInteger($value);
                    },
                    true,
                    function ($value) {
                        return (int) $value;
                    }
                ),
                new Parameter(
                    'perPage',
                    10,
                    function ($value) {
                        return Validator::isInteger($value);
                    },
                    true,
                    function ($value) {
                        return (int) $value;
                    }
                ),
            ]);

            $expectedParameters->setInputValues($request->getQueryParams());
            $expectedParameters->validate();

            /**
             * @var int $page
             * @var int $perPage
             */
            $page = $expectedParameters->getValue('page');
            $perPage = $expectedParameters->getValue('perPage');

            $controller = new NewsCategoryController();
            $all = $controller->_all($page, $perPage, true);

            $response = $response->withJson($all);

            return $response;
        } else {
            throw new NotFoundException($request, $response);
        }

        return $response;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function translations(Request $request, Response $response)
    {
        $actionType = $request->getAttribute('actionType', 'translate');

        //Acciones permitidas
        $allowedActions = [
            get_config('translationAIEnable') ? 'translate' : uniqid(),
            'saveGroup',
        ];
        if (!in_array($actionType, $allowedActions)) {
            throw new NotFoundException($request, $response);
        }

        //Selección de idioma de respuesta
        $lang = Config::get_lang();
        $expectedLang = get_config('responseExpectedLang');
        $lang = is_string($expectedLang) && mb_strlen($expectedLang) > 1 ? $expectedLang : $lang;
        set_config('app_lang', $lang);

        //Obtener dominio actual y verificar si la petición es del mismo dominio
        $isSameDomain = requestIsSameDomain();

        $method = mb_strtoupper($request->getMethod());
        $responseJSON = [
            'success' => false,
            'message' => '',
        ];

        try {
            if ($method === 'GET' || $method === 'POST') {

                if ($actionType == 'translate') {

                    $expectedParameters = new Parameters([
                        new Parameter(
                            'text',
                            null,
                            function ($value) {
                                return is_string($value) || is_array($value) || is_null($value);
                            },
                            false,
                            function ($value) {
                                $jsonParsed = null;
                                $parseJSON = function (string $jsonStr) {
                                    $decoded = json_decode($jsonStr, true);
                                    $decoded = json_last_error() === \JSON_ERROR_NONE  ? $decoded : null;
                                    return $decoded;
                                };
                                if (is_string($value)) {

                                    //Intentar convertir a JSON directamente
                                    $jsonParsed = ($parseJSON)($value);
                                    //Tratar de decodificar Base 64
                                    if ($jsonParsed == null) {
                                        $base64Decoded = url_safe_base64_decode($value);
                                        $jsonParsed = ($parseJSON)($base64Decoded);
                                    }

                                }
                                return $jsonParsed;
                            }
                        ),
                        new Parameter(
                            'from',
                            null,
                            function ($value) {
                                return is_string($value);
                            },
                            false,
                            function ($value) {
                                return $value;
                            }
                        ),
                        new Parameter(
                            'to',
                            null,
                            function ($value) {
                                return is_string($value);
                            },
                            false,
                            function ($value) {
                                return $value;
                            }
                        ),
                    ]);

                    if ($method === 'POST') {
                        $inputValues = $request->getParsedBody();
                    } else {
                        $inputValues = $request->getQueryParams();
                    }
                    $expectedParameters->setInputValues($inputValues);
                    $expectedParameters->validate();

                    /**
                     * @var array<string,string>|null $text
                     * @var string $from
                     * @var string $to
                     */
                    $text = $expectedParameters->getValue('text');
                    $from = $expectedParameters->getValue('from');
                    $to = $expectedParameters->getValue('to');

                    $translationAI = get_config('translationAI');
                    $modelOpenAI = get_config('modelOpenAI');
                    $modelMistral = get_config('modelMistral');
                    $aiHandler = null;

                    if ($translationAI == AI_OPENAI) {
                        $aiHandler = new OpenAIHandlerAdapter(get_config('OpenAIApiKey'), '-', $modelOpenAI);
                    } elseif ($translationAI == AI_MISTRAL) {
                        $aiHandler = new MistralHandlerAdapter(get_config('MistralAIApiKey'), $modelMistral);
                    }

                    $responseJSON = [
                        'success' => false,
                        'message' => '',
                        'result' => [
                            'text' => $text,
                            'from' => $from,
                            'to' => $to,
                            'translation' => null,
                        ],
                        'error' => null,
                        'AI' => [
                            'provider' => $translationAI,
                            'modelOpenAI' => $modelOpenAI,
                            'modelMistral' => $modelMistral,
                        ],
                    ];

                    try {
                        $translation = $aiHandler->translate($text, $from, $to, '/\{(?:[^{}]|(?R))*\}/');
                        if ($translation !== null) {
                            $responseJSON['success'] = true;
                            $responseJSON['result']['translation'] = $translation;
                            $responseJSON['message'] = __(self::LANG_GROUP, 'La traducción se realizó con éxito.');
                        } else {
                            $responseJSON['message'] = __(self::LANG_GROUP, 'No pudo efectuarse la traducción, intente más tarde.');
                        }
                    } catch (\Throwable $e) {
                        $responseJSON['message'] = __(self::LANG_GROUP, 'Ha ocurrido un error con el servicio de traducción, intente más tarde.');
                        $responseJSON['error'] = $e->getMessage();
                    }

                } elseif ($actionType == 'saveGroup') {

                    $expectedParameters = new Parameters([
                        new Parameter(
                            'text',
                            null,
                            function ($value) {
                                return is_string($value) || is_array($value) || is_null($value);
                            },
                            false,
                            function ($value) {
                                $jsonParsed = null;
                                $parseJSON = function (string $jsonStr) {
                                    $decoded = json_decode($jsonStr, true);
                                    $decoded = json_last_error() === \JSON_ERROR_NONE  ? $decoded : null;
                                    return $decoded;
                                };
                                if (is_string($value)) {

                                    //Intentar convertir a JSON directamente
                                    $jsonParsed = ($parseJSON)($value);
                                    //Tratar de decodificar Base 64
                                    if ($jsonParsed == null) {
                                        $base64Decoded = url_safe_base64_decode($value);
                                        $jsonParsed = ($parseJSON)($base64Decoded);
                                    }

                                }
                                return $jsonParsed;
                            }
                        ),
                        new Parameter(
                            'to',
                            null,
                            function ($value) {
                                return is_string($value);
                            },
                            false,
                            function ($value) {
                                return $value;
                            }
                        ),
                        new Parameter(
                            'saveGroup',
                            null,
                            function ($value) {
                                return is_string($value);
                            },
                            false,
                            function ($value) {
                                return $value;
                            }
                        ),
                        new Parameter(
                            'database',
                            false,
                            function ($value) {
                                return is_string($value) || is_bool($value);
                            },
                            true,
                            function ($value) {
                                return $value === 'yes';
                            }
                        ),
                    ]);

                    if ($method === 'POST') {
                        $inputValues = $request->getParsedBody();
                    } else {
                        $inputValues = $request->getQueryParams();
                    }
                    $expectedParameters->setInputValues($inputValues);
                    $expectedParameters->validate();

                    /**
                     * @var array<string,string>|null $text
                     * @var string $to
                     * @var string $saveGroup
                     * @var bool $database
                     */
                    $text = $expectedParameters->getValue('text');
                    $to = $expectedParameters->getValue('to');
                    $saveGroup = $expectedParameters->getValue('saveGroup');
                    $database = $expectedParameters->getValue('database');

                    $responseJSON = [
                        'success' => false,
                        'message' => '',
                        'error' => null,
                    ];

                    if ($isSameDomain) {

                        if ($database) {

                            $responseJSON['success'] = true;
                            $translationsDatabaseName = 'dynamicTranslations';
                            $translationsDatabase = new AppConfigModel($translationsDatabaseName);
                            $translationsDatabase->name = $translationsDatabaseName;
                            $currentTranslationsDatabase = $translationsDatabase->value;
                            $currentTranslationsDatabase = is_string($currentTranslationsDatabase) ? base64_decode($currentTranslationsDatabase) : null;
                            $currentTranslationsDatabase = is_string($currentTranslationsDatabase) ? @json_decode($currentTranslationsDatabase, true) : null;
                            $currentTranslationsDatabase = is_array($currentTranslationsDatabase) ? $currentTranslationsDatabase['data'] : null;

                            $baseTranslationsDatabase = [
                                $to => [
                                    $saveGroup => [],
                                ],
                            ];
                            $currentTranslationsDatabase = $currentTranslationsDatabase !== null ? objectToArray($currentTranslationsDatabase) : $baseTranslationsDatabase;

                            if (!array_key_exists($to, $currentTranslationsDatabase)) {
                                $currentTranslationsDatabase[$to] = [];
                            }
                            if (!array_key_exists($saveGroup, $currentTranslationsDatabase[$to])) {
                                $currentTranslationsDatabase[$to][$saveGroup] = [];
                            }

                            $finalTranslationsDatabase = $currentTranslationsDatabase;
                            $finalTranslationsDatabase[$to][$saveGroup] = array_merge($finalTranslationsDatabase[$to][$saveGroup], $text);
                            $translationsDatabase->value = base64_encode(json_encode([
                                'data' => $finalTranslationsDatabase,
                                'updated' => date('Y-m-d H:i:s'),
                            ], \JSON_UNESCAPED_UNICODE  | \JSON_UNESCAPED_SLASHES));

                            if ($translationsDatabase->id !== null) {
                                $translationsDatabase->update();
                            } else {
                                $translationsDatabase->save();
                            }

                        } else {

                            if (is_string($saveGroup) && mb_strlen($saveGroup) > 0) {

                                $responseJSON['success'] = true;
                                $langDynamicTranslationsPath = basepath('app/lang/dynamic-translations/');
                                $langDynamicTranslationsLangPath = append_to_path_system($langDynamicTranslationsPath, $to);
                                $langDynamicTranslationsDirectoryExists = file_exists($langDynamicTranslationsPath);
                                $langDynamicTranslationsLangDirectoryExists = file_exists($langDynamicTranslationsLangPath);

                                if (!$langDynamicTranslationsDirectoryExists) {
                                    mkdir($langDynamicTranslationsPath, 0777, true);
                                }
                                if (!$langDynamicTranslationsLangDirectoryExists) {
                                    mkdir($langDynamicTranslationsLangPath, 0777, true);
                                }

                                $langDynamicTranslationsLangGroupPath = append_to_path_system($langDynamicTranslationsLangPath, "{$saveGroup}.php");
                                $langDynamicTranslationsLangGroupFileExists = file_exists($langDynamicTranslationsLangGroupPath);
                                $arrayLangVariableName = "\$dynamicTranslation_{$saveGroup}";

                                if (!$langDynamicTranslationsLangGroupFileExists) {
                                    file_put_contents($langDynamicTranslationsLangGroupPath, [
                                        "<?php\n",
                                        "{$arrayLangVariableName} = [];\n\n",
                                        "return {$arrayLangVariableName};\n",
                                    ]);
                                    chmod($langDynamicTranslationsLangGroupPath, 0777);
                                }

                                $currentTranslations = include $langDynamicTranslationsLangGroupPath;
                                $translatedTexts = [];
                                $langDynamicTranslationsLangGroupPathContent = file_get_contents($langDynamicTranslationsLangGroupPath);
                                foreach ($text as $originalText => $translatedText) {
                                    $originalText = str_replace('"', '\\"', $originalText);
                                    $translatedText = str_replace('"', '\\"', $translatedText);
                                    if (!array_key_exists($originalText, $currentTranslations)) {
                                        $translatedTexts[] = "{$arrayLangVariableName}[\"{$originalText}\"] = \"{$translatedText}\";";
                                    }
                                }

                                $translatedTexts = implode("\n", $translatedTexts);
                                $langDynamicTranslationsLangGroupPathContent = str_replace("return {$arrayLangVariableName};", "{$translatedTexts}\n\nreturn {$arrayLangVariableName};", $langDynamicTranslationsLangGroupPathContent);
                                file_put_contents($langDynamicTranslationsLangGroupPath, $langDynamicTranslationsLangGroupPathContent);

                            }

                        }

                    } else {
                        throw new NotFoundException($request, $response);
                    }

                }

                return $response->withJson($responseJSON);

            } else {
                throw new NotFoundException($request, $response);
            }
        } catch (MissingRequiredParamaterException $e) {
            $responseJSON['success'] = false;
            $responseJSON['error'] = $e->getMessage();
            $response = $response->withJson($responseJSON);
        }
        return $response;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function usersActions(Request $request, Response $response)
    {

        $actionType = $request->getAttribute('actionType');
        $method = mb_strtoupper($request->getMethod());

        if ($actionType == 'register') {

            if ($method !== 'POST') {
                throw new NotFoundException($request, $response);
            }

            //Acciones de REGISTRO EXTERNO PERSONALIZADO - INICIO
            /**
             * Se espera que userType y organizationID pasaran por encriptación con BaseHashEncryption.
             * Si organanizationID resulta como NONE se crea la organización desde organizationName.
             */
            $parsedBody = $request->getParsedBody();
            $equivalences = [
                'type' => [
                    'from' => 'userType',
                    'parse' => function ($value) {
                        return BaseHashEncryption::decryptBidirectionalHash($value);
                    },
                ],
                'firstname' => [
                    'from' => 'firstName',
                    'parse' => function ($value) {
                        return $value;
                    },
                ],
                'secondname' => [
                    'from' => 'secondName',
                    'parse' => function ($value) {
                        return $value;
                    },
                ],
                'first_lastname' => [
                    'from' => 'firstLastName',
                    'parse' => function ($value) {
                        return $value;
                    },
                ],
                'second_lastname' => [
                    'from' => 'secondLastName',
                    'parse' => function ($value) {
                        return $value;
                    },
                ],
                'password' => [
                    'from' => 'password',
                    'parse' => function ($value) {
                        return $value;
                    },
                ],
                'password2' => [
                    'from' => 'passwordConfirm',
                    'parse' => function ($value) {
                        return $value;
                    },
                ],
                'username' => [
                    'from' => 'email',
                    'parse' => function ($value) {
                        return $value;
                    },
                ],
                'email' => [
                    'from' => 'email',
                    'parse' => function ($value) {
                        return $value;
                    },
                ],
                'organization' => [
                    'from' => 'organizationID',
                    'parse' => function ($value) {
                        $value = mb_strlen($value) > 0 ? BaseHashEncryption::decryptBidirectionalHash($value) : null;
                        return $value != 'NONE' ? $value : null;
                    },
                ],
                'organizationID' => [
                    'from' => 'organizationID',
                    'parse' => function ($value) {
                        $value = mb_strlen($value) > 0 ? BaseHashEncryption::decryptBidirectionalHash($value) : null;
                        return $value;
                    },
                ],
                'phoneCode' => [
                    'from' => 'phoneCode',
                    'parse' => function ($value) {
                        return $value;
                    },
                ],
                'phoneNumber' => [
                    'from' => 'phoneNumber',
                    'parse' => function ($value) {
                        return $value;
                    },
                ],
            ];
            foreach ($equivalences as $targetName => $configEquivalence) {
                $fromName = $configEquivalence['from'];
                $fromValue = array_key_exists($fromName, $parsedBody) ? $parsedBody[$fromName] : null;
                if ($fromValue !== null) {
                    $fromValueParsed = $configEquivalence['parse']($fromValue);
                    if ($fromValueParsed !== null) {
                        if (!in_array($fromName, array_keys($equivalences))) {
                            unset($parsedBody[$fromName]);
                        }
                        $parsedBody[$targetName] = $configEquivalence['parse']($fromValue);
                    }
                }
            }
            $organizationID = array_key_exists('organizationID', $parsedBody) ? $parsedBody['organizationID'] : null;
            $organizationName = array_key_exists('organizationName', $parsedBody) ? $parsedBody['organizationName'] : null;
            $organizationCreatedID = null;
            if ($organizationID == 'NONE') {
                if (is_string($organizationName) && mb_strlen($organizationName) > 0) {
                    $requestForOrganization = RequestRouteFactory::createFromGlobals();
                    $requestForOrganization = $requestForOrganization->withParsedBody([
                        'name' => $organizationName,
                        'nit' => mb_strtoupper(uniqid('SIN_INFORMACION_')),
                    ]);
                    $controllerOrganization = new OrganizationsController();
                    $reponseOrganization = $controllerOrganization->action($requestForOrganization, (new Response())->withStatus(200, ''));
                    $arrayBodyResponseOrganization = json_decode($reponseOrganization->getBody()->__toString(), true);
                    $parsedBody['organization'] = $arrayBodyResponseOrganization['values']['orgID'];
                    $organizationCreatedID = $arrayBodyResponseOrganization['values']['orgID'];
                }
            }
            $request = $request->withParsedBody($parsedBody);
            //Acciones de REGISTRO EXTERNO PERSONALIZADO - FIN

            //username - ESPERADOS
            //email - ESPERADOS
            //password - ESPERADOS
            //password2 - ESPERADOS
            //firstname - ESPERADOS
            //first_lastname - ESPERADOS
            //type
            //status
            $parsedBody = $request->getParsedBody();
            $inputUserType = $request->getParsedBodyParam('type', null);
            $inputUserType = is_string($inputUserType) ? trim($inputUserType) : null;
            $defaultUserType = UsersModel::TYPE_USER_GENERAL;
            $availableUserTypes = [
                UsersModel::TYPE_USER_ADMIN_ORG,
                UsersModel::TYPE_USER_GENERAL,
            ];
            $defaultOrganizationByType = [
                UsersModel::TYPE_USER_GENERAL => OrganizationMapper::INITIAL_ID_GLOBAL,
            ];
            $userType = in_array($inputUserType, $availableUserTypes) ? $inputUserType : $defaultUserType;
            $parsedBody['type'] = (string) $userType;
            $parsedBody['status'] = (string) UsersModel::STATUS_USER_APPROVED_PENDING;

            //Asignación automática de organización si no hay una definida. Según tipo de usuario.
            if (!array_key_exists('organization', $parsedBody)) {
                if (array_key_exists($parsedBody['type'], $defaultOrganizationByType)) {
                    $parsedBody['organization'] = $defaultOrganizationByType[$parsedBody['type']];
                }
            }

            $request = $request->withParsedBody($parsedBody);

            $controller = new UsersController();
            $response = $controller->register($request, $response);

            //Acciones de REGISTRO EXTERNO PERSONALIZADO - INICIO
            $creationSuccess = json_decode($response->getBody()->__toString(), true)['success'];
            $username = $request->getParsedBodyParam('username');
            $phoneCode = $request->getParsedBodyParam('phoneCode');
            $phoneNumber = $request->getParsedBodyParam('phoneNumber');
            if ($creationSuccess) {
                $userByUsername = UsersModel::getBy($username, 'username', [], new UserDataPackage(1), true);
                if ($userByUsername !== null) {
                    $profileUser = UserProfileMapper::getProfile($userByUsername->id);
                    if ($profileUser !== null) {
                        $profileUser->phoneCode = $phoneCode;
                        $profileUser->phoneNumber = $phoneNumber;
                        $profileUser->update();
                    }
                    if ($organizationCreatedID !== null) {
                        $mapperOrg = new OrganizationMapper($organizationCreatedID);
                        $mapperOrg->administrator = $userByUsername->id;
                        $mapperOrg->update();
                    }

                    //Envío de correo - INICIO
                    $message = strReplaceTemplate(__(self::LANG_GROUP, "Sr(a). {NAME}, le informamos que su usuario ha sido creado y está a la espera de aprobación. De momento puede iniciar sesión y completar su perfil para agilizar el proceso de aprobación."), [
                        '{NAME}' => $userByUsername->getFullName(),
                    ]);
                    $mailer = new Mailer();
                    $mailConfig = new MailConfig;
                    $subject = __(self::LANG_GROUP, 'Aprobaciones');
                    set_title($subject);
                    $subject = get_title(true);
                    $mailer->setFrom($mailConfig->user(), $mailConfig->name());
                    $mailer->addAddress($userByUsername->email, $userByUsername->getFullName());
                    $mailer->isHTML(true);
                    $mailer->Subject = mb_convert_encoding($subject, 'UTF-8');
                    $data = [];
                    $data['text'] = mb_convert_encoding($message, 'UTF-8');
                    $data['url'] = get_route('admin');
                    $data['text_button'] = __(self::LANG_GROUP, 'Iniciar sesión');
                    $mailer->Body = $this->helpController->render('mailing/template_base', $data, false, false);
                    if (!$mailer->checkSettedSMTP()) {
                        $mailer->asGoDaddy();
                    }
                    $mailer->send();
                    //Envío de correo - FIN

                }
            }
            //Acciones de REGISTRO EXTERNO PERSONALIZADO - FIN

        } elseif ($actionType == 'edit') {

            if ($method !== 'POST') {
                throw new NotFoundException($request, $response);
            }

            $currentUser = getLoggedFrameworkUser();

            //Verificar que la sesión está activa
            if ($currentUser !== null) {

                //id - OBLIGATORIO
                //username
                //email
                //is_profile
                //current-password
                //password
                //password2
                //firstname
                //first_lastname
                $parsedBody = $request->getParsedBody();
                $userID = (int) $request->getParsedBodyParam('id', null);

                $availableParams = [
                    'username',
                    'email',
                    'firstname',
                    'first_lastname',
                ];

                $isSameUser = $userID == $currentUser->id;
                $userMapper = null;

                if ($isSameUser) {

                    if (Validator::isInteger($userID)) {

                        $userMapper = new UsersModel($userID);
                        $parsedBody['is_profile'] = true;

                        if ($userMapper->id !== null) {

                            $availableFields = array_merge(array_keys($userMapper->getFields()), array_keys($userMapper->getMetaProperties()));

                            foreach ($availableParams as $paramName) {
                                if (!array_key_exists($paramName, $parsedBody) && in_array($paramName, $availableFields) && $paramName !== 'meta') {
                                    $specialBehaviour = [
                                        'TEST' => function ($mapper) {
                                            return '';
                                        },
                                    ];
                                    $parsedBody[$paramName] = array_key_exists($paramName, $specialBehaviour) ? ($specialBehaviour[$paramName])($userMapper) : $userMapper->$paramName;
                                }
                            }
                        }
                    } else {
                        return throw403($request, [
                            'line' => __LINE__,
                            'file' => __FILE__,
                        ]);
                    }

                    $request = $request->withParsedBody($parsedBody);

                    $controller = new UsersController();
                    $response = $controller->edit($request, $response);

                    $arrayBodyResponse = json_decode($response->getBody()->__toString(), true);

                    if ($arrayBodyResponse['success'] && $userMapper !== null && $userMapper->id !== null) {
                        LogsMapper::addLog(LogsMapper::MSG_UPDATE_PROFILE, [
                            '%username%' => $userMapper->username,
                        ], 'id', $userMapper->id, UsersModel::TABLE);
                    }

                } else {
                    return throw403($request, [
                        'line' => __LINE__,
                        'file' => __FILE__,
                    ]);
                }

            } else {
                return throw403($request, [
                    'line' => __LINE__,
                    'file' => __FILE__,
                ]);
            }
        } elseif ($actionType == 'profile-image') {

            if ($method !== 'POST') {
                throw new NotFoundException($request, $response);
            }

            $currentUser = getLoggedFrameworkUser();

            //Verificar que la sesión está activa
            if ($currentUser !== null) {

                //id - OBLIGATORIO
                $parsedBody = $request->getParsedBody();
                $userID = (int) $request->getParsedBodyParam('id', null);

                $isSameUser = $userID == $currentUser->id;
                $userMapper = null;

                if ($isSameUser) {

                    if (Validator::isInteger($userID)) {

                        $userMapper = new UsersModel($userID);

                        if ($userMapper->id !== null) {

                            $parsedBody['user_id'] = $userID;
                        } else {
                            return throw403($request, [
                                'line' => __LINE__,
                                'file' => __FILE__,
                            ]);
                        }
                    } else {
                        return throw403($request, [
                            'line' => __LINE__,
                            'file' => __FILE__,
                        ]);
                    }

                    $request = $request->withParsedBody($parsedBody);

                    $controller = new AvatarController();
                    $response = $controller->register($request, $response);

                    $arrayBodyResponse = json_decode($response->getBody()->__toString(), true);

                    if ($arrayBodyResponse['success'] && $userMapper !== null && $userMapper->id !== null) {
                        LogsMapper::addLog(LogsMapper::MSG_UPDATE_PROFILE_IMAGE, [
                            '%username%' => $userMapper->username,
                        ], 'id', $userMapper->id, UsersModel::TABLE);
                    }

                } else {
                    return throw403($request, [
                        'line' => __LINE__,
                        'file' => __FILE__,
                    ]);
                }
            } else {
                return throw403($request, [
                    'line' => __LINE__,
                    'file' => __FILE__,
                ]);
            }
        } elseif ($actionType == 'get-data-user') {

            if ($method !== 'GET') {
                throw new NotFoundException($request, $response);
            }

            $currentUser = getLoggedFrameworkUser();

            //Verificar que la sesión está activa
            if ($currentUser !== null) {

                //id - OBLIGATORIO
                $parsedBody = $request->getParsedBody();
                $userID = (int) $request->getQueryParam('id', null);

                $isSameUser = $userID == $currentUser->id;

                if ($isSameUser) {

                    $userMapper = new UsersModel($userID);

                    if ($userMapper->id !== null) {

                        $userLoginData = $userMapper->humanReadable();

                        $avatar = AvatarModel::getAvatar($userMapper->id);
                        $userLoginData['misc'] = [
                            'avatar' => $avatar,
                        ];
                        unset($userLoginData['password']);
                        unset($userLoginData['meta']);

                        foreach ($userLoginData as $k => $i) {
                            if (strpos($k, 'META:') !== false) {
                                unset($userLoginData[$k]);
                                $userLoginData['misc'][str_replace('META:', '', $k)] = $i;
                            }
                        }

                        $response = $response->withJson([
                            'userData' => $userLoginData,
                        ]);
                    } else {
                        return throw403($request, [
                            'line' => __LINE__,
                            'file' => __FILE__,
                        ]);
                    }
                } else {
                    return throw403($request, [
                        'line' => __LINE__,
                        'file' => __FILE__,
                    ]);
                }
            } else {
                return throw403($request, [
                    'line' => __LINE__,
                    'file' => __FILE__,
                ]);
            }
        } elseif ($actionType == 'recovery-password') {

            if ($method !== 'POST') {
                throw new NotFoundException($request, $response);
            }

            $controller = new RecoveryPasswordController();

            //Parámetros
            $params = $request->getParsedBody();

            //Conjunto posible de datos para autenticación
            $requerido = [
                'username',
            ];

            //Verificar que el grupo de datos para solicitados esté completo
            $parametros_ok = require_keys($requerido, $params) === true && count($requerido) === count($params);

            //Cuerpo de la respuesta
            $json_response = [
                'send_mail' => false,
                'error' => UserProblemsController::NO_ERROR,
                'message' => '',
            ];
            $usuario = null;

            //Si los parámetros son válidos en nombre y en cantidad se inicia el proceso de recuperación
            if ($parametros_ok) {

                //Se selecciona un elemento que concuerde con el usuario
                $username = $params['username'];

                $usuario = $controller->userMapper->getWhere([
                    'username' => [
                        '=' => $username,
                        'and_or' => 'OR',
                    ],
                    'email' => [
                        '=' => $username,
                    ],
                ]);

                //Verificación de existencia
                if ($usuario !== null) {

                    //Datos de recuperación
                    $recoveryPassword = new RecoveryPasswordModel();
                    $recoveryPassword->created = new \DateTime();
                    $recoveryPassword->expired = $recoveryPassword->created->modify('+24 hour');
                    $recoveryPassword->email = $usuario->email;
                    $recoveryPassword->code = generate_code(6);
                    $recoveryPassword->save();

                    //Envío de correo de recuperación
                    $json_response['send_mail'] = $controller->mailRecoveryPasswordCode($recoveryPassword->code, $usuario, true);
                    $json_response['message'] = __(RecoveryPasswordController::LANG_GROUP, 'Se ha enviado un mensaje al correo proporcionado.');

                    $logRequest = new TicketsLogModel();
                    $logRequest->created = $recoveryPassword->created;
                    $logRequest->email = $recoveryPassword->email;
                    $logRequest->information = [
                        'code' => $recoveryPassword->code,
                        'email_sended' => $json_response['send_mail'],
                        'ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0',
                    ];
                    $logRequest->type = (string) __(self::LANG_GROUP, 'Solicitud de restablecimiento de contraseña.');
                    $logRequest->save();
                } else {

                    $json_response['error'] = RecoveryPasswordController::USER_NO_EXISTS;
                    $json_response['message'] = vsprintf($controller->getMessage($json_response['error']), [$username]);
                }
            } else {

                $json_response['error'] = RecoveryPasswordController::MISSING_OR_UNEXPECTED_PARAMS;
                $json_response['message'] = $controller->getMessage($json_response['error']);
            }

            $response = $response->withJson($json_response);

            if ($usuario !== null) {
                LogsMapper::addLog(LogsMapper::MSG_REQUEST_PASSWORD_RECOVERY, [
                    '%username%' => $usuario->username,
                ], 'id', $usuario->id, UsersModel::TABLE);
            }

        } elseif ($actionType == 'change-password-code') {

            if ($method !== 'POST') {
                throw new NotFoundException($request, $response);
            }

            //code
            //password
            //repassword
            $parsedBody = $request->getParsedBody();
            $request = $request->withParsedBody($parsedBody);

            $controller = new RecoveryPasswordController();
            $response = $controller->newPasswordCreateCode($request, $response, []);

            $arrayBodyResponse = json_decode($response->getBody()->__toString(), true);

            if ($arrayBodyResponse['success'] && $arrayBodyResponse['user'] !== null) {
                LogsMapper::addLog(LogsMapper::MSG_PASSWORD_RECOVERY_BY_CODE, [
                    '%username%' => $arrayBodyResponse['user']['username'],
                ], 'id', $arrayBodyResponse['user']['id'], UsersModel::TABLE);
            }
        }

        return $response;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function cronJobs(Request $request, Response $response)
    {

        /**
         * Ejemplo para programar cron job:
         * Contenido: curl -X GET -H "Cron-Job-Key: LLAVE" https://domain.tld/core/api/cron-jobs/run
         * Tiempo: 0 * * * *
         */
        $actionType = $request->getAttribute('actionType');
        $method = mb_strtoupper($request->getMethod());

        $CronJobKey = get_config('CronJobKey');
        $cronJobKeyOnRequest = $request->getHeaderLine('Cron-Job-Key');
        $cronJobKeyIsValid = $cronJobKeyOnRequest === $CronJobKey;

        if (!$cronJobKeyIsValid) {
            return throw403($request, [
                'line' => __LINE__,
                'file' => __FILE__,
            ]);
        }

        $currentUser = getLoggedFrameworkUser();
        $isLogged = $currentUser !== null;
        $responseJSON = [
            'TasksRuns' => [],
        ];

        if ($actionType == 'run') {

            if ($method !== 'GET') {
                throw new NotFoundException($request, $response);
            }

            $responseJSON['TasksRuns']["CheckWorking"] = true;

        } else {
            throw new NotFoundException($request, $response);
        }

        return $response->withJson($responseJSON);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function externalActions(Request $request, Response $response)
    {

        $context = $request->getAttribute('context');
        $actionType = $request->getAttribute('actionType');
        $method = mb_strtoupper($request->getMethod());

        $apiHandler = new APIExternalAdapterExample('user@domain.tld', '123456');
        $hasValidToken = $apiHandler->verifyCurrentAuthToken();
        if (!$hasValidToken) {
            $apiHandler->login();
        }

        if ($context == 'context') {

            if ($actionType == 'action') {

                if ($method !== 'GET') {
                    throw new NotFoundException($request, $response);
                }

                $responseJSON = [];
                $response = $response->withJson($responseJSON);

            } else {
                throw new NotFoundException($request, $response);
            }

        } else {
            throw new NotFoundException($request, $response);
        }

        return $response;
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

        $publications = $allRoles;

        $news = $allRoles;

        $translations = $allRoles;

        $usersActions = $allRoles;

        $cronJobs = $allRoles;

        $externalActions = $allRoles;

        $other = [
            UsersModel::TYPE_USER_ROOT,
            UsersModel::TYPE_USER_ADMIN_GRAL,
        ];

        $routes = [];
        $routesGeneral = [

            //──── GET|POST ───────────────────────────────────────────────────────────────────────────────
            //JSON
            new Route( //Rutas de publicaciones
                "{$startRoute}/publications/{context}/{actionType}[/]",
                $classname . ':publications',
                self::$baseRouteName . '-publications-actions',
                'GET',
                true,
                null,
                $publications,
            ),
            new Route( //Rutas de noticias
                "{$startRoute}/news/{actionType}[/]",
                $classname . ':news',
                self::$baseRouteName . '-news-actions',
                'GET',
                true,
                null,
                $news,
            ),
            new Route( //Acciones sobre el módulo de usuarios
                "{$startRoute}/cron-jobs/{actionType}[/]",
                $classname . ':cronJobs',
                self::$baseRouteName . '-cron-jobs',
                'GET|POST',
                false,
                null,
                $cronJobs,
            ),
            //new Route( //Acciones sobre API externa (muestra)
            //    "{$startRoute}/external/{context}/{actionType}[/]",
            //    $classname . ':externalActions',
            //    self::$baseRouteName . '-external-actions',
            //    'GET|POST',
            //    false,
            //    null,
            //    $externalActions,
            //),

        ];
        $routesUsers = [

            //──── GET|POST ───────────────────────────────────────────────────────────────────────────────
            //JSON
            new Route( //Acciones sobre el módulo de usuarios
                "{$startRoute}/users/{actionType}[/]",
                $classname . ':usersActions',
                self::$baseRouteName . '-users-actions',
                'GET|POST',
                false,
                null,
                $usersActions,
            ),

        ];
        $routesTranslation = [

            //──── GET|POST ───────────────────────────────────────────────────────────────────────────────
            //JSON
            new Route( //Rutas de traducciones (corta)
                "{$startRoute}/translations[/]",
                $classname . ':translations',
                self::$baseRouteName . '-translations-actions',
                'GET|POST',
                true,
                null,
                $translations,
                [
                    'actionType' => 'translate',
                ]
            ),
            new Route( //Rutas de traducciones (larga)
                "{$startRoute}/translations/{actionType}[/]",
                $classname . ':translations',
                self::$baseRouteName . '-translations-actions-full',
                'GET|POST',
                true,
                null,
                $translations
            ),

        ];

        if (APIRoutes::ENABLE) {
            $routes = array_merge($routes, $routesGeneral);
        }

        if (APIRoutes::ENABLE_TRANSLATIONS) {
            $routes = array_merge($routes, $routesTranslation);
        }

        if (APIRoutes::ENABLE_USERS) {
            $routes = array_merge($routes, $routesUsers);
        }

        if (APIRoutes::ENABLE || APIRoutes::ENABLE_TRANSLATIONS || APIRoutes::ENABLE_USERS) {
            $group->register($routes);

            $group->addMiddleware(function (\PiecesPHP\Core\Routing\RequestRoute $request, $handler) {
                return (new DefaultAccessControlModules(self::$baseRouteName . '-', function (string $name, array $params) {
                    return self::routeName($name, $params);
                }))->getResponse($request, $handler);
            });
        }

        return $group;
    }
}
