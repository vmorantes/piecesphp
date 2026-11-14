<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/en/00-tu-cuenta.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>This manual explains the screens you can use with your account. You only see the chapters for what you are allowed to
do: if your account type changes, the manual changes with it. The same applies inside each screen: if an action named in
the manual does not appear for you, your user type does not have it.</p>
<h2 class="ui header" id="logging-in-and-out">Logging in and out</h2>
<ol>
<li>On the login screen, choose how to log in:<ul>
<li><strong>“Password”</strong>: type your username or email and your password, then click <strong>“Log in”</strong>.</li>
<li><strong>“Access code”</strong>: type your username or email and click <strong>“Send code”</strong>. A six-digit code arrives by email; type
  it (or paste it whole) into the six boxes and click <strong>“Verify and log in”</strong>. The code expires after a few minutes
  (the screen says how many); if it did not arrive, wait for <strong>“Resend code”</strong> to become active.</li>
</ul>
</li>
<li>If your account has two-step verification, it then asks for the code from your app.</li>
<li>If something fails, the message appears inside the form and the field with the problem is highlighted: fix it and
   try again.</li>
<li>To log out, open your name menu, at the top right, and choose <strong>“Logout”</strong>.</li>
</ol>
<p><img class="ui image" alt="Logging in and out" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/users-form-login.webp')); ?>"></p>
<div class="ui positive message">
<div class="header">If you cannot log in</div>
<p>On the login screen, the help link lets you recover your password or username, or report another problem.</p>
</div>
<h2 class="ui header" id="help">Help</h2>
<p>The question mark icon in the top bar opens the <strong>help center</strong>: this manual and, if your organization has it
configured, the form to <strong>contact support</strong>.</p>
