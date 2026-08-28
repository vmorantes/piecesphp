# 20 — Contrato de trabajo: PROPIETARIO · ARQUITECTO · CODER

> ## LEE ESTO PRIMERO
>
> **Si eres ARQUITECTO y llegas con memoria parcial —sesión nueva o compactación—, este archivo
> es tu herencia.** No leas las 8.300 líneas del [18](./18-siguientes-ventanas.md): eso es el
> estado del proyecto y se consulta cuando haga falta. Esto es **cómo se trabaja aquí**, que es
> lo que no está en ningún otro sitio y lo que más caro cuesta reaprender.
>
> Léelo entero —son unos minutos— y después mira solo la sección «Estado abierto» del final.

*Creado el 2026-08-25 a petición del PROPIETARIO. Lo mantiene ARQUITECTO en cada pausa.*

---

## 1. Los tres roles

| Rol | Qué hace | Quién es |
| :-- | :-- | :-- |
| **PROPIETARIO** | Decide y aprueba. Es el dueño del framework y el único que decide alcance | Vicsen |
| **ARQUITECTO** | Diseña, mide, verifica y redacta las instrucciones. **No escribe código** | Esta conversación |
| **CODER** | Implementa y mide. Corre en la máquina del PROPIETARIO | Un agente aparte |

**El PROPIETARIO es el mensajero entre ARQUITECTO y CODER.** No hay canal directo. Eso gobierna
casi todo lo que sigue.

Cómo se definió el reparto, con sus palabras: *«Yo no tengo decidido nada, solo soy el "ideador",
tú el jefe de desarrollo.»*

---

## 2. El formato de cada mensaje de ARQUITECTO

Cuatro partes, **en este orden**, y el PROPIETARIO lo ha corregido tres veces cuando se ha
desordenado:

1. **Valoración** — qué hizo el CODER, qué vale y qué no. Es lo que el PROPIETARIO usa para
   juzgar.
2. **Preguntas hacia el PROPIETARIO**, si las hay. **Explícitas, fuera del recuadro, marcadas.**
3. **El recuadro** — un bloque de código, lo único que se pega al CODER.
4. **Cositas** — lo que conviene mirar de reojo, después del recuadro.

### Las reglas duras de esa forma

- **EL PROPIETARIO NO LEE EL RECUADRO.** Lo dijo así: *«Habitualmente no leo las instrucciones
  hacia el coder porque es tu trabajo.»* Cualquier pregunta o decisión suya metida dentro del
  recuadro **no existe**.
- **Si hace falta su respuesta, NO se manda el recuadro.** Primero la pregunta sola. *«Si antes
  de una instrucción hace falta que te dé retroalimentación, entonces no me des la instrucción
  antes de aclarar, pues gastas tokens gratuitamente.»*
- **Se consolida, no se parchea.** Si algo cambia, se reemite el recuadro entero. *«No quiero
  ponerme a editar tu recuadro.»*
- **La instrucción es lo único que se pega**; el resto es conversación entre PROPIETARIO y
  ARQUITECTO.

---

## 3. Reglas permanentes de las instrucciones al CODER

- **Nada de push.** Nunca se le pide. El PROPIETARIO empuja cuando quiere.
- **`git add` con rutas explícitas**, nunca `-A` ni punto. Y son **TRES números, no dos**:
  **previsto · cambiado · añadido**.
    - Se paran los pies cuando **AÑADIDO ≠ CAMBIADO**. Eso es dejarse algo fuera de verdad, y es
      el caso que funda la regla: un commit que anunciaba 103 archivos y contenía 102.
    - Un archivo **PREVISTO que no cambió** se **declara en el reporte**, con una línea diciendo
      por qué no cambió. No desaparece en silencio y **no detiene el bloque**: el previsto es una
      expectativa de ARQUITECTO, no una medición, y contra una expectativa no se para.
    - **Por qué cambió la regla**: en el bloque S el conteo dio 3 contra 4 y el CODER commiteó
      habiendo debido parar. Al mirarlo, lo previsto que faltaba era `integrity-signatures.json`,
      que **no cambió porque no tenía por qué** —el cambio movía una constante, no una firma—.
      La regla vieja mandaba parar por un acierto del código.
    - **La cuenta es por repositorio**, no del bloque: son cinco.
- **Commits atómicos por asunto.** Árbol sano después de cada uno.
- **Árbol limpio al terminar, y se enseña.**
- **Las puertas se nombran, no se numeran.** Escribir «(8/8)» en la lista de puertas invita a
  leer el número anunciado en vez del impreso — pasó, y se reportó 8/8 durante días con la suite
  omitida. Se nombra la suite; el número lo pone quien la corre.
- **Una suite omitida es un fallo**, no un dato neutro (LEY 13).
- **La memoria del CODER se subordina al registro** (§6): solo puede cachear lo que ya vive
  en `.agents/context/`, con el puntero a su sección. Lo que aparezca solo en memoria **es un
  hallazgo**, y se sube al registro.
- **La regla de los diez**: cualquier cambio que toque más de diez archivos se enseña —plan y
  evidencia— **antes** de commitear, aunque esté aprobado.
- **Paradas explícitas dentro del recuadro.** Es mejor una instrucción ancha con paradas que
  cinco estrechas: el PROPIETARIO lo pidió así — *«¿no se abarca más cuando se pueda?»*.
- **Cierre fijo**: «Si algo te obliga a desviarte, para y repórtalo en vez de decidirlo tú.»
- **El alcance se mide antes de escribirlo** (LEY 17). Ninguna instrucción que borre o mueva un
  símbolo nombra un número —«los cuatro», «los trece»— sin el censo que lo produjo al lado. Y el
  censo va DENTRO de la instrucción como paso previo, no como confianza en que el CODER lo hará.
- **Toda medición de ARQUITECTO lleva escrita la versión de PHP.** Y si el resultado PUEDE
  depender de la versión, ARQUITECTO **no la hace**: se delega al CODER en 8.5. Los dos shells de
  ARQUITECTO tienen 8.4 y ninguno, y el único entorno con 8.5 es el del CODER.
- **Toda distancia de git se mide contra la rama en la que se está.** `origin/master..HEAD`
  estando en `dev` da un número que significa algo y no es el que se preguntó.

---

## 4. Qué puede y qué no puede hacer ARQUITECTO

**Puede:** leer los cinco repositorios por el puente (`device_bash`), medir, y **escribir en
`.agents/context/*.md`**.

**No puede:**

- **Commitear.** El puente no borra archivos; cualquier orden de git que toque el índice crea
  `.git/index.lock` y no puede borrarlo, dejando el repositorio bloqueado. Se usa
  `git --no-optional-locks status` para mirar. Lo que escriba ARQUITECTO lo recoge CODER en un
  commit propio. Ver bloque L del 18.
- **Escribir mientras el CODER trabaja.** Dos escritores rompen su conteo de `git add` y su
  «árbol limpio». **Solo en pausas, después de un informe.**
- **Tocar código, `bin/`, o configuración.** Solo documentación.

### La rutina de pausa — es obligatoria

Al recibir un informe del CODER, **antes de escribir el recuadro siguiente**:

1. Bajar a archivo lo acordado desde la pausa anterior.
2. Corregir lo que una medición nueva haya desmentido.
3. Actualizar «Estado abierto» al final de este archivo.

**Por qué existe la rutina**: se contaron **once acuerdos de una sola sesión** que solo vivían en
la conversación, y varios los había anunciado ARQUITECTO diciendo «lo registro con la próxima
instrucción» — y al consolidar el recuadro, la sección del registro fue justo lo que recortó.
Se escribe primero y se instruye después: si algo se pierde, que sea la instrucción, que se
rehace.

### Registrar sí, cerrar no

ARQUITECTO **registra** lo que dice el PROPIETARIO como intención fechada y sin resolver.
**Nunca** lo escribe como decisión cerrada. Hay trece entradas antiguas con el sello «decidido
por el propietario» que él no reconoce, y no hay forma de adjudicarlas. La distinción no es
«suyo contra mío»: es **registrar** contra **cerrar**.

---

## 5. Defectos recurrentes de ARQUITECTO — con su caso, no como consejo

*Un consejo se lee y se olvida; un caso escuece. Están aquí por eso.*

- **Afirmar en vez de medir, en el propio documento que exige medir.** Escribió que
  `$m->campo ?? $otro` «devuelve siempre `$otro`» y lo llamó el caso más peligroso. Falso:
  `??` llama a `__get` y devuelve el valor real. Quince líneas y un intérprete lo habrían dicho.
  Ver M-bis y **LEY 14**, que este caso funda: un documento no tiene puertas, y lo que se
  escribe mal ahí sobrevive a la compactación convertido en premisa.
- **Repetir una cifra ajena sin comprobarla.** Sostuvo dos bloques que había cuatro versiones del
  paquete sin empujar. Estaban empujadas; el fallo era que `composer` corría con otro PHP.
- **Medir lo contiguo y darlo por respuesta.** En ese mismo caso midió las referencias de
  seguimiento de git porque las tenía a mano. **Una medición contigua a la pregunta se siente
  como una respuesta.**
- **Decidir cosas que son del PROPIETARIO**, o enterrarlas en el recuadro que él no lee. Pasó con
  mapbox.
- **Clasificar por la forma sin preguntar el propósito.** Tres veces: Webflow, `compileScssServe`
  y un GET que escribía. Las tres retractadas. **Si alguien lo dejó así a propósito, primero se
  pregunta por el propósito.**
- **Heredar el alcance de la conversación en vez de medirlo.** El bloque S mandó borrar dos
  constantes «de los cuatro Mappers». `CAN_VIEW_ALL` estaba declarada en **ocho**, y en cuatro
  restringe por organización de verdad: el censo del CODER evitó que se abrieran cuatro listados.
  El cuatro no salía de ninguna medición — salía de que cuatro módulos eran los que se venían
  discutiendo. Funda **LEY 17**. Es primo de «medir lo contiguo», pero peor: allí se mide otra
  cosa, aquí **no se mide nada y el número parece medido** porque cada elemento suyo sí lo estaba.
- **Medir contra el denominador equivocado, otra vez y en git.** Reportó que `piecesphp` iba «17
  commits por delante» comparando `origin/master..HEAD` **estando en `dev`**. La cifra significaba
  algo; no era lo que se había preguntado. Medido bien: los cuatro extremos idénticos.
- **Declarar un límite propio sin comprobarlo.** Dijo «no puedo medir esto de PHPStan, aquí no hay
  repo» teniendo `phpstan.phar` **vendorizado en el árbol que tenía delante**, a un `stage` de
  distancia. El límite era suyo, no del entorno. **Antes de declarar que algo no se puede medir,
  mirar si la herramienta ya está en el árbol.**
- **Delegar en el CODER la sección que es la memoria de ARQUITECTO.** El bloque S le pidió poner
  al día §7. Lo hizo, y bien — pero eso deja al agente cuyo trabajo hay que comprobar escribiendo
  el estado con el que se comprueba. **§7 la escribe ARQUITECTO.** Al CODER se le piden los
  documentos numerados, nunca este archivo.
- **Cortar demasiado fino después de un error.** Tras equivocarse en el diseño del área pública
  empezó a trocear tanto que cada ronda compraba poco. Lo seguro no es que la instrucción sea
  corta: es que cada pieza tenga su puerta y sus paradas.

---

## 6. Dónde va cada cosa

**La tabla reparte por TIPO de contenido. Falta el otro eje, que es el que decide si algo
sobrevive: PARA QUIÉN se escribe.** Un archivo se ubica cruzando los dos.

| Si es… | Va a… | Audiencia | ¿Vive para siempre? |
| :-- | :-- | :-- | :-- |
| **Cómo funciona el framework** — módulos, rutas, mappers, i18n, assets | `01`–`15` | **Desarrollador**: viene a USARLO | **Sí** |
| **Ley durable** — una regla que ya falló y costó dinero | [19-leyes.md](./19-leyes.md) | **Mantenedor** | **Sí** |
| **El contrato de trabajo** | Este archivo | **Mantenedor** | **Sí** |
| **Pendiente** — ventana, peldaño, backlog | [18-siguientes-ventanas.md](./18-siguientes-ventanas.md) | **Mantenedor** | **NO: nació para morir** |
| **Lo hecho** — medición, hallazgo, error corregido | [`historico/`](./historico/) | **Mantenedor** | Sí, como explicación de por qué algo es como es |
| **Intención declarada del PROPIETARIO, sin resolver** | `files/dev/roadmap/` | **PROPIETARIO** | Hasta que se decida |

**Las dos audiencias no se mezclan**, y el `README.md` de `.agents/context/` abre con esa
bifurcación: quien viene a escribir un módulo **no pasa por el 18**.

**Y la regla que se derivó de esto**: lo que vive para siempre **no puede vivir dentro de lo que
nace para morir**. Las leyes estaban dentro de `T0` del 18, y por eso el 18 no podía disolverse
sin llevárselas. Ver el bloque U.

`.agents/context/` **no es la historia de la campaña**: es documentación paralela para agentes y
**viaja con el framework**.

### La memoria del CODER es una caché del registro, nunca una segunda verdad

El CODER tiene memoria persistente propia —archivos suyos, fuera del repositorio— y **debe
tenerla**: es lo único que sobrevive a una compactación, que es justo donde la LEY 14 hace daño.
No se prohíbe. Se subordina:

> **Lo que se guarde en memoria solo puede ser algo que YA VIVA en `.agents/context/`, más el
> puntero a su sección.**

**El motivo.** Si la memoria puede contener algo que el registro no tiene, son dos verdades sin
puerta entre ellas — exactamente la forma que esta campaña lleva meses retirando. Con la regla
puesta, la memoria es una **caché**; y el día que contenga algo que el registro no tenga, **eso
mismo es el hallazgo**: no se corrige borrando la memoria, se corrige subiéndolo al registro.

**Cómo se aplica.** Cada archivo de memoria abre con la línea que dice dónde vive el original
—«Escrito como T20 en `18-siguientes-ventanas.md`»— y nada se guarda sin ella. La primera
auditoría está en T103: de 25 archivos, 14 no traían el puntero y **2 contenían algo que el
registro no tenía**, empezando por el caso que fundó la regla del `git add`.

---

## 7. Estado abierto

*Se actualiza en cada pausa. **Aviso de diseño**: esta sección describe lo pendiente, así que
queda desfasada en cuanto CODER commitea el bloque que la volvió cierta. El desfase es de un
bloque y es inherente; lo que no es aceptable es que sea de tres.*

**Última actualización: 2026-08-26, tras el BLOQUE S.**

*Escrita por ARQUITECTO. La del bloque S la puso el CODER porque la instrucción se lo pidió, y
eso fue un error de ARQUITECTO: esta sección es su memoria, y no la escribe el agente cuyo
trabajo sirve para comprobar.*

> **DECISIÓN DE ALCANCE, del PROPIETARIO**: esta campaña **rompe compatibilidad retroactiva a
> propósito** y desemboca en una MAJOR. No se conserva nada por compatibilidad con clones
> existentes. Las rupturas se agrupan en `CHANGELOG.md` bajo un solo encabezado; **el número de
> versión y la etiqueta los pone el PROPIETARIO**.

### Dónde estamos

**E2-a cerrada** salvo 11 rutas declaradas NO comprobadas por falta de datos (T39).

**E2-b, primera mitad LEÍDA** (T114): son **59** rutas `-actions-*`, no 50, y 48 de ellas son dos
plantillas. **La segunda mitad —`$_FILES` y los POST sueltos— sin empezar.**

**Hoja de ruta completa: bloque R del [18](./18-siguientes-ventanas.md).**

### Cerrado en el bloque S

- **`serveModuleStatic()` borrado**; los 24 módulos llaman a `serve()` (T115).
- **Las ocho ramas gemelas** colapsadas, `CAN_ADD_ALL` y `CAN_VIEW_ALL` fuera de los cuatro mappers
  donde estaban muertas —y **conservadas en los otros cuatro, donde sí restringen**— (T117).
- **`$isEdit` sale de la RUTA**, en los 13 controladores, y el desajuste con el cuerpo se rechaza
  con **400** registrado. Con suite que invoca el controlador de verdad (T120).
- **Los tres caminantes entran en PHPStan**: 812 → 815 archivos, 9 errores arreglados, ninguno
  silenciado, baseline sigue en 888 (T119).
- **`gates` corre 18 suites**, no 16: su prefijo dejaba fuera una que existía (T118).
- **Comprobación 18**: ningún `if/else` con las dos ramas iguales, por árbol de sintaxis (T117).
- **LEY 16 y `bin/censo`**: ningún censo reporta cero sin probar antes que el instrumento ve (T116).

### Abierto, sin decidir — LO DE ESTE BLOQUE PRIMERO

- **PARADA 2 · `CacheControllersManager.php:419`**: un `if/else` con las dos ramas iguales, fuera de
  los cuatro módulos de la chuleta. Su origen es otro —una intención a medias, no una copia—, así
  que la decisión no es la misma. **`verify-integrity` está EN ROJO por esto, a propósito.**
  **LEÍDO POR ARQUITECTO, y NO es código muerto: es un defecto de ida y vuelta.** La rama está en
  `jsonUnserialize()`, que se alimenta de `json_decode(file_get_contents($fileConfig), true)` —o
  sea, **array asociativo**—. `criteries` se declara `protected $criteries = null`, el constructor
  le pone un `\ArrayObject`, `setCriteries()` exige un `CacheControllersCriteries` y
  `jsonSerialize()` lo vuelca. Al restaurar de caché se le asigna **el array crudo**: el
  `if ($propertyName == 'criteries')` era exactamente el sitio donde iba la rehidratación, y las
  dos ramas acabaron con el mismo cuerpo. **Después de una restauración, `getCriteries()` devuelve
  un array donde el resto del código espera un objeto.** Falta medir el radio: quién llama a
  `getCriteries()` tras restaurar y qué le hace. **La comprobación 18 encontró un defecto real en
  su primera pasada fuera del conjunto que la motivó.**
- **PARADA 5 · `tests:mautic-batch-send`**: segunda suite huérfana, con prefijo `tests:`, que
  `gates` no alcanza ni ensanchando. Declara red y correo. Hoy no aparece ni como «no se corre».
- **La asimetría de guardas de T114**: `Documents` y `Organizations` exigen propiedad para editar y
  nada para dar de alta. S3 cerró el cruce entre rutas; **la asimetría sigue**.
- **`ImagesRepository::toDelete()`** borra los archivos DENTRO de la transacción y solo hace
  `rollBack()` si la excepción es `PDOException` (T114).
- **Los 6 censos sospechosos de la LEY 16** (T116), a remedir cuando se toque su sección.
- **El 4.0.0 del paquete** (bloque Q): cinco piezas diseñadas, ninguna ejecutada.
- **Listas abiertas de LEY 11**: `shared-toolchain.json`, la parte de `volatile-state.json` que no
  es del slug, `deprecated-functions.json`.
- **La puerta de `HttpClient`**, sin cobertura desde el 2026-08-25.
- **T86**: una columna `json` a NULL se guarda como la cadena `'null'`, desde 2018.
- **PHPStan en 888 y no baja porque nadie se lo ha pedido**: el trinquete es un tope, no un motor.

### En el tintero — declarado por el PROPIETARIO, sin ejecutar

- **El módulo como patrón mecanizable**, y los `staticResolver` universalizables. Escrito en
  `files/dev/roadmap/El módulo como patrón mecanizable.md` con lo medido, las dos trampas y el
  requisito de entrada (E2-b). **Sin decidir**: fase de esta campaña o campaña aparte.
- **El versionado del framework.** El PROPIETARIO no se siente listo: las versiones se atan a
  `last-stable`, y esa rama está **236 commits por detrás** y no tiene nada de la campaña. Lo
  discutirán PROPIETARIO y ARQUITECTO. Los paquetes van por su cuenta y no entran aquí.

### Bloqueado en el PROPIETARIO

- **`composer update piecesphp/database` NO SE EJECUTÓ, y es una PARADA.** El bloque S pedía
  comprobar antes con qué PHP corre composer. Corre con **8.1.34**: `/usr/bin/composer` tiene
  el shebang `#!/usr/bin/php`, y `/usr/bin/php` apunta por `alternatives` a 8.1.34, por debajo
  del piso declarado (`>=8.4.1 <8.6`). `src/composer.json` **no declara `config.platform`**, así
  que composer resolvería contra 8.1.34. Están instalados `php8.4` y `php8.5`. La salida
  natural es invocarlo como `php8.5 /usr/bin/composer …` o fijar `config.platform.php`, pero
  **eso lo decide el PROPIETARIO**: el bloque decía parar. `composer.lock` sigue en **v3.8.0**.
- Si el push se cuelga: `GIT_TERMINAL_PROMPT=0 git push origin master` convierte el cuelgue en un
  error legible, y `git ls-remote origin` separa autenticación de empuje.
