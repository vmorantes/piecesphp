<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use News\Mappers\NewsCategoryMapper;

/**
 * @var NewsCategoryMapper $element
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
        <div class="ui form">
            <div class="field required">
                <label><?= __($langGroup, 'Idiomas'); ?></label>
                <select required class="ui dropdown search langs">
                    <?= $allowedLangsOptions; ?>
                </select>
            </div>
        </div>
        <?php endif; ?>

        <form method='POST' action="<?= $action; ?>" class="ui form initial news-categories" data-content-form>

            <div class="container-standard-form">

                <input type="hidden" name="id" value="<?= $element->id; ?>">
                <input type="hidden" name="lang" value="<?= $selectedLang; ?>">

                <div class="fields">

                    <div class="ten wide field">

                        <h4 class="ui dividing header"><?= __($langGroup, 'Datos básicos'); ?></h4>

                        <div class="field required">
                            <label><?= __($langGroup, 'Nombre'); ?></label>
                            <input required type="text" name="name" maxlength="300" value="<?= escape_html($element->getLangData($selectedLang, 'name', false, '')); ?>">
                        </div>


                        <div class="field required">
                            <label><?= __($langGroup, 'Color'); ?></label>
                            <input type="text" name="color" color-picker-js data-color-picker-alpha="yes" data-color-picker-format="rgb" value="<?= escape_html($element->color); ?>">
                        </div>

                    </div>

                    <div class="six wide field">

                        <h4 class="ui dividing header"><?= __($langGroup, 'Ícono de categoría'); ?></h4>

                        <?php
                            $this->helpController->_render('panel/built-in/content/image-field.php', [
                                'card' => [
                                    'id' => 'category-icon',
                                    'title' => __($langGroup, 'Ícono de categoría'),
                                    'field' => 'iconImage',
                                    'image' => (string) $element->getLangData($selectedLang, 'iconImage'),
                                    'width' => 300,
                                    'height' => 300,
                                    'format' => 'image/png',
                                    'fillColor' => 'transparent',
                                ],
                            ]);
                        ?>

                    </div>

                </div>

            </div>

            <?php
                //La barra al final del formulario (§2.3): Eliminar es `button` y delete-config.js lo intercepta en el clic.
                $secondaryActions = [['text' => __($langGroup, 'Cancelar'), 'url' => $backLink, 'icon' => 'times']];
                if ($allowDelete) {
                    $secondaryActions[] = ['text' => __($langGroup, 'Eliminar'), 'type' => 'button', 'icon' => 'trash alternate', 'danger' => true, 'attributes' => ['delete-news-category-button' => '', 'data-route' => $deleteRoute]];
                }
                $this->helpController->_render('panel/built-in/content/form-action-bar.php', [
                    'saveText' => __($langGroup, 'Guardar'),
                    'saveAttributes' => ['save' => ''],
                    'secondaryActions' => $secondaryActions,
                ]);
            ?>

        </form>

    </div>

</section>
<?php $this->helpController->_render('panel/built-in/content/image-editor.php'); ?>
