# Gestor de archivos

El gestor de archivos es [elFinder](https://github.com/Studio-42/elFinder): el cliente va en `src/statics/plugins/elfinder/` y el
conector, por Composer (`studio-42/elfinder`). Los dos tienen que ir en la misma versión. Lo monta
`PiecesPHP\FileManager\Controllers\FileManagerController`, y se apaga con la constante `FILE_MANAGER_MODULE`
(`src/app/config/constants.php`).

## Las dos rutas

| | Gestor general | Gestor del editor |
| :-- | :-- | :-- |
| Vista | `filemanager-admin-filemanager` | `filemanager-admin-filemanager-rich-editor` |
| Conector | `filemanager-admin-filemanager-configuration` | `filemanager-admin-filemanager-configuration-rich-editor` |
| Quién entra | Solo el usuario principal | **Todos los tipos de usuario** |
| Carpetas | `files`, `documents` y `statics/uploads`, con sus papeleras | `documents` y su papelera |
| Tipos que se suben | Todos | Lista cerrada (abajo) |
| Comandos apagados | `chmod` | `chmod`, `extract`, `archive`, `mkfile`, `put`, `edit` |
| Subir por URL | Sí | No |

El gestor del editor es el que abre CKEditor al insertar una imagen o enlazar un archivo. Si cambias su configuración, prueba
insertar desde el editor de una publicación.

## Qué se puede subir por el editor

`FileManagerController::EDITOR_UPLOAD_ALLOWED`: JPEG, PNG, GIF, WebP, PDF, texto plano, CSV, los formatos de Office
(`.doc`, `.docx`, `.xls`, `.xlsx`, `.ppt`, `.pptx`), OpenDocument de texto y de hoja de cálculo, vídeo MP4 y WebM, y audio
MP3, Ogg y WAV.

**Decide el tipo detectado en el contenido, no la extensión.** Un `.jpg` con HTML dentro se rechaza. SVG, HTML y XML no
entran por esta ruta. Para ampliar la lista, añade el MIME tal como lo detecta el servidor: un `.wav`, por ejemplo, se
detecta como `audio/x-wav`, y por eso están los dos.

**Sin medir** con archivos reales: `.doc`, `.xls`, `.ppt` y `.mp3`. Si uno legítimo se rechaza, mira qué tipo detecta el
servidor y añádelo.

## Lo activo no se activa

Un SVG o un HTML servido desde tu dominio podría ejecutar código en la sesión de quien lo abra. Por eso `statics/filemanager/`
lleva el mismo `.htaccess` que las subidas, escrito por `ProtectedUploads::writeDenyHtaccess()` al arrancar y al abrir el
conector. Lo ignora git.

- `.svg`, `.svgz`, `.html`, `.htm`, `.xhtml`, `.xml`, `.xsl` y `.xslt` salen con `Content-Security-Policy: sandbox`.
- Los mismos, salvo `.svg` y `.svgz`, salen además como descarga.
- Un `.svg` en una etiqueta `<img>` se sigue viendo.

## Limitaciones conocidas

- **Pide Apache con `mod_headers` y `AllowOverride`**. Con nginx ese `.htaccess` no aplica: pide esas cabeceras en tu
  configuración; sin medir en nginx.
- **La carpeta `documents` es común**: un usuario ve lo que subieron los demás en esa carpeta.
- **El usuario principal no tiene lista cerrada**: su gestor ve las mismas carpetas y sube cualquier tipo.
- **`netmount`**: su botón se ve, pero no hay controlador que montar. elFinder no da una forma limpia de apagarlo.
- **Un archivo de texto plano llamado `.jpg`** entra como texto y se sirve como imagen, sin activar nada.

## Añadir una carpeta

`structureOptions($dirs, $trashes)` arma cada raíz con sus valores seguros: `acceptedName` (rechaza `.php` y compañía en
cualquier posición del nombre), `accessControl` y la protección de lo privado. Añade la carpeta en el arreglo `$dirs` del
método del conector que le toque, con `alias` y `relativePath`. Si la va a usar alguien que no sea el usuario principal, ponle
lista cerrada y comandos apagados como las raíces del editor.

Al actualizar elFinder, copia `js/`, `css/`, `img/` y `sounds/` de `src/vendor/studio-42/elfinder/` a `src/statics/plugins/elfinder/`.
