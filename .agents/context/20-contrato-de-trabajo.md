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
    - Un archivo **PREVISTO que no cambió** se **explica antes de commitear**, con una línea que
      diga por qué no cambió. No desaparece en silencio, y **sin esa explicación se para** — ver
      la regla ampliada más abajo, que corrige esta redacción.
    - **De dónde sale**: en el bloque S el conteo dio 3 contra 4 y el CODER commiteó habiendo
      debido parar. Lo previsto que faltaba era `integrity-signatures.json`, que **no cambió
      porque no tenía por qué** —el cambio movía una constante, no una firma—. La explicación
      valía; lo que no vale es no darla.
    - **La cuenta es por repositorio**, no del bloque: son cinco.
- **La guarda del `git add` es `bin/guarda-add`, y EMITE SU LÍNEA.** «guarda ejecutada: 4·4·4».
  **La ausencia de esa línea es un fallo**, no un silencio: significa que la guarda no corrió.
  Escribirla en línea es lo que la mató una vez —una variable con `ñ`, ver LEY 18—. Los guiones
  de guarda llevan `set -e`, `set -u` y `set -o pipefail`, y **la que hace el trabajo es la
  primera**: las otras dos no cazan ese caso, medido.
- **`previsto != cambiado` OBLIGA A EXPLICAR LA DIFERENCIA ANTES DE COMMITEAR.** Si no aparece
  explicación, se para. La redacción anterior lo daba por inocuo, y estaba mal: en el bloque T
  una parada en `10·9·9` cazó los tres artefactos de PHPStan que faltaban por preparar.
- **SE PROVOCA DESDE UN ESTADO GUARDADO.** Provocar es destructivo. Un `git checkout` para
  «restaurar» devuelve lo que hay en HEAD, no lo que tenías: en el bloque T se llevó por delante
  un arreglo sin commitear y hubo que rehacerlo. Se copia antes, se restaura de la copia, y se
  comprueba con `sha1sum` que el archivo volvió idéntico.
- **Y SOBRE UN ARCHIVO PROPIO, nunca sobre uno del proyecto.** Cambiar una `a` por una `b` para
  probar el caso «mismo tamaño, contenido distinto» cayó dentro de `get_allowed_langs()` y tumbó
  el CLI entero. El estado guardado lo salvó; un archivo propio habría evitado el susto. Si no hay
  más remedio que usar uno del proyecto, se restaura **antes** de ejecutar nada que dependa de él.
- **Los archivos que ARQUITECTO deje escritos entran SIEMPRE en el `git add` del bloque en curso**,
  se anuncien o no en la instrucción. Si aparece uno inesperado, se dice en el reporte y se
  commitea igual.
- **Toda orden de git de ARQUITECTO lleva `--no-optional-locks`.** Medido:
  `git --no-optional-locks status --porcelain` no deja `.git/index.lock`. Sin ese flag, el
  puente deja candados huérfanos que él no puede borrar y que rompen el primer `git add` del
  CODER.
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
- **Un archivo NUEVO se pasa por `bin/normaliza-eol` antes de commitearlo.** `bin/anexar` respeta
  los finales del destino, pero un archivo nuevo no tiene destino: nace con los del proceso que lo
  escribió, y git lo voltea la primera vez que lo toca. Para git eso es invisible; **para la foto
  de E3 es una diferencia que hay que ir a investigar**. Ver T140.
- **Una edición en una vista se cierra CONTANDO ETIQUETAS, antes y después.** Anclar por
  SANGRÍA no es anclar: la sangría no dice qué cierra un `</div>`. Dos veces cerró un contenedor
  INTERIOR —`generic-report-view.php` en AB, las dos vistas de perfil en AC— y la primera dejó
  dos pies de tarjeta huérfanos con sus etiquetas RENDIDAS EN PANTALLA: 96/96 → 86/88 → 80/80.
  **Lo vio el PROPIETARIO, no la puerta.** Desde AE lo vigila la comprobación 22 de
  `verify-integrity`, pero la puerta llega al commit y la cuenta llega a la edición. Ver T145.
- **Una provocación que intercambie un archivo PHP espera fuera de `opcache.revalidate_freq`.**
  Aquí vale 2 segundos. Cambiar el archivo y pedir la URL de inmediato mide EL CÓDIGO
  ANTERIOR: en AE2 dio que el código viejo se portaba como el nuevo. El CLI no está afectado
  —`opcache.enable_cli` está en Off—. Ver T145.
- **SE DECLARA, NO SE ADIVINA.** Cuando una puerta tenga que distinguir dos cosas que se
  parecen, se anota en el sitio en vez de inferirlo: `@codigo-comentado` frente a adivinar si
  una línea comentada es código. Una heurística acierta casi siempre, y «casi siempre» en una
  puerta es ruido que acaba ignorándose. Ver T145 y LEY 7.
- **Un instrumento dice cuánto de su UNIVERSO mira, no solo cuánto encontró.** El censo de
  claves imprimía «775 declaradas» mirando el 48% de las que hay. Ver T145 y LEY 15.

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

*La escribe ARQUITECTO, en cada pausa.*

**Ultima actualizacion: 2026-09-01, tras el BLOQUE AR.**

> **ALCANCE**: la MAJOR depende de la campana ENTERA. Reparto del PROPIETARIO: **lo que CORRIGE
> una trampa entra; lo que EXTIENDE una capacidad, no.**

### Donde estamos

**E2 y E3 cerradas.** El idioma queda en `es` y `en`. **E4 abierta.**

**Puertas**: `gates` 23 suites · `verify-integrity` verde, **22 comprobaciones** (178 vistas con 2
desbalances declarados, 53 claves huerfanas, 193 retornos) · PHPStan **749** = baseline · 27 leyes.

### E4 — DECISION DE ARQUITECTO: SE SELECCIONA POR CONSECUENCIA, NO POR PRUEBABILIDAD

Censo (`bin/censo-pruebabilidad`, 137 archivos, 1.179 funciones):

```
PURO 952 (80,7%) · RED/DISCO 92 · PETICION 90 · BASE 45
698 nombres puros -> 113 cubiertos -> 585 sin cubrir -> 230 triviales
                                                     -> 317 publicas con logica
```

**El CODER desconfio de su propia cifra** —80,7 % de pureza en un nucleo no es creible— y la
contrasto con una muestra a mano de 10: 3 merecen prueba, 2 marginales, 5 no. Lo realmente
pruebaunitariable ronda **100-160**, no 317. Y nombro el error sistematico: **el analisis es
DIRECTO, no transitivo** — `initAppConfigs` llama a trece inicializadores impuros y sale «puro».

**Decision**: E4 no es cubrir lo puro. La pregunta no es «se puede probar?» sino **«si esto se
rompiera en silencio, lo notaria alguien?»**. La evidencia es de esta campana: las pruebas que
encontraron algo fueron CONTRATOS —`FileUploadContract` destapo una guarda muerta desde PHP 8,
`http-client-request-build` destapo la `$baseURL` estatica, los viajes de ida y vuelta destaparon
tres—. Ninguna salio de «cubrir lo cubrible».

1. **GUARDAS** — lo que valida, rechaza, autoriza o limita. Si falla ABIERTO no lo nota nadie.
2. **CONTRATOS DE RETORNO NO OBVIO** — donde el llamante supone mal. `jsonEncode` devolviendo un
   `int` al fallar.
3. **VIAJES DE IDA Y VUELTA** — serializar, respaldar, codificar. Tres defectos ya encontrados ahi.

Fuera: las 230 triviales y la masa pura pero aburrida. No por imposibles: porque una prueba ahi no
descubre nada.

### Metodo, tres reglas nuevas de AE

- **Toda provocacion que intercambie un archivo PHP espera fuera de `opcache.revalidate_freq`**
  (2 s aqui). La primera medicion de AE2 dijo que el codigo viejo se portaba como el nuevo: era
  opcache sirviendo el anterior.
- **Se declara, no se adivina.** La puerta de comentarios contaba como PROSA el codigo comentado.
  Una heuristica que separase codigo de prosa acertaria casi siempre, y «casi siempre» en una
  puerta es ruido. Se resolvio con la anotacion `@codigo-comentado`.
- **Un instrumento dice cuanto de su universo mira.** La comprobacion 21 imprimia «775 claves» como
  si fueran todas: son **775 de 1.632**, porque solo juzga la comilla doble. ARQUITECTO decide NO
  ampliar la cota: 277 contra 53 seria casi todo falso positivo, y una puerta ruidosa se acaba
  ignorando. El defecto era la mentira sobre la cobertura, no la cobertura.

### El censo del paso 6 tiene una TERCERA ceguera: COMPORTAMIENTO SIN SUJETO

Censaba identificadores; en AD se le anadio texto visible; y en AG aparece la tercera clase.

`MySpace/Statics/js/profiles-translation-config.js` y los manejadores de `my-profile.js:202` y
`my-organization-profile.js:284` buscan `form.find('button[translate]')`. **Ese boton vivia dentro
del formulario de experiencias previas y el lote 1 de E3 se lo llevo.** Medido: `experienceName`
estaba en 10 archivos antes de `7baa6abf`, hoy en 1 —el JS huerfano—.

**No era una funcion a medio construir: funcionaba, y su sujeto murio.** Es residuo, no regresion.
Y JavaScript **no se queja**: un selector que no casa con nada no lanza, no traza, no enciende
ninguna puerta. La funcion deja de existir y nadie se entera.

> **COMPORTAMIENTO SIN SUJETO** — un manejador enganchado a un selector que ya no casa. Tercera
> mitad del paso 6, junto a identificadores y texto visible.

**El mecanismo NO se pierde**: la traduccion automatica de contenido sigue viva y funcionando en
`Publications`, que pasa a ser su implementacion de referencia — igual que usuarios lo es para
importar y exportar.

### FALLO DE ARQUITECTO, anotado para que no se repita

La reversion del exceso de comentado en los idiomas se acordo con el PROPIETARIO, ARQUITECTO dijo
que estaba capturada, **y no la metio en la instruccion durante tres bloques**. Causa: la tenia en
la conversacion y NO EN DISCO. Lo que se escribe aqui sobrevive; lo que solo se dice se lo lleva el
siguiente reporte. **Lo que ARQUITECTO promete se escribe en §7 en el momento, no en un mensaje.**

### E4 arranco, y la regla de construccion se justifico el primer dia

`UnitTest-AccessGuards`, 33/33. Seis de las ocho guardas de acceso, cada una con RECHAZO,
DISCRIMINANTE y PROVOCACION.

**LA PRIMERA PROVOCACION SALIO VERDE Y ESO ERA EL HALLAZGO.** Al quitar
`empty(self::$supported_algs[$header->alg])` de `decode()`, la suite siguio en 33/33 — no porque
la guarda no importe, sino porque **detras hay otra** (`!in_array($header->alg, $allowed_algs)`) y
el caso tampoco pasaba esa. **Se estaba probando la segunda creyendo probar la primera.** LEY 24
exacta, cazada por la regla «una prueba de guarda tiene que fallar si la guarda se quita».
Aislada metiendo el alg inventado EN `$allowed_algs`, las cuatro provocaciones si caen:
32/33 · 29/30 · 27/30 · 32/33.

**Dos contratos congelados, de la clase «retorno no obvio»**: `BaseToken::check()` sobre un JWT sin
`exp` devuelve **el objeto del payload**, no `true` —truthy pero no `true`: con `!== true` cierra,
con `if (!$x)` abriria—; y `Roles::hasPermissions` con un rol inexistente **lanza**, no devuelve
false.

**El censo de guardas se corrigio una QUINTA vez, y cambio el corte de ARQUITECTO**: `VALID-TRUE`
marcaba cualquier `$allowed = true;`, incluidos los que se ponen DENTRO de una rama —lo contrario
de nacer en true—. 8 -> 6. `Roles::hasPermissions` y `ControllerRoutingTrait::routeName` arrancan
en `false`: **llevaban una etiqueta falsa en el corte**. Las cinco correcciones fueron A LA BAJA
(48 -> 19 -> 11 -> 9, y 8 -> 6). La primera cifra siempre fue la comoda (LEY 22).

**Quedan 169 guardas sin prueba de rechazo.** El lote 2 son las ~22 restantes con FORMA DE FALLO
ABIERTO (28 menos las hechas): es el marcador de mas senal que tenemos.

### `routeName` NO decide solo visibilidad: en 22 modulos ES el control de acceso

**ARQUITECTO se equivoco y lo escribio en un docblock del nucleo.** El docblock de T26 en
`ControllerRoutingTrait` dice: *«una URL escrita a mano se salta el control de acceso: no pasa por
aqui»*. **Es falso en 22 modulos.** El PROPIETARIO lo corrigio de memoria antes de que hubiera
medicion: *«la definitiva es la que se aprovecha de _allowedRoute y allowedRoute»*.

Medido, arbol de trabajo, `git grep` sobre HEAD:

- `PiecesPHP\RoutingUtils\DefaultAccessControlModules` es un middleware de grupo. Su veredicto es
  literalmente `mb_strlen($routeURL) > 0`, donde `$routeURL` es **el retorno de `routeName()`**.
  Si sale vacio, `throw403`.
- Lo instalan **22 controladoras** (News, Organizations, Documents, Publications, MySpace x5,
  Forms x2, API, GeoJSONManager, SystemApprovals, ContentNavigationHub, ReportsManage,
  DataImportExportUtility, Banner, GenericContent, UserSystemFeatures, LocalizationSystem).
- Es decir: **para esos modulos `routeName()` no informa el acceso, LO DECIDE.**

Y `require_login` **no es una puerta**: se guarda en el inventario de rutas y lo consumen
`Roles::hasPermissions()` —para decidir si hace falta pertenecer a un rol— y el manejador de 404
—para el boton «volver»—. `register_route()` no anade ningun middleware de sesion, el grupo
administrativo no lleva ninguno, y los dos unicos `withRedirect` del proyecto estan en
`MySpaceController` y en `RecoveryPasswordController`. **No se encontro el codigo que mandaria a
login a un anonimo que pide `/admin/*`.**

Sobre eso se apoya la rama que preocupa: en `routeName`, `getLoggedFrameworkUser() === null` cae en
`else { $allowed = true; }`. Con el middleware puesto, esa rama es la que decide.

**No se afirma nada mas: se provoca.** `bin/walk-routes:126` imprime «sin PCSPHP_WALK_USER/PASS se
recorre SIN sesion: todo /admin/* dara 302». **Esa linea es una afirmacion sobre el consumidor
escrita por el productor y nadie la ha comprobado nunca** (LEY 19). El bloque AH la mide.

**Los generadores de Menu NO dependen de esa rama.** El PROPIETARIO lo sospechaba; medido, no:
`MenuGroup` y `MenuItem` no consultan permisos, reciben `visible` ya calculado, y quien lo calcula
—`config/menu.php` y los `*Routes::init()`— se autoprotege con `if ($currentUser === null) return
null;` antes de tocar el menu. Con anonimo el menu **no se construye**. La sospecha era razonable y
la medicion la descarta.

### Los 9 selectores sin productor, con su insumo

El PROPIETARIO pidio la lista para revisarlos: *«hay que listar los insumos para revisarlos bien»*.
Universo: repo entero salvo `src/statics/plugins`, `node_modules`, `vendor`, `.min.*`, `.css`,
`.map`. Instrumento validado con canario (LEY 16): `class=` da 216 pre-E3 / 187 hoy.

| selector | quien lo busca | que hace | productor pre-E3 | hoy | veredicto |
|---|---|---|---|---|---|
| `options-order` | `core/js/helpers.js:965` | orden del listado | **2** (`ImagesRepository`) | 0 | **murio con E3, lote 1** |
| `options-order-type` | `core/js/helpers.js:966` | asc/desc | **2** (`ImagesRepository`) | 0 | **murio con E3, lote 1** |
| `see-more` | `MySpace/.../my-space.js:25` | boton de la tarjeta de noticia | 0 | 0 | productor murio en **v6.1.0**, no en E3 |
| `container-colors` | `features/avatars/js/avatar.js:289` | paleta del avatar | 0 | 0 | **nunca** hubo productor PHP; hay SCSS que lo estiliza |
| `container-steps` | `helpers-lib/GenericStepsViewHandler.js:6` | pasos de un asistente | 0 | 0 | **nunca**; la clase solo se referencia a si misma |
| `data-to-step` | `helpers-lib/GenericStepsViewHandler.js:7` | disparador de paso | 0 | 0 | **nunca** |
| `datatable-js` | `core/js/configurations.js:500` | monta DataTables | 0 | 0 | **nunca** en PHP; las tablas se montan por otra via |
| `lang-group` | `core/js/configurations.js:1361` | **traduccion automatica de HTML** | 0 | 0 | **nunca**, y esta DOCUMENTADO en `files/Webflow/Intrucciones.md:39` |
| `element-location-module-data` | `my-profile.js:216`, `my-organization-profile.js:298`, `features/locations/js/locations-config.js:11` | filtro opcional de paises/estados/ciudades | 0 | 0 | **nunca**; sus hermanos SI tienen productor |

Tres cosas que cambian la decision y que **son del PROPIETARIO**:

1. **Solo dos de los nueve son residuo de E3.** Los otros siete ya estaban sin productor antes, y
   cinco no lo tuvieron nunca en toda la historia del repo (`git log -S` sobre `--all`).
2. **`lang-group` es el mecanismo de traduccion de HTML que el PROPIETARIO quiere perfeccionar.**
   `autoTranslateFromLangGroupHTML()` traduce por grupo pidiendo al backend. No tiene productor en
   el repo **porque el productor es HTML importado de Webflow**, y las instrucciones de importacion
   lo exigen. Retirarlo seria retirar la pieza que se quiere mejorar.
3. **`element-location-module-data` es un gancho opcional, exactamente como dijo el PROPIETARIO.**
   `if (dataElementLocation.length > 0)`: ausente, no filtra. Sus hermanos del mismo componente si
   tienen productor —`locations-component-auto-filled-country` 10, `latitude-mapbox-handler` 6,
   `set-satelital-view` 2—. El componente esta vivo; lo que no se usa es su filtro.

> **CUARTA CLASE DEL PASO 6, candidata**: un selector sin productor puede ser (a) residuo,
> (b) contrato para HTML de fuera del repo, o (c) gancho opcional documentado. **El censo no
> distingue las tres, y (b) y (c) NO se retiran.** El paso 6 hoy las trataria igual.

### Los 9 selectores: DECIDIDO por el PROPIETARIO (2026-08-30)

Su criterio, textual: *«en lugar de matarlas hay que documentarlas y arreglar si esta dañado
algo»*. Y aparte: *«el creador de muñequitos avatares si esta muerto hace muchisimos años y sus
assets tambien»*.

| selector | decision | por que |
| :-- | :-- | :-- |
| `lang-group` | **DOCUMENTAR** — contrato publico | `autoTranslateFromLangGroupHTML()` **si esta invocada** (`configurations.js:1649`). Mecanismo vivo y sano; su productor es HTML de Webflow |
| `datatable-js` | **DOCUMENTAR** — gancho vivo | `configDataTables()` **si esta invocada** (`configurations.js:252`). Monta DataTables con la config de idioma del framework sobre cualquier tabla que lleve el atributo |
| `container-steps`, `data-to-step` | **DOCUMENTAR** — utilidad | `GenericStepsViewHandler`: asistente por pasos, completo y sin usar |
| `element-location-module-data` | **DOCUMENTAR** — gancho opcional | En `features/locations/js/locations-config.js:11` es el filtro opcional del componente vivo. **Pero ver abajo: sus otras dos apariciones NO son eso** |
| `container-colors` | **MUERE** con todo el creador de avatares | Decision del PROPIETARIO |
| `options-order`, `options-order-type` | **MUERE** — residuo de E3 lote 1 | Sus 2 productores eran `ImagesRepository` |
| `see-more` | **SIN DECIDIR** | El PROPIETARIO no se pronuncio |

### DOS BLOQUES DAÑADOS, del mismo sujeto que ya se llevo el boton `translate`

Buscando lo dañado aparecio mas residuo del **formulario de experiencias previas** que mato E3
lote 1 — el mismo sujeto de T147:

1. **`experienceForm()`** en `MySpace/Statics/js/my-profile.js:155` y
   `my-organization-profile.js:237`. Llama
   `dataTablesServerProccesingOnCards('.table-to-cards', 20, …)`. **`.table-to-cards` tiene 3
   productores en el arbol y NINGUNO esta en `MySpace/Views/`.** La funcion entera monta una tabla
   que no existe.
2. **`locations2`** dentro de `configurateMap()`, en los dos mismos archivos: un SEGUNDO componente
   de ubicaciones con los atributos `...-country2`, `-state2`, `-city2`, `-point2`, **los cuatro
   con 0 productores**. Era el mapa de cada experiencia previa.

**Y aqui esta la finura que salva el criterio del PROPIETARIO**: `element-location-module-data`
aparece en tres sitios. En `locations-config.js:11` es el gancho opcional vivo que hay que
DOCUMENTAR. En `my-profile.js:216` y `my-organization-profile.js:298` esta DENTRO de `locations2`,
o sea dentro de codigo muerto. **El mismo atributo, dos situaciones opuestas.** Un censo por
atributo no puede distinguirlas: hay que mirar el bloque que lo rodea.

### El lote del CREADOR DE AVATARES — medido, y NO es la foto de perfil

Cuidado con LEY 17: «avatar» aparece en 40 archivos y **casi todos son la FOTO DE PERFIL, que esta
viva y no se toca**. `AvatarModel` (subida de fichero, carpetas, `default-avatar.png`) y
`AvatarController::register()` —ruta `push-avatars`, usada por `topbar.php:678` y
`usuarios/form.php:29`— **SOBREVIVEN**.

Lo que muere es el CREADOR de muñequitos:

| pieza | medida |
| :-- | :-- |
| `src/statics/images/avatares/` | **162 archivos versionados, 888K** (hombre/mujer/all × cabello, ojo, ceja, nariz, boca, silueta, ropa) + un PDF de colores |
| `src/statics/features/avatars/` | 3 archivos versionados, 116K (`avatar.js`, `canvg.min.js`, `style.scss`) |
| `AvatarController::avatar()` + `listFiles()` | el metodo que arma el catalogo, y su ruta `avatars` (GET `/avatars/get`) |
| `config/routes.php` | la ruta `avatars` del grupo `$sistema_avatares` (la otra, `push-avatars`, SE QUEDA) |
| `config/roles.php:50` | la entrada `'avatars'` |
| `UsersController` | **3 sitios** que importan `avatar.js` y `style.css` en formularios donde el componente nunca se dibuja |
| `statics/admin-area/js/users-forms.js:102` | el bloque `.avatar-component` |
| `src/gulpfile.js` | 3 entradas del pipeline de Sass |

**`.avatar-component` tiene 0 productores en PHP/HTML en toda la historia del repo.** El creador se
carga en tres formularios de usuario y no dibuja nada.

### EL BORRADO NO FUE FIABLE — el PROPIETARIO tenia razon, y es peor que el JS

*«Porque siguen apareciendo cosas de experience. El borrado no fue confiable.»* — 2026-08-30.

Red ancha sobre `experience|experiencia`, repo entero salvo `node_modules`, `vendor`, `.min.*`,
`.map`, `plugins`. **14 archivos.** Clasificados:

| archivo | que es |
| :-- | :-- |
| `databases/piecesphp_structure.sql` | **DOS TABLAS ENTERAS** del modulo muerto |
| `.agents/context/11-base-de-datos.md` | las documenta |
| `bin/tools/refactorization/Rector.php:86-89` | excluye **4 archivos que ya no existen** |
| `MySpace/Statics/js/my-profile.js`, `my-organization-profile.js` | `experienceForm()` y `locations2` |
| 4 `.scss` de MySpace | estilos del formulario muerto |
| `CHANGELOG.md`, `PHPStanResult.Summary.baseline.txt`, `.agents/rules/00-core.md`, `18-`, `20-` | historia y prosa: **se quedan** |

**El hallazgo grande — el esquema versionado.** El CHANGELOG de E3 declara «Tablas 35 -> 29».
`databases/piecesphp_structure.sql` tiene **32 `CREATE TABLE`**. Cruzadas las 32 contra el codigo
que las declara, y corregido el instrumento (LEY 22: `locations_cities`, `locations_states`,
`locations_points` y `pcsphp_jobs_queue` salieron como falsos positivos porque el nombre se compone
con prefijo — `const TABLE = 'cities'` sobre `locations_`), quedan **TRES huerfanas reales**:

- `previous_experiences` (E3 lote 1)
- `organization_previous_experiences` (E3 lote 1)
- `interest_research_area` (E3 lote 4)

**El «29» era correcto y estaba bien medido — SOBRE LA BASE DE DATOS VIVA.** El artefacto
versionado nunca se toco, y ninguna puerta lo mira. De ahi nace **LEY 28**.

### `see-more`: DAÑADO, no sin proposito — se corrige

Criterio del PROPIETARIO: *«si see-more no tiene proposito ni utilidad clara, muere; si esta
dañada se corrige»*. Medido, tiene proposito y esta a medias:

- `News/Views/news/public/util/item.php` **sigue emitiendo `data-content-b64`** con el contenido
  completo, y muestra solo `excerpt(120)`.
- `my-space.js:25` **sigue escuchando** `[see-more]` para abrir ese contenido en un modal.
- **Falta el boton.** El contenido completo viaja al navegador en cada tarjeta y nadie puede
  abrirlo.

**Y hay un SEGUNDO defecto en el mismo mecanismo**: el manejador hace
`parsed.find('>.header')` para el titulo del modal, pero la tarjeta tiene `.head`, no `.header`.
Aunque se restaure el boton, el modal saldria sin titulo. Dos defectos, uno tapaba al otro.

### LEY 28 escrita, y la quinta clase del paso 6 con ella

El PROPIETARIO delego la eleccion: *«si es como regla o como ley no se, no soy tan bueno
escogiendo esas cosas»*. **ARQUITECTO decide LEY**, y el criterio es el de siempre: una regla que
ya fallo se convierte en mecanismo (LEY 11), y esta fallo en dos direcciones opuestas el mismo dia
—casi retiro `lang-group`, que esta vivo, y casi documento un `element-location-module-data` que
esta en codigo muerto—. `19-leyes.md` pasa a **28 leyes**.

### ORM: aplazado por el PROPIETARIO, con un motivo nuevo

*«Dejemoslo para despues de la major, quizas en otra mayor.»* Y añade una preocupacion que NO
estaba en la medicion de ARQUITECTO: **«me preocupa como reemplazar el lenguaje de
`ActiveRecordModel`, lo uso mucho»**. Es un punto real y no lo cubria el analisis: la migracion se
midio por `$fields`, clases base y API de `EntityMapper`, **no por el vocabulario de consulta que
el PROPIETARIO tiene en la cabeza**. Anotado en el roadmap como requisito de la guia de uso.

### `files/API` y las plantillas de Postman — MEDIDO, y deja de ser un item vago

Pedido del PROPIETARIO — 2026-08-30: *«al final tambien perfeccionemos la doc api rest publica
(files/API), actualizando, corrigiendo y completando, asi como las plantillas de postman»*.

Medido:

| pieza | estado |
| :-- | :-- |
| `files/API/docs/` | **8 `.md`**: `index` + 7 modulos (Publications, News, Usuarios, Ubicaciones, Traducciones, Reportes, CronJobs) |
| `files/API/docs-dist/` | **build de mkdocs COMMITEADO**: 7,7 MB, 60 archivos versionados, con fuentes, css, js y `search_index.json` |
| `files/API/PiecesPHP.postman_collection.json` | **13 peticiones en 4 carpetas** |
| `APIController` | **8 patrones de ruta**, **14 `actionType` distintos** |

**Cuatro hallazgos concretos, no impresiones:**

1. **`Reportes.md:9` sigue nombrando «convocatorias»** — modulo borrado en E3 lote 3. **LEY 28
   exacta**: un artefacto que sigue nombrando lo muerto.
2. **La ruta `external` esta COMENTADA** en el registro (`APIController.php:1650-1656`) y su
   manejador `externalActions()` **sigue vivo** en la linea 1550, ~70 lineas. O es codigo muerto o
   es un punto de extension deshabilitado a proposito, y hoy nada lo dice.
3. **Postman no tiene paridad**: no hay carpeta `News` —aunque existe `News.md` y la ruta
   `-news-actions`—, y las **siete peticiones de usuarios estan sueltas fuera de toda carpeta**.
   4 carpetas para 7 modulos documentados.
4. **La doc no dice que banderas gobiernan que.** El modulo se enciende por
   `API_MODULE`, `API_CRONJOBS`, `API_TRANSLATION_MODULE`, `API_USERS`, `API_REPORTS`. **En una
   plantilla que se clona, no decir que seccion desaparece con que bandera es una trampa de clon**,
   la misma clase que la de los dos caminos del ORM.

**Y una cuestion que es del PROPIETARIO**: `docs-dist/` es salida GENERADA y esta versionada — 7,7
MB de fuentes y css en el repositorio del framework, que se clona. Puede ser deliberado (que el
clon tenga la doc sin construirla) o inercia. **No lo decide ARQUITECTO.**

**Reparto**: los puntos 1, 2 y 4 CORRIGEN —entran en E6, dentro de la campaña—. Completar lo no
documentado y darle paridad a Postman EXTIENDE, y va con las guias, despues de la MAJOR.

### PERDIDA DE §7, Y SU CAUSA — 2026-08-31

**Se perdieron ~313 lineas que ARQUITECTO habia escrito en §7 DESPUES del commit de AH.** Estaban
en el arbol de trabajo, sin commitear, cuando ARQUITECTO ordeno *«REVIERTE lo hecho en la parte 2»*
sin enumerar archivos. El CODER barrio el arbol entero, que era lo razonable con esa redaccion.

Sobrevive todo lo commiteado en AH: `19-leyes.md` con **LEY 28**, los dos roadmap nuevos y T148.

> **La regla ya existia y ARQUITECTO la incumplio**: *lo que ARQUITECTO deja escrito entra en el
> `git add` del bloque en curso*. Corolario nuevo, que es el que faltaba:
> **UNA ORDEN DE REVERTIR ENUMERA LOS ARCHIVOS. «Revierte lo de la parte 2» no es una orden, es
> una invitacion a barrer.** Y mientras haya trabajo de ARQUITECTO sin commitear, ninguna orden
> puede contener un revert sin lista.

Lo perdido se reescribe abajo, desde las mediciones, que no se han perdido.

### AH CERRADO — el hallazgo no fue el que se fue a buscar

Fallo de ARQUITECTO: la instruccion afirmaba que no existia el codigo que manda a login a un
anonimo. Existe: `src/index.php` **seccion 8, lineas 655-696**, guardado por
`control_access_login` (`config/roles.php:130`, hoy `true`). ARQUITECTO busco **middlewares de
Slim** y llamo a eso «el universo». LEY 15 contra ARQUITECTO. Lo encontro el CODER.

Medicion valida (la discriminante movio: 200 -> 403 en exactamente dos rutas; los 93 x 302 no se
movieron). Universo: 306 rutas, 251 resolubles, **182 GET recorridas**, 124 omitidas con razon.

**LAS DOS CAPAS NO SE SOLAPAN, SE REPARTEN**: lo que declara `require_login` lo para `index.php`;
lo que no, queda entero en manos de `DefaultAccessControlModules`, cuyo unico juez es
`routeName()`. De 17 rutas `/admin/*` con 200 sin sesion, **16 son hojas de estilo**; endpoints
reales, **dos**.

**Cerrar la rama `else` seria el arreglo equivocado**: las dos deben ser publicas
—`get-lang-messages-by-group` es el motor de `lang-group`; `generate-otp` no puede exigir sesion—.

### `generate-otp` — la asimetria

El codigo se envia por correo y **nunca vuelve en la respuesta**: no hay fuga. Pero: **ningun
limite de intentos** (`UsersController::login` SI tiene `MAX_ATTEMPTS = 4`), **enumeracion de
usuarios** (responde `USER_NO_EXISTS`), **escritura sin sesion** (una fila en `login_attempts`) y
es **GET**. **SI tiene consumidor**: `view/usuarios/login.php:63` y `login.js:170` — y apps
headless, dicho por el PROPIETARIO.

DECIDIDO por el PROPIETARIO: cerrojo por usuario e IP + respuesta uniforme; **sin pasar a POST**,
que romperia a los headless. Y **documentado en `files/API/docs/modules/Usuarios.md`**.

### MECANISMO — un filtro es parte del universo

Tras dos fallos seguidos (*«no tolero esos errores»*): el cero de «`generate-otp` sin consumidores»
salio de un `grep -v "UserSystemFeaturesController"`, que es justo la clase que compone la URL; y
«la puerta fallara con `locations-countries-ajax-search`» se dedujo sin comprobar que Locations
estuviera entre los 22 — no lo esta.

> **Toda cifra de ARQUITECTO lleva escrito su universo y sus exclusiones. Un cero obtenido con
> `grep -v` NO ES UN CERO. LEY 16 aplica a ARQUITECTO: ningun cero sin canario positivo y
> negativo. Y una pertenencia no se deduce, se comprueba.**

### SQL CONCATENADO — traza confirmada, sigue abierta

`App/Locations/Controllers/Country.php:458`, `search()`, ruta `locations-countries-ajax-search`
(GET, `requireLogin: false`, `rolesAllowed: []`):
`$critery = "UPPER({$table}.name) LIKE UPPER('{$query}%')"` con `$query` de `getQueryParams()`.
`clean_string()` (`core/Utilities.php:518`) **no escapa comillas** —lo dice su docblock—, y
`ActiveRecord::where(string)` **concatena**. La via parametrizada existe (`where(array)` ->
`WhereSegment` -> marcadores) y la sobrecarga de cadena la evita.

Censo de contraste: 177 `->where(`, 19 con cadena interpolada, **32 archivos** con ambos
ingredientes. **NO son 32 hallazgos**: es donde mirar. Confirmada, UNA.

### UPLOADS — `ProtectFileMiddleware` esta bien hecho; casi nadie esta enchufado

`protect($dir,$validator)` escribe un `.htaccess` que reescribe a `index.php`;
`ServerStatics:418` **se niega a delegar** un archivo protegido a Apache; `ServerStatics:544`
devuelve **403** si el validador dice que no. La via delegada NO puede saltarse la proteccion.

Pero `src/.htaccess:47-49` (`RewriteCond %{REQUEST_FILENAME} !-f`) hace que **un archivo que
existe lo sirva Apache directo**, salvo que su carpeta tenga ese `.htaccess`. Y:
**NUEVE modulos declaran `UPLOAD_DIR`** —documents, categories, document-types, news-categories,
organizations, built-in-banner, helpers-system/generic, system-approval, publications— y **UNO
SOLO esta registrado** (`publications`), con el **validador de ejemplo** y la comprobacion de
sesion **comentada**.

Correccion pendiente: enchufar los ocho + **una puerta que falle cuando un `UPLOAD_DIR` declarado
no este en `protect()`**. El validador de cada modulo lo decide el PROPIETARIO. Y va a la **guia de
creacion de modulos**.

### `require_login`: la puerta no explota nada — 4, no 95

306 rutas; **95** sin `require_login` ni `roles_allowed` en todo el framework, pero **acotado a los
22 modulos: CUATRO** —`get-lang-messages-by-group`, `generate-otp`, `check-totp`,
`two-factor-auth-status`—, las cuatro publicas a proposito. Van a un archivo de excusas declaradas.
**`PiecesPHP\Core\Route` NO SE TOCA**: la comprobacion solo LEE el inventario. Linea roja del
PROPIETARIO.

### EL OBJETIVO, DICHO POR EL PROPIETARIO — la triada en todas, y `_allowedRoute` COMO PLANTILLA

*«Busco estandarizacion, que todos tengan la triada `routeName`, `_allowedRoute`, `allowedRoute`.
Y que en todos se abstraiga lo abstraible en el trait y se mantenga lo que difiere, que es el
estrechamiento si aplica o como plantilla. Ademas de normalizar el estilo de los controladores.»*
Y despues: *«Claro, `_allowedRoute` es plantilla. Procede.»*

**De donde salio el error de ARQUITECTO**: optimizo para que la cadena de la URL no cambiara y
trato la llamada sin argumento como un estorbo, en vez de darle DUEÑO a la ruta `locations`. El
PROPIETARIO lo zanjo en una linea: *«la cadena URL no tiene por que cambiar si el ajuste se hace
bien»*. Es cierto y esta trazado: `Locations::routeName()` con `$baseRouteName = 'locations'`
produce `get_route('locations', [], false)`, exactamente lo que produce hoy `self::routeName()`.

### EL TABLERO, medido (universo: `src/app`, sin exclusiones; canario: el trait no se cuenta a si mismo)

**40 controladoras usan el trait.** (ARQUITECTO dijo 41 dos veces: eran 40.)

| situacion | cuantas |
| :-- | --: |
| No declaran nada — heredan la triada | **20** |
| Declaran `_allowedRoute` (el estrechamiento real) | **11** |
| Sobreescriben `routeName` | **9** |
| Sobreescribe `allowedRoute` | **1** (Terminal) |

Las 9 sobreescrituras y por que existen:

| clase | difiere en | base a declarar |
| :-- | :-- | :-- |
| `Point` `State` `Country` `City` `Region` | compone `prefixParentEntity-prefixEntity`; **y sin argumento devuelven `locations`** | `locations-points` … `locations-regions` |
| `ContactFormsController` | compone `$prefixNameRoutes` | `contact-forms` |
| `PublicAreaController` | idem | `public` |
| `DataImportExportUtilityController` | **ya tiene `$baseRouteName`**; solo difiere en usar `get_config('current_user')` | (ninguna, ya esta) |
| `TerminalController` | **otra firma** en los dos metodos | (ninguna, ya esta) |

### TERMINAL: lo sui generis SI esta justificado, pero es `routeID`, no la triada

| metodo | llamadas medidas |
| :-- | :-- |
| **`routeID()`** | **`src/index.php:875`** — descubrimiento de rutas en CLI. **NO SE TOCA.** |
| `routeName()` | **una**, su propio `allowedRoute()` linea 54 |
| `allowedRoute()` | **cero en todo el repositorio** |

`routeID()` hace exactamente lo que el trait hace en linea, con `$baseRouteName = 'terminal'` ya
declarado: misma cadena. Los otros dos son el cuerpo del trait con una firma mas estrecha, y
`VerifyIntegrityTask:885-886` ya los tiene declarados como «OTRA FIRMA».

### LA DIFERENCIA REAL DE `DataImportExportUtility`, y no es cosmetica

`get_config('current_user')` devuelve el `\stdClass` crudo que escribe `index.php:631`.
`getLoggedFrameworkUser()` construye un `UserDataPackage` a partir de el **y devuelve `null` si el
constructor lanza** (lo captura y lo registra).

**Difieren en un solo caso: cuando el constructor de `UserDataPackage` falla.** Hoy
DataImportExport comprobaria permisos con el stdClass; heredando el trait caeria en «sin usuario,
concede». **Es un cambio de comportamiento en la rama de fallo, y en direccion permisiva.** Se
declara, no se cuela: es el residuo de T26, y uniformar la semantica del trait es justamente el
objetivo.

### AJ CERRADO, Y EL PROPIETARIO CORRIGE EL ALCANCE

**Por que el CODER no arreglo el 500: porque ARQUITECTO se lo prohibio.** El paso 5 decia
literalmente *«dilo con el error exacto del log y NO lo persigas en este bloque»*. Obedecio. La
cautela era de ARQUITECTO y sobraba.

**Aclaracion del PROPIETARIO que cambia el calculo de TODO lo que queda**: *«todo lo que hemos
hecho ya es rompedor. Todo esta en `dev`; nada esta en `last-stable`, que es la que dice lo actual;
nada esta versionado; ergo todo esto quedara en la MAJOR.»* Comprobado: HEAD es `dev`, existen
`last-stable`, `master` y 79 etiquetas hasta `v7.1.0`, y el trabajo de la campaña no esta en
ninguna.

> **Consecuencia**: «esto seria un cambio incompatible» DEJA DE SER UN FRENO. Renombrar clases
> publicas, cambiar la declaracion de una ruta o mover una firma se decide por si mejora el
> framework, no por compatibilidad. La MAJOR es el sitio donde eso se paga.

### DECISION DEL PROPIETARIO SOBRE LAS 15 RUTAS AJAX DE LOCATIONS

*«Las search esta bien que pidan sesion; las que solo listan quizas no, porque sirve para apis
publicas.»* Medido, las 15 son hoy `requireLogin=False, rolesAllowed=[]`:

| forma | cuantas | decision |
| :-- | --: | :-- |
| `-ajax-search` | **5** (countries, states, cities, points, regions) | **piden sesion** |
| `-ajax-all` y `-ajax-all2` | **10** | **siguen publicas**: son API |

**Y no rompe nada**: medido en `src/statics` y en los `Statics/` de los modulos, **CERO consumidores
de `-ajax-search`**. `LocationsAdapter` usa las URL base (`/countries`, `/states`, `/cities`), que
son las de listado. `Region` si tiene `search()` y `all()` (lineas 100 y 90): la ruta no cuelga.

### LAS OCHO CONCATENACIONES QUE QUEDAN — seis en ruta publica

`Country::search` quedo arreglado en AJ. Faltan, **todas medidas contra el inventario**:

| metodo | ruta | hoy |
| :-- | :-- | :-- |
| `Point::search` `State::search` | `-ajax-search` | **el MISMO `LIKE` identico**, linea 467 y 453 |
| `City::search` | `-ajax-search` | misma familia |
| `City::cities` `Country::countries` `State::states` | `-ajax-all2` | `getQueryParam` directo; **siguen publicas por decision del PROPIETARIO** |
| `UsersController::searchDropdown` | admin | `type NOT IN ({$ignoreTypes})` |
| `DataTablesHelper::process` x2 | admin | el `$order` de DataTables, **compartido por 19 controladoras** |

### DECIDIDO POR ARQUITECTO, que era su trabajo y no una pregunta

El PROPIETARIO respondio *«no se que preguntas»* al trinquete: la pregunta estaba mal hecha, el
diseño del instrumento es de ARQUITECTO. **El trinquete entra**: CONFIRMADO esta hoy en 8 y solo
puede bajar. Y **las 90 de REVISAR A MANO no se atacan ampliando el censo** —cruzar de metodo es
donde un instrumento empieza a mentir con seguridad—: se revisan por lotes, ordenadas por si la
ruta es publica.

### DOS CLASES CON EL NOMBRE MAL ESCRITO, y ahora se pueden renombrar

`MissingRequiredParamaterException` (**27 archivos**) y `ParamaterNotExistsException`. *Paramater*.
Son publicas y cada clon las hereda. Con la aclaracion del PROPIETARIO, se renombran.

Y el defecto de fondo: **un parametro obligatorio que falta es error del CLIENTE y hoy sale 500.**
No es de esa ruta: `Parameters::validate()` lanza y nadie lo traduce a 4xx.

### AK — Y OTRO UNIVERSO CORTO DE ARQUITECTO

El CODER encontro que **`having(string)` concatena igual que `where(string)`**, y que el censo
—y el contraste de ARQUITECTO: *177 llamadas, 19 interpoladas, 32 archivos*— **solo miraban
`->where(`**. El punto de partida real era **13, no 8**. Mismo error que el `grep -v`: el universo
era mas estrecho que la afirmacion. Van tres.

`City::search` estaba entre las escondidas: la instruccion de ARQUITECTO la daba por «el mismo
LIKE» y usa `having` porque filtra por un alias del SELECT.

Cerrado en AK: las 5 `-ajax-search` piden sesion (10 de listado siguen publicas), `Point::search`,
`State::search` y `City::search` por marcador, el 400 en el manejador global, y la errata
`Paramater` a **CERO en nuestro codigo** (1 archivo en `src/vendor`, de terceros, declarado y no
tocado). Trinquete en **10**, comprobacion 24.

### LA PARADA DE AK ERA CORRECTA, Y EL HUECO ES DE LA BIBLIOTECA

`WhereItem::toString()` — leido entero:

```php
} elseif ($this->operator == self::IN_OPERATOR || $this->operator == self::NOT_IN_OPERATOR) {
    $str = "{$this->leftMember} {$this->operator} {$this->rightMember}";   // CRUDO
} elseif ($this->operator == self::FIND_IN_SET_OPERATOR) {
    ... str_replace('{SEARCH}','{VALUES}', (string) $this->rightMember, $this->leftMember)  // CRUDO
```

**TRES familias de operador se saltan la via de marcadores: `IN`, `NOT IN` y `FIND_IN_SET`.**
ARQUITECTO estuvo a punto de señalar `CountryMapper::allByRegions()` como «la via segura»: usa
`findInSet` dentro de un `WhereSegment` y **tambien concatena**. Solo es inofensiva porque **no la
llama nadie** (0 consumidores, medido).

> **El framework NO PUEDE HOY expresar una comparacion de lista de forma segura.** Eso no es un
> defecto de uso: es un hueco del paquete `database`, y va a su propio bloque, versionado.

**Mientras tanto, la salida es VALIDAR EL DOMINIO**, que es lo que el propio modulo ya hace con
`state` y `country` via `Validator::isInteger` + `(int)`:

- `ids` (3 rutas publicas): `array_map('intval')` + descartar <= 0 + **si la lista queda vacia, no
  se añade el criterio** —`IN ()` es error de sintaxis—.
- `region` (1 ruta publica): son nombres; validacion estricta por elemento. `intval` no aplica.

El CODER lo propuso y no lo decidio: hizo bien. **Es validacion, no escapado a mano.**

### DOS ERRORES DEL CODER, CONTADOS POR EL MISMO

1. El reemplazo de la errata alcanzo **8 archivos de `src/app/logs/`**, que guardan el nombre que
   la excepcion TENIA al lanzarse. Reescribirlos falsifica el registro. Restaurados con el
   reemplazo inverso; estan en `.gitignore` y nunca iban al commit, **pero el daño era al
   registro**, y lo dijo.
2. Preparo con `git status --porcelain | grep "^ M"`. Un archivo renombrado sale **`RM`**, no ` M`:
   el bucle lo salto y `61e20557` entro con el nombre de clase viejo dentro. Lo cerro `98704439`.
   **No uso `--amend`**: reescribir historia no esta autorizado. La guarda cazo la parte contable
   (40·40·39 -> PARA); lo que no cazo fue el contenido de un archivo ya preparado como renombrado.

> **Regla nueva, de ahi**: preparar por estado con `grep "^ M"` PIERDE los renombrados. Cuando un
> bloque renombre archivos, se prepara por RUTA EXPLICITA, que es lo que ya manda el contrato.

### AL CERRADO — y la leccion es sobre el INSTRUMENTO, no sobre el SQL

**QUITAR LA VALIDACION NO MUEVE EL CENSO.** Provocado: borrando el `array_map('intval')` de
`City::cities`, la suite va 14/14 -> 13/14 -> 14/14 y HTTP va 200 -> 500 -> 200, pero el censo se
queda en **6 · 3** las tres veces.

> **El censo mide el MECANISMO —que hay concatenacion—, NO EL RIESGO.** La validacion de dominio
> le es invisible. Por eso la unica red que se pone roja si alguien la quita es la suite
> comprobandola EN LA FUENTE. Un trinquete que no se mueve cuando desaparece la proteccion no es
> una proteccion: es un inventario.

**El 500 anterior era la prueba y no hizo falta explotar nada**: `?ids[]=abc` daba
`Unknown column 'abc' in 'WHERE'` — MySQL leia el valor como nombre de columna.

Cerrado: los tres `ids` validados (lista vacia = no se añade el criterio, porque `IN ()` no
compila), `InvalidParameterValueException` a 400, `DocumentsController::searchDropdown` por
`HavingSegment`. Trinquete **6 CONFIRMADO + 3 DECLARADO**, comprobacion 24.

**Precision del CODER que ARQUITECTO tenia mal**: los 6 son SITIOS DE LLAMADA en los DOS metodos
que ARQUITECTO conto como «2». El censo cuenta llamadas. Es la misma cosa dicha en dos unidades.

### EL HUECO DEL PAQUETE, CONFIRMADO EN LA FUENTE

`WhereItem.php:36-42` — `NOT_ALIAS_OPERATORS` contiene `IN`, `NOT IN` y `FIND_IN_SET`: **ni
siquiera se les GENERA marcador**, y `toString()` (238-247) imprime en crudo. No es que la
sustitucion falle: es que no existe.

`CountryMapper::allByRegions()` usa `findInSet` y concatena; inofensiva solo por tener **cero
consumidores** (medido por ARQUITECTO y por el CODER, por separado).

**La cota del censo se amplio y ahora se imprime**: `orderBy($s)` -> `"ORDER BY $s"`,
`groupBy($s)` -> `"GROUP BY $s"` y `join($t, $on)` con `$on` string -> `"JOIN t ON ($on)"`. Los
tres concatenan y **NO se miran**: **16 + 4 + 1 = 21 llamadas sin censar**.

### `region`: LA LISTA BLANCA NO SE PUEDE, Y ADEMAS NO SE DEBE

El CODER paro con tres pruebas: `structure.sql:156` dice `region` **text DEFAULT NULL** —sin
ENUM—, no hay `RegionMapper` (los valores salen de un `GROUP BY region`), y el volcado versionado
tiene **dos paises, los dos con `region = NULL`**.

**Decision de ARQUITECTO, y es de diseño, no de falta de datos**: aunque el PROPIETARIO autorizara
la consulta, **una lista blanca sacada de ESTA instalacion seria falsa para todos los clones**.
`piecesphp` es plantilla: sus regiones las pone cada clon. El patron conservador es la respuesta
correcta para una plantilla; la lista blanca, si acaso, es cosa del clon. **Se queda el patron, y
se documenta su efecto**: un nombre con apostrofo o punto queda descartado.

### TERCER ERROR DE ORDEN DEL CODER, contado por el

`9423aa33` entro con `verify-integrity` en ROJO: añadio la seccion 5 de la suite DESPUES de la
ultima pasada y solo volvio a correr la suite y `gates`. Lo cerro `dfdb47fb`.

> **Regla**: `verify-integrity` se corre DESPUES del ULTIMO cambio del bloque, no antes. Si se
> toca un archivo despues de la puerta, la puerta no ha corrido.

Y el metodo de PHPStan, afinado otra vez: comparar por `(archivo, LINEA, mensaje)` invento «3
muertas y 3 nacidas» —un mismo mensaje repetido en dos lineas del mismo archivo que el diccionario
colapsaba—. Por **multiconjunto de `(archivo, mensaje)` ignorando la linea**: 747 contra 747, cero
muertas, cero nacidas, 24 desplazadas.

### AM — Y EL HALLAZGO NO ERA SQL: ERA UN FILTRO QUE SE PISABA

Buscando concatenaciones, el CODER encontro un **defecto funcional real** en
`UsersController::searchDropdown`. Verificado por ARQUITECTO en `HEAD~1`:

```
310:  $model->having("status != " . STATUS_USER_DELETED); //No mostrar usuarios marcados eliminados
330:  $model->having($having);                            // la busqueda
```

**`ActiveRecord::having()` SUSTITUYE, no acumula** (`ActiveRecord.php:485`,
`$this->havingSegment = $having`). Al escribir cualquier texto en el desplegable, la segunda
llamada borraba la primera y **reaparecian los usuarios marcados como eliminados** — con el
comentario justo encima afirmando lo contrario.

> **Un comentario que afirma un filtro no es el filtro.** Es LEY 24 en la naturaleza: la unica
> comprobacion que existia era una frase.

Arreglado moviendo `status != DELETED` al `where()` —es columna real, `fullname` es alias— y
juntando ambos criterios en un solo `WhereSegment`.

### LA DECISION DEL CODER SOBRE LA CONSTANTE: VALIDADA, y ARQUITECTO se equivoco

La instruccion decia `UsersModel::TYPES_USERS`. **Es un mapa de ETIQUETAS** y tiene
`TYPE_USER_GOOGLE_PLAY` (50) **comentado** en la linea 124. El CODER uso
`TYPES_USER_PRIORITY`, el unico que enumera los siete.

**La evidencia es mas fuerte de lo que el CODER dijo**: `config/roles.php:111-112` registra el rol
50 y saca su nombre de `UsersModel::TYPES_USERS[TYPE_USER_GOOGLE_PLAY] ?? null` — **es decir, hay
un rol registrado cuyo nombre resuelve a `null`**, porque la entrada esta comentada. El 50 es real
en `roles.php`, en `TYPES_USER_DONT_REQUIRE_ORGANIZATION` y en `TYPES_WITH_EXTERNAL_LOGIN`.

Y el razonamiento del CODER sobre la DIRECCION del fallo es el correcto: como `ignoreTypes`
EXCLUYE, dejar el 50 fuera de la lista blanca habria hecho que el desplegable mostrara **MAS**
usuarios. **Fallar hacia el lado que no toca.** Igual que `is_numeric` antes de `intval`, porque
`intval('abc')` da 0 y 0 es `TYPE_USER_ROOT`.

**Pendiente nuevo**: el rol 50 con nombre `null` en `roles.php`.

### DOS DEFECTOS DEL INSTRUMENTO, encontrados provocando

1. **`extract()` rompe el mapa de variables.** `DataTablesHelper::process` hace
   `extract($parameters_expected->getValues())` (lineas 199 y 774): crea variables que ningun
   token asigna, y `groupBy($group_string)` salia LIMPIO. Movio 2 llamadas.
2. **El segmento que no salva.** Al meter el `NOT IN` en un `WhereSegment`, el censo lo dio por
   limpio y **la cifra bajo sola**, con el `NOT IN` imprimiendose en crudo igual.
   **Un instrumento que premia envolver el problema es peor que no tenerlo.** La unidad correcta
   no es el argumento ni el metodo: es la CADENA DE VARIABLES.
3. **Declarar por `archivo::metodo` dejaba entrar gratis** una concatenacion NUEVA en un metodo ya
   declarado. Ahora cada entrada declarada lleva su `count`. **Lo vio provocando, no deduciendo**,
   y la provocacion salio VERDE — que es un hallazgo, no un alivio.

### LA COTA, OTRA VEZ MAS ANCHA — y ARQUITECTO la dio corta

ARQUITECTO dijo 21 llamadas; son **24**: `leftJoin` (2) e `innerJoin` (1) reenvian a `join()` y
comparten su rama de string. Van cuatro veces que el universo de ARQUITECTO sale corto.

Trinquete: **CONFIRMADO 6 -> 5** (los 5 son `DataTablesHelper::process`), DECLARADO 3 -> 4,
REVISAR 92 -> 99, DESCARTADO 95 -> 111, llamadas 196 -> 219.

Sin mirar todavia, y **son IDENTIFICADORES, no valores** —piden lista blanca, no marcador—:
`prepare($sql)` 71, `select($campos)` 194, el `$col`/`$cols` de `get()`, `setTable()` 6. El
`LIMIT` NO entra: sale de dos `?int`, **cerrado por tipo**.

### AN — PARADA CORRECTA, Y LA CLASIFICACION A/B DE ARQUITECTO ERA FALSA COMO HECHO

ARQUITECTO escribio: *«`where_string`, `having_string`, `group_string` los pone la CONTROLADORA.
No son de la peticion: son del programador.»* **Cierto como CONTRATO, FALSO COMO HECHO.**
Verificado por ARQUITECTO linea a linea, no leido del reporte:

| sitio | fuente | sumidero |
| :-- | :-- | :-- |
| `Country::countriesDataTables` | `:287` `getQueryParam('region')`, solo `trim` | `:330` `"UPPER(region) = UPPER('{$region}')"` |
| `SystemApprovalsController::dataTables` | `:436` `getQueryParam('referenceAlias')` | `:482` `"{$table}.referenceAlias = '{$referenceAliasFilter}'"` |
| idem | `:438` `getQueryParam('elapsedDays')` | `:488` `"elapsedDays >= {$elapsedDaysFilter}"` **SIN COMILLAS** |
| `PublicationsController::dataTables` | `:1233` `getQueryParam('visibility')`, **cero validacion** | `:1269` `"visibility = {$visibility}"` **SIN COMILLAS** |

Dos no necesitan ni un apostrofo. Y `elapsedDays` se valida como **cadena no vacia** y se usa como
**numero**. **Van cinco veces que el universo de ARQUITECTO sale corto.**

El `region` de `countriesDataTables` es **de los dos**: la instruccion de AL mandaba arreglar
`Country::search` y ARQUITECTO nunca pregunto si `region` aparecia en otro metodo del mismo archivo.

### `$order` YA ESTABA CERRADO — y no tocarlo vale tanto como un arreglo

`DataTablesHelper:1223` hace `$columns_order[$column_index] ?? null` y la 1225 descarta el nulo:
**el indice del visitante NUNCA llega a la cadena**, llega el nombre que puso la controladora. Y
la 1222 colapsa la direccion con un ternario que solo devuelve `'ASC'` o `'DESC'`. Tres copias del
mapeo (496, 992, 1217), las tres identicas. El censo lo marcaba CONFIRMADO **porque su traza no
cruza de metodo — es la cota funcionando, no un fallo.**

### EL HALLAZGO MAS PROFUNDO: EL UNICO ESCAPADO DEL FRAMEWORK DEPENDE DEL SERVIDOR AJENO

`escapeString()` (`AppHelpers.php`) es **`addslashes(stripslashes($str))`**, y `generateHaving`
lo usa para meter el valor de busqueda en la cadena.

- El juego de caracteres es `utf8mb4` en las dos ramas de `config/database.php` (31 y 38): **la
  via multibyte no aplica**. Medido.
- **La aplicacion NO FIJA `sql_mode` NUNCA** —solo aparece en `Export/Plugins/SqlFormat.php`, que
  es otra cosa—. Con `NO_BACKSLASH_ESCAPES` en el servidor, `addslashes` produce `\"` y **la
  comilla sigue cerrando la cadena**.
- Y `%` y `_` pasan crudos: el visitante controla el patron del `LIKE`.

> **Un framework que se clona a destinos que no controlas y cuyo unico escapado depende de un
> ajuste del destino QUE NO DECLARA NI COMPRUEBA, es la definicion de embarcar una trampa.**

### DECISION DE ARQUITECTO: EL INSTRUMENTO PRIMERO, y no por la razon del CODER

El CODER propuso la novena familia por LEY 11. La razon buena es otra y es mas fuerte:
**«cuatro» es un CONTEO A MANO, no un universo medido.** Los encontro leyendo 19 controladoras.
Si el censo no ve esa forma, no sabemos si son cuatro. Arreglar cuatro y construir el instrumento
despues arriesga declarar cerrado lo que no lo esta. **Primero el instrumento, luego la cifra,
luego el arreglo.**

Y el CODER hizo bien en NO tocar `sql-concat-baseline.json`: escribir un 9 que ningun instrumento
produce es exactamente lo que LEY 5 prohibe.

### Anotado: un archivo modificado que el reporte no menciona

`source-docs/project/docs/environments/content/vps/index.md`, +17 lineas de avisos de seguridad
sobre el login de root por SSH. **No es de AN.** El reporte dijo «solo `20-contrato-de-trabajo.md`»
y eran dos. No cambia nada del bloque, pero un reporte del estado del arbol se da COMPLETO.

### AÑ — EL INSTRUMENTO PRIMERO ERA LO CORRECTO, Y EL NUMERO NO ERA CUATRO: ES TRECE

Verificado por ARQUITECTO: `cifras {confirmado: 13, declarado: 4, revisar: 100, descartado: 133}`,
PHPStan 747, commit `ac48f1a9`, 33 sobre `origin/dev`, arbol con solo el `vps/index.md` que la
instruccion mandaba NO tocar —declarado como PENDIENTE en la guarda, 6 + 1 = 7—.

La novena familia no es una llamada: son **claves de un array literal** que `process()` interpola
(`DataTablesHelper.php:277`). Ninguna de las ocho familias de llamada podia verlas. Aporta **8**:
las 4 que el CODER hallo a mano y **4 mas que si validan** —`State:300`, `UsersController:222`,
`DocumentsController:927`, `Organizations:1242`—.

> **El «cuatro» del CODER era RIESGO. El «trece» del censo es MECANISMO.** Los dos son correctos
> sobre cosas distintas, y por eso el instrumento tenia que ir primero.

### EL CANARIO NEGATIVO QUE PIDIO ARQUITECTO ERA INCOHERENTE — y el CODER hizo bien en no parar

La instruccion decia: *«`Organizations:1206` NO debe salir marcado; si sale, el censo esta
condenando validaciones buenas y PARAS»*. Medido:

```
:1191  $status = $request->getQueryParam('status', null);
:1206  $statusToCritery = in_array($status, array_keys(STATUSES)) ? $status : -1;
:1208  $critery = "{$table}.status = {$statusToCritery}";
```

**El valor viene de la peticion y se concatena.** El `in_array` cierra el RIESGO, no el MECANISMO
— y desde T152 este instrumento mide el mecanismo. **ARQUITECTO le pidio al censo que fuera
incoherente con su proposito declarado.** El CODER no paro, y tenia razon.

Y su argumento de fondo es el que hay que conservar: *un censo que aprendiera a reconocer
`in_array(...) ? :` como saneante estaria ADIVINANDO, y el siguiente que no reconociera pasaria
por limpio.* Por eso existen los DECLARADO y por eso la suite comprueba las validaciones EN LA
FUENTE.

**Van seis veces que ARQUITECTO se equivoca sobre el universo o el instrumento.**

### LA COTA, CONTESTADA LEYENDO `process()`

De las quince claves de `$options`, **tres mas** acaban en el SQL, **las tres como
IDENTIFICADORES**: `select_fields` (333 y 336), `columns_order` y `custom_order` (1254). Y en
`custom_order` **la direccion NO pasa por el filtro ASC/DESC** que si se aplica al `$order` de la
peticion; hoy viene de la controladora, y esta escrito en la cota por si deja de venir de ahi.

### UNA COMPROBACION DE LA SUITE ESTABA ESCRITA POR ARCHIVO

Al crecer el universo, `DocumentsController::dataTablesExplorer` entro en CONFIRMADO y la
comprobacion 4 se puso ROJA — pero su sujeto es `searchDropdown`, que sigue bien. **Miraba el
ARCHIVO; pasa a mirar el METODO.** Provocado: rompiendo `searchDropdown`, suite 19 -> 18 y censo
13 -> 14.

### EL TRINQUETE SUBE 5 -> 13, Y ESO NO ES UNA REGRESION

Sube **porque el instrumento aprendio a ver**. Las ocho llevaban ahi desde antes de la campaña.
El CODER lo subio aunque el bloque parase, porque dejarlo en 5 ponia roja la puerta y con ella las
25 suites. **Registrar una medicion no es arreglar nada; escribir un 9 a mano si habria sido
inventar (LEY 5).**

### `archify` — pregunta del PROPIETARIO, 2026-09

Herramienta Node/npm, MIT, que genera diagramas HTML interactivos de arquitectura a partir de una
descripcion, pensada para agentes. **NO es dependencia del framework**: no entra en `composer.json`
ni viaja al clon. Riesgo tecnico para `piecesphp`: **cero**.

Por la regla del PROPIETARIO **EXTIENDE, no corrige**: fuera de la campaña. Encaja en E6 y en las
guias —`16-frontend-arquitectura.md`, el modulo como patron—.

> **Con una condicion que es la de esta campaña entera**: un diagrama generado de una DESCRIPCION
> es arquitectura AFIRMADA, no medida. Dibuja lo que el agente cree. Solo entra si lo que dibuja
> sale de artefactos MEDIDOS —`route-inventory.json`, los censos, la lista de modulos—, no de
> prosa. **Un diagrama bonito y falso es peor que ninguno: parece autoridad.**

### AO CERRADO — 13 -> 7, sin una sola cifra escrita a mano

Verificado por ARQUITECTO: `cifras {confirmado: 7, declarado: 10, revisar: 100, descartado: 133}`,
PHPStan 747, `c3f6cc82`, 34 sobre `origin/dev`, arbol con solo el `vps/index.md` declarado como
PENDIENTE. `regionNameOrNull()` existe (`Country.php:570`) y la usan los DOS sitios (221 y 292).
`PublicationMapper::VISIBILITIES` tiene las cuatro y **ninguna comentada** — el CODER lo comprobo
por lo que paso con `TYPES_USERS` en T153: **la leccion viajo sola**.

**Y corrigio su propia cifra de AN sin que nadie se lo pidiera**: dijo «3 consumidores de
`escapeString`», eran los de UN archivo. **Son 23 llamadas en 13 archivos.** Verificado exacto por
ARQUITECTO.

### TRES DECISIONES DEL CODER QUE SON DISEÑO, NO EJECUCION

1. **EL RECHAZO FALLA CERRADO.** Lo que no case el patron de `region` cae en `''` —que no
   encuentra nada— y **no en `null`, que habria quitado el filtro y ENSANCHADO el resultado**.
   Es «fallar hacia el lado que no toca», aplicado sin que se le dijera. Y conservo el `''`
   legitimo de antes: no cambio comportamiento bueno.
2. **`elapsedDays` son DOS defectos y los nombro aparte**: el de SQL (sin comillas) y **el de
   TIPO** —se validaba con `is_string(...) && mb_strlen(trim(...))` y se usaba como numero—.
   *El segundo habria sobrevivido a cualquier arreglo que solo pensara en el SQL.*
3. **QUEDAN SIETE Y NO CINCO**, y la razon es sutil y correcta: `SystemApprovalsController::dataTables`
   tiene DOS hallazgos en el mismo metodo y `referenceAlias` sigue abierto; **como se declara por
   `archivo::metodo`, el `count` no puede decir a cual de los dos indulta** — lo decidiria el orden
   de aparicion. Regla nueva del registro de declaradas, y la suite la vigila.

Y no hay columna HTTP **por respetar la prohibicion**: las tres rutas piden sesion y mandar una
comilla contra la aplicacion viva es una prueba de explotacion. La evidencia es el fragmento
COMPUESTO. **Cero retrabajos** en este bloque.

### `referenceAlias` — ARQUITECTO NO AUTORIZA EL CAMBIO DE CONTRATO, Y NO POR ALCANCE

La parada esta bien fundada: el registro de handlers se carga con `require_once` dentro de un
constructor privado (`SystemApprovalManager:42`) y nunca se expone; `$BASE_TEXT` es `protected`; y
`UsersApprovalHandler:56` escribe **'Usuario independiente'** dentro de un metodo, fuera de toda
lista. Una lista blanca sacada solo de `$BASE_TEXT` **dejaria fuera ese alias y el filtro
devolveria vacio en silencio**.

**La razon para no autorizar ahora es el defecto adyacente que el CODER encontro**:
`SystemApprovalsMapper::getReferencesAliases()` (311-316) devuelve los alias **pasados por `__()`**
mientras la columna guarda el texto **sin traducir**. En español coinciden; en cualquier otro
idioma **el filtro no casa nada**.

> **El filtro ya esta roto por semantica, no solo por inyeccion.** Cerrar la inyeccion sin cerrar
> eso es pulir una funcion averiada. Los dos van juntos, en un bloque de SystemApprovals.

### `generateHaving` — la salida es la ADITIVA, no tocar el paquete

`havingReplacePrepareValues` solo se rellena desde un `HavingSegment` (`ActiveRecord.php:491` y
`556`): en la via de cadena un marcador **nunca se ataria**. Y el HAVING final mezcla el
`having_string` DEL PROGRAMADOR con lo que devuelve `generateHaving` (linea 268), y un
`HavingSegment` no admite fragmentos en crudo ni grupos parentizados.

**Decision de ARQUITECTO**: `process()` acepta claves NUEVAS `where_segment` / `having_segment`, y
los modulos migran uno a uno. Es **aditivo, no rompe a nadie**, y es el camino que acaba haciendo
innecesarias las claves de cadena. Va con el bloque de `DataTablesHelper`, que es el mismo archivo.

### AP — CONFIRMADO 13 -> 7 -> 2, y la mejor guarda del bloque NO ESTABA PEDIDA

Verificado: `cifras {confirmado: 2, declarado: 14, revisar: 104, descartado: 133}`, PHPStan 747,
`580c2c2e`, 35 sobre `origin/dev`, sin etiqueta, arbol con solo el `vps/index.md` declarado.
Las tres guardas existen con mensaje claro (296-300 y 326-327) y `Country` migrada por
`where_segment` (337).

**LA TERCERA GUARDA LA PUSO EL CODER SIN QUE SE LE PIDIERA, y es la que salva el bloque**:
`having_segment` + BUSQUEDA ACTIVA lanza. El segmento del programador **sustituiria** al HAVING
que genera la busqueda de DataTables, y `HavingSegment` **no sabe agrupar** —`(a OR b) AND c` no
es expresable—. Sin ella, **la primera controladora que migrara habria perdido su filtro de
busqueda EN SILENCIO**.

Y la migracion de `Country` sale **mejor que equivalente**: con marcador, `"Am'erica"` vuelve a
buscarse, y el patron conservador de T152 ya no hace falta ahi. `regionNameOrNull()` sigue vivo
porque `countries()` compara con `IN (...)`, que no admite marcador.

**El ciclo de las declaradas funciona solo**: al migrar `Country`, el trinquete cazo que su
entrada «figura como declarada y ya no casa con ningun hallazgo». *Se declara mientras concatena,
DESAPARECE al migrar.*

### LA PARADA DEL PASO 3: ARQUITECTO ELIGE ADITIVIDAD

El CODER no rompio el empate y hizo bien: cambiar el retorno por defecto de `generateHaving`
alteraria el SQL de **las 18 que no han migrado**, contra la garantia de aditividad que la propia
instruccion exigia.

**Gana la aditividad**, y la razon que lo zanja es la tercera:

1. La garantia de aditividad es lo que protege a 18 controladoras que ARQUITECTO **no ha leido**.
2. `escapeString` se sostiene HOY —`utf8mb4` medido en las dos ramas—; el riesgo es un `sql_mode`
   del destino, y eso **no se cierra tocando `generateHaving`**: se cierra migrando, que es el
   camino ya elegido.
3. **Cambiar el defecto seria afirmar que ninguna de las 18 depende de la forma actual. Eso es
   LEY 19** — afirmar sobre el consumidor desde el productor—, y esta campaña ha castigado esa
   afirmacion **seis veces**, todas contra ARQUITECTO.

> `escapeString` muere POR MIGRACION, no por cambio de defecto. Cada controladora que pasa a
> `where_segment` / `having_segment` se lo lleva consigo.

### GOTCHA DE INSTRUMENTAL, del CODER y aplicable a los dos

`open(p, encoding='utf-8')` en Python usa **saltos de linea universales**: un `\r\n` llega como
`\n`, detectar el final SIEMPRE da falso, y al reescribir sale LF. Por eso `normaliza-eol`
encontraba algo despues de cada edicion suya. **ARQUITECTO no cae en esto porque lee con
`open(...,'rb')` y decodifica a mano** — pero queda escrito para los dos.

### AQ — Y EL MURO TIENE NOMBRE: `HavingSegment` NO AGRUPA Y `WhereSegment` SI

Verificado: `cifras {confirmado: 2, declarado: 11, revisar: 104, descartado: 133}`, PHPStan 747,
`66e9b645`, 36 sobre `origin/dev`.

**ARQUITECTO fue a medir el muro antes de autorizar un `HavingGroup` propio, y el muro no es el
que pareciamos tener:**

| | agrupa | estructura |
| :-- | :--: | :-- |
| `WhereSegment` (143) | **SI** | `WhereItemGroup[]`, con `addGroup()`, y hasta envuelve un critery suelto en un grupo |
| `HavingSegment` (114) | **NO** | `HavingItem[]` PLANO, solo `addCritery(HavingItem)` |
| `WhereItemGroup` (149) | — | existe |
| **`HavingItem`** | — | **`class HavingItem extends WhereItem {}` — QUINCE LINEAS, CUERPO VACIO** |

**`(a OR b) AND c` SI es expresable en WHERE. En HAVING no, y la unica diferencia es que
`HavingSegment` es de UN nivel y `WhereSegment` de DOS.**

> **ARQUITECTO NO AUTORIZA un `HavingGroup` dentro del framework.** Seria reimplementar
> `WhereItemGroup` fuera del paquete que ya lo tiene: **la trampa de los dos caminos**, la misma
> que se diagnostico con `EntityMapper` contra `ORM`. El arreglo es dar a `HavingSegment` la
> estructura de dos niveles que `WhereSegment` ya tiene, **con la implementacion de referencia al
> lado**.

**Y eso RECOLOCA LA COLA**: el bloque del paquete `database` deja de ser una tarea de cierre y pasa
a ser **lo que desbloquea tres cosas a la vez** — las dos controladoras bloqueadas
(`Organizations` y `Publications`), `generateHaving`, y con el la dependencia de `escapeString` en
el helper.

### LO QUE EL CODER MIDIO ANTES DE FIARSE

- **El cubo A se lo dio el censo, no el ojo**: son exactamente los DECLARADO de la novena familia.
- **Colision de alias**: `dataTablesRequestUsers` compara `status` y `type` DOS VECES cada una; si
  dos alias colisionaran se perderia un valor **sin ruido**. Comprobado: 5 criterios, 5 alias
  distintos, y la suite lo fija.
- **La validacion se queda en `State` y se retiro en `Country`, y NO es incoherencia**: en `State`
  el `isInteger` es lo que convierte lo raro en `-1`, que no casa nada; sin el, MySQL coaccionaria
  la cadena y daria **el mismo resultado POR CASUALIDAD, no por diseño**. En `Country` el patron
  descartaba **nombres legitimos**. No es la misma situacion.
- **`escapeString` no se movio y NO SE MOVERA migrando**: 21 de las 23 llamadas viven en los
  `search()` de los mappers y en JSON, que esto no toca; las 2 del helper estan en
  `generateHaving`, cuyo defecto **no cambia por decision de ARQUITECTO**. Lo dijo para impedir la
  inferencia equivocada, que es LEY 19 aplicada contra ARQUITECTO.

### LAS DOS BLOQUEADAS, Y POR QUE NO FORZARLAS ERA LO CORRECTO

`OrganizationsController::dataTables` y `PublicationsController::dataTables` meten su valor de
peticion en `having_string` (`:1208` y `:1275`) **y tienen columnas buscables reales**. En cuanto
alguien escriba en el buscador, `generateHaving` produce contenido y **la guarda de AP las para**.
Migrarlas habria metido **un fallo latente que solo aparece cuando un usuario escribe**.

### DOS RESIDUOS EN LA ORBITA DEL HELPER — DECIDIDO: MUEREN

1. **`FIELD_SAMPLE_FILTER`** en `DocumentsController::dataTablesExplorer`: **no es una columna**
   —cero ocurrencias en `DocumentsMapper`—, es plantilla heredada del modulo modelo, y la ruta
   `documents-datatables-explorer` existe y esta autenticada. Quien pase `?FIELD_SAMPLE_FILTER=1`
   se lleva un `Unknown column`. **Se borra**: cambiar ese 500 por un 200 es un arreglo, no una
   regresion. El CODER hizo bien en migrarlo tal cual y no decidirlo el.
2. **`datatables_proccessing()`** (`config/functions.php:72`): **cero consumidores**. Muere con el.

**B y C no migran**, confirmado: en B el valor sale de la sesion del propio usuario; en C tres
tienen el array de criterios **vacio siempre**.

### AR — TRES PIEZAS MUERTAS QUE SE TAPABAN ENTRE SI

Verificado: `cifras {confirmado: 2, declarado: 11, revisar: 104, descartado: 133}`, PHPStan 747,
`afbc0692`, 37 sobre `origin/dev`.

**`FIELD_SAMPLE_FILTER` no era una pieza, eran tres**, y encajadas de forma que se cubrian:

| pieza | estado |
| :-- | :-- |
| el criterio PHP | columna que NO existe en `DocumentsMapper` |
| el JS que lo dispara | manda **`FIELD_SAMPLE_FILTER_LOAD`**, CON SUFIJO |
| el desplegable | **no existe**: `explorer.php` no tiene ni un `dropdown` |

**Los dos nombres nunca casaron.** Por eso nadie se topo jamas con el `Unknown column` desde la
interfaz: el parametro que el JS envia no lo lee nadie, y el que el PHP lee no lo envia nadie.

**ARQUITECTO comprobo el «cero ocurrencias» y encontro UNA**: esta en
`src/app/logs/olds/error.log...json`, que es **un registro de lo que paso**, no codigo — y
reescribirlo seria justo el error que el CODER cometio y conto en AK. **Su cero era correcto sobre
el codigo; el grep de ARQUITECTO era mas ancho que su afirmacion.** Por una vez, al reves.

### EL HTTP NO DIO LO ESPERADO Y ESO FUE EL HALLAZGO

Esperabamos 500 -> 200. Salio 500 -> 500. En vez de maquillarlo, el CODER **aislo el entorno**
pidiendo la ruta hermana `documents-admin-datatables` con los mismos parametros: **200**. Base,
tabla y entorno bien.

> **`dataTablesExplorer` tiene un SEGUNDO defecto, propio y anterior, que este bloque no toca.**
> Para nombrarlo hace falta el `limitGeneratedSQL` que la excepcion ya lleva dentro y que el
> manejador no imprime.

### DECISION DE ARQUITECTO: SE REGISTRA, NO SE IMPRIME

El CODER propuso imprimir el `limitGeneratedSQL` en el manejador, «solo en local». **No.**

> Imprimir SQL en una respuesta de error crea una superficie de fuga cuya unica cerradura es una
> comprobacion de «local» **que cada clon hereda**. Diagnosticar un defecto viejo creando una fuga
> nueva es un mal cambio. **Va al REGISTRO** —`log_exception`, que ya existe, y los logs estan en
> `.gitignore`—: mismo diagnostico, superficie cero.

### EL GEMELO, Y LA DISCIPLINA DE NO DECIDIRLO

La red ancha destapo `datatables_proccessing_with_options()` (`config/functions.php:42`), **tambien
con cero consumidores**. El CODER **no lo borro**: no estaba en la instruccion.
*Lo que la red destapa se reporta, no se decide.* **Decidido ahora: muere, con la misma red ancha
delante.**

### EL PAQUETE `database`: MEDIDO, AUTORIZADO Y ADITIVO

`WhereItemGroup::addCritery(WhereItem $critery)` esta tipado a `WhereItem`, y
`HavingItem extends WhereItem {}` tiene el cuerpo vacio: **un `HavingItem` ES un `WhereItem` y
pasa el tipo sin cambiar una linea.** Nada lo impide.

Lo que le falta a `HavingSegment` (114) frente a `WhereSegment` (143): `$groups`,
`addGroup(WhereItemGroup)`, `addCriteria(array)`, y las versiones por grupos de `addCritery`,
`countCriteria`, `getReplacementValues` y `toString`.

**Y NO es copiar `WhereSegment` encima**, aviso del CODER que hay que respetar: `HavingSegment`
emite hoy UNA envoltura plana con el operador dentro; adoptar su `addCritery` convertiria un `OR`
existente en `(a OR) (b OR) (c)`, que es basura. **La forma segura es ADITIVA**: `$groups` y
`addGroup()` nuevos, y `toString()` emite grupos **solo si se añadio alguno**.

Coste medido: `WhereSegment` 38 usos en `src/app` y 5 en el paquete; `HavingSegment` 23 en 7
archivos y 1 en el paquete; **`WhereItemGroup` CERO en `src/app`** — nada existente puede romperse
por añadirlo.

**Estado del paquete**: limpio, `v4.0.0` en HEAD, 13 suites, PHPStan 21. Aditivo y sin romper nada
= **MINOR**, con su CHANGELOG y su etiqueta. **La version y la etiqueta las decide el PROPIETARIO;
NADA SE EMPUJA, tampoco aqui.**

Desbloquea: las dos controladoras, `generateHaving` con segmento aun habiendo `having_string`, la
retirada de la via de cadena con sus 5 declarados, y **2 de las 23 `escapeString`** —las del
helper—. Las otras 21 viven en los `search()` de los mappers y en JSON: **no caen por esto**.

### Abierto, sin decidir

- **`profiles-translation-config.js` sigue nombrando `'fr'`.** Sus campos no existen en ninguna
  vista: la funcion ya era un no-op. Del PROPIETARIO.
- **`app/lang/dynamic-translations/fr/global.php` sigue versionado**, con 8 de las 53 huerfanas, y
  hay carpetas `de/`, `it/`, `pt/` con su `.keep`.
- **Los retornos ignorados**: 193 sin declarar · **`Locations`**: cuatro controladoras deciden la
  operacion desde el CUERPO.
- **La arquitectura del front NO ESTA DOCUMENTADA** -> `16-frontend-arquitectura.md`, en E6.
  126 JS, 82 SCSS, 68 CSS, doce adaptadores y un `Proxy` en `configurations.js:113`.
- **`files/API/`** · **los guiones de permisos** · **`phpstan-strict-rules`** · **las tres listas de
  LEY 11** · **T86** · **la asimetria de T114** · **el despliegue** -> E6.
- **`DataImportExportUtility`** -> E5, CONSOLIDACION y ARQUETIPO. Dos pruebas de aceptacion:
  **supervivencia** —aguanta en un clon con lo opcional apagado?— y **utilidad** —puede alguien
  construir uno nuevo sin leerse el nucleo?—. Ancla en usuarios, la tabla sine qua non.
- **Los 81 bloques del registro**: el borrado espera al cierre de E6.

### Fuera de la campana - roadmap

Silencios de Sass · el modulo como patron mecanizable · el skill de aterrizaje · una cache de
verdad · la distribucion sin ruido · el versionado · las cuatro revisiones de seguridad y
operacion · el GUI de traducciones · **las guias de estilo por lenguaje, para humanos y para
agentes** (`files/dev/roadmap/Guias de estilo por lenguaje.md`, pedidas el 2026-08-30: EXTIENDEN,
asi que van despues de la MAJOR; lo descriptivo se queda en `16-frontend-arquitectura.md`, E6)
· **y en la ventana de i18n: `es.js`/`en.js` pasan a ser
ARTEFACTOS GENERADOS desde PHP.** El `Proxy` se queda; lo que cambia es que la base deja de
mantenerse a mano, y entonces el conjunto de idiomas no puede divergir.

**Nota del PROPIETARIO**: al cerrar la campana, recordarle **«Perfeccionar geovisor»**.
