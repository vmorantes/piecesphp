<?php

use App\Controller\PublicAreaController;
use PiecesPHP\BuiltIn\Article\Category\Controllers\CategoryController;
use PiecesPHP\BuiltIn\Article\Controllers\ArticleControllerPublic;

defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

$categories = (object) CategoryController::_all();
?>

<nav id="menu">
    <header class="major">
        <h2><?= __(LANG_GROUP, 'Menú'); ?></h2>
    </header>
    <ul>
        <li><a href="<?= PublicAreaController::routeName('index'); ?>"><?= __(LANG_GROUP, 'Página principal'); ?></a></li>
        <li><a href="<?= genericViewRoute('about-us'); ?>"><?= __(LANG_GROUP, 'Quiénes somos'); ?></a></li>
        <li><a href="<?= genericViewRoute('tabs-sample'); ?>"><?= __(LANG_GROUP, 'Ejemplo de tabs'); ?></a></li>
        <li>
            <span class="opener"><?= __(LANG_GROUP, 'Blog'); ?></span>
            <ul>
                <li><a href="<?= ArticleControllerPublic::routeName('list'); ?>"><?= __(LANG_GROUP, 'Todas las categorías'); ?></a></li>
                <?php foreach($categories as $category): ?>
                <li>
                    <a href="<?= ArticleControllerPublic::routeName('list-by-category', ['category' => $category->friendly_url,]) ?>"><?= $category->name; ?></a>
                </li>
                <?php endforeach; ?>
            </ul>
        </li>
        <?php if(!isset($withContactFormGlobal) || $withContactFormGlobal === true): ?>
        <li><a href="#" data-smooth data-smooth-to="contact-form"><?= __(LANG_GROUP, 'Contacto'); ?></a></li>
        <?php endif;?>
    </ul>
</nav>
