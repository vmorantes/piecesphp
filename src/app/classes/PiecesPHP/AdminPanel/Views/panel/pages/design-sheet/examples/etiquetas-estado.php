<?php
use News\Mappers\NewsMapper;
use Publications\Mappers\PublicationMapper;

//Un `ui label` con el color de su estado; el color y el texto salen del mapper, nunca de la vista.
$visibility = PublicationMapper::VISIBILITY_SCHEDULED;
$color = PublicationMapper::VISIBILITIES_COLORS[$visibility];
$text = PublicationMapper::visibilities()[$visibility];
?>
<span class="ui <?= escape_html($color); ?> label"><?= escape_html($text); ?></span>
<?php foreach (NewsMapper::statuses() as $status => $statusText): ?>
<span class="ui <?= escape_html(NewsMapper::STATUSES_COLORS[$status]); ?> label"><?= escape_html($statusText); ?></span>
<?php endforeach; ?>
