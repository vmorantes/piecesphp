<?php
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;

//SurveyJS, con su vista real (design-sheet/resources/survey-js-creator.php): la pinta la ruta design-sheet-resources, que solo
//abre el usuario principal.
$source = ['source' => 'survey-js-creator'];
?>
<?php if (AdminPanelController::allowedRoute('design-sheet-resources', $source)): ?>
<iframe src="<?= escape_html(AdminPanelController::routeName('design-sheet-resources', $source)); ?>" title="<?= escape_html(__($langGroup, 'Creador de formularios (SurveyJS)')); ?>" width="100%" height="800" frameborder="0"></iframe>
<?php else: ?>
<div class="ui info message"><?= __($langGroup, 'La vista previa de SurveyJS solo la abre el usuario principal.'); ?></div>
<?php endif; ?>
