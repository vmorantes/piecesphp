<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Controller\BlackboardNewsController;
$langGroup = BlackboardNewsController::LANG_GROUP;
?>

<h3 class="ui dividing header">
    <?= __($langGroup, 'Noticias'); ?>
</h3>
<a href="<?=get_route('blackboard-news-create-form');?>" class="ui mini button green"><?= __($langGroup, 'Nueva noticia'); ?></a>
<br>
<br>
<div table blackboard-list='<?=get_route('blackboard-news-get');?>'>
    <table content edit-route='<?=get_route('blackboard-news-edit-form', ['id' => '{{ID}}']);?>' delete-route='<?=get_route('blackboard-news-delete', ['id' => '{{ID}}']);?>' class="ui table striped celled">
		<thead></thead>
		<tbody></tbody>
	</table>
	<div paginate></div>
</div>
