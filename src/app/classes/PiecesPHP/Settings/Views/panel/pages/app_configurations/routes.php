<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\Settings\Controllers\SettingsController;
$langGroup = SettingsController::LANG_GROUP;
/**
 * @var array<string,list<array{name:string,route:string,sample:string,shortController:string,controller:string,requireLogin:bool,roles:list<string>,allRoles:bool}>> $groups
 * @var string $title
 * @var string $description
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $helpController El que pinta las piezas del panel.
 */
//Una ruta o un controlador parten en sus separadores (/, \ y ::) sin cambiar el texto.
$breakable = fn (string $text): string => str_replace(['::', '/', '\\'], ['::<wbr>', '/<wbr>', '\\<wbr>'], escape_html($text));
?>

<section class="module-view-container routes-view" data-routes-view>

    <?php
        $helpController->_render('panel/built-in/content/page-header.php', [
            'title' => $title,
            'description' => $description,
        ]);
    ?>

    <section class="ui segment" aria-label="<?= escape_html($title); ?>">

        <?php //Solo lectura y en memoria (get_routes()): DataTables en el cliente; la columna Módulo, oculta, sirve para buscar. ?>
        <table class="ui selectable very basic padded fixed table routes-table" data-routes-table data-search-placeholder="<?= escape_html(__($langGroup, 'Nombre, ruta, controlador o rol')); ?>">
            <thead>
                <tr>
                    <th class="four wide"><?= __($langGroup, 'Nombre'); ?></th>
                    <th class="three wide"><?= __($langGroup, 'Ruta'); ?></th>
                    <th class="four wide"><?= __($langGroup, 'Controlador'); ?></th>
                    <th class="five wide"><?= __($langGroup, 'Roles con acceso'); ?></th>
                    <th><?= __($langGroup, 'Módulo'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($groups as $module => $routes) : ?>
                <?php foreach ($routes as $route) : ?>
                <tr>
                    <td data-order="<?= escape_html($route['name']); ?>">
                        <strong><?= escape_html($route['name']); ?></strong>
                        <br><span class="ui small muted text"><?= $breakable($route['route']); ?></span>
                    </td>
                    <td><?= $breakable($route['sample']); ?></td>
                    <td><span title="<?= escape_html($route['controller']); ?>"><?= $breakable($route['shortController']); ?></span></td>
                    <td>
                        <?php if (!$route['requireLogin']): ?>
                        <?= __($langGroup, 'No requiere autenticación'); ?>
                        <?php elseif ($route['allRoles']): ?>
                        <div class="ui labels"><span class="ui label"><?= __($langGroup, 'Todos los tipos de usuario'); ?></span></div>
                        <?php else: ?>
                        <div class="ui labels">
                            <?php foreach ($route['roles'] as $role): ?>
                            <span class="ui label"><?= escape_html($role); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td><?= escape_html($module); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>

    </section>

</section>
