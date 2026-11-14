---
titulo: Users
rutas: [users-list, users-selection-create, users-form-create, users-form-edit]
---

# Users

Here you see who signs in to the panel, with what type of user and in what status, and you create or edit accounts.

## The users list
<!-- ve: users-list -->

You get there with the gear button in the top bar: **“System settings” › “User management” › “Users management”**.

![The users list](img/users-list.webp)

The list is divided into tabs by account status, and each one shows next to it how many accounts it has. It opens on **Active**:

- **Active**
- **Inactive**
- **All**
- **Blocked by unsuccessful attempts**
- **Pending approval**
- **Not approved**

The counters only count the accounts you can see: if you manage an organization, those of yours.

Each tab shows the accounts as cards: name, status (active or inactive), type, username, email and organization. At the top you choose how many to see in **“Show … items”** (12, 24 or 48) and search in **“Search:”**, which finds by name (also the full name, with first names and surnames), username, email or organization. At the bottom, the line **“Viewing from … to … of … elements”** and the pagination. The pencil button on each card, **“Edit”**, opens that account.

## Add a user
<!-- ve: users-selection-create, users-form-create -->

Click **“Add user”**. First the system takes you to choose the type of user, among those you can create; once you choose it, the creation form for that type opens.

![Add a user](img/users-selection-create.webp)

## Add and edit
<!-- ve: users-form-edit, users-form-create -->

The creation form and the edit form are almost the same. When editing, the title shows the person's name and type, and three tabs appear: **Account**, **Profile picture** and **Sessions**. When creating there are no tabs: only the form.

![Add and edit](img/users-form-edit.webp)

| Field | Required | What it is for |
| :-- | :-- | :-- |
| Base organization | Yes | The organization the user belongs to |
| First name | Yes | |
| Second name | No | |
| Surname | Yes | |
| Second surname | No | |
| Username | Yes | The name the person signs in with |
| E-mail | Yes | The person's email address |
| Password (when editing, “New password”) | When creating, yes; when editing, no | The access password. When editing, if you leave it empty it does not change |
| Confirm password | Same as the password | The password is repeated to avoid mistakes |
| Status | Yes | Whether the account is active or not |

Next to each password field there is a **“Show password”** button to see what you type. When you are done, click **“Save”**.

### Profile picture

If the account does not have a picture yet, the card says **“No photo”**. With **“Change image”** you choose one from your computer.

### Sessions

The **Sessions** tab only appears if you can close that account's sessions. At the top is **“Close all their sessions”**: it signs the person out of all their devices at once, and they will have to sign in again on each one. It asks you to confirm.

Below, the table of their open sessions, with pagination and a search by browser or IP: **Browser**, **IP**, **Start**, **Last activity**, **Expires** and **Actions**. In each row:

- the red button, **“Force sign-out”**, closes that session once you confirm it; if the session is yours, the button says **“Sign out”**;
- **“Extend”** (the calendar button, next to the red one) extends its expiry. It only appears if your user type can
  do so on that account.

It is the same table as **Logs › Open sessions**, filtered by this person.
