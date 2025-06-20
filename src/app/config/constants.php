<?php

/**
 * constants.php
 */

/**
 * Constantes globales
 * En este este archivo se puede añadir cualquier constante adicional.
 * Puede hacerse uso de todas las funciones del sistema.
 */

//Errores
define('LOG_ERRORS_PATH', app_basepath('logs'));
define('LOG_ERRORS_BACKUP_PATH', app_basepath('logs/olds'));

//Configuraciones
define('APP_CONFIGURATION_MODULE', true);
define('CONNECT_AS_ANOTHER_USER_ID_COOKIE_NAME', 'asUserID');
define('CONNECT_AS_ANOTHER_USER_ID_GET_PARAM_NAME', 'asUser');
define('ROOT_ORIGINAL_ID_CONFIG_NAME', 'RootOriginalID');
define('ROOT_ID_AS_CONNECT_CONFIG_NAME', 'RootIsLoggedAsUser');

//Zona administrativa
define('ADMIN_PATH_VIEWS', 'panel');

//Importadores
define('IMPORTS_MODULE_ENABLED', true);

//Mensajería
define('MESSAGES_ENABLED', true);
define('MESSAGES_PATH_VIEWS', 'messages');
define('MESSAGES_PATH_STATICS', 'statics/features/messages');
define('MESSAGES_PATH_JS', MESSAGES_PATH_STATICS . '/js');
define('REFRESH_MESSAGES_STATUS', false);

//Módulo de imágenes integrado
define('PIECES_PHP_DYNAMIC_IMAGES_ENABLE', true);

//Ubicaciones
define('LOCATIONS_ENABLED', true);
define('LOCATIONS_LANG_GROUP', 'locationBackend');

//Temporizadores
define('ACTIVE_TIMER', false);

//Gestor de archivos
define('FILE_MANAGER_MODULE', true);

//Publicaciones
define('PUBLICATIONS_MODULE', true);

//Noticias
define('NEWS_MODULE', true);

//Repositorio de imágenes
define('IMAGES_REPOSITORY', true);

//Módulo de documentos
define('DOCUMENTS_MODULE_ENABLE', true);

//Formularios
define('FORMS_MODULE_ENABLE', true);
define('FORMS_MODULE_CATEGORIES_ENABLE', true);
define('FORMS_MODULE_DOCUMENTS_TYPES_ENABLE', true);

//Módulo de presentaciones para capacitaciones tipo diapositiva
define('APP_PRESENTATIONS_ENABLE', false);

//Personas
define('PERSONS_MODULE', true);

//Registro de eventos
define('EVENTS_LOG_MODULE', true);

//Listado de suscriptores
define('NEWSLETTER_MODULE', true);

//Traducciones por API
define('API_TRANSLATION_MODULE', true);

//Inteligencia artificial
define('AI_OPENAI', 'OpenAI');
define('AI_MISTRAL', 'Mistral');
define('TRANSLATION_AI_LIST', [
    AI_OPENAI => 'OpenAI',
    AI_MISTRAL => 'Mistral',
]);
define('AI_MODELS', [
    AI_OPENAI => [
        'gpt-3.5-turbo' => 'gpt-3.5-turbo',
        'gpt-3.5-turbo-instruct' => 'gpt-3.5-turbo-instruct',
        'gpt-4' => 'gpt-4',
        'gpt-4-turbo' => 'gpt-4-turbo',
        'gpt-4o' => 'gpt-4o',
        'gpt-3.5-turbo-0125' => 'gpt-3.5-turbo-0125',
    ],
    AI_MISTRAL => [
        'mistral-tiny' => 'mistral-tiny',
        'mistral-small' => 'mistral-small',
        'mistral-medium' => 'mistral-medium',
        'mistral-large' => 'mistral-large',
        'mixtral-8x7b' => 'mixtral-8x7b',
        'open-mixtral-8x22b' => 'open-mixtral-8x22b',
    ],
]);

//API
define('API_MODULE', false);
define('API_USERS', false);
define('API_REPORTS', false);

//Organizaciones
define('ORGANIZATIONS_MODULE', true);

//Aprobaciones
define('SYSTEM_APPROVALS_MODULE', true);

//Otras
define('ADMIN_AREA_PATH_JS', 'statics/admin-area/js');

//Traducciones
define('ADMIN_MENU_LANG_GROUP', 'sidebarAdminZone');
define('SUPPORT_FORM_ADMIN_LANG_GROUP', 'supportFormAdminZone');
define('CROPPER_ADAPTER_LANG_GROUP', 'cropper');
define('LOGIN_REPORT_LANG_GROUP', 'loginReport');
define('MAIL_TEMPLATES_LANG_GROUP', 'mailTemplates');
define('USER_LOGIN_LANG_GROUP', 'userLogin');
define('GENERAL_LANG_GROUP', 'general');
define('LANG_GROUP', 'public');
define('MAILING_GENERAL_LANG_GROUP', 'mailingGeneral');

//Proyecto
define('GLOBAL_LANG_GROUP', 'global');
define('PHONE_AREA_CODES', [
    'Francia' => '+33',
    'Colombia' => '+57',
]);
define('NATIONALITIES', [
    'Francesa' => 'Francesa',
    'Colombiana' => 'Colombiana',
    'Otras nacionalidad' => 'Otra nacionalidad',
]);
define('CURRENCIES', [
    'EUR' => 'Euro (EUR)',
    'COP' => 'Peso colombiano (COP)',
    'USD' => 'Dólar estadounidense (USD)',
]);
