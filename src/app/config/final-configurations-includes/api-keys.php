<?php

//Api Keys
$keysToSet = [
    [
        'configName' => 'OpenAIApiKey',
        'fileKeyName' => 'openai',
    ],
    [
        'configName' => 'MistralAIApiKey',
        'fileKeyName' => 'mistral',
    ],
];

foreach ($keysToSet as $keysToSet) {
    $configName = $keysToSet['configName'];
    $fileKeyName = $keysToSet['fileKeyName'];
    $currentValue = get_config($configName);
    if (!is_string($currentValue) || mb_strlen($currentValue) == 0) {
        set_config($configName, getKeyFromSecureKeys($fileKeyName));
    }
}
