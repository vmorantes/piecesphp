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
 * @var string $indexLink
 * @var string $probeURL
 * @var string $testSendURL
 * @var string $currentEmail Por omisión, el correo de prueba va al del usuario.
 * @var array<string,string> $mailProtocols SettingsController::mailProtocolOptions(): valor => texto, en su orden.
 * @var string $mailProtocolSaved El protocolo guardado, tal cual.
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $helpController El que pinta las piezas del panel.
 */
?>
<section class="module-view-container email-view">

    <?php
        //La cabecera del diseño de contenidos (diseno/02-configuracion.md §2.1).
        $helpController->_render('panel/built-in/content/page-header.php', [
            'title' => $title,
            'description' => $description,
        ]);

        //Los interruptores van dentro del campo al que modifican.
        $toggle = function (string $inputName, string $label, bool $checked) use ($helpController): string {
            ob_start();
            $helpController->_render('panel/built-in/content/toggle-field.php', ['inputName' => $inputName, 'label' => $label, 'checked' => $checked, 'id' => "email-{$inputName}"]);
            return (string) ob_get_clean();
        };
        //«Quitar la guardada» (P317): solo si hay una guardada; marcada, se guarda vacía.
        $passwordSaved = mb_strlen((string) $element->password()) > 0;
        $clearPassword = $passwordSaved
            ? '<div class="field"><div class="ui checkbox"><input type="checkbox" id="email-clear-password" name="clear_password" value="1" data-clear-saved><label for="email-clear-password">' . escape_html(__($langGroup, 'Quitar la guardada')) . '</label></div></div>'
            : '';
    ?>

    <div class="ui secondary pointing menu tabs-controls content-tabs">
        <a class="active item" data-tab="mail-config"><i class="cog icon" aria-hidden="true"></i><?= __($langGroup, 'Configuración'); ?></a>
        <a class="item" data-tab="mail-tests"><i class="vial icon" aria-hidden="true"></i><?= __($langGroup, 'Pruebas'); ?></a>
    </div>

    <div class="ui active tab" data-tab="mail-config">

        <form action="<?= $actionURL; ?>" method="POST" class="ui form initial email segment" data-content-form>

            <section aria-labelledby="email-server-title">
                <?php
                    $helpController->_render('panel/built-in/content/section-header.php', [
                        'id' => 'email-server-title',
                        'title' => __($langGroup, 'Servidor'),
                    ]);
                ?>
                <div class="three fields">
                    <div class="required field">
                        <label for="email-host"><?= __($langGroup, 'Host'); ?></label>
                        <input type="text" id="email-host" name="host" value="<?= escape_html((string) $element->host()); ?>" required>
                    </div>
                    <div class="required field">
                        <label for="email-protocol"><?= __($langGroup, 'Protocolo'); ?></label>
                        <?php
                            //Los valores y los textos, del controlador. «Ninguno» va el último: un primer option vacío sería el
                            //marcador del select y `required` lo rechazaría. Un alias guardado («STARTTLS») sale como el suyo.
                            $normalizedProtocol = \PiecesPHP\Settings\Controllers\SettingsController::normalizeMailProtocol($mailProtocolSaved);
                            $knownProtocol = $normalizedProtocol !== null;
                            $currentProtocol = $mailProtocolSaved;
                            $protocols = $mailProtocols;
                        ?>
                        <select class="ui fluid selection dropdown" id="email-protocol" name="protocol" required data-no-placeholder>
                            <?php foreach ($protocols as $protocolValue => $protocolText): ?>
                            <option value="<?= $protocolValue; ?>" <?= $knownProtocol && $normalizedProtocol === (string) $protocolValue ? 'selected' : ''; ?>><?= $protocolText; ?></option>
                            <?php endforeach; ?>
                            <?php if (!$knownProtocol): ?>
                            <?php //Un texto libre de antes: se enseña tal cual y no se acepta al guardar, para que se elija uno bueno. ?>
                            <option value="<?= escape_html($currentProtocol); ?>" selected><?= escape_html($currentProtocol); ?> <?= __($langGroup, '(valor actual, no válido)'); ?></option>
                            <?php endif; ?>
                        </select>
                        <?= $toggle('auto_tls', __($langGroup, 'Auto TLS'), (bool) $element->autoTls()); ?>
                    </div>
                    <div class="required field">
                        <label for="email-port"><?= __($langGroup, 'Puerto'); ?></label>
                        <input type="number" id="email-port" name="port" min="1" max="65535" value="<?= escape_html((string) $element->port()); ?>" required>
                    </div>
                </div>
                <div class="two fields">
                    <div class="field">
                        <label for="email-user"><?= __($langGroup, 'Correo electrónico'); ?></label>
                        <input type="text" id="email-user" name="user" value="<?= escape_html((string) $element->user()); ?>">
                    </div>
                    <?php
                        //La contraseña no viaja al HTML: el campo va vacío y, vacío, al guardar la conserva.
                        $helpController->_render('panel/built-in/content/password-field.php', [
                            'id' => 'email-password',
                            'inputName' => 'password',
                            'value' => '',
                            'label' => __($langGroup, 'Contraseña'),
                            'placeholder' => $passwordSaved ? __($langGroup, 'Guardada: vacío la conserva') : '',
                            'autocomplete' => 'new-password',
                            'fieldExtra' => $clearPassword . $toggle('auth', __($langGroup, 'Autenticar'), (bool) $element->auth()),
                        ]);
                    ?>
                </div>
            </section>

            <div class="ui section divider"></div>

            <section aria-labelledby="email-delivery-title">
                <?php
                    $helpController->_render('panel/built-in/content/section-header.php', [
                        'id' => 'email-delivery-title',
                        'title' => __($langGroup, 'Entrega del correo y sumidero de pruebas'),
                    ]);
                ?>
                <div class="three fields" data-mail-test-mode>
                    <div class="required field">
                        <label for="email-delivery"><?= __($langGroup, 'Entrega del correo'); ?></label>
                        <select class="ui fluid selection dropdown" id="email-delivery" name="mail_delivery" required>
                            <?php foreach ([
                                    MailDelivery::AUTO => __($langGroup, 'Según el entorno (sin declarar, se retiene)'),
                                    MailDelivery::SINK => __($langGroup, 'Retenido: no sale de esta máquina'),
                                    MailDelivery::REAL => __($langGroup, 'Real: sale por el SMTP de arriba'),
                            ] as $deliveryValue => $deliveryText): ?>
                            <option value="<?= $deliveryValue; ?>" <?= MailDelivery::declared() === $deliveryValue ? 'selected' : ''; ?>><?= $deliveryText; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="required field" data-mail-test-target>
                        <label for="email-test-host"><?= __($langGroup, 'Servidor de pruebas'); ?></label>
                        <input type="text" id="email-test-host" name="test_host" value="<?= escape_html((string) $element->testHost()); ?>" required>
                    </div>
                    <div class="required field" data-mail-test-target>
                        <label for="email-test-port"><?= __($langGroup, 'Puerto de pruebas'); ?></label>
                        <input type="number" id="email-test-port" name="test_port" min="1" max="65535" value="<?= escape_html((string) $element->testPort()); ?>" required>
                    </div>
                </div>
                <?php //Dentro de `ui form`, Fomantic oculta los mensajes que no llevan `visible`. ?>
                <div class="ui visible info icon message">
                    <i class="info circle icon" aria-hidden="true"></i>
                    <div class="content"><p><?= __($langGroup, 'Lo que decide si el correo sale es «Entrega del correo». Retenido, ninguno sale al exterior: todos van al servidor de pruebas.'); ?></p></div>
                </div>
            </section>

            <div class="ui section divider"></div>

            <section aria-labelledby="email-extra-title">
                <?php
                    $helpController->_render('panel/built-in/content/section-header.php', [
                        'id' => 'email-extra-title',
                        'title' => __($langGroup, 'Información adicional'),
                    ]);
                ?>
                <div class="field">
                    <label for="email-name"><?= __($langGroup, 'Nombre del remitente'); ?></label>
                    <input type="text" id="email-name" name="name" value="<?= escape_html((string) $element->name()); ?>">
                </div>
            </section>

            <?php
                //La barra al final del formulario (§2.3).
                $helpController->_render('panel/built-in/content/form-action-bar.php', [
                    'saveText' => __($langGroup, 'Guardar'),
                    'secondaryActions' => [['text' => __($langGroup, 'Cancelar'), 'url' => $indexLink, 'icon' => 'times']],
                ]);
            ?>

        </form>

    </div>

    <div class="ui tab" data-tab="mail-tests">

        <?php //Las pruebas usan lo GUARDADO, no lo que haya en el formulario; y dicen a dónde fueron de verdad (ADR 0046). ?>
        <section class="ui form segment" aria-label="<?= escape_html(__($langGroup, 'Probar el correo')); ?>" data-mail-tests data-probe-url="<?= escape_html($probeURL); ?>" data-test-send-url="<?= escape_html($testSendURL); ?>">
            <div class="ui visible warning icon message">
                <i class="exclamation triangle icon" aria-hidden="true"></i>
                <div class="content"><p><?= __($langGroup, 'Las pruebas usan la configuración guardada. Si cambiaste algo en Configuración, guárdalo antes de probar.'); ?></p></div>
            </div>
            <div class="fields">
                <div class="seven wide field">
                    <label for="mail-test-to"><?= __($langGroup, 'Destinatario del correo de prueba'); ?></label>
                    <input type="email" id="mail-test-to" name="to" value="<?= escape_html($currentEmail); ?>" data-mail-test-to>
                </div>
            </div>
            <div class="ui stackable flex">
                <button type="button" class="ui basic labeled icon button" data-mail-probe><i class="plug icon" aria-hidden="true"></i><?= __($langGroup, 'Probar conexión'); ?></button>
                <button type="button" class="ui basic labeled icon button" data-mail-test-send><i class="paper plane icon" aria-hidden="true"></i><?= __($langGroup, 'Enviar correo de prueba'); ?></button>
            </div>
            <div class="ui message" data-mail-test-result hidden>
                <div class="header" data-mail-test-message></div>
                <p data-mail-test-route></p>
            </div>
        </section>

    </div>

</section>
