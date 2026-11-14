<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/en/86-estado-del-sistema.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>Here you see how the installation is doing and close the site for maintenance. To get there, use the gear button in the top bar,
<strong>“System settings” › “System”</strong>, and then the screen by its name.</p>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['system-status-alerts'])): ?>
<h2 class="ui header" id="system-alerts">System alerts</h2>
<p>Go to <strong>“System alerts”</strong>. It shows what the system detects and is worth reviewing, and which alerts are shown.</p>
<p><img class="ui image" alt="System alerts" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/system-status-alerts.webp')); ?>"></p>
<p>The table has the columns <strong>Severity</strong>, <strong>Message</strong>, <strong>Status</strong>, <strong>How to fix it</strong> and <strong>Actions</strong>. An alert can
include in its row a button that takes you to where it is fixed, such as <strong>“Mail”</strong>. If there are no active alerts, the screen
says so with <strong>“There are no active alerts”</strong>, without the table.</p>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['system-status-health'])): ?>
<h2 class="ui header" id="installation-health">Installation health</h2>
<p>Go to <strong>“Installation health”</strong>. It is a reference screen: how this installation is doing in terms of versions,
environment, database, tasks and folders. Each group is a table with the columns <strong>Item</strong>, <strong>Status</strong>, <strong>Value</strong> and
<strong>Origin</strong>.</p>
<p><img class="ui image" alt="Installation health" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/system-status-health.webp')); ?>"></p>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['system-status-maintenance'])): ?>
<h2 class="ui header" id="status-and-caches">Status and caches</h2>
<p>Go to <strong>“Status and caches”</strong>. It shows how much space each cache of the installation takes, with its <strong>“Empty”</strong> button: broken shortcuts (those that
point to a file that no longer exists), all shortcuts, optimized images and the saved
publication listings. <strong>“Renew”</strong> changes the version of the static files (styles,
scripts and images of the pages). <strong>“Delete everything temporary”</strong> empties all the caches at once: the
site will be somewhat slower while they are created again.</p>
<p><img class="ui image" alt="Status and caches" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/system-status-maintenance.webp')); ?>"></p>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['system-status-site-maintenance'])): ?>
<h2 class="ui header" id="site-under-maintenance">Site under maintenance</h2>
<p>Go to <strong>“Site under maintenance”</strong>. It is used to close the public site for maintenance and decide who can
still get in. At the end, <strong>“Cancel”</strong> or <strong>“Save”</strong>.</p>
<p><img class="ui image" alt="Site under maintenance" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/system-status-site-maintenance.webp')); ?>"></p>
<table class="ui celled table">
<thead>
<tr>
<th>Field</th>
<th>Required</th>
<th>Purpose</th>
</tr>
</thead>
<tbody>
<tr>
<td>Site under maintenance</td>
<td>No</td>
<td>Turns maintenance on or off. When off, visitors see the site as usual.</td>
</tr>
<tr>
<td>Time to check again (Retry-After)</td>
<td>No</td>
<td>Seconds search engines are advised to wait before checking the site again.</td>
</tr>
<tr>
<td>Roles that can still get in</td>
<td>No</td>
<td>While maintenance lasts, they use the site and the panel as usual. If you choose none, only the main user gets in.</td>
</tr>
</tbody>
</table>
<div class="ui positive message">
<div class="header">You can always get back in</div>
<p>Panel login ALWAYS goes through, and so does the main user: that is the way to get back in and turn it off.</p>
</div>
<?php endif; ?>
