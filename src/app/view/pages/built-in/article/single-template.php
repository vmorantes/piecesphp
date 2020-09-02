<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use PiecesPHP\BuiltIn\Article\Controllers\ArticleControllerPublic;

/**
 * @var \PiecesPHP\BuiltIn\Article\Mappers\ArticleViewMapper $article
 * @var \PiecesPHP\BuiltIn\Article\Mappers\ArticleViewMapper[] $relateds
 */
$article;
$relateds;

?>
<!-- Main -->
<div id="main">

    <div class="inner">

        <!-- Header -->
        <?php if(isset($withSocialBar) && $withSocialBar === true):?>
        <?php $this->render('layout/template-inc/social-bar', isset($socialBarData) ? $socialBarData : []); ?>
        <?php endif;?>

        <!-- Content -->
        <section>

            <header class="main">
                <h1><?= $article->title; ?></h1>
                <p><small><?= $date; ?></small></p>
            </header>

            <span class="image single-post"><img loading="lazy" src="<?=$article->images->imageMain; ?>" alt="<?= $article->title; ?>" /></span>

            <div><?= $article->content; ?></div>

        </section>

        <?php if (count($relateds) > 0): ?>

        <!-- Section Relateds-->
        <section>

            <header class="major">
                <h2><?= __(LANG_GROUP, 'Relacionadas'); ?></h2>
            </header>

            <div class="posts">

                <?php foreach ($relateds as $related): ?>

                <article>
                    <a href="#" class="image"><img loading="lazy" src="<?= $related->images->imageThumb; ?>" alt="" /></a>
                    <h3><?= $related->title; ?></h3>
                    <p><?= $related->seo_description; ?></p>
                    <ul class="actions">
                        <li>
                            <a href="<?= ArticleControllerPublic::routeName('single', ['friendly_name' => $related->friendly_url]); ?>" class="button">
                                <?= __(LANG_GROUP, 'Ver más'); ?>
                            </a>
                        </li>
                    </ul>
                </article>

                <?php endforeach;?>
            </div>

        </section>

        <?php endif;?>
