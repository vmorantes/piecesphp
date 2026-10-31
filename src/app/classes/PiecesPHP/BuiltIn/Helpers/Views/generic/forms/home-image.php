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
            $this->helpController->_render('panel/built-in/content/page-header.php', [
                'breadcrumbs' => $breadcrumbs,
                'title' => $title,
                'description' => $description,
            ]);
        ?>

        <form method='POST' action="<?= escape_html($action); ?>" class="ui form initial generic" data-content-form>

            <div class="container-standard-form mw-800">

                <input type="hidden" name="configName" value="<?= escape_html($contentName); ?>">
                <?php foreach($allowedLangs as $lang): ?>
                <?php $value = $element->getLangData($lang, $contentName, true, ''); ?>
                <?php $isMultilang = $element->isTranslatable($contentName); ?>
                <?php $langSuffix = $isMultilang ? " ({$lang})" : ''; ?>
                <?php if($isMultilang || $lang === $defaultLang): ?>
                <?php
                    $this->helpController->_render('panel/built-in/content/image-field.php', [
                        'card' => [
                            'id' => "main-image-{$lang}",
                            'title' => __($langGroup, 'Imagen principal') . $langSuffix,
                            'hint' => __($langGroup, 'Imagen preferiblemente en formato .jpg'),
                            'image' => (string) $value,
                            'field' => "{$contentName}[{$lang}]",
                            'width' => 1200,
                            'height' => 900,
                        ],
                    ]);
                ?>
                <?php endif; ?>
                <?php endforeach; ?>

            </div>

            <?php
                $this->helpController->_render('panel/built-in/content/form-action-bar.php', [
                    'saveText' => __($langGroup, 'Guardar'),
                ]);
            ?>

        </form>

    </div>

</section>
<?php $this->helpController->_render('panel/built-in/content/image-editor.php'); ?>
