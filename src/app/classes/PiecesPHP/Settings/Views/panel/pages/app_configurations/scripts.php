<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\Core\Utilities\Helpers\ExtraScripts;
use PiecesPHP\Core\Utilities\Helpers\StatusLabel;

/**
 * @var string $langGroup
 * @var string $actionURL
 * @var array<int,array{label:string,zone:string,position:string,active:bool,code:string}> $entries
 * @var array<string,string> $zones
 * @var array<string,string> $positions
 * @var string $title
 * @var string $description
 * @var string $indexLink
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $helpController El que pinta las piezas del panel.
 */
$escape = fn($value): string => htmlspecialchars(is_scalar($value) ? (string) $value : '', ENT_QUOTES, 'UTF-8');
$entryHTML = function (array $entry, string $key) use ($escape, $helpController, $langGroup, $zones, $positions): string {
    ob_start();
    ?>
    <section data-script-entry aria-labelledby="script-title-<?= $escape($key); ?>">
        <div class="ui relaxed flex">
            <h2 class="ui header" id="script-title-<?= $escape($key); ?>" data-script-title data-script-untitled="<?= $escape(__($langGroup, 'Nuevo script')); ?>"><?= $escape($entry['label'] !== '' ? $entry['label'] : __($langGroup, 'Nuevo script')); ?></h2>
            <span data-script-state="on"<?= $entry['active'] ? '' : ' hidden'; ?>><?= StatusLabel::html(__($langGroup, 'Activo'), StatusLabel::POSITIVE); ?></span>
            <span data-script-state="off"<?= $entry['active'] ? ' hidden' : ''; ?>><?= StatusLabel::html(__($langGroup, 'Inactivo'), StatusLabel::NEGATIVE); ?></span>
            <div class="right floated ui stackable buttons">
                <button type="button" class="ui negative basic labeled icon button" data-script-remove data-confirm="<?= $escape(__($langGroup, '¿Quitar este script? Se retira al guardar.')); ?>"><i class="trash alternate icon" aria-hidden="true"></i><?= __($langGroup, 'Quitar'); ?></button>
            </div>
        </div>
        <p class="ui muted text"><small data-script-summary><?= $escape($zones[$entry['zone']] ?? ''); ?> · <?= $escape($positions[$entry['position']] ?? ''); ?></small></p>
        <div class="required field">
            <label for="script-label-<?= $escape($key); ?>"><?= __($langGroup, 'Nombre'); ?></label>
            <input type="text" id="script-label-<?= $escape($key); ?>" data-script-field="label" value="<?= $escape($entry['label']); ?>" required>
            <div class="description"><?= __($langGroup, 'Solo para reconocerlo en esta lista; no se muestra en el sitio.'); ?></div>
        </div>
        <div class="two fields">
            <div class="field">
                <label for="script-zone-<?= $escape($key); ?>"><?= __($langGroup, 'Dónde'); ?></label>
                <select class="ui fluid selection dropdown" id="script-zone-<?= $escape($key); ?>" data-script-field="zone">
                    <?php foreach ($zones as $value => $text): ?>
                    <option value="<?= $escape($value); ?>"<?= $entry['zone'] === $value ? ' selected' : ''; ?>><?= $escape($text); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="script-position-<?= $escape($key); ?>"><?= __($langGroup, 'En qué parte'); ?></label>
                <select class="ui fluid selection dropdown" id="script-position-<?= $escape($key); ?>" data-script-field="position">
                    <?php foreach ($positions as $value => $text): ?>
                    <option value="<?= $escape($value); ?>"<?= $entry['position'] === $value ? ' selected' : ''; ?>><?= $escape($text); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="field">
            <label for="script-code-<?= $escape($key); ?>"><?= __($langGroup, 'Código'); ?></label>
            <textarea class="ui code segment" id="script-code-<?= $escape($key); ?>" rows="6" spellcheck="false" data-script-field="code" placeholder="<?= $escape(__($langGroup, "<script src='ejemplo.js'></script>")); ?>"><?= $escape($entry['code']); ?></textarea>
            <div class="description"><?= $escape(__($langGroup, 'Incluye las etiquetas <script> o <noscript> tal como te las da el proveedor.')); ?></div>
        </div>
        <?php
            $helpController->_render('panel/built-in/content/toggle-field.php', [
                    'label' => __($langGroup, 'Activo'),
                    'checked' => $entry['active'],
                    'attributes' => ['data-script-field' => 'active'],
                ]);
            ?>
        <div class="ui section divider"></div>
    </section>
    <?php
        return (string) ob_get_clean();
        };
    ?>
<section class="module-view-container scripts-view" data-scripts-view>

    <?php
        //La cabecera del diseño de contenidos (diseno/02-configuracion.md §2.1).
        $helpController->_render('panel/built-in/content/page-header.php', [
            'title' => $title,
            'description' => $description,
        ]);
    ?>

    <?php //Fuera del formulario: dentro de un `.ui.form`, Fomantic oculta los `.warning.message`. ?>
    <div class="ui warning icon message">
        <i class="exclamation triangle icon" aria-hidden="true"></i>
        <div class="content">
            <div class="header"><?= __($langGroup, 'Pega solo código en el que confíes'); ?></div>
            <p><?= __($langGroup, 'Lo que escribas aquí se ejecuta tal cual en el navegador de cada visitante o usuario. Un error puede romper la página o exponer datos; prueba primero con el script desactivado.'); ?></p>
        </div>
    </div>

    <form class="ui form initial segment" action="<?= $escape($actionURL); ?>" method="POST" data-scripts-form data-content-form>

        <div data-scripts-list>
            <?php foreach ($entries as $index => $entry): ?>
            <?= $entryHTML($entry, (string) $index); ?>
            <?php endforeach; ?>
        </div>

        <template data-script-template><?= $entryHTML(['label' => '', 'zone' => ExtraScripts::ZONE_PUBLIC, 'position' => ExtraScripts::POSITION_HEAD, 'active' => true, 'code' => ''], 'new'); ?></template>

        <?php //Añadir es de la lista, no del formulario: va con ella y no en la barra. ?>
        <div class="ui stackable buttons">
            <button type="button" class="ui basic labeled icon button" data-script-add><i class="plus icon" aria-hidden="true"></i><?= __($langGroup, 'Añadir script'); ?></button>
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

</section>
