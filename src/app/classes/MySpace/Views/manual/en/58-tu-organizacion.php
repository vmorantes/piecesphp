<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/en/58-tu-organizacion.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>An organization's public profile is what others see about it in the Directory and on the map.</p>
<h2 class="ui header" id="the-public-profile">The public profile</h2>
<p>You open it with <strong>“Public profile”</strong>, from the list or the edit screen of an organization; with <strong>“My organization”</strong>, in the menu under your name, if your account has it; or with <strong>“Edit”</strong>, on the profile page of the organization in the Directory. At the top, it says that this is what others see about the organization in the Directory and on the map. It has two tabs: <strong>General data</strong> and <strong>Professional data</strong>. If the organization has no person in charge, the profile cannot be changed: choose one with <strong>“Change”</strong>.</p>
<p><img class="ui image" alt="The public profile" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/my-organization-profile-admin-my-organization-profile.webp')); ?>"></p>
<p>From here you can:</p>
<ul>
<li><strong>“Account”</strong>: go back to the organization's account data.</li>
<li><strong>“View profile”</strong>: opens the organization's profile page.</li>
<li><strong>“Change image”</strong>.</li>
<li><strong>“Save”</strong> or <strong>“Cancel”</strong>.</li>
</ul>
<p>Fields of the <strong>General data</strong> tab:</p>
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
<td class="left aligned">Activity sector - Es</td>
<td class="left aligned">Yes</td>
<td class="left aligned">The sector, in Spanish</td>
</tr>
<tr>
<td class="left aligned">Activity sector - En</td>
<td class="left aligned">Yes</td>
<td class="left aligned">The sector, in English</td>
</tr>
<tr>
<td class="left aligned">Email</td>
<td class="left aligned">Yes</td>
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
<td class="left aligned">Phone</td>
<td class="left aligned">No</td>
<td class="left aligned"></td>
</tr>
</tbody>
</table>
<p>At the end, mark the location on the map.</p>
<p>Fields of the <strong>Professional data</strong> tab:</p>
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
<td class="left aligned">Institutions you currently belong to</td>
<td class="left aligned">No</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Show the organization profile in the directory and on the map</td>
<td class="left aligned">No</td>
<td class="left aligned">A checkbox to decide whether the profile is shown</td>
</tr>
</tbody>
</table>
