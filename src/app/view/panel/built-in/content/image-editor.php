<?php
/**
 * El editor de la tarjeta de imagen (design.md §3.19): `ui fullscreen modal`, uno por página y fuera de todo formulario.
 * Lo maneja `ContentImageEditor.js`, que toma de aquí sus textos. Descartable.
 *
 * @var string $scope Opcional. El alcance: el mismo en las tarjetas que abre y en `new ContentImageEditor(scope)`.
 */
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;

$langGroup = AdminPanelController::ADMIN_LANG_GROUP;
$scope = isset($scope) && is_string($scope) ? $scope : '';
$editorId = $scope === '' ? 'image-editor' : "image-editor-{$scope}";
$tooltip = function (string $text, string $position): string {
    return 'aria-label="' . escape_html($text) . '" data-tooltip="' . escape_html($text) . '" data-position="' . $position . '" data-inverted=""';
};
?>
<div class="ui fullscreen modal" id="<?= escape_html($editorId); ?>" aria-labelledby="<?= escape_html($editorId); ?>-title" data-image-editor="<?= escape_html($scope); ?>" data-text-replaced="<?= __($langGroup, 'Imagen reemplazada'); ?>" data-text-replaced-detail="<?= __($langGroup, 'La imagen se actualizó correctamente.'); ?>" data-text-added="<?= __($langGroup, 'Imagen agregada'); ?>" data-text-added-detail="<?= __($langGroup, 'La imagen se agregó correctamente.'); ?>" data-text-error="<?= __($langGroup, 'No se pudo guardar la imagen'); ?>" data-text-error-detail="<?= __($langGroup, 'Inténtelo de nuevo. La imagen anterior sigue en su sitio.'); ?>" data-text-unreadable="<?= __($langGroup, 'No se pudo abrir el archivo'); ?>" data-text-unreadable-detail="<?= __($langGroup, 'El navegador no puede leer esa imagen. Pruebe con otro archivo.'); ?>" data-text-export="<?= __($langGroup, 'Se exportará a ${size}'); ?>">
    <i class="close icon" aria-label="<?= __($langGroup, 'Cerrar'); ?>"></i>
    <div class="header" id="<?= escape_html($editorId); ?>-title"><?= __($langGroup, 'Editar imagen'); ?> · <span data-image-editor-name></span> <span class="ui label" data-image-editor-size></span></div>
    <div class="content">
        <div class="ui stackable grid">
            <div class="ten wide column">
                <div class="ui checkered crop segment" data-image-editor-stage></div>
            </div>
            <div class="six wide column">
                <div class="ui form">
                    <div class="field">
                        <label id="<?= escape_html($editorId); ?>-zoom-title"><?= __($langGroup, 'Zoom'); ?></label>
                        <div class="ui nowrap flex">
                            <button type="button" class="ui icon button" data-image-editor-action="zoom-out" <?= $tooltip(__($langGroup, 'Alejar'), 'bottom left'); ?>><i class="search minus icon" aria-hidden="true"></i></button>
                            <div class="fluid ui slider" data-image-editor-zoom aria-labelledby="<?= escape_html($editorId); ?>-zoom-title"></div>
                            <button type="button" class="ui icon button" data-image-editor-action="zoom-in" <?= $tooltip(__($langGroup, 'Acercar'), 'bottom right'); ?>><i class="search plus icon" aria-hidden="true"></i></button>
                        </div>
                    </div>
                    <div class="field">
                        <label id="<?= escape_html($editorId); ?>-transform-title"><?= __($langGroup, 'Girar y voltear'); ?></label>
                        <div class="ui basic icon buttons" role="group" aria-labelledby="<?= escape_html($editorId); ?>-transform-title">
                            <button type="button" class="ui button" data-image-editor-action="rotate-left" <?= $tooltip(__($langGroup, 'Girar a la izquierda'), 'bottom left'); ?>><i class="undo icon" aria-hidden="true"></i></button>
                            <button type="button" class="ui button" data-image-editor-action="rotate-right" <?= $tooltip(__($langGroup, 'Girar a la derecha'), 'bottom center'); ?>><i class="redo icon" aria-hidden="true"></i></button>
                            <button type="button" class="ui button" data-image-editor-action="flip-horizontal" <?= $tooltip(__($langGroup, 'Voltear horizontal'), 'bottom center'); ?>><i class="arrows alternate horizontal icon" aria-hidden="true"></i></button>
                            <button type="button" class="ui button" data-image-editor-action="flip-vertical" <?= $tooltip(__($langGroup, 'Voltear vertical'), 'bottom center'); ?>><i class="arrows alternate vertical icon" aria-hidden="true"></i></button>
                            <button type="button" class="ui button" data-image-editor-action="reset" <?= $tooltip(__($langGroup, 'Restablecer'), 'bottom right'); ?>><i class="sync alternate icon" aria-hidden="true"></i></button>
                        </div>
                    </div>
                    <div class="field">
                        <label><?= __($langGroup, 'Resultado'); ?></label>
                        <div class="ui secondary segment">
                            <div class="ui centered flex"><div data-image-editor-preview></div></div>
                        </div>
                        <div class="description" data-image-editor-note></div>
                    </div>
                    <div class="field">
                        <button type="button" class="ui basic labeled icon button" data-image-editor-other><i class="folder open icon" aria-hidden="true"></i><?= __($langGroup, 'Seleccionar otro archivo'); ?></button>
                        <input type="file" data-image-editor-file hidden>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="actions">
        <button type="button" class="ui basic cancel button"><?= __($langGroup, 'Cancelar'); ?></button>
        <button type="button" class="ui primary approve button"><?= __($langGroup, 'Aplicar'); ?></button>
    </div>
</div>
