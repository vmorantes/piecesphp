<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/en/30-publicaciones.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>Here you manage what is published on the site, with its category and its date.</p>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['publications-admin-list'])): ?>
<h2 class="ui header" id="the-publications-list">The publications list</h2>
<p>How to get there: <strong>“Publications” › “Publications”</strong>, in the side menu.</p>
<p><img class="ui image" alt="The publications list" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/publications-admin-list.webp')); ?>"></p>
<p>At the top there are four tabs to filter: <strong>“All”</strong>, <strong>“Published”</strong>, <strong>“Scheduled”</strong> and <strong>“Draft”</strong>.</p>
<p>The table shows these columns: <strong>Title</strong>, <strong>Status</strong>, <strong>Date</strong>, <strong>Author</strong>, <strong>Visits</strong>, <strong>Featured</strong> and <strong>Actions</strong>.</p>
<p>In each row you can:</p>
<ul>
<li><strong>“Edit”</strong> the publication.</li>
<li><strong>“View on site”</strong>, to open it as the public sees it.</li>
<li><strong>“Delete”</strong> the publication.</li>
</ul>
<p>At the top there are also the buttons:</p>
<ul>
<li><strong>“New publication”</strong>, to create one.</li>
<li><strong>“Categories”</strong>, to go to the publication categories.</li>
<li><strong>“Export current list”</strong>.</li>
</ul>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['publications-admin-forms-add', 'publications-admin-forms-edit'])): ?>
<h2 class="ui header" id="add-and-edit-a-publication">Add and edit a publication</h2>
<p>How to get there: click <strong>“New publication”</strong> in the list, or <strong>“Edit”</strong> in a publication's row.</p>
<p><img class="ui image" alt="Add and edit a publication" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/publications-admin-forms-add.webp')); ?>"></p>
<p>The form is organized in tabs: <strong>“Basic data”</strong>, <strong>“Images”</strong>, <strong>“Details”</strong>, <strong>“Attachments”</strong> and <strong>“SEO”</strong>.
When you edit a publication you also see one tab per language (<strong>“Spanish”</strong> and <strong>“English”</strong>), to complete each
version of the text.</p>
<p>Fields in <strong>“Basic data”</strong>:</p>
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
<td>Main language</td>
<td>Yes</td>
<td>Only when creating: the language in which you write the publication.</td>
</tr>
<tr>
<td>Name</td>
<td>Yes</td>
<td>The name of the publication.</td>
</tr>
<tr>
<td>Author</td>
<td>Yes</td>
<td>Who signs it.</td>
</tr>
<tr>
<td>Date</td>
<td>Yes</td>
<td>The date of the publication.</td>
</tr>
<tr>
<td>Featured</td>
<td>No</td>
<td>Marks the publication as featured.</td>
</tr>
<tr>
<td>Draft</td>
<td>No</td>
<td>Check it if it is not ready yet.</td>
</tr>
<tr>
<td>Content</td>
<td>Yes</td>
<td>The text, with an editor that has bold, italics, lists, links, images and tables.</td>
</tr>
</tbody>
</table>
<p>More fields in the form:</p>
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
<td>Categories</td>
<td>Yes</td>
<td>It is in the <strong>“Details”</strong> tab: the category of the publication.</td>
</tr>
<tr>
<td>Main image</td>
<td>Yes when creating</td>
<td>It is in the <strong>“Images”</strong> tab. Preferably in .jpg format.</td>
</tr>
<tr>
<td>Thumbnail image</td>
<td>Yes when creating</td>
<td>It is in the <strong>“Images”</strong> tab. Preferably in .jpg format.</td>
</tr>
<tr>
<td>Start</td>
<td>No</td>
<td>It is in the <strong>“Details”</strong> tab. Start date.</td>
</tr>
<tr>
<td>End</td>
<td>No</td>
<td>It is in the <strong>“Details”</strong> tab. End date.</td>
</tr>
<tr>
<td>Description</td>
<td>No</td>
<td>It is in the <strong>“SEO”</strong> tab.</td>
</tr>
</tbody>
</table>
<p>Buttons:</p>
<ul>
<li><strong>“Save”</strong>.</li>
<li><strong>“Save and translate”</strong>, only when creating.</li>
<li><strong>“Cancel”</strong>, to go back to the list.</li>
<li><strong>“Delete”</strong>, only when editing.</li>
<li><strong>“View on site”</strong>, only when editing.</li>
</ul>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['publications-category-admin-list'])): ?>
<h2 class="ui header" id="publication-categories">Publication categories</h2>
<p>How to get there: <strong>“Publications” › “Categories”</strong>, in the side menu, or the <strong>“Categories”</strong> button in the list.</p>
<p><img class="ui image" alt="Publication categories" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/publications-category-admin-list.webp')); ?>"></p>
<p>The table shows the <strong>Name</strong> of each category, with the <strong>“Edit”</strong> action. At the top there are <strong>“New category”</strong> and <strong>“Export current list”</strong>.</p>
<h3 class="ui header">Add and edit a category</h3>
<p>Click <strong>“New category”</strong> or <strong>“Edit”</strong> in a row.</p>
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
<td>Main language</td>
<td>Yes</td>
<td>Only when creating.</td>
</tr>
<tr>
<td>Name</td>
<td>Yes</td>
<td>The name of the category.</td>
</tr>
<tr>
<td>Name (Spanish)</td>
<td>Yes</td>
<td>Only when editing.</td>
</tr>
<tr>
<td>Name (English)</td>
<td>No</td>
<td>Only when editing.</td>
</tr>
</tbody>
</table>
<p>Buttons: <strong>“Save”</strong>, <strong>“Cancel”</strong> and, when editing, <strong>“Delete”</strong>.</p>
<?php endif; ?>
