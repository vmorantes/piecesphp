<?php 
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
$langGroup = LOGIN_REPORT_LANG_GROUP;
?>
<section class="module-view-container">

    <div class="breadcrumb">
        <?= $breadcrumbs ?>
    </div>

    <div class="header-options">
        <div class="columns two">
            <div class="column">
                <div class="section-title">
                    <div class="title"><?=__($langGroup, $tittle);?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="not-access-cards">
        <div class="user-report-card">
            <div class="info">
                <div class="titlle-info">
                    <div class="icon-chart">
                        <i class="chart bar icon"></i>
                    </div>
                    <div class="tittle">
                        <span><?=__($langGroup, 'Reporte de Usuarios');?></span>
                        <small><?=__($langGroup, 'Sin ingreso');?></small>
                    </div>
                </div>
                <div class="chart-info">
                    <div class="info">
                        <span class="tag"><?=__($langGroup, 'Total Usuarios');?></span>
                        <span total-users class="value">0</span>
                    </div>
                    <div class="info red">
                        <span class="tag"><?=__($langGroup, 'Sin ingreso');?></span>
                        <span users-not-logged class="value">0</span>
                    </div>
                </div>
            </div>
            <div class="line-chart">
                <span class="total"></span>
                <span bar-chart-no-logged class="no-access"></span>
            </div>
        </div>
        <a class="card export" href="<?= $exportUrl ?>">
            <span><?=__($langGroup, 'Exportar datos');?></span>
            <i class="file export icon"></i>
        </a>
    </div>

    <div class="card-table">
        <table process="<?=get_route('informes-acceso-ajax');?>" class="ui table striped celled not-logged"
            style="max-width:100%;width:100%;">
            <thead>
                <tr>
                    <th><?= __($langGroup, 'ID'); ?></th>
                    <th><?= __($langGroup, 'Nombre'); ?></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

</section>

<script>
window.onload = function(e) {
    $('.tabular.menu .item').tab()
    let tableNotLogged = $('table.ui.table.not-logged')
    dataTableServerProccesing(tableNotLogged, tableNotLogged.attr('process'), 25, {
        drawCallback: function ({jqXHR}) {
           const { responseJSON: {recordsFiltered, recordsTotal} } = jqXHR
           $('[total-users]').text(recordsTotal)
           $('[users-not-logged]').text(recordsFiltered)
           $('[bar-chart-no-logged]').css('width',  (recordsFiltered * 100 / recordsTotal) + '%')
        },
    })

}
</script>
