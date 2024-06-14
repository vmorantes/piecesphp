<?php 
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
$langGroup = LOGIN_REPORT_LANG_GROUP;
$percent =  intval(($successAttempts * 100) / $totalAttempts);
?>
<section class="module-view-container limit-size">

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

    <div class="attemps-cards">
        <div class="total-card">
            <div class="info">
                <span class="value"><?= $totalAttempts; ?></span>
                <span><?=__($langGroup, 'Intentos Totales');?></span>
            </div>
            <div class="rounded-chart" style="--percentage: <?= $percent; ?>; --text: <?= '#21BA45'; ?>; --fill: <?= '#DB2828' ?>; --fill2: <?= '#21BA45'; ?>;">
                <div class="content">
                    <span><?= $percent; ?>%</span>
                    <small><?=__($langGroup, 'Ingresado');?></small>
                </div>
            </div>
        </div>
        <div class="cards-grup">
            <div class="card">
                <div class="info">
                    <span><?= $successAttempts; ?></span>
                    <small><?=__($langGroup, 'Intentos exitosos');?></small>
                </div>
                <div class="chart"></div>
            </div>
            <div class="card red">
                <div class="info">
                    <span><?= $errorAttempts; ?></span>
                    <small><?=__($langGroup, 'Intentos fallidos');?></small>
                </div>
                <div class="chart"></div>
            </div>

            <a class="card export" href="<?= $exportUrl; ?>">
                <span><?=__($langGroup, 'Exportar datos');?></span>
                <i class="file export icon"></i>
            </a>
        </div>
    </div>

    <div class="card-table">
        <table process="<?=get_route('informes-acceso-ajax',['type'=>'attempts']);?>"
            class="ui basic table attempts no-border" style="max-width:100%;width:100%;">
            <thead>
                <tr>
                    <th><?= __($langGroup, 'Indicador'); ?></th>
                    <th><?= __($langGroup, 'Usuario ingresado'); ?></th>
                    <th><?= __($langGroup, 'Información'); ?></th>
                    <th><?= __($langGroup, 'IP'); ?></th>
                    <th><?= __($langGroup, 'Fecha'); ?></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

</section>

<script>
window.onload = function(e) {
    $('.tabular.menu .item').tab()
    let tableAttempts = $('table.ui.table.attempts')
    dataTableServerProccesing(tableAttempts, tableAttempts.attr('process'), 25)
}
</script>
