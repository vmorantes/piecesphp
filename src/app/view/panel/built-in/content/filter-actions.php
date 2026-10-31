<?php
/**
 * Los botones de un formulario de filtros (design.md §3.24): Filtrar, y Limpiar a su lado; en el móvil se reparten el
 * ancho. Sin `clearUrl`, Limpiar es un `reset`, para el JavaScript que filtra sin recargar; con ella, un enlace, para un
 * formulario que filtra por GET. Descartable.
 *
 * @var string $clearUrl Opcional.
 */
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;

$langGroup = AdminPanelController::ADMIN_LANG_GROUP;
$clearUrl = isset($clearUrl) && is_string($clearUrl) ? $clearUrl : '';
?>
<div class="ui mobile equal width flex">
    <button type="submit" class="ui primary labeled icon button"><i class="filter icon" aria-hidden="true"></i><?= __($langGroup, 'Filtrar'); ?></button>
    <?php if ($clearUrl !== ''): ?>
    <a class="ui basic button" href="<?= escape_html($clearUrl); ?>"><?= __($langGroup, 'Limpiar'); ?></a>
    <?php else: ?>
    <button type="reset" class="ui basic button"><?= __($langGroup, 'Limpiar'); ?></button>
    <?php endif; ?>
</div>
