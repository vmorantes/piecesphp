<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * La barra de periodo de las tres sub-vistas de Accesos: 7/14/30/90 días (cada enlace recarga la sub-vista con su
 * `days`), el rango y «Exportar» con el periodo. La incluyen attempts.php, logged.php y not-logged.php.
 *
 * @var string $langGroup
 * @var array<int,array{days:int,url:string,active:bool}> $periods
 * @var \DateTimeImmutable $from El primer día del periodo
 * @var \DateTimeImmutable $until El último, hoy
 * @var string $exportUrl Con el periodo
 */
?>
<?php //El periodo recarga la pestaña con su `days`; el rango, los días que acaban hoy, incluido. ?>
<div class="ui stackable middle aligned relaxed flex" data-access-toolbar>
    <div class="ui mini buttons" role="radiogroup" aria-label="<?= escape_html(__($langGroup, 'Periodo')); ?>" data-access-periods>
        <?php foreach ($periods as $period): ?>
        <a class="ui<?= $period['active'] ? ' active' : ''; ?> button" role="radio" aria-checked="<?= $period['active'] ? 'true' : 'false'; ?>" tabindex="<?= $period['active'] ? '0' : '-1'; ?>" href="<?= escape_html($period['url']); ?>" data-access-period="<?= (int) $period['days']; ?>"><?= sprintf(__($langGroup, '%d días'), (int) $period['days']); ?></a>
        <?php endforeach; ?>
    </div>
    <span class="ui muted text" data-access-range><?= escape_html($from->format('d-m-Y') . ' – ' . $until->format('d-m-Y')); ?></span>
    <a class="right floated ui basic labeled icon button" href="<?= escape_html($exportUrl); ?>" data-access-export="<?= escape_html($exportUrl); ?>"><i class="download icon" aria-hidden="true"></i><?= __($langGroup, 'Exportar'); ?></a>
</div>
