<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");?>
<section class="main-view-admin">
	<div normal blackboard-list='<?=get_route('blackboard-news-get');?>'>
		<h1><?= __('news', 'Tablero de noticias'); ?></h1>
		<div content></div>
		<div paginate></div>
	</div>
</section>
