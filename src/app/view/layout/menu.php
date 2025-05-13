<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Controller\PublicAreaController;
use PiecesPHP\Core\Menu\MenuGroup;
use PiecesPHP\Core\Menu\MenuGroupCollection;
use PiecesPHP\Core\Menu\MenuItem;
use Publications\Controllers\PublicationsCategoryController;
use Publications\Controllers\PublicationsPublicController;
use Publications\Mappers\PublicationCategoryMapper;
use Publications\PublicationsRoutes;

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
            'visible' => genericViewRouteExists(__(PublicAreaController::LANG_REPLACE_GENERIC_TITLES, 'elements'), null),
            'asLink' => true,
            'href' => genericViewRoute(__(PublicAreaController::LANG_REPLACE_GENERIC_TITLES, 'elements'), null, true),
        ]),
        new MenuGroup([
            'name' => __(LANG_GROUP, 'Ejemplo de tabs'),
            'visible' => genericViewRouteExists(__(PublicAreaController::LANG_REPLACE_GENERIC_TITLES, 'tabs-sample'), null),
            'asLink' => true,
            'href' => genericViewRoute(__(PublicAreaController::LANG_REPLACE_GENERIC_TITLES, 'tabs-sample'), null, true),
        ]),
        new MenuGroup([
            'name' => __(LANG_GROUP, 'Contacto'),
            'visible' => PublicAreaController::allowedRoute('contact'),
            'asLink' => true,
            'href' => PublicAreaController::routeName('contact'),
            'position' => 90,
        ]),
    ],
]);

//Menú para el blog
if (PublicationsRoutes::ENABLE) {
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
}

//Menú de idiomas
$menuLangGroup = new MenuGroup([
    'name' => __(LANG_GROUP, 'Idiomas') . '&nbsp;&nbsp;',
    'visible' => true,
    'items' => [],
    'position' => 100,
]);

$langs = \PiecesPHP\Core\Config::get_config('alternatives_url');

if (!empty($langs)) {

    foreach ($langs as $lang => $url) {

        $flagHTML = get_config('get_fomantic_flag_by_lang')($lang, 'small', 0.3);
        $menuLangGroup->addItem(new MenuItem([
            'text' => strReplaceTemplate("<span style='display: inline-block;background-color:rgba(0, 0, 0, 0.33);border-radius: 10px;padding:2.5px 2.5px 5px 2.5px'>{flag}</span>", [
                '{flag}' => $flagHTML,
            ]),
            'visible' => true,
            'href' => $url,
        ]));

    }

    $publicMenu->addItem($menuLangGroup);
}

?>

<nav class="navigation">

    <div class="content">

        <button class="open-nav" aria-label="<?= __(LANG_GROUP, 'Desplegar menú'); ?>">
            <i class="icon ellipsis vertical"></i>
        </button>

        <div class="logo">
            <a href="./">
                <img src="<?= baseurl('statics/images/navbar-logo.png'); ?>">
            </a>
        </div>

        <div class="items">

            <?php foreach($publicMenu->getItems() as $element): ?>
            <?php $elementAttributes = $element->getAttributes(); ?>
            <?php $elementClasses = array_key_exists('class', $elementAttributes) ? ' ' . $elementAttributes['class'] : ''; ?>

            <?php if($element->asLink()): ?>

            <a class="item<?= $element->isCurrent() ? ' current' : '' ?><?= $elementClasses; ?>" href="<?= $element->getHref(); ?>" <?= $element->getAttributes(true); ?>>
                <div class="text"><?= $element->getName(); ?></div>
            </a>

            <?php else: ?>

            <span class="item menu<?= $element->isCurrent() ? ' current' : '' ?><?= $elementClasses; ?>" <?= $element->getAttributes(true); ?>>

                <div class="text">
                    <?= $element->getName(); ?> <i class="icon angle down"></i>
                </div>

                <div class="subitems">

                    <?php foreach($element->getItems() as $subElement): ?>
                    <?php $subElementAttributes = $subElement->getAttributes(); ?>
                    <?php $subElementClasses = array_key_exists('class', $subElementAttributes) ? ' ' . $subElementAttributes['class'] : ''; ?>

                    <a class="item<?= $subElementClasses; ?>" href="<?= $subElement->getHref(); ?>" <?= $subElement->getAttributes(true); ?>>
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
