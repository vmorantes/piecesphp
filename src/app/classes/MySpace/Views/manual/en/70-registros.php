<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/en/70-registros.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>Here you check what the system records: what happened, who did it and when. You only see the parts your account type
is allowed to see.</p>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['logs-admin-index'])): ?>
<h2 class="ui header" id="logs">Logs</h2>
<p>To get there, use the gear button in the top bar, <strong>“System settings” › “Records and logs”</strong>,
and then the log entry you are looking for: <strong>“Errors”</strong>, <strong>“Login attempts”</strong>, <strong>“Login record”</strong>,
<strong>“Users without login”</strong>, <strong>“Sent mail”</strong>, <strong>“Tokens and codes”</strong>, <strong>“Scheduled tasks”</strong> or
<strong>“All logs”</strong>. <strong>“Open sessions”</strong> is under <strong>“User management”</strong>.</p>
<p><img class="ui image" alt="Logs" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/logs-admin-index.webp')); ?>"></p>
<p>The screen is called <strong>Logs</strong> and has a row of tabs: <strong>Errors</strong>, <strong>Sent mail</strong>, <strong>Access</strong>,
<strong>Open sessions</strong>, <strong>Tokens and codes</strong> and <strong>Scheduled tasks</strong>. Each tab shows one type of record, and
starts with its title and a sentence saying what you see in it.</p>
<h3 class="ui header">The Errors tab</h3>
<p>Errors are grouped by day, newest first, with the day as the heading. Each entry shows the
time, its severity, a reference, the error type, the message and the file where it happened.</p>
<ul>
<li>The button next to the reference, <strong>“Copy reference”</strong>, copies it so you can paste it wherever you need; for a
  moment it says <strong>“Copied”</strong>.</li>
<li><strong>“Detail”</strong> expands everything that was recorded about the error: the request, the extra data, the trace and the full
  message with its path. If you search for an error by its reference, the detail opens by itself.</li>
<li>If no error has been recorded, the tab says so; if there are errors but none match the filters, it also says so.</li>
</ul>
<p>You can <strong>“Filter”</strong> with these fields and <strong>“Clear”</strong> to see everything again:</p>
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
<td>Reference</td>
<td>No</td>
<td>Find an error by its reference.</td>
</tr>
<tr>
<td>Text (message or file)</td>
<td>No</td>
<td>Search by part of the message or the file name.</td>
</tr>
<tr>
<td>Exception type</td>
<td>No</td>
<td>Keep only one type of error.</td>
</tr>
<tr>
<td>Level</td>
<td>No</td>
<td>Keep only one severity.</td>
</tr>
<tr>
<td>Day</td>
<td>No</td>
<td>See only one day.</td>
</tr>
<tr>
<td>Other files</td>
<td>No</td>
<td>Switch to another error log.</td>
</tr>
</tbody>
</table>
<p>At the end of the list, <strong>“Older”</strong> loads the next page.</p>
<h3 class="ui header">The Open sessions tab</h3>
<p>The sessions that are still open, the one that started most recently first. You only see those of the accounts you have
authority over, and your own.</p>
<p>You can <strong>“Filter”</strong> with these fields and <strong>“Clear”</strong> to see everything again:</p>
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
<td>User</td>
<td>No</td>
<td>Search by part of the username or email.</td>
</tr>
<tr>
<td>Started since</td>
<td>No</td>
<td>See only the sessions that started on that day or later.</td>
</tr>
</tbody>
</table>
<p>The table shows <strong>User / email</strong>, <strong>Browser</strong>, <strong>IP</strong>, <strong>Start</strong>, <strong>Last activity</strong>, <strong>Expires</strong> and
<strong>Actions</strong>. Next to the user, the <strong>“Current”</strong> tag marks the session you are looking from, and <strong>“Other
sessions”</strong>, your other sessions. In each row:</p>
<ul>
<li>the red button says <strong>“Sign out”</strong> if the session is yours and <strong>“Force sign-out”</strong> if it belongs to someone else; both close
  that session, after you confirm;</li>
<li><strong>“Extend”</strong> (the calendar button) lengthens its expiry. It only appears if your user type can do it on
  that account.</li>
</ul>
<h3 class="ui header">The Tokens and codes tab</h3>
<p>Two tables, each in its own block:</p>
<ul>
<li><strong>Current</strong>: the access codes, links and secrets that still work, with <strong>Type</strong>, <strong>User</strong>, <strong>Created</strong>,
  <strong>Expires</strong> and <strong>Actions</strong>. There are always three actions, in the same order; the one that does not apply to that row appears
  dimmed. <strong>“Reset two-factor”</strong> (the person will log in with their password only until they turn it on again) and
  <strong>“Invalidate now”</strong> (the code stops working) are red; <strong>“Show”</strong>
  reveals the value of a code and is recorded. Each one asks you to confirm.</li>
<li><strong>History</strong>: what happened to them, with <strong>Date</strong>, <strong>Event</strong>, <strong>User</strong> and <strong>Who</strong>.</li>
</ul>
<h3 class="ui header">The Access tab</h3>
<p>It has three views: <strong>Login attempts</strong>, <strong>Login record</strong> and <strong>Users without login</strong>. Only users you have authority
over count.</p>
<p>In <strong>Login attempts</strong>:</p>
<ul>
<li>At the top you choose the <strong>period</strong>: <strong>7</strong>, <strong>14</strong> (the default), <strong>30</strong> or <strong>90 days</strong>, ending today. Next to it, the dates
  of the period and <strong>“Export”</strong>, which downloads the attempts for the period and the filter you have selected.</li>
<li>Four figures for the period: <strong>Total attempts</strong>, with how much it changed compared with the previous period of the same length;
  <strong>Successful</strong> and <strong>Failed</strong>, with their percentage; and <strong>Unique users</strong>, the different users who tried to log in.</li>
<li>The <strong>Attempts per day</strong> chart: failed attempts at the bottom and successful ones on top, one point per day, including days with no
  attempts.</li>
<li>Above the table, <strong>All</strong>, <strong>Successful</strong> and <strong>Failed</strong>, each with its figure, filter the table without reloading the page.</li>
<li>The table shows <strong>Indicator</strong>, <strong>User Login</strong>, <strong>Information</strong>, <strong>Channel</strong>, <strong>IP</strong> and <strong>Date</strong>. Failed
  attempts appear in red; a correct login says <strong>“Successful login”</strong>. The date says how long ago it was (“3 hours ago”);
  the exact date appears when you hover the mouse over it.</li>
</ul>
<p><strong>Login record</strong> and <strong>Users without login</strong> have their own period (7, 14, 30 or 90 days) and their own <strong>“Export”</strong>:</p>
<ul>
<li><strong>Login record</strong>: how many users logged in during the period and how many did not, with their percentage of the total, and
  <strong>Total hours</strong>, the time on the platform since records began (it is not stored per day, so it does not change with the
  period). The table lists whoever logged in during the period: <strong>ID</strong>, <strong>User</strong>, <strong>Name</strong>, <strong>Last access</strong> (the most
  recent first) and <strong>Time on platform</strong> (“No record” if there is none).</li>
<li><strong>Users without login</strong>: how many did not log in during the period and how many did, with their percentage, and the table of those who did not
  log in: <strong>ID</strong>, <strong>User</strong> and <strong>Name</strong>. If everyone logged in, the screen says so.</li>
</ul>
<h3 class="ui header">The other tabs</h3>
<p>The <strong>Scheduled tasks</strong> tab exists on the screen, but this manual does not describe its content yet.</p>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['system-status-mail-log'])): ?>
<h2 class="ui header" id="sent-mail">Sent mail</h2>
<p>This is the <strong>Sent mail</strong> tab of <strong>Logs</strong>. It lists the emails the platform sent or tried to send. The
body is stored encrypted; attachments are not stored.</p>
<p><img class="ui image" alt="Sent mail" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/system-status-mail-log.webp')); ?>"></p>
<p>At the top, the summary of the last 24 hours: how many were delivered, how many ended up in the test outbox and how many
did not arrive (in red if there are any).</p>
<table class="ui celled table">
<thead>
<tr>
<th>Column</th>
<th>What it shows</th>
</tr>
</thead>
<tbody>
<tr>
<td>Date</td>
<td>When it was sent.</td>
</tr>
<tr>
<td>Recipients</td>
<td>Who it was addressed to.</td>
</tr>
<tr>
<td>Subject</td>
<td>The subject of the email.</td>
</tr>
<tr>
<td>Origin</td>
<td>Which part of the system sent it.</td>
</tr>
<tr>
<td>Delivery</td>
<td>How it was delivered.</td>
</tr>
<tr>
<td>Result</td>
<td>Whether it went well or failed.</td>
</tr>
<tr>
<td>Body</td>
<td><strong>“View body”</strong> opens the email in a window; <strong>“Close”</strong> or the Esc key closes it.</td>
</tr>
</tbody>
</table>
<p>With <strong>“Filter”</strong> and <strong>“Clear”</strong> you narrow the list; <strong>“Export current list”</strong> downloads what you are viewing.</p>
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
<td>From</td>
<td>No</td>
<td>First date of the range.</td>
</tr>
<tr>
<td>To</td>
<td>No</td>
<td>Last date of the range.</td>
</tr>
<tr>
<td>Result</td>
<td>No</td>
<td>Keep only those that went well or only those that failed.</td>
</tr>
<tr>
<td>Delivery</td>
<td>No</td>
<td>Keep only one way of delivery.</td>
</tr>
<tr>
<td>Origin</td>
<td>No</td>
<td>Search by the part of the system that sent it.</td>
</tr>
</tbody>
</table>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['actions-logs-admin-list'])): ?>
<h2 class="ui header" id="activity-log">Activity log</h2>
<p>To get there, use <strong>“Latest movements”</strong> in the side menu. It answers “who did what in the panel, and when”.</p>
<p><img class="ui image" alt="Activity log" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/actions-logs-admin-list.webp')); ?>"></p>
<p>The table shows: <strong>#</strong>, <strong>Module</strong>, <strong>Event</strong>, <strong>User</strong>, <strong>IP</strong>, <strong>Date</strong> and <strong>Actions</strong>. In each row,
<strong>“View detail”</strong> opens the detail of that movement.</p>
<p>You can <strong>“Filter”</strong> and <strong>“Clear”</strong>, and <strong>“Export current list”</strong> downloads what you are viewing.</p>
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
<td>From</td>
<td>No</td>
<td>First date of the range.</td>
</tr>
<tr>
<td>To</td>
<td>No</td>
<td>Last date of the range.</td>
</tr>
<tr>
<td>Type</td>
<td>No</td>
<td>Keep only one type of movement.</td>
</tr>
<tr>
<td>User</td>
<td>No</td>
<td>See one person's movements.</td>
</tr>
</tbody>
</table>
<?php endif; ?>
