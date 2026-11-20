---
titulo: Appearance settings
rutas: [configurations-appearance-brand-images, configurations-appearance-backgrounds, configurations-appearance-colors, configurations-appearance-accessibility, configurations-appearance-seo, configurations-appearance-site-files]
---

# Appearance settings

Here you customize how the platform looks and how it is presented. To get there, use the gear button in the top bar, **“System settings”**. Brand
images, Login backgrounds and Panel style are in the **“Appearance”** group; Identity and SEO and
Files for search engines are in **“SEO and scripts”**.

## Brand images
<!-- ve: configurations-appearance-brand-images -->

Go to **“Brand images”**. The platform is white-label: these images identify your organization across the whole
system, from the login and the side menu to the emails and the public site. The screen has three tabs:
**“Images”**, **“Loader”** and **“Preview”**. The chosen tab stays in the address and the screen remembers it when you
come back.

![Brand images](img/configurations-appearance-brand-images.webp)

To edit an image, click it. Each one also has the **“Change image”** button and an icon to download or view it. Each
card says where its image is used and shows how it looks, at its real size: in **“Light mode”** and, where it applies,
in **“Dark mode”**, side by side.

In **“Images”**:

- **Logo**.
- **Favicon, admin area** and **Favicon, public area / website**.
- **E-mails**.
- In **“Third parties”**: **Horizontal banner** and **Vertical banner**, and the text above them.

| Field | Required | Purpose |
| --- | --- | --- |
| Text above the sponsor | No | Optional: it appears in the menu footer, above the sponsor's image. There is one field per active site language. In the main language, empty, it is not shown; in the others, empty uses the main language's. |
| In dark mode | No | It is on the logo, both banners and all the loader images. **“Same”** shows the same image; **“Invert to white”** turns it white (it needs a transparent background: an opaque one would show as a white rectangle); **“Another image”** lets you upload a dark-mode version with **“Choose dark image”**. It is saved when you change it. |

When you are done with the sponsor text, click **“Save”**.

### The loader

In **“Loader”** is what you see while a screen loads, in the panel and on the public site. At the top, **“What the
loader shows”** has three switches, on by default: **“Animation”**, **“Logo”** and **“Loading screen”**. Turning one off
does not delete its image: turning it on again brings back the same one. With all three off, the loader shows a simple
ring. Brand images are not deleted: they are only replaced.

![The loader](img/configurations-appearance-brand-images-loader.webp)

- **Loading animation**: it accepts GIF, of any size (360 px high is recommended), and is uploaded as is, without
  cropping. The main user can also upload an SVG, which is checked on upload. By default it is the logo drawing
  itself.
- **Loader logo**: a still image below the animation. By default it is the logo's “P” (white and light blue in dark
  mode).
- **Loading screen** and **Mobile loading screen**: a full-screen image behind the loader, animated (GIF; the main
  user, also SVG) or still (PNG, JPG or WebP), up to 3 MB. The animation, the logo, the message and the percentage go on
  top, centered: keep the center clean. 1920 × 1080 px is recommended for desktop and 1080 × 1920 px for mobile; if you
  upload the desktop one and not the mobile one, a phone in portrait uses yours cropped to the center. By default there
  are four, animated, light and dark. With “Reduce motion”, an animated screen stays still.

The images you upload here are saved without their hidden data (location, camera), keeping their orientation.

In **“Test area”**, each button launches the full-screen loader for a few seconds, with what you have saved and the
switches as they are: **“Current one”**, **“Without the loading screen”** (if there is one), **“With message”**, **“With
percentage”**, **“With message and percentage”**, **“With the loading screen”** (if it is on) and **“Everything off”**
(the ring). While it lasts, a button shows it in the other mode, light or dark, without changing your preference;
**“Close”** or Escape remove it earlier.

### The preview

In **“Preview”** you see, with what is saved, how your images look in each place where they are used, in **“Light
mode”** and **“Dark mode”** side by side: the side menu expanded and collapsed, the mobile bar, the login column with
the sponsors box, the loader, the error page, an email header and the browser tab. It updates when you save an image,
without reloading.

![The preview](img/configurations-appearance-brand-images-preview.webp)

!!! note "Two simulations with a caveat"
    Email has no dark mode of its own (the recipient's email program decides), so its two samples are the same. The
    browser tab is an imitation: each browser draws it.

## Login backgrounds
<!-- ve: configurations-appearance-backgrounds -->

Go to **“Login backgrounds”**. These images fill the left panel of the login page; the
platform shows one at random on each visit.

![Login backgrounds](img/configurations-appearance-backgrounds.webp)

There can be from **one to five** backgrounds (**Background 1** to **Background 5**); the images fill the first cards and the rest
stay empty. On a card with an image, click it or use **“Change image”** to edit it, the icon to download or
view it and **“Delete”** to remove it (it asks you to confirm). An empty card only has **“Change image”**: that is how
you add a background. At least one always remains: with a single background, “Delete” is disabled.

Separately there is the **Background login problems**, which is changed the same way and cannot be deleted.

## Panel style
<!-- ve: configurations-appearance-colors -->

Go to **“Panel style”**. It is a single screen with three tabs: **“Colors”**, **“Typography and shape”** and
**“Accessibility”**. The chosen tab stays in the address, so you can bookmark or share it, and the screen remembers it
when you come back.

![Panel style](img/configurations-appearance-colors.webp)

Below the tabs, the **“Live preview”** shows the panel in **“Light mode”** and **“Dark mode”** side by side (stacked on
phones), and it updates right away with what you change in any of the three tabs, before saving.

Each tab has **“Reset”**: it tells you what goes back to the default values, asks you to confirm and saves it right
away. It only resets that tab; anything you changed on the screen without saving is lost.

!!! note "Colors and typography are saved together"
    “Colors” and “Typography and shape” are one form: the **“Save colors and typography”** button saves both tabs. If
    you leave unsaved changes in the other one, it warns you next to the button. “Accessibility” is saved separately,
    with its own **“Save”**.

### Colors

Your organization's color is used only as an accent (buttons, selection, links and focus). The complementary colors
are used only in charts and data. Next to each color, **“Choose color”** opens the picker.

| Field | Required | Purpose |
| --- | --- | --- |
| Accent color | No | Your organization's main color. If it is too light or too dark to read, the panel adjusts it on its own, without changing its hue. |
| Dark mode accent | No | The accent when the panel is in dark mode. With **“Automatic”** the panel calculates it from the accent; below it you see the resulting color and its contrast. |
| Custom, Analogous, Triad, Complementary | No | How the chart colors are generated from the accent, or “Custom” to choose them yourself. Series 1 is always the accent. |
| Series 2, Series 3, Series 4, Series 5 | No | The colors of the chart series. |
| Use the accent, Dark neutral | No | The color of the browser's top bar on phones. |
| Accent 2, Accent 3 | No | Colors for occasional highlights: the panel does not use them on its own. |

### Typography and shape

![Typography and shape](img/configurations-appearance-typography.webp)

| Field | Required | Purpose |
| --- | --- | --- |
| Font | No | The panel's typeface: Atkinson Hyperlegible Next (the default), Red Hat Text, IBM Plex Sans or Onest. |
| Base size | No | The base size of the text. |
| Radius | No | How rounded the corners are across the whole panel. |

## Panel accessibility
<!-- ve: configurations-appearance-accessibility -->

It is the **“Accessibility”** tab of “Panel style” (the old “Accessibility” address leads to it). Here you choose how
the panel looks for whoever has not chosen their own in “My account” › “Preferences” (see “Your account”, “Your
preferences”). It has the same two cards as each person's preferences: **Appearance** and **Reading and motion**.

![Accessibility](img/configurations-appearance-accessibility.webp)

| Field | Required | Purpose |
| --- | --- | --- |
| Default color mode | No | In **Appearance**: **“Light”**, **“Dark”** or **“System”** (each person's device). |
| Default text size | No | **“Normal”**, **“Large”** or **“Larger”**. Large and Larger add 2 and 4 pixels to the “Base size” in “Typography and shape”. |
| Default text spacing | No | **“Normal”** or **“Wide”**: more space between lines, paragraphs, words and letters. |
| Default contrast | No | In **Reading and motion**: **“Automatic”** (it follows each person's device) or **“More contrast”**. |
| Default motion | No | **“Automatic”** or **“Reduce motion”**. |
| Default transparency | No | **“Like the system”** (it follows each person's device) or **“Reduce”**: solid backgrounds instead of translucent ones. |
| Default focus indicator | No | **“Normal”** or **“Strong”**: the outline of what is selected with the keyboard, more visible. |

!!! note "Whoever already chose their own"
    Whoever already chose any of this in their preferences keeps their own even if you change this. To go back to the
    site's, they choose **“Like the site”** in that group of their preferences.

## Identity and SEO
<!-- ve: configurations-appearance-seo -->

Go to **“Identity and SEO”**. It gathers the site data used by search engines and by the preview when a link is shared
on social networks and messaging apps.

![Identity and SEO](img/configurations-appearance-seo.webp)

It has two tabs, **Spanish** and **English**: you write the texts in each language. The image for sharing is changed
with **“Change image”**. At the end, **“Cancel”** or **“Save”**.

| Field | Required | Purpose |
| --- | --- | --- |
| Title | Yes | Name of the platform: it is shown in the browser tab, the headers and the emails (up to 60 characters). |
| Description | Yes | Short summary of the site that search engines show in their results (up to 160 characters). |
| Owner | Yes | Who owns the site. |
| Share title | No | Title of the card when the home page is shared. Empty: the usual title (up to 60 characters). |
| Share description | No | Description of the card when shared. Empty: the site description (up to 200 characters). |
| X account | No | Your organization's account on X. |
| Keywords | No | Keywords of the site. |

Next to each field, in small print, is the tag it generates on the page (`<title>`, `og:title`, `meta keywords`…), in case
you need to find it in the page's code.

- **“Use the site title”** and **“Use the site description”**: when checked, the title or description for sharing
  are the site's and the field is disabled showing them; when unchecked, you write your own.
- **What is not edited here, but is shown**: the **site name** (`og:site_name`, the Title), the **address** and the
  **canonical URL** of the home page, the **sharing language** (`og:locale`, which is changed in `config.php`), the **type**
  (website on the home page, article on publications) and the **author** (`meta author`, the Owner).

## Files for search engines
<!-- ve: configurations-appearance-site-files -->

Go to **“Files for search engines”**. This is what search engines and artificial intelligence read: the files
robots.txt, humans.txt and llms.txt. For each one, **“See the fixed configuration”** shows the part the system already
provides and that cannot be changed; in the **Add** field you write your own.

![Files for search engines](img/configurations-appearance-site-files.webp)

| Field | Required | Purpose |
| --- | --- | --- |
| Add (robots.txt) | No | For example, to keep search engines out of a page: `Disallow: /my-page/`. |
| Add (humans.txt) | No | The team, the acknowledgements or whatever you want to tell about the site. |
| Add (llms.txt) | No | Text in Markdown format that is added at the end of the summary the system already generates. |

When you finish, click **“Save”**.
