---
titulo: Locations
rutas: [locations, locations-countries-list, locations-countries-forms-add, locations-countries-forms-edit, locations-states-list, locations-states-forms-add, locations-states-forms-edit, locations-cities-list, locations-cities-forms-add, locations-cities-forms-edit, locations-points-list, locations-points-forms-add, locations-points-forms-edit]
---

# Locations

Here you maintain the places the rest of the panel uses: countries, departments, cities and coordinates.

## How to get there
<!-- ve: locations -->

From the side menu: **“Locations”**. The section's start screen offers a list of countries, of departments, of cities and of coordinates.

![How to get there](img/locations.webp)

Every list has the same shape: a table with an **“Edit”** button per row, a main button to add and **“Export current list”**. In the forms, **“Cancel”** goes back to the list and **“Save”** saves.

## Countries
<!-- ve: locations-countries-list -->

Columns: ID, code, name, region, active or inactive, and actions. To create one, click **“Add country”**.

![Countries](img/locations-countries-list.webp)

| Field | Required | What it is for |
| :-- | :-- | :-- |
| Name | Yes | |
| Code | No | |
| Phone code | No | For example, +57 |
| Demonym | No | Offered as the nationality in profiles. For example, Colombian |
| Region | No | You can type the region and press ENTER if you don't find it in the list |
| Active/Inactive | Yes | |

## Departments
<!-- ve: locations-states-list -->

Columns: ID, code, name, country, active or inactive, and actions. To create one, click **“Add department”**.

![Departments](img/locations-states-list.webp)

| Field | Required | What it is for |
| :-- | :-- | :-- |
| Country | Yes | |
| Name | Yes | |
| Code | No | |
| Active/Inactive | Yes | |

## Cities
<!-- ve: locations-cities-list -->

Columns: ID, code, name, country, department, active or inactive, and actions. To create one, click **“Add city”**.

![Cities](img/locations-cities-list.webp)

| Field | Required | What it is for |
| :-- | :-- | :-- |
| Country | Yes | |
| Department | Yes | |
| Name | Yes | |
| Code | No | |
| Active/Inactive | Yes | |

## Coordinates
<!-- ve: locations-points-list -->

Columns: ID, name, country, department, city, address, coordinates, active or inactive, and actions. To create one, click **“Add coordinate”**.

![Coordinates](img/locations-points-list.webp)

| Field | Required | What it is for |
| :-- | :-- | :-- |
| Country | Yes | |
| Department | Yes | |
| City | Yes | |
| Address | Yes | Locality, hamlet, neighborhood, etc. |
| Name | Yes | |
| Active/Inactive | Yes | |

This form includes a map. With the map buttons you can switch between **“Satellite view”** and **“Drawing view”**, **“Center”** the map and zoom it in or out.

## Add and edit
<!-- ve: locations-countries-forms-add, locations-countries-forms-edit, locations-states-forms-add, locations-states-forms-edit, locations-cities-forms-add, locations-cities-forms-edit, locations-points-forms-add, locations-points-forms-edit -->

For each type of location, the creation form and the edit form have the same fields. Only the title changes (“Add…” or “Edit…”).

![Add and edit](img/locations-countries-forms-add.webp)
