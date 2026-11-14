---
titulo: Appearance settings
rutas: [configurations-appearance-brand-images, configurations-appearance-backgrounds, configurations-appearance-colors, configurations-appearance-seo, configurations-appearance-site-files]
---

# Appearance settings

Here you customize how the platform looks and how it is presented. To get there, use the gear button in the top bar, **“System settings”**. Brand
images, Login backgrounds and Colors are in the **“Appearance”** group; Identity and SEO and
Files for search engines are in **“SEO and scripts”**.

## Brand images
<!-- ve: configurations-appearance-brand-images -->

Go to **“Brand images”**. The platform is white label: these images identify your organization across
the whole system, from the login page and the side menu to the emails and the public site.

![Brand images](img/configurations-appearance-brand-images.webp)

To edit an image, click it. Each one also has a **“Change image”** button and an icon to download or view it. The images are:

- Logo
- Favicon, admin area
- Favicon, public area / website
- E-mails
- Horizontal banner
- Vertical banner

| Field | Required | Purpose |
| --- | --- | --- |
| Text above the sponsor | No | Optional: it appears in the menu footer, above the sponsor image. There is one field for each active language of the site. In the main language, if empty, nothing is shown; in the others, if empty, the main language's text is used. |
| Invert colors in dark mode | No | It is on some of the images. It needs a PNG with a transparent background: an opaque background would show as a white rectangle. |

When you finish, click **“Save”**.

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

## Colors
<!-- ve: configurations-appearance-colors -->

Go to **“Colors”**. Your organization's color is used only as an accent (buttons, selection, links and focus). The
complementary colors are used only in charts and data.

![Colors](img/configurations-appearance-colors.webp)

Next to each color, **“Choose color”** opens the picker. Below, the **“Live preview”** shows how
the accent and the palette will look in the panel. At the end, **“Cancel”** or **“Save”**.

| Field | Required | Purpose |
| --- | --- | --- |
| Accent color | No | Your organization's main color. |
| Custom, Analogous, Triad, Complementary | No | How the chart colors are generated from the accent, or “Custom” to choose them yourself. Series 1 is always the accent. |
| Series 2, Series 3, Series 4, Series 5 | No | The colors of the chart series. |
| Use the accent, Dark neutral | No | The color of the browser's top bar on phones. |
| Accent 2, Accent 3 | No | Colors for occasional highlights: the panel does not use them on its own. |
| Font | No | The panel's typeface. |
| Base size | No | The base size of the text. |
| Radius | No | How rounded the corners are across the whole panel. |

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
