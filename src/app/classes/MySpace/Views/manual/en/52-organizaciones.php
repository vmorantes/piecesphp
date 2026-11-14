<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/en/52-organizaciones.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>Here you manage the registered organizations and what is shown about each one.</p>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['organizations-admin-list'])): ?>
<h2 class="ui header" id="the-organizations-list">The organizations list</h2>
<p>You get there from the side menu: <strong>“Management of organizations”</strong>.</p>
<p><img class="ui image" alt="The organizations list" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/organizations-admin-list.webp')); ?>"></p>
<p>The list has three tabs: <strong>Active</strong>, <strong>Inactive</strong> and <strong>Pending approval</strong>. The table shows code, NIT, organization name, location (country or department and city) and actions.</p>
<p>Each row offers these actions:</p>
<ul>
<li><strong>“Edit”</strong>: opens the organization's account data.</li>
<li><strong>“Public profile”</strong>: opens what others see about the organization, so you can edit it.</li>
<li><strong>“View profile”</strong>: opens the organization's profile page.</li>
<li><strong>“Delete”</strong>.</li>
</ul>
<p>At the top you have <strong>“Add organization”</strong> and <strong>“Export current list”</strong>.</p>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['organizations-admin-forms-edit', 'organizations-admin-forms-add'])): ?>
<h2 class="ui header" id="add-and-edit-an-organization">Add and edit an organization</h2>
<p>The two forms are almost the same. Editing adds the <strong>“Public profile”</strong> button, the <strong>“Delete”</strong> button and the “Code” and “Organization contact” fields.</p>
<p><img class="ui image" alt="Add and edit an organization" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/organizations-admin-forms-edit.webp')); ?>"></p>
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
<td class="left aligned">Name of the organization</td>
<td class="left aligned">Yes</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Activity sector</td>
<td class="left aligned">Yes</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Size of the organization</td>
<td class="left aligned">No</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Lines of action</td>
<td class="left aligned">No</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Country</td>
<td class="left aligned">Yes</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">City</td>
<td class="left aligned">Yes</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Organization email</td>
<td class="left aligned">No</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">LinkedIn link</td>
<td class="left aligned">No</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Website link</td>
<td class="left aligned">No</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Phone</td>
<td class="left aligned">No</td>
<td class="left aligned">The country dialing code and the number.</td>
</tr>
<tr>
<td class="left aligned">NIT</td>
<td class="left aligned">Yes</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">ESAL authorized by DIAN</td>
<td class="left aligned">No</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Address</td>
<td class="left aligned">Yes</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Billing email</td>
<td class="left aligned">Yes</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Organization contact (only when editing)</td>
<td class="left aligned">Yes</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">RUT</td>
<td class="left aligned">Yes when adding; when editing, no</td>
<td class="left aligned">A file, with <strong>“Attach”</strong>. Maximum size: 2 MB</td>
</tr>
<tr>
<td class="left aligned">Status</td>
<td class="left aligned">Yes</td>
<td class="left aligned"></td>
</tr>
</tbody>
</table>
<p>You can also <strong>“Change image”</strong> to set the organization's image. Finish with <strong>“Save”</strong>.</p>
<?php endif; ?>
