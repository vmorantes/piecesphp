<?php

use App\Controller\PublicAreaController;
use Publications\Controllers\PublicationsCategoryController;
use Publications\Controllers\PublicationsPublicController;
use Publications\Mappers\PublicationCategoryMapper;

defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

$categories =  PublicationsCategoryController::_all()->elements();
foreach($categories as $k => $i){
    $categories[$k] = PublicationCategoryMapper::objectToMapper($i);
}
?>

<nav class="navigation">

    <div class="content">

        <button class="open-nav">
            <i class="icon ellipsis vertical"></i>
        </button>

        <div class="logo">
            <a href="./">
                <img src="<?= baseurl('statics/images/navbar-logo.png'); ?>">
            </a>
        </div>

        <div class="items">


            <a class="item" href="<?=  PublicAreaController::routeName('index'); ?>">

                <div class="text"><?= __(LANG_GROUP, 'Página principal')?></div>

            </a>

            <a class="item" href="<?= genericViewRoute('elements'); ?>">

                <div class="text"><?= __(LANG_GROUP, 'Elementos')?></div>

            </a>

            <a class="item" href="<?= genericViewRoute('tabs-sample'); ?>">

                <div class="text"><?= __(LANG_GROUP, 'Ejemplo de tabs')?></div>

            </a>

            <span class="item menu">

                <div class="text"><?=__(LANG_GROUP, 'Blog');?>&nbsp;&nbsp;<i class="icon angle down"></i></div>

                <div class="subitems">
                    <a href="<?= PublicationsPublicController::routeName('list');?>" class="item"><?=__(LANG_GROUP, 'Todas las categorías'); ?></a>

                    <?php foreach ($categories as $category): ?>
                    <a href="<?= PublicationsPublicController::routeName('list-by-category', ['categorySlug' => $category->getSlug()]) ?>" class="item">
                        <?= $category->name; ?>
                    </a>
                    <?php endforeach; ?>
                </div>

            </span>

            <a class="item" href="<?=  PublicAreaController::routeName('contact'); ?>">

                <div class="text"><?= __(LANG_GROUP, 'Contacto')?></div>

            </a>

            <span class="item menu">

                <div class="text"><?= __(LANG_GROUP, 'Idiomas'); ?>&nbsp;&nbsp;<i class="icon angle down"></i></div>

                <div class="subitems">
                    <?php foreach(\PiecesPHP\Core\Config::get_config('alternatives_url') as $lang => $url): ?>
                    <a href="<?= $url; ?>" class="item">
                        <?= $lang; ?>
                    </a>
                    <?php endforeach; ?>
                </div>

            </span>

        </div>

    </div>

</nav>
