<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 * @var string $processTableLink
 * @var string $addLink
 * @var bool $hasPermissionsAdd
 * @var string $addAction
 * @var string[] $langs
 * @var string $defaultLang
 */
$manyLangs = count($langs) > 1;
//El idioma por omisión, primero: es el del alta y su nombre es el obligatorio.
$orderedLangs = array_values(array_unique(array_merge([$defaultLang], $langs)));
?>
<section class="module-view-container">

    <div class="limiter-content">

        <?php
            //La cabecera del diseño de contenidos (diseno/01-contenidos.md §2.1); «Nueva categoría» abre la ventana (§2.4).
            $this->helpController->_render('panel/built-in/content/page-header.php', [
                'breadcrumbs' => $breadcrumbs,
                'title' => $title,
                'primaryAction' => $hasPermissionsAdd ? ['text' => __($langGroup, 'Nueva categoría'), 'url' => $addLink, 'icon' => 'plus', 'attributes' => ['data-content-modal' => 'publications-category-modal']] : null,
            ]);
        ?>

        <div class="mirror-scroll-x" mirror-scroll-target=".container-standard-table">
            <div class="mirror-scroll-x-content"></div>
        </div>

        <div class="ui fluid card container-standard-table">

            <table url="<?= $processTableLink; ?>" class="ui selectable very basic table content-table">

                <thead>

                    <tr>
                        <th><?= __($langGroup, 'Nombre'); ?></th>
                        <th order="false" class-name="collapsing" with-container="true"><?= __($langGroup, 'Acciones'); ?></th>
                    </tr>

                </thead>

            </table>

        </div>

    </div>

</section>

<?php
//Los campos que esperan las acciones de alta y de edición: name[<idioma>] (obligatorio el del idioma base), baseLang e id.
ob_start();
?>
<input type="hidden" name="id" value="" data-content-modal-when-value>
<input type="hidden" name="baseLang" value="<?= escape_html($defaultLang); ?>">
<?php foreach ($orderedLangs as $lang): ?>
<div class="field<?= $lang === $defaultLang ? ' required' : ''; ?>">
    <label><?= __($langGroup, 'Nombre'); ?><?php if ($manyLangs): ?> <small>(<?= escape_html(__('lang', $lang)); ?>)</small><?php endif; ?></label>
    <input type="text" name="name[<?= escape_html($lang); ?>]" maxlength="300" data-content-modal-requirable<?= $lang === $defaultLang ? ' required' : ''; ?>>
</div>
<?php endforeach; ?>
<?php
$this->helpController->_render('panel/built-in/content/form-modal.php', [
    'modalId' => 'publications-category-modal',
    'title' => __($langGroup, 'Nueva categoría'),
    'action' => $addAction,
    'fields' => (string) ob_get_clean(),
]);
?>
