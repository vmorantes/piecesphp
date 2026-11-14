<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/en/22-notificaciones.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>The bell in the top bar gathers what concerns you: the notices the system sends you and the internal news that reach
you.</p>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['notifications-view'])): ?>
<h2 class="ui header" id="the-bell">The bell</h2>
<p>How to get there: the <strong>bell</strong> icon, at the top right. The number on it is the notifications you have not read yet; if
there are none, it does not appear.</p>
<p>Clicking it opens a window with:</p>
<ul>
<li>four filters, each with its number: <strong>“All”</strong>, <strong>“Internal news”</strong>, <strong>“Unread”</strong> and <strong>“Read”</strong>
  (“Internal news” only appears if the site uses news);</li>
<li>the list, from newest to oldest. Each notification has an icon according to its type (information, success,
  warning or error), its title, its text and when it arrived. Unread ones look different from read ones;</li>
<li><strong>“Mark all as read”</strong>, at the top, when there are unread ones;</li>
<li>at the bottom, <strong>“Load more”</strong> if there are more, and <strong>“See all”</strong>, which opens the screen with the full list.</li>
</ul>
<p>Clicking a notification marks it as read and, if it leads somewhere, takes you there. An internal news item opens in a
window with its content.</p>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['notifications-view'])): ?>
<h2 class="ui header" id="all-notifications">All notifications</h2>
<p>How to get there: <strong>“See all”</strong>, at the bottom of the bell.</p>
<p><img class="ui image" alt="All notifications" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/notifications-view.webp')); ?>"></p>
<p>The same list as a full page, with the four filters and <strong>“Mark all as read”</strong>. If there are more, <strong>“Load more”</strong> at
the end brings the next ones.</p>
<p>The ones you have already read are deleted when a new one arrives, if they are more than 90 days old. The ones you have
not read stay until you read them.</p>
<?php endif; ?>
