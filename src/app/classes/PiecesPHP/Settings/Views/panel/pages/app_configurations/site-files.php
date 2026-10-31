<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 * @var string $actionURL
 * @var array<string,array{file:string,title:string,help:string,value:string,base:string,url:string}> $files
 * @var string $title
 * @var string $description
 * @var string $indexLink
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $helpController El que pinta las piezas del panel.
 */
$escape = fn($value): string => htmlspecialchars(is_scalar($value) ? (string) $value : '', ENT_QUOTES, 'UTF-8');
?>
<section class="module-view-container site-files-view">

    <?php
        //La cabecera del diseño de contenidos (diseno/02-configuracion.md §2.1).
        $helpController->_render('panel/built-in/content/page-header.php', [
            'title' => $title,
            'description' => $description,
        ]);
    ?>

    <form class="ui form initial site-files segment" action="<?= $escape($actionURL); ?>" method="POST" data-content-form>

        <p><?= __($langGroup, 'Estos archivos les dicen a los buscadores y a las herramientas de inteligencia artificial qué pueden leer de tu sitio. El sistema ya trae una configuración fija; aquí puedes añadir lo tuyo.'); ?></p>

        <div class="ui hidden divider"></div>

        <?php $last = array_key_last($files); ?>
        <?php foreach ($files as $name => $file): ?>
        <section aria-labelledby="site-file-title-<?= $escape($name); ?>">
            <?php
                $helpController->_render('panel/built-in/content/section-header.php', [
                    'id' => "site-file-title-{$name}",
                    'title' => $file['file'],
                    'description' => $file['title'],
                    'url' => $file['url'],
                ]);
            ?>
            <div class="field">
                <label for="site-file-<?= $escape($name); ?>"><?= __($langGroup, 'Añadir'); ?></label>
                <textarea id="site-file-<?= $escape($name); ?>" name="<?= $escape($name); ?>" rows="4" spellcheck="false" aria-describedby="site-file-help-<?= $escape($name); ?>"><?= $escape($file['value']); ?></textarea>
                <div class="description" id="site-file-help-<?= $escape($name); ?>"><?= $escape($file['help']); ?></div>
            </div>
            <div class="ui styled fluid accordion">
                <div class="title" role="button" tabindex="0"><i class="dropdown icon" aria-hidden="true"></i><?= __($langGroup, 'Ver la configuración fija'); ?></div>
                <div class="content">
                    <pre class="ui code segment"><?= $escape($file['base']); ?></pre>
                    <p><small class="ui muted text"><?= __($langGroup, 'La pone el sistema y no se edita aquí.'); ?></small></p>
                </div>
            </div>
        </section>

        <?php if ($name !== $last): ?>
        <div class="ui section divider"></div>
        <?php endif; ?>
        <?php endforeach; ?>

        <?php
            //La barra al final del formulario (§2.3).
            $helpController->_render('panel/built-in/content/form-action-bar.php', [
                'saveText' => __($langGroup, 'Guardar'),
                'secondaryActions' => [['text' => __($langGroup, 'Cancelar'), 'url' => $indexLink, 'icon' => 'times']],
            ]);
        ?>

    </form>

</section>
