<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/en/32-noticias-internas.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>The notices that panel users see, by profile and with their dates.</p>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['news-admin-list'])): ?>
<h2 class="ui header" id="the-news-list">The news list</h2>
<p>How to get there: <strong>“Internal News”</strong>, in the side menu.</p>
<p><img class="ui image" alt="The news list" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/news-admin-list.webp')); ?>"></p>
<p>The table shows: <strong>Title</strong>, <strong>Status</strong>, <strong>Start date</strong>, <strong>End date</strong> and <strong>Actions</strong>. In each row you can <strong>“Edit”</strong> or <strong>“Delete”</strong> the news item.</p>
<p>At the top are the buttons <strong>“New news”</strong>, <strong>“Categories”</strong> and <strong>“Export current list”</strong>.</p>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['news-admin-forms-add', 'news-admin-forms-edit'])): ?>
<h2 class="ui header" id="add-and-edit-a-news-item">Add and edit a news item</h2>
<p>How to get there: click <strong>“New news”</strong>, or <strong>“Edit”</strong> in a news item's row.</p>
<p><img class="ui image" alt="Add and edit a news item" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/news-admin-forms-add.webp')); ?>"></p>
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
<td>Draft</td>
<td>No</td>
<td>Check it if it is not ready yet.</td>
</tr>
<tr>
<td>Profile types for which it will be visible</td>
<td>Yes</td>
<td>The user types that will see the news item.</td>
</tr>
<tr>
<td>Categories</td>
<td>Yes</td>
<td>The category of the news item.</td>
</tr>
<tr>
<td>Start date</td>
<td>Yes</td>
<td>From when it is shown.</td>
</tr>
<tr>
<td>End date</td>
<td>Yes</td>
<td>Until when it is shown.</td>
</tr>
<tr>
<td>Title</td>
<td>Yes</td>
<td>The title of the news item.</td>
</tr>
<tr>
<td>Content</td>
<td>Yes</td>
<td>The text, with an editor that has bold, italics, lists, links, images and tables.</td>
</tr>
</tbody>
</table>
<p>Buttons: <strong>“Save”</strong>, <strong>“Cancel”</strong> (goes back to the list) and, when editing, <strong>“Delete”</strong>.</p>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['news-category-admin-list'])): ?>
<h2 class="ui header" id="news-categories">News categories</h2>
<p>How to get there: the <strong>“Categories”</strong> button in the news list.</p>
<p><img class="ui image" alt="News categories" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/news-category-admin-list.webp')); ?>"></p>
<p>The table shows: <strong>Category</strong>, <strong>Color</strong>, <strong>Image</strong> and <strong>Actions</strong>. In each row you can <strong>“Edit”</strong> or <strong>“Delete”</strong>. At the top are <strong>“New category”</strong> and <strong>“Export current list”</strong>.</p>
<h3 class="ui header">Add and edit a category</h3>
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
<td>The name of the category.</td>
</tr>
<tr>
<td>Color</td>
<td>Yes</td>
<td>The color of the category; the <strong>“Choose color”</strong> button lets you pick it.</td>
</tr>
</tbody>
</table>
<p>With <strong>“Change image”</strong> you choose the category's icon: it is required when creating. When editing, <strong>“Download / view”</strong> opens the current one. Buttons: <strong>“Save”</strong>, <strong>“Cancel”</strong> and, when editing, <strong>“Delete”</strong>.</p>
<p>When editing, the <strong>“Languages”</strong> selector chooses the language you write in.</p>
<?php endif; ?>
