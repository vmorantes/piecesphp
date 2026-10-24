<?php
use News\Mappers\NewsMapper;
use Publications\Mappers\PublicationMapper;

//Los colores de estado de la familia de contenidos viven en las constantes de cada mapper: se leen de ahí.
$publicationStates = PublicationMapper::visibilities();
$newsStates = NewsMapper::statuses() + [NewsMapper::PSEUDO_STATUS_DRAFT => __(NewsMapper::LANG_GROUP, 'Borrador')];
?>
<table class="ui very basic compact table">
    <tbody>
        <?php foreach (PublicationMapper::VISIBILITIES_COLORS as $state => $color): ?>
        <tr>
            <td><code>PublicationMapper::VISIBILITIES_COLORS</code></td>
            <td><span class="ui <?= escape_html($color); ?> label"><?= escape_html($publicationStates[$state] ?? (string) $state); ?></span></td>
            <td><code><?= escape_html($color); ?></code></td>
        </tr>
        <?php endforeach; ?>
        <?php foreach (NewsMapper::STATUSES_COLORS as $state => $color): ?>
        <tr>
            <td><code>NewsMapper::STATUSES_COLORS</code></td>
            <td><span class="ui <?= escape_html($color); ?> label"><?= escape_html($newsStates[$state] ?? (string) $state); ?></span></td>
            <td><code><?= escape_html($color); ?></code></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
