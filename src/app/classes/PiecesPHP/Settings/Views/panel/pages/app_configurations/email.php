<?php

defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\Core\ConfigHelpers\MailConfig;
use PiecesPHP\Core\Email\MailDelivery;
/**
 * @var MailConfig $element
 */
?>


<main class="container-email">

    <section class="main-body-header">
        <div class="head">
            <h2 class="tittle"><?= __($langGroup, 'Correo'); ?></h2>
            <span class="sub-tittle"><?= __($langGroup, 'Integraciones'); ?></span>
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

                <div class="title-tag"><?= __($langGroup, 'Entrega del correo y sumidero de pruebas'); ?></div>

                <div class="fields three" data-mail-test-mode>

                    <div class="field required">
                        <label><?= __($langGroup, 'Entrega del correo'); ?></label>
                        <select class="ui dropdown" name="mail_delivery" required>
                            <?php foreach ([
                                MailDelivery::AUTO => __($langGroup, 'Según el entorno (sin declarar, se retiene)'),
                                MailDelivery::SINK => __($langGroup, 'Retenido: no sale de esta máquina'),
                                MailDelivery::REAL => __($langGroup, 'Real: sale por el SMTP de arriba'),
                            ] as $deliveryValue => $deliveryText): ?>
                                <option value="<?= $deliveryValue; ?>" <?= MailDelivery::declared() === $deliveryValue ? 'selected' : ''; ?>><?= $deliveryText; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field required" data-mail-test-target>
                        <label><?= __($langGroup, 'Servidor de pruebas'); ?></label>
                        <input type="text" name="test_host" value="<?= $element->testHost(); ?>" required>
                    </div>

                    <div class="field required" data-mail-test-target>
                        <label><?= __($langGroup, 'Puerto de pruebas'); ?></label>
                        <input type="text" name="test_port" value="<?= $element->testPort(); ?>" required>
                    </div>

                </div>

                <div class="ui info message">
                    <?= __($langGroup, 'Lo que decide si el correo sale es «Entrega del correo». Retenido, ninguno sale al exterior: todos van al servidor de pruebas.'); ?>
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
