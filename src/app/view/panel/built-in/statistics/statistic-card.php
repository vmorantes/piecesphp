<?php
/**
 * Una tarjeta de estadística (`card-statistic`, _graphs-and-statistics.scss): la barra con su título y su ayuda, el dato y
 * el pie con su texto y su flecha. El dato es un número (`number`) o el HTML que pinte quien llama (`content`): un gráfico
 * (chart-card.php), un círculo de progreso (progress-circle.php), una leyenda (chart-legend.php) o unas cifras
 * (number-cards.php). Los números los rellena el JavaScript de quien llama por su `data-type`.
 *
 * @var string[] $modifiers `tall`, `wide`, `fullsize`, `horizontal-center`, `bg-gray` y `fullw-on-break-1`.
 * @var array<string,string> $attributes Atributos extra (p. ej. `data-type`), con el filtro de RowActions::attributes().
 * @var string|null $title Sin título, la barra solo lleva la ayuda.
 * @var string[] $titleModifiers `big`, `medium` y `wide`.
 * @var string $help El texto de la ayuda (`data-tooltip`); vacío, sin icono de ayuda.
 * @var string $toolbarExtra HTML que va en la barra, entre el título y la ayuda (p. ej. un botón); lo escapa quien llama.
 * @var array{dataType:string,initial?:string}|null $number El número: su `data-type` y su valor inicial («0»).
 * @var string $content HTML del dato, si no es un número; lo pinta (y escapa) quien llama.
 * @var array{caption?:string|null,url?:string|null,two?:bool}|null $footer Sin pie, null. `caption`, su texto; `url`, la
 *      flecha; `two`, la variante `footer two`.
 * @var array{value?:string,dataType?:string,label?:string,statistics?:array<array{value:string,dataType?:string,label?:string}>,url?:string,meta?:string,description?:string,content?:string,variant?:string,size?:string}|null $figure
 *      La tarjeta de cifra del tema (`statistic card`): va dentro de `ui cards`, y usa `title`, `help`, `toolbarExtra`,
 *      `footer` y `attributes`. `variant`, `compact` o `tall`; `size`, `huge marked`. `content`, HTML que pinta y escapa
 *      quien llama. `statistics`, varias cifras en la misma tarjeta (en lugar de `value`). Con `url`, la tarjeta entera
 *      es un enlace (acceso directo): el pie no lleva enlace propio, porque un enlace no puede ir dentro de otro. El pie
 *      sale de `footer`: su `caption` es el texto del enlace a `url`; sin `caption`, no hay pie.
 *      `toolbarExtra` son botones `ui right floated mini tertiary icon button`, hermanos de la ayuda (el tema los estila así).
 */
use PiecesPHP\Core\Utilities\Helpers\RowActions;

$modifiers = isset($modifiers) && is_array($modifiers) ? array_values(array_intersect(['tall', 'wide', 'fullsize', 'horizontal-center', 'bg-gray', 'fullw-on-break-1'], $modifiers)) : [];
$attributes = isset($attributes) && is_array($attributes) ? RowActions::attributes(array_filter($attributes, 'is_string')) : '';
$title = isset($title) && is_string($title) ? $title : null;
$titleModifiers = isset($titleModifiers) && is_array($titleModifiers) ? array_values(array_intersect(['big', 'medium', 'wide'], $titleModifiers)) : [];
$help = isset($help) && is_string($help) ? $help : '';
$toolbarExtra = isset($toolbarExtra) && is_string($toolbarExtra) ? $toolbarExtra : '';
$number = isset($number) && is_array($number) && isset($number['dataType']) && is_string($number['dataType']) ? $number : null;
$content = isset($content) && is_string($content) ? $content : '';
$footer = isset($footer) && is_array($footer) ? $footer : null;
$caption = $footer !== null && isset($footer['caption']) && is_string($footer['caption']) ? $footer['caption'] : null;
$url = $footer !== null && isset($footer['url']) && is_string($footer['url']) ? $footer['url'] : null;
$figure = isset($figure) && is_array($figure) && (is_string($figure['value'] ?? null) || is_array($figure['statistics'] ?? null) || is_string($figure['url'] ?? null) || is_string($figure['content'] ?? null)) ? $figure : null;
?>
<?php if ($figure !== null): ?>
<?php
$figureVariant = in_array($figure['variant'] ?? '', ['compact', 'tall'], true) ? $figure['variant'] . ' ' : '';
$figureSize = ($figure['size'] ?? '') === 'huge marked' ? 'huge marked ' : '';
$figureUrl = isset($figure['url']) && is_string($figure['url']) ? $figure['url'] : null;
$figureStatistics = [];
foreach (is_array($figure['statistics'] ?? null) ? $figure['statistics'] : [$figure] as $statistic) {
    if (is_array($statistic) && isset($statistic['value']) && is_string($statistic['value'])) {
        $figureStatistics[] = $statistic;
    }
}
//Un botón no puede ir dentro de un enlace: el acceso directo no lleva ayuda ni botones.
$toolbarExtra = $figureUrl !== null ? '' : $toolbarExtra;
$helpButton = $help !== '' && $figureUrl === null ? '<button type="button" class="ui right floated mini tertiary icon button" aria-label="' . escape_html($help) . '" data-tooltip="' . escape_html($help) . '" data-position="bottom right" data-inverted=""><i class="question circle icon" aria-hidden="true"></i></button>' : '';
$figureFooter = $caption;
$figureTag = $figureUrl !== null ? 'a' : 'div';
$figureLabel = $figureUrl !== null ? ' href="' . escape_html($figureUrl) . '" aria-label="' . escape_html(implode(': ', array_filter([$title ?? '', is_string($figure['description'] ?? null) ? $figure['description'] : ''], fn($part) => $part !== ''))) . '"' : '';
?>
<<?= $figureTag; ?> class="<?= $figureUrl !== null ? 'link ' : ''; ?><?= $figureVariant; ?>statistic card"<?= $figureLabel; ?><?= $attributes; ?>>
    <div class="content">
        <?php //Hijos directos de .content, como `.right.floated.icon.button`: así casan con el estilo del tema (card.css). ?>
        <?= $helpButton; ?><?= $toolbarExtra; ?>
        <?php if ($title !== null): ?>
        <div class="header"><?= escape_html($title); ?></div>
        <?php endif; ?>
        <?php if (isset($figure['meta']) && is_string($figure['meta']) && $figure['meta'] !== ''): ?>
        <div class="meta"><?= escape_html($figure['meta']); ?></div>
        <?php endif; ?>
        <?php if (count($figureStatistics) > 1): ?>
        <div class="ui wide flex">
        <?php endif; ?>
        <?php foreach ($figureStatistics as $statistic): ?>
        <div class="ui <?= $figureSize; ?>statistic">
            <div class="value"<?= isset($statistic['dataType']) && is_string($statistic['dataType']) ? ' data-type="' . escape_html($statistic['dataType']) . '"' : ''; ?>><?= escape_html($statistic['value']); ?></div>
            <?php if (isset($statistic['label']) && is_string($statistic['label']) && $statistic['label'] !== ''): ?>
            <div class="label"><?= escape_html($statistic['label']); ?></div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php if (count($figureStatistics) > 1): ?>
        </div>
        <?php endif; ?>
        <?php if (isset($figure['description']) && is_string($figure['description']) && $figure['description'] !== ''): ?>
        <div class="description"><?= escape_html($figure['description']); ?></div>
        <?php endif; ?>
        <?= isset($figure['content']) && is_string($figure['content']) ? $figure['content'] : ''; ?>
    </div>
    <?php if ($figureFooter !== null): ?>
    <div class="extra content">
        <?php if ($figureUrl !== null || $url === null): ?>
        <span class="ui <?= $figureUrl !== null ? 'primary' : 'muted'; ?> text"><?= escape_html($figureFooter); ?></span>
        <?php else: ?>
        <a href="<?= escape_html($url); ?>"><span class="ui primary text"><?= escape_html($figureFooter); ?></span></a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</<?= $figureTag; ?>>
<?php return; ?>
<?php endif; ?>
<div class="ui segment card-statistic<?= $modifiers !== [] ? ' ' . implode(' ', $modifiers) : ''; ?>"<?= $attributes; ?>>
    <div class="toolbar">
        <?php if ($title !== null): ?>
        <div class="title<?= $titleModifiers !== [] ? ' ' . implode(' ', $titleModifiers) : ''; ?>"><?= escape_html($title); ?></div>
        <?php endif; ?>
        <?= $toolbarExtra; ?>
        <?php if ($help !== ''): ?>
        <div class="help" data-tooltip="<?= escape_html($help); ?>">
            <i class="icon help"></i>
        </div>
        <?php endif; ?>
    </div>
    <?php if ($number !== null): ?>
    <div class="data">
        <div data-type="<?= escape_html($number['dataType']); ?>" class="number"><?= escape_html(isset($number['initial']) && is_string($number['initial']) ? $number['initial'] : '0'); ?>&nbsp;</div>
    </div>
    <?php endif; ?>
    <?= $content; ?>
    <?php if ($footer !== null): ?>
    <div class="footer<?= ($footer['two'] ?? false) === true ? ' two' : ''; ?>">
        <?php if ($caption !== null): ?>
        <div class="caption">
            <?= escape_html($caption); ?>
        </div>
        <?php else: ?>
        <div></div>
        <?php endif; ?>
        <div class="action-button">
            <?php if ($url !== null): ?>
            <a class="button" href="<?= escape_html($url); ?>">
                <i class="arrow right icon"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
