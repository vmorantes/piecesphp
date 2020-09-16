<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Controller\BlackboardNewsController;
$langGroup = BlackboardNewsController::LANG_GROUP;
?>
<section class="main-view-admin">
	<div normal blackboard-list='<?=get_route('blackboard-news-get');?>'>
		<h1><?= __($langGroup, 'Tablero de noticias'); ?></h1>
		<div content></div>
		<div paginate></div>
	</div>
</section>
