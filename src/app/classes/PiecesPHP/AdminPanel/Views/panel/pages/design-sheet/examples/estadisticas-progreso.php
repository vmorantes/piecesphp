<?php
//Un porcentaje con su círculo y, al lado, la leyenda de lo que suma (con `stack`, el nombre encima del número).
ob_start();
?>
<div class="with-circle-and-data-two-columns">
    <div class="data">
        <?php $this->helpController->_render('panel/built-in/statistics/progress-circle.php', [
                'dataType' => 'exampleProgress',
                'numberDataType' => 'exampleProgressNumber',
                'progress' => 70,
                'modifiers' => ['no-margin-t-b'],
        ]); ?>
    </div>
    <?php $this->helpController->_render('panel/built-in/statistics/chart-legend.php', [
            'items' => [
                ['name' => __($langGroup, 'Aprobadas'), 'dataType' => 'exampleApproved', 'level' => 'primary', 'stack' => true, 'initial' => '70'],
                ['name' => __($langGroup, 'Pendientes'), 'dataType' => 'examplePending', 'level' => 'secondary', 'stack' => true, 'valueModifiers' => ['text-black'], 'initial' => '30'],
            ],
    ]); ?>
</div>
<?php
$circle = (string) ob_get_clean();
ob_start();
$this->helpController->_render('panel/built-in/statistics/statistic-card.php', [
    'title' => __($langGroup, 'Solicitudes'),
    'titleModifiers' => ['big', 'wide'],
    'content' => $circle,
]);
$this->helpController->_render('panel/built-in/statistics/statistics-section.php', [
    'content' => (string) ob_get_clean(),
]);
