<!-- Con `content-tabs`, por debajo de 768 px las pestañas son UNA fila que se desliza en horizontal. -->
<div class="tabs-controls content-tabs" example-tabs>
    <div class="active" data-tab="example-all"><?= __($langGroup, 'Todas'); ?></div>
    <div data-tab="example-published"><?= __($langGroup, 'Publicadas'); ?></div>
    <div data-tab="example-scheduled"><?= __($langGroup, 'Programadas'); ?></div>
    <div data-tab="example-draft"><?= __($langGroup, 'Borradores'); ?></div>
</div>
<div class="ui tab tab-element active" data-tab="example-all"><?= __($langGroup, 'Todas las publicaciones.'); ?></div>
<div class="ui tab tab-element" data-tab="example-published"><?= __($langGroup, 'Las publicadas.'); ?></div>
<div class="ui tab tab-element" data-tab="example-scheduled"><?= __($langGroup, 'Las programadas.'); ?></div>
<div class="ui tab tab-element" data-tab="example-draft"><?= __($langGroup, 'Los borradores.'); ?></div>
