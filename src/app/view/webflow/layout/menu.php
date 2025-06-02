<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Controller\PublicAreaController;
use PiecesPHP\Core\Menu\MenuGroup;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Menu\MenuItem;
$menusItems = [
    new MenuGroup([
        'name' => 'Acerca de',
        'visible' => genericViewRouteExists('SAMPLE', null),
        'asLink' => true,
        'href' => genericViewRoute('SAMPLE', null, true),
    ]),
];
//Menú de idiomas
$currentLang = Config::get_lang();
$menuLangGroup = [];
$langsURLs = isset($langsURLs) && is_array($langsURLs) ? $langsURLs : Config::get_config('alternatives_url_include_current');
if (!empty($langsURLs)) {
    foreach ($langsURLs as $lang => $url) {
        $menuLangGroup[$lang] = new MenuItem([
            'text' => lang('lang', $lang, $lang),
            'visible' => true,
            'href' => $url,
        ]);
    }
}
?>
<ul>
    <?php foreach($menusItems as $menuItem): ?>
    <?php if(!$menuItem->isVisible()): continue; endif; ?>
    <?php $isCurrent = $menuItem->isCurrent(); ?>
    <?php $href = $menuItem->getHref(); ?>
    <?php $menuText = $menuItem->getName(); ?>
    <li>
        <a href="<?= $href; ?>" <?= $isCurrent ? 'ATTR-ON-CURRENT' : '' ?> class="<?= $isCurrent ? ' CLASS-ON-CURRENT' : '' ?>"><?= $menuText; ?></a>
    </li>
    <?php endforeach; ?>
</ul>