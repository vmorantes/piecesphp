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

Go to **“Artificial intelligence”**. It sets the provider, the model and the keys the platform uses for the
artificial intelligence features.

![Artificial intelligence](img/configurations-integrations-ai.webp)

| Field | Required | Purpose |
| --- | --- | --- |
| OpenAI Model | No | The OpenAI model that is used. |
| OpenAI API Key | No | The access key for OpenAI. |
| Mistral Model | No | The Mistral model that is used. |
| Mistral API Key | No | The access key for Mistral. |
| Enable AI translation | No | Turns translation with artificial intelligence on or off. |
| AI for translations | No | Which provider translates. |
| Who can translate with AI | No | Which user types can use it. The main user (Root) always can. |
| AI tokens limit | Yes | Informational only: the contracted quota, which the report uses to calculate the remaining tokens. It does not limit use. It is saved with its own **“Save”**. |

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
