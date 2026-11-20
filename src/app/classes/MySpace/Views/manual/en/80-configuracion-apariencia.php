<?php
//Generado por bin/manual: se edita el .md (src/app/manual/framework/en/80-configuracion-apariencia.md).
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
?>
<p>Here you customize how the platform looks and how it is presented. To get there, use the gear button in the top bar, <strong>“System settings”</strong>. Brand
images, Login backgrounds and Panel style are in the <strong>“Appearance”</strong> group; Identity and SEO and
Files for search engines are in <strong>“SEO and scripts”</strong>.</p>
<div class="ui styled fluid accordion" data-section-accordion>
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['configurations-appearance-brand-images'])): ?>
<h2 class="active title" id="brand-images"><span role="button" tabindex="0" aria-expanded="true" aria-controls="brand-images-contenido"><i class="dropdown icon" aria-hidden="true"></i>Brand images</span></h2>
<div class="active content" id="brand-images-contenido">
<p>Go to <strong>“Brand images”</strong>. The platform is white-label: these images identify your organization across the whole
system, from the login and the side menu to the emails and the public site. The screen has three tabs:
<strong>“Images”</strong>, <strong>“Loader”</strong> and <strong>“Preview”</strong>. The chosen tab stays in the address and the screen remembers it when you
come back.</p>
<p><img class="ui image" alt="Brand images" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/configurations-appearance-brand-images.webp')); ?>"></p>
<p>To edit an image, click it. Each one also has the <strong>“Change image”</strong> button and an icon to download or view it. Each
card says where its image is used and shows how it looks, at its real size: in <strong>“Light mode”</strong> and, where it applies,
in <strong>“Dark mode”</strong>, side by side.</p>
<p>In <strong>“Images”</strong>:</p>
<ul>
<li><strong>Logo</strong>.</li>
<li><strong>Favicon, admin area</strong> and <strong>Favicon, public area / website</strong>.</li>
<li><strong>E-mails</strong>.</li>
<li>In <strong>“Third parties”</strong>: <strong>Horizontal banner</strong> and <strong>Vertical banner</strong>, and the text above them.</li>
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
<td>Optional: it appears in the menu footer, above the sponsor's image. There is one field per active site language. In the main language, empty, it is not shown; in the others, empty uses the main language's.</td>
</tr>
<tr>
<td>In dark mode</td>
<td>No</td>
<td>It is on the logo, both banners and all the loader images. <strong>“Same”</strong> shows the same image; <strong>“Invert to white”</strong> turns it white (it needs a transparent background: an opaque one would show as a white rectangle); <strong>“Another image”</strong> lets you upload a dark-mode version with <strong>“Choose dark image”</strong>. It is saved when you change it.</td>
</tr>
</tbody>
</table>
<p>When you are done with the sponsor text, click <strong>“Save”</strong>.</p>
<h3 class="ui header">The loader</h3>
<p>In <strong>“Loader”</strong> is what you see while a screen loads, in the panel and on the public site. At the top, <strong>“What the
loader shows”</strong> has three switches, on by default: <strong>“Animation”</strong>, <strong>“Logo”</strong> and <strong>“Loading screen”</strong>. Turning one off
does not delete its image: turning it on again brings back the same one. With all three off, the loader shows a simple
ring. Brand images are not deleted: they are only replaced.</p>
<p><img class="ui image" alt="The loader" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/configurations-appearance-brand-images-loader.webp')); ?>"></p>
<ul>
<li><strong>Loading animation</strong>: it accepts GIF, of any size (360 px high is recommended), and is uploaded as is, without
  cropping. The main user can also upload an SVG, which is checked on upload. By default it is the logo drawing
  itself.</li>
<li><strong>Loader logo</strong>: a still image below the animation. By default it is the logo's “P” (white and light blue in dark
  mode).</li>
<li><strong>Loading screen</strong> and <strong>Mobile loading screen</strong>: a full-screen image behind the loader, animated (GIF; the main
  user, also SVG) or still (PNG, JPG or WebP), up to 3 MB. The animation, the logo, the message and the percentage go on
  top, centered: keep the center clean. 1920 × 1080 px is recommended for desktop and 1080 × 1920 px for mobile; if you
  upload the desktop one and not the mobile one, a phone in portrait uses yours cropped to the center. By default there
  are four, animated, light and dark. With “Reduce motion”, an animated screen stays still.</li>
</ul>
<p>The images you upload here are saved without their hidden data (location, camera), keeping their orientation.</p>
<p>In <strong>“Test area”</strong>, each button launches the full-screen loader for a few seconds, with what you have saved and the
switches as they are: <strong>“Current one”</strong>, <strong>“Without the loading screen”</strong> (if there is one), <strong>“With message”</strong>, <strong>“With
percentage”</strong>, <strong>“With message and percentage”</strong>, <strong>“With the loading screen”</strong> (if it is on) and <strong>“Everything off”</strong>
(the ring). While it lasts, a button shows it in the other mode, light or dark, without changing your preference;
<strong>“Close”</strong> or Escape remove it earlier.</p>
<h3 class="ui header">The preview</h3>
<p>In <strong>“Preview”</strong> you see, with what is saved, how your images look in each place where they are used, in <strong>“Light
mode”</strong> and <strong>“Dark mode”</strong> side by side: the side menu expanded and collapsed, the mobile bar, the login column with
the sponsors box, the loader, the error page, an email header and the browser tab. It updates when you save an image,
without reloading.</p>
<p><img class="ui image" alt="The preview" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/configurations-appearance-brand-images-preview.webp')); ?>"></p>
<div class="ui info message">
<div class="header">Two simulations with a caveat</div>
<p>Email has no dark mode of its own (the recipient's email program decides), so its two samples are the same. The
browser tab is an imitation: each browser draws it.</p>
</div>
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
<h2 class="title" id="panel-style"><span role="button" tabindex="0" aria-expanded="false" aria-controls="panel-style-contenido"><i class="dropdown icon" aria-hidden="true"></i>Panel style</span></h2>
<div class="content" id="panel-style-contenido">
<p>Go to <strong>“Panel style”</strong>. It is a single screen with three tabs: <strong>“Colors”</strong>, <strong>“Typography and shape”</strong> and
<strong>“Accessibility”</strong>. The chosen tab stays in the address, so you can bookmark or share it, and the screen remembers it
when you come back.</p>
<p><img class="ui image" alt="Panel style" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/configurations-appearance-colors.webp')); ?>"></p>
<p>Below the tabs, the <strong>“Live preview”</strong> shows the panel in <strong>“Light mode”</strong> and <strong>“Dark mode”</strong> side by side (stacked on
phones), and it updates right away with what you change in any of the three tabs, before saving.</p>
<p>Each tab has <strong>“Reset”</strong>: it tells you what goes back to the default values, asks you to confirm and saves it right
away. It only resets that tab; anything you changed on the screen without saving is lost.</p>
<div class="ui info message">
<div class="header">Colors and typography are saved together</div>
<p>“Colors” and “Typography and shape” are one form: the <strong>“Save colors and typography”</strong> button saves both tabs. If
you leave unsaved changes in the other one, it warns you next to the button. “Accessibility” is saved separately,
with its own <strong>“Save”</strong>.</p>
</div>
<h3 class="ui header">Colors</h3>
<p>Your organization's color is used only as an accent (buttons, selection, links and focus). The complementary colors
are used only in charts and data. Next to each color, <strong>“Choose color”</strong> opens the picker.</p>
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
<td>Your organization's main color. If it is too light or too dark to read, the panel adjusts it on its own, without changing its hue.</td>
</tr>
<tr>
<td>Dark mode accent</td>
<td>No</td>
<td>The accent when the panel is in dark mode. With <strong>“Automatic”</strong> the panel calculates it from the accent; below it you see the resulting color and its contrast.</td>
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
</tbody>
</table>
<h3 class="ui header">Typography and shape</h3>
<p><img class="ui image" alt="Typography and shape" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/configurations-appearance-typography.webp')); ?>"></p>
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
<td>Font</td>
<td>No</td>
<td>The panel's typeface: Atkinson Hyperlegible Next (the default), Red Hat Text, IBM Plex Sans or Onest.</td>
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
<?php if (\MySpace\Controllers\MySpaceController::manualSectionVisible(['configurations-appearance-accessibility'])): ?>
<h2 class="title" id="panel-accessibility"><span role="button" tabindex="0" aria-expanded="false" aria-controls="panel-accessibility-contenido"><i class="dropdown icon" aria-hidden="true"></i>Panel accessibility</span></h2>
<div class="content" id="panel-accessibility-contenido">
<p>It is the <strong>“Accessibility”</strong> tab of “Panel style” (the old “Accessibility” address leads to it). Here you choose how
the panel looks for whoever has not chosen their own in “My account” › “Preferences” (see “Your account”, “Your
preferences”). It has the same two cards as each person's preferences: <strong>Appearance</strong> and <strong>Reading and motion</strong>.</p>
<p><img class="ui image" alt="Accessibility" src="<?= escape_html(\MySpace\MySpaceRoutes::staticRoute('manual/es/configurations-appearance-accessibility.webp')); ?>"></p>
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
<td>Default color mode</td>
<td>No</td>
<td>In <strong>Appearance</strong>: <strong>“Light”</strong>, <strong>“Dark”</strong> or <strong>“System”</strong> (each person's device).</td>
</tr>
<tr>
<td>Default text size</td>
<td>No</td>
<td><strong>“Normal”</strong>, <strong>“Large”</strong> or <strong>“Larger”</strong>. Large and Larger add 2 and 4 pixels to the “Base size” in “Typography and shape”.</td>
</tr>
<tr>
<td>Default text spacing</td>
<td>No</td>
<td><strong>“Normal”</strong> or <strong>“Wide”</strong>: more space between lines, paragraphs, words and letters.</td>
</tr>
<tr>
<td>Default contrast</td>
<td>No</td>
<td>In <strong>Reading and motion</strong>: <strong>“Automatic”</strong> (it follows each person's device) or <strong>“More contrast”</strong>.</td>
</tr>
<tr>
<td>Default motion</td>
<td>No</td>
<td><strong>“Automatic”</strong> or <strong>“Reduce motion”</strong>.</td>
</tr>
<tr>
<td>Default transparency</td>
<td>No</td>
<td><strong>“Like the system”</strong> (it follows each person's device) or <strong>“Reduce”</strong>: solid backgrounds instead of translucent ones.</td>
</tr>
<tr>
<td>Default focus indicator</td>
<td>No</td>
<td><strong>“Normal”</strong> or <strong>“Strong”</strong>: the outline of what is selected with the keyboard, more visible.</td>
</tr>
</tbody>
</table>
<div class="ui info message">
<div class="header">Whoever already chose their own</div>
<p>Whoever already chose any of this in their preferences keeps their own even if you change this. To go back to the
site's, they choose <strong>“Like the site”</strong> in that group of their preferences.</p>
</div>
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
