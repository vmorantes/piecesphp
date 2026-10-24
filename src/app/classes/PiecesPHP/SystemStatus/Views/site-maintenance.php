<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use PiecesPHP\UserSystem\ORM\UsersModel;

/**
 * @var string $langGroup
 * @var string $title
 * @var string $saveURL
 * @var bool $enabled
 * @var int[] $allowedRoles
 * @var int $retryAfter
 * @var int $retryAfterMax
 * @var int $rootCode
 * @var int|null $currentRole
 * @var string $description
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 * @var string $indexLink
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $helpController El que pinta las piezas del panel.
 */
$escape = fn($value): string => htmlspecialchars(is_scalar($value) ? (string) $value : '', ENT_QUOTES, 'UTF-8');
//Pintada fuera del controlador (las suites la incluyen con sus propias variables): la cabecera, con lo que haya.
$helpController = isset($helpController) && $helpController instanceof \PiecesPHP\AdminPanel\Controllers\HelperController ? $helpController : new \PiecesPHP\AdminPanel\Controllers\HelperController();
$description = isset($description) && is_string($description) ? $description : '';
$breadcrumbs = isset($breadcrumbs) && is_array($breadcrumbs) ? $breadcrumbs : [];
?>
<section class="module-view-container site-maintenance-view">

    <div class="limiter-content">

        <?php
        $helpController->_render('panel/built-in/content/page-header.php', [
            'breadcrumbs' => $breadcrumbs,
            'title' => $title,
            'description' => $description,
        ]);
        ?>

        <div class="container-standard-table">
            <table class="ui basic table">
                <tbody>
                    <tr>
                        <td><?= __($langGroup, 'Estado'); ?></td>
                        <td data-site-maintenance-state><strong><?= $enabled ? __($langGroup, 'EN MANTENIMIENTO') : __($langGroup, 'Disponible'); ?></strong></td>
                    </tr>
                    <tr>
                        <td><?= __($langGroup, 'Qué responde a los visitantes'); ?></td>
                        <td><?= __($langGroup, 'HTTP 503, que le dice a un buscador que es temporal'); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <form pcs-generic-handler-js site-maintenance-form action="<?= $escape($saveURL); ?>" method="POST" class="ui form"
            data-current-role="<?= $currentRole === null ? '' : (int) $currentRole; ?>"
            data-root-code="<?= (int) $rootCode; ?>"
            data-confirm-title="<?= $escape(__($langGroup, 'Confirma el cambio')); ?>"
            data-confirm-yes="<?= $escape(__($langGroup, 'Guardar igual')); ?>"
            data-confirm-no="<?= $escape(__($langGroup, 'Cancelar')); ?>"
            data-warn-off="<?= $escape(__($langGroup, 'Vas a dejar el sitio fuera de servicio para los visitantes. Podrás volver a entrar por el acceso y apagarlo.')); ?>"
            data-warn-excluded="<?= $escape(__($langGroup, 'La lista que vas a guardar NO incluye tu rol: dejarás de navegar por el panel mientras el modo esté encendido. El usuario principal siempre puede entrar a apagarlo.')); ?>">

            <div class="container-standard-form">

                <div class="field">
                    <div class="ui toggle checkbox">
                        <input type="checkbox" name="enabled" value="1" <?= $enabled ? 'checked' : ''; ?> site-maintenance-toggle>
                        <label><?= __($langGroup, 'Sitio en mantenimiento'); ?></label>
                    </div>
                    <small><?= __($langGroup, 'El acceso al panel SIEMPRE pasa, y el usuario principal también: es la forma de volver a entrar y apagarlo.'); ?></small>
                </div>

                <div class="ui divider"></div>

                <div class="field">
                    <label><?= __($langGroup, 'Roles que siguen navegando por el panel'); ?></label>
                    <select multiple class="ui dropdown" site-maintenance-roles>
                        <?= array_to_html_options(UsersModel::getTypesUser(), $allowedRoles, true); ?>
                    </select>
                    <small><?= __($langGroup, 'Si no dejas ninguno, solo entra el usuario principal. Es un ajuste válido: que no trabaje nadie más.'); ?></small>
                </div>

                <!-- La lista viaja como JSON: un selector múltiple sin nada marcado NO manda nada, y
                     entonces «ningún rol» sería indistinguible de un error de escritura. -->
                <input type="hidden" name="allowed_roles" value="<?= $escape((string) json_encode($allowedRoles)); ?>" site-maintenance-roles-json>

                <div class="field">
                    <label><?= __($langGroup, 'Segundos que se le piden a los buscadores antes de volver (Retry-After)'); ?></label>
                    <input type="number" name="retry_after" min="1" max="<?= (int) $retryAfterMax; ?>" value="<?= (int) $retryAfter; ?>">
                </div>

            </div>

            <?php
            $helpController->_render('panel/built-in/content/form-action-bar.php', [
                'saveText' => __($langGroup, 'Guardar'),
                'secondaryActions' => [['text' => __($langGroup, 'Cancelar'), 'type' => 'reset', 'icon' => 'times']],
            ]);
            ?>

        </form>

    </div>

</section>
