<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/en/40-documentos.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>The files shared in the panel, with their type.</p>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['documents-admin-explorer'])): ?>
<h2 class="ui header" id="explore-the-documents">Explore the documents</h2>
<p>How to get there: <strong>“Documents” › “Explore”</strong>, in the side menu.</p>
<p><img class="ui image" alt="Explore the documents" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/documents-admin-explorer.webp')); ?>"></p>
<p>It is for searching and opening the shared documents. Each document appears on a card with its name, its type and its description. At the top there is a <strong>“Search”</strong> box and at the bottom, the pagination.</p>
<p>On each card, <strong>“Open”</strong> shows the file. If the document has no file, the card says <strong>“No file”</strong>.</p>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['documents-admin-list'])): ?>
<h2 class="ui header" id="manage-the-documents">Manage the documents</h2>
<p>How to get there: <strong>“Documents” › “Manage”</strong>, in the side menu.</p>
<p><img class="ui image" alt="Manage the documents" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/documents-admin-list.webp')); ?>"></p>
<p>The table shows the <strong>Document name</strong> and its <strong>Actions</strong>. In each row you can <strong>“Edit”</strong>, <strong>“View file”</strong> or <strong>“Delete”</strong>.</p>
<p>At the top are <strong>“Document types”</strong>, <strong>“New document”</strong> and <strong>“Export current list”</strong>.</p>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['documents-admin-forms-add', 'documents-admin-forms-edit'])): ?>
<h2 class="ui header" id="add-and-edit-a-document">Add and edit a document</h2>
<p>How to get there: click <strong>“New document”</strong>, or <strong>“Edit”</strong> in a document's row.</p>
<p><img class="ui image" alt="Add and edit a document" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/documents-admin-forms-add.webp')); ?>"></p>
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
<td>Type of document</td>
<td>Yes</td>
<td>The type it is classified under.</td>
</tr>
<tr>
<td>Document name</td>
<td>Yes</td>
<td>The name it shows with in the list.</td>
</tr>
<tr>
<td>Document</td>
<td>Yes</td>
<td>The file.</td>
</tr>
<tr>
<td>Description</td>
<td>No</td>
<td>A text that explains the document.</td>
</tr>
</tbody>
</table>
<p>With <strong>“Change image”</strong> you choose the document's image.</p>
<p>Buttons: <strong>“Save”</strong> and <strong>“Cancel”</strong>. When creating, the file is chosen with <strong>“Add document”</strong>. When editing you also have <strong>“View document”</strong>, <strong>“Change document”</strong> and <strong>“Delete”</strong>.</p>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['document-types-admin-list'])): ?>
<h2 class="ui header" id="document-types">Document types</h2>
<p>How to get there: the <strong>“Document types”</strong> button in the documents list.</p>
<p><img class="ui image" alt="Document types" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/document-types-admin-list.webp')); ?>"></p>
<p>The table shows the <strong>Name</strong> of each type. In each row you can <strong>“Edit”</strong>, <strong>“Translate”</strong> or <strong>“Delete”</strong>. At the top are <strong>“New type”</strong> and <strong>“Export current list”</strong>.</p>
<h3 class="ui header">Add and edit a type</h3>
<p>Click <strong>“New type”</strong>, or <strong>“Edit”</strong> in a type's row; <strong>“Translate”</strong> opens the same form to write the name in English. When editing, the <strong>“Languages”</strong> selector chooses the language you write in.</p>
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
<td>The name of the type.</td>
</tr>
</tbody>
</table>
<p>Buttons: <strong>“Save”</strong>, <strong>“Cancel”</strong> and, when editing, <strong>“Delete”</strong>.</p>
<?php endif; ?>
