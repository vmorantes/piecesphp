<?php

//El orden de las claves: la real de las claves seguras, luego la de prueba de config.php, luego nada (falla cerrada).
//Ninguna comprobación sale a la red: solo se mira qué clave elige secretKey().

use GoogleReCaptchaV3\Controllers\GoogleReCaptchaV3Controller;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/recaptcha-keys', function ($args) {

    echoTerminal("\e[33m[TEST:RecaptchaKeys] reCAPTCHA v3 elige la clave real, luego la de prueba, y sin ninguna rechaza\e[39m");
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name, string $detail = '') use (&$passed, &$failed): void {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}" . ($detail !== '' ? " — {$detail}" : ''));
        }
    };

    $realOriginal = get_config('GoogleReCaptchaV3SecretKey');
    $pruebaOriginal = get_config('GoogleReCaptchaV3TestSecretKey');

    try {
        echoTerminal('[1/1] GoogleReCaptchaV3Controller::secretKey()');
        set_config('GoogleReCaptchaV3SecretKey', '');
        set_config('GoogleReCaptchaV3TestSecretKey', '');
        $usada = GoogleReCaptchaV3Controller::secretKey();
        $check($usada === null, "q1'. sin la real ni la de prueba, secretKey() da null", var_export($usada, true));
        $check(GoogleReCaptchaV3Controller::verifyTokenCaptcha('zz-token-sin-claves') === false, "q1'. sin claves, verifyTokenCaptcha() rechaza");

        set_config('GoogleReCaptchaV3TestSecretKey', 'zz-secreta-de-prueba-ficticia');
        $usada = GoogleReCaptchaV3Controller::secretKey();
        $check($usada === 'zz-secreta-de-prueba-ficticia', 'q3. sin la real, secretKey() usa la de prueba', var_export($usada, true));

        set_config('GoogleReCaptchaV3SecretKey', 'zz-secreta-real-ficticia');
        $usada = GoogleReCaptchaV3Controller::secretKey();
        $check($usada === 'zz-secreta-real-ficticia', 'q4. con las dos, secretKey() usa la real', var_export($usada, true));
    } catch (\Throwable $exception) {
        $check(false, 'secretKey() sin excepciones', get_class($exception) . ': ' . $exception->getMessage());
    } finally {
        set_config('GoogleReCaptchaV3SecretKey', $realOriginal === false ? null : $realOriginal);
        set_config('GoogleReCaptchaV3TestSecretKey', $pruebaOriginal === false ? null : $pruebaOriginal);
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('reCAPTCHA v3 elige la clave real, luego la de prueba, y sin ninguna rechaza.')->setEffects([CliActions::EFFECT_NONE])->register();
