<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use Forms\Categories\Controllers\CategoriesController;
use Forms\Categories\Mappers\CategoriesMapper;

/**
 * @var CategoriesMapper $element
 * @var CategoriesController $this
 */
/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 */
?>
<section class="module-view-container">

    <div class="limiter-content">

        <?php
        //La cabecera del diseño de contenidos (diseno/01-contenidos.md §2.1); sin frase en la edición.
        $this->helpController->_render('panel/built-in/content/page-header.php', [
            'breadcrumbs' => $breadcrumbs,
            'title' => $title,
        ]);
        ?>

        <?php if($manyLangs): ?>
        <div class="ui form max-w-800">
            <div class="field required">
                <label><?= __($langGroup, 'Idiomas'); ?></label>
                <select required class="ui dropdown search langs">
                    <?= $allowedLangs; ?>
                </select>
            </div>
        </div>
        <br>
        <?php endif; ?>

        <form method='POST' action="<?= $action; ?>" class="ui form initial max-w-800" category-form data-content-form>

            <div class="container-standard-form">

                <input type="hidden" name="id" value="<?= $element->id; ?>">
                <input type="hidden" name="lang" value="<?= $lang; ?>">

                <div class="field required">
                    <label><?= __($langGroup, 'Nombre'); ?></label>
                    <input type="text" name="categoryName" required value="<?= escape_html($element->getLangData($lang, 'categoryName')); ?>">
                </div>

            </div>

            <?php
            //La barra al final del formulario (§2.3): Eliminar es `button` y delete-config.js lo intercepta en el clic.
            $secondaryActions = [['text' => __($langGroup, 'Cancelar'), 'url' => $backLink, 'icon' => 'times']];
            if ($allowDelete) {
                $secondaryActions[] = ['text' => __($langGroup, 'Eliminar'), 'type' => 'button', 'icon' => 'trash alternate', 'danger' => true, 'attributes' => ['delete-categories-button' => '', 'data-route' => $deleteRoute]];
            }
            $this->helpController->_render('panel/built-in/content/form-action-bar.php', [
                'saveText' => __($langGroup, 'Guardar'),
                'secondaryActions' => $secondaryActions,
            ]);
            ?>

        </form>

    </div>

</section>
