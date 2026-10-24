<?php

defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\Core\ConfigHelpers\MailConfig;
use PiecesPHP\Core\Email\MailDelivery;
/**
 * @var MailConfig $element
 * @var string $langGroup
 * @var string $actionURL
 * @var string $title
 * @var string $description
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 * @var string $indexLink
 * @var string $probeURL
 * @var string $testSendURL
 * @var string $currentEmail Por omisión, el correo de prueba va al del usuario.
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $helpController El que pinta las piezas del panel.
 */
?>
<section class="module-view-container email-view">

    <div class="limiter-content">

        <?php
        //La cabecera del diseño de contenidos (diseno/02-configuracion.md §2.1).
        $helpController->_render('panel/built-in/content/page-header.php', [
            'breadcrumbs' => $breadcrumbs,
            'title' => $title,
            'description' => $description,
        ]);
        ?>

        <form action="<?= $actionURL; ?>" method="POST" class="ui form initial email" data-content-form>

            <div class="container-standard-form">

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
                            <?php //La contraseña no viaja al HTML: el campo va vacío y, vacío, al guardar la conserva. ?>
                            <input type="password" name="password" value="" autocomplete="new-password" placeholder="<?= mb_strlen((string) $element->password()) > 0 ? __($langGroup, 'Hay una contraseña guardada: déjelo vacío para conservarla') : ''; ?>">
                            <i class="inverted circular eye link icon"></i>
                        </div>
                    </div>

                </div>

                <div class="ui divider"></div>

                <div class="form-section-title"><?= __($langGroup, 'Entrega del correo y sumidero de pruebas'); ?></div>

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

                <?php //Dentro de `ui form`, Fomantic oculta los mensajes que no llevan `visible`. ?>
                <div class="ui info visible message">
                    <?= __($langGroup, 'Lo que decide si el correo sale es «Entrega del correo». Retenido, ninguno sale al exterior: todos van al servidor de pruebas.'); ?>
                </div>

                <div class="ui divider"></div>

                <div class="form-section-title"><?= __($langGroup, 'Información adicional'); ?></div>

                <div class="field">
                    <label><?= __($langGroup, 'Nombre del remitente'); ?></label>
                    <input type="text" name="name" value="<?= $element->name(); ?>">
                </div>

            </div>

            <?php
            //La barra al final del formulario (§2.3).
            $helpController->_render('panel/built-in/content/form-action-bar.php', [
                'saveText' => __($langGroup, 'Guardar'),
                'secondaryActions' => [['text' => __($langGroup, 'Cancelar'), 'url' => $indexLink, 'icon' => 'times']],
            ]);
            ?>

        </form>

        <?php //Las pruebas usan lo GUARDADO, no lo que haya en el formulario; y dicen a dónde fueron de verdad (ADR 0046). ?>
        <div class="container-standard-form" data-mail-tests data-probe-url="<?= escape_html($probeURL); ?>" data-test-send-url="<?= escape_html($testSendURL); ?>">
            <div class="form-section-title"><?= __($langGroup, 'Probar el correo'); ?></div>
            <p><?= __($langGroup, 'Las pruebas usan la configuración guardada: guarde antes lo que haya cambiado arriba.'); ?></p>
            <div class="ui form">
                <div class="fields">
                    <div class="eight wide field">
                        <label for="mail-test-to"><?= __($langGroup, 'Destinatario del correo de prueba'); ?></label>
                        <input type="email" id="mail-test-to" name="to" value="<?= escape_html($currentEmail); ?>" data-mail-test-to>
                    </div>
                </div>
            </div>
            <button type="button" class="ui basic button" data-mail-probe><i class="plug icon"></i><?= __($langGroup, 'Probar conexión'); ?></button>
            <button type="button" class="ui basic button" data-mail-test-send><i class="paper plane icon"></i><?= __($langGroup, 'Enviar correo de prueba'); ?></button>
            <div class="ui message" data-mail-test-result hidden>
                <div class="header" data-mail-test-message></div>
                <p data-mail-test-route></p>
            </div>
        </div>

    </div>

</section>
