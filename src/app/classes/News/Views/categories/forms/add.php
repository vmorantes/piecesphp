<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 */
?>
<section class="module-view-container">

    <div class="limiter-content">

        <?php
        //La cabecera del diseño de contenidos (diseno/01-contenidos.md §2.1); sin frase en el alta.
        $this->helpController->_render('panel/built-in/content/page-header.php', [
            'breadcrumbs' => $breadcrumbs,
            'title' => $title,
        ]);
        ?>

        <form method='POST' action="<?= $action; ?>" class="ui form news-categories initial max-w-1200" data-content-form>

            <div class="container-standard-form">

                <input type="hidden" name="lang" value="<?= \PiecesPHP\Core\Config::get_default_lang(); ?>">

                <div class="fields">

                    <div class="ten wide field">

                        <h4 class="ui dividing header"><?= __($langGroup, 'Datos básicos'); ?></h4>

                        <div class="field required">
                            <label><?= __($langGroup, 'Nombre'); ?></label>
                            <input required type="text" name="name" maxlength="300">
                        </div>

                        <br>

                        <div class="field required">
                            <label><?= __($langGroup, 'Color'); ?></label>
                            <input type="text" name="color" value="<?= \News\Mappers\NewsCategoryMapper::DEFAULT_COLOR; ?>" color-picker-js data-color-picker-alpha="yes" data-color-picker-format="rgb">
                        </div>

                    </div>

                    <div class="six wide field">

                        <h4 class="ui dividing header"><?= __($langGroup, 'Ícono de categoría'); ?></h4>

                        <div class="field" placeholder="<?= __($langGroup, 'Ícono de categoría'); ?>" image-element>
                            <label><?= __($langGroup, 'Tamaño del ícono'); ?> 300x300px</label>
                            <?php simpleUploadPlaceholderWorkSpace([
                            'inputNameAttr' => 'iconImage',
                            'buttonText' => __($langGroup, 'Agregar'),
                            'classesButton' => 'fomantic green',
                            'required' =>  true,
                            'multiple' => false,
                            'icon' => 'upload',
                            'accept' => 'image/*',
                        ]); ?>
                            <br><br>
                        </div>

                    </div>

                </div>

            </div>

            <?php
            //La barra al final del formulario (§2.3). utils.js guarda con el submit del form.
            $this->helpController->_render('panel/built-in/content/form-action-bar.php', [
                'saveText' => __($langGroup, 'Guardar'),
                'saveAttributes' => ['save' => ''],
                'secondaryActions' => [['text' => __($langGroup, 'Cancelar'), 'url' => $backLink, 'icon' => 'times']],
            ]);
            ?>

        </form>

    </div>

</section>
