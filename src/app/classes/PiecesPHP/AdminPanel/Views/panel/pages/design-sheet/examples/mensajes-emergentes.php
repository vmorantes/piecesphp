<?php
$toasts = [
    'success' => [__($langGroup, 'Éxito'), __($langGroup, 'Hecho'), __($langGroup, 'Los datos se guardaron.')],
    'info' => [__($langGroup, 'Información'), __($langGroup, 'Información'), __($langGroup, 'Algo que conviene saber antes de seguir.')],
    'warning' => [__($langGroup, 'Aviso'), __($langGroup, 'Aviso'), __($langGroup, 'Revise el campo marcado.')],
    'error' => [__($langGroup, 'Error'), __($langGroup, 'Error'), __($langGroup, 'No se pudo guardar.')],
];
?>
<?php foreach ($toasts as $type => [$button, $title, $message]): ?>
<button class="ui basic button" type="button" example-toast="<?= $type; ?>" data-title="<?= escape_html($title); ?>" data-message="<?= escape_html($message); ?>"><?= escape_html($button); ?></button>
<?php endforeach; ?>
