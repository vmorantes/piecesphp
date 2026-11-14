<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/en/84-configuracion-sistema.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>Security, backup and permission options. To get there, use the gear button in the top bar,
<strong>“System settings” › “System”</strong>, and then the screen by its name.</p>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['configurations-system-security'])): ?>
<h2 class="ui header" id="security">Security</h2>
<p>Go to <strong>“Security”</strong>. It gathers the platform's security options. At the end, <strong>“Cancel”</strong> or
<strong>“Save”</strong>.</p>
<p><img class="ui image" alt="Security" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/configurations-system-security.webp')); ?>"></p>
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
<td>Use user IP to encrypt session token</td>
<td>No</td>
<td>The session is tied to the IP address it was started from. If the user's IP changes (for example, when switching from wifi to mobile data), they will have to sign in again.</td>
</tr>
<tr>
<td>Hide the placeholder app_key warning in the panel</td>
<td>No</td>
<td>The warning appears when the key that encrypts the data (app_key) is still the sample one that comes with the installation. Hide it only if you know why it is kept that way.</td>
</tr>
<tr>
<td>Days without activity that close the session</td>
<td>Yes</td>
<td>How many days without activity close the session. With 0 it does not expire from inactivity.</td>
</tr>
<tr>
<td>Days since it was opened that close it (cap)</td>
<td>Yes</td>
<td>How many days, counted from when it was opened, close the session. With 0 there is no cap.</td>
</tr>
</tbody>
</table>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['configurations-system-backups'])): ?>
<h2 class="ui header" id="backups">Backups</h2>
<p>Go to <strong>“Backups”</strong>. It sets how often the database is backed up, how many backups are kept and which tables
are saved without their rows. At the end, <strong>“Cancel”</strong> or <strong>“Save”</strong>.</p>
<p><img class="ui image" alt="Backups" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/configurations-system-backups.webp')); ?>"></p>
<p>With the saved policy, the screen calculates how many backups would be kept and how many would be deleted. Files
the system did not write are never touched.</p>
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
<td>Back up the database automatically</td>
<td>No</td>
<td>When off, no new backup is made and none is deleted either.</td>
</tr>
<tr>
<td>How many minutes apart</td>
<td>Yes</td>
<td>The backup is made when the latest one reaches this age; 1440 is one day (from 60 to 10080).</td>
</tr>
<tr>
<td>Keep the latest</td>
<td>Yes</td>
<td>The most recent ones, always (from 1 to 1000).</td>
</tr>
<tr>
<td>Delete the backups that are left over</td>
<td>No</td>
<td>Deletion only happens after a successful backup, and only for what the system wrote. When off, all are kept.</td>
</tr>
<tr>
<td>One backup a day, for</td>
<td>Yes</td>
<td>Days with a backup that are kept; 0 turns this level off.</td>
</tr>
<tr>
<td>One a week, for</td>
<td>Yes</td>
<td>Weeks with a backup that are kept; 0 turns this level off.</td>
</tr>
<tr>
<td>One a month, for</td>
<td>Yes</td>
<td>Months with a backup that are kept; 0 turns this level off.</td>
</tr>
<tr>
<td>Tables exported without their rows</td>
<td>No</td>
<td>One checkbox per table.</td>
</tr>
</tbody>
</table>
<div class="ui warning message">
<div class="header">Tables without their rows</div>
<p>The table is saved with its structure, but empty: when restoring, its data does NOT come back. It is useful to keep
sensitive data from leaving the server.</p>
</div>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['configurations-system-routes'])): ?>
<h2 class="ui header" id="routes-and-permissions">Routes and permissions</h2>
<p>Go to <strong>“Routes and permissions”</strong>. It is a reference table: which routes exist and which user types can use each
one. It has the columns <strong>Name</strong>, <strong>Route</strong>, <strong>Controller</strong> and <strong>Roles with access</strong>.</p>
<p><img class="ui image" alt="Routes and permissions" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/configurations-system-routes.webp')); ?>"></p>
<?php endif; ?>
