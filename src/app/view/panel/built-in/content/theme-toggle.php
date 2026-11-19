<?php
/**
 * El botón luna/sol de las pantallas sin sesión con su propio html (el acceso y su ayuda, los tokens y el error). Se
 * incluye dos veces con `include`: `$themeTogglePart = 'head'` en la cabecera y `'button'` al principio del cuerpo.
 * Con sesión no pinta nada: manda la preferencia de la persona (AdminPanelController::htmlPreferenceAttributes()).
 *
 * @var string $themeTogglePart `head` (el script en línea, que aplica lo guardado antes de pintar) o `button`.
 */
//La página de error puede pintarse con la base caída: sin poder saberlo, como sin sesión.
try {
    $themeToggleSession = getLoggedFrameworkUser() !== null;
} catch (\Throwable $e) {
    $themeToggleSession = false;
}
if ($themeToggleSession) {
    return;
}
?>
<?php if (($themeTogglePart ?? '') === 'head'): ?>
<?php
//El mismo archivo, también como recurso: con una CSP sin 'unsafe-inline' el en línea no corre y este sí (con parpadeo).
add_global_asset('statics/core/js/theme-toggle.js', 'js');
$themeToggleFile = basepath('statics/core/js/theme-toggle.js');
$themeToggleScript = is_file($themeToggleFile) ? (string) file_get_contents($themeToggleFile) : '';
?>
<?php if ($themeToggleScript !== ''): ?>
<script><?= str_replace('</', '<\/', $themeToggleScript); ?></script>
<?php endif; ?>
<?php elseif (($themeTogglePart ?? '') === 'button'): ?>
<?php //Oculto hasta que el script le pone su texto: sin script, no queda un botón que no hace nada. ?>
<button type="button" class="theme-toggle" data-theme-toggle data-label-dark="<?= escape_html(__(USER_LOGIN_LANG_GROUP, 'Cambiar a modo oscuro')); ?>" data-label-light="<?= escape_html(__(USER_LOGIN_LANG_GROUP, 'Cambiar a modo claro')); ?>" hidden>
    <svg class="moon" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path fill="currentColor" d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
    <svg class="sun" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4.5" fill="currentColor"/><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M12 1.5v3M12 19.5v3M1.5 12h3M19.5 12h3M4.6 4.6l2.1 2.1M17.3 17.3l2.1 2.1M4.6 19.4l2.1-2.1M17.3 6.7l2.1-2.1"/></svg>
</button>
<?php endif; ?>
