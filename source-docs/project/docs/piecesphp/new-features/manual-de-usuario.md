# El manual de usuario del panel

El panel trae un **manual para quien lo usa**, no para quien programa. Se abre desde el centro de ayuda (el icono de
interrogación de la barra superior, entrada «Manual»). Cada persona ve **solo los capítulos de las pantallas en las
que puede entrar**, en su idioma, y en español si el suyo no existe.

## Escribir un capítulo de tu proyecto

Los capítulos son archivos Markdown con una cabecera:

```markdown
---
titulo: Inscripciones
rutas: [inscripciones-list, inscripciones-forms-add]
---

# Inscripciones

Desde **«Inscripciones»**, en el menú lateral, ves las del periodo abierto…
```

- **`rutas`**: los nombres de las rutas de las pantallas que el capítulo explica. Lo ve quien tiene permiso sobre
  **alguna** de ellas: lo que administra solo un rol, solo lo ve ese rol, y lo que usan todos lo ven todos.
  `rutas: todos` es para lo general.
- **`ve`** (opcional): si el capítulo explica a la vez pantallas de administración y alguna que puede mucha gente,
  `ve: [ruta, …]` dice cuáles de sus `rutas` deciden quién lo ve. Por ejemplo, un capítulo de administración que cita
  también la edición que cada uno hace de lo suyo: `ve` con la lista y el alta, y lo verá solo quien administra.
- **Dónde**: en `src/app/manual/proyecto/`, una carpeta por idioma (`es/`, `en/`…) y un archivo por capítulo, `NN-tema.md`. El prefijo `NN` da el orden. Las imágenes, en una
  carpeta `img/` junto a cada idioma, y en el texto como `![Descripción](img/archivo.png)`.
- **Los del framework** están en `src/app/manual/framework/`: no los edites, porque una actualización los
  sobrescribe. Un capítulo de tu proyecto con **el mismo nombre de archivo** sustituye al del framework, y con
  `oculto: true` en su cabecera lo oculta.
- Los avisos, con la sintaxis de admonición: `!!! tip "Título"` y el texto sangrado debajo.

- **Por sección y por subsección**: si una sección (`##`) o una subsección (`###`) explica una pantalla que no todos los
  que ven el capítulo pueden abrir, pon en la línea siguiente a su título `<!-- ve: ruta, … -->`. Una sección marcada
  se oculta hasta el siguiente `##`, con sus subsecciones; una subsección, hasta el siguiente `###` o `##`. Solo la ve
  quien tiene permiso sobre alguna de esas rutas, y desaparece para todos si la ruta no existe (un módulo apagado). Sus
  rutas tienen que estar también en las `rutas` del capítulo; una subsección dentro de una sección marcada necesita las
  dos. Por ejemplo:

    ```markdown
    ## Agregar un usuario
    <!-- ve: users-selection-create, users-form-create -->
    ```

- **El índice del capítulo**: cada `##` recibe un ancla sacada de su título, y el menú del manual enseña, bajo el
  capítulo que se lee, las secciones que ese usuario ve. Dos títulos `##` iguales en un capítulo reciben anclas
  distintas, pero mejor no repetirlos.
- **Plegables**: un capítulo con **cinco `##` o más** pinta cada sección plegada (la primera, abierta); con menos, como
  títulos seguidos. Cuéntalo al escribir: si un capítulo se alarga, partirlo en dos suele leerse mejor que plegarlo.
  En un capítulo plegable, **un `#` después del primer `##` es un error** y el capítulo no se genera.

## Generar

```bash
bin/manual           # convierte los capítulos en pantallas del panel
bin/manual --check   # falla si lo generado no es lo que daría regenerarlo
```

Lo generado vive en `src/app/classes/MySpace/Views/manual/` y **se commitea**: a producción solo viaja `src/`.
Cada vez que cambies un capítulo, regenera y commitea. Necesita `python3` con el paquete `markdown` (`python3-markdown` en tu sistema, o `pip install markdown`).

`bin/manual` se niega, sin escribir nada, si un capítulo no tiene cabecera, `titulo` o `rutas`, o si un nombre de ruta
está mal formado, y si un capítulo cita una imagen (`img/<archivo>`) que no está en ningún `img/` de su idioma: lista todas
las que faltan. Las de `framework/` y `proyecto/` se publican juntas, así que un capítulo tuyo puede usar una del framework.

Las capturas del framework son WebP de 1440 px de ancho, una por pantalla, hechas con el usuario de menos privilegio
que ve esa pantalla como la describe su capítulo. Para las tuyas, cualquier formato que el navegador pinte sirve; cuida
que no salgan datos de prueba, llaves ni direcciones de tu servidor: la imagen viaja con `src/`.

## Configuración

- `$config['manual_for_pending']` (`src/app/config/config.php`, `true` de fábrica): si lo ven también las cuentas
  pendientes de aprobación, que son las que más lo necesitan.

## Ninguna pantalla sin capítulo

`bin/cli verify-integrity` comprueba que toda ruta citada por un capítulo existe, también en tu proyecto. La cuenta
de pantallas sin capítulo es un trinquete del framework: en tu proyecto no se mide, porque sus listas viven en
`files/dev/`, que no viaja.
