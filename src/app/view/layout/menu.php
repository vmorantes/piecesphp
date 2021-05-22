<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Controller\PublicAreaController;
use PiecesPHP\Core\Menu\MenuGroup;
use PiecesPHP\Core\Menu\MenuGroupCollection;
use PiecesPHP\Core\Menu\MenuItem;
use Publications\Controllers\PublicationsCategoryController;
use Publications\Controllers\PublicationsPublicController;
use Publications\Mappers\PublicationCategoryMapper;
/**
 * @var MenuGroupCollection $publicMenu
 */
$publicMenu = new MenuGroupCollection([
    'items' => [
        new MenuGroup([
            'name' => __(LANG_GROUP, 'Página principal'),
            'visible' => PublicAreaController::allowedRoute('index'),
            'asLink' => true,
            'href' => PublicAreaController::routeName('index'),
        ]),
        new MenuGroup([
            'name' => __(LANG_GROUP, 'Elementos'),
            'visible' => true,
            'asLink' => true,
            'href' => genericViewRoute(__(PublicAreaController::LANG_REPLACE_GENERIC_TITLES, 'elements')),
        ]),
        new MenuGroup([
            'name' => __(LANG_GROUP, 'Ejemplo de tabs'),
            'visible' => true,
            'asLink' => true,
            'href' => genericViewRoute(__(PublicAreaController::LANG_REPLACE_GENERIC_TITLES, 'tabs-sample')),
        ]),
        new MenuGroup([
            'name' => __(LANG_GROUP, 'Contacto'),
            'visible' => true,
            'asLink' => true,
            'href' => PublicAreaController::routeName('contact'),
            'position' => 20,
        ]),
    ],
]);

//Menú para el blog

$menuBlogGroup = new MenuGroup([
    'name' => __(LANG_GROUP, 'Blog') . '&nbsp;&nbsp;',
    'visible' => true,
    'items' => [
        new MenuItem([
            'text' => __(LANG_GROUP, 'Todas las categorías'),
            'visible' => true,
            'href' => PublicationsPublicController::routeName('list'),
        ]),
    ],
]);

$categories = PublicationsCategoryController::_all()->elements();

foreach ($categories as $k => $i) {

    $categoryMapper = PublicationCategoryMapper::objectToMapper($i);

    if ($categoryMapper->id != PublicationCategoryMapper::UNCATEGORIZED_ID) {

        $menuBlogGroup->addItem(new MenuItem([
            'text' => $categoryMapper->currentLangData('name'),
            'visible' => true,
            'href' => PublicationsPublicController::routeName('list-by-category', ['categorySlug' => $categoryMapper->getSlug()]),
        ]));

    }

}

$publicMenu->addItem($menuBlogGroup);

//Menú de idiomas

$menuLangGroup = new MenuGroup([
    'name' => __(LANG_GROUP, 'Idiomas') . '&nbsp;&nbsp;',
    'visible' => true,
    'items' => [],
    'position' => 21,
]);

$langs = \PiecesPHP\Core\Config::get_config('alternatives_url');

if (count($langs) > 0) {

    foreach ($langs as $lang => $url) {

        $menuLangGroup->addItem(new MenuItem([
            'text' => $lang,
            'visible' => true,
            'href' => $url,
        ]));

    }

    $publicMenu->addItem($menuLangGroup);
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

            <?php foreach($publicMenu->getItems() as $element): ?>

            <?php if($element->asLink()): ?>

            <a class="item <?= $element->isCurrent() ? 'current' : '' ?>" href="<?= $element->getHref(); ?>">
                <div class="text"><?= $element->getName(); ?></div>
            </a>

            <?php else: ?>

            <span class="item menu <?= $element->isCurrent() ? 'current' : '' ?>">

                <div class="text">
                    <?= $element->getName(); ?> <i class="icon angle down"></i>
                </div>

                <div class="subitems">

                    <?php foreach($element->getItems() as $subElement): ?>

                    <a class="item" href="<?= $subElement->getHref(); ?>">
                        <?= $subElement->getText(); ?>
                    </a>

                    <?php endforeach; ?>

                </div>

            </span>

            <?php endif; ?>

            <?php endforeach; ?>

        </div>

    </div>

</nav>
