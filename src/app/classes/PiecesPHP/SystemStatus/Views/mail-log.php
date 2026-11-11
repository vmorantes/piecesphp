<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\Core\Utilities\Helpers\StatusLabel;

/**
 * La pestaña «Correos enviados» de «Registros»: sin cabecera propia, la pone la pantalla.
 *
 * @var string $langGroup
 * @var string $processTableLink
 * @var bool $tableExists Sin la tabla no se anota nada, y una tabla vacía se leería como «no se mandó nada»
 * @var string $updateFile El archivo de databases/actualizaciones/ que hay que aplicar si falta la tabla
 * @var int $windowHours La ventana del resumen, la misma del aviso y de bin/cli mail-doctor
 * @var array{failed:int,outbox:int,delivered:int} $counts
 * @var bool $canSeeBody Sin el permiso del cuerpo, su columna no existe para quien mira (ADR 0048 §5)
 * @var array<string,string> $results Resultado => su rótulo
 * @var string[] $deliveries Las entregas con que se anota un correo
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $pieces Pinta las piezas del panel
 */
$escape = fn($value): string => htmlspecialchars(is_scalar($value) ? (string) $value : '', ENT_QUOTES, 'UTF-8');
?>
<div class="mail-log-view">

    <?php if (!$tableExists): ?>

    <div class="ui red message">
        <p><?= sprintf(
    __($langGroup, 'Falta la tabla del registro, así que no se está anotando ningún envío: ni los que fallan. Aplique «%s» en la base de datos de esta instalación.'),
    $escape($updateFile)
); ?></p>
    </div>

    <?php else: ?>

    <p class="ui flex">
        <span><?= sprintf(__($langGroup, 'En las últimas %d horas:'), $windowHours); ?></span>
        <?= StatusLabel::html(sprintf(__($langGroup, '%d entregados'), $counts['delivered']), StatusLabel::POSITIVE); ?>
        <?= StatusLabel::html(sprintf(__($langGroup, '%d en el buzón'), $counts['outbox']), StatusLabel::WARNING); ?>
        <?= $counts['failed'] > 0 ? StatusLabel::html(sprintf(__($langGroup, '%d no llegaron'), $counts['failed']), StatusLabel::NEGATIVE) : StatusLabel::neutral(sprintf(__($langGroup, '%d no llegaron'), $counts['failed'])); ?>
    </p>

    <?php //Los filtros van en la dirección de los datos (mail-log.js), como en el registro de actividad. ?>
    <form class="ui form segment" data-mail-log-filters>
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
            <div class="three wide field">
                <label><?= __($langGroup, 'Resultado'); ?></label>
                <select name="result" class="ui dropdown">
                    <option value=""><?= __($langGroup, 'Todos'); ?></option>
                    <?php foreach ($results as $value => $label): ?>
                    <option value="<?= $escape($value); ?>"><?= $escape($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="three wide field">
                <label><?= __($langGroup, 'Entrega'); ?></label>
                <select name="delivery" class="ui dropdown">
                    <option value=""><?= __($langGroup, 'Todos'); ?></option>
                    <?php foreach ($deliveries as $value): ?>
                    <option value="<?= $escape($value); ?>"><?= $escape($value); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="four wide field">
                <label><?= __($langGroup, 'Origen'); ?></label>
                <div class="ui input">
                    <input type="text" name="origin">
                </div>
            </div>
        </div>
        <?php $pieces->_render('panel/built-in/content/filter-actions.php'); ?>
    </form>

    <div class="mirror-scroll-x" mirror-scroll-target=".container-standard-table">
        <div class="mirror-scroll-x-content"></div>
    </div>

    <div class="ui fluid card container-standard-table">

        <table url="<?= $escape($processTableLink); ?>" class="ui selectable very basic table content-table" data-search-placeholder="<?= $escape(__($langGroup, 'Destinatario, asunto u origen')); ?>" data-mail-log-table>

            <thead>

                <tr>
                    <th><?= __($langGroup, 'Fecha'); ?></th>
                    <th><?= __($langGroup, 'Destinatarios'); ?></th>
                    <th><?= __($langGroup, 'Asunto'); ?></th>
                    <th><?= __($langGroup, 'Origen'); ?></th>
                    <th><?= __($langGroup, 'Entrega'); ?></th>
                    <th><?= __($langGroup, 'Resultado'); ?></th>
                    <?php if ($canSeeBody): ?>
                    <th class-name="collapsing right aligned" order="false"><?= __($langGroup, 'Cuerpo'); ?></th>
                    <?php endif; ?>
                </tr>

            </thead>

        </table>

    </div>

    <?php if ($canSeeBody): ?>
    <?php //El cuerpo se pinta AISLADO: `sandbox` VACÍO —sin scripts, sin mismo origen, sin formularios—
    //y por `srcdoc`. Puede traer texto de quien llenó un formulario: pintarlo en el panel sería un XSS contra root. ?>
    <?php //En una ventana modal de Fomantic: Cerrar y Esc la cierran (mail-log.js), y al cerrarla se vacía el iframe. ?>
    <div class="ui large modal" data-mail-log-body-viewer data-error-text="<?= $escape(__($langGroup, 'No se pudo pedir el cuerpo del correo.')); ?>">
        <div class="header"><?= __($langGroup, 'Cuerpo del correo'); ?></div>
        <div class="scrolling content">
            <p data-mail-log-body-message hidden></p>
            <iframe data-mail-log-body-frame sandbox="" title="<?= $escape(__($langGroup, 'Cuerpo del correo')); ?>" style="width: 100%; min-height: 480px; border: 0;"></iframe>
        </div>
        <div class="actions">
            <button type="button" class="ui basic button" data-mail-log-body-close><?= __($langGroup, 'Cerrar'); ?></button>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>

</div>
