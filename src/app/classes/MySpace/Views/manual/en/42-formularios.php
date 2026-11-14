<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/en/42-formularios.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>This section brings together the form categories.</p>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['categories-admin-list'])): ?>
<h2 class="ui header" id="form-categories">Form categories</h2>
<p>How to get there: <strong>“Forms” › “Categories”</strong>, in the side menu.</p>
<p><img class="ui image" alt="Form categories" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/categories-admin-list.webp')); ?>"></p>
<p>The table shows the <strong>Name</strong> of each category. In each row you can <strong>“Edit”</strong>, <strong>“Translate”</strong> or <strong>“Delete”</strong>. At the top are <strong>“New category”</strong> and <strong>“Export current list”</strong>.</p>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['categories-admin-forms-add', 'categories-admin-forms-edit'])): ?>
<h2 class="ui header" id="add-and-edit-a-category">Add and edit a category</h2>
<p>Click <strong>“New category”</strong>, or <strong>“Edit”</strong> in a category's row; <strong>“Translate”</strong> opens the same form so you can write the name in English. When editing, the <strong>“Languages”</strong> selector chooses which language you are writing in.</p>
<p><img class="ui image" alt="Add and edit a category" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/categories-admin-forms-add.webp')); ?>"></p>
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
</tbody>
</table>
<p>Buttons: <strong>“Save”</strong>, <strong>“Cancel”</strong> and, when editing, <strong>“Delete”</strong>.</p>
<?php endif; ?>
