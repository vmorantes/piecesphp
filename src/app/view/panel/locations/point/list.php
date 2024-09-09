<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<section class="module-view-container">

    <?php if(isset($breadcrumbs)): ?>
    <div class="breadcrumb">
        <?= $breadcrumbs ?>
    </div>
    <?php endif; ?>

    <div class="limiter-content">

        <div class="section-title">
            <div class="title"><?= $title ?></div>
            <?php if(isset($description) && is_string($description) && mb_strlen(trim($description)) > 0): ?>
            <div class="description"><?= $description; ?></div>
            <?php endif; ?>
        </div>

        <br>

        <div class="main-buttons">

            <?php if ($has_add_link_permissions) : ?>
            <a href="<?= $add_link; ?>" class="ui button green"><?= __(LOCATIONS_LANG_GROUP, 'Agregar'); ?></a>
            <?php endif; ?>

        </div>

        <br>

        <div class="mirror-scroll-x all" mirror-scroll-target=".container-standard-table.all">
            <div class="mirror-scroll-x-content"></div>
        </div>

        <div class="container-standard-table all">

            <table process="<?= $process_table; ?>" style='width:100%;' class="ui basic table">
                <thead>
                    <tr>
                        <th name='id' order='true' search='true'><?= __(LOCATIONS_LANG_GROUP, 'ID'); ?></th>
                        <th name='name' order='true' search='true'><?= __(LOCATIONS_LANG_GROUP, 'Nombre'); ?></th>
                        <th name='country' order='true' search='true'><?= __(LOCATIONS_LANG_GROUP, 'País'); ?></th>
                        <th name='state' order='true' search='true'><?= __(LOCATIONS_LANG_GROUP, 'Departamento'); ?></th>
                        <th name='city' order='true' search='true'><?= __(LOCATIONS_LANG_GROUP, 'Ciudad'); ?></th>
                        <th name='address' order='true' search='true'><?= __(LOCATIONS_LANG_GROUP, 'Dirección'); ?></th>
                        <th name='coords' order='true' search='true'><?= __(LOCATIONS_LANG_GROUP, 'Coordenadas'); ?></th>
                        <th name='active' order='true' search='true'><?= __(LOCATIONS_LANG_GROUP, 'Activo/Inactivo'); ?></th>
                        <th order='false' search='false'><?= __(LOCATIONS_LANG_GROUP, 'Acciones'); ?></th>
                    </tr>
                </thead>
            </table>

        </div>

    </div>

</section>
<script>
window.onload = () => {

    let table = $(`[process]`)
    let processURL = table.attr('process')
    dataTableServerProccesing(table, processURL, 10, {
        responsive: false,
        autoWidth: false,
        drawCallback: function() {
        },
        initComplete: function() {
            configMirrorScrollX('namespace.mirror-scroll-x.all', '.mirror-scroll-x.all')
        },
    })

}
</script>
