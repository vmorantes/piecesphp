<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\Core\ConfigHelpers\MailConfig;
/**
 * @var MailConfig $element
 */
?>


<main class="container-email">

    <section class="main-body-header">
        <div class="head">
            <h2 class="tittle"><?= __($langGroup, 'Configuración de emails'); ?></h2>
            <span class="sub-tittle"><?= __($langGroup, 'Configuración de Plataforma'); ?></span>
        </div>
        <div class="body-card">
            <form action="<?= $actionURL; ?>" method="POST" class="ui form email">

                <div class="fields">

                    <div class="field">
                        <div class="ui toggle checkbox">
                            <input type="checkbox" name="auto_tls" <?= $element->autoTls() ? 'checked' : ''; ?>>
                            <label><?= __($langGroup, 'Auto TLS'); ?></label>
                        </div>
                    </div>

                    <div class="field">
                        <div class="ui toggle checkbox">
                            <input type="checkbox" name="auth" <?= $element->auth() ? 'checked' : ''; ?>>
                            <label><?= __($langGroup, 'Autenticar'); ?></label>
                        </div>
                    </div>

                </div>

                <div class="ui divider"></div>

                <div class="fields three">

                    <div class="field required">
                        <label><?= __($langGroup, 'Host'); ?></label>
                        <input type="text" name="host" value="<?= $element->host(); ?>" required>
                    </div>

                    <div class="field required">
                        <label><?= __($langGroup, 'Protocolo'); ?></label>
                        <input type="text" name="protocol" value="<?= $element->protocol(); ?>" required>
                    </div>

                    <div class="field required">
                        <label><?= __($langGroup, 'Puerto'); ?></label>
                        <input type="text" name="port" value="<?= $element->port(); ?>" required>
                    </div>

                </div>

                <div class="ui divider"></div>

                <div class="fields two">

                    <div class="field">
                        <label><?= __($langGroup, 'Correo electrónico'); ?></label>
                        <input type="text" name="user" value="<?= $element->user(); ?>">
                    </div>

                    <div class="field">
                        <label><?= __($langGroup, 'Contraseña'); ?></label>
                        <div class="ui icon input" show-hide-password-event>
                            <input type="password" name="password" value="<?= htmlentities($element->password()); ?>">
                            <i class="inverted circular eye link icon"></i>
                        </div>
                    </div>

                </div>

                <div class="ui divider"></div>

                <div class="title-tag"><?= __($langGroup, 'Información adicional'); ?></div>

                <div class="field">
                    <label><?= __($langGroup, 'Nombre del remitente'); ?></label>
                    <input type="text" name="name" value="<?= $element->name(); ?>">
                </div>

                <div style="width: 100%; text-align: right">
                    <button style="border-radius: 8px !important;" type="reset" class="ui grey basic button"><?= __($langGroup, 'Cancelar'); ?></button>
                    <button style="border-radius: 8px !important;" type="submit" class="ui primary button"><?= __($langGroup, 'Guardar'); ?></button>
                </div>

            </form>
        </div>
    </section>

</main>
