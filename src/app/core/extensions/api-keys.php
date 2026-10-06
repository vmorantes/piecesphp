<?php

/**
 * @pcsphp-config framework
 * Qué conviene editar aquí: nada: son las llaves que lee el framework. Las de tus servicios van en config/extensions/api-keys.php.
 */

//Fallback HestiaCP para plataformas en subdirectorio directo [domain]/public_html/../../private/: segundo argumento
//!is_local() ? basepath('../../private') : ''
set_configs_from_secure_keys([
    [
        'configName' => 'OpenAIApiKey',
        'fileKeyName' => 'openai',
        'override' => true,
    ],
    [
        'configName' => 'MistralAIApiKey',
        'fileKeyName' => 'mistral',
        'override' => true,
    ],
    [
        'configName' => 'GroqAPIKey',
        'fileKeyName' => 'groq',
        'override' => true,
    ],
    [
        'configName' => 'CronJobKey',
        'fileKeyName' => 'cronjob',
        'override' => true,
    ],
    [
        'configName' => 'GoogleReCaptchaV3SecretKey',
        'fileKeyName' => 'recaptcha-v3-secret',
        'override' => true,
    ],
    [
        'configName' => 'GoogleReCaptchaV3SiteKey',
        'fileKeyName' => 'recaptcha-v3-site',
        'override' => true,
    ],

]);
