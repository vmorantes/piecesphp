<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * El marco del acceso y de la ayuda (patrón `pagina-acceso`): dos cuerpos, marca y formulario, que se apilan bajo escritorio.
 * La vista captura su cuerpo con `ob_start()` y lo pasa en `$accessContent`. El panel de marca lleva las imágenes de fondo: `bg-js` es el gancho del que elige una.
 *
 * @var string $accessContent
 * @var array<int,string> $accessBackgrounds
 */
$accessLang = USER_LOGIN_LANG_GROUP;
$accessBackgrounds = isset($accessBackgrounds) && is_array($accessBackgrounds) ? array_values($accessBackgrounds) : [];
$accessPartnersText = \PiecesPHP\Settings\Controllers\SettingsController::partnersText();
$accessDeveloper = trim((string) get_config('developer'));
$accessDeveloperLogo = trim((string) get_config('developer_logo'));
$accessDeveloperURL = trim((string) get_config('developer_url'));
$accessDeveloperName = $accessDeveloper === '' ? '' : '<strong>' . escape_html($accessDeveloper) . '</strong>';
if ($accessDeveloperName !== '' && $accessDeveloperURL !== '') {
    $accessDeveloperName = '<a href="' . escape_html($accessDeveloperURL) . '" target="_blank" rel="noopener">' . $accessDeveloperName . '</a>';
}
$accessTitle = (string) get_config('title_app');
$accessDescription = (string) get_config('description');
$accessWelcome = trim(strip_tags(str_replace('</h1>', ' ', (string) __($accessLang, 'WELCOME_MSG'))));
?>
<div class="ui very relaxed internally celled stackable stretched equal width fullscreen grid">

    <section class="computer only tinted column access-brand" aria-label="<?= escape_html(__($accessLang, 'Plataforma y aliados')); ?>" bg-js="<?= base64EncodeOrDefault(jsonEncodeFallbackNull($accessBackgrounds) ?? '[]'); ?>">
        <div class="ui vertical wide flex">
            <div class="fluid ui centered flex">
                <div class="ui vertical centered very relaxed flex">
                    <div class="ui raised segment"><img class="ui tiny image" src="<?= escape_html((string) get_config('logo')); ?>" alt=""></div>
                    <div class="ui huge center aligned header"><?= escape_html($accessTitle); ?>
                        <?php if ($accessDescription !== ''): ?>
                        <div class="sub header"><span class="ui large text"><?= escape_html($accessDescription); ?></span></div>
                        <?php endif; ?>
                    </div>
                    <?php if ($accessWelcome !== ''): ?>
                    <p class="welcome-msg"><?= escape_html($accessWelcome); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="ui vertical centered relaxed flex" role="group" aria-label="<?= escape_html(__($accessLang, 'Patrocinador')); ?>">
                <?php if ($accessPartnersText !== ''): ?>
                <small class="ui muted text"><strong><?= escape_html($accessPartnersText); ?></strong></small>
                <?php endif; ?>
                <img class="ui small image" src="<?= escape_html((string) get_config('partners')); ?>" alt="">
                <?php if ($accessDeveloperName !== ''): ?>
                <small class="ui muted text tight nowrap centered flex developer-credit">
                    <?php if ($accessDeveloperLogo !== ''): ?>
                    <img class="ui mini image" src="<?= escape_html($accessDeveloperLogo); ?>" alt="">
                    <?php endif; ?>
                    <span><?= __('general', 'Desarrollado por'); ?> <?= $accessDeveloperName; ?></span>
                </small>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="column">
        <div class="ui vertical flex">

            <div class="ui grid">
                <div class="mobile tablet only row">
                    <div class="column">
                        <div class="ui relaxed nowrap flex">
                            <img class="ui mini image" src="<?= escape_html((string) get_config('logo')); ?>" alt="">
                            <div class="ui medium header"><?= escape_html($accessTitle); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="fluid ui top aligned centered grid">
                <div class="row">
                    <div class="sixteen wide mobile ten wide tablet ten wide computer column">
                        <?= $accessContent; ?>
                    </div>
                </div>
            </div>

            <footer class="ui center aligned basic segment">
                <small class="ui muted text">© <?= date('Y'); ?> · <?= __(USER_LOGIN_LANG_GROUP, 'Todos los derechos reservados'); ?> · <?= escape_html((string) get_config('title_app')); ?></small>
            </footer>

        </div>
    </section>

</div>
