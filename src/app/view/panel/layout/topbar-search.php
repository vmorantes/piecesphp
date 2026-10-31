<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
//El buscador de la barra (Ctrl K, atajo del shell): lo que este usuario puede abrir, sin pedir nada al servidor. Lo inicia
//panelSearch() (`admin-area/js/main.js`). Sale de las mismas fuentes que los menús: cada entrada ya viene filtrada por permisos.
use MySpace\Controllers\MySpaceController;
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\UserSystem\Controllers\UsersController;

require __DIR__ . '/topbar-data.php';
$searchLang = AdminPanelController::ADMIN_LANG_GROUP;
$searchSource = [];
$searchAdd = function (string $category, string $title, string $url) use (&$searchSource): void {
    if ($url !== '' && $url !== '#') {
        $searchSource[] = ['category' => $category, 'title' => $title, 'url' => $url];
    }
};
$categoryModules = __($searchLang, 'Módulos');
$categoryConfiguration = __($searchLang, 'Configuración');
$categoryAccount = __($searchLang, 'Cuenta');
foreach (get_sidebar_menu()->getItems() as $element) {
    if (!$element->isVisible()) {
        continue;
    }
    if ($element->asLink()) {
        ($searchAdd)($categoryModules, (string) $element->getName(), (string) $element->getHref());
    } else {
        foreach (array_filter($element->getItems(), fn($subElement) => $subElement->isVisible()) as $subElement) {
            ($searchAdd)($categoryModules, "{$element->getName()} › {$subElement->getText()}", (string) $subElement->getHref());
        }
    }
}
foreach ($adminOptionsGroups as $groupName => $group) {
    if ($group['visible']) {
        foreach ($group['collection']->getItems() as $item) {
            if ($item->isVisible()) {
                ($searchAdd)($categoryConfiguration, "{$groupName} › {$item->getName()}", (string) $item->getHref());
            }
        }
    }
}
if (UsersController::allowedRoute('form-profile')) {
    ($searchAdd)($categoryAccount, __($searchLang, 'Editar Cuenta'), (string) UsersController::routeName('form-profile'));
}
if (MySpaceController::allowedRoute('user-security')) {
    ($searchAdd)($categoryAccount, __($searchLang, 'Seguridad'), (string) MySpaceController::routeName('user-security'));
}
foreach ($userOptionsEnd->getItems() as $item) {
    if ($item->isVisible() && $item->asLink()) {
        ($searchAdd)($categoryAccount, (string) $item->getName(), (string) $item->getHref());
    }
}
?>
<div class="ui category search" panel-search data-source="<?= escape_html(jsonEncodeFallbackNull($searchSource) ?? '[]'); ?>" data-no-results="<?= escape_html(__($searchLang, 'Ninguna funcionalidad coincide con la búsqueda.')); ?>" data-no-results-header="<?= escape_html(__($searchLang, 'Sin resultados')); ?>">
    <div class="ui left icon input">
        <input class="prompt" type="text" placeholder="<?= escape_html(__($searchLang, 'Buscar funcionalidad…')); ?>" aria-label="<?= escape_html(__($searchLang, 'Buscar funcionalidad')); ?>" autocomplete="off">
        <i class="search icon" aria-hidden="true"></i>
        <span class="shortcut" aria-hidden="true"><kbd>Ctrl</kbd><kbd>K</kbd></span>
    </div>
    <div class="results"></div>
</div>
