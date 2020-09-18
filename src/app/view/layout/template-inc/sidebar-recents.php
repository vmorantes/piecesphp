<?php

use PiecesPHP\BuiltIn\Article\Controllers\ArticleController;
use PiecesPHP\BuiltIn\Article\Controllers\ArticleControllerPublic;

defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

$articlesRequest = ArticleController::_all(null, 'yes', 1, 3);
$articlesRequest = array_key_exists('dataParsed', $articlesRequest) ? (object) $articlesRequest['dataParsed'] : null;
$articles = $articlesRequest !== null && isset($articlesRequest->data) ? $articlesRequest->data : [];

?>

<?php if(count($articles) > 0): ?>
<section>

    <header class="major">
        <h2><?= __(LANG_GROUP, 'Publicaciones recientes'); ?></h2>
    </header>

    <div class="mini-posts">

        <?php foreach($articles as $article): ?>
        <article>
            <a href="<?= $article->link ?>" class="image">
                <img loading="lazy" src="<?= $article->images->imageThumb; ?>" />
            </a>
            <p><?= $article->title; ?></p>
        </article>
        <?php endforeach;?>
    </div>

    <ul class="actions">
        <li><a href="<?= ArticleControllerPublic::routeName('list'); ?>" class="button"><?= __(LANG_GROUP, 'Más'); ?></a></li>
    </ul>
</section>
<?php endif;?>
