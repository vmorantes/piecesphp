---
titulo: System settings
rutas: [configurations-system-security, configurations-system-backups, configurations-system-routes]
---

# System settings

Security, backup and permission options. To get there, use the gear button in the top bar,
**“System settings” › “System”**, and then the screen by its name.

## Security
<!-- ve: configurations-system-security -->

Go to **“Security”**. It gathers the platform's security options. At the end, **“Cancel”** or
**“Save”**.

![Security](img/configurations-system-security.webp)

| Field | Required | Purpose |
| --- | --- | --- |
| Use user IP to encrypt session token | No | The session is tied to the IP address it was started from. If the user's IP changes (for example, when switching from wifi to mobile data), they will have to sign in again. |
| Hide the placeholder app_key warning in the panel | No | The warning appears when the key that encrypts the data (app_key) is still the sample one that comes with the installation. Hide it only if you know why it is kept that way. |
| Days without activity that close the session | Yes | How many days without activity close the session. With 0 it does not expire from inactivity. |
| Days since it was opened that close it (cap) | Yes | How many days, counted from when it was opened, close the session. With 0 there is no cap. |

### Trusted proxies

Only root sees it. You need it if the platform is behind an intermediary (a proxy, a load balancer or a content delivery
network such as Cloudflare): without it, every visit arrives with that intermediary's IP, and the login brakes would stop
everyone at once. With the list empty, the platform uses the connection IP, as always.

The section shows what the platform sees now: **“Connection IP (REMOTE_ADDR)”**, what the `X-Forwarded-For` and
`X-Real-IP` headers bring (“(not received)” if they don't come) and the **“Visitor IP the platform uses”**. If the
connection comes from a private or local address with `X-Forwarded-For` and the list is empty, a warning says so above:
there is an intermediary the platform does not know yet.

How to get the list:

- **The intermediary is on the same machine** (for example, an nginx in front of Apache, as HestiaCP usually sets up): open the panel and
  look at the “Connection IP”; if it is the server's or `127.0.0.1`/`::1` and `X-Forwarded-For` brings another one,
  click **“Add this IP to the list”** and then **“Save”**. The button is only available when the request comes with
  `X-Forwarded-For`; if the connection IP is public, it asks for confirmation, because it could be yours and not the
  intermediary's.
- **A content delivery network (CDN)**: the provider publishes its IP ranges (Cloudflare, at `cloudflare.com/ips`). Copy
  each range on its own line.
- **A load balancer or a proxy from your provider**: ask them for its IPs or ranges.

One IP or CIDR range per line. A range that covers every address (`0.0.0.0/0`, `::/0`) is not accepted: anyone could
then make up their IP. With “Use the user's IP to encrypt the session token” on, changing the list closes once the
session of whoever comes in through the proxies you add or remove. To empty the list, delete every line and save.

## Backups
<!-- ve: configurations-system-backups -->

Go to **“Backups”**. It sets how often the database is backed up, how many backups are kept and which tables
are saved without their rows. At the end, **“Cancel”** or **“Save”**.

![Backups](img/configurations-system-backups.webp)

With the saved policy, the screen calculates how many backups would be kept and how many would be deleted. Files
the system did not write are never touched.

| Field | Required | Purpose |
| --- | --- | --- |
| Back up the database automatically | No | When off, no new backup is made and none is deleted either. |
| How many minutes apart | Yes | The backup is made when the latest one reaches this age; 1440 is one day (from 60 to 10080). |
| Keep the latest | Yes | The most recent ones, always (from 1 to 1000). |
| Delete the backups that are left over | No | Deletion only happens after a successful backup, and only for what the system wrote. When off, all are kept. |
| One backup a day, for | Yes | Days with a backup that are kept; 0 turns this level off. |
| One a week, for | Yes | Weeks with a backup that are kept; 0 turns this level off. |
| One a month, for | Yes | Months with a backup that are kept; 0 turns this level off. |
| Tables exported without their rows | No | One checkbox per table. |

!!! warning "Tables without their rows"
    The table is saved with its structure, but empty: when restoring, its data does NOT come back. It is useful to keep
    sensitive data from leaving the server.

## Routes and permissions
<!-- ve: configurations-system-routes -->

Go to **“Routes and permissions”**. It is a reference table: which routes exist and which user types can use each
one. It has the columns **Name**, **Route**, **Controller** and **Roles with access**.

![Routes and permissions](img/configurations-system-routes.webp)
