<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\UserSystem\Authentication\OTPHandler;
use MySpace\Controllers\MySpaceController;
use PiecesPHP\UserSystem\Controllers\UserSystemFeaturesController;
use PiecesPHP\UserSystem\Controllers\UserSessionsController;

/**
 * @var string $langGroup
 * @var string $title
 * @var string $description
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 * @var \MySpace\Controllers\HelperController $helpController El que pinta las piezas del panel.
 */
$currentUser = getLoggedFrameworkUserOrFail();
$isEnabled2FA = OTPHandler::isEnabled2FA();
$wasViewedQRData = OTPHandler::wasViewedCurrentUserQRData();
$totpData = $currentUser->TOTPData;
$totpAlias = $totpData !== null && $totpData->twoAuthFactorAlias !== null ? $totpData->twoAuthFactorAlias : get_config('owner');
$username = $currentUser->username;
//Durante una suplantación no se ofrece: serían las sesiones del suplantado, no las de root.
$canRevokeMySessions = !MySpaceController::isImpersonating((int) $currentUser->id);
if($isEnabled2FA && !$wasViewedQRData){
    //Si está activado pero no confirmado, se revierte el proceso porque ya se ha refrescado la página
    OTPHandler::toggleCurrentUser2AF(false, '');
    header("Refresh:0");
}
?>
<section class="module-view-container">

    <?php
    $helpController->_render('panel/built-in/content/page-header.php', [
        'breadcrumbs' => $breadcrumbs,
        'title' => $title,
        'description' => $description,
    ]);
    ?>

    <div class="tabs-controls content-tabs">
        <div class="active" data-tab="A"><?= __($langGroup, 'Autenticación de dos factores (con app TOTP)'); ?></div>
        <?php if($canRevokeMySessions): ?>
        <div data-tab="B"><?= __($langGroup, 'Mis sesiones'); ?></div>
        <?php endif; ?>
    </div>

    <?php //Cada pestaña con su caja; la de configurar, con su barra aparte, como el resto de formularios del panel. ?>
    <div class="ui tab tab-element active" data-tab="A">

            <?php if(!$wasViewedQRData): ?>
            <div class="container-standard-form" qr-activation-container style="display: none;">
            <div class="ui form mw-400">

                <div class="field">
                    <label><?= __($langGroup, 'QR'); ?></label>
                    <span qr-container data-qr-url="<?= UserSystemFeaturesController::routeName('get-current-totp-qr-data'); ?>" data-activate-url="<?= UserSystemFeaturesController::routeName('mark-current-totp-qr-as-viewed'); ?>"></span>
                </div>

                <div class="field">
                    <label><?= __($langGroup, 'Código de seguridad'); ?></label>
                    <div>
                        <small><?= __($langGroup, 'Debe guardarlo en un lugar seguro, esto lo necesitará si pierde el acceso a su aplicación de 2FA'); ?></small>
                    </div>
                    <br>
                    <span security-code></span>
                </div>

                <div class="field">

                    <form class="field" action="<?= UserSystemFeaturesController::routeName('check-totp'); ?>" method="POST" totp>

                        <label><?= __($langGroup, 'Comprobar código TOTP de la aplicación'); ?></label>
                        <div>
                            <small><?= __($langGroup, 'Es recomendable que pruebe que su aplicación de autenticación está generando correctamente los códigos antes de confirmar.'); ?></small>
                        </div>
                        <br>
                        <input type="hidden" name="username" value="<?= escape_html($username); ?>">

                        <div class="two fields">
                            <div class="field">
                                <input type="text" name="totp" required value="" placeholder="">
                            </div>
                            <div class="field">
                                <button class="ui button small green" type="submit"><?= __($langGroup, 'Comprobar'); ?></button>
                            </div>
                        </div>

                    </form>

                </div>

                <div class="field">
                    <button type="submit" class="ui button blue small" activate-do><?= __($langGroup, 'Confirmar'); ?></button>
                </div>

            </div>
            </div>
            <?php endif; ?>

            <form action="<?= UserSystemFeaturesController::routeName('configure-totp'); ?>" class="ui form initial" configure-2af data-content-form>

            <div class="container-standard-form">

            <p class="explanation"><?= __($langGroup, 'Cada vez que desactive o active la autenticación de doble factor deberá volver a escanear el código. Esto funciona con aplicaciones como <strong>Google Authenticator</strong>.'); ?></p>

            <br>

            <div class="mw-400">

                <?php if(!$isEnabled2FA): ?>
                <div class="ui header dividing medium"><?= __($langGroup, 'Activar 2FA'); ?></div>
                <input type="hidden" name="enable" value="yes">
                <?php else: ?>
                <div class="ui header dividing medium"><?= __($langGroup, 'Desactivar de 2FA'); ?></div>
                <?php endif; ?>

                <?php if(!$isEnabled2FA): ?>
                <div class="field required">
                    <label><?= __($langGroup, 'Título de la clave (para identificarla en la app)'); ?></label>
                    <input type="text" name="issuerName" value="<?= escape_html($totpAlias); ?>" required>
                </div>
                <?php endif; ?>

                <?php if($isEnabled2FA): ?>
                <div class="field required">
                    <label><?= __($langGroup, 'Código TOTP (el que muestra su aplicación de 2FA) o Código de seguridad'); ?></label>
                    <input type="text" name="totp" required placeholder="">
                </div>
                <?php endif; ?>

                <div class="field required">
                    <label><?= __($langGroup, 'Contraseña actual'); ?></label>
                    <input type="password" name="password" required placeholder="">
                </div>

            </div>

            </div>

            <?php
            //Lo mismo activa que desactiva: el formulario lo decide por sus campos.
            $helpController->_render('panel/built-in/content/form-action-bar.php', [
                'saveText' => __($langGroup, 'Configurar'),
            ]);
            ?>

            </form>

    </div>

    <?php if($canRevokeMySessions): ?>
    <div class="ui tab tab-element" data-tab="B">

        <div class="container-standard-form">

            <p class="explanation"><?= __($langGroup, 'Los dispositivos donde has iniciado sesión. Cierra la que no reconozcas.'); ?></p>

            <?= UserSessionsController::userSessionsHTML((int) $currentUser->id, true); ?>

        </div>

    </div>
    <?php endif; ?>

</section>
