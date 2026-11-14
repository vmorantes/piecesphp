<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/en/50-usuarios.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>Here you see who signs in to the panel, with what type of user and in what status, and you create or edit accounts.</p>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['users-list'])): ?>
<h2 class="ui header" id="the-users-list">The users list</h2>
<p>You get there with the gear button in the top bar: <strong>“System settings” › “User management” › “Users management”</strong>.</p>
<p><img class="ui image" alt="The users list" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/users-list.webp')); ?>"></p>
<p>The list is divided into tabs by account status, and each one shows next to it how many accounts it has. It opens on <strong>Active</strong>:</p>
<ul>
<li><strong>Active</strong></li>
<li><strong>Inactive</strong></li>
<li><strong>All</strong></li>
<li><strong>Blocked by unsuccessful attempts</strong></li>
<li><strong>Pending approval</strong></li>
<li><strong>Not approved</strong></li>
</ul>
<p>The counters only count the accounts you can see: if you manage an organization, those of yours.</p>
<p>Each tab shows the accounts as cards: name, status (active or inactive), type, username, email and organization. At the top you choose how many to see in <strong>“Show … items”</strong> (12, 24 or 48) and search in <strong>“Search:”</strong>, which finds by name (also the full name, with first names and surnames), username, email or organization. At the bottom, the line <strong>“Viewing from … to … of … elements”</strong> and the pagination. The pencil button on each card, <strong>“Edit”</strong>, opens that account.</p>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['users-selection-create', 'users-form-create'])): ?>
<h2 class="ui header" id="add-a-user">Add a user</h2>
<p>Click <strong>“Add user”</strong>. First the system takes you to choose the type of user, among those you can create; once you choose it, the creation form for that type opens.</p>
<p><img class="ui image" alt="Add a user" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/users-selection-create.webp')); ?>"></p>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['users-form-edit', 'users-form-create'])): ?>
<h2 class="ui header" id="add-and-edit">Add and edit</h2>
<p>The creation form and the edit form are almost the same. When editing, the title shows the person's name and type, and three tabs appear: <strong>Account</strong>, <strong>Profile picture</strong> and <strong>Sessions</strong>. When creating there are no tabs: only the form.</p>
<p><img class="ui image" alt="Add and edit" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/users-form-edit.webp')); ?>"></p>
<table class="ui celled table">
<thead>
<tr>
<th class="left aligned">Field</th>
<th class="left aligned">Required</th>
<th class="left aligned">What it is for</th>
</tr>
</thead>
<tbody>
<tr>
<td class="left aligned">Base organization</td>
<td class="left aligned">Yes</td>
<td class="left aligned">The organization the user belongs to</td>
</tr>
<tr>
<td class="left aligned">First name</td>
<td class="left aligned">Yes</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Second name</td>
<td class="left aligned">No</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Surname</td>
<td class="left aligned">Yes</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Second surname</td>
<td class="left aligned">No</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Username</td>
<td class="left aligned">Yes</td>
<td class="left aligned">The name the person signs in with</td>
</tr>
<tr>
<td class="left aligned">E-mail</td>
<td class="left aligned">Yes</td>
<td class="left aligned">The person's email address</td>
</tr>
<tr>
<td class="left aligned">Password (when editing, “New password”)</td>
<td class="left aligned">When creating, yes; when editing, no</td>
<td class="left aligned">The access password. When editing, if you leave it empty it does not change</td>
</tr>
<tr>
<td class="left aligned">Confirm password</td>
<td class="left aligned">Same as the password</td>
<td class="left aligned">The password is repeated to avoid mistakes</td>
</tr>
<tr>
<td class="left aligned">Status</td>
<td class="left aligned">Yes</td>
<td class="left aligned">Whether the account is active or not</td>
</tr>
</tbody>
</table>
<p>Next to each password field there is a <strong>“Show password”</strong> button to see what you type. When you are done, click <strong>“Save”</strong>.</p>
<h3 class="ui header">Profile picture</h3>
<p>If the account does not have a picture yet, the card says <strong>“No photo”</strong>. With <strong>“Change image”</strong> you choose one from your computer.</p>
<h3 class="ui header">Sessions</h3>
<p>The <strong>Sessions</strong> tab only appears if you can close that account's sessions. At the top is <strong>“Close all their sessions”</strong>: it signs the person out of all their devices at once, and they will have to sign in again on each one. It asks you to confirm.</p>
<p>Below, the table of their open sessions, with pagination and a search by browser or IP: <strong>Browser</strong>, <strong>IP</strong>, <strong>Start</strong>, <strong>Last activity</strong>, <strong>Expires</strong> and <strong>Actions</strong>. In each row:</p>
<ul>
<li>the red button, <strong>“Force sign-out”</strong>, closes that session once you confirm it; if the session is yours, the button says <strong>“Sign out”</strong>;</li>
<li><strong>“Extend”</strong> (the calendar button, next to the red one) extends its expiry. It only appears if your user type can
  do so on that account.</li>
</ul>
<p>It is the same table as <strong>Logs › Open sessions</strong>, filtered by this person.</p>
<?php endif; ?>
