<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\BuiltIn\Helpers\Mappers\GenericContentPseudoMapper;
use PiecesPHP\Core\Config;
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

    <?php
        //La cabecera de Configuración (diseno/02-configuracion.md §2.1).
        $this->helpController->_render('panel/built-in/content/page-header.php', [
            'title' => $title,
            'description' => $description,
        ]);
    ?>

    <div class="ui segment">

        <?php //Dos formularios: cada uno guarda su opción (configName), y el manejador envía el primero de lo que recibe. ?>
        <form method='POST' action="<?= $action; ?>" class="ui form initial generic" data-content-form>

            <p><?= __($langGroup, 'La plataforma viene integrada por defecto con Mapbox. Estas llaves permiten mostrar los mapas: la local se usa mientras se desarrolla y la de producción en el sitio publicado.'); ?></p>

            <input type="hidden" name="configName" value="<?= escape_html($contentName); ?>">
            <?php foreach($allowedLangs as $lang): ?>
            <?php $value = $element->getLangData($lang, $contentName, true, ''); ?>
            <?php $isMultilang = $element->isTranslatable($contentName); ?>
            <?php $langSuffix = $isMultilang ? " ({$lang})" : ''; ?>
            <?php if($isMultilang || $lang === $defaultLang): ?>
            <div class="two fields">
                <div class="required field">
                    <label for="mapbox-key-local-<?= escape_html($lang); ?>"><?= __($langGroup, 'Llave local') . $langSuffix; ?></label>
                    <input type="text" id="mapbox-key-local-<?= escape_html($lang); ?>" name="<?= escape_html($contentName); ?>[<?= escape_html($lang); ?>][keyLocal]" value="<?= escape_html((string) $value['keyLocal']); ?>" spellcheck="false" autocomplete="off" required>
                </div>
                <div class="required field">
                    <label for="mapbox-key-domain-<?= escape_html($lang); ?>"><?= __($langGroup, 'Llave producción') . $langSuffix; ?></label>
                    <input type="text" id="mapbox-key-domain-<?= escape_html($lang); ?>" name="<?= escape_html($contentName); ?>[<?= escape_html($lang); ?>][keyDomain]" value="<?= escape_html((string) $value['keyDomain']); ?>" spellcheck="false" autocomplete="off" required>
                </div>
            </div>
            <?php endif; ?>
            <?php endforeach; ?>

            <?php
                //La barra al final del formulario (§2.3), como en el resto de Configuración: «Cancelar» vuelve a su índice.
                $this->helpController->_render('panel/built-in/content/form-action-bar.php', [
                    'saveText' => __($langGroup, 'Guardar'),
                    'secondaryActions' => [['text' => __($langGroup, 'Cancelar'), 'url' => $indexLink, 'icon' => 'times']],
                ]);
            ?>

        </form>

        <div class="ui section divider"></div>

        <form method='POST' action="<?= $action; ?>" class="ui form initial generic" data-content-form>

            <?php
                $this->helpController->_render('panel/built-in/content/section-header.php', [
                    'title' => __($langGroup, 'Centro del mapa por defecto'),
                    'description' => __($langGroup, 'Donde se abren los mapas cuando no tienen otra posición: el geovisor y los mapas de los perfiles.'),
                ]);
            ?>

            <input type="hidden" name="configName" value="<?= escape_html($centerName); ?>">
            <div class="three fields">
                <div class="required field">
                    <label for="mapbox-center-longitude"><?= __($langGroup, 'Longitud'); ?></label>
                    <input type="number" id="mapbox-center-longitude" step="any" min="-180" max="180" name="<?= escape_html($centerName); ?>[longitude]" value="<?= escape_html((string) $center['longitude']); ?>" required>
                </div>
                <div class="required field">
                    <label for="mapbox-center-latitude"><?= __($langGroup, 'Latitud'); ?></label>
                    <input type="number" id="mapbox-center-latitude" step="any" min="-90" max="90" name="<?= escape_html($centerName); ?>[latitude]" value="<?= escape_html((string) $center['latitude']); ?>" required>
                </div>
                <div class="required field">
                    <label for="mapbox-center-zoom"><?= __($langGroup, 'Acercamiento'); ?></label>
                    <input type="number" id="mapbox-center-zoom" step="any" min="0" max="22" name="<?= escape_html($centerName); ?>[zoom]" value="<?= escape_html((string) $center['zoom']); ?>" required>
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
