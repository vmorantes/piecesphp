<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/en/44-suscriptores.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>The list of people who have subscribed.</p>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['newsletter-admin-list'])): ?>
<h2 class="ui header" id="the-subscribers-list">The subscribers list</h2>
<p>How to get there: <strong>“Subscribers”</strong>, in the side menu.</p>
<p><img class="ui image" alt="The subscribers list" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/newsletter-admin-list.webp')); ?>"></p>
<p>The table shows: <strong>#</strong>, <strong>Name</strong>, <strong>Email</strong>, <strong>Accepts receiving emails</strong> and <strong>Actions</strong>. In each row you can <strong>“Edit”</strong> or <strong>“Delete”</strong>.</p>
<p>At the top are <strong>“Add subscriber”</strong> and <strong>“Export current list”</strong>.</p>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['newsletter-admin-forms-add', 'newsletter-admin-forms-edit'])): ?>
<h2 class="ui header" id="add-and-edit-a-subscriber">Add and edit a subscriber</h2>
<p>How to get there: click <strong>“Add subscriber”</strong>, or <strong>“Edit”</strong> in a subscriber's row.</p>
<p><img class="ui image" alt="Add and edit a subscriber" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/newsletter-admin-forms-add.webp')); ?>"></p>
<table class="ui celled table">
<thead>
<tr>
<th>Field</th>
<th>Required</th>
<th>What it is for</th>
</tr>
</thead>
<tbody>
<tr>
<td>Name</td>
<td>Yes</td>
<td>The subscriber's name.</td>
</tr>
<tr>
<td>Email</td>
<td>Yes</td>
<td>Their email address.</td>
</tr>
<tr>
<td>Accepts receiving emails</td>
<td>No</td>
<td>Tick it if the person agrees to receive emails.</td>
</tr>
</tbody>
</table>
<p>Buttons: <strong>“Save”</strong> and, when editing, <strong>“Delete”</strong>.</p>
<?php endif; ?>
