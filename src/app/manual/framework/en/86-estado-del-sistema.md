---
titulo: System status
rutas: [system-status-alerts, system-status-health, system-status-maintenance, system-status-site-maintenance]
---

# System status

Here you see how the installation is doing and close the site for maintenance. To get there, use the gear button in the top bar,
**“System settings” › “System”**, and then the screen by its name.

## System alerts
<!-- ve: system-status-alerts -->

Go to **“System alerts”**. It shows what the system detects and is worth reviewing, and which alerts are shown.

![System alerts](img/system-status-alerts.webp)

The table has the columns **Severity**, **Message**, **Status**, **How to fix it** and **Actions**. An alert can
include in its row a button that takes you to where it is fixed, such as **“Mail”**. If there are no active alerts, the screen
says so with **“There are no active alerts”**, without the table.

## Installation health
<!-- ve: system-status-health -->

Go to **“Installation health”**. It is a reference screen: how this installation is doing in terms of versions,
environment, database, tasks and folders. Each group is a table with the columns **Item**, **Status**, **Value** and
**Origin**.

![Installation health](img/system-status-health.webp)

## Status and caches
<!-- ve: system-status-maintenance -->

Go to **“Status and caches”**. It shows how much space each cache of the installation takes, with its **“Empty”** button: broken shortcuts (those that
point to a file that no longer exists), all shortcuts, optimized images and the saved
publication listings. **“Renew”** changes the version of the static files (styles,
scripts and images of the pages). **“Delete everything temporary”** empties all the caches at once: the
site will be somewhat slower while they are created again.

![Status and caches](img/system-status-maintenance.webp)

## Site under maintenance
<!-- ve: system-status-site-maintenance -->

Go to **“Site under maintenance”**. It is used to close the public site for maintenance and decide who can
still get in. At the end, **“Cancel”** or **“Save”**.

![Site under maintenance](img/system-status-site-maintenance.webp)

| Field | Required | Purpose |
| --- | --- | --- |
| Site under maintenance | No | Turns maintenance on or off. When off, visitors see the site as usual. |
| Time to check again (Retry-After) | No | Seconds search engines are advised to wait before checking the site again. |
| Roles that can still get in | No | While maintenance lasts, they use the site and the panel as usual. If you choose none, only the main user gets in. |

!!! tip "You can always get back in"
    Panel login ALWAYS goes through, and so does the main user: that is the way to get back in and turn it off.
