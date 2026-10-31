<?php
use News\Mappers\NewsMapper;
use PiecesPHP\Core\Utilities\Helpers\StatusLabel;
use Publications\Mappers\PublicationMapper;

//StatusLabel pinta el estado: el color y el texto salen del mapper, nunca de la vista.
$visibility = PublicationMapper::VISIBILITY_SCHEDULED;
$color = PublicationMapper::VISIBILITIES_COLORS[$visibility];
$text = PublicationMapper::visibilities()[$visibility];
?>
<?= StatusLabel::html($text, $color); ?>
<?php foreach (NewsMapper::statuses() as $status => $statusText): ?>
<?= StatusLabel::html($statusText, NewsMapper::STATUSES_COLORS[$status]); ?>
<?php endforeach; ?>
<?= StatusLabel::html(__($langGroup, 'Advertencia'), StatusLabel::WARNING); ?>
<?= StatusLabel::neutral(__($langGroup, 'Neutro')); ?>
