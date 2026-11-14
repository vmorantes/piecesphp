<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/en/85-correos-del-sitio.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>Here you see, one below the other, all the emails the site can send, each with sample data: how it arrives,
when it is sent, to whom and with what subject. Opening this screen does not send anything.</p>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['mail-catalog-list'])): ?>
<h2 class="ui header" id="site-emails">Site emails</h2>
<p>To get there, use the gear button in the top bar, <strong>“System settings” › “System” › “Site
emails”</strong>.</p>
<p><img class="ui image" alt="Site emails" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/mail-catalog-list.webp')); ?>"></p>
<p>Each email has its own card, with:</p>
<ul>
<li>its <strong>name</strong> and <strong>when it is sent</strong> (for example, when a code is requested to recover the password);</li>
<li><strong>who</strong> it is sent to and its <strong>subject</strong>;</li>
<li>the <strong>email as it arrives</strong>, with made-up data: the names, codes and links in the sample belong to nobody.</li>
</ul>
<p>On one side (on mobile, at the top, in a dropdown) is the list of emails grouped by module: one click takes you to its
card, and the list marks the one you are viewing.</p>
<p>Only the emails of the modules that are active in your installation appear. If your project adds its own emails and
declares them, they appear here next to the system's.</p>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['mail-catalog-send-test'])): ?>
<h2 class="ui header" id="send-a-test">Send a test</h2>
<p>The <strong>“Send test”</strong> button on each card sends that email, with its sample data, to the address in the
<strong>“Recipient of the test email”</strong> field (by default, yours). It only appears when the email is held: with
<strong>“Mail delivery”</strong> set to <strong>“Held: it never leaves this machine”</strong>, or to <strong>“Based on the environment”</strong> in a
development installation (in <strong>“System settings” › “System” › “Mail”</strong>). Then it goes through the test server
configured there, not through real delivery. The subject is prefixed with <strong>[PRUEBA]</strong> and the send is recorded in the
action log, with the recipient. If the email is not held, the screen tells you why it cannot be sent.</p>
<?php endif; ?>
