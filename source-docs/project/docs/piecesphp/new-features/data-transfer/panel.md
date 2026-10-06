# Usar el panel

Para quien importa o exporta desde el panel, sin programar. El menú **«Importar y exportar»** lleva a una portada con
dos pestañas, **Importar** y **Exportar**, y en cada una una tabla con una fila por entidad (Usuarios…), su descripción
y sus acciones:

- **Importar:** «Importar» (el formulario), «Plantilla XLSX» y, si el importador admite CSV, «Plantilla CSV».
- **Exportar:** «Exportar» (el formulario) y, si el exportador no tiene filtros obligatorios, descargas directas «XLSX»
  y «CSV».

Solo salen las entidades que tu tipo de usuario puede usar. La pestaña abierta queda en la dirección (`#importar` o
`#exportar`), así que recargar no la pierde.

**Si eres root**, la tabla tiene además la columna **«Activo»**, con un interruptor por fila. Apagar una entidad la quita
de la portada para los demás y bloquea todas sus entradas, también desde la terminal; la fila apagada se ve en gris y se
vuelve a encender con el mismo interruptor.

## Importar

1. **Descarga la plantilla.** Es un XLSX con una columna por dato. Los encabezados obligatorios van **en negrita**, y si
   pasas el ratón por un encabezado verás su ayuda y un ejemplo.
2. **Llénala.** Una fila por registro. Puedes cambiar el orden de las columnas, y los encabezados se reconocen aunque
   cambies mayúsculas o uses el nombre alternativo que diga la ayuda. Una columna que el importador no conoce se ignora.
3. **Valídala antes, si el importador lo ofrece.** Marca **«Solo validar (no guarda nada)»** y sube el archivo: te dice
   todos los errores, fila a fila, sin guardar ni una.
4. **Súbela.** Se validan todas las filas y, **si una sola tiene errores, no se guarda ninguna**. El resultado dice cada
   error con su número de fila. Corrige y vuelve a subir el archivo entero.

Formatos: XLSX siempre; CSV solo si el importador lo admite (el formulario lo dice). Tamaño y número de filas máximos, en
el formulario (5 MB y 5.000 filas si el importador no dice otra cosa).

Las fechas de un XLSX se leen como fechas, y los números como números, sea cual sea la configuración regional del libro.

## Exportar

1. **Elige los filtros** (fechas, listas, sí/no…) y el formato, **XLSX** o **CSV**.
2. Si el exportador lo ofrece:
   - **Vista previa**: las primeras 20 filas, sin descargar nada;
   - **Columnas**: marca las que quieres y ordénalas con «Subir» y «Bajar»; sin marcar ninguna, salen todas;
   - **Filtros guardados**: guarda con un nombre la combinación que usas a menudo y cárgala después con un clic. Cada
     usuario ve solo los suyos.
3. **Exporta.** Si un filtro no es válido, el formulario lo dice y no se descarga nada.

**XLSX o CSV.** El XLSX es el informe completo: títulos, filtros aplicados, varias hojas, totales, formatos de número y
fecha, logo. El CSV es solo la tabla principal, sin nada de eso, y es mejor para exportaciones muy grandes o para
llevarlas a otro sistema.

**Enlazar una exportación.** La descarga admite los filtros en la URL, así que se puede guardar o compartir el enlace:
`…/data-transfer/export/<clave>/?created_from=2026-01-01&format=xlsx`.

**Seguridad de las hojas de cálculo.** Un dato que empiece por `=`, `+`, `-`, `@`, tabulador o retorno de carro nunca se
ejecuta como fórmula: en XLSX va como texto, y en CSV lleva un `'` delante (salvo un número). Al importar, ese `'` se
quita.

## Usuarios

**Importar usuarios:**

- Columnas: Usuario, Correo, Primer nombre y Primer apellido (obligatorias); Segundo nombre, Segundo apellido,
  Contraseña, Tipo y Organización (opcionales). Admite XLSX y CSV.
- **Solo se importan usuarios generales** por defecto, y nadie importa un tipo de prioridad igual o mayor que la suya.
  Ni el `id` ni un tipo de administrador se toman del archivo.
- **Organización**: si la fila no la trae, la de quien importa; si quien importa tampoco tiene, **la fila falla**: no
  se cae en ninguna organización «global», así que hay que poner el código (o el id) de una organización. Solo se
  asigna a los tipos que la requieren.
- **Duplicados**: un usuario o correo que ya existe, o repetido dentro del archivo, es un error de fila.
- **Contraseñas**: si una fila no trae contraseña, se genera. Las generadas se entregan **una sola vez**, en unas
  **fichas imprimibles** (nombre completo, usuario, contraseña y enlace de acceso), pensadas para quien reparte
  credenciales, como una escuela. **Si no las descargas en ese momento, se pierden**: no se guardan en ningún sitio.

**Exportar usuarios:** un XLSX o CSV con las mismas columnas que el importador (sin contraseña), así que su archivo se
puede volver a importar. El formulario lo dice con un enlace al importador.

## Desde la terminal

Importar sin panel, por ejemplo en un servidor:

```bash
bin/cli data-transfer-import definition=users file=/ruta/usuarios.xlsx as-user=<id> credentials-out=/fuera/del/proyecto/credenciales.html
```

- `as-user`: el usuario con cuyos permisos se importa.
- `credentials-out`: obligatorio si el importador puede generar credenciales. Tiene que estar **fuera del repositorio**
  y no existir; se escribe con permisos `0600`, y en pantalla solo sale su ruta.
- `dry-run=yes`: solo valida. No guarda nada, no exige `credentials-out` ni escribe ningún archivo.
- Sale con 0 si todo fue bien y con 1 si no.
