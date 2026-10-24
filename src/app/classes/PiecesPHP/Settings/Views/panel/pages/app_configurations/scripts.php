<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 * @var string $actionURL
 * @var array<int,array{label:string,zone:string,position:string,active:bool,code:string}> $entries
 * @var array<string,string> $zones
 * @var array<string,string> $positions
 * @var string $title
 * @var string $description
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 * @var string $indexLink
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $helpController El que pinta las piezas del panel.
 */
$escape = fn($value): string => htmlspecialchars(is_scalar($value) ? (string) $value : '', ENT_QUOTES, 'UTF-8');
$entryHTML = function (array $entry) use ($escape, $langGroup, $zones, $positions): string {
    ob_start();
    ?>
    <div class="ui segment" data-script-entry>
        <div class="three fields">
            <div class="field required">
                <label><?= __($langGroup, 'Nombre'); ?></label>
                <input type="text" data-script-field="label" value="<?= $escape($entry['label']); ?>">
            </div>
            <div class="field">
                <label><?= __($langGroup, 'Dónde'); ?></label>
                <select class="ui dropdown" data-script-field="zone">
                    <?php foreach ($zones as $value => $text): ?>
                    <option value="<?= $escape($value); ?>"<?= $entry['zone'] === $value ? ' selected' : ''; ?>><?= $escape($text); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label><?= __($langGroup, 'En qué parte'); ?></label>
                <select class="ui dropdown" data-script-field="position">
                    <?php foreach ($positions as $value => $text): ?>
                    <option value="<?= $escape($value); ?>"<?= $entry['position'] === $value ? ' selected' : ''; ?>><?= $escape($text); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="field">
            <label><?= __($langGroup, 'Código'); ?></label>
            <textarea class="script-code" rows="6" spellcheck="false" data-script-field="code" placeholder="<?= $escape(__($langGroup, "<script src='ejemplo.js'></script>")); ?>"><?= $escape($entry['code']); ?></textarea>
        </div>
        <div class="inline fields">
            <div class="field">
                <div class="ui toggle checkbox">
                    <input type="checkbox" data-script-field="active"<?= $entry['active'] ? ' checked' : ''; ?>>
                    <label><?= __($langGroup, 'Activo'); ?></label>
                </div>
            </div>
            <div class="field">
                <button type="button" class="ui button" data-script-remove data-confirm="<?= $escape(__($langGroup, '¿Quitar este script? Se retira al guardar.')); ?>"><?= __($langGroup, 'Quitar'); ?></button>
            </div>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
};
?>
<section class="module-view-container scripts-view" data-scripts-view>

    <div class="limiter-content">

        <?php
        //La cabecera del diseño de contenidos (diseno/02-configuracion.md §2.1).
        $helpController->_render('panel/built-in/content/page-header.php', [
            'breadcrumbs' => $breadcrumbs,
            'title' => $title,
            'description' => $description,
        ]);
        ?>

        <?php //Fuera del formulario: dentro de un `.ui.form`, Fomantic oculta los `.warning.message`. ?>
        <div class="ui warning message"><?= __($langGroup, 'El código se pega tal cual en las páginas que elijas. Úsalo solo con código de servicios en los que confíes (analítica, chat, píxeles de publicidad).'); ?></div>

        <form class="ui form initial" action="<?= $escape($actionURL); ?>" method="POST" data-scripts-form data-content-form>

            <div class="container-standard-form">

                <div data-scripts-list>
                    <?php foreach ($entries as $entry): ?>
                    <?= $entryHTML($entry); ?>
                    <?php endforeach; ?>
                </div>

                <template data-script-template><?= $entryHTML(['label' => '', 'zone' => \PiecesPHP\Core\Utilities\Helpers\ExtraScripts::ZONE_PUBLIC, 'position' => \PiecesPHP\Core\Utilities\Helpers\ExtraScripts::POSITION_HEAD, 'active' => true, 'code' => '']); ?></template>

                <?php //Añadir es de la lista, no del formulario: va con ella y no en la barra. ?>
                <button type="button" class="ui button brand-color alt" data-script-add><?= __($langGroup, 'Añadir script'); ?></button>

            </div>

            <?php
            //La barra al final del formulario (§2.3); su JS busca `data-scripts-save` en Guardar.
            $helpController->_render('panel/built-in/content/form-action-bar.php', [
                'saveText' => __($langGroup, 'Guardar'),
                'saveAttributes' => ['data-scripts-save' => ''],
                'secondaryActions' => [['text' => __($langGroup, 'Cancelar'), 'url' => $indexLink, 'icon' => 'times']],
            ]);
            ?>

        </form>

    </div>

</section>
