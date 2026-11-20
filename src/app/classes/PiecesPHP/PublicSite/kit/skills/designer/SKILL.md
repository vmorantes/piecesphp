---
name: designer
description: Ayuda a un diseñador (o a su agente) a cambiar el sitio público de un proyecto PiecesPHP sobre su carpeta de diseño, sin programar y sin romper nada: qué puede tocar libremente, qué son las marcas `<pcs-…>` y cómo comprobar antes de entregar. Úsala cuando se trabaje dentro de una carpeta con `site/`, `CHECK.html` y `pcs.json`, o cuando se diga «cambiar el diseño del sitio», «tocar la página», «preparar la entrega al programador».
---

# Trabajar en la carpeta de diseño de un sitio PiecesPHP

Esta carpeta es tu sitio. Lo que cambies aquí se ve igual en el proyecto real: se sube y se sirve, sin que nadie lo
vuelva a adaptar. Solo hay una regla: **las marcas `<pcs-…>` se respetan**.

## 1. Lo que puedes tocar con total libertad

- **Todo el diseño**: el CSS, el JavaScript visual, las imágenes, las fuentes (en `site/assets/`), la maquetación de
  cada página (cada `.html` de `site/`; la portada es `site/index.html`) y de cada pieza repetida (en `site/parts/`).
- **Páginas nuevas**: crea `site/<nombre>.html` copiando una existente. Enlázala con
  `<pcs-link page="<nombre>">texto</pcs-link>`.
- **Mover las marcas** de sitio dentro del diseño, y cambiar el HTML que hay alrededor y dentro de una `<template>`.

## 2. Las marcas, en una frase cada una

| Marca | Qué es |
| :-- | :-- |
| `<pcs-part name="menu">` | Aquí va la pieza `site/parts/menu.html`. Cámbiala una vez y cambia en todas las páginas. |
| `<pcs-link page="contact">` | Un enlace a otra página del sitio. El proyecto pone la dirección correcta y el idioma. |
| `<pcs-meta>` | El título y la descripción para buscadores y redes. Va en el `<head>`. |
| `<pcs-image brand="logo">` | El logo (u otra imagen de la marca). El proyecto pone el real, también en modo oscuro. |
| `<pcs-text key="…">` | Un texto que se traduce. Cambia el texto de dentro y el de `key` a la vez. |
| `<pcs-list resource="…">` | Una lista que llena el sistema. Diseña UN elemento dentro de la `<template>`; `{{campo}}` es el dato. |
| `<pcs-form action="…">` | Un formulario que recibe el sistema. Diséñalo como quieras; no cambies los `name` de sus campos. |

En la vista previa ves datos de ejemplo (los de `site/data/`). En el proyecto real salen los de verdad.

## 3. Lo que no se hace

- Cambiar el nombre de una marca o de sus atributos, o inventar marcas nuevas.
- Usar en una lista un `{{campo}}` que no está en `site/data/`, o editar `site/data/` (lo genera el proyecto).
- Poner otras marcas dentro de la `<template>` de una lista: en el sitio real no se resuelven.
- Escribir PHP, o meter el contenido del sistema (noticias, textos traducibles) escrito a mano en el HTML.
- Tocar `site/.pcs/`, `pcs.json` ni el andamiaje de agente (`AGENTS.md`, `CLAUDE.md`, `.agents/`, `.claude/`, `.cursor/`).

## 4. Antes de entregar

1. Abre `CHECK.html` (doble clic), elige esta carpeta y corrige lo que diga. Cada aviso dice el archivo, la línea y qué
   hacer. Si cambiaste algo de `site/parts/`, pulsa «Actualizar el paquete»: la vista previa lee las partes de un paquete
   (`site/parts/parts.js`), porque con doble clic el navegador no deja leer archivos sueltos.
2. Abre `site/index.html` y repasa cada página en escritorio y en móvil.
3. Si tu herramienta tiene subagentes, lanza `design-reviewer` antes de dar el trabajo por terminado.
4. Entrega la carpeta entera, tal cual (comprimida, si hace falta). El programador la importa con una orden y, si algo
   no estuviera bien, el proyecto no se rompe: sigue sirviendo la versión anterior.

## 5. Si necesitas un dato que no existe

Un dato nuevo (un campo más en una lista, un tipo de contenido que el sistema no tiene) es lo único que pide trabajo de
programación. Pídeselo al equipo del proyecto con la página y el campo; mientras, diseña con un dato de ejemplo parecido.
