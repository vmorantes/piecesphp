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
<h2 class="ui header" id="your-preferences">Your preferences</h2>
<p>In the menu under your name, at the top right, <strong>“Preferences”</strong> takes you to the <strong>“Preferences”</strong> tab of <strong>“My
account”</strong>. It has two sections:</p>
<ul>
<li><strong>Appearance</strong>: the <strong>color mode</strong>, <strong>“Light”</strong>, <strong>“Dark”</strong> or <strong>“System”</strong> (your device's). You also have it in the
  menu under your name, under <strong>“Color mode”</strong>, to change it quickly.</li>
<li><strong>Accessibility</strong>:</li>
<li><strong>Text size</strong>: <strong>“Normal”</strong>, <strong>“Large”</strong> or <strong>“Larger”</strong>.</li>
<li><strong>Contrast</strong>: <strong>“Automatic”</strong> (it follows your device) or <strong>“More contrast”</strong>, with stronger text and colors.</li>
<li><strong>Motion</strong>: <strong>“Automatic”</strong> (it follows your device) or <strong>“Reduce motion”</strong>, with fewer animations.</li>
<li><strong>Focus</strong>: <strong>“Normal”</strong> or <strong>“Strong”</strong>, with a more visible outline on what you have selected when you move with
    the keyboard.</li>
</ul>
<p>Everything applies at once and is saved in your account. Each group also has <strong>“Like the site”</strong>, which follows whatever
the administrator chose and says next to it what that is (for example, “Like the site: More contrast”). It is what you
have until you choose something else, and choosing it undoes your choice.</p>
<h2 class="ui header" id="help">Help</h2>
<p>The question mark icon in the top bar opens the <strong>help center</strong>: this manual and, if your organization has it
configured, the form to <strong>contact support</strong>.</p>
<h2 class="ui header" id="the-assistant">The assistant</h2>
<p>If the administrator has turned it on for you, next to the notifications bell you have the <strong>assistant</strong> button. On a
phone it is in the menu under your name. It opens a window where you ask in plain language about the platform: where
something is done (“Where do I create a new user?”), or what you have pending (“Do I have unread notifications?”).</p>
<ul>
<li>It answers <strong>only with what you can see</strong> on the platform, and below the answer it leaves buttons to go to the screens
  it names.</li>
<li><strong>It only handles the platform.</strong> If you ask it something else, it says it cannot and offers what it can do.</li>
<li><strong>Enter</strong> sends; <strong>Shift+Enter</strong> adds a line break. Each question, at most 1,000 characters.</li>
<li><strong>“New conversation”</strong>, at the top of the window, empties it. <strong>Esc</strong> or the <strong>×</strong> close it.</li>
<li>If the assistant reached its usage limit, the window tells you so: when it renews or, if the limit does not renew on its
  own, that the administrator can reset it. If it is not available to you, the button does not appear.</li>
</ul>
