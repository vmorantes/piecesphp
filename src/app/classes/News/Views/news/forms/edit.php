<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use News\Mappers\NewsMapper;
use PiecesPHP\Core\Config;

/**
 * @var NewsMapper $element
 */

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
$langsOnCreation = array_unique(array_merge([$defaultLang], NewsMapper::$LANGS_ON_CREATION));
$manyLangsOnCreation = count($langsOnCreation) > 1;
?>
<section class="module-view-container">

    <div class="breadcrumb">
        <?= $breadcrumbs ?>
    </div>

    <div class="limiter-content">

        <div class="section-title">
            <div class="title"><?= $title ?></div>
            <?php if(isset($description) && is_string($description) && mb_strlen(trim($description)) > 0): ?>
            <div class="description"><?= $description; ?></div>
            <?php endif; ?>
        </div>

        <br>

        <form method='POST' action="<?= $action; ?>" class="ui form news">

            <div class="container-standard-form">

                <input type="hidden" name="id" value="<?= $element->id; ?>">
                <input type="hidden" name="lang" value="<?= $selectedLang; ?>">

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
                        <input required type="text" name="startDate" autocomplete="off" value="<?= $element->startDate !== null ? $element->startDate->format('Y-m-d h:i:s A') : ''; ?>">
                    </div>

                    <div class="field required" calendar-group-js='periodo' end>
                        <label><?= __($langGroup, 'Fecha de final'); ?></label>
                        <input required type="text" name="endDate" autocomplete="off" value="<?= $element->endDate !== null ? $element->endDate->format('Y-m-d h:i:s A') : ''; ?>">
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
                                <input required type="text" name="<?= $fieldName; ?>[<?= $langOnCreation; ?>]" maxlength="300" value="<?= $element->getLangData($langOnCreation, $fieldName, false, ''); ?>">
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <div class="sixteen wide column">
                            <div class="field required">
                                <label><?= __($langGroup, 'Título'); ?></label>
                                <input required type="text" name="<?= $fieldName; ?>[<?= $defaultLang; ?>]" maxlength="300" value="<?= $element->getLangData($defaultLang, $fieldName, false, ''); ?>">
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
                                <div rich-editor-adapter-component="<?= $langOnCreation; ?>"><?= $element->getLangData($langOnCreation, $fieldName, false, ''); ?></div>
                                <textarea name="<?= $fieldName; ?>[<?= $langOnCreation; ?>]" required><?= $element->getLangData($langOnCreation, $fieldName, false, ''); ?></textarea>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <div class="sixteen wide column">
                            <div class="field required">
                                <label><?= __($langGroup, 'Contenido'); ?></label>
                                <div rich-editor-adapter-component="<?= $defaultLang; ?>"><?= $element->getLangData($defaultLang, $fieldName, false, ''); ?></div>
                                <textarea name="<?= $fieldName; ?>[<?= $defaultLang; ?>]" required><?= $element->getLangData($defaultLang, $fieldName, false, ''); ?></textarea>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <br>

            <div class="field">
                <button type="submit" class="ui button brand-color" save><?= __($langGroup, 'Guardar'); ?></button>
                <?php if($allowDelete): ?>
                <button type="submit" class="ui button brand-color alt2" delete-news-button data-route="<?= $deleteRoute; ?>"><?= __($langGroup, 'Eliminar'); ?></button>
                <?php endif; ?>
            </div>

        </form>

    </div>

</section>