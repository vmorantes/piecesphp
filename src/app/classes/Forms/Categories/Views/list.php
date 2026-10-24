<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * @var string $langGroup
 * @var string $processTableLink
 * @var string $addLink
 * @var string $addAction
 * @var bool $hasPermissionsAdd
 * @var string $defaultLang
 */
?>
<section class="module-view-container">

    <div class="limiter-content">

        <?php
        //La cabecera del diseño de contenidos (diseno/01-contenidos.md §2.1); «Nueva categoría» abre la ventana (§2.4).
        $this->helpController->_render('panel/built-in/content/page-header.php', [
            'breadcrumbs' => $breadcrumbs,
            'title' => $title,
            'primaryAction' => $hasPermissionsAdd ? ['text' => __($langGroup, 'Nueva categoría'), 'url' => $addLink, 'icon' => 'plus', 'attributes' => ['data-content-modal' => 'forms-category-modal']] : null,
        ]);
        ?>

        <div class="mirror-scroll-x" mirror-scroll-target=".container-standard-table">
            <div class="mirror-scroll-x-content"></div>
        </div>

        <div class="container-standard-table">

            <table url="<?= $processTableLink; ?>" class="ui basic table content-table">

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
//Los campos que esperan las acciones de alta y de edición: categoryName, lang (el alta lo ignora y guarda en el idioma
//por omisión, pero lo exige) e id.
ob_start();
?>
<input type="hidden" name="id" value="" data-content-modal-when-value>
<input type="hidden" name="lang" value="<?= escape_html($defaultLang); ?>">
<div class="field required">
    <label><?= __($langGroup, 'Nombre'); ?></label>
    <input type="text" name="categoryName" required>
</div>
<?php
$this->helpController->_render('panel/built-in/content/form-modal.php', [
    'modalId' => 'forms-category-modal',
    'title' => __($langGroup, 'Nueva categoría'),
    'action' => $addAction,
    'fields' => (string) ob_get_clean(),
]);
?>
