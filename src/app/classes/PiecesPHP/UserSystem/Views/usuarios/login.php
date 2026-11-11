<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\UserSystem\Controllers\UserSystemFeaturesController;
?>
<!DOCTYPE html>
<html lang="<?= get_config('app_lang'); ?>" dlang="<?= get_config('default_lang'); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="front-configurations" value="<?= base64EncodeOrDefault(jsonEncodeFallbackNull(get_front_configurations()), '[]'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <base href="<?= baseurl(); ?>">
    <?php \PiecesPHP\Core\Utilities\Helpers\MetaTags::setRobots('noindex, nofollow'); ?>
    <?= \PiecesPHP\Core\Utilities\Helpers\MetaTags::getMetaTagsGeneric(); ?>
    <link rel="shortcut icon" href="<?= add_cache_stamp_to_url(get_config('favicon-back')); ?>" type="image/x-icon">
    <?php load_css(['base_url' => "", 'custom_url' => ""]) ?>
    <?php //Sin ExtraScripts a propósito: un script de terceros aquí leería la contraseña mientras se teclea. ?>
</head>

<body>

    <?php
        $accessBackgrounds = get_config('backgrounds');
        $accessBackgrounds = is_array($accessBackgrounds) ? $accessBackgrounds : [];
        ob_start();
    ?>

    <?php
        $loginLang = USER_LOGIN_LANG_GROUP;
        $loginText = fn(string $text): string => escape_html(__($loginLang, $text));
        $codeMinutes = \PiecesPHP\UserSystem\Authentication\OTPRateLimiter::config()['oneUseCodeMinutes'];
        $codeDigits = 6;
    ?>
    <div class="login-form">

        <h1 class="ui large header">
            <?= __($loginLang, 'Iniciar sesión'); ?>
            <div class="sub header" data-login-subtitle data-text-password="<?= $loginText('Ingresa con tu cuenta para continuar.'); ?>" data-text-code="<?= $loginText('Ingresa el código que te enviamos para continuar.'); ?>"><?= __($loginLang, 'Ingresa con tu cuenta para continuar.'); ?></div>
        </h1>

        <?php //Los textos del JavaScript salen de aquí (data-text-*): las traducciones de translations/*.js van compiladas. ?>
        <form login-form-js last-uri='<?= htmlspecialchars((string) $requested_uri, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>' class="ui form initial" novalidate data-otp-url="<?= escape_html(UserSystemFeaturesController::routeName('generate-otp')); ?>" data-code-minutes="<?= (int) $codeMinutes; ?>" data-text-required="<?= $loginText('Este campo es obligatorio.'); ?>" data-text-code-required="<?= $loginText('Escribe las seis cifras del código.'); ?>" data-text-code-invalid-title="<?= $loginText('El código no es válido o ya venció.'); ?>" data-text-code-invalid="<?= $loginText('Revísalo o solicita uno nuevo.'); ?>" data-text-wait="<?= $loginText('Podrá intentarlo de nuevo en ${time}.'); ?>" data-text-code-error-title="<?= $loginText('No pudimos enviar el código.'); ?>" data-text-sent-to="<?= $loginText('Usuario o correo: ${target}'); ?>" data-text-expires="<?= escape_html(strReplaceTemplate(__($loginLang, 'El código vence en ${minutes} minutos.'), ['${minutes}' => (string) $codeMinutes])); ?>" data-text-resend-in="<?= $loginText('Reenviar en ${time}'); ?>" data-text-resend="<?= $loginText('Reenviar código'); ?>">

            <div class="ui fluid two item secondary menu" role="tablist" aria-label="<?= $loginText('Método de ingreso'); ?>" data-login-methods>
                <a class="active item" data-tab="login-password" id="login-tab-password" role="tab" aria-selected="true" aria-controls="login-panel-password" tabindex="0"><i class="lock icon" aria-hidden="true"></i><?= __($loginLang, 'Contraseña'); ?></a>
                <a class="item" data-tab="login-code" id="login-tab-code" role="tab" aria-selected="false" aria-controls="login-panel-code" tabindex="-1"><i class="key icon" aria-hidden="true"></i><?= __($loginLang, 'Código de acceso'); ?></a>
            </div>

            <?php //El error, dentro del formulario y arriba; el formulario sigue a la vista (`ui form` lo enseña con la clase `error`). ?>
            <div class="ui error message" role="alert" data-login-error>
                <div class="header" data-login-error-title></div>
                <p data-login-error-text></p>
                <p data-login-error-help><?= __($loginLang, 'Si continúa con problemas para ingresar, por favor utilice la ayuda.'); ?></p>
            </div>

            <div class="ui active tab" data-tab="login-password" id="login-panel-password" role="tabpanel" aria-labelledby="login-tab-password">

                <div class="field">
                    <label for="login-username"><?= __($loginLang, 'Usuario o correo'); ?></label>
                    <div class="ui left icon input">
                        <input type="text" id="login-username" required name='username' autocomplete="username" placeholder="<?= $loginText('correo@dominio.com'); ?>">
                        <i class="user icon" aria-hidden="true"></i>
                    </div>
                </div>

                <div class="field">
                    <label for="login-password"><?= __($loginLang, 'Contraseña'); ?></label>
                    <div class="ui left icon action input">
                        <input type="password" id="login-password" required name='password' autocomplete="current-password" placeholder="••••••••">
                        <i class="lock icon" aria-hidden="true"></i>
                        <button type="button" class="ui icon button" aria-pressed="false" aria-controls="login-password" aria-label="<?= $loginText('Mostrar contraseña'); ?>" data-show-text="<?= $loginText('Mostrar contraseña'); ?>" data-hide-text="<?= $loginText('Ocultar contraseña'); ?>" data-login-eye><i class="eye icon" aria-hidden="true"></i></button>
                    </div>
                </div>

                <div class="field">
                    <div class="ui checkbox remember-me">
                        <input type="checkbox" id="login-remember">
                        <label for="login-remember"><?= __($loginLang, 'Recordarme'); ?></label>
                    </div>
                </div>

            </div>

            <div class="ui tab" data-tab="login-code" id="login-panel-code" role="tabpanel" aria-labelledby="login-tab-code">

                <div data-login-code-step="1">
                    <div class="field">
                        <label for="login-code-username"><?= __($loginLang, 'Usuario o correo'); ?></label>
                        <div class="ui left icon input">
                            <input type="text" id="login-code-username" autocomplete="username" placeholder="<?= $loginText('correo@dominio.com'); ?>">
                            <i class="user icon" aria-hidden="true"></i>
                        </div>
                        <div class="description"><?= escape_html(strReplaceTemplate(__($loginLang, 'Te enviaremos un código de ${digits} cifras al correo de tu cuenta. Vence en ${minutes} minutos.'), ['${digits}' => (string) $codeDigits, '${minutes}' => (string) $codeMinutes])); ?></div>
                    </div>
                    <button type="button" class="ui fluid primary button" data-login-code-send><?= __($loginLang, 'Enviar código'); ?></button>
                </div>

                <div data-login-code-step="2" hidden>
                    <button type="button" class="ui basic compact labeled icon button" data-login-code-change><i class="chevron left icon" aria-hidden="true"></i><?= __($loginLang, 'Cambiar'); ?></button>
                    <?php //Dentro de `ui form`, Fomantic oculta los mensajes que no llevan `visible`. ?>
                    <div class="ui visible info message" data-login-code-sent>
                        <p data-login-code-sent-message></p>
                        <p><b data-login-code-sent-target></b> · <span data-login-code-sent-expires></span></p>
                    </div>
                    <div class="field" data-login-code-field>
                        <label id="login-code-label"><?= __($loginLang, 'Código de acceso'); ?></label>
                        <div class="unstackable six fields" role="group" aria-labelledby="login-code-label" data-login-code-boxes>
                            <?php for ($digit = 1; $digit <= $codeDigits; $digit++): ?>
                            <div class="field"><div class="ui large code input"><input type="text" inputmode="numeric" maxlength="1" pattern="[0-9]"<?= $digit === 1 ? ' autocomplete="one-time-code"' : ' autocomplete="off"'; ?> aria-label="<?= escape_html(strReplaceTemplate(__($loginLang, 'Dígito ${n}'), ['${n}' => (string) $digit])); ?>"></div></div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <p>
                        <small class="ui muted text"><?= __($loginLang, '¿No te llegó?'); ?></small>
                        <button type="button" class="ui basic compact button" data-login-code-resend disabled></button>
                    </p>
                </div>

            </div>

            <?php //El segundo factor de aplicación, en los dos métodos: se pide tras el usuario si la cuenta lo tiene. ?>
            <div class="field" twofa-field>
                <label for="login-two-factor"><?= __($loginLang, 'Código temporal de aplicación'); ?></label>
                <input type="text" id="login-two-factor" name='twoFactor' autocomplete="one-time-code" inputmode="numeric" placeholder="000000">
            </div>

            <div class="field" data-login-submit>
                <button type="submit" class="ui fluid primary button" data-text-password="<?= $loginText('Ingresar'); ?>" data-text-code="<?= $loginText('Verificar e ingresar'); ?>"><?= __($loginLang, 'Ingresar'); ?></button>
            </div>

        </form>

        <div class="ui hidden divider"></div>

        <div class="ui center aligned container problems-button">
            <small><?= strReplaceTemplate(__($loginLang, 'NEED_HELP_TO_LOGIN'), [
    '${ot}' => "<a href='" . \PiecesPHP\UserSystem\Controllers\UserProblemsController::routeName('problems-list') . "'>",
    '${ct}' => "</a>",
]); ?></small>
        </div>

    </div>

    <?php $accessContent = (string) ob_get_clean(); require __DIR__ . '/inc/access-frame.php'; ?>

    <?php load_js(['base_url' => "", 'custom_url' => ""]) ?>

</body>

</html>
