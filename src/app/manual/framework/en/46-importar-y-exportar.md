---
titulo: Import and export
rutas: [data-transfer-hub, data-transfer-import-users, data-transfer-export-users-form]
---

# Import and export

Load data from a spreadsheet, or download the system's data in the format you choose.

## The main screen
<!-- ve: data-transfer-hub -->

How to get there: **“Import and export”**, in the side menu.

![The main screen](img/data-transfer-hub.webp)

It has two tabs:

- **“Import”**: a table with **Entity**, **Description**, **Actions** and **Active**. In each row you can **“Import”**, or download the template with **“XLSX template”** or **“CSV template”**.
- **“Export”**: the same table. In each row you can **“Export”**, or download with **“Download XLSX”** or **“Download CSV”**.

## Import users
<!-- ve: data-transfer-import-users -->

How to get there: in the **“Import”** tab, click **“Import”** in the users row; or use the gear button in the top bar, **“System settings” › “User management” › “Users import”**.

![Import users](img/data-transfer-import-users.webp)

The system accepts xlsx or csv files of up to 5 MB and 5000 rows.

!!! warning "All or nothing"
    If a row has errors, none is saved.

At the top, a notice: **keep the template headings**. Columns are recognized by their heading (or by the other names in the
table below), so the order does not matter; if you change a heading, that column will not be read.

There are two steps:

1. **Download the template**: the file `plantilla-users.xlsx`. Click **“Download template”** and fill it in without changing its
   headings.
2. **Upload the completed template**: in **“Template file”** (required) click **“Browse”**, choose the
   file and click **“Import”**. If you click “Import” without a file, the field asks you for one.

When it finishes, the **Import result** appears:

- the file and how many rows were read;
- a message: **“All N rows were imported”** if they were all valid, or **“No rows were imported”** with how many have
  errors; if the file has no rows with data or could not be read (for example, a required column is missing), it tells
  you so;
- the **valid** rows and, if there are any, the rows **with errors**, in two labels;
- if there are errors, a **Row · Error** table. The row is the one in the file: row 1 is the headings row.

The template columns:

| Column | Required | Also recognized as |
| --- | --- | --- |
| User | Yes | user, nombre de usuario |
| Email | Yes | correo electrónico, e-mail |
| First name | Yes | nombre |
| Middle name | No | |
| First last name | Yes | apellido |
| Second last name | No | |
| Password | No | |
| Type | No | |
| Organization | No | código de organización, codigo de organizacion |

## Export users
<!-- ve: data-transfer-export-users-form -->

How to get there: in the **“Export”** tab, click **“Export”** in the users row.

![Export users](img/data-transfer-export-users-form.webp)

Choose the **Format**, XLSX (the default) or CSV, and click **“Download”**. The **“Download XLSX”** and
**“Download CSV”** shortcuts in the tab do the same without going through this screen.

The file is named `users-` followed by the date and time, and has one row per user with these columns: User,
Email, First name, Middle name, First last name, Second last name, Type and Organization. It can be imported
again with **“Users import”**; it does not include passwords.
