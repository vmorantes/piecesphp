# Íconos SVG del tema PiecesPHP

Hoja **sin compilar** para usar SVG propios como íconos de Fomantic. No hace falta recompilar el
tema para añadir íconos: se edita `svg-icons.css` y se copia el `.svg` a esta carpeta.

## Uso

1. Carga la hoja después de `semantic.css`:

   ```html
   <link rel="stylesheet" href="semantic.min.css">
   <link rel="stylesheet" href="themes/piecesphp/assets/svg-icons/svg-icons.css">
   ```

2. Usa el ícono con el marcado normal de Fomantic, añadiendo `svg`:

   ```html
   <i class="usuarios svg icon" aria-hidden="true"></i>
   <a class="item"><i class="reportes svg icon" aria-hidden="true"></i>Reportes</a>
   <i class="large circular primary inicio svg icon"></i>
   ```

   Hereda el color del texto (el ítem activo de un menú lo pinta con el acento) y el tamaño de
   un ícono normal.

3. Un ícono puntual sin tocar la hoja:

   ```html
   <i class="svg icon" style="--icon: url(/img/mi-icono.svg)" aria-hidden="true"></i>
   ```

   En línea, la ruta debe ser **absoluta o desde la raíz** (`/img/…`, `https://…`): una `url()`
   relativa dentro de `--icon` se resuelve respecto a `semantic.css`, no a la página.

## Añadir un ícono

1. Copia el archivo a esta carpeta (p. ej. `facturas.svg`).
2. Añade una línea a `svg-icons.css`:

   ```css
   i.facturas.svg.icon.icon::before { -webkit-mask-image: url("facturas.svg"); mask-image: url("facturas.svg"); }
   ```

   La ruta es relativa a esta hoja. La regla repite `.icon` para ganar a la del framework
   aunque el orden de carga cambie.

3. Úsalo: `<i class="facturas svg icon"></i>`.

## Requisitos del SVG

- **Monocolor.** Se usa como máscara: cuenta la forma, no el color (da igual el `fill` del
  archivo). Para imágenes multicolor (logos, fotos) usa `<img>`.
- **Con `viewBox`** y sin márgenes de sobra: el dibujo se ajusta a la caja del ícono (`contain`).
- **Nombres en minúsculas y con guiones.** Si el nombre coincide con un ícono de la fuente
  (`users`, `home`…), gana el SVG; usa un prefijo de proyecto si quieres evitar confusiones
  (`i.mp-users.svg.icon`).

## Incluidos

| Clase | Archivo | Origen |
|---|---|---|
| `panel` | `panel.svg` | Gráfico fijo del shell (pestaña de contraer el menú lateral) |
| `inicio`, `usuarios`, `contenidos`, `reportes`, `documentos`, `calendario` | `*.svg` | Ejemplos del menú lateral del diseño (Font Awesome 5.15.4 Free, CC BY 4.0); cada proyecto los sustituye |
