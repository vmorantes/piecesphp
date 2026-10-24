<?php
use Documents\Controllers\DocumentsController;

//Las del explorador de documentos: sin miniatura, el icono de su tipo de archivo (DocumentsController::fileTypeIcon()).
$cards = [
    ['name' => __($langGroup, 'Informe anual'), 'type' => __($langGroup, 'Informes'), 'file' => 'informe-anual.pdf'],
    ['name' => __($langGroup, 'Presupuesto'), 'type' => __($langGroup, 'Finanzas'), 'file' => 'presupuesto.xlsx'],
    ['name' => __($langGroup, 'Acta de la reunión'), 'type' => __($langGroup, 'Actas'), 'file' => 'acta.docx'],
];
?>
<div class="ui three doubling stackable cards">
    <?php foreach ($cards as $card): ?>
    <div class="card">
        <div class="center aligned content">
            <i class="huge grey <?= escape_html(DocumentsController::fileTypeIcon($card['file'])); ?> icon"></i>
        </div>
        <div class="content">
            <div class="header"><?= escape_html($card['name']); ?></div>
            <div class="meta"><?= escape_html($card['type']); ?></div>
        </div>
        <a class="ui bottom attached button" href="#"><i class="external alternate icon"></i><?= __($langGroup, 'Abrir'); ?></a>
    </div>
    <?php endforeach; ?>
</div>
