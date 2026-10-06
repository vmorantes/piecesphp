<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\Settings\Controllers\SettingsController;
$langGroup = SettingsController::LANG_GROUP;
?>

<main class="routes-view">

    <section class="main-body-header">

        <div class="head">
            <h2 class="tittle"><?= __($langGroup, 'Rutas y permisos'); ?></h2>
            <span class="sub-tittle"><?= __($langGroup, 'Sistema'); ?></span>
        </div>

        <div class="body-card max">

        <div>

        <p><?= __($langGroup, 'Descripción_Routes_And_Permissions'); ?></p>

        <table class="ui celled padded table roles">
            <thead>
                <tr>
                    <th><?= __($langGroup, 'Nombre'); ?></th>
                    <th><?= __($langGroup, 'Definición'); ?></th>
                    <th><?= __($langGroup, 'Ruta'); ?></th>
                    <th><?= __($langGroup, 'Clase'); ?></th>
                    <th><?= __($langGroup, 'Método'); ?></th>
                    <th><?= __($langGroup, 'Roles con acceso'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($routes as $name => $information) : ?>
                <?php if (!is_string($information['controller'])) continue; ?>
                <tr>
                    <td><?= $information['name']; ?></td>
                    <td><?= $information['route']; ?></td>
                    <td><?= str_replace(baseurl(), '', get_route_sample($information['name'])); ?></td>
                    <td><?= explode(':', $information['controller'])[0]; ?></td>
                    <td><?= explode(':', $information['controller'])[1]; ?></td>
                    <td><?= $information['require_login'] ? '- ' . implode('<br>- ', get_route_roles_allowed($name, 'name')) : __($langGroup, 'No requiere autenticación'); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        </div>

        </div>

    </section>

</main>

<script>
window.addEventListener('load', function(e) {
    let config = Object.assign(pcsphpGlobals.configDataTables, {
        drawCallback: function(settings) {
            console.log('Draw occurred at: ' + new Date().getTime());
        },
        pageLength: 10,
        responsive: false,
    })
    let table = $('.ui.table.roles').DataTable(config)
})
</script>
