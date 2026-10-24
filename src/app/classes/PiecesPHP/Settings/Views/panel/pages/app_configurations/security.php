<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 * @var string $actionURL
 * @var string $title
 * @var string $description
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 * @var string $indexLink
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $helpController El que pinta las piezas del panel.
 */
//En días en la pantalla y en segundos en la configuración (SessionToken): un valor que no es un número de días se redondea hacia arriba.
$days = static fn(int $seconds): int => (int) ceil($seconds / 86400);
?>

<section class="module-view-container security-view">

    <div class="limiter-content">

        <?php
        $helpController->_render('panel/built-in/content/page-header.php', [
            'breadcrumbs' => $breadcrumbs,
            'title' => $title,
            'description' => $description,
        ]);
        ?>

            <form action="<?= $actionURL; ?>" method="POST" class="ui form initial security" data-content-form>

            <div class="container-standard-form">

                <div class="fields">

                    <div class="field">
                        <div class="ui toggle checkbox">
                            <input type="checkbox" name="check_aud_on_auth" <?= get_config('check_aud_on_auth') ? 'checked' : ''; ?>>
                            <label><?= __($langGroup, 'Usar IP del usuario para encriptar el token de sesión'); ?></label>
                        </div>
                    </div>

                    <div class="field">
                        <div class="ui toggle checkbox">
                            <input type="checkbox" name="hide_app_key_warning" <?= get_config('hide_app_key_warning') === true ? 'checked' : ''; ?>>
                            <label><?= __($langGroup, 'Ocultar en el panel el aviso de app_key de relleno'); ?></label>
                        </div>
                    </div>

                </div>

                <div class="form-section-title"><?= __($langGroup, 'Duración de la sesión'); ?></div>
                <p><?= __($langGroup, 'Cada uso de la sesión corre su caducidad; el tope la cierra pase lo que pase. Con 0, no caduca por ese motivo, y siempre se puede cerrar desde «Mis sesiones».'); ?></p>

                <div class="two fields">
                    <div class="field">
                        <label><?= __($langGroup, 'Días sin actividad que cierran la sesión'); ?></label>
                        <input type="number" name="session_inactivity" min="0" max="365" step="1" required value="<?= $days(\PiecesPHP\Core\SessionToken::inactivity()); ?>">
                        <small><?= __($langGroup, '0 = no caduca por inactividad.'); ?></small>
                    </div>
                    <div class="field">
                        <label><?= __($langGroup, 'Días desde que se abrió que la cierran (tope)'); ?></label>
                        <input type="number" name="session_ceiling" min="0" max="365" step="1" required value="<?= $days(\PiecesPHP\Core\SessionToken::ceiling()); ?>">
                        <small><?= __($langGroup, '0 = sin tope.'); ?></small>
                    </div>
                </div>

            </div>

            <?php
            $helpController->_render('panel/built-in/content/form-action-bar.php', [
                'saveText' => __($langGroup, 'Guardar'),
                'secondaryActions' => [['text' => __($langGroup, 'Cancelar'), 'url' => $indexLink, 'icon' => 'times']],
            ]);
            ?>

            </form>

    </div>

</section>
