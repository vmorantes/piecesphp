<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/en/60-ubicaciones.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>Here you maintain the places the rest of the panel uses: countries, departments, cities and coordinates.</p>
<div class="ui styled fluid accordion" data-section-accordion>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['locations'])): ?>
<h2 class="active title" id="how-to-get-there"><span role="button" tabindex="0" aria-expanded="true" aria-controls="how-to-get-there-contenido"><i class="dropdown icon" aria-hidden="true"></i>How to get there</span></h2>
<div class="active content" id="how-to-get-there-contenido">
<p>From the side menu: <strong>“Locations”</strong>. The section's start screen offers a list of countries, of departments, of cities and of coordinates.</p>
<p><img class="ui image" alt="How to get there" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/locations.webp')); ?>"></p>
<p>Every list has the same shape: a table with an <strong>“Edit”</strong> button per row, a main button to add and <strong>“Export current list”</strong>. In the forms, <strong>“Cancel”</strong> goes back to the list and <strong>“Save”</strong> saves.</p>
</div>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['locations-countries-list'])): ?>
<h2 class="title" id="countries"><span role="button" tabindex="0" aria-expanded="false" aria-controls="countries-contenido"><i class="dropdown icon" aria-hidden="true"></i>Countries</span></h2>
<div class="content" id="countries-contenido">
<p>Columns: ID, code, name, region, active or inactive, and actions. To create one, click <strong>“Add country”</strong>.</p>
<p><img class="ui image" alt="Countries" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/locations-countries-list.webp')); ?>"></p>
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
<td class="left aligned">Name</td>
<td class="left aligned">Yes</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Code</td>
<td class="left aligned">No</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Phone code</td>
<td class="left aligned">No</td>
<td class="left aligned">For example, +57</td>
</tr>
<tr>
<td class="left aligned">Demonym</td>
<td class="left aligned">No</td>
<td class="left aligned">Offered as the nationality in profiles. For example, Colombian</td>
</tr>
<tr>
<td class="left aligned">Region</td>
<td class="left aligned">No</td>
<td class="left aligned">You can type the region and press ENTER if you don't find it in the list</td>
</tr>
<tr>
<td class="left aligned">Active/Inactive</td>
<td class="left aligned">Yes</td>
<td class="left aligned"></td>
</tr>
</tbody>
</table>
</div>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['locations-states-list'])): ?>
<h2 class="title" id="departments"><span role="button" tabindex="0" aria-expanded="false" aria-controls="departments-contenido"><i class="dropdown icon" aria-hidden="true"></i>Departments</span></h2>
<div class="content" id="departments-contenido">
<p>Columns: ID, code, name, country, active or inactive, and actions. To create one, click <strong>“Add department”</strong>.</p>
<p><img class="ui image" alt="Departments" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/locations-states-list.webp')); ?>"></p>
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
<td class="left aligned">Country</td>
<td class="left aligned">Yes</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Name</td>
<td class="left aligned">Yes</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Code</td>
<td class="left aligned">No</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Active/Inactive</td>
<td class="left aligned">Yes</td>
<td class="left aligned"></td>
</tr>
</tbody>
</table>
</div>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['locations-cities-list'])): ?>
<h2 class="title" id="cities"><span role="button" tabindex="0" aria-expanded="false" aria-controls="cities-contenido"><i class="dropdown icon" aria-hidden="true"></i>Cities</span></h2>
<div class="content" id="cities-contenido">
<p>Columns: ID, code, name, country, department, active or inactive, and actions. To create one, click <strong>“Add city”</strong>.</p>
<p><img class="ui image" alt="Cities" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/locations-cities-list.webp')); ?>"></p>
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
<td class="left aligned">Country</td>
<td class="left aligned">Yes</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Department</td>
<td class="left aligned">Yes</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Name</td>
<td class="left aligned">Yes</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Code</td>
<td class="left aligned">No</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Active/Inactive</td>
<td class="left aligned">Yes</td>
<td class="left aligned"></td>
</tr>
</tbody>
</table>
</div>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['locations-points-list'])): ?>
<h2 class="title" id="coordinates"><span role="button" tabindex="0" aria-expanded="false" aria-controls="coordinates-contenido"><i class="dropdown icon" aria-hidden="true"></i>Coordinates</span></h2>
<div class="content" id="coordinates-contenido">
<p>Columns: ID, name, country, department, city, address, coordinates, active or inactive, and actions. To create one, click <strong>“Add coordinate”</strong>.</p>
<p><img class="ui image" alt="Coordinates" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/locations-points-list.webp')); ?>"></p>
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
<td class="left aligned">Country</td>
<td class="left aligned">Yes</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Department</td>
<td class="left aligned">Yes</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">City</td>
<td class="left aligned">Yes</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Address</td>
<td class="left aligned">Yes</td>
<td class="left aligned">Locality, hamlet, neighborhood, etc.</td>
</tr>
<tr>
<td class="left aligned">Name</td>
<td class="left aligned">Yes</td>
<td class="left aligned"></td>
</tr>
<tr>
<td class="left aligned">Active/Inactive</td>
<td class="left aligned">Yes</td>
<td class="left aligned"></td>
</tr>
</tbody>
</table>
<p>This form includes a map. With the map buttons you can switch between <strong>“Satellite view”</strong> and <strong>“Drawing view”</strong>, <strong>“Center”</strong> the map and zoom it in or out.</p>
</div>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['locations-countries-forms-add', 'locations-countries-forms-edit', 'locations-states-forms-add', 'locations-states-forms-edit', 'locations-cities-forms-add', 'locations-cities-forms-edit', 'locations-points-forms-add', 'locations-points-forms-edit'])): ?>
<h2 class="title" id="add-and-edit"><span role="button" tabindex="0" aria-expanded="false" aria-controls="add-and-edit-contenido"><i class="dropdown icon" aria-hidden="true"></i>Add and edit</span></h2>
<div class="content" id="add-and-edit-contenido">
<p>For each type of location, the creation form and the edit form have the same fields. Only the title changes (“Add…” or “Edit…”).</p>
<p><img class="ui image" alt="Add and edit" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/locations-countries-forms-add.webp')); ?>"></p>
</div>
<?php endif; ?>
</div>
