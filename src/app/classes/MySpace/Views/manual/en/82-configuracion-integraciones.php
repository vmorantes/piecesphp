<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/en/82-configuracion-integraciones.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>Here you connect the platform with outside services. To get there, use the gear button in the top bar, <strong>“System settings”</strong>: Artificial
intelligence, Mail, OsTicket and Maps keys are in the <strong>“System”</strong> group, and Scripts is in <strong>“SEO and
scripts”</strong>. All of them end with <strong>“Cancel”</strong> and <strong>“Save”</strong>.</p>
<div class="ui styled fluid accordion" data-section-accordion>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['configurations-integrations-ai'])): ?>
<h2 class="active title" id="artificial-intelligence"><span role="button" tabindex="0" aria-expanded="true" aria-controls="artificial-intelligence-contenido"><i class="dropdown icon" aria-hidden="true"></i>Artificial intelligence</span></h2>
<div class="active content" id="artificial-intelligence-contenido">
<p>Go to <strong>“Artificial intelligence”</strong>. It is the control center for all of the platform's artificial intelligence: here you
turn it on or off, choose the providers and their keys, and decide, feature by feature, who uses it and how much it can
spend.</p>
<p><img class="ui image" alt="Artificial intelligence" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/configurations-integrations-ai.webp')); ?>"></p>
<p><strong>Artificial intelligence</strong></p>
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
<td>AI enabled</td>
<td>No</td>
<td>The main switch. When off, no artificial intelligence feature calls a provider.</td>
</tr>
</tbody>
</table>
<p><strong>Providers and keys</strong></p>
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
<td>OpenAI Model</td>
<td>No</td>
<td>The OpenAI model that is used.</td>
</tr>
<tr>
<td>OpenAI API Key</td>
<td>No</td>
<td>The access key for OpenAI. If the keys file defines it, the field cannot be changed and says so.</td>
</tr>
<tr>
<td>Mistral Model</td>
<td>No</td>
<td>The Mistral model that is used.</td>
</tr>
<tr>
<td>Mistral API Key</td>
<td>No</td>
<td>The access key for Mistral, with the same rule.</td>
</tr>
</tbody>
</table>
<p><strong>Features</strong>: one section for each artificial intelligence feature the platform has (for example, “Translation with
AI”), with these fields:</p>
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
<td>Enabled</td>
<td>No</td>
<td>Turns that feature on or off.</td>
</tr>
<tr>
<td>Who uses it</td>
<td>No</td>
<td>Which user types can use it. The main user (Root) always can.</td>
</tr>
<tr>
<td>AI for translations</td>
<td>No</td>
<td>Only in “Translation with AI”: which provider translates.</td>
</tr>
<tr>
<td>Quota (tokens)</td>
<td>No</td>
<td>How much the feature can spend in each period. <strong>0 = no limit.</strong> When the quota is reached, the feature stops calling the provider and says so, until the next period or until you reset the counter.</td>
</tr>
<tr>
<td>Period</td>
<td>No</td>
<td>How often the quota starts counting again: <strong>Cumulative</strong> (it never does), <strong>Monthly</strong>, <strong>Every N days</strong> or <strong>Every N months</strong>. With the last two, the <strong>“N”</strong> field appears.</td>
</tr>
</tbody>
</table>
<p>Below, the <strong>“Usage this period”</strong> line shows what has been spent (and the quota, if any) and since when. The <strong>“Reset the
counter”</strong> button asks for confirmation and makes the quota count from zero again; what was spent until then is kept for
the reports.</p>
<p><strong>Token quota</strong>, the form at the bottom, with its own <strong>“Save”</strong>: the <strong>“AI tokens limit”</strong> (required) is only used by the
usage report and <strong>does not limit anything</strong>. The quota that limits is the one of each feature.</p>
</div>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['configurations-integrations-mail'])): ?>
<h2 class="title" id="mail"><span role="button" tabindex="0" aria-expanded="false" aria-controls="mail-contenido"><i class="dropdown icon" aria-hidden="true"></i>Mail</span></h2>
<div class="content" id="mail-contenido">
<p>Go to <strong>“Mail”</strong>. It sets where the platform's email comes from and how it is sent. It has two tabs:
<strong>Settings</strong> and <strong>Tests</strong>. <strong>“Show password”</strong> lets you see the password you type.</p>
<p><img class="ui image" alt="Mail" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/configurations-integrations-mail.webp')); ?>"></p>
<div class="ui warning message">
<div class="header">What decides whether mail goes out</div>
<p>What decides whether mail goes out is “Mail delivery”. When held, none goes out to the outside: everything goes to the
test server.</p>
</div>
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
<td>Host</td>
<td>Yes</td>
<td>The mail server.</td>
</tr>
<tr>
<td>Protocol</td>
<td>Yes</td>
<td>The connection encryption, chosen from a list: <strong>STARTTLS</strong> (the usual one with port 587), <strong>SSL/TLS</strong> (port 465) or <strong>None</strong>. If you see “(current value, not valid)”, choose one of the three before saving.</td>
</tr>
<tr>
<td>Automatic TLS</td>
<td>No</td>
<td>Checkbox to turn on automatic encryption.</td>
</tr>
<tr>
<td>Port</td>
<td>Yes</td>
<td>The server's port.</td>
</tr>
<tr>
<td>Email</td>
<td>No</td>
<td>The account used to send.</td>
</tr>
<tr>
<td>Password</td>
<td>No</td>
<td>That account's password.</td>
</tr>
<tr>
<td>Authenticate</td>
<td>No</td>
<td>Checkbox to indicate the server requires authentication.</td>
</tr>
<tr>
<td>Mail delivery</td>
<td>Yes</td>
<td>Decides whether mail goes out to the outside or is held.</td>
</tr>
<tr>
<td>Test server</td>
<td>Yes</td>
<td>Where emails go when they are held.</td>
</tr>
<tr>
<td>Test port</td>
<td>Yes</td>
<td>The test server's port.</td>
</tr>
<tr>
<td>Sender's name</td>
<td>No</td>
<td>The name the emails are sent under.</td>
</tr>
<tr>
<td>Recipient of the test email</td>
<td>No</td>
<td>Who the test email is sent to.</td>
</tr>
</tbody>
</table>
<p>To see what was sent, look at the <strong>Sent mail</strong> tab of <strong>Logs</strong>.</p>
</div>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['configurations-integrations-osticket'])): ?>
<h2 class="title" id="osticket"><span role="button" tabindex="0" aria-expanded="false" aria-controls="osticket-contenido"><i class="dropdown icon" aria-hidden="true"></i>OsTicket</span></h2>
<div class="content" id="osticket-contenido">
<p>Go to <strong>“OsTicket”</strong>. It stores the connection with the osTicket support system.</p>
<p><img class="ui image" alt="OsTicket" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/configurations-integrations-osticket.webp')); ?>"></p>
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
<td>URL</td>
<td>No</td>
<td>The address of the support system.</td>
</tr>
<tr>
<td>Key</td>
<td>No</td>
<td>The access key for that system.</td>
</tr>
</tbody>
</table>
</div>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['configurations-integrations-scripts'])): ?>
<h2 class="title" id="scripts"><span role="button" tabindex="0" aria-expanded="false" aria-controls="scripts-contenido"><i class="dropdown icon" aria-hidden="true"></i>Scripts</span></h2>
<div class="content" id="scripts-contenido">
<p>Go to <strong>“Scripts”</strong>. Here you insert your own code into the public site, the panel or both: analytics, ad
pixels, chats and the like. <strong>“Add script”</strong> adds a new one to the list.</p>
<p><img class="ui image" alt="Scripts" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/configurations-integrations-scripts.webp')); ?>"></p>
<div class="ui warning message">
<div class="header">Paste only code you trust</div>
<p>What you write here runs as is in the browser of every visitor or user. A mistake can break the
page or expose data; try it first with the script turned off.</p>
</div>
</div>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['helpers-system-generic-content-admin-forms-mapbox-keys'])): ?>
<h2 class="title" id="maps-keys"><span role="button" tabindex="0" aria-expanded="false" aria-controls="maps-keys-contenido"><i class="dropdown icon" aria-hidden="true"></i>Maps keys</span></h2>
<div class="content" id="maps-keys-contenido">
<p>Go to <strong>“Maps keys”</strong>. It stores the access keys for the platform's map service.</p>
<p><img class="ui image" alt="Maps keys" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/helpers-system-generic-content-admin-forms-mapbox-keys.webp')); ?>"></p>
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
<td>Local key</td>
<td>Yes</td>
<td>The map service key for the local installation.</td>
</tr>
<tr>
<td>Production key</td>
<td>Yes</td>
<td>The map service key for the production site.</td>
</tr>
<tr>
<td>Longitude</td>
<td>Yes</td>
<td>Initial position of the map.</td>
</tr>
<tr>
<td>Latitude</td>
<td>Yes</td>
<td>Initial position of the map.</td>
</tr>
<tr>
<td>Zoom</td>
<td>Yes</td>
<td>Initial zoom level of the map.</td>
</tr>
</tbody>
</table>
</div>
<?php endif; ?>
</div>
