<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use MySpace\Controllers\MySpaceController;
use PiecesPHP\UserSystem\Authentication\OTPHandler;
use PiecesPHP\UserSystem\Controllers\UserSessionsController;
use PiecesPHP\UserSystem\Controllers\UserSystemFeaturesController;

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
if ($isEnabled2FA && !$wasViewedQRData) {
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

    <div class="ui secondary pointing menu tabs-controls content-tabs">
        <div class="active item" data-tab="A"><?= __($langGroup, 'Autenticación de dos factores (con app TOTP)'); ?></div>
        <?php if($canRevokeMySessions): ?>
        <div class="item" data-tab="B"><?= __($langGroup, 'Mis sesiones'); ?></div>
        <?php endif; ?>
    </div>

    <div class="ui tab tab-element active" data-tab="A">

        <?php if(!$wasViewedQRData): ?>
        <div class="ui form segment" qr-activation-container hidden>

            <div class="field">
                <label><?= __($langGroup, 'QR'); ?></label>
                <span qr-container data-qr-url="<?= UserSystemFeaturesController::routeName('get-current-totp-qr-data'); ?>" data-activate-url="<?= UserSystemFeaturesController::routeName('mark-current-totp-qr-as-viewed'); ?>"></span>
            </div>

            <div class="field">
                <label><?= __($langGroup, 'Código de seguridad'); ?></label>
                <div class="description"><?= __($langGroup, 'Debe guardarlo en un lugar seguro, esto lo necesitará si pierde el acceso a su aplicación de 2FA'); ?></div>
                <span security-code></span>
            </div>

            <div class="field">

                <form class="field" action="<?= UserSystemFeaturesController::routeName('check-totp'); ?>" method="POST" totp>

                    <label><?= __($langGroup, 'Comprobar código TOTP de la aplicación'); ?></label>
                    <div class="description"><?= __($langGroup, 'Es recomendable que pruebe que su aplicación de autenticación está generando correctamente los códigos antes de confirmar.'); ?></div>
                    <input type="hidden" name="username" value="<?= escape_html($username); ?>">

                    <div class="two fields">
                        <div class="field">
                            <input type="text" name="totp" required value="" placeholder="">
                        </div>
                        <div class="field">
                            <button class="ui basic button" type="submit"><?= __($langGroup, 'Comprobar'); ?></button>
                        </div>
                    </div>

                </form>

            </div>

            <div class="field">
                <button type="submit" class="ui primary button" activate-do><?= __($langGroup, 'Confirmar'); ?></button>
            </div>

        </div>
        <?php endif; ?>

        <form action="<?= UserSystemFeaturesController::routeName('configure-totp'); ?>" class="ui form initial segment" configure-2af data-content-form>

            <?php
                $helpController->_render('panel/built-in/content/section-header.php', [
                    'title' => $isEnabled2FA ? __($langGroup, 'Desactivar de 2FA') : __($langGroup, 'Activar 2FA'),
                    'description' => __($langGroup, 'Cada vez que desactive o active la autenticación de doble factor deberá volver a escanear el código. Esto funciona con aplicaciones como Google Authenticator.'),
                ]);
            ?>

            <?php if(!$isEnabled2FA): ?>
            <input type="hidden" name="enable" value="yes">
            <div class="two fields">
                <div class="field required">
                    <label><?= __($langGroup, 'Título de la clave (para identificarla en la app)'); ?></label>
                    <input type="text" name="issuerName" value="<?= escape_html($totpAlias); ?>" required>
                </div>
            </div>
            <?php else: ?>
            <div class="two fields">
                <div class="field required">
                    <label><?= __($langGroup, 'Código TOTP (el que muestra su aplicación de 2FA) o Código de seguridad'); ?></label>
                    <input type="text" name="totp" required placeholder="">
                </div>
            </div>
            <?php endif; ?>

            <div class="two fields">
                <div class="field required">
                    <label><?= __($langGroup, 'Contraseña actual'); ?></label>
                    <input type="password" name="password" required placeholder="">
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

        <div class="ui segment">

            <?php $helpController->_render('panel/built-in/content/section-header.php', ['title' => __($langGroup, 'Mis sesiones'), 'description' => __($langGroup, 'Los dispositivos donde has iniciado sesión. Cierra la que no reconozcas.')]); ?>

            <?= UserSessionsController::userSessionsHTML((int) $currentUser->id, true); ?>

        </div>

    </div>
    <?php endif; ?>

</section>
