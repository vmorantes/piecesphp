<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/en/34-banners-e-imagen-principal.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>Here you manage the site's banners and main image.</p>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['built-in-banner-admin-list'])): ?>
<h2 class="ui header" id="the-banners-list">The banners list</h2>
<p>How to get there: <strong>“Banners”</strong>, in the side menu.</p>
<p><img class="ui image" alt="The banners list" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/built-in-banner-admin-list.webp')); ?>"></p>
<p>One tab, <strong>“All”</strong>. The table shows: <strong>#</strong>, <strong>Title</strong>, <strong>Position</strong>, <strong>Image</strong>, <strong>Mobile image</strong>, <strong>Active date</strong> and <strong>Actions</strong>.</p>
<p>In each row you can:</p>
<ul>
<li><strong>“View the desktop image”</strong>.</li>
<li><strong>“Edit”</strong> the banner.</li>
<li><strong>“Delete”</strong> the banner.</li>
</ul>
<p>At the top are <strong>“Add banner”</strong> and <strong>“Export current list”</strong>.</p>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['built-in-banner-admin-forms-add', 'built-in-banner-admin-forms-edit'])): ?>
<h2 class="ui header" id="add-and-edit-a-banner">Add and edit a banner</h2>
<p>How to get there: click <strong>“Add banner”</strong>, or <strong>“Edit”</strong> in a banner's row.</p>
<p><img class="ui image" alt="Add and edit a banner" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/built-in-banner-admin-forms-add.webp')); ?>"></p>
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
<td>Position</td>
<td>No</td>
<td>The order of the banner.</td>
</tr>
<tr>
<td>Link</td>
<td>No</td>
<td>Where the banner leads.</td>
</tr>
<tr>
<td>Start</td>
<td>No</td>
<td>From when it is active.</td>
</tr>
<tr>
<td>End</td>
<td>No</td>
<td>Until when it is active.</td>
</tr>
<tr>
<td>Name</td>
<td>No</td>
<td>The name of the banner.</td>
</tr>
<tr>
<td>Content</td>
<td>No</td>
<td>The banner's text, with an editor that has bold, italics, underline, links and colors.</td>
</tr>
</tbody>
</table>
<p>There are two images, each with its own <strong>“Change image”</strong>: <strong>Image</strong> (required when creating), the desktop one, and <strong>Phone image</strong>, the one for mobile devices. When editing, <strong>“Download / view”</strong> opens the current image.</p>
<p>Buttons: <strong>“Save”</strong> and, when editing, <strong>“Delete”</strong>.</p>
<p>When editing, the <strong>“Languages”</strong> selector chooses the language you write in.</p>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['helpers-system-generic-content-admin-forms-home-image'])): ?>
<h2 class="ui header" id="the-main-image">The main image</h2>
<p>How to get there: <strong>“Main image”</strong>, in the side menu.</p>
<p><img class="ui image" alt="The main image" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/helpers-system-generic-content-admin-forms-home-image.webp')); ?>"></p>
<p>The screen shows the current image. With <strong>“Change image”</strong> you choose another one, with <strong>“Download / view”</strong> you open the current one and with <strong>“Save”</strong> you confirm the change.</p>
<?php endif; ?>
