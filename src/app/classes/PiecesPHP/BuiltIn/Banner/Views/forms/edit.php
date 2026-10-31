<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\BuiltIn\Banner\Mappers\BuiltInBannerMapper;

/**
 * @var BuiltInBannerMapper $element
 */

/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 */
$BuiltInBannerConfiguration = get_config('BuiltInBannerConfiguration');
?>
<section class="module-view-container">

    <div class="limiter-content">

        <?php
            //La cabecera del panel (diseno/01-contenidos.md §2.1).
            $this->helpController->_render('panel/built-in/content/page-header.php', [
                'breadcrumbs' => $breadcrumbs,
                'title' => $title,
                'description' => $description ?? '',
            ]);
        ?>

        <div class="ui secondary pointing menu tabs-controls content-tabs">
            <div class="active item" data-tab="basic"><?= __($langGroup, 'Datos básicos'); ?></div>
        </div>

        <?php if($manyLangs): ?>
        <div class="ui form">
            <div class="field required">
                <label><?= __($langGroup, 'Idiomas'); ?></label>
                <select required class="ui dropdown search langs">
                    <?= $allowedLangs; ?>
                </select>
            </div>
        </div>
        <?php endif; ?>

        <form method='POST' action="<?= $action; ?>" class="ui form initial built-in-banner" data-content-form>

            <div class="container-standard-form">

                <input type="hidden" name="id" value="<?= $element->id; ?>">
                <input type="hidden" name="lang" value="<?= $lang; ?>">

                <div class="ui tab active" data-tab="basic">

                    <div class="two fields">
                        <?php
                            $this->helpController->_render('panel/built-in/content/image-field.php', [
                                'card' => [
                                    'id' => 'desktop-image',
                                    'title' => __($langGroup, 'Imagen'),
                                    'hint' => __($langGroup, 'Imagen preferiblemente en formato .jpg'),
                                    'image' => (string) $element->getLangData($lang, 'desktopImage', false, ''),
                                    'field' => 'desktopImage',
                                    'width' => (int) $BuiltInBannerConfiguration['desktop']['referenceW'],
                                    'height' => (int) $BuiltInBannerConfiguration['desktop']['referenceH'],
                                ],
                            ]);
                            $this->helpController->_render('panel/built-in/content/image-field.php', [
                                'card' => [
                                    'id' => 'mobile-image',
                                    'title' => __($langGroup, 'Imagen teléfonos'),
                                    'hint' => __($langGroup, 'Imagen preferiblemente en formato .jpg'),
                                    'image' => (string) $element->getLangData($lang, 'mobileImage', false, ''),
                                    'field' => 'mobileImage',
                                    'width' => (int) $BuiltInBannerConfiguration['mobile']['referenceW'],
                                    'height' => (int) $BuiltInBannerConfiguration['mobile']['referenceH'],
                                ],
                            ]);
                        ?>
                    </div>

                    <div class="field">
                        <label><?= __($langGroup, 'Posición'); ?></label>
                        <input type="number" name="orderPosition" value="<?= $element->getLangData($lang, 'orderPosition', false, ''); ?>" min="0">
                    </div>

                    <div class="field">
                        <label><?= __($langGroup, 'Enlace'); ?></label>
                        <input type="text" name="link" value="<?= escape_html($element->getLangData($lang, 'link', false, '')); ?>">
                    </div>

                    <div class="two fields">

                        <div class="field" calendar-group-js='periodo' start>
                            <label><?= __($langGroup, 'Iniciar'); ?></label>
                            <input type="text" name="startDate" autocomplete="off" value="<?= $element->startDate !== null ? $element->startDate->format('Y-m-d h:i:s A') : ''; ?>">
                        </div>

                        <div class="field" calendar-group-js='periodo' end>
                            <label><?= __($langGroup, 'Finalizar'); ?></label>
                            <input type="text" name="endDate" autocomplete="off" value="<?= $element->endDate !== null ? $element->endDate->format('Y-m-d h:i:s A') : ''; ?>">
                        </div>

                    </div>
                    <div class="field">
                        <label><?= __($langGroup, 'Nombre'); ?></label>
                        <input type="text" name="title" maxlength="300" value="<?= escape_html($element->getLangData($lang, 'title', false, '')); ?>" placeholder="">
                    </div>

                    <div class="field">
                        <label><?= __($langGroup, 'Contenido'); ?></label>
                        <div rich-editor-adapter-component></div>
                        <?php //Escapado: un </textarea> en el contenido se saldría del campo; el editor lee el .value, que llega decodificado. ?>
                        <textarea name="content"><?= htmlspecialchars((string) $element->getLangData($lang, 'content', false, ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></textarea>
                    </div>

                </div>

            </div>

            <?php
                //La barra aparte (§2.3). «Eliminar», secundaria en rojo y type=button, con lo que busca delete-config.js.
                $this->helpController->_render('panel/built-in/content/form-action-bar.php', [
                    'saveText' => __($langGroup, 'Guardar'),
                    'secondaryActions' => $allowDelete ? [[
                        'text' => __($langGroup, 'Eliminar'),
                        'type' => 'button',
                        'icon' => 'trash alternate',
                        'danger' => true,
                        'attributes' => ['delete-built-in-banner-button' => '', 'data-route' => $deleteRoute],
                    ]] : [],
                ]);
            ?>

        </form>

    </div>

</section>

<?php $this->helpController->_render('panel/built-in/content/image-editor.php'); ?>
