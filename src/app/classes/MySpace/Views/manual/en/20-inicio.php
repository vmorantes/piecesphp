<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/en/20-inicio.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>The home screen is the first thing you see when you log in: a summary of what concerns you at a glance.</p>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['admin'])): ?>
<h2 class="ui header" id="the-home-screen">The home screen</h2>
<p>How to get there: <strong>“Home”</strong>, in the side menu.</p>
<p><img class="ui image" alt="The home screen" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/admin.webp')); ?>"></p>
<p>At the top you see a greeting with your name. Below there are cards, each with its topic and a link to the details:</p>
<ul>
<li><strong>My sessions</strong>: how many sessions you have open with your account, with the link <strong>“See my sessions”</strong>.</li>
<li><strong>Users and sign-ins</strong>: active users and today's sign-ins, with the link <strong>“See the sign-ins”</strong>.</li>
<li><strong>Errors and notices</strong>: it is collapsed. Click <strong>“Expand”</strong> to see the figures. Its link is <strong>“See the errors”</strong>.</li>
<li><strong>General report</strong>: a summary of the platform's figures, with the link <strong>“View the report”</strong>.</li>
</ul>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['reports-manage-admin-generic-report-view'])): ?>
<h2 class="ui header" id="general-report">General report</h2>
<p>The platform's figures: publications, organizations and users.</p>
<p><img class="ui image" alt="General report" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/reports-manage-admin-generic-report-view.webp')); ?>"></p>
<p>How to get there: from the <strong>“General report”</strong> card on the home screen, with <strong>“View the report”</strong>.</p>
<p>What you see:</p>
<ul>
<li>The <strong>available AI tokens</strong>.</li>
<li>The <strong>publications to validate</strong>, with the link <strong>“See the approvals”</strong>.</li>
<li>Two donut charts: <strong>“Total of general users”</strong> and <strong>“Total Organizations”</strong>. Each one splits the total by country and
  shows the number for each part.</li>
</ul>
<p>This screen only shows figures: it has no forms.</p>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['admin-about-framework'])): ?>
<h2 class="ui header" id="about">About</h2>
<p>Platform data: the installed version, its date, a description, the own libraries, and the third-party plugins and
dependencies with their license.</p>
<p><img class="ui image" alt="About" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/admin-about-framework.webp')); ?>"></p>
<p>How to get there: your user menu, at the top right › <strong>“About”</strong>.</p>
<p>What you see:</p>
<ul>
<li>The <strong>installed version</strong>, the <strong>date</strong> and the <strong>commit</strong> of that version.</li>
<li>The <strong>“GitHub”</strong> button, which opens the PiecesPHP repository, the framework the platform is built on.</li>
<li>The sections <strong>“Description”</strong>, <strong>“Own libraries”</strong> and <strong>“Plugins, scripts, and dependencies”</strong>.</li>
</ul>
<?php endif; ?>
