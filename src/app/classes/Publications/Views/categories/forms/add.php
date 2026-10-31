<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 */
$langs = \PiecesPHP\Core\Config::get_allowed_langs();
$allowedManyLangs = count($langs) > 1;
?>
<section class="module-view-container">

    <div class="limiter-content">

        <?php
            //La cabecera del diseño de contenidos (diseno/01-contenidos.md §2.1).
            $this->helpController->_render('panel/built-in/content/page-header.php', [
                'breadcrumbs' => $breadcrumbs,
                'title' => $title,
                'description' => $description ?? '',
            ]);
        ?>

        <form method='POST' action="<?= $action; ?>" class="ui form initial publications-categories" data-content-form>

            <div class="container-standard-form">

                <div class="field required"<?= $allowedManyLangs ? '' : ' hidden'; ?>>
                    <label><?= __('lang', 'Idioma principal'); ?></label>
                    <select class="ui dropdown search" name="baseLang" required>
                        <?= $langsOptions; ?>
                    </select>
                </div>

                <div class="field required">
                    <label>
                        <?= __($langGroup, 'Nombre'); ?>
                    </label>
                    <input required type="text" name="name" maxlength="300" placeholder=" ">
                </div>

            </div>

            <?php
                //La barra al final del formulario (§2.3).
                $this->helpController->_render('panel/built-in/content/form-action-bar.php', [
                    'saveText' => __($langGroup, 'Guardar'),
                    'secondaryActions' => [['text' => __($langGroup, 'Cancelar'), 'url' => $backLink, 'icon' => 'times']],
                ]);
            ?>

        </form>

    </div>

</section>
