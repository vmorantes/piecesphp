<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
//El buscador de la barra (Ctrl K, atajo del shell): lo que este usuario puede abrir, sin pedir nada al servidor. Lo inicia
//panelSearch() (`admin-area/js/main.js`). La lista sale de PanelSearchSource, la misma que usa la herramienta MCP panel_find.
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\AdminPanel\PanelSearchSource;

$searchLang = AdminPanelController::ADMIN_LANG_GROUP;
$searchSource = PanelSearchSource::entries(getLoggedFrameworkUserOrFail());
?>
<div class="ui category search" panel-search data-source="<?= escape_html(jsonEncodeFallbackNull($searchSource) ?? '[]'); ?>" data-no-results="<?= escape_html(__($searchLang, 'Ninguna funcionalidad coincide con la búsqueda.')); ?>" data-no-results-header="<?= escape_html(__($searchLang, 'Sin resultados')); ?>">
    <div class="ui left icon input">
        <input class="prompt" type="text" placeholder="<?= escape_html(__($searchLang, 'Buscar funcionalidad…')); ?>" aria-label="<?= escape_html(__($searchLang, 'Buscar funcionalidad')); ?>" autocomplete="off">
        <i class="search icon" aria-hidden="true"></i>
        <span class="shortcut" aria-hidden="true"><kbd>Ctrl</kbd><kbd>K</kbd></span>
    </div>
    <div class="results"></div>
</div>
