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
 * @var string $deleteRoute
 * @var bool $allowDelete
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
        //La cabecera del diseño de contenidos (diseno/01-contenidos.md §2.1); sin frase en la edición.
        $this->helpController->_render('panel/built-in/content/page-header.php', [
            'breadcrumbs' => $breadcrumbs,
            'title' => $title,
        ]);
        ?>

        <form method='POST' action="<?= $action; ?>" class="ui form initial news" data-content-form>

            <div class="container-standard-form">

                <input type="hidden" name="id" value="<?= $element->id; ?>">
                <input type="hidden" name="lang" value="<?= $selectedLang; ?>">

                <div class="field">
                    <div class="ui toggle checkbox">
                        <input type="checkbox" name="draft" value="yes" <?= $element->draft === 1 ? 'checked' : ''; ?>>
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
                                <input required type="text" name="<?= $fieldName; ?>[<?= $langOnCreation; ?>]" maxlength="300" value="<?= escape_html($element->getLangData($langOnCreation, $fieldName, false, '')); ?>">
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <div class="sixteen wide column">
                            <div class="field required">
                                <label><?= __($langGroup, 'Título'); ?></label>
                                <input required type="text" name="<?= $fieldName; ?>[<?= $defaultLang; ?>]" maxlength="300" value="<?= escape_html($element->getLangData($defaultLang, $fieldName, false, '')); ?>">
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
                                <textarea name="<?= $fieldName; ?>[<?= $langOnCreation; ?>]" required><?= htmlspecialchars((string) $element->getLangData($langOnCreation, $fieldName, false, ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></textarea>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <div class="sixteen wide column">
                            <div class="field required">
                                <label><?= __($langGroup, 'Contenido'); ?></label>
                                <div rich-editor-adapter-component="<?= $defaultLang; ?>"><?= $element->getLangData($defaultLang, $fieldName, false, ''); ?></div>
                                <textarea name="<?= $fieldName; ?>[<?= $defaultLang; ?>]" required><?= htmlspecialchars((string) $element->getLangData($defaultLang, $fieldName, false, ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></textarea>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <?php
            //La barra fija (§2.3): edit-form.js busca `[save]`; Eliminar es `button` y delete-config.js lo intercepta en el clic.
            $secondaryActions = [['text' => __($langGroup, 'Cancelar'), 'url' => $backLink, 'icon' => 'times']];
            if ($allowDelete) {
                $secondaryActions[] = ['text' => __($langGroup, 'Eliminar'), 'type' => 'button', 'icon' => 'trash alternate', 'danger' => true, 'attributes' => ['delete-news-button' => '', 'data-route' => $deleteRoute]];
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
