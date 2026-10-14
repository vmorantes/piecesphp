# Escapar lo que escribe el usuario, y sanear lo que sube

Dos funciones globales del núcleo, en `src/app/core/Utilities.php`. Cubren dos de los sitios por los que el texto de un
usuario entra en una página: **lo que se pinta** y **el nombre de lo que se sube**.

Las dos están disponibles en cualquier vista y en cualquier controlador sin importar nada: `Utilities.php` se carga
siempre.

---

## `escape_html()` — para todo lo que se pinta

```php
escape_html(mixed $value): string
```

Convierte en entidades los caracteres con los que se puede cerrar un atributo o abrir una etiqueta. Acepta cualquier
valor —no solo cadenas— y devuelve siempre una cadena, así que se puede usar sobre un valor que puede venir `null`.

### Cuándo se usa

**Siempre que un valor que no escribió el programador acabe en el HTML.** En un atributo, en un nodo de texto, en el
texto de un enlace. Da igual que el valor venga de la base de datos, de la petición o de un archivo: lo que decide no
es de dónde salió, sino **dónde acaba**.

```php
<!-- Un nodo de texto -->
<div class="title"><?= escape_html($attachment->getDisplayName()); ?></div>

<!-- Un atributo con comilla doble -->
<div data-image="<?= escape_html($element->getLangData($lang, 'logo', false, '')); ?>" class="image"></div>

<!-- Un atributo que se construye en PHP: el escape va sobre la variable -->
<?php $attr = $isImage ? 'data-image' : 'data-file'; ?>
<div <?= $attr; ?>="<?= escape_html($fileLocation); ?>"></div>
```

En un listado que se carga por JSON, el HTML de la celda lo pone el programador y **solo se escapa la parte variable**:

```php
$columns[] = "<a class='ui button icon blue' href='#' data-image-preview='" . escape_html($e->desktopImage) . "'>"
    . "<i class='icon image'></i></a>";
```

### Cuándo NO se usa

- **En un campo de texto enriquecido** (lo que sale de CKEditor). Ese contenido es HTML a propósito, y escaparlo lo
  rompe: se vería el código en vez del formato. Quien tiene permiso para escribirlo es de confianza. Es una decisión del
  dueño del producto, no un olvido.
- **Sobre una celda entera** de un listado, ni en bloque dentro de `DataTablesHelper`: las tablas pintan HTML a
  propósito y se escaparían los botones y las etiquetas de estado.

### El error que parece correcto y no lo es

**Escapar en PHP no sirve si un JavaScript vuelve a montar el valor como HTML.** Si la vista escapa un atributo pero el
JS lo lee y lo interpola en una cadena de HTML, la entidad se decodifica al leerla y el valor vuelve a ser código:

```javascript
// MAL: lo que escapó el PHP vuelve a ser HTML aquí
container.html('<img src="' + element.attr('data-image') + '">');

// BIEN: el valor va como valor, nunca como HTML
container.find('img').attr('src', element.attr('data-image'));
container.find('.name').text(element.attr('data-file-name'));
```

Y **dentro de CSS escapar no protege**: si el valor acaba en un `style` o en una propiedad de fondo, hay que validar que
la ruta es segura y, si no lo es, no pintarla.

---

## `safe_upload_name()` — para el nombre de lo que se sube

```php
safe_upload_name(string $name): string
```

Reduce un nombre de archivo a **letras, números, `_` y `-`**, lo recorta a **100 bytes** y devuelve uno generado
(`file_<id>`) si no queda nada. **No deja puntos**: la extensión la pone el framework al mover el archivo —la del
archivo original, una vez que el validador lo ha aceptado contra los tipos permitidos—, y así no se puede formar un
nombre como `shell.php.png`.

### Dónde va, exactamente

El nombre lo elige **quien sube**, y acaba en tres sitios: la ruta que se guarda en la base, el nombre en disco y la
vista previa de la pantalla. Por eso se sanea **después** de quitar la extensión original y **antes** de mover el
archivo:

```php
if ($setNameByInput && $valid) {

    $name = $_FILES[$nameOnFiles]['name'];
    $lastPointIndex = mb_strrpos($name, '.');

    if ($lastPointIndex !== false) {
        $name = mb_substr($name, 0, $lastPointIndex);
    }

    $name = safe_upload_name($name);

}
```

Ese orden es el contrato, y la suite de pruebas lo comprueba: corte de la extensión → saneo → mover.

### Qué NO hace

- **No valida el tipo del archivo.** Eso lo hace el manejador de subida con su lista de tipos permitidos, y sigue siendo
  obligatorio.
- **No renombra lo ya subido.** Un archivo que entró antes conserva su nombre; si uno tiene un nombre extraño, hay que
  volver a subirlo.

---

## Si clonas el framework y añades tu módulo

- Toda vista tuya que pinte un valor de la base o de la petición **pasa por `escape_html()`**, y si un JavaScript tuyo
  lee ese valor, lo monta con `.attr()` y `.text()`, nunca con `.html()`.
- Todo punto de subida tuyo llama a `safe_upload_name()` en el orden de arriba.
- Las dos cosas están vigiladas, **en el framework**, por la suite `unit-tests:core/user-data-escapes`:

```bash
bin/cli unit-tests:core/user-data-escapes --local
```

**Y aquí está el límite exacto, que conviene saber antes de confiarse, porque no es el mismo para las dos cosas:**

- **Las vistas que tú añadas, no las mira.** La suite comprueba los 15 sitios escapados de las vistas del framework,
  nombrándolos uno a uno: si tocas una de ellas y su cifra deja de cuadrar, se pone en rojo; si añades una vista nueva,
  no se enterará nadie. Esa la cubre tu propia prueba.
- **Las subidas, sí.** La suite **censa todo `src/app`** y se pone en rojo si una lectura del nombre de un archivo
  subido llega al disco **sin** `safe_upload_name()`, salvo las que declara exentas **con su motivo**. Así que si añades
  un punto de subida y te olvidas del saneo, la prueba te lo dice.

La diferencia no es capricho: el saneo de subidas tiene una forma reconocible que se puede censar, y pintar un valor en
una vista tiene demasiadas.

## El gestor de archivos es OTRA puerta, y no pasa por `safe_upload_name()`

Todo lo de arriba vale para los puntos de subida que escribe el framework: un formulario tuyo recibe un `$_FILES`, le
pide un nombre seguro y lo mueve. **El gestor de archivos del panel no funciona así.** Es elFinder, una biblioteca de
terceros que recibe la subida, decide el nombre y la escribe sin pasar por nuestro código, así que `safe_upload_name()`
no lo toca y la suite de arriba **no lo cubre**.

Su puerta es otra, y está donde se configura cada volumen:
`src/app/classes/PiecesPHP/FileManager/Controllers/FileManagerController.php`.

### Por qué se filtra por NOMBRE y no por tipo

Es la decisión que más fácil se toma mal, así que va con su motivo.

**Lo que decide si un servidor ejecuta un archivo es su extensión, no su contenido.** Un `shell.php` cuyos primeros
bytes son la cabecera de un GIF pasa cualquier comprobación de contenido, y Apache lo ejecuta igual porque mira el
nombre. Así que un filtro por tipo de archivo es, para esto, decorativo.

> [!WARNING]
> **Y hay una trampa concreta en elFinder que hay que conocer antes de tocar su configuración.** Sus opciones
> `uploadAllow` y `uploadDeny` se resuelven en `allowPutMime()` con la lógica del `Order` de Apache: con
> `uploadOrder => ['deny', 'allow']` —que es el valor por defecto— **solo deniega si el tipo casa con la lista de
> denegados Y NO casa con la de permitidos**. Como `'all'` casa siempre, **mientras `uploadAllow` contenga `'all'`, la
> lista de denegados no hace absolutamente nada.** Una configuración escrita sin saber esto queda verde y no protege,
> que es el peor resultado posible: parece hecha.

### La opción que sí decide: `acceptedName`

elFinder comprueba el nombre **antes** que el tipo, en `upload()`, con `nameAccepted()`, que aplica la opción
`acceptedName` —una expresión regular o un invocable— al nombre del archivo. Si no casa, devuelve «nombre no válido» y
**no escribe nada**.

Tres cosas que hay que saber para no equivocarse:

- **El driver local se la pone a sí mismo en su constructor**, y **con un valor distinto en cada plataforma**: en Linux
  `/^[^\.\/\x00][^\/\x00]*$/` —sin punto inicial, sin barras y sin bytes nulos— y en Windows uno que además prohíbe
  `\ : * ? " < > |`. No basta con leer el valor por defecto de la clase base.
- **Y por eso el patrón del proyecto se construye por plataforma.** Declarar la opción **sustituye** a la del driver,
  no se suma a ella: un patrón escrito solo con el juego de Linux **debilitaría Windows en silencio**, aceptando
  nombres con `\` o `:` que el driver rechazaba. El constructor del patrón lee `DIRECTORY_SEPARATOR` por ese motivo, y
  no por gusto.
- **Las opciones que pasa el proyecto ganan**, porque `mount()` las fusiona encima **después** del constructor. Por eso
  declararla en la configuración del volumen funciona.

### Y alcanza a más cosas que a la subida: tenlo presente antes de ajustar el patrón

`acceptedName` la consulta elFinder en todos los caminos que escriben un nombre en el volumen de destino, no solo al
subir, y `acceptedDirname` cae en ella si está vacía. Medido en su código: **crear carpeta**, **copiar desde otro
volumen** —y restaurar de la papelera es eso—, y **extraer un comprimido**, donde lo que no pasa el patrón se descarta
al descomprimir.

**Un patrón demasiado estricto no solo frena la subida: rompe el renombrado legítimo y la restauración.** Si lo
ajustas, pruébalo contra nombres que deban pasar, no solo contra los que deban caer.

> [!NOTE]
> **Las papeleras NO llevan el patrón, y es una decisión, no un olvido.** Mandar algo a la papelera es, por dentro, una
> copia a otro volumen, y esa sí consulta el nombre en el destino. Si la papelera llevara el patrón, **un archivo con
> una de esas extensiones que ya estuviera en disco no se podría tirar**. Borrar del todo sí funciona en cualquier caso:
> ese camino no mira el nombre. Y subir a una papelera no es posible: su driver se deshabilita la subida.

### Un efecto de la configuración que conviene conocer

`structureOptions()` **escribe en disco al construir las opciones**: crea las carpetas raíz que falten. En una
instalación recién clonada, la primera llamada —el primer uso del gestor, o la suite que lo comprueba— crea
`statics/filemanager/files`, `.../documents` y las dos papeleras. Es idempotente y es exactamente lo que haría el gestor
al abrirse por primera vez, pero **conviene saberlo antes de llamarla desde una prueba** y creer que no toca nada.

### Si clonas el framework y necesitas cambiar la lista

La lista de extensiones rechazadas vive en **una sola constante** del gestor, con su patrón al lado, para que no haya
que buscarla por el código. Amplíala con lo que tu servidor pueda ejecutar y que no esté ya; redúcela solo si sabes qué
ejecuta tu servidor y por qué, y **déjalo escrito en tu módulo**.

**Lo que no se hace es resolverlo con un `.htaccess`.** El proyecto trae uno que deniega `.php` en todo el árbol, y
viene bien, pero **es del servidor y no del framework**: con `AllowOverride None` —lo que recomienda la documentación de
Apache— o detrás de nginx, desaparece sin avisar. La guarda tiene que estar en el código, que es lo único que viaja con
el clon.
