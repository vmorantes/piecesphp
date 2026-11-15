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
turn it on or off, save your providers with their keys, and decide, feature by feature, which provider and model it
works with, who uses it and how much it can spend.

![Artificial intelligence](img/configurations-integrations-ai.webp)

**Artificial intelligence**

| Field | Required | Purpose |
| --- | --- | --- |
| AI enabled | No | The main switch. When off, no artificial intelligence feature calls a provider. |

**Providers**: each artificial intelligence provider you use, with its nickname and its key. Each feature chooses its own
further down. You can have several of the same provider (for example, two OpenAI ones with keys for different purposes).

- **OpenAI**, **Mistral** and **Groq**, at the top, are the ones from the previous configuration: translation uses the first two. In each one
  you choose its **model** and type its **key**; if the keys file defines the key, it cannot be changed and it says so; if
  it is saved, **“Remove the saved one”** deletes it. **Groq**, likewise, is the speech to text one (its key and its model).
- **“+ Add provider”** adds a new one. With none, the section says “There are no saved providers yet”.

Each added provider has these fields:

| Field | Required | Purpose |
| --- | --- | --- |
| Type | Yes | The provider: OpenAI, Mistral, Groq, DeepSeek, xAI, OpenRouter or **Custom** (any other that speaks the OpenAI format). |
| Nickname | Yes | How you recognize it in the features, for example “Main OpenAI”. It cannot be repeated. |
| Key | No | The access key. **Once saved it is never shown again**: the field says “Saved” with its last four characters. Empty when saving, it keeps the one there was; **“Remove the saved one”** deletes it. |
| Base URL | Yes, for Custom | The service address: `https://`, or `http://localhost` or `http://127.0.0.1` for a model installed on your own computer. |
| Key header | No, for Custom | If the provider wants the key in another header. Empty, it is sent as `Authorization: Bearer`. |
| Quota (tokens) | No | How much that provider can spend in each period, **adding up all the features that use it**. **0 = no limit.** |
| Period | No | As in the features: Cumulative, Monthly, Every N days or Every N months. |
| Model to test with | No | The model “Test” uses. |

Below each one, its **usage this period**. **“Test”** checks the key and the model with a minimal call, without saving
anything or spending quota, and says next to it whether it works or why not (the key rejected, a model that does not exist,
no connection). **“Remove”** takes it out when saving; if a feature is using it, saving is refused and it tells you which
one: first choose another provider for that feature.

**Features**: one section for each artificial intelligence feature the platform has (for example, “Translation with
AI”), with these fields:

| Field | Required | Purpose |
| --- | --- | --- |
| Enabled | No | Turns that feature on or off. |
| Who uses it | No | Which user types can use it. The main user (Root) always can. |
| AI for translations | No | Only in “Translation with AI”: which provider translates (OpenAI or Mistral, the ones above). |
| Provider | No | Which of your providers the feature uses. If none is chosen, speech to text uses Groq, and the rest, the “AI for translations” one. |
| Model | Yes, if you choose a provider | The model of that provider the feature uses. |
| Quota (tokens) | No | How much the feature can spend in each period. **0 = no limit.** When the quota is reached, the feature stops calling the provider and says so, until the next period or until you reset the counter. |
| Period | No | How often the quota starts counting again: **Cumulative** (it never does), **Monthly**, **Every N days** or **Every N months**. With the last two, the **“N”** field appears. |

Below, the **“Usage this period”** line shows what has been spent (and the quota, if any) and since when. The **“Reset the
counter”** button asks for confirmation and makes the quota count from zero again; what was spent until then is kept for
the reports.

**“Speech to text”** turns a recording into text, for the features that need it. **It comes switched off**; with no provider chosen it uses the Groq key and model.

**“Panel assistant”** is the feature for the panel's chat assistant: it answers questions about the platform (where
something is done, what you have pending) using only what the person asking can see. **It comes switched off**, with a quota of
200,000 tokens a month: turn it on and choose who uses it before anyone sees it. For now only this section exists: the chat window arrives in an upcoming
version.

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
