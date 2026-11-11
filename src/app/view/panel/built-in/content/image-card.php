<?php
/**
 * La tarjeta de imagen (patrón `tarjeta-imagen`, design.md §3.18): vista previa sobre damero, medida, textos, «Cambiar
 * imagen» y «Descargar». `article.card` con la variación `checkered image` del fork: va dentro de `ui horizontal cards`.
 * El archivo y el recorte los lleva `ContentImageEditor.js`, con la pieza `image-editor.php`, una por página. Descartable.
 *
 * @var string $id Identificador de la tarjeta, el de `editor.onApply()`.
 * @var string $title
 * @var string $headingTag 'h2' o 'h3', según la vista: nunca un salto desde su título. Por omisión, 'h3'.
 * @var int $width Ancho de salida, en píxeles.
 * @var int $height Alto de salida, en píxeles.
 * @var string $description Dónde se usa la imagen.
 * @var string $hint Recomendación y formatos: coincide con `accept`.
 * @var string $accept El `accept` del selector de archivo.
 * @var string $image La imagen actual. Vacía, la variante Vacía: el mismo Cambiar imagen, sin Descargar.
 * @var string $imageAlt Por omisión, el título.
 * @var bool $dark `inverted checkered image`, para probar el contraste de una imagen que irá sobre fondo oscuro.
 * @var string $field El campo del archivo al enviarlo.
 * @var string $action URL a la que se envía al aplicar. Sin ella, se entrega a `editor.onApply()`.
 * @var string $format 'image/png' o 'image/jpeg'.
 * @var float $quality Calidad de salida, si el formato la admite.
 * @var string $fillColor El color del fondo de la salida.
 * @var string $scope Opcional. El alcance de su editor (`image-editor.php`).
 * @var bool $required El archivo es obligatorio: la tarjeta va dentro de un `.field` con su `label` oculta, de donde
 * genericFormHandler saca el nombre y donde pinta el error. Al aplicar una imagen deja de serlo.
 * @var bool $invertOnDarkSaved Ya tiene opción guardada: sin ella, una imagen propia recién subida la desmarca (brandImageInvertsOnDark()).
 * @var bool|null $invertOnDark Con valor, pinta la casilla «Invertir colores en modo oscuro» con ese estado (logos-favicons.js la guarda).
 * @var string|null $deleteValue Con valor, pinta «Eliminar» con él en `data-value` (oculto mientras no haya imagen); quien
 * la usa escucha `[data-image-card-delete]`. Sin él, la tarjeta no se puede vaciar.
 * @var string $deleteDisabledReason No vacío, «Eliminar» va deshabilitado y el tooltip de su envoltorio lo explica.
 * @var string $emptyText Opcional. Sin imagen, este texto sobre el damero (p. ej. «Sin foto»); el editor lo quita al aplicar.
 */
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;

$langGroup = AdminPanelController::ADMIN_LANG_GROUP;
$field = isset($field) && is_string($field) ? $field : '';
$id = isset($id) && is_string($id) && $id !== '' ? $id : "image-card-{$field}";
$title = isset($title) && is_string($title) ? $title : '';
$headingTag = ($headingTag ?? 'h3') === 'h2' ? 'h2' : 'h3';
$width = isset($width) && is_int($width) ? $width : 0;
$height = isset($height) && is_int($height) ? $height : 0;
$description = isset($description) && is_string($description) ? $description : '';
$hint = isset($hint) && is_string($hint) ? $hint : '';
$accept = isset($accept) && is_string($accept) ? $accept : 'image/*';
$image = isset($image) && is_string($image) ? $image : '';
$imageAlt = isset($imageAlt) && is_string($imageAlt) && $imageAlt !== '' ? $imageAlt : $title;
$dark = isset($dark) && $dark === true;
$action = isset($action) && is_string($action) ? $action : '';
$format = ($format ?? 'image/png') === 'image/jpeg' ? 'image/jpeg' : 'image/png';
$quality = isset($quality) && is_numeric($quality) ? (float) $quality : 0.8;
$fillColor = isset($fillColor) && is_string($fillColor) ? $fillColor : 'transparent';
$scope = isset($scope) && is_string($scope) ? $scope : '';
$required = isset($required) && $required === true;
$invertOnDark = isset($invertOnDark) && is_bool($invertOnDark) ? $invertOnDark : null;
$deleteValue = isset($deleteValue) && is_string($deleteValue) ? $deleteValue : null;
$deleteDisabledReason = isset($deleteDisabledReason) && is_string($deleteDisabledReason) ? $deleteDisabledReason : '';
$emptyText = isset($emptyText) && is_string($emptyText) ? $emptyText : '';

$hasImage = $image !== '';
$imageURL = $hasImage ? add_cache_stamp_to_url($image) : '';
$fileId = "{$id}-file";
$titleId = "{$id}-title";
$downloadText = __($langGroup, 'Descargar / ver');
$deleteText = __($langGroup, 'Eliminar');
$editText = strReplaceTemplate(__($langGroup, 'Editar imagen: ${title}'), ['${title}' => escape_html($title)]);
?>
<article class="card" id="<?= escape_html($id); ?>" aria-labelledby="<?= escape_html($titleId); ?>" data-image-card="<?= escape_html($scope); ?>" data-width="<?= $width; ?>" data-height="<?= $height; ?>" data-field="<?= escape_html($field); ?>" data-action="<?= escape_html($action); ?>" data-format="<?= $format; ?>" data-quality="<?= $quality; ?>" data-fill-color="<?= escape_html($fillColor); ?>" data-title="<?= escape_html($title); ?>" data-accept="<?= escape_html($accept); ?>" data-edit-label="<?= $editText; ?>">
    <div class="<?= $dark || $invertOnDark === true ? 'inverted ' : ''; ?><?= $invertOnDark === true ? 'content-image-card-inverted ' : ''; ?>checkered image">
        <?php if ($hasImage): ?>
        <button class="content-image-card-edit" type="button" aria-label="<?= $editText; ?>" data-image-card-edit><img class="ui small image" src="<?= escape_html($imageURL); ?>" alt="<?= escape_html($imageAlt); ?>"></button>
        <?php elseif ($emptyText !== ''): ?>
        <div class="fluid ui centered flex" data-image-card-empty><span class="ui muted text"><?= escape_html($emptyText); ?></span></div>
        <?php endif; ?>
    </div>
    <div class="ui vertical relaxed flex content">
        <div class="ui vertical left aligned tight flex">
            <<?= $headingTag; ?> class="ui medium header" id="<?= escape_html($titleId); ?>"><?= escape_html($title); ?></<?= $headingTag; ?>>
            <span class="ui label"><b><?= $width; ?> × <?= $height; ?> px</b></span>
        </div>
        <div class="ui vertical tight flex">
            <?php if ($description !== ''): ?>
            <p><?= escape_html($description); ?></p>
            <?php endif; ?>
            <?php if ($hint !== ''): ?>
            <small class="ui muted text"><?= escape_html($hint); ?></small>
            <?php endif; ?>
        </div>
        <div class="ui flex">
            <button class="mobile stretched ui basic labeled icon button" type="button" data-input="<?= escape_html($fileId); ?>"><i class="upload icon" aria-hidden="true"></i><?= __($langGroup, 'Cambiar imagen'); ?></button>
            <input type="file" id="<?= escape_html($fileId); ?>" accept="<?= escape_html($accept); ?>"<?= $required ? ' required' : ''; ?> hidden>
            <?php if ($hasImage): ?>
            <a class="ui icon button" href="<?= escape_html($imageURL); ?>" download aria-label="<?= $downloadText; ?>" data-image-card-download data-tooltip="<?= $downloadText; ?>" data-position="top center" data-inverted=""><i class="download icon" aria-hidden="true"></i></a>
            <?php else: ?>
            <?php //El Descargar de la tarjeta que se llena al aplicar: el JavaScript no lleva textos. ?>
            <template data-image-card-download-template>
                <a class="ui icon button" href="#" download aria-label="<?= $downloadText; ?>" data-image-card-download data-tooltip="<?= $downloadText; ?>" data-position="top center" data-inverted=""><i class="download icon" aria-hidden="true"></i></a>
            </template>
            <?php endif; ?>
            <?php if ($deleteValue !== null): ?>
            <?php //El envoltorio lleva el tooltip: un botón deshabilitado no recibe el puntero. Un div: el tema subraya span[data-tooltip]. ?>
            <div data-image-card-delete-holder data-tooltip="<?= $deleteDisabledReason !== '' ? escape_html($deleteDisabledReason) : $deleteText; ?>" data-position="top center" data-inverted=""<?= $hasImage ? '' : ' hidden'; ?>>
                <button class="ui basic negative icon button" type="button" aria-label="<?= $deleteText; ?>" data-image-card-delete data-value="<?= escape_html($deleteValue); ?>"<?= $deleteDisabledReason !== '' ? ' disabled' : ''; ?>><i class="trash alternate icon" aria-hidden="true"></i></button>
            </div>
            <?php endif; ?>
        </div>
        <?php if ($invertOnDark !== null): ?>
        <?php
            $this->_render('panel/built-in/content/toggle-field.php', [
                'id' => "{$id}-invert",
                'label' => __($langGroup, 'Invertir colores en modo oscuro'),
                'description' => __($langGroup, 'Necesita un PNG con fondo transparente: un fondo opaco saldría como un rectángulo blanco.'),
                'checked' => $invertOnDark,
                'attributes' => ['data-image-card-invert' => $field, 'data-saved' => isset($invertOnDarkSaved) && $invertOnDarkSaved === true ? 'yes' : 'no'],
            ]);
        ?>
        <?php endif; ?>
    </div>
</article>
