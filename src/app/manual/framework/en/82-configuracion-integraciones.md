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

The screen has four tabs: **General**, **Providers**, **Features** and **Log**. A single **“Save”**, below, saves what is
in the first three; the token quota in **General** has its own, and **Log** is only for looking up. If something is not valid in another tab, the screen jumps to it and
points it out.

**General**

| Field | Required | Purpose |
| --- | --- | --- |
| AI enabled | No | The main switch. When off, no artificial intelligence feature calls a provider. |

**Providers**, the second tab: each artificial intelligence provider you use, with its nickname and its key. Each feature chooses its own
further down. You can have several of the same provider (for example, two OpenAI ones with keys for different purposes).

- If the platform already used **OpenAI**, **Mistral** or **Groq**, they appear here as normal providers, with their usual
  key and model. They are edited and removed like any other. Until you save this screen for the first time, everything
  works exactly as before; when you save, they are stored like the rest.
- **“+ Add provider”** adds a new one. With none, the section says “There are no saved providers yet”.

Each provider has these fields:

| Field | Required | Purpose |
| --- | --- | --- |
| Type | Yes | The provider: OpenAI, Mistral, Groq, DeepSeek, xAI, OpenRouter or **Custom** (any other that speaks the OpenAI format). |
| Nickname | Yes | How you recognize it in the features, for example “Main OpenAI”. It cannot be repeated. |
| Key source | No | **“Write it here”** (the default) or **“From the keys file”**, if the key is stored on the server, in the keys folder. |
| Key | No | With “Write it here”: the access key. **Once saved it is never shown again**: the field says “Saved” with its last four characters. Empty when saving, it keeps the one there was; **“Remove the saved one”** deletes it. If you change the type, the base URL or the header, you have to type it again. |
| Name in the file | Yes, with “From the keys file” | The exact name of the key's file: the type's own (`openai`, `mistral` or `groq`, only for those three types) or `ai-<type>-…`, for example `ai-openai-marketing` or `ai-custom-local`. “Test” with a key from the file needs the provider saved first. Next to it, it says **“It is in the file”** or **“It is not in the file”**, without showing the key. |
| Base URL | Yes, for Custom | The service address: `https://`, or `http://localhost` or `http://127.0.0.1` for a model installed on your own computer. |
| Key header | No, for Custom | If the provider wants the key in another header. Empty, it is sent as `Authorization: Bearer`. |
| Quota (tokens) | No | How much that provider can spend in each period, **adding up all the features that use it**. **0 = no limit.** |
| Period | No | As in the features: Cumulative, Monthly, Every N days or Every N months. |
| Model to test with | No | The model “Test” uses. It is chosen from the list, like the features' one. |
| Prices | No | Only on a saved provider: what each model costs, in **dollars per million tokens** of **input** and **output**, as the provider publishes it. They are used to estimate the cost in **Log**; **they do not limit anything**. **“+ Add price”** adds another model. |

**The list of models** is the one the provider itself gives. **“Refresh models”** asks for it with its key and remembers
it; next to it, it says where it comes from: “Provider list, refreshed on…” or, if it has not been asked for yet or it
could not be, “Fallback list”, with the date of the documentation it comes from. If the model you want is not there,
choose **“Other…”** and type it. A provider you just added only offers “Other…” until you save it.

Below each one, its **usage this period**. **“Test”** checks the key and the model with a minimal call, without saving
anything or spending quota, and says next to it whether it works or why not (the key rejected, a model that does not exist,
no connection). **“Remove”** takes it out when saving; if a feature is using it, saving is refused and it tells you which
one: first choose another provider for that feature.

**Features**, the third tab: one section for each artificial intelligence feature the platform has (for example, “Translation with
AI”), with these fields:

| Field | Required | Purpose |
| --- | --- | --- |
| Enabled | No | Turns that feature on or off. |
| Who uses it | No | Which user types can use it. The main user (Root) always can. |
| Provider | No | Which of your providers the feature uses. **With no provider, the feature is not available.** |
| Model | Yes, if you choose a provider | The model of that provider the feature uses, from its list or with “Other…”. |
| Quota (tokens) | No | How much the feature can spend in each period. **0 = no limit.** When the quota is reached, the feature stops calling the provider and says so, until the next period or until you reset the counter. |
| Period | No | How often the quota starts counting again: **Cumulative** (it never does), **Monthly**, **Every N days** or **Every N months**. With the last two, the **“N”** field appears. |

Below, the **“Usage this period”** line shows what has been spent (and the quota, if any) and since when. The **“Reset the
counter”** button asks for confirmation and makes the quota count from zero again; what was spent until then is kept for
the reports.

**“Speech to text”** turns a recording into text, for the features that need it. **It comes switched off**, and like the others it needs its provider and its model (Groq or OpenAI can turn speech into text).

**“Panel assistant”** is the feature for the panel's chat assistant: it answers questions about the platform (where
something is done, what you have pending) using only what the person asking can see. **It comes switched off**, with a quota of
200,000 tokens a month: turn it on and choose who uses it before anyone sees it. Also choose its **provider** and its **model** (one
that supports tools) and the **“Window position”**: **“Right”** (the default), **“Left”**, **“Floating bottom right”** or
**“Floating bottom left”**; on a phone it always takes the whole screen. Whoever can use it sees the assistant button next to the
bell (see “Your account”, “The assistant”).

**Log**, the fourth tab: what artificial intelligence has spent, to look it up.

- **“Group by”**: **Feature**, **Provider** or **Model**. **“Since”**: the last 3, 6 or 12 months, or **All**.
- Each row gives the **period**, the **calls**, the **input tokens**, **output tokens** and the **total**, and the
  **approximate cost in dollars**. Below, the **Total** row.
- The cost comes from the **prices** you set on each model in **Providers**, or from the one the provider itself returns
  (OpenRouter does). Without a price it shows **“—”**; if only part has a price, the total says **“partial”**.
- **It is an estimate**: what the provider charges may be something else.
- Features with a **cumulative** period have a single row, which is not split by month.

**Token quota**, in **General**, with its own **“Save”**: the **“AI tokens limit”** (required) is only used by the
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
