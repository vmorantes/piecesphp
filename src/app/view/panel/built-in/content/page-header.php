<?php
/**
 * La cabecera de una pantalla de contenido (diseño de contenidos, §2.1): migas, título, una frase, UNA acción principal
 * y las secundarias como botón básico. Con el marcado del patrón cabecera-pagina del tema: `h1.ui.header` con su
 * `.sub.header`, y con acciones un `ui stackable bottom aligned flex` del fork: el título ocupa lo que sobra y las acciones van
 * al borde derecho, la principal la última (a ≤767 se apilan). No se apaga: es el armazón. `content-page-header`
 * (en la raíz) y `content-breadcrumb` (en las migas) son clases del framework, sin estilo (ADR 0069).
 *
 * @var string $title
 * @var string $description Para qué sirve la pantalla, en una frase.
 * @var array<string|int,string|array{url:string}> $breadcrumbs Las migas, con la forma de get_breadcrumbs(): 'Texto' =>
 *      ['url' => …] para una con enlace, o 'Texto' sin enlace. Se pintan con `nav.ui.breadcrumb` de Fomantic y ESCAPADAS
 *      aquí: quien llama pasa el texto tal cual. La última va recortada con «…» y su texto entero en `title`. Un texto (la forma
 *      de antes de la 9.0.0) se ignora con un aviso.
 * @var array{text:string,url:string,icon?:string,attributes?:array<string,string>}|null $primaryAction
 * @var array<array{text:string,url:string,icon?:string,attributes?:array<string,string>}> $secondaryActions
 *      `attributes`, atributos extra del enlace (p. ej. `target`), con el filtro de RowActions::attributes().
 */
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Core\Utilities\Helpers\RowActions;

$title = isset($title) && is_string($title) ? $title : '';
$description = isset($description) && is_string($description) ? $description : '';
if (isset($breadcrumbs) && is_string($breadcrumbs) && $breadcrumbs !== '') {
    trigger_error('page-header.php: las migas van como arreglo desde la 9.0.0; se ignoró un texto', E_USER_DEPRECATED);
}
$breadcrumbs = isset($breadcrumbs) && is_array($breadcrumbs) ? $breadcrumbs : [];
$primaryAction = isset($primaryAction) && is_array($primaryAction) ? $primaryAction : null;
$secondaryActions = isset($secondaryActions) && is_array($secondaryActions) ? $secondaryActions : [];
$iconOf = fn (array $action): string => isset($action['icon']) && is_string($action['icon']) && $action['icon'] !== ''
    ? '<i class="' . escape_html(trim((string) preg_replace('/[^a-z0-9 -]/', '', mb_strtolower($action['icon'])))) . ' icon" aria-hidden="true"></i>'
    : '';
$buttonClassOf = fn (string $kind, array $action): string => 'ui ' . $kind . ($iconOf($action) !== '' ? ' labeled icon' : '') . ' button';
$attributesOf = fn (array $action): string => isset($action['attributes']) && is_array($action['attributes'])
    ? RowActions::attributes(array_filter($action['attributes'], 'is_string'))
    : '';
$actionsCount = ($primaryAction !== null ? 1 : 0) + count($secondaryActions);
?>
<?php if ($breadcrumbs !== []): ?>
<nav class="ui breadcrumb content-breadcrumb" aria-label="<?= escape_html(__(AdminPanelController::ADMIN_LANG_GROUP, 'Ruta de navegación')); ?>">
    <?php $lastKey = array_key_last($breadcrumbs); ?>
    <?php foreach ($breadcrumbs as $key => $crumb): ?>
    <?php
        $isLink = is_array($crumb);
        $text = $isLink ? (string) $key : (is_scalar($crumb) ? (string) $crumb : '');
        $url = $isLink && isset($crumb['url']) && is_string($crumb['url']) ? $crumb['url'] : null;
    ?>
    <?php if ($key === $lastKey): ?>
    <span class="active section" aria-current="page" title="<?= escape_html($text); ?>"><?= escape_html($text); ?></span>
    <?php else: ?>
    <?php if ($url !== null): ?>
    <a class="section" href="<?= escape_html($url); ?>"><?= escape_html($text); ?></a>
    <?php else: ?>
    <span class="section"><?= escape_html($text); ?></span>
    <?php endif; ?>
    <i class="chevron right icon divider" aria-hidden="true"></i>
    <?php endif; ?>
    <?php endforeach; ?>
</nav>
<?php endif; ?>
<?php if ($actionsCount === 0): ?>
<h1 class="ui header content-page-header"><?= escape_html($title); ?><?php if ($description !== ''): ?><div class="sub header"><?= escape_html($description); ?></div><?php endif; ?></h1>
<?php else: ?>
<div class="ui stackable bottom aligned flex content-page-header">
    <h1 class="ui header"><?= escape_html($title); ?><?php if ($description !== ''): ?><div class="sub header"><?= escape_html($description); ?></div><?php endif; ?></h1>
    <?php //`spaced`, de Fomantic: sin él el grupo sale pegado; nuestro gap de .ui.buttons salió en JCK. ?>
    <div class="ui right floated stackable spaced buttons">
        <?php foreach ($secondaryActions as $action): ?>
        <a class="<?= $buttonClassOf('basic', $action); ?>" href="<?= escape_html($action['url']); ?>"<?= $attributesOf($action); ?>><?= $iconOf($action); ?><?= escape_html($action['text']); ?></a>
        <?php endforeach; ?>
        <?php if ($primaryAction !== null): ?>
        <a class="<?= $buttonClassOf('primary', $primaryAction); ?>" href="<?= escape_html($primaryAction['url']); ?>"<?= $attributesOf($primaryAction); ?>><?= $iconOf($primaryAction); ?><?= escape_html($primaryAction['text']); ?></a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
<div class="ui hidden divider"></div>
