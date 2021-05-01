<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var \PiecesPHP\BuiltIn\Article\Mappers\ArticleViewMapper $article
 */
$article;

?>

<section class="body">

    <div class="content">

        <div class="wrapper">

            <div class="post-image">
                <img src="<?=$article->images->imageMain; ?>" alt="<?= $article->title; ?>">
            </div>

        </div>

        <div class="wrapper no-padding-top-mobile">

            <h2 class="segment-title text-center"><?= $article->title; ?></h2>
            <p><small><?= $date; ?></small></p>

            <div class="post-content"><?= $article->content; ?></div>

        </div>

    </div>

</section>
