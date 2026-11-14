<?php
/**
 * La lista de notificaciones (ADR 0073), la misma en la campana y en la vista central, con el patrón del tema
 * popup-notificaciones: los filtros en chips con su número (filtros-chips), la lista, el vacío compacto (estado-vacio) y
 * «Cargar más». En la campana es el popup entero, con cabecera y pie: el tema quita el margen solo a los segmentos que
 * son hijos directos del popup. La pinta y la mueve notifications-list.js. Sin CSS propio.
 *
 * @var string $id El id de la lista; los botones «Marcar todas» la nombran en [notifications-mark-all]
 * @var bool $inPopup En la campana: carga al abrirse, lleva cabecera y la lista se desplaza dentro de su alto
 * @var string[] $filters Los de Notifications::filters()
 * @var array<string,string> $filterTitles
 * @var array<string,string> $texts Para el JS: unread, error
 * @var string $listURL
 * @var string $markURL
 * @var string $markAllURL
 * @var int $perPage
 * @var string $emptyTitle
 * @var string $emptyText
 * @var string $moreText
 * @var string $filtersLabel
 * @var string $title
 * @var string $markAllText
 * @var string $viewURL
 * @var string $viewAllText
 */
$panelID = "{$id}-panel";
?>
<div id="<?= escape_html($id); ?>"<?= $inPopup ? ' class="ui wide fitted docked popup" notifications-popup role="dialog" aria-label="' . escape_html($title) . '"' : ''; ?> notifications-list data-list-url="<?= escape_html($listURL); ?>" data-mark-url="<?= escape_html($markURL); ?>" data-mark-all-url="<?= escape_html($markAllURL); ?>" data-per-page="<?= (int) $perPage; ?>" data-load-on-open="<?= $inPopup ? 'yes' : 'no'; ?>" data-texts="<?= escape_html((string) json_encode($texts, JSON_UNESCAPED_UNICODE)); ?>">
    <div class="ui basic<?= $inPopup ? '' : ' fitted'; ?> segment">
        <div class="ui vertical relaxed flex">
            <?php if ($inPopup): ?>
            <div class="ui spaced flex">
                <strong><?= escape_html($title); ?></strong>
                <a href="#" notifications-mark-all="<?= escape_html($id); ?>" hidden><?= escape_html($markAllText); ?></a>
            </div>
            <?php endif; ?>
            <div class="ui chip labels" role="tablist" aria-label="<?= escape_html($filtersLabel); ?>">
                <?php foreach ($filters as $index => $filter): ?>
                <button class="ui<?= $index === 0 ? ' active' : ''; ?> label" type="button" id="<?= escape_html("{$id}-filter-{$filter}"); ?>" role="tab" aria-selected="<?= $index === 0 ? 'true' : 'false'; ?>" tabindex="<?= $index === 0 ? '0' : '-1'; ?>" aria-controls="<?= escape_html($panelID); ?>" notifications-filter="<?= escape_html($filter); ?>"><?= escape_html($filterTitles[$filter] ?? $filter); ?> <span class="detail" notifications-count>0</span></button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php if ($inPopup): ?>
    <div class="ui fitted divider"></div>
    <?php endif; ?>
    <div id="<?= escape_html($panelID); ?>" role="tabpanel" aria-labelledby="<?= escape_html("{$id}-filter-" . ($filters[0] ?? '')); ?>">
        <div class="ui divided selection<?= $inPopup ? ' scrolling' : ''; ?> list" role="list" notifications-items hidden></div>
        <div class="ui basic tight placeholder segment" notifications-empty hidden>
            <div class="ui icon header">
                <i class="check double icon" aria-hidden="true"></i>
                <?= escape_html($emptyTitle); ?>
                <div class="sub header"><?= escape_html($emptyText); ?></div>
            </div>
        </div>
        <?php if (!$inPopup): ?>
        <button class="ui fluid basic button" type="button" notifications-more hidden><?= escape_html($moreText); ?></button>
        <?php endif; ?>
    </div>
    <?php if ($inPopup): ?>
    <div class="ui fitted divider"></div>
    <?php //«Cargar más» en el pie: el patrón no lo trae, y aquí toma el margen del segmento en vez de ir de borde a borde. ?>
    <div class="ui basic center aligned segment">
        <div class="ui vertical relaxed flex">
            <button class="ui fluid basic button" type="button" notifications-more hidden><?= escape_html($moreText); ?></button>
            <a href="<?= escape_html($viewURL); ?>"><?= escape_html($viewAllText); ?></a>
        </div>
    </div>
    <?php endif; ?>
</div>
