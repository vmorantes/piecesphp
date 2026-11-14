<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/en/56-directorio.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>The Directory brings together the people and organizations whose public profile is visible.</p>
<div class="ui styled fluid accordion" data-section-accordion>
<h2 class="active title" id="how-to-get-there"><span role="button" tabindex="0" aria-expanded="true" aria-controls="how-to-get-there-contenido"><i class="dropdown icon" aria-hidden="true"></i>How to get there</span></h2>
<div class="active content" id="how-to-get-there-contenido">
<p>From the side menu: <strong>“Directory”</strong>.</p>
</div>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['directory-admin-list'])): ?>
<h2 class="title" id="what-you-see"><span role="button" tabindex="0" aria-expanded="false" aria-controls="what-you-see-contenido"><i class="dropdown icon" aria-hidden="true"></i>What you see</span></h2>
<div class="content" id="what-you-see-contenido">
<p>A table with three columns: <strong>Actor</strong> (the person or organization), <strong>Location</strong> and <strong>Actions</strong>.</p>
<p><img class="ui image" alt="What you see" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/directory-admin-list.webp')); ?>"></p>
</div>
<?php endif; ?>
<h2 class="title" id="what-you-can-do"><span role="button" tabindex="0" aria-expanded="false" aria-controls="what-you-can-do-contenido"><i class="dropdown icon" aria-hidden="true"></i>What you can do</span></h2>
<div class="content" id="what-you-can-do-contenido">
<ul>
<li><strong>“View profile”</strong>, in each row: opens the profile page of that person or organization. These are screens for reading; the breadcrumbs take you back to the Directory.</li>
<li><strong>“Export current list”</strong>: downloads what you are viewing.</li>
</ul>
<div class="ui positive message">
<div class="header">Edit an organization</div>
<p>On an organization's profile page, the <strong>“Edit”</strong> button leads to its public profile. It is explained in the <strong>Your organization</strong> chapter.</p>
</div>
</div>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['profile-admin-profile'])): ?>
<h2 class="title" id="a-person-s-profile"><span role="button" tabindex="0" aria-expanded="false" aria-controls="a-person-s-profile-contenido"><i class="dropdown icon" aria-hidden="true"></i>A person's profile</span></h2>
<div class="content" id="a-person-s-profile-contenido">
<p>From the <strong>Directory</strong>, the <strong>“View profile”</strong> button of a person opens their public profile page. At the top, the breadcrumbs take you back to the Directory. It is a screen for reading: it has no fields to fill in.</p>
<p><img class="ui image" alt="A person's profile" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/profile-admin-profile.webp')); ?>"></p>
</div>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['profile-organization-admin-profile'])): ?>
<h2 class="title" id="an-organization-s-profile-page"><span role="button" tabindex="0" aria-expanded="false" aria-controls="an-organization-s-profile-page-contenido"><i class="dropdown icon" aria-hidden="true"></i>An organization's profile page</span></h2>
<div class="content" id="an-organization-s-profile-page-contenido">
<p><strong>“View profile”</strong> opens the organization's profile page, with the breadcrumbs <strong>Home › Directory › organization name</strong>. It is a screen for reading. The <strong>“Edit”</strong> button leads to its public profile.</p>
<p><img class="ui image" alt="An organization's profile page" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/profile-organization-admin-profile.webp')); ?>"></p>
</div>
<?php endif; ?>
</div>
