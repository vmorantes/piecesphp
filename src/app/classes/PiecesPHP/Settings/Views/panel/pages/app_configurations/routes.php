<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\Settings\Controllers\SettingsController;
$langGroup = SettingsController::LANG_GROUP;
/**
 * @var array<string,list<array{name:string,route:string,sample:string,shortController:string,controller:string,requireLogin:bool,roles:list<string>,allRoles:bool}>> $groups
 * @var string $title
 * @var string $description
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $helpController El que pinta las piezas del panel.
 */
?>

<section class="module-view-container routes-view" data-routes-view>

    <div class="limiter-content">

        <?php
        $helpController->_render('panel/built-in/content/page-header.php', [
            'breadcrumbs' => $breadcrumbs,
            'title' => $title,
            'description' => $description,
        ]);
        ?>

        <div class="container-standard-table">

            <div class="ui fluid icon input routes-search">
                <input type="search" data-routes-search placeholder="<?= __($langGroup, 'Buscar por nombre, ruta, controlador o tipo de usuario'); ?>">
                <i class="search icon"></i>
            </div>

            <p class="routes-empty" data-routes-empty hidden><?= __($langGroup, 'Ninguna ruta coincide con la búsqueda.'); ?></p>

            <div class="ui styled fluid accordion" data-routes-accordion>
                <?php foreach ($groups as $module => $routes) : ?>
                <div class="title" data-routes-group>
                    <i class="dropdown icon"></i>
                    <?= escape_html($module); ?>
                    <span class="ui small grey text"><?= count($routes) === 1 ? __($langGroup, '1 ruta') : sprintf(__($langGroup, '%d rutas'), count($routes)); ?></span>
                </div>
                <div class="content">
                    <div class="routes-table-wrapper">
                        <table class="ui basic table roles">
                            <thead>
                                <tr>
                                    <th><?= __($langGroup, 'Nombre'); ?></th>
                                    <th><?= __($langGroup, 'Ruta'); ?></th>
                                    <th><?= __($langGroup, 'Controlador'); ?></th>
                                    <th><?= __($langGroup, 'Roles con acceso'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($routes as $route) : ?>
                                <tr data-search="<?= escape_html(implode(' ', array_merge([$route['name'], $route['route'], $route['sample'], $route['controller']], $route['roles']))); ?>">
                                    <td>
                                        <?= escape_html($route['name']); ?>
                                        <br><span class="ui small grey text"><?= escape_html($route['route']); ?></span>
                                    </td>
                                    <td><?= escape_html($route['sample']); ?></td>
                                    <td class="routes-controller"><span title="<?= escape_html($route['controller']); ?>"><?= escape_html($route['shortController']); ?></span></td>
                                    <td>
                                        <?php if (!$route['requireLogin']): ?>
                                        <?= __($langGroup, 'No requiere autenticación'); ?>
                                        <?php elseif ($route['allRoles']): ?>
                                        <span class="ui small label"><?= __($langGroup, 'Todos los tipos de usuario'); ?></span>
                                        <?php else: ?>
                                        <?php foreach ($route['roles'] as $role): ?>
                                        <span class="ui small label"><?= escape_html($role); ?></span>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        </div>

    </div>

</section>
