<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use News\Mappers\NewsMapper;
use PiecesPHP\Core\Config;
/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 */
$defaultLang = Config::get_default_lang();
$allowedLangs = Config::get_allowed_langs(false, $defaultLang);
$emptyMapper = new NewsMapper();
$translatableProperties = $emptyMapper->getTranslatableProperties();
//Se agrega el lenguaje por defecto en el primer puesto, por si no está y se eliminan duplicados
$langsOnCreation = array_unique(array_merge([$defaultLang], NewsMapper::LANGS_ON_CREATION));
$manyLangsOnCreation = count($langsOnCreation) > 1;
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

        <form method='POST' action="<?= $action; ?>" class="ui form initial news" data-content-form>

            <div class="container-standard-form">

                <input type="hidden" name="lang" value="<?= \PiecesPHP\Core\Config::get_default_lang(); ?>">

                <div class="field">
                    <div class="ui toggle checkbox">
                        <input type="checkbox" name="draft" value="yes">
                        <label><?= __($langGroup, 'Borrador'); ?></label>
                    </div>
                </div>

                <div class="two fields">
                    <div class="field required">
                        <label><?= __($langGroup, 'Tipos de perfil para los que será visible'); ?></label>
                        <select class="ui dropdown multiple" multiple name="profilesTarget[]" required>
                            <?= $allUsersTypes; ?>
                        </select>
                    </div>
                    <div class="field required">
                        <label><?= __($langGroup, 'Categorías'); ?></label>
                        <select class="ui dropdown" name="category" required>
                            <?= $allCategories; ?>
                        </select>
                    </div>
                </div>
                <div class="two fields">

                    <div class="field required" calendar-group-js='periodo' start>
                        <label><?= __($langGroup, 'Fecha de inicio'); ?></label>
                        <input required type="text" name="startDate" autocomplete="off" placeholder=" ">
                    </div>

                    <div class="field required" calendar-group-js='periodo' end>
                        <label><?= __($langGroup, 'Fecha de final'); ?></label>
                        <input required type="text" name="endDate" autocomplete="off">
                    </div>

                </div>

                <div class="field">
                    <div class="ui stackable grid">
                        <?php $fieldName = 'newsTitle'; ?>
                        <?php if($manyLangsOnCreation && in_array($fieldName, $translatableProperties)): ?>
                        <?php foreach($langsOnCreation as $langOnCreation): ?>
                        <div class="eight wide column">
                            <div class="field required">
                                <label><?= __($langGroup, 'Título'); ?> (<?= __('lang', $langOnCreation); ?>)</label>
                                <input required type="text" name="<?= $fieldName; ?>[<?= $langOnCreation; ?>]" maxlength="300">
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <div class="sixteen wide column">
                            <div class="field required">
                                <label><?= __($langGroup, 'Título'); ?></label>
                                <input required type="text" name="<?= $fieldName; ?>[<?= $defaultLang; ?>]" maxlength="300">
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="field">
                    <div class="ui stackable grid">
                        <?php $fieldName = 'content'; ?>
                        <?php if($manyLangsOnCreation && in_array($fieldName, $translatableProperties)): ?>
                        <?php foreach($langsOnCreation as $langOnCreation): ?>
                        <div class="eight wide column">
                            <div class="field required">
                                <label><?= __($langGroup, 'Contenido'); ?> (<?= __('lang', $langOnCreation); ?>)</label>
                                <div rich-editor-adapter-component="<?= $langOnCreation; ?>"></div>
                                <textarea name="<?= $fieldName; ?>[<?= $langOnCreation; ?>]" required></textarea>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <div class="sixteen wide column">
                            <div class="field required">
                                <label><?= __($langGroup, 'Contenido'); ?></label>
                                <div rich-editor-adapter-component="<?= $defaultLang; ?>"></div>
                                <textarea name="<?= $fieldName; ?>[<?= $defaultLang; ?>]" required></textarea>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <?php
            //La barra fija (§2.3). El atributo `save` es el que busca add-form.js.
            $this->helpController->_render('panel/built-in/content/form-action-bar.php', [
                'saveText' => __($langGroup, 'Guardar'),
                'saveAttributes' => ['save' => ''],
                'secondaryActions' => [['text' => __($langGroup, 'Cancelar'), 'url' => $backLink, 'icon' => 'times']],
            ]);
            ?>

        </form>

    </div>

</section>
