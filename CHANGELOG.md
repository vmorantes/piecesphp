# 10.5.0 (06-11-2026)

- **Nuevo: el banco de pruebas de correo.** La pantalla «Correos del sitio» (Configuración del sistema › Sistema) enseña todos los correos que el sitio puede enviar, con datos de muestra y pintados por el mismo camino que el envío real, y deja enviar uno de prueba a la dirección que elijas. La ven root y el administrador general, y solo salen los correos de los módulos activos. **Si tu proyecto envía correos propios**, decláralos: cada uno es una subclase de `MailDefinition` registrada con `MailCatalog::register()` desde su módulo, dentro de su interruptor de activación. `bin/cli verify-integrity` falla si un envío no construye su cuerpo con una definición. Detalle y ejemplo en la guía «El banco de pruebas de correo».
- **El correo que se envía desde un enlace con token ya no lleva un botón vacío.**
- **Un usuario cuyo tipo ya no existe ya no se borra al entrar.** Se le cierra la sesión, el acceso le dice que su cuenta no tiene un tipo válido y root ve un aviso con cuántos usuarios están así. Asígnales un tipo existente para que vuelvan a entrar.
- **Si abres la pantalla de acceso con la sesión ya iniciada**, te lleva a donde ibas o al panel, nunca a la portada.
- **El menú «⋯» de las acciones de fila se abre fuera de la tabla**: ya no hace aparecer una barra vertical dentro de ella ni sale recortado, y se anima hacia el lado en que abre. Lo inicia y lo coloca el panel, también en cada dibujado de la tabla: tu `list.js` ya no tiene que iniciarlo (si lo hace, no rompe nada).
- **Las gráficas de tarta y dona ya no se cortan al pasar el ratón**, y su recuadro de información lleva los colores del tema en claro y en oscuro (era blanco también en oscuro). Para las tuyas, `fitStatisticsChart(chart, interior)` después de `setOption()`, sin `radius` en la serie; la guía de estadísticas trae el ejemplo actualizado.
- **El tema del panel, al día**: el texto seleccionado se lee también en modo oscuro (antes, texto claro sobre fondo claro), en campos y áreas de texto incluidos.
- **«Mi organización» se lee bien en modo oscuro**: el separador de secciones y la descripción de los adjuntos usan los componentes del tema. El fondo de la página lo pone el tema.
- **El editor entiende los JS del framework sin `/// <reference path>`**: se quitaron todas (269; 42 apuntaban a archivos que no existen), porque el `jsconfig.json` de la raíz ya le da al editor todos los JS de `src/statics/` y `src/app/classes/`. El `jsconfig.json` queda sin errores, también con TypeScript 6. **Si tu proyecto tiene JS propios con esas líneas, quítalas**: `bin/cli verify-integrity` las señala, y también señala un `jsconfig.json` o `tsconfig.json` que no sea JSON válido.
- **`pcsphpGlobals` tiene su tipo**: el editor autocompleta sus propiedades y, en un archivo con `// @ts-check`, avisa de una que no existe. Si tu proyecto le añade propiedades propias, decláralas ampliando la interfaz `PcsphpGlobals` en un `.d.ts` de tu proyecto dentro de lo que incluye el `jsconfig.json`. Sin `// @ts-check` no cambia nada.
- **El TypeScript propio del framework tiene su `tsconfig.json`** (`src/statics/core/ts/`), y `gulp ts-vendor` toma de él sus opciones: el editor y la compilación usan las mismas. La salida compilada no cambia.
- **Para quien mantiene el framework:** `APP_VERSION_DATE` tiene que ser la fecha del commit que cambió `APP_VERSION`, y `bin/cli verify-integrity` lo comprueba (sin historia de git, no aplica).

# 10.4.0 (05-11-2026)

- **El manual de usuario se ve por sección.** Cada sección que explica una pantalla solo la ve quien puede abrirla, y desaparece para todos si su pantalla no existe (un módulo apagado). En los capítulos de tu proyecto, pon `<!-- ve: ruta, … -->` en la línea siguiente al título `##` de la sección; `bin/manual` y `bin/cli verify-integrity` comprueban que esas rutas sean del capítulo y existan. Detalle en la guía, «Manual de usuario».
- **El Mapa de contenidos**: los botones, arriba a la derecha; Filtros y Leyenda se abren juntos en una columna debajo, sin tapar el zoom; el botón de la leyenda lleva un icono de mapa.
- **Abrir el listado de usuarios ya no cambia ningún dato.** Un usuario cuyo tipo ya no existe sale en él con «Tipo desconocido».
- **No se puede actuar como un usuario cuyo tipo ya no existe**: se rechaza con un mensaje, en vez de un error en cada petición.
- **Más estricta la comprobación del código de un token.**
- **«Atrás» de las pantallas de problemas de acceso, traducible.**
- **Para quien programa:** `Impersonation::isImpersonating()` y `Impersonation::rootOriginalID()` responden en un solo sitio si root está actuando como otro usuario y quién es root; `SettingsController::seoOptionName()` da el nombre de una opción SEO por idioma. Las variables `$asUserLoggedID` y `$rootOriginalID` de `topbar-data.php` se mantienen.
- **Más CSS en desuso hasta la `11.0.0`**: la clase `ui-pcs`, que ya no pone ninguna vista, y todo lo que cuelga de ella (`ui-pcs.scss`, `.ui-pcs.button`). Si tu proyecto la usa, sigue funcionando hasta entonces.

# 10.3.0 (05-11-2026)

- **Varias pantallas se pintan con los componentes del tema** (y las del panel se leen bien en modo oscuro): las fichas del Directorio (persona y organización), «Mi perfil», el perfil público de una organización, los formularios de problemas de acceso (recuperar la contraseña o el usuario, cuenta bloqueada, otros problemas), Aprobaciones (la lista y la revisión) y los paneles del Mapa de contenidos. Su SCSS propio pasa de 1.583 líneas a 357 (contando los dos parciales de perfiles); lo que queda es lo que el tema no da, como lo que flota sobre el mapa. **Si tu proyecto cuelga estilos de las clases viejas de esas pantallas** (por ejemplo `.section` dentro de las fichas, `.base-text`, `.title-step`, `.time-tag`), revísalos: el marcado es otro.
- **`_profile-view.scss` y `_profile-form.scss` quedan en desuso y salen en la `11.0.0`.** El framework ya no los carga; si tu proyecto los incluye, siguen funcionando igual.
- **El manual de usuario cubre todas las pantallas del panel**: se añaden la revisión de un contenido pendiente de aprobación y la exportación de usuarios.
- **Las migas de las fichas del Directorio, en una línea** (se pintaban como cajas apiladas), y la vista previa de una publicación en la revisión, con la letra del panel.
- **El formulario de exportar usuarios ya no promete filtros** que no tiene.

# 10.2.3 (05-11-2026)

- **El manual de usuario trae capturas**: una de cada pantalla, hecha con el tipo de usuario de menos privilegio que la ve completa. Si a ti te falta una acción que se ve en la captura, tu tipo de usuario no la tiene: lo explica el capítulo «Tu cuenta».
- **`bin/manual` para si un capítulo cita una imagen que no existe**, y lista todas las que faltan sin escribir nada. Si tu proyecto tiene capítulos propios en `src/app/manual/proyecto/`, cada `![…](img/<archivo>)` tiene que tener su archivo en el `img/` de su idioma (vale el de `framework/` o el de `proyecto/`: los dos se publican juntos).

# 10.2.2 (04-11-2026)

- **La leyenda de las gráficas ya no se descoloca si falta un dato.** Cada fila se enlaza con su serie, y toma su color, por el nombre y no por la posición: en Informes, con un solo país, «Otros países» se enciende y se apaga y lleva su color. Si tu proyecto usa `linkStatisticsChartLegend()`, el texto de cada `.name` tiene que ser el nombre de su serie (o de su porción).

# 10.2.1 (04-11-2026)

- **Cada usuario ve en el manual solo lo suyo.** Las fichas del Directorio y el perfil público de una organización tienen su propio capítulo, y «Usuarios» y «Organizaciones» solo los ve quien los administra. Para tus capítulos, una cabecera nueva y opcional: `ve: [ruta, …]` dice cuáles de sus `rutas` deciden quién lo ve (sin ella, todas, como hasta ahora). Regenera con `bin/manual`.

# 10.2.0 (04-11-2026)

- **El panel trae un manual de usuario.** Se abre desde el centro de ayuda («Manual») y cada persona ve solo los capítulos de las pantallas en las que puede entrar, en su idioma (y en español si falta). Cubre las pantallas del framework. Tu proyecto añade los suyos en `src/app/manual/proyecto/`, y puede sustituir u ocultar los del framework; se generan con `bin/manual`. `$config['manual_for_pending']` decide si lo ven las cuentas pendientes de aprobación (de fábrica, sí). La guía «El manual de usuario del panel» lo explica.
- **El CSS del panel, un poco más ligero**: fuera cuatro reglas que repetían lo que ya pinta el tema. Las clases propias que el framework ya no usa llevan la marca `@en-desuso 11.0.0` en su SCSS: si tu proyecto usa alguna en sus vistas, ve buscándole sustituto, porque salen en la `11.0.0`.

# 10.1.2 (04-11-2026)

- **El formulario de soporte del panel envía un correo**, a las direcciones de `$config['support_form_recipients']` (en `config.php`, como `other_problems_recipients`), con la respuesta dirigida a quien escribe. Sin direcciones, el botón no sale. **Ya no usa osTicket**: si tu clon lo usaba para el soporte, pon ahí la dirección de tu mesa de ayuda. osTicket sigue en «otros problemas» del acceso hasta la `11.0.0`, donde sale del framework.
- **El centro de ayuda ya no enlaza la documentación de desarrolladores como «Preguntas frecuentes»**: `help_faq_url` viene vacío. Si tu proyecto tiene sus preguntas frecuentes, pon su dirección ahí.

# 10.1.1 (04-11-2026)

- **La cola reintenta con espera.** Un fallo con reintento volvía a la cola en el acto; ahora espera 1, 5, 15, 30 y 60 minutos según el intento, salvo que el manejador pida su propia espera: `QueueHandlerResponse::fail($mensaje, true, $minutos)`, o `delay` en el arreglo que devuelve. **Si tu manejador contaba con el reintento inmediato**, pasa `0` de forma explícita.
- **Un formulario enviado sin datos ya no da un error interno** en el registro y la edición de usuarios, los tokens genéricos y las traducciones de la API: responde la validación, como con cualquier dato que falte.
- **`seconds_to_duration()` pasa de 24 horas** (90.000 segundos dan 25 horas, no 1) y **`decimalCoordinatesToDMS()` acepta la coma decimal**.

# 10.1.0 (04-11-2026)

- **Las gráficas del panel se dibujan con Apache ECharts 5.5.1**: Registros › Accesos, Informes y el ejemplo de la hoja de diseño. Para las tuyas: `import_echarts()` en el controlador y, en `chart-card.php`, `chartModifiers` (el contenedor pasa a ser un `ui chart` del tema; una dona necesita al menos `['tall']`). `linkStatisticsChartLegend()` enlaza la leyenda con un gráfico de ECharts, y `resizeStatisticsChart()` lo redimensiona con la ventana y al abrir su pestaña. La guía de estadísticas trae un ejemplo completo.
- **ApexCharts queda en desuso y sale en la `11.0.0`.** Hasta entonces nada cambia para tu clon: `import_apexcharts()` funciona, `chart-card.php` sin `chartModifiers` pinta el contenedor de siempre y la leyenda enlazada acepta también un gráfico de ApexCharts.
- **El tema del panel, al día**: un icono con caja ya no se deforma junto a un texto largo, y los indicadores de un carrusel sin imágenes se ven sobre la superficie.

# 10.0.4 (01-11-2026)

- **Con el lateral contraído, el menú desplaza.** Iconos y logos se mueven juntos y los logos conservan su tamaño; los submenús flotantes y los tooltips siguen a su ítem. Sustituye a lo que hacía la `10.0.3`, que los encogía.
- **Si tu clon** cuelga estilos o pruebas de `.collapsed.only` dentro de `.footer`, ese bloque ahora vive al final del menú. Se aplica al recompilar las hojas y el JavaScript de configuración.

# 10.0.3 (01-11-2026)

- **Con el lateral contraído, los logos del pie ya no se pintan sobre el menú.** El pie cede el alto: si no hay espacio, los logos encogen o dejan de verse. Con el lateral expandido no cambia nada. Se aplica al recompilar las hojas.
- **La documentación pública cuenta los arreglos de seguridad en términos generales.** Este `CHANGELOG` y la guía se han reescrito así: el detalle de versiones anteriores ya no está aquí. Si mantienes un clon en una versión anterior, actualiza.

# 10.0.2 (31-10-2026)

- **El Directorio usa la maqueta común de los listados.** Desaparecen las barras de desplazamiento que no hacían falta; el buscador y «Exportar listado actual» son los de los demás listados. Cada fila dice si es una persona o una organización.
- **Si tu clon personalizaba esa pantalla:** salen `Directory/Statics/sass/list.scss`, la hoja `list.css` y las clases `.user-info` y `.custom-search`, y la última columna se titula «Acciones».
- **Tras actualizar**, si Mantenimiento o `bin/cli verify-integrity` señalan un enlace roto de `list.css`, bórralo desde Sistema, en la pantalla de Mantenimiento.

# 10.0.1 (30-10-2026)

- **Sale la pantalla suelta del límite de tokens** (`forms-tokens-limit`): la cuota se edita dentro de Inteligencia artificial, y su texto decía otra cosa. Si tu clon la enlazaba, apunta a Configuración › Inteligencia artificial.
- **Una organización recién creada ya se puede editar**: pedía un encargado que aún no podía existir.
- **El aviso de cambiar de encargado** pregunta por lo que hace.

# 10.0.0 (30-10-2026)

> **La versión del rediseño del panel.** Todo el panel y el acceso van con el tema nuevo y sus piezas. La zona pública sigue con
> su Fomantic de siempre. Lee las secciones ⚠ si tu clon cambió vistas o CSS del panel. Las gráficas siguen con ApexCharts:
> Apache ECharts llega en una `10.x`.

## ⚠ Rompe — Seguridad y Personas, Datos, Inicio y Actividad con el diseño

- **El alta pública por la API, apagada de fábrica**: `API_PUBLIC_SIGNUP` en `constants.php`, `false`. Con ella apagada, la ruta responde 404 y no crea nada. Si tu clon la usa, ponla en `true`.
- **Sale `FileValidator::$ignoreMimeType`**, el interruptor estático que saltaba la comprobación del tipo en todo el proceso. Si tu clon lo usaba, declara el tipo en el campo.
- **La barra del panel trae un buscador con Ctrl K** (el menú, Configuración y la cuenta, filtrados por permisos) y **un centro de ayuda**: «Preguntas frecuentes», un enlace en `$config['help_faq_url']` (vacío, no sale), y «Contactar a soporte», que sale si osTicket está configurado y el rol tiene permiso.
- **Los listados con `dataTableServerProccesing` llevan «Exportar listado actual»**: un CSV de la página visible, con la extensión Buttons. Los listados en tarjetas, no.
- **El campo de archivo (`simpleUploadPlaceholderWorkSpace`) ya no lleva la vista previa borrosa**: conserva su API. La imagen de Documentos y el icono de las categorías de Noticias usan la tarjeta de imagen; Organizaciones, también para su logo.
- **La cuota de tokens se edita dentro de Inteligencia artificial.** Su ruta sigue.
- **Fuera `news.scss`** y la carga de Poppins en `ui-pcs.scss`. El panel usa la fuente del tema.
- **Corregido: en el mapa, las capas de clústeres y puntos fallaban** porque Mapbox no admite `oklch()`: el color pasa a sRGB antes de llegar a la capa.

## ⚠ Rompe — Cuenta, Contenidos, el pie del panel y tres librerías fuera

Conservan sus rutas, sus campos y su lógica. Si tu clon sobrescribe estas vistas o usa lo que sale, revisa esto.

- **Salen Spectrum y NProgress**, con `import_spectrum()`, `import_nprogress()` y sus carpetas de `statics/plugins/`. `input[color-picker-js]`
  sigue funcionando: ahora lo atiende el `ui colorpicker` del tema, igual en cualquier navegador. NProgress no tenía ninguna llamada.
- **Salen `dataTables.semanticui.min.css` y `responsive.semanticui.min.css`**: el panel no las cargaba. Si tu clon las pedía fuera del panel, tráelas de nuevo.
- **La hoja de diseño, solo para root**: `admin-design-sheet` y `admin-design-sheet-resources` ya no conceden al administrador general.
- **Publicaciones, Banners e Imagen principal usan la tarjeta de imagen** (`panel/built-in/content/image-field.php`) en lugar de `.attach-placeholder`
  y `SimpleCropperAdapter`. Los campos se llaman igual. `SimpleCropperAdapter` sigue disponible.
- **La foto de perfil del menú usa el editor nuevo**: fuera el `profile-image-modal` de `topbar.php`. `new ContentImageEditor('scope')` permite un segundo editor en la página.
- **El pie del menú lleva solo el texto de terceros, la bandera y el crédito**: la versión pasa bajo el nombre de la plataforma, y el nombre repetido y los derechos salen.
- **Las banderas de terceros se pintan siempre**, también las de fábrica, que ahora son PNG con transparencia; `developer` y `developer_logo` de `config.php` traen marcadores: cámbialos.
- **`partnersText` trae «Con el apoyo de» si la opción no existe.** Guardada vacía, no se pinta.
- **El logo y las banderas se pueden invertir en modo oscuro**, con una casilla en Imágenes de marca. Pide un PNG con fondo transparente: un fondo opaco sale como un rectángulo blanco.
- **`.ui.form:invalid` del tema pasa a `:user-invalid`**: ningún formulario sale rosa al cargar.

## ⚠ Rompe — Usuarios, el acceso y las páginas de error, con el diseño

Conservan sus rutas, sus campos y su lógica: el inicio de sesión, el código de un uso, la recuperación y el bloqueo funcionan
igual. Lo que cambia es el marcado y sus hojas: si tu clon sobrescribe o estiliza estas vistas, revísalas.

- **Los 18 formularios de usuario son una sola pieza**, `usuarios/form-by-type/user-form.php`. Cada `form-by-type/<modo>/<tipo>/form.php`
  solo dice su modo y su tipo. Conservan sus `name` y sus clases. Si tu clon editó uno, lleva el cambio a su archivo o sustitúyelo
  por el suyo completo. Los campos ya no llevan `placeholder`: cada uno tiene su `label`.
- **Las seis vistas del acceso usan un marco**, `usuarios/inc/access-frame.php`, con marca y formulario, que se apilan en el móvil.
- **403, 404 y 503 son una sola página**, `view/pages/error-page.php`, sin JavaScript y con la hoja del tema, también en la zona
  pública. **Sale `src/statics/sass/errors.scss`**, y con ella `statics/css/errors.css`: si tu clon la cargaba, la pierde.
- **Sale `UserSystem/Statics/sass/users-list.scss`**, con su `set_custom_assets`. `login.scss`, `problems.scss` y `problems-form.scss`
  adelgazan, y sus reglas comunes pasan a `import/_access.scss`.
- **La foto de perfil usa la tarjeta de imagen**; `CropperAdapterComponent` sigue disponible para tu clon.
- **El listado de Usuarios ya no lleva migas**, y la miga activa va en el color del texto: sale de `.breadcrumb` su `var(--accent-2)`.
- **El aviso de error en cada acceso** por pedir la llave de mapas sin sesión ya no sale.

## ⚠ Rompe — Registros y Sistema, con el diseño

Correos enviados, Sesiones, Tokens, Salud, Errores, Tareas, Accesos y Respaldos adoptan el diseño. Conservan sus rutas y sus
acciones.

- **Registros ya no lleva migas.** Sigue siendo una pantalla con pestañas.
- **Tareas programadas abre siempre en «Estado actual»**, y si no hay ejecuciones avisa arriba de que mire la pestaña Instrucciones.
- **Las etiquetas de estado de estas pantallas son `StatusLabel`**, también las que pintaban los controladores.
- **Sale `UserSystem/Statics/sass/attempts.scss`**, con su `set_custom_assets`.
- **Piezas nuevas**: `panel/built-in/content/filter-actions.php`, Filtrar y Limpiar en cualquier formulario de filtros, y
  `figure` en `panel/built-in/statistics/statistic-card.php`, la tarjeta de cifra del tema; las dos, con su ejemplo en la hoja de
  diseño.
- **Pulsar la imagen de una tarjeta abre el editor con la imagen actual**, para recortarla sin volver a subirla.
- **Dos pantallas cambian de nombre**: «Fondos» es «Fondos del inicio de sesión», y «Llaves de MapBox», «Llaves de Mapas».

## ⚠ Rompe — Imágenes de marca, Fondos e Identidad y SEO, con el diseño

Las tres pantallas adoptan el diseño. Conservan sus rutas, sus opciones y los campos que envían.

- **Una sola forma de cargar una imagen**: «Cambiar imagen», elegir archivo, recortar en un editor a pantalla completa y
  «Aplicar». El recuadro es fijo: se mueve, se acerca, se gira o se voltea la imagen. Banners, Imagen principal,
  Organizaciones y Publicaciones siguen con su recortador.
- **Logo, favicons, logo de correo y banderas se guardan con fondo transparente.** Antes, Logo y banderas salían sobre blanco.
- **Los fondos del acceso se recortan a 1000 × 1080** (antes, 650 × 730). Lo ya subido no cambia.
- **Se acepta cualquier imagen que el navegador lea**, también SVG e ICO; lo que se guarda sigue siendo PNG, o JPG en los
  fondos y en la imagen para compartir.
- **Una tarjeta de fondo vacía ahora guarda de verdad.** Cada tarjeta enseña su archivo `bg<n>`, no el de su posición en la
  lista, y la opción `backgrounds` recibe el que falte.
- **Identidad y SEO** se reparte en tres bloques con una vista previa al compartir. Sigue siendo un solo formulario por
  idioma, y la imagen para compartir sigue siendo por idioma.
- **Piezas nuevas**: `image-card.php` e `image-editor.php`, con `ContentImageEditor` (viene con `import_cropper()`); su
  ejemplo, en la hoja de diseño.
- **`toggle-field.php` pierde `inline`**: el tema ya no hereda el asterisco del campo a la casilla. Quita ese parámetro.
- **Salen `Settings/Statics/sass/logos-favicons.scss` y `backgrounds.scss`**, con sus `set_custom_assets`.

## Corregido — la lista de quién traduce con IA

Configuración › Inteligencia artificial › «Quién puede traducir con IA» no restringía a nadie desde la `v9.2.0`: las rutas
se registran antes de aplicar las opciones de la base, y la lista siempre daba los seis tipos. Ahora se comprueba al atender
la petición, con `APIController::currentUserCanTranslate()`, y quien no esté en la lista ni ve el botón ni puede traducir.
Si guardaste una lista, empieza a aplicarse al actualizar.

## ⚠ Rompe — las piezas del diseño: preguntar, errores, tablas, etiquetas y avisos

Las piezas comunes del panel adoptan lo que pide el diseño. Lo que tu clon pinte con ellas cambia sin tocar nada; lo que
cambia de contrato se dice abajo.

- **`confirmAction()` pregunta con un `ui mini modal`**, no con un aviso emergente. Su firma, sus textos y su promesa son los
  mismos. El foco empieza en cancelar, y Esc o un clic fuera cuentan como «no». `confirmClass: 'blue'` pinta `primary`, y
  `'red'`, `negative`. `message` sigue siendo HTML: quien pase datos del usuario, los escapa. Ya no pone `wait-to-action` en
  el `body`.
- **Una tabla es una tabla.** `table.content-table` ya no se convierte en tarjetas en el móvil: se desplaza en horizontal y
  conserva el orden. Para las tarjetas, añade la clase `content-table-cards`.
- **Las etiquetas de estado**, con la pieza `PiecesPHP\Core\Utilities\Helpers\StatusLabel`: `StatusLabel::html()` con
  `POSITIVE`, `NEGATIVE`, `WARNING` o `INFO`, y `StatusLabel::neutral()`. **Cambian dos constantes**: en
  `PublicationMapper::VISIBILITIES_COLORS` y `NewsMapper::STATUSES_COLORS`, borrador pasa de `orange` a `yellow`, y
  programada, de `blue` a `teal`. Si tu clon pintaba `ui <color> label` con ellas, sigue valiendo, pero el texto sale negro
  sobre color: usa la pieza.
- **Los avisos** de `successMessage()`, `warningMessage()`, `infoMessage()` y `errorMessage()` salen arriba a la derecha.
  Para otra posición, `options.position`.
- **Los errores de un formulario**: `genericFormHandler` pinta el mensaje del navegador bajo cada campo y un resumen arriba
  con «Revisa los campos marcados», también en un adjunto (`.attach-placeholder`). Los formularios del panel dejan de avisar
  con un mensaje emergente: salen 28 `onInvalidEvent` propios. El tuyo sigue sustituyendo todo eso; si solo llamaba a
  `errorMessage()`, quítalo.
- **La tabla de un listado llega al borde de su tarjeta**: el contenedor es `ui fluid card container-standard-table` si la
  tabla pasa por DataTables, y la columna de `RowActions` va a la derecha. Un listado de tu clon con `ui segment` sigue
  funcionando, con la tabla metida en el relleno; cámbialo.
- **Los tooltips dentro de una tabla de DataTables** van con el módulo popup, para que el desplazamiento no los recorte: el
  panel quita `data-tooltip`, `data-position` y `data-inverted` de esas celdas al dibujar. Un JavaScript de tu clon que los
  leyera después del dibujado ya no los encuentra.
- **Nuevo en `RowActions`**: `primary()`, `inactive()` (no aplica a esta fila: se ve, enseña «No aplica» y no hace nada) y
  `text()` (un botón de texto).
- **`form-modal.php`** lleva una X y su contenido se desplaza. **Un desplegable dentro de esa ventana sale recortado**; se
  llega a sus opciones desplazando.
- **`empty-state.php`** admite `description`, y `icon` dentro de `action`.
- **Pieza nueva `panel/built-in/content/password-field.php`**: la contraseña con ojo, con su ejemplo en la hoja de diseño.
  El parámetro del nombre del campo es `inputName`.
- **Siete pantallas con el diseño**: Archivos para buscadores, Inteligencia artificial, Llaves de mapas, Scripts, Correo,
  Seguridad y Sitio en mantenimiento. Conservan rutas, opciones y `name`. Salen `Settings/Statics/sass/site-files.scss` y
  `scripts.scss`, con sus `set_custom_assets`, y Scripts guarda con `genericFormHandler`.
- **Las etiquetas de los formularios del panel** las pone el tema: sale de `admin_app_base.scss` la regla que las fijaba en
  16 px. Si tu clon contaba con ese tamaño, se ven más pequeñas.
- **Piezas nuevas**: `section-header.php` y `toggle-field.php`, con su ejemplo en la hoja de diseño; `password-field.php` admite
  `fieldExtra`; un `input[data-live-reading="days|seconds"]` escribe su lectura en `[data-live-reading-for]`.
- **Las tablas** dicen «_MENU_ registros por página». Una tabla con `data-search-placeholder` pone ese texto en su buscador.
- Las pestañas `.ui.menu` con `.item[data-tab]` llevan sus roles ARIA y se manejan con el teclado, sin tocar las vistas.

## ⚠ Rompe — la pantalla Colores, con el prototipo; siete opciones fuera

Configuración › Apariencia › Colores es ahora **un solo formulario** con un solo «Guardar»: el color de acento, con su
contraste y sus tonos; la paleta de datos para gráficas, con armonías; la barra del navegador en el móvil; los colores de
realce; la tipografía y la forma; y una muestra en vivo. Todo se ve en el panel mientras lo cambias, y «Cancelar» lo
devuelve a lo guardado.

- **Antes de desplegar, aplica `databases/actualizaciones/2026-10-30-opciones-de-color-retiradas.sql`.** Salen las opciones
  `font_color_one`, `font_color_two`, `menu_color_background`, `menu_color_mark`, `menu_color_font`, `bg_tools_buttons` y
  `font_family_global`. Si sus filas siguen en la base, cada una deja **un error grave en el registro**, una vez, por
  opción sin declarar (`ConfigOptions::warnUndeclared()`). Sus valores no se migran: el tema no los lee.
- **Sale la ruta `configurations-generic-save`** con `SettingsController::actionGeneric()`,
  `GENERIC_SAVE_ALLOWED`, `ROOT_ONLY_CONFIG_KEYS` y `isRootOnlyConfigName()`. La sustituye
  `configurations-appearance-colors-save` (POST `admin/configurations/appearance/colors/save/`), con el mismo permiso:
  valida **todos** los campos antes de escribir y escribe en una transacción, así que un campo inválido no cambia nada. Si
  tu clon llamaba a la ruta vieja, llama a la nueva con todo el formulario. Solo admite las opciones de color de
  `SettingsController::COLOR_OPTIONS` y las tres de tipografía y forma.
- **Opciones nuevas**: `third_brand_color` (`--accent-3`), `data_color_2` a `data_color_5` (`--data-2` a `--data-5`; vacías,
  se usan las del tema, y la 5 cambia sola en oscuro), `theme_font` (`inter`, `poppins` o `public-sans`), `theme_font_size`
  (14, 15 o 16) y `theme_radius` (0, 4, 6, 8 o 12). `SettingsController::themeTokens()` decide qué se publica: lo que vale
  de fábrica no se publica, y un valor guardado fuera de la lista tampoco.
- **De fábrica, los colores del tema**: `main_brand_color` y `meta_theme_color` `#356DCA`, `second_brand_color` `#7A5AF8`,
  `third_brand_color` `#C11574`. Si tu clon no guardó sus colores, cambian al desplegar. Lo guardado en la base se respeta.
- **La barra del navegador** se elige entre «Usar acento» y «Neutro oscuro» (`#1F2430`). Si tenías otro color, se queda hasta
  que guardes el formulario; al guardar pasa a una de las dos.
- **Las gráficas** toman la fuente del panel (`--font-body`), no de `font_family_global`.
- **`ui-pcs.scss` ya no fija `font-size` en `html` y `body`**: lo pone el tema con `--font-size-base`. Una hoja de tu clon
  que contara con ese tamaño sigue valiendo 14 px de fábrica.
- **Pieza nueva `panel/built-in/content/color-field.php`**: el campo de color con su hexadecimal, con su ejemplo en la hoja
  de diseño. La sincroniza `contentColorFields()`, de `admin-area/js/main.js`.
- Salen `Statics/sass/configurations.scss` y la vista `app_configurations/configurations.php`: si tu clon las extendía,
  general.php se pinta directo.

## ⚠ Seguridad — el gestor de archivos endurece lo que sirve y lo que acepta el editor

El gestor de archivos pasa a servir los archivos con contenido activo con las mismas protecciones que las subidas, y la ruta
que usa el editor admite menos operaciones.

- **`statics/filemanager/` lleva el `.htaccess` de las subidas**. Se escribe solo al arrancar. Pide Apache con `mod_headers`; con nginx, añade esas cabeceras.
- **La ruta del editor admite una lista cerrada de tipos**, según el contenido: imágenes, PDF, Office, OpenDocument, texto, CSV,
  vídeo y audio. **Si tu clon sube otro tipo por el editor, añádelo a `FileManagerController::EDITOR_UPLOAD_ALLOWED`.**
- **Esa ruta limita las operaciones del gestor** y la subida por URL. El gestor del usuario principal no cambia.
- Guía nueva: «Gestor de archivos», en Desarrolladores.

## Cambia — los campos de formulario, a la altura del tema; el cliente de elFinder, a 2.1.70

- **Los campos de formulario del panel y del acceso** miden lo que dice el tema (40 px de fábrica) y escalan con el tamaño base:
  sale el `min-height: 52px` de `admin_app_base.scss`, y el acceso ya no fija `html` a 16 px. Si una vista de tu clon contaba con esos 52 px, ajústala con el marcado.
- **El cliente de elFinder pasa de 2.1.57 a 2.1.70**, la versión del conector (`src/statics/plugins/elfinder/`). Trae las
  correcciones de seguridad en el navegador de esa versión. La integración con el editor no cambia.

## ⚠ Rompe — el panel de Configuración, con su patrón

El panel que abre el engranaje de la barra lleva un buscador («Buscar ajuste…») que filtra los enlaces, abre sus grupos y
recuenta; cada grupo muestra su icono y su contador. Nace con todos los grupos cerrados y no marca la pantalla actual.

- **Solo enseña lo que el rol puede abrir**: antes pintaba todos los enlaces de un grupo visible. Un grupo con enlaces que
  tu clon no declare como visibles dejará de mostrarlos.
- `SettingsController::panelGroups()` lleva una clave `icon` en cada grupo.
- Los ítems del panel van sin icono, como el patrón. Si un clon usa `panelGroups()`, su clave `icon` por ítem sigue ahí.

## ⚠ Rompe — el panel publica los colores como tokens del tema; fuera nuestras 20 variables CSS

La hoja de variables globales (`admin-global-variables-css`, que monta `src/app/config/containers.php`) publica ahora
**los tokens del contrato del tema** en lugar de nuestras variables: `--accent`, desde el color de marca
(`main_brand_color`), `--accent-2`, desde el segundo (`second_brand_color`), y los que se configuran en la pantalla Colores
(la sección siguiente). El tema saca del acento todos sus tonos.

- **Salen las 20 variables propias**: `--main-brand-color` (y `-opacity`, `-0-5`, `-0-8`), `--second-brand-color`,
  `--font-color-one`, `--font-color-two`, `--menu-color-*`, `--bg-tools-buttons`, `--body-gradient`, `--meta-theme-color`,
  `--loader-*`, `--font-family-global` y `--font-family-sidebars`. Las hojas del panel y del acceso leen los tokens del
  tema: el color de marca es `var(--accent)`, el segundo `var(--accent-2)`, el texto `var(--theme-text)`, el texto sobre
  el color de marca `var(--accent-contrast)` y las superficies `var(--theme-surface)`. Las variables de SCSS de siempre
  (`$colorFirst`, `$colorSecond`…) siguen y apuntan a esos tokens.
- **Si una hoja de tu clon usa `var(--main-brand-color)`** o cualquiera de las de arriba, cámbiala por el token del tema.
- **La zona pública y las páginas de error** no cargan el tema: llevan sus colores y su fuente fijos en su propia hoja
  (`src/statics/sass/imports/_variables.scss`, `errors.scss`), los de fábrica. Cambiar el color de marca en el panel ya no
  las cambia.
- **El correo** sigue leyendo el color de marca de la opción, por PHP. Y la barra del navegador en el móvil
  (`meta_theme_color`), también por PHP.
- Salen de `config.php`: `bg_tools_buttons`, `body_gradient` y `font_family_sidebars`, que nadie leía.
- **Las gráficas de Informes** toman sus series de los colores de datos del tema (`--data-1`, el de marca; `--data-2`…).

## ⚠ Rompe — el perfil se crea al crear el usuario, no en cada petición

Hasta ahora, cada petición al framework (al registrar las rutas de Mi espacio) buscaba los usuarios generales y
administradores de organización sin perfil y se lo creaba. Era una consulta en cada petición; y cuando dos peticiones
llegaban a la vez, la creación chocaba y dejaba dos errores graves en el registro (`PDOException` HY093).

- **El perfil se crea donde se crea el usuario o cambia su tipo**, con `UserProfileMapper::ensureFor($userID)`: el alta del
  panel, el alta pública por la API, la importación y `UsersModel::changeType()`. Es idempotente y bloquea la fila del
  usuario mientras crea, así que dos llamadas a la vez dejan un solo perfil (por diseño; probado con dos llamadas seguidas
  y dentro de una transacción abierta). Solo crea perfil a los tipos que lo llevan (`UsersModel::TYPES_USER_SHOULD_HAVE_PROFILE`):
  los demás ya no reciben un perfil vacío al darse de alta.
- **Si tu clon crea usuarios por su cuenta** (una tarea, un importador, un alta propia), llama a `ensureFor()` después de
  guardarlo; ya no lo rellena el arranque.
- **Al actualizar una instalación**, rellena una vez los perfiles que falten: `bin/cli profiles-create-missing` enumera
  los usuarios sin perfil, y con `confirm=yes` los crea.
- `ProfileTasksUtilities::generateMissingProfiles()` devuelve ahora lo que encontró y creó (`missing`, `created`,
  `failed`) en vez de nada.

## ⚠ Rompe — las pestañas del panel, con el tema

Las pestañas de las pantallas del panel (los estados de un listado, las secciones de un formulario, los idiomas…) son el
`ui secondary pointing menu` del tema, con un `item` por pestaña. En el móvil, la fila se desliza por dentro.

- **El marcado**: el contenedor pasa de `tabs-controls content-tabs` a `ui secondary pointing menu tabs-controls
  content-tabs`, y cada pestaña lleva `item` (y `active` la activa). `tabs-controls` se queda **solo como gancho** para
  el JavaScript (`$('.tabs-controls [data-tab]').tab()`): ya no pinta nada.
- **Los botones junto a las pestañas** (`.actions-buttons`, como «Traducir» en la edición de una publicación) van en un
  `right menu actions-buttons`.
- **Salen las reglas de `.tabs-controls`** de `admin_app_base.scss` (18 px, ancho mínimo de 166 px, el color de marca, la
  negrita de la activa) y su ajuste del móvil en `_content-screens.scss`. Si tu clon pinta pestañas propias con
  `tabs-controls`, añade `ui secondary pointing menu` al contenedor e `item` a cada pestaña.
- La hoja de diseño › «Pestañas» enseña la pieza nueva.

## ⚠ Rompe — los botones y los títulos de sección del panel, con el tema

El panel deja de repintar los botones de Fomantic: los pinta el tema, con su tamaño (40 px de alto, texto de 14 px) y su
radio. Lo que se quita:

- **`brand-color` sale.** Cambia la clase en tus vistas:

  | Antes | Ahora |
  | :-- | :-- |
  | `ui button brand-color`, `custom-main-color` | `ui primary button` (una como máximo por grupo) |
  | `ui button brand-color alt` | `ui button` (secundario) |
  | `ui button brand-color alt2` | `ui basic button` |

  El botón primario lleva el color de marca de la instalación (`--main-brand-color`, que el tema lee como `--accent`).
  **El segundo y el tercer color de marca dejan de pintar botones**: la guía del tema los reserva para realces, con sus
  propias variables (`--accent-2`, `--accent-3`). Las nuestras siguen existiendo para las piezas propias que las usen.
- **Fuera las reglas globales de tamaño**: `.ui.button` de `ui-pcs.scss` (14 px, radio 8, sin margen) y
  `.module-view-container .ui.button` y `.ui.buttons` de `admin_app_base.scss` (18 px, radio 10, grupos con hueco).
  Un grupo de botones que tenga que ir separado, con `ui spaced buttons` de Fomantic (como la cabecera de las pantallas,
  `page-header.php`); sin variación, el grupo va unido.
- **`ui dividing header`** queda con el aspecto del tema: salen su color y su borde de marca, y las variaciones `alt`,
  `alt2`, `white` y `no-border`. Sale también `.ui.tag.brand-color`, que nada usaba.
- **La fuente propia que quedaba en el panel** (`$fontGlobal`, `$fontModule`, `$pFontFamily`, `$listFontFamily` en las
  hojas del panel; la Public Sans de la franja de suplantación y del cambio de foto de perfil) sale: todo el panel va con
  la del tema. Las variables `$fontGlobalText`, `$pFontFamily` y `$listFontFamily` de `_variables.scss` salen; si tu clon
  las usaba en una hoja propia, escribe la fuente o, mejor, no la escribas.
- La hoja de diseño enseña los botones con las clases del tema.

## Cambia — el panel y el acceso, con la fuente del tema

El panel y el acceso usan la fuente del tema (Inter). Nuestro CSS imponía encima la de `font_family_global`
(`config.php`, la Poppins) con `_reset-font-family-fomantic.scss` y varias reglas propias (`html, body` de `ui-pcs.scss`,
`.module-view-container` y sus botones, el acceso y la recuperación): salen de las hojas que solo sirven con el tema. La
zona pública y las páginas de error (403, 404, 503) siguen con `font_family_global`. Si tu clon cambió esa opción para el
panel, hoy no tiene efecto ahí (se decide si se conecta a la fuente del tema: P264).

## ⚠ Rompe — fuera el índice de Configuración; sus pantallas, sin migas

- **Sale la ruta `configurations-index`** (`admin/configurations/`), con `SettingsController::indexView()` y su vista
  `app_configurations/index.php` (ADR 0070). La lista de grupos y pantallas la da el panel de Configuración de la barra
  superior, desde cualquier pantalla. Quien pida `admin/configurations/` recibe el 404 del panel; los estáticos del módulo
  (`admin/configurations/statics/…`) siguen igual. Si tu clon enlazaba el índice, enlaza la pantalla concreta.
- **Las pantallas de Configuración no llevan migas**: son vistas principales, y se llega a ellas por el panel de
  Configuración. `SettingsController::sectionHeaderData()` pierde el primer parámetro (`$section`, que solo servía a la
  miga): ahora es `sectionHeaderData($title, $description)` y devuelve `breadcrumbs` vacío. Si tu clon la llamaba con tres
  argumentos, quita el primero.
- **«Cancelar» vuelve al inicio del panel** (`$indexLink`).
- **El panel de la barra se titula «Configuración del sistema»**, como en el diseño (antes, «Administrativo»).

## Cambia — los íconos SVG del tema, en el panel y en el acceso

- El panel y el acceso cargan, tras el tema, su hoja de íconos SVG
  (`statics/plugins/fomantic-piecesphp/themes/piecesphp/assets/svg-icons/svg-icons.css`). Font Awesome sigue siendo el de
  siempre; los SVG del tema se piden con su nombre y `svg`: `<i class="inicio svg icon">`, o en un `MenuGroup`,
  `'icon' => 'inicio svg'`. El botón de contraer el menú lateral lleva el del tema (`panel svg icon`).
- **Los SVG propios de un clon**, en `src/statics/svg-icons/`: el `.svg` y su regla en `svg-icons.css` de esa carpeta, con
  el patrón que trae comentado. El panel la carga detrás de la del tema, y `bin/traer-tema` no la toca. **No los pongas en
  el `Statics/` de un módulo**: esa hoja se sirve desde `statics/server-delegated/`, y su `url("x.svg")` relativa da 404.
- El ejemplo, en la hoja de diseño › «Íconos SVG».
- El selector de modo del menú de usuario, sin el hueco de debajo.

## ⚠ Rompe — Configuración y el menú de usuario, con el orden del prototipo

El panel de Configuración de la barra superior y el menú de usuario siguen el orden del diseño del panel, **sin quitar
ninguna opción**. Lo que el diseño no nombra va al final del grupo más afín.

- **Configuración, en cinco grupos** (`SettingsController::panelGroups()`, la única fuente del panel):
  - **Gestión de usuarios**: Agregar, Gestionar e Importar usuarios, y Sesiones abiertas.
  - **Registros y Logs**: Errores, Intentos de ingreso, Registro de ingresos, Usuarios sin ingresos, Correos enviados,
    Tokens y códigos, Tareas programadas y Todos los registros. Cada uno abre su pestaña de Registros (`?tab=`), con el
    título, el icono y el permiso de `LogTabRegistry`.
  - **Apariencia**: Imágenes de marca, Fondos y Colores.
  - **SEO y Scripts**: Identidad y SEO, Archivos para buscadores y Scripts.
  - **Sistema**: Inteligencia artificial, Límite de tokens de IA, Avisos, Estado y cachés, Salud, Sitio en mantenimiento,
    Correo, Llaves de MapBox, Rutas y permisos, Seguridad, Respaldos, OsTicket y la Hoja de diseño.
- **El grupo «Integraciones» desaparece.** Sus pantallas pasan a Sistema (Correo, OsTicket, Inteligencia artificial, las
  llaves de MapBox, el límite de tokens) o a SEO y Scripts (Scripts), y sus migas y los textos que decían
  «Integraciones › …» cambian con ellas: el aviso del sistema de la llave de MapBox, el del geovisor, el correo de
  prueba y la tarea del correo de demostración (`MailDemoTask`). Las claves de traducción en inglés se renombraron con
  ellas. Si tu clon citaba «Integraciones › …» en un texto propio, cámbialo.
- **El menú de usuario**: Editar cuenta, Cambiar contraseña y Opciones de seguridad, y detrás Mi perfil y Mi organización
  si el usuario los tiene; después, Idiomas, Conectar como otro usuario y Volver a mi usuario; después, el modo de color;
  y al final, Acerca de y Cerrar sesión. La Hoja de diseño sale del menú de usuario y pasa a Configuración › Sistema.
  Sale el encabezado «Plataforma».

## Cambia — el modo oscuro del panel, por usuario

- **Claro, Oscuro o el del Sistema**, con tres botones en el menú de usuario. Se guarda por usuario, en su meta
  `theme_mode` (`light`, `dark` o `auto`), con la ruta `admin-theme-mode` (POST, parámetro `mode`; cualquier otro valor
  responde 400 y no cambia nada).
- **Se aplica antes de pintar**: `panel/layout/header.php` pone `data-theme` en `<html>` desde la meta, así que la página
  llega ya en el modo elegido y no parpadea: `data-theme` es siempre `light`, `dark` o `auto`. **Sin elegir nada, el
  panel sigue el modo del sistema** (`auto`); el claro elegido se guarda y se respeta.
- **Lo que nuestro CSS dejaba mal en oscuro, corregido con los tokens de LECTURA del tema** (`--theme-surface`,
  `--theme-text`, `--theme-text-muted`, `--theme-fill`): la caja de los formularios (`.container-standard-form`), sus
  etiquetas y los títulos de sección (los pinta el tema), las tarjetas de estadística (sus `--statistics-*` siguen siendo
  el gancho), la pestaña activa, las cajas blancas fijas que quedaban (`.table-to-cards`, las tarjetas de Colores, el
  filtro del Directorio, los adjuntos y cuatro más) y el fondo del donut del Informe general. **El editor (CKEditor) sigue
  el modo**: la hoja de su adaptador (`rich-editor-adapter.scss`) asigna sus variables (`--ck-color-*`) a las del tema.
  Si tu clon tiene hojas propias en el panel, pinta fondos y textos con esas variables, no con blanco o negro fijos.
  **Provisional:** en oscuro aún se ven claros el gestor de archivos (elFinder), el mapa de MapBox, las imágenes de
  relleno y el logo de fábrica (`statics/images/logo.png`).
- **El selector son tres botones (`ui fluid three mini buttons`), no la pieza segmentada de la guía del tema**: dentro de
  un `ui dropdown`, Fomantic trata cualquier `.menu` anidada como un submenú, la oculta y la coloca fuera del desplegable.

## Cambia — el texto sobre el patrocinador, en el pie del menú

El pie del menú lateral sigue el del diseño: un texto opcional, la imagen del patrocinador y el crédito del desarrollador.
Debajo, en pequeño, van el dueño, los derechos, el año y la versión. El texto es una opción nueva, `partnersText`
(`SettingsController::PARTNERS_TEXT_CONFIG`, hasta 120 caracteres, texto plano): se edita en Configuración › Apariencia ›
Imágenes de marca, junto a la imagen del patrocinador. Vacía, no se pinta. Sale escapada.

## Cambia — las tablas del panel, con el marcado del tema

- **Los listados**: las 30 tablas de 23 vistas pasan a `ui selectable very basic table content-table`, dentro de un
  `ui segment container-standard-table`, como en el diseño. `.container-standard-table` ya solo pone el desplazamiento
  horizontal y el apilado de las celdas en el móvil: sin borde, radio ni relleno propios. Sale la variable
  `$standardTableBorder`. Si tu clon copió el marcado viejo de un listado (`ui basic table content-table`), cámbialo.
- **Rutas y permisos**: `ui selectable very basic padded fixed table`, con el ancho de cada columna.

## ⚠ Rompe — el armazón del panel con el `ui shell` del tema

El menú lateral, la barra superior y el contenedor del panel pasan al componente `ui shell` del tema nuevo. El menú es el
mismo (`get_sidebar_menu()`, los mismos `MenuGroup` de cada módulo) y la barra superior tiene los mismos botones; cambia
el marcado que los envuelve y **cómo se desplaza la página**.

- **La estructura** (`src/app/view/panel/layout/header.php`, `menu.php`, `footer.php`):
  `div.ui.shell.admin-shell` > `nav.navigation` (marca, botón de contraer, `ui vertical accordion menu`, pie) +
  `div.main` > `header.bar` + `main.content`. Salen `div.ui-pcs.container-sidebar`, `aside[main-aside].ui-pcs.sidebar`,
  `div.content` y `.sidebar-toggle`. **Lo que cada vista pinta va dentro de `main.content`**, como antes dentro de
  `div.content`, y `$containerClasses` se le sigue sumando.
- **Ahora se desplaza la página**, no una caja interior. Si tu clon desplaza a mano
  `.ui-pcs.container-sidebar > .content` (por ejemplo, para llevar al usuario a un error del formulario), desplaza
  `html, body`. Lo que se coloque con `position: sticky` arriba tiene que dejar sitio a la barra (64 px; 56 en el móvil).
- **Por ancho de ventana**: desde 1200 px, el menú abierto (se puede contraer a una franja de iconos y se recuerda al
  recargar); de 768 a 1199 px, la franja de iconos, que se abre por encima del contenido; por debajo de 768 px, un cajón
  que se abre con el botón de la barra.
- **El contenido tiene un ancho máximo de 1440 px**, con el relleno del tema.
- **La marca** lleva el logotipo y el nombre de la instalación (`title_app`); la versión pasa al pie del menú, con el
  dueño, los derechos y el año. Las imágenes de patrocinadores siguen la misma regla (no se pintan mientras sean las de
  fábrica): la horizontal con el menú abierto y la vertical en la franja.
- **`pcsAdminSideBarIsOpen()` y `pcsAdminSideBarToggle()` siguen existiendo** y obran sobre el `ui shell`. El resto del JS
  del menú lateral viejo (`pcsAdminSideBar` y su estado en `localStorage`) sale.
- **`admin-shell` es una clase del framework que no se pinta a sí misma: es el ámbito de las reglas del contenido.** Las
  que hacen funcionar el contenido (las tablas que se apilan en el móvil con el título de cada dato, las pestañas que se
  deslizan, la paginación del móvil) se apoyan en ella. El shell **no lleva `ui-pcs`**, así que las reglas de `ui-pcs.css`
  sobre títulos, párrafos y enlaces ya no alcanzan el contenido del panel: los pinta el tema. Si tu clon se apoyaba en
  `.ui-pcs h1` (o `p`, `a`) dentro del panel, usa la clase de tu vista.
- **La barra superior es la del tema, con las mismas opciones**: el menú de usuario es un `ui dropdown` (avatar, nombre
  y tipo; las mismas opciones en el mismo orden), las noticias se abren en un `ui popup`, y Configuración y el perfil en
  línea (editar cuenta, contraseña, foto, eliminar cuenta) en paneles `ui right flyout`. «Volver a mi usuario» sigue en la
  barra al suplantar. Salen `.topbar-content`, `.topbar-options`, `.topbar-switches` y `.topbar-toggle`, y la caja de
  `.profile-content-system` (su contenido se queda).
- **Se reparte en tres parciales**: `topbar-data.php` (los datos), `topbar-bar.php` (los botones, dentro de
  `header.bar`) y `topbar.php` (lo que flota: el popup, los flyouts, las ventanas y la franja de suplantación, fuera del
  shell). Si tu clon pintaba `panel/layout/topbar` en una página propia, pinta también `topbar-bar`.
- **El shell va dentro de un `div.pusher`** (lo pide el `ui flyout` de Fomantic; sin él lo crea al vuelo y avisa en la
  consola). Lo que se ancla arriba descuenta la barra sola: `html` lleva `scroll-padding-top` (64 px; 56 en el móvil; más
  la franja al suplantar), así que un enlace `#ancla` o un `scrollIntoView()` dejan el destino a la vista. Los avisos fijos
  de abajo publican su alto en `--pcs-bottom-nags` y la navegación lo descuenta.
- **Un acordeón de una vista se inicia sobre su propio formulario**, nunca con `$('.ui.accordion').accordion()`: eso
  reinicia también el de la navegación (en la franja de iconos, abría el grupo además del flotante). Los seis del framework
  que lo hacían, corregidos.
- **El crédito del desarrollador** («Desarrollado por …», en el pie del menú) sale de `config.php`: `developer`,
  `developer_logo` y `developer_url`, vacías de fábrica (vacío = no se pinta). Se editan en el código a propósito: el
  crédito es de quien hace el proyecto, y el administrador del cliente no lo cambia desde el panel. Con el menú contraído
  solo se ve el logo. `developer` valía `'Lorem ipsum'` y nadie lo leía.
- Salen las hojas `_sidebar.scss` y `_container-aside.scss` y sus variables (`$sidebarBreakPoint`, los colores del menú
  lateral, `$containerAsideContentPadding*`).

## ⚠ Rompe — las piezas comunes del panel, con el marcado de Fomantic

Las cuatro piezas con las que se componen las pantallas del panel (`src/app/view/panel/built-in/content/`) pintan ahora
el marcado de Fomantic del tema nuevo. Se llaman igual y aceptan los mismos parámetros; lo que cambia es el HTML que
devuelven:

- **La cabecera** (`page-header.php`): las migas son un `<nav class="ui breadcrumb">` con su `aria-label`, el título un
  `<h1 class="ui header">` con la frase en su `.sub.header`, y las acciones van en `ui stackable buttons`, **con la
  principal la última** (a la derecha). La cabecera deja de pintar las clases `content-page-header`,
  `content-breadcrumb` y `section-title`, y sale el CSS de las dos primeras (`section-title` lo siguen usando otras vistas).
- **La barra de guardar** (`form-action-bar.php`): un `<div class="actions">` en lugar de un `ui segment`; primero las
  acciones peligrosas (`ui basic negative button`), luego las secundarias (`ui basic button`) y **«Guardar» la última**
  (`ui primary button`). El texto de cada botón ya no se oculta en el móvil, y desaparece `form-action-text`. Con el tema
  nuevo, la barra queda alineada a la derecha y separada de los campos por una línea, sin caja.
- **El botón principal es `ui primary button`** en las cuatro piezas, no `ui button brand-color`. Lleva el color de marca
  de tu instalación a través del tema nuevo (abajo).

**Si tu clon** tiene CSS que apunta a esas clases, o JavaScript que busca los botones de la cabecera por
`.content-page-header`, deja de casar: búscalos en el marcado nuevo (por ejemplo, `.ui.stackable.buttons > a.ui.button`).

## ⚠ Rompe — el panel y el acceso cargan el tema nuevo de Fomantic

El panel y las pantallas de acceso dejan de cargar el Fomantic de siempre (`statics/plugins/semantic/`) y cargan el tema
nuevo del panel, un Fomantic propio en `statics/plugins/fomantic-piecesphp/` (con su licencia y un `ORIGEN.json` que dice
de qué versión del tema sale). **La zona pública no cambia**: sigue con el Fomantic de siempre.

- **El color de marca** de tu instalación entra al tema como `--accent` (lo hace `ui-pcs.css`, desde
  `--main-brand-color`): los botones principales, los interruptores activos y los acentos salen de él.
- **Se nota a la vista** aunque una pantalla no se haya rediseñado todavía: títulos y campos con las medidas del tema,
  tablas con su piel, botones con icono sin la franja oscura. El menú lateral y la barra superior aún no cambian.
- El tema se renueva desde su repositorio con `bin/traer-tema`, una herramienta del mantenimiento del framework que no
  viaja a los clones.
- **Si tu clon** cargaba `statics/plugins/semantic/` a mano en una pantalla del panel, ahora tendrá los dos: cárgalo con
  `import_front_library('fomantic-piecesphp', [])`, como el panel.
- Las clases `content-page-header`, `content-breadcrumb` y `form-action-bar` vuelven a las piezas comunes, sin estilo: son
  clases del framework sobre las que puedes apoyarte.

## Cambia — Configuración › Sistema, con el marcado de Fomantic

Las siete pantallas de Configuración › Sistema (Salud de la instalación, Avisos del sistema, Estado y cachés, Sitio en
mantenimiento, Rutas y permisos, Seguridad y Respaldos) pasan al marcado de Fomantic, como el resto del panel irá
haciendo: cada bloque en su segmento, los estados como etiquetas de color, las ayudas debajo de cada campo y **sin migas**
(son pantallas a las que se llega desde el panel de Configuración). Hacen lo mismo que antes. Seguridad explica ahora qué
hace cada interruptor.

- `SystemStatusController::systemHeader()` ya no devuelve las migas.
- Sale la hoja `security.css` de Configuración (estaba vacía en la práctica).
- La raíz de cada pantalla conserva `module-view-container` y su clase propia (`security-view`, `backups-view`…): son
  las clases del framework sobre las que un clon puede apoyarse.

## ⚠ Rompe — Avisos, Estado y cachés y Rutas y permisos, con las funciones del prototipo

Tres pantallas de Configuración › Sistema cambian de forma, no solo de marcado:

- **Avisos del sistema**: ocultar o mostrar un aviso es un botón de icono (un ojo tachado para «Ocultar», un ojo para
  «Mostrar») en la columna «Acciones», en lugar de un interruptor. Lo pinta `RowActions`, la pieza de las acciones de fila;
  sus ganchos son `data-system-alert-toggle`, `data-key` y `data-hidden="yes|no"`. Lo sigue viendo solo root, y solo en
  los avisos que se pueden ocultar.
- **Estado y cachés**: una lista con una fila por caché (su icono, su nombre, qué vacía, cuánto ocupa y su botón
  «Vaciar», o «Renovar» en la versión de los archivos estáticos), en lugar de una tabla de tamaños y una fila de botones
  aparte. Los accesos rotos tienen fila propia, con su valor como «N de M». «Borrar todo lo temporal» va al pie, en un
  recuadro. Las peticiones de cada botón no cambian.
- **Rutas y permisos**: una sola tabla de DataTables con buscador, orden y paginación, en lugar del acordeón por módulo.
  El módulo es una columna oculta por la que también se busca.

Para quien registra una limpieza (`CleanTargetRegistry`):

- `CleanTarget::withRow()` acepta un último parámetro, `string $icon = 'archive'`: el icono de Fomantic de su fila. Un
  nombre con otros caracteres que letras minúsculas, números, guiones y espacios se rechaza con
  `InvalidArgumentException`. Su lectura, `rowIcon()`.
- **`UNIT_NUMBER` con dos claves cambia de significado**: antes se pintaba «N · rotos: M»; ahora, «N de M» (la segunda
  clave es el total). Si tu clon registra una fila así, revisa el texto que espera.
- La fila de los accesos directos del núcleo pinta solo `linksTotal`; `linksStatus()` sigue dando `linksBroken` y
  `brokenShown`.

**Y una pieza común más, la zona de una acción principal**: `src/app/view/panel/built-in/content/main-action.php` pinta el recuadro de una acción de peso al pie de una pantalla: el
título y lo que hace a la izquierda, y su botón a la derecha (en el móvil, apilado y con el botón a todo el ancho). Recibe
`title`, `description`, `buttonText`, `attributes` (los del botón, con el filtro de `RowActions::attributes()`),
`variant` (`'primary'` o `'negative'`, la de una acción destructiva) e `icon`, opcional. Su clase del framework es
`content-main-action`. La usa Estado y cachés, y la enseña la hoja de diseño con sus dos variantes.

## ⚠ Rompe — vuelven dos utilidades, revisadas: los pasos y el correo por secciones

En la `9.0.0` (desde la `9.0.0-alpha.5`) salieron por error dos utilidades que el framework ofrece a quien lo clona: si
clonaste la 9, ya no las tienes. Vuelven, mejoradas:

- **`GenericStepsViewHandler`** (JavaScript global, en `statics/core/js/helpers-lib/`): muestra un paso de varios y los
  cambia con botones `data-to-step="next|previous|<valor>"`. Ahora avanza y retrocede por el ORDEN de los pasos que recibe
  (antes solo funcionaba si cada paso llevaba el atributo `registration-form-step`), ignora un segundo cambio mientras el
  primero espera la promesa de `setOnChange()`, y acepta un cuarto parámetro, `options.indicators`: un `ui steps` de
  Fomantic cuyos `.step[data-step]` reciben `active` y `completed` solos. Métodos nuevos: `stepIndex()` y
  `syncIndicators()`. La hoja de diseño la enseña en «Pasos». Ojo: el valor del paso es el número del constructor al
  empezar y el texto del atributo `step` después; compara con `==`, no con `===`.
- **`mailing/generic-dynamic-message`** (la vista de un correo con un título y secciones «etiqueta: valor»): **ahora
  escapa el título, las etiquetas y los valores**, y conserva los saltos de línea del valor. Si tu clon le pasaba HTML en
  los valores, ahora sale como texto. Sin título no pinta el encabezado, y un `message` que no es un arreglo no pinta nada.

## ⚠ Rompe — DataTables 3

El panel pasa de DataTables 2.3.7 a la **3.1.4**, con su integración de Fomantic (`datatables.net-se` 3.1.4) y Responsive
4.1.1, todo MIT y copiado de su CDN con la versión exacta (`src/statics/plugins/datatables/`). La comunicación con el
servidor (`DataTablesHelper`), los textos y las clases `dt-*` no cambian: un listado del framework funciona igual.

- **`import_datatables()` ya no carga RowReorder ni ColReorder**: ningún listado del framework los usaba y DataTables 3
  los cambia de versión. Si tu clon los pide por nombre, el plugin no se carga y queda constancia en el registro de errores
  (`InvalidArgumentException` que lo nombra), sin romper la página. Para usarlos, cópialos de la CDN (RowReorder 2.1.1,
  ColReorder 3.1.2) y decláralos en `src/app/config/assets.php`.
- **En los callbacks de DataTables, `this` ya no es jQuery**: es un objeto propio de DataTables. `this.api()` sigue
  funcionando; `this.DataTable()` y `this.find()` sobre la tabla, no. Si tu clon los usa, cámbialos a `this.api()` y a
  `$(this.api().table().node())`.
- **Las propiedades internas de la configuración** (`settings.aoColumns`, `bVisible`, `nTable`…) pasan a otro nombre en la
  3: usa la API pública (`api.columns().every(…)`, `api.table().node()`). El framework ya lo hace en `helpers.js` y en el
  panel.
- **El estilo de las tablas del panel lo pone el tema**: el panel ya no carga `dataTables.semanticui.min.css` ni
  `responsive.semanticui.min.css` (el tema trae su componente `datatables`); se siguen cargando los dos `.js` de la
  integración, que adaptan el marcado a Fomantic. Las hojas siguen en la carpeta para quien use DataTables fuera del panel.
  En el tema, cifras y fechas van alineadas a la izquierda; para una columna a la derecha, `className: 'dt-right'`.
- **Responsive ya no se carga ni se enciende por defecto**: `import_datatables()` no trae ningún plugin y
  `pcsphpGlobals.configDataTables.responsive` es `false`. Un listado que lo quiera lo pide con
  `import_datatables(['responsive'])` y `responsive: true` en sus opciones. En pantallas pequeñas, la tabla se desplaza
  dentro de su caja.
- **La paginación de todas las tablas es `simple_numbers`** (Anterior · 1-5 · … · N · Siguiente, sin «Primero» ni
  «Último»), y el buscador lleva la etiqueta «Buscar:», como el diseño nuevo. Un listado puede cambiarlo en sus opciones.

## Corrige — seguridad: se refuerzan los listados del panel

Actualiza a esta versión si tienes un clon en producción con una anterior.

- **Un tamaño de página no válido da 10 filas**; antes daba 1. El panel nunca lo envía.

## Corrige — `bin/cli seed … limpiar=yes` ya no se para si un usuario sembrado usó el panel

Si alguien entraba al panel como un usuario de la siembra (por ejemplo, suplantándolo para revisar), lo que ese usuario
hacía quedaba en el registro de acciones y en el tiempo en la plataforma, y el limpiado de la siembra se deshacía entero
con un error de clave ajena (1451). Ahora el limpiado borra, en la misma transacción y antes que al usuario, su
**actividad**: registro de acciones, tiempo en la plataforma, intentos de acceso, noticias leídas y códigos OTP. Lo que
un usuario sembrado **creó o editó** (publicaciones, documentos, organizaciones…) no se borra: si existe, el limpiado para
y nombra la tabla.

## Cambia — la hoja de diseño: su menú nombra todos los ejemplos y se pliega en el móvil

El menú de la hoja de diseño del panel nombra, bajo cada sección, sus ejemplos (31; antes solo nombraba las secciones, y
un ejemplo como «Pasos» no se encontraba). En el móvil (por debajo
de 768 px) queda plegado en un acordeón, «Índice de la hoja», en lugar de ocupar más de 1.300 px antes del primer
ejemplo; en escritorio sigue abierto y acompaña al desplazarse.

## Cambia — avisos emergentes con su icono y acciones de fila con su espacio

- **Estado y cachés** pinta su lista con el marcado del tema (`ui divided list` y el mosaico `primary tinted bordered`);
  sus ganchos `data-system-maintenance-*` no cambian.
- **La suite que cierra todas las sesiones de verdad** (`core/session-revocation-global`) declara el efecto nuevo
  `sessions` y `bin/cli gates` no la corre por defecto (`gates with=external`): cerraba también la sesión de quien estaba
  trabajando en el panel.
- `successMessage()`, `infoMessage()`, `warningMessage()` y `errorMessage()` piden el icono de su tipo (`showIcon`) si quien
  llama no lo da: el tema nuevo pinta los avisos en claro y distingue el tipo por el icono.
- `RowActions` separa sus botones con un espacio en el marcado (el tema no da margen a los botones) y sale la regla
  `.row-actions` que lo suplía.

# 9.3.1 (28-10-2026)

> **Parche de seguridad de las subidas, tras la `v9.3.0`**, y lo último antes de empezar el rediseño del panel (que irá
> en la `10.0.0`). No rompe nada: actualiza sin tocar tu código salvo que tu clon guarde avatares que no sean JPEG, PNG
> o WebP.

## ⚠ Seguridad — la foto de perfil solo acepta JPEG, PNG o WebP, mirando el contenido

La foto de perfil se valida ahora por su contenido, no por su nombre: solo se aceptan imágenes
JPEG, PNG o WebP, y lo demás se rechaza con «Tipo de archivo inválido.» sin tocar la foto que ya hubiera.

- La comprobación vive en `AvatarModel::isAcceptedImage()`, y `AvatarModel::save()` la aplica también: un clon que guarde
  avatares por su cuenta con `save()` queda cubierto.
- **Si tu clon usaba avatares GIF** (o cualquier otro formato), los que ya existen se siguen viendo; para cambiarlos hay
  que subir uno de los tres formatos.

## ⚠ Seguridad — las subidas toman la extensión del contenido

`UploadedFileAdapter::moveTo()` y `::copyTo()` toman ahora la extensión del contenido cuando quien llama no la fija,
igual que `FileUpload::moveTo()` desde la `v9.3.0` (`ProtectedUploads::safeExtension()`). Si quien llama fija la
extensión, se respeta. En el framework la usan la imagen de la página de inicio (Configuración › Imagen principal) y la
ruta de pruebas de la cola; si tu clon llama a `moveTo()` o `copyTo()` sin
extensión, el nombre guardado puede cambiar de extensión.

## Arregla — un adjunto rechazado ya no deja creada la publicación

Al crear una publicación con un adjunto de un tipo no permitido, la respuesta era un error pero la publicación quedaba
creada (sin el adjunto). Ahora los adjuntos se comprueban y se suben ANTES que las imágenes y antes de guardar la
publicación: si uno se rechaza, no se crea la publicación.

- **Lo que sigue pasando, y se arreglará aparte:** al EDITAR una publicación, un adjunto rechazado deja guardados los
  adjuntos válidos que iban antes en la misma petición (medido). Y en un alta con varios adjuntos, si uno válido va antes
  de uno rechazado, el válido queda en disco sin publicación (deducido del código, sin medir).

# 9.3.0 (28-10-2026)

> **Versión estable, tras la `v9.2.0`.** Sobre todo, **seguridad de las subidas**: el RUT y el logo de una organización
> solo aceptan imagen o PDF; un archivo nuevo ya no puede borrar uno ajeno a su módulo; un SVG, HTML o XML subido ya no
> ejecuta nada al abrirlo; y la extensión de un archivo sale de su contenido. Las subidas de los formularios pasan por
> una sola pieza, `FormUploadHandler`, y **el código queda con un formato fijo** que viaja con el framework y que el
> editor también aplica. Es menor y no parche porque rompe algo: lee las secciones ⚠, y «`bin/formatear`» antes de
> fusionar si tu clon modificó archivos del framework.

## ⚠ Rompe — seguridad: el RUT y el logo de una organización solo aceptan imagen o PDF

Hasta ahora el RUT y el logo de una organización aceptaban un archivo de cualquier tipo. Ahora solo aceptan los de abajo.

- **El RUT acepta** `.jpg`, `.jpeg`, `.png`, `.webp` y `.pdf`; **el logo**, `.jpg`, `.jpeg`, `.png` y `.webp`. Sin SVG
  ni GIF. Los tipos viven en `OrganizationsController::RUT_ALLOWED_TYPES` y `LOGO_ALLOWED_TYPES`; los formularios de
  alta, edición y «Mi organización» ofrecen solo esos tipos en el selector de archivos.
- **Si tu clon guarda logos SVG o GIF**, se siguen viendo y la organización se puede volver a guardar sin tocarlos;
  para cambiarlos hay que subir uno de los tipos de arriba.
- **Un tipo rechazado se explica** (aquí y, desde esta misma versión, en Documentos, Publicaciones y Banners): el
  formulario responde «Tipo de archivo inválido.», no «Ocurrió un error interno»
  con una referencia, y ya no deja un error grave en el registro por cada intento.
- **Fuera de este cambio queda** cómo se sirven todas las subidas, que se decide aparte.

## Cambia — `bin/formatear` fija el formato de PHP, JS y SCSS, y viaja con el framework

`bin/formatear` formateaba solo PHP y no salía de este repositorio. Ahora formatea también JS, SCSS y CSS, cada uno con
un formateador **congelado dentro del repositorio**, con las mismas opciones que el editor (VS Code) del framework, y
**viaja a los clones**: el formato no cambia porque se actualice un editor.

- **PHP**: phpfmt 1.2.71 (`bin/tools/phpfmt/`), ahora con las siete `phpfmt.passes` que declara
  `.vscode/settings.json` (antes usaba las dieciséis por omisión de la extensión, que no eran las del proyecto).
- **JS**: el TypeScript del proyecto, fijado a **5.9.3 exacto** en `package.json`, con las opciones de formato de VS Code,
  sin punto y coma y con tabulador. **SCSS y CSS**: js-beautify 1.10.1 (`bin/tools/js-beautify/`, MIT).
- **`.vscode/settings.json`** declara lo mismo para el editor (y `js/ts.tsdk.path` al TypeScript del proyecto: acepta
  «Use Workspace Version» la primera vez).
- `bin/formatear --check --todo` dice cuánto cambiaría; `--universo` lista lo que formatea. Guía: «`bin/formatear`» en
  las guías de entorno.
- **El formato ya está aplicado a todo el código del framework** (522 archivos), en un commit que cambia espacios y
  saltos de línea y, además, lo que las passes declaradas hacen a propósito: quita los `use` que no se usan y ordena los
  demás, pone llaves a un control de una línea, comas finales, `true`/`false`/`null` en minúsculas, quita el `?>` final
  de un archivo solo PHP y, en JS, los `;`. `.git-blame-ignore-revs` lo saca de `git blame` (actívalo una vez en tu
  clon con `git config blame.ignoreRevsFile .git-blame-ignore-revs`).
- **El formato no cambia lo que se escribió a propósito**: no junta ni parte líneas (salvo llaves añadidas a un control
  de una línea, el cuerpo de un método de una línea, dos sentencias en una misma línea y los `use`, que se ordenan y
  pierden los que no se usan), deja cada llave en su línea, conserva la alineación hecha a mano (los `=>` de los
  archivos de idioma) y el espacio que había entre dos piezas. Normaliza: `?Tipo`, `): tipo`, `string|array`, la
  sangría de una continuación (un nivel por dentro de su sentencia), una sola separación entre piezas de una línea y
  nada tras `(` ni antes de `)`, `,` o `;`.
- **Tres comprobaciones dentro de `bin/formatear`**, y un archivo que no las pasa no se escribe: el código de cada PHP
  es el mismo antes y después, salvo espacios, saltos y esos cambios a propósito; formatear dos veces da lo
  mismo que una; y ninguna línea se junta ni se parte fuera de esos casos.
- **El editor formatea igual**: en PHP, `.vscode/settings.json` apunta `phpfmt.php_bin` a
  `bin/tools/formato/php-editor`, que pasa el archivo por `bin/formatear`; sirve en Linux y macOS con git y un shell
  POSIX. En otro sistema, y para el SCSS (la extensión `code-beautifier` no se puede enganchar), la tarea «Formatear
  este archivo» de `.vscode/tasks.json`.
- **Al traer esta versión a un clon que modificó archivos del framework**, la fusión choca en casi todos los archivos que
  tocó, porque cambian sus espacios. Copia primero a tu clon `bin/formatear`, `bin/tools/` y
  `.vscode/settings.json` de esta versión, formatéalo (`bin/formatear --todo`, en un commit propio) y fusiona después: los choques se quedan en lo que cambiaste de verdad.
  Sin probar en un clon real.

## Corrige — seguridad: la extensión de un archivo subido sale de su contenido

La extensión con la que se guarda un archivo subido sale ahora de su contenido y no de su nombre.

- **`ProtectedUploads::safeExtension($archivo, $extensiónDelNombre)`** mira el contenido real del archivo: si es de
  un tipo conocido (JPEG, PNG, WEBP, GIF, PDF, SVG, HTML o XML) y la extensión del nombre no es de ese tipo, usa la de
  su tipo (`ProtectedUploads::CONTENT_EXTENSIONS`). Si el tipo es desconocido (un `.docx`, un binario), se queda la del
  nombre. `jpg` y `jpeg`, `htm` y `html`, se respetan. La extensión se guarda siempre en minúsculas (`foto.JPG` se guarda `.jpg`).
- **Se aplica sola** al guardar con `ProtectedUploads::moveUploadedToPrivate()` y con `FileUpload::moveTo()` cuando no
  se le pasa la extensión. Si tu código le pasa una extensión fija a `moveTo()`, se respeta como antes.
- **Lo que puede notarse:** un archivo subido con un nombre que no casa con su contenido se guarda con otra extensión
  (un PNG llamado `foto.html` se guarda `foto.png`). La ruta guardada en la base es la que devuelve la subida, así que
  sale coherente sola.

## Corrige — seguridad: los archivos subidos con contenido activo se sirven protegidos

Los archivos subidos con contenido activo se sirven ahora con cabeceras que impiden que se ejecuten al abrirlos.

- **Los archivos de subidas con extensión `svg`, `svgz`, `html`, `htm`, `xhtml`, `xml`, `xsl` o `xslt`** se sirven
  ahora con `Content-Security-Policy: sandbox`, y los que no son imagen, además, con
  `Content-Disposition: attachment`. Un SVG se sigue viendo como imagen. Las extensiones viven en
  `ProtectedUploads::ACTIVE_CONTENT_EXTENSIONS` y `ACTIVE_CONTENT_INLINE_EXTENSIONS`.
- **Lo privado** (lo sirve PHP) lleva las cabeceras siempre. **Lo público** (lo sirve el servidor web) las lleva por
  el `.htaccess` de `statics/uploads/`, que el framework reescribe solo en el siguiente arranque.
- **⚠ Si tienes nginx delante de Apache** (HestiaCP, Plesk…), nginx no lee `.htaccess`: **quita esas extensiones de
  la lista de las que nginx sirve por su cuenta**, o pon en nginx las cabeceras para las subidas. Ejemplos de las dos
  formas, con la lista de HestiaCP, en la guía de archivos protegidos, «Lo que puede ejecutar código».
- Los estáticos propios del framework (sus iconos SVG, por ejemplo) no cambian.

## Corrige — seguridad: un archivo reemplazado solo se borra dentro de la carpeta de su módulo

Al sustituir un archivo subido (el logo o el RUT de una organización, el documento o su imagen, las imágenes de un banner
o de una publicación), el framework comprueba ahora que el anterior esté dentro de la carpeta de subidas de su módulo
antes de borrarlo, y el archivo nuevo va a la carpeta de su registro.

- **El anterior solo se borra si está dentro de la carpeta de subidas de su módulo**; si no, se deja donde está y el
  guardado sigue. Vale para Organizaciones, Documentos, Banners y Publicaciones.
- **El archivo nuevo va siempre a la carpeta de su registro** (`folder`), también al sustituir uno.
- **Si un módulo de tu clon copió alguno de estos `handlerUpload`**, pásalo a
  `FormUploadHandler` (la sección siguiente), que lleva la comprobación dentro (`deletePrevious()`, en
  `src/app/core/psr4/PiecesPHP/Core/Forms/FormUploadHandler.php`).

## Cambia — las subidas de los formularios pasan por una sola pieza: `FormUploadHandler`

Organizaciones, Documentos, Banners y Publicaciones tenían cada uno su copia del código que recibe el archivo de un
campo de formulario, lo valida, lo guarda en la carpeta de su registro y borra el anterior; el contenido genérico tenía
una quinta, sin uso. Los arreglos de seguridad de esta versión hubo que hacerlos cuatro veces. Ahora las cuatro usan
`PiecesPHP\Core\Forms\FormUploadHandler` (ADR 0068), y un arreglo de las subidas se hace una vez.

**Para quien usa el panel no cambia nada.** Antes de tocarlas, una prueba por HTTP (`core/form-uploads`) fijó cómo
respondían las cuatro —alta con archivo, edición con y sin él, campo vacío, tipo rechazado, el idioma de Publicaciones y
lo servido con y sin sesión— y sigue igual después.

- **El `handlerUpload` de un módulo queda en pocas líneas** y conserva su firma; dice solo su política, el modo y los
  tipos por omisión:

  ```php
  protected static function handlerUpload(?string $nameOnFiles, string $folder, ?string $currentRoute = null, ?array $allowedTypes = null, bool $setNameByInput = true, ?string $name = null)
  {
      try {
          return FormUploadHandler::forModule(self::UPLOAD_DIR, FormUploadHandler::MODE_PRIVATE)
              ->upload($nameOnFiles, $folder, $currentRoute, $allowedTypes ?? [FileValidator::TYPE_ALL_IMAGES], $setNameByInput, $name);
      } catch (FormUploadRejectedException $e) {
          //Un tipo no permitido es culpa de quien sube: se le explica, y no es un error interno que registrar.
          throw new SafeException($e->getMessage());
      }
  }
  ```

- **Tres modos**: `MODE_PRIVATE`, un archivo que nace privado (Organizaciones, Documentos); `MODE_PUBLIC`, un archivo
  público (Banners); `MODE_PRIVATE_MULTIPLE`, un archivo por campo, privado, leído con `UploadedFileAdapter` (admite el
  campo anidado `campo[0]`) y el único que usa el sufijo `$suffixName` (Publicaciones). El nombre del modo es histórico:
  dos o más archivos en un mismo campo se rechazan (abajo).
- **Un tipo no permitido lanza `FormUploadRejectedException`**; el módulo la convierte en su `SafeException`, y el
  mensaje llega a quien sube sin registrarse como error.
- **`forModule(<carpeta del módulo>, <modo>)`** calcula las rutas dentro de `upload_dir` igual que lo hacían los
  controladores; para otra carpeta, el constructor recibe la ruta en disco, su URL y el modo.
- **Si un módulo de tu clon copió un `handlerUpload`, sigue funcionando**; cámbialo por la pieza para recibir los
  arreglos futuros. El `handlerUpload` sin uso de `GenericContentController` se ha borrado.

Y tres defectos que la prueba de antes destapó, arreglados ya en la pieza:

- **Publicaciones: un campo de archivo sin elegir ya no hace fallar el guardado.** Llegaba como una parte vacía
  (`filename=""`), lo que manda un `<form>` normal; ahora es «sin archivo», como en los otros tres módulos. Con el panel
  no pasaba (medido: su JS no manda esa parte), pero sí con un formulario propio de un clon o una petición a mano.
- **Publicaciones: dos archivos en un mismo campo se rechazan** con «Solo se admite un archivo por campo.», sin escribir
  nada. Antes la fila apuntaba al último y el primero quedaba en disco sin dueño. Ningún formulario del framework permite
  elegir dos.
- **Banners: si el archivo no se puede mover, es un error interno** (se registra) y no se guarda la dirección de la
  carpeta como si fuera la imagen.

## Nuevo — las direcciones dentro de las hojas llevan la huella de su archivo: `static-url()`

En el SCSS propio, una dirección a un archivo (una imagen de fondo, una hoja importada) se escribe ahora con
`static-url('ruta')` en vez de una ruta suelta: al compilar sale con la huella del contenido de ese archivo, así que si
el archivo cambia, el navegador lo vuelve a pedir, y mientras no cambie lo guarda un año. Si el archivo no existe, la
hoja no compila (ADR 0067; ver «Cómo compila SASS» en la guía de gulp).

- **Si tu clon tiene SCSS propio con `url('../images/…')` o `@import url('…')` a un archivo local**, sigue funcionando
  (el navegador lo revalida en cada visita), pero `bin/cli verify-integrity` lo señala: cámbialo por
  `url(static-url('…'))` (o `@import url(static-url('…'))`). Lo externo y `data:` se quedan con `url()`.
- **Una función de Sass anunciada como nueva se prueba con su declaración**: la comprobación 39 de
  `verify-integrity` acepta en `files/dev/announced-capabilities.json` la vía `sass_functions`, que exige el nombre
  declarado en `functions` de `src/gulpfile.js`. La comprobación 55 es la que señala las direcciones sin huella.

## Cambia — la compilación sin `gulp-sourcemaps` ni `gulp-typescript`

`gulp-sourcemaps` (sin mantenimiento; traía 6 de los 9 avisos de `npm audit`, todos de herramientas de desarrollo) y
`gulp-typescript` (en versión alfa, para un solo archivo) salen de `package.json`, y con ellos 100 paquetes que solo
pedían ellos. Los mapas los inicia y los escribe el propio `gulpfile.js` (`initSourceMaps()` y `writeSourceMaps()`), como
hacía `gulp-sourcemaps`, sin leer los mapas que ya traigan las fuentes; TypeScript se compila con el paquete
`typescript`, que ya estaba. Lo compilado sale idéntico, byte a byte, y con los mismos mapas (medido sobre los 90
archivos que genera `gulp init-project`).

- **Si tu clon amplió `src/gulpfile.js` con `sourcemaps.init()`/`sourcemaps.write()` o con `gulp-typescript`**, esas
  líneas dejan de encontrar su paquete: usa las funciones del `gulpfile.js` del framework (`sourcesOf()`, `initSourceMaps()`
  y `writeSourceMaps()`), o reinstala el paquete en tu clon.

## Nuevo — un módulo registra lo que se puede limpiar en «Estado y cachés»

Los botones de limpieza de **Sistema → Estado y cachés** y su tabla de tamaños salen ahora de un registro
(`PiecesPHP\SystemStatus\CleanTargetRegistry`): un módulo con caché propia declara la suya, con su botón y su fila, sin
tocar Sistema (ver «Registrar una limpieza» en la guía de avisos del sistema y mantenimiento). La pantalla y las
respuestas de los cuatro botones de siempre no cambian.

- **`SystemStatusController::CLEAN_TARGETS` desaparece**: si tu clon la leía, usa `CleanTargetRegistry::all()`. La caché
  de Publications la registra Publications.

## Corrige — las pestañas de «Importar y exportar» ya no mandan a la portada al recargar

Al pulsar «Importar» o «Exportar», la dirección del navegador pasaba a la de la portada (`…/#exportar`), porque la
pestaña escribía una dirección relativa y el panel declara su `<base href>`: recargar llevaba a la portada. Ahora la
dirección se arma sobre la de la página (`…/admin/data-transfer/#exportar`) y recargar vuelve a la misma pestaña. Si tu
clon escribe la dirección desde JavaScript con `history.replaceState()` o `pushState()`, ármala con
`new URL(window.location.href)`, como hacen las demás pantallas del framework.

## Corrige — el cargador de páginas ya no se queda con un logo viejo

El logo y la animación del cargador (`statics/images/loader-logo.png` y `loader-animation.gif`) se pedían con la marca
global de caché, que les daba un año: si un clon cambiaba su logo sin limpiar la caché, el navegador seguía enseñando el
viejo. Ahora se piden sin marca y el navegador comprueba en cada visita si cambiaron.

# 9.2.0 (27-10-2026)

> **Versión estable, tras la `v9.1.0`.** La base ya solo aplica las opciones que alguien declaró (el catálogo del ADR
> 0055, segunda versión: **declara las tuyas**); salen las librerías y dependencias que nada usaba (Quill, tidy-html5,
> Fancybox, Jodit, SurveyJS, HubSpot, jQuery Mask, OpenLayers, iziToast, `scssphp`, `php-ffmpeg-video-streaming` y el
> SDK abandonado de Azure Storage) y once funciones globales sin uso; Azure Blob pasa a la API REST con Guzzle; y las
> páginas vuelven a pedir que no se guarden. Es menor y no parche porque quita API pública: lee las secciones ⚠.

## ⚠ Rompe — la base solo aplica las opciones que alguien declaró

Segunda versión del catálogo de opciones (ADR 0055). Hasta ahora, al arrancar, toda fila de la tabla de opciones
pasaba a la configuración (`set_config`) **antes** de registrar las rutas, la hubiera declarado alguien o no: una fila
con el nombre de un valor de `config.php` (`app_lang`, `upload_dir`, una llave de API…) lo pisaba en silencio. Ahora
las opciones se cargan **después** de registrar las rutas, que es cuando los módulos declaran las suyas, y solo se
aplican las declaradas (`ConfigOptions::applyDeclared()`). Lo no declarado sigue avisándose una vez por nombre, como
desde la `9.1.0`, y ya no llega a `get_config()`.

- **Si tu clon guarda opciones propias en la base**, decláralas en el `routes()` de su módulo con
  `ConfigOptions::declare('<nombre>', '<Módulo>')`. Sin la declaración, dejan de aplicarse. El aviso de la `9.1.0` en el
  registro de errores («no está declarada en el catálogo») te dice cuáles son; `bin/cli config-options` lista las
  declaradas.
- **Lo que se lee mientras se registran las rutas** recibe el valor de `config.php`, no el de la base. Medido en el
  framework: ninguna opción de la base se lee por `get_config()` en ese tramo. Las lecturas directas de la tabla
  (`new SettingsModel('<nombre>')`, `SettingsModel::getConfigValue()`) no cambian: leen la fila cuando se piden.

## ⚠ Rompe — Azure Blob sin el SDK abandonado de Microsoft

`microsoft/azure-storage-blob` y `microsoft/azure-storage-common` estaban abandonados (la última versión del primero, la
1.5.4, es del 2022-09-02) y salen de `src/composer.json`; Guzzle, que pedían ellos, se queda, porque el framework ya lo
pedía directamente. `API\Adapters\BlobStorageAzureAdapter` hace lo mismo que antes —leer un archivo de un contenedor,
con su carpeta opcional— hablando con la API REST de Azure Blob por Guzzle, con la misma firma de clave compartida
(versión de la API `2017-11-09`, la que usaba el SDK) y la misma configuración
(`$config['Azure']['BASE_STORAGE_ACCOUNT_NAME']` y `['BASE_STORAGE_ACCOUNT_KEY']`).

- **Se usa igual**: `new BlobStorageAzureAdapter($cuenta, $llave)`, `->read($contenedor, $archivo, $carpeta)` (devuelve
  el archivo o `null` si no existe), `BlobStorageAzureAdapter::init()` y `getConnectionString()`. El constructor acepta
  un tercer argumento opcional, el `GuzzleHttp\Client` con el que se pide, para probarlo sin red.
- **Más rápido**: antes `read()` listaba el contenedor página a página hasta dar con el nombre y luego lo pedía; ahora
  lo pide directamente.
- **Lo que cambia, en `API\Adapters\Packages\BlobStorageFileAzurePackage`**: `properties()` devuelve las cabeceras de la
  respuesta (`array<string,string>`, con los nombres en minúsculas), no un `BlobProperties` del SDK; `lastModified()` sigue devolviendo un
  `\DateTime`, y `content()` y `contentType()`, una cadena. `blob()`, que se llamaba a sí mismo sin fin y nunca pudo
  usarse, sale; el nombre del archivo lo da `name()`.
- **Un error de Azure** (permisos, contenedor que no existe) lanza una `\RuntimeException` con el código HTTP y, si
  viene, el código de error de Azure (`403 (AuthorizationFailure)`), nunca con la llave; y también una respuesta sin
  fecha de modificación (`Last-Modified`), así que `lastModified()` siempre tiene un `\DateTime` que dar.
- **Un nombre con `/` delante** (`/archivo.txt`) ahora se encuentra: se le quitan las barras de los extremos, con carpeta
  o sin ella. Antes, sin carpeta, no se encontraba.
- **Sin verificar contra una cuenta real**: la firma se probó contra la que calculaba el SDK antes de quitarlo
  (`unit-tests:core/blob-storage-azure`), y las peticiones, con respuestas simuladas.

## ⚠ Rompe — fuera iziToast

Por orden del PO, medido antes: se cargaba en ocho páginas y no se usaba. Los cuatro avisos de `helpers.js`
(`successMessage`, `infoMessage`, `warningMessage`, `errorMessage`) ya pintaban el toast de Fomantic, e iziToast solo era
el respaldo para una página sin Fomantic, y ninguna página carga iziToast sin Fomantic.

- Salen `src/statics/plugins/izitoast/`, la clave `izitoast` de `assets.php`, el atajo `import_izitoast()`, sus ocho
  cargas y su crédito en «Acerca de».
- **Los cuatro avisos sin Fomantic** muestran la confirmación de un solo botón (`confirmAction()`), como ya hacían
  cuando no había ninguno de los dos. El cuarto argumento, `options`, son las opciones del toast de Fomantic.
- **Si tu clon llama a `iziToast` directamente o a `import_izitoast()`**, trae la librería en el propio clon: su
  carpeta en `statics/plugins/` y su clave en tu `assets.php` (`import_front_library('izitoast')` para cargarla).
  `iziToast.question` sigue vetado (`bin/cli verify-integrity`, «una sola forma de preguntar»).

## ⚠ Rompe — fuera jQuery Mask, OpenLayers, `scssphp` y `php-ffmpeg-video-streaming`

Por orden del PO, medido antes: nada del framework los usaba.

| Sale | Con ella | Qué usar |
| :-- | :-- | :-- |
| jQuery Mask (`src/statics/plugins/jquery-mask/`) | la clave `jquerymask` de `assets.php` y el atajo `import_jquerymask()` | ningún campo del framework usa máscaras; para validar la forma, `pattern` e `inputmode` de HTML |
| OpenLayers y ol-ext (`src/statics/plugins/open-layers/`) | la clave `openlayers` de `assets.php`, el atajo `import_openlayers()` y nuestro adaptador `src/statics/core/own-plugins/OpenLayersAdapter/` (capas GeoJSON y WMS) | los mapas del framework usan Mapbox (`import_mapbox()`); el adaptador sigue en la historia de git |
| el alias `mapbox` de `package.json` | — | `mapbox-v3.19.0`, que es el que se copia a `statics/plugins/mapbox/` |
| `scssphp/scssphp` (Composer) | `scssphp/source-span`, `league/uri` y `league/uri-interfaces`, que solo pedía él | las hojas se compilan con `sass` desde gulp |
| `aminyazdanpanah/php-ffmpeg-video-streaming` (Composer) | la función global `ffmpeg()` de su `src/helpers.php`, que nadie llamaba; y, con `scssphp`, `symfony/filesystem` y los dos polyfills que solo pedía este (`symfony/polyfill-ctype` y `symfony/polyfill-mbstring`: el framework ya exige `ext-ctype` y `ext-mbstring`) | **`php-ffmpeg/php-ffmpeg`** (la misma v1.4.0 que traía), que es lo único que usaba el framework (la conversión de audio de `FfmpegAudioAdapter`) y ahora se pide directamente |

jQuery Mask y OpenLayers salen también de la lista de créditos de «Acerca de». En `src/composer.json` el `require`
queda en orden alfabético (lo ordena Composer: `sort-packages`). **Si tu clon usa alguna**, tráela en el propio clon
(`composer require` para los paquetes de Composer).

## ⚠ Rompe — fuera seis librerías que nada usaba: Quill, tidy-html5, Fancybox, Jodit, SurveyJS y el cliente de HubSpot

Por orden del PO, medido antes: ninguna la usaba el framework, sus vistas ni su JavaScript.

| Sale | Con ella |
| :-- | :-- |
| Quill (`src/statics/plugins/quilljs/`) | su adaptador `src/statics/core/own-plugins/QuillAdapterComponent.js`, la clave `quilljs` de `assets.php` y el atajo `import_quilljs()` |
| tidy-html5 (`src/statics/plugins/tidy-html5/`) | solo lo cargaba el adaptador de Quill |
| Fancybox (`src/statics/plugins/fancybox/`) | la clave `fancybox3` de `assets.php` y el atajo `import_fancybox3()` |
| Jodit (`src/statics/plugins/jodit/`) | nada más |
| SurveyJS (`src/statics/plugins/surveyjs/`) | sus dos muestras en la hoja de diseño (el creador y el formulario, con su fuente `survey-js-creator` y `survey-js-form` de la ruta `admin-design-sheet-resources`) y `$config['SurveyJSKey']`, que nadie leía |
| `hubspot/api-client` (Composer) | `API\Adapters\HubSpotAPIAdapter`, que nadie llamaba |

Las seis salen también de la lista de créditos de «Acerca de». **Si tu clon usa alguna**, tráela en el propio clon:
su carpeta en `statics/plugins/`, su clave en tu `assets.php` (y `import_front_library('<clave>')` para cargarla) o
`composer require hubspot/api-client`. Lo aprendido al integrar SurveyJS queda en las guías de entorno, que no viajan
con el framework (`source-docs/entornos`, «Herramientas › SurveyJS»).

## ⚠ Rompe — fuera once funciones globales y una constante que nada usaba

Ninguna la llamaba el framework, ni sus vistas, ni sus paquetes (medido por nombre en `src/`, `bin/`, `files/` y
`src/vendor/`). Solo salen las rotas, las que repiten en una línea lo que PHP o el framework ya hacen, y las que
contradicen sus reglas; lo que aporta algo propio a un clon se queda.

| Sale | Por qué | Qué usar |
| :-- | :-- | :-- |
| `remove_char()` | Rota: en PHP 8 lanza `Error` («Cannot assign an empty string to a string offset») | `substr_replace($cadena, '', $pos, 1)` |
| `remove_last_char_on()` | Rota con caracteres de varios bytes: `remove_last_char_on('/', 'año/')` devolvía `'año/'` | `str_ends_with($cadena, $c) ? mb_substr($cadena, 0, -1) : $cadena` |
| `replace_last_char()` | Rota igual: `replace_last_char('año', 's')` devolvía `'años'` | `mb_substr($cadena, 0, -1) . $reemplazo` |
| `imageDimensions()` | Con un archivo que no es imagen lanzaba `TypeError`, y cargaba la imagen entera en GD | `getimagesize($ruta)` |
| `last_char_pos()` | `strlen($cadena) - 1` | eso |
| `replace_first_char()` | `$reemplazo . mb_substr($cadena, 1)` | eso |
| `compare_array_length()` | `count($a) === count($b)` | eso |
| `remove_emptys()` | Pese al nombre, solo quitaba las cadenas vacías | `array_filter($arreglo, fn($v) => $v !== '')` |
| `lang2()` | `lang()` con los parámetros en otro orden | `lang($grupo, $mensaje, $idioma)` |
| `register_routes()` | Un bucle sobre `register_route()`; las rutas se declaran con `Route` y `RouteGroup` | `(new RouteGroup($prefijo))->register([...])` |
| `get_route_by_controller()` | Ignoraba su `$method_request`; las URL salen del nombre de la ruta | `Controller::routeName()` |
| `ADMIN_PATH_VIEWS` | Parecía configurar la carpeta de vistas del panel y nadie la leía: cambiarla no hacía nada | nada: la carpeta es `src/app/view/panel/` |

**Si tu clon usa alguna**, cópiala en sus propias funciones o cámbiala por lo de la columna de la derecha.

## Corrige — `bin/verify` y los censos de SCSS, en un clon recién creado

- `bin/censo-scss` y `bin/huella-css` crean su carpeta de salida (`files/dev/`), que no viaja con el framework: en un
  clon caían con un error al terminar el cálculo.
- `bin/artefactos` da por **generada**, y no por rancia, una foto que git ignora y que todavía no existe
  (`files/dev/route-inventory.json`): en un clon nuevo, `bin/verify` caía en su paso de artefactos.

## Corrige — `bin/cli clean-cache` desde la terminal

Los enlaces de `statics/server-delegated/` que la terminal no puede borrar (son del usuario del servidor web) se avisan
como error leve (`log_soft_error()`) y ya no como error grave (`log_exception()`); la respuesta los sigue contando.

## Cambia — el framework dice con qué Node compila

`package.json` declara `"engines": {"node": ">=22"}` y la raíz lleva un `.nvmrc` con `22`, la versión con la que se
compila y se prueba hoy (medido: Node 22.22.2). Con otra versión, `npm` avisa y no falla; `nvm use` en la raíz elige la
buena.

## Corrige — las páginas vuelven a pedir que no se guarden

Desde la `9.1.0`, leer un mensaje flash ya no abre la sesión de PHP, y con ella se fue la cabecera que PHP añadía a toda
página (`Cache-Control: no-store, no-cache, must-revalidate`): ninguna página la llevaba, tampoco las del panel, con datos
privados (tras cerrar la sesión, «atrás» podía enseñarlas). Ahora el framework vuelve a poner las tres de entonces
(`Cache-Control: no-store, no-cache, must-revalidate`, `Pragma: no-cache` y `Expires: Thu, 19 Nov 1981 08:52:00 GMT`)
en toda respuesta que no traiga ya su propio `Cache-Control`; los estáticos, las descargas, el mapa del sitio y lo que
fija su caché a propósito conservan la suya, sin heredar `Pragma` ni ese `Expires`. Lo vigila
`core/static-cache`, que desde ahora corre en `bin/cli gates`.

## Corrige — `bin/cli clean-logs` dice si un registro no se pudo vaciar

Antes anunciaba «vaciado» aunque la escritura fallara, y un cambio de permisos sobre un archivo de otro usuario del
sistema avisaba (y en local, un aviso aborta). Ahora dice «<archivo> NO se pudo vaciar» y el cambio de permisos no avisa.

## Corrige — la siembra

- Los documentos sembrados van sin miniatura, como un alta sin imagen, y el explorador pinta el icono de su tipo (antes
  todos llevaban la imagen genérica).
- La simulación de `bin/cli seed` rotula con su tabla (`pcsphp_task_runs`) el mapper que `scheme-create` no ve, y no con
  su clase.

## Corrige — instrumentos que viajan con el framework

- `bin/huella-css`, ante una hoja que desaparece, enseña las reglas que se van antes de parar.
- Una suite corrida sola dice también cuántas líneas de `error.plain.log` y bloques de `error.unique.message.log` podó,
  con el mismo texto que `bin/cli gates`.

# 9.1.0 (26-10-2026)

> **Versión estable, la primera tras la `v9.0.0`.** `database` 5.5.3 (guardar escribe solo lo que cambió), el catálogo de
> las opciones de la base en modo aviso (la versión siguiente dejará de aplicar lo no declarado: declara las tuyas),
> el explorador del editor enriquecido otra vez en marcha, `html` y `geojson` 4.0, la suplantación de root acotada y
> navegar sin aprobar usuarios. **Las rupturas van marcadas con ⚠ en cada sección**: quien actualiza un clon desde la
> `9.0.0` las lee todas antes de empezar.

## ⚠ Rompe — navegar ya no aprueba a un usuario: se aprueba al crearlo en una organización aprobada

Hasta ahora, un usuario pendiente de una organización ya aprobada quedaba activo, y su fila de aprobación aprobada, la
primera vez que el framework comprobaba su aprobación: en cada petición de ese usuario o en el arranque, al dar de alta
su fila en Aprobaciones. Comprobar escribía.

Ahora `UsersApprovalHandler::isAutoApproval()` solo lee. En el alta de usuarios
(`UsersController::createUserFromRequest()`, por la que pasan el panel y el alta pública de la API) la aprobación por la
organización la hace `UsersApprovalHandler::approveIfOrganizationApproved()`. La aprobación de una organización sigue
aprobando, como antes, a sus usuarios activos y pendientes, con su propia lógica
(`OrganizationApprovalHandler::onApprovedSpecificMapper()`), que aplica la misma regla.

- **Un clon que crea usuarios con su propio código** y quiere que un pendiente de una organización aprobada quede activo
  llama a `UsersApprovalHandler::approveIfOrganizationApproved($usuario)` después de guardarlo, con
  `SystemApprovalsRoutes::ENABLE` encendido. Si no lo llama, el usuario se queda pendiente y su aprobación aparece en
  Aprobaciones para que una persona decida; antes se activaba solo en la primera petición de quien fuera.
- **Al actualizar**: los pendientes que ya existan en organizaciones aprobadas siguen pendientes, con el acceso
  recortado, hasta que alguien los apruebe en Aprobaciones; antes se activaban en su primera petición. Un usuario
  activo cuya fila siga pendiente conserva su acceso, y su fila queda a la vista hasta que una persona la resuelva.
- **Lo que no cambia**: root, los tipos que se autoaprueban y los activos no quedan recortados; uno rechazado por una
  persona no lo aprueba su organización.
- Pruebas: `unit-tests:core/auto-approval-status` (14 casos: la petición que ya no aprueba, el alta que sí y el rechazado por una persona, que la organización no aprueba) y el
  caso b26 de `unit-tests:core/status-transitions`.

## Corrige — root puede terminar la suplantación de un usuario pendiente o rechazado

Al suplantar a un usuario recortado (pendiente o rechazado), root no podía usar «Volver a mi cuenta» y se quedaba dentro
hasta que la suplantación caducaba, a los 60 minutos. Ahora puede terminarla. Prueba: el caso d3 de `unit-tests:core/impersonation` (declara red: `gates` la corre con
`with=external`).

## Corrige — las pruebas ya no dejan rastro en `error.plain.log` ni en `error.unique.message.log`

Desde la `9.0.0` lo que escribe una prueba en `error.log.jsonl` lleva la marca de su corrida y se poda al terminar (ADR
0063). Los dos registros de texto se quedaban fuera y acumulaban para siempre lo de las pruebas. Ahora:

- la línea de `error.plain.log` lleva `[test <corrida>]` tras `[ref ERR-…]`, y el bloque de `error.unique.message.log`
  la lleva al final de su primera línea, dentro de la firma (así un error real con la misma firma que uno de prueba se
  registra igual);
- la poda los limpia por bloques, con el mismo cerrojo, la misma comprobación final (si un solo bloque que se iba a
  quitar no lleva la marca, ese archivo no se toca) y la misma reescritura atómica;
- las dos escrituras van con cerrojo, y la del único comprueba la firma bajo él;
- `gates` dice cuántas líneas y bloques podó de cada uno.

Lo rotado en `olds/` sigue sin podarse. Prueba: `unit-tests:core/test-errors-prune` (20 casos, con un error real en
medio que queda byte a byte).

## Cambia — el framework pasa a `piecesphp/database` 5.5.3: guardar escribe solo lo que cambió

`src/composer.json` pide ahora `piecesphp/database` `^5.5.3`; actualiza un clon con
`composer update piecesphp/database`. `EntityMapper::update()` escribe solo las columnas que cambiaron respecto a la
fila tal como se leyó, y un guardado sin cambios no lanza ninguna consulta ni dispara el evento «actualizado» (el
contador de filas cambiadas queda en `0`, como antes). Detalle en el `CHANGELOG` del paquete.

- **Lo que NO arregla, medido**: los formularios del panel envían todos sus campos. Si dos personas abren la misma
  edición y cada una cambia un campo distinto, el segundo guardado sigue devolviendo el campo del primero a su valor
  anterior, porque llega en el envío con el valor que tenía al abrir el formulario y, frente a la fila recién leída, es
  un cambio. Medido con dos sesiones sobre una coordenada de Ubicaciones: el nombre que cambió la primera se perdió y
  quedó la dirección de la segunda. Lo que sí deja de pisarse es lo que escribe código que asigna solo lo que cambia.
- **La comparación es por la forma de texto** (`(string) $guardado === (string) $nuevo`; `NULL` solo es igual a `NULL`):
  el texto `'2492'` de un envío frente al entero `2492` de la base no cuenta como cambio, y por eso un formulario guardado
  tal cual no escribe nada; `'0e1'` frente a `'0e2'`, o `'1e3'` frente a `'1000'`, sí cuentan.
- **Un campo `json` anulable que se guarda vacío queda en `NULL`** (5.5.3), no con el texto `'null'`; una fila vieja con
  ese texto se iguala en su siguiente guardado, que entonces cuenta como cambio (dispara el evento «actualizado») una
  sola vez. En una instalación local medida, solo lo tenía `organizations_elements.actionLines` (5 filas). Para
  igualarlas de una vez: `UPDATE organizations_elements SET actionLines = NULL WHERE actionLines = 'null';`
- **Y de paso, en el paquete** (5.5.1 a 5.5.3): una condición con `$`, `?`, `=`, `-` y otros caracteres en la
  expresión ya no rompe la consulta; `having()` y `join()` aceptan un criterio sin `and_or`; `getCompiledSQL(true)` sin
  conexión lanza una excepción que lo explica; la consulta compilada no altera los textos con dos espacios. El
  geovisor ya no rodea lo de los caracteres con `CHAR(36)`: su respuesta sale idéntica byte a byte.

## Nuevo — el catálogo de las opciones que la base puede dar (ADR 0055), primera versión: avisa

Hasta ahora, cualquier fila de la tabla de opciones se aplicaba al arrancar con `set_config()`, también una con el
nombre de un ajuste fijo de `config.php`: el nombre de la fila decidía qué ajuste se cambiaba. Ahora cada módulo
**declara** las opciones que guarda con `ConfigOptions::declare()`, y el framework declara las suyas (69 en una
instalación con español e inglés). Una fila que nadie declaró **se avisa una vez** en el registro de errores, por su
nombre y sin su valor, y **en esta versión se sigue aplicando**. `bin/cli config-options` lista el catálogo y las filas
que no lo cumplen.

- **Qué tiene que hacer un clon con opciones propias**: declararlas en el `routes()` de su módulo (receta en
  `configuration.md`, «Las opciones que la base puede dar se declaran»). La versión siguiente dejará de aplicar lo no
  declarado.
- **Revisa tu tabla**: `bin/cli config-options` dice qué filas no reconoce; si no son tuyas, bórralas.
- Lo vigilan `core/config-options` y `core/config-options-warning`.

## ⚠ Rompe — el listado de organizaciones del panel ya no publica el esquema de la tabla

`GET admin/organizations/all/` (la ruta `organizations-admin-ajax-all`) mandaba en cada `parsedElements[]` el mapper
entero, y con él el esquema de la tabla: `table`, `primaryKey`, `foreingsKeys` y la definición de todas sus columnas
(`fields`). Ahora manda los mismos datos sin esas cuatro claves; el resto de la respuesta no cambia. Ninguna pantalla
del framework pide esa ruta; si un clon lee esas claves de ahí, deja de tenerlas. Lo vigila
`core/organizations-incomplete` (f2).

## Corrige — el botón de insertar archivo del editor enriquecido vuelve a abrir el explorador

Desde la `6.3.0`, el botón «Insertar imagen o archivo» de todos los editores enriquecidos del panel abría una ventana
con un 404 en lugar del explorador de archivos, y pegar una imagen en el editor iba a la misma dirección rota. El
adaptador armaba la dirección a mano con `admin_url`, que vale `''`, y el explorador vive bajo `admin/`. Ahora la da el
servidor: el módulo FileManager publica al frente `richEditorFileManagerURLs` con `routeName()`, y el adaptador la lee.
Si un clon pasaba sus propias direcciones al adaptador (`fileManagerConfigURL`, `fileManagerUIURL`), siguen mandando. Lo
vigila `core/rich-editor-file-manager-urls`.

## Quita — el cliente comercial de CKFinder

Se retira `src/statics/plugins/ckeditor/ckfinder/ckfinder.js`, el cliente de CKFinder, que es un producto comercial con
licencia propia y viajaba en cada clon. El editor no lo usaba: su orden de insertar archivos la sustituye el adaptador
por el explorador de elFinder. Medido en el navegador: con y sin él, el editor y el explorador se comportan igual.
Fuera también su entrada `ckfinder` de los plugins de `ckeditor` en `app/config/assets.php`.

## Corrige — el correo de prueba de Configuración valida la dirección por su forma

La pantalla que envía un correo de prueba validaba el destinatario con `Validator::isEmail()`, que no mira la forma:
solo pregunta por DNS si el dominio tiene servidor de correo. Aceptaba una dirección mal formada con un dominio bueno
(`x y@mailinator.com`) y rechazaba una buena si el DNS fallaba en ese momento. Ahora la valida con `filter_var`, como
el resto del framework. `Validator::isEmail()` no cambia. Lo vigila `core/mail-settings-tests` (b6).

## Corrige — cuatro instrumentos de `bin/`

- **`bin/navegador`** respeta el `--perfil` de la línea de órdenes (antes ponía el suyo delante y el dado se ignoraba en
  silencio; la variable `PCSPHP_NAVEGADOR_PERFIL` sigue igual), y `bin/navegador --help` imprime la ayuda en vez de
  lanzar el recorrido.
- **`bin/guarda-add`** cuenta un archivo renombrado como un borrado y un añadido para su aviso de la foto de borrados;
  antes un renombrado no avisaba.
- **`bin/censo-borrados`** ya no da por decidido el borrado de un archivo porque el registro nombre la ruta exacta de
  OTRO archivo de su carpeta. Sigue avalando por la carpeta cuando se nombra la carpeta misma (límite conocido).
- **`bin/censo-sql-concatenado --trinquete`** para también cuando una entrada declarada tiene MENOS concatenaciones que
  su cota, y dice a cuánto bajarla: la lista solo puede encoger.

## Corrige — cerrar la sesión borra la cookie del token

`PiecesPHPSystemUserHelper.deleteSession()` caducaba la cookie del token con un número de milisegundos, que no es una
fecha: el navegador la conservaba después de cerrar la sesión. Ahora la caduca con una fecha pasada, como ya hace
`PiecesPHPGenericHandlerSession`, corregido antes en esta misma versión. Medido en Chromium: la cookie estaba antes y ya no está después. Lo
vigila `core/session-contract` (l4).

## ⚠ Rompe — el framework pasa a `piecesphp/html` 4.0 y `piecesphp/geojson` 4.0

`src/composer.json` pide ahora `piecesphp/html` `^4.0` y `piecesphp/geojson` `^4.0`; actualiza un clon con
`composer update piecesphp/html piecesphp/geojson`. Lo que cambia para quien los use directamente:

- **`piecesphp/html` escapa por defecto** el texto y los atributos (antes no escapaba nada). El HTML de confianza va
  con `setRawContent()` o como hijos; si escapabas antes, quita tu escape o saldrá doble. Un nombre de etiqueta o de
  atributo raro lanza. Detalle en su `CHANGELOG`. En el framework solo lo usaba el menú (`MenuGroup`), que ya
  construye su icono y su nombre como hijos; las capturas del menú lateral salen idénticas. **Fuera
  `menu_sidebar_items()`** (`src/app/config/functions.php`), que no llamaba nadie.
- **`piecesphp/geojson` sale en `[longitud, latitud]` por defecto** (RFC 7946); `ORDER_LAT_LNG` queda en desuso. El
  geovisor genera ya su GeoJSON con la librería: la respuesta de sus puntos es idéntica byte a byte a la de antes.

Lo vigilan `core/menu-group-html` y `core/geo-sources`.

## ⚠ Cambia — un error dentro de una vista llega a quien la pidió, y se registra una vez

`BaseController::render()` y `_render()`, ante una excepción de la plantilla, la registraban, pintaban la página 500
y morían: quien pedía la vista (por ejemplo, un correo que se arma con una plantilla) no podía capturar el fallo, y
cada error de vista quedaba **dos veces** en Registros › Errores, con dos referencias gemelas. Ahora relanzan la
excepción sin registrarla. Por HTTP no cambia nada visible: la recoge el manejador global, que pinta la misma página
500, ahora con **una sola** referencia. **Si tu clon envuelve `render()` en un `try` y quiere el error en el registro,
regístralo él** con `log_exception()`. Lo vigila `core/mail-templates-escape` [5/5].

## Corrige — la página 500 declara su favicon

La página de error 500 no declaraba icono y el navegador pedía `/favicon.ico` a la raíz del dominio (404). Ahora usa el
favicon del sitio, como la 403 y la 404.

## Corrige — pedir en silencio una ruta sin sus parámetros ya no tumba la página

`allowedRoute()`, `routeName(…, true)` y `get_route(…, true)` sobre una ruta que lleva parámetros, llamados sin ellos,
lanzaban («Missing data for URL segment») aunque pedían silencio: la página daba 500 en vez de no enseñar el enlace
(pasó el 2026-10-22). Ahora, en silencio, responden como con una ruta que no existe (`false`, `''`, `null`); sin
silencio, siguen lanzando. Lo vigila `core/anonymous-route-access` [4/4].

## Corrige — el desempate de las tablas del panel no se pierde

`DataTablesHelper::generateOrderBy()` daba por puesto el orden de desempate (`custom_order`) si su columna aparecía
como subcadena de la que ordenaba el usuario, y lo quitaba: al paginar se podían repetir o saltar filas. Ahora compara
el nombre exacto de la columna. De los 20 usos del framework cambian dos: ordenar por fecha en Registros › Accesos
(`dateFormat` gana su desempate por `date`) y en Aprobaciones (`referenceDateFormat`, por `referenceDate`). Lo vigila
`core/sql-placeholders` §13.

## Cambia — un enlace dentro de un texto del panel se distingue

Un enlace en medio de un texto del contenido del panel (párrafos, listas, citas) tenía el color del texto, y los
visitados se veían negros. Ahora lleva el color de marca (`--main-brand-color`) y subrayado, y no hay color propio de
visitado. No cambian los enlaces con clase (botones, etiquetas, migas), los de las tablas, el menú lateral ni la barra
superior. Hoy solo la hoja de diseño tiene enlaces así; las capturas del inicio, un listado y Configuración salen
idénticas.

## ⚠ Rompe — una ruta sin nombre ya no arranca

`RouteAdapter` inventaba un nombre con `uniqid()` a la ruta que no traía ninguno: cambiaba en cada arranque, así que no
se le podía dar permiso ni enlazarla. Ahora el framework se niega a arrancar con una ruta sin nombre (o con nombre
vacío) y dice cuál. En el framework no hay ninguna (0 de 378, medido). **Si tu clon declara una ruta sin nombre, dale
uno**: el nombre es su permiso. Lo vigila `core/route-validation`.

## ⚠ Seguridad — la llave del cron ya no trae un valor de ejemplo

`config.php` ya no declara una llave del cron de ejemplo. Sin `secure-keys/cronjob`, la ruta del cron rechaza toda llave.
**Si tu clon lanzaba el cron con la llave de ejemplo que traía `config.php`, crea `secure-keys/cronjob` con una llave
propia**. Lo vigila `core/task-runs`.

## Corrige — el tamaño máximo de subida por defecto

Sin un máximo propio, una subida tomaba el menor de `post_max_size` y `upload_max_filesize` comparándolos como texto y
quitando la «M»: con «1G» el máximo era 1 MB, con «512K» 512 MB, y con «0» (sin límite) se rechazaba todo. Ahora se
convierten a bytes (`FileValidator::iniSizeToBytes()`, K, M y G), se comparan como números y «0» es sin límite
(`FileValidator::defaultMaxBytes()`). Un máximo explícito no cambia. Lo vigila `core/file-validator-max-size`.

## Corrige — las traducciones dinámicas pendientes no se pierden

Al volcarlas de la base al archivo, el framework escribía el JSON sin comprobarlo y vaciaba la base después: si la
escritura fallaba, se perdían. Ahora `DynamicTranslationsHelper::saveCurrentDynamicTranslationsJSON()` devuelve si
escribió (a un temporal y con `rename()`), y la base solo se vacía si lo hizo; si no, queda en el registro. Lo vigila
`core/dynamic-translations-sync`.

## Corrige — defectos del núcleo, de bajo riesgo

- **`import_front_library()` con `$all`** pisaba su parámetro con el nombre del último plugin y limpiaba la lista de
  otra librería («Undefined array key»). Reimportar un plugin sigue volviendo a pasar sus archivos (un archivo quitado
  a mano vuelve), como antes. Lo vigila `core/import-front-library`.
- **El límite de tokens de la IA** se declaraba dos veces; un valor mal formado daba 500 y ahora 400, como todo error de
  validación de esa acción (antes, 200 con `success: false`).
- **«Identidad y SEO» guarda solo lo que cambia**, e intenta cada opción aunque otra falle.
- **Los colores de la acción genérica** solo pasan a mayúsculas si son `#hex`: un `rgba()` se guarda como se escribió.
- **Los formularios enseñan el motivo del servidor** en una respuesta 4xx (antes, «error al conectar con el servidor»):
  `genericFormHandler`, con el texto de reserva traducido.
- **La hora de cada operación** de las respuestas (`Operation`) sale en 24 h (`Y-m-d H:i:s`); antes, en 12 h y sin AM/PM.
- **La marca de caché de una URL con una entidad numérica** (`&#039;`) ya no parte la URL por el `#` de la entidad. Lo
  vigila `core/static-versions-entities`.
- **El gestor de archivos** ya no declara `'permissions' => 0755` en sus raíces ni cambia los permisos de las que ya
  existen (una raíz que falta se sigue creando con 0755): en un servidor donde funcionara le quitaba al grupo la
  escritura que pone `permissions.sh`. `structureOptions()` es pública:
  si tu clon le pasaba `'permissions'`, ya no se aplica.
- **Sustituir una imagen** (fondos, logos, contenidos genéricos, banners, categorías de noticias, organizaciones,
  documentos y publicaciones) ya no da error si la anterior no existe o no se puede borrar: se registra y la respuesta
  no cambia. Lo vigila `core/previous-file-missing`.
- **Un recurso de la hoja de diseño** (el formulario de SurveyJS) declara su favicon; **la siembra** da a las categorías
  de noticias colores distintos; **el código de un solo uso** ya no enciende la depuración del correo.

## Corrige — con el idioma fuera de la URL el framework arranca

Con `lang_by_url` en `false` el arranque caía («Using null as an array offset»), y con `lang_by_browser` también en
`false` las rutas salían con doble barra (`//admin/`). Ahora, sin idioma en la URL, el prefijo es vacío y el idioma
sale del navegador, la cookie o el de por omisión. Con `lang_by_url` en `true` (lo de fábrica) nada cambia: el
inventario de rutas sale igual. Lo vigila `core/lang-by-url-off`.

## Cambia — el aviso de una organización aprobada va a su encargado

Iba a quien la creó. Ahora va a su encargado y, si no tiene o está eliminado, a quien la creó (P216). Lo vigila
`core/approval-forms` [e].

## ⚠ Rompe — una fuente del geovisor implementa `filterOptions()`; el filtro de organizaciones busca bajo demanda

La interfaz `PiecesPHP\Geo\GeoSource` pide un método nuevo, `filterOptions(string $key, string $search, int $limit):
array`: las opciones de uno de sus filtros remotos que casan con la búsqueda (valor → texto, sin escapar), o un arreglo
vacío si el filtro no es suyo o no es remoto. **Si tu clon tiene su propia `GeoSource`, añádele el método**; sin
filtros remotos basta `public function filterOptions(string $key, string $search, int $limit): array { return []; }`.
El tipo nuevo de filtro es `GeoFilter::TYPE_REMOTE_OPTIONS` (sin opciones en línea). El filtro «Organizaciones» del
geovisor ya no carga todas las organizaciones en la página: el desplegable busca al escribir, por la ruta nueva
`geo-admin-filter-options` (`{geo}/filters/{key}/options/`, con el permiso del geovisor, hasta 20 resultados). Y
`filters()` de las dos fuentes ya no consulta la base. Lo vigila `core/geo-sources` (c14-c17, h8-h11).

## Cambia — leer un mensaje flash no abre una sesión

Toda petición, también la portada pública, abría la sesión de PHP (`PHPSESSID`) porque mirar si había mensajes flash
la arrancaba: todo visitante, robots incluidos, recibía una cookie y dejaba un archivo de sesión en el servidor, y PHP
añadía `Cache-Control: no-store` a toda página. Ahora `FlashMessages` y `SessionDataHandler`, al LEER, no arrancan una
sesión si no está abierta y la petición no trae su cookie: responden «no hay». **Escribir un mensaje la sigue
abriendo**, como antes. Medido en la portada local: sin `Set-Cookie` y sin `Cache-Control`. Lo vigila
`core/session-on-demand`; el camino de escribir en una petición y leer en la siguiente (la suplantación), `core/impersonation`.

## Corrige — la errata `DYNAMIC_TRANSCALTIONS`, con su migración

La fila de configuración donde esperan las traducciones dinámicas pendientes se llamaba
`…|DYNAMIC_TRANSCALTIONS`. Pasa a `…|DYNAMIC_TRANSLATIONS` (la constante es ahora
`GenericContentPseudoMapper::CONTENT_DYNAMIC_TRANSLATIONS`, y `dataConfigName` de `src/app/config/lang.php`). **En una
instalación que ya existe, aplica `databases/actualizaciones/2026-10-26-errata-traducciones-dinamicas.sql` ANTES de
desplegar el código**: renombra la fila y la clave de su JSON; si el código nuevo ya creó la fila nueva vacía, la borra
primero. Si se despliega sin aplicarlo no se pierde nada: mientras la fila nueva no exista, se leen los datos de la
vieja, sin escribir en ella (`GenericContentPseudoMapper::LEGACY_CONTENT_DYNAMIC_TRANSLATIONS`), y el primer guardado
ya va a la nueva. Lo vigila `core/dynamic-translations-sync`.

## Corrige — se puede guardar una organización cuyo encargado fue eliminado

El desplegable del encargado solo ofrece usuarios que no estén eliminados, así que, si el encargado actual lo estaba,
el campo quedaba vacío y obligatorio: no se podía guardar nada de la organización hasta elegir otro, y nunca si no había
otro candidato. Ahora, en ese caso, el campo deja de ser obligatorio y avisa «El encargado actual fue eliminado: elija
otro cuando pueda.»; guardar sin elegir conserva el encargado. Lo vigila `core/organizations-incomplete` [d].

## Corrige — aprobaciones: un usuario eliminado no cuenta como pendiente, y no se avisa al usuario de sistema

Las aprobaciones pendientes de usuarios Eliminados se listaban y se contaban en el inicio (las de organizaciones ya
no). Y el aviso por correo de una aprobación ya no se intenta enviar al usuario de sistema ni a una dirección
`.invalid`: se omite sin error. Lo vigila `core/approval-forms` [d].

## Corrige — caché y estáticos

- **La página 404 ya no pide `statics/css/404.css`**, que no tenía fuente ni viajaba con el framework: en un clon daba
  404. Ya llevaba `errors.css`, como la 403 y la 503; se ve igual (capturas idénticas).
- **El CSS de variables del panel se versiona por su contenido.** Su URL lleva como `cacheStamp` la huella de lo que
  sirve (`CSSVariables::fingerprint()`, `versionedURL()`, `globalVersionedURL()`), y solo con esa huella se guarda un
  año (`immutable`); sin marca, o con otra (la marca global que llevan los `globals-vars.css` de los módulos), se sirve
  `no-cache` y se revalida con su ETag. Cambiar un color cambia la URL. `CSSVariables::toResponse()` acepta
  `?string $cacheControl` (nulo: esa regla). Lo vigila `core/css-variables-version`.
- **`clean-cache` borra los enlaces delegados con `ServerDelegatedLinks::deleteAll()`** y dice cuántos no pudo. Desde la
  terminal sigue sin poder borrar los que crea el servidor web (carpetas de `www-data`): para esos, «Borrar accesos rotos»
  en el panel.
- **`clean-cache` barre las miniaturas del geovisor de puntos que ya no existen** (`GeoThumbnail::sweep()`); una borrada
  de más se regenera al pedirse.
- **Los mapas de fuentes (`.css.map`) apuntan a su `.scss`**, relativos a la carpeta del propio `.map` (`gulpfile.js`).
  Medido: de 0 a 42 de 59 que resuelven; los que no, son hojas sin fuente o que no compila gulp. Los `.css` salen
  idénticos.

## Corrige — Informes

La URL de los datos del informe genérico la da el servidor (atributo de la vista) en vez de armarse en el JavaScript, y
salen cuatro traducciones inglesas que nadie pedía.

## Cambia — la vista de osTicket recibe solo si hay clave, no la clave

La pantalla Configuración › Integraciones › OsTicket ya no pintaba la clave guardada (el campo va vacío y, vacío, la
conserva), pero el controlador seguía pasándosela a la vista, que solo miraba su longitud. Ahora la vista recibe
`$hasKey` (`bool`) en lugar de `$key`, y la dirección guardada se pinta escapada. **Si tu clon sobrescribe la vista
`panel/pages/app_configurations/os-ticket.php`**, cambia `mb_strlen((string) $key) > 0` por `$hasKey`. Lo vigila
`core/view-escapes` [f].

## Corrige — `preferSlug` no esconde el id

El comentario de la propiedad `preferSlug` decía que era «un token usado para acceso individual sin exponer el ID» en
ocho mappers: `UserProfileMapper`, `NewsMapper`, `NewsCategoryMapper`, `OrganizationMapper`, `DocumentTypesMapper`,
`CategoriesMapper` (formularios), `PublicationCategoryMapper` y `PublicationMapper`. No es así: en los ocho es el id con
un sufijo aleatorio, **ofuscado** con `BaseHashEncryption::obfuscate()` usando como clave el nombre de la tabla, y se
puede deshacer (todos menos `UserProfileMapper` lo hacen ellos mismos con `deobfuscate()` para buscar por slug). Sirve para que la
dirección no sea un número correlativo, no para ocultar nada. Solo cambia el comentario.

## Quita — tres restos que no hacían nada

- **`src/app/core/extensions/patches_composer_dependencies.php`**: definía `ffmpeg()` solo si no existía, para
  `aminyazdanpanah/php-ffmpeg-video-streaming`. La versión instalada (v1.3.1) la declara con los mismos tipos en su
  `src/helpers.php`, que Composer carga antes que las extensiones, así que el parche no entraba nunca. Nadie en el
  framework llama a `ffmpeg()`. Si tu clon usa esa librería, no cambia nada: la función sigue saliendo de ella.
- **`src/app/classes/PiecesPHP/Settings/Statics/sass/includes/_base.scss`**: una hoja parcial que ninguna otra
  importaba; no aparecía en ningún CSS compilado.
- **La línea `src/statics/login-and-recovery/css/*` de `.gitignore`**: esa carpeta no existe desde que las hojas del
  acceso pasaron al módulo `UserSystem`. Las imágenes de `statics/login-and-recovery/images/` siguen donde estaban.

## Cambia — el resumen de Registros › Accesos cuenta sin cargar todos los intentos

La vista «Intentos» de la pestaña Accesos enseña tres cifras (total, exitosos y erróneos), y para calcularlas cargaba
en memoria TODAS las filas de `login_attempts` y las filtraba una a una con la regla de quién ve qué (P133). Ahora la
base cuenta por usuario y resultado (`LoginAttemptsModel::countsBySuccess()`), y la misma regla se aplica a cada grupo:
las cifras son idénticas, porque la regla solo mira el usuario, su tipo y su organización. Medido con 652 intentos: de
unos 2,8 ms a 1,1 ms y de 3,8 MiB a 0,23 MiB de memoria; lo que se ahorra crece con los intentos, porque los grupos
crecen con los usuarios. La exportación sigue con las filas, que son su contenido. `LoginAttemptsModel::all()` acepta
además quién mira (`$actor`, opcional). Lo vigila `core/login-attempts-counts`.

## Seguridad — los estáticos validan la ruta antes de servirse

`ServerStatics` valida ahora la ruta pedida al principio, antes de buscar el archivo, también en lo que delega en el
servidor web. El camino rápido no se frena. La lista de caracteres permitidos sigue solo en el camino de PHP: un archivo
delegado con espacio o `@` en el nombre se sirve como antes. La vigila `core/server-statics` (§6).

## Seguridad — los desplegables escapan el valor y el texto de cada opción

Las opciones de casi todos los desplegables del panel salen de `array_to_html_options()` (53 llamadas en el framework).
Ahora escapa el valor y el texto (`htmlspecialchars` con `ENT_QUOTES`) **sin volver a escapar lo que ya llega
escapado** (`double_encode` en `false`): las llamadas que escapaban antes —`array_map('escape_html', …)`,
`Locations::escapeOptions()`, `CountryMapper::phoneCodeOptions()` y `demonymOptions()`— salen igual que antes, y una
entidad puesta a propósito (`&nbsp;`) se conserva; por lo mismo, lo que un usuario escriba con forma de entidad
(`&amp;`, `&eacute;`) se pinta como su carácter. El valor seleccionado se sigue comparando con la clave tal como
llega. Medido antes del cambio: ninguna de las 53 llamadas pinta una etiqueta a propósito. Lo vigila
`core/html-options-escape`.

**Para quien actualiza**: lo que tu clon ya escapaba antes de llamar no cambia, y puede dejar de hacerlo. Si pasaba una
etiqueta HTML a propósito en el texto de una opción, ahora sale escrita como texto.

## Arregla — el listado de usuarios de un administrador de organización sin organización

`UsersModel::all()` filtra por la organización de quien pide si no es root ni administrador general. Con la organización
vacía, la pestaña Accesos de Registros daba un error 500. Ahora ese caso devuelve una lista vacía (no ver a nadie, nunca
a todos), y la organización se trata como entero.
Hoy la aplicación no deja crear ese usuario (el alta, la edición y la importación exigen organización), así que el caso
solo llega por datos escritos a mano. Lo vigila `core/users-all-organization`.

## Arregla — guardar «Identidad y SEO» ya no recomprime la imagen para redes sociales

Cada guardado de Configuración › Identidad y SEO, aunque solo cambiara un texto, volvía a subir la imagen Open Graph de su
idioma, y el servidor la recomprimía al 70 % sobre `statics/images/open_graph[_<idioma>].jpg`, que es un archivo
versionado: la imagen perdía calidad con cada guardado y el clon quedaba con cambios que nadie había hecho. La causa: el
recortador descarga la imagen actual al abrir la pantalla y `getFile()` la devuelve aunque no se recorte. Ahora la imagen
solo se envía si se recortó una nueva (`SimpleCropperAdapter::wasChange()`). Lo mismo en Imágenes de marca y en Fondos,
donde la pantalla ya solo enviaba tras un recorte, pero el envío sin recorte también reescribía el archivo.

## Arregla — la opción `default` de los campos de 13 mappers se llamaba `dafault`

Trece mappers del framework (Publicaciones y sus categorías y adjuntos, Noticias y sus categorías, Documentos, Formularios,
Organizaciones, Banners, perfiles, Aprobaciones y el registro de acciones) declaraban un campo con `'dafault' => null`. El
ORM ignoraba la clave y usaba su propio valor por omisión, que también es `null`, así que **no cambia nada**: el SQL de
`bin/cli scheme-create module=all` sale idéntico antes y después. Importa a quien clonó un módulo desde `Publications`:
la errata viajó con la copia. Desde ahora `core/mapper-field-options` falla si un campo de cualquier mapper lleva una
opción que el ORM no conoce.

## Seguridad — la suplantación de root se maneja desde el panel y caduca

- **«Conectar como otro usuario» se empieza y se termina solo desde el modal del panel**, con rutas propias solo para
  root, y ya no desde un enlace.
- **El estado va en una cookie firmada que solo escribe el servidor.**
- **Nadie actúa como un root**, ni como el usuario de sistema, ni como un usuario inactivo o de una organización que no
  está activa ni pendiente: el modal enseña el motivo.
- **Un id que no existe** deja a root como root, con un aviso.
- **Caduca a los 60 minutos**, y lo deja en el registro de acciones.
- **Una franja fija en todo el panel**: «Conectado como <nombre> · Volver a mi cuenta». Cuando la suplantación termina
  sola, la franja lo dice una vez.
- **Cerrar las sesiones del usuario suplantado ya no deja a root sin la suya.**
- **Los avisos «empieza / deja de actuar como otro usuario»** quedan siempre en el registro de acciones.
- ⚠ **Para quien actualiza**: `?asUser=` ya no hace nada; la constante `CONNECT_AS_ANOTHER_USER_ID_GET_PARAM_NAME` se
  queda, sin efecto, hasta la 10. Una suplantación en curso al actualizar termina sola (su cookie es del formato viejo).
  El código propio que escribiera la cookie `asUserID` desde JavaScript deja de funcionar: se usan las dos rutas.

## Arregla — Mi perfil pide la contraseña para cambiar el usuario o el correo, y los borradores solo los ve quien edita

- **Mi perfil**: cambiar el nombre de usuario o el correo exige ahora la contraseña actual, como ya la exigía cambiar la
  contraseña. Sin la contraseña, o con una equivocada, responde «La contraseña es errónea.» y no guarda nada.
  Cambiar solo el nombre no la pide, y un administrador que edita a otro usuario tampoco. Bajo el campo, una línea lo
  explica. Lo decidió el PO (P206 a).
- **Los borradores de publicaciones** (previsualizarlos y listarlos) los ven solo quienes pueden editar publicaciones:
  root, el administrador general, comunicaciones e institucional. El usuario general y el administrador de organización
  ya no (`PublicationMapper::CAN_VIEW_DRAFT`; P207 a). Ningún aprobador pierde la previsualización.

Lo vigila `core/profile-identity-password`.

## Arregla — las búsquedas de Ubicaciones leen `%` y `_` como texto

Las búsquedas de países, departamentos, ciudades y puntos tomaban un `%` o un `_` de quien busca como comodín y devolvían
de más (no era una inyección: el valor ya iba por marcador). Ahora los escapan con `|` y `ESCAPE '|'`, como la de regiones
desde la corrección anterior; una búsqueda sin esos caracteres da lo mismo que antes. Lo vigila
`core/locations-search-wildcards`.

## Arregla — un error cuya traza se purgó lo dice, y los censos de CSS paran si falta una hoja

- **Registros › Errores**: la traza de un error se borra a los 90 días, y hasta ahora su bloque desaparecía sin más, igual
  que en un error que nunca tuvo traza. Ahora el error (y cada excepción previa) dice «La traza de este error ya se
  purgó.». El lector lo expone en la clave `tracePurged` (`ErrorLogReader`). Lo vigila `core/logs-trace-purged`.
- **`bin/censo-scss` y `bin/huella-css`**: si un archivo versionado que leen no está en el árbol, paran antes de compilar
  con un mensaje que lo nombra y dice qué hacer, y salen con código 2. Antes, el primero caía con una traza de Python y el
  segundo podía medir un CSS viejo sin avisar.

## Arregla — una tarea ruidosa ya no expulsa del libro de tareas la historia de las demás

«Procesar la cola» corre cada minuto y deja una línea por pasada en el libro de tareas (unas 1.440 al día). Con el tope
global de 20.000 líneas, en unas dos semanas expulsaba toda la historia de las tareas diarias. Ahora, además del tope
global, cada tarea guarda como mucho sus **1.000 ejecuciones más recientes** (`TaskRunMapper::MAX_ROWS_PER_TASK`; la cola
guarda unas 16 horas, una diaria casi tres años). El recorte cuesta entre 0,5 y 2,4 ms por anotación con el libro lleno
(medido con 20.000 líneas). Lo vigila `core/task-runs`.

## Arregla — Registros › Tareas programadas traduce las ejecuciones saltadas

Las ejecuciones que se saltan porque otra está en curso (`locked`) o porque ya se hizo en su franja (`already-done`) salían
en el libro de tareas con su valor crudo, en inglés también en español. Ahora llevan su etiqueta y su color: «Saltada: otra
en curso» (amarillo) y «Saltada: ya hecha en su franja» (gris), con su inglés, y el filtro «Resultado» ofrece los cuatro.
Un resultado que no se conozca sigue saliendo tal cual. Las etiquetas salen de
`SystemStatusController::taskRunStatusLabels()`. En la misma pantalla, en inglés: los nombres de las tareas del framework
se traducen al pintarse (`taskDisplayName()`; el nombre que guarda el libro no cambia, y el de una tarea de un clon sale tal
cual hasta que añada su entrada en el diccionario de `SystemStatus`), el ejemplo del cron dice `<KEY>` y `--local` ya no se
parte en dos líneas; y la pestaña «Accesos» de Registros sale traducida (su título se traducía antes de cargar su
diccionario). La ayuda de `bin/cli scan-missing-lang` ya no da cifras de idiomas que
se quedaban viejas: dice de dónde salen. Lo vigilan `core/task-runs` y `core/publications-list-lang`.

## Cambia — `bin/cli gates` dice qué casos de una prueba cayeron

Cuando una prueba cae dentro de `bin/cli gates`, debajo de su línea `[FALLÓ]` salen ahora los casos que cayeron, con su
detalle (hasta diez; si hay más, «… y N más»), o «sin caso marcado: código de salida N» si la prueba cayó sin marcar
ninguno. Antes solo salía el balance, y una prueba que falla a veces no se podía atribuir. Lo vigila
`core/gates-failed-cases`. La línea de una prueba que cae lleva su balance entero (`BALANCE FINAL: 1/3 PASADAS, 2 FALLIDAS`);
antes se cortaba en «PASADAS». Además, `core/queue-abandoned` deja de procesar y de recuperar los trabajos ajenos de la
base: `ProcessQueueTask::reclaimAbandoned()` acepta una lista de ids (`null`, todos, como antes; una lista vacía, ninguno).

## Arregla — las categorías de formularios y los tipos de documento borrados ya no estorban, y el icono de una categoría de noticias se va con ella

Borrar una categoría de formularios o un tipo de documento los deja inactivos en la base (el borrado es lógico), y hasta
ahora seguían pesando:

- **Su nombre ya no impide crear otro igual**: `existsByName()` de `CategoriesMapper` y `DocumentTypesMapper` solo cuenta
  los activos.
- **Ya no salen en las listas**: `CategoriesMapper::allForSelect()` y las rutas que devuelven todas las categorías y todos
  los tipos (`…/admin/categories/all/` y `…/admin/document-types/all/`) solo dan los activos. `all()` y `allBy()` siguen
  siendo la lectura cruda, con los borrados.
- **Borrar una categoría de noticias borra su icono** del disco, y su carpeta si queda vacía. Nunca el icono por omisión,
  uno que use otra categoría o un archivo fuera de la carpeta de iconos del módulo; si el borrado del archivo falla, se
  anota y la categoría se borra igual.

Lo vigila `core/forms-categories-status`.

## Arregla — los 22 puntos del núcleo que podían dar un error 500

Con IK quedaron cerrados los 20 de fuera del núcleo; estos son los 22 que quedaban dentro (`files/dev/fatal-candidates.json`
queda vacío). Con datos válidos, todo responde igual que antes.

- **Los listados del panel** (`DataTablesHelper::process()`): los dos ajustes que un módulo puede pasarle a la consulta,
  `on_set_model` y `config_result_model`, **fallan cerrados**. Si no devuelven nada, la consulta se queda como estaba;
  si devuelven un modelo (`ActiveRecord`, la clase de lo que dan los `getModel()`), se usa; cualquier otra cosa lanza
  `\UnexpectedValueException` con el nombre del ajuste, en vez de un 500 o, peor, seguir sin la restricción que el ajuste
  quería poner. **Cambia para un clon que los use**: un modelo distinto del recibido, que antes se ignoraba si no era una
  subclase de `BaseModel`, ahora se usa. Ningún listado del framework los usa.
- **El exportador de la base** (`Exporter`, los respaldos): sin plugin de formato o de salida lanza `\RuntimeException` con
  su mensaje; `db-backup` y la pantalla de respaldos ya la recogen, la anotan y responden «Ha ocurrido un error».
- **El volcado de una rutina** (`SqlFormat`) deja la definición intacta si no puede separar sus parámetros, en vez de
  escribirla sin ellos.
- **`Utilities`**: borrar un directorio que no se puede listar no borra su contenido; comprimir sin archivo zip anota el error y
  vuelve sin escribir. **`Roles::hasPermissions()`** con una petición sin ruta responde «sin permiso», como con una ruta
  que no existe. **El arranque** compara al encargado de la organización por su id, venga como número o como usuario.

PHPStan baja de 395 a 372 errores (23 por arreglo, 0 por supresión). Lo vigila `core/fatal-candidates-core`.

## Cambia — quién puede traducir con IA se elige en Configuración › IA

Cada traducción con IA gasta créditos de pago del proveedor (OpenAI, Mistral o Groq) si la instalación tiene su clave.
Hasta ahora la podía disparar cualquier usuario con sesión, sin forma de cambiarlo salvo tocando el código.

- **Configuración › IA tiene un campo nuevo, «Quién puede traducir con IA»**, junto a «Activar traducción con IA»: los
  tipos de usuario que pueden usar las rutas de traducción (`api-admin-translations-actions` y
  `api-admin-translations-actions-full`). **Por omisión, los seis tipos**, como antes. **Root siempre puede**, esté o no
  en la lista: nadie se queda sin poder volver a cambiarla. Sin ninguno elegido, solo root. El cambio cuenta desde la
  petición siguiente. Los demás reciben 403.
- **En código**: la opción `translationAIUserTypes` (`APIController::TRANSLATION_AI_USER_TYPES_CONFIG`), y la regla en
  `APIController::translationUserTypes()`: sin opción o si no es una lista, los seis; lo que no es un tipo conocido se
  descarta. Al guardar, `SettingsController::translationUserTypesIsValid()` rechaza un tipo desconocido.
- **El botón «Traducir»** de la edición de una publicación en otro idioma solo aparece a quien tiene la ruta, y su
  dirección la da el servidor (`APIController::routeName()`); antes iba escrita a mano en el JS.
- **Las pantallas de «Olvidé mi usuario», «Mi usuario está bloqueado» y la recuperación de contraseña** pierden su cabecera
  de «Correo no registrado», que ya no se enseñaba nunca (en las dos primeras, desde el cambio anterior; en la
  recuperación, desde antes).

La traducción automática de los textos marcados con `lang-group` (`configurations.js`) usa la misma ruta: para un usuario
sin permiso el texto se queda sin traducir, sin aviso; hoy ninguna vista del framework la usa. Lo vigila
`core/translation-permissions`.

## Arregla — el acceso y la recuperación responden igual exista o no el usuario

- **Un único mensaje**: con un usuario que no existe o una contraseña equivocada, el acceso responde «Usuario o
  contraseña incorrectos.», con el error `INCORRECT_PASSWORD`, y tarda lo mismo.
- **El estado de la cuenta solo se enseña con la contraseña buena** (o con el código de un uso), y no siempre: una cuenta
  con señales de ataque tampoco lo dice durante un tiempo. Su dueño la desbloquea con el código al correo de
  «¿Problemas para ingresar?».
- **«Olvidé mi usuario» y «Mi usuario está bloqueado»** responden igual exista o no el correo.

**Para quien consume la API**: el acceso ya no devuelve `USER_NO_EXISTS`, y `BLOCKED_FOR_ATTEMPTS`, `APPROVED_PENDING`,
`INACTIVE_USER` y `ORGANIZATION_IS_NOT_ACTIVE` solo llegan con la contraseña buena. Quien ya trataba `INCORRECT_PASSWORD`
no tiene que cambiar nada. Lo vigila `core/login-uniform-errors`.

## Arregla — unos intentos fallidos ya no bloquean una cuenta por sí solos: el acceso frena por IP

- **Tras varios fallos desde una misma IP**, esa IP espera. Mientras tanto, el acceso responde **429** con la cabecera
  `Retry-After` y el error `TOO_MANY_ATTEMPTS`.
- **La cuenta solo se bloquea con señales de ataque.** El desbloqueo por correo no cambia.
- **El límite del segundo factor** responde como el resto del acceso, y la pantalla dice «Demasiados intentos».

Se configura con claves de `otp_security` en `config.php`; un clon que no las tenga usa los valores de fábrica. **Si la
instalación está detrás de un proxy inverso**, comprueba que llegue la IP real del cliente: si no, todos los usuarios
comparten una y se frenan a la vez. Quien consume la API de acceso tiene que tratar el 429. Lo vigila
`core/login-ip-throttle`.

## Arregla — veinte puntos que podían dar un error 500, fuera del núcleo

PHPStan señalaba 42 sitios donde un valor podía llegar vacío y el código lo usaría igual (`files/dev/fatal-candidates.json`).
Los 20 de fuera del núcleo quedan cerrados, con este criterio: lo que no existe responde «no existe», un dato opcional
ausente se trata como vacío, y un error de verdad se anota y se responde de forma controlada.

- **Los adaptadores de OpenAI y Mistral** sin su cliente HTTP lanzan un error con mensaje («El cliente del proveedor de
  IA no está disponible»), que las rutas de traducción ya recogen y anotan, en vez de un 500; el de Groq lee la
  respuesta de un error una sola vez.
- **Las tareas de terminal** `fix-webm-duration` y `scan-missing-lang`, y el guion `files/CliScripts/CorregirTiempoDuraciónWebm.php`,
  tratan como vacía una búsqueda de archivos que falla; **la cola** cae en su propio manejo de errores si le falta la
  conexión.
- **`files/CliScripts/GeneradorIntegralTipoUsuario.php` y `bin/phpstan-process-result.php`** paran con su mensaje si se
  incluyen sin argumentos.

PHPStan baja de 415 a 395 errores. Quedan 22 en el núcleo, pendientes de una decisión. Lo vigila
`core/fatal-candidates-modules`.
## Arregla — editar un usuario que no existe responde 404, no 500

`admin/users/edit/<id>` con un id que no existe comprobaba la autoridad sobre el tipo del usuario antes de mirar si
existía, y con el tipo vacío lanzaba un `TypeError`: el panel respondía 500. Ahora responde 404, como las demás
ediciones (`UsersController::formEditByType()`). Medido de paso: ninguna otra edición del panel da 500 con un id que no
existe (las de publicaciones y organizaciones responden 403). Lo vigila `core/users-edit-not-found`.
## Cambia — toda prueba declara su área, y la verificación parcial corre solo lo que toca

Cada suite de `src/app/core/system-controllers/local-tests/` declara ahora, en su cabecera, a qué parte del código
pertenece (`//@areas <carpeta de módulo>`), y las que solo prueban el núcleo lo dicen con **`//@areas nucleo`** (ADR
0066). Una verificación parcial (`bin/verify --changed-since=<commit>`) que toca un solo módulo deja de correrlas todas:
medido el 2026-10-23, un cambio solo en `NewsController.php` pasa de 157 suites a 14, uno en `SettingsController.php` de
157 a 21, y uno solo en la línea base de PHPStan, de 155 a 7. Cinco suites que recorren todos los módulos siguen sin área
y corren siempre. Un cambio de núcleo las corre todas, como antes, y la verificación entera no cambia. **Si añades una
suite**, declara su área (o `//@areas nucleo`): `verify-integrity` comprueba que el área declarada exista; una suite
sin área no falla, pero corre siempre.
## Arregla — borrar una cookie la borra, y una cookie con duración no nace caducada

- **`CookiesHandler.deleteCookie()`** (JavaScript) no borraba: pasaba una caducidad negativa que `setCookie()` ignora.
  Ahora escribe la cookie con fecha de 1970, en las mismas tres formas de dominio, con el mismo `path` y `SameSite`.
- **`deleteSession()` de `PiecesPHPGenericHandlerSession`** escribía la caducidad en milisegundos, que no es una fecha:
  la cookie podía quedarse. Ahora usa una fecha pasada.
- **«Cerrar todas mis sesiones», en «Mi espacio», borra la cookie de sesión con las mismas opciones con que se escribió** (`Secure` en https,
  `SameSite=Lax`, las tres formas de dominio).
- **`lifetime` de `config/cookies.php` es una duración en segundos** también para las cookies de `setCookieByConfig()`:
  con un valor mayor que 0, la cookie dura eso desde ahora. Antes nacía caducada (se tomaba como fecha de 1970). Con 0,
  el de fábrica, sigue siendo una cookie de sesión del navegador.

Lo vigila `core/cookie-options`.
## Arregla — límites en los códigos de recuperación, el encargado de una organización y la búsqueda de regiones

- **Los códigos de «olvidé mi usuario», «cuenta bloqueada» y recuperación de contraseña tienen un límite por usuario**:
  uno por minuto y cinco por hora, contados por correo. Pasado el límite, la respuesta es la misma que cuando se envía,
  pero no se crea ni se envía nada.
- **El encargado de una organización tiene que ser de esa organización** en «Mi organización».
- **La búsqueda de regiones de Ubicaciones valida el texto buscado**. Una búsqueda normal devuelve lo mismo.

Lo vigila `core/security-live-fixes`.
## Cambia — el formato de los archivos de configuración, en la documentación

Los archivos de `src/app/config/` explicaban su formato en comentarios largos, y varios decían cosas que ya no eran
ciertas (por ejemplo, que las rutas usan Slim 3; el framework va sobre Slim 4). Ese formato está ahora en
`source-docs/project/docs/piecesphp/content/configuration.md`, sacado del código: qué configura cada archivo, sus claves,
sus valores por omisión, un ejemplo y sus trampas. Los comentarios de los archivos se quedan en una o dos líneas que
apuntan ahí. **Si tu clon editó esas cabeceras**, al actualizar puedes encontrar un conflicto solo en comentarios:
quédate con la versión nueva. `database-exporter.md` lista además las cuatro opciones del exportador que faltaban
(`auto_increment`, `drop_if_exists_on_functions`, `create_if_not_exists` y `hex_blob`).
# 9.0.0 (23-10-2026)

> **Versión estable.** Reúne las rupturas que el PO decidió el 2026-10-08 —el panel entero bajo `admin/` y una lista de
> rupturas pequeñas que no merecían esperar—, el rediseño del panel familia a familia y lo que cerró la campaña hasta
> aquí. La rama `last-stable` apunta siempre a la última estable. **Las rupturas van marcadas con ⚠ en cada sección**:
> quien actualiza un clon desde la 8 las lee todas antes de empezar. Antes, estas pre-versiones:

| Pre-versión | Fecha | Hasta |
| :-- | :-- | :-- |
| `v9.0.0-alpha.5` | 2026-10-22 | El geovisor (una pieza con fuentes registradas, miniaturas, el centro configurable; fuera el mapa viejo y sus dos módulos), las aprobaciones rápidas con miles de registros, los errores de las pruebas marcados y podados, el prefijo telefónico y la nacionalidad desde los países (fuera `PHONE_AREA_CODES` y `NATIONALITIES`), el informe por los países de los datos, Rutas y permisos por módulo, los colores del panel por variable, el paquete de sesión de solo lectura, la cola que no reintenta un fallo sin reintento, la documentación de la API con la sesión de la 9, y la que viaja, solo la del framework |
| `v9.0.0-alpha.4` | 2026-10-17 | El rediseño del panel, familia a familia: usuarios, organizaciones, banners, suscriptores, Ubicaciones, Aprobaciones y el gestor de archivos; Mi espacio, el Directorio y los perfiles; el inicio con tarjetas; Configuración entera, «Acerca de» y la Salud de la instalación. Además: una sola forma de preguntar (`confirmAction()`), la sesión que caduca por inactividad, la llave del cron solo por cabecera, las migas como arreglo, MapBox sin llaves de fábrica y las pruebas por área tocada |
| `v9.0.0-alpha.3` | 2026-10-10 | Registros en una pantalla, las sesiones con su fila, los errores graves y suaves, y el cifrado de verdad con la clave de la aplicación en su llavero |
| `v9.0.0-alpha.2` | 2026-10-09 | Empieza el rediseño del panel con la familia de contenidos: los listados de publicaciones y noticias y el alta y la edición de publicaciones. Además, una denegación de permiso responde 403 también dentro de un `try`, y sale `CURRENCIES` |
| `v9.0.0-alpha.1` | 2026-10-09 | El panel entero bajo `admin/` y ocho rupturas pequeñas: `bin/cli` sale con error en rojo, 401 sin sesión, 404 a una acción de usuarios que no existe, `database` 5.3, `asSystemMailer()`, fuera `test_mode`, `route_alias` e `interest_research_area` |

## ⚠ CAMBIO INCOMPATIBLE — los textos de Configuración viven en su módulo

El diccionario de las pantallas de Configuración estaba fuera de su módulo, en `src/app/lang/app_config/`, y desde hoy
vive en `src/app/classes/PiecesPHP/Settings/lang/`, como el de cualquier otro módulo (`es.php`, `en.php` y la carpeta
`files/`). El grupo de traducción no cambia (`SettingsController::LANG_GROUP`), así que las llamadas a `__()` siguen
igual.

- **Qué hacer en un clon**: si editaste `src/app/lang/app_config/es.php` o `en.php`, lleva tus cambios a los archivos del
  mismo nombre en `src/app/classes/PiecesPHP/Settings/lang/`. Si no los tocaste, nada.
- Por qué: un texto nuevo de Configuración tocaba un archivo que no era de su módulo, y la selección de pruebas y de
  capturas por área lo trataba como núcleo y lo rehacía todo.

## Cambia — una verificación parcial que solo regenera artefactos ya no corre todas las pruebas

`bin/cli gates changed-since=<commit>` (la verificación parcial de las rondas intermedias, ADR 0062) trataba como núcleo
cualquier cambio en `files/dev/` o en los `PHPStanResult.*` de la raíz, y corría las 168 suites. Desde ahora, un
artefacto de instrumento ya no fuerza todas: elige las suites que lo leen (las que nombran ese archivo, y las que nombran
`files/dev` sin decir cuál), y además corren, como siempre, las que no declaran `@areas` (ADR 0065). Hoy son 154 de
168, así que el ahorro es pequeño hasta que las suites declaren su área. La verificación entera no cambia.

## Cambia — `piecesphp/database` 5.4.0: el juego de caracteres en la conexión, y un tipo de campo desconocido avisa

- **`piecesphp/database` se instala en la 5.4.0** (el requisito sigue siendo `^5.3`). **Qué hacer**:
  `composer update piecesphp/database --working-dir=src`.
- **El juego de caracteres de `database.php` va en la conexión** (el DSN), no en un `SET NAMES` posterior: PDO lo conoce
  y entrecomilla de acuerdo con él. Las consultas no cambian. Un `charset` que no sea un nombre de juego (letras, cifras y
  guion bajo) lanza `\InvalidArgumentException` al conectar.
- **Un tipo desconocido en el `$fields` de un mapper avisa con `E_USER_DEPRECATED`** al validar un valor. En local
  aborta, como toda obsolescencia, y fuera se anota y sigue (ADR 0060). Los mappers del framework no tienen ninguno: lo
  vigila la comprobación 10 de `bin/cli verify-integrity`, que exige que todo tipo declarado esté en el vocabulario del
  paquete. **Un clon con un tipo propio** lo cambia por el conocido más cercano (`mixed` acepta cualquier valor).

## ⚠ CAMBIOS INCOMPATIBLES — sin sesión, `allowedRoute()` y el control de acceso de los módulos respetan `require_login`

`allowedRoute()` y `DefaultAccessControlModules` (el control de acceso que instalan 24 controladores) respetan ahora
`require_login` también sin usuario conectado. Ahora, **sin usuario**:

- `allowedRoute()` devuelve `false` para una ruta que declara `require_login = true`. Con usuario, nada cambia.
- `DefaultAccessControlModules` responde **403** a una ruta de su módulo que declara `require_login = true`, antes de
  preguntar al validador del módulo.
- **`routeName()` no cambia**: sigue dando la dirección sin sesión, así que los enlaces de los correos de alta y de
  recuperación, el cron y la zona pública funcionan igual, y nadie tiene que migrar nada.
- Las rutas sin `require_login` siguen igual, también las que declaran roles: una lista de roles no cierra una ruta
  (`source-docs/project/docs/piecesphp/new-features/permisos-de-ruta.md`).
- `getLoggedFrameworkUser()` devuelve `null` también si leer el usuario falla; en ese caso `allowedRoute()` contesta
  como a un visitante: falla cerrado.

**Qué revisar en un clon**: un sitio que se pinte sin sesión y use `allowedRoute()` para decidir si enseña un enlace a
una ruta con sesión deja de enseñarlo. Si ese enlace tiene que verse (por ejemplo, para llevar al visitante al acceso),
usa `routeName()` y comprueba que no sea `''`. En el framework no hay ninguno: con la matriz de permisos medida antes y
después del cambio, ninguna página sin sesión cambió de respuesta ni de tamaño, y ninguna suite dependía de la concesión
anterior. Lo vigila `core/anonymous-route-access`.

## Cambia — la siembra deja a cada organización con su encargado

Las organizaciones sembradas no tenían encargado, y sin él «Mi organización» no pinta su formulario («Debe seleccionar
una persona encargada…»): con la base sembrada no se podía revisar. Ahora, al terminar de sembrar, cada organización
sembrada queda con un encargado que es un usuario sembrado de ella, preferentemente el administrador de organización. Si
no hay ninguno que pueda serlo (por ejemplo, con `seed module=Organizations` a solas), lo dice y sigue. Limpiar la siembra
lo deshace como siempre.

Para quien escribe sembradores: `Terminal\Seeding\ModuleSeeder::afterAll(SeedContext $context): array` es un paso
opcional que corre cuando todos los sembradores han escrito, dentro de la misma transacción, y devuelve lo que hizo para
el informe. Por omisión no hace nada.

## Arregla — `bin/cli bundle` ya no deja su copia a la vista de git

`bundle` copia `src/app` (sin `sass`, `app/cache` ni `app/logs`) y `src/statics` (sin `plugins`, `uploads` ni
`filemanager`) a `src/bundle/`, y ninguna regla de `.gitignore` cubría esa carpeta: tras correrlo, el árbol quedaba con
miles de archivos sin seguimiento, entre ellos lo que git ignora en `src/app` (`src/app/config/environment.php`, los
volcados de traducciones dinámicas), a un `git add` de entrar en un commit. Ahora `src/bundle/` está ignorada. La copia
se hace en `BundleTask::bundleInto()`, y `scan-missing-lang` reúne lo pendiente en
`ScanMissingLangTask::collectMissing()`: los dos se prueban sobre carpetas de prueba.

## Arregla — un trabajo de la cola que falla sin reintento ya no se reintenta

`QueueHandlerResponse::fail($mensaje, retry: false)` caía en la misma rama que un fallo con reintento: el trabajo
volvía a «pendiente» hasta agotar sus intentos. Ahora pasa a «fallido» en el primer intento, con su mensaje. El fallo
con reintento y la espera voluntaria (`wait()`) siguen igual. El trabajo de un elemento de la cola se procesa en un
método propio, `ProcessQueueTask::processJob()`, que usa `processPending()` y que una prueba puede recorrer con un
trabajo suyo sin tocar los demás pendientes.

## ⚠ CAMBIO INCOMPATIBLE — el paquete de sesión no se escribe desde fuera

`PiecesPHP\UserSystem\UserDataPackage` (lo que devuelve `getLoggedFrameworkUser()`) tenía `__get()` y no `__set()`:
escribir un nombre que no declara creaba una propiedad dinámica en silencio (en PHP 8.5, con un aviso de obsolescencia:
en local abortaba, fuera se anotaba y seguía), y escribir una propiedad suya daba `Error`. **Ahora las dos cosas lanzan
`\LogicException`**, con el nombre de la propiedad, en todos los entornos. Leer sigue igual. Un clon que guardara datos
propios en ese objeto los pasa a una variable suya o al mapper del usuario.

## Arregla — `db-restore` encuentra el volcado desde `src/` o desde la raíz

`bin/cli` corre dentro de `src/`, y `db-restore file=` buscaba la ruta relativa solo ahí: `file=databases/x.sql` o
`file=src/dumps/x.sql`, escritos pensando en la raíz, daban «no existe» sin decir dónde se había buscado. Ahora prueba la ruta tal cual, luego contra `src/` y
luego contra la raíz del proyecto; si no la encuentra, el error nombra las dos carpetas.

## Cambia — la documentación que viaja es solo la del framework

Las guías de entorno y de herramientas (Entornos: VPS, LAMP, HestiaCP, Tomcat, Docker y Mailpit; Rendimiento: ZRam y
Apache con PHP-FPM; Herramientas: Mautic con RabbitMQ y GeoServer) salen de `source-docs/project` y pasan a un sitio
MkDocs propio del repositorio de desarrollo, `source-docs/entornos`, que **no viaja a los clones**. Lo que el framework
necesitaba de ellas está ahora en su propia documentación, en «Despliegue» (`piecesphp/content/general.md`): qué paquete
de Ubuntu trae cada extensión de PHP, `mod_php` o PHP-FPM, y cómo probar el correo con Mailpit.

**De paso se corrige** «Correo en desarrollo», que todavía describía el modo de pruebas que dejó de decidir el
2026-10-03: lo que decide si un correo sale es «Entrega del correo» (`mail_delivery`).

## ⚠ CAMBIOS INCOMPATIBLES — el prefijo telefónico y la nacionalidad salen de los países, y las listas fijas se van

Ubicaciones › Países tiene dos campos nuevos, opcionales: **Prefijo telefónico** (`+` y de una a cuatro cifras, sin cero
delante: `+57`, `+1`) y **Gentilicio** (lo que se ofrece como nacionalidad: «Colombiana»). Un prefijo con otra forma se
rechaza al guardar. Son las columnas `phoneCode` y `demonym` de `locations_countries`.

Los desplegables de prefijo (Mi perfil, Mi organización, y el alta y la edición de organizaciones) y el de nacionalidad
(Mi perfil) ofrecen ahora lo de los **países activos que tienen el dato**: el prefijo con el nombre del país al lado, y
el gentilicio traducido con el grupo de nombres de Ubicaciones. Se guarda el mismo texto que antes (el prefijo, el
gentilicio), así que lo guardado sigue valiendo. **Al guardar se valida** —en los formularios y en el alta por la API—
que el valor sea de un país activo; un valor que ya estaba guardado en ese registro y que ningún país ofrece (como
«Otras nacionalidad») se enseña tal cual y se puede volver a guardar sin cambiarlo, pero no se puede elegir de nuevo. El
prefijo de una organización nueva ya no es `+57` por omisión: va vacío.

**Lo que rompe:**

- Salen `PHONE_AREA_CODES` y `NATIONALITIES` (`src/app/config/constants.php`) y las funciones `getPhoneAreas()` y
  `getNationalities()` (`src/app/config/functions.php`). Un clon que las use pasa a
  `PiecesPHP\App\Locations\Mappers\CountryMapper::phoneCodeOptions()` y `demonymOptions()`, que devuelven lo mismo
  para `array_to_html_options()`.
- Un clon que guardaba prefijos o nacionalidades que no son de ningún país activo deja de poder elegirlos: los da de
  alta en Ubicaciones › Países, con su dato.

**Un clon que ya esté instalado** aplica `databases/actualizaciones/2026-10-22-prefijo-y-gentilicio-de-los-paises.sql`
(se puede con la aplicación en marcha, y ANTES de desplegar el código, que lee las columnas): añade las dos columnas y
rellena Colombia (`+57`, «Colombiana») y Francia (`+33`, «Francesa») si existen con ese nombre y sin el dato. Sin él, los
desplegables saldrían vacíos. Una instalación nueva ya nace con ellas.

## ⚠ CAMBIOS INCOMPATIBLES — el informe de Informes reparte por los países de los datos, y su JSON cambia

**Si consumes `core/api/reports/get-generic-data/` (la API de reportes), mira esto antes de actualizar.**

El informe contaba fijo Colombia, Francia y «otros» (buscaba los dos países por su nombre). Ahora enseña **los dos
países con más registros** y «Otros países», que suma el resto y los registros sin país, en el mismo gráfico de tres
partes. Los totales no cambian. El JSON:

- `researchersData` pasa a **`peopleData`**, y `totalResearchersQty`, `…Colombia`, `…Francia` y `…Others` pasan a
  **`total`**, **`byCountry`** (lista de `{country, name, qty}` de los dos primeros) y **`others`**. Lo mismo en
  **`organizationsData`** (`total`, `byCountry`, `others`).
- `chartData` deja de llevar `colors` (la pantalla colorea con las variables CSS).
- **Desaparece `publicationsProjectionData`**: nadie la leía y sus dos barras llevaban los mismos valores.
- `totalApprovedPublicationsQty`, `totalPendingPublicationsQty` y `totalRemainingTokens` no cambian.

La forma nueva, en `source-docs/api/docs/modules/Reportes.md`. Una suite nueva, `core/reports-by-country`, la vigila.

## Cambia — el proveedor de componentes, sin la comprobación que no comprobaba, y el bloque público de destacadas con tope

`components-provider-provide` (`src/app/classes/PiecesPHP/Components/Controllers/ComponentProvider.php`) responde con
`Content-Type: text/html; charset=utf-8` y deja de comprobar algo que siempre era cierto (si existía la carpeta de
vistas). Una plantilla de grupo que lanza una excepción sigue respondiendo 500: un 404 lo decide el filtro del grupo,
antes de ejecutarla. El grupo `publications`, que sirve las destacadas de la portada sin sesión, **atiende
como mucho diez `slugs[]`** por petición (el cliente pide cinco) y escapa la dirección, el slug y la fecha que pinta. Su
documentación, nueva: `source-docs/project/docs/piecesphp/new-features/componentes.md`.

## ⚠ CAMBIOS INCOMPATIBLES — cuatro archivos sin uso se van

Ningún código del framework los usaba (medido en HG: nadie los carga, los incluye ni los instancia). **Si tu clon usa
alguno, cópialo a tu módulo antes de actualizar.**

- `src/statics/core/js/helpers-lib/GenericStepsViewHandler.js`: la clase `GenericStepsViewHandler` sale del paquete
  común (`configurations.min.js`). Un clon que la instancie verá `GenericStepsViewHandler is not defined` en la consola.
- `src/app/view/mailing/generic-dynamic-message.php`: una plantilla de correo que nadie pinta.
- `src/app/classes/PiecesPHP/AdminPanel/Views/panel/pages/dashboard.php` y `src/statics/core/sass/dashboard.scss` (y su
  compilado `statics/core/css/dashboard.css`): el tablero de antes del inicio con tarjetas.

## Cambia — el aviso de errores nuevos ya no dice cuántos

El aviso del pie que ve root dice «Hay errores nuevos desde tu última visita», sin la cifra, como la tarjeta plegada del
inicio: las demostraciones a clientes no enseñan números de errores (ADR 0064). La cifra sigue en Registros › Errores.

## Cambia — los colores del SCSS del panel, por variable, y una comprobación que lo vigila

Los 107 colores escritos a mano en las hojas del panel pasan a variables de SCSS con el nombre de su papel: los que se
repiten, a la **paleta del panel** (`src/statics/core/sass/includes/_variables.scss`, sección «Paleta del panel»), y los
de una sola hoja, arriba de ella. El CSS compilado es el mismo (`bin/huella-css --comparar`: idéntica). **Para quien
clona**: `bin/cli verify-integrity` tiene una comprobación nueva, la 54, que **falla** si una hoja versionada del panel
lleva un color escrito a mano (`#…`, `rgb()`, `rgba()`) fuera de la línea que define su variable. Si tu clon tiene hojas
propias del panel con colores fijos, pásalos a variables (la forma, en `source-docs/project/docs/piecesphp/content/panel.md`).
La zona pública (`src/statics/sass/`) no entra.

## Cambia — las tarjetas de Accesos miden lo que su contenido, y la hoja de diseño enseña su ejemplo real

En **Registros › Accesos** (intentos de ingreso, registro de ingreso y usuarios sin ingreso), las tarjetas de cifra y de
«Exportar datos» ya no pasan el modificador `tall`: en el móvil miden lo que su contenido (de 250 px a 168 y 180); a 1440
la rejilla sigue igualando la fila. En la **hoja de diseño del panel**, una entrada puede llevar su ejemplo real en el
panel como tercer elemento (`[texto, dirección]`), y se pinta «Ejemplo en el panel: <enlace>» solo si la dirección no
está vacía. La «Ventana de formulario» enlaza el listado de categorías genéricas (`Forms/Categories`), documentado en
`source-docs/project/docs/piecesphp/new-features/categorias-genericas.md`.

## Cambia — Rutas y permisos, por módulo y con buscador

**Configuración › Sistema › Rutas y permisos** deja la tabla única de DataTables (283 filas de 10 en 10) y agrupa las
rutas por módulo en un acordeón de Fomantic, cerrado al entrar y con «N rutas» en cada título. El módulo sale del
controlador de la ruta: la parte de su espacio de nombres anterior a `\Controllers\` (o el espacio de nombres de la
clase, si no la tiene), sin el prefijo `PiecesPHP\`. Un buscador filtra por nombre, ruta, controlador completo y tipo de
usuario, y abre los grupos que tienen algo. Una ruta que pueden usar todos los tipos de usuario lleva una sola etiqueta,
«Todos los tipos de usuario»; el controlador se ve como `Clase::método`, con el nombre completo al pasar el ratón. Las
filas y los permisos no cambian. El JavaScript de la pantalla pasa a `src/app/classes/PiecesPHP/Settings/Statics/js/routes.js`.

## Cambia — Correos enviados con filtros, y lo de fábrica que ya no se pinta

- **Registros › Correos enviados** se filtra por fecha (desde y hasta), resultado (entregado, en el buzón, no llegó),
  entrega (de prueba o real) y origen.
- **Guardar los colores de marca** renueva la marca de los estáticos: el color nuevo se ve al recargar, sin «Borrar todo
  lo temporal».
- **Las imágenes de socios de fábrica** (`statics/images/partners.png` y `partners-vertical.png`, el recuadro «280x50»)
  ya no se pintan en el acceso, en la ayuda para ingresar ni en el pie del menú. Se reconocen por su contenido, porque una
  imagen propia se guarda en el mismo archivo: **un clon** que suba la suya la ve como siempre.
- **El mapa del sitio** deja fuera la categoría genérica «Sin categoría».
- **El logo del framework** de «Acerca de» dice «PiecesPHP», junto.

## Cambia — las tarjetas de cifra miden lo que su contenido, y cinco hojas de estilo vacías se van

La pieza de tarjeta de estadística (`panel/built-in/statistics/statistic-card.php`, y `card-action`) ya no tiene un alto
mínimo de 250 px ni `height: max-content`: mide lo que su contenido y, dentro de una rejilla, la fila la iguala. El mínimo
sigue con el modificador `tall`. El modificador de título `color-black` se retira (no hacía nada: los títulos ya salían
en ese color). La rejilla `three-columns-grid` reparte el ancho en tres columnas iguales.

Salen cinco hojas que solo llevaban un `@use` y compilaban vacías, con su carga: `Forms/Categories/…/categories-style.scss`,
`Forms/DocumentTypes/…/document-types-style.scss`, `Newsletter/…/newsletter.scss`,
`PiecesPHP/BuiltIn/Banner/…/built-in-banner.scss` y `PiecesPHP/BuiltIn/Helpers/…/helpers-system.scss`. **Un clon** que
cargara una de ellas desde su propio código deja de hacerlo; no aportaban ninguna regla.

## Cambia — el Informe general, con el armazón del panel

La pantalla «Informe general» (`reports-manage-generic-report-view`) pierde el fondo degradado y el saludo: lleva la
cabecera del panel (migas, título y una frase), las dos cifras arriba y las dos tortas debajo, y sus colores salen de
las variables de marca (`--main-brand-color` y `--second-brand-color`): cambiar el color en Apariencia cambia las tortas.
Sus textos pasan del grupo de idioma de Mi espacio al de su módulo (`ReportsManageLang`): **un clon** que tradujera esos
textos en el grupo de Mi espacio los mueve al de Informes. El JSON de datos que pinta la pantalla no cambia.

## Cambia — sin llave de MapBox, la ubicación se escribe a mano; y un punto con coordenadas que no lo son, se rechaza

Desde la `9.0.0-alpha.4` un clon sin llave de MapBox no tiene mapas, y el alta de un punto de Ubicaciones no se podía
completar: la latitud y la longitud las ponía el mapa. Ahora, sin llave, los formularios que piden una ubicación con el
mapa (un punto de Ubicaciones, los perfiles de persona y de organización) enseñan dos campos numéricos, «Latitud» y
«Longitud», además del aviso. Y el servidor, al guardar un punto, **rechaza una latitud o una longitud que no sea un
número o se salga de su rango** (−90 a 90 y −180 a 180) con «Los parámetros recibidos son erróneos.»; antes no las
comprobaba.

## Cambia — los errores que escriben las pruebas se podan al terminar

Las pruebas siguen pudiendo escribir en el registro de errores, pero cada entrada que escriben lleva su marca
(`context.test`, el identificador de la corrida) y, al terminar, `bin/cli gates` o la suite lanzada sola las borran:
Registros › Errores y el aviso «Hay N errores nuevos» dejan de llenarse con lo que dejan las pruebas (medido el
2026-10-21: 72 entradas podadas por cada `bin/verify` entero, el registro real idéntico antes y después). **Un error real
nunca se poda**: antes de reescribir, la poda comprueba que cada línea que quita lleva la marca de una corrida que se
está podando, y si una sola no la lleva, no escribe nada («poda abortada»).

- En la terminal, la marca llega por la variable de entorno `PCSPHP_TEST_RUN`, que `gates` pone a cada suite.
- Por web, por la cabecera `X-PCSPHP-Test-Run`, que **solo se acepta en una instalación local**: fuera, se ignora, para
  que nadie pueda hacer pasar un error real por uno de prueba.
- **Una suite propia de un clon** que pida páginas con curl usa `Terminal\TestRun::curlInit()`,
  `curlSetopt()` y `curlSetoptArray()` en lugar de las funciones de curl, y sus errores se podan igual. Si no lo hace,
  sigue funcionando: sus errores se quedan en el registro, como antes.
- Lo que la poda no toca todavía: `error.plain.log`, `error.unique.message.log` y los registros ya rotados de `olds/`.

Decidido en el ADR 0063 (P191).

## ⚠ CAMBIO INCOMPATIBLE — fuera el mapa viejo y sus dos módulos; el centro del mapa se configura

Salen `ContentNavigationHub` y `GeoJSONManager`, con sus rutas `content-navigation-hub-admin-contents-map`
(`admin/content-hub/contents-map/`, ahora 404) y `geojson-manager-admin-contents-geojson-features`, sus vistas,
estáticos, textos y la constante `CONTENT_NAVIGATION_HUB_MODULE`. Los sustituye el geovisor (`geo-admin-viewer`, y sus
puntos por `geo-admin-points` y `geo-admin-point`); el listado de actores ya vivía en el Directorio
(`directory-admin-list`). Ninguno tenía tablas. **Un clon** que enlazara o llamara a esas rutas o clases pasa a las del
geovisor; uno que pintara puntos con las vistas `map-elements` registra una fuente (`GeoSource`).

**El centro por omisión de los mapas** deja de estar escrito en el código: se configura en Configuración › Integraciones
› Llaves de MapBox, «Centro del mapa por omisión» (longitud, latitud y acercamiento, con su rango validado), y lo leen
el geovisor, Mi perfil, Mi organización y el valor por omisión de `MapBoxAdapter.js`. Sin configurar, Barranquilla con
acercamiento 11. El panel lo entrega en `pcsphpGlobals.mapDefaultCenter` (`null` si no hay), por el mismo camino que la
llave de MapBox. **En Mi perfil y Mi organización**, el mapa sin posición abre con ese acercamiento (antes, 10).

## ⚠ CAMBIO INCOMPATIBLE — una fuente del geovisor dice el archivo de su imagen, y los puntos llevan miniatura

`PiecesPHP\Geo\GeoSource` gana un método: `thumbnailSource(int $id): ?string`, el archivo ORIGINAL de la imagen de un
punto en disco (nunca una URL), o `null` si no tiene imagen o si la fuente no lo mostraría en `points()`. Con él, la
ruta nueva `geo-admin-thumbnail` (`admin/geo/thumbnail/{source}/{id}/{size}/`, solo 64 o 128 px) genera la miniatura
UNA vez, la guarda en `app/cache/geo-thumbnails/` (que no se sirve) y la entrega con el mismo permiso de la fuente que
los puntos: un logo privado de una organización sale en miniatura solo a quien ve la fuente. Si el original cambia, se
regenera. `PiecesPHP\Geo\GeoThumbnail::url($clave, $id, $archivo)` da la URL que lleva cada punto, con su sello de
versión.

**Un clon con una fuente propia** añade el método: `return null;` si no quiere imagen, o la ruta en disco de la imagen
del punto, comprobando que el punto sea visible para la fuente (las dos que trae el framework lo hacen con la misma
búsqueda que su detalle).

## Cambia — el geovisor solo lee lo que cae en el mapa, con una consulta por fuente

El encuadre se filtra en `WHERE` (antes, en `HAVING`, tras calcular cada columna de cada fila) y el total y la página
salen de una sola consulta (`COUNT(*) OVER ()`). Las personas usan el índice nuevo `geo` (`longitude`, `latitude`) de
`user_system_profile`. Medido el 2026-10-21 con 10.000 puntos y un encuadre de un barrio: las personas leen 1.068 filas
de 7.850 y pasan de 73 a 10 ms; las organizaciones, de 34 a 8 ms. Los puntos que salen son los mismos.
`GeoSourceSql::addBbox()` sigue y funciona; la fuente que quiera el filtro en `WHERE` usa `GeoSourceSql::addBboxWhere()`, con la expresión de cada coordenada. **Trampa**: el marcador de un `WhereItem` se fabrica con el texto de la expresión, y una con `$`, `^`, `?` o `+` rompe la consulta (defecto del paquete `piecesphp/database`); la de las organizaciones escribe el `$` con `CHAR(36)`.

**Un clon que ya esté instalado** aplica `databases/actualizaciones/2026-10-21-indice-geo-de-perfiles.sql`; sin él,
todo funciona y las personas leen la tabla entera.

## Arregla — con miles de registros, las aprobaciones ya no hunden el panel

Cada arranque busca lo que aún no tiene su fila de aprobación, y cada listado de usuarios, perfiles u organizaciones lee
el estado de aprobación con una subconsulta por fila. Las dos buscaban en `system_approvals_elements` sin índice: con
10.000 perfiles, cada página del panel tardaba de 12 a 25 s y los puntos del geovisor, hasta 44 s. Ahora
`referenceTable` y `referenceValue` son `varchar(191)` con el índice `reference` sobre los dos, y las tres consultas
comparan la referencia como texto para poder usarlo. Medido el 2026-10-21 con 10.000 perfiles: de 12,97 s a 0,12 s el
inicio del panel, de 25,01 s a 0,16 s el geovisor, de 43,85 s a 0,39 s los puntos de las personas; mismos estados de
aprobación antes y después.

**Un clon que ya esté instalado** aplica `databases/actualizaciones/2026-10-21-indice-de-aprobaciones.sql` (se puede
con la aplicación en marcha; el archivo dice qué comprobar antes). Sin aplicarlo, todo funciona igual que antes, y
igual de lento. Una instalación nueva ya nace con el índice.

## Nuevo — el geovisor: un mapa del panel al que cada módulo le registra sus puntos

**«Mapa de contenidos»** (y «Mapa de actores y contenidos» en el menú de los demás tipos de usuario) abre la pantalla
nueva, `admin/geo/` (ruta `geo-admin-viewer`, los mismos cinco tipos de usuario que el mapa de antes). Es un mapa a
pantalla completa con el menú plegado al entrar, que enseña los puntos sin pulsar nada y encuadra los que hay:

- los agrupa en círculos con su cifra cuando hay muchos, y al acercarse los separa; desde cerca, cada uno con su imagen
  (o sus iniciales) y su nombre;
- el contador «Viendo X de Y» avisa cuando hay más puntos de los que se ven;
- los filtros y la leyenda van en dos paneles flotantes; apagar una fuente en la leyenda la quita del mapa;
- al pulsar un punto sale su tarjeta, con un enlace a su perfil;
- calles o satélite, sin perder lo que había en el mapa.

Hoy trae dos fuentes: las **personas** (con perfil público visible), con su avatar, y las **organizaciones**, con su
logo; sin imagen, las iniciales. Lo que ve cada usuario lo decide el permiso de cada fuente. El mapa de antes (`admin/content-hub/contents-map/`) sigue en su dirección hasta que
salga en la próxima pre-versión.

**Para un módulo propio**: una clase que implementa `PiecesPHP\Geo\GeoSource` (clave, título, la ruta cuyo permiso la
hace visible, su marcador —`GeoSource::MARKER_DOT` o `GeoSource::MARKER_AVATAR`, el modo de cada fuente—, su color
como variable CSS, sus filtros, sus puntos —cada `GeoPoint` con su imagen como último argumento, una URL absoluta o
desde la raíz, o `null`—, el detalle de un punto y `cardView()`, la vista de su tarjeta con marcado fijo y
`data-geo-field`), registrada con `GeoSourceRegistry::register()` desde su
`*Routes`. El servidor no pinta HTML: los textos viajan tal cual y la pantalla los escribe como texto. La guía para
implementadores llega con la versión que retire el mapa de antes.

**Para medirlo**: `bin/cli geo-load-fixture escribir=yes cantidad=<n>` (de 1 a 20.000; por defecto 10.000; solo en una
instalación local) escribe personas y organizaciones `zz-carga-` repartidas por Barranquilla, y `limpiar=yes` las borra.
Con 10.000, el panel responde por debajo de medio segundo desde el índice de las aprobaciones (entrada de arriba).

## ⚠ CAMBIO INCOMPATIBLE — MapBox ya no trae llaves de fábrica, y un aviso dice cuándo falta la del entorno

Las dos llaves públicas (`pk.`) de MapBox que el framework traía escritas en `GenericContentPseudoMapper` salen del
código: cada instalación pone las suyas en **Configuración › Integraciones › Llaves de MapBox** (una para local y otra
para el dominio), que las guarda en la base. Sin la del entorno en que corre:

- el aviso del sistema `mapbox-key-missing` (root y administrador general, ocultable) lo dice, con un enlace al
  formulario;
- en el sitio de cada mapa del panel sale «Falta la llave de MapBox: configúrela en Integraciones › Llaves de MapBox.» en
  lugar de un error de JavaScript que cortaba el resto del script;
- no hay mapas, y el alta de un punto de Ubicaciones no se puede completar, porque sus coordenadas las pone el mapa.

**Un clon** que ya guardó sus llaves desde ese formulario no nota nada. Uno que vivía de las del framework las configura
al actualizar. `SettingsController::mapboxKey()` devuelve una cadena vacía, sin aviso de PHP, si no hay llave.

## Cambia — `bin/cli gates areas` dice qué tocó un cambio

`bin/cli gates areas changed-since=<commit>` no corre ninguna suite: imprime un JSON con los módulos de `AREA_MODULES`
tocados desde ese commit, el primer archivo de núcleo tocado (el que obligaría a correrlo todo) y la carpeta de módulo de
cada ruta, sacada de su controlador. `unchanged=<json>` descuenta lo que ya estaba sucio y no ha cambiado. Es el mismo
criterio que `changed-since` (ADR 0062), y lo usa el recorrido de capturas para rehacer solo lo tocado.

## Cambia — «Acerca de» y Configuración, lo que quedaba

**«Acerca de»** da la versión de CKEditor (27.1.0), separa las librerías de PHP de las de JavaScript, dice «Es un
conjunto» y lleva sus migas. En **Configuración**, el bloque que sigue a un formulario ya no va pegado a su barra de
guardar (el mismo 1rem que separa dos contenedores, regla `form + *` de `src/statics/core/sass/admin_app_base.scss`;
se nota en Correo y en Apariencia › Fondos y Colores), **Correo** tiene una sola acción principal («Guardar»; «Enviar
correo de prueba» pasa a secundaria) y en **Respaldos** los nombres de tabla solo se parten tras un guion bajo.

## Nuevo — la Salud de la instalación

Una pantalla nueva, la primera de **Configuración › Sistema** y solo para root: versiones del framework y de cada
dependencia de PHP con su licencia, el entorno de PHP (versión, extensiones exigidas, límites, OPcache, ffmpeg), la base
de datos (motor, juego de caracteres, tablas y las declaradas que faltan), los respaldos y las tareas programadas, y si
cada carpeta que debe escribirse se puede escribir. **Cada fila dice de dónde sale y ninguna enseña un valor secreto.**
Detalle en `source-docs/project/docs/piecesphp/new-features/system-health.md`. La clase `PiecesPHP\Core\InstalledPackages`
lee las versiones y licencias de lo instalado por Composer y la usan esta pantalla y «Acerca de».

## Cambia — «Acerca de», «Agregar usuario», el índice de Configuración y la lista de carpetas escribibles

**«Acerca de»** lee de lo instalado las versiones y licencias de las dependencias de PHP (antes eran una lista escrita a
mano que se había quedado en las de hace años), cuenta los años desde 2018 al pintar, escribe «PiecesPHP» junto, enlaza
a GitHub y dice «Versión instalada». Las librerías de JavaScript siguen escritas a mano, medidas el 2026-10-16. **«Agregar
usuario»** y el índice de Configuración llevan la cabecera y las tarjetas del panel.

**Las carpetas escribibles** pasan de la lista `WRITABLE_DIRS` repetida en `permissions-and-property.sh` y en
`src/permissions.sh` a un solo archivo, `src/app/config/writable-dirs.txt`, que leen los dos guiones y la Salud de la
instalación. **Un clon** que hubiera añadido carpetas a esa lista en sus guiones las pasa al archivo.

## Arregla — reimportar una librería ya no quita un archivo que otro plugin sigue pidiendo, y las páginas de acceso cargan la hoja base

`import_front_library()` (y con ella `import_app_libraries()` y el resto de `import_*`) sustituye la lista de plugins
de una librería al volver a llamarla: los plugins que salen se quitan con sus archivos. **Antes quitaba también los
archivos que pedía otro plugin que seguía importado**, de esa librería o de otra; ahora esos se quedan, y solo se quitan
los exclusivos del plugin que sale. Lo prueba la suite `core/import-front-library`. Por ese defecto las páginas de acceso
nunca tuvieron la hoja base del panel: ahora la importan con el plugin nuevo `baseStyle` de `app_libraries` (solo
`ui-pcs.css`, la misma hoja que lleva `adminStyle`), y con ella les llegan las fuentes, que ya no se cargan aparte. Su
aspecto cambia poco: el fondo pasa a `#fcfcfc` y los botones y el logo se ajustan a la hoja base. En el móvil, «olvidé mi
usuario», la recuperación y las demás páginas de acceso se pueden desplazar hasta el final. **Un clon** con una página
suelta que quiera la base del panel sin su armazón: `import_app_libraries(['baseStyle'])`.

## Cambia — los bloques del panel ya no van pegados, y Rutas y permisos cabe en la pantalla

Dos bloques seguidos del panel (`container-standard-form`, `container-standard-table`) llevan un margen entre ellos en
todas las pantallas, y un título de sección (`form-section-title`) que parte en dos líneas ya no se monta sobre sí mismo.
**Rutas y permisos** pasa a cuatro columnas que caben a 1440 de ancho: nombre con su definición debajo, ruta,
controlador (`Clase::método`) y los roles como etiquetas, todo escapado. En Respaldos los nombres de tabla largos parten
línea en vez de montarse, y el bloque «Probar el correo» de Configuración › Correo va en su tarjeta.

## Cambia — Configuración › Sistema con el armazón del panel, y Respaldos con tarjetas

Las seis pantallas de **Configuración › Sistema** (avisos del sistema, estado y cachés, sitio en mantenimiento, rutas y
permisos, seguridad y respaldos) llevan la cabecera común —migas «Inicio › Configuración › Sistema › …», título y una
frase— y, las que guardan, la barra de guardar en su propio segmento, como Apariencia e Integraciones. El contenido de
cada una va en su tarjeta y cada tabla en el contenedor de los listados; sale el párrafo que repetía la frase de la
cabecera (en Sitio en mantenimiento, el aviso de que el acceso al panel y el usuario principal siempre entran queda como
ayuda del interruptor); los títulos de sección dejan el subrayado magenta, y en Estado y cachés queda una sola acción
principal, «Borrar todo lo temporal», con las demás como botones básicos. **Respaldos** enseña su estado con cuatro
tarjetas de estadística en lugar de la rejilla de cifras gigantes. Lo que hace cada pantalla no cambia.

En la hoja de diseño, el título de un bloque de formulario es `div.form-section-title` dentro de
`container-standard-form`, y el ejemplo de la barra de guardar la enseña después de esa tarjeta. **Un clon** que use
`ui dividing header` como título de sección lo verá con el segundo color de marca.

`SettingsController::sectionHeaderData(string $section, string $title, string $description): array` devuelve la cabecera
de una pantalla de Configuración (título, frase, migas y enlace al índice) para usarla desde otro controlador. La hoja
`system-status.css` pasa del módulo `Settings` al de `SystemStatus`: **un clon** que la cargara con
`SettingsRoutes::staticRoute('css/system-status.css')` usa `SystemStatusRoutes::staticRoute('css/system-status.css')`.

## ⚠ CAMBIO INCOMPATIBLE — la cabecera de las pantallas recibe las migas como arreglo, y las hojas de acceso cambian de sitio

**Las migas.** `panel/built-in/content/page-header.php` recibe `breadcrumbs` como el arreglo que antes se pasaba a
`get_breadcrumbs()` (`'Texto' => ['url' => …]` o `'Texto'`), no como su HTML. Las pinta con el componente de migas de
Fomantic, **escapa cada texto y cada URL**, recorta la última con «…» si no cabe (con el texto entero en `title`) y no
se mete bajo la barra superior. **Un clon** con una vista propia que incluya la pieza: pasa el arreglo y **deja de
escapar** lo que escapaba antes (si no, se verá `&amp;`). Si le pasa un texto, la pieza no pinta migas y deja un aviso
`E_USER_DEPRECATED`, que en local detiene la página. `get_breadcrumbs()` no cambia: la siguen usando las vistas que no
usan la pieza. `UsersController::usersBreadcrumbs()` y el `breadcrumbs` de `SettingsController::sectionHeader()`
devuelven ahora el arreglo.

**Las hojas de acceso y recuperación** (`login.css`, `problems.css`, `problems-form.css`) pasan de
`statics/login-and-recovery/css/` al módulo `UserSystem`, y se sirven con
`UserSystemFeaturesRoutes::staticRoute('css/<hoja>.css')`; desde la entrada de arriba, con la hoja base del panel delante. Sale la
tarea `sass-compile-users` de `gulp`: las compila `sass-modules`. **Un clon** con una página de acceso propia que
cargara esas URL las cambia por las nuevas. El aspecto no cambia.

## Arregla — el nombre largo en la barra, y en el móvil el título y la pestaña activa

El nombre del usuario en la barra superior ocupa una línea y se recorta con «…» (el entero, en `title`); antes podía
ocupar cuatro y tapar las acciones de la cabecera. En el móvil, el título de una pantalla sin migas ya no queda bajo el
botón del menú, y en unas pestañas de contenido la activa queda a la vista al cargar y al pulsar.

## Cambia — las pruebas pueden correr solo las del área tocada, y dos comprobaciones nuevas

`bin/cli gates changed-since=<commit>` y `bin/verify --changed-since=<commit>` corren, de las suites, solo las sin área,
las que cambiaron y las del área tocada desde ese commit; si se tocó algo fuera de una carpeta de módulo, todas. Una
suite declara su área con líneas `//@areas src/app/classes/<Módulo>/` en su cabecera. La verificación parcial lo dice en
su última línea y no sirve para cerrar un trabajo ni para etiquetar. Cada suite de `gates` dice ahora cuánto tardó. Sin
el argumento, todo sigue igual. Detalle en «Pruebas Unitarias (CLI)».

`bin/cli verify-integrity` suma dos comprobaciones: la **52** falla si el código llama a un método que decide alta o
edición por el nombre de la ruta (los `action()` que usan `isEditRoute()`), que fuera de su ruta falla sin avisar; y la
**53**, si un `//@areas` nombra algo que no existe o que no es un módulo. **Un clon** con un controlador propio que
llame a `action()` de otro extrae su cuerpo a un método que reciba la operación, como
`OrganizationsController::saveOrganization(…, bool $isEdit)`.

## Cambia — la siembra llena Registros, y la ayuda del cron solo mira las tareas registradas

`bin/cli seed module=all escribir=yes` siembra ahora las pestañas de **Registros**: intentos de acceso, sesiones
abiertas, correos enviados, tareas programadas y, en «Tokens y códigos», los dispositivos de doble factor, las
recuperaciones de contraseña y los códigos de usuario olvidado o bloqueado. Las filas llevan fechas pasadas, fuera de las
ventanas que cuentan intentos fallidos y fallos de correo, así que no bloquean a nadie ni encienden el aviso de «Entrega
del correo». No se siembran los errores (salen de un archivo, no de una tabla) ni los tokens genéricos. Detalle en la
guía «Datos de prueba».

**La ayuda del cron** de Registros › Tareas programadas («Última ejecución anotada», y si sale abierta) solo cuenta las
ejecuciones de las tareas registradas en `cronjobs.php`. Antes contaba cualquier fila del libro: una tarea retirada o
renombrada podía hacer decir que el cron corría. **Un clon** que lea esa ayuda en sus propias vistas usa
`SystemStatusController::registeredRuns()` antes de `cronCheatsheet()`.

## Arregla — el usuario de sistema ya no acumula una aprobación pendiente

En cada arranque, las aprobaciones creaban una pendiente para el usuario interno del sistema, que no se aprueba nunca;
borrada, volvía. Ahora el manejador de cada tipo de aprobación puede excluir elementos con
`excludesElement(int $id): bool` (en `BaseApprovalHandler`, que devuelve `false`), y el de usuarios excluye al de sistema.
**Un clon** con un manejador propio que extienda `BaseApprovalHandler` no tiene que hacer nada; para excluir algo,
sobrescribe ese método. En una instalación que ya tenga esa aprobación pendiente, la fila sigue: se borra a mano.

## ⚠ CAMBIO INCOMPATIBLE — fuera `DialogPCS.js` e `import_dialog_pcs()`

`src/statics/core/own-plugins/DialogPCS.js`, una ventana arrastrable que no usaba nada del framework ni ningún
proyecto, sale con su registro de recursos (la clave `dialgo_pcs` de `src/app/config/assets.php`) y la función global
`import_dialog_pcs()`. **Un clon** que la llamara usa una ventana de Fomantic (`ui modal`) o su propia copia del archivo.

## Cambia — la pieza de estadística es una tarjeta de tablero

Las piezas de `src/app/view/panel/built-in/statistics/` se rehacen por dentro: las tarjetas arrancan alineadas con el
título de la página, **hasta cuatro por fila, todas del mismo ancho** (una o dos no se estiran: ocupan un cuarto cada
una) y del mismo alto en cada fila (sin el tope de alto de `tall`); la primera línea de todos los títulos va a la misma
altura, en el color de título de la pieza, con plegar y la ayuda a la derecha; las cifras son compactas y van
arriba, con el número en el color de texto fuerte y el icono en el primario; el pie queda siempre dentro de la tarjeta,
en el color primario; plegada, la tarjeta se reduce a su barra y su pie. Mejoran con ellas el inicio, los informes de
ingresos, Ubicaciones y el informe genérico. **Un clon** que haya escrito estilos propios sobre esas clases los revisa:
la hoja ya no rebautiza `.module-view-container` como `module-section`, la sección de tarjetas reparte las columnas
sola (`auto-fill` con tope de cuatro) salvo con `one-column`, y el modificador de título `color-black` se admite pero ya
no cambia nada. **Añade** a la tarjeta de acción un texto de pie opcional: `DashboardCardBody::action(string $url,
string $description, ?string $footerCaption = null)` y el parámetro `caption` de `action-card.php`; sin él, la tarjeta
queda como antes. El «Informe general» del inicio dice «Ver el informe».

## ⚠ CAMBIO INCOMPATIBLE — un usuario eliminado no se ve, y la exportación de usuarios lo omite

En el framework no se borra ninguna cuenta: «eliminar» pone al usuario en estado «Eliminado» y la fila se queda, por
lo que la cita. Desde esta versión ese usuario **no se ve**: no sale en el Directorio, el mapa, los listados ni los
buscadores; no se puede elegir como encargado de una organización (un id de un eliminado se rechaza como uno que no
existe, y el formulario de organización ya no acepta como encargado un id que no exista); su perfil público responde
404 a todos, root incluido. Donde aparece por una relación —autor de una publicación, registros, historial de
ingresos, sesiones, aprobaciones— sigue su nombre.

**La exportación de usuarios deja fuera a los eliminados.** Si se importa en otra instalación, esas cuentas no se
crean, y lo que las cite allí (por ejemplo, el autor de una publicación) llegará sin ellas. **Un clon** que liste o
elija usuarios en un módulo propio excluye `UsersModel::STATUS_USER_DELETED`, o valida el id con
`UsersModel::existsAndNotDeleted(int $id)`. Lo vigila la suite `core/deleted-user-gone`.

## Arregla — «Mi organización» vuelve a guardar, y el perfil público de una organización enseña su propio contacto

**Desde la `v8.0.0`, «Mi organización» no guardaba para nadie**: ni el encargado su propia organización ni root
ninguna. Su guardado reutilizaba el de la gestión de organizaciones, que desde esa versión decide si es un alta o una
edición por el nombre de la ruta y se niega si la ruta no lo dice; la de «Mi organización» no lo dice. Ahora el guardado
de organizaciones está en `OrganizationsController::saveOrganization(Request $request, Response $response, bool $isEdit)`,
que recibe la operación; `action()` la sigue sacando de su ruta, y «Mi organización» la fija en edición después de
comprobar quién guarda. **Un clon** que llame a `action()` de otro controlador desde una ruta suya tiene el mismo
problema: que extraiga el cuerpo a un método así.

Además:

- **Root ve y guarda la organización de la dirección.** Antes, si el encargado era de otra organización, el formulario
  enseñaba la del encargado y guardar daba 403.
- **El contacto del perfil público de una organización es el de la organización** (su teléfono, su correo informativo,
  su web y su LinkedIn), no el de su encargado.
- **El perfil público de una organización sin encargado se ve**, si está completo y visible; antes respondía 404.

## Cambia — Mi perfil, Seguridad, Mi organización, los perfiles públicos y el Directorio con el armazón del panel

Las seis pantallas llevan la cabecera del panel (migas, título, una frase y sus acciones), las pestañas y la barra de
guardar aparte. La cuenta y el perfil público se enlazan entre sí con los mismos nombres e iconos en personas y
organizaciones: **«Cuenta»** (`user circle`) y **«Perfil público»** (`id card outline`); «Ver perfil» lleva `eye`. El
Directorio pinta «Ver perfil» con las acciones de fila, y se corrige la errata «Ver perflil». Sale
`MySpace/Statics/sass/my-space.scss`, que solo estilizaba `.my-space-content`, que ya no usa nadie; **un clon** que use
esa clase en una vista suya pierde sus estilos. Ningún nombre de ruta ni URL cambia.

## Cambia — la siembra llena los perfiles, y las noticias declaran sus lecturas

`bin/cli seed module=all escribir=yes` siembra ahora tres usuarios por tipo, sus perfiles completos (`UserProfilesSeeder`)
y las organizaciones con su perfil entero y su logo, para poder revisar los perfiles y el Directorio. Antes, limpiar lo
sembrado fallaba si alguien había abierto una noticia sembrada: su lectura apuntaba a ella y no se declaraba. Detalle en
la guía «Datos de prueba».

## ⚠ CAMBIO INCOMPATIBLE — Mi espacio solo con lo del usuario, y el Directorio en su módulo

Mi espacio (`MySpace`) se queda con Mi perfil, la seguridad de la cuenta y Mis sesiones. Lo demás se va a su sitio:

| Qué | Antes | Ahora |
| :-- | :-- | :-- |
| Mi organización y el perfil público de una organización | `MySpace\Controllers\MyOrganizationProfileController` y `OrganizationProfileController` | `Organizations\Controllers\…`, mismos nombres de ruta y mismas URL |
| El perfil público de una persona y los datos del listado | `MySpace\Controllers\ProfileController` y `AllProfilesController` | `Directory\Controllers\…`, mismos nombres de ruta y mismas URL |
| El listado de personas y organizaciones | ruta `content-navigation-hub-admin-profiles-list`, `admin/content-hub/profiles-list/` | **ruta `directory-admin-list`, `admin/directory/`** |
| Los ejemplos de la hoja de diseño (plantilla de correo, SurveyJS) | ruta `my-space-admin-iframe-sources`, `admin/my-space/iframe-sources/{source}/` | **ruta `admin-design-sheet-resources`, `admin/design-sheet/resources/{source}/`** (solo root) |
| La página de Mi espacio, el inicio viejo que ya nadie enlazaba | ruta `my-space-admin-my-space`, `admin/my-space/` | **sale** |

En el menú, el grupo «Consultar» desaparece: quedan «Directorio» y «Mapa de contenidos», y para los usuarios externos
«Usuarios generales» pasa a «Directorio». **Un clon** que use esas clases cambia su `use`; el que enlace o dé permisos
por esos tres nombres de ruta, los cambia por los nuevos. Los parciales de estilos de los perfiles
(`_profile-view.scss`, `_profile-form.scss`) pasan a `src/statics/core/sass/includes/`. El mapa de contenidos sigue
donde estaba hasta que llegue el geovisor.

## ⚠ CAMBIO INCOMPATIBLE — el inicio del panel son tarjetas que registra cada módulo

El inicio ya no es una vista fija: son tarjetas (`PiecesPHP\AdminPanel\DashboardCardRegistry`) que cada módulo
registra y que cada usuario ve según su permiso: «Mis sesiones», «Tareas pendientes» (las aprobaciones que puede
resolver), «Usuarios y accesos», «Errores y avisos» (root; nace plegada y, plegada, sin cifras) y el informe general.
**El informe general deja de ser el inicio entero** de root, del administrador general y del institucional: es una
tarjeta más. Las noticias muertas del inicio de Mi espacio salen. **Las tarjetas se pintan con las piezas de
estadística de la hoja de diseño**: lo que enseña una tarjeta es un `DashboardCardBody` (cifras, acción o HTML), no HTML
libre, y `DashboardCardRegistry::numberContent()` desaparece. **Un clon** que personalizara el inicio quita o
sustituye tarjetas con `DashboardCardRegistry::remove()` y `replace()`, y añade las suyas con `register()`: ver
«El panel por dentro» (`source-docs/project/docs/piecesphp/content/panel.md`), «El inicio».

## ⚠ CAMBIO INCOMPATIBLE — el dueño y root deciden si el perfil público se ve

Mi perfil y Mi organización tienen una casilla «Mostrar el perfil en el directorio y en el mapa». Un perfil oculto no
sale en el listado de perfiles ni en el mapa, y su página responde 404 salvo a su dueño (en una organización, su
administrador) y a root. Todo sigue visible como hasta ahora (por omisión, visible). **Antes de desplegar, aplica
`databases/actualizaciones/2026-10-14-perfil-publico-visible.sql`**: añade la columna `publicVisible` a
`user_system_profile` y a `organizations_elements`.

## Nuevo — `bin/cli session-name`, y con otro nombre de sesión ya se entra

- **Con un nombre de sesión propio (`session_token_name`) no se podía entrar**, desde la 8.0.0: la página de acceso y
  las de «problemas para entrar» no publicaban el nombre, el navegador guardaba la sesión como `JWTAuth` y el servidor
  la buscaba con el otro nombre, así que volvía al login una y otra vez. Ahora lo publican, y `bin/cli verify-integrity`
  (comprobación 51) falla si una vista con su propio `<head>` no lo hace sin estar declarada en
  `files/dev/head-without-front-configurations.json`.
- **`bin/cli session-name`** propone un nombre de sesión derivado de la instalación y, con `escribir=yes`, lo fija en
  `config.php` (la línea comentada de la sección «Seguridad»). Cambiarlo cierra todas las sesiones. Ver
  «El nombre de la sesión» (`source-docs/project/docs/piecesphp/content/general.md`).

## Seguridad — la suplantación de root y los desplegables de ubicaciones

- **La suplantación de root comprueba la sesión y el estado de root**, no solo los del usuario suplantado.
- **Los desplegables de ubicaciones de los perfiles y las organizaciones** pintan los nombres como texto.

## Cambia — la chuleta del cron, los textos de los estáticos y del límite de tokens, y el lateral de la cuenta

- **Registros › Tareas programadas** enseña cómo programar el cron en ESTA instalación: la línea de crontab con la ruta
  real del `index.php`, la alternativa por HTTP con la llave en la cabecera, si la llave está configurada (nunca cuál)
  y cuándo corrió por última vez.
- **Estado y cachés**: el botón y la fila dicen «archivos estáticos», porque renuevan también las imágenes de las
  páginas; debajo, qué no alcanzan (las que cargan las hojas de estilo, las que pone el JavaScript y las del correo).
- **El límite de tokens de IA dice que es solo informativo**: sirve para el «tokens restantes» del informe y no detiene
  el uso. Solo root lo guarda, ahora con su prueba.
- **El lateral de la cuenta** lleva a la cuenta completa y no acumula clics al abrirlo varias veces.
- **Ubicaciones: «Coordenadas»** en lugar de «Localidades» (y de «puntos») en toda la interfaz, y con su tarjeta en la
  portada, donde no salían. Los nombres de código (`Point`, `points`, las rutas y la tabla) no cambian.

## Seguridad — Ubicaciones escapa lo que viene de la base

Los listados de países, departamentos, ciudades y puntos, y los desplegables de sus formularios, escapan ahora los
nombres con `escape_html()`, en las celdas y en las opciones (el texto, el valor y lo que viene seleccionado), y la
edición conserva su selección aunque el nombre lleve comillas. Lo vigila `core/view-escapes` (§n).

**Si tu clon usa `array_to_html_options()` con datos de la base, escápalos tú antes de pasarlos**: la función no escapa.
El patrón está en `Locations::escapeOptions()` y en
«Escapar lo que escribe el usuario» (`source-docs/project/docs/piecesphp/new-features/escapado-y-subidas.md`).

## Nuevo — probar el correo desde Configuración

En **Configuración › Integraciones › Correo**, root tiene dos botones:

- **Probar conexión**: conecta con el servidor SMTP guardado, saluda, cifra si toca, **se autentica** y cuelga **sin
  enviar nada**. Si falla, enseña el error del servidor tal cual.
- **Enviar correo de prueba**: manda un correo real, por el mismo camino que el resto, a la dirección que escribas (por
  omisión, la tuya), y queda en el registro de correos.

Los dos dicen **a qué servidor fueron de verdad**: «Configurado: X · usado: Y». Con el modo de pruebas encendido el
correo se desvía al servidor de pruebas, y el botón lo dice en vez de dar por probado el servidor configurado. Las rutas
son `configurations-integrations-mail-probe` y `configurations-integrations-mail-test-send`, POST y solo root. De paso,
el aviso «Entrega del correo» de esa pantalla y el de Respaldos ya se ven: Fomantic los ocultaba dentro del formulario.

## Cambia — importar y exportar, imagen principal, acerca de, últimos movimientos y la prueba del recortador, con el armazón del panel

- **Importar y exportar**: la portada y los formularios de importar y de exportar llevan la cabecera común, las
  pestañas, la tabla del panel y la barra aparte; cada acción de fila con su icono, sin repetir. Borrar un filtro
  guardado pregunta antes. **En inglés**: `DataImportExportUtility` y el grupo nuevo `DataTransfer`
  (`src/app/lang/DataTransfer/`).
- **Imagen principal**, **Acerca de**, **Últimos movimientos** y **la prueba del recortador**: dentro del armazón.
- **La hoja de «Acerca de» pasa a su módulo**: de `src/statics/core/sass/about-framework.scss` a
  `AdminPanel/Statics/sass/about-framework.scss`. **Un clon que sobrescribiera la hoja global** tiene que pasar sus
  reglas a la del módulo. El JavaScript de la prueba del recortador pasa a `AdminPanel/Statics/js/test-cropper.js`.

## Cambia — Ubicaciones, Aprobaciones y el gestor de archivos con el armazón del panel; «Eliminar» en rojo

- **Ubicaciones**: las trece pantallas (países, departamentos, ciudades y puntos, listado y formularios, y la portada)
  salen de una plantilla de listado y otra de formulario del módulo (`App/Locations/Views/locations/_list.php` y
  `_form.php`), con la cabecera común, «Editar» con su icono en cada fila y la barra de guardar aparte. Las vistas de
  cada entidad (`country/`, `state/`, `city/`, `point/`) desaparecen: un clon que las sobrescribiera tiene que pasar a la
  plantilla. La portada usa los nombres de ruta de cada clase, no escritos a mano.
- **Aprobaciones**: listado y formularios con el armazón; «Revisar» con su icono; Aprobar y Rechazar en la barra.
- **Gestor de archivos**: dentro del armazón del panel.
- **Una sola forma de preguntar, también con toasts**: las preguntas que quedaban con un toast de botones (Mis sesiones,
  aprobar o rechazar, cancelar en Mi perfil y Mi organización, activar el doble factor) usan `confirmAction()`, y la
  comprobación 50 de `bin/cli verify-integrity` caza ahora también un toast con botones.
- **«Eliminar» de `RowActions` sale en rojo**, en la fila y en el menú, sea cual sea la clase que se le pase.

## Arregla — la instalación de la 8.0.x no traía la tabla del registro de correos

El `databases/piecesphp_structure.sql` de la `v8.0.0` a la `v8.0.8` no crea `pcsphp_mail_log`, y el código de esas
versiones la usa: la tabla nació con el generador de su módulo y no se subió al esquema del núcleo hasta esta versión.
**Un clon instalado desde una 8.0.x** aplica, en este orden, `databases/actualizaciones/2026-10-03-registro-de-correos.sql`
y `databases/actualizaciones/2026-10-05-cuerpo-del-correo.sql` (vienen en la 8.0.8). Desde la 9.0.0, el esquema la trae.
Desde esta versión lo impide la suite `core/schema-matches-database`, que corre `bin/cli gates`: compara las tablas,
columnas, índices y claves de `databases/piecesphp_structure.sql`, y las vistas y funciones por nombre, con la base
local, y falla nombrando lo que no cuadra.

## Cambia — usuarios, organizaciones, banners y suscriptores con el armazón del panel, y el menú ordenado por lo que se hace

- **Usuarios**: el listado lleva arriba «Agregar usuario» y, en cada tarjeta, «Editar» con su icono en lugar de los
  tres puntos; los formularios de alta y edición (los dieciocho, uno por tipo y acción) tienen la cabecera común, la
  pestaña «Cuenta» y la barra de guardar aparte. La hoja de la pantalla de elegir el tipo de usuario pasa de
  `src/statics/login-and-recovery/` a `UserSystem/Statics/`.
- **Organizaciones, banners y suscriptores**: listados y formularios con el armazón del panel; las acciones de cada fila
  con `RowActions`, sin dos acciones distintas con el mismo icono; «Eliminar» sale del formulario a la barra.
- **Banners y suscriptores, en inglés**: sus `en.php` estaban vacíos.
- **Las variables de entorno pasan a Configuración** (el límite de tokens de IA y las llaves de MapBox), con los
  mismos nombres de ruta: no cambia ningún permiso.
- **El menú lateral, por lo que se hace**: Inicio, Mi espacio, Contenidos, Personas y organizaciones, Comunicación,
  Datos, Actividad e Idiomas, cada uno en su banda de posición (`MenuGroupCollection::BAND_*`), y ningún icono repetido.
  **Un clon con entradas propias en el menú** debería pasar sus `position` a la banda que les toque
  (`MenuGroupCollection::BAND_CONTENTS + 40`, por ejemplo): con un número suelto siguen saliendo, pero en otro sitio.
- En la barra superior, «Contraseña» deja el icono de «Cuenta» por una llave.

## Cambia — el andamiaje de agentes que viaja: fuera las skills `caveman*`, y SDD deja de ser regla

- Salen las cuatro skills de terceros `caveman`, `caveman-commit`, `caveman-compress` y `cavecrew` (con sus enlaces de
  `.claude/skills/` y `skills-lock.json`): no las usaba nadie, y `caveman-compress` traía guiones de Python ajenos. La
  forma de escribir comprimido es la regla propia `.agents/rules/50-prosa-comprimida.md`.
- El flujo por fases (SDD) deja de cargarse en cada mensaje: pasa de `.agents/rules/20-sdd-workflow.md` a
  `.agents/docs/sdd.md`, que se lee cuando un cambio lo pide.

## ⚠ CAMBIO INCOMPATIBLE — un usuario de sistema para lo que se hace sin sesión

Hasta ahora, lo que se hacía sin sesión —una tarea programada disparada por HTTP, una petición pública como pedir un
código o recuperar la contraseña, el relleno inicial de aprobaciones— quedaba guardado como del usuario 1, que es un
root de verdad. Ahora queda como del **usuario de sistema**: id fijo **-10**
(`UsersModel::SYSTEM_USER_ID`), tipo **99** (`UsersModel::TYPE_USER_SYSTEM`), sin contraseña válida, inactivo y
sin rol. No puede iniciar sesión ni recuperar la contraseña, no aparece en ningún listado ni recuento, no se puede
editar, borrar ni suplantar, y en la jerarquía de Registros tiene el rango de «nadie» (solo root ve lo suyo). Para
preguntar por él en código: `UsersModel::isSystemUser($id)`. **Lo que se hace desde la terminal (`bin/cli`, también
`bin/cli run-cronjobs`) sigue siendo del usuario 1**: la terminal entra como root (`src/index.php:320-326`).

**Antes de desplegar, aplica `databases/actualizaciones/2026-10-14-usuario-de-sistema.sql`.** Las columnas que lo
guardan (`createdBy` y compañía) tienen clave ajena a `pcsphp_users`: sin la fila -10, todo lo que se anote sin sesión
falla. El mismo archivo pasa al usuario de sistema las filas del registro de acciones que ya se sabían del sistema
(`meta.actor.kind = 'system'`); las demás tablas no guardan con qué se hizo cada fila y se quedan como estaban. Las
instalaciones nuevas lo traen en `databases/piecesphp_data.sql`.

## Nuevo — la pestaña «Tokens y códigos» en Registros

Una pestaña nueva de Registros con lo vigente: los códigos de acceso de un solo uso, los de recuperación de contraseña,
los de «usuario olvidado» y «usuario bloqueado», el doble factor activo de cada usuario y los tokens genéricos. Debajo,
**la historia**: cada código emitido, usado, rechazado (caducado, o inexistente o ya usado), visto o invalidado queda en
el registro de acciones, sin el valor del código. La ven root y los administradores, cada uno con su alcance (la misma
jerarquía que las sesiones); **solo root** puede, desde cada fila y nunca suplantando a otro:

- **Mostrar** un código (el de un solo uso, el de recuperación, el de usuario olvidado o bloqueado), por si alguien lo
  pide por teléfono. Verlo queda registrado. El secreto del doble factor no se enseña nunca.
- **Invalidar ahora**: el código deja de valer en ese momento.
- **Restablecer el doble factor** de otro usuario, que tendrá que volver a activarlo.

## Nuevo — el libro de tareas programadas, y el cerrojo también para las tareas sin franja

Cada ejecución real de una tarea programada (la que corre, no la que no tocaba) queda en una tabla nueva,
`pcsphp_task_runs`: tarea, franja, quién la disparó (`http` o `cli`), resultado, intento, inicio, fin, duración y
mensaje. Guarda las últimas 20.000 (`TaskRunMapper::MAX_ROWS`). Se ve en la pestaña nueva **Tareas programadas** de
Registros (solo root), con el estado de cada tarea y su próxima franja. Si la tabla no existe todavía, las tareas
siguen corriendo y un aviso del sistema lo dice.

Y **las tareas sin franja también toman su cerrojo** (`when(...)`, como «Respaldar base de datos» o «Procesar la
cola»): dos disparos a la vez ya no la ejecutan dos veces, y la condición se vuelve a mirar con el cerrojo en la mano.

Para instalaciones que ya existen: `databases/actualizaciones/2026-10-14-libro-de-tareas-y-cola.sql`.

## Cambia — la cola de trabajos: un trabajo lo toma un solo trabajador, y lo terminado se purga

- Un trabajo pendiente se toma con una actualización condicional: si dos trabajadores van a por el mismo, solo uno lo
  consigue y el otro lo salta. Antes solo lo impedía un turno de archivos, que no vale con dos servidores.
- Lo fallido lleva también su fecha de fin.
- La tarea diaria «Purgar registros viejos» borra lo terminado de más de 30 días y lo fallido de más de 90
  (`QueueJobMapper::PURGE_COMPLETED_DAYS`, `PURGE_FAILED_DAYS`).
- La tabla `pcsphp_jobs_queue` pasa `name` y `status` a `varchar` y gana dos índices (el mismo archivo de
  actualizaciones de arriba).

## Cambia — los errores suaves se guardan 30 días y las trazas 90

La tarea diaria «Purgar registros viejos» (03:30) borra los contadores de errores suaves de más de 30 días
(`GenericHandler::SOFT_RETENTION_DAYS`) y las trazas de más de 90 (`TRACE_RETENTION_DAYS`). Antes solo había
`bin/cli clean-logs`, que lo vacía todo. Una entrada de error cuya traza ya se purgó se sigue abriendo, sin la traza.

## Arregla — `composer install` ya no falla en un clon de la distribución

Desde la 8.0.1, `composer install` en un clon terminaba con error: el paso que instala el instrumental de desarrollo
buscaba `bin/tools/composer.json`, que la distribución no lleva. Las dependencias sí se instalaban. Ahora ese paso lo
dice en una línea y sigue. En un clon de una versión anterior, `composer install --no-dev` da el mismo resultado sin el
error (no hay dependencias de desarrollo).

## Arregla — «usuario olvidado» y «usuario bloqueado»

La fecha de creación del código quedaba igual a la de caducidad (se movían juntas 24 horas), y el registro de tickets
guardaba el código. Las dos cosas, corregidas; las filas viejas del registro de tickets no se tocan.

## Arregla — la distribución lleva la configuración del editor (`.vscode/`)

`bin/distribution-exclude.txt` dejaba fuera `.vscode/` desde que nació `bin/make-distribution`, sin motivo. Ahora viaja:
quien clona recibe la configuración de Visual Studio Code con la que se escribe el framework (`settings.json`: formateo
de PHP con phpfmt en PSR-2, formateo al guardar de JavaScript y HTML, la versión de PHP de intelephense, que pasa a la
8.5, la del proyecto) y las extensiones recomendadas (`extensions.json`). No cambia nada para quien use otro editor.

## ⚠ CAMBIO INCOMPATIBLE — la sesión caduca por inactividad con un tope, configurables; sale `session_duration`

- **La sesión se cierra tras un tiempo sin actividad** (`session_inactivity`, 31 días por omisión) y, pase lo que pase,
  al llegar a su tope desde que se abrió (`session_ceiling`, 365 días por omisión). Cada uso corre la caducidad (como
  mucho, una escritura cada pocos minutos). Las dos opciones, en Configuración › Seguridad.
- **Con 0, no caduca** (P142 a, para aplicaciones que trabajan sin conexión). Con tope 0, el token se emite para diez
  años y la sesión sigue mandando en el servidor: cerrada su fila («Mis sesiones», un administrador), el token deja de
  valer en todos los caminos.
- **Sale la opción `session_duration`**: la sustituye `session_inactivity`. Si tu instalación la tenía configurada, se
  anota una vez que ya no se usa.

**Qué hacer:** si configurabas `session_duration`, pon ese valor en `session_inactivity`.

## ⚠ CAMBIO INCOMPATIBLE — una sola forma de preguntar: `confirmAction()`; fuera `confirm`, `alert` e `iziToast.question`

El panel pregunta siempre con `confirmAction()` (`src/statics/core/js/helpers-lib/confirmAction.js`), la confirmación de
Fomantic que ya usaba `genericFormHandler`. Las nueve preguntas que quedaban con `iziToast.question` o `window.confirm`
(borrar en Documentos, Formularios, Noticias y Suscriptores; usuarios; Configuración › Scripts; Estado y cachés) y los
avisos con `alert()` de los adaptadores de mapas y del editor pasan a ella o a `errorMessage()`.

**Qué hacer:** si tu clon llama a `window.confirm`, `alert` o `iziToast.question`, cámbialo por `confirmAction()` (ver
«Preguntar al usuario» en la documentación). `bin/cli verify-integrity` lo rechaza en `src/app` y `src/statics`.

## Cambia — las piezas de estadística son tarjetas, con los colores por variable

Las piezas de `src/app/view/panel/built-in/statistics/` llevan el marco de Fomantic (`ui segment`): se ven como tarjetas
también sobre fondo blanco. El número va en el color de marca (antes, un azul escrito a mano) y los colores neutros son
variables `--statistics-*` con su valor de siempre como reserva. Afecta a los informes de Registros › Accesos, al
informe genérico y a la hoja de diseño. Sin cambios en cómo se llaman.

## Cambia — Documentos, la barra de guardar y la hoja de diseño

- El explorador de documentos pinta sus tarjetas a la misma altura, la imagen sin pasarse y, si no hay archivo, la
  acción desactivada «Sin archivo».
- La barra de guardar va en su propio bloque, hermano del contenido, en el alta y la edición de documentos y en la
  edición de publicaciones (como ya estaba en las demás).
- La hoja de diseño trae su JavaScript y su hoja a su módulo (`AdminPanel/Statics`), sin recuadros alrededor de cada
  ejemplo, con SurveyJS en «Bibliotecas incluidas» y un ejemplo de `confirmAction()`. El correo de muestra se pinta una
  vez.
- El registro de acciones toma el color de marca de la instalación (antes, un gris oscuro escrito a mano).

## Arregla — iniciar sesión ya no anota un error grave

Cada inicio de sesión de un usuario sin código de un solo uso anotaba «toExpireOTP: … no tiene registro ONE_USE_CODE» como
error grave. Es el caso normal: ya no se anota.

## Cambia — la página pedida antes del login viaja en `?next=`

Sin sesión, una página del panel redirige al login con `users/login/?next=<la URL pedida>`, además del aviso de un solo
uso de siempre. Así una recarga del login ya no la pierde. El login solo acepta en `next` una URL de este mismo sitio
(mismo esquema, host y puerto que `baseurl()`, y bajo su ruta); cualquier otra se ignora y se entra como antes. Lo
valida `UsersController::safeNextURL()`.

## ⚠ CAMBIO INCOMPATIBLE — fuera dos nombres viejos de clase: `PiecesPHP\Core\ServerStatics` y `PiecesPHP\Core\Helpers\Directories\ProtectFileMiddleware`

Eran alias (`class_alias`) de las clases que viven en `PiecesPHP\Core\Statics` desde la 8. Salen los dos archivos
`src/app/core/psr4/PiecesPHP/Core/ServerStatics.php` y
`src/app/core/psr4/PiecesPHP/Core/Helpers/Directories/ProtectFileMiddleware.php`.

**Qué hacer:** en tus módulos, cambia los `use`:

```php
use PiecesPHP\Core\Statics\ServerStatics;          // antes: use PiecesPHP\Core\ServerStatics;
use PiecesPHP\Core\Statics\ProtectFileMiddleware;  // antes: use PiecesPHP\Core\Helpers\Directories\ProtectFileMiddleware;
```

Con el nombre viejo, PHP no encuentra la clase y la página falla: búscalo con
`git grep -n 'Core\\ServerStatics\|Directories\\ProtectFileMiddleware' -- src/app`.

## Arregla — al volver a iniciar sesión, se vuelve a la página que se pidió

Si la sesión se cerraba en el servidor (otra pestaña, «Mis sesiones», una revocación) y el navegador conservaba el token,
al volver a entrar se acababa en la raíz del sitio en lugar de en la página que se había pedido. La página de login
borraba el token viejo y se recargaba, y la página pedida, que llega en un aviso de un solo uso, se perdía en esa
recarga. Ahora el login borra el token sin recargarse (`PiecesPHPSystemUserHelper.js`, `deleteSession()`).

## ⚠ CAMBIO INCOMPATIBLE — la ruta declara si renueva un token caducado; sale la lista de `index.php`

La lista `$ignoreExpiredForRoutesName` de `src/index.php`, que renovaba el token caducado en las rutas que nombraba, ya
no existe. Ahora lo declara la propia ruta con `->renewExpired(true)`; por omisión, `false`. La renovación sigue sin
saltarse la revocación: firma válida, posterior a las marcas y con la sesión abierta en el servidor.

**Qué hacer:** si tu clon añadió rutas a esa lista, quítalas de `index.php` y declara cada una:

```php
(new Route('webhooks/pagos[/]', MiController::class . ':pagos', 'mi-modulo-webhook-pagos', 'POST', true))
    ->renewExpired(true);
```

Detalle en la documentación de rutas, «Rutas que renuevan un token caducado».

## ⚠ CAMBIO INCOMPATIBLE — la llave de las tareas programadas, solo por cabecera

`core/api/cron-jobs/run/` ya no acepta la llave en el parámetro de consulta `?Cron-Job-Key=`: solo en la cabecera
`Cron-Job-Key`. Un parámetro de consulta queda escrito en los logs de acceso del servidor, y con él la llave.

**Qué hacer:** si tu cron llama con `?Cron-Job-Key=LLAVE`, cámbialo por la cabecera:

```
* * * * * curl -X GET -H "Cron-Job-Key: LLAVE" https://domain.tld/core/api/cron-jobs/run
```

Con la llave solo en la URL, la ruta responde **403**.

## ⚠ CAMBIO INCOMPATIBLE — todo el panel vive bajo `admin/`

- **Toda la gestión del panel cuelga de `admin/`**, como ya hacían publicaciones, noticias o documentos: la
  configuración, los usuarios, las ubicaciones, las organizaciones, los tickets, el temporizador y los avatares.
  **77 direcciones cambian; ningún nombre de ruta cambia**, así que los permisos, los roles y los menús siguen igual, y
  todo lo que pide su dirección con `routeName()` sigue funcionando sin tocarlo (P51, decidida por el PO el
  2026-09-18).
- **Lo público se queda donde estaba**: el acceso y la recuperación de la contraseña (`users/login/`…), los listados
  públicos de ubicaciones, lo público de las organizaciones, `robots.txt`, el mapa del sitio. **La API (`core/api/`) y
  la terminal (`terminal/`) tampoco cambian.** **Pero un cliente de la API desde otro origen sí puede usar una ruta que
  se muda**: el tiempo de uso, `timing-add`, pasa de `/timing/add/` a `/admin/timing/add/` (en la tabla de abajo); y el
  cierre de sesión en el servidor, nuevo en esta versión, vive en `/admin/users/sessions/logout/`.
- **Dos rutas salen de `admin/`** porque sirven a la parte pública y responden sin sesión: los mensajes de idioma (a
  `core/`) y el alta de un suscriptor del boletín (a la zona pública).
- **Sin redirecciones**: una dirección vieja responde 404. Lo que la tenía escrita se corrigió en su sitio, y **se corrigió
  sacándola de su nombre**, no escribiendo la nueva a mano: el JavaScript que pedía `configurations/…` lee ahora la
  dirección que le pone la vista.
- **`robots.txt`** deja de nombrar `/configurations/`, `/tickets/`, `/timing/` y `/avatars/`: ya están bajo `/admin/`, que
  sigue cerrado.
- **Para un clon**:
  - si en tu JavaScript, tus plantillas o tu documentación escribiste a mano una de las direcciones de abajo, cámbiala
    por la que da su nombre (`Controller::routeName()` en PHP; en JavaScript, la que la vista le pase desde PHP);
  - **un módulo tuyo con parte privada y parte pública** recibe ahora dos grupos, como `PublicationsRoutes::routes($grupoAdministracion, $grupoPublico)`;
  - y si tus marcadores apuntaban a la configuración o a los usuarios, ahora empiezan por `admin/`.

**Las direcciones que cambian** (el patrón, sin el prefijo de idioma):

| Nombre | Antes | Ahora |
| :-- | :-- | :-- |
| `push-avatars` | `/avatars/push/` | `/admin/avatars/push/` |
| `configurations-appearance-backgrounds` | `/configurations/appearance/backgrounds/` | `/admin/configurations/appearance/backgrounds/` |
| `configurations-appearance-brand-images` | `/configurations/appearance/brand-images/` | `/admin/configurations/appearance/brand-images/` |
| `configurations-appearance-colors` | `/configurations/appearance/colors/` | `/admin/configurations/appearance/colors/` |
| `configurations-appearance-seo` | `/configurations/appearance/seo/` | `/admin/configurations/appearance/seo/` |
| `configurations-appearance-site-files-save` | `/configurations/appearance/site-files/save/` | `/admin/configurations/appearance/site-files/save/` |
| `configurations-appearance-site-files` | `/configurations/appearance/site-files/` | `/admin/configurations/appearance/site-files/` |
| `configurations-generic-save` | `/configurations/generic-save/` | `/admin/configurations/generic-save/` |
| `configurations-integrations-ai` | `/configurations/integrations/ai/` | `/admin/configurations/integrations/ai/` |
| `configurations-integrations-mail` | `/configurations/integrations/mail/` | `/admin/configurations/integrations/mail/` |
| `configurations-integrations-mapbox-key` | `/configurations/integrations/mapbox-key/` | `/admin/configurations/integrations/mapbox-key/` |
| `configurations-integrations-osticket` | `/configurations/integrations/osticket/` | `/admin/configurations/integrations/osticket/` |
| `configurations-integrations-scripts-save` | `/configurations/integrations/scripts/save/` | `/admin/configurations/integrations/scripts/save/` |
| `configurations-integrations-scripts` | `/configurations/integrations/scripts/` | `/admin/configurations/integrations/scripts/` |
| `configurations-system-backups-save` | `/configurations/system/backups/save/` | `/admin/configurations/system/backups/save/` |
| `configurations-system-backups` | `/configurations/system/backups/` | `/admin/configurations/system/backups/` |
| `configurations-system-cache-clean` | `/configurations/system/cache-clean/` | `/admin/configurations/system/cache-clean/` |
| `configurations-system-routes` | `/configurations/system/routes/` | `/admin/configurations/system/routes/` |
| `configurations-system-security` | `/configurations/system/security/` | `/admin/configurations/system/security/` |
| `configurations-index` | `/configurations/` | `/admin/configurations/` |
| `locations-cities-actions-add` | `/locations/cities/action/add/` | `/admin/locations/cities/action/add/` |
| `locations-cities-actions-edit` | `/locations/cities/action/edit/` | `/admin/locations/cities/action/edit/` |
| `locations-cities-datatables` | `/locations/cities/datatables/` | `/admin/locations/cities/datatables/` |
| `locations-cities-forms-add` | `/locations/cities/forms/add/` | `/admin/locations/cities/forms/add/` |
| `locations-cities-forms-edit` | `/locations/cities/forms/edit/{id}/` | `/admin/locations/cities/forms/edit/{id}/` |
| `locations-cities-list` | `/locations/cities/list/` | `/admin/locations/cities/list/` |
| `locations-cities-ajax-search` | `/locations/cities/search/` | `/admin/locations/cities/search/` |
| `locations-countries-actions-add` | `/locations/countries/action/add/` | `/admin/locations/countries/action/add/` |
| `locations-countries-actions-edit` | `/locations/countries/action/edit/` | `/admin/locations/countries/action/edit/` |
| `locations-countries-datatables` | `/locations/countries/datatables/` | `/admin/locations/countries/datatables/` |
| `locations-countries-forms-add` | `/locations/countries/forms/add/` | `/admin/locations/countries/forms/add/` |
| `locations-countries-forms-edit` | `/locations/countries/forms/edit/{id}/` | `/admin/locations/countries/forms/edit/{id}/` |
| `locations-countries-list` | `/locations/countries/list/` | `/admin/locations/countries/list/` |
| `locations-countries-ajax-search` | `/locations/countries/search/` | `/admin/locations/countries/search/` |
| `locations-generate-lang-file` | `/locations/generate-lang-file/` | `/admin/locations/generate-lang-file/` |
| `locations-points-actions-add` | `/locations/points/action/add/` | `/admin/locations/points/action/add/` |
| `locations-points-actions-edit` | `/locations/points/action/edit/` | `/admin/locations/points/action/edit/` |
| `locations-points-datatables` | `/locations/points/datatables/` | `/admin/locations/points/datatables/` |
| `locations-points-forms-add` | `/locations/points/forms/add/` | `/admin/locations/points/forms/add/` |
| `locations-points-forms-edit` | `/locations/points/forms/edit/{id}/` | `/admin/locations/points/forms/edit/{id}/` |
| `locations-points-list` | `/locations/points/list/` | `/admin/locations/points/list/` |
| `locations-points-ajax-search` | `/locations/points/search/` | `/admin/locations/points/search/` |
| `locations-regions-ajax-search` | `/locations/regions/search/` | `/admin/locations/regions/search/` |
| `locations-states-actions-add` | `/locations/states/action/add/` | `/admin/locations/states/action/add/` |
| `locations-states-actions-edit` | `/locations/states/action/edit/` | `/admin/locations/states/action/edit/` |
| `locations-states-datatables` | `/locations/states/datatables/` | `/admin/locations/states/datatables/` |
| `locations-states-forms-add` | `/locations/states/forms/add/` | `/admin/locations/states/forms/add/` |
| `locations-states-forms-edit` | `/locations/states/forms/edit/{id}/` | `/admin/locations/states/forms/edit/{id}/` |
| `locations-states-list` | `/locations/states/list/` | `/admin/locations/states/list/` |
| `locations-states-ajax-search` | `/locations/states/search/` | `/admin/locations/states/search/` |
| `locations` | `/locations/` | `/admin/locations/` |
| `organizations-admin-actions-add` | `/organizations/admin/action/add/` | `/admin/organizations/action/add/` |
| `organizations-admin-actions-delete` | `/organizations/admin/action/delete/{id}/` | `/admin/organizations/action/delete/{id}/` |
| `organizations-admin-actions-edit` | `/organizations/admin/action/edit/` | `/admin/organizations/action/edit/` |
| `organizations-admin-ajax-all` | `/organizations/admin/all/` | `/admin/organizations/all/` |
| `organizations-admin-datatables` | `/organizations/admin/datatables/` | `/admin/organizations/datatables/` |
| `organizations-admin-forms-add` | `/organizations/admin/forms/add/` | `/admin/organizations/forms/add/` |
| `organizations-admin-forms-edit` | `/organizations/admin/forms/edit/{id}/{lang}/` | `/admin/organizations/forms/edit/{id}/{lang}/` |
| `organizations-admin-list` | `/organizations/admin/list/` | `/admin/organizations/list/` |
| `admin-tickets-create` | `/tickets/create/` | `/admin/tickets/create/` |
| `timing-add` | `/timing/add/` | `/admin/timing/add/` |
| `users-form-create` | `/users/add/type/{type}/` | `/admin/users/add/type/{type}/` |
| `users-ajax-all` | `/users/all/` | `/admin/users/all/` |
| `users-datatables` | `/users/datatables/` | `/admin/users/datatables/` |
| `users-delete-account-request` | `/users/delete-account/` | `/admin/users/delete-account/` |
| `users-form-edit` | `/users/edit/{id}/` | `/admin/users/edit/{id}/` |
| `users-edit-request` | `/users/edit/` | `/admin/users/edit/` |
| `users-list` | `/users/list/` | `/admin/users/list/` |
| `users-form-profile` | `/users/profile/` | `/admin/users/profile/` |
| `users-register-request` | `/users/register/` | `/admin/users/register/` |
| `users-revoke-sessions-request` | `/users/revoke-sessions/` | `/admin/users/revoke-sessions/` |
| `users-search-dropdown` | `/users/search-dropdown/` | `/admin/users/search-dropdown/` |
| `users-selection-create` | `/users/select-type/add/` | `/admin/users/select-type/add/` |
| `user-system-features-configure-totp` | `/users/user-system-features/configure-totp/` | `/admin/users/user-system-features/configure-totp/` |
| `user-system-features-get-current-totp-qr-data` | `/users/user-system-features/get-current-totp-qr-data/` | `/admin/users/user-system-features/get-current-totp-qr-data/` |
| `user-system-features-get-current-totp` | `/users/user-system-features/get-current-totp/` | `/admin/users/user-system-features/get-current-totp/` |
| `user-system-features-mark-current-totp-qr-as-viewed` | `/users/user-system-features/mark-current-totp-qr-as-viewed/` | `/admin/users/user-system-features/mark-current-totp-qr-as-viewed/` |
| `localization-system-features-get-lang-messages-by-group` | `/admin/localization-system-features/get-lang-messages-by-group/` | `/core/localization-system-features/get-lang-messages-by-group/` |
| `newsletter-admin-add` | `/admin/newsletter/add/` | `/newsletter/add/` |

## ⚠ Seguridad y CAMBIO INCOMPATIBLE — el cifrado pasa a AES-256-GCM, y la clave vive en su llavero

- **El cifrado anterior del framework no ofrecía la protección que su nombre promete.** Afectaba a **la configuración
  del correo (con la contraseña SMTP), la de Mailjet** y los datos de los tokens. **Si tu instalación guarda
  credenciales de correo, cámbialas en tu proveedor después de actualizar.**
- **Ahora se cifra con AES-256-GCM** (`PiecesPHP\Core\Crypto\Sealed`), con una llave por propósito derivada de
  `app_key`: datos de los tokens, correo, Mailjet, cuerpo de los correos y el secreto del doble factor (TOTP). Lo guardado en el formato viejo se lee siempre: la configuración de correo y de Mailjet se
  vuelve a guardar cifrada al cargarse; **el secreto TOTP y el cuerpo de los correos, con `bin/cli seal-legacy-data`, que
  hay que ejecutar una vez después de actualizar** (no cambia la clave ni cierra sesiones). Los tokens de antes no se
  leen: todos vuelven a iniciar sesión.
- **La clave sale de `config.php`**: vive en `secure-keys/app-key.json` (el llavero), con la actual y las anteriores.
  **En el primer arranque con la clave de relleno se genera sola**. Si tu clave está en `config.php`, se sigue leyendo y
  un aviso pide moverla: `bin/cli store-app-key` (no la cambia).
- **Cambiar la clave ya no pierde nada**: `bin/cli rotate-app-key` vuelve a cifrar todo lo declarado; `retire-app-key`
  quita una clave anterior cuando ya nadie la usa. Detalle en `source-docs`, «La clave de la aplicación».
- **Los archivos protegidos y los tokens genéricos validan también con la sesión**, además de la firma.
- **`BaseHashEncryption::encrypt()`/`decrypt()` pasan a llamarse `obfuscate()`/`deobfuscate()`**, y `verify-integrity` falla si se usan fuera de su lista o si algo cifra con un
  propósito no declarado. `UsersController::encodeURL()`/`decodeURL()` salen (nadie las llamaba). El tipo de usuario del
  alta va firmado, y el id del usuario ya no viaja a las vistas de la zona pública.
- **Al actualizar, todos vuelven a iniciar sesión**, y un token genérico guardado antes (los de `TokenModel`) deja de
  abrirse, sin error.
- **Para un clon**: después de actualizar, ejecuta `bin/cli db-backup` y `bin/cli seal-legacy-data`; comprueba en Avisos del
  sistema que no salga el de la `app_key` en `config.php` (o ejecuta `store-app-key`), y cambia en tu proveedor las credenciales de correo y de Mailjet que tuvieras guardadas. Si
  tu código cifraba con `BaseHashEncryption::encrypt()`, decláralo y usa `Sealed`.

## ⚠ CAMBIO INCOMPATIBLE — un aviso de PHP ya no tumba la página en producción (errores graves y suaves)

- **Dos clases de error** (ADR 0060). **Graves** (excepciones no capturadas, `E_ERROR`, `E_PARSE`, `E_RECOVERABLE_ERROR`,
  `E_USER_ERROR`): rompen en todas partes, como siempre, y enseñan su referencia. **Suaves** (`E_WARNING`, `E_NOTICE`,
  `E_DEPRECATED` y sus `E_USER_*`): **fuera de local ya no rompen**; se anotan con su referencia y la petición sigue.
  **En local siguen rompiendo**, para que se arreglen, salvo `E_USER_WARNING` y `E_USER_NOTICE`, que nunca rompieron.
- **`log_soft_error($message, $context)`**: anota algo suave a propósito (una integración que falla) y devuelve su
  referencia. No rompe nunca.
- **El registro de errores cambia de formato**: `logs/error.log.jsonl`, una entrada por línea, que solo crece y se
  escribe con cerrojo (dos errores a la vez ya no se pisan). Cada traza se guarda una vez, en `logs/error-traces/`, y
  las entradas la nombran por su huella: medido sobre un registro real, **un 77 % menos de espacio sin perder nada**. Se
  guardan además las excepciones previas y el contexto mínimo de la petición (método, nombre de ruta, dirección sin la
  cadena de consulta, usuario). Los suaves repetidos el mismo día suman un contador en vez de otra entrada. El
  `error.log.json` viejo se sigue leyendo.
- **Registros › Errores** enseña el nivel, las repeticiones y los marcos del enrutador y de `vendor/` plegados. Y root
  ve un aviso flotante «Hay N errores nuevos desde tu última visita», que se oculta desde Avisos del sistema.
- **Lo que ve cada uno en Registros y en el registro de acciones** sigue la jerarquía: root, todo; los demás, lo suyo y
  lo de rangos menores (`UserHierarchy`). Lo que hizo root suplantando a alguien cuenta como de root.
- **Para un clon**: si contabas con que un aviso abortara una operación en producción, lanza una excepción. Si leías
  `error.log.json` con tus herramientas, lee también el `.jsonl`.

## ⚠ CAMBIO INCOMPATIBLE — cada sesión es una fila, y al actualizar todos vuelven a iniciar sesión

- **Cada inicio de sesión crea una fila** en la tabla nueva `pcsphp_user_sessions` (núcleo:
  `databases/actualizaciones/2026-10-10-sesiones.sql`), y el token lleva el identificador de su sesión. En cada petición
  se comprueba que esa sesión existe, es del mismo usuario, no está cerrada y no ha caducado. **La base guarda el sha256
  del identificador, nunca el identificador.**
- **Un token sin identificador ya no entra: al actualizar, todo el mundo vuelve a iniciar sesión una vez** (decisión del
  PO, P130 a).
- **La fila manda sobre la caducidad** (ver arriba, «la sesión caduca por inactividad con un tope»).
- **Cerrar sesión cierra en el servidor** (ruta `user-sessions-logout`); antes solo se borraba la cookie y el token
  seguía valiendo hasta caducar.
- **Nuevo**: «Mis sesiones» en la seguridad de la cuenta (cerrar una o todas las demás); la pestaña **Sesiones
  abiertas** en Registros; las sesiones en la ficha de cada usuario. Los administradores cierran o extienden las de
  rangos menores que el suyo (root, las de todos). Cada cierre y extensión queda en el registro de acciones.
- **Para un clon**: aplica `2026-10-10-sesiones.sql`. Si tu código fabricaba tokens con `SessionToken::generateToken()`
  para iniciar sesión, usa `UserSessions::issueToken()`: un token sin fila ya no vale. Si validabas con
  `SessionToken::isActiveSession()`, usa `UserSessions::jwtIsValid()` (firma y fila).

## ⚠ CAMBIO INCOMPATIBLE — los registros, en una sola pantalla

- **Nueva pantalla «Registros»** (`logs-admin-index`, en la barra superior), con una pestaña por registro: **Errores**
  (solo root), **Correos enviados** (solo root) y **Accesos** (root, administrador general y administrador de
  organización, este con lo de su organización). Cada usuario ve solo las pestañas a las que tiene permiso. Detalle en
  `source-docs`, «Registros».
- **Los errores, por fin una pantalla**: por día, con filtros y su detalle; la referencia `ERR-…` que ve el usuario se
  busca y abre su entrada, también en los archivos rotados.
- **Sale la ruta `admin-error-log`** (el JSON crudo del registro de errores), y su línea del catálogo de roles.
- **Dos direcciones viejas solo redirigen**: `system-status-mail-log` lleva a la pestaña de correos y
  `login-attempts-reports` (con `?attempts`, `?logged` o `?not-logged`) a la de accesos. Sus rutas de datos y de
  exportación no cambian.
- **El menú «Registro»** lleva cuatro accesos directos: Intentos de ingreso, Registro de ingresos, Usuarios sin ingresos
  y Todos los registros; «Registro de correos» sale de Configuración › Sistema.
- **Los informes de acceso** se pintan con las piezas de estadísticas y ApexCharts: salen 74 de las 76 reglas de su hoja.
- **El registro de acciones lo ven también los administradores**: el general, todo; el de organización, solo lo de su
  organización. Tiene filtros, el detalle de cada acción (ruta nueva `actions-logs-admin-detail`) y ocho tipos propios
  donde antes se escribía `GENERIC`. Las filas viejas siguen como `GENERIC`.
- **`actions_log` y `login_attempts` tienen índice por fecha y un tope de 200.000 filas** (las más viejas se borran;
  nunca por debajo de 1.000): `databases/actualizaciones/2026-10-10-indices-de-los-registros.sql`. Y `pcsphp_mail_log`
  entra por fin en `databases/piecesphp_structure.sql`.
- **La leyenda de los gráficos es interactiva** (`linkStatisticsChartLegend()`, `statics/core/js/statistics-chart-legend.js`).
- **`SystemAlert` acepta un décimo parámetro opcional**, `$fixQuery`: lo que va tras «?» en el enlace «Cómo arreglarlo».
- **`error.unique.message.log` cambia de formato**: la línea de la fecha lleva la referencia de la primera aparición,
  `[fecha] [ref ERR-…] [Tipo] …`. La deduplicación no cambia.
- **La hoja de los informes de acceso** pasa de `statics/core/sass/registers/attempts.scss` a
  `UserSystem/Statics/sass/attempts.scss`, con las mismas reglas.
- **Para un clon**:
  - si un rol tuyo tenía `admin-error-log` en su `allowed_routes`, cámbialo por `logs-admin-index` y
    `logs-admin-errors`;
  - si enlazabas `system-status-mail-log` o `login-attempts-reports`, enlaza `logs-admin-index` con `?tab=mails` o
    `?tab=access&view=attempts|logged|not-logged`;
  - **un registro tuyo puede tener su pestaña**: `LogTabRegistry::register(new LogTab(…))` en tu `*Routes`;
  - aplica `2026-10-10-indices-de-los-registros.sql`; si tus tablas de registro deben guardar más de 200.000 filas,
    llama a `setMaxRows()`.

## Cambia — el listado de publicaciones estrena el armazón nuevo del panel

- **Es la primera pantalla del rediseño** (la propuesta de la familia de contenidos, aprobada por el PO el 2026-10-09).
  Una cabecera con una sola acción principal («Nueva publicación») y «Categorías» como secundaria; la tabla sin la
  columna de número; el título, enlazado a su edición y con su categoría debajo; y las acciones de la fila: editar a la
  vista, y «Ver en el sitio» y «Eliminar» a su lado, los tres con el color de marca (Editar en el principal, los otros dos
  en el secundario) y del tamaño normal del panel; el menú «⋯» solo aparece en una fila con más de tres acciones. El
  borrado es el de siempre.
- **El listado de noticias, igual**: se llama «Noticias internas», como en el menú; «Categorías» pasa a secundaria
  (antes «Gestionar categorías», como principal) y la tabla pierde la columna de número, que se sigue buscando.
- **El alta y la edición de publicaciones, igual**: la misma cabecera («Nueva publicación», «Editar: <título>», con «Ver en
  el sitio» al lado) y **la barra de guardar al pie del formulario**, con «Guardar»,
  «Cancelar» y, en la edición, «Eliminar» en rojo a la derecha. Un campo obligatorio vacío ya no nace rosa: el rojo sale al
  intentar guardar. En el móvil, las pestañas del formulario van en una fila que se desliza, y la barra cabe en una fila: «Guardar» con su
  texto y las demás acciones como iconos. «Eliminar» ya no es un botón de envío: sin JavaScript, pulsarlo habría guardado.
- **El alta y la edición de noticias, igual que las de publicaciones**: «Nueva noticia» y «Editar: <título>» (antes,
  «Gestión de noticias» en las dos) y la barra de guardar al pie.
- **Documentos, igual**: el listado ya no lleva el formulario de alta encima (el primer pliegue no enseñaba ningún
  documento); «Nuevo documento» lleva a su propia página, con la misma cabecera y la barra de guardar que publicaciones y
  noticias, y «Tipos de documento» queda como secundaria. El explorador de documentos estrena la cabecera (antes su
  título no se veía).
- **Las categorías de publicaciones, en una ventana**: el listado tiene la cabecera nueva, y «Nueva categoría» y «Editar»
  abren una ventana sobre él; al guardar, la tabla se actualiza sin recargar la página. Las páginas de alta y edición
  siguen funcionando por su dirección. La tarjeta vieja del listado
  (`src/app/classes/Publications/Views/categories/util/list-card.php`) se borra: nadie la usaba ya, y su botón de eliminar
  estaba roto. **Las categorías de noticias**, que llevan color e icono, se quedan en su página, con la cabecera y la barra
  nuevas. **Las categorías de formularios y los tipos de documento, también en ventana**, con «Traducir» en cada fila
  para editar el otro idioma en su página.
- **La hoja de diseño del panel** (menú de la rueda, junto a «Acerca de»; la ven root y el administrador general): una
  sola página, con índice, que enseña todo lo que usa el panel —colores de marca y de estado, tipografía, botones,
  etiquetas, campos de formulario, las piezas de las pantallas de contenido, mensajes, avisos y tarjetas—, cada cosa con
  cuándo se usa y **el código que la pinta, que es el mismo que corre**: cada ejemplo es un archivo
  (`src/app/classes/PiecesPHP/AdminPanel/Views/panel/pages/design-sheet/examples/`) que la hoja ejecuta y enseña. La
  pestaña «Pantallas de contenido» de «Ejemplos de elementos» se va a ella.
- **Los gráficos y estadísticas de los informes, piezas reutilizables** (`src/app/view/panel/built-in/statistics/`: sección,
  tarjeta de cifra, tarjeta de gráfico, leyenda, círculo de progreso, cifras con icono y tarjeta de acción), cada una con
  sus variantes como parámetros. El informe genérico (`ReportsManage`) ya pinta con ellas, con el mismo HTML de antes, y
  la hoja de diseño las enseña. El gráfico lo sigue dibujando el JavaScript de cada pantalla, con ApexCharts.
- **Gráficos y estadísticas, con su página para desarrolladores** («Gráficos y estadísticas»,
  `source-docs/project/docs/piecesphp/new-features/estadisticas.md`) y su CSS en una hoja del núcleo
  (`statics/core/css/graphs-and-statistics.css`), para cargarla desde cualquier pantalla.
- **Para quien desarrolla un clon, todas las piezas con ejemplos que se pueden copiar**, en la documentación:
  «Pantallas de contenido» (`source-docs/project/docs/piecesphp/new-features/pantallas-de-contenido.md`).
- **Para quien desarrolla un clon, una pieza más: la ventana de formulario** (`panel/built-in/content/form-modal.php`),
  que se abre vacía (alta) o con valores (edición) desde cualquier elemento con `data-content-modal`, y al guardar con
  éxito se cierra y recarga la tabla de la página.
- **En el móvil, la paginación de los listados ya no se sale de la pantalla**: envuelve, y sin «Primero» ni «Último».
- **En el móvil, los listados de contenido son tarjetas**: cada fila se lee entera, con la etiqueta de cada dato, y la
  tabla ya no se desliza en horizontal. Vale para toda tabla con la clase `content-table`, sin que su módulo haga nada
  (`contentTablesStackable()`, en `admin-area/js/main.js`).
- **El explorador de documentos, en tarjetas**: con migas, y en cada tarjeta el icono de su tipo de archivo (PDF,
  imagen, hoja de cálculo, Word, texto) cuando no hay miniatura, el nombre, el tipo, la descripción y «Abrir». La tabla de
  iconos vive en un solo sitio: `DocumentsController::FILE_TYPE_ICONS` y `fileTypeIcon()`.
- **Un título largo cabe en la cabecera**: con una acción o ninguna, la columna del título es más ancha (once de
  dieciséis), y «Editar: <nombre largo>» ocupa una línea menos (una sola hasta unos 40 caracteres).
- **En los listados, el título y su categoría ya no se parten** por hacer sitio a los botones de la fila: las columnas
  cortas (estado, fechas, visitas, destacado) ocupan solo lo que necesitan.
- **Lo sembrado con `bin/cli seed` se puede editar desde el panel**: sus carpetas de subida quedan escribibles por el
  servidor web (antes, cambiar la imagen o la traducción de un registro sembrado daba error interno), y las noticias salen
  con perfiles y fechas, que su formulario exige.
- **El estado de las noticias, con los colores de la familia**: verde activa, rojo inactiva y naranja borrador.
- **Las piezas aceptan atributos** (`attributes` en las acciones de la cabecera y de la barra; `saveAttributes`, `icon` y
  `danger` en la barra), escapados y sin manejadores `on…`, para que el JavaScript de cada módulo encuentre sus marcas.
- **La cabecera ya no se pega a lo que viene debajo**, y en tableta y móvil sus botones van a la izquierda, como el título.
- **El selector «Ver [ ] elementos» ya no sale vacío** en ningún listado: el número de filas que pide la pantalla está
  siempre entre las opciones.
- **Para quien desarrolla un clon, piezas para sus propias pantallas**, enseñadas en «Ejemplos de elementos», pestaña
  «Pantallas de contenido»: la cabecera (`panel/built-in/content/page-header.php`), el aviso de «aún no hay…»
  (`empty-state.php`), la barra de guardar al pie del formulario (`form-action-bar.php`) y `PiecesPHP\Core\Utilities\Helpers\RowActions`
  para la celda de acciones, que escapa todo lo que pinta. Un formulario con la barra nueva no se pinta en rojo hasta que
  se intenta guardar. **Lo demás del panel no cambia de aspecto** (medido con `bin/huella-css`: solo dos reglas nuevas).

## ⚠ CAMBIO INCOMPATIBLE — una denegación de permiso responde 403, también dentro de un `try`

- **Antes**, cuando un controlador del framework negaba el acceso con `throw403()` dentro de un bloque que captura
  `\Exception`, ese `catch` se comía la denegación: el cliente recibía el mensaje de error genérico (con su referencia de
  registro) y el registro de errores guardaba cada denegación como si fuera un fallo.
- **Ahora** esos bloques relanzan `HttpForbiddenException` antes de tratar lo demás: **la respuesta es un 403** y el
  registro de errores ya no se llena de denegaciones. Si un cliente propio buscaba el mensaje genérico para detectar «no
  tienes permiso», tiene que mirar el 403.
- **En el código de un clon**, la misma trampa sigue ahí si no la evitas: la forma de evitarla está en la página de
  permisos de ruta, «Y una trampa de `throw403()`». La vigila una prueba de `gates` (`permissions-coherence`, [9]): todo
  `try` que envuelve un `throw403()` y captura `\Exception` tiene que relanzar antes la denegación.

## ⚠ CAMBIO INCOMPATIBLE — fuera «Ejemplos de elementos»

- La pantalla (`my-space-admin-example-resources`, `/admin/my-space/example-resources/`) y su entrada de menú se retiran:
  lo que enseñaba lo enseña ahora la **hoja de diseño** (`admin-design-sheet`), con su código.
- **Se quedan las bibliotecas** que demostraba: SurveyJS y la del diálogo propio, por si un clon las usa.

## ⚠ CAMBIO INCOMPATIBLE — las hojas de estilo y el JavaScript de la configuración, en su módulo

- **Antes** vivían en el núcleo: `src/statics/core/sass/app_config/` (14 hojas y su `includes/_base.scss`) y
  `src/statics/core/js/app_config/` (11 archivos).
- **Ahora** viven con su módulo, en `src/app/classes/PiecesPHP/Settings/Statics/{sass,js}/`, y se sirven por la ruta de
  estáticos del módulo (`SettingsRoutes::staticRoute()`, bajo `/admin/configurations/statics/`). Las reglas son las mismas,
  una a una: ninguna pantalla cambia de aspecto por esto.
- **Si tu clon había modificado alguna de esas hojas o scripts**, lleva tus cambios al sitio nuevo. Y si en tu servidor
  queda `statics/core/css/app_config/`, son compilados viejos: se pueden borrar.
- **El índice «Configuración»** estrena la cabecera del panel, sin la franja de color.
- **Las cinco pantallas de Apariencia, igual** (imágenes de marca, fondos, colores, identidad y SEO, archivos para
  buscadores): cabecera con migas y una frase de qué hace cada una, y cada formulario con su barra de guardar en el color
  de marca (antes, el azul de Fomantic). Las imágenes y los fondos se siguen guardando desde su recortador. Sale la hoja
  `seo.scss`, que se había quedado vacía.
- **Las cuatro de Integraciones, igual** (correo, osTicket, inteligencia artificial y scripts). Salen sus hojas `email.scss`,
  `os-ticket.scss` y `ai.scss`, que se quedaban vacías. Ningún secreto cambia de trato: el campo vacío sigue significando
  «no cambiar» y la vista no los enseña.

## ⚠ CAMBIO INCOMPATIBLE — fuera la constante `CURRENCIES`

- La lista de monedas de `src/app/config/constants.php` no la usaba nada del framework. Si un clon la usaba, se la define
  en su propia configuración.

## ⚠ CAMBIO INCOMPATIBLE — `bin/cli` sale con error cuando una prueba falla o la orden no existe

- **Una suite que termina en rojo hace que `bin/cli <suite>` salga con código 1.** Antes salía con 0, y solo la línea
  «BALANCE FINAL» decía la verdad: quien encadenaba `bin/cli <suite> && <lo siguiente>` —en un guion, en una
  integración continua— creía que había pasado.
- **Una orden que no existe también sale con 1**, después de listar las que hay. Antes, `bin/cli` con una errata salía
  con 0.
- **Qué cuenta como rojo**: que la acción devuelva `['success' => false, …]`, como hacen todas las suites del framework.
  Una acción propia que devuelva otra cosa, o nada, sigue saliendo con 0.
- **`bin/cli gates` da el mismo veredicto que antes**: ya leía el balance de cada suite, y ahora el código de salida
  dice lo mismo.

## ⚠ CAMBIO INCOMPATIBLE — una petición de datos sin sesión responde 401, no 403

- **A una ruta que pide sesión, una petición de datos (XHR) sin sesión responde `401 Unauthorized`**, con la cabecera
  `WWW-Authenticate: Bearer`. Antes respondía `403 Forbidden`, que significa «sé quién eres y no puedes», y aquí nadie
  se había identificado. El cuerpo no cambia: `{"error": "RESTRICTED_AREA", "message": …}`.
- **Lo que no cambia**: una petición de página sin sesión sigue redirigiendo al acceso, y un usuario con sesión al que su
  rol no le deja sigue recibiendo `403`.
- **Para quien consuma la API o tenga JavaScript propio**: si distinguía «sin sesión» por el `403`, ahora es el `401`.

## ⚠ CAMBIO INCOMPATIBLE — una acción de usuarios que no existe responde 404

- **Pedir una acción de usuarios que no existe responde `404`**, como cualquier ruta que no existe. Antes respondía
  `200` con el cuerpo vacío, y un cliente lo leía como un éxito sin datos.

## ⚠ CAMBIO INCOMPATIBLE — el framework pide `piecesphp/database` 5.3 o superior

- **`src/composer.json` pide `piecesphp/database: ^5.3`** (antes, `^5.0`). Los totales de los listados del panel usan
  desde la `v8.0.8` un método que solo existe desde la 5.3 (`getPreparedSQL()`, ADR 0054): con una anterior, fallan. La
  `v8.0.8` ya instalaba la 5.3 desde su `composer.lock`; solo se rompía quien fijara a mano una versión anterior.
- **Para un clon**: `composer update piecesphp/database --working-dir=src`.

## ⚠ CAMBIO INCOMPATIBLE — `Mailer::asGoDaddy()` se llama `asSystemMailer()`, que es lo que hace

- **`asGoDaddy()` no salía por GoDaddy**: configura la entrega por el correo de la propia máquina —`mail()` o, con
  `true`, `sendmail`—, sin autenticación, a `localhost:25`. **Ahora se llama `asSystemMailer(bool $sendmail = false)`**,
  con el mismo comportamiento, incluido que con el correo retenido no hace nada (ADR 0045).
- **Sin alias**: el nombre viejo desaparece. **Para un clon**: busca `asGoDaddy(` y cámbialo por `asSystemMailer(`.

## ⚠ CAMBIO INCOMPATIBLE — fuera `test_mode` de la configuración de correo

- **Desde la `v8.0.0` no decidía nada**: quién recibe el correo de verdad lo declara la entrega (`mail_delivery`, ADR
  0045), y el selector de la pantalla de correo ya editaba esa declaración. `test_mode` seguía guardándose sin que nadie
  lo leyera.
- **Salen** `MailConfig::testMode()`, las constantes `TEST_MODE_AUTO`, `TEST_MODE_ON`, `TEST_MODE_OFF` y `TEST_MODES`, y
  la clave `test_mode` de lo que se guarda. **Se queda `testModeActive()`**, que dice si el correo está retenido.
- **Una configuración guardada con `test_mode` sigue cargando**: la clave se ignora.

## ⚠ CAMBIO INCOMPATIBLE — el catálogo de rutas ya no lleva `route_alias`

- **Valía siempre el nombre de la propia ruta** desde que los alias se retiraron en la campaña de la 8: un campo que no
  decía nada. Sale de lo que devuelve `RouteAdapter::toArray()` y, con él, del catálogo de rutas (`get_routes()`).
- **Para un clon**: si leías `route_alias`, lee `name`.

## ⚠ CAMBIO INCOMPATIBLE — la tabla `interest_research_area` sale de verdad del esquema

- **La `v8.0.0` anunció que desaparecía** con el módulo de áreas de interés de investigación, **pero
  `databases/piecesphp_structure.sql` la seguía creando**: un clon nuevo nacía con una tabla que ningún código usa.
  Ahora no la crea.
- **Para una instalación que ya la tenga**: `databases/actualizaciones/2026-10-09-retira-interest-research-area.sql`, un
  `DROP TABLE IF EXISTS`. **Antes, un respaldo**: si un módulo propio la usaba, sus filas se pierden.

# 8.0.8 (09-10-2026)

## ⚠ Seguridad — una tabla puede declarar qué columnas no salen NUNCA, y el framework lo respeta solo

**Esto cierra una trampa de diseño, no una fuga en curso.** Hasta ahora, que una columna sensible no saliera al serializar
un registro dependía de que cada controlador se acordara de taparla. PiecesPHP se clona, y nada obligaba al siguiente.

Ahora **la tabla declara qué columnas suyas no se serializan jamás**, en una línea, y el framework lo cumple por su
cuenta en todas las vías por las que un dato puede salir: pedir el registro directo, pedirlo anidado dentro de otro, a
través de una meta-propiedad y convirtiéndolo a texto.

Lo declaran dos tablas del framework: la de **usuarios** y la de los **secretos del doble factor**.

**Lo que no cambia**: la columna sigue existiendo y se lee y escribe igual; la descripción de los campos sigue saliendo
completa, porque es la definición de la tabla y no tiene valores dentro; y las protecciones que los controladores hacían
a mano se quedan, porque dos capas no estorban.

Todo esto lo vigila una suite nueva, que se ha visto fallar al vaciar la declaración.

## ⚠ Seguridad — los totales de los listados del panel se piden con la búsqueda enlazada

Un listado del panel enseña sus filas y, debajo, dos totales: cuántas hay con la búsqueda y cuántas sin ella. Ahora los
totales se piden como las filas: la consulta por un lado y la búsqueda por otro. Además, una búsqueda con dos espacios
seguidos ya no da un total falso.

Necesita **`piecesphp/database` 5.3.0**, que trae el método nuevo con el que se arma la consulta para ejecutarla
enlazando; el `composer.lock` ya la fija.

**Si tu clon ejecuta en algún sitio el texto de `getCompiledSQL(true)`**, cámbialo por `getPreparedSQL()` con sus
valores enlazados: `getCompiledSQL(true)` es para registrar y depurar, no para ejecutar. Lo vigila una suite nueva,
`unit-tests:core/datatables-counts`, que compara los dos totales con las filas reales en cuatro formas de listado.

## Retira — el CSS muerto del navegador de contenidos, sin cambiar el aspecto

Fuera **`src/app/classes/ContentNavigationHub/Statics/sass/detail-view.scss`**: ninguna página la cargaba y ninguna hoja
la importaba, así que su CSS no llegaba a ningún sitio. Y fuera las reglas de las otras hojas del módulo que estilaban un
marcado que nada emite: **59 reglas**, casi todas colgadas de una clase de listado que ninguna vista usa.

**El aspecto no cambia, y está medido**: comparado el CSS compilado regla a regla, lo único que desaparece son
exactamente las reglas previstas, y no aparece ni cambia ninguna otra. Antes de quitar cada una se buscaron sus clases
también como trozo de texto en el código, por si alguna se armaba concatenando: ninguna.

**Si tu clon cargaba `detail-view.css`** en una vista propia, esa hoja ya no se genera: copia lo que uses a tu módulo.

## Cambia — las hojas de perfil de MySpace comparten su cuerpo, y el CSS es el mismo byte a byte

Las dos vistas de perfil —la personal y la de organización— y sus dos formularios tenían cada uno su hoja de estilos,
copiada de la otra casi entera. Ahora cada pareja comparte un parcial, en la carpeta `includes` de las hojas de
estilo de MySpace: `_profile-view.scss` y `_profile-form.scss`, con un `@mixin` al que cada hoja pasa lo suyo —la clase
de su sección, su color de acento y el bloque que solo tiene la de organización—. Son **401 renglones de SCSS menos**,
contando ya los dos parciales.

**El aspecto no cambia, y está medido**: la huella del CSS compilado da IDÉNTICA y los cuatro `.css` son los mismos byte
a byte.

**Si tu clon editó alguna de esas cuatro hojas** (`profile.scss`, `profile-organization.scss`, `my-profile.scss`,
`my-organization-profile.scss`), al actualizar tendrás conflicto: lo común está ahora en el parcial, y lo tuyo va o en el
parcial —si vale para las dos— o en el bloque que cada hoja pasa al `@mixin`.

## Otra herramienta nueva: `bin/cli seed`, datos de prueba que se escriben y se deshacen

Llena la base **local** con registros de prueba reconocibles, para ver una pantalla con contenido sin crearlo a mano, y
los quita después **por id**, sin tocar nada más. **Sin pedir nada, solo simula**: escribir hay que pedirlo con
`escribir=yes`, y deshacerlo con `limpiar=yes`. Solo escribe en una instalación que sea local por las dos vías —la
terminal y su `environment.php`—, en una sola transacción, y se niega a sembrar dos veces.

Cada módulo aporta los suyos **sin tocar el framework**: una clase en la carpeta `Seeds/` del módulo. Hoy siembra
dieciséis tablas: organizaciones, usuarios, países, regiones, ciudades, puntos, noticias y sus categorías, publicaciones
con sus categorías y sus adjuntos, banners, documentos con sus tipos, categorías de formularios y suscriptores del
boletín.

**Las tablas con archivos llevan archivos PROPIOS**: el sembrador copia una imagen de muestra a una carpeta que es solo
de esa fila, y al limpiar borra la carpeta entera. Nunca apunta a un archivo del repositorio, porque al cambiar la imagen
de un registro el panel borra la anterior. Y el limpiado borra también **lo que la aplicación crea sola** por cada fila
sembrada —los perfiles y las aprobaciones—, distinguiendo las aprobaciones de cada tabla para no llevarse las de otra
con el mismo número.

Guía: «Datos de prueba», en la documentación para desarrolladores.

## Otra herramienta nueva: `bin/huella-css`, para demostrar que tu CSS sigue haciendo lo mismo

Sirve para una cosa concreta y difícil: **reorganizar hojas de estilo sin cambiar el aspecto, y poder probarlo**.
Guarda un calco del CSS compilado, y después de tus cambios te dice si sigue siendo el mismo.

Lo que la distingue de comparar los archivos con un `diff`:

- **Guarda el ORDEN**, y eso es lo que la hace útil. En CSS, cuando dos reglas tocan la misma propiedad gana la que
  viene después, así que **mover una regla de sitio puede cambiar el resultado sin cambiar ni un carácter de su
  contenido**. Un `diff` de texto no ve eso; esta sí.
- **Clasifica la diferencia en cuatro**: una regla que **desaparece**, una **nueva**, una cuyas **declaraciones**
  cambian, y una que **solo cambia de posición**. Así una revisión sabe de qué está hablando.
- **Y tiene un modo para cuando sí quieres borrar**: le pasas la lista de selectores que esperas perder y aprueba solo
  si las únicas diferencias son esas. Cualquier otra cosa sale en rojo.
- **Se niega a dar un veredicto si no se ve a sí misma.** Antes de comparar, se altera por dentro de cuatro formas y
  exige acertar las cuatro; si una falla, **no dice «idéntica»: se para**.

Lo normaliza poco a propósito: colapsa espacios y pone en minúsculas los nombres de propiedad, pero **no toca los
valores** —en `content` y en `url()` las mayúsculas cuentan— y **no deduplica, no fusiona y no reordena nada**. Es un
calco, no un resumen.

## Una herramienta nueva: `bin/censo-scss`, para saber qué CSS tuyo no se usa

Si has heredado hojas de estilo que nadie se atreve a tocar, esta mide cuáles de sus clases están realmente en uso.
No borra nada: solo cuenta y señala.

Lo que la hace fiable, y es lo que distingue una cifra de una cifra en la que se puede confiar:

- **Lee los selectores del CSS compilado, no del texto del SASS.** En SASS un nombre se construye anidando, con `&` y
  con `&-sufijo`, así que **el nombre final no está escrito en ninguna línea del archivo**. Leer el `.scss` a mano
  contaría nombres que no existen y se dejaría los que sí. Y con los mapas del compilador vuelve atrás, así que cada
  clase se atribuye a **su archivo y su línea de origen**.
- **Separa en cuatro grupos**, y dos están ahí para que no borres de más: lo que tu CSS **no inventa sino que pisa** de
  Fomantic o de un plugin —borrarlo cambia el aspecto aunque la clase no aparezca en ningún archivo tuyo— y lo que tu
  código **construye pegando un prefijo a una variable** (`'icono-' + tipo`, en JavaScript o en PHP), que no se puede
  saber leyendo y por eso se marca «no verificable» en vez de «sin uso». Y si el nombre **entero** es una variable
  (`'ui ' + color`), no lo marca: lo **cuenta y lo lista**, porque no hay a qué clase atribuirlo.
- **Mide la regla, no la clase.** Una regla como `.a .b` no puede aplicarse nunca si falta **una sola** de las dos,
  aunque la otra esté muy viva. Da las dos cifras: la de reglas que no pueden aplicarse nunca y, aparte, la estricta.
- **Se niega a mentir.** Antes de dar un número comprueba que se ve a sí mismo: que una clase que sabe usada sale como
  usada, que una inventada sale sin uso, y que su lógica de reglas acierta en seis casos cuyo resultado se conoce de
  antemano. Si algo de eso falla, **no imprime cifras: se para**. Y declara siempre su propio universo: cuántas hojas
  leyó, cuántos archivos miró y qué dejó fuera.

Lo que **no** hace, y está en su propia ayuda: no mira variables, mixins ni animaciones —solo clases—; no ve el HTML
que venga de tu base de datos; y **no prueba que algo se pueda borrar**. Eso lo prueba comparar el CSS compilado antes
y después, que es otra herramienta.

## ⚠ CAMBIOS INCOMPATIBLES — una hoja de estilos duplicada pasa a vivir en un solo sitio

**Si tu proyecto importa estilos de MySpace o de Informes, mira esto antes de actualizar.**

Dos módulos tenían **el mismo archivo de estilos**, copiado: 683 renglones idénticos hasta el último byte. Ahora
vive una sola vez, en los recursos compartidos del panel, y los dos módulos lo usan desde ahí.

- **Desaparecen** `src/app/classes/MySpace/Statics/sass/imports/_grapsh_and_statistics.scss` y
  `src/app/classes/ReportsManage/Statics/sass/reports-manage/_report-design.scss`.
- **Aparece** `src/statics/core/sass/includes/_graphs-and-statistics.scss`, con el mismo contenido y el nombre
  corregido —el original decía `grapsh`—.
- **Si alguna hoja tuya importaba una de las dos**, cámbiala por la nueva. No hay capa de compatibilidad: una ruta de
  `@use` que no existe **rompe la compilación**, así que lo vas a ver en cuanto compiles, no en producción.

**Y el CSS que sale es el mismo, byte a byte: los archivos compilados ni siquiera se reescriben**; solo cambian sus
mapas de origen, que es lo único que debía cambiar. No es una afirmación: lo comprueba `bin/huella-css`, que guarda
un calco ordenado del CSS compilado antes del cambio y lo enfrenta al de después. Si hubiera cambiado una
declaración, una regla o incluso **el orden** de una regla, el cambio no se habría publicado.

## ⚠ CAMBIOS INCOMPATIBLES — tres permisos se restringen

**Si tu proyecto depende de que usuarios sin privilegios de administración toquen el banner o la imagen de inicio,
lee esto antes de actualizar.** Son decisiones de permisos que tomó el propietario del framework.

En PiecesPHP, declarar un rol en una ruta **concede** el permiso y no se lo quita a nadie; el acceso real es la unión de
lo que declara la pantalla y lo que da la configuración de roles. Está explicado en la documentación para
desarrolladores, en «Permisos de ruta».

- **El banner del sitio** (listar, crear, editar y borrar) pasa a ser de **Comunicaciones, Administrador general y
  Principal**. Lo pierden Administrador de organización, Usuario general e Institucional; Comunicaciones lo gana.
- **La imagen de inicio del sitio** pasa a ser **solo del usuario principal**. Se guarda por la acción común de los
  ajustes, y lo que la limita es una comprobación dentro del controlador: si amplías esa acción con un ajuste nuevo,
  **necesita su propia comprobación**.
- **El contador de tiempo en la plataforma** suma siempre al usuario de la sesión y ya no acepta otro identificador. El
  panel sigue funcionando igual. Si tu proyecto la llamaba con el identificador de otro usuario a propósito, tendrás que
  registrar el tiempo de otra forma. La dirección sigue abierta a todos los roles, a propósito.

## ⚠ Seguridad — el gestor de archivos rechaza los nombres de archivos ejecutables

Los dos gestores de archivos del panel —el completo y el que se abre desde el editor de texto— **rechazan ahora, antes de
escribir nada, los nombres con extensiones que un servidor web puede ejecutar** (la familia de PHP, los CGI, otras
plataformas, las inclusiones del servidor y la configuración de Apache), en cualquier posición del nombre. PiecesPHP se
clona, así que esta defensa no puede depender de cómo tenga montado el servidor cada quien.

Lo que sí se sigue pudiendo subir no cambia: documentos, hojas de cálculo, imágenes, audio, vídeo, comprimidos y
cualquier nombre con varios puntos, siempre que ninguno de sus tramos sea una de esas extensiones. Quien clona puede
**ampliar o reducir** esa lista: está en una constante del gestor, documentada.

La comprobación entra por donde el gestor valida los nombres, así que alcanza también a crear carpetas, copiar entre
raíces, extraer de un comprimido y rebautizar. El juego de caracteres prohibidos en un nombre no cambia.

**Si tu proyecto subía a propósito archivos con alguna de esas extensiones** por el gestor, dejará de poder hacerlo y
tendrás que ampliar la lista a conciencia.

## ⚠ Seguridad — el nombre de un grupo de componentes se valida contra los que existen

La dirección pública que sirve las plantillas de componentes valida ahora el nombre del grupo, para no depender de cómo
esté montado el servidor de cada quien.

El grupo tiene que ser **uno de los que existen de verdad** (se calculan solos, de lo que hay en el proyecto: un grupo
nuevo funciona sin tocar nada) **y** tener forma de nombre: solo letras, números, guion bajo y guion. Lo que no cumpla
las dos cosas da un 404.

## Corrige lo que dijo la `v8.0.7` sobre el permiso de la pantalla de errores

La `v8.0.7` anunció que la pantalla del registro de errores «dice que es solo del administrador principal». **Eso era
cierto como declaración y engañoso leído como garantía**, y conviene decirlo claro porque afecta a cómo se entienden
los permisos de todo el framework:

**Declarar un rol en una ruta CONCEDE ese permiso; no impide que otro rol lo tenga.** Lo que una ruta declara se **suma**
a lo que la configuración de roles conceda, y el acceso real es la unión de las dos cosas. Así que la pantalla de errores
declara que es del administrador principal —y con eso la conserva aunque se la quiten de la configuración—, pero **si
alguien añade esa ruta a un rol general, ese rol entra**, y la declaración no lo frena.

No ha cambiado el comportamiento: entra quien entraba. Lo que cambia es que **ahora está explicado**, y que la
documentación para desarrolladores lo dice con sus dos consecuencias.

# 8.0.7 (07-10-2026)

## ⚠ Seguridad — lo que escriben los usuarios se pinta escapado

Las pantallas del panel y de la zona pública que pintaban tal cual lo que un usuario había escrito ahora lo escapan:
perfiles, organizaciones, usuarios, documentos, banner, publicaciones, noticias, formularios, mapa de contenidos, el
panel, la barra superior, los datos del SEO, los informes de acceso y el registro de actividad.

Y en **todos los listados del panel que se cargan por JSON**: el registro de actividad, los usuarios, los intentos de
acceso, el banner, las noticias y sus categorías, las publicaciones, las organizaciones, las aprobaciones, los
documentos, los perfiles y los formularios. Se escapa **solo la parte variable** de cada celda: los botones, las
etiquetas de estado y el diseño siguen siendo HTML, como antes.

Además, los enlaces de web y de LinkedIn de un perfil **solo se enlazan si empiezan por `http://` o `https://`**; con
cualquier otra cosa se enseña el texto, sin enlace.

**El contenido del editor no cambia**: sigue guardándose y pintándose tal cual, a propósito (ver la nota de la
`v8.0.6`). Quien tiene permiso para escribirlo es de confianza.

## ⚠ Seguridad — la pantalla del registro de errores declara su permiso

La pantalla que entrega el registro de errores del servidor **declara ahora que es solo del administrador principal**,
como ya hacían el registro de actividad y el de correos. Quien podía entrar antes sigue entrando igual: lo que cambia es
que ahora está escrito.

## ⚠ Seguridad — el nombre de los anexos, las rutas de las imágenes y el recortador

La **ruta de una imagen o de un archivo** y el **nombre de un anexo** se escapan ahora al pintarlos: la imagen de
portada de la pantalla de inicio, el logotipo y el RUT de una organización, el logotipo de «mi organización», las
imágenes de los formularios de publicaciones, el nombre de cada anexo y la imagen del recortador compartido.

## Cambia — el nombre de los archivos que se suben se sanea en todas las subidas

El saneo que la `v8.0.6` estrenó en el banner **ya se aplica a las cinco subidas del framework** que guardan en disco el
nombre que escribe quien sube: el banner, las organizaciones, las publicaciones, **los documentos** y los contenidos
genéricos. El nombre se reduce a letras, números, guion bajo y guion, se recorta si es larguísimo y **pierde los
puntos**: la extensión la pone el framework al mover el archivo —la del original, una vez validado contra los tipos
permitidos—. **Los archivos ya subidos conservan su nombre.**

Para quien clona: la función está en el núcleo, como `safe_upload_name()`, junto a `escape_html()`. Las dos se explican,
con ejemplos copiables y con los errores que parecen correctos y no lo son, en «Escapado y nombres de subidas» de la
documentación del proyecto.

## ⚠ Seguridad — el nombre de las imágenes que se suben al banner

El banner **sanea el nombre de las imágenes nuevas** (solo letras, números, guion bajo y guion; lo demás pasa a guion),
lo recorta si es larguísimo, y su vista previa trata la ruta como texto. La extensión
la sigue poniendo el framework, de la lista de imágenes permitidas. **Las imágenes ya subidas conservan su nombre**: si
una se llamaba de forma extraña, vuelve a subirla.

## Cambia — la vista previa de las subidas

El ayudante de subida solo pinta el fondo de la vista previa **si la ruta es segura**; si trae comillas, paréntesis o
espacios, la pantalla se ve sin fondo en vez de arriesgarse. Afecta a la edición de las categorías de noticias.

## Cambia — el color de una categoría de noticias

Solo se admite un color (hexadecimal, `rgb()` o `rgba()`). Se valida al guardar y, si una fila guardada antes trae otra
cosa, **se pinta el color por defecto** (`#000000`) tanto en el listado del panel como en la tarjeta pública.

Si una categoría guardada antes trae algo que no es un color (un nombre, `hsl()`…), al volver a guardarla hay que
**elegir un color**: el valor viejo ya no se acepta.

## Nuevo para quien desarrolla un clon — `escape_html()`

`escape_html($valor)` escapa texto plano para pintarlo en HTML o en un atributo entre comillas. `null` da `''`, los
escalares y los objetos con `__toString()` se convierten, y un array lanza `TypeError` en vez de pintar «Array». **No
sirve** para JavaScript, para una URL ni para CSS, y **nunca** se aplica al contenido del editor.

`Validator::isColor($valor)` dice si un valor es un color (hexadecimal de 3, 4, 6 u 8 cifras, o `rgb()`/`rgba()`
numérico). Vive junto a `isInteger`, `isEmail` y las demás.

## ⚠ CAMBIO INCOMPATIBLE — el texto del registro de actividad se escapa

Si tu clon guardaba HTML en el texto de una entrada del registro de actividad (con una plantilla propia o metiendo
marcado en el mensaje), ahora se verá como texto. En el framework no hay ninguna entrada que lleve marcado, así que
esto solo afecta a quien lo haya añadido.

## ⚠ CAMBIO INCOMPATIBLE — la función `escape_html()`

Si tu clon declara su propia función global `escape_html()`, renómbrala: el núcleo ya la declara y, si no, la
aplicación no arranca.

## Cambia

- La pantalla de colores **ya no acepta dejar vacíos** el color principal ni el fondo del menú: en blanco dejaban
  botones y pies de correo ilegibles.
- Se retira `app/classes/SystemApprovals/Views/mailing/template_base.php`, una copia huérfana de la plantilla de correo que nadie
  usaba y que se habría quedado atrás sin que nadie lo notara. La plantilla base de correo vive en el núcleo
  (`app/view/mailing/`); el cuerpo de cada correo sigue donde estaba, en su módulo.

# 8.0.6 (07-10-2026)

## ⚠ CAMBIO INCOMPATIBLE — la acción genérica de configuración solo guarda los colores de marca

`configurations-generic-save` responde **403** a cualquier opción que no sea uno de los nueve colores de marca
(`SettingsController::GENERIC_SAVE_ALLOWED`). Si tu clon guardaba otras opciones por esa acción, dale a cada una su
propia acción, con su permiso y su validación, como las de `SettingsController`.

## ⚠ Seguridad — los cambios de estado de un usuario

**Si tu instalación tiene usuarios sin privilegios de administración, actualiza.** Todo esto ya estaba en la `v7.1.0`.

- **El código de desbloqueo** solo desbloquea ahora al usuario bloqueado por intentos y le devuelve el estado que tenía;
  si su perfil fue rechazado, vuelve rechazado. A quien no está bloqueado no se le envía código, y la respuesta no lo
  delata.
- **Aprobar o rechazar una organización o un perfil** ya no reactiva a inactivos ni borrados ni toca un perfil que una
  persona rechazó; si el usuario está bloqueado, la resolución se conserva para cuando se desbloquee. La autoaprobación
  tampoco deshace ya el rechazo de un perfil.
- **Las aprobaciones exigen autoridad**: nadie resuelve su propia aprobación salvo el principal, hay que tener autoridad
  sobre el tipo del usuario, y el listado de aprobaciones solo enseña lo que se puede resolver, también al buscar o
  filtrar.
- **Los formularios de aprobación y su listado escapan** lo que escribe el usuario, y el contenido de una publicación se
  enseña aislado.
- **La dirección de vuelta** de la pantalla de acceso y la del formulario de contacto se escapan al pintar.
- **El alta del boletín y el formulario de contacto validan el correo**: el alta exige uno válido, el formulario solo
  suscribe con uno válido, y el listado y el formulario de edición escapan el nombre y el correo.
- **Los formularios de configuración ya no muestran contraseñas ni claves** (las claves de API de los servicios de IA,
  la contraseña del SMTP y la clave de osTicket): un campo que se deja vacío conserva el valor guardado. La
  configuración del correo y de osTicket pasa a ser solo del principal.
- **La acción genérica de configuración** solo acepta los colores de marca de su pantalla, valida que cada valor sea un
  color y rechaza cualquier otra opción; las opciones reservadas solo las escribe el principal por su propia pantalla.
  Revisa la tabla `pcsphp_app_config` y borra las filas que no reconozcas: la aplicación las sigue cargando.
- **Los colores de marca y el titular de la configuración SEO** se escapan en las plantillas de correo.

## Cambia — un usuario bloqueado por intentos

Un usuario bloqueado por intentos fallidos no puede iniciar una sesión nueva, pero **no pierde la que tenía abierta**:
el bloqueo protege la contraseña. Si antes de bloquearse estaba pendiente o rechazado, sigue con el panel recortado a lo suyo. Inactivos y
borrados sí pierden la sesión, como antes.

## Corregido

- La fecha de modificación de los usuarios se guardaba con el reloj de 12 horas: por la tarde, doce horas antes.
- En el formulario de aprobación de una organización, «Longitud» enseñaba la latitud.
- En los formularios de publicaciones, banners, noticias y documentos, un texto con `</textarea>` se salía del campo; y
  un `&lt;b&gt;` guardado entraba al editor como negrita.

## Cambia — el contenido enriquecido no se filtra: quien lo escribe es de confianza

El HTML del editor (el contenido de las publicaciones y de cualquier campo enriquecido) se guarda y se pinta **tal
cual**, a propósito: el editor permite «Insertar HTML» y las incrustaciones de medios. Quien tiene permiso para escribir
publicaciones puede, por tanto, poner cualquier HTML, también código que se ejecuta en quien la abre. **Da ese permiso
solo a quien confíes.** La única excepción es la vista de aprobación, que enseña el contenido aislado, sin ejecutar
nada, para proteger a quien aprueba algo que aún no ha revisado.

# 8.0.5 (06-10-2026)

## ⚠ Seguridad — editar y crear usuarios exige lo mismo que sus formularios

**Si tu instalación tiene usuarios sin privilegios de administración, actualiza.**

- **Editar un usuario** exige ahora, en la petición, lo mismo que su formulario: el permiso, que el tipo del usuario
  editado tenga rol, la autoridad sobre ese tipo, solo los estados que el formulario ofrece y, para quien no gestiona
  todas las organizaciones, solo usuarios de la suya y sin moverlos a otra. Uno mismo se edita como perfil y con su
  contraseña actual. El formulario de edición de otro usuario tampoco se abre si no se gestiona su organización. Ya
  estaba en la `v7.1.0`.
- **Crear usuarios desde el panel** exige lo mismo que su formulario: el permiso, la autoridad sobre el tipo, sus
  estados y, para quien no gestiona todas, su propia organización. El alta pública por la API sigue igual, con su
  propia política (usuario general o administrador de organización, pendiente de aprobación). Ya estaba en la `v7.1.0`.
- **Cambiar el avatar de otro usuario** queda para el propio usuario o quien puede gestionarlo. Ya estaba en la
  `v7.1.0`.
- **La autoaprobación de usuarios** solo pasa a activo a quien estaba pendiente de aprobación; los demás estados no se
  tocan. Ya estaba en la `v7.1.0`.

## ⚠ Seguridad — las publicaciones no públicas de otras organizaciones

- **La API de detalle de publicaciones** solo entrega lo que la vista pública o la vista previa dejan ver, y del autor
  solo su id y su nombre. Ya estaba en la `v7.1.0`.
- **Los borradores** se limitan a la organización de quien los ve, en el JSON de publicaciones y en la vista individual;
  sin organización, ninguno. Quien aprueba publicaciones de todas las organizaciones conserva la vista previa de lo que
  tiene pendiente de aprobar. Ya estaba en la `v7.1.0`.
- **Editar una publicación** exige lo mismo que abrir su formulario. Ya estaba en la `v7.1.0`.
- **Los archivos privados de una publicación no pública** se sirven solo a quien puede verla o editarla. Un archivo
  privado que no está en la carpeta de ninguna publicación solo lo recibe quien puede ver todas las publicaciones. Un
  token que la aplicación ya no acepta no cuenta como sesión para estos archivos. Los archivos privados llegaron
  después de la `v7.1.0`.
- **La API de detalle de noticias** solo entrega lo que el listado le daría a ese usuario. Ya estaba en la `v7.1.0`.

Lo activo y público no cambia para nadie.

## Cambia — un usuario pendiente de aprobación o rechazado entra con el panel recortado

Un usuario pendiente de aprobación o rechazado puede iniciar sesión, como antes, pero el panel solo le abre las rutas de
lo suyo: su perfil, su espacio, sus datos y las generales. Antes entraba con todas las de su tipo. Funciona con el módulo
de aprobaciones encendido o apagado. El principal no se recorta nunca, como ya hacía el módulo de aprobaciones. El
recorte es por rutas; inactivos, bloqueados y borrados siguen sin poder iniciar
sesión.

## Cambia — los usuarios de ejemplo

El `README` del repositorio público avisa de que los usuarios de ejemplo de `databases/piecesphp_data.sql` (entre ellos
`root`) traen contraseñas cuyo hash es público: hay que cambiarlas o borrarlos antes de exponer la instalación.

## ⚠ CAMBIO INCOMPATIBLE — el detalle de publicaciones y de noticias por la API

- `GET …/api/publications/publications/detail?id=N` responde **404** cuando la publicación no existe (antes, 200 con
  `{"publicationData": null}`) y cuando quien pregunta no puede verla. Y `author` pasa de
  ser el usuario entero a `{"id": …, "fullName": …}`: un consumidor que leyera otros campos del autor tiene que
  pedirlos por su lado, con su permiso.
- El detalle de noticias de la API hace lo mismo: **404** para la noticia que no existe (antes, 200 con
  `{"newsData": null}`) y para la que su listado no le daría a quien pregunta.

## ⚠ Seguridad — un usuario sin organización no ve las publicaciones de todas

Un usuario cuyo tipo exige organización y que no la tiene (un dato defectuoso: el alta y la edición lo impiden, pero
puede llegar de una base antigua) recibía un error 500 en todo el panel. Ahora entra al panel, el menú no le ofrece la entrada «Organización» y el listado de publicaciones le sale vacío. Quien
puede ver todas las publicaciones, y quien tiene organización, no notan nada.

## Cambia — una organización sin traducciones se lee completa

Una organización guardada sin `langData` en su `meta` ya se carga entera en lugar de quedarse en nada. Tiene dos
efectos sobre el acceso, los dos coherentes con la política declarada: su encargado puede editar y borrar las
publicaciones de los miembros de su organización, y los usuarios de una organización inactiva o borrada ya no pueden
iniciar sesión (antes, si a la organización le faltaba `langData`, entraban). Lo que guarda la aplicación siempre lleva
`langData`: esto solo toca filas escritas por otros medios.

## Corregido — los listados del panel con datos sin traducciones

Los listados del panel de noticias, categorías de noticias, organizaciones, y categorías y tipos de documento de los
formularios respondían error 500 si alguna fila no tenía sus datos de idioma (`langData`) en `meta`. Ahora se listan
con sus datos base. Editar una organización que no existe respondía 500 a todos, también al principal; ahora responde
403 o 404.

# 8.0.4 (06-10-2026)

## ⚠ Seguridad — las exportaciones de los informes de acceso se limitan a la organización de quien exporta

Las tres exportaciones de los informes de acceso (intentos de ingreso, usuarios con ingreso y usuarios sin ingreso)
traen ahora solo lo de la organización de quien exporta, con la misma regla que el listado en pantalla. El principal y el administrador general siguen viéndolo todo. Los intentos con un nombre de usuario que no
existe se siguen viendo, por decisión del propietario: conservan la fidelidad del registro. Ya estaba en la
`v7.1.0`. **Si tu instalación tiene administradores de organización, actualiza.**

## Corregido — un banner sin traducciones ya no tumba los listados

Un banner con sus datos de idioma incompletos hacía que respondieran error 500 el listado del panel, su JSON y el JSON
público de banners, que no pide sesión. Ahora se lista con sus datos base. Venía de la `v7.1.0`.

# 8.0.3 (06-10-2026)

## Corregido — aprobaciones que daban error 500

El formulario de una aprobación cuyo contenido no tiene quien lo gestione respondía 500, y ahora es un 404. El de un
usuario sin organización también daba 500: ahora se pinta con la organización como «N/A». Y en esa misma pantalla, la
«Longitud» mostraba la latitud. Venía de la `v7.1.0`.

## Corregido — la aprobación de una organización ya no escribe al mirarla

Abrir el formulario de aprobación de una organización sin encargado le asignaba el usuario 1 como encargado y lo
guardaba: una página que solo se mira modificaba datos. Ahora muestra que no tiene encargado y no escribe nada.

## Corregido — una publicación sin traducciones ya no tumba los listados

Una publicación con sus datos de idioma incompletos (por ejemplo, importada a mano) hacía que el listado de
publicaciones del panel respondiera error 500, y también la carga por AJAX del listado público. Ahora se lista con sus
datos base en los dos. Venía de la `v7.1.0`.

## Corregido — `permissions-and-property.sh` se puede ejecutar recién clonado

El guion de permisos y propiedad, y `src/permissions.sh`, no llevaban permiso de ejecución, y `./permissions-and-property.sh`
respondía «Permission denied» en un clon nuevo. Ahora lo llevan.

## Cambia — el repositorio público lleva su propio README

El `README.md` de la distribución hablaba del repositorio de desarrollo (cómo se empuja, herramientas que no viajan). Ahora
la distribución lleva uno escrito para quien clona: instalación desde `last-stable`, entorno, comprobaciones que sí
funcionan en un clon y la terminal.

## Corregido — «mi organización» con una organización que no existe

Quien puede editar cualquier organización (el principal o el administrador general) recibía un error 500 al abrir o
guardar el perfil de una organización que no existe, o al entrar sin indicar ninguna. Ahora, sin organización en la
dirección, abre la suya; y una que no existe responde 404. Venía de la `v7.1.0`.

## Corregido — una categoría de publicaciones incompleta ya no tumba el sitio público

El menú del sitio público recorre las categorías de publicaciones, y una con sus datos de idioma incompletos (por
ejemplo, a medio crear o importada a mano) hacía que **la portada respondiera error 500** a cualquier visitante, y con
ella las demás páginas públicas que pintan ese menú (las del área pública y las de publicaciones). Ahora esa
categoría se salta y las demás se pintan igual. Venía de la `v7.1.0`.

# 8.0.2 (06-10-2026)

## Corregido — solo los programas llevan permiso de ejecución

La `v8.0.1` traía 627 archivos marcados como ejecutables, y casi todos no lo eran: 201 `.php`, 188 `.js`, imágenes,
hojas de estilo, `.gitignore`, `.htaccess`. Ahora **solo** puede llevarlo un archivo que empiece por `#!` (en la
distribución, los guiones de `bin/` y los hooks de git; no todo lo que empieza por `#!` lo lleva), y la comprobación 9 de `bin/cli verify-integrity` falla si un archivo sin `#!` vuelve a
llevarlo. No cambia el contenido de ningún archivo.

## Cambia — `PUBLICAR.txt` lleva la ruta del repositorio en cada orden

Cada orden de la guía de publicación es `git -C <repositorio de distribución> …`: funciona igual desde cualquier
carpeta, y no puede commitear por error en otro repositorio.

# 8.0.1 (06-10-2026)

La primera versión que se publica en el repositorio público: la `v8.0.0` se etiquetó en el de desarrollo, y esta es
la que se puede clonar.

## Nuevo — el repositorio público se genera desde una etiqueta, y una comprobación lo vigila

`bin/make-distribution <etiqueta> [<destino>]` genera el repositorio público: el árbol de la etiqueta sin lo que solo
sirve para mantener el framework. **Si el destino es el repositorio de distribución que ya existe**, sustituye su
contenido versionado por el de la versión nueva sin tocar su historia ni commitear, y deja en su raíz `PUBLICAR.txt`
(que git ignora) con las órdenes para revisar, commitear, etiquetar, avanzar `last-stable` y subir. Si el destino no
existe, crea un repositorio nuevo de un solo commit. Sin destino, en una terminal, pregunta la ruta. Lleva
el andamiaje de agentes que sirve para desarrollar sobre PiecesPHP (las capas A y B de `.agents/capas.json`) y deja fuera
la historia de la campaña, los censos, el instrumental de PHPStan y sus resultados. La comprobación 47 de
`bin/cli verify-integrity` genera la última versión en un temporal y comprueba que cumple lo decidido.

**En un clon**, `bin/cli verify-integrity` y `bin/cli gates` dicen «[NO APLICA EN LA DISTRIBUCIÓN]» en lo que depende de
esas herramientas, y el resultado final cuenta cuántas comprobaciones no se hicieron. Los pasos después de clonar están
en la guía general, «Paso 6».

## Corregido — la documentación de `source-docs/`, auditada página por página

Las 63 páginas, contrastadas con el código antes de publicarlas. Se corrigieron, entre otras cosas:
- ejemplos que no funcionaban al copiarlos: clases que no existen (`App\Controller\AdminPanelController`,
  `App\Model\UsersModel`, `AppConfigModel`), métodos inexistentes (`QueueHandlerResponse::error()`) y un `clone`
  sobre un texto;
- las guías de entorno, reescritas para **Ubuntu 26.04 LTS**, que trae PHP 8.5 en sus repositorios: ya no hace
  falta el PPA de ondrej. Tomcat 11 con Java 21 para GeoServer 3, HestiaCP 1.10, Docker con `php:8.5-apache` y
  `mariadb:11.8`, Mautic 7 y phpMyAdmin 5.2.3;
- la API de usuarios, que nombraba mal los campos de `get-data-user` y no decía que solo se accede al propio usuario;
- la bandera `API_CRONJOBS`, que sí registra sus rutas sola.

# 8.0.0 (05-10-2026)

> **Versión estable.** Esta sección reúne las rupturas de la campaña desde la `v7.1.0`. La rama `last-stable` apunta
> siempre a la última estable. Antes, estas pre-versiones:

| Pre-versión | Fecha | Hasta |
| :-- | :-- | :-- |
| `v8.0.0-alpha.1` | 2026-09-16 | La ruptura 28 y la corrección del escape en los correos de los formularios públicos |
| `v8.0.0-alpha.2` | 2026-09-16 | La ruptura 29 y la corrección de la recuperación de contraseña (se podía tomar una cuenta) |
| `v8.0.0-alpha.3` | 2026-09-16 | La ruptura 30: `piecesphp/database` 5.0, el texto se guarda tal cual, y la tarea `repair-escaped-text` |
| `v8.0.0-alpha.4` | 2026-09-16 | Las pruebas de los envíos de correo y el escape del HTML en los correos de token, aprobación y alta por API |
| `v8.0.0-rc.1` | 2026-10-02 | La candidata: respaldos con su política, el campo opcional que ya no se traga datos, el registro que no miente al suplantar, el alias de ruta retirado y las cuatro suites que nunca se habían ejecutado |
| `v8.0.0-rc.2` | 2026-10-05 | La segunda candidata: el correo declarado y su registro con el cuerpo cifrado, `gates` solo en local, las URLs con sus números y la redirección a la verdadera, las tareas programadas sin choques, el número de registro sin recortar, y la auditoría de los posibles 500 —ninguno nuevo— |
| `v8.0.0-rc.3` | 2026-10-05 | La candidata de la estable: las pruebas de correo ya no borran correos ajenos de Mailpit ni dejan sus filas en el registro, y la comprobación de restos de prueba reconoce también la marca `zz_` |

## ⚠ CAMBIO INCOMPATIBLE — las URLs conservan sus números, y la forma vieja redirige a la verdadera

- **`friendlyURLString()` ya no se come los dígitos**: «Informe anual 2026» da `informe-anual-2026`, no `informe-anual`.
  Todo lo demás sigue igual —tildes fuera, `ñ` como `nn`, minúsculas, puntuación fuera, `maxWords`—, y un texto sin
  dígitos sale **idéntico** a antes.
- **⚠ Cambian las URLs de los títulos con números** de publicaciones, noticias, organizaciones y sus categorías. **La
  forma vieja no se rompe: responde 301 a la verdadera**, de un solo salto y conservando el idioma (`?i18n=`), en el
  detalle de una publicación y en el listado por categoría. Y cualquier otra forma del slug con el código bueno, también.
- **La redirección solo ocurre si la página se puede ver.** Un borrador o una publicación programada pedidos con el
  slug equivocado responden como siempre, **sin 301**.
- **Los adjuntos nuevos de Publications conservan los números en el nombre de su archivo.** Los ya subidos no cambian.
- **Para quien haya extendido esos controladores:** `singleView()` y `listView()` de `PublicationsPublicController`
  devuelven ahora un `Response`, como toda ruta debe.

## ⚠ CAMBIO INCOMPATIBLE — cada tarea programada tiene su propio estado, aunque los nombres se parezcan

- **El archivo de estado y el candado de una tarea se nombraban con `friendlyURLString()` de su nombre**, que pierde
  tildes, mayúsculas y puntuación: «Respaldo» y «RESPALDO», o «Exportación» y «Exportacion», **compartían estado y
  candado**: una podía saltarse su ejecución creyendo que ya se había hecho, en silencio. Ahora llevan además una
  huella del nombre exacto.
- **⚠ Cada tarea con franja se ejecuta una vez de más tras actualizar**, porque su archivo de estado cambia de nombre.
  **Los archivos viejos de `src/app/cache/cronjobs/` se pueden borrar.**

## Corregido — el número de registro de los listados ya no se recorta, y ordena de verdad

- **21 listados del panel calculaban el número que enseñan con `LPAD(id, 5, 0)`, que recorta en silencio**: desde el
  registro 100.000, el 123456 salía como `12345`, el número de **otro** registro. Y ordenaban por ese texto: «lo más
  nuevo primero» dejaba de serlo, y el orden no usaba el índice.
- **Ahora del 1 al 99.999 se ve igual que antes** (`00042`), **desde el 100.000 sale entero**, y se ordena por el número
  real, con índice. Lo mismo para la preferencia de orden de noticias y de aprobaciones.
- **Lo que cambia en el buscador de esos listados:** buscar el número **con sus ceros** («00042») ya no lo encuentra;
  **sin ellos** («42»), sí.
- **`bin/cli verify-integrity` lo vigila** (comprobación 46): ningún `LPAD` de ancho fijo y ningún número rellenado como
  columna de orden. Si su clon tenía listados copiados con la forma vieja, se lo dirá con su archivo.

## ⚠ CAMBIO INCOMPATIBLE — `bin/cli gates` solo corre en una instalación declarada `local`

- **`bin/cli gates`, y con él `bin/verify`, se niegan a correr fuera de una instalación declarada `local`**: sin
  `src/app/config/environment.php`, o con otro valor, salen con código 2 **sin correr ninguna suite**, y dicen por qué.
- **El motivo**: las suites escriben en la base de datos de la instalación donde se corren —crean y borran registros,
  cambian configuración—. Lanzadas por error en un servidor de producción, lo harían sobre los datos reales, y hasta
  ahora nada lo impedía.
- **No hay interruptor para saltárselo**, a propósito: un «forzar» es lo que alguien escribe con prisa en el servidor
  equivocado. En una instalación de desarrollo, declare `return 'local';` en `environment.php`.
- **Y lo dice también cuando pasa** («entorno: local»): una guarda que solo habla al fallar no se distingue de una que
  no corrió.

## ⚠ CAMBIO INCOMPATIBLE — el correo hay que declararlo: sin `environment.php`, se retiene

- **Una opción nueva, `mail_delivery`**, decide qué pasa con el correo: `real` (sale por el SMTP configurado), `sink`
  (no sale) o `auto`, que es la de por omisión y vale `sink` en el entorno `local` y `real` en `production`.
- **⚠ Lo que rompe: si `src/app/config/environment.php` NO EXISTE, `auto` vale `sink` y el correo NO SALE.** Antes, un
  clon sin ese archivo enviaba. **Si su despliegue de producción no tiene `environment.php`, créelo declarando
  `production`**, o elija `real` en el panel, en **Integraciones → Correo**. La ausencia de una declaración ya no puede
  significar la opción peligrosa.
- **Con `sink`, el correo va al sumidero SMTP local** (Mailpit) **y, si no hay ninguno escuchando, se guarda como `.eml`
  en `src/app/logs/mail-outbox/`**. El envío no cuenta como fallo: ningún correo se pierde ni se escapa.
- **⚠ Y con `sink` ya no se usa la reserva del sistema** (`mail()` o `sendmail`), que antes entraba si el SMTP no
  respondía. Un despliegue que dependiera de esa reserva **y** declare `sink` deja de entregar: con `sink`, el correo se
  retiene, que es lo que dice su nombre.
- **`bin/cli clean-logs` vacía también el buzón en disco** (`src/app/logs/mail-outbox/*.eml`) y dice cuántos borró.
- **Tres avisos nuevos** para el usuario principal, ninguno bloquea: `mail-sin-declarar` (no hay `environment.php`),
  `mail-retenido-en-produccion` (producción con `sink`) y `mail-real-en-local` (local con `real`).
- **Cada correo lleva la cabecera `X-Tags`** con el nombre de la instalación, que es la que Mailpit usa para agrupar: una
  bandeja compartida entre varios clones sigue siendo legible. **Desde la terminal**, donde la URL base es `localhost`,
  la etiqueta es **el nombre de la carpeta de la instalación**: dos clones de una misma máquina también se distinguen.

## ⚠ CAMBIO INCOMPATIBLE — el registro de correos: dos archivos SQL que aplicar

- **Cada envío deja una fila**: fecha, destinatarios, asunto, el archivo y la línea que lo originaron, la entrega
  declarada, y el resultado —**entregado**, **en el buzón** o **no llegó**— con su motivo.
- **Y desde el 2026-10-05, también el cuerpo del mensaje, cifrado** con una clave **derivada** de la de la aplicación,
  propia de este registro. **Los adjuntos no se guardan nunca**: ni su contenido ni una copia en el registro.
- **Si la clave de la aplicación es la de relleno y la instalación NO se declara `local`, el cuerpo no se guarda**: la
  fila sí, y queda su línea en el registro de errores. Con una clave pública, cifrar no protegería nada. **Toda
  instalación nace con la clave de relleno**: genere una con `bin/cli generate-app-key` y póngala en
  `src/app/config/config.php`, que es lo que ya pide el aviso `app-key-placeholder`. En una instalación `local` el
  cuerpo se guarda igual, porque sus datos son de prueba.
- **Aplique, en este orden, los dos archivos:** `databases/actualizaciones/2026-10-03-registro-de-correos.sql` (la tabla)
  y `databases/actualizaciones/2026-10-05-cuerpo-del-correo.sql` (la columna del cuerpo). Si falta el primero, el aviso
  `mail-log-sin-tabla` lo dice y **no se registra nada**; si falta solo el segundo, el aviso `mail-log-sin-cuerpo` lo dice
  y **las filas se guardan sin el cuerpo**: el registro no deja de funcionar por una migración pendiente.
- **La pantalla «Registro de correos»** es del usuario principal. **El cuerpo no viaja en el listado**: se pide fila a
  fila por su propia ruta, que es su propio permiso, y se enseña **dentro de un marco aislado y sin scripts**, porque el
  cuerpo puede contener texto de quien llenó un formulario. **Un clon puede dar el registro a otro rol sin darle los
  cuerpos.**
- **Qué se guarda del cuerpo lo decide su clon** en `src/app/config/extensions/mail-log.php`: por omisión, entero; el
  archivo trae comentados los dos ajustes habituales, no guardar ninguno o tachar un dato antes de guardarlo.
- **⚠ Si su clon pierde o rota su clave de aplicación, los cuerpos ya guardados no se pueden leer**, y la pantalla los
  marca como «ilegibles».
- **Los cuerpos viajan también en los respaldos de la base**, cifrados, durante lo que diga su política de respaldos:
  recortar el registro a 20.000 filas no los borra de un respaldo ya hecho.
- **Los mensajes retenidos en el buzón en disco (`.eml`) están completos y en claro**, adjuntos incluidos: son el
  correo que no salió, no un registro. Trátelos como tal.
- **Se conservan las últimas 20.000 filas.** Medido: con los correos del framework, una fila con su cuerpo cifrado ocupa
  alrededor de 2 KB, así que la tabla se queda en torno a 40 MB. **El tope se cambia** en `mail-log.php` con
  `MailLogMapper::setMaxRows()`, con un suelo de 1.000 —por debajo, una ráfaga normal borraría los fallos del día
  antes de que su aviso los contara—. Si su clon manda cuerpos mucho mayores, el transformer es la otra palanca.
- **El visor del cuerpo no carga nada de la red**: una imagen remota dentro de un correo no se pide, así que abrirlo no
  le avisa a nadie de que lo abrió. Ver un cuerpo **queda anotado** en el registro de actividad, sin su contenido.
- **El aviso `mail-con-fallos`** se enciende con el **primer** envío que no llegó en las últimas 24 horas, y no se puede
  ocultar: se apaga solo cuando pasa la ventana.
- **Lo que este registro NO ve:** los envíos de la cola de Mautic no pasan por el mismo camino y **no dejan fila**.

## Nuevo — `bin/cli mail-demo`: correos para mirar

- **Manda un correo de cada plantilla del framework** —recuperación de contraseña, código de acceso, aprobaciones,
  alta por la API, formulario de contacto…— a `demo-correos@localhost.test`, **por el envío real**, con datos de ejemplo.
  Los verá en Mailpit y en **Sistema → Registro de correos**, con su cuerpo.
- **Solo en una instalación `local` y con la entrega retenida**: si no, se niega **antes** de mandar nada, y dice por
  qué. No crea códigos, tokens ni aprobaciones: solo pinta y envía las plantillas.
- **No borra nada**: cada vez, otra tanda. Y como no lleva la marca de las pruebas, **no ensucia la verificación**.

## Nuevo — `bin/cli mail-doctor`

- **Dice en una pantalla cómo está el correo**: el entorno, la entrega declarada y la que se aplica de verdad, si el
  sumidero responde, cuántos mensajes hay en el buzón en disco, el resumen del registro y si se están guardando los
  cuerpos. **Es lo que conviene correr al clonar.**
- **Por omisión no toca la red**, y lo dice. Con **`smtp=yes`** abre una conexión al SMTP configurado y la corta **sin
  enviar nada**: sirve para enterarse de que el puerto o las credenciales están mal antes del primer correo de verdad.

## ⚠ CAMBIO INCOMPATIBLE — el «alias» de una ruta se retira, y con él el atajo `bin/cli h`

- Una ruta podía declarar un **alias**: una segunda dirección, más corta, al mismo controlador, que heredaba su
  permiso. En todo el framework **había uno**: `bin/cli h`, atajo de `bin/cli help`.
- **El mecanismo se retira.** `bin/cli h` responde «Acción no reconocida» y enseña la lista de acciones; **use
  `bin/cli help`**. Lo vigila una comprobación nueva de `bin/cli verify-integrity`: ninguna ruta puede declarar un
  alias.
- **Si su clon declaraba alguno**: su segunda dirección deja de responder, y la verificación de integridad se lo dirá
  por su archivo y su línea. Use el nombre de la ruta.
- **El sexto parámetro de `Route` NO desaparece**: queda **ignorado y documentado**, porque hay 221 declaraciones de
  rutas que cuentan posiciones y retirarlo desplazaría los roles permitidos en todas ellas, también en su clon.
- Por qué se retira: su dirección quedaba mal formada (se pegaba al prefijo del grupo), el catálogo guardaba para el
  alias la dirección de la ruta principal, y ninguno de nuestros instrumentos lo distinguía de su ruta. Con un solo
  uso real, arreglarlo valía menos que quitarlo.

## ⚠ Seguridad — el registro de acciones dice quién actuaba de verdad al conectarse como otro usuario

- **Hasta ahora**: cuando el usuario principal usaba «Conectar como otro usuario», el registro de acciones **atribuía
  al usuario suplantado** todo lo que hacía el principal. Y una acción **sin sesión** se guardaba como del usuario con
  id 1, o sea del principal. **Era un registro que mentía en los dos casos.**
- **Desde ahora**:
  - la columna `createdBy` **no cambia de significado** (sigue siendo el usuario que la aplicación tenía por
    conectado), y nada de lo ya guardado se reinterpreta;
  - en la columna `meta` se añade `actor`, con **quién actuaba** y **en nombre de quién**, cuando hay suplantación, o
    con la marca de que lo hizo el **sistema** cuando no había sesión;
  - el listado de registros enseña «**quien actuaba, en nombre de quien figura**» y «**Sistema**» en lugar del nombre
    del principal;
  - **se registra el principio y el fin de la suplantación**, y solo una vez: en la petición que la activa, no en cada
    una de las que vienen después con la cookie puesta.
- **No hay cambio de esquema**: `meta` ya existía y lo que ya guardaba (la IP y la geolocalización) se conserva.
- **Pendiente, y se dice para que nadie lo dé por hecho**: `createdBy` sigue guardando `1` en las acciones sin sesión,
  porque la columna no acepta nulo y tiene clave ajena. Lo que ya no ocurre es que la pantalla lo presente como el
  usuario principal.

## Corregido — una página pública con un dato incompleto ya no responde error 500

- Las dos vistas del mapa público de personas usaban el resultado de `UserProfileMapper::objectToMapper()` sin
  comprobarlo, y ese método **devuelve `null` si la fila del perfil llega incompleta** (le basta que falte una
  columna). Con un perfil así, la página pública entera respondía **500**.
- Ahora el elemento **se omite**: no sale su tarjeta ni su punto en el mapa, y la página se sirve. Nunca se inventa un
  dato para rellenar.
- Lo cubre la suite `core/public-map-elements`, cuyo caso de referencia sale de una fila real de la base.
- De regalo, por si usa `objectToMapper()` en sus propias vistas: **compruebe el `null`**. Si su consulta no trae la
  fila completa, cualquier uso directo del resultado está a un error 500 de distancia.

## Corregido — una tarea de la cola cuyo proceso muere ya no se queda «en curso» para siempre

- Si el proceso que estaba ejecutando una tarea de la cola se caía (una máquina reiniciada, un `kill`, un tiempo de
  ejecución agotado), **la fila se quedaba en `running` indefinidamente**: nadie la terminaba y nadie la reintentaba.
  El turno de la cola ya se liberaba solo; lo que no se recuperaba era la tarea.
- Ahora, **al empezar cada pasada y antes de tomar nada nuevo**, el worker recupera lo abandonado: las tareas que
  llevan más de **30 minutos** en `running` desde que empezaron vuelven a `pending` si les quedan reintentos —y
  **recuperar no gasta un reintento**— o pasan a `failed` con un error que dice que su proceso murió. Una tarea que
  empezó hace poco **no se toca**.
- **Si alguna de sus tareas puede tardar más de 30 minutos**, pártala: con ese umbral, una tarea viva de 40 minutos se
  consideraría abandonada y se encolaría otra vez.
- De paso, la documentación de colas decía que el estado en ejecución era `processing`; el valor real siempre fue
  **`running`**.

## ⚠ CAMBIO INCOMPATIBLE — una sesión caducada deja una línea sin el token, y solo si se enciende

- **Hasta ahora**: cada petición con el token caducado escribía **un archivo** en `src/app/logs/expired-sessions/`
  con el **JWT entero** dentro, además de su `aud`, su `data` (el contenido de la sesión), la IP, la ruta y la URL. Y
  en cada una de esas peticiones se recorría la carpeta completa para borrar lo de más de 30 días.
- **Desde ahora**:
  - **no se escribe nada por omisión**. Hay una opción nueva, `log_expired_sessions`, **falsa** de serie: enciéndala
    solo mientras depure una renovación de sesión (y hace falta el booleano `true`; una cadena `"1"` no la enciende);
  - encendida, escribe **una línea** en `src/app/logs/expired-sessions.log` con fecha, usuario (o `anónimo`), ruta,
    URL, IP, `iat`, `exp` y si la ruta era candidata a renovación. **El token no se escribe nunca**, ni entero ni
    troceado, y la URL se sanea de cualquier cosa con forma de JWT;
  - el registro **rota por tamaño** (1 MB): pasa a `expired-sessions.log.1` y nunca hay más de dos archivos.
- **Medido**: el bloque viejo costaba **1,78 ms por petición** con 574 archivos en la carpeta, y crecía con el
  historial; el nuevo, 0,005 ms, y 0 operaciones de disco con la opción apagada. En esa instalación, además, la
  limpieza de 30 días **no borraba nada**: solo recorría.
- **⚠ `bin/cli clean-logs` ya NO borra los `.json` viejos**: los cuenta y avisa de que llevan un token. Si usaba esa
  tarea para vaciarlos, hágalo a mano. La carpeta se retira sola cuando queda vacía.
- **Qué revisar en un clon**: si alguna herramienta propia leía esos `.json`, cambia de formato y de ruta, y por
  omisión no se escribe nada. **Y conviene vaciar la carpeta vieja**: cada archivo contiene una credencial.

## ⚠ CAMBIO INCOMPATIBLE — un campo opcional mal rellenado ya no se guarda como si estuviera vacío

- **Hasta ahora**: un parámetro **opcional** con un valor que su validación rechazaba se sustituía **en silencio** por
  su valor por omisión, y la operación seguía adelante diciendo «Datos guardados». Quien lo envió no se enteraba de que
  su dato se había perdido. Pedir un listado con `page=abc` devolvía la primera página, como si eso se hubiera pedido.
- **Desde ahora**:
  - **vacío o ausente → el valor por omisión**, sin error, igual que siempre (un campo de formulario en blanco sigue
    funcionando como antes);
  - **presente y con un valor que la validación rechaza → error**: `InvalidParameterValueException`, que el framework
    convierte en **400 `INVALID_PARAMETER_VALUE`** con un mensaje que **nombra el campo**. Pedir un listado con
    `page=abc` pasa de responder la página 1 a responder 400.
- **Qué revisar en un clon**: cualquier cliente propio (un JavaScript, una integración, un informe) que hoy mande un
  valor mal formado en un campo opcional **empezará a recibir 400 en vez de ser ignorado**. En el framework no hay
  ninguno: se censaron los **223 parámetros opcionales** y se ejercitaron con 95 baterías de pruebas, el recorrido
  completo de rutas y seis formularios del panel enviados de verdad, sin una sola caída silenciosa.
- También importa para quien pase opciones al helper de tablas (`DataTablesHelper`, 25 parámetros opcionales): una
  opción mal escrita pasa de ignorarse a dar error. Es lo deseable, y es ruptura.

## Corregido — un grupo de rutas con un prefijo ya usado dejaba de registrarse

- `RouteGroupAdapter::register()` guardaba los grupos **por prefijo**: un segundo grupo con el mismo prefijo **no se
  registraba y sus rutas desaparecían sin error**. Ahora cada grupo se guarda por instancia y conviven todos, cada uno
  con sus middlewares.
- **En el framework no cambia ninguna ruta** (341 cotejables antes y después): no se perdía ninguna porque
  `config/routes.php` ya esquivaba el caso.
- **⚠ Para un clon que registre dos grupos con el mismo prefijo, el fallo cambia de forma**: antes le faltaban rutas
  en silencio; ahora el segundo grupo se registra **al final**, y si sus rutas son estáticas y el grupo anterior acaba
  en una ruta comodín, **FastRoute rechaza el conjunto y la aplicación no arranca**
  («Static route … is shadowed by previously defined variable route …»). Es el fallo que queremos —alto y en el acto—,
  pero conviene saberlo: **el orden en que se registran los grupos manda**, y las rutas estáticas van antes que
  cualquier comodín.

## Corregido — el inventario de rutas duplicaba una ruta y se dejaba otra fuera

- `bin/cli route-inventory` leía el **campo** `name` de cada entrada del catálogo en vez de su **clave**. Como una
  ruta con alias se guarda dos veces (con su nombre y con su alias) y las dos entradas llevan dentro el nombre
  principal, el inventario listaba `terminal-help` **dos veces** y **no listaba `terminal-h`**, que es una ruta real.
  El total cuadraba por casualidad.
- Lo consumían siete piezas de verificación (dos comprobaciones de integridad, los tres recorredores, el censo de
  consumidores y una suite), todas con el universo corto en una ruta y con una repetida.
- Lo vigila la suite nueva `core/route-inventory`, que coteja el inventario contra el router de verdad.

## ⚠ CAMBIO INCOMPATIBLE — los respaldos de la base se gobiernan con una política, y los que sobran se borran

**Al actualizar, el primer respaldo correcto borra los respaldos que sobren de la política por omisión.** Si quiere
conservarlos todos, entre en **Configuración → Sistema → Respaldos** y desmarque «Borrar los respaldos que sobran»
antes de que corra el primer respaldo, o copie `src/dumps/` fuera del proyecto. Solo se borran los archivos que escribió
el propio framework, reconocidos por su nombre (`dd-mm-aaaa_hh-mm-ss-AM|PM.sql` o `.sql.gz`, en la raíz de `dumps/`):
un volcado hecho a mano, con otro nombre o en una subcarpeta, **no se toca nunca**.

- **Hasta ahora**: un respaldo al día, a las 00:00, y **ninguno se borraba**. En la máquina de desarrollo del
  framework se habían acumulado 682 archivos.
- **Desde ahora**: la pantalla **Respaldos** (grupo «Sistema» de la configuración, **solo el usuario principal**)
  gobierna:
  - **Cada cuánto se respalda**: un intervalo en minutos, 1440 (un día) por omisión, de 60 a 10080. Se cuenta **desde
    el último respaldo**, no a una hora fija: si una pasada se pierde, la siguiente la recupera.
  - **Cuántos se guardan**: los 24 más recientes, más uno por día de los últimos 30 días con respaldo, uno por semana
    de las últimas 12 y uno por mes de los últimos 24. Son unos 90 archivos como mucho, con dos años de historia.
    Cuentan los periodos **que tienen respaldo**, así que una instalación parada no pierde su historia.
  - **Tablas que salen sin sus filas**: su estructura entra en el respaldo y sus datos no. Es la forma de que datos
    sensibles no salgan del servidor. **Lo excluido no se puede recuperar desde ese respaldo.**
  - **Si respaldar está activo o no.**
- **Tras un respaldo fallido no se borra nada**: un fallo repetido no puede llevarse los respaldos buenos. Y el que se
  acaba de escribir se conserva siempre.
- **Un aviso del sistema** («Avisos del sistema», para el usuario principal) salta si el último respaldo tiene más del
  doble del intervalo.
- **Nuevo en el terminal**: `bin/cli db-backup-rotate` enseña qué se conservaría y qué se borraría **sin borrar nada**;
  con `apply=yes`, borra.
- **Para quien programa sobre el framework**:
  - `PiecesPHP\Core\Backups\BackupPolicy::excludeDataOf('tabla', 'motivo')`, desde
    `app/config/extensions/`, deja un módulo o un clon declarando en código tablas que salen sin filas. La pantalla las
    enseña con su motivo y no se pueden desmarcar desde ahí.
  - Eventos nuevos en el contexto `Backups`: **`BackupCreated`** (archivo, tamaño y tablas) y **`BackupFailed`**
    (motivo). Escuche el primero para llevarse la copia fuera del servidor.
  - `DbBackupTask::EXCLUDED_TABLES` (tablas que no entran **en absoluto**) sigue como estaba.
- **El cronjob «Respaldar base de datos» ya no dice `dailyAt("00:00")`**: pregunta a la política. Si su `crontab`
  ejecuta el ejecutor de tareas cada minuto (ver la documentación de cronjobs), no tiene que cambiar nada.

## ⚠ CAMBIO INCOMPATIBLE — `app/config` dice de quién es cada cosa, y `final-configurations-includes` pasa a `extensions`

- **Cada archivo de `src/app/config/` declara de quién es**: `@pcsphp-config framework` (no lo edites: una
  actualización lo sobrescribe), `clon` (es tuyo) o `ambos` (con marcas «Del framework: no lo edites» / «Del clon»), y
  una línea «Qué conviene editar aquí». Lo exige la comprobación 41 de `bin/cli verify-integrity`: **un archivo nuevo en
  `app/config` sin esa cabecera hace fallar la verificación.**
- **`src/app/config/final-configurations-includes/` pasa a llamarse `src/app/config/extensions/` y es solo tuya.** Lo
  que el framework metía ahí —el cronjob de respaldo y los demás del sistema, las traducciones dinámicas, el correo, el
  parche de dependencias, los archivos protegidos de sus módulos, la acción `loop-sample`— vive ahora en
  `src/app/core/extensions/`, que se carga antes.
  - **Si tu clon tiene archivos en la carpeta vieja, se siguen cargando**, y «Avisos del sistema» te pide moverla. Muévelos
    a `extensions/` y bórrala. **Ojo con no duplicar** lo que ahora trae el framework en `core/extensions/`.
  - Para tus claves y configuraciones por defecto hay dos ayudantes: `set_configs_from_secure_keys()` y
    `set_configs_if_empty()` (los archivos `extensions/api-keys.php` y `extensions/set-additional-configurations.php`
    traen el ejemplo).
- **Cronjobs del sistema y del desarrollador, separados**, y uno nuevo del sistema: **«Procesar la cola», cada minuto**.
  **Si tenías otra línea de `crontab` para `process-queue`, quítala**: basta la de `run-cronjobs`.
- **La cola ya no se atasca si un proceso muere a mitad**: el turno se toma con `flock`, que el sistema suelta solo.
  Antes, un `kill -9` dejaba un archivo de bloqueo que hacía abortar a todas las ejecuciones siguientes.
- Ningún comportamiento cambia: lo que se registra (cronjobs, colas, oyentes, acciones, archivos protegidos) es lo
  mismo antes y después, más el cronjob de la cola.

## ⚠ CAMBIO INCOMPATIBLE — dónde están ahora las vistas, los scripts y las clases que se movieron

El censo de lo borrado y movido en la campaña (`bin/censo-borrados`, `files/dev/campaign-removals.json`: 402 archivos
borrados y 148 movidos desde el 2026-08-20 hasta el 2026-10-01, contados como los dejó el commit que los sacó) encontró **113 traslados que este
registro no contaba**: estaban explicados
en sus commits, pero no aquí. **Si tu clon nombra alguna de estas rutas viejas —una vista con `render()`, un `<script>`,
un `@import`, un `use` o una carpeta en tu `gulpfile.js`— deja de funcionar sin más aviso.** Busca la ruta vieja y
cámbiala por la nueva:

| Antes | Ahora |
| :-- | :-- |
| `src/app/classes/App/Locations/` (y el espacio de nombres `App\Locations\`) | `src/app/classes/PiecesPHP/App/Locations/` (`PiecesPHP\App\Locations\`) |
| `src/app/classes/Components/` | `src/app/classes/PiecesPHP/Components/` |
| `src/app/classes/FileManager/` | `src/app/classes/PiecesPHP/FileManager/` |
| `src/app/controller/AppConfigController.php` | `src/app/classes/PiecesPHP/Settings/Controllers/` |
| `src/app/controller/{AvatarController,RecoveryPasswordController}.php` | `src/app/classes/PiecesPHP/UserSystem/Controllers/` |
| `src/app/view/panel/pages/app_configurations/` | `src/app/classes/PiecesPHP/Settings/Views/panel/pages/app_configurations/` |
| `src/app/view/panel/pages/{about-framework,dashboard,test-cropper}.php` | `src/app/classes/PiecesPHP/AdminPanel/Views/panel/pages/` |
| `src/app/view/layout/{header,footer}-for-token.php` y `src/app/view/panel/pages/generic_token/` | `src/app/classes/PiecesPHP/Tokens/Views/` |
| `src/app/view/usuarios/` (formularios por tipo, acceso, problemas de acceso, correos, `utils/`) | `src/app/classes/PiecesPHP/UserSystem/Views/usuarios/` |
| `src/app/view/panel/pages/list-usuarios.php` y `src/app/view/panel/pages/login-reports/` | `src/app/classes/PiecesPHP/UserSystem/Views/panel/pages/` |
| `src/statics/login-and-recovery/js/` (acceso y problemas de acceso) y `src/statics/admin-area/js/users-forms.js` | `src/app/classes/PiecesPHP/UserSystem/Statics/js/` |
| `src/statics/login-and-recovery/images/problems/forms/` | `src/app/classes/PiecesPHP/UserSystem/Statics/images/problems/forms/` |
| `src/statics/admin-area/sass/users-list.scss` | `src/app/classes/PiecesPHP/UserSystem/Statics/sass/` (la carpeta `admin-area/sass` ya no existe; el `gulpfile.js` del framework ya no la nombra) |

Todos son traslados: ninguno se borró sin sustituto. El detalle, archivo por archivo, está en
`files/dev/campaign-removals.json`.

## Eliminado — Adminer deja de venir con el framework

- **`src/adminer/` se retira** (decisión del PO): un cliente de base de datos dentro de la aplicación es superficie de
  ataque, y ya se borraba en cada despliegue. Si lo usabas en local, instálalo aparte
  (`source-docs/entornos/docs/environments/content/lamp/content/Adminer.md`).

## Cambia — SASS compila con la API moderna, sin reescribir lo que no cambió, y las tareas de gulp fallan de verdad

- **`src/gulpfile.js` compila SASS con un adaptador propio** sobre el `sass` instalado y su API moderna; deja de usar
  `gulp-sass`, que llamaba a la API antigua (57 avisos `legacy-js-api` por compilación). **Compilar ya no da ningún
  aviso.** El CSS generado es idéntico byte a byte; los mapas de fuente, iguales salvo el orden de sus claves.
- **Un `.css` o un `.map` cuyo contenido no cambió no se reescribe**: su fecha no cambia, así que su versión tampoco
  (ADR 0034). Un `sass-all` sin cambios ya no obliga a los visitantes a volver a descargar las 57 hojas.
- **Un error de Sass o de TypeScript hace fallar la tarea**, con el archivo y la línea. Antes, `ts-vendor` y
  `js-vendor` decían «Finished» antes de terminar, y un error de TypeScript reventaba el proceso después; y un error
  de Sass cortaba la compilación de las demás hojas del grupo. Ahora se compilan las demás y la tarea falla al final.
- **`gulp-sass` y `gulp-replace` salen de `package.json`**: ya no los usaba nada. En un clon con su propio
  `package.json`, quítalos con `npm uninstall gulp-sass gulp-replace`.

## Cambia — las hojas SASS del framework usan módulos (`@use`), no `@import`

- **Dart Sass va a retirar `@import`.** Las 61 hojas del framework que lo usaban pasan a `@use` y `@forward`: ya no queda
  ningún `@import` de parciales y **compilar no da ningún aviso de Sass**. El CSS generado es **idéntico byte a byte**
  (comprobado en las 57 hojas tras cada paso).
- **`$fontSizeHTML` del panel vive ahora en `statics/core/sass/includes/_font-size-base.scss`**: `_variables.scss` usa
  `pxToRem()` y `pxToRem()` lee `$fontSizeHTML`; juntas harían un ciclo de módulos. **Si la cambiabas en `_variables.scss`,
  cámbiala ahí.** (En lo público sigue en `statics/sass/imports/_variables.scss`.)
- **`_reset-font-family-fomantic` recibe la tipografía por configuración**: `@use '…/reset-font-family-fomantic' with
  ($fontGlobal: …);`. Un `@import` antiguo de ese parcial desde tu clon sigue dando el mismo CSS.
- **Si tus hojas propias hacen `@import` de parciales del framework, siguen compilando** (con el aviso de Sass). Para
  pasarlas a `@use`: `@use '<ruta>' as *;` para variables, funciones y mixins, sin tocar ninguna referencia.

## Cambia — la compilación de SASS, sin avisos muertos ni marca de compilación

- **Compilar deja de imprimir 57 veces** «mixed-decls deprecation is obsolete»: el silenciador de esa obsolescencia,
  que ya no existe en Dart Sass 1.99, era el que avisaba.
- **`gulp` ya no sustituye `CACHESTAMP` en las hojas compiladas.** El framework lo usaba solo en los `@import url(…)` de
  las fuentes; ahora van sin marca (se revalidan, ADR 0034) y **dos compilaciones seguidas dan exactamente el mismo
  CSS**. **Si una hoja de tu clon usa `CACHESTAMP`, ya no se sustituye**: quítalo.
- Las funciones globales de Sass que Dart Sass va a retirar (`unquote()`, `transparentize()`) pasan a sus módulos
  (`string.unquote()`, `color.adjust()`), y salen 15 `@import` que nadie usaba. El CSS generado es idéntico.
- `gulp-replace` queda sin uso en `package.json`.

## Cambia — «Estado y cachés» dice lo que no pudo borrar, y el sitio público deja de anunciar el framework

- **Los mensajes de «Estado y cachés»** hablan como su pantalla («Imágenes optimizadas borradas…»), sin rutas internas
  ni términos técnicos; la lista de accesos rotos borrados pasa al registro de acciones y la pantalla enseña los
  recuentos.
- **Lo que no se puede borrar se cuenta y se dice.** Antes, un acceso directo de `server-delegated` sin permiso de
  escritura **tumbaba la petición entera** (el aviso de PHP es fatal en esta aplicación). Ahora se salta, se cuenta
  («%d no se pudieron borrar por falta de permiso») y queda en el registro. `ServerDelegatedLinks::deleteAll()` y
  `deleteBroken()` devuelven además `failed`.
- **`humans.txt` ya no dice «Software: PiecesPHP»**, y la cabecera `X-Served-By` sale solo en local (y solo en la redirección que
  hace PHP; lo que sirve Apache ya no la lleva en ningún caso): eran una huella gratis para quien busca fallos conocidos del framework. Para que Apache y PHP no digan su
  versión, mira la guía de LAMP (`ServerTokens Prod`, `ServerSignature Off`, `expose_php = Off`).

## Cambia — tres pantallas de configuración hablan como quien las usa

- **«Archivos para buscadores», «Scripts» y «Estado y cachés» cambian sus textos**: títulos en lenguaje de quien usa la
  pantalla, el nombre técnico en pequeño, y la configuración fija plegada. Ninguna acción, ruta ni atributo cambia.
- **Si tu clon traducía o sobrescribía esas claves de idioma**, las viejas ya no existen (35 claves en inglés
  sustituidas).

## Cambia — los estáticos versionados se guardan un año, y compilar ya no invalida todo

- **Un estático con `cacheStamp` en su dirección recibe `Cache-Control: public, max-age=31536000, immutable`**: el
  navegador lo guarda un año sin volver a preguntar. **Sin `cacheStamp`, `no-cache`**: el navegador pregunta en cada
  visita y recibe un 304 si no cambió. La regla es la misma para lo que sirve Apache (`src/.htaccess`) y lo que sirve
  PHP (`ServerStatics`); lo que se sirve tras validar el acceso nunca es `public`.
- **Antes**, Apache no mandaba ninguna cabecera de caché para `statics/`, y `statics/server-delegated/.htaccess`
  pedía un año para todo lo suyo, **llevara marca o no** (con `mod_expires` activo en el servidor). Ese bloque queda
  desactivado: una URL sin marca ya no puede quedarse un año en el navegador de un visitante.
- **Compilar ya no renueva la marca global.** Las trece tareas de compilación de `src/gulpfile.js` ejecutaban
  `bin/cli clean-cache` al terminar: en un despliegue, cada compilación obligaba a todos los visitantes a descargar
  todo. Ahora lo compilado cambia de versión solo. Para forzarlo todo: `gulp clean-cache`, `bin/cli clean-cache` o el
  botón de «Estado y cachés».
- **La pantalla de acceso y las de problemas de acceso marcan su favicon.**
- **Si en un servidor tenías reglas de caché propias para `statics/`**, revisa que no den caché larga a URL sin
  `cacheStamp`.
- Pruebas: `core/static-cache` (por HTTP; declara red) y `core/server-statics`.

## Corregido — cada estático lleva la versión de su propio archivo, y las imágenes se marcan por atributo

- **Antes había una sola marca (`cacheStamp`) para todos los estáticos**: cambiar un archivo obligaba a cada visitante
  a volver a descargarlos todos. **Ahora la versión sale del propio archivo** —su fecha de modificación, combinada con
  la marca global—: cambia solo lo que cambió, sin botón. La marca global sigue siendo el «forzar todo»: «Renovar marca
  de estáticos» en «Estado y cachés» cambia todas las versiones. Una URL que no es un archivo local lleva la marca
  global, como antes. Función nueva: `static_file_version(string $url)`; `add_cache_stamp_to_url()` y los `load_*`
  la usan (ADR 0034).
- **Dos defectos de la marca en las imágenes, confirmados y corregidos** en `BaseController::render()`:
  - dos imágenes cuyo nombre empieza igual (`a.png` y `a.png.webp`) dejaban rota la segunda
    (`a.png?cacheStamp=….webp`);
  - una imagen con `&amp;` en su dirección se quedaba sin marca.

  La marca se pone ahora **atributo por atributo**, con el valor tal como está escrito, y cubre también `srcset` (cada
  candidato), `<source>`, `poster` y `url(…)` en un `style` en línea. Lo de dentro de `<script>` no se toca.
- **Las páginas 403, 404 y 503 marcan sus hojas de estilo.**
- Prueba: `core/static-versions` (declara red por la 404).

## CAMBIO INCOMPATIBLE — el sitemap lo sirve la aplicación, y cada módulo aporta sus URL

- **Desaparecen el botón «Actualizar sitemap», su ruta `configurations-appearance-seo-sitemap` y `src/sitemap.xml`.**
  El archivo versionado era un `urlset` vacío: el repositorio anunciaba a los buscadores un sitemap sin nada. Y el
  botón, que este mismo ciclo había arreglado, dependía de que el servidor web pudiera escribir en `src/`.
- **`/sitemap.xml` lo sirve la ruta `site-files-sitemap`**, sin sesión. Se calcula al pedirlo si no hay una copia de
  menos de una hora en `app/cache/sitemap.xml`, y se guarda ahí. Si la caché no se puede escribir, se sirve igual.
- **Cada URL es exactamente la canónica de su página**, una por idioma en que la página existe (con `lang_by_cookie`,
  con `?i18n=`). La calcula `MetaTags::canonicalFor($url, $lang)`, la misma función que la cabecera.
- **Cada módulo aporta sus URL** registrando un proveedor desde sus rutas; el de publicaciones lo trae el framework
  (publicaciones visibles al público, el listado y sus categorías) y el del núcleo, la portada y las páginas públicas
  sin parámetros:

```php
use PiecesPHP\Core\Sitemap\Sitemap;

Sitemap::registerProvider('mi-modulo', [MiModuloPublicController::class, 'sitemapItems']);
// sitemapItems(): SitemapItem[]; cada URL, MetaTags::canonicalFor($url, $lang)
```

  Un nombre repetido **falla al registrarse**. Un proveedor que lanza no tumba el sitemap: se registra el error y
  salen los demás.
- **Si tu clon enlazaba el botón o llamaba a `SettingsController::createSitemap()`**, ya no existen.
- `SitemapItem` escapa `<loc>`: una URL con `&` ya no rompe el XML.
- **Los extractos de las publicaciones cortan por caracteres**, no por bytes: antes podían partir una tilde.
- Prueba: `core/sitemap` (por HTTP; declara red).

## CAMBIO INCOMPATIBLE — `robots.txt`, `humans.txt` y `llms.txt` los sirve la aplicación

- **`src/robots.txt` desaparece del repositorio.** Lo sirve la ruta `site-files-robots` con una base del framework
  más lo que añadas en **Configuración → Apariencia → Archivos para buscadores** (mismos roles que «Identidad y SEO»).
  **Si editabas `src/robots.txt` a mano, pasa tus líneas a esa pantalla.** La base ya **no cierra `/statics/`** —le
  impedía al buscador ver el CSS, el JS y las imágenes subidas— y termina con la línea `Sitemap:` absoluta.
- **Nuevos:** `humans.txt` (formato de humanstxt.org, con el propietario y lo que escribas) y `llms.txt` (formato de
  llmstxt.org, en Markdown: nombre, descripción y secciones públicas del sitio, más lo que añadas).
- **Solo cuentan en la raíz del dominio**: una instalación en una subcarpeta los sirve en `/subcarpeta/robots.txt`,
  donde los buscadores no miran.
- Una línea añadida a `robots.txt` tiene que estar vacía, ser un comentario o `Campo: valor` con `User-agent`,
  `Allow`, `Disallow`, `Sitemap` o `Crawl-delay`; si no, no se guarda.
- Prueba: `core/site-files` (por HTTP; declara red).

## Seguridad — la vista pública de publicaciones escapa lo que escribe el editor

- El título, el autor, la imagen, los adjuntos y el título de la sección se escapan ahora al pintarse en
  `Publications/Views/publications/public/`. **El contenido de la publicación sigue saliendo como HTML**, a propósito: es lo que
  escribe el editor enriquecido.
- Si tu clon copió esas vistas, revisa las suyas.

## Cambia — la cabecera de toda página pública: metadatos completos y datos estructurados

- **Se corrigen dos valores que mentían:** `og:locale` valía `es_CO` en cualquier idioma y `og:type` valía `website`
  también en una publicación. Ahora `og:locale` sale del idioma de la página por `$config['og_locales']`
  (`src/app/config/config.php`; de fábrica `es` → `es_CO`, `en` → `en_US`) y la publicación declara `article`.
- **Nuevo en la cabecera:** enlace canónico; alternativas por idioma (`hreflang`, solo de los idiomas en que la página
  existe) y `x-default`; X/Twitter (`twitter:card`, título, descripción, imagen y cuenta); dimensiones y texto
  alternativo de la imagen para compartir; y un bloque JSON-LD con `WebSite`, `Organization` y, en cada publicación,
  `Article`. Todo sale por los métodos de siempre (`MetaTags::getMetaTagsGeneric()`, `getMetaTagsOpenGraph()`) y por
  `StructuredData::get()` en los dos layouts públicos: **un layout de clon recibe los metadatos sin tocarlo; el JSON-LD,
  añadiendo esa llamada.**
- **La canónica lleva el idioma cuando el idioma viaja en la consulta** (`lang_by_cookie`, lo de fábrica):
  `…/?i18n=es`. Sin `lang_by_cookie`, sin consulta. **Una página cuyo contenido dependa de otra consulta tiene que fijar
  su canónica con `MetaTags::setURL()`** (ADR 0037).
- **`noindex, nofollow`** en el panel, las pantallas de token y las de acceso y problemas de acceso; no emiten canónica
  ni alternativas.
- **«Identidad y SEO» gana tres campos:** título y descripción para compartir (por idioma) y la cuenta de X. El título
  para compartir es el de la tarjeta de la portada y de las páginas sin título propio; un clon con portada propia llama
  a `MetaTags::setShareTitle()` desde su controlador.
- **Métodos nuevos, ninguna firma cambiada:** `MetaTags::setRobots()`, `setShareTitle()`, `setImageAlt()`; la clase
  `StructuredData` (`add()`, `get()`, `clear()`).
- **Si comparabas el HTML de tus páginas**, la cabecera cambia: hay etiquetas nuevas.
- **Pruebas:** `core/meta-tags` y `core/seo-metadata` (por HTTP; declara red). Guía: `source-docs/project/docs/piecesphp/new-features/seo-y-scripts.md`.

## CAMBIO INCOMPATIBLE — los scripts inyectados tienen su pantalla, con zona y punto de la página

- **El campo «Scripts adicionales» sale de «Identidad y SEO»** y pasa a **Configuración → Integraciones → Scripts**
  (ruta `configurations-integrations-scripts`, y `configurations-integrations-scripts-save` para guardar). **Solo el
  usuario principal**: un script en el panel puede leer la sesión de cualquier usuario. El campo de SEO lo podía
  editar quien abriera SEO.
- **Cada script dice dónde va:** zona (panel, sitio público o ambos) y punto (cabecera, principio del cuerpo o final
  del cuerpo), y se puede desactivar sin borrarlo. **Ya no van por idioma.** Se guardan en la opción
  `injected_scripts`.
- **Solo los cinco layouts los imprimen**: `view/layout/`, `view/webflow/layout/` y el de tokens como sitio público;
  los dos del panel como panel. **La pantalla de acceso, las de problemas de acceso y las páginas 403, 404 y 503 no
  reciben scripts**, como hasta hoy: en el acceso, un script de terceros leería la contraseña (ADR 0033).
- **Qué tienes que hacer en un clon:**
  1. **Migrar:** `bin/cli settings-migrate-extra-scripts`. Pasa el `extra_scripts` del idioma por omisión a una
     entrada activa de sitio público y cabecera; el de otro idioma, si es distinto, a una entrada **inactiva** para
     que la revises; y retira las opciones `extra_scripts*`. Una segunda ejecución no hace nada.
  2. **Si tu código leía `get_config('extra_scripts')`**, ya no existe: lee `ExtraScripts::getScriptsFor()`.
  3. **Si tienes layouts propios**, `ExtraScripts::getScripts()` sigue devolviendo lo mismo —lo público de la
     cabecera—. Para los otros puntos, añade `getScriptsFor('public', 'body_start')` tras `<body>` y
     `getScriptsFor('public', 'body_end')` antes de `</body>` (o `'panel'` en un layout del panel).
  4. **`ExtraScripts::setScripts()` ya no sustituye lo guardado**: lo suma a lo público de la cabecera.
  5. Hay que **recompilar el SASS** del núcleo: hoja nueva `app_config/scripts.scss`.
- **Pruebas:** `core/extra-scripts` y `core/injected-scripts` (por HTTP; declara red).

## ⚠ Seguridad — el CORS solo da credenciales al propio origen y a los declarados, y las cookies llevan `SameSite`

- `Access-Control-Allow-Credentials: true` solo sale para **el propio origen de la instalación** (el de
  `base_url`) y para los que declares en **`$config['cors_credentials_origins']`** (`src/app/config/config.php`,
  vacío de fábrica). La comparación es **exacta**: esquema, host y puerto. `https://tu-dominio.com.otro.net`, el
  mismo host por `http` o con otro puerto **no** cuentan. Una lista que no sea de cadenas cuenta como vacía.
  - `Access-Control-Allow-Origin` sigue devolviendo el origen que pide: **sin credenciales**, un cliente de otro
    origen sigue pudiendo llamar a la API mandando el token en la cabecera.
  - La regla vive en `cors_origin_allows_credentials()` (`src/app/core/AppHelpers.php`) y la usan el contenedor
    `cors` y el manejador de errores fatales de `bootstrap.php`.
- **CAMBIO INCOMPATIBLE para un cliente de otro origen que pida `credentials: 'include'`** (con `fetch`) o
  `withCredentials` (con `XMLHttpRequest`): el navegador **rechaza la respuesta entera**. Dos salidas:
  1. **La recomendada:** quitar `credentials: 'include'` y mandar el token en la cabecera `JWTAuth`, que es como
     la API lo espera (`source-docs/api/`).
  2. Declarar su origen exacto en `cors_credentials_origins`. **Ese origen recibirá las cookies del usuario**:
     solo si es tuyo.
- **Las cookies llevan `SameSite`.** Las del servidor (`setCookieByConfig()`) toman `$config['cookies']['samesite']`
  (`src/app/config/cookies.php`, `Lax` de fábrica; valen `Lax`, `Strict` y `None`, y `None` sin `secure` pasa a
  `Lax` porque el navegador la rechazaría). La del token, que escribe el JavaScript, lleva `SameSite=Lax` y
  `Secure` cuando la página va por `https`; y `CookiesHandler.setCookie` pone `SameSite=Lax` si no se le dice otra
  cosa (opción `sameSite`). **Hay que recompilar el JavaScript del núcleo** (`gulp js-vendor`).
- **Pruebas:** `core/cors` (por casos y por HTTP; declara red: `bin/cli gates with=external`) y
  `core/cookie-options`.

## Nuevo — cerrar sesiones desde el panel y desde el terminal

- **Antes** la revocación existía solo como mecanismo: las marcas estaban, pero **nadie podía moverlas** sin escribir
  en la base a mano (ver la corrección de fechas en «Seguridad — se puede cerrar la sesión de un usuario»).
- **Cada usuario cierra todas sus sesiones**, incluida la que está usando, desde «Mi espacio → Seguridad», pestaña
  «Sesiones». Ruta `my-space-admin-revoke-my-sessions` (POST), para los mismos tipos de usuario que abren esa pantalla.
  - **El usuario sale de la sesión, nunca del cuerpo de la petición**: mandar el id de otro no cierra las del otro.
  - **No se emite ningún token nuevo.** Después hay que volver a entrar con la contraseña. Si se renovara la sesión del
    dispositivo actual, quien robara un token podría echar al dueño de todos sus dispositivos y quedarse dentro.
  - **Durante una suplantación no se ofrece** y la ruta responde **403**: cerraría las sesiones del suplantado.
- **Un administrador cierra las sesiones de un usuario** desde la edición del usuario, pestaña «Sesiones». Ruta
  `users-revoke-sessions-request` (POST, campo `id`), para el principal y el administrador general.
  - Responde **400** si el id no es válido, **404** si el usuario no existe y **403** si no hay permiso. Nunca un 200
    con `success: false`.
  - **A un usuario principal solo lo cierra otro principal.** La regla es una lista blanca en el controlador, además de
    en la ruta: un rol nuevo no hereda el poder de echar al principal.
- **Todo el sistema, solo desde el terminal:** `bin/cli sessions-revoke-all confirm=yes` mueve la marca global a
  ahora y dice a cuántos usuarios alcanza. Sin `confirm=yes` no hace nada. No hay ruta web.
- **Las tres acciones quedan en el registro de acciones**: quién cerró las sesiones de quién.
- **CAMBIA lo que ve una ruta pública con una sesión no válida (P78).** Antes, con un token revocado —o de un usuario
  inactivo, o de una organización no válida— las rutas que no exigen sesión **seguían viendo al usuario y su rol**: un
  administrador revocado seguía viendo los borradores de publicaciones. **Ahora se le trata como visitante.** Si una
  vista tuya sin `require_login` usaba el usuario actual, con una sesión no válida ya no lo recibe.
- **Nueva comprobación en `bin/cli verify-integrity` (39):** cada capacidad anunciada en
  `files/dev/announced-capabilities.json` tiene que tener su ruta o su acción de terminal. Si ofreces una función en
  una pantalla, decláralo ahí.
- **Prueba:** la suite `core/session-revocation`, por HTTP: el token con el que se pide y uno anterior dejan de valer,
  también uno del mismo segundo; los 400, 404 y 403; el principal contra otro principal; el id del cuerpo ignorado; la
  ruta pública tratando como visitante a un revocado; el acceso sigue abriendo con un token revocado, y la acción de
  terminal. Cada una de sus tres guardas se quitó a propósito y la suite se puso en rojo.

## Corregido — el sitemap, las comillas en los metadatos y la renovación de un token caducado

- **«Actualizar sitemap» vuelve a funcionar.** Desde la `v6.4.0` respondía HTTP 500 en cuanto había **una**
  publicación activa: pedía las filas al listado público, que les quita `id`, `meta` y otros campos antes de
  entregarlas, y con esas filas no se puede reconstruir la publicación.
  - **Ahora lee por los mappers**, completas y **sin paginar** —antes, aun sin el fallo, habrían entrado diez—, y
    solo entra lo que ve un visitante: publicación activa, en fecha y aprobada.
  - **La respuesta dice lo que pasó**: cuántas URL entraron, cuántas publicaciones y categorías, cuántas quedaron
    fuera por no ser visibles y cuáles se saltaron por error, con su identificador.
  - **Si el archivo no se puede escribir, lo dice.** Antes respondía «Sitemap creado» sin mirar si lo había
    guardado. Ahora responde 200 con `success: false` y «No se pudo escribir sitemap.xml». **En tu servidor,
    `src/sitemap.xml` tiene que ser escribible por el usuario del servidor web.**
  - Prueba: la suite `core/sitemap`.
- **El título y el Open Graph admiten comillas.** `MetaTags` escribía el `<title>` y las etiquetas de Open Graph sin
  escapar: un título con una comilla rompía la etiqueta en la cabecera pública. Ahora **todo valor que `MetaTags`
  escribe sale escapado**, con el mismo criterio.
  - **El contrato: a `MetaTags` y a `set_title()` se les pasa el texto tal cual.** Si en tu clon escapabas el valor
    antes de pasarlo, ahora saldrá escapado dos veces (`&amp;amp;`): quita tu escapado.
  - Ninguna firma pública cambia. Prueba: la suite `core/meta-tags`.
- **La marca global de sesiones y la de usuario dicen lo mismo: un token vale si nació DESPUÉS de la marca.** La
  global comparaba con «mayor o igual», así que un token del **mismo segundo** que la marca sobrevivía. Ya no.
  - **La regla vive en un solo sitio:** `SessionToken::isCreatedAfterMarks(?int $tokenCreated, ?string
    $userSessionsValidFrom): bool`. Sin fecha entera, o con una marca de usuario que no es una fecha, **niega**.
  - **La lista de rutas que renuevan un token caducado respeta la revocación.** En `src/index.php` hay una lista,
    sin ninguna ruta real de fábrica (solo el marcador de la plantilla), de rutas a las que se les renueva el token caducado (pensada para consultas de terceros). La
    renovación fabricaba un token con fecha de ahora y la revocación miraba esa fecha: un usuario con las sesiones
    cerradas volvía a entrar por ahí. **Ahora se decide con la fecha del token viejo, antes de fabricar el nuevo.**
    Si usas esa lista en tu clon, un usuario revocado deja de renovarse.
  - Prueba: la suite `core/session-revocation`.

## Cambia — las pantallas de «Sistema» y el inglés del estado del sistema

- **«Rutas y permisos», «Avisos del sistema», «Estado y cachés» y «Sitio en mantenimiento» usan el mismo armazón
  que el resto de la configuración**: título, grupo debajo y tarjeta, sin migas de pan. «Rutas y permisos» pierde el
  botón «Regresar».
- **Hay que volver a compilar el SASS del núcleo** (`gulp sass-compile-general`): hay dos hojas nuevas,
  `app_config/routes.scss` y `app_config/system-status.scss`, y el módulo de estado ya no trae
  `Statics/css/site-maintenance.css`.
- **El módulo de estado del sistema está en inglés.** Con el panel en inglés salía en español: no tenía archivo de
  idioma. Ahora lo inyecta `SystemStatusLang`, como los demás módulos del núcleo.
- **«SEO» pasa a llamarse «Identidad y SEO»**, porque la pantalla lleva el título del sitio y el propietario. La ruta
  no cambia: `configurations-appearance-seo`.
- **En «Estado y cachés» los botones se separan 7px en los dos sentidos** al saltar de línea, y en «Avisos del
  sistema» el botón de cada aviso ya no parte su texto: dos reglas en `app_config/system-status.scss`. También pide
  recompilar.

## Nuevo — modo mantenimiento

- **Ahora se puede dejar el sitio fuera de servicio desde el panel**, sin tocar el servidor y sin desplegar nada.
  **Se maneja desde el menú del panel, en «Configuración plataforma → Sitio en mantenimiento», y solo el usuario
  principal puede tocarlo.**
- **Responde HTTP 503 con `Retry-After`**, no 200. **El tiempo de reintento se configura**; por omisión, una hora. Un mantenimiento que responde 200 le dice a un buscador que esa es
  la página definitiva del sitio. Una petición de datos recibe `{"error":"MAINTENANCE_MODE"}` en vez de una página
  entera.
- **El usuario principal nunca queda fuera**, lo diga la lista o no. Los demás roles pasan si los eliges; la lista
  vacía significa «solo el principal».
- **CAMBIO INCOMPATIBLE parcial:** hasta la primera versión de esta entrega, la lista vacía significaba «no pasa nadie,
  ni el principal». Ya no: dejar al dueño de la instalación fuera de su propio sitio era un riesgo sin ganancia.
- **Las tres configuraciones del mantenimiento están reservadas al principal**: la acción genérica de configuraciones
  las rechaza con un **403** antes de escribir. Es un cierre provisional: la genérica dejará de servir para lo serio
  cuando cada configuración con consecuencias tenga su propia acción.
- **El acceso siempre pasa**, incluido el segundo paso del 2FA. Sin eso, quien no tuviera ya la sesión abierta no
  podría entrar a apagarlo y el sitio quedaría muerto hasta editar la base de datos a mano. Las siete rutas que nunca
  se bloquean están enumeradas con su motivo en `MaintenanceMode::ALWAYS_ALLOWED`.
- **Dejar pasar una ruta no es darle permiso:** el modo se comprueba **antes** del control de acceso, no en su lugar.
  Un anónimo que pida la pantalla de configuraciones con el modo encendido acaba en el formulario de acceso.
- **La lista de roles vacía es válida** y significa «no pasa nadie salvo el acceso». Sigue siendo recuperable, y la
  pantalla avisa antes de guardar una lista que te deje fuera.
- **Si la configuración está mal escrita, el sitio se queda EN PIE** y queda anotado. Encender el mantenimiento por una
  errata apagaría el sitio entero. Y una lista de roles con algo inválido **se descarta entera**: quedarse con los
  códigos buenos daría un reparto de acceso que nadie escribió.
- **La vista se renombra**: `src/app/view/pages/maintenance.php` pasa a ser **`pages/503.php`**, como `403.php` y
  `404.php`, y como ya decía su propio título. **Si la habías personalizado, tu archivo sigue ahí con el nombre
  nuevo**; sus claves de traducción (`page503`) no cambian. El nombre anterior chocaba con la vista del panel de
  mantenimiento de `root`, que es otra cosa.
- **Prueba:** el ciclo entero por la interfaz, sin ninguna petición a mano — encender, ver el 503 con una identidad no
  permitida, comprobar que los roles elegidos pasan, dejar la lista vacía, y **entrar con el usuario principal sin
  sesión previa y apagarlo desde la pantalla**. Más 78 comprobaciones de la suite `core/maintenance-mode`.

## Seguridad — quién puede abrir cada ruta se vigila entero

- **La comprobación automática de permisos recorría solo las páginas que se piden con GET** (204 de 322 rutas); un
  cambio de permiso en crear, editar o borrar podía pasar sin que la verificación lo viera.
- **Ahora se comprueban las 322**, preguntándole al framework quién abre cada una y comparando con lo declarado. No
  pide ninguna URL: comprobar quién puede borrar pidiendo el borrado es borrar.
- **Corregido un defecto en el cálculo de permisos** que devolvía los roles **por duplicado** en 223 rutas. Una
  variable interna pisaba el parámetro de la función. **Nadie gana ni pierde acceso** —la comprobación usa
  `in_array`—, pero cualquier pantalla que contara esa lista contaba de más.

## Interno — una plantilla que no existe deja de descubrirse en producción

- **Antes**, una ruta de vista mal escrita no fallaba al analizar el código: fallaba **cuando un visitante entraba en
  esa página**, y se llevaba la página entera. Ahora **327 rutas de vista se comprueban en cada verificación**, en las
  cuatro formas en que el framework carga una plantilla.
- No cambia nada para quien clona; es la red que permite mover vistas de sitio sin romperlas.

## CAMBIO INCOMPATIBLE — el boletín pasa a ser de comunicaciones

- **Antes**, el módulo de boletín repartía sus permisos **lista por lista**: quién veía, quién daba de alta, quién
  editaba y quién borraba se declaraban por separado, y no coincidían.
- **Ahora las ocho rutas con sesión las abren solo tres tipos**: **root**, **administrador general** y
  **comunicaciones**. Ver, crear, editar y borrar van juntos: no se pueden volver a separar por descuido, porque **las
  cuatro listas son ahora una sola**.
- **Quién pierde acceso:** el usuario **general**, el **institucional** y el **administrador de organización**. Los
  tres dejan de ver el listado y dejan de poder dar de alta, editar o borrar suscriptores. **El enlace desaparece de su
  menú**, además de negarse la ruta.
- **Quién gana acceso: comunicaciones**, que antes no tenía el módulo y ahora lo tiene entero. Es el reparto que pide
  el módulo: un boletín es trabajo de comunicación.
- **El alta pública NO se toca.** `newsletter-admin-add` sigue siendo la ruta sin sesión que usan los formularios de
  suscripción del sitio público. Comprobado después del cambio: **se suscribe sin haber entrado**.
- **Si clonas y dependías de que el general gestionara el boletín**, tienes dos caminos: cambiar a esos usuarios al
  tipo comunicaciones, o declarar las rutas del módulo en las listas de `src/app/config/roles.php`.
- **Prueba:** el ciclo completo por HTTP con cada identidad. Comunicaciones ve el listado, da de alta un suscriptor, lo
  edita y lo borra. General recibe **403** en las ocho, **incluida la petición directa al alta**, sin pasar por ningún
  formulario. Y la matriz de rutas por identidades recoge el reparto nuevo.

## Seguridad — se puede cerrar la sesión de un usuario, y la de todos

> **Corrección de fechas (2026-09-30).** Esta entrada se escribió el **2026-09-24** y describe el **mecanismo**: las dos
> marcas y la comprobación. Pero ese día **no había ninguna forma de moverlas** salvo escribir en la base a mano:
> ninguna pantalla, ninguna ruta, ninguna acción de terminal. **La función —cerrar sesiones de verdad— llega el
> 2026-09-30**, en la entrada «Nuevo — cerrar sesiones desde el panel y desde el terminal». Además, desde entonces la
> columna se llama `sessionsValidFrom` (ver el renombrado de columnas), y la regla «después de la marca» **no es
> idéntica en las dos**: la del usuario deja fuera un token nacido en el mismo segundo que la marca, y la global lo
> deja pasar.

- **Antes**, quien entraba tenía acceso hasta que su credencial caducaba: **no había forma de echarlo antes**. Si una
  cuenta quedaba comprometida, lo único que servía era esperar.
- **Ahora hay dos marcas**, y en las dos vale la misma regla: una sesión sirve si empezó **después** de la marca.
  - **Un usuario:** la columna nueva `sessions_valid_from` de la tabla de usuarios. Ponerla a «ahora» cierra todas sus
    sesiones. Vacía significa «sin revocaciones», que es como quedan todos los usuarios existentes.
  - **Todo el sistema:** la configuración `session_minimum_date`. Moverla echa fuera a todo el mundo. **Sustituye al
    literal** que vivía escrito a mano en `src/app/config/roles.php`, con el mismo valor, así que nadie queda fuera por
    esta actualización.
- **CAMBIO INCOMPATIBLE: la tabla de usuarios gana una columna.** Aplica
  `databases/actualizaciones/2026-09-24-revocacion-de-sesiones.sql`. Se puede aplicar con la aplicación en marcha y no
  cierra ninguna sesión: la columna nace vacía.
- **Se retiran tres llamadas que parecían revocar y no revocaban nada.** Vivían en `src/index.php` y su efecto moría
  con la petición. Lo que sí cortaba la petición —el usuario inactivo, la organización no válida— sigue igual.
- **Alcanza a la suplantación:** si un administrador entra como otro usuario y ese usuario tiene la sesión revocada, no
  entra.
- **Lo que todavía NO se puede:** cerrar **una** sesión concreta y dejar las demás abiertas. Hoy «cerrar esta» y
  «cerrar todas las mías» son la misma orden. Llega en su propia entrega.
- **Prueba:** el ciclo entero por HTTP —entrar, revocar, ver que el mismo token deja de valer, quitar la marca y ver
  que vuelve— y la matriz de 204 rutas por 7 identidades sin un solo cambio.

## Nuevo — la duración de la sesión se configura

- **Antes** eran **31 días clavados en el código**: para cambiarlos había que editar el framework.
- **Ahora salen de la configuración**, con `session_duration` en segundos. **El valor por defecto sigue siendo 31
  días**, así que si no tocas nada no cambia nada y ninguna sesión abierta se cae.
- **Se admite de 1 a 365 días.** Cualquier otra cosa —cero, negativo, texto, decimal, o más de un año— **cae al valor
  por defecto** y deja un aviso en el registro. Un entero escrito como texto (`'7200'`) sí vale.
- **Por qué hay tope:** por encima de un año la caducidad deja de acotar nada, y acotar es justo para lo que existe.
- **Ojo antes de subirla:** hoy **no se puede revocar una sesión** antes de que caduque. Alargar la duración alarga
  también la vida de una credencial robada. La revocación llega en su propia entrega; hasta entonces, subirla es una
  decisión con ese coste delante.
- **No cambia** la sesión aislada (`SessionTokenIsolated`), que tiene su propio valor por defecto de 60 segundos.
- **Prueba:** la suite de la sesión comprueba el valor por defecto, el configurado y los inválidos, **y que el token
  emitido dura lo configurado**, no solo que la función acierte.

## Seguridad — la comprobación de acceso falla cerrada

- **Las dos capas que protegen una ruta con `require_login` se endurecen.** Ahora, sin sesión, una petición de datos a
  una ruta con `require_login` recibe siempre **403 `RESTRICTED_AREA`**; y la comprobación de permisos por rol **niega**
  en cuanto la ruta exige sesión y no la hay.
- **Qué NO cambia:** las rutas con `require_login` en `false`, la redirección al formulario de acceso de una petición
  normal, el `requested_uri` que se guarda para volver, el caso del terminal y el reparto de permisos de cada rol.
  Medido ruta por ruta: **204 rutas por 7 identidades, ninguna casilla distinta**.
- **Lo que sí te toca mirar si clonas:** si tu instalación declara `require_login` en la **ruta del formulario de
  acceso**, esa ruta ahora se niega. En el framework vale `false`, que es lo correcto; si lo cambiaste, déjalo en
  `false`.
- **Puerta:** suite `core/access-without-session` (efecto de red, entra con `bin/cli gates with=external`). Comprueba
  sin sesión que una ruta protegida niega, que una petición normal va al formulario de acceso y que una ruta abierta
  sigue respondiendo. Falla si se deshace el cambio.

## Cambia — el paquete del login deja de llevar «extras»

- **Antes**, la respuesta del login traía una clave `extras`, vacía en el camino normal.
- **Ahora esa clave no se envía.** El detalle de los errores sigue registrándose en el log del servidor.
- **Por qué se retira y no se arregla.** El canal estaba roto desde hacía tiempo: el servidor mandaba `extras` y el
  JavaScript del framework leía `extraData`, un nombre que nunca existió en la respuesta. Medido antes de decidir:
  **un emisor y ningún receptor** —2 apariciones en el login, ninguna lectura en el JavaScript propio—. La tercera
  aparición de `extras` en el código, la del vehículo genérico `ResultOperations`, **se queda**: no es de esta ruptura.
- **Si tu JavaScript leía `extras` de la respuesta del login, deja de estar.** No dará error: dará indefinido. Si
  necesitas un canal de datos extra hacia el navegador, **decláralo**, como se hizo con los campos del usuario.
- **De regalo, un efecto visible en el navegador:** el paquete guardaba en el almacenamiento local la **cadena de texto
  `"null"`** cada vez que alguien entraba. La causa exacta, porque la de bulto es otra: el código pasaba un **nulo
  pelado** a `localStorage.setItem()`, y es `setItem()` quien lo convierte en la cadena `"null"` —`JSON.stringify()` no
  llegaba a ejecutarse en ese camino—. Ya no escribe esa clave cuando no hay datos extra, y retira la que hubiera
  quedado de antes.
- **Prueba:** `core/session-contract`, sección `[l]`. Hace un login de verdad y comprueba las claves de primer nivel de
  la respuesta: tienen que estar `auth` y `userData`, y no pueden estar `extras` ni `extraData`.

## Eliminaciones — módulos obsoletos

- El módulo de chat interno.
- El módulo de presentaciones de capacitación.

## Eliminaciones — código sin llamadores (lote 10)

Nada del framework lo usaba. **Si tu proyecto lo llamaba, deja de existir:**

- `Documents\Mappers\DocumentsMapper::folderRemove()`.
- `Publications\Mappers\AttachmentPublicationMapper::existsByPublication()`. Además metía `$lang` en el SQL sin
  marcador. **Qué hacer:** consulta con `where([...])` por marcador.
- La constante `TokenController::TOKEN_PASSWORD_RECOVERY_CODE`.
- La clase vacía `API\Adapters\CronJobTaskAdapter`. **Qué hacer:** extiende `PiecesPHP\Terminal\CronJobTask`
  directamente.
- En Documents, Forms (Categories y DocumentTypes), Organizations, Publications, SystemApprovals, Banner y los contenidos
  genéricos: la constante `UPLOAD_DIR_TMP` y las propiedades `$uploadTmpDir` y `$uploadDirTmpURL` de sus
  controladores, que se calculaban y nadie leía.
- En `bin/tools/refactorization/Rector.php`, seis exclusiones que apuntaban a módulos ya borrados.

## Nuevo — avisos del sistema y mantenimiento

- **«Avisos del sistema»** en el panel (root y administrador general): una tabla con todo lo que hay que revisar, con su
  gravedad y un botón para ir a arreglarlo. Root puede ocultar un aviso; oculto, sigue en la tabla. Los módulos pueden
  registrar los suyos (`PiecesPHP\SystemStatus\SystemAlertRegistry`). Primeros avisos: la `app_key` de relleno (el que
  ya salía, ahora desde el registro) y los enlaces rotos de `statics/server-delegated`.
- **«Mantenimiento»** (root): el estado de las cachés y de los enlaces, **borrar solo los enlaces rotos** (sin permisos de
  administrador del sistema: lo hace el servidor web) y la limpieza completa de caché que ya existía.
- **`bin/cli system-alerts`**: los mismos avisos desde la terminal, con su gravedad y si están ocultos en el panel.
- Guía: `source-docs/project/docs/piecesphp/new-features/system-status.md`.

## Corregido — cinco búsquedas de catálogo respondían «prohibido» a todo el mundo

- `locations-countries-ajax-search` y sus cuatro hermanas (estados, regiones, ciudades y puntos) **exigían sesión pero
  no estaban concedidas a ningún rol**, así que respondían 403 **a todos los tipos de usuario, incluido root**. Medido
  antes y después.
- **La causa, y conviene saberla para no repetirla:** el catálogo de permisos se rellena con el `rolesAllowed` **de cada
  ruta** al arrancar la aplicación. Una ruta que declara `requireLogin` y **no declara roles** no queda «abierta a todo
  el que entre»: queda **cerrada para todos**.
- Ahora reparten como sus hermanas del mismo módulo (root y administrador general).
- **Nueva puerta:** la suite `core/permissions-coherence` falla si una ruta con sesión no está concedida a nadie, si una
  ruta sin sesión reparte por roles, o si un tipo de usuario se queda sin rol.

## Cambia — cada sesión aislada firma con su propia clave

- `SessionTokenIsolated` ya **no firma con la `app_key` de la aplicación**, sino con una clave derivada de ella **y del
  nombre del canal**. Con eso, un token de la sesión de usuarios deja de valer en un canal aislado, y **dos canales
  aislados distintos dejan de aceptarse el token entre sí**.
- **Antes, el aislamiento era solo del nombre:** las dos clases firmaban con la misma clave, así que un token del panel
  presentado en el canal aislado se aceptaba. Ya no.
- **Al actualizar, los tokens aislados que estuvieran vivos dejan de valer de golpe:** quien esté dentro de una de esas
  zonas vuelve a entrar. El token no se corrompe —sigue siendo válido para la clave anterior—, simplemente ya no
  pertenece a ese canal.
- **Si tu código lee la carga del token sin comprobar, ojo:** en ese caso `getJWTData()` **no devuelve null**, devuelve
  la cadena `SIGNATURE_VERIFICATION_FAILED`, y leer una propiedad sobre ella aborta la petición. Comprueba
  `isActiveSession()` antes.
- **Quien pase su propia clave al constructor no cambia:** solo cambia el valor por defecto.
- **Nada del framework usa hoy esa clase**: el cambio solo afecta a quien haya montado una zona propia con ella.

## Corregido — un tipo de usuario inválido en la URL da 404, no 500

- `/users/add/type/{type}/` lleva el tipo **cifrado**. Si alguien escribía ahí un número o cualquier texto, ese valor
  llegaba sin validar a un parámetro declarado `int` y la página respondía **500 con un TypeError**; en local, con el
  error a la vista.
- **Ahora responde 404**, con la misma página de «no encontrado» que ya usaba ese controlador para un tipo desconocido.
  No se inventó nada: se copió el patrón que ya estaba en el mismo método.

## Cambia — donde hace falta sesión, ahora se exige

- **46 sitios en 30 archivos** —mappers de siete módulos, vistas del panel, formularios de usuarios y organizaciones—
  leían al usuario conectado **sin comprobar que existiera**. Funcionaban porque esos caminos siempre tienen sesión;
  el día que uno no la tuviera, la página moría con «Attempt to read property on null» y sin decir por qué.
- **Ahora usan `getLoggedFrameworkUserOrFail()`**: si falta la sesión, el fallo es el mismo fallo, pero **con nombre**.
- **La falta de sesión tiene excepción propia:** `PiecesPHP\Core\Exceptions\SessionRequiredException`. Extiende de
  `\Exception`, así que **cualquier `catch` que ya la recogiera la sigue recogiendo**: medido, ninguno de los 92 sitios
  que capturan alrededor de esas llamadas cambia de comportamiento. El mensaje no cambia.
- **Qué ve el usuario, sin cambios respecto de ayer.** Una ruta que declara `requireLogin` **redirige al login antes de
  ejecutar nada**, así que la excepción no llega a verse; a una petición de datos le responde 403 `RESTRICTED_AREA`.
  La excepción es la **segunda línea**: salta si una ruta no declara `requireLogin` o si tu instalación apaga
  `control_access_login`. Entonces sí: en local, el detalle; en producción, el mensaje genérico con su código de
  referencia.
- **Para quien desarrolla:** si tu código llama a `getLoggedFrameworkUser()` y desreferencia el resultado, usa
  `…OrFail()` cuando ese camino exija sesión, y comprueba el null cuando no.

## Cambia — `NewsMapper::save()` ya no atribuye la noticia al usuario 1

- **Antes**, si no había sesión, una noticia se guardaba **firmada por el usuario 1**. No era un valor por defecto: era
  atribuirle a alguien un trabajo que no hizo, y en un registro de autoría eso es peor que fallar.
- **Ahora lanza.** El alta por el panel no cambia: su ruta exige sesión. Si guardabas noticias desde un camino sin
  sesión —una tarea propia, un guion de carga—, ahora recibes una excepción y tienes que decidir con qué usuario se
  guardan.

## Cambia — al navegador solo viajan los campos declarados del usuario

- **Antes**, al entrar, el login mandaba al navegador **la ficha del usuario entera**: lo que tuviera la tabla, menos la
  contraseña y el JSON interno, quitados a mano. Eso significaba que **una columna nueva viajaba sola** al navegador de
  todos los usuarios, sin que nadie lo decidiera.
- **Ahora hay una lista declarada** y el login **filtra por ella**: lo que no esté declarado no viaja. El paquete pasa
  de 14 campos a 10.
- **Dejan de viajar cuatro:** `failed_attempts`, `status`, `created_at` y `modified_at`. Siguen viajando el
  identificador, el usuario, el correo, los nombres, el tipo, la organización y `misc` (el avatar y las
  meta-propiedades).
- **Si tu JavaScript leía alguno de los cuatro, deja de estar**, y **no dará error: dará vacío**. Una fecha en blanco,
  un contador a cero. Si te hace falta, pídelo al servidor.
- **Puerta:** `bin/cli verify-integrity` falla si el login puede mandar un campo no declarado, si la lista declara algo
  que ya no manda, o si aparece una columna nueva en la tabla de usuarios **sin decidir si viaja o no**.

## Documentado — las sesiones aisladas: qué garantizan y qué no

`PiecesPHP\Core\SessionTokenIsolated`, con su pareja en el navegador `PiecesPHPGenericHandlerSession`, sirve para
**sesiones distintas de la de usuarios**: una zona pública o privada con su propia capa de acceso. Ahora tiene su
contrato fijado por pruebas (`core/session-isolated`, 23 comprobaciones). Lo que hay que saber antes de usarla:

- **El aislamiento es del CANAL, no criptográfico.** Por defecto firma con la misma clave de la aplicación que la sesión
  de usuarios, así que **un token de usuario presentado en el canal aislado se acepta**. Lo separa el nombre del canal,
  nada más. **Si necesitas aislamiento de verdad, pásale una clave propia** en el constructor —el framework ya sabe
  derivar claves por uso— y así un token de un canal no vale en el otro. Al revés no hay riesgo: un token aislado no
  lleva identificador de usuario, así que no abre sesión de usuario.
- **La duración va en MINUTOS y por defecto es 60**, mientras que en la sesión de usuarios el argumento son segundos y
  el defecto son 31 días. Dos métodos con la misma forma y una hora frente a un mes.
- **El candado que ata el token a su cliente viene apagado** aquí y encendido en la sesión de usuarios.
- **La fecha mínima de validez por defecto es `2024-12-13`** (en la de usuarios, `1990-01-01`): un token creado antes no
  vale, aunque esté bien firmado y sin caducar. Y es **por instancia**, así que invalidar los de una zona no toca a las
  demás.
- **La carga es libre:** no exige identificador ni tipo. Es lo contrario del contrato de la sesión de usuarios.

## Cambia — el token de sesión dice QUIÉN, nunca QUÉ ES

- **El token ya no lleva dentro el tipo de usuario.** Solo lleva el `id`, más la fecha de emisión, la de caducidad y,
  si está activo, el candado que lo ata a su cliente. **El tipo, el estado y la organización se leen de la base en cada
  petición**, que es donde está la verdad.
- **Por qué:** ese dato viajaba congelado hasta 31 días —y con la duración configurable, más—. Hoy no concedía nada,
  pero estaba ahí para que alguien se lo creyera. Medido antes de quitarlo: **ningún sitio del framework decidía con
  él**.
- **Los tokens emitidos antes siguen valiendo**, con su tipo dentro, ignorado. Nadie se queda fuera por actualizar.
  Además, un token con un tipo que ya no existe **ahora vale**: antes lo rechazaba una comprobación que solo miraba la
  forma.
- **Si algo tuyo lee el contenido del token por su cuenta** para saber el tipo de un usuario, deja de estar ahí: léelo
  de la base.
- También existe `PiecesPHP\Core\SessionTokenIsolated` (con su pareja en el navegador,
  `PiecesPHPGenericHandlerSession`) para **sesiones distintas de la de usuarios** en una zona propia. Su carga es libre:
  si escribiste encima leyendo el tipo, cámbialo igual.
- Ese mecanismo recibió el mismo arreglo que la sesión principal: una fecha de creación inutilizable ya no se da por
  buena.

## Nuevo — el nombre de la sesión se configura

- **El nombre con el que viaja la sesión** (la cabecera y la cookie `JWTAuth`) **sale ahora de la configuración**, con
  `JWTAuth` como valor por defecto: quien no toque nada no nota ningún cambio. La clave es `session_token_name`.
- **Para qué sirve:** dos instalaciones del framework en el mismo dominio ya no se pisan la sesión. Hasta ahora, entrar
  en una desconectaba de la otra.
- **Una sola fuente.** Antes el literal estaba escrito en **seis** sitios: la constante de PHP, tres variables del
  JavaScript y las dos listas de cabeceras permitidas para peticiones de otro origen. Ahora el servidor lo publica y el
  JavaScript lo recibe; **`bin/cli verify-integrity` falla si los valores por defecto de los dos lados divergen**.
- **Qué nombres valen:** letras, dígitos y guion bajo, hasta 64 caracteres. Un guion NO vale: PHP lo convierte en guion
  bajo al leer una cabecera, y el nombre llegaría bien por la cookie y mal por la cabecera, en silencio. Un nombre
  inválido cae al de por defecto y queda anotado.
- **CUIDADO AL CAMBIARLO:** cambiar el nombre **cierra todas las sesiones abiertas** —los tokens que viajan con el
  nombre anterior dejan de reconocerse— y deja las claves antiguas huérfanas en el navegador de cada usuario. Cámbialo
  en una ventana en la que puedas avisar.

## Corregido — la comprobación de la sesión es más estricta

- **Un token con datos inutilizables ya no se da por bueno.**
- **Si falta la dirección del cliente**, la petición ya no revienta.
- **Para quien despliega:** la sesión se ata también al nombre de la máquina. Cambiar el nombre del servidor invalida
  todas las sesiones.
- Cubierto por `core/session-contract` (36 comprobaciones) y `core/session-terminal-root` (11), las dos provocadas.

## Nuevo — cada organización tiene un código, y la importación lo usa

- **Código de organización.** Cada organización lleva un código propio, `ORG` + 7 dígitos (por ejemplo `ORG8545741`),
  **único, inmutable y al azar** —no correlativo: no dice cuántas organizaciones hay ni cuál es más antigua—. Se ve en
  el listado y en la ficha, y el buscador del listado lo encuentra. La organización global lleva el código reservado
  `ORG0000000`.
- **Sirve para nombrarla sin usar su identificador interno**, que es lo que había que escribir hasta ahora en los
  archivos de importación.
- **Qué hacer al actualizar** (se puede con la aplicación en marcha): aplica
  `databases/actualizaciones/2026-09-22-organizaciones-code.sql`, que trae sus tres pasos; el del medio es
  `bin/cli organizations-assign-codes`, que reparte los códigos a las organizaciones que ya existen y se puede repetir
  sin miedo. Entre el primer paso y el segundo, las organizaciones se ven con normalidad: solo les falta el código.
- Intentar cambiar un código no rompe el guardado: se ignora, se repone el que tenía y queda en el log.

## Cambia — la importación de usuarios exige organización y la nombra por su código

- **Antes**, una fila sin organización caía en la organización global sin decir nada. **Ahora:**
  - si la fila trae el código, esa organización;
  - si no lo trae y quien importa tiene organización, se hereda la suya;
  - si no lo trae y quien importa tampoco tiene, **la fila falla** y el informe dice qué fila y qué poner;
  - un código o un identificador **que no existe se rechaza**; no se sustituye por el de quien importa.
- **La columna «organización» acepta el código** (también escrito como «código de organización») y sigue aceptando el
  identificador interno, para que las plantillas repartidas antes sigan valiendo.
- **La exportación de usuarios escribe el código** en esa columna, y deja de escribir el identificador interno. Un
  archivo exportado se reimporta sin tocarlo, y uno exportado con la versión anterior también.
- Como siempre en esta importación, **una fila mala impide la importación entera**: no quedan usuarios a medias.

## Corregido — navegar ya no reparte la organización global

- **Antes**, cada petición del panel comprobaba si el usuario tenía organización y, si no la tenía, **le escribía la
  organización global** (`-10`) en la base. Un dato malo se tapaba solo, en silencio, mientras alguien navegaba.
- **Ahora** nadie recibe una organización por el hecho de navegar:
  - a los tipos que no requieren organización (principal, administrador general…) no se les toca: no la necesitan;
  - a un tipo que sí la requiere y está sin ella se le anota el defecto en el log —una vez por sesión, con su código de
    referencia— y se deja como está, para que se vea y se corrija de verdad.
- **Qué hacer:** si en tu instalación aparecen usuarios sin organización, es un dato que ya estaba mal; asígnasela desde
  el panel. Los usuarios que hoy tienen la organización global **se quedan como están**: este cambio no toca datos.
- Medido en la instalación de desarrollo antes de cambiarlo: ningún usuario de un tipo que requiere organización la
  tenía vacía, así que el cambio no deja a nadie en un estado nuevo.

## Corregido — el alta pública por API con organización nueva

- **Estaba rota:** pedir el alta con `organizationID=NONE` y `organizationName` no creaba la organización (el código se
  fabricaba una petición HTTP interna que ya no era válida) y el alta seguía con una organización vacía. Además, el
  usuario que creaba su organización **no quedaba como administrador de ella**: su tipo se perdía por el camino.
- **Ahora:** la organización se crea con una llamada directa al mismo código que usa el panel; el usuario queda como su
  administrador; y si algo falla, **no queda ni usuario ni organización a medias**. El error se responde con el código
  de referencia de P56.
- **La organización global (`-10`) deja de ser el comodín de «no hay organización»:** solo es el valor por defecto de los
  tipos de usuario que no la requieren. A un tipo que sí la requiere, el alta le exige una organización de verdad.
- Nuevo: `OrganizationsController::createOrganization(array $values): ?OrganizationMapper` y la constante
  `OrganizationMapper::NIT_WITHOUT_INFORMATION_PREFIX`. Documentación para integradores en `source-docs/api/`, y para
  quien desarrolla, en la guía general.

## Sustituido dentro de la misma versión — el «modo de pruebas» de correo

- **Las pre-versiones (`alpha` y la candidata `rc.1`) traían en «Configuración de emails» un modo de pruebas** con tres
  estados: automático, siempre encendido y apagado. **En la `v8.0.0` ya no decide nada**: lo sustituye la **entrega del
  correo** —ver «el correo hay que declararlo», más arriba—, en **Integraciones → Correo**, con «Según el entorno (sin
  declarar, se retiene)», «Retenido: no sale de esta máquina» y «Real: sale por el SMTP de arriba».
- **Si viene de una pre-versión, revise «Entrega del correo»**: el valor que hubiera dejado en el modo viejo se conserva,
  pero **ya no decide nada**.
- **Lo que se conserva:** el **servidor y el puerto de pruebas** (`127.0.0.1:1025` de fábrica, en la misma pantalla), y que
  la configuración SMTP real no se toca: el desvío ocurre solo al enviar.
- **Mientras el correo está retenido**, root y el administrador general ven el aviso «El correo está retenido…»
  (`mail-test-mode`): informativo en una instalación local, de atención fuera de ella.
- Guía del servidor de pruebas (Mailpit): `source-docs/entornos/docs/environments/content/mailpit/index.md`.

## Nuevo — las rutas se comprueban al registrarse

- **Una ruta con el patrón mal escrito ya no tumba el sitio entero.** FastRoute analiza todos los patrones juntos en el
  primer despacho, así que antes una sola ruta mala dejaba sin web y sin `bin/cli`, y el error no decía cuál era.
- **En una instalación local:** el arranque falla en el acto, con el nombre de la ruta, su patrón, lo que el analizador
  no entendió y **el archivo y la línea donde se declaró**.
- **En producción:** la aplicación sigue en pie; esa ruta (y solo esa) se descarta, el error va al log y root ve el aviso
  «Se descartaron N ruta(s) por tener un patrón que no se puede analizar…» en «Avisos del sistema» y en
  `bin/cli system-alerts`. **Ojo:** una ruta descartada responde 404, así que el aviso hay que atenderlo.
- **`bin/check-routes` sigue siendo obligatorio** y no lo sustituye nada: analiza los patrones FINALES (con el prefijo de
  su grupo) y funciona aunque la aplicación no arranque. La comprobación nueva mira el patrón propio de cada ruta.

## Nuevo — la instalación sabe de qué commit salió

- **`bin/cli version`** dice la versión, su fecha y el **commit** del que salió la instalación, con su fuente. «Acerca del
  framework» lo muestra también (los 12 primeros caracteres; el completo, al pasar el ratón).
- **De dónde sale el commit:** del propio repositorio si hay `.git` (lee solo `HEAD`, `refs/` y `packed-refs`, nunca
  `.git/config`, y no ejecuta git), o de un **sello de despliegue**, `src/app/version-stamp.json`, para las instalaciones
  que se suben sin `.git`.
- **Qué hacer al desplegar sin `.git`:** en la copia de origen, `bin/cli version-stamp` (sella el commit actual) o
  `bin/cli version-stamp commit=<40 hex>`, y sube el sello con el resto. El sello está en `.gitignore`: no se versiona.

## Corregido — el registro de acciones y `piecesphp/database` 5.2.0

- **`piecesphp/database` se instala en la v5.2.0** (el requisito sigue siendo `^5.0`). `save()` deja en el objeto el id de
  la fila insertada, los segmentos WHERE y HAVING vacíos no producen SQL y compilarlos ya no los modifica. **Qué hacer:**
  `composer update piecesphp/database --working-dir=src`.
- **`UsersModel::save()` ya no pone el id a mano**: lo hace el paquete. El resultado es el mismo.
- **`QueueTask::dispatch()` devuelve de verdad el id de la tarea encolada.** Antes devolvía `null` aunque encolara.
- **El registro de acciones se puede leer sin la extensión `geoip`.** `LogsMapper::addLog()` guarda siempre la IP
  (`0.0.0.0` desde la terminal) y la geolocalización («Sin especificar» si no hay `geoip`). Antes, sin esa extensión, las
  filas quedaban sin esos datos y `LogsMapper` no podía cargarlas. Las filas antiguas escritas así siguen sin cargarse.
- **Los mensajes de los interruptores de importar y exportar y del mantenimiento salen con su texto**, no con
  «%message%» literal.
- Los botones de la portada de importar y exportar, de «Avisos del sistema» y del formulario de exportación tienen el
  tamaño estándar del panel.

## Corregido — residuos del lote 10

- **Cada informe de accesos exporta lo suyo.** El botón de exportar de «Informes de acceso» descargaba siempre el
  informe de «con ingreso», aunque se estuviera viendo el de intentos o el de «sin ingreso».

- **El alta de suscriptor del formulario de contacto pasa por la comprobación del CAPTCHA.**
- **Un error antes de preparar el correo del formulario de contacto ya no rompe la respuesta** con una variable sin
  definir.
- **`SystemApprovalsMapper::save()` sin sesión** ya no falla leyendo el usuario: respeta el `createdBy` que ponga quien
  llama. Sin sesión y sin `createdBy`, lanza la excepción del ORM por campo nulo. **Qué hacer:** si creas aprobaciones
  desde una tarea o una ruta pública, asigna `createdBy`.
- **`UserProblemsController::sendCode()` con un tipo no soportado** lanza `InvalidArgumentException` en lugar de enviar
  un correo vacío.
- **La vista previa de una publicación ya no suma visitas.** Un borrador, una programada o una pendiente de aprobación
  vistas por quien puede previsualizarlas no cuentan; decide `PublicationMapper::countsVisits()`.
- **`CronJobTask::weeklyOn()` con un día fuera de 0-6** lanza `InvalidArgumentException` al registrar la tarea. Antes
  se aceptaba y la tarea no corría nunca. **Qué hacer:** usa 0 (domingo) a 6 (sábado).

- **`generate_code()` y `generate_pass()` usan `random_int()`**: los códigos de verificación, de
  recuperación, de los tokens y las contraseñas generadas salen de un generador criptográficamente seguro. Mismo formato.
- **Una sola plantilla de correo sin estilos.** `SystemApprovals` tenía su propia copia de
  `mailing/template_base_no_style.php` solo para añadir los comentarios de una aprobación; la plantilla del núcleo
  (`src/app/view/mailing/`) admite ahora `reason` (ya escapado por quien llama) y la copia se retira.
- **El alta de usuarios por formulario vuelve a crear el perfil del usuario.** Desde el 22-08-2026 (cuando la creación
  del perfil salió del camino de lectura del login) no lo creaba: `UsersModel::save()` no dejaba el id insertado en el
  objeto y la condición nunca se cumplía. Ahora `UsersModel::save()` deja el id. **Qué hacer:** los usuarios creados por
  formulario desde esa fecha pueden no tener perfil; se crea con `UserProfileMapper::createProfile($id)`.
- **Una traducción dinámica guardada en el mismo segundo que el último volcado ya no queda pendiente** hasta el cambio
  siguiente (`add-dynamic-translations.php` comparaba con `>` una fecha con resolución de segundos).
- **El banner de la portada ya no rompe la página.** `home.js` leía `tagName` sobre el objeto jQuery que le pasa
  `BuiltInBannerAdapter`, y lanzaba un error en cuanto había un banner publicado.
- **La vista pública de cada banner escapa** su enlace (ahora entre comillas) y su título. El contenido sigue siendo
  texto enriquecido del administrador.
- **`API_CRONJOBS` registra la ruta del cron aunque las demás banderas de la API estén apagadas.** Antes hacía falta
  otra bandera encendida.

## Cambios que rompen compatibilidad — las claves de reCAPTCHA v3 salen del código

Hasta ahora la clave secreta estaba escrita en `GoogleReCaptchaV3Controller` y la de sitio en
`src/statics/js/contact-form.js`. Ahora se leen de la configuración, cargada desde las claves seguras por
`src/app/config/final-configurations-includes/api-keys.php`: `GoogleReCaptchaV3SecretKey` y `GoogleReCaptchaV3SiteKey`.
Sin clave secreta, el formulario de contacto **rechaza** el envío y deja una línea en el log.

**Qué hacer:** crea `secure-keys/recaptcha-v3-secret` y `secure-keys/recaptcha-v3-site` con tus claves de reCAPTCHA v3.
Sin claves reales se usan las de prueba del propietario, versionadas a propósito en `src/app/config/config.php`
(`GoogleReCaptchaV3TestSiteKey` y `GoogleReCaptchaV3TestSecretKey`; ADR 0021 de la documentación de agentes): solo
funcionan en sus dominios de prueba y en `localhost`. En producción pon las tuyas.

## Herramientas — PHPStan mide solo PHP 8.5

`bin/phpstan` corre una sola pasada, con `phpVersion` fijo en 8.5, en el framework y en los cuatro paquetes. Antes eran
dos pasadas (8.4 y 8.5) con su unión. Ninguna cifra de las líneas base cambió.

## Herramientas — PHPStan sin supresiones muertas y `guarda-add` más estricta

- `bin/phpstan` sale con 1 si una entrada de `ignoreErrors` no casa con ningún error en alguna de sus pasadas. Se
  retiraron las seis que no casaban.
- `bin/guarda-add` para si lo preparado no coincide con lo previsto, aunque se dé `--motivo`.

`PublicationsController` expresa con la constante `SOLO_PROPIAS = false` la regla apagada «quien no administra la
organización solo ve sus publicaciones»; antes era un `&& false` escondido. Comportamiento idéntico.

## CÓMO ACTUALIZAR — LEER ANTES DE FUSIONAR

**Crea `src/app/config/environment.php` antes de nada** (ruptura 34): `cp src/app/config/environment.example.php
src/app/config/environment.php` y pon `return 'local';` en tu máquina de desarrollo. Sin él, la instalación funciona como
producción y usa las credenciales de producción de `database.php`.

**Los finales de línea pasan a LF** en todo el repositorio y en los cuatro paquetes
(`.gitattributes`: `* text=auto eol=lf`; ADR 0012 de la documentación de agentes).
- **Ningún archivo cambia de contenido en git:** el índice ya guardaba LF. Solo cambian
  `.gitattributes` y `.editorconfig`.
- **Lo que cambia es tu disco.** Tras fusionar, con el árbol limpio:

  ```bash
  bin/normaliza-eol --arregla
  git diff --name-only       # lo que cambia de contenido: debería salir vacío
  git add --renormalize .    # refresca el índice: mete los mismos contenidos
  git diff --cached --stat   # debe salir vacío
  git status                 # ya sin los cientos de archivos
  ```

- **Por qué hace falta `--renormalize`:** tras cambiar la política, git marca como modificados
  todos los archivos reescritos, aunque su contenido sea idéntico. Medido aquí: 1.511 marcados y
  9 con cambios reales. `git update-index --refresh` no lo arregla.
- Si `git diff --cached --stat` muestra algo, eso SÍ cambió de contenido: revísalo antes de
  commitear.
- `bin/normaliza-eol` lee las rutas de git con `-z` desde esta versión. La anterior fallaba con
  nombres de archivo con tildes.
- Si tu editor respeta `.editorconfig`, a partir de ahí escribe LF solo.

Esta versión **renormaliza los finales de línea de todo el repositorio**: 1.126 archivos,
**cero cambio de contenido** (verificado con `git diff --ignore-cr-at-eol`, que sale vacío).
El motivo está en el commit `0ac751b9`.

**Si fusionas sin más, cada archivo que tu despliegue haya tocado dará conflicto.** No por
el código: por los finales de línea.

```bash
git merge -X renormalize <rama-del-framework>
```

o, si prefieres dejarlo puesto de una vez:

```bash
git config merge.renormalize true
```

**Comprobado en una fusión de prueba**, no supuesto: un despliegue con cambios propios sobre
archivos afectados da **2 conflictos sin la opción y 0 con ella**, y **conserva sus cambios
locales** en ambos archivos.

Y una vez fusionado, para que la arqueología no se pierda:

```bash
git config blame.ignoreRevsFile .git-blame-ignore-revs
```

Sin eso, `OrganizationMapper.php` y `PublicationsController.php` atribuyen **todas** sus
líneas al commit de renormalización — comprobado: de 1 commit distinto en 600 líneas se pasa
a la historia real al activarlo.

## ⚠ CAMBIOS INCOMPATIBLES — agrupados a propósito, para una MAJOR

*Esta tanda **rompe compatibilidad hacia atrás deliberadamente**. No se ha conservado nada por
compatibilidad con instalaciones existentes. **Sin número de versión y sin etiqueta**: el número lo
decide quien publique, con todo esto delante.*

**Si mantienes módulos propios sobre este framework, esto es lo que te toca cambiar.**

### 1 · `ServerStatics::serveModuleStatic()` YA NO EXISTE. Usa `serve()`

Era un método intermedio que solo reenviaba a `serve()` e ignoraba dos de sus parámetros. En cada
`<Modulo>Routes::staticResolver()`:

```diff
-return $server->serveModuleStatic($request, $response, $args, __DIR__ . '/Statics', [], self::staticRoute());
+return $server->serve($request, $response, $args, __DIR__ . '/Statics');
```

**El comportamiento es idéntico**: `$replacement` y `$baseStaticURL` no se reenviaban a nada, y
`$mustValidate` vale `true` por defecto en las dos firmas. Comprobado en seis caminos —extensión
delegada, extensión no delegada, el enlace por el servidor web, otro módulo del panel, uno de zona
pública y el que cuelga de `/../Statics`—.

De paso desaparece un cálculo que se tiraba: cada llamada evaluaba `self::staticRoute()` —que
recorre el contenedor y hace dos `file_exists()`— para pasarlo a un parámetro que nadie leía.

### 2 · El alta y la edición dejan de decidirse por el cuerpo de la petición

`-actions-add` y `-actions-edit` son rutas distintas con permisos distintos, y la operación debe
coincidir con la ruta.

En los **13 controladores** afectados la operación sale ahora del **nombre de la ruta**, y el
desajuste **se rechaza**:

```php
$isEdit = self::isEditRoute($request);
if ($isEdit !== ($id !== -1)) {
    return self::rejectOperationMismatch($request, $response, $isEdit, $id);
}
```

**Qué cambia para un cliente**: un POST con `id` a una ruta `-actions-add`, o sin `id` a una
`-actions-edit`, ahora responde **HTTP 400** con `success:false` y queda **registrado**. Antes se
atendía. Si tienes un cliente propio que reutilizaba una de las dos rutas para las dos
operaciones, deja de funcionar — **y esa es la intención**.

`BaseController` gana `isEditRoute()` y `rejectOperationMismatch()`; una ruta que llegue a esos
métodos sin declarar su operación **lanza** en vez de elegir.

### 3 · Fuera `CAN_ADD_ALL` y `CAN_VIEW_ALL` de cuatro Mappers

`ImagesRepositoryMapper`, `DocumentsMapper`, `DocumentTypesMapper` y `CategoriesMapper`. Si tu
código las lee, deja de compilar.

**No cambia ningún permiso**: la única condición que las consultaba tenía **las dos ramas
iguales**, así que ya permitía siempre. Se conservan en los otros cuatro mappers que sí las usan
—`ApplicationCalls`, `Organizations`, `InterestResearchAreas` y `Publications`—, donde restringen
por organización de verdad.

### 4 · El bloque de compilación SCSS y su `//TODO` ya no están

Ver la entrada «Eliminado — el compilador de SCSS que no compilaba» más abajo. Si dependías del
método `compileScssServe()` por su nombre, primero pasó a `serveModuleStatic()` y ahora no existe:
el punto 1 de esta lista es tu ruta.

### 5 · El piso de PHP sube a 8.5, y composer deja de depender del binario que lo arranque

```diff
-"php": ">=8.4.1 <8.6"
+"php": ">=8.5 <8.6"
```

**El código debe ser 100 % compatible con 8.5; 8.4 es un extra, no un compromiso.** Si tu despliegue
va por 8.4, se queda en la versión publicada que ya tienes.

Y `src/composer.json` gana **`config.platform.php: "8.5.0"`**. Sin eso, composer resuelve contra el
PHP que lo ejecuta: en la máquina de desarrollo `/usr/bin/composer` arranca con **8.1.34** por su
shebang, tres versiones por debajo del piso declarado, y nadie lo veía porque el `platform_check`
que debería gritar está silenciado por el manejador de errores del propio framework.

**Es el piso de la rama, `8.5.0`, no `8.5.9`**: atarlo al parche que sirve Apache hoy es atarlo a
hoy. Comprobado — `composer diagnose` responde:

```
PHP version: 8.5.0 - Package overridden via config.platform, actual: 8.1.34
```

**Los cuatro paquetes `piecesphp/*` hacen lo mismo en su propia tanda**, y además cierran el techo:
declaraban `<9.0`, o sea un PHP 8.9 que no existe y que nadie ha probado. Ahora `>=8.5 <8.6`, que es
para lo que hay puertas.

### 6 · `piecesphp/database` pasa a v3.9.0

`composer update piecesphp/database`. Trae el arreglo de `humanReadable()`, que devolvía el mapper
entero —con su esquema dentro— en vez del valor declarado en `human_readable_reference_field`.

**Ninguna cifra se movió por esto**: PHPStan sigue en 888, las 18 suites en verde y las 52 entradas
de `ignoreErrors` intactas.

### 7 · Los cuatro paquetes `piecesphp/*` suben de mayor, y el framework los alcanza

```diff
-"piecesphp/database": "^3.1",
-"piecesphp/datastructures": "^3.0",
-"piecesphp/geojson": "^2.0",
-"piecesphp/html": "^2.0",
+"piecesphp/database": "^4.0",
+"piecesphp/datastructures": "^4.0",
+"piecesphp/geojson": "^3.0",
+"piecesphp/html": "^3.0",
```

`database` y `datastructures` van a **v4.0.0**; `html` y `geojson`, a **v3.0.0**. La mayor la
fuerza el punto 5: pasar de `<9.0` a `>=8.5 <8.6` **estrecha** el rango, y estrechar un requisito
de plataforma rompe a quien resolvía por debajo. `html` sube además su propia dependencia a
`piecesphp/datastructures: "^4.0"`, así que las dos ramas de 3.x quedan cerradas a la vez.

**Si tu despliegue va por PHP 8.4, `composer update` no te dará estas versiones y hace bien**: te
deja en las anteriores, que siguen publicadas.

Comprobado tras el `composer update`: el lock trae las cuatro mayores, `composer why
piecesphp/datastructures` muestra las dos exigencias de `^4.0` —la del framework y la de `html`—
resueltas sin conflicto, y **ninguna cifra se movió**: PHPStan en 886, las 21 suites en verde y
`verify-integrity` sin novedad.

### 8 · La aplicación se sirve en `es` y `en`. `/fr/`, `/de/`, `/it/` y `/pt/` dan 404

Decisión del PROPIETARIO. Los cuatro **se comentan en `allowed_langs` y en ningún sitio más**:
eso es el interruptor, y lo que no está ahí no existe. El resto de sus entradas —locale,
formatos, banderas, nombres de idioma, mapas de plugins— **sigue vivo**, porque nadie las pide
si el idioma no está dado de alta. La receta se explica en `.agents/context/08-i18n.md`, no se
deja comentada: el código comentado se pudre y nadie lo actualiza.

**Si tu despliegue servía alguno de los cuatro**, descomenta su línea en `allowed_langs` y ya
está. Solo un idioma NUEVO obliga a recorrer la lista entera del documento.

Lo que se retira de verdad: `src/statics/core/js/translations/{fr,de,it,pt}.js`. El front
ofrecía arranque en idiomas que PHP ya no puede completar. Y
`src/app/lang/dynamic-translations/{fr,de,it,pt}/`, que ya se ignoraba en ejecución:
`add-dynamic-translations.php` recorre las carpetas que existen y solo carga las que estén en
`allowed_langs`.

**MEDIDO**, provocándolo sobre copia guardada y restaurando por `sha256`:

| | comentado | descomentado |
| :-- | :-- | :-- |
| `/fr/` | 404 | 200 |
| banderas del selector | 1 (`gb`) | 2 (`gb`, `fr`) |

`/` sigue en 200 y en español; `/en/` en 200 y en inglés.

---

## `lc_time_names`: un candidato descartado dejó de registrarse como error

`BaseModel` y `BaseEntityMapper` prueban una lista de locales por idioma
—`'es' => ['es_ES', 'es_CO', 'es_MX']`—, se quedan con el primero que la base acepta y
**registraban una excepción por cada uno que descartaban**. En un servidor donde `es_ES` no
exista —MariaDB y varias versiones de MySQL traen listas distintas— el mecanismo funciona
perfectamente y llena el registro de errores mientras funciona. Es el `db-backup` al revés:
aquel reportaba éxito sobre un fallo, este reportaba fallo sobre un éxito.

Ahora solo se registra si fallan TODOS, y entonces **una sola vez**, diciendo el idioma y los
candidatos probados. **MEDIDO** con una petición idéntica y tres candidatos inválidos:

| | entradas en el registro |
| :-- | :-- |
| antes | **6** — tres candidatos × dos clases |
| ahora | **2** — una por clase, nombrando los tres |
| con un candidato válido al final | **0** |

El bloque estaba **duplicado línea por línea** en las dos clases —28 líneas, idénticas salvo de
dónde sale la conexión— y pasa a `PiecesPHP\Core\LcTimeNamesTrait`. No habían derivado
todavía; se unifica antes de que lo hagan.

Queda anotado en `.agents/context/12-convenciones.md` que la interpolación de
`SET lc_time_names = '…'` es **forzada** —`SET` no admite parámetros— y **no es inyectable**:
el idioma solo se usa como clave y el valor sale de la lista blanca de configuración.

---

### 9 · Dos excepciones cambian de nombre: estaban mal escritas

```diff
-PiecesPHP\Core\Validation\Parameters\Exceptions\MissingRequiredParamaterException
+PiecesPHP\Core\Validation\Parameters\Exceptions\MissingRequiredParameterException
-PiecesPHP\Core\Validation\Parameters\Exceptions\ParamaterNotExistsException
+PiecesPHP\Core\Validation\Parameters\Exceptions\ParameterNotExistsException
```

*Paramater* con «a». Se renombran archivo, clase y los 113 usos. **Si tu despliegue tiene un
`catch` de cualquiera de las dos, cámbialo.** Van también dos métodos con la misma errata:
`Parameters::addParamater()` → `addParameter()` y `removeParamater()` → `removeParameter()`.

La misma errata sigue en `src/vendor/php-ffmpeg`, que es de terceros y no se toca.

### 10 · Un parámetro obligatorio que falta ahora da 400, no 500

`Parameters::validate()` lanzaba y nadie lo traducía: cualquier petición sin un parámetro
obligatorio salía como error del servidor. Se traduce en el manejador global de
`src/index.php`, que es donde el framework ya separa 404, 405 y 403 del 500:

```json
400  {"success":false,"error":"MISSING_REQUIRED_PARAMETER","message":"El parámetro query es obligatorio"}
```

**Si tu despliegue detectaba estos casos por el 500**, ahora son 400.

### 11 · Las búsquedas de ubicaciones piden sesión

`locations-{countries,states,cities,points,regions}-ajax-search` pasan a `require_login`. Los
listados `-ajax-all` y `-ajax-all2` **siguen siendo públicos**: sirven APIs públicas. Sin sesión,
las cinco búsquedas redirigen al login.

### 12 · `contact-forms-general` deja de devolver el registro SMTP

La respuesta JSON del formulario de contacto llevaba en `values.logMailer` el registro del envío
cuando este fallaba. **Esa clave ya no existe.** El registro va a `log_exception()`, al registro
de errores del servidor.

`values` trae ahora solo `redirect`, `redirect_to` y `reload`, y `message` sigue diciendo por qué
falló. **Si tu cliente leía `logMailer`, léelo del registro de errores.**

### 13 · El listado de perfiles de `MySpace` deja de mostrar usuarios sin aprobar

La aprobación se exige ya a organizaciones y a usuarios.

**Si tu instalación tiene usuarios sin aprobar de un tipo permitido, desaparecen del listado.** En
la instalación de referencia no había ninguno —2 filas antes y 2 después, medido—, así que ahí no
se nota; en la tuya puede.

### 14 · Las herramientas de desarrollo se instalan siempre desde su lock

Al instalar, `TasksManager` hacía `composer update` en `bin/tools` si ya existía `vendor/`, y eso
ignoraba `bin/tools/composer.lock`, que está versionado: dos clones podían medir con analizadores
distintos. **Ahora hace siempre `install`.**

**Solo te afecta si añadiste o subiste herramientas en `bin/tools/composer.json` sin actualizar su
lock**: dejan de instalarse solas, porque composer instala lo que dice el lock y avisa de que va
desfasado. Actualiza el lock a mano dentro de `bin/tools` y versiónalo.

### 15 · Los listados públicos de publicaciones y banners solo devuelven lo publicado

`publications-ajax-all` y `built-in-banner-ajax-all` devuelven ahora lo publicado a quien
no tiene sesión con permiso, y el estado pedido se ignora.
- **En publicaciones**, el permiso es el tipo de usuario de `PublicationMapper::CAN_VIEW_DRAFT`,
  el mismo criterio que la vista individual.
- **En banners**, poder ver su listado de administración.

Con permiso, todo sigue igual. **Si un cliente sin interfaz leía borradores por esa ruta sin
autenticarse, deja de recibirlos.**

### 16 · El listado de documentos deja de mostrar los inactivos

`/documents/all` filtra ahora por estado y ya no devuelve los documentos inactivos, es decir,
los borrados. **Quien viera documentos borrados en
ese listado deja de verlos.**

### 17 · Los archivos subidos se sirven con la política de su carpeta

Los archivos subidos de los módulos pasan a servirse con la política de su carpeta.
- **Publicaciones:** sus imágenes y adjuntos se sirven sin sesión solo si la publicación está
  publicada y en fecha, con el mismo criterio que su vista pública (`isVisibleToPublic()`). Los
  de borradores, programadas, caducadas o desactivadas piden sesión.
- **Documentos, organizaciones (RUT y logo) y categorías de noticias** piden sesión.
- **Banners y la imagen de inicio** siguen públicos a propósito, declarados en
  `files/dev/upload-dirs.json` con su motivo.
- Los tipos de documento, las categorías de formularios y las aprobaciones tenían una carpeta de
  subidas que nada usaba: se retiró.

**Si una vista pública tuya enlaza archivos de esos módulos, sin sesión darán 403.** Declara esa
carpeta como pública, con su motivo, o dale un validador como el de publicaciones.

### 18 · `saveGroup` de traducciones responde 410: usa `translateGroup`, y recompila el JS

El guardado de traducciones por texto libre se retira.
- **`saveGroup` responde 410 y no escribe nada.**
- **Lo sustituye `translateGroup`** (solo POST). Recibe el idioma, el grupo y las claves que
  faltan, NUNCA sus textos.
  - El servidor pide la traducción a la IA y guarda solo lo que conserva las mismas etiquetas
    HTML que la clave y no trae nada ejecutable.
  - Nunca sobrescribe una traducción existente.
  - Topes: 100 claves por grupo y 1.400 caracteres por clave.
- **Recompila el JS al actualizar:** `cd src && gulp js-vendor`. `configurations.min.js` no se
  versiona. Si no recompilas, el navegador seguirá llamando a `saveGroup`, recibirá 410 y la
  traducción automática no guardará nada. La página no se rompe.
- **Si tu proyecto llamaba a `saveGroup` desde su propio JS**, cámbialo por `translateGroup` con
  las claves.

### 19 · Los administradores de organización entran a Aprobaciones

- Hasta ahora, Aprobaciones solo admitía a root, al administrador general y al institucional.
- **Ahora también entra el administrador de organización (tipo 12)**, con alcance limitado en
  el SERVIDOR:
  - solo ve y resuelve lo creado por usuarios de su organización, y solo si él es el
    administrador de esa organización;
  - nunca lo suyo propio.
- El formulario y la acción de aprobar responden 404 fuera de ese alcance, aunque se llamen a
  mano.
- **Si tu proyecto daba por hecho que el tipo 12 no entraba**, revisa tus menús: la ruta ya lo
  admite.
- Además, el administrador de organización solo resuelve lo PENDIENTE: lo ya aprobado o
  rechazado no lo puede cambiar. El correo al autor solo se envía cuando el estado cambia.

### 20 · Una publicación sin aprobar se oculta al público

- Con el módulo de aprobaciones activo, sin sesión, la página de una publicación pendiente de aprobar da 404 y sus
  archivos 403.
- Quien tiene sesión y permiso de vista previa (`CAN_VIEW_DRAFT`) la sigue viendo, igual que
  un borrador.
- Si las aprobaciones están apagadas, nada cambia.
- `PublicationMapper::isVisibleToPublic()` exige ahora la aprobación a través de
  `isApprovedForPublic()`. **Si tu proyecto la usaba**, ten en cuenta que puede dar `false`
  donde antes daba `true`.

### 21 · El cron recupera lo que falla, y su ruta web exige clave

- **Antes:** una tarea programada solo corría si el crontab pasaba justo en su minuto. Si
  fallaba, o si el servidor llegaba tarde, esa ejecución se perdía. Con el crontab que
  documentaba la ruta (`0 * * * *`), una tarea a las 00:10 no corría nunca.
- **Ahora:** `onMinute`, `hourly`, `dailyAt` y `weeklyOn` tienen FRANJAS.
  - La tarea corre si su última hora programada no ha salido bien todavía, dentro de una ventana
    de recuperación (60 minutos por defecto) y hasta 3 intentos por franja.
  - Nunca corre dos veces a la vez: hay un bloqueo por tarea.
  - Nunca repite días atrasados en cadena.
  - El estado de cada tarea queda en `app/cache/cronjobs/`; se consulta con
    `bin/cli cronjobs-status`.
  - Por tarea: `recoveryWindow(int $minutos)` y `maxAttempts(int $n)`.
- **Configura el crontab cada minuto:** `* * * * *`. Una tarea programada solo con `when()`, sin
  método de programación, se comporta como antes.
- **Cambio de conducta visible:** las tareas diarias, incluido el respaldo de la base, se
  REINTENTAN hasta 3 veces en la hora siguiente si fallan.
- **La ruta `core/api/cron-jobs/run` falla cerrada:** si `CronJobKey` no está configurada
  (`secure-keys/cronjob`), responde 403 siempre. Usa la cabecera `Cron-Job-Key`.
- La respuesta de la ruta ya no incluye detalle interno cuando una tarea falla.

### 22 · Los archivos protegidos se deciden por su NOMBRE (`.protected`), y el subsistema vive en `Core/Statics`

- **Por qué:** bajo HestiaCP, nginx no lee el `.htaccess`, así que la protección por carpeta no
  alcanza a las subidas.
- **Ahora:**
  - un archivo PRIVADO se guarda en disco con el sufijo AL FINAL: `foto.jpg.protected`. Su URL
    no cambia (`…/foto.jpg`);
  - quien pide `foto.jpg` y no existe, llega a PHP. Si existe `foto.jpg.protected`, se valida
    con la política de su carpeta y se sirve con el tipo de `foto.jpg`;
  - pedir el nombre de disco (`….protected`) da 403 o 404 siempre: lo niega
    `statics/uploads/.htaccess`, que escribe el subsistema;
  - lo PÚBLICO lleva su nombre real y lo sirven Apache o nginx directamente, sin PHP.
- **`ServerStatics` y `ProtectFileMiddleware` viven en `PiecesPHP\Core\Statics`.** Los nombres
  viejos siguen funcionando con `class_alias`. Cambia tus `use` cuando puedas.
- **La política de cada carpeta:**
  - `ProtectFileMiddleware::protectWithSession()`: se sirve solo con sesión;
  - `protect(…, validador)`: decide un validador;
  - lo que no se declara, es público.

  El sufijo se configura en `protected_uploads_suffix` (por defecto `.protected`).
- **Solo se comprime el texto** (css, js, json, csv, svg, txt, html, xml, map). PDF, imágenes,
  audio, vídeo y fuentes no se comprimen, así que van en streaming y admiten `Range`.
- **Las subidas de documentos, organizaciones y categorías de noticias nacen protegidas.**
  La carpeta de cada publicación se protege o se libera con su visibilidad: al crear, al editar,
  al aprobar o rechazar, y por fecha, con una tarea del cron.
- **`protect()` sin validador falla cerrado.** Ya no escribe un `.htaccess` en cada carpeta.
- **`src/.htaccess` solo comprime texto:** ya no comprime imágenes, PDF, audio, vídeo ni
  fuentes.
- **MIGRA tu instalación tras actualizar**, con una copia de `src/statics/uploads` hecha antes:

  ```bash
  bin/cli statics-protect-migrate            # simulacro
  bin/cli statics-protect-migrate --run      # primero renombra lo privado, después retira los .htaccess viejos
  bin/cli statics-protect-migrate --revert   # vuelta atrás, si hiciera falta
  ```

  **Sin migrar,** los archivos privados existentes siguen con su nombre público y sin la protección nueva. `--revert` deja también con su nombre
  público lo que ya nació privado antes de migrar.
- Guía completa: `source-docs/project/docs/piecesphp/new-features/protected-files.md`.
- **Si sirves con nginx sin Apache detrás,** añade una regla que niegue `\.protected$` y deje
  pasar a PHP lo que no existe.
- Al reemplazar la imagen o un adjunto de una publicación, el archivo nuevo va a la carpeta de su
  publicación. Los que ya estaban sueltos siguen la visibilidad de su publicación.

### 23 · OTP: límite de intentos y respuesta que no revela si el usuario existe

- **Límite de intentos:** `generate-otp`, `check-totp`, `two-factor-auth-status` y el segundo
  factor del login bloquean tras 5 fallos por usuario o 20 por IP en 15 minutos, con **429** y
  `Retry-After`.
- Todo se configura en `$config['otp_security']` (`config.php`):
  - `maxFailuresPerUser`;
  - `maxFailuresPerIP`;
  - `windowMinutes`;
  - `lockMinutes`;
  - `uniformResponse`;
  - `oneUseCodeMinutes`.
- **`generate-otp` ya no dice si el usuario existe:** responde siempre «Si el usuario existe,
  recibirá un código en su correo.». Con `uniformResponse = false` vuelve a responder como antes.
  **Si tu app leía el error `USER_NO_EXISTS`, ya no llega.**
- **Detrás de un proxy o de un balanceador,** todas las peticiones pueden llegar con la misma IP
  (`REMOTE_ADDR`), y el límite por IP bloquearía a todos a la vez. En ese caso, sube
  `maxFailuresPerIP`.
- `two-factor-auth-status` sigue diciendo si un usuario tiene activo el segundo factor, porque la
  interfaz lo necesita, pero ahora tiene límite de ritmo.

### 24 · Los enlaces de token llevan un selector opaco, y las claves salen de `app_key`

- **Ahora:**
  - la URL lleva un **selector opaco** (`random_bytes(16)` en hexadecimal), guardado en la
    columna nueva `selector`;
  - se busca por selector **y** por tipo, y solo se borra lo que es suyo;
  - un selector que no casa da 404, sin tocar nada.
- **Los enlaces genéricos ya emitidos dejan de valer.** Los de recuperación de contraseña siguen.
- **AÑADE LA COLUMNA al actualizar:**

  ```sql
  ALTER TABLE `pcsphp_tokens`
    ADD COLUMN `selector` varchar(32) DEFAULT NULL AFTER `type`,
    ADD UNIQUE KEY `selector` (`selector`);
  ```

- **`GenericTokenController::KEY_JWT` y `TokenModel::KEY_BASE_JWT` desaparecen.** Ahora las claves se derivan de
  `app_key` con `hash_hmac`, una por uso. **Si tu proyecto las usaba, cámbialas por
  `Config::app_key_derived('<uso>')`.**
- **Aviso de `app_key`:** si sigue con el valor de ejemplo (vacío o empezando por `TODO`), el
  framework lo apunta en el log una vez al día y lo muestra en el panel a root y a los
  administradores generales. **No impide arrancar.**
  - En el panel es un aviso flotante abajo (`nag` de Fomantic-UI), que no desplaza la
    maquetación. Se puede **descartar**, y el descarte se recuerda 7 días en ese navegador; si
    la clave sigue siendo la de relleno, vuelve.
  - Se puede **ocultar del todo** en Configuración → Seguridad e IA
    (`$config['hide_app_key_warning']`, `false` por defecto). Ocultarlo **no** apaga el registro
    diario del log. Un `config.php` que no tenga la clave deja el aviso encendido.
  - `bin/cli generate-app-key` imprime una clave nueva para pegarla en `config.php`.
  - **Cambiar `app_key` cierra todas las sesiones abiertas e invalida los tokens**, porque con
    ella se firman.

### 25 · Dos comprobaciones fallan cerrado

Si tu proyecto se apoyaba en el comportamiento anterior, ahora recibe un «no» o una excepción:
es el cambio que se busca, pero avisa a tus desarrolladores.

- **`Roles::addPermission()` con `IDENTIFIER_TYPE_CODE` y un identificador que NO es numérico
  LANZA `RoleNotExistsException`.** Un código numérico, entero o como cadena (`770001` y
  `'770001'`), sigue funcionando igual.
- **`UploadedFileAdapter::validate()` devuelve `false` cuando no se subió ningún archivo**, igual
  que `FileUpload::validate()`. Quien preguntaba antes por `hasInput()` no nota nada.

Las dos tienen ahora prueba de rechazo: si alguien las vuelve a abrir, la suite falla.

### 26 · Se retira el creador de avatares; la foto de perfil se queda

El creador de «muñequitos» (cabello, ojos, ropa…) no se dibujaba en ninguna vista desde hacía años:
`.avatar-component` no tiene ni ha tenido productor en PHP ni en HTML. Aun así se cargaba en tres
formularios de usuario y seguía sirviendo su catálogo.

- **Desaparece la ruta `avatars`** (`GET /avatars/get`) y su entrada en `roles.php`. Quien la pida
  recibe 404.
- **Desaparecen** `src/statics/images/avatares/` (162 imágenes), `src/statics/features/avatars/`
  (`avatar.js`, `canvg.min.js` y su SCSS), `AvatarController::avatar()` y
  `AvatarController::listFiles()`, `configAvatar()` de `users-forms.js`, y su compilación en
  `gulpfile.js`, con la tarea `sass-compile-avatars`.
- **La foto de perfil no cambia:** `AvatarModel`, `AvatarController::register()` y la ruta
  `push-avatars` siguen.
- **Si tu proyecto usaba el creador** en una vista propia (`.avatar-component`), deja de funcionar y no
  tiene sustituto.

### 27 · El importador de usuarios solo crea usuarios generales

- **Una fila con la columna `type` distinta del tipo general se rechaza**, con su mensaje, y no se importa.
- **Una fila con la columna `id` que no sea vacía o un entero positivo se rechaza.**
- Sin la columna `type`, las filas se siguen importando como usuarios generales, como antes.
- **Si tu proyecto importaba administradores o roots con este importador**, deja de poder hacerlo: se crean
  por el formulario de usuarios.

### 28 · Los destinatarios del formulario de contacto y de «otros problemas» salen de la configuración

- **`ContactFormsController::RECIPIENTS_MESSAGES` y `UserProblemsController::EMAIL_ON_FAILED_OS_TICKET`
  desaparecen.** Llevaban una dirección escrita en el código.
- **Los destinatarios pasan a `src/app/config/config.php`**, como lista de direcciones:
  `$config['contact_form_recipients']` (formulario de contacto) y `$config['other_problems_recipients']`
  (el correo de «otros problemas» cuando osTicket no está configurado o no responde). **Vienen vacías.**
- **Sin destinatarios válidos no se envía el correo.** Una lista vacía, una clave que no es una lista o una
  lista con alguna dirección inválida cuentan igual. El formulario de contacto responde con el error
  genérico; «otros problemas», si osTicket tampoco recibió el mensaje, responde que no pudo enviarlo. Cada
  intento deja en el log una línea que nombra la clave.
- **La respuesta de fallo de «otros problemas» ya no lleva `extra`.**
- **Qué hacer:** si tu proyecto usa cualquiera de los dos formularios, pon sus destinatarios en esas claves.
  Si leías las constantes desde código propio, lee la configuración.

### 29 · La recuperación de contraseña es un código ligado al usuario, con límite de intentos

- **El enlace del correo ya no cambia la contraseña.** Lleva al formulario de recuperación con el código y el
  correo ya puestos. `GET /users/recovery/{url_token}` (`new-password-create`) no escribe nada: redirige al
  formulario, así que un enlace viejo lleva allí y se pide un código nuevo.
- **`POST /users/recovery` (`recovery-password-request`) hace lo mismo que `POST /users/recovery-code`**:
  guarda un código y envía el correo con el código y el enlace.
- **Verificar y usar un código exige `username`** (nombre de usuario o correo) además de `code`:
  `POST /users/verify-create-password-code`, `POST /users/create-password-code` y la acción
  `change-password-code` de la API. Esas rutas siguen aceptando exactamente sus parámetros: sin `username`,
  responden `MISSING_OR_UNEXPECTED_PARAMS`.
- **Tras varios fallos responden 429** con `Retry-After`. Es el límite de `otp_security`, compartido con el
  OTP: por defecto, cinco fallos por usuario o veinte por IP en 15 minutos bloquean 15.
- **Ninguna respuesta devuelve el usuario**: desaparece `user` de `create-password-code` (y de la API
  `change-password-code`) y `userName` de `verify-create-password-code`.
- **Las peticiones de código responden igual exista o no el usuario** (`recovery`, `recovery-code` y la API
  `recovery-password`): `send_mail` es siempre `true` y el mensaje, «Si el usuario existe, recibirá un código en
  su correo.». Ya no devuelven `USER_NO_EXISTS`.
- **Se retiran** `RecoveryPasswordController::mailRecoveryPassword()` y `mailNewPassword()`, las plantillas
  `usuarios/mail/recovery_password.php` y `usuarios/mail/restored_password.php`, y
  `RecoveryPasswordModel::exist()`, `getUserNameByCode()` e `instanceByCode()`.
- **Qué hacer:** si tu app o tu JS llaman a estas rutas, envía `username`, no esperes `user`, `userName` ni
  `USER_NO_EXISTS`, y trata el 429. Si personalizaste una plantilla retirada, pasa lo que necesites a
  `usuarios/mail/recovery_password_code.php`.

### 30 · El texto se guarda tal cual: `piecesphp/database` ^5.0, y fuera las compensaciones del escape

- **El framework pasa a `piecesphp/database` ^5.0.** Hasta la 4.1.0, el ORM aplicaba `stripslashes()` y
  `addslashes()` a todo campo de texto al guardar: **borraba las barras invertidas legítimas** (`C:\ruta` se
  guardaba `C:ruta`) y dejaba `O\'Brien` en la columna. Desde la 5.0.0 el texto se guarda y se lee exactamente
  como se asignó. Detalle en el `CHANGELOG` del paquete.
- **Se retiran las diez compensaciones del framework**, que ahora borrarían barras que sí se guardan:
  - los listados de ciudades, países, puntos y estados de Locations (`stripslashes` del nombre);
  - el listado de usuarios (nombres, apellidos y usuario) y el de intentos de acceso (el mensaje);
  - el nombre y el correo ocultos de la cabecera del panel;
  - la tarjeta de noticias (`News/Views/news/public/util/item.php`), que borraba TODAS las barras del contenido;
  - el modal de noticias del panel (`statics/core/js/configurations.js`), que hacía lo mismo.
- **Lo ya guardado sigue escapado**: un `O\'Brien` de antes se verá con su barra en los listados que leen filas
  crudas, y las barras que se perdieron al guardar no vuelven.
- **El límite de intentos del OTP ya cuenta los nombres con comilla** en los intentos nuevos: antes el nombre se
  guardaba escapado y no coincidía con el que se comparaba.
- **Qué hacer:**
  - quita los `stripslashes()` (o `str_replace` de barras) con los que tu código compensaba el escape;
  - recompila el JS: `cd src && gulp js-vendor`, o el modal de noticias seguirá borrando barras;
  - **para recuperar lo ya guardado**, justo después de actualizar y antes de que nadie escriba:
    `bin/cli db-backup`, luego `bin/cli repair-escaped-text` (solo cuenta, por columna) y, si lo que cuenta es lo
    esperado, `bin/cli repair-escaped-text apply=yes`. Aplica un `stripslashes()` único a los campos de texto de los
    mappers. **Se niega sin un respaldo de la última hora, y a correr dos veces en la misma base.** No distingue una
    barra legítima guardada después de actualizar, ni una fila insertada con SQL propio, que nunca estuvo escapada:
    por eso va antes de que se escriba nada, y solo una vez;
  - si tu proyecto usaba `piecesphp/database` ^4 en otro `composer.json`, súbelo a ^5.

### 31 · Se retira el importador viejo: la importación y la exportación pasan a `DataTransfer`

El motor nuevo (`PiecesPHP\Core\DataTransfer`), el panel «Importar y exportar» y la importación por terminal
sustituyen a lo viejo. Guía: `source-docs/project/docs/piecesphp/new-features/data-transfer.md`.

- **Desaparece `PiecesPHP\Core\Importer\*`.** Un importador propio se reescribe como `ImportDefinition`
  (receta en la guía) y se registra con `DataImportExportUtilityRoutes::importer()`.
- **Mueren el módulo `Importers`, la constante `IMPORTS_MODULE_ENABLED`, el grupo de rutas `/importers` y las
  rutas `importer-form`, `importer-action` e `importer-template`.** Las nuevas son `data-transfer-import-<clave>`,
  `-action` y `-template`, y se encienden con `DATA_IMPORT_EXPORT_MODULE`. Si tu `roles.php` o tus vistas citaban
  `importer-*`, cámbialos.
- **La respuesta JSON de una importación cambia de forma**, y sus mensajes van en texto, sin HTML.
- **Un archivo se importa entero o no se importa.** Antes se guardaba fila a fila y un error a mitad dejaba medio
  archivo dentro.
- **Solo XLSX y CSV**, con tamaño y número de filas máximos por definición.
- **Desaparece la importación exógena por GET** (`ODS_Usuarios`) y las fichas de credenciales que dejaba. La sustituye `bin/cli data-transfer-import`, que escribe las credenciales generadas en un archivo
  fuera del proyecto, con permisos `0600`.
- **El export de usuarios tiene otras columnas**: las del importador, sin las cuatro vacías que venían de otro
  proyecto. Un archivo exportado se puede volver a importar.
- **Desaparecen `BaseExportData` y `UsersExporter`** (`DataImportExportUtility\Controllers\ExportHandlers`). Si tu
  proyecto tiene exportadores propios que los extienden, pásalos a `ExportDefinition`: la guía
  `source-docs/project/docs/piecesphp/new-features/data-transfer/migrar-desde-baseexportdata.md` lo explica paso a paso.
  El motor nuevo trae filtros validados, columnas con tipo (un importe se puede sumar), varias hojas, totales, logo,
  estilo de informe, vista previa y filtros guardados.
- **Un importador propio acepta solo XLSX** salvo que declare otros formatos en `acceptedExtensions()`; la plantilla
  descargable es XLSX.
- **Por defecto solo se importan usuarios generales**, y nadie importa un tipo con prioridad igual o mayor que la
  suya. Ni el `id` ni un `type` de administrador se toman del archivo.
- **Qué hacer:** reescribe tus importadores como `ImportDefinition`; cambia `IMPORTS_MODULE_ENABLED` por
  `DATA_IMPORT_EXPORT_MODULE` en tu `constants.php`; y si usabas la importación exógena, pásala a la terminal.

### 37 · La configuración del panel cambia de direcciones: doce rutas se renombran y «Seguridad e IA» se parte en dos

**Un enlace guardado a una pantalla de configuración deja de valer, y un permiso asignado por su nombre de
ruta, también.** No queda ningún alias: las direcciones antiguas responden 404.

El panel tenía la configuración en dos grupos de nombre casi igual, y la entrada «Colores» era en realidad la
raíz de todo. Ahora hay **tres grupos** —Apariencia, Integraciones y Sistema—, el nombre de cada ruta dice su
grupo (`configurations-<grupo>-<pantalla>`) y la dirección va en paralelo
(`/configurations/<grupo>/<pantalla>/`).

| Antes | Ahora | Dirección |
| :-- | :-- | :-- |
| `configurations-generals` (la raíz, «Colores») | `configurations-index` | `/configurations/` |
| | `configurations-appearance-colors` | `/configurations/appearance/colors/` |
| `configurations-logos-favicons` | `configurations-appearance-brand-images` | `/configurations/appearance/brand-images/` |
| `configurations-backgrounds` | `configurations-appearance-backgrounds` | `/configurations/appearance/backgrounds/` |
| `configurations-seo` | `configurations-appearance-seo` | `/configurations/appearance/seo/` |
| `configurations-email` | `configurations-integrations-mail` | `/configurations/integrations/mail/` |
| `configurations-os-ticket` | `configurations-integrations-osticket` | `/configurations/integrations/osticket/` |
| `configurations-mapbox-key` | `configurations-integrations-mapbox-key` | `/configurations/integrations/mapbox-key/` |
| `configurations-security-and-ia` | `configurations-system-security` | `/configurations/system/security/` |
| | `configurations-integrations-ai` | `/configurations/integrations/ai/` |
| `configurations-routes` | `configurations-system-routes` | `/configurations/system/routes/` |
| `configurations-generals-generic-action` | `configurations-generic-save` | `/configurations/generic-save/` |
| `configurations-generals-cache-clean` | `configurations-system-cache-clean` | `/configurations/system/cache-clean/` |
| `configurations-generals-sitemap-create` | `configurations-appearance-seo-sitemap` | `/configurations/appearance/seo/sitemap/` |

**Ningún tipo de usuario gana ni pierde acceso**: cada ruta nueva la abren exactamente los mismos que abrían
la antigua, y las dos pantallas que salen de «Seguridad e IA» heredan su permiso.

**Lo que tienes que revisar en un clon:**

- **Llamadas por sufijo**: `SettingsController::routeName('seo')` pasa a `routeName('appearance-seo')`, y así
  con los doce.
- **Direcciones escritas a mano en JavaScript.** El propio framework tenía seis: los mapas pedían la clave
  con `fetch('configurations/mapbox-key')`, que ahora es `fetch('configurations/integrations/mapbox-key')`.
- **Listas de rutas por nombre.** Si restringes o amplías permisos nombrando una de estas rutas —en
  `roles.php` o en una lista propia—, el nombre antiguo deja de casar **sin dar error**: ese usuario pierde la
  ruta en silencio.
- **Archivos renombrados**: la vista, la hoja y el script `security-and-ia` pasan a ser `security` y `ai`.
  Hay que **volver a compilar el SASS** del núcleo.

**La raíz vuelve a ser una puerta.** `/configurations/` ya no pinta ningún ajuste: lista los tres grupos y,
en cada uno, las pantallas que quien mira puede abrir. Los colores tienen su propia pantalla.

**El menú, en tres grupos y sin acciones:**

| Grupo | Entradas |
| :-- | :-- |
| Apariencia | Imágenes de marca · Fondos · Colores · Identidad y SEO |
| Integraciones | Correo · OsTicket · Inteligencia artificial |
| Sistema | Avisos del sistema · Estado y cachés · Sitio en mantenimiento · Rutas y permisos · Seguridad |

- **Cuatro rótulos cambian**: «Mantenimiento» pasa a **«Estado y cachés»** —el menú y el título de la
  pantalla—, para que deje de confundirse con la que apaga el sitio; «Email SMTP» pasa a **«Correo»**;
  «Ajustes SEO», a **«Identidad y SEO»**; y «Personalización de fondos», a **«Fondos»**.
- **El menú ya no ejecuta nada.** «Limpiar caché» se queda donde ya estaba, en «Estado y cachés»; y
  **«Actualizar sitemap» pasa a la pantalla de «Identidad y SEO»**, como un botón con confirmación que solo ve quien puede
  regenerarlo.
- El menú y el índice salen de la misma lista, `SettingsController::panelGroups()`: si añades una pantalla de
  configuración, añádela ahí y aparece en los dos.
- Cada pantalla dice su grupo bajo el título.

### 36 · Ninguna columna de la base lleva ya guion bajo: nueve cambian de nombre

Las columnas de este esquema van en camelCase y ganaban 64 a 8; estas nueve llevaban guion bajo desde
el principio, salvo la última, que entró en esta misma campaña. Cambia la **forma**, no el
vocabulario.

| Tabla | Antes | Ahora |
| :-- | :-- | :-- |
| `login_attempts` | `user_id` | `userID` |
| `login_attempts` | `username_attempt` | `usernameAttempt` |
| `login_attempts` | `extra_data` | `extraData` |
| `time_on_platform` | `user_id` | `userID` |
| `pcsphp_users` | `first_lastname` | `firstLastname` |
| `pcsphp_users` | `second_lastname` | `secondLastname` |
| `pcsphp_users` | `failed_attempts` | `failedAttempts` |
| `pcsphp_users` | `created_at` | `createdAt` |
| `pcsphp_users` | `modified_at` | `modifiedAt` |
| `pcsphp_users` | `sessions_valid_from` | `sessionsValidFrom` |

Y con ellas los dos índices que se llamaban `user_id`, que pasan a `userID`. Los `*_ibfk_N` los
genera MySQL y no se tocan.

**Si tu proyecto consulta esas tablas con SQL propio, en una vista suya o desde su JavaScript, deja
de encontrar esas columnas.** Las migraciones están en `databases/actualizaciones/`
(`2026-09-26-columnas-a-camelcase-intentos-y-tiempo.sql` y
`2026-09-26-columnas-a-camelcase-usuarios.sql`) y **no se pueden aplicar con la aplicación en
marcha**: `pcsphp_users` la lee cada petición con sesión, así que entre el `ALTER` y el despliegue del
código no entra nadie. Conviene hacerlo con el sitio en mantenimiento.

**Lo que NO cambia, y es deliberado:**

- **Los nombres de PARÁMETRO de las peticiones.** El formulario de usuarios sigue mandando
  `first_lastname` y `second_lastname`, y el temporizador, el avatar y la API siguen esperando
  `user_id`. Renombrar una columna no es cambiar un contrato de entrada.
- **Las cabeceras de CSV** de la importación y la exportación de usuarios, por la misma razón: son la
  hoja de cálculo de quien importa.
- **Los alias de `UserDataPackage`**, que ya existían antes de esta campaña.

Desde aquí, `bin/cli verify-integrity` falla si el esquema gana una columna o un índice con guion
bajo que no esté declarado.

### 35 · Fuera de local, un error interno ya no enseña su detalle: responde con un código de referencia

- **Ahora**, en producción, responde con estado 500 y solo un mensaje genérico con un **código de referencia**
  (`ERR-AAAAMMDD-XXXXXX`). En JSON: `success`, `message` y `reference`, nada más. En local se ve todo, más la referencia.
- El código queda en `logs/error.log.json` (campo `reference`) y en `logs/error.plain.log`, cuya línea añade
  `[ref ERR-…]` tras la clase: **si algo tuyo lee ese archivo por posiciones, ajústalo.**
- `log_exception()` devuelve ahora el código (antes, nada). Nuevo:
  `CustomSlimErrorHandler::genericMessage($reference)`.
- **Qué hacer:** si una aplicación o un cliente de la API leía `detail` en las respuestas 500, deja de venir fuera de
  local. Para investigar, busca la referencia en los logs.
- **Lo mismo en los controladores:** 67 sitios que respondían con el mensaje de una excepción interna (errores de la
  base, del ORM…) responden ahora con el mensaje genérico y su referencia. Ejemplo: un alta con un dato demasiado largo
  decía «SQLSTATE[22001]: … Data too long…»; ahora, «Ocurrió un error interno. Si lo reporta, indique la referencia …».
- **Lo que sigue diciéndose tal cual:**
  - los errores de parámetros (`MissingRequiredParameterException`, `InvalidParameterValueException` y, en el alta y la
    edición de usuarios y en la configuración genérica, `ParsedValueException`): dicen qué parámetro falla;
  - los mensajes pensados para el usuario (`SafeException`, `DuplicateException`, `ExportParameterException`). Al borrar
    en Banner, Newsletter, News, Organizations, Documents, tipos de documento, categorías de formularios y Publications,
    «No pudo conectarse a la base de datos» sigue saliendo; cualquier otro fallo del borrado da el genérico. En los
    comentarios por token, el token caducado o inexistente sigue diciendo «El recurso al que intenta acceder ha
    expirado o ya ha sido utilizado.».
- **Configuración** (mapa del sitio y sello de estáticos): la clave `exception` de la respuesta lleva `reference`;
  `file`, `line` y `code` solo en local.
- **Para quien desarrolla:** `bin/cli verify-integrity` falla si el mensaje de una excepción llega a una respuesta sin
  estar declarado en `files/dev/exception-message-declared.json`. Lo correcto casi siempre es
  `CustomSlimErrorHandler::genericMessage(log_exception($e))`; un mensaje para el usuario se lanza como `SafeException`.

### 34 · El entorno sale de la configuración

- **`is_local()` ya no depende de la cabecera `Host`** de la petición.
- **Ahora lo decide `src/app/config/environment.php`** (no versionado; plantilla `environment.example.php`), que devuelve
  `'local'` o `'production'`. **Sin él, o con otro valor, es producción.**
- **Qué hacer al actualizar:** `cp src/app/config/environment.example.php src/app/config/environment.php` y pon
  `return 'local';` en tu instalación de desarrollo. Si no, funcionará como producción: usará las credenciales de
  producción de `database.php` (en una máquina de desarrollo, lo normal es que no conecte y todo dé 500), ocultará rutas
  y trazas en los errores y no escribirá deprecaciones ni `debug_sql`.
- Root lo ve en «Avisos del sistema» («El entorno no está configurado…»).
- La terminal no cambia: `bin/cli` es local con `--local`.
- Nuevo: `app_environment()` devuelve `'local'` o `'production'`; `get_config('environment')`, igual.

### 33 · Los listados no envían su SQL ni sus filas crudas, y el total respeta el alcance

Los ayudantes de `DataTablesHelper` que sirven las tablas del panel:

- **La respuesta ya no trae `SQL_MAIN_EXECUTED`, `SQL_FILTER_COUNT_EXECUTED` ni `SQL_TOTAL_COUNT_EXECUTED`.** Para depurar en local: la opción `'debug_sql' => true`, que escribe en
  `logs/datatables-sql.log` (solo con `is_local()`). En el servidor: `DataTablesHelper::lastExecutedSQL()`.
- **La respuesta ya no trae `rawData`** con las filas crudas de la base, todas las columnas seleccionadas incluidas. Se
  leen en el servidor con `DataTablesHelper::rawRows($result)`. Un listado de tarjetas
  (`dataTablesServerProccesingOnCards`) pone él su HTML en `rawData`, como hacen `UsersController`,
  `DocumentsController::dataTablesExplorer` y `PublicationsCategoryController`.
- **`recordsTotal` cuenta con los filtros fijos del listado** (`where_string|where_segment|having_string|having_segment`,
  con su `group_string`) y sin la búsqueda ni los filtros por columna. Antes contaba la tabla entera.
  `recordsFiltered` no cambia. `recordsTotal` es ahora `int`.
- **Aprobaciones:** el desplegable «Tipo de contenido» sale de los tipos registrados, no de los valores de la tabla.
- **Qué hacer:** si tu JavaScript leía `SQL_*` o `rawData` de una respuesta de DataTables, deja de hacerlo o pásalo al
  servidor con `rawRows()`. Si un listado propio mete su alcance en `config_result_model`, muévelo a `where_*` o
  `having_*`: ni `recordsTotal` ni `recordsFiltered` lo ven.

### 32 · Los controladores del sistema siguen el estándar de rutas: 28 nombres cambian, ninguna URL

`UsersController`, `LoginAttemptsController`, `AdminPanelController`, `GenericTokenController` y `TimerController`
adoptan `ControllerRoutingTrait` con un prefijo por controlador (`users`, `login-attempts`, `admin`, `generic-token`,
`timing`), como el resto de módulos. **Las URL no cambian**: los enlaces de los correos ya enviados, los tokens y el
JavaScript siguen valiendo. **Cambian los nombres**, que son los permisos:

| Antes | Ahora |
| :-- | :-- |
| `recovery-form` | `users-recovery-form` |
| `new-password-create` | `users-new-password-create` |
| `user-forget-form` | `users-forget-form` |
| `user-blocked-form` | `users-blocked-form` |
| `other-problems-form` | `users-other-problems-form` |
| `user-problems-list` | `users-problems-list` |
| `login-request` | `users-login-request` |
| `verify-login-request` | `users-verify-login-request` |
| `delete-account-request` | `users-delete-account-request` |
| `register-request` | `users-register-request` |
| `user-edit-request` | `users-edit-request` |
| `recovery-password-request` | `users-recovery-password-request` |
| `recovery-password-request-code` | `users-recovery-password-request-code` |
| `new-password-create-code` | `users-new-password-create-code` |
| `new-password-verify-code` | `users-new-password-verify-code` |
| `user-forget-request-code` | `users-forget-request-code` |
| `user-blocked-request-code` | `users-blocked-request-code` |
| `user-forget-get` | `users-forget-get` |
| `user-blocked-resolve` | `users-blocked-resolve` |
| `other-problems-send` | `users-other-problems-send` |
| `informes-acceso` | `login-attempts-reports` |
| `attempts-export` | `login-attempts-export-attempts` |
| `not-logged-export` | `login-attempts-export-not-logged` |
| `logged-export` | `login-attempts-export-logged` |
| `informes-acceso-ajax` | `login-attempts-reports-ajax` |
| `about-framework` | `admin-about-framework` |
| `cropper-testing` | `admin-cropper-testing` |
| `tickets-create` | `admin-tickets-create` |

- **`config/roles.php` no cambia**: los nombres que cita ya llevaban el prefijo.
- **Un usuario pendiente de aprobación** conserva la edición de su perfil, ahora por su nombre exacto
  (`users-edit-request`), en lugar de por cualquier ruta que empiece por `user-`. Si tu proyecto tenía rutas propias
  `user-*` que un usuario no aprobado debía poder usar, añádelas a esa lista en `SystemApprovalsMiddleware`.
- **Qué hacer:** busca en tu proyecto (roles, vistas, JavaScript y módulos propios) cada nombre de la columna «Antes» y
  cámbialo por el de «Ahora». `get_route('recovery-form')` pasa a ser `UsersController::routeName('recovery-form')`.
- **`get_route()` con un nombre escrito a mano queda vetado.** `bin/cli verify-integrity` falla si encuentra uno fuera de
  las excepciones de `files/dev/get-route-direct-allowed.json`. Usa `Controlador::routeName('sufijo', $params)`. Ojo:
  `routeName()` devuelve `''` a quien no tiene permiso para una ruta protegida, así que un enlace de una pieza
  compartida (barra superior, menú) se pinta solo si no es `''`. En una ruta pública nunca da `''`.

---

## ⚠ Corregido — la recuperación de contraseña se endurece

- **El código de recuperación** pasa a tener límite de intentos, ligadura al usuario y una caducidad más corta, y la
  respuesta ya no devuelve datos del usuario.
- **El enlace del correo** entrega una contraseña que el usuario puede leer, y deja de escribir al abrirse.
- **Las peticiones ya no dicen si un usuario existe**, y el registro de tickets no guarda el código en claro.
- **Tras actualizar, vacía `pcsphp_recovery_password`**: los códigos pendientes de antes valen hasta que caducan,
  y los que quedaron en el registro de tickets los puede leer quien vea ese registro.
- Ruptura 29. Probado en `unit-tests:core/password-recovery-guards`.

## ⚠ Corregido — el importador de usuarios valida más

- **El importador valida más columnas del archivo** (ruptura 27).
- Probado en `unit-tests:core/importer-users-guards`.

## Corregido — los correos de aprobación y de alta por la API escapan los datos

- Los correos de aprobación o rechazo de un contenido y el de bienvenida del alta por la API escapan ahora los datos
  que pintan. Probado en `unit-tests:core/mail-senders-db`.

## Corregido — el texto de los correos se refuerza

- Probado en `unit-tests:core/mail-senders-db`.

## ⚠ Corregido — los correos de los formularios públicos escapan los datos del visitante

- **El formulario de contacto y el de «otros problemas» escapan** lo que escribe el visitante al pintarlo en el
  correo: nombre, correo, asunto, mensaje y los campos `extra`.
- **Si tu proyecto tiene plantillas de correo propias** con datos de un formulario público, revísalas: el
  patrón era el mismo.
- De paso, los campos `extra` de «otros problemas» se separaban con el texto literal `\n`.
- Probado en `unit-tests:core/mail-templates-escape`, que falla si se quita el escape.

## Corregido — «Acerca del framework» mostraba la versión con la «v» repetida

- La página imprimía `v` delante de `APP_VERSION`, que ya la lleva: salía `vv7.1.0`.

## Corregido — «Ver más» vuelve a las tarjetas de noticias

Desde la v6.1.0 cada tarjeta de noticia mandaba al navegador su contenido completo
(`data-content-b64`), pero el rediseño quitó el botón que lo abría: nadie podía leer más allá del
extracto. Y el modal buscaba el título en `.header`, que la tarjeta nueva llama `.head`, así que aunque
el botón volviera habría salido sin título. Un defecto tapaba al otro.

- Vuelve el botón «Ver más» cuando el contenido pasa de 117 caracteres, con el marcado que tenía.
- El modal toma el título de la tarjeta actual.

## Cambios internos — una sola clasificación de archivos subidos

`FileUpload::validate()` y `UploadedFileAdapter::validate()` eran dos copias del mismo árbol de
decisión, y un mismo arreglo hubo que hacerlo en las dos.

Ahora las dos llaman a **`PiecesPHP\Core\Forms\UploadedFileValidation::errors()`**, que devuelve la
lista de errores (vacía si el archivo es válido) y recibe cómo mostrar cada texto: `FileUpload` los
deja en español, como antes, y `UploadedFileAdapter` los traduce, como antes.

- **Sin cambios de comportamiento**, comprobado con una suite de caracterización escrita y commiteada
  ANTES de unificar: `unit-tests:core/upload-validation` fija, para cada caso y en las dos clases, el
  resultado, los mensajes exactos y la excepción.
- **Corregido de paso en `UploadedFileAdapter`:** los mensajes de tamaño máximo se traducían con el
  valor ya pegado dentro (`… (8MB)`), así que la traducción nunca casaba. Ahora se traduce el texto
  fijo y el valor se añade después. En español el texto no cambia.
- Las constantes públicas de esas dos clases siguen existiendo, con el mismo valor.

## Eliminado — los restos del módulo de experiencias (E3)

El módulo `experience` se borró hace tiempo, pero quedaban piezas sin sujeto. Se retiran:
- el JavaScript que montaba un formulario que ya no existe, en los dos perfiles de MySpace
  (`experienceForm()` y su escucha `wasDeletedPreviousExperience`);
- sus estilos, en los cuatro SCSS de MySpace;
- las dos tablas que `databases/piecesphp_structure.sql` todavía creaba
  (`organization_previous_experiences` y `previous_experiences`), sin mapper que las usara;
- una exclusión de Rector que apuntaba a dos vistas inexistentes.

**Si tu instalación tiene esas dos tablas, siguen ahí:** no hay migración que las borre, porque
pueden contener datos tuyos. El archivo de estructura ya no las crea en una instalación nueva.
**Recompila los estilos** (`gulp`) para que el CSS deje de llevar las reglas muertas; mientras
tanto son inofensivas.

## ⚠ Corregido — el nombre de usuario del OTP y del login va por marcador

La búsqueda del usuario por nombre en el OTP y en el inicio de sesión va ahora por marcador, y lo vigila una prueba.
En la misma revisión se cerraron tres más, menos graves: los nombres de tipos de documento y de categorías de
formularios, y el borrado del token de recuperación por su valor.

## Corregido — los archivos protegidos se servían como públicos para las cachés

Lo que sirve PHP tras validar el acceso (subidas de documentos, organizaciones, categorías de
noticias y publicaciones) declara ahora que no es cacheable por terceros.
- **Ahora:** sale con `Cache-Control: private, …, must-revalidate` y
  `Vary: Cookie, Authorization`, más `Accept` si se convierte a WebP y `Accept-Encoding` si se
  comprime. El CORS añade `Origin` al `Vary` en lugar de sustituirlo.
- **Cada archivo tiene su propio ETag** (fecha, tamaño y formato de salida). Antes, varios
  archivos con la misma fecha compartían uno. La primera vez, un cliente con un ETag viejo
  recibe 200 en lugar de 304.
- **Streaming y `Range`:** lo que no se convierte ni se comprime se envía por trozos y admite
  una petición parcial (206 o 416), por ejemplo para saltar en un vídeo.
- **La conversión a WebP se guarda en disco** (`src/app/cache/statics-webp/`). Una imagen de
  4,7 MB pasó de 0,60 s a 0,07 s en local.

## ⚠ Corregido — la edición de traducciones se valida en el servidor

Las traducciones pueden llevar HTML a propósito. Ahora el servidor traduce y valida lo que guarda, y la edición exige
el permiso correspondiente. Detalle y migración en la ruptura 18.

## Corregido — se refuerza el buscador de las tablas del panel

- **Las búsquedas normales dan exactamente lo mismo que antes**, también las que usan `%` y
  `_`. Se comprobó contra la aplicación con sesión, en los 21 listados, con nueve búsquedas cada
  uno y comparando antes y después.
- **Un solo cambio visible:** buscar una barra invertida (`\`) antes no filtraba nada; ahora se
  trata como texto.
- **Los tres listados que pasaban su propio filtro de texto** (el de aprobaciones y dos de los
  intentos de acceso) también van ya por marcador (`having_segment`), con el mismo resultado.
- **`having_string` sigue admitido como legado**, para los módulos de proyectos clonados. Pasa a
  `having_segment`.

## ⚠ Corregido — los informes de accesos y los listados de usuarios devuelven menos columnas

- **Las consultas de esos listados ya no incluyen la contraseña**; las demás columnas siguen llegando.
- **Si tu proyecto la leía de esas filas**, ya no llega. Carga el usuario con su mapper cuando
  necesites comprobarla, como hace el inicio de sesión.
- **Tras actualizar**, conviene pedir a los usuarios que cambien su contraseña.

## Corregido — las etiquetas traducidas entran en el SQL con un ayudante nuevo

Los mappers que muestran el nombre de un estado, un tamaño o un tipo dentro del `SELECT` usan
`sqlStringLiteral()`. Se ven las mismas etiquetas que antes, y una etiqueta con apóstrofo ya no rompe
la consulta.
- **Para tu código:** un texto **del servidor** que tenga que ir dentro del SQL va con
  `sqlStringLiteral()`; un valor **de la petición** va por marcador.

## Corregido — más comparaciones van por marcador

Las búsquedas y las comprobaciones de duplicados pasan a marcador.
- **Para el usuario no cambia nada, salvo en los bordes:** un nombre con comilla o barra se compara
  tal cual.
- **No uses `escapeString()` en código nuevo:** manda el valor por marcador.

## Herramientas — `verify-integrity` exige que toda carpeta de subidas esté protegida o declarada

Comprobación 29. Toda constante `UPLOAD_DIR` de `src/app` tiene que cumplir una de dos cosas:
- estar registrada en `ProtectFileMiddleware::protect()`, lo que se lee en tiempo de ejecución
  con `getProtectedDirectories()`;
- estar declarada en `files/dev/upload-dirs.json`, como `publicas` o `sin_archivos`, con su
  motivo.

También falla si una entrada del registro ya no casa con ninguna constante, porque el registro
solo encoge, o si una carpeta está en dos sitios a la vez. Un módulo nuevo con subidas no puede
nacer servible a cualquiera sin decirlo.

## Corregido — `ProtectFileMiddleware::protect()` crea la carpeta que protege

Si la carpeta no existe al arrancar, `protect()` la crea y la protege. Si no puede crearla, lanza una
excepción con la ruta.

## ⚠ Corregido — los listados paginados validan los valores de sus búsquedas

Los listados paginados del framework mandan ahora los valores de sus búsquedas por marcador. **Las búsquedas legítimas
devuelven lo mismo que antes.**

**Actualiza.** Y si copiaste el patrón a un módulo tuyo, revisa que ninguna cadena SQL mezcle valores de la petición:
los valores van por marcador, también en `PageQuery` y en `prepare()`.

## `PageQuery` acepta valores ligados

`new PageQuery($select, $count, $page, $perPage, 'total', [':nombre' => $valor])`. El sexto
parámetro es opcional; sin él, todo sigue igual. Cada consulta recibe solo los valores cuyos
marcadores lleva. Solo se admiten marcadores con nombre, no `?`. **Sin valores, el SQL tiene que
ser tuyo, nunca de la petición.**

## Herramientas — `verify-integrity` pone trinquete a los censos de identificadores e interpolación

Dos comprobaciones nuevas, la 27 y la 28, que fallan y no solo avisan:
- **La 27: ningún identificador de SQL viene de la petición.** Lo mide
  `bin/censo-sql-identificadores --trinquete`.
- **La 28: la interpolación de SQL con valor de petición no crece.** Lo mide
  `bin/censo-sql-interpolado --trinquete`.
  - Las excepciones quedan declaradas con un `count` exacto: un sitio de más o de menos, falla.

Los tres censos de SQL dejan además de contar dos veces lo que hay dentro de una función anónima.

## Herramientas — los cuatro paquetes `piecesphp/*` miden con el mismo analizador que el framework

`database`, `datastructures`, `geojson` y `html` pasan a phpstan 2.2.12 y rector 2.6.6, los
mismos que usa el framework. Una cifra medida con otro analizador no es comparable, y
`verify-integrity` lo vigila en su comprobación 7.
- **El trinquete de los cuatro** acepta ahora «destapados» y «murieron», como el del framework.
  Así se registra sin llamarla arreglo una bajada que causa el analizador: 21 → 18 en `database`,
  3 → 1 en `html`.
- **Si mantienes un clon de uno de los paquetes:** su `composer.lock` no se versiona. Si es
  anterior a su última mayor, puede estar anclado a una versión que su `composer.json` ya no
  admite; así estaba `html`. Actualízalo con Composer nombrando también la dependencia
  `piecesphp/*`.

## Corregido — la dirección de `custom_order` se normaliza a `ASC` o `DESC`

La dirección de cada entrada de `custom_order` sale `ASC` si lo es (sin distinguir mayúsculas) y
`DESC` en cualquier otro caso, igual que el orden que pide el navegador. Los
listados del framework no cambian: todos pasaban `ASC` o `DESC`. **Si un módulo tuyo pasaba otra
cosa como dirección, ahora ordena `DESC`.** La columna sigue siendo un identificador que pones tú:
no la construyas con datos de la petición.

## Herramientas — `bin/censo-sql-identificadores`: tablas, columnas y campos

Mide lo que ningún otro censo de SQL miraba: los argumentos que acaban en la sentencia como
identificador y no como valor.
- **Qué mira:**
  - el argumento de `select()`, `setTable()` y `rowCount()`;
  - los dos últimos de `get()`;
  - la tabla de `join()` y sus variantes;
  - las claves `select_fields`, `columns_order` y `custom_order` de `DataTablesHelper`.
- **Resultado:** ninguno trae un valor de la petición. Para un identificador no existe marcador,
  así que esa es la única cifra que protege. Dice su cota al ejecutarse.

## Las búsquedas de ubicaciones validan sus valores

Las búsquedas de `Locations` mandan sus valores por marcador, y sus filtros de lista validan el dominio.

**Si tu despliegue pasaba a esos filtros un valor que no correspondía**, antes recibía un **500** y
ahora recibe **200 con el criterio omitido**.

## Herramientas — `verify-integrity` comprueba que la línea base de PHPStan dice una sola cifra

`PHPStanResult.Summary.baseline.txt` nombraba la cifra vigente en tres sitios —la cabecera, el
campo `[TOTAL DE ERRORES VISIBLES]` y el `[REPARTO]` más reciente—, y cada uno derivó por su lado
hasta decir 749, 747 y 744. La comprobación 26 falla si no concuerdan, si la cadena de repartos se
rompe o si el archivo no existe. La cabecera ya no repite la cifra: dice de dónde sale y con qué
analizador se midió.

**Si mantienes tu propia línea base**, escribe la cifra en el campo y en su `[REPARTO]`, y en
ningún otro sitio.

## Herramientas — el censo de SQL deja de contar paréntesis dentro de un literal

El mapa de asignaciones de `bin/censo-sql-concatenado` contaba el `(` de `"($a) AND …"` como un
paréntesis, y la expresión se comía el resto del método. Dos sitios salían decididos sin estarlo, y pasan de
declarados a revisar a mano. **`DECLARADO` baja de 10 a 8
y no es una mejora: era una cifra inflada.** `CONFIRMADO` sigue en 0, y un canario nuevo cae si el
defecto vuelve.

## Herramientas — `bin/censo-sql-interpolado`: lo que el censo de SQL no mira

`bin/censo-sql-concatenado` mira llamadas y claves de array; este guion mide las cadenas SQL armadas con una variable.
**No es una puerta**: su traza no cruza de método.

## Corregido — se refuerza la búsqueda de los listados

Los resultados no cambian.

## Herramientas — `verify-integrity` vigila la versión del analizador

`files/dev/shared-toolchain.json` declara con qué versión de PHPStan mide cada repositorio, y
**`verify-integrity` falla si la instalada es otra**. Si subes PHPStan en `bin/tools`, declara la
versión nueva en el mismo cambio. El analizador pasa a phpstan 2.2.12 y Rector a 2.6.6, y la cifra
de errores no se movió con la subida.

La comparación de etiquetas acepta ya `vX`, `vX.Y` y `vX.Y.Z`: antes descartaba en silencio las que
no tenían tres partes.

## Herramientas — el censo de formas de lectura pasa a ser puerta

`bin/censo-formas-de-lectura --trinquete` entra en `verify-integrity` como comprobación 25: falla si
una forma «para leer» —como `getCompiledSQL()` sin argumento, que produce SQL no ejecutable— acaba
ejecutándose sin estar declarada en `files/dev/reading-forms-baseline.json`.

## `having_segment` convive con la búsqueda de DataTables

`piecesphp/database` sube a **4.1.0**, que es aditiva: `HavingSegment::addGroup()` y
`HavingItemGroup` permiten expresar `(a OR b) AND c`. Con eso, cuando un listado pasa
`having_segment`, `DataTablesHelper::process()` le añade la búsqueda como un grupo unido con `AND`,
y el texto buscado viaja por marcador. Sin `having_segment`, todo sigue como estaba.

**Pasar `having_segment` en un listado con búsqueda ya no lanza**: antes lanzaba porque la búsqueda
se habría perdido. Las dos exclusiones —cadena y segmento a la vez— siguen lanzando.

## Corregido — el filtro de aprobaciones no encontraba nada fuera de español

`SystemApprovalsMapper::getReferencesAliases()` devolvía la etiqueta **traducida** como clave del
desplegable, mientras la columna `referenceAlias` guarda el texto sin traducir. En inglés el
desplegable mandaba `Profile` y el `WHERE` comparaba contra `Perfil`: **el filtro no casaba
nada**. Ahora la clave es el valor crudo y el texto el traducido, que es lo que un desplegable
necesita; el consumidor no cambió porque ya esperaba clave y texto distintos.

## Los handlers de aprobación declaran todos sus textos

`ApprovalElementHandlerInterface` gana `getContentTypes()`, y `SystemApprovalManager` la unión de
los registrados. Hacía falta porque `UsersApprovalHandler` escribe **dos** —`Perfil` y
`Usuario independiente`—, y el segundo era un literal suelto dentro de un método: cualquier lista
blanca construida sin él habría sido falsa. Ahora sale del mismo sitio que lo escribe.

## Herramientas — `bin/censo-formas-de-lectura`, y la respuesta es que era única

Busca en los cinco repositorios toda función cuyo propósito sea producir texto **para mirar** y
traza si su salida acaba ejecutándose. Criterio declarado: el nombre, el docblock, o —la que
importa— **sustituir marcadores por valores**. El `toString()` de los segmentos queda fuera por
decisión, porque emite marcadores y no valores.

**736 archivos, 22 formas, y ninguna ejecutándose además de la que ya se corrigió.** La única
que sale marcada es `humanReadable()`, que devuelve datos legibles de la entidad y acaba en una
respuesta de API: su destino es su propósito, y queda explicado en la cota impresa.

**No se añade ninguna puerta.** Una comprobación que nunca puede fallar es ruido; el censo queda
como instrumento y cablearlo es una línea el día que aparezca un segundo caso.

El docblock de `DataTablesHelper::process()` deja escrito que `getCompiledSQL()` sin argumento
produce SQL que no se puede ejecutar, y por qué el defecto durmió años.

## Corregido — el conteo de los listados con filtros por marcador

Un listado que pasaba sus filtros por marcador podía responder 500 al contar. Comprobado por HTTP en las rutas afectadas:
**500 → 200**.

## Eliminaciones — `datatables_proccessing_with_options()`

Cero llamadas en todo el repositorio, gemelo del que ya se retiró. Muere con él.

## Eliminaciones — el filtro de plantilla del explorador de documentos y un envoltorio muerto

`FIELD_SAMPLE_FILTER` era un residuo de **tres piezas**: un criterio en PHP sobre una columna que
no existe, un JS que enviaba `FIELD_SAMPLE_FILTER_LOAD` —con sufijo, así que el PHP nunca lo
leía— y un `configFomanticDropdown` que bindeaba a un desplegable ausente de la vista. Las tres
fuera; cero ocurrencias en `src/`. Aparece en un solo módulo, así que era residuo y no patrón.

`datatables_proccessing()` se retira de `config/functions.php`: cero consumidores en todo el
repositorio. La comprobación de firmas de `verify-integrity` lo cazó por su nombre y la
instantánea se regeneró por su vía. Queda vivo su gemelo `datatables_proccessing_with_options()`,
también sin consumidores, a la espera de decisión.

## Más listados mandan sus filtros por marcador

Más listados del framework pasan a `where_segment`.

## `DataTablesHelper::process()` acepta `where_segment` y `having_segment`

Dos claves nuevas y **aditivas**: reciben un `WhereSegment` / `HavingSegment` ya construido y sus
valores viajan **por marcador**. Sin ellas, el comportamiento es idéntico al de antes, así que
ninguna controladora tiene que cambiar.

Son **excluyentes** con su cadena equivalente, y `having_segment` **no puede convivir con la
búsqueda de DataTables**: sustituiría al HAVING que ésta genera, y `HavingSegment` no admite
agrupación, así que `(a OR b) AND c` no es expresable. Las tres situaciones lanzan con mensaje
claro en vez de perder un filtro en silencio.

## Corregido — más filtros de listado validan sus parámetros

**El rechazo no ensancha:** un valor que no valide no encuentra nada; no quita el filtro.

## `DataTablesHelper::process()` dice dónde está la frontera de su contrato

`where_string`, `having_string` y `group_string` son fragmentos de SQL **del programador**, y no
existe vía preparada para ellos: `having()` solo rellena sus valores de reemplazo cuando recibe
un `HavingSegment`. Queda escrito en el docblock que meter ahí un valor de la petición es un
error de diseño, junto con las tres claves de identificador —`select_fields`, `columns_order` y
`custom_order`—.

## Herramientas — el censo de SQL mira también las opciones de `DataTablesHelper::process()`

Las claves que acaban en el SQL como fragmentos o como identificadores entran en el censo.

## Corregido — el desplegable de usuarios volvía a mostrar los eliminados al buscar

**Para tu código:** `ActiveRecord::having()` **sustituye** el segmento, no lo acumula.

## La búsqueda de usuarios manda el valor por marcador

El buscador del desplegable pasa a `HavingSegment`, y sus listas de filtro validan el dominio.

## Herramientas — el censo de SQL concatenado mira más formas

`bin/censo-sql-concatenado` mira también órdenes, agrupaciones y uniones, e imprime qué sigue sin mirar.

**Para tu código:** los operadores de lista (`IN`, `NOT IN`, `FIND_IN_SET`) y las claves de orden y agrupación no van por
marcador. Valida el dominio de lo que les pases, y no los construyas con datos de la petición.

## Un valor de parámetro inválido también da 400

Junto al parámetro obligatorio que falta, `InvalidParameterValueException` pasa a **400** con
`INVALID_PARAMETER_VALUE`. `ParsedValueException` **se queda en 500 a propósito**: salta cuando
el `parse()` de un módulo devuelve algo que su propio `validate()` rechaza, y las dos son
código del servidor.

## Ninguna ruta nace pública sin decirlo

Comprobación 23 de `bin/cli verify-integrity`. Una ruta de un módulo con
`DefaultAccessControlModules` que no declare ni `require_login` ni `roles_allowed` no la ve
ninguna de las dos capas de acceso: **nace pública**. Son cuatro hoy, las cuatro a propósito, y
están declaradas una a una en `files/dev/public-routes-in-guarded-modules.json`.

## Las guardas de acceso ya prueban que RECHAZAN

`UnitTest-AccessGuards` prueba las guardas del núcleo: cada una con su caso de RECHAZO, uno que NO debe rechazarse y su
provocación: **se quita la guarda y la suite tiene que ponerse roja**.

## Fuera la traducción automática de `MySpace`

No estaba a medio construir: **funcionaba y su sujeto murió**. El botón que la disparaba vivía
en el formulario de experiencias previas, que se retiró con el primer lote de la limpieza.
`experienceName` estaba en 13 archivos y hoy en 1. `Publications` conserva la suya —su
`[do-translation]` sigue en la vista— y pasa a ser la implementación de referencia.

## E4 se selecciona por consecuencia, no por «lo que se puede probar»

La pregunta no es «¿se puede probar?» sino **«si esto se rompiera en silencio, ¿lo notaría
alguien?»**. Las tres pruebas que han encontrado algo en esta campaña fueron CONTRATOS, no
cobertura. `bin/censo-guardas` mide lo que valida, rechaza, autoriza o limita, y marca lo que
puede FALLAR ABIERTO —decir que sí cuando debía decir que no—, que es un fallo invisible por
definición: nadie reporta un permiso concedido de más.

En el núcleo: **180 guardas por forma**, 29 con forma de fallo abierto —comparación laxa,
acumulador nacido en `true`, cadena `if/elseif` sin `else`: las tres formas de
`FileUpload::validate()`— y **solo 5 con prueba de RECHAZO**. Llamar a una guarda desde una
suite no es probarla: cuatro de las nueve que alguna suite llama solo comprueban que ACEPTA lo
bueno.

## Una línea de código comentada no es un relato

La puerta de comentarios narrativos contaba como prosa **cualquier** línea comentada, así que
«comentar en vez de borrar» —lo que pidió el PROPIETARIO para los idiomas— la ponía roja: cuatro
entradas comentadas seguidas son un bloque de cuatro líneas de prosa. La anotación
**`@codigo-comentado`** exime al bloque, igual que ya lo hacían `@param` o `@return`. Se declara
en el sitio, no se adivina con una heurística.

---

## Documentación — la LEY 19 llega a cinco casos, y los cinco son de la misma persona

*Una afirmación sobre el consumidor no se deduce del productor.* Los dos nuevos:

| Se leyó | Se afirmó | Era |
| :-- | :-- | :-- |
| Un ejemplo de mensaje de commit | «la convención es `docs(t114)`» | Se usó **una vez en 2.056 commits** |
| El aviso `LF will be replaced by CRLF` | «los artefactos dan ruido de diff» | El aviso habla del **checkout**; no había ningún diff |

El cuarto es **generalizar desde un solo ejemplo**; el quinto, **heredar una premisa de un informe
sin volver a medirla** — la LEY 14 y ésta actuando juntas, hasta convertir un error del registro en
una tarea del bloque siguiente.

## Documentación — los seis censos sospechosos de la LEY 16 se cierran, y ninguno era un instrumento roto

La lista llevaba semanas abierta. **Cerrada entera, y el resultado es más útil que el que se
esperaba: en cinco de los seis lo que faltaba era la UNIDAD, no el instrumento.**

| Censo | Veredicto | Qué era |
| :-- | :-- | :-- |
| `$_FILES`: 44 | **unidad** | 44 **líneas**, 49 **ocurrencias**; el árbol no ha cambiado |
| los 9 de `$_FILES[$nameOnFiles]` | **conteo corto por uno** | Son 10, y ya lo eran |
| los diez `$showSQL` | **unidad** | Diez **archivos**, 31 apariciones |
| 34 columnas `json` | **cuadra** | 35 declaradas, 34 en el literal `$fields` |
| 59 y 62 de `human_readable_reference_field` | **ya reconciliado en su sitio** | «62, 60 y 59 son TRES PREGUNTAS» |
| 44 de 50, y 26 | **otro instrumento** | Son identificadores de error de PHPStan, no un `grep` |

**Y hay un argumento que los cierra todos a la vez**: con el `$` roto, ese `grep` devuelve **cero**,
no un número menor. Los seis dieron cifras distintas de cero, así que **ninguna pudo salir de ahí**.

> **TODA CIFRA DEL REGISTRO LLEVA SU UNIDAD Y SU PATRÓN AL LADO.** «44 accesos» no significa nada;
> «44 líneas que casan con `$_FILES` en `src/app`» sí. Es la regla de «toda cifra lleva su método»
> con el detalle que faltaba: **el método incluye la unidad y el patrón, no solo la herramienta.**

Una discrepancia entre dos censos es **más probable que sea de unidad que de instrumento**, y
mirar la unidad primero cuesta un minuto.

## ⚠ Corregido — la guía de despliegue mandaba a instalar el PHP que no arranca

`general.md` decía *«el piso es PHP 8.4.1»* y `sudo apt install php8.4 …`. **El piso es
`>=8.5 <8.6`**, y `platform_check.php` —que el propio aviso citaba— aborta el arranque con 8.4.
**Un despliegue nuevo que siguiera esa guía no levantaba.**

Corregidos el texto y el comando. La justificación caducada —«el `.1` lo exige `symfony/cache`
8.1»— se sustituye por la fuente real: **`require.php` de `src/composer.json`, que es la única**.
Y se añade que **el techo es tan real como el piso**: `<8.6` significa que 8.6 tampoco vale
mientras no se pruebe.

**Y la familia entera, porque media corrección fabrica divergencia**: censada la versión en toda la
documentación aparecieron cuatro sitios más con la misma afirmación —el bloque de requisitos del
propio `general.md`, la portada `index.md`, y **un `FROM php:8.4-apache` en la guía de Docker**,
que es el mismo defecto en otro entorno—.

Se dejan a propósito las dos menciones que son **historia y no instrucción**: la entrada del
changelog de la 7.1.0 y una salida de `composer` citada en el apartado de diagnóstico.

## ⚠ Corregido — el informe genérico tenía el HTML roto y mostraba texto de un módulo borrado

Al retirar las dos tarjetas de convocatorias, el corte se ancló en un `</div>` a sangría
fija y esa sangría cerraba un `div` interior. Quedaron dos pies de tarjeta huérfanos —con
sus etiquetas **visibles en pantalla**— y dos `</div>` de más. Medido: 96/96 etiquetas antes,
86/88 después, 80/80 ahora.

Comprobadas las otras nueve vistas afectadas por la limpieza de módulos: cuadran todas.

## ⚠ CAMBIO INCOMPATIBLE — fuera los diccionarios de `fr`, `pt`, `it` y `de`

Decisión del PROPIETARIO. Se conservan `es`, `en` y los diccionarios de JS.

**No rompe nada**: `LangInjector` guarda con `file_exists()` en sus tres puntos de carga, así
que un diccionario ausente se salta y `__($grupo, 'Texto')` devuelve su segundo argumento.
**Pero `config/lang.php` sigue declarando los cuatro en `allowed_langs`**, así que `/fr/`
sigue siendo una URL válida y el selector los ofrece mostrando el texto en español. Retirarlos
de ahí es decidir qué idiomas ofrece la aplicación.

## Corregido — 39 claves de traducción que ya no pedía nadie

La limpieza de módulos dejó texto de módulos muertos en los diccionarios de los que se
conservan. Se separó de la deuda anterior **midiendo**: se extrajo el árbol previo a la
limpieza y se corrió el mismo censo — 69 cadenas huérfanas antes, 92 después, y solo las 39
de diferencia se retiraron.

## Herramientas — `verify-integrity` encuentra las claves de traducción que nadie pide

Faltaba esta puerta, y por eso los censos de huérfanos daban cero: miraban IDENTIFICADORES y
nunca miraban TEXTO VISIBLE. El cero era cierto dentro del universo que miraban.

`bin/censo-claves-huerfanas` compara cadena contra cadena —en este framework la clave *es* el
texto en español—, con canario de dos caras y trinquete sobre lo que crece. Cuesta 0,11 s.

**Lo que no mide, dicho**: una clave pedida con `__($grupo, $variable)` sale como huérfana,
porque no resuelve variables. Es un trinquete, no una lista de borrado automático.

## ⚠ CAMBIO INCOMPATIBLE — fuera las áreas de interés de investigación

**E3, cuarto y último lote de borrado.** Desaparece `InterestResearchAreas` entero —24
archivos y la tabla `interest_research_area`— y con él el **alias vacío** de
`PiecesPHP/UserSystem/Profile/SubMappers/`, que era una clase sin cuerpo. Ese directorio
queda vacío y también se borra.

**Los perfiles de usuario y las organizaciones pierden un campo.** `interestResearhAreas`
**no era una columna**: es una meta-propiedad guardada dentro del JSON de `meta`, así que no
hay migración de esquema que aplicar. En un despliegue existente la clave se queda en el
`meta` como dato huérfano e inerte.

**Si mantienes módulos propios sobre este framework:**

- **`getInteresResearchAreas()` YA NO EXISTE.** Vivía en `config/functions.php` y solo servía
  a este módulo.
- Los perfiles y las organizaciones ya no exponen `interestResearhAreasNames`,
  `interestResearhAreasIDsNames` ni `interestResearhAreasColorsNames` en sus consultas.
- El listado de actores y el mapa pierden su columna y su filtro de áreas.

**Cambio de comportamiento**: el campo estaba entre los requeridos para dar un perfil —y una
organización— por completos. Ahora se completan con un campo menos.

## E3 cierra sus borrados

| | al empezar | hoy |
| :-- | --: | --: |
| Tablas | 35 | 29 |
| Vistas | 5 | 3 |
| Errores de PHPStan | 883 | 749 |

**−6 tablas, −2 vistas, −134 errores**, y de esos 134 ni uno salió de un arreglo ni de una
supresión: es código que dejó de existir. Queda pendiente la reescritura de
`DataImportExportUtility`, que no es un borrado.

## ⚠ CAMBIO INCOMPATIBLE — fuera el módulo de convocatorias, con sus dos tablas y su vista

**E3, tercer lote.** Desaparece `ApplicationCalls` entero: 32 archivos, las tablas
`application_calls_elements` y `application_calls_attachments`, y la vista
`application_calls_active_date_elements`.

**Si tu despliegue lo usa, pierdes los datos y la funcionalidad.** El DDL sale también de
`databases/piecesphp_structure.sql` y `databases/piecesphp_views.sql`.

**Lo que cambia en los módulos que SE CONSERVAN**, cada uno en su commit:

| Módulo | Qué pierde |
| :-- | :-- |
| `ContentNavigationHub` | El listado y el detalle de convocatorias con sus tres rutas, dos piezas de mapa, dos filtros y la rama de tipo de característica del JS. **El mapa sigue funcionando**: pintaba tres tipos de punto y conserva dos |
| `SystemApprovals` | El manejador de aprobación de convocatorias y su formulario |
| `GeoJSONManager` | `withApplicationCalls()` y el caso `APPLICATION_CALLS` del enum `FeaturesTypes` |
| `ReportsManage` | Los tres contadores de convocatorias del informe, con sus dos tarjetas |
| `MySpace` | **Cambio de comportamiento**, ver abajo |

### ⚠ Dónde aterrizan ahora los usuarios aprobados que no son root

`MySpaceController` los mandaba al listado de convocatorias. Ahora **caen en «Mi espacio»**,
que es la rama que ya existía. No se ha inventado un destino nuevo.

Lo destapó PHPStan, no el censo: el nombre del método llamado —`applicationCallsListView`—
no casa con ninguna de las doce formas de nombrar el módulo.

## Herramientas — trinquete de los valores de retorno que nadie lee

Cuarta aparición de la misma clase de defecto. Se congelan **195** sin triajar ninguno, y se
pone trinquete sobre la cifra de **no declarados**, que es la única que tiene que encoger.

Para declarar un ignorado deliberado se escribe una marca **pegada a la llamada**, con el
motivo:

```php
//RETORNO-IGNORADO: el temporal puede no existir y da igual; se borra por si acaso.
@unlink($temporal);
```

Una marca sin motivo no declara nada: si no, la marca sería la forma nueva de callar. La
línea base vive en `files/dev/ignored-returns-baseline.json` con su método al lado, y el
trinquete compara **también el universo** — una cifra que baja porque el censo mira menos
archivos no es una mejora.

`verify-integrity` gana la comprobación 19 para correrlo.

## Herramientas — `verify-integrity` detecta los enlaces rotos del árbol servido

`ServerStatics::createDynamicSymlink()` crea enlaces al servir y **nunca retira uno cuyo
destino desapareció**. Borrar los `Statics/` de un módulo deja un enlace roto por archivo, y
el directorio está declarado volátil, así que nada lo veía.

**Se detecta, no se retira en caliente**, y por dos motivos medidos: el retorno temprano de
esa función ocurre *antes* de normalizar la ruta, y ese camino solo corre cuando alguien pide
el asset — nadie pide los de un módulo muerto, así que la limpieza en caliente no llegaría
nunca a los que importan.

## Corregido — `bin/normaliza-eol` no veía los archivos nuevos

Construía su lista con `git ls-files`, que no lista lo no versionado. Un archivo **nuevo** —el
único caso para el que existe la regla— era invisible para la herramienta escrita para verlo.

## ⚠ Corregido — `db-backup` decía «Operación exitosa» sobre un respaldo de 7 tablas de 33

**Si tienes copias hechas con este framework, compruébalas.** La tarea descartaba el valor
devuelto por el exportador y probaba el éxito con `file_exists()` — y el archivo existe igual
aunque la exportación reviente a mitad. Con una vista rota encima de una tabla borrada
producía 8 KB, 7 tablas de 33 y cero `INSERT`, y terminaba con código de salida 0.

Ahora el valor devuelto se lee, se imprime el motivo, y **el respaldo se verifica a sí
mismo**: lo esperado sale de la base, lo escrito se lee del archivo —comprimido o no—, y si
falta algo dice cuántos objetos y cuáles. Salida distinta de cero cuando falla.

`db-restore` no tenía la enfermedad, y se comprobó ejecutándolo en vez de suponerlo.

## ⚠ CAMBIO INCOMPATIBLE — `scheme-drop` emite también las vistas del módulo

Las tablas salen de `$fields`; una **vista** no tiene `$fields`, así que el script de borrado
la callaba. Retirar la tabla y dejar la vista apuntando al vacío deja un objeto roto en la
base y **mata al exportador entero**.

La fuente es `databases/piecesphp_views.sql`. Se emiten los `DROP VIEW` **antes** de los
`DROP TABLE`. Si el archivo no se puede leer, lo dice en vez de callar.

**Si automatizas borrados de módulo, el orden importa**: la herramienta lee las vistas del
archivo de declaración, no de la base. Retira primero de la base —con la declaración aún
puesta— y edita el archivo después. Al revés, la base se queda con la vista huérfana.

## ⚠ CAMBIO INCOMPATIBLE — se va la tabla y la vista del repositorio de imágenes

Cierra el borrado empezado en la entrada de abajo. Desaparecen `image_repository_images` y
`image_repository_images_view`, y sus declaraciones salen de `databases/piecesphp_structure.sql`
y `databases/piecesphp_views.sql`.

## La foto de `snapshot` cubre más: `databases/` y las vistas

El universo se declara en una constante y la descripción dice también lo que **no** cubre
—`bin/`, `files/`, `.agents/`, `source-docs/` y la raíz—. Un módulo tiene piezas fuera de
`src/`: su DDL y sus vistas viven en `databases/`.

Y `snapshotTables()` filtraba por `TABLE_TYPE = 'BASE TABLE'`, así que **una vista borrada no
aparecía en la comparación**. Ahora se guardan por su definición, con lo que se ven las dos
cosas que pueden pasarles: que desaparezcan y que les cambien el cuerpo.

## Corregido — dos puertas más que preguntaban por un texto en vez de por un comportamiento

`core/operation-from-route` buscaba el literal `$isEdit = $id !== -1;`. Las cuatro
controladoras de `Locations` escriben `$is_edit`, con guion bajo, y **llevaban desde su cierre
sin ser vistas**: la puerta decía cero sobre cuatro controladores que tienen el defecto.

Ahora se tokeniza y se sigue el flujo — una variable que sale del cuerpo de la petición y
acaba decidiendo otra contra `-1` —, así que el nombre da igual. Las cuatro quedan declaradas
con su razón, no calladas, y una segunda comprobación exige que esa lista no cubra nada que ya
esté arreglado.

## Herramientas — `bin/censo-retornos-ignorados`

Mide la cuarta aparición de la misma clase de defecto: un valor de retorno que nadie lee.
Tokeniza, distingue una llamada de método de una función homónima, y declara su universo.
**Solo mide y clasifica.**

## Corregido — `.editorconfig` no cubría lo que `.gitattributes` sí

`.gitattributes` declara `bin/* text eol=lf`. `.editorconfig` nombraba **cuatro** guiones a
mano y los otros trece caían en `[*]`, que declara CRLF: abrir `bin/censo` en un editor
conforme lo habría dejado sin arrancar —con el shebang en CRLF, `/usr/bin/env` busca
«`python3\r`»— y git no lo delata, porque normaliza en el índice.

Igual con `PHPStanResult.*` y `*.lock`. Y en tres de los cuatro paquetes faltaba `bin/rector`.

Medido con la implementación real de editorconfig, archivo a archivo: de **19 divergencias a
cero** sobre los 2.170 archivos versionados, y de 3 a cero en los paquetes.

## ⚠ CAMBIO INCOMPATIBLE — fuera el repositorio de imágenes, y su tabla NO se ha borrado todavía

**E3, segundo lote.** Desaparece `ImagesRepository` entero: 25 archivos versionados, más los 2 CSS
compilados que no lo estaban.

**Si tu despliegue lo usa, pierdes la funcionalidad.** El módulo no está y su constante
`IMAGES_REPOSITORY` tampoco.

| | |
| :-- | :-- |
| **Se borra** | `src/app/classes/ImagesRepository/` completo — controladores, mapper, vistas, JS, SASS y `lang/` |
| **Se edita** | `MySpaceController` y la vista `my-space` —el conteo y las dos tarjetas—, `config/routes.php` y `config/constants.php` |
| **Registros que encogen** | La sobreescritura de ruta en `verify-integrity`, la entrada de comentarios narrativos y la tabla en `volatile-state.json` |

**`MySpace` se conserva y por eso va en commit propio**: pierde el número de fotografías y los dos
enlaces del panel, y nada más.

**LA TABLA `image_repository_images` SIGUE EN PIE, A PROPÓSITO.** Tiene una **vista** encima,
`image_repository_images_view`, que `scheme-drop` no emite: las tablas salen de `$fields` y una
vista no tiene `$fields`. Aplicar solo el `DROP TABLE` deja la vista rota, y con ella rota
**`db-backup` produce un volcado truncado —7 tablas de 33— y termina diciendo «Operación
exitosa»**. La base quedó restaurada a su estado previo y las 23 puertas en verde; retirar tabla y
vista juntas queda pendiente de decisión.

Censo de huérfanos con canario sobre 2.647 archivos: **cero** en las diez formas de nombrar el
módulo, y cero menciones en el inventario de 330 rutas. El baseline de PHPStan baja de 844 a 835,
y los nueve murieron con su archivo.

## Corregido — `db-backup-round-trip` dejaba una fila de aprobación por corrida

La suite creaba su usuario de prueba y lanzaba `db-backup` en un subproceso. Ese arranque ejecuta
`SystemApprovalManager::init()`, que **rellena una fila de aprobación por cada usuario que no la
tenga**, y el `finally` no la recogía. Medido: 82 filas con el arreglo, 83 sin él.

Es la segunda pieza de la misma ley que cazó la fuga del exportador —una suite no puede medir su
propio efecto—, y la afila: **el efecto de una suite incluye lo que otro escribe por haber corrido
ella**.

De las 82 filas de aprobación de usuario, **75 son huérfanas** de corridas anteriores. No se
borran: es un `DELETE` sobre una tabla real.

## Herramientas — `bin/normaliza-eol`, y el «ruido CRLF» eran 298 archivos, no tres

Un archivo en LF con `eol=crlf` declarado **no produce diff**, así que git no lo señala nunca —
pero lo reescribe «la próxima vez que lo toque», y ese volteo cambia un byte por línea sin cambiar
el contenido. Invisible para git; una diferencia a investigar para cualquier instrumento que mire
bytes.

Bajo `src/` había **298** en ese estado. Normalizados, **cero blobs alterados**. Y como un archivo
nuevo vuelve a caer siempre —`bin/anexar` respeta los finales del destino, y un archivo nuevo no
tiene destino—, la regla pasa a ser herramienta: `bin/normaliza-eol` lee la forma correcta de `git
check-attr` y no decide nada por su cuenta.

De propina, medido al provocarla sobre el propio guion: con el shebang en CRLF, `/usr/bin/env`
busca «`python3\r`» y el guion no arranca. `bin/* eol=lf` no era cosmético.

## Corregido — dos puertas llevaban una cifra escrita, y el lote las puso rojas sin motivo

`core/operation-from-route` exigía exactamente 13 controladores usando `self::isEditRoute()`. Al
morir uno con su módulo, la puerta se puso roja sin que nada estuviera mal.

Ahora la población sale del árbol por **dos métodos independientes**: por texto, los que usan el
ayudante; por estructura, los que registran el **mismo manejador** para `-actions-add` y
`-actions-edit`. Se exige que el segundo conjunto esté contenido en el primero, y el fallo
**nombra** al que falta en vez de dar una resta.

**Hallazgo abierto que esto destapó**: la otra comprobación de esa suite busca el literal
`$isEdit = $id !== -1;`, y las cuatro controladoras de `Locations` escriben `$is_edit`, con guion
bajo. Llevan desde su cierre contando «cero» sobre cuatro controladores que tienen el defecto.

## ⚠ CAMBIO INCOMPATIBLE — fuera las experiencias previas del perfil

**E3, primer lote.** Desaparecen las experiencias previas de usuario y de organización: la entidad,
su CRUD, sus vistas, su JS y sus dos tablas.

**Si tu despliegue las usa, pierdes los datos.** `previous_experiences` y
`organization_previous_experiences` se borran del esquema, y el SQL lo emite `scheme-drop`, no se
escribe a mano.

| | |
| :-- | :-- |
| **Se borran** | `PreviousExperiencesMapper`, `OrganizationPreviousExperiencesMapper`, sus dos controladores `Util`, las dos vistas `experience-list-card` y los dos JS `delete-config` |
| **Se editan** | Los dos controladores de perfil, `ProfileTasksUtilities`, cuatro vistas de `MySpace` y **las dos vistas de aprobación de `SystemApprovals`** |
| **Rutas que dejan de existir** | `-datatables-experience`, `-actions-save-experience`, `-actions-delete-experience`, en los dos controladores |

**`SystemApprovals` se conserva y por eso va en commit propio**: las dos vistas de aprobación
dejan de mostrar el bloque de experiencias, y **lo de áreas de interés no se toca**.

`ProfileTasksUtilities` **no se borra**: además de las experiencias genera el SQL de
`UserProfileMapper`, que se queda.

**`InterestResearchAreasMapper` tampoco se toca**, aunque viva en el mismo directorio: es un alias
vacío hacia `InterestResearchAreas`, un módulo que muere en otro lote, y tiene 104 referencias
vivas en siete módulos —cuatro de ellos de los que se conservan—.

Censo de huérfanos con canario: **cero** referencias vivas a lo borrado, rutas y permisos
incluidos. Y el baseline de PHPStan baja de 883 a 844.

## Herramientas — el trinquete gana su cuarto término: `murieron`

E3 borra código, y **un error que se va porque se borró su código no es un arreglo** —nadie
corrigió nada— **ni una supresión** —el `.neon` sigue en 52 entradas— **ni un destapado**.

```
[REPARTO] 844 <- 883 = 0 arreglos + 0 supresiones + 39 murieron + 0 destapados
```

Y el nombre se queda corto, medido: de los 39, solo **17 murieron con su archivo**; los otros
**22 se fueron con código retirado de archivos que se quedan**. Los diez archivos cuya cifra se
mueve son exactamente los diez del lote.

## ⚠ Corregido — la foto de `snapshot` tenía ruido propio Y un punto ciego, y eran opuestos

E3 se apoya en «foto antes y foto después». La red se probó **antes** de usarla y no estaba lista.
Lo que la daba por buena: `db-restore` sin fallos, dos fotos seguidas con diferencia vacía, y las
altas y bajas provocadas viéndose todas. **Los dos defectos salieron al RESTAURAR.**

**1 · El mtime decidía la igualdad.** El registro es `tamaño:mtime:sha1` y se comparaba la cadena
entera. Restaurar un archivo con `cp` devuelve el contenido pero no el mtime: `sha1sum -c` decía
«la suma coincide» y la foto lo marcaba como modificado. Como el procedimiento de trabajo es
*copiar, romper, restaurar*, **cada archivo revertido iba a salir como cambiado**.

**2 · Y el contrario: 59 archivos sin huella.** Había un corte —`$size <= 1048576 ? sha1_file() :
'grande'`— y **`'grande' == 'grande'` se leía como «idéntico»**. Son el **37,2 % del peso del árbol
servido**: para ellos, una modificación que no cambiara el tamaño era invisible, y lo tapaba justo
el mtime del defecto 1. **Arreglar uno sin ver el otro habría cambiado un ruido por una ceguera.**

Quitar el corte cuesta **0,07 s** medidos. El motivo escrito se sustituye por esa cifra.

| Pieza | Qué cambia |
| :-- | :-- |
| Sin corte | El sha1 se calcula siempre: los 5.437 con huella real |
| El coste se dice | `272 MB hasheados en 598 ms` |
| Tres estados | igual, distinto y **NO COMPARABLE** — el mtime se guarda y deja de decidir |

**«Sin huella» no es «igual»**: un archivo ilegible sale con su marca `?`, se cuenta aparte y la
foto lo avisa. Cero es el estado normal.

**Provocado en seis formas más las dos que discriminan**: un byte cambiado en uno de los 59 grandes
—invisible antes, visible ahora— y un `cp` que restaura sin cambiar nada —falso positivo antes,
silencio ahora—.

## Corregido — la suite del exportador dejaba su tabla de andamiaje en la base

`core/database-exporter` hace `DROP TABLE IF EXISTS` + `CREATE TABLE` al entrar y **nadie borraba
al salir**: cada corrida dejaba `pcs_unit_tests_core_database_exporter_v1` puesta. Una suite con un
efecto que no deshace. Recogida ahora en un `finally`, para que limpie también cuando revienta:
**36 tablas antes, 35 después**.

**La suite pasa 23/23 con la fuga y sin ella** — no podía cazar su propio efecto. La cazó la foto,
un instrumento distinto mirando desde fuera. Y no se ha declarado nada volátil: declarar algo
volátil es el último recurso, no el primero.

## Herramientas — `bin/censo-comparaciones-cero`

Las comparaciones laxas contra cero cambiaron de sentido entre PHP 7 y PHP 8. El censo las mide
tokenizando el código y resuelve las constantes con `constant()`. **PHPStan nivel 8 no detecta esto.**

## ⚠ Corregido — la validación de archivos subidos rechaza lo que no reconoce

La validación vuelve a rechazar cuando no llega un archivo válido, y rechaza también cualquier código de
error que no conozca. **Actualiza.** Lo fija la puerta `core/file-upload-contract`.

## Herramientas — el bit de ejecución se pone solo, y no esperamos a la tercera

`verify-integrity` cazó **dos veces** un guion nuevo de `bin/` guardado en el índice como
`100644`: `bin/guarda-add` en un bloque y `bin/anexar` en el siguiente. El repositorio tiene
`core.fileMode=false`, así que **un `chmod +x` del disco no llega al índice** y git guarda el
guion sin su bit. La puerta lo veía, sí — pero **después de commitear**.

`bin/guarda-add` lo pone ahora él mismo, y lo dice:

```
guarda ejecutada: 2·2·2  (previsto·cambiado·anadido) en .
  bit de ejecucion puesto en el indice: bin/censo-rutas-doc
```

**Va en la guarda porque por ahí pasa todo lo que se va a commitear**, sin que nadie tenga que
acordarse de una herramienta nueva — que es justo lo que la LEY 11 dice que no funciona.
Discrimina por el `#!`: un `.txt` o un `.neon` dentro de `bin/` se queda como está, comprobado
plantando los dos a la vez.

> **Se ha hecho a la SEGUNDA, no a la tercera.** La LEY 11 pide tres fallos, pero es un **suelo,
> no un techo**: cuando el patrón ya es idéntico dos veces y el mecanismo cabe en una línea,
> esperar a la tercera es ceremonia.

## Corregido — la guía de instalación mandaba al operador a archivos que no existen

`source-docs/.../general.md` decía **cuatro veces** `src/app/database.php` y
`src/app/constants.php`. Los dos viven en **`src/app/config/`**. Es lo primero que lee quien clona
el framework, y lo seguía. Corregidas las cuatro, más una quinta ambigua en `cronjobs.md`.

**Y se generaliza, que es lo que importa**: `bin/censo-rutas-doc` lee los **38 documentos** de
`source-docs/`, extrae toda ruta de archivo que mencionan y comprueba cuáles existen.

| | |
| :-- | --: |
| Documentos leídos | 38 |
| Rutas comprobables | **16** |
| Descartadas por alcance | 18 |
| **Que no existen** | **0** |

Las 18 descartadas son ramas sueltas de diagramas de estructura (`app/`, `core/`, `view/`…),
rutas del sistema (`/etc/sysctl.d/`) y plantillas con marcador (`[Modulo]`). **Se dicen una a una
con su motivo**: un censo que descarta en silencio miente sobre su propia cobertura.

El guion lleva canario obligatorio, y hace falta: **su primera versión hacía `lstrip('./')` y se
comía el punto de `.agents/`**, reportando como rotas rutas que existían. Probado también al
revés, plantando una ruta inventada: sale en rojo y devuelve código 1.

## ⚠ Corregido — `HttpClient` compartía la URL base entre TODAS sus instancias

`HttpClient::$baseURL` estaba declarada `protected static` y el **constructor la escribía**:

```diff
-protected static $baseURL = '';
-self::$baseURL = $baseURL;      // en __construct()
-$baseURL = trim(self::$baseURL, '/');
+protected string $baseURL = '';
+$this->baseURL = $baseURL;
+$baseURL = trim($this->baseURL, '/');
```

**Construir un cliente reescribía la URL base de todos los demás, incluidos los ya construidos y
en uso.** Demostrado con el autoload real antes de tocar nada:

```
tras construir el de reCaptcha : https://www.google.com/recaptcha/api
tras construir el de Mautic    : https://mautic.interno.example/api
y el de reCaptcha, intacto     : https://mautic.interno.example/api
```

**Quién estaba expuesto**: siete archivos construyen `HttpClient` —nueve construcciones—, y **dos
guardan el cliente en una propiedad y lo usan más tarde**: `MailjetHandler` y `OsTicketAPI`. Ésos
son los que podían enviar a la URL equivocada sin que nadie viera por qué.

**Ninguno dependía del comportamiento compartido**: las nueve construcciones pasan su propia URL.
Y el radio estaba acotado: la propiedad es `protected` y **`HttpClient` no tiene ninguna
subclase**, comprobado por dos métodos independientes —búsqueda de texto en los cinco
repositorios, y reflexión sobre 452 clases cargadas—.

**La regresión se fija con una prueba, no con un comentario.** `core/http-client-request-build`
gana un quinto caso: dos clientes con destinos distintos, y cada uno tiene que conservar el suyo.
Provocada reintroduciendo la propiedad estática, **sale 11/13 con dos fallos**. Sube a 13/13.

**Cómo llegó a verse**: la puerta que nunca se corría era la que lo habría cazado — su último caso
construía un segundo cliente. Ver el bloque anterior.

## Documentación — las leyes 1 a 7 no faltaban: se llamaban «puntos». Y el archivo pasa a 24

`19-leyes.md` empezaba en la **LEY 8**, y «LEY 1» a «LEY 7» no aparecían en ningún archivo. Buscadas
en los **2.056 commits** del framework y en los de los cuatro paquetes, con el instrumento validado
por canario, **nunca existieron con ese nombre**.

**No eran un hueco: eran los siete puntos del criterio de cierre de `T0`.** El commit que introduce
la primera ley lo dice en su propio mensaje —*«T0 gana la LEY 8. Con los siete casos que explican
media campaña»*— y la colocó **inmediatamente después del punto 7**, continuando esa numeración. Las
siete primeras se quedaron con su nombre viejo, dentro del documento que nació para morir.

Recuperadas íntegras, con su procedencia anotada. **Y eran las más citadas**: `verify-integrity`
imprimía «(T0, punto 7)» en su mensaje de error, y `bin/phpstan-deadcode`, el baseline y dos
registros JSON citaban «T0, punto 5». Las cinco referencias apuntan ya a la LEY 7 y a la LEY 5.

**Cinco bloques del registro que llevaban tiempo funcionando como leyes** —y citándose como
tales— pasan a serlo: no se edita código por posición (**20**), una regla mecánica se aplica a toda
la familia o a ninguna (**21**), se sospecha del instrumento cuando sorprende y más cuando confirma
(**22**), pedir la demostración es un detector (**23**), y una prueba que pasaría con el defecto
puesto no es una prueba (**24**).

> **El número de ley NO guarda relación con el número de bloque `T`**, y el archivo lo dice en su
> encabezado. La LEY 17 no sale de T17 ni la 20 de T20; la casualidad estaba servida y habría
> vuelto el documento ilegible.

Y entra la **LEY 19 — una afirmación sobre el consumidor no se deduce del productor**, con tres
casos del mismo tramo: los avisos de `@import` predichos leyendo el SCSS cuando la consola los
tenía silenciados, una suite declarada incapaz de ponerse roja leyendo su `return` cuando `gates`
parsea el balance, y `set -u`/`pipefail` recetados para un fallo que solo caza `set -e`.

## Herramientas — `bin/anexar`, el ayudante que vivía fuera del repositorio

Anexa un fragmento a un documento **respetando los finales de línea del destino**, que es lo que
hace falta constantemente porque los documentos del registro van en CRLF y todo lo que escribe una
herramienta sale en LF. Mezclar los dos ya rompió un docblock por desplazamiento de índices.

Vivía en un bloc de notas **fuera del repositorio** y se perdió; hubo que rehacerlo de memoria.
**Un ayudante fuera del repositorio es memoria, no mecanismo** — LEY 11. Ahora está versionado, con
su bit de ejecución, y probado en sus cuatro caminos: destino CRLF con fragmento LF, destino LF con
fragmento CRLF, destino inexistente y llamada sin argumentos.

## Herramientas — los artefactos de PHPStan se declaran en LF, y el «ruido CRLF» resulta que no existía

`.gitattributes` gana `PHPStanResult.* text eol=lf`, el mismo trato que ya tenían los `*.lock`:
los escribe una herramienta que **siempre** pone LF, y forzar CRLF es pelearse con ella en cada
ejecución. Lo que se quita es el aviso `LF will be replaced by CRLF` de cada `git add` y la
reescritura que git haría al tocarlos.

**Pero la premisa de la que salía este cambio era falsa, y conviene decirlo porque estaba escrita
en un informe.** Se venía diciendo que esos artefactos aparecían modificados «solo por
normalización CRLF». No es así, y se mide en tres pasos:

| Paso | Resultado |
| :-- | :-- |
| Árbol limpio | `git status` vacío |
| `bin/phpstan`, sin tocar una línea de código | los tres artefactos quedan en **LF** |
| `git status` otra vez | **vacío** |

Con `text` puesto, git normaliza a LF **en los dos sentidos**: un archivo en LF en el árbol de
trabajo no produce diff aunque el checkout declare CRLF. Cuando esos artefactos aparecían
modificados era porque **la cifra había cambiado de verdad**.

Los `.md` no necesitan nada: de los 118 versionados, **65 están en CRLF y 53 en LF, y git no ve
modificado ni uno**. Y `git add --renormalize .` sobre el repositorio entero, con la regla nueva
puesta, toca **cero archivos ya versionados**.

## Herramientas — la puerta de `HttpClient` no podía opinar, y ahora son dos

`unit-tests:core/http-client` existía desde siempre y **no daba veredicto**: su `$checkResult`
devolvía un booleano que nadie leía, no imprimía línea de balance y no devolvía nada. `gates`
la habría contado como «no dice si pasó» aunque se hubiera corrido — y no se corría, porque
declara `network`. **Una puerta que ni corre ni puede opinar no es una puerta.**

Se parte en dos, con el mismo criterio que la prueba de Mautic:

| Suite | Qué mira | En `gates` |
| :-- | :-- | :--: |
| `core/http-client-request-build` | Cómo se **construye** la petición: URI, cuerpo, cabeceras | **sí** |
| `core/http-client` | Que la petición **sale** y que el tiempo de espera se honra | no, declara `network` |

**La partición no es una opinión, es una medición.** En `HttpClient::request()`, `requestHeaders`,
`requestBody` y `requestURI` quedan escritos en las líneas 133, 143/148 y 178 — **antes** del
`file_get_contents()` de la 182. Todo lo que se comprueba sobre ellos es independiente de que
alguien responda, y el destino de la suite local es un `data://`, que sirve el propio PHP: ni
socket, ni DNS, ni puerto.

Las dos imprimen balance, devuelven veredicto y **se han provocado**: con un fallo inyectado,
`core/http-client-request-build` sale `10/11` y `gates` la marca `[FALLÓ]`; `core/http-client`
sale `5/6`. Restauradas por `sha1sum`, cero rastros.

**El veredicto de la externa, que nadie había visto nunca: 5/5 PASADAS.** Su destino deja de ser
un token personal de webhook.site —caducan, y al caducar la prueba miente— y pasa a
`https://example.com`, reservado por la RFC 2606 para esto.

De paso desaparecen del baseline los **tres únicos errores** que PHPStan veía en la suite vieja,
que eran el mismo defecto: `request()` devuelve `string|false` y el resultado se trataba como
`string` sin estrecharlo.

## Herramientas — `gates` enumera lo que existe, y deja de callar lo que no corre

Una suite ya no se reconoce por un **prefijo en su nombre** —una lista a mano de un elemento, que se
quedó corta dos veces— sino por **estar declarada bajo `local-tests/`**. `CliActions` recuerda qué
archivo la registró y el corredor filtra por eso: una suite nueva entra **por existir**.

**Verde, rojo y NO-CORRIÓ son tres estados, y el tercero ahora se imprime.** Las excluidas a
propósito salen con su motivo y no cuentan como fallo:

```
[NO SE CORRE]   tests:mautic-batch-send  declara «network, email». Para incluirla: bin/cli gates with=external
```

Y una suite que **exista sin declarar sus efectos** detiene el corredor: el estado por defecto es
«no sé qué hace esto», y eso es un fallo, no un dato neutro.

El corredor pasa de ver **19 suites** a ver **21**.

## Herramientas — la prueba de Mautic se parte en dos

**`tests:mautic-batch-send` era una sola pieza que necesitaba claves y salía a la red**, así que
nunca entraba en el corredor de puertas y su lógica —lotear contactos, encadenar listas, los cuatro
caminos de error— no la comprobaba nadie.

Ahora el recorrido vive aparte, **sin construir el adaptador y sin leer credenciales**, y lo
comparten dos mitades:

- **`bin/cli unit-tests:core/mautic-batch-logic`** lo ejerce contra un **transporte falso** que
  hereda del adaptador real. Sin red, sin claves, sin correo. **Entra en `gates`.**
- **`bin/cli tests:mautic-batch-send`** lo ejerce con el adaptador de verdad. Sigue **fuera** de la
  pasada por defecto, declarando `network` y `email`.

No cambia ninguna clase de producción: el adaptador ya entraba por parámetro.

**Un defecto encontrado al partirla**: `createEmailTemplate()` declara `?int` y `sendEmail()` exige
`int`, así que un fallo al crear la plantilla **reventaba con un `TypeError`** en vez de devolver su
motivo. Ahora tiene guarda, y ese camino es una de las comprobaciones.

## Corregido — `systemOutFormatted()` acepta la condición de terminal, y sus dos modos se prueban

La función tiene **dos modos y los dos son la función**: embellecer la salida en una terminal, y
salir limpia hacia una tubería o un archivo de log. Detectaba con `stream_isatty(STDOUT)` y la suite
**repetía la misma detección**, así que sin terminal se saltaba 7 comprobaciones — y como el
corredor de puertas corre sin terminal, **probaba la mitad que allí no se ejecuta**.

`systemOutFormatted()` gana un tercer parámetro `?bool $isTty`, con **`null` = detectar, que es lo
de siempre**. El comportamiento por defecto no cambia; lo que cambia es que ahora se pueden
ejercitar los dos modos sin un pseudo-terminal. El envoltorio global de `AppHelpers.php` lo reenvía.

La suite pasa de **3 comprobaciones con 7 omitidas** a **17 sin ninguna omitida**.

**Comprobado, porque era la pregunta que importaba**: la rama sin terminal **no deja ni una
secuencia de escape** — la salida es idéntica al texto de entrada, también cuando el formato se
hereda de `get_config`. Lo que va a los archivos de log va limpio.

**Y un defecto de la propia suite**: devolvía `'success' => true` literal, así que **no podía poner
el corredor en rojo** pasara lo que pasara. Ahora devuelve el resultado real.

## Corregido — la caché de controladores devolvía `criteries` como array crudo

`CacheControllersManager::jsonUnserialize()` tenía un `if/else` con **las dos ramas idénticas**
justo donde iba la rehidratación de `criteries`. Al restaurar de caché, el valor entraba como el
array crudo del JSON, así que **`getCriteries()` devolvía un array donde su propia firma declara un
`CacheControllersCriteries`**.

La clase de al lado, `CacheControllersCriteries::__unserialize()`, tiene la misma forma **con el
caso especial escrito**: aquí el cuerpo se había perdido y quedó el molde.

Ahora hay un solo camino con rehidratación, y la propiedad se alinea con las dos firmas que ya la
declaraban —`setCriteries()` la exige por tipo y `getCriteries()` la promete—. **PHPStan llevaba
toda la campaña reportando esa contradicción**: son los dos errores que desaparecen.

**Si construyes un `CacheControllersManager` y NO llamas a `setCriteries()`**, el hash con el que se
nombra su archivo de caché cambia, así que esa entrada se recalcula una vez. Quien llama a
`setCriteries()` antes de `process()` —el único uso del framework— no se ve afectado.

Puerta nueva: `bin/cli unit-tests:core/cache-criteries-round-trip`.

## Herramientas — cuatro instrumentos que no medían lo que decían

- **`bin/cli gates` no corría todas las suites.** Su prefijo era `unit-tests:core/` y dejaba fuera
  `unit-tests:functions/systemOutFormatted`, que existe, corre e informa. Ahora corre **18**. Esa
  suite omite 7 de sus 10 comprobaciones sin TTY —la función suprime los ANSI a propósito—; bajo un
  pseudo-terminal da 10/10.
- **PHPStan no analizaba tres archivos versionados.** `bin/live-cache`, `bin/walk-attribute` y
  `bin/walk-routes` no tienen extensión `.php`, y PHPStan solo mira `.php` salvo que se listen como
  archivo explícito. Son las herramientas con las que se mide todo lo demás. Entraron —**812 → 815
  archivos**—, traían 9 errores y **se arreglaron los 9**: ninguno al baseline, que sigue en 888 y
  ahora significa más.
- **`verify-integrity` gana la comprobación 18**: falla cuando las **dos ramas de un `if/else` son
  idénticas**. Por árbol de sintaxis, no por expresiones regulares. Existía el defecto ocho veces en
  cuatro módulos, propagado copiando módulos, y **no hay generador que lo impida**.
- **`bin/censo`**: todo censo lleva por delante una búsqueda de control, y si el control falla el
  censo **aborta** en vez de reportar cero. Toda cifra sale con la ruta resuelta del binario y su
  versión.

## Información — cuatro módulos sin restricción por tipo de usuario

`ImagesRepository`, `Documents`, `Forms/DocumentTypes` y `Forms/Categories` **no restringen por
tipo de usuario ni en el alta ni en el listado**: cualquier usuario autenticado con permiso de ruta
puede. **No es un cambio de esta tanda**: es lo que ya hacían, detrás de una condición que parecía
decidir y no decidía. Se documenta para que quien clone el framework lo sepa y elija.

## Cambio de nombre — `compileScssServe()` pasa a llamarse `serveModuleStatic()`

> **⚠ LEA ESTO ANTES DE APLICAR EL CAMBIO DE ABAJO: `serveModuleStatic()` TAMPOCO EXISTE YA.** Una entrada posterior
> lo renombró otra vez, y el nombre vivo es **`ServerStatics::serve()`**. La cadena completa fue
> `compileScssServe()` → `serveModuleStatic()` → `serve()`. Si aplica el diff de esta entrada tal cual, se llevará un
> «Call to undefined method»: vaya a la sección «CÓMO ACTUALIZAR» de la entrada de `ServerStatics`.

**Un método que se llama «compila SCSS y sirve» y no compila nada es una mentira que se lee 24
veces**, una por cada módulo que copió el patrón, y que sobrevive a quien la escribió: por ese
nombre llegó a proponerse «implementar el TODO» de un bloque que no había que implementar.

**Si tienes módulos propios, es el único cambio que te afecta de este lote**, y es mecánico:

```diff
-return $server->compileScssServe($request, $response, $args, __DIR__ . '/Statics', [], self::staticRoute());
+return $server->serveModuleStatic($request, $response, $args, __DIR__ . '/Statics', [], self::staticRoute());
```

La firma no cambia y el comportamiento tampoco. Comprobado en cinco caminos: extensión delegada
(302 al enlace), extensión no delegada servida desde PHP (200), el enlace por el servidor web
(200), otro módulo del panel y un módulo de zona pública.

Nada dependía del nombre para otra cosa que llamarlo: no hay llamadas dinámicas, ni cadenas
literales en configuración o JavaScript, ni usos en `vendor/` o en los paquetes `piecesphp/*`.

## Eliminado — el compilador de SCSS que no compilaba

`ServerStatics::compileScssServe()` tenía dentro un bloque con `$enableSassCompilation = false;`
escrito a fuego y un `//TODO: Implementar la compilación de scss` **dentro del bloque
inalcanzable**. Un TODO en código que no se ejecuta es peor que ninguno: promete que alguien lo
terminará.

**No era una funcionalidad a medias: era una duplicación.** La compilación de estáticos vive entera
en gulp —`sassCompileModules()` en `src/gulpfile.js`, además de las tareas de núcleo, login, área
de admin y avatares—. Los `.scss` de los módulos los compila el build.

Retirado el bloque, el método era **idéntico** a `serve()`, así que ahora delega en él. Nada cambia
para quien lo llama, y los dos caminos siguen comprobados: extensión delegada → 302 al enlace;
extensión no delegada → 200 servido desde PHP.

**Queda anotado, sin tocar**: `$replacement` y `$baseStaticURL` sostenían aquel bloque y **no se
usan** —`$baseStaticURL` no aparecía en el cuerpo ni con el bloque puesto—, pero quitarlos rompería
la firma de un método público del núcleo. Y `scssphp/scssphp` se queda sin ningún consumidor
posible: **2,3 MB en `src/vendor/`** cuya única mención es una línea de créditos.

## Herramientas — tres instrumentos decían cubrir más de lo que miran

Un instrumento informa sobre el universo que mira, no sobre el que su encabezado promete, y el
denominador vive en un comentario que nadie comprueba. Medidos los seis candidatos, tres no
coincidían y **se corrigió el texto, no el alcance**:

- **`bin/cli scan-missing-lang`** decía «revisa los mensajes faltantes por traducción». Revisa **un
  idioma de seis**: `allowed_langs` trae 6 y `no_scan_langs` deja fuera 5, así que solo se escanea
  `en`. Ahora lo dice en su descripción.
- **`bin/cli snapshot`** decía «la base de datos y el árbol de archivos». Fotografía **solo
  `src/`** — el árbol servido—, así que los **251 archivos versionados fuera de `src/`** no entran
  en ninguna comparación. Ahora lo dice.
- **`bin/cli verify-integrity`** decía «comprueba cuatro cosas» y enumeraba ocho: **corre
  diecisiete**. Cubría más de lo que prometía. La cabecera deja de contar —un número a mano vuelve
  a divergir— y remite a `main()` y a `files/dev/tests.md`.

## Corregido — el enlace delegado se sustituía dejando un instante sin enlace

`ServerStatics::createDynamicSymlink()` borraba el enlace y lo volvía a crear. **Entre las dos
llamadas la ruta no existía**, y una petición que cayera dentro de esa ventana recibía un **404**;
además, un `getSymbolicLink()` concurrente devolvía `null` y la vista reenviaba a PHP, que repetía
la operación. No se reproduce en local, y este framework se clona.

Ahora el enlace se crea con nombre temporal y se publica con `rename()`, que es **atómico**: la
ruta apunta al enlace viejo o al nuevo, **nunca a nada**.

De paso, dos defectos que salieron al reescribirlo:

- **Un enlace roto no se reparaba nunca.** `file_exists()` sigue el enlace, así que uno apuntando a
  la nada parecía ausente; luego `symlink()` fallaba porque la ruta sí estaba ocupada, y se quedaba
  rota indefinidamente.
- **La `umask` se quedaba en `0`** cuando el recurso no existía: ese `return` estaba fuera del
  `try`, así que no pasaba por el `finally` que la restauraba.

El apartado a `.backup` de un archivo real que ocupe esa ruta **se queda, y ahora hace más falta**:
medido, `rename()` de un enlace encima de un archivo real lo sustituye y **el contenido se pierde
sin rastro**.

Puerta nueva: `bin/cli unit-tests:core/symlink-no-window`.

## Herramientas — `verify-integrity` gana una decimoséptima comprobación: las versiones de los paquetes

Compara la versión **instalada** de cada paquete `piecesphp/*` en `src/composer.lock` con la
**última etiquetada** en su repositorio hermano, y lo dice cuando difieren.

**No falla**: una etiqueta preparada y todavía sin empujar es un estado legítimo. Lo que no puede
es ser invisible — `v3.8.1` de `piecesphp/database` estuvo etiquetada y sin instalar durante un
bloque entero sin que nada lo dijera. Lo único que sí falla es que **no se pueda leer
`composer.lock`**, porque entonces la comprobación no miró nada.

Las etiquetas se ordenan **por versión y no alfabéticamente**, que es donde `v3.10.0` contra
`v3.9.0` se equivoca. Si el paquete no está clonado al lado, lo dice: no aprueba en silencio.

## Herramientas — `statics/server-delegated/` queda declarado como estado volátil

Servir un estático de módulo **crea un enlace simbólico** en `src/statics/server-delegated/`. Es
deliberado: los assets de cada módulo se sirven desde una ruta estable en vez de las rutas
entreveradas de cada módulo, y el enlace se crea **al servir y no al desplegar** porque un enlace
creado en el despliegue deja fuera cualquier asset que aparezca después.

Medido antes de declararlo: sobre 33 recursos sin enlace, la primera pasada crea 32 y **la segunda
crea cero**; y dos recorridos completos de 205 rutas con sus assets no crean ni rehacen ninguno,
porque en cuanto el enlace existe la vista emite su URL y lo sirve el servidor web sin pasar por
PHP.

**Cuidado si mides con esto**: sobre la ruta PHP la escritura **no es condicional** —`unlink` más
`symlink` en cada petición—, así que «una vez por recurso» describe lo que hace la aplicación, no
lo que hace el código.

**Y dos cosas anotadas sin arreglar**: el directorio **crece sin límite** —nada poda los enlaces de
un módulo retirado— y, si en esa ruta hay un archivo real en vez de un enlace, se **renombra a
`.backup` en silencio** y nadie limpia esos `.backup` jamás.

La regla 3 de `files/dev/volatile-state.json` decía «LA LISTA SOLO PUEDE ENCOGER». Ahora **encoge
siempre que se pueda**, y para crecer la entrada nueva tiene que traer la medición, el propósito
escrito y la fecha del hallazgo que la motivó.

## Documentación — la memoria de un agente es una caché del registro

`CLAUDE.md` gana una novena regla: lo que un agente guarde en su memoria persistente **solo puede
ser algo que ya viva en `.agents/context/`**, más el puntero a su sección. Si la memoria contiene
algo que el registro no tiene, son dos verdades sin puerta entre ellas, y eso **es el hallazgo**:
se resuelve subiéndolo al registro.

## Herramientas — los recorredores no pedían ni un `.css` ni un `.js`

`bin/walk-routes` dice en su encabezado que pide «TODOS los assets que aparecen en las páginas
visitadas». **No lo hacía**: su extractor solo aceptaba comillas dobles, y los ayudantes de assets
del framework emiten comillas simples. Sobre `/admin/`: 73 enlaces entre comillas dobles con
**cero** `.css`/`.js`, y 48 entre comillas simples con **los 48**.

Arreglado en los dos recorredores. Y `bin/walk-attribute` **pide ahora los estáticos de cada
vista antes de fotografiar**, porque servir un estático escribe:
`ServerStatics::createDynamicSymlink()` hace `mkdir`, `unlink` y `symlink` al servir. Con el
extractor arreglado aparecieron **61 escrituras** que la foto no veía.

Se añaden `--no-assets` y `--skipped`.

**Aviso para quien mida con estas herramientas**: el comparador de fotos **no ve que un enlace
simbólico se recree**, porque `mtime` y el hash siguen el enlace hasta el destino, que no cambia.
Solo se ve el enlace que no existía. Y `bin/cli db-restore` restaura la base, **no el árbol**: una
escritura de archivo que ya ocurrió no vuelve a ocurrir.

## Herramientas — las vistas de formulario vuelven a la pasada de recorrido

`files/dev/forbidden-routes.json` vetaba las 34 rutas `-forms-add` / `-forms-edit`. **Son GET y no
escriben** —el propio archivo lo reconocía—, y son justo donde vive el defecto que la pasada
busca: abrir un formulario que crea una fila. **Veto derogado el 2026-08-26.**

De paso, dos falsos positivos del patrón `/actions`: `actions-logs-admin-list` y
`actions-logs-admin-datatables`, que son un listado de puro leer.

Quitar los patrones no bastaba —la comparación es por subcadena y `-add` casa con `-forms-add`—,
así que el archivo gana un bloque **`allow`** que gana sobre `patterns`, con la razón escrita en
cada entrada. `bin/cli verify-integrity` falla si una excepción libera una ruta que no sea GET, si
no libera ninguna, o si no declara su razón.

## Herramientas — `bin/walk-attribute` dice su propia cobertura

**Una ruta que responde 4xx o 5xx no llegó al código que podría escribir**, así que su «sin
cambios» no significa nada. Hasta ahora se contaba junto a las que sí se ejercitaron, y el
recorrido informaba en verde. Sobre el mismo universo, el antes y el después:

```
antes:  186 rutas pedidas, 1 escriben, 0 diferencias NO declaradas          (salida 0)
ahora:  186 rutas pedidas, 136 EJERCITADAS, 50 con error
        1 escriben, 0 diferencias NO declaradas
        166 omitidas antes de pedir, con su razón                           (salida 1)
```

La pasada sale con **código 1** también por cobertura parcial, no solo por diferencias sin
declarar, y `--skipped` lista las omitidas con su motivo.

## Corregido — dos caminos de LECTURA devolvían 500

**Los endpoints de DataTables fallaban ante cualquier petición sin los parámetros que envía
DataTables.** Un enlace pegado en el navegador, un monitor o un rastreador recibían un 500:
`generateHaving(): Argument #2 ($columns) must be of type array, null given`. Eran **23 de las 24
rutas** de este tipo.

El valor por defecto de `columns` era `null` y el parámetro exige `array`. Ahora es `[]`, que es
lo correcto: **`columns` solo se lee cuando hay término de búsqueda**, así que ausente y vacío
significan lo mismo. **Para DataTables no cambia nada** —siempre manda el parámetro—; lo que
cambia es que la ruta deja de reventar sin él.

`locations-points-datatables` sigue devolviendo 404 sin cabecera `X-Requested-With`: es el único
de los 24 que se defiende, y es deliberado.

**El formulario de países abortaba si algún país no tenía región.** `CountryMapper` usaba ese
nombre nulo como índice de array; en PHP 8.4+ eso es una deprecación, y en este proyecto las
deprecaciones abortan la petición. Afectaba a alta **y** edición de países. Ahora se descarta la
fila sin nombre.

Los dos quedan fijados por `bin/cli unit-tests:core/read-paths-survive`.

## AVISO PARA DESPLIEGUES EXISTENTES — tus copias no traían las rutinas almacenadas

**Un volcado hecho con `bin/cli db-backup` de una versión anterior a esta no incluye
`DROP FUNCTION IF EXISTS` antes de crear las rutinas.** Las tablas sí iban con `DROP`+`CREATE`;
las funciones y procedimientos no.

**Qué pasa al restaurarlo:**

- Sobre una base que **ya tiene** esas rutinas, el `CREATE FUNCTION` falla con
  `1304 … already exists` y la rutina se queda **como estaba antes**, no como decía la copia.
- Sobre una base **nueva** entra bien la primera vez, pero la copia **no es reaplicable**: la
  segunda restauración falla.

**Y `bin/cli db-restore` no sabía leer esos bloques.** Partía el archivo por `;` sin entender
`DELIMITER`, así que el cuerpo de cada rutina —que lleva `;` dentro— llegaba a MySQL troceado.
En un volcado de este proyecto eran **18 sentencias fallidas de 130**.

**Qué hacer si tienes copias antiguas:** restáuralas con el cliente de MySQL, que sí entiende
`DELIMITER`, y vuelve a generar la copia con esta versión.

```bash
mysql -u <usuario> -p <base> < dumps/<archivo>.sql
```

**Desde esta versión** el volcado trae el `DROP` de las rutinas, la restauración entiende
`DELIMITER`, cadenas, identificadores y comentarios, y **`unit-tests:core/db-restore` restaura un
volcado producido por `db-backup`** —no uno fabricado por la propia prueba— comprobando que las
rutinas, las tablas y las vistas llegan.

`db-restore` **ignora y anuncia** `USE`, `CREATE DATABASE` y `DROP DATABASE`: eligen la base por
su cuenta y anularían el parámetro `database=`.

## AVISO PARA DESPLIEGUES EXISTENTES — TUS COPIAS DE SEGURIDAD NO RESTAURAN

**Si tienes copias hechas con `bin/cli db-backup` de una versión anterior a esta, no sirven tal cual:
restaurarlas deja a TODOS los usuarios sin poder entrar.** No es una sospecha; está medido de punta a
punta: volcado, restaurado en una base de usar y tirar e intento de login.

**Los datos no están perdidos**, pero recuperarlos pide un paso manual sobre las contraseñas: pídelo a
quien mantenga el framework.

**Las copias hechas desde esta versión no necesitan nada de esto**, y
`bin/cli unit-tests:core/db-backup-round-trip` comprueba el viaje entero en cada ejecución
—exportar, restaurar en una base de usar y tirar, y entrar— para que no vuelva a pasar.

## AVISO PARA DESPLIEGUES EXISTENTES — los emoji NO se están guardando (arreglado en el paquete)

**Medido, no supuesto**: un emoji escrito por el ORM en una columna de texto se guarda como
`?`. `HEX()` sobre lo guardado devuelve `3F` donde PHP mandó `F09F9880`, y `save()` devuelve
`true` mientras ocurre.

La causa no está en las tablas —**189 de 193 columnas de texto ya son `utf8mb4`**— sino en la
conexión: `piecesphp/database` ejecuta `SET CHARACTER SET`, que fija `character_set_client` y
`character_set_results` pero deja `character_set_connection` en el juego **de la base de
datos** (`utf8mb3`). `SET NAMES` es el que fija los tres. El `charset = 'utf8mb4'` de
`config/database.php` es correcto y queda anulado ahí.

**Arreglado en `piecesphp/database` v3.2.1**, que cambia esa sentencia por `SET NAMES`.
Comprobado de punta a punta escribiendo por el ORM: los bytes vuelven idénticos.

**Antes de actualizar el paquete, mide tu propia base.** Con la conexión en `utf8mb4`, todo
camino que hoy escriba **bytes que no son UTF-8** en una columna de texto —imágenes crudas, por
ejemplo— pasa de corromperse en silencio a **fallar con el error 1366**. Es mejor
comportamiento, pero es un cambio de comportamiento.

```bash
bin/cli scan-invalid-utf8     # qué columnas de texto tienen hoy valores no-UTF-8
```

Si sale vacío, actualiza sin más. **En la base de desarrollo de este repositorio sale vacío,
pero eso no dice nada de la tuya: son 89 filas en total y 22 de las 36 tablas están vacías.**

**Y recomendado aparte, para instalaciones existentes**, porque la base se creó sin juego
explícito y quedó en `utf8mb3`:

```sql
ALTER DATABASE `tu_base` CHARACTER SET utf8mb4 COLLATE utf8mb4_bin;
```

Con v3.2.1 puesto no hace falta —`SET NAMES` manda sobre el valor por defecto—, pero sin él
cualquier cosa que lea ese valor por defecto sigue heredando `utf8mb3`. Ver T28bis y T37 en
`.agents/context/18-siguientes-ventanas.md`.

> **TERCERA PATA, cerrada en v3.5.0.** Faltaba una: `SchemeCreator` emitía
> `DEFAULT CHARSET=utf8` **escrito a fuego**, así que **una tabla recién generada nacía en
> `utf8mb3`** por mucho que la conexión fuera `utf8mb4`. Las tres patas —conexión, valor por
> defecto de la base y DDL generado— eran **independientes**, y arreglar una no arreglaba las
> otras. Las tablas ya creadas no cambian.

## AVISO PARA DESPLIEGUES EXISTENTES

**Hasta la `v7.1.0` incluida**, el inicio de sesión creaba datos de 2FA para cuentas que nunca lo
pidieron. Son inertes.

**Al actualizar:** no hace falta purgarlos.

**Antes de actualizar**, `bin/cli scan-invalid-utf8` avisa de otra cosa que sí puede romper:
desde esta versión `json_encode()` lanza en vez de devolver `false`, así que un texto con
UTF-8 inválido en base de datos pasa de servir un dato ligeramente mal a cortar la petición.

## Cambios que rompen compatibilidad — `mapbox` se fija por versión exacta y `v2.6.0` se retira

El alias `mapbox-v3.4.0` estaba fijado a `^3.4.0`: **el nombre fingía fijar y el acento hacía lo
contrario**. Hoy resolvía a 3.19.0 —lo desplegado— mientras `node_modules` ya traía 3.21.0, y el
contenido se movía solo en cada `npm install` sin que nada lo dijera.

- `mapbox-v3.4.0` (`^3.4.0`) pasa a **`mapbox-v3.19.0` (`3.19.0`, exacta)**, y la carpeta
  `statics/plugins/mapbox/v3.4.0/` a **`v3.19.0/`**. Se fija en **lo que ya estaba desplegado**:
  no cambia ni un byte de lo que se sirve. Subir a 3.21.0 es otra decisión.
- `mapbox-geocoder-v2.3.0` pasa a versión exacta. No había derivado, pero podía.
- **`mapbox-v2.6.0` se retira entera** —carpeta y alias—: no la referenciaba nadie, y eran 1 MB
  versionados.

**Después de actualizar hace falta `npm install`**: los archivos de bloqueo aún nombran el alias
viejo y `bin/node/copyDependencies.php` avisa —ahora sale con código 1— si no encuentra el origen.

Y ese guion **ya no copia callando**: compara `sha1` antes de sobrescribir y dice qué va a cambiar.
Un cambio de contenido ahí es un cambio de librería, y el mensaje del commit tiene que decirlo.

## Corregido — un guardado sin cambios ya no reabre un rechazo en NINGÚN módulo

El arreglo anterior hacía que `updated` solo saltara si la base decía que cambió una fila, y eso
**no bastaba en tres de los cuatro tipos con aprobaciones**: sus mappers sellan `updatedAt` y
`modifiedBy` en cada guardado, así que la fila cambiaba de verdad.

Ahora el escuchador pregunta **qué** campos cambiaron y los compara con los que el manejador
declara como sellos de auditoría. **La declaración la exige la interfaz**: un manejador nuevo que
no la haga no compila, mientras que una lista central se quedaría corta en silencio.

**El escuchador conserva su intención**: reabrir un rechazo al editar sigue pasando, y hay una
comprobación que lo demuestra.

**Requiere `piecesphp/database` >= 3.8.0.** Con una versión anterior se mantiene la conducta
anterior y la suite `unit-tests:core/updated-event` **falla** para decirlo.

## Herramientas — cada suite declara qué hace fuera de sí misma

Correr las puertas hacía una petición a un servicio de terceros, y lo único que impedía que
mandara correos era que una suite no llevaba el prefijo `core/`. Eso no es una guarda: es un
accidente de nombre.

Ahora cada suite declara sus efectos con `setEffects()` —`network`, `email`, `database`, `files`,
o `none`—. `bin/cli gates` **no corre** las que salen a la red o mandan correo salvo que se pida
con `with=external`, y **una suite sin declarar no se corre y cuenta como fallo**: el estado por
defecto es «no sé qué hace esto».

De paso, dos suites salen del limbo: `core/database-exporter` y `core/helpers-directories` ya
imprimen su veredicto. La segunda además **devolvía éxito pase lo que pase** mientras imprimía
`[FALLÓ]` por pantalla.

## Herramientas — el análisis estático pasa a mirar el repositorio entero

`paths` apuntaba a `src/app` y `src/index.php`: **802 de los 835 archivos PHP versionados**. Ahora
apunta a la raíz, y lo que queda fuera son **7 exclusiones declaradas con su razón** en
`files/dev/phpstan-universe.json` —todas de código de terceros o regenerado—. **El código nuevo
nace analizado**; antes nacía invisible.

La cifra de referencia sube de **859 a 889**, y **no es una regresión**: los 859 del universo viejo
no se mueven ni en un error, y los 30 nuevos vienen de 5 de los 11 archivos que entran. El
trinquete gana un tercer término para poder declararlo —`+ N destapados`— sin que una regresión
pueda disfrazarse de lo mismo.

**Las cifras de PHPStan anteriores a esta versión no son comparables con las siguientes**: no
cuentan sobre el mismo conjunto de archivos.

## Herramientas — `verify-integrity` gana una decimotercera comprobación

El análisis estático mira `src/app` y `src/index.php`: **802 de los 835 archivos PHP versionados**.
De los 33 restantes, 22 son `src/adminer` —de terceros, excluido a propósito en el neon— y **11 no
estaban declarados en ninguna parte**, entre ellos las herramientas de `bin/` y el corredor de
tareas de Composer.

Un archivo fuera del árbol analizado no sube la cifra de errores: **la deja igual de verde midiendo
menos**. Ahora `files/dev/phpstan-universe.json` declara qué queda fuera y por qué, y la
comprobación falla si aparece PHP en un sitio que nadie declaró.

## Corregido — guardar sin cambiar nada reabría un elemento rechazado

El evento `updated` se despachaba cuando `parent::update()` devolvía `true`, y eso significa «la
sentencia corrió», no «cambió una fila». Consecuencia: abrir un elemento **rechazado**, guardarlo
sin tocar nada y verlo volver a la cola de aprobación como pendiente.

Ahora `updated` solo se despacha si la base dice que cambió algo. **El escuchador no se toca**:
reabrir un rechazo al editar es intención, y se conserva —hay una comprobación que lo demuestra,
no solo que no lo rompe—.

> **Lo que este arreglo NO cura, y conviene saberlo**: un mapper que sella `updatedAt` en su
> propio `update()` **cambia la fila él mismo**, así que para él «guardar sin tocar nada» sigue
> siendo un cambio real. Son 3 de los 4 tipos con aprobaciones y 16 mappers en total. Medido, no
> supuesto.

**Requiere `piecesphp/database` >= 3.7.0.** Con una versión anterior se mantiene la conducta de
siempre y la suite `unit-tests:core/updated-event` **falla** para decirlo: no se omite en silencio.

## Dependencias — `piecesphp/database` sube a v3.6.0

Trae `SchemeCreator::createScript()` y `dropScript()`, el charset `utf8mb4` configurable, el
arreglo de los campos nulables y las excepciones tipadas con el nombre del campo. Con ellas
`bin/cli scheme-create`, `bin/cli scheme-drop` y la suite del esquema **dejan de guardarse a sí
mismas**: llevaban desde el 24-08 sin poder correr.

**Aviso para quien actualice**: `composer` toma el PHP del PATH, y si ese PHP está por debajo del
piso declarado en `composer.json` **se niega a resolver sin tocar nada**. Aquí el PHP por defecto
es 8.1.34, así que la orden que funciona es:

```bash
php8.5 /usr/bin/composer update piecesphp/database
```

## Herramientas — `bin/cli gates`, y una suite omitida deja de contar como verde

Una suite que no corre no informa: calla. Y el silencio se lee como verde.
`unit-tests:core/scheme-sql-round-trip` llevaba desde el 24-08 omitiéndose sola —el paquete
instalado no traía `createScript()`— sin que nada lo señalara.

`bin/cli gates` corre todas las suites y **termina en 1 si alguna no dijo si pasó**. No hay lista
que mantener: las suites salen del registro de acciones y el veredicto se exige por la línea de
balance que cada una imprime. Acepta `only=<trozo>` para acotar.

Y la suite del esquema **ya no se omite**: si el paquete instalado se queda corto, **falla**.

## Herramientas — la lista de rutas que un recorredor nunca pide vive en un solo sitio

Estaba **copiada** en `bin/walk-routes` y en `bin/walk-attribute`. Los 17 patrones aún coincidían
—el comentario que explica por qué se mira también la URL, no: ya solo estaba en uno de los dos—,
pero lo que cuesta que diverja aquí no es ruido: un recorredor que pida una ruta de escritura
**escribe creyendo que solo lee**, y se lo atribuye a una ruta de lectura.

Ahora los patrones viven en `files/dev/forbidden-routes.json` —legible por cualquier herramienta,
en PHP o no— y la comparación en `bin/tools/forbidden-routes.php`. Los dos recorredores lo leen de
ahí y **ninguno conserva copia**; si el archivo falta, el recorrido no empieza.

Comprobado sobre las **351 rutas** del inventario real: los veredictos son los mismos que antes,
**cero cambios**. Y `verify-integrity` gana una **duodécima comprobación** que falla si alguien
vuelve a declarar la lista en otro sitio o deja de leerla de ahí.

## Herramientas — `verify-integrity` gana una undécima comprobación

La lista de tablas con acuñado de slug de `files/dev/volatile-state.json` está **copiada** de lo
que descubre el código, y una vez escrita nada detectaba que divergiera: añadir un módulo con
`preferSlug` la dejaba corta **en silencio**, y el recorrido de atribución reportaría un hallazgo
falso. Ahora se comparan las dos y falla si no coinciden.

## Cambios que rompen compatibilidad — `QueueJobMapper::migrate()` se retira

Era un experimento de migraciones anterior a este sistema y está superado por
`bin/cli scheme-create`. **No estaba dormido**: colgaba de `EVENT_INIT_ROUTES_NAME` en
`config/final-configurations-includes/event-listeners.php`, así que **ejecutaba DDL en cada
petición local**.

Comprobado antes de retirarlo: `databases/piecesphp_structure.sql` aplicado sobre una base vacía
crea **35 tablas**, `pcsphp_jobs_queue` entre ellas. El camino normal ya la creaba.

Con esto **la aplicación no ejecuta DDL en ningún sitio: solo lo emite.** Los únicos que lo
ejecutan son las suites y las tareas de terminal, que son herramientas.

## Añadido — `bin/cli db-restore`, el inverso de `db-backup`

Restaurar era `mysql < archivo.sql`: a mano y sin registro. Ahora es una tarea, con las mismas
salvaguardas que el respaldo y **con confirmación explícita**, porque destruye datos:

```bash
bin/cli db-restore file=dumps/x.sql confirm=yes
```

Deja rastro en `files/dev/last-restore.json` —fecha, archivo de origen y base destino—, y
`bin/walk-attribute` lo lee para avisar cuando una pasada de atribución **no** se hace sobre una
base recién restaurada. Probado de verdad: respaldar, cambiar, restaurar y comprobar que volvió.

## Corregido — abrir un formulario de contenido genérico creaba una fila

`GenericContentPseudoMapper::__construct()` insertaba la fila de configuración si no existía, así
que **abrir el formulario y no enviarlo dejaba rastro**. Ahora la fila se crea al guardar, que es
cuando alguien decide que existe.

Comprobado antes de tocarlo que **nada dependía de que existiera**: el valor por defecto vive en
las propiedades de la clase —apartando la fila, la lectura sigue devolviéndolo— y `save()` ya
inserta cuando no hay fila.

Se van con ello dos ramas muertas y el parámetro `$setDefaultData`, que no hacía nada.

## Corregido — tres guiones de `bin/` no arrancaban por sus finales de línea

`.gitattributes` pone todo el repositorio en CRLF y luego exceptúa **guion por guion** los de
`bin/`. Los que se añadieron después no estaban en esa lista, así que git les daba CRLF y **`env`
buscaba un intérprete llamado «php\r»**: `bin/live-cache`, `bin/walk-attribute` y
`bin/pieces-completion.bash` estaban inservibles.

La lista de excepciones se sustituye por **un patrón**, `bin/* text eol=lf`, para que no vuelva a
quedarse corta. Y la comprobación de `verify-integrity` sobre los guiones de `bin/` pasa a mirar
también el final de la primera línea, no solo el bit de ejecución.

## Corregido — dos peticiones simultáneas podían acuñar dos slugs y matar una URL

Los 14 mappers con `preferSlug` rellenan el slug de las filas que no lo tienen —las que entran por
importación o alta directa en base—. Esa escritura **no estaba condicionada**: dos peticiones
simultáneas generaban dos slugs distintos y ganaba la última, así que **si el primero ya había
salido en una URL, esa URL moría**. Ahora el `UPDATE` va condicionado a que el slug siga nulo, y
quien pierde la carrera relee el del ganador.

- La escritura sale del cuerpo de `objectToMapper()` a `mintPreferSlugIfMissing()`, en el trait
  `PreferSlugMinter`, y el docblock del convertidor **declara que escribe**.
- Los dos mappers que no comprobaban el nombre antes de acuñar pasan a hacerlo, como los otros
  doce. Comprobado contra `information_schema` que sus columnas de nombre son `NOT NULL`, así que
  el comportamiento de esos módulos no cambia.
- Las 14 tablas quedan declaradas en `files/dev/volatile-state.json` como escritura legítima en
  camino de lectura.

## Añadido — tarea para rellenar los slugs de golpe

`Terminal\Jobs\PreferSlugsFiller`, registrada como cronjob «Rellenar slugs pendientes», rellena
los `preferSlug` que falten sin esperar a que alguien navegue — pensada para después de una
importación. **No sustituye al relleno perezoso: lo complementa**, y usa exactamente el mismo
método, así que no hay dos versiones del mismo acuñado.

## Rendimiento — tres módulos generaban su `CREATE TABLE` en cada petición

`Documents`, `Forms\DocumentTypes` y `Forms\Categories` tenían el volcado comentado pero **el
`$sqlCreate` de encima no**: dentro de `routes()` instanciaban el mapper y generaban el DDL
completo en cada petición, para tirarlo. **Los seis sitios que tenían ese idiom se han borrado** —`Documents`,
`Forms\DocumentTypes`, `Forms\Categories`, `Newsletter`, `ImagesRepository` y `EventsLog`—,
tanto los vivos como los que ya estaban comentados: `bin/cli scheme-create module=<Nombre>` hace
lo mismo mejor, para cualquier módulo, y avisa si un mapper no se puede instanciar.

Medido: **0 consultas** y **1,228 ms** por petición de trabajo puro tirado.

## Añadido — dos interruptores para el área pública

Dos constantes booleanas en `config/constants.php`, **las dos en `true` por defecto**, así que
ningún despliegue cambia de comportamiento al actualizar:

```php
define('PUBLIC_AREA_VIEWS', true);          // las cinco vistas públicas GET
define('PUBLIC_AREA_CONTACT_FORMS', true);  // el destino POST del formulario de contacto
```

Están separadas a propósito: `contact-forms-general` es un **POST** y no aparece en ningún
listado de vistas, así que apagarlo por accidente no se vería — el formulario seguiría pintándose
y dejaría de enviar. Medido: con las vistas apagadas y el contacto encendido, 350 rutas → 345,
cero `public-*`, y el destino del formulario sigue ahí.

## Corregido — apagar el área pública rompía el envío de correo

Cinco plantillas pedían la URL de baja **sin** `$silentOnNotExists`, así que con la ruta
`public-unsubscribe` sin registrar lanzaban `RuntimeException` **al renderizar** —dentro de una
cola o de un cronjob—, no un `href` vacío. Ahora la piden en silencio y **omiten el bloque de baja
entero** si no hay URL. Ni enlace vacío ni excepción, y **no depende de ninguna constante**.

Se retira además la línea comentada `//self::$startSegmentRoutes = uniqid();`, que "apagaba" el
área pública **escondiéndola** tras un segmento impronunciable: las rutas seguían existiendo y
respondiendo. `$startSegmentRoutes` sigue siendo un prefijo legítimo y no cambia.

## Corregido — el enlace de baja de los correos llevaba un apóstrofo de más

`UNSUSCRIBE_TEXT` emitía `<a href='{{url}}'' target='_blank'>` en los **seis idiomas**. Sale
hacia el usuario en cada correo que manda el sistema. Un carácter por archivo.

## Cambios que rompen compatibilidad — el bloque `$showSQL` ya no existe

Diez `<Modulo>Routes` traían un bloque `$sqlCreate = […]; $showSQL = false; if ($showSQL) {…}`
que había que **editar en el código fuente** para volcar el `CREATE TABLE` de ese módulo. Se
retira entero, junto con 13 `use` que quedaban huérfanos.

**En su lugar**, y para todos los módulos, no solo para diez:

```bash
bin/cli scheme-create module=MiModulo    # el CREATE, ordenado por claves ajenas
bin/cli scheme-drop   module=MiModulo    # su inverso
```

**Si tu despliegue usaba el bloque**, el procedimiento cambia; el resultado, no. La regla 7 de
`CLAUDE.md` y cinco documentos de `.agents/context/` quedan corregidos en este mismo cambio.

> **Y una corrección de contabilidad nuestra**: estos diez `if ($showSQL)` **nunca contaron como
> ramas muertas**. Su supresión en `bin/phpstan.neon` estaba colocada **fuera del rango que mide
> `bin/phpstan-deadcode`**, así que no aparecían ni en el baseline ni en la deuda. Medido en las
> dos direcciones: con y sin los bloques, 285 ramas y 859 errores. Una supresión fuera del rango
> que mide la deuda no la reduce: **la esconde del instrumento que la cuenta.**

## Corregido — los mappers declaraban `int` en 39 claves ajenas que apuntan a un `bigint`

**Ahora el esquema entero se puede generar desde los mappers.** `bin/cli scheme-create module=all`
produce un script que MariaDB acepta de principio a fin; antes rechazaba 21 de las 34 tablas.

39 declaraciones en 21 archivos pasan de `'type' => 'int'` a `'type' => 'bigint'`: son las que
referencian `pcsphp_users.id`, que es `bigint`. **No hay migración**: las columnas reales ya eran
`bigint(20)` en el esquema que se distribuye —comprobado contra `information_schema` y contra
`databases/piecesphp_structure.sql`—; el que mentía era el mapper.

**El único cambio observable es el DDL generado**, y se comprobó por partes:

- `length` no interviene: `EntityMapper::$typesValidateLength` es `['varchar','text']`, y
  `SchemeCreator::$typesLengths` solo tiene `varchar => 255`, así que ni `int` ni `bigint` llevan
  longitud.
- La conversión no cambia: los dos tipos van juntos en `$typesInt` y en `SQLTypesEnum::INTEGERS`,
  y sus únicos consumidores los tratan como un grupo.
- Nadie compara: **cero** ocurrencias de `== 'int'`, `case 'int'` o `in_array('int'` en toda la
  aplicación.
- Y medido sobre datos: leída una fila real **por el mapper** antes y después, en las tres
  columnas que tienen filas, el valor devuelto y su tipo son idénticos.

**Lo que NO entra**: 20 claves ajenas cuya tabla referenciada es realmente `int(11)` —comprobadas
una a una contra `information_schema`— y 65 campos `int` que no referencian nada.

## AVISO PARA DESPLIEGUES EXISTENTES — el `CREATE TABLE` que el framework genera NO SE APLICA

**Versiones afectadas: todas hasta esta entrada.** La regla del proyecto dice que el SQL de las
tablas se genera con `SchemeCreator` y no se escribe a mano, y **hasta ahora esa regla no se podía
cumplir**: de las 34 tablas del proyecto, **21 eran rechazadas por MariaDB** al aplicar el script
generado. **Corregido en esta misma versión** (ver la entrada de las 39 claves ajenas); esto queda
como explicación de qué había y por qué.

| Causa | Cuántas |
| :-- | --: |
| `errno 150` — la clave ajena declara `int` y la columna referenciada es `bigint` | 19 |
| `Unknown data type: 'test'` — `'text'` mal escrito en `SystemApprovalsMapper` | 1 |

**Causa raíz: 38 claves ajenas declaran un tipo distinto del de la columna que referencian**,
en 19 archivos de mappers. Casi todas son `createdBy` / `modifiedBy` apuntando a
`pcsphp_users.id`, que es `bigint`.

**Por qué nadie lo había visto.** Once módulos tapan el síntoma con un reemplazo de cadenas
sobre el SQL ya generado, dentro de su bloque `$showSQL`:

```php
'createdBy` int' => 'createdBy` bigint',
```

Los módulos que no tienen ese bloque —`Documents`, `Forms`, `ImagesRepository`, `EventsLog`—
no tapan nada. Y el parche arregla la salida **dejando el mapper mintiendo**, que es justo lo
que la regla existe para evitar.

**Qué hacer al actualizar:** nada urgente — las tablas ya creadas funcionan. Pero **si vas a
regenerar una tabla desde su mapper, revisa el tipo de sus claves ajenas antes de aplicar el
script**. La comprobación está en `bin/cli unit-tests:core/scheme-sql-round-trip`, que hoy
**sale en rojo a propósito**: 5 de 8, y las tres que fallan son las que describen este defecto.

**Y el DDL generado es `CHARSET=utf8 COLLATE=utf8_bin`** —`utf8mb3`— escrito a fuego en
`piecesphp/database`. La conexión va en `utf8mb4`; las tablas recién generadas, no.

## Dependencias

- **`piecesphp/database` v3.5.0**: el DDL generado pasa de `utf8mb3` a `utf8mb4` y el juego de
  caracteres se vuelve configurable. Era la **tercera pata** del problema de los emojis —conexión
  (v3.2.1), valor por defecto de la base (documentado) y DDL generado—, y las tres eran
  independientes: arreglar una no arreglaba las otras. **Las tablas ya creadas no cambian.**

## Herramientas — el SQL del esquema, de ida y de vuelta

- **`bin/cli scheme-create module=<Nombre>|all`**, el gemelo de `scheme-drop`. Las dos
  **descubren** los mappers (`Mappers/`, `SubMappers/`, `ORM/` y `app/model`), sacan el orden
  del grafo que los propios `$fields` declaran en `reference_table`, y **emiten: no ejecutan**.

  Hasta ahora la única forma de sacar el `CREATE` de un módulo era **editar el código fuente**
  y poner un literal `$showSQL` en `true`. Eso no es una herramienta: es un interruptor
  escondido, y solo existía en once de los módulos.

  Necesita `piecesphp/database` **v3.4.0**; con una versión anterior avisa y sale con 1.

- **`Terminal\Tasks\SchemeSqlTask`**: el descubrimiento, compartido por las dos tareas. Dos
  listas serían dos verdades.

- **Corregido: una clase abstracta en `Tasks/` tumbaba la CLI entera.** `TerminalController`
  instancia por reflexión todo lo que encuentre allí con un método `route()`, y una abstracta
  cumple `method_exists()` pero revienta `call_user_func`. Ahora se comprueba `isAbstract()`.

- **Corregido: la tarea contaba mappers y decía «tablas».** Dos mappers pueden compartir tabla
  y el resolvedor los funde: decía «34 tablas» con 33 sentencias en el script. Ahora cuenta las
  sentencias y avisa de la diferencia.

## Corregido — `scheme-create`/`scheme-drop` se dejaban mappers fuera

El descubrimiento buscaba mappers solo en carpetas llamadas `Mappers`, `SubMappers` u `ORM`.
`UserProfileMapper` vive suelto en `Profile/`, así que **la tabla `user_system_profile` no
entraba en el script**. Ahora se recorre el módulo entero descartando por lista negra
—`Views`, `Statics`, `lang`, `lang-public`, `Exceptions`, `Controllers`—.

| | Antes | Después |
| :-- | --: | --: |
| Mappers descubiertos | 34 | **35** |
| Tablas en `scheme-create module=all` | 33 | **34** |

Si tu despliegue tiene mappers fuera de esas tres carpetas, **el script que generabas estaba
incompleto**.

## Herramientas — `bin/walk-attribute`: qué ruta de lectura escribe, y dónde

Toma una foto de la base de datos y del árbol de archivos **después de cada petición** y le
atribuye la diferencia a la ruta que la provocó. Hasta ahora los caminos de lectura que escriben
aparecían de uno en uno y por accidente.

En su primera corrida, sobre 184 rutas GET, encontró **dos escrituras no declaradas** y sus
causas, que son la misma de siempre —crear al leer—:

- **`NewsCategoryMapper::objectToMapper()` llama a `update()`**: un convertidor que escribe.
  Listar categorías actualiza filas. **Y no es un caso aislado: 14 de los 21 mappers que
  implementan `objectToMapper()` llaman a `update()` dentro.**
- **`GenericContentPseudoMapper::__construct()` llama a `save()`**: construir el objeto crea la
  fila. Es la misma forma del defecto de `UserDataPackage` en el login.

**No se ha corregido ninguno**: se documentan con su medición para decidir sobre ellos.

## Herramientas — la caché de la aplicación viva deja de depender de la memoria

- **`bin/live-cache`** y **`bin/tools/live-cache.php`**. Cualquier medición A/B contra la web
  tiene que invalidar la caché de código antes de medir; esa regla estaba escrita y **falló tres
  veces**. Ahora vive en el arnés: `bin/walk-routes` la llama al arrancar, y si no puede
  invalidar **aborta con código 1**.

  La espera no es folclore: sale de `php-fpm<version> -i` —**al binario, no a los `.ini`**, que
  no mencionan OPcache porque viene compilado— y es
  `max(revalidate_freq, file_update_protection) + 1`. En el entorno de referencia, 3 segundos.

  **`bin/live-cache --self-test` provoca la trampa y después la desactiva**: exige ver el código
  viejo sin invalidar y el nuevo invalidando. Una puerta vista solo en verde no se ha visto
  funcionar.

## AVISO PARA DESPLIEGUES EXISTENTES — un tipo mal escrito deja el campo SIN VALIDAR

**Versiones afectadas: todas.** `SystemApprovalsMapper` declaraba `'type' => 'test'` —«text» mal
escrito— en el campo `reason`. **No es un error de escritura suelto: es la sonda de un problema
de fondo**, porque ninguna capa lo rechazaba.

**Qué hace cada capa con un tipo que no existe, medido:**

| Capa | Qué hace |
| :-- | :-- |
| `EntityMapper::validateType()` | **Devuelve `true` para TODO** — cadenas, arrays, objetos, `null`. El campo **deja de validarse** |
| `EntityMapper::castPHPToSQLTypes()` | **No convierte**: el valor sale tal como entró |
| `SchemeCreator` | **Lo copia tal cual al DDL** |
| `MetaProperty` | **Lanza.** La única que lo rechaza |

La guarda que el ORM ya trae —`$onlySupportedTypes`— **no la activa ningún mapper**, y además
solo salta al asignar un valor, no al construir el mapper.

**Corregido** (`'test'` → `'text'`; la columna real ya era `text NULL`, así que no hay migración)
**y con puerta**: `verify-integrity` gana una décima comprobación que exige que todo
`'type' => '…'` declarado en un `$fields` esté en el vocabulario de `EntityMapper`. **370 tipos
comprobados, uno cazado.**

**Revisa tus mappers propios.** Si algún despliegue añadió campos con un tipo inventado, ese
campo lleva sin validarse desde que se escribió. `bin/cli verify-integrity` los lista.

## Corregido — las rutas de prueba de colas solo existen en local

`/pcsphp-testing/queue-request/` y su `handle` se registraban **también en producción**, públicas
y sin login, guardadas solo por `requestIsSameDomain()`. Ahora van dentro de `is_local()`.

Y con eso **dejan de necesitar la ocultación por `uniqid()`**: tenían el nombre de ruta generado
al azar en cada arranque, lo que impedía usar `get_route()` y obligaba a la vista a escribir la
URL a mano —rompiendo la regla 3 del proyecto como consecuencia forzosa—. Ahora tienen nombre
normal y la vista genera su URL.

**`img-gen` no cambia**: es otro grupo del mismo archivo, lo usan la portada, el cropper y las
tarjetas de subida, y conserva su guarda de `requestIsSameDomain()` contra el hotlinking.

## Corregido — cuatro guiones de `bin/` llegaban sin permiso de ejecución

El repositorio tiene `core.fileMode = false`, así que `chmod +x` funciona en el disco y **git
no lo registra**. Cuatro guiones estaban en el índice como `100644` y llegaban sin permisos a
cualquiera que clonara: `bin/rector`, `bin/package-css`, `bin/pieces-completion.bash` y
`bin/node/copyDependencies.sh`.

**`bin/rector` ni siquiera era ejecutable aquí**: devolvía «Permiso denegado» (salida 126) pese
a estar documentado en `CLAUDE.md` como una de las herramientas del proyecto.

Arreglados los cuatro, y **`verify-integrity` gana una novena comprobación** para que no vuelva
a pasar: todo archivo de `bin/` que empiece por `#!` tiene que estar en el índice como `100755`.

## Pruebas

- **`bin/cli unit-tests:core/scheme-sql-round-trip`**: descubre todos los mappers, emite el
  `CREATE` y el `DROP`, y **se los da a MariaDB** en una base de usar y tirar. **El juez es la
  base, no el generador.**
- `files/dev/tests.md` marca ahora, suite por suite, **quién la juzga**: un oráculo externo
  —base de datos, sistema de archivos, servidor HTTP— o ella misma. Las que se juzgan solas son
  las que hay que mirar con más cuidado: dos de ellas ya dejaron pasar un defecto.

## Seguridad

- **Las copias de `db-backup` se restauran ahora de punta a punta**: tras restaurar, los usuarios pueden entrar. Si
  tienes volcados viejos, no están perdidos: ver «AVISO PARA DESPLIEGUES EXISTENTES — TUS COPIAS DE SEGURIDAD NO
  RESTAURAN». `bin/cli unit-tests:core/db-backup-round-trip` fija el viaje de ida y vuelta, y se validó rompiéndola.

- **Dos llamadas a funciones que podían devolver `false` se protegen**, en `src/index.php` y en
  `PublicationsController`. Con esto quedan **cero** errores de la forma «llamar a un método sobre un `false`».

- **El doble factor se activa al confirmarlo**, no al prepararlo; el QR escaneado sigue siendo válido.

- **Comprobar credenciales ya no escribe en la base de datos.** Los buscadores de `OTPSecretsUsersMapper` pueden
  devolver `null`: si tu código los usa, compruébalo.

## Rendimiento

- **El registro de traducciones faltantes deja de escribir en producción.** `Config::i18n()`
  anotaba cada cadena sin traducir sin ninguna guarda de entorno, así que **cada página servida
  hacía un `file_exists()` por llamada a `__()`** también en producción — y una página del panel
  hace decenas. Su consumidor es `bin/cli scan-missing-lang`, una herramienta de desarrollo, y
  nadie lee ese directorio en producción.
    - Ahora solo anota en local, **coherente con la decisión ya tomada para las deprecaciones**:
      abortan en local, se registran en producción. No se inventa criterio nuevo.
    - Comprobado en las dos direcciones: con `is_local()` verdadero escribe, con falso no.
    - **Salida disponible y no implementada**: una constante de módulo permitiría recolectar
      contra tráfico real en un despliegue. Se descarta por ahora porque sería un interruptor
      más que configurar por una necesidad hipotética.

- **`clean-logs` vacía `missing-lang-messages`.** También crece en local: había **1.586
  anotaciones en 61 grupos** y nada las retiraba.

- **Un `foreach` duplicado y anidado sobre sí mismo, en el mapper del que se clonan los
  módulos.** `objectToMapper()` recorría `$defaultMetaPropertiesValues` dentro de un bucle
  idéntico sobre el mismo array: **cada meta-propiedad por defecto se comprobaba una vez por
  cada meta-propiedad por defecto.** El resultado es correcto —la asignación es idempotente—
  pero el trabajo es cuadrático, y `objectToMapper()` corre por cada fila que se hidrata.
    - **16 sitios, y en 10 el array NO está vacío**, así que el bucle interno se ejecutaba de
      verdad. Entre ellos `PublicationMapper` y `PublicationCategoryMapper`, **el módulo
      canónico**: cualquier módulo clonado desde ahí lo heredaba.
    - No es limpieza de código muerto: **es un defecto de rendimiento en la plantilla**, y por
      eso se cuenta aquí y no en «Cambios internos».
    - Barrido de comprobación tras el arreglo: **cero `foreach` anidados sobre sí mismos en
      todo `src/app`** y cero en los cuatro paquetes. Ningún módulo se quedó fuera.

- **`createOTPAlternativesRecords()` sale del registro de rutas.** Se llamaba desde
  `UserSystemFeaturesRoutes::routes()`, que corre **en cada petición**: dos consultas con
  `GROUP_CONCAT` y `LEFT JOIN` sobre la tabla entera de usuarios por cada carga de página.
  Ahora es la tarea `bin/cli sync-otp-records`, que **solo informa** salvo que se le pase
  `apply=yes`.

## Corregido

- **El iframe de SurveyJS se quedaba en blanco.** La vista vacía toda la configuración de
  assets —a propósito, para que el CSS y el JS del panel no se cuelen— y con ello se llevaba
  `statics/core/js/configurations.min.js`, **único emisor en todo el proyecto** del evento
  `PiecesPHP-Configurations-And-Window-Load` del que depende su propio arranque. Sin él no
  fallaba nada: simplemente no ocurría nada. Se restaura **ese archivo y solo ese**; el borrado
  sigue siendo grueso a propósito.
    - `survey-js-creator.php` no lo necesita —arranca con `load`, no con el evento— y se deja
      como estaba.

- **La URL del generador de imágenes del cropper deja de ser relativa.**
  `view/panel/built-in/utilities/cropper/workspace.php` y
  `view/panel/pages/test-cropper.php` construían `img-gen/{w}/{h}` a mano, saltándose la
  regla de que las URLs se generan con los helpers. Funcionaba **solo** porque
  `view/panel/layout/header.php` emite `<base href>`, que normaliza cualquier relativa: sin
  esa etiqueta, las tres pantallas que usan el cropper —`Documents` add y edit, y
  `usuarios/form.php`— quedaban con el marcador de imagen roto. Ahora las dos usan
  `baseurl()`, que es la forma que ya usaban las otras ocho apariciones de `img-gen`.
    - Barrido completo de las vistas: eran **las dos únicas** de su especie. Los otros 200
      atributos de URL delegan en una variable construida con `routeName()`, y los 18
      literales `statics/…` están en páginas que llevan su propio `<base href>`.

- **Rector dejaba fuera 34 de 195 archivos.** El formateador de tabla de PHPStan recorta las
  rutas al ancho de terminal (80 al redirigir), y `Rector.php` descartaba con `file_exists()`
  lo que no resolvía **sin avisar**: el 17 % de la superficie con errores no entraba al
  análisis. `bin/phpstan` fija `COLUMNS=400` y emite `PHPStanResult.json`; Rector lee el JSON
  y ya no descarta en silencio. `bin/rector` pasa a usar `php8.4`, no el `php` por defecto.
- **`toggle2FA()` devolvía siempre `false`**, incluso al guardar bien: inicializaba
  `$result = false` y no lo reasignaba nunca.
- **Dos defectos que escondía la familia `strpos`.**
    - `PublicationsController:1388` devolvía el resultado de `mb_strpos()` como predicado de
      `array_filter`, y ese resultado se evalúa por veracidad: una coincidencia en la
      **posición 0** vale `0`, que es falso. Un campo llamado exactamente
      `systemApprovalStatus` quedaba fuera del filtro, que es justo el caso buscado.
    - `FixWebmDurationTask:130` usaba `mb_strpos()` como longitud de `mb_substr()`: si la
      extensión no aparecía, `false` valía `0` y `$fileName` quedaba **vacío**, de modo que
      los tres archivos derivados se llamaban `.tmp.wav`, `.bk` y `.fixed` a secas y se
      pisaban entre iteraciones.
- **`Config::basepath()` y `app_basepath()` tenían una carrera.** Comprobaban
  `file_exists($path)` y **después** llamaban a `realpath($path)`; entre las dos llamadas el
  archivo puede desaparecer, y entonces devolvían `false` desde un método que declara
  `string`. Una sola llamada responde ambas preguntas sin ventana entre ellas.
  `Config::app_path()` no comprobaba nada, y de su valor cuelgan las otras dos.
- **`json_encode()` declara sus fallos** en 15 sitios, con `JSON_THROW_ON_ERROR`. Con datos
  válidos la salida es **byte a byte idéntica**; lo único que cambia es el camino de fallo,
  y ese camino era peor de lo que parecía: en `PublicationsController:1152` el código era
  `sha1(json_encode($checksumData))`, así que **todo dato no codificable compartía el mismo
  checksum** (`sha1(false)` = `sha1('')`) y el caché HTTP servía contenido equivocado.
  La excepción es `GenericHandler:192`, dentro del manejador de errores, donde lanzar
  rompería justo el registro que se intenta escribir: ahí la respuesta es manejo explícito.
- **Una entrada de caché vacía ya no cuenta como caché.** `hasCachedData()` solo
  comprobaba `file_exists()`, así que un archivo de **cero bytes se servía como contenido
  válido indefinidamente** —no hay recaché hasta que algo marque `shouldBeRecached`—. Es
  la vía por la que un `json_encode()` fallido llegaba a disco: `setDataCache(string $data)`
  sin `strict_types` convierte `false` en `''` en silencio. Ahora un archivo vacío se trata
  como ausencia de caché y **la entrada envenenada se cura sola en la siguiente petición**.
    - **Al desplegar no hace falta purgar nada por este motivo.** Se comprobó el árbol de
      `src/app/cache`: no hay ninguna entrada almacenada en este entorno. Si en producción
      quedara alguna, la corrección la invalida automáticamente por tamaño. `bin/cli
      clean-cache` sigue disponible si se prefiere forzarlo.
    - **Matiz sobre el ETag**, que se describió de más en el análisis inicial: el checksum
      de `PublicationsController:1152` no se almacena en servidor, va a una cabecera `ETag`
      emitida junto a `Cache-Control: no-cache`. Eso obliga a revalidar, así que un ETag
      viejo provoca un 200 con contenido fresco y también se cura solo. El defecto real era
      que dos contenidos distintos podían compartir tag, no que se sirvieran cruzados: los
      ETag se comparan por URL.
- **El subsistema de exportación se retipa de `PDO` a `Database`.** Los 17 errores de
  `PDOStatement|false` vivían ahí porque el receptor estaba declarado como el padre, y por
  eso subir el paquete a v3.2.0 no movía el contador. Los dos únicos llamantes pasan
  `getDatabase()`, y uno ya usaba `getDatabaseName()`, que solo existe en `Database`.
- **`APP_VERSION_DATE` deja de depender del reloj.** `createFromFormat('d-m-Y', …)` sin parte
  horaria hereda la hora actual; `->format('Y-m-d')` la descartaba, así que el valor nunca
  cambió, pero el objeto intermedio era distinto en cada petición.

## Seguridad

- **`get-current-totp-qr-data/` devolvía 500 para todo usuario que no hubiera nombrado su
  segundo factor**, que es el estado normal de una cuenta nueva: se pasaba un alias nulo a
  un parámetro `string`. Ahora el emisor por defecto es **el nombre del sitio**, que es lo
  que ya hacían las vistas y lo que el usuario reconoce en su aplicación de autenticación.
- **Suite nueva `bin/cli unit-tests:core/otp-fresh-user`.** Inserta un usuario real sin
  filas OTP y recorre toda la superficie de autenticación. Confirma que **el arreglo del
  login no dejó a nadie sin poder entrar**: `setOTP()` crea la fila que falta. Es la única
  suite que escribe, y borra lo que crea pase lo que pase.

## Corregido — arranque de un despliegue

- **La línea de crontab documentada llevaba `--local`, y ese flag DECIDE LA BASE DE DATOS.**
  En terminal `is_local()` devuelve lo que diga el flag, y `config/database.php` elige
  credenciales y nombre de base según ese valor: una línea así en un servidor apunta los
  cronjobs **a la base de desarrollo**. Corregida en los dos sitios donde estaba escrita.
- **La activación de idiomas dejaba de copiar en silencio.** Los dos `@copy()` de la
  configuración SEO tragaban el fallo: sin fila y sin registro, cada render lo reintentaba
  para siempre sin que nadie se enterara. Ahora registran qué archivo y a dónde. **La
  lógica no cambia**: materializar la configuración por idioma al pintar la vista es el
  camino de activación, no un efecto lateral.

## Seguridad — cifrado

- **`BaseHashEncryption::encrypt()` y `decrypt()` dejaron de funcionar en PHP 8.5.** Efecto visible: la
  configuración de correo dejaba de descifrarse y **cinco páginas públicas devolvían 500** —
  recuperación de contraseña, desbloqueo de usuario, «no recuerdo mi usuario», problemas de
  ingreso y solicitud de soporte.
    - El arreglo es **compatible con lo ya cifrado**, demostrado en las dos direcciones: el código nuevo
      descifra lo cifrado por el viejo y el viejo descifra lo del nuevo.
    - **No se toca su algoritmo.** Cualquier otro cambio vuelve indescifrable todo lo ya guardado en cada despliegue.

## Corregido — el servidor de estáticos

- **23 rutas `*/statics/` devolvían 500 a la vez.** El patrón `{params:.*}` es **opcional** y
  `ServerStatics` lo leía como obligatorio en tres sitios: pedir `/statics/` sin recurso daba
  `E_WARNING` → excepción → 500, una por módulo. Ahora responden **404**, que es lo correcto,
  y los estáticos reales siguen sirviéndose.
## Herramientas — los cinco repositorios al mismo instrumental

- **Los cuatro paquetes `piecesphp/*` tenían la misma configuración que había cegado al
  framework**: `phpVersion` como rango, que reporta la intersección y no la unión. Se les
  propagan las **dos pasadas**, `PCSPHP_PHP_BIN`, el trinquete leyendo JSON y su baseline
  con nota de método. **Delta por configuración: cero en los cuatro** — pero ahora medido,
  no supuesto.
- **Séptima comprobación de `verify-integrity`: instrumental común.** `files/dev/shared-toolchain.json`
  lista qué debe estar presente en cada paquete, y la tarea falla si uno se desvía o si le
  falta un archivo. Si no están clonados al lado, lo dice y no aprueba en silencio.
- **Repuesto el prefijo `project://` del informe de PHPStan**, que se perdió al cambiar el
  resumen de la tabla al JSON. Lo consume un plugin del editor para saltar al archivo; el
  formato es idéntico al anterior. Ahora sale del JSON, sin expresión regular.

## PHP 8.5 — la migración no estaba terminada

- **Se borran nueve llamadas a funciones deprecadas en PHP 8.5**: `imagedestroy()` ×4,
  `finfo_close()` ×4 y `curl_close()` ×1. Desde 8.0 no hacen nada, y **en 8.5 avisan**;
  como el manejador de `bootstrap.php` promueve `E_DEPRECATED` a excepción, cada una era
  una petición abortada esperando a que alguien pisara esa línea.
    - **`img-gen` devolvía 400 por esto.** Demostrado poniendo la llamada de vuelta: con
      ella HTTP 400, sin ella HTTP 200 y un JPEG de 400×300.
    - **Por qué no se detectó**: Apache sirve **8.5.9** y todas las herramientas
      (`bin/cli`, `bin/phpstan`, `bin/rector`) corren con **php8.4**, donde esas funciones
      no avisan. Y `phpVersion` en `phpstan.neon` es un RANGO 8.4–8.5, que reporta solo lo
      que es error en TODAS las versiones del rango. **Los dos detectores miraban a la
      versión equivocada.**
- **`bin/cli verify-integrity` gana una sexta comprobación: FUNCIONES DEPRECADAS.** Busca
  por tokens las funciones de `files/dev/deprecated-functions.json` y falla si aparece
  alguna. La lista es un archivo editable, con la versión en que cada una se deprecó y las
  rutas donde se permite con su razón. **Es determinista: mira el código, no la ejecución.**
- **Nuevo: `bin/cli route-inventory` y `bin/walk-routes`.** Recorren por HTTP todas las
  rutas GET que el framework declara —347, sacadas del propio framework— y después todos
  los assets de las páginas visitadas. Solo lectura. Ver el README.
- **`bin/phpstan` pasa a DOS PASADAS y el baseline es la UNIÓN de 8.4 y 8.5.** El
  `phpVersion` como rango reportaba la **intersección**, no la unión: solo lo que es error en
  todas las versiones del rango, así que **toda deprecación exclusiva de 8.5 se descartaba en
  silencio**. El baseline pasa de 875 instancias a **888 tripletas**, que se reconcilia
  exacto: −9 duplicados que la unión deduplica, +22 que solo existen en 8.5.
    - **No es una regresión: es un destape por configuración más un cambio de unidad.** El
      resumen lleva ahora un bloque `[UNIDAD]` y otro `[REPARTO POR VERSIÓN DE PHP]`.
    - `phpstan-process-result.php` **deja de parsear la tabla** y construye el resumen desde
      el JSON de la unión.
- **Las herramientas de `bin/` usaban `php8.4` mientras Apache sirve 8.5.9**, así que las
  suites nunca habían corrido en la versión de producción. Ahora el binario es explícito y
  configurable con `PCSPHP_PHP_BIN`, **y por defecto es el que sirve el navegador**. Las seis
  suites y `verify-integrity` pasan en **las dos** versiones.
- **El recorredor pide también las rutas con parámetros**, cosechando los enlaces reales de
  las páginas ya visitadas en vez de inventar valores: 122 páginas más por recorrido.
## Seguridad y corrección de datos

- **El XML exportado declaraba `encoding="utf8mb4"` y NINGÚN parser podía leerlo.**
  `utf8mb4` es un nombre de charset de MySQL, no un encoding XML: un lector estándar
  rechaza el documento **entero** por la primera línea, por bien formado que esté el resto.
  Confirmado con dos parsers independientes (`libxml` y `xmllint`). `XmlFormat` traduce
  ahora los nombres de MySQL a los nombres IANA.
- **CAMBIO DE FORMATO EN LA SALIDA XML, y cambia porque antes era ilegible.** Las columnas
  binarias se exportan ahora en hexadecimal (`0x…`), igual que ya hacía la salida SQL. Antes
  se escribían con sus bytes crudos, y **XML 1.0 prohíbe los caracteres de control**, así que
  un export de cualquier tabla con un BLOB producía un documento que ningún parser aceptaba.
    - La detección era incorrecta de raíz: se preguntaba si el valor era UTF-8 válido, y eso
      **no responde «¿esto es binario?»** — los bytes de un PNG son UTF-8 válido. Ahora lo
      decide **el tipo de la columna**, leído del esquema, como en `SqlFormat`.
    - Además, cualquier valor que conserve un carácter prohibido en XML sale también en
      hexadecimal, **independientemente de la opción `hex_blob`**: un dato suelto no puede
      tumbar el documento entero.
    - **Si algo consume ese XML, tiene que contar con el `0x…`.** A cambio, por primera vez
      puede leerlo.
- **AVISO, defecto abierto y de otra capa**: la semilla de pruebas inserta un PNG que empieza
  por el byte `0x89`, y **las dos** exportaciones lo devuelven como `0x3f` —el signo `?`—.
  Los dos formatos leen con el mismo `SELECT *`, así que **el byte se pierde antes**, en la
  capa de base de datos. Afecta a cualquier dato binario, no solo al exportador. **No se
  toca aún**: si el fallo está en la escritura, los datos ya guardados están dañados y eso
  cambia qué significa arreglarlo.

## Pruebas — puertas verificadas en las dos direcciones

- **Desactivar el 2FA se comprueba por su EFECTO, no por su valor devuelto.** La suite
  verificaba que `toggle2FA(false)` devolviera `true`; ahora comprueba que la cuenta **deje de
  pedir código** y que el código de seguridad quede vacío. Validada rompiéndola: con un inverso
  que devuelve `true` sin desactivar, falla 2 de 27.

- **Apagar un módulo, medido por primera vez.** `NEWS_MODULE` en `false` retira sus 19 rutas del
  inventario, `/admin/news/list/` pasa de 200 a 404, el panel sigue en 200 y el menú deja de
  mencionarlo. Reencendido, **vuelve el mismo conjunto de rutas nombre por nombre**. No quedan
  restos.

- **`UnitTest-DbBackupRoundTrip` comprueba el viaje ENTERO**: exporta, restaura en una base de
  usar y tirar y **entra**. Leer el archivo no basta: un volcado puede tener el hash bien y no
  restaurar. Validada rompiéndola — con la transformación reintroducida falla 3 de 5.

- **`otp-write-separation` deja de juzgar por el texto.** Sus comprobaciones eran `grep` de
  `->save(` sobre el cuerpo del método, y **pasaban en verde con el defecto D2 reintroducido**
  porque un grep no ve una escritura delegada una llamada más abajo — que es exactamente la
  forma que tenía D2. Ahora llama a los buscadores y cuenta filas. 7/7.

- **`verify-integrity` deja de pedir el borrado de una sobreescritura de ruta viva.** Su
  clasificador decidía «¿este método decide algo?» leyendo el cuerpo, así que una sobreescritura
  cuya decisión se mudara a un método que ella llama se marcaba como **«YA NO DECIDE NADA:
  Bórralo»**. Ahora, si la clase declara también el método al que llama, no se declara inerte.
  Ese registro se usa en E3 para borrar, así que el agujero se tapa antes.

- **`UnitTest-OTPFreshUser` sube a 25 comprobaciones**: tres nuevas fijan que preparar el
  doble factor no lo activa, que confirmarlo sí, y que el secreto **no** se regenera al
  confirmar.

- **Se provocó el fallo de todas las puertas**, no solo de las nuevas. De doce, nueve
  funcionaban; los tres hallazgos van abajo. Las mutaciones exactas quedan anotadas en
  `.agents/context/18-siguientes-ventanas.md` (T23) para poder repetirlas.
- **`bin/phpstan` gana el trinquete, que hasta ahora no existía.** `CLAUDE.md` mandaba
  comparar contra `PHPStanResult.Summary.baseline.txt` y **nada lo comparaba**. Ahora
  `bin/phpstan-process-result.php` compara instancias contra instancias y **sale con 1 si
  el total sube** — y también si no consigue leer una de las dos medidas, porque una puerta
  que no puede medir no puede aprobar. El baseline pasa de **968 a 877**, con su nota de
  método dentro del archivo.
- **`unit-tests:core/database-exporter` valida ahora que la salida esté BIEN FORMADA**
  (JSON y XML), no solo que contenga lo esperado. Antes, un JSON corrupto en cada fila daba
  23/23. Esa comprobación es la que destapó el defecto del XML.
- **`unit-tests:functions/systemOutFormatted` llevaba meses roja diciendo que iba bien.**
  Afirmaba códigos ANSI que la función suprime a propósito sin terminal, así que fallaba
  7 de 10 en cuanto la salida se redirigía — y como no contaba nada, devolvía éxito. Ahora
  omite con su razón lo que exige terminal, y tiene balance y resultado real.
- **`bin/cli` devolvía código 0 aunque la aplicación muriera al arrancar.** El manejador
  global de excepciones terminaba en `die($content)`, y `die()` con una cadena **sale con
  código cero**. Cualquier puerta lanzada por la CLI —`verify-integrity`, las suites, los
  cronjobs— informaba de éxito **sin haberse llegado a ejecutar**. Ahora sale con **1** en
  CLI; en HTTP no cambia nada, porque ahí manda el 500 que ya se envió.
    - **Afecta a cualquier despliegue que lance tareas por CLI y mire el código de salida**:
      hasta esta versión, un árbol que no compila pasaba por bueno.

## Cambios internos

- **Una guarda de una línea contra un cuelgue**: `createOTPData()` y `createTOTPData()` llaman a
  su buscador para ser idempotentes, así que el día que alguien vuelva a hacer que el buscador
  cree, no habrá un defecto: habrá una **recursión infinita**, y se descubrirá por tiempo de
  espera agotado, que es la peor forma de descubrirlo. Ya nos costó dos ejecuciones al intentar
  reintroducir el defecto para validar una prueba.

- **`verify-integrity` cambia una orden por una pregunta.** Su clasificador de sobreescrituras
  decía «YA NO DECIDE NADA: Bórralo», y **nadie verifica una orden antes de obedecerla**. Como
  la comprobación lee el cuerpo y no ve lo que se delega, ahora pregunta si sigue decidiendo y
  pide comprobarlo antes de borrar.

- **Recorte de comentarios narrativos: de 132 bloques y 729 líneas de prosa a 91 y 486.** Los
  41 bloques recortados eran todos de esta campaña, y pasan al reparto que fija la regla: la
  guarda que impide romper algo se queda **en una línea**, y el relato —qué pasaba antes, qué
  se midió, por qué se decidió así— vive en este `CHANGELOG` y en
  `.agents/context/18-siguientes-ventanas.md`. Un comentario largo envejece mal: el día que el
  arreglo que narra sea irrelevante, el comentario miente y nadie va a ir a buscarlo.
    - Los 91 que quedan son heredados y **se recortan al pasar**, cuando se toque el archivo
      por otro motivo. Nada de barridos.

- **Se retira el hueco de plantilla `$defaultPropertiesValues = []` y su `foreach`**, 24
  apariciones en 19 mappers. El array se declaraba vacío y su bucle no podía hacer nada. Con
  esto, `foreach.emptyArray` desaparece del análisis y las ramas muertas bajan de **309 a 285**.

- **`routeName()`, `allowedRoute()` y `_allowedRoute()` dejan de estar copiados en cada
  controlador.** Los aporta **un solo trait**, `PiecesPHP\Core\Routing\ControllerRoutingTrait`,
  con el hook y su `return true;` por defecto. **Se borraron 89 copias** repartidas en 26
  controladores.
    - **El criterio es una sola pregunta: ¿este método DECIDE algo?** No el parecido con el
      cuerpo canónico — ese criterio, el de la primera pasada, dejaba vivas dieciséis copias
      que solo devolvían si la ruta vino vacía, con closures que nadie llamaba, variables
      asignadas y no leídas y un `if` comparando contra una ruta llamada `'SAMPLE'` que no
      existe.
    - **Sobreviven 25**, todas con su razón escrita: 15 que deciden de verdad —propiedad del
      recurso, conflicto de interés, registro protegido— y 10 estructurales, que nombran la
      ruta de otra forma o tienen otra firma.
    - **Sin cambio de comportamiento.** PHPStan queda en **877** en cada paso, con los
      **606** sitios de llamada resolviendo en nivel 8, y las suites sin moverse.
    - **`bin/cli verify-integrity` gana una quinta comprobación**: falla si un controlador
      sobreescribe uno de los tres sin estar en `KNOWN_ROUTE_OVERRIDES`, si una entrada del
      registro **deja de decidir algo**, o si apunta a una declaración que ya no existe.
    - **Al crear un módulo ya no se copian esos métodos**: se añade `use ControllerRoutingTrait;`
      y se escribe `_allowedRoute()` solo si hay reglas de autorización propias. Los tres
      patrones están documentados en la receta 9 de `.agents/context/13-recetas.md`.

## Análisis estático — el .neon deja de ser cajón de sastre

- **Las 285 ramas muertas pasan a supresión documentada agrupada por MOTIVO**, no por
  identificador. Eran veintiséis identificadores silenciados sin una sola razón escrita; ahora
  son cinco motivos, cada uno con lo que lo sostiene y su condición de retirada: defensa sobre
  datos que el tipo no describe (129), interruptores de módulo (65, sin condición de retirada
  porque borrarlos cablearía todos los módulos en encendido), variables que inyecta el
  renderizador de vistas (38), estrechamientos que en ejecución no lo están (46) e inofensivo y
  real (4).
    - Se retira la supresión de `foreach.emptyArray`, que ya no silencia nada.
    - Baseline **874 → 859**, y el reparto va escrito: **4 arreglos y 11 supresiones**. Una
      cifra que baja por supresión no significa lo mismo que una que baja por arreglo, y el
      trinquete no distingue solo.

- **`catch.neverThrown` pasa de supresión de familia a dos supresiones por ruta.** Se
  miraron los cinco: **tres eran muertos de verdad y se han borrado** —un `try` alrededor
  de una asignación de cadena, otro con el cuerpo entero comentado, y un
  `$exception->getCode()`—. Los dos que quedan son **alcanzables en ejecución**, cada uno
  por una razón distinta: uno pasa por `__set()`, que sí lanza, y el otro por un
  `E_WARNING` que el manejador de `bootstrap.php` promueve a excepción.
    - No se puede expresar en configuración: se probó
      `exceptions.reportUncheckedExceptionDeadCatch: false` y no mueve ninguno. PHPStan
      modela el lenguaje; aquí manda además el manejador de errores.
- **`if.alwaysFalse` en los `<Modulo>Routes` pasa a supresión PERMANENTE**, sin condición
  de retirada. Son el bloque `$showSQL` que la regla 7 de `CLAUDE.md` manda usar para sacar
  el DDL, más un módulo apagado en árbol con `const ENABLE = false`. **No es deuda: es un
  interruptor.** Los otros 9, en controladores, siguen contados como candidatos.

## Herramientas

- **`bin/cli scheme-drop module=<Nombre>` EMITE el SQL de borrado de un módulo. No lo ejecuta.**
  La regla de que el SQL de las tablas se genera y no se escribe a mano solo existía hacia
  adelante: deshacer un módulo obligaba a escribir a mano justo lo que la regla prohíbe, y cada
  despliegue que actualice necesita ese SQL.
    - El orden —hijas antes que padres— **sale del grafo que los mappers ya declaran** en
      `reference_table`. No hay lista aparte que mantener.
    - Emite y no ejecuta a propósito: esto viaja a despliegues ajenos, lo revisa una persona y
      se aplica deliberadamente.
    - Necesita **`piecesphp/database` >= 3.3.0**; con una versión anterior avisa y sale, en vez
      de reventar.

- **`bin/cli snapshot compare` exige que la volatilidad esté DECLARADA.** Un comparador con
  ruido enseña a ignorar los diffs, igual que un rojo permanente enseña a ignorar el rojo. El
  registro cerrado vive en `files/dev/volatile-state.json`, cada entrada con su razón medida, y
  la comparación **falla con salida 1 ante cualquier cambio no declarado**.
    - Dos entradas, las dos comprobadas: `login_attempts`, que escribe una fila por intento de
      acceso, y `src/app/lang/missing-lang-messages/`, donde el framework anota cada cadena sin
      traducir — **una escritura en un camino de lectura, y deliberada**.

- **El trinquete acota la declaración del reparto contra el `.neon`.** Una supresión cambia el
  `.neon` y un arreglo cambia el código: si se declaran cero supresiones y el bloque de
  `ignoreErrors` creció, la declaración es imposible y la puerta lo dice. **No verifica la
  atribución —se puede seguir mintiendo dentro de la cota— pero descarta lo imposible.**

- **`bin/cli snapshot`: fotografía la base entera y el árbol servido, y compara dos fotos.**
  Por tabla guarda el conteo, un hash agregado y **un hash por fila indexado por su clave
  primaria**, sacada de `information_schema`; del árbol, tamaño, `mtime` y hash de cada
  archivo. Comparar dos fotos atribuye cada diferencia a lo que corrió entre ellas.
    - Existe porque «solo GET» resultó no ser una propiedad de seguridad en este código: hay
      caminos de lectura que escriben, y se descubrieron de uno en uno y por accidente.
    - Por encima de 20.000 filas no guarda hashes por fila, **y lo dice nombrando las tablas**:
      un recorte que no se declara se lee como cobertura completa.
    - Las fotos no se versionan; la herramienta sí.

- **El trinquete exige el REPARTO de cada movimiento del baseline.** Premiaba igual arreglar
  que callar: una bajada por supresión se lee exactamente igual que una por arreglo. Ahora cada
  cifra del baseline declara `[REPARTO] n <- anterior = x arreglos + y supresiones`, las
  cuentas tienen que cuadrar, y sin esa línea `bin/phpstan` no pasa. No es para prohibir
  suprimir: es para que suprimir no pueda disfrazarse de progreso.

- **`shared-toolchain` deja de mirar solo el contenido y pasa a mirar el estado.** Aprobaba en
  verde sobre cuatro repositorios que no estaban sincronizados. Ahora comprueba tres capas: las
  marcas de siempre (más `bin/cli`), el **estado de seguimiento de lo que las herramientas
  producen** —qué debe estar versionado, qué ignorado, y distinguiendo el caso de *ni una cosa
  ni la otra*— y el **bit de ejecución** de `bin/phpstan` y `bin/cli`, que estaba en `100644`
  en dos paquetes y hacía que `./bin/phpstan` respondiera «Permiso denegado».
    - Se alinean los cinco repositorios: `PHPStanResult.json` versionado, los dos intermedios y
      `bin/Preview/` ignorados, y `database/bin/cli` pasa a honrar `PCSPHP_PHP_BIN` en vez de
      fijar `php8.4` a mano.

- **`bin/cli verify-integrity` gana su octava comprobación: comentarios narrativos.** Un
  bloque con **más de dos líneas de prosa y ninguna anotación** (`@param`, `@return`, `@var`,
  `@package`, `@author`, `@throws`) es la firma mecánica del relato: los docblocks de API
  siempre traen anotaciones, las historias no. El registro cerrado vive en
  `files/dev/narrative-comments.json` y **solo puede encoger**.
    - Guarda las **líneas de prosa** de cada entrada, no solo el número de bloques: un bloque
      que crece de 4 a 30 líneas no cambia el conteo de entradas y sí empeora el archivo.
    - Se ancla por **archivo**, no por línea, para que editar algo encima no genere ruido.
    - `bin/cli verify-integrity list-narrative=yes` lista los bloques con archivo, línea y
      prosa, que es lo que hace falta para recortarlos.
    - Falla en las tres direcciones posibles —bloque nuevo sin registrar, prosa que crece,
      entrada que ya no tiene bloques— y las tres se probaron provocándolas.

- **`bin/phpstan.neon` declara los interruptores de módulo como `dynamicConstantNames`.**
  Las 25 constantes de `config/constants.php` que **cada despliegue configura** ya no se
  resuelven al valor de este árbol, así que PHPStan deja de dar por muertas las ramas que
  dependen de ellas. **No es una supresión**: borrar esas ramas habría cableado todos los
  módulos en ENCENDIDO para cualquier despliegue que los apague.
- **`bin/phpstan-deadcode`**, nuevo. Mide las ramas muertas que el bloque de ignores de
  `phpstan.neon` silencia, derivando la configuración de ese mismo archivo en cada
  ejecución para que no pueda quedarse atrás. El baseline visible no se mueve (877); la
  medición tapada baja de 465 a 373 tripletas.

## Dependencias

- `piecesphp/database` sube a **v3.2.0**: `query()` y `prepare()` declaran `\PDOStatement`
  en vez de `\PDOStatement|false`.

## Pruebas

- Suite nueva `bin/cli unit-tests:core/otp-write-separation`: comprobar credenciales y
  registrar rutas no deben escribir en base de datos.
- **`bin/cli verify-integrity` gana una cuarta comprobación: ECLIPSES DE CLASES.** Falla si
  el núcleo declara bajo `PiecesPHP\Core\` una clase que también existe en un paquete
  `piecesphp/*`. PSR-4 resuelve por prefijo más largo, así que en ese caso el núcleo gana
  **siempre y en silencio**. Ya pasó con `MetaProperty`, y el coste no fue el eclipse sino
  que un arreglo aplicado al archivo del paquete no llegaba al framework **sin que nadie
  pudiera notarlo**. Los eclipses aceptados se registran en `KNOWN_ECLIPSES` con su razón y
  la condición que los retira; una entrada cuyo eclipse desaparezca también hace fallar la
  tarea, porque una supresión que sobrevive a su motivo es una mentira que nadie relee.
- **Suite nueva `bin/cli unit-tests:core/meta-property-hybrid`.** Prueba `MetaProperty`
  **tal como se ejecuta en el framework**, que no es como lo prueba nadie: lo que corre
  aquí es la copia del núcleo llamando a `EntityMapper::validateType()` del paquete, y esa
  combinación no la cubre ninguno de los dos repositorios. El arreglo de la deprecación de
  PHP 8.5 llegó a este código **de rebote**, por el único hilo que quedaba. Doce
  comprobaciones, todas de solo lectura.

# 7.1.0 (20-08-2026)

**Rango de PHP soportado: `>=8.4.1 <8.6`** (antes `>=8.1 <8.5`).

## Cambios que rompen compatibilidad

- **El piso de PHP sube de 8.1 a 8.4.1.** No es una elección estética: 8.1 lleva sin
  parches de seguridad desde el 31-dic-2025. El `.1` lo impone Symfony 8.1, que exige
  `>=8.4.1`; declarar `>=8.4` a secas mentía sobre lo que la aplicación necesita.
    - **Ubuntu 24.04 LTS trae PHP 8.3 por defecto**, así que el despliegue ahora requiere
      el repositorio de ondrej. Ver `source-docs/.../general.md`.
- **`bootstrap.php` cambia cómo trata los errores.** Ver abajo, es el único cambio de
  esta versión que altera el comportamiento en producción.

## Corregido — compatibilidad con PHP 8.5

13 sitios en el código propio, en tres familias:

- **9 casts no canónicos `(double)` → `(float)`** en 7 archivos. Es el mismo cast; solo
  cambia la grafía. Importaba porque la deprecación se emite **en tiempo de compilación**:
  bastaba con que el autoloader tocara el archivo.
- **3 llamadas a `Reflection*::setAccessible()` eliminadas** (`Config.php:712`,
  `BaseEntityMapper.php:159`, `index.php:338`). Desde 8.1 no tienen efecto. La de
  `BaseEntityMapper` era la grave: `__callStatic` la ejecutaba en cada `fieldsToSelect()`,
  o sea en el camino de **todo `SELECT` de mapper**.
- **`$http_response_header` → `http_get_last_response_headers()`** en `HttpClient.php:186`.
  Sin guarda `function_exists()`: la función existe desde 8.4, que es el piso.

## Manejo de errores — cambio de comportamiento en producción

`bootstrap.php` promovía a excepción cualquier nivel de su tabla, y **devolvía `true`
para todo lo demás**, de modo que lo descartaba en silencio.

- **`E_USER_ERROR` ya no se traga.** Se perdían todos los `trigger_error()` de librerías,
  incluido el `platform_check` de Composer: la aplicación arrancaba sin decir nada sobre
  PHP 8.1 con un `vendor/` que declara necesitar 8.4.1. Ahora aborta.
- **`E_RECOVERABLE_ERROR` ahora aborta**; antes se descartaba en silencio.
- **Las deprecaciones solo abortan en local.** En producción se registran en
  `app/logs/deprecations.log` y la petición continúa. Un cronjob lanzado sin `--local`
  cae en la rama de producción.
- `CleanLogsTask` limpia el log nuevo; limpiaba por nombre explícito y no lo conocía.
- **Deuda anotada**: `E_WARNING` y `E_NOTICE` siguen abortando. Es herencia, y cambiarlo
  merece su propia ventana de pruebas.

## Dependencias

- **Los cuatro paquetes propios** pasan a la misma forma canónica `">=8.4 <9.0"`, sin
  techo por minor. `piecesphp/database` era el único bloqueante real de 8.5.

| Paquete | Antes | Ahora |
| :-- | :-- | :-- |
| `piecesphp/database` | v3.0.4 | **v3.1.0** |
| `piecesphp/datastructures` | v3.0.0 | **v3.1.0** |
| `piecesphp/html` | v2.0.0 | **v2.1.0** |
| `piecesphp/geojson` | v2.0.0 | **v2.1.0** |

- **Symfony salta de 6.4 a 8.1** (`cache`, `filesystem`, `process`, `var-exporter`), más
  `phpspreadsheet` 5.9.0, `zipstream` 3.2.2 y `macroable` 2.1.0. Entran como transitivos:
  ningún archivo de `src/app` importa `Symfony\Component\*`.
- **`src/composer.lock` y `bin/tools/composer.lock` pasan a versionarse.** `src/` es la
  aplicación, no una librería, y con Symfony saltando dos majors hace falta
  reproducibilidad entre máquinas y despliegue.
- `composer why-not php 8.5` no devuelve nada.

## Herramientas

- PHPStan analiza el **rango** `{min: 80400, max: 80500}`, no una sola versión, y se añade
  `phpstan/phpstan-deprecation-rules`. Línea base congelada en
  `PHPStanResult.Summary.baseline.txt`: 1.078 errores en 192 archivos.
- Se retiran seis `ignoreErrors` de `cast.*` que no casaban con ningún error. `cast.*`
  significa «Cannot cast X to Y», no la sintaxis no canónica: esa PHPStan no la detecta.
- `bin/phpstan-process-result.php`: el regex de número de línea buscaba un formato que
  PHPStan no emite, así que el resumen imprimía `Líneas: , ,` y abortaba la generación de
  `bin/Preview`.
- `bin/cli` prefiere `php8.4` con fallback a `php`.
- `src/dumps/` (volcados de base de datos) pasa a estar ignorado por git.

## Validado

Recorrido completo del panel en **8.4 y 8.5**, con la promoción de deprecaciones activa:
login, panel, listado de Publications y su endpoint `-datatables`, formularios, las tres
exportaciones a Excel y el CLI completo. **Cero deprecaciones y cero 500.**
Las 9 suites de `piecesphp/database` (72 pruebas) verdes en ambas versiones.

# 7.0.6 (05-04-2026)

- **Exportador de Base de Datos Nativo**:
    - Se elimina el uso del ejecutable del sistema `mysqldump`.
    - Integración de `PiecesPHP\Core\Database\Export\Exporter` como nuevo motor para respaldo y exportación de bases de datos de forma agnóstica al sistema operativo.
    - Soporta múltiples formatos de salida (SQL, JSON, CSV, PHP, XML) y algoritmos de compresión (ZIP, Gzip, Bzip2, File).
- **Sistema CLI y Terminal**:
    - Novedades en el comando `db-backup` para elegir qué componentes backupear (`data`, `routines`, `views`, `definer`).
    - Scripts de autocompletado nativos en terminal para bash (`bin/pieces-completion.bash`) y zsh (`bin/pieces-completion.zsh`).
- **Sistema de Archivos y Logs**:
    - Soporte completo de manipulación de enlaces simbólicos (`Symlinks`) en `DirectoryObject` y borrado seguro.
    - Nuevo modelo de logs bajo demanda con trazas exclusivas y control de redundancia en formato plano para fácil lectura (`error.plain.log`).

# 7.0.5 (27-03-2026)

- **Sistema CLI y Terminal**:
    - Implementación de la clase `PiecesPHP\Cli` para gestionar argumentos y salida formateada en terminal.
    - Integración del soporte de `Cli` en `TerminalData` y actualización de `bootstrap.php`.
    - Refactorización de la detección de entorno local en `AppHelpers` y `CustomSlimErrorHandler`.
    - Se actualiza la versión de la aplicación a v7.0.5.

# 7.0.3 (26-03-2026)

- **Protección de archivos**:
    - Se implementó un sistema de protección de archivos que permite restringir el acceso a ciertos directorios.
    - Para ello se usa `ProtectFileMiddleware::protect`.
        - El cual es validado por `ServerStatics::protectFileMiddleware`.
    - El primer parámetro es la ruta del directorio a proteger.
    - El segundo parámetro es una función que recibe como parámetros un objeto Request y la ruta del archivo.
    - La función debe retornar true si se permite el acceso y false si se deniega.
    - Se puede usar la función `SessionToken::isActiveSession(SessionToken::getJWTReceived())` para validar la sesión.
```php
ProtectFileMiddleware::protect(append_to_path_system($uploadsDir, 'ruta/al/directorio'), function (Request $request, string $filePath) {
    return true;
});
```

# 7.0.2 (25-03-2026)

- **CLI**:
    - Mejor semántica en tareas de terminal que no son cronjobs y desacopladas del sistema de rutas con PiecesPHP\Terminal\CliActions.
    - Tareas afectadas
```bash
#Antes
bin/cli run-cronjobs unit-tests core/http-client
#Ahora
bin/cli unit-tests:core/http-client
#Antes
bin/cli run-cronjobs unit-tests core/helpers-directories
#Ahora
bin/cli unit-tests:core/helpers-directories
#Antes
bin/cli run-cronjobs mautic run
#Ahora
bin/cli tests:mautic-batch-send
```
- **Pruebas unitarias añadidas**:
    - `files/dev/tests.md` (ya no existe)
- **Eliminaciones**:
    - Se elimina la función `objectToArray`.

# 7.0.1 (25-03-2026)

- **Núcleo y Gestión de Archivos**:
    - Implementada normalización de rutas manual en `DirectoryObject` y `FileObject` para soportar enlaces simbólicos sin resolver `realpath()`.
    - Mejora en la seguridad de borrado recursivo para proteger las fuentes originales de los enlaces simbólicos.
    - Actualización en `ServerStatics` para la creación de enlaces simbólicos dinámicos más robustos.
- **Sistema de Logs**:
    - Nuevo método `loggingUniqueMessage()` en `GenericHandler` para registrar errores únicos con una firma detallada de 5 líneas (Cabecera + 4 niveles de traza).
    - Optimización del log JSON:
        - Eliminado el anidamiento redundante por segundos, agrupando ahora por día.
        - Limpieza automática de argumentos (`args`) en las trazas para reducir drásticamente el tamaño del archivo y mejorar la seguridad.
        - Eliminación de ordenamientos costosos (`uksort`) en cada escritura para mejorar el rendimiento.
- **ORM y Modelos**:
    - Ajuste de sintaxis en `where()` del `OTPSecretsUsersMapper` para compatibilidad con PiecesPHP\Core\* en versiones futuras.
- **Dependencias**:
    - Actualización de librerías composer: `pragmarx/google2fa` (v9), `hubspot/api-client` (v14), `spatie/url` (v2.4), `slim/psr7` (v1.8), `guzzlehttp/guzzle` (v7.10), entre otras.
    - Sincronización de la vista "About Framework" con las nuevas versiones.
- **Pruebas**:
    - Nueva suite de pruebas unitarias para validación de gestión de directorios y symlinks.
```bash
php index.php cli --local run-cronjobs unit-tests core/helpers-directories
```

# 7.0.0 (23-03-2026)

- Migración a PHP 8.4 funcional. Con soporte hasta 8.1.

# 7.0.0-beta

- Soporte para PHP 8.4 en proceso.
- Ajuste de composer.json.
- Upgrade con PHPStan:
    - Se ignoran falsos positivos con __() añadiendo doc condicional.
    - Se corrigieron nullables implicitos en el código.
    - Se corrigieron errores de variables no declaradas.
    - Hasta level 2 completo.

# 6.4.4 (22-03-2026)

- **Integración con Mautic**:
    - Refactorización de `MauticEmailAdapter` para mayor confiabilidad.
    - Prueba de procesamiento vía cronjob (`test-mautic-cronjob.php`).
        - Plantilla de ejemplo de correo (`template_mautic.php`).
```bash
php index.php cli --local run-cronjobs mautic run
```
- **HttpClient**:
    - Mejoras significativas en `HttpClient.php` con soporte para métodos modernos y mayor robustez.
    - Adición de pruebas unitarias exhaustivas para el cliente HTTP en src/app/core/system-controllers/local-tests/UnitTest-HttpClient.php
```bash
php index.php cli --local run-cronjobs unit-tests core/http-client
```
- **Gestión de Usua7rios (Soporte Mejorado sin Organizaciones)**:
    - Optimizada la lógica de visualización para admitir el funcionamiento del sistema cuando el módulo de organizaciones está desactivado.
    - Los formularios se ajustan dinámicamente ocultando campos relacionados con organizaciones si son innecesarios.
    - Reestructuración de formularios por tipos para mayor claridad.
    - Mejora en la visualización de perfiles en `user-card.php`.
    - Nuevo estado de usuario "Eliminado". Para una gestión ordenada las eliminaciones.
- **Núcleo y Otros**:
    - Ajustes en utilidades de `AppHelpers.php`.
    - Ajustes en utilidades de `Utilities.php`.
    - Mejoras menores en el punto de entrada `index.php`, incluyendo soporte para estados de inactividad equivalentes.

# 6.4.3 (18-03-2026)

- **Sistema de Colas (Implementación Inicial)**:
    - Introducción del sistema de procesamiento de tareas en segundo plano.
    - Implementación de `QueueTask` y `QueueHandlerResponse` para la gestión de colas.
    - Nuevo mapeador `QueueJobMapper` para persistencia de tareas con soporte para reintentos, programación diferida (`scheduledAt`) y registro de errores.
    - Tarea CLI `ProcessQueueTask` para el procesamiento robusto de la cola con manejo de señales y aislamiento de errores.
    - Ejemplo de implementación sugerida integrado en `TestQueueRequest`.
- **FreezeRequest (Persistencia de Contexto HTTP)**:
    - Motor de "congelación" de peticiones para su posterior ejecución en tareas de cola.
    - Captura completa de `$_POST`, `$_GET`, `$_FILES` (PSR-7 jerárquico), `$_COOKIE`, `$_SESSION` y `Body`.
    - Soporte para metadatos personalizados (`customData`) persistidos junto a la petición.
    - `UploadedFilesStructureMapper`: Nueva utilidad para normalizar y reconstruir estructuras complejas de archivos.
    - Lógica de limpieza recursiva de archivos temporales con gestión de permisos (`chmod 0777`) para operación multiplataforma (Web -> CLI).
- **Eventos de Base (Centralización)**:
    - Mejor centralización de los eventos del sistema en `BaseEventDispatcher`.
    - Introducción de `event-listeners.php` como archivo centralizado de utilidades para escuchar eventos globales de forma organizada. Ejemplo de migración.
- **Núcleo y Configuración**:.
    - Soporte mejorado para rutas y manejo de archivos subidos en `UploadedFileAdapter`.

# 6.4.201

- Ajuste en lógica de cronjobs internos.
- Añadido endpoint para ejecutar cronjobs desde terminal.

# 6.4.200002

- Ajuste de SQL a utf8mb4.
- Otros ajustes menores.

# 6.4.200001

- Ajustes para CORS.
- Algunos ajustes en mailing.
- Mejor gestión de errores en rutas 404.

# 6.4.2

- Eliminación de console.log innecesarios.
- Independización de archivos que gestionan la traducción con IA en el front.
- Internacionalización:
    - Mejora en revisión de traducciones pendientes.
    - Optimización de adaptadores de modelo IA para mejor manejo de las traducciones.
    - Fragmentos grandes de HTML se dividen en traducción con IA, deben ser especificados en asHTMLProperties.
    - Se destruye la conexión con la base de datos actual para evitar el error de "MySQL server has gone away" por tiempo de espera en la traducción con IA.
    - Se añade en lang.php la configuración DYNAMIC_TRANSLATIONS para gestionar elementos relevantes del sistema de inyección dinámica de mensajes de traducción.
    - Gestión de JSON de "traducciones" dinámicas se reemplaza por GeneriContentPseudoMapper.
    - Se simplifica la lógica de translations/saveGroup por solo interacción con base de datos.
    - Introducción de DynamicTranslationsHelper para persistencia de traducciones dinámicas. Ahora la base de datos se usa solo como un estado intermedio para guardar las traducciones "pendientes" y los mensajes fijos se circuncriben a un JSON denominado current-translations. Se gestiona fechas de actualización para no leer innecesariamente desde la base de datos. Se refactoriza add-dynamic-translations.php
- Servido estático de archivos personalizado:
    - Mejora en el servido de archivos estáticos desde los módulos.
    - Refactorización de ServerStatics.php.
    - ServerStatics.php crea enlaces simbólicos en statics/server-delegated para tener que servirlos siempre con PHP.
    - Los métodos staticRoute ahora se soportan con staticRouteModulesResolver de container para hacer funcionar la lógica de ServerStatics.php anteriormente descrita. Valida si el enlace simbólico existe.
- Bases de datos:
    - En BaseModel si introdujo gestión de tiempo de ejecución de MySQL con PDO::ATTR_TIMEOUT basado en 'max_execution_time' de PHP.
- Sesión:
    - Corregido: Ahora se toma en cuenta distintos estados de organización que son candidatos para habilitar el ingreso.
- Configuraciones en config.php:
    - Se introducen: domain, domain_protocol, base_domain_path, base_url.
        - i.e.: domain.tld, https://, /ruta/base/src, https://domain.tld/ruta/base/src
- En librerías base del framework:
    - Loader general:
        - Modulizarización de showGenericLoader, removeGenericLoader activeGenericLoader por un manejador desde una clases.
        - Se mejoró la lógica interna y se añadió la posibilidad mostrar un mensaje.
    - Se independizó la función genericFormHandler hacia un archivo único.
- En el adaptador del editor CKEditor:
    - Se añadió insertLink para permitir la posibilidad de carga de cualquier tipo de archivo como link.
    - Se hizo el ajuste correspondiente en la gestión del manejador de archivos.
- Se recomienda el tag en comentarios @category SpecialCaseSolution para soluciones particulares de modo que sean fáciles de buscar.
- Llaves mapbox se manejan desde "variables de entorno".

# 6.4.2-beta

- Ajuste de bug que hacía que se registraran sesiones "expiradas" sin motivo.
- Separación de require-dev del composer principal hacia bin/tools.
- Mejoramiento de base de código js del framework:
    - CookiesHandler.
    - GenericStepsViewHandler.
    - Mejor gestión de adición de librerías adicionales en helpers mediante combinación en gulp con helpers-lib/*
    - Exposiciones de pcsAdminSideBarIsOpen y pcsAdminSideBarToggle para manipular el sidebar del menú.
    - Manejo de persistencia de estado (plegado/desplegado) del sidebar con localStorage.
    - registerDynamicLocalizationMessages y relacionados puede cargar múltiples grupos simultáneamente.
    - Adición ignoreSearch en MapBoxAdapter para casos en los que no se quiera ejecutar la búsqueda de forma automática.
- Vista de reporte integrada en front.
- Internacionalización:
    - Adición de mensajes, en general.
    - Optimización de manejo persistente de idiomas, preferencia según navegador y otras mejoras. Se delega el manejor pleno a Config.php
- Ajustes de permisos según organizaciones y de sistema de aprobaciones.
- Ajustes de algunas opciones por defecto en inicio de MySpace.
- Simplificación general de archivos delete-config.js en términos de internacionalización. Es el primer paso para le delegación completa al sistema en lugar de manejarlo en el archivo.
- Mejora en el manejo de errores para renderización de BaseController.
- Mejora de funcionalidad de validaciones en PiecesPHP\Core\Validation\Validator.
- Sesión:
    - El inicio de sesión toma en cuenta estados de usuario y de organización que son candidatos para habilitar el ingreso.
- Sidebar interno diferenciado según tipos de usuario.
- En aprobaciones:
    - Se verifica isActive que se añade dinámicamente por el manejador.
    - Optimización de auto aprobaciones.
- Soporte base de reportes.
- Soporte de "variables de entorno" con GeneriContentPseudoMapper.
- Varios modos de listado base de publicaciones.

# 6.4.0

- Unificación de archivos del módulo de ubicación.
- getPCSPHPConfig a configurations.js.
- Mejora del sistema de traducciones y agrupaciones de mensajes más modularizadas.
    - Actualización de módulo de noticias internas y de publicaciones.
    - Mejoramiento de función de cambio de idioma y manejo persistente de selección (lang_by_cookie, cookie_lang_definer).
    - Búsqueda de traducciones faltantes con scan-missing-lang y registro de faltantes en app/lang/missing-lang-messages.
- Unificación de plantillas de correo en view/mailing/template_base.php y plantilla con poco html en view/mailing/template_base_no_style.php.
- Ampliación de roles de usuarios base.
- Mejora del listado de usuarios.
- Sistema de usuarios con capa de aprobación y mejor acoplado a sistema de organizaciones. Como medida que "prescinde" de esa características se puede dejar la organización base única.
- Sistema de "Perfiles" para usuarios y organizaciones.
- Ajuste de error en DefaultAccessControlModules que hacía que algunas rutas se mostran indebidamente con 403. Se verifica que empiece por la parte comparada del nombre de la ruta que se está buscando.
- Eliminación y reordenamiento de código scss.
- Mejoramiento de LocationsAdapter para trabajar con par país-ciudad (y más) y de MapBoxAdapter para mejorar la búsqueda del geocoder. Y mejoras en general.
- En AttachmentPlaceholder se agregó una opción para nombres personalizados distinto del nombre del archivo.
- Para ROOT, se integra en backend la posibilidad de "conectarse" como otro usuario.
- Adjuntos en Publications es añadible.
- Ajustes de lógica y orden en sistema de reporte de login.
- Ajuste dinámico de algunos permisos según si se es el administrador de una organización.
- Sistema de aprobación, según el que si no se está aprobado el márgen de acción es limitado (integrado con organizaciones, usuarios, convocatorias y publicaciones).
    - BaseEntityMapper intercepta fieldsToSelect (por lo tanto debe definirse como protected) con y devuelve un campo en consulta relacionado al estatus de aprobación (systemApprovalStatus).
- Comentarios @category AddToBackendSidebarMenu para rastrear mejor el uso del menú lateral del backend.
- ContentNavigationHub como un módulo de navegación entre los contenidos de otros módulos internamente.
- Implementación de un sistema de eventos en BaseEventDispatcher. Útil para el sistema de aprobaciones.
    - En BaseEntityMapper se disparan: saving, saved, updating y updated.
    - aseEventDispatcher::dispatch('AddDynamicTransaltions', 'added') para después de añadidas las traducciones dinámicas.

# 6.3.4

- Mejoramiento de multi-idioma.
- Traducción de textos faltantes.
- Integración con IA para traducción.
- Configuración dinámica de IA OpenAI y Mistral.
- Flujo de multi-idioma de Publicaciones mejorado, integración con traducción por IA.
- Acceso a claves seguras con getKeyFromSecureKeys.
- Evento onChange en RichEditorAdapterComponent y método textareaTarget.get(0).updateRichEditor
- onSuccessFinally en genericFormHandler
- PCSPHP-Response-Expected-Language como método de definir un idioma para la respuesta back-end desde front-end (recibe el idioma, ie.: es, en, fr, etc....)
- Mejoramiento de configuraciones finales, se pueden añadir archivos indefinidamente para configuraciones más claras.
- getExtension en FileObject

# 6.3.1

- Módulo de localización mejorado con LocalizationSystem que permite acceder a las traducciones desde front mediante una ruta con registerDynamicLocalizationMessages.
    - Se añade en el header la ruta lang-messages-from-server-url
- onLogout en PiecesPHPGenericHandlerSession.
- Actualización de adminer.
- Estructura de base de datos definida en utf8mb3.
- Organizaciones:
    - Ajustes en permisos.
    - Campos requeridos.
    - Traducciones.
- Ajustes menores en filtro de países.
- Ajustes menores en vistas de recursos de MySpaceController.
- Adición de SurveyJS como plugin frontend integrado.
- GEO_IP en config.php.
- Mejoramiento en manejo de errores 403 y 404.
- Función para devolver banderas según idioma en set_config 'get_fomantic_flag_by_lang', lang.php.
- Más idiomas por defecto.
- Remoción de #[\ReturnTypeWillChange].
- Prevención de inexistencia de constantes de carpeta de errores en GenericHandler.
- Mejor manejo de errores en BaseController.
- Mejoramiento en convert_lang_url y adición de lang2 y getCookie.
- Configuración pcsphp_system_translations contiene todas las traducciones.
- setConfigValue en AppConfigModel para agilizar la creación.
- Tipo de usuario Administrativo => Administrador.
- Ajustes en plantillas de correo.
- Adición de mailing-logo en gestión de imágenes.
- Intentar usar color principal en círculo de carga genérico.
- Configuración alternatives_url_include_current incluye la ruta del idioma actual.
- Configuración calculate_alternatives_langs_urls es una función que recrea las alternatives_url y alternatives_url_include_current.

# V6.3.0

- Independización de módulo importador.
- Manejador de sesiones sin usuario: PiecesPHPGenericHandlerSession, SessionTokenIsolated.
- Ajustes de seguridad en rutas expuestas.
- En módulo de publicaciones cambio de self::view por $this->render sobreescrito para no repetir importación de módulos.
- Unificación y simplificación de plantillas de correo electrónico.
- Nuevos métodos de encriptación bidireccional (BaseHashEncryption).
- Utilidad para crear cookie: setCookieByConfig.
- @strftime para ignorar deprecated.
- TokenModel/TokenController ajustados.
- Ajustes menores en módulo de ubicaciones.
- GoogleReCaptchaV3 ajustado para poder ser desactivado.
- Ajustes en recursos de prueba.

# V6

- Cambio de versión de Slim a v4.
    - Ya no es retrocompatible.
- Verisión mínima de compatibilidad de PHP: 7.4

# V5

- Implementación de la plantilla Editorial de HTML5UP en el front por defecto.
	- Formulario de contacto.
	- Vistas de blog.
	- Slidershow.
- Migración a Gulp 4 para las tareas.
	- Se recomiendan los pasos:
		- npm install
		- npm audit --force -fix
		- npm --force install
- Actualización a JQuery 3.5.1
- PiecesPHPSystemUserHelper.js libre de JQuery (usa Fetch API).
- Creación de CustomNamespace.js para algunas tareas genéricas (con la intención de eliminar helpers.js en el futuro)
	- Slideshow.
	- Desplazamiento suave.
	- Loader.
- Varias modificaciones que no afectan el comportamiento en algunos archivos JS/PHP.
- En el módulo de imágenes (HeroController en PHP) se implemento internacionalización y posibilidad de eliminar.
- Mejoramiento del sistema de traducciones.
- Mejoramiento en el sistema de rutas.
Nota: No hay nigún problema de retro-compatibilidad conocido.
