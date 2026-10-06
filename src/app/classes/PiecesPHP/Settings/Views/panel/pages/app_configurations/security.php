<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 * @var string $actionURL
 */
?>

<main class="security-view">
    <section class="main-body-header">
        <div class="head">
            <h2 class="tittle"><?= __($langGroup, 'Seguridad'); ?></h2>
            <span class="sub-tittle"><?= __($langGroup, 'Sistema'); ?></span>
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

                    <div class="field">
                        <div class="ui toggle checkbox">
                            <input type="checkbox" name="hide_app_key_warning" <?= get_config('hide_app_key_warning') === true ? 'checked' : ''; ?>>
                            <label><?= __($langGroup, 'Ocultar en el panel el aviso de app_key de relleno'); ?></label>
                        </div>
                    </div>

                </div>

                <div class="save-button">
                    <button type="submit" class="ui button primary"><?= __($langGroup, 'Guardar'); ?></button>
                </div>

            </form>
        </div>
    </section>
</main>
