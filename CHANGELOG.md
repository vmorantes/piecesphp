# 8.0.7 (07-10-2026)

## ⚠ Seguridad — lo que escriben los usuarios se pinta escapado

Hasta ahora, muchas pantallas pintaban tal cual lo que un usuario había escrito, así que alguien podía guardar código
en un campo suyo y ese código se ejecutaba en el navegador de quien lo miraba. Ahora se escapa al pintar en:

- los perfiles de persona y de organización, «mi organización», mi perfil, mi espacio y la pantalla de seguridad;
- las organizaciones, los usuarios y sus tarjetas y formularios por tipo, y el buscador de usuarios;
- los documentos, sus tipos y el **nombre del archivo subido**, y las categorías de Formularios;
- el banner, el autor de una publicación, los formularios de noticias y publicaciones y sus categorías, el mapa de
  contenidos, el panel y la barra superior;
- el propietario, la descripción y las palabras clave del SEO, que salen en el menú del panel y en páginas públicas;
- **los informes de acceso**, incluido el nombre de usuario que alguien teclea al intentar entrar, que **cualquiera
  puede escribir sin tener cuenta** y que se enseña a quien revisa los accesos; también su mensaje y su IP;
- el listado de usuarios;
- **el registro de actividad**, entero: el texto de cada entrada —que lleva dentro el nombre de usuario, y cualquier
  usuario puede cambiar el suyo—, quién la hizo, la IP, la geolocalización y el saludo de su pantalla.

Y en **todos los listados del panel que se cargan por JSON**: el registro de actividad, los usuarios, los intentos de
acceso, el banner, las noticias y sus categorías, las publicaciones, las organizaciones, las aprobaciones, los
documentos, los perfiles y los formularios. Se escapa **solo la parte variable** de cada celda: los botones, las
etiquetas de estado y el diseño siguen siendo HTML, como antes.

Además, los enlaces de web y de LinkedIn de un perfil **solo se enlazan si empiezan por `http://` o `https://`**; con
cualquier otra cosa se enseña el texto, sin enlace.

**El contenido del editor no cambia**: sigue guardándose y pintándose tal cual, a propósito (ver la nota de la
`v8.0.6`). Quien tiene permiso para escribirlo es de confianza.

## ⚠ Seguridad — la pantalla del registro de errores declara su permiso

La pantalla que entrega el registro de errores del servidor —con las trazas, las rutas de los archivos y los mensajes
internos— **no declaraba quién puede verla**. En la práctica solo entraba el administrador principal, pero porque lo
decidía la regla general de permisos, no la pantalla. Ahora **la pantalla dice que es solo del administrador
principal**, como ya hacían el registro de actividad y el de correos. Quien podía entrar antes sigue entrando igual: lo
que cambia es que ahora está escrito.

## ⚠ Seguridad — el nombre de los anexos, las rutas de las imágenes y el recortador

Quedaban sitios donde la **ruta de una imagen o de un archivo** y el **nombre de un anexo** se pintaban sin escapar, y
el nombre lo teclea quien sube el archivo. Ahora se escapan: la imagen de portada de la pantalla de inicio, el logotipo
y el RUT de una organización, el logotipo de «mi organización», las imágenes de los formularios de publicaciones, el
nombre de cada anexo —que además se pintaba como texto de la pantalla, así que **alguien podía guardar código en el
nombre de un anexo y ese código se ejecutaba al abrir el formulario de quien lo revisa**— y la imagen del recortador
compartido, con su nombre.

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

**El nombre del archivo lo pone quien sube**, y llegaba tal cual a la ruta guardada, al disco y a la vista previa del
listado, que lo interpretaba como HTML. Ahora el banner **sanea el nombre de las imágenes nuevas** (solo letras,
números, guion bajo y guion; lo demás pasa a guion), lo recorta si es larguísimo, y su vista previa ya no interpreta la
ruta como HTML. La extensión
la sigue poniendo el framework, de la lista de imágenes permitidas. **Las imágenes ya subidas conservan su nombre**: si
una se llamaba de forma extraña, vuelve a subirla.

## Cambia — la vista previa de las subidas

El ayudante de subida solo pinta el fondo de la vista previa **si la ruta es segura**; si trae comillas, paréntesis o
espacios, la pantalla se ve sin fondo en vez de arriesgarse. Afecta a la edición de las categorías de noticias.

## Cambia — el color de una categoría de noticias

Solo se admite un color (hexadecimal, `rgb()` o `rgba()`). Se valida al guardar y, si una fila guardada antes trae otra
cosa, **se pinta el color por defecto** (`#000000`) tanto en el listado del panel como en la tarjeta pública: ese valor
se pintaba dentro de los estilos de la página, donde escapar no protege.

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
- Se retira `SystemApprovals/Views/mailing/template_base.php`, una copia huérfana de la plantilla de correo que nadie
  usaba y que se habría quedado atrás sin que nadie lo notara. La plantilla base de correo vive en el núcleo
  (`app/view/mailing/`); el cuerpo de cada correo sigue donde estaba, en su módulo.

# 8.0.6 (07-10-2026)

## ⚠ CAMBIO INCOMPATIBLE — la acción genérica de configuración solo guarda los colores de marca

`configurations-generic-save` responde **403** a cualquier opción que no sea uno de los nueve colores de marca
(`SettingsController::GENERIC_SAVE_ALLOWED`). Si tu clon guardaba otras opciones por esa acción, dale a cada una su
propia acción, con su permiso y su validación, como las de `SettingsController`.

## ⚠ Seguridad — los cambios de estado de un usuario

**Si tu instalación tiene usuarios sin privilegios de administración, actualiza.** Todo esto ya estaba en la `v7.1.0`.

- **El código de desbloqueo ponía ACTIVO a cualquier usuario**, también a un rechazado, un pendiente, un inactivo o un
  borrado. Ahora solo desbloquea al bloqueado por intentos y le devuelve el estado que tenía; si su perfil fue
  rechazado, vuelve rechazado. A quien no está bloqueado no se le envía código, y la respuesta no lo delata.
- **Aprobar o rechazar una organización o un perfil reescribía el estado de todos sus usuarios.** Ahora no reactiva a
  inactivos ni borrados ni toca un perfil que una persona rechazó; si el usuario está bloqueado, la resolución se
  conserva para cuando se desbloquee. La autoaprobación tampoco deshace ya el rechazo de un perfil.
- **Cualquiera con permiso de aprobar podía aprobar su propia fila**, y un institucional podía resolver el perfil del
  principal o de un administrador general. Ahora nadie resuelve su propia aprobación salvo el principal, hay que tener
  autoridad sobre el tipo del usuario, y el listado de aprobaciones solo enseña lo que se puede resolver, también al
  buscar o filtrar.
- **Los formularios de aprobación pintaban sin escapar lo que escribe el usuario** (nombres, usuario, correo, datos del
  perfil, títulos, anexos): un usuario podía ejecutar código en el navegador de quien lo aprobaba. Ahora se escapa al
  pintar, y el contenido de una publicación se enseña aislado, sin ejecutar nada. Lo mismo en el listado.
- **Datos que llegaban crudos a páginas públicas**: la dirección de vuelta de la pantalla de acceso y la del formulario
  de contacto se escapan ahora al pintar.
- **Cualquier visitante, sin cuenta, podía consultar la base de datos a través del alta del boletín**: el correo se
  metía tal cual en la consulta que comprueba si ya existe. Ahora va como parámetro.
- **Cualquier visitante, sin cuenta, podía dejar código en el listado de suscriptores del boletín**, por el alta del
  boletín o por el formulario de contacto: el correo no se validaba y el listado lo pintaba tal cual, así que se
  ejecutaba en quien lo abría. Ahora el alta del boletín exige un correo válido, el formulario de contacto solo suscribe
  con uno válido, y el listado y el formulario de edición escapan el nombre y el correo.
- **Las contraseñas y claves de configuración viajaban dentro del HTML** de sus formularios: las claves de API de los
  servicios de IA, la contraseña del SMTP y la clave de osTicket. Ahora los formularios no las muestran; un campo que se
  deja vacío conserva el valor guardado. La configuración del correo y de osTicket pasa a ser solo del principal: quien
  la cambiaba podía apuntarla a otro servidor y llevarse la credencial guardada.
- **La acción genérica de configuración dejaba a un administrador general escribir opciones reservadas al principal**,
  entre ellas las que gobiernan los permisos y los scripts del panel. Ahora la acción genérica solo acepta los
  colores de marca de su pantalla, valida que cada valor sea un color, y rechaza cualquier otra opción; las opciones
  reservadas, además, solo las escribe el principal por su propia pantalla. **Si en tu instalación un usuario que no es
  el principal pudo usar esa acción**, revisa la tabla `pcsphp_app_config` y borra las filas que no reconozcas: la
  aplicación las sigue cargando.
- **Los colores de marca y el titular de la configuración SEO se pintaban sin escapar en las plantillas de correo.**
  Ahora se escapan.

## Cambia — un usuario bloqueado por intentos

Un usuario bloqueado por intentos fallidos no puede iniciar una sesión nueva, pero **no pierde la que tenía abierta**:
el bloqueo protege la contraseña contra quien la prueba, y cortar la sesión daría a cualquiera la forma de echar a otro
usuario. Si antes de bloquearse estaba pendiente o rechazado, sigue con el panel recortado a lo suyo. Inactivos y
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

## ⚠ Seguridad — editar y crear usuarios exigía menos que sus formularios

**Si tu instalación tiene usuarios sin privilegios de administración, actualiza.**

- **Cualquier usuario con sesión, de cualquier tipo, podía editar a otro usuario** enviando la petición de edición:
  su nombre de usuario, su correo, su estado, su organización y su contraseña, también la de un principal. El
  formulario comprobaba la autoridad; la petición, no. Además, uno mismo podía cambiarse la contraseña sin dar la
  actual, y el perfil le dejaba cambiarse el estado. Ahora la petición exige lo mismo que su formulario: el permiso,
  que el tipo del usuario editado tenga rol, la autoridad sobre ese tipo, solo los estados que el formulario ofrece y,
  para quien no gestiona todas las organizaciones, solo usuarios de la suya y sin moverlos a otra. Uno mismo se edita
  como perfil y con su contraseña actual. El formulario de edición de otro usuario tampoco se abre ya si no se gestiona
  su organización. Ya estaba en la `v7.1.0`.
- **Cualquier usuario con sesión podía crear usuarios de cualquier tipo**, también un principal, y en cualquier
  organización. Ahora el alta del panel exige lo mismo que su formulario: el permiso, la autoridad sobre el tipo, sus
  estados y, para quien no gestiona todas, su propia organización. El alta pública por la API sigue igual, con su
  propia política (usuario general o administrador de organización, pendiente de aprobación). Ya estaba en la `v7.1.0`.
- **Cualquier usuario con sesión podía cambiar el avatar de otro.** Ahora solo el propio usuario o quien puede
  gestionarlo. Ya estaba en la `v7.1.0`.
- **La autoaprobación de usuarios ponía ACTIVO a cualquier usuario de una organización aprobada**, aunque estuviera
  inactivo, bloqueado, rechazado o borrado, y lo hacía al navegar: bastaba una petición. Ahora solo pasa a activo a quien
  estaba pendiente de aprobación; los demás estados no se tocan. Ya estaba en la `v7.1.0`.

## ⚠ Seguridad — las publicaciones no públicas de otras organizaciones

- **La API de detalle de publicaciones entregaba cualquier publicación a cualquier usuario con sesión** (borradores,
  programadas, sin aprobar o de otra organización), **y con ella el usuario autor entero, incluido el hash de su
  contraseña.** Ahora la API solo entrega lo que la vista pública o la vista previa dejan ver, y del autor solo su id y
  su nombre. Ya estaba en la `v7.1.0`.
- **Quien podía ver borradores sin poder ver todas las publicaciones recibía los de todas las organizaciones**, en el
  JSON de publicaciones (con `status=ANY` o un estado no público) y en la vista individual, cuyo enlace se puede
  construir. Ahora solo los de su organización; sin organización, ninguno. Quien aprueba publicaciones de todas las
  organizaciones conserva la vista previa de lo que tiene pendiente de aprobar. Ya estaba en la `v7.1.0`.
- **Quien podía editar publicaciones podía sobrescribir y publicar las de otra organización** enviando su id a la
  acción de edición. Ahora la edición exige lo mismo que abrir su formulario. Ya estaba en la `v7.1.0`.
- **Con sesión, los archivos privados de una publicación no pública se servían a cualquiera.** Ahora solo a quien puede
  verla o editarla. Un archivo privado que no está en la carpeta de ninguna publicación (el código actual ya no los
  produce, pero pueden venir de datos antiguos) solo lo recibe quien puede ver todas las publicaciones. Y un token que
  la aplicación ya no acepta (revocado, o de un usuario dado de baja) no cuenta como sesión para estos archivos. Los
  archivos privados llegaron después de la `v7.1.0`.
- **La API de detalle de noticias entregaba cualquier noticia a cualquier usuario con sesión**: borradores, inactivas,
  fuera de fecha o dirigidas a otros perfiles, aunque su listado sí las filtraba. Ahora solo entrega lo que el listado
  le daría a ese usuario. Ya estaba en la `v7.1.0`.

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
  `{"publicationData": null}`) y cuando quien pregunta no puede verla (antes se entregaba entera). Y `author` pasa de
  ser el usuario entero a `{"id": …, "fullName": …}`: un consumidor que leyera otros campos del autor tiene que
  pedirlos por su lado, con su permiso.
- El detalle de noticias de la API hace lo mismo: **404** para la noticia que no existe (antes, 200 con
  `{"newsData": null}`) y para la que su listado no le daría a quien pregunta (antes se entregaba entera).

## ⚠ Seguridad — un usuario sin organización ya no ve las publicaciones de todas

Un usuario cuyo tipo exige organización y que no la tiene (un dato defectuoso: el alta y la edición lo impiden, pero
puede llegar de una base antigua) recibía un error 500 en todo el panel. Ese error tapaba algo peor: sin él, el
listado de publicaciones del panel no le aplicaba ningún filtro de organización y le habría enseñado las de todas.
Ahora entra al panel, el menú no le ofrece la entrada «Organización» y el listado de publicaciones le sale vacío. Quien
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

## ⚠ Seguridad — un administrador de organización exportaba los accesos de todas las organizaciones

Desde los informes de acceso, un administrador de organización podía descargar las tres exportaciones (intentos de
ingreso, usuarios con ingreso y usuarios sin ingreso) con los datos de **todas** las organizaciones: nombres de usuario,
mensajes, IPs y fechas. Ahora cada exportación trae solo lo de su organización, con la misma regla que el listado en
pantalla. El principal y el administrador general siguen viéndolo todo. Los intentos con un nombre de usuario que no
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
  slug equivocado responden como siempre, **sin 301**: la redirección lleva el título en su cabecera, y redirigir antes
  de comprobar la visibilidad habría enseñado el título de un borrador a quien adivinara su código.
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
  (`source-docs/project/docs/environments/content/lamp/content/Adminer.md`).

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

- El título, el autor, la imagen, los adjuntos y el título de la sección se imprimían **sin escapar** en
  `Publications/Views/publications/public/`: un título con comillas rompía el atributo `alt` y uno con etiquetas las
  inyectaba en la página pública. **El contenido de la publicación sigue saliendo como HTML**, a propósito: es lo que
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

- **Antes**, con `API_MODULE` activo —lo que viene de fábrica—, **toda respuesta devolvía
  `Access-Control-Allow-Credentials: true` al origen que la pidiera**. Una página de cualquier dominio podía hacer
  peticiones con la cookie de sesión de un usuario que tuviera el panel abierto y **leer la respuesta**.
- **Ahora** `Access-Control-Allow-Credentials: true` solo sale para **el propio origen de la instalación** (el de
  `base_url`) y para los que declares en **`$config['cors_credentials_origins']`** (`src/app/config/config.php`,
  vacío de fábrica). La comparación es **exacta**: esquema, host y puerto. `https://tu-dominio.com.otro.net`, el
  mismo host por `http` o con otro puerto **no** cuentan. Una lista que no sea de cadenas cuenta como vacía.
  - `Access-Control-Allow-Origin` sigue devolviendo el origen que pide: **sin credenciales**, un cliente de otro
    origen sigue pudiendo llamar a la API mandando el token en la cabecera.
  - La regla vive en `cors_origin_allows_credentials()` (`src/app/core/AppHelpers.php`) y la usan el contenedor
    `cors` y el manejador de errores fatales de `bootstrap.php`, que también las daba a cualquiera.
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
- **La puerta de atrás quedó cerrada:** la acción genérica de configuraciones la abren el principal **y el
  administrador general**, y podía escribir cualquier configuración, incluidas las tres del mantenimiento. Ahora esas
  tres están reservadas al principal y la acción las rechaza con un **403** antes de escribir. Es un cierre
  provisional: la genérica dejará de servir para lo serio cuando cada configuración con consecuencias tenga su propia
  acción.
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

- **Antes, la comprobación automática de permisos solo recorría las páginas que se piden con GET**: 204 de 322 rutas.
  Un permiso de crear, editar o borrar se podía cambiar por error y **la verificación seguía en verde**. Se demostró:
  con un permiso revertido, la comprobación dijo «sin cambios» mientras un usuario que no debía borraba un registro.
- **Ahora se comprueban las 322**, preguntándole al framework quién abre cada una y comparando con lo declarado. No
  pide ninguna URL: comprobar quién puede borrar pidiendo el borrado es borrar.
- **Corregido un defecto en el cálculo de permisos** que devolvía los roles **por duplicado** en 223 rutas. Una
  variable interna pisaba el parámetro de la función. **Nadie gana ni pierde acceso** —la comprobación usa
  `in_array`—, pero cualquier pantalla que contara esa lista contaba de más.
- **Ojo si vienes de PHP 7:** ese mismo defecto se comportaba **al revés** antes de PHP 8. La comparación laxa acertaba
  por accidente y el efecto era *callar* permisos: una ruta que declarase el rol principal no recibía lo que la
  configuración de roles le daba. Si alguna vez viste la pantalla «Rutas y permisos» comportarse de forma rara, tiene
  aquí su explicación. *Deducido del cambio de la comparación laxa en PHP 8; no medido en una instalación con
  PHP 7.*

## Interno — una plantilla que no existe deja de descubrirse en producción

- **Antes**, una ruta de vista mal escrita no fallaba al analizar el código: fallaba **cuando un visitante entraba en
  esa página**, y se llevaba la página entera. Ahora **327 rutas de vista se comprueban en cada verificación**, en las
  cuatro formas en que el framework carga una plantilla.
- No cambia nada para quien clona; es la red que permite mover vistas de sitio sin romperlas.

## CAMBIO INCOMPATIBLE — el boletín pasa a ser de comunicaciones

- **Antes**, el módulo de boletín repartía sus permisos **lista por lista**: quién veía, quién daba de alta, quién
  editaba y quién borraba se declaraban por separado, y no coincidían. El resultado era que **el usuario general
  entraba al listado de suscriptores**, con sus nombres y sus correos.
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

- **Las dos capas que protegen una ruta con `require_login` se endurecen.** Antes, la primera admitía una excepción y
  la segunda se quedaba sin veredicto cuando no había sesión, así que **no negaba**. Ahora: sin sesión, una petición de
  datos a una ruta con `require_login` recibe siempre **403 `RESTRICTED_AREA`**; y la comprobación de permisos por rol
  **niega** en cuanto la ruta exige sesión y no la hay, en vez de abstenerse.
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

- **Antes**, la respuesta del login traía una clave `extras`. En el camino normal iba **vacía**, y solo se rellenaba en
  un caso de error: llevaba el **mensaje técnico de la excepción** que se hubiera producido al interpretar los valores
  enviados.
- **Ahora esa clave no se envía.** El mensaje de esa excepción **sigue registrándose en el log del servidor**, como
  hasta hoy: no se pierde el dato, cambia dónde se lee.
- **Por qué se retira y no se arregla.** El canal estaba roto desde hacía tiempo: el servidor mandaba `extras` y el
  JavaScript del framework leía `extraData`, un nombre que nunca existió en la respuesta. Medido antes de decidir:
  **un emisor y ningún receptor** —2 apariciones en el login, ninguna lectura en el JavaScript propio—. La tercera
  aparición de `extras` en el código, la del vehículo genérico `ResultOperations`, **se queda**: no es de esta ruptura. Cablear los dos
  nombres habría hecho viajar al navegador un texto técnico que nadie declaró, justo lo contrario de la lista de campos
  declarados de la sección anterior.
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

## Corregido — dos defectos en la comprobación de la sesión

- **Una fecha de creación inutilizable ya no se da por buena.** `SessionToken::isActiveSession()` usaba la fecha del
  token sin comprobar su tipo. Con un código de error rompía la petición, y **con un valor ausente tomaba la hora
  actual**, de modo que un token expirado podía pasar por recién creado. Ahora, si esa fecha no es utilizable, la sesión
  simplemente no es válida. *No era alcanzable desde fuera con el código sano —la comprobación de firma y caducidad
  filtra antes—, pero era una puerta a un paso de abrirse.*
- **El candado que ata una sesión a su cliente ya no revienta si falta la dirección del cliente.** `BaseToken::aud()`
  leía `REMOTE_ADDR` sin protección, y era el único sitio del framework que lo hacía así. Ahora usa `0.0.0.0` como los
  demás. **Ojo:** ese valor no es un comodín; un token emitido sin dirección sigue sin valer desde un cliente que sí la
  tiene.
- **Para quien despliega:** ese candado se calcula con la dirección del cliente **y el nombre de la máquina**. Cambiar
  el nombre del servidor invalida todas las sesiones que lo usen.
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
- Guía del servidor de pruebas (Mailpit): `source-docs/project/docs/environments/content/mailpit/index.md`.

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

- **El formulario de contacto ya no suscribe al boletín a quien falla el CAPTCHA.** Antes, el alta de suscriptor
  estaba fuera de la comprobación del CAPTCHA y se hacía siempre.
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

- **`generate_code()` y `generate_pass()` usan `random_int()`** en lugar de `rand()`: los códigos de verificación, de
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

`-actions-add` y `-actions-edit` son rutas distintas con permisos distintos, pero dentro del
controlador la rama se elegía con `$isEdit = $id !== -1;` **leído del cuerpo**. La comprobación
miraba la puerta y el cuerpo elegía la habitación.

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

La respuesta JSON del formulario de contacto —una ruta **pública**— llevaba en `values.logMailer`
el registro de la conversación SMTP cuando el envío fallaba: con `SMTPDebug = 2`, el banner del
servidor y el texto del fallo, a la vista de cualquiera. **Esa clave ya no existe.** El registro
va a `log_exception()`, al registro de errores del servidor.

`values` trae ahora solo `redirect`, `redirect_to` y `reload`, y `message` sigue diciendo por qué
falló. **Si tu cliente leía `logMailer`, léelo del registro de errores.**

### 13 · El listado de perfiles de `MySpace` deja de mostrar usuarios sin aprobar

`AllProfilesController` armaba el `HAVING` como `A AND B OR C`, y `AND` liga más fuerte: se leía
`(aprobado AND sin tipo) OR (tipo permitido)`, así que **las organizaciones se filtraban por
aprobación y los usuarios no**. Ahora va entre paréntesis y la aprobación se exige a los dos.

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

`publications-ajax-all` y `built-in-banner-ajax-all` aceptaban `?status=ANY` (o un estado
concreto) sin mirar quién preguntaba: un visitante sin cuenta podía listar borradores y elementos
borrados. **Ahora, sin sesión con permiso, el estado pedido se ignora y se devuelve lo publicado.**
- **En publicaciones**, el permiso es el tipo de usuario de `PublicationMapper::CAN_VIEW_DRAFT`,
  el mismo criterio que la vista individual.
- **En banners**, poder ver su listado de administración.

Con permiso, todo sigue igual. **Si un cliente sin interfaz leía borradores por esa ruta sin
autenticarse, deja de recibirlos.**

### 16 · El listado de documentos deja de mostrar los inactivos

`DocumentsController::_all()` construía su filtro de estado como par clave-valor y lo unía como
texto, así que el `WHERE` se quedaba sin filtro y `/documents/all` devolvía también los documentos
inactivos, es decir, los borrados. Ahora filtra por estado. **Quien viera documentos borrados en
ese listado deja de verlos.**

### 17 · Los archivos subidos dejan de servirse a cualquiera

Hasta ahora Apache servía directamente, a quien tuviera la URL, los archivos subidos de casi todos
los módulos. Publicaciones estaba «protegida» con un validador que dejaba pasar a todos.
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

Hasta ahora cualquier usuario con sesión podía guardar el texto que quisiera como traducción de
cualquier clave (`core/api/translations/saveGroup`), y ese texto se imprimía sin escapar.
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

### 20 · Una publicación sin aprobar deja de verse sin sesión

- **Antes:** con el módulo de aprobaciones activo, una publicación activa y en fecha, pero
  pendiente de aprobar, se veía por su enlace directo, y sus archivos también. Los listados
  públicos ya la ocultaban.
- **Ahora:** sin sesión, su página da 404 y sus archivos 403.
- Quien tiene sesión y permiso de vista previa (`CAN_VIEW_DRAFT`) la sigue viendo, igual que
  un borrador.
- Si las aprobaciones están apagadas, nada cambia.
- `PublicationMapper::isVisibleToPublic()` exige ahora la aprobación a través de
  `isApprovedForPublic()`. **Si tu proyecto la usaba**, ten en cuenta que puede dar `false`
  donde antes daba `true`.

### 21 · El cron recupera lo que falla, y su ruta web ya no se abre sin clave

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
  (`secure-keys/cronjob`), responde 403 siempre. Antes, sin la clave, cualquiera podía lanzar el
  cron sin cabecera, respaldo incluido. La comparación es `hash_equals()`. Usa la cabecera
  `Cron-Job-Key`: el parámetro GET sigue funcionando, pero queda en los logs de acceso.
- La respuesta de la ruta ya no incluye la traza de la pila cuando una tarea falla.

### 22 · Los archivos protegidos se deciden por su NOMBRE (`.protected`), y el subsistema vive en `Core/Statics`

- **Por qué:** bajo HestiaCP, nginx sirve directamente los archivos que existen y no lee el
  `.htaccess`, así que la protección por carpeta no protegía los archivos existentes de las
  subidas.
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

  **Sin migrar,** los archivos privados existentes siguen con su nombre público, y bajo nginx se
  sirven a cualquiera, como antes de esta versión. `--revert` deja también con su nombre
  público lo que ya nació privado antes de migrar.
- Guía completa: `source-docs/project/docs/piecesphp/new-features/protected-files.md`.
- **Si sirves con nginx sin Apache detrás,** añade una regla que niegue `\.protected$` y deje
  pasar a PHP lo que no existe.
- Al reemplazar la imagen o un adjunto de una publicación, el archivo nuevo va a la carpeta de su
  publicación. Antes caía en la raíz de `publications/` y, desde el lote 3, no se veía sin
  sesión aunque la publicación fuera pública. Los que ya estaban sueltos siguen la visibilidad
  de su publicación.

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

### 24 · Los enlaces de token ya no se pueden adivinar, y las claves salen de `app_key`

- **Antes:** la URL de `GenericTokenController` llevaba el `id` de la fila cifrado con el nombre
  de la clase, que es público, y con un cifrado que suma byte a byte. Cualquiera podía calcular
  la URL de cualquier token. Además, al entrar con un token que no valía, el controlador
  **borraba esa fila sin mirar su tipo**: se podían destruir enlaces de recuperación de
  contraseña ajenos.
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

- **`GenericTokenController::KEY_JWT` y `TokenModel::KEY_BASE_JWT` desaparecen.** Eran constantes
  públicas con un valor fijo, igual en todos los despliegues. Ahora las claves se derivan de
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

### 25 · Dos comprobaciones que concedían por error ahora fallan cerrado

Las dos decían «sí» donde debían decir «no». Si tu proyecto se apoyaba en ese «sí», ahora recibe
un «no» o una excepción: es el cambio que se busca, pero avisa a tus desarrolladores.

- **`Roles::addPermission()` con `IDENTIFIER_TYPE_CODE` y un identificador que NO es numérico
  LANZA `RoleNotExistsException`.**
  - **Antes:** ese identificador se convertía en `0` sin avisar, y la ruta **se concedía al rol
    de código 0, que es root**. El rol que se quería nombrar no recibía nada.
  - Un código numérico, entero o como cadena (`770001` y `'770001'`), sigue funcionando igual.
- **`UploadedFileAdapter::validate()` devuelve `false` cuando no se subió ningún archivo.**
  - **Antes devolvía `true`**: la cadena de comprobaciones no tenía `else` y el acumulador nacía
    en `true`. Es la misma trampa que ya se había corregido en `FileUpload::validate()`.
  - Quien preguntaba antes por `hasInput()` no nota nada.

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
  Antes se aceptaba sin validar: un administrador general podía crear un root subiendo `type = 0`.
- **Una fila con la columna `id` que no sea vacía o un entero positivo se rechaza.**
- Sin la columna `type`, las filas se siguen importando como usuarios generales, como antes.
- **Si tu proyecto importaba administradores o roots con este importador**, deja de poder hacerlo: se crean
  por el formulario de usuarios.

### 28 · Los destinatarios del formulario de contacto y de «otros problemas» salen de la configuración

- **`ContactFormsController::RECIPIENTS_MESSAGES` y `UserProblemsController::EMAIL_ON_FAILED_OS_TICKET`
  desaparecen.** Llevaban una dirección escrita en el código, así que todo clon enviaba esos mensajes a esa
  dirección.
- **Los destinatarios pasan a `src/app/config/config.php`**, como lista de direcciones:
  `$config['contact_form_recipients']` (formulario de contacto) y `$config['other_problems_recipients']`
  (el correo de «otros problemas» cuando osTicket no está configurado o no responde). **Vienen vacías.**
- **Sin destinatarios válidos no se envía el correo.** Una lista vacía, una clave que no es una lista o una
  lista con alguna dirección inválida cuentan igual. El formulario de contacto responde con el error
  genérico; «otros problemas», si osTicket tampoco recibió el mensaje, responde que no pudo enviarlo. Cada
  intento deja en el log una línea que nombra la clave.
- **La respuesta de fallo de «otros problemas» ya no lleva `extra`**, que eran las cabeceras HTTP de la
  respuesta de osTicket entregadas a un visitante sin sesión. Con osTicket sin configurar, esa línea daba un
  error fatal.
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
- **Desaparece la importación exógena por GET** (`ODS_Usuarios`) y las fichas de credenciales que dejaba **en claro
  en el disco**. La sustituye `bin/cli data-transfer-import`, que escribe las credenciales generadas en un archivo
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

- **Antes**, una excepción no capturada respondía, en cualquier ruta, con su mensaje, su tipo, su código y su traza; fuera
  de local solo se ocultaban las rutas y los argumentos.
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

### 34 · El entorno sale de la configuración: la cabecera `Host` ya no decide si la instalación es local

- **`is_local()` era verdadero en las peticiones a `localhost` o `*.localhost`**, una cabecera que manda el navegador. En
  un servidor que aceptara cualquier `Host`, un visitante podía hacer que el framework se creyera en local: errores con
  rutas y trazas completas, y hasta la elección de las credenciales de la base en `database.php`.
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

`DataTablesHelper::process()` y `processFromQuery()`, que sirven todas las tablas del panel:

- **La respuesta ya no trae `SQL_MAIN_EXECUTED`, `SQL_FILTER_COUNT_EXECUTED` ni `SQL_TOTAL_COUNT_EXECUTED`.** Enseñaban
  al navegador las consultas, tablas y columnas. Para depurar en local: la opción `'debug_sql' => true`, que escribe en
  `logs/datatables-sql.log` (solo con `is_local()`). En el servidor: `DataTablesHelper::lastExecutedSQL()`.
- **La respuesta ya no trae `rawData`** con las filas crudas de la base, todas las columnas seleccionadas incluidas. Se
  leen en el servidor con `DataTablesHelper::rawRows($result)`. Un listado de tarjetas
  (`dataTablesServerProccesingOnCards`) pone él su HTML en `rawData`, como hacen `UsersController`,
  `DocumentsController::dataTablesExplorer` y `PublicationsCategoryController`.
- **`recordsTotal` cuenta con los filtros fijos del listado** (`where_string|where_segment|having_string|having_segment`,
  con su `group_string`) y sin la búsqueda ni los filtros por columna. Antes contaba la tabla entera, incluidas filas de
  otras organizaciones o usuarios e inactivas: un administrador de organización veía el tamaño de las demás.
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

## ⚠ Corregido — la recuperación de contraseña permitía tomar una cuenta, y el enlace dejaba al usuario fuera

- **El código de recuperación se podía adivinar.** Tenía 6 cifras, valía 24 horas, no tenía límite de
  intentos y se buscaba entre los de todos los usuarios. Al acertar, la respuesta devolvía el usuario con el
  hash de su contraseña.
- **El enlace del correo dejaba al usuario sin contraseña**: la generaba, la guardaba y la mandaba con una
  plantilla que no la imprimía. Como era un GET que escribía, un escáner de enlaces del correo lo disparaba solo.
- **Las peticiones decían si un usuario existía**, y el registro de tickets guardaba el código en claro.
- **Tras actualizar, vacía `pcsphp_recovery_password`**: los códigos pendientes de antes valen hasta que caducan,
  y los que quedaron en el registro de tickets los puede leer quien vea ese registro.
- Ruptura 29. Probado en `unit-tests:core/password-recovery-guards`, que falla si se quita la ligadura al
  usuario, el límite de intentos o la retirada del usuario de la respuesta.

## ⚠ Corregido — el importador de usuarios permitía crear un root, y `getByID()` concatenaba el id

- **`UsersModel::getByID()` construía `"id = '" . $id . "'"`.** El importador de usuarios le pasaba la celda
  `id` del archivo subido, así que desde esa hoja se podía inyectar SQL (con sesión de root o de
  administrador general). Ahora va por marcador, con `WhereSegment`. Sus otros llamadores del framework
  recibían enteros y no eran explotables.
- **El importador aceptaba la columna `type` del archivo sin validar** (ruptura 27).
- Probado en `unit-tests:core/importer-users-guards`, que falla si se quita cualquiera de las dos guardas.

## Corregido — los correos de aprobación y de alta por la API llevaban el nombre sin escapar

- El correo que avisa de que un contenido se aprobó o se rechazó metía en el HTML el nombre del usuario y el motivo
  sin escapar; el de bienvenida del alta por la API (`/core/api/users/register`, pública), el nombre. Ahora se escapan.
  Probado en `unit-tests:core/mail-senders-db`.

## Corregido — el comentario de un token genérico llevaba su HTML al correo

- `GenericTokenController::commentary()` metía el mensaje de quien tiene el enlace en el HTML del correo sin
  escapar. Ahora se escapa. Probado en `unit-tests:core/mail-senders-db`, que falla si se quita el escape.

## ⚠ Corregido — los correos de los formularios públicos llevaban el HTML del visitante

- **El formulario de contacto y el de «otros problemas» metían lo que escribe el visitante en el HTML del
  correo sin escapar**: nombre, correo, asunto, mensaje y los campos `extra`. Cualquiera, sin sesión, podía
  mandar al administrador un correo con enlaces o contenido falsos. `clean_string()` solo quitaba saltos de
  línea y espacios.
- Ahora las plantillas `mailing/generic-contact-form.php` y `usuarios/mail/other-problems.php` escapan esos
  valores. El título del formulario de contacto sigue siendo HTML, porque lo compone el servidor.
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
decisión: ausencia de archivo, subida que no llegó por POST, cada código `UPLOAD_ERR_*` y el código
desconocido. El mismo defecto (T135: una comparación laxa contra `'FAKE_ERROR'` que desde PHP 8
aprobaba sin archivo) hubo que arreglarlo en las dos, con dos años de diferencia.

Ahora las dos llaman a **`PiecesPHP\Core\Forms\UploadedFileValidation::errors()`**, que devuelve la
lista de errores (vacía si el archivo es válido) y recibe cómo mostrar cada texto: `FileUpload` los
deja en español, como antes, y `UploadedFileAdapter` los traduce, como antes.

- **Sin cambios de comportamiento**, comprobado con una suite de caracterización escrita y commiteada
  ANTES de unificar: `unit-tests:core/upload-validation` fija, para cada caso y en las dos clases, el
  resultado, los mensajes exactos y la excepción.
- **Corregido de paso en `UploadedFileAdapter`:** los mensajes de tamaño máximo se traducían con el
  valor ya pegado dentro (`… (8MB)`), así que la traducción nunca casaba. Ahora se traduce el texto
  fijo y el valor se añade después. En español el texto no cambia.
- Las constantes `NOT_UPLOAD_FAKE_ERROR` y `NOT_UPLOAD_FAKE_TMP_NAME` de las dos clases siguen
  existiendo, con el mismo valor: ahora apuntan a las de `UploadedFileValidation`.

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

## ⚠ Corregido — el nombre de usuario entraba tal cual en el SQL del OTP y del login

`OTPHandler::getUserDataByUsername()` construía su filtro pegando el nombre recibido:
`where("username = '{$usuario}' OR email = '{$usuario}'")`. Como el ORM trata una cadena como
SQL literal, ese valor llegaba a la base como código, no como dato.
- **Se alcanzaba sin sesión** desde `generate-otp`, `check-totp`, `two-factor-auth-status` y el
  propio inicio de sesión.
- **Ahora va por marcador**, y lo vigila una prueba que falla si vuelve la forma vieja.
- **En la misma revisión se cerraron tres más**, menos graves: los nombres de tipos de documento
  y de categorías de formularios, y el borrado del token de recuperación por su valor.

## Corregido — los archivos protegidos se servían como públicos para las cachés

Lo que sirve PHP tras validar el acceso (subidas de documentos, organizaciones, categorías de
noticias y publicaciones) salía con `Cache-Control: public`, así que un proxy o una CDN podía
guardarlo y dárselo a otro.
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

## ⚠ Corregido — cualquier usuario con sesión podía reescribir cualquier traducción

Era a la vez un control de acceso roto y un XSS almacenado: lo guardado se imprime sin escapar,
porque las traducciones pueden llevar HTML a propósito. Ahora el servidor traduce y valida lo que
guarda. Detalle y migración en la ruptura 18.

## Corregido — el buscador de las tablas del panel manda el texto por marcador

`DataTablesHelper::process()` metía en el SQL lo que se escribía en el buscador de las tablas,
escapado con `escapeString()`, que depende del `sql_mode` del servidor.
- **Ahora:** cuando el listado no pasa su propio `having_string`, el texto buscado viaja por
  marcador. Así pasa en 18 de los 21 listados del framework.
- **Las búsquedas normales dan exactamente lo mismo que antes**, también las que usan `%` y
  `_`. Se comprobó contra la aplicación con sesión, en los 21 listados, con nueve búsquedas cada
  uno y comparando antes y después.
- **Un solo cambio visible:** buscar una barra invertida (`\`) antes no filtraba nada, porque
  `escapeString()` la convertía en una búsqueda vacía. Ahora se trata como texto.
- **Los tres listados que pasaban su propio filtro de texto** (el de aprobaciones y dos de los
  intentos de acceso) también van ya por marcador (`having_segment`), con el mismo resultado.
- **`having_string` sigue admitido como legado**, para los módulos de proyectos clonados: si un
  listado lo pasa, el buscador vuelve a la vía antigua con `escapeString()`. Se recomienda pasar
  a `having_segment`.

## ⚠ Corregido — los informes de accesos enviaban al navegador el hash de las contraseñas

La respuesta de los informes de accesos del panel (usuarios que han entrado y que no) incluía,
en `rawData`, **todas las columnas de cada usuario, con el hash de su contraseña**. La causa era
que `UsersModel::fieldsToSelect()` seleccionaba la tabla entera.
- **Ahora:** `fieldsToSelect()` ya no selecciona `password`. Ningún listado ni ninguna
  consulta que lo use devuelve el hash.
- **Tampoco `/users/all/`** (`users-ajax-all`), que armaba su propio `SELECT` de la tabla
  entera y devolvía el hash de todos los usuarios a cualquiera con sesión. Sigue devolviendo las
  demás columnas.
- **Si en tu proyecto leías `password` de una fila obtenida con `fieldsToSelect()`**, ya no
  llega. Carga el usuario con su mapper cuando necesites comprobar la contraseña, como hace el
  inicio de sesión.
- **Si tu instalación ha estado expuesta**, cualquiera con acceso a esos informes pudo ver los
  hashes. No son las contraseñas en claro, pero conviene pedir que las cambien.

## Corregido — las etiquetas traducidas entran en el SQL como literal hexadecimal

Seis mappers (Organizations, Users, Banner, News, SystemApprovals y Publications) muestran el
nombre de un estado, un tamaño o un tipo metiendo en el `SELECT` un JSON con las etiquetas
traducidas, entre comillas.
- **Antes:** Organizations lo escapaba con `escapeString()`, Users con `addslashes()` y los
  otros cuatro no escapaban nada. Una etiqueta con apóstrofo rompía la consulta. Como las
  traducciones se pueden editar desde el panel, era además una vía de inyección SQL.
- **Ahora:** entran con `sqlStringLiteral()`, un ayudante nuevo que las convierte en un literal
  hexadecimal (`CONVERT(X'…' USING utf8mb4)`). Ninguna comilla ni barra puede cerrar la cadena,
  sea cual sea el `sql_mode` del servidor. Se ven las mismas etiquetas que antes.
- **Para tu código:** un texto **del servidor** que tenga que ir dentro del SQL va con
  `sqlStringLiteral()`; un valor **de la petición** va por marcador.

## Corregido — 17 comparaciones dejan de depender de `escapeString()`

`escapeString()` es `addslashes(stripslashes())`. Con `NO_BACKSLASH_ESCAPES` activo en el
servidor, la comilla sigue cerrando la cadena, y el framework no fija nunca `sql_mode`.
- **Pasan a marcador 17 de sus 22 usos:**
  - el login;
  - las búsquedas de usuarios por criterios;
  - las de aprobaciones;
  - las comprobaciones de nombre o código duplicado de países, estados, ciudades, puntos,
    organizaciones (NIT), categorías de noticias, documentos, categorías de publicaciones y
    publicaciones.
- **Para el usuario no cambia nada, salvo en los bordes:**
  - un nombre de usuario con comilla o barra ahora entra en el login;
  - un nombre con barra ya no choca por error con otro parecido.
- **`escapeString()` todavía NO está marcada como obsoleta**, aunque el mensaje del commit
  `a5e5e231` lo diga. Le quedan dos usos sin vía directa a marcador: las etiquetas de
  organizaciones dentro de su `SELECT` y la búsqueda de las tablas del panel en
  `DataTablesHelper::process()`. Se marcará cuando no quede ninguno.
  *Corrección (2026-09-16):* hoy le queda **uno**, la búsqueda de `DataTablesHelper::process()`
  (`DataTablesHelper.php:1329`); las etiquetas de organizaciones ya van sin ella. **No la uses en código
  nuevo:** manda el valor por marcador.

## Herramientas — `verify-integrity` exige que toda carpeta de subidas esté protegida o declarada

Comprobación 29. Toda constante `UPLOAD_DIR` de `src/app` tiene que cumplir una de dos cosas:
- estar registrada en `ProtectFileMiddleware::protect()`, lo que se lee en tiempo de ejecución
  con `getProtectedDirectories()`;
- estar declarada en `files/dev/upload-dirs.json`, como `publicas` o `sin_archivos`, con su
  motivo.

También falla si una entrada del registro ya no casa con ninguna constante, porque el registro
solo encoge, o si una carpeta está en dos sitios a la vez. Un módulo nuevo con subidas no puede
nacer servible a cualquiera sin decirlo.

## Corregido — `ProtectFileMiddleware::protect()` no protegía una carpeta que aún no existía

Si la carpeta no existía al arrancar, `protect()` volvía sin registrar nada, y cuando después se
creaba, Apache la servía entera sin validador. **Ahora la crea, escribe su `.htaccess` y la
registra.** Si no puede crearla, lanza una excepción con la ruta: una carpeta que no se puede
proteger no se deja servible en silencio. Además, proteger `…/publications` ya no protege por
error `…/publications-x`: la comparación exige el separador.

## ⚠ Corregido — inyección SQL en las búsquedas de los listados paginados, dos de ellos públicos

Estas rutas metían en su SQL, sin escapar, un valor que manda quien pide:
- **Sin sesión:**
  - `publications-ajax-all`, en sus parámetros `title` e `ignoreSlugs`;
  - `built-in-banner-ajax-all`, en `title`.
- **Con sesión:**
  - `news-admin-ajax-all`, en `newsTitle` e `ignoreSlugs`;
  - `organizations-admin-ajax-all`, en `name`;
  - `geojson-manager-admin-contents-geojson-features`, en `search`.

Ahora esos valores viajan por marcador. **Las búsquedas legítimas devuelven lo mismo que antes.**
`ignoreSlugs` valida además todos sus elementos; antes solo miraba el último.

**Si tu despliegue tiene alguna de estas rutas, actualiza.** Y si copiaste el patrón a un módulo
tuyo, búscalo: una cadena SQL que mete `{$variable}` entre comillas y acaba en `PageQuery` o en
`prepare()` sin valores.

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
  - Las seis de `DataTablesHelper::processFromQuery()` quedan declaradas con un `count` exacto:
    un sitio de más o de menos, falla.
  - Lo que aporta ahí la petición está acotado: la bandera `searchable` de `columns` y el índice
    y la dirección de `order`.

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

`DataTablesHelper::process()` y `processFromQuery()` metían la dirección de cada entrada de
`custom_order` en el `ORDER BY` tal cual llegaba. Ahora sale `ASC` si lo es (sin distinguir
mayúsculas) y `DESC` en cualquier otro caso, igual que el orden que pide el navegador. Los
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

## La búsqueda de países manda el valor por marcador, no concatenado

`Country::search()` —ruta pública, sin sesión— armaba su `WHERE` interpolando lo que llega por
`getQueryParams()`. `clean_string()` quita espacios y saltos, **no comillas**, y
`ActiveRecord::where(string)` concatena. Ahora va por `WhereSegment`/`WhereItem`, que es la vía
que **ya existía** en el paquete: la sentencia lleva un marcador y el valor viaja en
`getReplacementValues()`.

`bin/censo-sql-concatenado` mide el resto y **dice su cota**. Al enseñarle también `->having(`
—que concatena igual— aparecieron cinco casos que no veía: el punto de partida real era **13**,
no 8. Arregladas las cuatro búsquedas de `Locations`, quedan **10**, congeladas en un trinquete.

**Los filtros `IN (...)` van por validación de dominio, no por marcador.** `WhereItem` no
parametriza `IN`, `NOT IN` ni `FIND_IN_SET` —los tres están en `NOT_ALIAS_OPERATORS` y
`toString()` imprime el valor en crudo—, y el paquete `database` está etiquetado y no se toca
aquí. Así que `ids` pasa por `array_map('intval')` con descarte de los `<= 0`, y `region` por un
patrón estricto; **si la lista queda vacía, el criterio no se añade**, porque `IN ()` no compila.

Las tres quedan declaradas, con la validación que cierra cada una, en
`files/dev/sql-concat-declared.json`.

**Si tu despliegue llamaba a `/locations/{countries,states,cities}/?ids[]=…` con algo que no
fuera un entero**, antes recibía un **500** y ahora recibe **200 con el criterio omitido**.

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
paréntesis, y la expresión se comía el resto del método. Dos sitios de `DataTablesHelper::process()`
salían decididos sin estarlo, y pasan de declarados a revisar a mano. **`DECLARADO` baja de 10 a 8
y no es una mejora: era una cifra inflada.** `CONFIRMADO` sigue en 0, y un canario nuevo cae si el
defecto vuelve.

## Herramientas — `bin/censo-sql-interpolado`: lo que el censo de SQL no mira

`bin/censo-sql-concatenado` mira llamadas y claves de array; no ve un valor de la petición metido
en una variable que después se ejecuta como SQL. El guion nuevo lo mide: 223 cadenas SQL armadas
con una variable, 38 llegan a `prepare`, `query` o `exec` en su propio método, y 6 llevan algo de
la petición —las seis son una sola cadena en `processFromQuery()`, y lo que llega es un nombre de
columna, no un valor—. **No es una puerta**: su traza no cruza de método, y 126 de las 223 cadenas
salen del suyo. `files/dev/sql-concat-baseline.json` dice ahora qué significa `CONFIRMADO 0`: que no
quedan concatenaciones **de las formas que el censo mira**.

## Corregido — la búsqueda de `processFromQuery()` metía el texto buscado en el SQL

`DataTablesHelper::processFromQuery()` —la que usa el listado de perfiles de `MySpace`— interpolaba
el HAVING de la búsqueda en la sentencia. Ahora el texto buscado viaja por marcador. Los resultados
no cambian.

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
`OrganizationsController` y `PublicationsController` pasan a `having_segment` y conservan la
validación de dominio de sus filtros.

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

Con esa lista, el filtro `referenceAlias` valida su dominio **y** viaja por marcador con
`where_segment`. El `elapsedDays` se queda en el fragmento de cadena con su validación de entero,
declarado, porque el módulo tiene columnas buscables y un `having_segment` chocaría con la guarda
que impide perder la búsqueda.

**Con esto el censo de SQL concatenado llega a CERO confirmados.** No significa que no quede SQL
concatenado: significa que todo el que recibe un valor de la petición está declarado con su
validación, o va por marcador.

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

## Corregido — `DataTablesHelper` ejecutaba la forma de DEPURACIÓN de su SQL de conteo

`process()` armaba su conteo filtrado con `getCompiledSQL()` **sin argumento** (línea 665), que es
la forma de depuración: sustituye `:ALIAS` por `(ALIAS=valor)`, **sin los dos puntos**. Ese texto
se envolvía en `SELECT COUNT(*) FROM (…)` y se ejecutaba, así que MySQL leía el alias como nombre
de columna —`Unknown column 'WH…_UPPERREGION' in 'WHERE'`—. Sus dos líneas hermanas, la 450 y la
686, ya llamaban con `true`.

**Es un defecto preexistente, no una regresión**: con `where_string` no hay valores de reemplazo,
el bucle de sustitución no hace nada y el SQL salía intacto. Sólo dispara cuando se pasa un
segmento, y por eso apareció al migrar los primeros listados.

Comprobado por HTTP en las tres rutas afectadas: **500 → 200**. Y los cinco arreglos de seguridad
de bloques anteriores se ejecutaron uno a uno: **los seis sitios de búsqueda responden 200**,
ninguno estaba roto.

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

## Tres listados más mandan sus filtros por marcador

`State::statesDataTables`, `UsersController::dataTablesRequestUsers` y
`DocumentsController::dataTablesExplorer` pasan a `where_segment`. Con `Country`, van cuatro
archivos de dieciocho; el censo imprime la cuenta y la saca del árbol.

Dos no se pueden migrar todavía y queda dicho por qué: `OrganizationsController::dataTables` y
`PublicationsController::dataTables` meten su filtro en `having_string` y tienen columnas
buscables, así que chocan con la guarda que impide perder la búsqueda en silencio. Esperan a que
`HavingSegment` sepa agrupar.

En `State` la validación de entero **se queda**: ya no hace falta para la seguridad, pero es lo
que convierte un valor raro en `-1`, que no encuentra nada.

## `DataTablesHelper::process()` acepta `where_segment` y `having_segment`

Dos claves nuevas y **aditivas**: reciben un `WhereSegment` / `HavingSegment` ya construido y sus
valores viajan **por marcador**. Sin ellas, el comportamiento es idéntico al de antes, así que
ninguna controladora tiene que cambiar.

Son **excluyentes** con su cadena equivalente, y `having_segment` **no puede convivir con la
búsqueda de DataTables**: sustituiría al HAVING que ésta genera, y `HavingSegment` no admite
agrupación, así que `(a OR b) AND c` no es expresable. Las tres situaciones lanzan con mensaje
claro en vez de perder un filtro en silencio.

`Country::countriesDataTables` es la primera migrada — de los filtros que quedaban era el único
cuyo valor sigue siendo una cadena. Efecto secundario bueno: un nombre de región con apóstrofo
vuelve a poder buscarse, porque el patrón conservador que lo descartaba ya no hace falta.

## Corregido — tres filtros de listado metían el valor de la petición en el SQL

`Country::countriesDataTables` (`region`), `SystemApprovals::dataTables` (`elapsedDays`) y
`PublicationsController::dataTables` (`visibility`) interpolaban el parámetro en un fragmento de
SQL sin validar nada. Las tres rutas son autenticadas. Ahora `region` pasa por el patrón de
`Country::regionNameOrNull()` —factorizado, una sola copia para los dos sitios que comparan por
nombre—, `visibility` por la lista blanca de `PublicationMapper::VISIBILITIES`, y `elapsedDays`
por `Validator::isInteger`, que además cierra un defecto de tipo: se validaba como cadena no
vacía y se usaba como número.

**El rechazo no ensancha:** un `region` que no case cae en `''`, que no encuentra nada, y no en
`null`, que habría quitado el filtro.

## `DataTablesHelper::process()` dice dónde está la frontera de su contrato

`where_string`, `having_string` y `group_string` son fragmentos de SQL **del programador**, y no
existe vía preparada para ellos: `having()` solo rellena sus valores de reemplazo cuando recibe
un `HavingSegment`. Queda escrito en el docblock que meter ahí un valor de la petición abre un
agujero, junto con las tres claves de identificador —`select_fields`, `columns_order` y
`custom_order`—, y con el aviso de que en `custom_order` la dirección no pasa por el filtro
`ASC`/`DESC`.

## Herramientas — el censo de SQL ve una novena familia, y la cifra sube de 5 a 13

`DataTablesHelper::process()` recibe un **array literal**, y tres de sus claves —`where_string`,
`having_string` y `group_string`— son fragmentos de SQL que el helper interpola. No hay `->where(`
en el sitio, así que las ocho familias de llamada nunca las vieron. El censo busca ahora la clave.

**La cifra sube porque el instrumento aprendió a ver, no porque nadie rompiera nada:** las ocho
concatenaciones que aparecen llevaban ahí desde antes. Cuatro no validan nada
—`Country::countriesDataTables`, `SystemApprovalsController::dataTables` (dos) y
`PublicationsController::dataTables`— y cuatro sí, pero con validaciones que el censo no puede ver
porque mide el mecanismo. Ninguna de las rutas es pública.

Queda dicho además qué otras claves de `$options` acaban en el SQL —`select_fields`,
`columns_order` y `custom_order`—, las tres como identificadores.

## Corregido — el desplegable de usuarios volvía a mostrar los eliminados al buscar

`ActiveRecord::having()` **sustituye** el segmento, no lo acumula. `UsersController::searchDropdown`
tenía dos: el de `status != STATUS_USER_DELETED` y el de la búsqueda, y **el segundo pisaba al
primero**. Al escribir cualquier texto reaparecían los usuarios marcados como eliminados. El
criterio de `status` pasa al `WHERE` —es columna real— junto al de `type`, en un solo `where()`.

## La búsqueda de usuarios manda el valor por marcador, y `ignoreTypes` valida el dominio

Los seis `LIKE` de `searchDropdown` pasan a `HavingSegment` con `LOWER({%VALUE%})`. El
`type NOT IN (...)` **no se puede parametrizar** —los tres operadores de lista imprimen su valor
en crudo—, así que valida contra `array_keys(UsersModel::TYPES_USER_PRIORITY)`, con `is_numeric`
antes de `intval` porque `intval('abc')` da `0` y `0` es `TYPE_USER_ROOT`.

## Herramientas — el censo de SQL concatenado pasa de 2 familias a 8

`bin/censo-sql-concatenado` mira ahora también `orderBy`, `groupBy`, `join`, `leftJoin`,
`rightJoin` e `innerJoin`. **Un array no salva a `orderBy` ni a `groupBy`**: la biblioteca lo
recorre con `implode()` y acaba en la cadena igual. Imprime el reparto por familia y qué familias
siguen sin mirarse. Canario de 17 caras.

Dos **falsos DESCARTADO** corregidos: un método con `extract()` deja el mapa de asignaciones
incompleto, y un `WhereSegment` que lleve `IN`, `NOT IN` o `FIND_IN_SET` **no prepara nada**.
Cada entrada de `files/dev/sql-concat-declared.json` fija además su `count`, para que una
concatenación nueva en un método ya declarado no entre gratis.

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

`UnitTest-AccessGuards`, 33 comprobaciones sobre siete guardas del núcleo: verificación de
hash y de firma JWT, `decode()`, `check()`, `Roles::hasPermissions()`,
`get_route_roles_allowed()` y `Parameter::validate()`. Cada una con su caso de RECHAZO, su
discriminante —uno que NO debe rechazarse— y su provocación: **se quita la guarda y la suite
tiene que ponerse roja**.

Dos contratos que sorprendieron y quedan congelados: `BaseToken::check()` sobre un JWT sin
`exp` devuelve **el objeto del payload**, no `true` ni un código de error —por eso los
consumidores comparan con `!== true` y no con `!`—; y `Roles::hasPermissions()` con un rol
inexistente **lanza**, no devuelve `false`, salvo en modo silencioso.

Y la primera provocación salió **verde**, que es un hallazgo: la prueba comprobaba una guarda
creyendo comprobar otra, porque `decode()` tiene dos en cadena. Se añadió el caso que aísla la
primera.

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

## Herramientas — `bin/censo-comparaciones-cero`, y la clase de defecto de `FileUpload` queda acotada

`FileUpload::validate()` murió en la migración a PHP 8 por comparar con `==` una cadena contra
`UPLOAD_ERR_OK`. La pregunta era cuántas más había. **La respuesta es ninguna, y la clase resultó
ser mucho más estrecha de lo que parecía.**

**Medido en los dos binarios, no deducido del manual:**

| Valor a la izquierda | `== 0` en 7.4 → 8.5 | `== 1` / `== 200` / `== 1305` |
| :-- | :-- | :-- |
| `"abc"` no numérica | **`true` → `false`** | `false` → `false` |
| `""` cadena vacía | **`true` → `false`** | `false` → `false` |
| `"42000"` numérica | `false` → `false` | igual |

**Solo la columna del cero cambió.** Con cualquier otro entero, PHP 7 y PHP 8 coinciden — una
cadena no numérica nunca casó con `1305`. Por eso `FileUpload` murió y los `$e->getCode() == 1305`
de los mappers no: **`UPLOAD_ERR_OK` vale cero**.

El censo **tokeniza** con `token_get_all()`, porque un `grep` no distingue un `==` dentro de una
cadena ni sabe cuál es el operando de la izquierda. Y **resuelve las constantes con `constant()`**:
sin eso no habría cazado el caso que lo funda, que no comparaba contra el literal `0`.

| Repositorio | Archivos | Laxas contra cero | Sin `int` garantizado |
| :-- | --: | --: | --: |
| Framework | 811 | 37 | **9** |
| Los cuatro paquetes | 109 | 8 | **0** |

**Las nueve, leídas una a una: ninguna peligrosa.** Ninguna está en un camino de guarda y ninguna
puede recibir una cadena no numérica —`MetaProperty::TYPE_INT`, `gmdate("H:i:s")`, `floatval()`, un
parámetro declarado `int`—.

**No hay puerta automática**: PHPStan nivel 8 **no detecta esto**, comprobado sobre el caso exacto.

*La primera pasada reportó 7.886 archivos: `bin/tools/` contiene PHPStan y Rector descargados. LEY
15 sobre el propio instrumento, cazada por la rareza de la cifra.*

## ⚠ Corregido — `FileUpload::validate()` decía que sí cuando el archivo no existía

**La migración a PHP 8 rompió esta guarda y nadie se enteró en dos versiones mayores.**

Cuando la clave pedida no viene en `$_FILES`, el constructor rellena la información con valores
falsos y `error` = `'FAKE_ERROR'`, **una cadena**. `validate()` la comparaba con `==` contra los
`UPLOAD_ERR_*`, que son enteros:

| | `'FAKE_ERROR' == 0` |
| :-- | :-- |
| PHP 7.4 | **`true`** → entraba en la rama, `is_uploaded_file()` fallaba, `$valid = false` |
| PHP 8.0 en adelante | **`false`** → **ninguna rama casaba** |

Y la cadena de `if/elseif` **no tenía `else`**. Así que `$valid` se quedaba en `true` y
`validate()` **afirmaba que había un archivo válido cuando no había ninguno**.

**Nueve accesos de producción dependen de esa respuesta** — el patrón
`$name = $_FILES[$nameOnFiles]['name'];` repetido en ocho controladores más `ImagesRepository`—,
todos detrás de un `if ($valid)`. Con la guarda muerta, quedaban desprotegidos.

Se arregla por los dos lados: una rama explícita para `FAKE_ERROR` **comparada con `===`**, y un
`else` final para cualquier código de error que no reconozcamos.

**Y se fija con una puerta nueva, `core/file-upload-contract` (7/7)**, porque un contrato escrito
en un comentario no es un contrato. Cada rama del arreglo tiene su propia comprobación, y se ha
verificado **quitándolas por separado**: sin la rama del `FAKE_ERROR` falla una; sin el `else`
final falla otra. La primera versión de la suite no distinguía —quitar cualquiera de las dos la
dejaba en verde— y ese es justo el fallo que la LEY 24 describe.

### E2-b · el resto de los accesos a superglobales, medidos y clasificados

|  | ocurrencias | líneas | archivos | sin guarda |
| :-- | --: | --: | --: | --: |
| `$_FILES` | 49 | 44 | 18 | **0** |
| `$_POST` | 77 | 41 | 18 | **0** |

Ninguno queda desprotegido: o llevan `isset`/`empty`/`array_key_exists` delante, o usan el
**array completo** —donde no hay índice que pueda faltar—, o se apoyan en el contrato de
`FileUpload` que esta misma entrada arregla. En los módulos que E3 va a borrar no se ha tocado
nada: 3 accesos a `$_FILES` y 2 a `$_POST` en `ImagesRepository` y `ApplicationCalls`.

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

**Si tienes copias hechas con `bin/cli db-backup` de una versión anterior a esta, no sirven
tal cual: restaurarlas deja a TODOS los usuarios sin poder entrar.** No es una sospecha; está
medido de punta a punta —volcado, restaurado en una base de usar y tirar, intento de login—.

**Por qué**: la exportación cifraba la columna `password` y **nada la descifraba al restaurar**,
así que en la base restaurada `password_verify()` recibe un hash cifrado y devuelve `false`
siempre.

### LOS DATOS NO ESTÁN PERDIDOS. Así se recuperan

El cifrado es reversible con la clave literal que se usaba. Comprobado: devuelve el hash
`$2y$…` exacto, byte a byte.

```php
$hashReal = PiecesPHP\Core\BaseHashEncryption::decrypt($valorDelVolcado, 'ENCRYPTION_KEY');
```

**Procedimiento:**

1. Restaura la copia como siempre: `mysql -u <usuario> -p <base> < dumps/<archivo>.sql`
2. Recorre `pcsphp_users` y sustituye cada `password` por su `decrypt(...)` con esa clave.
   Se puede hacer también sobre el `.sql` antes de cargarlo, si prefieres no tocar la base.
3. Comprueba con un usuario que conozcas: `password_verify('<su contraseña>', $hashReal)`
   tiene que devolver `true`.

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

**Versiones afectadas: todas hasta la `v7.1.0` incluida.** El framework es una plantilla que
se clona, así que este defecto **existe en cualquier despliegue anterior a esta entrada**.
No se puede corregir a distancia; quien actualice debe saber qué está corrigiendo.

**Qué es:** un **defecto de diseño con escritura no autenticada acotada.** Comprobar
credenciales en el login insertaba filas en `pcsphp_users_otp_secrets`, generando de paso un
secreto TOTP, sin que nadie se hubiera autenticado. La causa es que
`UserDataPackage::__construct()` llamaba a un buscador que creaba el registro al leerlo.

**Qué NO es:** no hay toma de cuentas. El secreto pregenerado **nunca llega a ser una
credencial viva**, porque activar el 2FA lo regenera. Tampoco hay crecimiento sin límite: como
mucho una fila por usuario y método.

**Qué implica en la práctica:**

- Filas y secretos TOTP creados para cuentas que nunca pidieron 2FA.
- Una primitiva de escritura alcanzable sin autenticar, acotada pero real.
- Un canal de enumeración de usuarios **débil**: el estado de la base cambia solo si el
  nombre existe. En la mayoría de despliegues estará tapado, porque una rutina de relleno que
  corría en cada petición ya había creado todas las filas.

**Al actualizar:** las filas existentes son inertes y **no hace falta purgarlas**. Vaciar la
columna `secret` de las filas `TOTP` con `twoAuthFactor = 'DISABLED'` es higiene opcional, de
prioridad baja. **Las filas `ONE_USE_CODE` no se tocan**: pueden sostener códigos vigentes;
mirar `maxDate` antes de nada.

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

- **`db-backup` cifraba las contraseñas y NADIE las descifraba: una restauración dejaba a todos
  los usuarios sin poder entrar.** Severidad alta, y está embarcado en todos los despliegues.
  Medido de punta a punta —volcado, restaurado en una base de usar y tirar, intento de login—:
  `password_verify` contra lo restaurado devuelve **false**.
    - El cifrado tampoco protegía nada: la clave era la **cadena literal `'ENCRYPTION_KEY'`**
      escrita en el propio archivo, y aparecía **una sola vez en todo el código** —en la llamada
      que cifra—. No hay ni un `decrypt` con esa clave en ninguna parte.
    - **Si tienes volcados viejos, no están perdidos**: la columna `password` se recupera con
      `BaseHashEncryption::decrypt($valor, 'ENCRYPTION_KEY')`, comprobado.
    - `bin/cli unit-tests:core/db-backup-round-trip` fija el viaje de ida y vuelta, y se validó
      rompiéndola: con la transformación puesta, falla 2 de 4.

- **Dos `false` que eran un fatal en ejecución, no un aviso de tipos.**
    - `src/index.php`: al recorrer el directorio de sesiones expiradas, la fecha se sacaba del
      **nombre del archivo**. Un archivo que no encajara con el patrón hacía que
      `DateTime::createFromFormat()` devolviera `false`, y la llamada siguiente reventaba la
      petición entera. Ahora ese archivo se descarta.
    - `PublicationsController`: el sello de última modificación podía llegar sin hidratar desde
      el mapper, y `getTimestamp()` sobre una cadena es un fatal. Guarda con `instanceof`, y el
      valor por defecto pasa a `new DateTime()`, que no puede devolver `false`.
    - Con esto quedan **cero** errores de la forma «llamar a un método sobre un `false`».

- **Ver el QR del doble factor dejaba el flujo sin salida: PREPARAR NO ES ACTIVAR.**
  `OTPSecretsUsersMapper::toggle2FA()` escribía `twoAuthFactor = ENABLED` al pulsar
  «Activar», antes de que el usuario confirmase nada. A partir de ahí, volver a pulsar
  «Activar» entraba por la rama `if (!$isCurrentlyEnabled)` de
  `UserSystemFeaturesController::configureTOTP()`, que **no regeneraba el código de
  seguridad y lo devolvía vacío** aunque respondiera «Activado.»; y `user-security.js` solo
  muestra el QR si ese código no viene vacío. Al recargar, la vista revertía el estado y
  **regeneraba el secreto**, invalidando en silencio cualquier QR ya escaneado.
    - `toggle2FA()` solo **prepara**: guarda secreto, alias y hash del código de seguridad, y
      deja el estado en `DISABLED`.
    - El nuevo `confirm2FA()` es lo único que escribe `ENABLED`, junto con
      `twoAuthFactorQRViewed = 1`, y **no toca el secreto**: el QR escaneado sigue siendo
      válido. `markQRDataAsViewed()` lo llama y **propaga su fallo** en vez de responder éxito.
    - **No había bloqueo de acceso**: la puerta de login exige `isEnabled2FA` **y**
      `wasViewedQRData`, y esta última seguía en `0`. Comprobado reproduciendo el estado
      antiguo, no deducido.
    - La rama de reversión de `MySpace/Views/user-security.php` **se mantiene**: es el camino
      de salida de las filas que quedaran armadas antes de este cambio.
    - Queda abierto y anotado: confirmar **no exige demostrar un TOTP válido**, así que
      confirmar sin haber escaneado bien sí deja fuera de verdad.

- **Comprobar credenciales dejaba de escribir en base de datos.**
  `OTPSecretsUsersMapper::getOTPData()` y `getTOTPData()` eran get-or-create: si no
  encontraban registro, lo insertaban generando un secreto TOTP. Como
  `UserDataPackage::__construct()` llama al segundo sin condiciones, **construir un paquete
  de usuario escribía**, y lo alcanzan sin autenticar `checkValidityOTP`,
  `checkValidityTOTP`, `toExpireOTP` y `generateOTP`. Los dos buscadores son ahora puros y
  devuelven `null`; la creación vive en `createOTPData()` / `createTOTPData()` y solo la
  llama `toggle2FA()`, donde el usuario ya autenticado configura su segundo factor.
    - Severidad **baja** en seguridad: el secreto se regenera al activar el 2FA, así que el
      material pregenerado nunca llega a ser credencial viva.
    - `TOTPData` pasa a ser nulable de verdad; seis sitios lo manejan de forma explícita.

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

- **AVISO, defecto abierto y de la misma familia que el del login**: `UserDataPackage`
  llama a `UserProfileMapper::getProfile()`, que es **get-or-create**, en la línea
  siguiente a la que se corrigió para el OTP. Ese constructor se alcanza **sin autenticar**
  desde el formulario de login, así que **comprobar credenciales sigue creando una fila de
  perfil**. Acotado —una por usuario— pero es escritura no autenticada. No se corrige aún.
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

- **`BaseHashEncryption::encrypt()` y `decrypt()` dejaron de descifrar en PHP 8.5.** Las dos
  suman y restan bytes y **dependen de que `chr()` dé la vuelta en 256**; desde 8.5 eso emite
  una deprecación y, con el manejador de errores del framework, **lanza**. Efecto visible: la
  configuración de correo dejaba de descifrarse y **cinco páginas públicas devolvían 500** —
  recuperación de contraseña, desbloqueo de usuario, «no recuerdo mi usuario», problemas de
  ingreso y solicitud de soporte.
    - El arreglo es `& 0xFF`, que **reproduce ese desbordamiento exactamente**. Comprobado
      valor a valor entre −600 y 600 contra el `chr()` de 8.4: cero diferencias.
    - **Compatible con lo ya cifrado**, demostrado en las dos direcciones: el código nuevo
      descifra lo cifrado por el viejo y el viejo descifra lo del nuevo, con cifrado byte a
      byte idéntico.
    - **NO SE TOCA ESA ARITMÉTICA.** Cualquier otro cambio vuelve indescifrable todo lo ya
      guardado en cada despliegue. El archivo lleva la prohibición escrita.

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
    - [Ver](./files/dev/tests.md)
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
