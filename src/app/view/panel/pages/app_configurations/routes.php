<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
$langGroup = 'routesViewAdminZone';
?>

<div class="container">

    <div class="pcs-header-options">

        <div class="main-options">

            <a href="<?= $backLink; ?>" class="ui icon button brand-color alt2" title="<?= __($langGroup, 'Regresar'); ?>">
                <i class="icon left arrow"></i>
            </a>

        </div>

        <div class="columns">

            <div class="column">

                <div class="section-title">
                    <div class="title"><?= __($langGroup, 'Rutas y Permisos'); ?></div>
                    <div class="subtitle"><?= __($langGroup, 'Configuración de Plataforma'); ?></div>
                </div>

            </div>

        </div>

    </div>

    <div style="max-width: 1500px; margin-top: 30px;">
        <table class="ui celled padded table roles" style="max-width:100%;">
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
<style>
.ui.form {
    max-width: 800px;
}
</style>
