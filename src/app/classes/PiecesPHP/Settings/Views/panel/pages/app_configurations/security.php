<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 * @var string $actionURL
 * @var string $title
 * @var string $description
 * @var string $indexLink
 * @var bool $canEditProxies Solo root ve y guarda los proxies de confianza
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

        <div class="ui section divider"></div>

        <section aria-labelledby="security-app-sessions-title">
            <?php
                $helpController->_render('panel/built-in/content/section-header.php', [
                    'id' => 'security-app-sessions-title',
                    'title' => __($langGroup, 'Sesiones de aplicaciones móviles'),
                    'description' => __($langGroup, 'Para las apps que entran con su propia clave de dispositivo; la web no cambia.'),
                ]);
            ?>
            <div class="three fields">
                <div class="field">
                    <label for="security-app-access"><?= __($langGroup, 'Minutos que vale el acceso de una app'); ?></label>
                    <input type="number" id="security-app-access" name="session_app_access_lifetime" min="5" max="1440" step="1" required value="<?= (int) ceil(\PiecesPHP\Core\SessionToken::appAccessLifetime() / 60); ?>">
                    <div class="description"><span><?= __($langGroup, 'De 5 a 1440 minutos'); ?></span></div>
                </div>
                <div class="field">
                    <label for="security-app-inactivity"><?= __($langGroup, 'Días sin actividad que cierran la sesión de una app'); ?></label>
                    <input type="number" id="security-app-inactivity" name="session_app_inactivity" min="0" max="365" step="1" required value="<?= $days(\PiecesPHP\Core\SessionToken::appInactivity()); ?>" data-live-reading="days" data-live-reading-zero="<?= escape_html(__($langGroup, 'Sin límite')); ?>">
                    <div class="description"><span><?= __($langGroup, '0 = no caduca por inactividad'); ?></span><span class="counter" data-live-reading-for="security-app-inactivity" aria-live="polite"></span></div>
                </div>
                <div class="field">
                    <label for="security-app-ceiling"><?= __($langGroup, 'Tope en días de la sesión de una app'); ?></label>
                    <input type="number" id="security-app-ceiling" name="session_app_ceiling" min="0" max="365" step="1" required value="<?= $days(\PiecesPHP\Core\SessionToken::appCeiling()); ?>" data-live-reading="days" data-live-reading-zero="<?= escape_html(__($langGroup, 'Sin tope')); ?>">
                    <div class="description"><span><?= __($langGroup, '0 = sin tope'); ?></span><span class="counter" data-live-reading-for="security-app-ceiling" aria-live="polite"></span></div>
                </div>
            </div>
        </section>

        <?php if (($canEditProxies ?? false) === true): ?>
        <?php
            //El diagnóstico de esta petición: lo que llega y la IP que la plataforma usa con la lista guardada (H28).
            $remoteAddr = is_string($_SERVER['REMOTE_ADDR'] ?? null) ? $_SERVER['REMOTE_ADDR'] : '';
            $forwardedFor = is_string($_SERVER['HTTP_X_FORWARDED_FOR'] ?? null) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '';
            $realIP = is_string($_SERVER['HTTP_X_REAL_IP'] ?? null) ? $_SERVER['HTTP_X_REAL_IP'] : '';
            $trustedProxies = \PiecesPHP\Core\VisitorIP::trustedProxies();
            $noHeader = __($langGroup, '(no llega)');
        ?>
        <div class="ui section divider"></div>

        <section aria-labelledby="security-proxies-title" data-trusted-proxies>
            <?php
                $helpController->_render('panel/built-in/content/section-header.php', [
                    'id' => 'security-proxies-title',
                    'title' => __($langGroup, 'Proxies de confianza'),
                    'description' => __($langGroup, 'Si la plataforma está detrás de un proxy, un balanceador o una red de distribución (CDN), todas las visitas llegan con la IP de ese intermediario, y los frenos de acceso frenarían a todos a la vez. Pon aquí las IP de tus intermediarios: solo de ellos se leerá la IP real del visitante (X-Forwarded-For). Sin intermediarios, deja la lista vacía.'),
                ]);
            ?>
            <?php if (\PiecesPHP\Core\VisitorIP::proxyWithoutList($_SERVER, $trustedProxies)): ?>
            <div class="ui warning message" data-proxies-without-list><p><?= __($langGroup, 'La conexión llega desde una dirección privada o local y trae X-Forwarded-For, pero la lista está vacía: la plataforma ve a todos los visitantes con la IP del intermediario, y «Usar IP del usuario para encriptar el token de sesión» no ata la sesión a nada. Añade el intermediario a la lista.'); ?></p></div>
            <?php endif; ?>
            <table class="ui very basic compact table" data-proxies-diagnostic>
                <tbody>
                    <tr><th><?= __($langGroup, 'IP de la conexión (REMOTE_ADDR)'); ?></th><td><code data-remote-addr><?= escape_html($remoteAddr); ?></code></td></tr>
                    <tr><th>X-Forwarded-For</th><td><code><?= escape_html($forwardedFor !== '' ? $forwardedFor : $noHeader); ?></code></td></tr>
                    <tr><th>X-Real-IP</th><td><code><?= escape_html($realIP !== '' ? $realIP : $noHeader); ?></code></td></tr>
                    <tr><th><?= __($langGroup, 'IP del visitante que usa la plataforma'); ?></th><td><code><?= escape_html(\PiecesPHP\Core\VisitorIP::get()); ?></code></td></tr>
                </tbody>
            </table>
            <div class="field">
                <label for="security-trusted-proxies"><?= __($langGroup, 'Una IP o un rango CIDR por línea'); ?></label>
                <textarea id="security-trusted-proxies" name="<?= \PiecesPHP\Core\VisitorIP::CONFIG_NAME; ?>" rows="4" placeholder="203.0.113.10&#10;2001:db8::/32"><?= escape_html(implode("\n", $trustedProxies)); ?></textarea>
                <div class="description"><?= __($langGroup, 'Con «Usar IP del usuario para encriptar el token de sesión» activa, cambiar esta lista cierra la sesión de quien entra a través de los proxies que añadas o quites: tendrá que volver a iniciarla una vez.'); ?></div>
            </div>
            <?php if ($forwardedFor === ''): ?>
            <button type="button" class="ui basic button" data-add-remote-addr disabled data-add-remote-addr-disabled><i class="plus icon"></i><?= __($langGroup, 'Añadir esta IP a la lista'); ?></button>
            <div class="description" data-add-remote-addr-help><?= __($langGroup, 'Se activa cuando la petición llega a través de un intermediario (con X-Forwarded-For).'); ?></div>
            <?php elseif (!\PiecesPHP\Core\VisitorIP::isPrivateOrLocal($remoteAddr)): ?>
            <button type="button" class="ui basic button" data-add-remote-addr data-remote-public data-confirm-message="<?= escape_html(__($langGroup, 'La IP de la conexión es pública. Añádela solo si es la de tu proxy o tu CDN, no la tuya: una IP de confianza puede decir que es cualquier visitante. ¿Añadirla?')); ?>"><i class="plus icon"></i><?= __($langGroup, 'Añadir esta IP a la lista'); ?></button>
            <?php else: ?>
            <button type="button" class="ui basic button" data-add-remote-addr><i class="plus icon"></i><?= __($langGroup, 'Añadir esta IP a la lista'); ?></button>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <?php
            $helpController->_render('panel/built-in/content/form-action-bar.php', [
                'saveText' => __($langGroup, 'Guardar'),
                'secondaryActions' => [['text' => __($langGroup, 'Cancelar'), 'url' => $indexLink, 'icon' => 'times']],
            ]);
        ?>

    </form>

</section>
