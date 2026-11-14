---
titulo: Site emails
rutas: [mail-catalog-list, mail-catalog-send-test]
---

# Site emails

Here you see, one below the other, all the emails the site can send, each with sample data: how it arrives,
when it is sent, to whom and with what subject. Opening this screen does not send anything.

## Site emails
<!-- ve: mail-catalog-list -->

To get there, use the gear button in the top bar, **“System settings” › “System” › “Site
emails”**.

![Site emails](img/mail-catalog-list.webp)

Each email has its own card, with:

- its **name** and **when it is sent** (for example, when a code is requested to recover the password);
- **who** it is sent to and its **subject**;
- the **email as it arrives**, with made-up data: the names, codes and links in the sample belong to nobody.

On one side (on mobile, at the top, in a dropdown) is the list of emails grouped by module: one click takes you to its
card, and the list marks the one you are viewing.

Only the emails of the modules that are active in your installation appear. If your project adds its own emails and
declares them, they appear here next to the system's.

## Send a test
<!-- ve: mail-catalog-send-test -->

The **“Send test”** button on each card sends that email, with its sample data, to the address in the
**“Recipient of the test email”** field (by default, yours). It only appears when the email is held: with
**“Mail delivery”** set to **“Held: it never leaves this machine”**, or to **“Based on the environment”** in a
development installation (in **“System settings” › “System” › “Mail”**). Then it goes through the test server
configured there, not through real delivery. The subject is prefixed with **[PRUEBA]** and the send is recorded in the
action log, with the recipient. If the email is not held, the screen tells you why it cannot be sent.
