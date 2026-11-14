<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
$sidebarMenu = get_sidebar_menu();
//Huecos de bloques registrados (SidebarBlockRegistry, P312 a): sus tres llamadas van SIN sangría para que, sin bloques, no salga ni un byte.
$sidebarUser = getLoggedFrameworkUser();
$langGroup = \PiecesPHP\AdminPanel\Controllers\AdminPanelController::ADMIN_LANG_GROUP;
//Los atributos de un grupo son ganchos de los módulos: van en su elemento, y su clase se suma a la del ítem.
$groupAttributes = function (\PiecesPHP\Core\Menu\MenuGroup $group): array {
    $attributes = $group->getAttributes();
    $attributes = is_array($attributes) ? $attributes : [];
    $class = array_key_exists('class', $attributes) ? ' ' . escape_html((string) $attributes['class']) : '';
    unset($attributes['class']);
    $string = [];
    foreach ($attributes as $name => $value) {
        $string[] = escape_html((string) $name) . '="' . escape_html((string) $value) . '"';
    }
    return [$class, implode(' ', $string)];
};
//El texto sobre el patrocinador (Configuración › Apariencia › Imágenes de marca, P258): vacío, no se pinta.
$partnersText = \PiecesPHP\Settings\Controllers\SettingsController::partnersText();
//La casilla «Invertir colores en modo oscuro» de Imágenes de marca (P277 c).
$invertOnDark = fn(string $image): string => \PiecesPHP\Settings\Controllers\SettingsController::brandImageInvertsOnDark($image) ? ' invert-on-dark' : '';
//El crédito del desarrollador (config.php, P263): fijo por clon; vacío no se pinta.
$developer = trim((string) get_config('developer'));
$developerLogo = trim((string) get_config('developer_logo'));
$developerURL = trim((string) get_config('developer_url'));
$developerName = $developer === '' ? '' : '<strong>' . escape_html($developer) . '</strong>';
if ($developerName !== '' && $developerURL !== '') {
    $developerName = '<a href="' . escape_html($developerURL) . '" target="_blank" rel="noopener">' . $developerName . '</a>';
}
?>
<nav class="navigation" aria-label="<?= escape_html(__($langGroup, 'Menú principal')); ?>">

    <div class="brand">
        <span class="logo"><img class="<?= trim(($invertOnDark)('logo')); ?>" src="<?= get_config('logo'); ?>" alt=""></span>
        <div class="identity">
            <span class="name"><?= escape_html((string) get_config('title_app')); ?></span>
            <small class="meta"><?= escape_html(strReplaceTemplate(__('general', 'Versión {ver}'), ['{ver}' => APP_VERSION])); ?></small>
<?= \PiecesPHP\AdminPanel\SidebarBlockRegistry::renderSlot(\PiecesPHP\AdminPanel\SidebarBlock::SLOT_BRAND_AFTER_META, $sidebarUser); ?>
        </div>
        <button class="ui tertiary icon button drawer toggle" type="button"><i class="times icon" aria-hidden="true"></i></button>
    </div>

    <button class="toggle" type="button"><i class="panel svg icon" aria-hidden="true"></i></button>

    <div class="ui vertical accordion menu">

        <?php foreach ($sidebarMenu->getItems() as $element) : ?>
        <?php
            if (!$element->isVisible()) {
                continue;
            }
            //Una subentrada invisible no se pinta: el constructor de MenuGroup ya las quita, addItem() no.
            $subItems = $element->asLink() ? [] : array_values(array_filter($element->getItems(), fn($subElement) => $subElement->isVisible()));
            if (!$element->asLink() && count($subItems) === 0) {
                continue;
            }
            $name = $element->getName();
            $isCurrent = $element->isCurrent();
            [$extraClass, $extraAttributes] = $groupAttributes($element);
            $icon = $element->hasIcon() ? '<i class="icon ' . $element->getIcon(false) . '" aria-hidden="true"></i>' : '';
        ?>

        <?php if ($element->asLink()) : ?>
        <?php $hrefTarget = $element->getHrefTarget(); ?>
        <a class="item<?= $isCurrent ? ' active' : ''; ?><?= $extraClass; ?>" href="<?= $element->getHref(); ?>" data-tooltip="<?= escape_html($name); ?>" data-position="right center"<?= $isCurrent ? ' aria-current="page"' : ''; ?><?= mb_strlen($hrefTarget) > 0 ? ' target="' . escape_html($hrefTarget) . '"' : ''; ?> <?= $extraAttributes; ?>>
            <?= $icon; ?><span><?= $name; ?></span>
        </a>
        <?php else : ?>
        <div class="item<?= $extraClass; ?>" <?= $extraAttributes; ?>>
            <button class="title<?= $isCurrent ? ' active' : ''; ?>" type="button">
                <?= $icon; ?><span><?= $name; ?></span><i class="dropdown icon" aria-hidden="true"></i>
            </button>
            <div class="content<?= $isCurrent ? ' active' : ''; ?>">
                <div class="header"><?= $name; ?></div>
                <div class="menu">
                    <?php foreach ($subItems as $subElement) : ?>
                    <?php $subIsCurrent = $subElement->isCurrent(); ?>
                    <a class="item<?= $subIsCurrent ? ' active' : ''; ?>" href="<?= $subElement->getHref(); ?>"<?= $subIsCurrent ? ' aria-current="page"' : ''; ?>><span><?= $subElement->getText(); ?></span></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php endforeach; ?>

<?= \PiecesPHP\AdminPanel\SidebarBlockRegistry::renderSlot(\PiecesPHP\AdminPanel\SidebarBlock::SLOT_MENU_AFTER, $sidebarUser); ?>
        <div class="collapsed only">
            <div class="ui vertical centered flex">
                <img class="partners<?= ($invertOnDark)('partnersVertical'); ?>" src="<?= get_config('partnersVertical'); ?>" alt="">
                <?php if ($developer !== '' && $developerLogo !== '') : ?>
                <img class="developer-logo" src="<?= escape_html($developerLogo); ?>" alt="" title="<?= escape_html(__('general', 'Desarrollado por') . ' ' . $developer); ?>">
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="footer">
        <div class="expanded only">
            <?php //Como el marco del tema: el texto, el patrocinador y el crédito. ?>
            <div class="ui vertical centered flex">
                <?php if ($partnersText !== '') : ?>
                <strong><?= escape_html($partnersText); ?></strong>
                <?php endif; ?>
                <img class="partners<?= ($invertOnDark)('partners'); ?>" src="<?= get_config('partners'); ?>" alt="">
                <?php if ($developerName !== '') : ?>
                <span class="developer-credit">
                    <?php if ($developerLogo !== '') : ?>
                    <img class="developer-logo" src="<?= escape_html($developerLogo); ?>" alt="">
                    <?php endif; ?>
                    <?= __('general', 'Desarrollado por'); ?> <?= $developerName; ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
<?= \PiecesPHP\AdminPanel\SidebarBlockRegistry::renderSlot(\PiecesPHP\AdminPanel\SidebarBlock::SLOT_FOOTER, $sidebarUser); ?>
    </div>

</nav>
