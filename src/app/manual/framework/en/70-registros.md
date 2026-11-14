---
titulo: Logs
rutas: [logs-admin-index, actions-logs-admin-list, system-status-mail-log]
---

# Logs

Here you check what the system records: what happened, who did it and when. You only see the parts your account type
is allowed to see.

## Logs
<!-- ve: logs-admin-index -->

To get there, use the gear button in the top bar, **“System settings” › “Records and logs”**,
and then the log entry you are looking for: **“Errors”**, **“Login attempts”**, **“Login record”**,
**“Users without login”**, **“Sent mail”**, **“Tokens and codes”**, **“Scheduled tasks”** or
**“All logs”**. **“Open sessions”** is under **“User management”**.

![Logs](img/logs-admin-index.webp)

The screen is called **Logs** and has a row of tabs: **Errors**, **Sent mail**, **Access**,
**Open sessions**, **Tokens and codes** and **Scheduled tasks**. Each tab shows one type of record, and
starts with its title and a sentence saying what you see in it.

### The Errors tab

Errors are grouped by day, newest first, with the day as the heading. Each entry shows the
time, its severity, a reference, the error type, the message and the file where it happened.

- The button next to the reference, **“Copy reference”**, copies it so you can paste it wherever you need; for a
  moment it says **“Copied”**.
- **“Detail”** expands everything that was recorded about the error: the request, the extra data, the trace and the full
  message with its path. If you search for an error by its reference, the detail opens by itself.
- If no error has been recorded, the tab says so; if there are errors but none match the filters, it also says so.

You can **“Filter”** with these fields and **“Clear”** to see everything again:

| Field | Required | Purpose |
| --- | --- | --- |
| Reference | No | Find an error by its reference. |
| Text (message or file) | No | Search by part of the message or the file name. |
| Exception type | No | Keep only one type of error. |
| Level | No | Keep only one severity. |
| Day | No | See only one day. |
| Other files | No | Switch to another error log. |

At the end of the list, **“Older”** loads the next page.

### The Open sessions tab

The sessions that are still open, the one that started most recently first. You only see those of the accounts you have
authority over, and your own.

You can **“Filter”** with these fields and **“Clear”** to see everything again:

| Field | Required | Purpose |
| --- | --- | --- |
| User | No | Search by part of the username or email. |
| Started since | No | See only the sessions that started on that day or later. |

The table shows **User / email**, **Browser**, **IP**, **Start**, **Last activity**, **Expires** and
**Actions**. Next to the user, the **“Current”** tag marks the session you are looking from, and **“Other
sessions”**, your other sessions. In each row:

- the red button says **“Sign out”** if the session is yours and **“Force sign-out”** if it belongs to someone else; both close
  that session, after you confirm;
- **“Extend”** (the calendar button) lengthens its expiry. It only appears if your user type can do it on
  that account.

### The Tokens and codes tab

Two tables, each in its own block:

- **Current**: the access codes, links and secrets that still work, with **Type**, **User**, **Created**,
  **Expires** and **Actions**. There are always three actions, in the same order; the one that does not apply to that row appears
  dimmed. **“Reset two-factor”** (the person will log in with their password only until they turn it on again) and
  **“Invalidate now”** (the code stops working) are red; **“Show”**
  reveals the value of a code and is recorded. Each one asks you to confirm.
- **History**: what happened to them, with **Date**, **Event**, **User** and **Who**.

### The Access tab

It has three views: **Login attempts**, **Login record** and **Users without login**. Only users you have authority
over count.

In **Login attempts**:

- At the top you choose the **period**: **7**, **14** (the default), **30** or **90 days**, ending today. Next to it, the dates
  of the period and **“Export”**, which downloads the attempts for the period and the filter you have selected.
- Four figures for the period: **Total attempts**, with how much it changed compared with the previous period of the same length;
  **Successful** and **Failed**, with their percentage; and **Unique users**, the different users who tried to log in.
- The **Attempts per day** chart: failed attempts at the bottom and successful ones on top, one point per day, including days with no
  attempts.
- Above the table, **All**, **Successful** and **Failed**, each with its figure, filter the table without reloading the page.
- The table shows **Indicator**, **User Login**, **Information**, **Channel**, **IP** and **Date**. Failed
  attempts appear in red; a correct login says **“Successful login”**. The date says how long ago it was (“3 hours ago”);
  the exact date appears when you hover the mouse over it.

**Login record** and **Users without login** have their own period (7, 14, 30 or 90 days) and their own **“Export”**:

- **Login record**: how many users logged in during the period and how many did not, with their percentage of the total, and
  **Total hours**, the time on the platform since records began (it is not stored per day, so it does not change with the
  period). The table lists whoever logged in during the period: **ID**, **User**, **Name**, **Last access** (the most
  recent first) and **Time on platform** (“No record” if there is none).
- **Users without login**: how many did not log in during the period and how many did, with their percentage, and the table of those who did not
  log in: **ID**, **User** and **Name**. If everyone logged in, the screen says so.

### The other tabs

The **Scheduled tasks** tab exists on the screen, but this manual does not describe its content yet.

## Sent mail
<!-- ve: system-status-mail-log -->

This is the **Sent mail** tab of **Logs**. It lists the emails the platform sent or tried to send. The
body is stored encrypted; attachments are not stored.

![Sent mail](img/system-status-mail-log.webp)

At the top, the summary of the last 24 hours: how many were delivered, how many ended up in the test outbox and how many
did not arrive (in red if there are any).

| Column | What it shows |
| --- | --- |
| Date | When it was sent. |
| Recipients | Who it was addressed to. |
| Subject | The subject of the email. |
| Origin | Which part of the system sent it. |
| Delivery | How it was delivered. |
| Result | Whether it went well or failed. |
| Body | **“View body”** opens the email in a window; **“Close”** or the Esc key closes it. |

With **“Filter”** and **“Clear”** you narrow the list; **“Export current list”** downloads what you are viewing.

| Field | Required | Purpose |
| --- | --- | --- |
| From | No | First date of the range. |
| To | No | Last date of the range. |
| Result | No | Keep only those that went well or only those that failed. |
| Delivery | No | Keep only one way of delivery. |
| Origin | No | Search by the part of the system that sent it. |

## Activity log
<!-- ve: actions-logs-admin-list -->

To get there, use **“Latest movements”** in the side menu. It answers “who did what in the panel, and when”.

![Activity log](img/actions-logs-admin-list.webp)

The table shows: **#**, **Module**, **Event**, **User**, **IP**, **Date** and **Actions**. In each row,
**“View detail”** opens the detail of that movement.

You can **“Filter”** and **“Clear”**, and **“Export current list”** downloads what you are viewing.

| Field | Required | Purpose |
| --- | --- | --- |
| From | No | First date of the range. |
| To | No | Last date of the range. |
| Type | No | Keep only one type of movement. |
| User | No | See one person's movements. |
