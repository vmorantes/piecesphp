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
                    <div class="title"><?= __($langGroup, $tittle); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="logged-cards">
        <div class="card-total-users">
            <div class="users-data">
                <h2 total-users>0</h2>
                <span><?= __($langGroup, 'Usuarios totales'); ?></span>
            </div>

            <div class="rounded-chart" style="--percentage: 0; --text: <?= 'var(--second-brand-color)'; ?>; --fill: <?= '#70707026' ?>; --fill2: <?= 'var(--second-brand-color)'; ?>;">
                <div class="content">
                    <span>0%</span>
                    <small><?= __($langGroup, 'Ingresado'); ?></small>
                </div>
            </div>

        </div>
        <div class="two-cards">
            <div class="card time">
                <span><?= __($langGroup, 'Tiempo de uso de la plataforma'); ?></span>
                <div class="hour">
                    <h3><?= $allTimeOnPlatform ?></h3>
                    <small><?= __($langGroup, 'Horas totales'); ?></small>
                </div>
            </div>
            <a class="card export" href="<?= $exportUrl; ?>">
                <span><?= __($langGroup, 'Exportar datos'); ?></span>
                <i class="file export icon"></i>
            </a>
        </div>
    </div>

    <div class="card-table">
        <table process="<?= get_route('informes-acceso-ajax', ['type' => 'logged']); ?>" class="ui basic table logged no-border" style="max-width:100%;width:100%;">
            <thead>
                <tr>
                    <th><?= __($langGroup, 'ID'); ?></th>
                    <th><?= __($langGroup, 'Nombre'); ?></th>
                    <th><?= __($langGroup, 'Último acceso'); ?></th>
                    <th><?= __($langGroup, 'Tiempo en plataforma'); ?></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</section>

<script>
    window.onload = function(e) {
        $('.tabular.menu .item').tab()
        let tableLogged = $('table.ui.table.logged')
        dataTableServerProccesing(tableLogged, tableLogged.attr('process'), 25, {
            drawCallback: function({
                jqXHR
            }) {
                const {
                    responseJSON: {
                        recordsFiltered,
                        rawData
                    }
                } = jqXHR
                const totalUsers = `<?= $totalUsers ?>`
                const percent = Math.round((recordsFiltered * 100) / totalUsers)
                $('.rounded-chart').css('--percentage', percent)
                $('.rounded-chart > .content > span').text(percent + '%')
                $('[total-users]').text(recordsFiltered)
            },
        })
    }
</script>
