<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * El inicio del panel: las tarjetas que registró cada módulo y que este usuario ve (DashboardCardRegistry), pintadas con
 * las piezas de estadística de la hoja de diseño: cifras (number-cards.php en statistic-card.php), acción (action-card.php)
 * o HTML. Plegada, una tarjeta no trae su contenido: su barra (título, ayuda y botón) y, si lo declara, su pie.
 *
 * @var string $langGroup
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $helpController
 * @var array<int,array{card:\PiecesPHP\AdminPanel\DashboardCard,collapsed:bool,body:\PiecesPHP\AdminPanel\DashboardCardBody|null}> $cards
 * @var string $collapseURL
 * @var string $userName
 */
use PiecesPHP\AdminPanel\DashboardCardBody;

$toggleOf = function (array $item) use ($langGroup): string {
    $card = $item['card'];
    if (!$card->collapsible()) {
        return '';
    }
    $text = $item['collapsed'] ? __($langGroup, 'Desplegar') : __($langGroup, 'Plegar');
    return '<button type="button" class="ui mini basic icon button" data-dashboard-card-toggle="' . escape_html($card->key()) . '" data-collapsed="' . ($item['collapsed'] ? 'no' : 'yes') . '" title="' . escape_html($text) . '" aria-label="' . escape_html($text) . '"><i class="' . ($item['collapsed'] ? 'chevron down' : 'chevron up') . ' icon"></i></button>';
};
//Una tarjeta, como en la hoja de diseño (examples/estadisticas-*.php).
$cardOf = function (array $item) use ($helpController, $toggleOf, $langGroup): string {
    $card = $item['card'];
    $body = $item['body'];
    $attributes = ['data-dashboard-card' => $card->key()];
    if ($body !== null && $body->kind() === DashboardCardBody::KIND_ACTION) {
        return (string) $helpController->_render('panel/built-in/statistics/action-card.php', [
            'url' => $body->url(),
            'icon' => $card->icon(),
            'title' => $card->title(),
            'description' => $body->description(),
            'caption' => $body->footerCaption(),
        ], false, false);
    }
    $content = '';
    $footer = null;
    //Sin `tall`: la rejilla de la sección ya da a la fila el alto de la más alta y a todas el mismo ancho, sin el mínimo de
    //250 px. Sin `wide`: las cifras se apilan, como en la hoja de diseño. Plegada, una frase en vez de las cifras.
    $modifiers = [];
    if ($body === null) {
        $caption = $card->collapsedFooterCaption();
        $footer = $caption !== null ? ['caption' => $caption, 'url' => $card->collapsedFooterUrl()] : null;
        $content = '<p class="ui muted text">' . escape_html(__($langGroup, 'Despliégala para ver las cifras.')) . '</p>';
    } else {
        if ($body->kind() === DashboardCardBody::KIND_NUMBERS) {
            $items = [];
            foreach ($body->items() as $index => $number) {
                $items[] = array_filter([
                    'dataType' => $card->key() . '-' . $index,
                    'initial' => (string) $number['value'],
                    'concept' => $number['concept'],
                    'subconcept' => $number['subconcept'],
                    'icon' => $number['icon'],
                    'color' => $number['color'],
                ], fn($value) => $value !== null);
            }
            $content = (string) $helpController->_render('panel/built-in/statistics/number-cards.php', ['items' => $items], false, false);
        } else {
            $content = $body->htmlContent();
        }
        $footer = $body->footerCaption() !== null || $body->footerUrl() !== null ? ['caption' => $body->footerCaption(), 'url' => $body->footerUrl()] : null;
    }
    return (string) $helpController->_render('panel/built-in/statistics/statistic-card.php', [
        'modifiers' => $modifiers,
        'attributes' => $attributes,
        'title' => $card->title(),
        'titleModifiers' => ['medium', 'wide'],
        'toolbarExtra' => $toggleOf($item),
        'help' => $card->help(),
        'content' => $content,
        'footer' => $footer,
    ], false, false);
};
?>
<section class="module-view-container" data-dashboard data-collapse-url="<?= escape_html($collapseURL); ?>">

    <div class="limiter-content">

        <?php
            $helpController->_render('panel/built-in/content/page-header.php', [
                'title' => __($langGroup, 'Inicio'),
                'description' => $userName !== '' ? sprintf(__($langGroup, 'Hola, %s. Esto es lo que te toca de un vistazo.'), $userName) : __($langGroup, 'Esto es lo que te toca de un vistazo.'),
            ]);
        ?>

        <?php if (count($cards) === 0) : ?>
        <?php $helpController->_render('panel/built-in/content/empty-state.php', [
                'icon' => 'th large',
                'text' => __($langGroup, 'Aquí aparecerán las tarjetas de los módulos que puedas ver.'),
        ]); ?>
        <?php else : ?>
        <?php
            $helpController->_render('panel/built-in/statistics/statistics-section.php', [
                'content' => implode('', array_map($cardOf, $cards)),
            ]);
        ?>
        <?php endif; ?>

    </div>

</section>
