<?php
use PiecesPHP\BuiltIn\Article\Controllers\ArticleControllerPublic;
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>


<section class="elements-container centered small">
    <h1><?=$article->title;?></h1>
    <h2><?=$article->category->name;?></h2>
    <p><?=$article->author->username;?></p>
    <p><?=$date;?></p>
    <div class="image">
        <img src="<?=$article->meta->imageMain;?>">
    </div>
    <h3>Contenido</h3>
    <div class="text-aling-j"><?=$article->content;?></div>
</section>

<?php if (count($relateds) > 0): ?>

<h3>Noticias relacionadas</h3>

<section>

    <?php foreach ($relateds as $related): ?>

    <article>

        <div>
            <img src="<?=$related->meta->imageThumb;?>">
        </div>

        <div>
            <?=$related->category->name . ' | ' . $related->formatPreferDate("{MONTH_NAME} {DAY_NUMBER} del {YEAR}");?>
        </div>

        <div><?=$related->title;?></div>

        <div>
            <a href="<?= $related->getSingleURL(); ?>">URL</a>
        </div>

    </article>

    <?php endforeach;?>

</section>

<?php endif;?>
