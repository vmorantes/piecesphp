<?php

//Api Keys
$keysToSet = [
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
];

foreach ($keysToSet as $keysToSet) {
    $directoryKeys = '';
    try {
        //Fallback HestiaCP para plataformas en subdirectorio directo [domain]/public_html/../../private/
        //$directoryKeys = !is_local() ? basepath('../../private') : '';
    } catch (\Throwable $e) {}
    $configName = $keysToSet['configName'] ?? null;
    $fileKeyName = $keysToSet['fileKeyName'] ?? null;
    $override = $keysToSet['override'] ?? false;
    $currentValue = get_config($configName);
    if (!is_null($configName) && !is_null($fileKeyName)) {
        if ((!is_string($currentValue) || mb_strlen($currentValue) == 0) || $override) {
            set_config($configName, getKeyFromSecureKeys($fileKeyName, $directoryKeys));
        }
    }
}
