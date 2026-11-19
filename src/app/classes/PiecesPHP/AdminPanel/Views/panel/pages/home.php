<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * El inicio del panel: las tarjetas que registró cada módulo y que este usuario ve (DashboardCardRegistry), pintadas como
 * la tarjeta de resumen del tema (statistic-card.php con `figure`): cifras, acceso directo o HTML, sin iconos. Plegada,
 * una tarjeta no trae su contenido: su título, su ayuda, su botón y, si lo declara, su pie.
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
    return '<button type="button" class="ui right floated mini tertiary icon button" data-dashboard-card-toggle="' . escape_html($card->key()) . '" data-collapsed="' . ($item['collapsed'] ? 'no' : 'yes') . '" title="' . escape_html($text) . '" aria-label="' . escape_html($text) . '"><i class="' . ($item['collapsed'] ? 'chevron down' : 'chevron up') . ' icon" aria-hidden="true"></i></button>';
};
//Sin texto, un pie con enlace no se vería: el de la tarjeta del tema es un enlace de texto, sin flecha.
$footerOf = function (?string $caption, ?string $url) use ($langGroup): ?array {
    if ($caption === null && $url === null) {
        return null;
    }
    return ['caption' => $caption ?? __($langGroup, 'Ver más'), 'url' => $url];
};
//`icon` y `color` de la tarjeta y de sus cifras ya no se pintan (en desuso hasta la 11.0.0).
$cardOf = function (array $item) use ($helpController, $toggleOf, $footerOf, $langGroup): string {
    $card = $item['card'];
    $body = $item['body'];
    $data = [
        'attributes' => ['data-dashboard-card' => $card->key()],
        'title' => $card->title(),
        'toolbarExtra' => $toggleOf($item),
        'help' => $card->help(),
    ];
    if ($body === null) {
        $data['footer'] = $footerOf($card->collapsedFooterCaption(), $card->collapsedFooterUrl());
        $data['figure'] = ['statistics' => [], 'description' => __($langGroup, 'Despliégala para ver las cifras.')];
    } elseif ($body->kind() === DashboardCardBody::KIND_ACTION) {
        $data['footer'] = $footerOf($body->footerCaption(), null);
        $data['figure'] = ['url' => $body->url(), 'description' => $body->description()];
    } elseif ($body->kind() === DashboardCardBody::KIND_NUMBERS) {
        $statistics = [];
        $details = [];
        foreach ($body->items() as $index => $number) {
            $statistics[] = ['value' => (string) $number['value'], 'dataType' => $card->key() . '-' . $index, 'label' => $number['concept']];
            if ($number['subconcept'] !== null) {
                $details[] = $number['subconcept'];
            }
        }
        $data['footer'] = $footerOf($body->footerCaption(), $body->footerUrl());
        $data['figure'] = ['statistics' => $statistics, 'description' => implode(' · ', $details)];
    } else {
        $data['footer'] = $footerOf($body->footerCaption(), $body->footerUrl());
        $data['figure'] = ['content' => $body->htmlContent()];
    }
    return (string) $helpController->_render('panel/built-in/statistics/statistic-card.php', $data, false, false);
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
