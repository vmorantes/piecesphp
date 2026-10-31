<!-- Las del tema: por debajo de 768 px, UNA fila que se desliza en horizontal. tabs-controls y content-tabs son ganchos. -->
<div class="ui secondary pointing menu tabs-controls content-tabs" example-tabs>
    <div class="active item" data-tab="example-all"><?= __($langGroup, 'Todas'); ?></div>
    <div class="item" data-tab="example-published"><?= __($langGroup, 'Publicadas'); ?></div>
    <div class="item" data-tab="example-scheduled"><?= __($langGroup, 'Programadas'); ?></div>
    <div class="item" data-tab="example-draft"><?= __($langGroup, 'Borradores'); ?></div>
</div>
<div class="ui tab tab-element active" data-tab="example-all"><?= __($langGroup, 'Todas las publicaciones.'); ?></div>
<div class="ui tab tab-element" data-tab="example-published"><?= __($langGroup, 'Las publicadas.'); ?></div>
<div class="ui tab tab-element" data-tab="example-scheduled"><?= __($langGroup, 'Las programadas.'); ?></div>
<div class="ui tab tab-element" data-tab="example-draft"><?= __($langGroup, 'Los borradores.'); ?></div>
