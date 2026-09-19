<?php

//Varias
$keysToSet = [
    [
        'configName' => 'translationAI',
        'configValue' => AI_OPENAI,
    ],
];

foreach ($keysToSet as $keysToSet) {
    $configName = $keysToSet['configName'];
    $configValue = $keysToSet['configValue'];
    $currentValue = get_config($configName);
    if (!is_string($currentValue) || mb_strlen($currentValue) == 0) {
        set_config($configName, $configValue);
    }
}
