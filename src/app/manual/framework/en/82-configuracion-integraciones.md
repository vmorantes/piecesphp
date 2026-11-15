---
titulo: Integrations settings
rutas: [configurations-integrations-ai, configurations-integrations-mail, configurations-integrations-osticket, configurations-integrations-scripts, helpers-system-generic-content-admin-forms-mapbox-keys]
---

# Integrations settings

Here you connect the platform with outside services. To get there, use the gear button in the top bar, **“System settings”**: Artificial
intelligence, Mail, OsTicket and Maps keys are in the **“System”** group, and Scripts is in **“SEO and
scripts”**. All of them end with **“Cancel”** and **“Save”**.

## Artificial intelligence
<!-- ve: configurations-integrations-ai -->

Go to **“Artificial intelligence”**. It is the control center for all of the platform's artificial intelligence: here you
turn it on or off, choose the providers and their keys, and decide, feature by feature, who uses it and how much it can
spend.

![Artificial intelligence](img/configurations-integrations-ai.webp)

**Artificial intelligence**

| Field | Required | Purpose |
| --- | --- | --- |
| AI enabled | No | The main switch. When off, no artificial intelligence feature calls a provider. |

**Providers and keys**

| Field | Required | Purpose |
| --- | --- | --- |
| OpenAI Model | No | The OpenAI model that is used. |
| OpenAI API Key | No | The access key for OpenAI. If the keys file defines it, the field cannot be changed and says so. |
| Mistral Model | No | The Mistral model that is used. |
| Mistral API Key | No | The access key for Mistral, with the same rule. |

**Features**: one section for each artificial intelligence feature the platform has (for example, “Translation with
AI”), with these fields:

| Field | Required | Purpose |
| --- | --- | --- |
| Enabled | No | Turns that feature on or off. |
| Who uses it | No | Which user types can use it. The main user (Root) always can. |
| AI for translations | No | Only in “Translation with AI”: which provider translates. |
| Quota (tokens) | No | How much the feature can spend in each period. **0 = no limit.** When the quota is reached, the feature stops calling the provider and says so, until the next period or until you reset the counter. |
| Period | No | How often the quota starts counting again: **Cumulative** (it never does), **Monthly**, **Every N days** or **Every N months**. With the last two, the **“N”** field appears. |

Below, the **“Usage this period”** line shows what has been spent (and the quota, if any) and since when. The **“Reset the
counter”** button asks for confirmation and makes the quota count from zero again; what was spent until then is kept for
the reports.

**Token quota**, the form at the bottom, with its own **“Save”**: the **“AI tokens limit”** (required) is only used by the
usage report and **does not limit anything**. The quota that limits is the one of each feature.

## Mail
<!-- ve: configurations-integrations-mail -->

Go to **“Mail”**. It sets where the platform's email comes from and how it is sent. It has two tabs:
**Settings** and **Tests**. **“Show password”** lets you see the password you type.

![Mail](img/configurations-integrations-mail.webp)

!!! warning "What decides whether mail goes out"
    What decides whether mail goes out is “Mail delivery”. When held, none goes out to the outside: everything goes to the
    test server.

| Field | Required | Purpose |
| --- | --- | --- |
| Host | Yes | The mail server. |
| Protocol | Yes | The connection encryption, chosen from a list: **STARTTLS** (the usual one with port 587), **SSL/TLS** (port 465) or **None**. If you see “(current value, not valid)”, choose one of the three before saving. |
| Automatic TLS | No | Checkbox to turn on automatic encryption. |
| Port | Yes | The server's port. |
| Email | No | The account used to send. |
| Password | No | That account's password. |
| Authenticate | No | Checkbox to indicate the server requires authentication. |
| Mail delivery | Yes | Decides whether mail goes out to the outside or is held. |
| Test server | Yes | Where emails go when they are held. |
| Test port | Yes | The test server's port. |
| Sender's name | No | The name the emails are sent under. |
| Recipient of the test email | No | Who the test email is sent to. |

To see what was sent, look at the **Sent mail** tab of **Logs**.

## OsTicket
<!-- ve: configurations-integrations-osticket -->

Go to **“OsTicket”**. It stores the connection with the osTicket support system.

![OsTicket](img/configurations-integrations-osticket.webp)

| Field | Required | Purpose |
| --- | --- | --- |
| URL | No | The address of the support system. |
| Key | No | The access key for that system. |

## Scripts
<!-- ve: configurations-integrations-scripts -->

Go to **“Scripts”**. Here you insert your own code into the public site, the panel or both: analytics, ad
pixels, chats and the like. **“Add script”** adds a new one to the list.

![Scripts](img/configurations-integrations-scripts.webp)

!!! warning "Paste only code you trust"
    What you write here runs as is in the browser of every visitor or user. A mistake can break the
    page or expose data; try it first with the script turned off.

## Maps keys
<!-- ve: helpers-system-generic-content-admin-forms-mapbox-keys -->

Go to **“Maps keys”**. It stores the access keys for the platform's map service.

![Maps keys](img/helpers-system-generic-content-admin-forms-mapbox-keys.webp)

| Field | Required | Purpose |
| --- | --- | --- |
| Local key | Yes | The map service key for the local installation. |
| Production key | Yes | The map service key for the production site. |
| Longitude | Yes | Initial position of the map. |
| Latitude | Yes | Initial position of the map. |
| Zoom | Yes | Initial zoom level of the map. |
