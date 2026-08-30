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

**Ultima actualizacion: 2026-08-29, tras el BLOQUE AE.**

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
operacion · el GUI de traducciones · **y en la ventana de i18n: `es.js`/`en.js` pasan a ser
ARTEFACTOS GENERADOS desde PHP.** El `Proxy` se queda; lo que cambia es que la base deja de
mantenerse a mano, y entonces el conjunto de idiomas no puede divergir.

**Nota del PROPIETARIO**: al cerrar la campana, recordarle **«Perfeccionar geovisor»**.
