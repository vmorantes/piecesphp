<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/en/54-aprobaciones.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>Here you see the content that is waiting for approval.</p>
<h2 class="ui header" id="how-to-get-there">How to get there</h2>
<p>From the side menu: <strong>“Approvals”</strong>.</p>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['system-approval-admin-list'])): ?>
<h2 class="ui header" id="what-you-see">What you see</h2>
<p>A table with these columns: <strong>Time</strong>, <strong>Content type</strong>, <strong>Date</strong>, <strong>User</strong> and <strong>Actions</strong>. If nothing is pending, the screen says “No information available”.</p>
<p><img class="ui image" alt="What you see" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/system-approval-admin-list.webp')); ?>"></p>
<?php endif; ?>
<h2 class="ui header" id="what-you-can-do">What you can do</h2>
<p>To narrow the list, choose a <strong>Content type</strong> and a <strong>Time</strong>, and click <strong>“Filter”</strong>. <strong>“Clear”</strong> removes the filters. <strong>“Export current list”</strong> downloads what you are viewing.</p>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['system-approval-admin-forms-approval'])): ?>
<h2 class="ui header" id="review-a-piece-of-content">Review a piece of content</h2>
<p>How to get there: in the list, click <strong>“Review”</strong> in the content's row.</p>
<p><img class="ui image" alt="Review a piece of content" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/system-approval-admin-forms-approval.webp')); ?>"></p>
<p>The screen is titled with the content type, for example <strong>“Approval of Publication”</strong>, and shows what has to be
reviewed: how long it has been waiting, its data (for a publication: category, name, author, dates and description), its
images and its attachments, which you can <strong>“Download”</strong>.</p>
<p>At the bottom, the <strong>“Reason”</strong> field, optional, and two buttons: <strong>“Reject”</strong> and <strong>“Approve”</strong>. Both ask for confirmation.
Once you confirm, you go back to the list, where the content no longer appears, and the person who created it receives an email with the
result.</p>
<div class="ui positive message">
<div class="header">What changes on the site</div>
<p>For a publication, the approved one is shown on the public site and the rejected one is not.</p>
</div>
<?php endif; ?>
