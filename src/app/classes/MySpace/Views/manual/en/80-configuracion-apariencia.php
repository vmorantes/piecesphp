<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/en/80-configuracion-apariencia.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>Here you customize how the platform looks and how it is presented. To get there, use the gear button in the top bar, <strong>“System settings”</strong>. Brand
images, Login backgrounds and Colors are in the <strong>“Appearance”</strong> group; Identity and SEO and
Files for search engines are in <strong>“SEO and scripts”</strong>.</p>
<div class="ui styled fluid accordion" data-section-accordion>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['configurations-appearance-brand-images'])): ?>
<h2 class="active title" id="brand-images"><span role="button" tabindex="0" aria-expanded="true" aria-controls="brand-images-contenido"><i class="dropdown icon" aria-hidden="true"></i>Brand images</span></h2>
<div class="active content" id="brand-images-contenido">
<p>Go to <strong>“Brand images”</strong>. The platform is white label: these images identify your organization across
the whole system, from the login page and the side menu to the emails and the public site.</p>
<p><img class="ui image" alt="Brand images" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/configurations-appearance-brand-images.webp')); ?>"></p>
<p>To edit an image, click it. Each one also has a <strong>“Change image”</strong> button and an icon to download or view it. The images are:</p>
<ul>
<li>Logo</li>
<li>Favicon, admin area</li>
<li>Favicon, public area / website</li>
<li>E-mails</li>
<li>Horizontal banner</li>
<li>Vertical banner</li>
</ul>
<table class="ui celled table">
<thead>
<tr>
<th>Field</th>
<th>Required</th>
<th>Purpose</th>
</tr>
</thead>
<tbody>
<tr>
<td>Text above the sponsor</td>
<td>No</td>
<td>Optional: it appears in the menu footer, above the sponsor image. There is one field for each active language of the site. In the main language, if empty, nothing is shown; in the others, if empty, the main language's text is used.</td>
</tr>
<tr>
<td>Invert colors in dark mode</td>
<td>No</td>
<td>It is on some of the images. It needs a PNG with a transparent background: an opaque background would show as a white rectangle.</td>
</tr>
</tbody>
</table>
<p>When you finish, click <strong>“Save”</strong>.</p>
</div>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['configurations-appearance-backgrounds'])): ?>
<h2 class="title" id="login-backgrounds"><span role="button" tabindex="0" aria-expanded="false" aria-controls="login-backgrounds-contenido"><i class="dropdown icon" aria-hidden="true"></i>Login backgrounds</span></h2>
<div class="content" id="login-backgrounds-contenido">
<p>Go to <strong>“Login backgrounds”</strong>. These images fill the left panel of the login page; the
platform shows one at random on each visit.</p>
<p><img class="ui image" alt="Login backgrounds" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/configurations-appearance-backgrounds.webp')); ?>"></p>
<p>There can be from <strong>one to five</strong> backgrounds (<strong>Background 1</strong> to <strong>Background 5</strong>); the images fill the first cards and the rest
stay empty. On a card with an image, click it or use <strong>“Change image”</strong> to edit it, the icon to download or
view it and <strong>“Delete”</strong> to remove it (it asks you to confirm). An empty card only has <strong>“Change image”</strong>: that is how
you add a background. At least one always remains: with a single background, “Delete” is disabled.</p>
<p>Separately there is the <strong>Background login problems</strong>, which is changed the same way and cannot be deleted.</p>
</div>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['configurations-appearance-colors'])): ?>
<h2 class="title" id="colors"><span role="button" tabindex="0" aria-expanded="false" aria-controls="colors-contenido"><i class="dropdown icon" aria-hidden="true"></i>Colors</span></h2>
<div class="content" id="colors-contenido">
<p>Go to <strong>“Colors”</strong>. Your organization's color is used only as an accent (buttons, selection, links and focus). The
complementary colors are used only in charts and data.</p>
<p><img class="ui image" alt="Colors" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/configurations-appearance-colors.webp')); ?>"></p>
<p>Next to each color, <strong>“Choose color”</strong> opens the picker. Below, the <strong>“Live preview”</strong> shows how
the accent and the palette will look in the panel. At the end, <strong>“Cancel”</strong> or <strong>“Save”</strong>.</p>
<table class="ui celled table">
<thead>
<tr>
<th>Field</th>
<th>Required</th>
<th>Purpose</th>
</tr>
</thead>
<tbody>
<tr>
<td>Accent color</td>
<td>No</td>
<td>Your organization's main color.</td>
</tr>
<tr>
<td>Custom, Analogous, Triad, Complementary</td>
<td>No</td>
<td>How the chart colors are generated from the accent, or “Custom” to choose them yourself. Series 1 is always the accent.</td>
</tr>
<tr>
<td>Series 2, Series 3, Series 4, Series 5</td>
<td>No</td>
<td>The colors of the chart series.</td>
</tr>
<tr>
<td>Use the accent, Dark neutral</td>
<td>No</td>
<td>The color of the browser's top bar on phones.</td>
</tr>
<tr>
<td>Accent 2, Accent 3</td>
<td>No</td>
<td>Colors for occasional highlights: the panel does not use them on its own.</td>
</tr>
<tr>
<td>Font</td>
<td>No</td>
<td>The panel's typeface.</td>
</tr>
<tr>
<td>Base size</td>
<td>No</td>
<td>The base size of the text.</td>
</tr>
<tr>
<td>Radius</td>
<td>No</td>
<td>How rounded the corners are across the whole panel.</td>
</tr>
</tbody>
</table>
</div>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['configurations-appearance-seo'])): ?>
<h2 class="title" id="identity-and-seo"><span role="button" tabindex="0" aria-expanded="false" aria-controls="identity-and-seo-contenido"><i class="dropdown icon" aria-hidden="true"></i>Identity and SEO</span></h2>
<div class="content" id="identity-and-seo-contenido">
<p>Go to <strong>“Identity and SEO”</strong>. It gathers the site data used by search engines and by the preview when a link is shared
on social networks and messaging apps.</p>
<p><img class="ui image" alt="Identity and SEO" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/configurations-appearance-seo.webp')); ?>"></p>
<p>It has two tabs, <strong>Spanish</strong> and <strong>English</strong>: you write the texts in each language. The image for sharing is changed
with <strong>“Change image”</strong>. At the end, <strong>“Cancel”</strong> or <strong>“Save”</strong>.</p>
<table class="ui celled table">
<thead>
<tr>
<th>Field</th>
<th>Required</th>
<th>Purpose</th>
</tr>
</thead>
<tbody>
<tr>
<td>Title</td>
<td>Yes</td>
<td>Name of the platform: it is shown in the browser tab, the headers and the emails (up to 60 characters).</td>
</tr>
<tr>
<td>Description</td>
<td>Yes</td>
<td>Short summary of the site that search engines show in their results (up to 160 characters).</td>
</tr>
<tr>
<td>Owner</td>
<td>Yes</td>
<td>Who owns the site.</td>
</tr>
<tr>
<td>Share title</td>
<td>No</td>
<td>Title of the card when the home page is shared. Empty: the usual title (up to 60 characters).</td>
</tr>
<tr>
<td>Share description</td>
<td>No</td>
<td>Description of the card when shared. Empty: the site description (up to 200 characters).</td>
</tr>
<tr>
<td>X account</td>
<td>No</td>
<td>Your organization's account on X.</td>
</tr>
<tr>
<td>Keywords</td>
<td>No</td>
<td>Keywords of the site.</td>
</tr>
</tbody>
</table>
<p>Next to each field, in small print, is the tag it generates on the page (<code>&lt;title&gt;</code>, <code>og:title</code>, <code>meta keywords</code>…), in case
you need to find it in the page's code.</p>
<ul>
<li><strong>“Use the site title”</strong> and <strong>“Use the site description”</strong>: when checked, the title or description for sharing
  are the site's and the field is disabled showing them; when unchecked, you write your own.</li>
<li><strong>What is not edited here, but is shown</strong>: the <strong>site name</strong> (<code>og:site_name</code>, the Title), the <strong>address</strong> and the
  <strong>canonical URL</strong> of the home page, the <strong>sharing language</strong> (<code>og:locale</code>, which is changed in <code>config.php</code>), the <strong>type</strong>
  (website on the home page, article on publications) and the <strong>author</strong> (<code>meta author</code>, the Owner).</li>
</ul>
</div>
<?php endif; ?>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['configurations-appearance-site-files'])): ?>
<h2 class="title" id="files-for-search-engines"><span role="button" tabindex="0" aria-expanded="false" aria-controls="files-for-search-engines-contenido"><i class="dropdown icon" aria-hidden="true"></i>Files for search engines</span></h2>
<div class="content" id="files-for-search-engines-contenido">
<p>Go to <strong>“Files for search engines”</strong>. This is what search engines and artificial intelligence read: the files
robots.txt, humans.txt and llms.txt. For each one, <strong>“See the fixed configuration”</strong> shows the part the system already
provides and that cannot be changed; in the <strong>Add</strong> field you write your own.</p>
<p><img class="ui image" alt="Files for search engines" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/configurations-appearance-site-files.webp')); ?>"></p>
<table class="ui celled table">
<thead>
<tr>
<th>Field</th>
<th>Required</th>
<th>Purpose</th>
</tr>
</thead>
<tbody>
<tr>
<td>Add (robots.txt)</td>
<td>No</td>
<td>For example, to keep search engines out of a page: <code>Disallow: /my-page/</code>.</td>
</tr>
<tr>
<td>Add (humans.txt)</td>
<td>No</td>
<td>The team, the acknowledgements or whatever you want to tell about the site.</td>
</tr>
<tr>
<td>Add (llms.txt)</td>
<td>No</td>
<td>Text in Markdown format that is added at the end of the summary the system already generates.</td>
</tr>
</tbody>
</table>
<p>When you finish, click <strong>“Save”</strong>.</p>
</div>
<?php endif; ?>
</div>
