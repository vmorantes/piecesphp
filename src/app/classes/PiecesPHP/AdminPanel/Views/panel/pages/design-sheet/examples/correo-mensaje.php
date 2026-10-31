<?php
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;

//El correo se pinta en SU propia petición (design-sheet-resources incluye este mismo archivo con $inResource): pintado
//dentro de la página, el new BaseController() de las vistas de correo vaciaría los assets de esta.
if (isset($inResource) && $inResource === true) {
    //La llamada de un módulo: título y «etiqueta: valor», tal cual, aunque los haya escrito un usuario; la vista escapa todo.
    $this->helpController->render('mailing/generic-dynamic-message', [
        'title' => __($langGroup, 'Nuevo mensaje de contacto'),
        'message' => [
            __($langGroup, 'Nombre') => 'Ana <b>Pérez</b>',
            __($langGroup, 'Mensaje') => "Hola.\nQuisiera más información.",
        ],
    ]);
    return;
}
$source = ['source' => 'mail-generic-dynamic-message'];
?>
<?php if (AdminPanelController::allowedRoute('design-sheet-resources', $source)): ?>
<iframe src="<?= escape_html(AdminPanelController::routeName('design-sheet-resources', $source)); ?>" title="<?= escape_html(__($langGroup, 'Correo con etiquetas')); ?>" width="100%" height="600" frameborder="0"></iframe>
<?php else: ?>
<div class="ui info message"><?= __($langGroup, 'La vista previa del correo solo la abre el usuario principal.'); ?></div>
<?php endif; ?>
