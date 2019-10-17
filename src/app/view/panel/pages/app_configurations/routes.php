<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>"); ?>

<div class="container-medium">

    <div class="ui top attached tabular menu">
        <a class="item active" data-tab="general"><?= __('routesViewAdminZone', 'Rutas y permisos'); ?></a>
    </div>

    <div class="ui bottom attached tab segment active" data-tab="general">

        <table class="ui table stripped celled roles" style="max-width:100%;">
            <thead>
                <tr>
                    <th><?= __('routesViewAdminZone', 'Nombre'); ?></th>
                    <th><?= __('routesViewAdminZone', 'Definición'); ?></th>
                    <th><?= __('routesViewAdminZone', 'Ruta'); ?></th>
                    <th><?= __('routesViewAdminZone', 'Clase'); ?></th>
                    <th><?= __('routesViewAdminZone', 'Método'); ?></th>
                    <th><?= __('routesViewAdminZone', 'Roles con acceso'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($routes as $name => $information): ?>
                <tr>
                    <td><?=$information['name'];?></td>
                    <td><?=$information['route'];?></td>
                    <td><?=str_replace(baseurl(), '', get_route_sample($information['name']));?></td>
                    <td><?=explode(':', $information['controller'])[0];?></td>
                    <td><?=explode(':', $information['controller'])[1];?></td>
                    <td><?=$information['require_login'] ? '- ' . implode('<br>- ', get_route_roles_allowed($name, 'name')) : __('routesViewAdminZone', 'No requiere autenticación');?>
                    </td>
                </tr>
                <?php endforeach;?>
            </tbody>
        </table>

    </div>

</div>

<script>
window.addEventListener('load', function(e) {
    let config = Object.assign({
        drawCallback: function(settings) {
            console.log('Draw occurred at: ' + new Date().getTime());
        }
    }, pcsphpGlobals.configDataTables)
    let table = $('.ui.table.roles').DataTable(config)
})
</script>
<style>
.ui.form {
    max-width: 800px;
}
</style>
