<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/en/46-importar-y-exportar.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>Load data from a spreadsheet, or download the system's data in the format you choose.</p>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['data-transfer-hub'])): ?>
<h2 class="ui header" id="the-main-screen">The main screen</h2>
<p>How to get there: <strong>“Import and export”</strong>, in the side menu.</p>
<p><img class="ui image" alt="The main screen" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/data-transfer-hub.webp')); ?>"></p>
<p>It has two tabs:</p>
<ul>
<li><strong>“Import”</strong>: a table with <strong>Entity</strong>, <strong>Description</strong>, <strong>Actions</strong> and <strong>Active</strong>. In each row you can <strong>“Import”</strong>, or download the template with <strong>“XLSX template”</strong> or <strong>“CSV template”</strong>.</li>
<li><strong>“Export”</strong>: the same table. In each row you can <strong>“Export”</strong>, or download with <strong>“Download XLSX”</strong> or <strong>“Download CSV”</strong>.</li>
</ul>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['data-transfer-import-users'])): ?>
<h2 class="ui header" id="import-users">Import users</h2>
<p>How to get there: in the <strong>“Import”</strong> tab, click <strong>“Import”</strong> in the users row; or use the gear button in the top bar, <strong>“System settings” › “User management” › “Users import”</strong>.</p>
<p><img class="ui image" alt="Import users" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/data-transfer-import-users.webp')); ?>"></p>
<p>The system accepts xlsx or csv files of up to 5 MB and 5000 rows.</p>
<div class="ui warning message">
<div class="header">All or nothing</div>
<p>If a row has errors, none is saved.</p>
</div>
<p>At the top, a notice: <strong>keep the template headings</strong>. Columns are recognized by their heading (or by the other names in the
table below), so the order does not matter; if you change a heading, that column will not be read.</p>
<p>There are two steps:</p>
<ol>
<li><strong>Download the template</strong>: the file <code>plantilla-users.xlsx</code>. Click <strong>“Download template”</strong> and fill it in without changing its
   headings.</li>
<li><strong>Upload the completed template</strong>: in <strong>“Template file”</strong> (required) click <strong>“Browse”</strong>, choose the
   file and click <strong>“Import”</strong>. If you click “Import” without a file, the field asks you for one.</li>
</ol>
<p>When it finishes, the <strong>Import result</strong> appears:</p>
<ul>
<li>the file and how many rows were read;</li>
<li>a message: <strong>“All N rows were imported”</strong> if they were all valid, or <strong>“No rows were imported”</strong> with how many have
  errors; if the file has no rows with data or could not be read (for example, a required column is missing), it tells
  you so;</li>
<li>the <strong>valid</strong> rows and, if there are any, the rows <strong>with errors</strong>, in two labels;</li>
<li>if there are errors, a <strong>Row · Error</strong> table. The row is the one in the file: row 1 is the headings row.</li>
</ul>
<p>The template columns:</p>
<table class="ui celled table">
<thead>
<tr>
<th>Column</th>
<th>Required</th>
<th>Also recognized as</th>
</tr>
</thead>
<tbody>
<tr>
<td>User</td>
<td>Yes</td>
<td>user, nombre de usuario</td>
</tr>
<tr>
<td>Email</td>
<td>Yes</td>
<td>correo electrónico, e-mail</td>
</tr>
<tr>
<td>First name</td>
<td>Yes</td>
<td>nombre</td>
</tr>
<tr>
<td>Middle name</td>
<td>No</td>
<td></td>
</tr>
<tr>
<td>First last name</td>
<td>Yes</td>
<td>apellido</td>
</tr>
<tr>
<td>Second last name</td>
<td>No</td>
<td></td>
</tr>
<tr>
<td>Password</td>
<td>No</td>
<td></td>
</tr>
<tr>
<td>Type</td>
<td>No</td>
<td></td>
</tr>
<tr>
<td>Organization</td>
<td>No</td>
<td>código de organización, codigo de organizacion</td>
</tr>
</tbody>
</table>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['data-transfer-export-users-form'])): ?>
<h2 class="ui header" id="export-users">Export users</h2>
<p>How to get there: in the <strong>“Export”</strong> tab, click <strong>“Export”</strong> in the users row.</p>
<p><img class="ui image" alt="Export users" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/data-transfer-export-users-form.webp')); ?>"></p>
<p>Choose the <strong>Format</strong>, XLSX (the default) or CSV, and click <strong>“Download”</strong>. The <strong>“Download XLSX”</strong> and
<strong>“Download CSV”</strong> shortcuts in the tab do the same without going through this screen.</p>
<p>The file is named <code>users-</code> followed by the date and time, and has one row per user with these columns: User,
Email, First name, Middle name, First last name, Second last name, Type and Organization. It can be imported
again with <strong>“Users import”</strong>; it does not include passwords.</p>
<?php endif; ?>
