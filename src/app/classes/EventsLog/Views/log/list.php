<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * El registro de acciones, con el armazón del panel (receta 10): cabecera, filtros, tabla y la ventana del detalle.
 *
 * @var string $langGroup
 * @var string $title
 * @var string $description
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 * @var \EventsLog\Controllers\HelperController $helpController
 * @var string $processTableLink
 * @var array<string,string> $types Tipo => nombre
 * @var string $scope El alcance cuando está recortado; vacío si ve todo
 */
?>
<section class="module-view-container">

    <div class="limiter-content">

        <?php
            $helpController->_render('panel/built-in/content/page-header.php', [
                'breadcrumbs' => $breadcrumbs,
                'title' => $title,
                'description' => $description,
            ]);
        ?>

        <?php if ($scope !== ''): ?>
        <p data-actions-log-scope><span class="ui grey text"><i class="filter icon"></i><?= escape_html($scope); ?></span></p>
        <?php endif; ?>

        <form class="ui form segment" data-actions-log-filters>
            <div class="fields">
                <div class="three wide field" calendar-js calendar-type="date">
                    <label><?= __($langGroup, 'Desde'); ?></label>
                    <div class="ui input left icon">
                        <i class="calendar icon"></i>
                        <input type="text" name="from" autocomplete="off">
                    </div>
                </div>
                <div class="three wide field" calendar-js calendar-type="date">
                    <label><?= __($langGroup, 'Hasta'); ?></label>
                    <div class="ui input left icon">
                        <i class="calendar icon"></i>
                        <input type="text" name="to" autocomplete="off">
                    </div>
                </div>
                <div class="five wide field">
                    <label><?= __($langGroup, 'Tipo'); ?></label>
                    <select name="type" class="ui search dropdown">
                        <option value=""><?= __($langGroup, 'Todos'); ?></option>
                        <?php foreach ($types as $type => $name): ?>
                        <option value="<?= escape_html($type); ?>"><?= escape_html($name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="five wide field">
                    <label><?= __($langGroup, 'Usuario'); ?></label>
                    <div class="ui input">
                        <input type="text" name="user">
                    </div>
                </div>
            </div>
            <button type="submit" class="ui primary button"><i class="filter icon"></i><?= __($langGroup, 'Filtrar'); ?></button>
            <button type="reset" class="ui basic button"><?= __($langGroup, 'Limpiar'); ?></button>
        </form>

        <div class="ui fluid card container-standard-table">

            <table url="<?= escape_html($processTableLink); ?>" class="ui selectable very basic table content-table" data-actions-log-table>
                <thead>
                    <tr>
                        <th class="collapsing"><?= __($langGroup, '#'); ?></th>
                        <th><?= __($langGroup, 'Módulo'); ?></th>
                        <th><?= __($langGroup, 'Evento'); ?></th>
                        <th><?= __($langGroup, 'Usuario'); ?></th>
                        <th><?= __($langGroup, 'IP'); ?></th>
                        <th><?= __($langGroup, 'Fecha'); ?></th>
                        <th class="collapsing" order="false" search="false"><?= __($langGroup, 'Acciones'); ?></th>
                    </tr>
                </thead>
            </table>

        </div>

    </div>

</section>

<?php //El detalle se rellena como TEXTO (textContent): el mensaje y la meta llevan datos que teclea cualquiera. ?>
<div class="ui modal" data-actions-log-modal data-error-text="<?= escape_html(__($langGroup, 'No se pudo pedir el detalle.')); ?>">
    <i class="close icon"></i>
    <div class="header"><?= __($langGroup, 'Detalle de la acción'); ?></div>
    <div class="content">
        <table class="ui very basic definition table">
            <tbody>
                <tr><td class="collapsing"><?= __($langGroup, 'Tipo'); ?></td><td data-detail="type"></td></tr>
                <tr><td><?= __($langGroup, 'Evento'); ?></td><td data-detail="message"></td></tr>
                <tr><td><?= __($langGroup, 'Usuario'); ?></td><td data-detail="user"></td></tr>
                <tr><td><?= __($langGroup, 'Fecha'); ?></td><td data-detail="date"></td></tr>
                <tr><td><?= __($langGroup, 'IP'); ?></td><td data-detail="ip"></td></tr>
                <tr><td><?= __($langGroup, 'País'); ?></td><td data-detail="country"></td></tr>
                <tr><td><?= __($langGroup, 'Referencia'); ?></td><td data-detail="reference"></td></tr>
            </tbody>
        </table>
        <p><strong><?= __($langGroup, 'Datos de la acción'); ?></strong></p>
        <pre class="actions-log-pre" data-detail="variables"></pre>
        <p><strong><?= __($langGroup, 'Meta (incluye quién actuaba si suplantaba)'); ?></strong></p>
        <pre class="actions-log-pre" data-detail="meta"></pre>
    </div>
</div>
