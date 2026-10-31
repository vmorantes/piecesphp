<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * La ventana de extender una sesión (la abre sessions.js con el botón [data-session-extend]).
 *
 * @var string $langGroup
 */
?>
<div class="ui tiny modal" data-session-extend-modal>
    <div class="header"><?= __($langGroup, 'Extender sesión'); ?></div>
    <div class="content">
        <form class="ui form" data-session-extend-form>
            <div class="field">
                <label><?= __($langGroup, 'Días desde ahora'); ?></label>
                <div class="ui input">
                    <input type="number" name="days" min="1" max="365" value="30" required>
                </div>
            </div>
            <p><span class="ui grey text"><?= __($langGroup, 'Como mucho hasta'); ?> <span data-session-ceiling></span></span></p>
        </form>
    </div>
    <div class="actions">
        <button type="button" class="ui basic button" data-session-extend-cancel><?= __($langGroup, 'Cancelar'); ?></button>
        <button type="button" class="ui primary button" data-session-extend-confirm><?= __($langGroup, 'Extender'); ?></button>
    </div>
</div>
