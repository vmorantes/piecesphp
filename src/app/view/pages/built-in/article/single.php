<?php
use PiecesPHP\BuiltIn\Article\Controllers\ArticleControllerPublic;
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>



<div><?=$date_created;?></div>
<div><?=$article->title;?></div>
<div><?=$article->category->name;?></div>
<div>Imagen grande: <img src="<?=$article->meta->imageMain;?>" style="max-width: 500px;"></div>
<div style="max-width:600px;"><?=$article->content;?></div>

<?php if (count($relateds) > 0): ?>


<h3>Noticias relacionadas</h3>

<div class="items" items-js>

    <?php foreach ($relateds as $related): ?>

    <div>
        Miniatura: <img src="<?=$related->meta->imageThumb;?>">
    </div>

    <span>
        <?=$related->category->name;?>
        |
        <?php if (!is_null($related->start_date)): ?>
        <?=num_month_to_text($related->start_date->format('d-m-Y'));?>
        <?=$related->start_date->format('d') . ' del ' . $related->start_date->format('Y');?>
        <?php else: ?>
        <?=num_month_to_text($related->created->format('d-m-Y'));?>
        <?=$related->created->format('d') . ' del ' . $related->created->format('Y');?>
        <?php endif;?>
    </span>

    <div><?=$related->title;?></div>
    <a href="<?=ArticleControllerPublic::routeName('single', ['friendly_name' => $related->friendly_url]);?>">
        URL
    </a>
    <?php endforeach;?>

</div>

<?php endif;?>
