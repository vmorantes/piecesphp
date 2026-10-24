<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 * @var string $actionURL
 * @var array<string,array{file:string,title:string,help:string,value:string,base:string,url:string}> $files
 * @var string $title
 * @var string $description
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 * @var string $indexLink
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $helpController El que pinta las piezas del panel.
 */
$escape = fn($value): string => htmlspecialchars(is_scalar($value) ? (string) $value : '', ENT_QUOTES, 'UTF-8');
?>
<section class="module-view-container site-files-view">

    <div class="limiter-content">

        <?php
        //La cabecera del diseño de contenidos (diseno/02-configuracion.md §2.1).
        $helpController->_render('panel/built-in/content/page-header.php', [
            'breadcrumbs' => $breadcrumbs,
            'title' => $title,
            'description' => $description,
        ]);
        ?>

        <form class="ui form initial site-files" action="<?= $escape($actionURL); ?>" method="POST" data-content-form>

            <div class="container-standard-form">

                <p><?= __($langGroup, 'Estos archivos les dicen a los buscadores y a las herramientas de inteligencia artificial qué pueden leer de tu sitio. El sistema ya trae una configuración fija; aquí puedes añadir lo tuyo.'); ?></p>

                <?php foreach ($files as $name => $file): ?>
                <div class="ui segment">
                    <h4 class="ui header">
                        <?= $escape($file['title']); ?>
                        <a href="<?= $escape($file['url']); ?>" target="_blank"><span class="ui grey text"><small>(<?= $escape($file['file']); ?>)</small></span></a>
                    </h4>
                    <div class="field">
                        <label for="site-file-<?= $escape($name); ?>"><?= __($langGroup, 'Añadir'); ?></label>
                        <textarea class="site-file-code" id="site-file-<?= $escape($name); ?>" name="<?= $escape($name); ?>" rows="5" spellcheck="false"><?= $escape($file['value']); ?></textarea>
                        <small><?= $escape($file['help']); ?></small>
                    </div>
                    <div class="ui accordion">
                        <div class="title">
                            <i class="dropdown icon"></i>
                            <?= __($langGroup, 'Ver la configuración fija'); ?>
                        </div>
                        <div class="content">
                            <div class="field">
                                <label for="site-file-base-<?= $escape($name); ?>"><?= __($langGroup, 'Configuración fija'); ?></label>
                                <textarea class="site-file-code" id="site-file-base-<?= $escape($name); ?>" rows="6" readonly><?= $escape($file['base']); ?></textarea>
                                <small><?= __($langGroup, 'La pone el sistema y no se edita aquí.'); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

            </div>

            <?php
            //La barra al final del formulario (§2.3).
            $helpController->_render('panel/built-in/content/form-action-bar.php', [
                'saveText' => __($langGroup, 'Guardar'),
                'secondaryActions' => [['text' => __($langGroup, 'Cancelar'), 'url' => $indexLink, 'icon' => 'times']],
            ]);
            ?>

        </form>

    </div>

</section>
