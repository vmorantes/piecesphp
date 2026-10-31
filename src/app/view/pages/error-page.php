<?php
/**
 * Las páginas de error (patrón `pagina-error`): marca, bloque centrado y pie, sin el layout. Las pintan 404.php, 403.php y
 * 503.php, tanto en el panel como en la zona pública: traen su hoja del tema y no pasan por los assets de la página.
 *
 * @var string $errorPageTitle El <title>.
 * @var string $errorCode El código grande, o vacío si lleva icono.
 * @var string $errorIcon El icono, si no lleva código.
 * @var string $errorHeading
 * @var string $errorText
 * @var string $errorFootnote Bajo el texto, en pequeño: «Código 403» o la hora.
 * @var array<int,array{text:string,url:string,icon?:string}> $errorActions La primera es la principal.
 */
$errorActions = isset($errorActions) && is_array($errorActions) ? $errorActions : [];
?>
<!DOCTYPE html>
<html lang="<?= get_config('app_lang'); ?>" dlang="<?= get_config('default_lang'); ?>" class="no-js">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= escape_html($errorPageTitle); ?></title>
    <base href="<?= base_url(); ?>">
    <link rel="shortcut icon" href="<?= add_cache_stamp_to_url(get_config('favicon')); ?>" type="image/x-icon">
    <link rel="stylesheet" href="<?= add_cache_stamp_to_url('statics/plugins/fomantic-piecesphp/semantic.min.css'); ?>">
    <link rel="stylesheet" href="<?= \PiecesPHP\CSSVariables::globalVersionedURL(); ?>">
</head>

<body>

    <div class="ui fullscreen vertical flex">

        <header class="ui basic center aligned segment">
            <a class="ui medium header inline relaxed flex" href="<?= base_url(); ?>">
                <img class="ui mini image" src="<?= escape_html((string) get_config('logo')); ?>" alt="">
                <?= escape_html((string) get_config('title_app')); ?>
            </a>
        </header>

        <main class="fluid ui centered flex">
            <section class="ui center aligned narrow text container" aria-labelledby="error-page-title">
                <div class="ui vertical centered wide flex">
                    <div class="ui vertical centered very relaxed flex">
                        <?php if ($errorCode !== ''): ?>
                        <div class="ui massive primary header" aria-hidden="true"><?= escape_html($errorCode); ?></div>
                        <?php else: ?>
                        <i class="huge primary bordered <?= escape_html($errorIcon); ?> icon" aria-hidden="true"></i>
                        <?php endif; ?>
                        <div class="ui vertical centered flex">
                            <h1 class="ui large header" id="error-page-title"><?= escape_html($errorHeading); ?></h1>
                            <p class="ui muted text"><?= escape_html($errorText); ?></p>
                            <?php if (isset($errorFootnote) && $errorFootnote !== ''): ?>
                            <small class="ui muted text"><b><?= escape_html($errorFootnote); ?></b></small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($errorActions !== []): ?>
                    <div class="fluid ui stackable centered relaxed flex">
                        <?php foreach ($errorActions as $errorIndex => $errorAction): ?>
                        <a class="ui <?= $errorIndex === 0 ? 'primary labeled icon' : 'basic'; ?> button" href="<?= escape_html($errorAction['url']); ?>"><?php if ($errorIndex === 0): ?><i class="<?= escape_html($errorAction['icon'] ?? 'home'); ?> icon" aria-hidden="true"></i><?php endif; ?><?= escape_html($errorAction['text']); ?></a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>

        <footer class="ui basic center aligned segment">
            <small class="ui muted text">© <?= date('Y'); ?> · <?= __(USER_LOGIN_LANG_GROUP, 'Todos los derechos reservados'); ?> · <?= escape_html((string) get_config('title_app')); ?></small>
        </footer>

    </div>

</body>

</html>
