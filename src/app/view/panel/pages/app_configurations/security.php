<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\Core\ConfigHelpers\MailConfig;
/**
 * @var MailConfig $element
 */
?>

<div class="ui header"><?=  __($langGroup, 'Seguridad'); ?></div>

<div class="container-security">

    <form action="<?= $actionURL; ?>" method="POST" class="ui form security">

        <div class="fields">

            <div class="field">
                <div class="ui toggle checkbox">
                    <input type="checkbox" name="check_aud_on_auth" <?= get_config('check_aud_on_auth') ? 'checked' : ''; ?>>
                    <label><?= __($langGroup, 'Usar IP del usuario para encriptar el token de sesión'); ?></label>
                </div>
            </div>

        </div>

        <div class="field">
            <button type="submit" class="ui button green"><?= __($langGroup, 'Guardar'); ?></button>
        </div>

    </form>

</div>
