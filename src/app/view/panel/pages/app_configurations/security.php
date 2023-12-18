<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use PiecesPHP\Core\ConfigHelpers\MailConfig;

/**
 * @var MailConfig $element
 */
?>

<main class="seo-view">
    <section class="main-body-header">
        <div class="head">
            <h2 class="tittle"><?= __($langGroup, 'Seguridad'); ?></h2>
            <span class="sub-tittle"><?= __($langGroup, 'Configuración de Plataforma'); ?></span>
        </div>
        <div class="body-card">
            <form action="<?= $actionURL; ?>" method="POST" class="ui form security">

                <div class="fields">

                    <div class="field">
                        <div class="ui toggle checkbox">
                            <input type="checkbox" name="check_aud_on_auth" <?= get_config('check_aud_on_auth') ? 'checked' : ''; ?>>
                            <label><?= __($langGroup, 'Usar IP del usuario para encriptar el token de sesión'); ?></label>
                        </div>
                    </div>

                </div>

                <div style="width: 100%; text-align: end;">
                    <button type="submit" class="ui button primary"><?= __($langGroup, 'Guardar'); ?></button>
                </div>

                <div class="divider"></div>

            </form>
        </div>
    </section>
</main>
