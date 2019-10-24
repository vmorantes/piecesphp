<?php
/**
 * @var \PiecesPHP\BuiltIn\Article\Mappers\ArticleViewMapper $article
 */
$article;

defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>

<section class="elements-container centered small">
    <h1><?=$article->title;?></h1>
    <h2><?=$article->category->getName();?></h2>
    <p><?=$article->author->username;?></p>
    <p><?=$date;?></p>
    <div class="image">
        <img src="<?=$article->images->imageMain;?>">
    </div>
    <h3><?= __('articlesFrontEnd', 'Contenido'); ?></h3>
    <div class="text-aling-j"><?=$article->content;?></div>
</section>

<?php if (count($relateds) > 0): ?>

<h3><?= __('articlesFrontEnd', 'Noticias relacionadas'); ?></h3>

<section>

    <?php foreach ($relateds as $related): ?>

    <article>

        <div>
            <img src="<?=$related->images->imageThumb;?>">
        </div>

        <div>
            <?=$related->category->getName() . ' | ' . $related->formatPreferDate("{MONTH_NAME} {DAY_NUMBER} del {YEAR}");?>
        </div>

        <div><?=$related->title;?></div>

        <div>
            <a href="<?= $related->getSingleURL(); ?>"><?= __('articlesFrontEnd', 'URL'); ?></a>
        </div>

    </article>

    <?php endforeach;?>

</section>

<?php endif;?>
