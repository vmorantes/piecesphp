<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * La vista en modo oscuro de una imagen de marca (H340), dentro de su tarjeta (logos-favicons.php, `extraContentHtml` de
 * image-card.php): el selector «En modo oscuro» y la vista previa sobre superficie oscura, que es otra tarjeta del editor
 * (la de la imagen oscura, con su recorte). logos-favicons.js guarda el modo y la refleja.
 *
 * @var string $langGroup
 * @var string $actionURL
 * @var string $image La clave de SettingsController::BRAND_IMAGES_DARK_MODE
 * @var string $cardId El id de la tarjeta de la clara
 * @var string $title El de la tarjeta de la clara, ya traducido
 * @var string $lightImage La imagen clara
 * @var int $width
 * @var int $height
 * @var bool $direct Se sube tal cual, sin recorte (la animación de carga): GIF o, si $canUploadSvg, SVG
 * @var bool $canUploadSvg
 * @var string $accept No vacío, el `accept` de la oscura (el de su clara); vacío, el que sale de $direct
 * @var string $emptyText No vacío, sin imagen que enseñar la vista previa es un damero de `width` × `height` con este rótulo
 * Deja en `$darkControlsHtml` el selector «En modo oscuro» y en `$darkPreviewHtml` la vista previa oscura.
 * @var array{mode:string,saved:bool,onReplace:string,image:string,factoryDark:bool,field:string,view:array{mode:string,image:string},fallbackMode:string} $dark
 * `view` es SettingsController::brandImageDarkView(): la vista previa y su «Descargar» enseñan eso y nada más.
 */
use PiecesPHP\Settings\Controllers\SettingsController;

$isImage = $dark['mode'] === SettingsController::BRAND_IMAGE_DARK_IMAGE;
$viewIsImage = $dark['view']['mode'] === SettingsController::BRAND_IMAGE_DARK_IMAGE;
$previewSrc = $dark['view']['image'];
$darkTitle = strReplaceTemplate(__($langGroup, '${title} en modo oscuro'), ['${title}' => $title]);
$downloadText = __($langGroup, 'Descargar / ver');
$download = '<a class="ui small icon button" href="' . escape_html($previewSrc !== '' ? add_cache_stamp_to_url($previewSrc) : '#') . '" download aria-label="' . escape_html($downloadText) . '" data-image-card-download data-tooltip="' . escape_html($downloadText) . '" data-position="top center" data-inverted=""><i class="download icon" aria-hidden="true"></i></a>';
$darkEdit = strReplaceTemplate(__($langGroup, 'Editar imagen: ${title}'), ['${title}' => $darkTitle]);
//Sin imagen, oculta y con un GIF transparente de 1 px: un `src` vacío es una imagen rota.
//Lo que se sube tal cual (la animación) se pinta a su medida real; lo recortado ya mide `width` × `height`.
$darkSize = $direct && $viewIsImage ? SettingsController::imageFileSize($previewSrc) : null;
$darkWidth = $darkSize['width'] ?? $width;
$darkHeight = $darkSize['height'] ?? $height;
$darkAccept = $accept !== '' ? $accept : ($direct ? ($canUploadSvg ? 'image/gif,image/svg+xml,.gif,.svg' : 'image/gif,.gif') : 'image/*');
$modes = [
    SettingsController::BRAND_IMAGE_DARK_SAME => __($langGroup, 'Igual'),
    SettingsController::BRAND_IMAGE_DARK_INVERT => __($langGroup, 'Invertir en blanco'),
    SettingsController::BRAND_IMAGE_DARK_IMAGE => __($langGroup, 'Otra imagen'),
];
//Dos piezas: el selector, con el texto de la tarjeta, y la vista previa oscura, junto a la clara (`previewsBelow`). Su fila
//«Elegir imagen oscura» va bajo las dos vistas a todo el ancho (_content-screens.scss), y pequeña: a 390 px cabe en una línea con «Descargar».
ob_start();
?>
<div class="ui form">
    <div class="field">
        <label for="<?= escape_html("{$cardId}-dark-mode"); ?>"><?= escape_html(__($langGroup, 'En modo oscuro')); ?></label>
        <select class="ui dropdown" id="<?= escape_html("{$cardId}-dark-mode"); ?>" aria-describedby="<?= escape_html("{$cardId}-dark-mode-description"); ?>" data-brand-dark-mode="<?= escape_html($image); ?>" data-saved="<?= $dark['saved'] ? 'yes' : 'no'; ?>" data-on-replace="<?= escape_html($dark['onReplace']); ?>">
            <?php foreach ($modes as $modeValue => $modeText): ?>
            <option value="<?= escape_html($modeValue); ?>"<?= $modeValue === $dark['mode'] ? ' selected' : ''; ?>><?= escape_html($modeText); ?></option>
            <?php endforeach; ?>
        </select>
        <div class="description" id="<?= escape_html("{$cardId}-dark-mode-description"); ?>" data-brand-dark-invert-hint<?= $dark['mode'] === SettingsController::BRAND_IMAGE_DARK_INVERT ? '' : ' hidden'; ?>><?= escape_html(__($langGroup, 'Necesita un PNG con fondo transparente: un fondo opaco saldría como un rectángulo blanco.')); ?></div>
    </div>
</div>
<?php
$darkControlsHtml = (string) ob_get_clean();
ob_start();
?>
<div class="ui fluid card content-image-card-dark" id="<?= escape_html("{$cardId}-dark"); ?>" data-image-card="" data-brand-dark-preview="<?= escape_html($image); ?>" data-width="<?= $width; ?>" data-height="<?= $height; ?>" data-field="<?= escape_html($dark['field']); ?>" data-action="<?= escape_html($actionURL); ?>" data-format="image/png" data-quality="0.8" data-fill-color="transparent" data-title="<?= escape_html($darkTitle); ?>" data-accept="<?= escape_html($darkAccept); ?>" data-edit-label="<?= escape_html($darkEdit); ?>"<?= $direct ? ' data-direct-upload="yes"' : ''; ?> data-dark-image="<?= escape_html($dark['image'] !== '' ? add_cache_stamp_to_url($dark['image']) : ''); ?>" data-dark-image-factory="<?= $dark['factoryDark'] ? 'yes' : 'no'; ?>" data-dark-fallback-mode="<?= escape_html($dark['fallbackMode']); ?>" data-image-card-has-image="<?= $dark['image'] !== '' && !$dark['factoryDark'] ? 'yes' : 'no'; ?>">
    <span class="ui mini label content-image-card-caption"><?= escape_html(__($langGroup, 'Modo oscuro')); ?></span>
    <div class="inverted checkered image">
        <?php //Pulsarla reabre el editor, como en la clara; solo con «Otra imagen» (logos-favicons.js la deshabilita si no). ?>
        <button class="content-image-card-edit" type="button" aria-label="<?= escape_html($darkEdit); ?>" data-image-card-edit<?= $isImage ? '' : ' disabled'; ?>><img width="<?= $darkWidth; ?>" height="<?= $darkHeight; ?>"<?= $dark['view']['mode'] === SettingsController::BRAND_IMAGE_DARK_INVERT ? ' class="content-image-card-dark-invert"' : ''; ?> src="<?= escape_html($previewSrc !== '' ? add_cache_stamp_to_url($previewSrc) : 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw=='); ?>" alt="<?= escape_html($darkTitle); ?>"<?= $previewSrc === '' ? ' hidden' : ''; ?>></button>
        <?php if ($emptyText !== ''): ?>
        <?php //Después del botón: logos-favicons.js y el editor toman el primer <img> como la vista previa. ?>
        <div class="ui vertical centered tight flex" data-brand-dark-empty<?= $previewSrc !== '' ? ' hidden' : ''; ?>><img width="<?= $width; ?>" height="<?= $height; ?>" data-image-card-placeholder src="<?= escape_html("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='{$width}' height='{$height}'/%3E"); ?>" alt=""><span class="ui muted text"><?= escape_html($emptyText); ?></span></div>
        <?php endif; ?>
    </div>
    <div class="content" data-brand-dark-upload<?= $isImage ? '' : ' hidden'; ?>>
        <div class="ui flex">
            <button class="ui small basic labeled icon button" type="button" data-input="<?= escape_html("{$cardId}-dark-file"); ?>"><i class="upload icon" aria-hidden="true"></i><?= escape_html(__($langGroup, 'Elegir imagen oscura')); ?></button>
            <input type="file" id="<?= escape_html("{$cardId}-dark-file"); ?>" accept="<?= escape_html($darkAccept); ?>" hidden>
            <?php if ($previewSrc !== ''): ?>
            <?= $download; ?>
            <?php else: ?>
            <template data-image-card-download-template><?= $download; ?></template>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
$darkPreviewHtml = (string) ob_get_clean();
