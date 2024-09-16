<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\UserSystem\Authentication\OTPHandler;
use PiecesPHP\UserSystem\Controllers\UserSystemFeaturesController;

/**
 * @var string $langGroup
 * @var string $editLink
 */
$currentUser = getLoggedFrameworkUser();
$isEnabled2FA = OTPHandler::isEnabled2FA();
$wasViewedQRData = OTPHandler::wasViewedCurrentUserQRData();
$totpData = $currentUser->TOTPData;
$totpAlias = $totpData->twoAuthFactorAlias !== null ? $totpData->twoAuthFactorAlias : get_config('owner');
$username = $currentUser->username;
if($isEnabled2FA && !$wasViewedQRData){
    //Si está activado pero no confirmado, se revierte el proceso porque ya se ha refrescado la página
    OTPHandler::toggleCurrentUser2AF(false, '');
    header("Refresh:0");
}
?>
<section class="module-view-container">

    <div class="home-hello-section-title">
        <div class="title"><?= __($langGroup, 'Seguridad de usuario'); ?></div>
        <div class="subtitle"><?= $subtitle; ?></div>
    </div>

    <div class="tabs-controls">
        <div class="active" data-tab="A"><?= __($langGroup, 'Autenticación de dos factores (con app TOTP)'); ?></div>
    </div>

    <div class="container-standard-form">

        <div class="ui tab tab-element active" data-tab="A">

            <p class="explanation"><?= __($langGroup, 'Cada vez que desactive o active la autenticación de doble factor deberá volver a escanear el código. Esto funciona con aplicaciones como <strong>Google Authenticator</strong>.'); ?></p>

            <br>

            <?php if(!$wasViewedQRData): ?>
            <div class="ui form mw-400" qr-activation-container style="display: none;">

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
                        <input type="hidden" name="username" value="<?= $username; ?>">

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
            <?php endif; ?>

            <br>

            <form action="<?= UserSystemFeaturesController::routeName('configure-totp'); ?>" class="ui form mw-400" configure-2af>

                <?php if(!$isEnabled2FA): ?>
                <div class="ui header dividing medium"><?= __($langGroup, 'Activar 2FA'); ?></div>
                <input type="hidden" name="enable" value="yes">
                <?php else: ?>
                <div class="ui header dividing medium"><?= __($langGroup, 'Desactivar de 2FA'); ?></div>
                <?php endif; ?>

                <?php if(!$isEnabled2FA): ?>
                <div class="field required">
                    <label><?= __($langGroup, 'Título de la clave (para identificarla en la app)'); ?></label>
                    <input type="text" name="issuerName" value="<?= $totpAlias; ?>" required>
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

                <div class="field">
                    <button type="submit" class="ui button green small"><?= __($langGroup, 'Configurar'); ?></button>
                </div>

            </form>

        </div>

    </div>

</section>
