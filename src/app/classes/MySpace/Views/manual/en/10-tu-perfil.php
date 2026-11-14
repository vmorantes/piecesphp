<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/en/10-tu-perfil.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>Everything about your account is in your name menu, at the top right.</p>
<h2 class="ui header" id="your-details">Your details</h2>
<p>Choose <strong>“Edit account”</strong>: your account opens in a side panel. Change what you need and save.</p>
<h2 class="ui header" id="your-password">Your password</h2>
<p>Choose <strong>“Change password”</strong>. To change it, the system first asks for your current password.</p>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['my-space-admin-user-security'])): ?>
<h2 class="ui header" id="your-account-security">Your account security</h2>
<p>In your user menu, choose <strong>“Security”</strong>: next to it you see whether your two-step verification is <strong>“Active”</strong> or
<strong>“Inactive”</strong>. The <strong>“Security”</strong> screen opens on the two-step verification tab. On the <strong>“My sessions”</strong> tab you see
the sessions open with your account. For each one you can <strong>“Close”</strong> the one you do not recognize or <strong>“Extend”</strong> your
own, and <strong>“Close the other sessions”</strong> closes all of them except the one you are using.</p>
<p><img class="ui image" alt="Your account security" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/my-space-admin-user-security.webp')); ?>"></p>
<?php endif; ?>
