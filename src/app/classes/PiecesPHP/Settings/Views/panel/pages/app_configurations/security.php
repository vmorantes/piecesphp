<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 * @var string $actionURL
 * @var string $title
 * @var string $description
 * @var string $indexLink
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $helpController El que pinta las piezas del panel.
 */
//En días en la pantalla y en segundos en la configuración (SessionToken): un valor que no es un número de días se redondea hacia arriba.
$days = static fn(int $seconds): int => (int) ceil($seconds / 86400);
?>

<section class="module-view-container security-view">

    <?php
        $helpController->_render('panel/built-in/content/page-header.php', [
            'title' => $title,
            'description' => $description,
        ]);
    ?>

    <form action="<?= $actionURL; ?>" method="POST" class="ui form initial security segment" data-content-form>

        <section aria-labelledby="security-session-title">
            <?php
                $helpController->_render('panel/built-in/content/section-header.php', [
                    'id' => 'security-session-title',
                    'title' => __($langGroup, 'Protección de la sesión'),
                ]);
                $helpController->_render('panel/built-in/content/toggle-field.php', [
                    'id' => 'security-check-aud',
                    'inputName' => 'check_aud_on_auth',
                    'label' => __($langGroup, 'Usar IP del usuario para encriptar el token de sesión'),
                    'checked' => (bool) get_config('check_aud_on_auth'),
                    'description' => __($langGroup, 'La sesión queda atada a la dirección IP desde la que se inició. Si la IP del usuario cambia (por ejemplo, al pasar del wifi a los datos móviles), tendrá que volver a iniciar sesión.'),
                ]);
                $helpController->_render('panel/built-in/content/toggle-field.php', [
                    'id' => 'security-hide-app-key',
                    'inputName' => 'hide_app_key_warning',
                    'label' => __($langGroup, 'Ocultar en el panel el aviso de app_key de relleno'),
                    'checked' => get_config('hide_app_key_warning') === true,
                    'description' => __($langGroup, 'El aviso aparece cuando la llave que cifra los datos (app_key) sigue siendo la de ejemplo que trae la instalación. Ocúltalo solo si sabes por qué se mantiene así.'),
                ]);
            ?>
        </section>

        <div class="ui section divider"></div>

        <section aria-labelledby="security-duration-title">
            <?php
                $helpController->_render('panel/built-in/content/section-header.php', [
                    'id' => 'security-duration-title',
                    'title' => __($langGroup, 'Duración de la sesión'),
                    'description' => __($langGroup, 'Cada vez que se usa la sesión vuelve a empezar la cuenta de inactividad. El tope cierra la sesión pase lo que pase, aunque se siga usando. Con 0 ese límite no se aplica. Siempre puedes cerrar sesiones abiertas desde Mis sesiones.'),
                ]);
            ?>
            <div class="two fields">
                <div class="field">
                    <label for="security-inactivity"><?= __($langGroup, 'Días sin actividad que cierran la sesión'); ?></label>
                    <input type="number" id="security-inactivity" name="session_inactivity" min="0" max="365" step="1" required value="<?= $days(\PiecesPHP\Core\SessionToken::inactivity()); ?>" data-live-reading="days" data-live-reading-zero="<?= escape_html(__($langGroup, 'Sin límite')); ?>">
                    <div class="description"><span><?= __($langGroup, '0 = no caduca por inactividad'); ?></span><span class="counter" data-live-reading-for="security-inactivity" aria-live="polite"></span></div>
                </div>
                <div class="field">
                    <label for="security-ceiling"><?= __($langGroup, 'Días desde que se abrió que la cierran (tope)'); ?></label>
                    <input type="number" id="security-ceiling" name="session_ceiling" min="0" max="365" step="1" required value="<?= $days(\PiecesPHP\Core\SessionToken::ceiling()); ?>" data-live-reading="days" data-live-reading-zero="<?= escape_html(__($langGroup, 'Sin tope')); ?>">
                    <div class="description"><span><?= __($langGroup, '0 = sin tope'); ?></span><span class="counter" data-live-reading-for="security-ceiling" aria-live="polite"></span></div>
                </div>
            </div>
        </section>

        <?php
            $helpController->_render('panel/built-in/content/form-action-bar.php', [
                'saveText' => __($langGroup, 'Guardar'),
                'secondaryActions' => [['text' => __($langGroup, 'Cancelar'), 'url' => $indexLink, 'icon' => 'times']],
            ]);
        ?>

    </form>

</section>
