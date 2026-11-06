<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * @var string $langGroup
 * @var string $title
 * @var string $description
 * @var array<int,array{module:string,mails:array<int,array{key:string,name:string,trigger:string,recipients:string,subject:string,body:string,error:string|null}>}> $groups
 * @var bool $canSendTest
 * @var string $cannotSendReason
 * @var string $sendTestURL
 * @var string $currentEmail
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $helpController
 */
$escape = fn($value): string => htmlspecialchars(is_scalar($value) ? (string) $value : '', ENT_QUOTES, 'UTF-8');
$anchor = fn(string $key): string => 'mail-catalog-' . $key;
?>
<section class="module-view-container mail-catalog-view" data-mail-catalog data-send-test-url="<?= $escape($sendTestURL); ?>">

    <?php
        $helpController->_render('panel/built-in/content/page-header.php', [
            'title' => $title,
            'description' => $description,
        ]);
    ?>

    <?php if ($canSendTest): ?>
    <section class="ui form segment" aria-label="<?= $escape(__($langGroup, 'Enviar prueba')); ?>">
        <div class="ui visible info message">
            <p><?= $escape(__($langGroup, 'La entrega del correo está retenida: «Enviar prueba» manda el correo con sus datos de muestra al destinatario de abajo, pero por el servidor de pruebas de Configuración › Sistema › Correo, no por la entrega real.')); ?></p>
        </div>
        <div class="fields">
            <div class="seven wide field">
                <label for="mail-catalog-to"><?= __($langGroup, 'Destinatario del correo de prueba'); ?></label>
                <input type="email" id="mail-catalog-to" name="to" value="<?= $escape($currentEmail); ?>" data-mail-catalog-to>
            </div>
        </div>
    </section>
    <?php else: ?>
    <div class="ui warning message">
        <p><?= $escape($cannotSendReason); ?></p>
    </div>
    <?php endif; ?>

    <div class="ui stackable grid">

        <div class="four wide computer sixteen wide tablet column">
            <?php
                $helpController->_render('panel/built-in/content/section-nav.php', [
                    'label' => __($langGroup, 'Correos del sitio'),
                    'jumpLabel' => __($langGroup, 'Ir a un correo'),
                    'items' => array_map(fn(array $group): array => [
                        'label' => $group['module'],
                        'children' => array_map(fn(array $mail): array => ['label' => $mail['name'], 'anchor' => $anchor($mail['key'])], $group['mails']),
                    ], $groups),
                ]);
            ?>
        </div>

        <div class="twelve wide computer sixteen wide tablet column" data-mail-catalog-cards>
            <?php foreach ($groups as $group): ?>
            <?php foreach ($group['mails'] as $mail): ?>
            <?php $headingID = $anchor($mail['key']); ?>
            <section class="ui segment mail-catalog-card" id="<?= $escape($headingID); ?>" aria-labelledby="<?= $escape($headingID); ?>-title" data-mail-catalog-card="<?= $escape($mail['key']); ?>">

                <div class="ui stackable grid">
                    <div class="eleven wide computer ten wide tablet column">
                        <h2 class="ui header" id="<?= $escape($headingID); ?>-title">
                            <i class="envelope outline icon" aria-hidden="true"></i>
                            <div class="content">
                                <?= $escape($mail['name']); ?>
                                <div class="sub header"><?= $escape($mail['trigger']); ?></div>
                            </div>
                        </h2>
                    </div>
                    <div class="five wide computer six wide tablet right aligned column">
                        <?php if ($canSendTest && $mail['error'] === null): ?>
                        <div class="ui stackable buttons">
                            <button type="button" class="ui basic labeled icon button" data-mail-catalog-send="<?= $escape($mail['key']); ?>">
                                <i class="paper plane outline icon" aria-hidden="true"></i>
                                <?= __($langGroup, 'Enviar prueba'); ?>
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="ui relaxed divided list">
                    <div class="item">
                        <div class="header"><?= __($langGroup, 'Módulo'); ?></div>
                        <div class="description"><?= $escape($group['module']); ?></div>
                    </div>
                    <div class="item">
                        <div class="header"><?= __($langGroup, 'A quién'); ?></div>
                        <div class="description"><?= $escape($mail['recipients']); ?></div>
                    </div>
                    <div class="item">
                        <div class="header"><?= __($langGroup, 'Asunto'); ?></div>
                        <div class="description"><?= $mail['error'] === null ? $escape($mail['subject']) : '—'; ?></div>
                    </div>
                    <div class="item">
                        <div class="header"><?= __($langGroup, 'Clave'); ?></div>
                        <div class="description"><code><?= $escape($mail['key']); ?></code></div>
                    </div>
                </div>

                <?php if ($mail['error'] !== null): ?>
                <div class="ui error message" data-mail-catalog-error>
                    <p><?= $escape(sprintf(__($langGroup, 'Este correo no se pudo pintar con sus datos de muestra (referencia %s): el registro de errores dice por qué.'), $mail['error'])); ?></p>
                </div>
                <?php else: ?>
                <?php //Sin allow-scripts el cuerpo no ejecuta nada; allow-same-origin solo deja a la página medir su alto (#2449). ?>
                <iframe class="mail-catalog-preview" sandbox="allow-same-origin" data-mail-catalog-preview title="<?= $escape(sprintf(__($langGroup, 'Vista previa: %s'), $mail['name'])); ?>" srcdoc="<?= $escape($mail['body']); ?>"></iframe>
                <?php endif; ?>

                <?php if (!$canSendTest): ?>
                <p class="ui muted text"><?= $escape(__($langGroup, 'Enviar prueba no está disponible: la entrega del correo no está retenida.')); ?></p>
                <?php endif; ?>

            </section>
            <?php endforeach; ?>
            <?php endforeach; ?>
        </div>

    </div>

</section>
