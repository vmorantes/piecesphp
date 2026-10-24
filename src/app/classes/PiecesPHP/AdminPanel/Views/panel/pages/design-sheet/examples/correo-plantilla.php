<?php
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;

//La plantilla de correo del framework (src/app/view/mailing/template_base.php), con datos de muestra: la pinta la ruta
//design-sheet-resources, que solo abre el usuario principal.
$source = ['source' => 'mail-users-template'];
?>
<?php if (AdminPanelController::allowedRoute('design-sheet-resources', $source)): ?>
<iframe src="<?= escape_html(AdminPanelController::routeName('design-sheet-resources', $source)); ?>" title="<?= escape_html(__($langGroup, 'Plantilla de correo')); ?>" width="100%" height="800" frameborder="0"></iframe>
<?php else: ?>
<div class="ui info message"><?= __($langGroup, 'La vista previa del correo solo la abre el usuario principal.'); ?></div>
<?php endif; ?>
