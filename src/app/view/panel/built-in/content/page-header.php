<?php
/**
 * La cabecera de una pantalla de contenido (diseño de contenidos, §2.1): migas, título, una frase, UNA acción principal
 * y las secundarias como botón básico. Con `ui grid` de Fomantic; su CSS propio (el hueco de abajo y los botones
 * apilados) está en `_content-screens.scss`. No se apaga: es el armazón.
 *
 * @var string $title
 * @var string $description Para qué sirve la pantalla, en una frase.
 * @var array<string|int,string|array{url:string}> $breadcrumbs Las migas, con la forma de get_breadcrumbs(): 'Texto' =>
 *      ['url' => …] para una con enlace, o 'Texto' sin enlace. Se pintan con `ui breadcrumb` de Fomantic y ESCAPADAS aquí:
 *      quien llama pasa el texto tal cual. La última va recortada con «…» y su texto entero en `title`. Un texto (la forma
 *      de antes de la 9.0.0) se ignora con un aviso.
 * @var array{text:string,url:string,icon?:string,attributes?:array<string,string>}|null $primaryAction
 * @var array<array{text:string,url:string,icon?:string,attributes?:array<string,string>}> $secondaryActions
 *      `attributes`, atributos extra del enlace (p. ej. `target`), con el filtro de RowActions::attributes().
 */
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
    ? '<i class="' . escape_html(trim((string) preg_replace('/[^a-z0-9 -]/', '', mb_strtolower($action['icon'])))) . ' icon"></i>'
    : '';
$attributesOf = fn (array $action): string => isset($action['attributes']) && is_array($action['attributes'])
    ? RowActions::attributes(array_filter($action['attributes'], 'is_string'))
    : '';
//Con una acción o ninguna, el título gana columnas: a 1440, eight wide parte a los 29 caracteres y eleven a los 41.
$fewActions = ($primaryAction !== null ? 1 : 0) + count($secondaryActions) <= 1;
$titleWide = $fewActions ? 'eleven' : 'eight';
$actionsWide = $fewActions ? 'five' : 'eight';
?>
<?php if ($breadcrumbs !== []): ?>
<div class="ui breadcrumb content-breadcrumb">
    <?php $lastKey = array_key_last($breadcrumbs); ?>
    <?php foreach ($breadcrumbs as $key => $crumb): ?>
    <?php
    $isLink = is_array($crumb);
    $text = $isLink ? (string) $key : (is_scalar($crumb) ? (string) $crumb : '');
    $url = $isLink && isset($crumb['url']) && is_string($crumb['url']) ? $crumb['url'] : null;
    ?>
    <?php if ($key === $lastKey): ?>
    <div class="active section" title="<?= escape_html($text); ?>"><?= escape_html($text); ?></div>
    <?php else: ?>
    <?php if ($url !== null): ?>
    <a class="section" href="<?= escape_html($url); ?>"><?= escape_html($text); ?></a>
    <?php else: ?>
    <div class="section"><?= escape_html($text); ?></div>
    <?php endif; ?>
    <i class="right angle icon divider"></i>
    <?php endif; ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<div class="ui stackable middle aligned grid content-page-header">
    <div class="sixteen wide tablet <?= $titleWide; ?> wide computer column">
        <div class="section-title">
            <div class="title"><?= escape_html($title); ?></div>
            <?php if ($description !== ''): ?>
            <div class="description"><?= escape_html($description); ?></div>
            <?php endif; ?>
        </div>
    </div>
    <div class="sixteen wide tablet <?= $actionsWide; ?> wide computer right aligned column">
        <?php foreach ($secondaryActions as $action): ?>
        <a class="ui basic button" href="<?= escape_html($action['url']); ?>"<?= $attributesOf($action); ?>><?= $iconOf($action); ?><?= escape_html($action['text']); ?></a>
        <?php endforeach; ?>
        <?php if ($primaryAction !== null): ?>
        <a class="ui button brand-color" href="<?= escape_html($primaryAction['url']); ?>"<?= $attributesOf($primaryAction); ?>><?= $iconOf($primaryAction); ?><?= escape_html($primaryAction['text']); ?></a>
        <?php endif; ?>
    </div>
</div>
