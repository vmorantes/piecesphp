<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * @var string $langGroup
 * @var string $title
 * @var string $processTableLink
 * @var bool $tableExists Sin la tabla no se anota nada, y una tabla vacía se leería como «no se mandó nada»
 * @var string $updateFile El archivo de databases/actualizaciones/ que hay que aplicar si falta la tabla
 * @var int $windowHours La ventana del resumen, la misma del aviso y de bin/cli mail-doctor
 * @var array{failed:int,outbox:int,delivered:int} $counts
 * @var bool $canSeeBody Sin el permiso del cuerpo, su columna no existe para quien mira (ADR 0048 §5)
 */
$escape = fn($value): string => htmlspecialchars(is_scalar($value) ? (string) $value : '', ENT_QUOTES, 'UTF-8');
?>
<main class="system-alerts-view mail-log-view">

    <section class="main-body-header">

        <div class="head">
            <h2 class="tittle"><?= $escape($title); ?></h2>
            <span class="sub-tittle"><?= __($langGroup, 'Sistema'); ?></span>
        </div>

        <div class="body-card max">

        <div>

        <p><?= __($langGroup, 'Cada correo que esta instalación intentó mandar, y si llegó. El cuerpo se guarda cifrado; los adjuntos, no.'); ?></p>

        <?php if (!$tableExists): ?>

        <div class="ui red message">
            <p><?= sprintf(
                __($langGroup, 'Falta la tabla del registro, así que no se está anotando ningún envío: ni los que fallan. Aplique «%s» en la base de datos de esta instalación.'),
                $escape($updateFile)
            ); ?></p>
        </div>

        <?php else: ?>

        <p>
            <?= sprintf(__($langGroup, 'En las últimas %d horas:'), $windowHours); ?>
            <span class="ui green label"><?= sprintf(__($langGroup, '%d entregados'), $counts['delivered']); ?></span>
            <span class="ui orange label"><?= sprintf(__($langGroup, '%d en el buzón'), $counts['outbox']); ?></span>
            <span class="ui <?= $counts['failed'] > 0 ? 'red' : 'grey'; ?> label"><?= sprintf(__($langGroup, '%d no llegaron'), $counts['failed']); ?></span>
        </p>

        <div class="mirror-scroll-x" mirror-scroll-target=".container-standard-table">
            <div class="mirror-scroll-x-content"></div>
        </div>

        <div class="container-standard-table">

            <table url="<?= $escape($processTableLink); ?>" class="ui basic table" data-mail-log-table>

                <thead>

                    <tr>
                        <th><?= __($langGroup, 'Fecha'); ?></th>
                        <th><?= __($langGroup, 'Destinatarios'); ?></th>
                        <th><?= __($langGroup, 'Asunto'); ?></th>
                        <th><?= __($langGroup, 'Origen'); ?></th>
                        <th><?= __($langGroup, 'Entrega'); ?></th>
                        <th><?= __($langGroup, 'Resultado'); ?></th>
                        <?php if ($canSeeBody): ?>
                        <th><?= __($langGroup, 'Cuerpo'); ?></th>
                        <?php endif; ?>
                    </tr>

                </thead>

            </table>

        </div>

        <?php if ($canSeeBody): ?>
        <?php //El cuerpo se pinta AISLADO: `sandbox` VACÍO —sin scripts, sin mismo origen, sin formularios—
        //y por `srcdoc`. Puede traer texto de quien llenó un formulario: pintarlo en el panel sería un XSS contra root. ?>
        <div class="ui segment" data-mail-log-body-viewer data-error-text="<?= $escape(__($langGroup, 'No se pudo pedir el cuerpo del correo.')); ?>" hidden>
            <button type="button" class="ui mini button" data-mail-log-body-close><?= __($langGroup, 'Cerrar'); ?></button>
            <p data-mail-log-body-message hidden></p>
            <iframe data-mail-log-body-frame sandbox="" title="<?= $escape(__($langGroup, 'Cuerpo del correo')); ?>" style="width: 100%; min-height: 480px; border: 0;"></iframe>
        </div>
        <?php endif; ?>

        <?php endif; ?>

        </div>

        </div>

    </section>

</main>
