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
                <div class="field required">
                    <label><?= __($langGroup, 'Límite de tokens de IA') . $langSuffix; ?></label>
                    <input type="number" step="1" name="<?= $contentName; ?>[<?= $lang; ?>]" value="<?= $value; ?>" required>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>

                <?php //Lo mide el informe genérico; nada lo compara antes de llamar a la IA (APIController::recordTokensUsed() suma después). ?>
                <div class="ui info visible message">
                    <?= __($langGroup, 'Solo informativo: es la cuota contratada y sirve para calcular los tokens restantes del informe. No limita el uso de la inteligencia artificial.'); ?>
                </div>

            </div>

            <?php
                //La barra al final del formulario (§2.3), como en el resto de Configuración: «Cancelar» vuelve a su índice.
                $this->helpController->_render('panel/built-in/content/form-action-bar.php', [
                    'saveText' => __($langGroup, 'Guardar'),
                    'secondaryActions' => [['text' => __($langGroup, 'Cancelar'), 'url' => $indexLink, 'icon' => 'times']],
                ]);
            ?>

        </form>

    </div>

</section>
