<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use PiecesPHP\Core\Utilities\Helpers\StatusLabel;
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
 * @var string $indexLink
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $helpController El que pinta las piezas del panel.
 */
$escape = fn($value): string => htmlspecialchars(is_scalar($value) ? (string) $value : '', ENT_QUOTES, 'UTF-8');
//Pintada fuera del controlador (las suites la incluyen con sus propias variables): la cabecera, con lo que haya.
$helpController = isset($helpController) && $helpController instanceof \PiecesPHP\AdminPanel\Controllers\HelperController ? $helpController : new \PiecesPHP\AdminPanel\Controllers\HelperController();
$description = isset($description) && is_string($description) ? $description : '';
?>
<section class="module-view-container site-maintenance-view">

    <?php
        $helpController->_render('panel/built-in/content/page-header.php', [
            'title' => $title,
            'description' => $description,
        ]);
    ?>

    <form pcs-generic-handler-js site-maintenance-form action="<?= $escape($saveURL); ?>" method="POST" class="ui form segment"
        data-current-role="<?= $currentRole === null ? '' : (int) $currentRole; ?>"
        data-root-code="<?= (int) $rootCode; ?>"
        data-confirm-title="<?= $escape(__($langGroup, 'Confirma el cambio')); ?>"
        data-confirm-yes="<?= $escape(__($langGroup, 'Guardar igual')); ?>"
        data-confirm-no="<?= $escape(__($langGroup, 'Cancelar')); ?>"
        data-warn-off="<?= $escape(__($langGroup, 'Vas a dejar el sitio fuera de servicio para los visitantes. Podrás volver a entrar por el acceso y apagarlo.')); ?>"
        data-warn-excluded="<?= $escape(__($langGroup, 'La lista que vas a guardar NO incluye tu rol: dejarás de navegar por el panel mientras el modo esté encendido. El usuario principal siempre puede entrar a apagarlo.')); ?>">

        <div class="ui secondary segment">
            <div class="ui stackable spaced top aligned wide flex">
                <div class="fluid field">
                    <div class="ui toggle checkbox">
                        <input type="checkbox" id="site-maintenance-enabled" name="enabled" value="1" <?= $enabled ? 'checked' : ''; ?> site-maintenance-toggle>
                        <label for="site-maintenance-enabled"><?= __($langGroup, 'Sitio en mantenimiento'); ?></label>
                    </div>
                    <div class="description" aria-live="polite" data-site-maintenance-effect data-text-on="<?= $escape(__($langGroup, 'Los visitantes ven una página de aviso con el código HTTP 503 (no disponible por ahora), que indica a los buscadores que el cierre es temporal.')); ?>" data-text-off="<?= $escape(__($langGroup, 'Los visitantes ven el sitio con normalidad.')); ?>"></div>
                    <div class="description"><?= __($langGroup, 'El acceso al panel SIEMPRE pasa, y el usuario principal también: es la forma de volver a entrar y apagarlo.'); ?></div>
                </div>
                <div>
                    <span data-site-maintenance-state="on"<?= $enabled ? '' : ' hidden'; ?>><?= StatusLabel::html(__($langGroup, 'En mantenimiento'), StatusLabel::WARNING); ?></span>
                    <span data-site-maintenance-state="off"<?= $enabled ? ' hidden' : ''; ?>><?= StatusLabel::html(__($langGroup, 'Disponible'), StatusLabel::POSITIVE); ?></span>
                </div>
            </div>
        </div>

        <div class="ui section divider"></div>

        <div class="field">
            <label for="site-maintenance-roles"><?= __($langGroup, 'Roles que pueden seguir entrando'); ?></label>
            <select multiple class="ui fluid search dropdown" id="site-maintenance-roles" site-maintenance-roles>
                <?= array_to_html_options(UsersModel::getTypesUser(), $allowedRoles, true); ?>
            </select>
            <div class="description"><?= __($langGroup, 'Mientras dure el mantenimiento, estos roles usan el sitio y el panel como siempre. Si no eliges ninguno, solo entra el usuario principal.'); ?></div>
        </div>

        <!-- La lista viaja como JSON: un selector múltiple sin nada marcado NO manda nada, y
             entonces «ningún rol» sería indistinguible de un error de escritura. -->
        <input type="hidden" name="allowed_roles" value="<?= $escape((string) json_encode($allowedRoles)); ?>" site-maintenance-roles-json>

        <div class="fields">
            <div class="five wide field">
                <label for="site-maintenance-retry"><?= __($langGroup, 'Tiempo para volver a revisar (Retry-After)'); ?></label>
                <input type="number" id="site-maintenance-retry" name="retry_after" min="1" max="<?= (int) $retryAfterMax; ?>" value="<?= (int) $retryAfter; ?>" data-live-reading="seconds">
                <div class="description"><span><?= __($langGroup, 'Segundos que se sugiere a los buscadores esperar antes de volver a revisar el sitio.'); ?></span><span class="counter" data-live-reading-for="site-maintenance-retry" aria-live="polite"></span></div>
            </div>
        </div>

        <?php
            $helpController->_render('panel/built-in/content/form-action-bar.php', [
                'saveText' => __($langGroup, 'Guardar'),
                'secondaryActions' => [['text' => __($langGroup, 'Cancelar'), 'type' => 'reset', 'icon' => 'times']],
            ]);
        ?>

    </form>

</section>
