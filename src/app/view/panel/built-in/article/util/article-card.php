<div class="ui card">

    <div class="content">

        <div class="header">
            <?=  strlen($mapper->title) > 50 ? trim(mb_substr($mapper->title, 0, 50)) . '...' : $mapper->title; ?>
        </div>

        <div class="ui divider"></div>

        <div class="description">

            <div><strong><?=__('articlesBackend', 'ID');?>:</strong> <?=  $mapper->id; ?></div>

            <div><strong><?=__('articlesBackend', 'Fechas');?>:</strong> </div>

            <?php if(!is_null($mapper->start_date)): ?>
            <div class="date">
                <?=__('articlesBackend', 'Inicio');?>:
                <?=  $mapper->start_date->format(__('formatsDate', 'd-m-Y h:i:s A')); ?>
            </div>
            <?php endif;?>

            <?php if(!is_null($mapper->end_date)): ?>
            <div class="date">
                <?=__('articlesBackend', 'Fin');?>:
                <?=  $mapper->end_date->format(__('formatsDate', 'd-m-Y h:i:s A')); ?>
            </div>
            <?php endif;?>

            <div class="date">
                <?=__('articlesBackend', 'Creado');?>:
                <?=  $mapper->created->format(__('formatsDate', 'd-m-Y h:i:s A')); ?>
            </div>

            <?php if(!is_null($mapper->updated)): ?>
            <div class="date">
                <?=__('articlesBackend', 'Fin');?>:
                <?=  $mapper->updated->format(__('formatsDate', 'd-m-Y h:i:s A')); ?>
            </div>
            <?php endif;?>

            <div><strong><?=__('articlesBackend', 'Autor');?>:</strong> <?=  $mapper->author->username; ?></div>
            <div><strong><?=__('articlesBackend', 'Categoría');?>:</strong> <?=  $mapper->category->getName(); ?></div>
			<div><strong><?=__('articlesBackend', 'Visitas');?>:</strong> <?=  $mapper->visits > 0 ? $mapper->visits : '-'; ?></div>
			
        </div>
	</div>
	
    <div class="extra content">
		
        <a class="fluid ui green button" href="<?= $editLink; ?>"><?= __('articlesBackend', 'Editar'); ?></a>

    </div>

</div>
