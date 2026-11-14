<?php

/**
 * APIRoutes.php
 */

namespace API;

use API\Adapters\BlobStorageAzureAdapter;
use API\Adapters\SpeechToTextAzureAdapter;
use API\Adapters\SpeechToTextGroqAdapter;
use API\Controllers\APIController;
use PiecesPHP\AI\AICapability;
use PiecesPHP\AI\AICapabilityRegistry;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\MailCatalog\Definitions\ApiUserCreatedMail;
use PiecesPHP\MailCatalog\Definitions\RecoveryPasswordMail;
use PiecesPHP\MailCatalog\MailCatalog;
use PiecesPHP\UserSystem\ORM\UsersModel;

/**
 * APIRoutes.
 *
 * @package     API
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2023
 */
class APIRoutes
{

    /**
     * @var boolean
     */
    private static $init = false;

    const ENABLE = API_MODULE;
    const ENABLE_CRONJOBS = API_CRONJOBS;
    const ENABLE_TRANSLATIONS = API_TRANSLATION_MODULE;
    const ENABLE_USERS = API_USERS;
    const ENABLE_PUBLIC_SIGNUP = API_PUBLIC_SIGNUP;
    const ENABLE_REPORTS = API_REPORTS;

    /**
     * @param RouteGroup $groupAdministration
     * @return RouteGroup[] Con los índices groupAdministration
     */
    public static function routes(RouteGroup $groupAdministration)
    {
        if (self::ENABLE || self::ENABLE_TRANSLATIONS || self::ENABLE_USERS || self::ENABLE_REPORTS || self::ENABLE_CRONJOBS) {

            $groupAdministration = APIController::routes($groupAdministration);

            APILang::injectLang();

            //Los correos de los usuarios por la API (el alta y el código de recuperación), en el catálogo de correos.
            if (self::ENABLE_USERS) {
                MailCatalog::register(new ApiUserCreatedMail(), 'API');
                MailCatalog::register(new RecoveryPasswordMail(true), 'API');
            }

            //La traducción, en el centro de mando de la IA (ADR 0074), con los valores por omisión de hoy.
            if (self::ENABLE_TRANSLATIONS) {
                AICapabilityRegistry::register(new AICapability(
                    'translation',
                    fn(): string => __(APILang::LANG_GROUP, 'Traducción con IA'),
                    AICapability::PROVIDER_CHAT,
                    true,
                    array_keys(UsersModel::TYPES_USERS),
                ));
            }

            \PiecesPHP\Core\Routing\InvocationStrategy::appendBeforeCallMethod(function () {
                self::init();
            });

        }

        //Inicializaciones independientes de la API
        BlobStorageAzureAdapter::init();
        SpeechToTextAzureAdapter::init();
        SpeechToTextGroqAdapter::init();

        return [
            'groupAdministration' => $groupAdministration,
        ];
    }

    /**
     * @return void|null
     */
    public static function init()
    {

        if (!self::$init) {

            $currentUser = getLoggedFrameworkUser();

            if ($currentUser === null) {
                return null;
            }

            $currentUserType = (int) $currentUser->type;

        }

        self::$init = true;

    }

}
