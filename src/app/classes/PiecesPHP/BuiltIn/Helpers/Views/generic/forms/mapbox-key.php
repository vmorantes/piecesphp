<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\Core\Config;
use PiecesPHP\BuiltIn\Helpers\Mappers\GenericContentPseudoMapper;
/**
 * @var GenericContentPseudoMapper $element
 */
/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 */
$defaultLang = Config::get_default_lang();
$allowedLangs = Config::get_allowed_langs();
$contentName = $element->userSetContentName();
$centerName = GenericContentPseudoMapper::CONTENT_MAP_DEFAULT_CENTER;
$center = GenericContentPseudoMapper::mapDefaultCenter();
?>
<section class="module-view-container">

    <div class="limiter-content">

        <?php
        //La cabecera de Configuración (diseno/02-configuracion.md §2.1).
        $this->helpController->_render('panel/built-in/content/page-header.php', [
            'breadcrumbs' => $breadcrumbs,
            'title' => $title,
            'description' => $description,
        ]);
        ?>

        <form method='POST' action="<?= $action; ?>" class="ui form initial generic" data-content-form>

            <div class="container-standard-form mw-800">

                <input type="hidden" name="configName" value="<?= $contentName; ?>">
                <?php foreach($allowedLangs as $lang): ?>
                <?php $value = $element->getLangData($lang, $contentName, true, ''); ?>
                <?php $isMultilang = $element->isTranslatable($contentName); ?>
                <?php $langSuffix = $isMultilang ? " ({$lang})" : ''; ?>
                <?php if($isMultilang || $lang === $defaultLang): ?>
                <div class="two field">
                    <div class="field required">
                        <label><?= __($langGroup, 'Llave local') . $langSuffix; ?></label>
                        <input type="text" name="<?= $contentName; ?>[<?= $lang; ?>][keyLocal]" value="<?= $value['keyLocal']; ?>" required>
                    </div>
                    <div class="field required">
                        <label><?= __($langGroup, 'Llave producción') . $langSuffix; ?></label>
                        <input type="text" name="<?= $contentName; ?>[<?= $lang; ?>][keyDomain]" value="<?= $value['keyDomain']; ?>" required>
                    </div>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>

            </div>

            <?php
            //La barra al final del formulario (§2.3), como en el resto de Configuración: «Cancelar» vuelve a su índice.
            $this->helpController->_render('panel/built-in/content/form-action-bar.php', [
                'saveText' => __($langGroup, 'Guardar'),
                'secondaryActions' => [['text' => __($langGroup, 'Cancelar'), 'url' => $indexLink, 'icon' => 'times']],
            ]);
            ?>

        </form>

        <form method='POST' action="<?= $action; ?>" class="ui form initial generic" data-content-form>

            <div class="container-standard-form mw-800">

                <h3 class="ui header"><?= __($langGroup, 'Centro del mapa por omisión'); ?></h3>
                <p><?= __($langGroup, 'Donde se abren los mapas cuando no tienen otra posición: el geovisor y los mapas de los perfiles.'); ?></p>

                <input type="hidden" name="configName" value="<?= $centerName; ?>">
                <div class="three fields">
                    <div class="field required">
                        <label><?= __($langGroup, 'Longitud'); ?></label>
                        <input type="number" step="any" min="-180" max="180" name="<?= $centerName; ?>[longitude]" value="<?= escape_html((string) $center['longitude']); ?>" required>
                    </div>
                    <div class="field required">
                        <label><?= __($langGroup, 'Latitud'); ?></label>
                        <input type="number" step="any" min="-90" max="90" name="<?= $centerName; ?>[latitude]" value="<?= escape_html((string) $center['latitude']); ?>" required>
                    </div>
                    <div class="field required">
                        <label><?= __($langGroup, 'Acercamiento'); ?></label>
                        <input type="number" step="any" min="0" max="22" name="<?= $centerName; ?>[zoom]" value="<?= escape_html((string) $center['zoom']); ?>" required>
                    </div>
                </div>

            </div>

            <?php
            $this->helpController->_render('panel/built-in/content/form-action-bar.php', [
                'saveText' => __($langGroup, 'Guardar'),
                'secondaryActions' => [['text' => __($langGroup, 'Cancelar'), 'url' => $indexLink, 'icon' => 'times']],
            ]);
            ?>

        </form>

    </div>

</section>