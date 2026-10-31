<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $helpController Pinta las piezas del panel
 * @var string[] $ownPackages Los piecesphp/* instalados, con versión y licencia
 * @var string[] $thirdPackages El resto del require de composer.json, igual
 * @var string[] $jsLibraries Las de statics/plugins/, medidas a mano (no tienen fuente)
 * @var int $years Desde enero de 2018
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 */
$langGroup = 'about-framework';
?>
<section class="module-view-container about-framework">

    <div class="limiter-content">

        <?php
            $helpController->_render('panel/built-in/content/page-header.php', [
                'breadcrumbs' => $breadcrumbs,
                'title' => __($langGroup, 'Acerca de'),
                'description' => get_config('title_app') . ' ' . __($langGroup, 'desarrollada en:'),
            ]);
        ?>

        <div class="ui segment">
            <div class="ui stackable two column middle aligned grid">
                <div class="column">
                    <img class="about-framework-logo invert-on-dark" src="<?= base_url('statics/images/framework/main_logo.svg') ?>" alt="PiecesPHP">
                    <div class="ui horizontal relaxed list">
                        <div class="item">
                            <div class="content">
                                <div class="header"><?= __($langGroup, 'Versión instalada'); ?></div>
                                <?= escape_html(APP_VERSION); ?>
                            </div>
                        </div>
                        <div class="item">
                            <div class="content">
                                <div class="header"><?= __($langGroup, 'Fecha'); ?></div>
                                <?= (new \DateTime(APP_VERSION_DATE))->format('m/Y'); ?>
                            </div>
                        </div>
                        <?php $commit = \PiecesPHP\Core\AppVersion::commit(); ?>
                        <?php if ($commit !== null): ?>
                        <div class="item">
                            <div class="content">
                                <div class="header"><?= __($langGroup, 'Commit'); ?></div>
                                <span title="<?= escape_html($commit); ?>"><?= escape_html(mb_substr($commit, 0, 12)); ?></span>
                                <div class="description"><?= \PiecesPHP\Core\AppVersion::commitSource() === \PiecesPHP\Core\AppVersion::SOURCE_STAMP ? __($langGroup, 'según el sello de despliegue') : __($langGroup, 'según git'); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <p>
                        <a class="ui basic button" target="_blank" rel="noopener" href="https://github.com/vmorantes/piecesphp"><i class="github icon"></i>GitHub</a>
                    </p>
                </div>
                <div class="computer only right aligned column">
                    <img class="about-framework-decorated" src="<?= base_url('statics/images/framework/animated_square.gif') ?>" alt="">
                </div>
            </div>
        </div>

        <div class="ui segment">
            <?php $helpController->_render('panel/built-in/content/section-header.php', ['title' => __($langGroup, 'Descripción')]); ?>
            <p><?= __($langGroup, 'Es un conjunto de componentes personalizables y reusables para el desarrollo web basado en PHP con licencia MIT (de código abierto, sin costos escondidos o adicionales para el cliente final), modular, liviano y flexible que permite “ensamblar” diferentes librerías, funcionalidades y plugins como su nombre “Pieces” lo indica; normalmente a este grupo de componentes se le conoce como Framework.'); ?></p>
            <p><?= escape_html(sprintf(__($langGroup, 'Este framework ha venido siendo desarrollado desde hace aproximadamente %d años por Tejido Digital y ha permitido la estandarización de procesos dentro de la organización, desarrollo de manera más ágil y rápida, disminuyendo las solicitudes de soporte (garantía) y en caso de presentarse alguna, lograr una solución más rápida.'), $years)); ?></p>
        </div>

        <div class="ui segment">
            <?php $helpController->_render('panel/built-in/content/section-header.php', ['title' => __($langGroup, 'Librerías propias')]); ?>
            <div class="ui bulleted list">
                <?php foreach ($ownPackages as $package): ?>
                <div class="item"><?= escape_html($package); ?></div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="ui segment">
            <?php $helpController->_render('panel/built-in/content/section-header.php', ['title' => __($langGroup, 'Plugins, scripts y dependencias')]); ?>
            <p><span class="ui small grey text"><?= __($langGroup, 'Desarrollados por terceros'); ?></span></p>
            <p><?= __($langGroup, 'Cada una tiene su propia licencia, que permite usarla en proyectos comerciales con las condiciones que fija esa licencia:'); ?></p>
            <p><span class="ui small grey text"><?= __($langGroup, 'De PHP, instaladas con Composer'); ?></span></p>
            <div class="ui list about-framework-dependencies">
                <?php foreach ($thirdPackages as $package): ?>
                <div class="item"><?= escape_html($package); ?></div>
                <?php endforeach; ?>
            </div>
            <p><span class="ui small grey text"><?= __($langGroup, 'De JavaScript, en statics/plugins/'); ?></span></p>
            <div class="ui list about-framework-dependencies">
                <?php foreach ($jsLibraries as $package): ?>
                <div class="item"><?= escape_html($package); ?></div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="about-framework-footer">
            <img class="invert-on-dark" src="<?= base_url('statics/images/framework/developer_by_logo.png'); ?>" alt="">
            <div><?= date('Y') ?></div>
        </div>

    </div>

</section>
