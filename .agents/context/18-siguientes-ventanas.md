# 18 — Siguientes ventanas

> ## TRASPASO — 2026-08-21
>
> Escrito para que alguien que llega en frío ejecute sin preguntar. Lo de aquí no está en
> el código y se pierde si no se lee.

## DÓNDE VA CADA COSA — la regla, y se aplica DESDE AHORA

`.agents/context/` **no es la historia de esta campaña**: es documentación paralela para agentes,
y **viaja con el framework**. Tiene dos propósitos que conviene no mezclar — decir qué falta, y
decir cómo funciona esto. Muchos de sus archivos deben **desaparecer** cuando queden satisfechos.

**Toda entrada nueva nace en su sitio. Tres destinos:**

| Si es… | Va a… | Y ahí |
| :-- | :-- | :-- |
| **LEY DURABLE** — cómo se trabaja, una convención, cómo funciona el framework | El documento numerado que corresponda (`06`, `10`, `11`, `12`…) | **se queda**: no caduca |
| **PENDIENTE** — una ventana, un peldaño de la escalera, backlog | **Este documento** | **mengua** conforme se cierra |
| **LO HECHO** — una medición, un hallazgo, un error y su corrección | [`historico/`](./historico/) | queda como explicación de por qué el proyecto es como es |

**Escrita aquí a propósito**, al principio del documento que más crece, para que se aplique sola:
antes de añadir una sección nueva, la pregunta es **a cuál de los tres pertenece**.

> **Lo que NO se hace todavía**: extraer de este documento las leyes que ya están escritas dentro
> —T0, T10, T17, T20, T21, LEY 8 a LEY 11— y llevarlas a documentos permanentes. Es la pieza
> difícil, porque hay que separar la ley de la anécdota que la funda sin perder ninguna de las
> dos. **Va en E6.**

## T0 · CRITERIO DE CIERRE DE LA FASE — decidido por el propietario

**El framework es una PLANTILLA QUE SE CLONA.** Hay muchos despliegues, cada uno congelado
en su versión. Nadie se rompe hoy con lo que tocamos aquí, pero **cada despliegue es un
consumidor futuro**, así que la regla no es «no rompas producción»: es

> **NO EMBARQUES UNA TRAMPA.**

Más exigente, porque el daño llega tarde y sin nadie delante para diagnosticarlo.

### La fase no cierra con un número. Cierra con esto:

1. **Cero errores que señalen un defecto real.**
2. **Todo lo demás arreglado, o suprimido CON RAZÓN ESCRITA** y, si la supresión es
   temporal, **con la condición que la retira**.
   > **PRECISIÓN, y viene de intentar aplicar esta regla a los 85 del grupo B: EL CIERRE POR
   > SUPRESIÓN DOCUMENTADA EXIGE QUE LA RAZÓN EXISTA.** No que esté escrita: que exista.
   >
   > Cuando la respuesta **no está en el código sino en datos que no tenemos**, no se suprime:
   > **se aplaza con el motivo escrito**. Escribir «no se puede saber si es un defecto» ochenta
   > y cinco veces no son ochenta y cinco supresiones justificadas — son ochenta y cinco
   > justificaciones falsas, y eso es peor que dejar el error a la vista, porque un error
   > visible al menos se ve.
   >
   > La diferencia práctica entre suprimir y aplazar: **una supresión se cierra y no se vuelve
   > a mirar; un aplazamiento lleva escrito QUÉ lo desbloquea.** Los 85 llevan «E2, cuando el
   > ciclo CRUD haya poblado la base», que es una condición comprobable y con fecha.
3. **Los `.neon` dejan de ser cajón de sastre y pasan a ser registro documentado.** La
   auditoría de los 67 `ignoreErrors` —48 vivos silenciando 3.083 errores, 19 muertos— es
   el formato de referencia. **Los cinco repositorios al mismo estándar.**
4. **TRINQUETE: el baseline solo baja.** Un error nuevo se arregla o se justifica por
   escrito. El que no cumpla ninguna de las dos cosas, no entra.
6. **UNA FASE NO SE CIERRA POR CONSENSO NI POR CHECKLIST.** Se cierra cuando existe una
   comprobación que falla si deja de ser cierta, **Y ESA COMPROBACIÓN SE HA VISTO FALLAR.**

   **Escrita porque la migración a 8.5 se dio por terminada y no lo estaba.** Había DOS
   detectores —PHPStan y la promoción de `E_DEPRECATED` a excepción— y **no se probó
   ninguno**. Los dos fallaban a la vez, cada uno por su motivo, y quedaron nueve llamadas
   deprecadas en el árbol; una tumbaba la generación de imágenes con un 400.

   Plantar un `imagedestroy()` el primer día y correr los dos habría costado **cinco
   minutos** y habría enseñado que el estático no lo veía con el rango de versiones puesto,
   y que el de ejecución solo dispara si alguien pisa la línea.

5. **TODA CIFRA QUE SOBREVIVE A LA SESIÓN LLEVA SU MÉTODO ESCRITO.** No es un paralelismo
   con la regla 2: **es lo que hace funcionar el trinquete.** Comparar dos baselines solo
   significa algo si se midieron igual, y ya falló una vez — cuando el pipeline pasó de
   tabla a JSON, la pregunta «¿es comparable?» no tuvo respuesta hasta que alguien fue a
   averiguarla. Un trinquete que compara peras con manzanas no impide nada.

   Con dos límites, para que no se vuelva burocracia:

   - **Solo las cifras que SOBREVIVEN a la sesión**: las que entran en un documento, en un
     baseline o en el `CHANGELOG`. Un número dicho en un reporte se explica por su contexto
     y muere con él; ese no lleva método.
   - **El método debe ser REPRODUCIBLE, NO DESCRIPTIVO**: el comando o la herramienta
     nombrada, no una frase. «Medido sobre la salida de PHPStan» no sirve; «`bin/phpstan`,
     contando instancias en `PHPStanResult.json`» sí.

7. **UN COMENTARIO QUE FRENA ALGO CABE EN UNA LÍNEA. SI NECESITA UN PÁRRAFO, EL PÁRRAFO NO ES
   EL COMENTARIO.** La regla anterior —«¿impide romper algo?»— era correcta y **no frenaba la
   deriva**, porque no decía nada del TAMAÑO: un relato de doce líneas siempre encuentra una
   frase suya que sí impide romper algo, y con esa se justifica entero.

   El reparto, y no hay cuarto sitio:

   | Dónde | Qué va | Tamaño |
   | :-- | :-- | :-- |
   | **El código** | la guarda: qué no se puede hacer aquí y qué pasa si se hace | **una línea** |
   | **El `CHANGELOG`** | el relato: qué se cambió, por qué, qué se midió | lo que haga falta |
   | **La instrucción / el documento** | el razonamiento que llevó a la decisión | lo que haga falta |

   Ejemplo del reparto bien hecho, sobre el 2FA:

   ```php
   //No pongas twoAuthFactor en ENABLED aquí: preparar no es activar. Lo activa confirm2FA().
   ```

   Eso frena el borrado. **Todo lo demás —la ventana de bloqueo, el código de seguridad vacío,
   el secreto que se regeneraba— es historia, y la historia va al `CHANGELOG` y a T35.**

   **Y hay un motivo que no es estético: un comentario largo ENVEJECE MAL.** El del `chr()`
   explica un arreglo de compatibilidad; el día que ese arreglo sea irrelevante, el comentario
   **miente**, y nadie va a ir a buscarlo. Un documento fechado puede envejecer sin mentir
   porque se lee como historia; un comentario en el código se lee como el presente.

   > **Causa reconocida por el propietario, y anotarla es parte de la regla:** escribir el
   > razonamiento en MAYÚSCULAS en la instrucción hace que acabe de encabezado en un docblock.
   > **EL RAZONAMIENTO VA EN LA INSTRUCCIÓN, LA LÍNEA VA EN EL CÓDIGO, EL RELATO VA EN EL
   > CHANGELOG.**

   La regla se vigila con una puerta, no a ojo: ver T38.
### LEY 8 — UNA DECISIÓN QUE NO VIVE EN UN ARCHIVO NO SE PROPAGA

**Se propagó lo escrito en un archivo compartido; lo decidido sin escribirlo en ninguno llegó a
exactamente un repositorio.** No es una observación: explica media campaña.

| Decisión | ¿Dónde vivía? | Qué costó |
| :-- | :-- | :-- |
| **La CLI es una caja de herramientas, no un desplegador obligatorio** | En la cabeza de nadie | **Tres diagnósticos míos equivocados**: propuse mover a la CLI cosas que el framework tiene que hacer solo al arrancar |
| **Las columnas van en `camelCase`** | En la costumbre | Diez columnas con guion bajo, y un censo a mano para saber cuáles eran excepción |
| **Webflow se conserva** | En la memoria del propietario | Propuesto para borrar |
| **`phpVersion` como rango** | En un comentario que decía **lo contrario** de lo que hacía la opción | **Cegó el análisis estático a toda deprecación de 8.5 durante la campaña entera** |
| **`PHPStanResult.json` se versiona** | En una decisión tomada aquí y no escrita | Cuatro paquetes divergentes, aprobados en verde por una puerta (T42) |
| **`bin/cli` honra `PCSPHP_PHP_BIN`** | Arreglado aquí, no escrito en ningún sitio común | **El defecto exacto que cegó la campaña, todavía vivo en `database`** después de haberlo arreglado |
| **`bin/phpstan` es ejecutable** | En el bit de un archivo | «Permiso denegado» en dos paquetes, que se lee como problema de la máquina |

**Los dos últimos son el mismo error en su forma más pura: arreglamos el síntoma donde nos
dolió y no lo escribimos en ningún sitio que alcanzara a los otros cuatro.**

> **CONSECUENCIA PRÁCTICA, y es una obligación, no un consejo: CUANDO TOMEMOS UNA DECISIÓN QUE
> APLICA A MÁS DE UN REPOSITORIO, EL MISMO COMMIT LA ESCRIBE EN UN ARCHIVO QUE LOS CINCO LEEN.**
>
> Hoy esos archivos son `files/dev/shared-toolchain.json` —para el instrumental— y este
> documento, para lo demás. **Si una decisión no cabe en ninguno, no es una decisión: es una
> intención**, y las intenciones no sobreviven a un `git clone`.

Y el corolario que la hace verificable: **una decisión escrita en un archivo compartido puede
tener una puerta detrás**. Las siete de la tabla que no la tenían, no la tuvieron.

### LEY 9 — UNA OPERACIÓN DOCUMENTADA EN UNA SOLA DIRECCIÓN TIENE UN INVERSO SIN ESTRENAR

**No está roto: está SIN ESTRENAR, que es peor, porque nadie sabe que lo está.** Un inverso roto
se descubre al usarlo; un inverso sin estrenar se descubre **el día que hace falta**, que por
definición es el peor día.

El caso que la funda: **ninguna de las dos guías que documentaban `db-backup` decía cómo
restaurar**. No es un descuido de redacción — es la razón de que el defecto sobreviviera. Nadie
escribió el procedimiento de vuelta, así que nadie lo ejecutó, así que nadie descubrió que
dejaba a todos fuera.

> **La regla operativa: documentar una operación incluye documentar su inverso, y el par se
> prueba entero, no cada mitad por su lado.** «Exporta bien» y «restaura bien» son dos
> afirmaciones que pueden ser las dos ciertas mientras el viaje completo no funciona.

Y su forma más traicionera, porque parece cobertura: **una prueba que verifica el VALOR
DEVUELTO del inverso y no su EFECTO**. La suite del 2FA comprueba que `toggle2FA(false)`
devuelve `true`; no comprueba que la cuenta deje de pedir código. La ida se verifica, la vuelta
se supone.

**La auditoría completa de inversos del framework está en T49**: de once operaciones con
inverso real, **tres tienen el viaje probado y ocho no**.

> **Y una forma más barata de la misma ley, que costó dos años**: `bin/rector` está documentado
> en `CLAUDE.md` como una de las cuatro herramientas del proyecto y **devolvía «Permiso denegado»,
> salida 126**. Nadie lo había corrido. Una herramienta documentada que nadie ejecuta es un
> inverso sin estrenar con otro nombre: **lo documentado no es lo probado**, y la distancia entre
> las dos cosas solo la mide ejecutarlo. La encontró la comprobación 9 de `verify-integrity` en
> su primera corrida.

### LEY 10 — `password` ES OPACO PARA TODA HERRAMIENTA QUE MUEVA FILAS DE USUARIO

**No se cifra, no se vuelve a hashear, no se transforma: SE COPIA.**

La columna ya contiene el resultado de una transformación irreversible. Aplicarle una segunda
—cifrarla, volver a hashearla— rompe el viaje de vuelta **sin dar ningún error**: la escritura
funciona, el archivo se genera, y `password_verify()` devuelve `false` el día que alguien
intenta entrar.

**El caso que la funda: `db-backup`** (T45). Cifraba la columna al exportar y nada la
descifraba al restaurar.

> **Y el conteo honesto, porque importa: fue UNA herramienta, no dos.** Sospeché que
> exportar/importar usuarios tenía el mismo defecto y **al medirlo resultó falso**: el
> exportador no emite la columna `password` en absoluto (T50 · 3). La regla se escribe igual,
> porque es exactamente la que habría evitado el caso real — pero se escribe con un caso, no
> con dos.

**Censo hecho**: revisados todos los sitios que tocan la columna fuera del login, **ninguno la
transforma**. Cada uno hace una de estas tres cosas, que son las tres correctas:

| Qué hace | Dónde |
| :-- | :-- |
| La **hashea** al fijar una contraseña nueva | alta de usuario, recuperación, importador |
| La **verifica** al entrar | login, API |
| La **`unset()`** antes de exponer el usuario | controladores públicos, API |

**Cualquier cuarta cosa es sospechosa por defecto.**

### LEY 11 — CUANDO UNA REGLA FALLA TRES VECES, EL ARREGLO NO ES REPETIRLA: ES CONVERTIRLA EN MECANISMO

**Es la contraparte de la LEY 8.** Aquella dice que una decisión que no vive en un archivo no se
propaga. Esta dice lo que faltaba: **una que sí vive en un archivo tampoco se aplica sola.**

Con dos veces es descuido. Con tres, el problema ya no es la memoria: **es que sigue siendo
posible olvidarlo.** Mientras cumplir la regla dependa de que alguien se acuerde, la regla es
una intención, no una garantía.

**El caso que la funda: invalidar la caché antes de medir contra la aplicación viva** (T51). La
regla estaba escrita —la escribí yo— y falló tres veces, la última en la medición de apagar un
módulo. Ahora vive en `bin/tools/live-cache.php`, la llama `bin/walk-routes` al arrancar, y si
no puede invalidar **aborta con código 1**. Ya no hace falta acordarse.

**Cómo se aplica.** Cuando una regla falle por tercera vez, no se reescribe más grande: se
busca dónde ponerla para que no se pueda saltar. Y después se repasa **qué otras reglas nuestras
dependen de que alguien se acuerde**, porque esas son las siguientes candidatas:

| Regla que hoy depende de la memoria | Cómo sería mecanismo |
| :-- | :-- |
| «Corre `bin/phpstan` antes de cerrar un cambio» | Gancho de pre-commit, o una comprobación dentro de `verify-integrity` |
| «Toda cifra lleva su método escrito» (T0) | Ya es mecanismo a medias: el trinquete exige el `[REPARTO]`, pero solo para el baseline |
| «`git add` con rutas explícitas, y cuadrar el conteo» | Comprobación que compare lo preparado contra lo modificado antes de commitear |
| «No editar PHP por línea ni por expresión regular» (T10) | **Sin mecanismo.** Hoy solo hay la regla escrita |
| «Un guion de `bin/` tiene que ser ejecutable» | **YA ES MECANISMO**: comprobación 9 de `verify-integrity`. Ver más abajo |
| «Los comentarios de prosa se recortan al pasar» | `verify-integrity` ya lo mide y ordena; falta que el registro no pueda crecer |

**Las dos últimas filas son las que más me preocupan**, porque las dos ya han fallado una vez.

#### AMPLIACIÓN — UN MECANISMO QUE DEPENDE DE UNA LISTA MANTENIDA A MANO SIGUE SIENDO MEMORIA

**Convertir una regla en mecanismo no basta: hay que mirar de qué se alimenta el mecanismo.**

**El caso que la funda**: la lista de excepciones de `bin/` en `.gitattributes` estaba mantenida a
mano, así que los guiones añadidos después nacían en CRLF. **La herramienta construida para que
nadie tuviera que acordarse —`bin/live-cache`— era precisamente la que no arrancaba.** El
mecanismo existía; su alimento era memoria.

#### El barrido de nuestra propia maquinaria

*Método: se listan los registros versionados de `files/dev/` y las listas embebidas en las
herramientas, y de cada uno se pregunta si lo REGENERA un comando o lo escribe una persona.*

| Registro / lista | Cómo se alimenta | Estado |
| :-- | :-- | :-- |
| `integrity-signatures.json` | **Derivada** — `verify-integrity update-snapshot=yes` | Sano |
| `narrative-comments.json` | **Derivada** — `list-narrative=yes`, y solo puede encoger | Sano |
| `route-inventory.json` | **Derivada** — `bin/cli route-inventory`, y va en `.gitignore` | Sano |
| `volatile-state.json` · las 14 tablas del slug | **Derivada y comprobada** | Cerrado por la comprobación 11 |
| `volatile-state.json` · el resto (`login_attempts`, `missing-lang`) | **A mano** | **Abierto** — nada detecta si sobra o falta una |
| `deprecated-functions.json` | **A mano** | **Abierto** |
| `shared-toolchain.json` — 4 paquetes, sus archivos y marcadores | **A mano** | **Abierto** |
| `.gitattributes` — excepciones de `bin/` | **A mano** | **CERRADO**: pasó a patrón `bin/*` |
| `bin/phpstan.neon` — 15 bloques `paths:` con 23 rutas | **A mano** | **Abierto**, y es el de mayor superficie |
| `bin/walk-attribute` y `bin/walk-routes` — la lista `$forbidden` de **17** patrones de escritura | **A mano** | **CERRADA**: pasó a `files/dev/forbidden-routes.json`, con la comprobación 12. Ver T73 |
| `SchemeSqlTask::IGNORED_DIRECTORIES` | **A mano**, pero es lista NEGRA | Sano por construcción: quedarse corta añade ruido, no ceguera |
| `PreferSlugsFiller::mappersWithSlug()` | **Derivada** | Sano |

**Van tres encontradas y cerradas** —`volatile-state` con la comprobación 11, `.gitattributes` con
el patrón, y `$forbidden` con la comprobación 12—. **Quedan cuatro abiertas.**

> **`$forbidden` estaba copiada en `bin/walk-routes` y en `bin/walk-attribute`.** Eran 17 patrones
> —no 14, cifra mía mal contada— que deciden **qué rutas NO se piden**. Si alguien añadía uno en un
> archivo y no en el otro, un recorrido pedía una ruta de escritura que el otro se saltaba, y el
> que la pedía **escribía** mientras creía que solo leía. Era la misma forma que el
> `PUBLIC_AREA_ROUTES` que deshicimos: la enumeración viviendo dos veces. **Cerrada en T73**, que
> trae además el diff previo y las cuatro restantes **ordenadas por lo que cuesta que diverjan**.

### LEY 12 — UNA PASADA DE ATRIBUCIÓN SOLO ES VÁLIDA SOBRE UNA BASE RECIÉN RESTAURADA

**La segunda pasada sobre la misma base no confirma la primera: mide un mundo que la primera ya
cambió.** Un verde en la segunda no significa que no haya nada; significa que **ya no queda nada
QUE HACER**, que es distinto.

**Las dos veces que pasó, y las dos en el mismo apartado:**

| Qué | Qué hizo el recorredor |
| :-- | :-- |
| `news_categories` | La pasada de T56 **rellenó los `preferSlug` nulos** que venía a detectar |
| `pcsphp_app_config` | La misma pasada **creó la fila** de `GenericContentPseudoMapper`, y en la segunda ya no había nada que crear |

**Un recorrido de solo-lectura que dispara rellenos perezosos se agota a sí mismo.** No es un
fallo del recorredor: es lo que pasa cuando el objeto medido reacciona a la medición.

**Consecuencia operativa para E3 —escrita también en la escalera—: cada lote necesita su
restauración ANTES de la foto.** Una base reutilizada entre lotes invalida todas las fotos
siguientes, y lo hace en la dirección tranquilizadora.

#### El mecanismo que pide la LEY 11, y lo que falta para tenerlo

**No lo hay, y no invento una heurística**: una heurística equivocada aquí es peor que nada,
porque daría por buena una pasada que no lo es.

Lo que haría falta, dicho para que se pueda construir:

1. **Que restaurar deje rastro.** Hoy restaurar es `mysql < archivo.sql` a mano — una operación
   sin registro. Haría falta una tarea (`bin/cli db-restore`) que, además de aplicar el volcado,
   **anote cuándo lo hizo**: una fila, un archivo en `files/dev/`, cualquier marca con fecha.
2. **Que el recorredor la exija.** `bin/walk-attribute` compararía esa marca con la fecha de su
   propia última corrida y **abortaría** si la base no se ha restaurado desde entonces.

Sin el paso 1 el paso 2 es imposible: **no hay ningún dato en el sistema del que se pueda deducir
con certeza si esta base viene de una restauración o de la pasada anterior.**

### LEY 13 — UNA SUITE OMITIDA ES UNA PUERTA FALLADA, NO UN DATO NEUTRO

**«Verde», «rojo» y «no corrió» son tres estados, y solo dos dicen algo del código.** Una puerta
que no se abre no informa: calla. Y una puerta que calla se lee como verde, porque nada rojo
aparece.

**El caso que la funda.** `unit-tests:core/scheme-sql-round-trip` se omitió a sí misma desde el
**24-08 a las 15:50** —el paquete instalado no traía `createScript()`— y su omisión se reportó
**dos veces como «8/8»**, leyendo el número que la lista de puertas anunciaba en vez del que
imprimía la suite. La condición de cierre de las 39 declaraciones de clave ajena dependía de esa
puerta, **y esa puerta no corrió**.

**La ley.** Una suite que no corre se reporta como **FALLO** hasta que alguien decida
explícitamente que su omisión está justificada, **y esa decisión se escribe**. El silencio nunca
cuenta como aprobación.

**La mitad que es de quien redacta las instrucciones**: las listas de puertas venían con el
resultado esperado impreso —«(8/8)»—, y **una puerta que anuncia su resultado invita a leer el
anunciado**. Desde ahora una lista de puertas **nombra la suite y no el número**.

**El mecanismo** está en `bin/cli gates`. Ver T74.

### El baseline vigente y su método

| Cifra | Con qué se midió |
| :-- | :-- |
| **877 errores** | `bin/phpstan`, leyendo `PHPStanResult.json`: `totals.file_errors`. Cuenta **instancias**, no tripletas distintas. |
| **190 archivos** | El mismo JSON: número de claves de `files`. |

El cambio de configuración del punto 3 —`dynamicConstantNames`— lo movió **cero**: ver T13,
donde el delta va contado aparte con su método.

Verificado con un segundo método independiente, como manda T20: `PHPStanResult.Summary.txt`
lo obtiene parseando **la tabla** con otro código —`bin/phpstan-process-result.php`— y da
los mismos **877 y 190**. Distinto formateador y distinto parser, así que el acuerdo no es
tautológico.

### Las tres unidades, y por qué hay que decir cuál se usa

Casi todas las discrepancias de esta campaña han sido **la misma cifra en otra unidad**, no
un error. Estas son las únicas tres que se usan, y **ninguna cifra publicada puede omitir la
suya**:

| Unidad | Qué cuenta | Cómo se obtiene |
| :-- | :-- | :-- |
| **Instancia** | Cada error reportado, aunque otro sea idéntico | `totals.file_errors` de `PHPStanResult.json` |
| **Tripleta** | Cada `(ruta, línea, mensaje)` **distinta** | Deduplicar las instancias por esa clave |
| **Archivo** | Cada archivo con al menos un error | Número de claves de `files` |

La diferencia entre instancia y tripleta son los **duplicados exactos**: dos errores en la
misma línea con el mismo mensaje. Hoy hay **9** (877 contra 868). No es ruido de medición:
son reales, y se cuentan dos veces cuando lo que importa es el volumen de trabajo y una sola
cuando lo que importa es el número de sitios a tocar.

**Es la reconciliación del «469 contra 464»**: las dos eran correctas, en unidades distintas.

### Refinamiento del marco de las cuatro respuestas

**«CONTRATO» solo está disponible cuando el lenguaje OFRECE una expresión para la garantía.**

Para `createFromFormat` existía: `new \DateTime(...)` devuelve `DateTime` sin condiciones.
Para `json_encode` no hay ninguna función que devuelva `string` incondicionalmente, así que
las opciones eran la bandera, un cast, un ignore o una rama inalcanzable — y solo una es
honesta.

> **Cuando el lenguaje no ofrece la expresión, la forma honesta más cercana es convertir el
> fallo imposible en un fallo RUIDOSO. No sustituye al contrato: es el contrato escrito de
> la única forma disponible.**

## T0bis · LA PRE-AUTORIZACIÓN CUBRE LA SEGURIDAD MECÁNICA, NO LA ESCALA

**Regla escrita a raíz de un fallo concreto, y el fallo fue de proceso, no de técnica.**

C.2 —unificar `routeName`/`allowedRoute` en un trait— estaba autorizado con una frontera
técnica correcta: **solo copias demostrablemente idénticas**. La frontera se respetó: la
identidad se comprobó por tokens archivo a archivo y las 28 que no coincidían quedaron
intactas. Y aun así **54 métodos borrados en 44 controladores aterrizaron sin que el
propietario los hubiera visto**.

Que el commit tuviera que ser único —borrar los métodos sin el `use` puesto deja el árbol
roto— **era cierto y no eximía de nada**: obliga a que el commit sea atómico, no a que el
plan sea invisible.

### La regla

> **Cualquier cambio que borre o mueva declaraciones en más de DIEZ archivos se enseña
> ANTES de commitear** —el plan y la evidencia—, **aunque esté pre-autorizado y aunque cada
> cambio individual sea trivial.**

Lo que se enseña son las dos cosas, no una:

- **El plan**: qué se toca, con qué criterio, y qué queda fuera y por qué.
- **La evidencia**: la medición que sostiene el criterio, y el diff de una muestra.

### Por qué diez y no «los grandes»

Porque «grande» lo juzga quien hace el cambio, y ahí está el sesgo. **Un umbral contable no
se puede racionalizar.** Y la escala es justo lo que vuelve irrevisable un lote de cambios
individualmente triviales: nadie revisa 44 archivos para comprobar que sobran — es el mismo
mecanismo que escondió el desastre de los CRLF (T21).

**La pre-autorización responde «¿es seguro?». No responde «¿es esto lo que quería?».**
## T1 · La garantía de `ERRMODE_EXCEPTION`, con su razón real

`Database::query()` y `Database::prepare()` declaran `\PDOStatement` con **tipo nativo**
desde `piecesphp/database` **v3.2.0**. La garantía de que nunca devuelven `false` **no
viene de `Database.php` fijando el atributo**: viene de que **PDO usa
`ERRMODE_EXCEPTION` por defecto desde PHP 8.0**, y el paquete declara `php: >=8.4 <9.0`.

Verificado empíricamente con un `new PDO(...)` pelado, sin tocar atributos:
**modo 2 en PHP 8.1 y en 8.4**.

El `setAttribute(ATTR_ERRMODE, ERRMODE_EXCEPTION)` de `instance()` **se conservó** aunque
hoy sea redundante: si la garantía descansara solo en un valor por defecto del lenguaje,
dependería de una decisión que podría volver a cambiar. Fijarlo en el paquete la vuelve
local y verificable.

**Es minor, no parche**, porque estrechar un retorno rompe a quien hereda: una subclase
que redeclare `query(): PDOStatement|false` deja de ser legal por covarianza y **PHP falla
al declarar la clase**. Comprobado que ninguna clase de los cinco repos hereda de
`Database` — `ActiveRecord` la compone.

## T2 · PHPStan truncaba las rutas largas — **RESUELTO**

**Era un defecto de herramienta, y salió más caro de lo estimado.**

El formateador de tabla de PHPStan recorta la cabecera de cada archivo al ancho de
terminal. Con la salida redirigida a un archivo ese ancho cae a 80, así que las rutas
salían **cortadas a 73 caracteres** (`ApplicationCallsController.p`).

`bin/tools/refactorization/Rector.php` armaba su lista de archivos con una expresión
regular sobre ese `.txt` y **descartaba con `file_exists()` lo que no resolvía, sin decir
nada**. Medido: **34 de 195 archivos —el 17 % de la superficie con errores— nunca entraron
al análisis.** Rector no fallaba; simplemente no los veía. Eso explica por qué parecía
proponer tan poco.

**Coste comprobado:** al recuperar la superficie, Rector propone cambios en **los 34**.

**Qué se hizo:**

| Archivo | Cambio |
| :-- | :-- |
| `bin/phpstan` | `COLUMNS=400` para que la tabla deje de recortar, y una segunda pasada con `--error-format=json` que emite `PHPStanResult.json` |
| `bin/tools/refactorization/Rector.php` | Lee el JSON como fuente de verdad; el `.txt` queda de respaldo con aviso. **Ya no descarta en silencio**: enumera lo que no resuelve y aborta si la lista queda vacía |
| `bin/rector` | Fija `php8.4`; corría con el `php` por defecto, que es 8.1.34 y está por debajo del piso |

La segunda pasada de PHPStan cuesta **menos de un segundo** con la caché de resultados
caliente, así que no hay motivo para no tener siempre la salida de máquina.

**Efecto lateral que confirma el alcance:** el resumen humano pasó de decir 192 archivos
a decir **195**. El truncado también estaba corrompiendo el conteo que leíamos nosotros.

**La regla que deja:** una herramienta que descarta entradas en silencio es peor que una
que falla. «0 cambios propuestos» y «no miré nada» son indistinguibles desde fuera.

## T3 · D2 — comprobar credenciales escribía en base de datos — **RESUELTO**

**El caso más instructivo de toda la campaña, porque la primera evidencia era falsa.**

El diagnóstico inicial decía que el defecto estaba en `getOTPData()`, alcanzado desde
`checkValidityOTP()` en el login. Al ir a comprobarlo antes de arreglarlo, se cayó:
`getOTPData()` filtra el método y **solo acepta `METHOD_ONE_USE_CODE`**, así que no puede
crear las 34 filas `TOTP` que se le atribuían. La atribución era imposible.

**Dónde estaba de verdad**, que resultó ser peor:

| | |
| :-- | :-- |
| `getTOTPData()` | Mismo patrón get-or-create, y lo llama **sin condiciones el constructor de `UserDataPackage`** (línea 243). **Construir un paquete de usuario escribía en base de datos.** |
| `createOTPAlternativesRecords()` | Llamada desde `UserSystemFeaturesRoutes::routes():67`. `routes()` corre **en cada petición**: dos `GROUP_CONCAT` + `LEFT JOIN` sobre la tabla entera de usuarios por carga de página. |

La superficie no era una función: era **el constructor del paquete de usuario**, que
alcanzan sin autenticar `checkValidityOTP`, `checkValidityTOTP`, `toExpireOTP` y
`generateOTP` — todos con `new UserDataPackage($id)` antes de verificar credencial alguna.

**Lo que NO era el defecto**, y conviene dejarlo escrito para que nadie lo «arregle»: el
orden de `UsersController` 899/900 es correcto. La condición es
`password_verify(...) || $otpIsValid`, así que el código de un solo uso es una vía de
autenticación alternativa y la comprobación OTP **tiene** que correr en todo intento. El
defecto nunca fue cuándo se comprueba: era que **comprobar escribía**.

### Severidad, reencuadrada tras comprobar

| Eje | Nivel | Por qué |
| :-- | :-- | :-- |
| Seguridad | **BAJA** | El secreto se regenera al activar el 2FA (`toggle2FA()` hace `generateSecret()` incondicional), así que el material pregenerado **nunca llega a ser credencial viva**. Y como el relleno masivo ya había creado todas las filas, al sondear no había escritura diferencial: el oráculo de enumeración por temporización tampoco existía. |
| Rendimiento | **ALTO** | Dos barridos sobre la tabla de usuarios por petición. Con 34 usuarios no se nota; con cien mil es un incendio. |
| Arquitectura | **ALTO** | Una migración de datos ejecutándose en bucle infinito dentro del registro de rutas, que debe ser puro. |

### La trampa de orden

**(b) tapaba a (a).** Si se hubiera sacado el relleno de `routes()` sin arreglar los
buscadores, los usuarios nuevos habrían dejado de tener filas precreadas, el get-or-create
habría vuelto a escribir en la ruta no autenticada y el oráculo de enumeración habría
**renacido**. Los dos cambios aterrizan juntos, y si hay que partirlos, **(a) primero**.

### Qué se hizo

- Los dos buscadores del mapper son **puros** y devuelven `null`. La mitad de escritura
  vive en `createOTPData()` / `createTOTPData()`, y su único llamante es `toggle2FA()`,
  que es donde el usuario ya autenticado pide configurar su segundo factor.
- `TOTPData` pasa a ser nulable de verdad: seis sitios que encadenaban sobre él lo manejan
  ahora de forma explícita. En `toExpireOTP()` la respuesta al `null` es **registrar y
  continuar** — sin registro no hay código de un uso que caducar.
- `createOTPAlternativesRecords()` sale de `routes()` y pasa a la tarea
  **`bin/cli sync-otp-records`**, que por defecto **solo informa** y exige `apply=yes` para
  escribir. El inventario se separó en `missingOTPRecords()`, de solo lectura.
- `toggle2FA()` inicializaba `$result = false` y no lo reasignaba nunca: devolvía `false`
  incluso al guardar bien. Su único llamante ignoraba el valor, así que no rompía nada —
  pero era una trampa esperando al primero que se fiara del docblock.

**Suite nueva:** `bin/cli unit-tests:core/otp-write-separation`. Falla antes del arreglo.
Las dos primeras comprobaciones son **estructurales**, no de comportamiento, y a propósito:
la versión de comportamiento exigiría crear un usuario sin filas —escribir datos de prueba
en una base ajena— y además **hoy no fallaría**, porque el relleno masivo tapaba el
defecto. Ese es justo el motivo por el que las dos reglas se comprueban por separado.

**Detalle del test que merece recordarse:** la primera versión buscaba por texto plano y
fallaba contra el propio comentario que documenta lo que se quitó. Se tokeniza con
`token_get_all()` y se descartan `T_COMMENT`/`T_DOC_COMMENT`. Un test que confunde
documentar con hacer obliga a no documentar, que es peor que el test.

### Las 34 filas huérfanas: no se purgan

Son basura **inerte**: el secreto se regenera al activar, así que nunca serán credenciales.
Y borrarlas antes del arreglo no servía de nada, porque el relleno las recreaba en la
siguiente petición. Vaciar la columna `secret` de las filas `TOTP` con
`twoAuthFactor = 'DISABLED'` es **higiene, no arreglo**: prioridad baja. Las
`ONE_USE_CODE` **no se tocan** — pueden sostener códigos vigentes; mirar `maxDate` antes
de nada.

## T4 · El token de GitHub sigue sin rotar

El framework tiene **credenciales en texto plano dentro de las URL de sus remotos**, en
`.git/config`. Avisado el 2026-08-20 y **sigue ahí**.

**No es un remoto: son TRES.** `origin` (GitHub), `origin2` (GitLab) y `origin3`
(Bitbucket) llevan cada uno su token incrustado en la URL. Rotar solo el de GitHub deja dos
fuera.

**DECIDIDO POR EL PROPIETARIO Y CERRADO**: las URL solo existen en su máquina local, a la
que nadie más accede, y ese razonamiento vale igual para tres que para uno. **No se vuelve a
levantar.** Queda anotado que son tres, no uno, por si alguna vez cambia el contexto.

## T5bis · Dos patrones del proyecto

### `token_get_all()` es la respuesta estándar para distinguir código de prosa

Cada vez que hay que preguntarle algo al código fuente —¿hay una llamada a X?, ¿este
docblock está cerrado?— **se tokeniza, no se busca por texto**. Ya lo usan
`verify-integrity` y `unit-tests:core/otp-write-separation`.

Las dos veces que se hizo a ojo salió mal: la primera versión de `verify-integrity`
contaba `/*` contra `*/` y daba 32 falsos positivos porque `'image/*'` aparece dentro de
cadenas; la primera versión del test de OTP buscaba por subcadena y **casaba con el
comentario** que documenta la llamada retirada.

### Un test que confunde documentar con hacer obliga a no documentar

Si un test castiga un comentario, **la herramienta está mal, no el comentario**. La salida
correcta no es quitar el comentario para que el test pase: es enseñarle al test a
distinguir. Descartar `T_COMMENT` y `T_DOC_COMMENT` cuesta seis líneas.

Un test que empuja a documentar menos es peor que no tenerlo, porque su coste no se ve en
la ejecución sino meses después, en lo que nadie escribió.

## T5 · Por qué las puertas son las que son

`bin/cli verify-integrity`, las dos suites y `PHPStanResult.Summary.baseline.txt` no son
ceremonia. Existen porque en esta migración **hubo diagnósticos que se sostenían al leer
el código y se caían al ejecutarlo**:

- El `E_DEPRECATED` escondido en un `array_keys()` de `bootstrap.php`: leyendo la tabla de
  niveles parecía informativa; ejecutando, promovía **toda** deprecación a excepción y
  tumbaba la aplicación en 8.5.
- **D1**, un defecto de reconexión que se argumentó, se documentó y **no existía**: la
  prueba que iba a confirmarlo lo desmintió.
- El `setAttribute` del constructor: se añadió creyéndolo necesario y **ninguna prueba
  notó la diferencia**, porque la garantía venía de otro sitio.
- Los **46 errores que apuntaban a archivos inexistentes**, que parecían una categoría del
  triaje y eran un defecto del parseo.

**La regla que queda escrita: una afirmación sobre comportamiento en ejecución no se da
por cierta hasta que se ejecuta.** Escribir la prueba primero no es rigor ceremonial —
tres veces evitó un cambio que no hacía falta.

---



Backlog posterior a la migración de PHP, ordenado por dependencias y por lo que
desbloquea cada cosa. **Sin fechas ni estimaciones de calendario**: el orden es la
información, no el plazo.

Estado de partida: migración cerrada en `v7.1.0`, rango `>=8.4.1 <8.6`, integrada en la
tríada salvo `last-stable`, congelada a propósito. Ver
[16-plan-php85.md](./historico/16-plan-php85.md) y [17-ruta-de-ejecucion.md](./historico/17-ruta-de-ejecucion.md),
ambos ejecutados.

---

## 1. ~~Cerrar el hueco de las tres librerías~~ — HECHO

`datastructures`, `geojson` y `html` ya tienen el rango `{min: 80400, max: 80500}`, las
reglas de deprecación, Rector y línea base. **Resultado: cero deprecaciones y cero
errores reales.** Los 3 de `html` son `function.alreadyNarrowedType` benignos —
`is_scalar()` tras `is_string()`, y un `!is_array()` sobre un parámetro ya tipado.
Publicar `">=8.4 <9.0"` sin haberlas analizado salió bien, pero fue suerte.

Corregido de paso: el `composer.lock` de `html` seguía fijado a `datastructures` v3.0.0
pese a pedir `^3.1`, así que sus 7 pruebas se habían dado por verdes contra la versión
anterior.

---

## 2. Cobertura de pruebas unitarias

**El riesgo dominante del proyecto, y lo ha sido desde el primer diagnóstico.**

Estado real:

| Repo | Cobertura |
| :-- | :-- |
| `database` | 9 suites, 72 pruebas — **la referencia a imitar** |
| `datastructures` | 26 pruebas |
| `html` | 7 pruebas |
| `geojson` | ninguna |
| **`piecesphp`** | **3 suites para ~88.000 líneas** |

La metodología ya existe y es la del propio framework: acciones de terminal
`bin/cli unit-tests:<suite>`, con los archivos en `src/app/core/system-controllers/local-tests/`
y las tareas registradas en el CLI. `piecesphp/database` demuestra que escala a 9 suites.
No hace falta inventar nada: hay que aplicarlo.

Prioridad dentro de esta ventana, por orden de lo que más se toca y peor se rompe:

1. **ORM** — `save`, `update`, meta-propiedades, `$fields` con relaciones,
   `systemApprovalStatus`
2. **Rutas y permisos** — `routeName`, `allowedRoute`, `Roles::hasPermissions`
3. **`ServerStatics`** — resolución de rutas, protección de directorios
4. **i18n** — `__()`, `LangInjector`, caída al idioma base
5. **Validación** — `Parameters`, `Validator`

Esta ventana **bloquea las ventanas 5 y 6**: no se refactoriza sin red.

---

## 3. Azure — cerrar la fase E

`microsoft/azure-storage-blob` sigue abandonado y `composer audit` lo reporta. Migrar a
`azure-oss/storage-blob`, reescribir `BlobStorageAzureAdapter::read()` como un `getBlob`
directo en vez de listar el contenedor entero, y arreglar la recursión infinita de
`BlobStorageFileAzurePackage::blob()`. Detalle en
[16-plan-php85.md](./historico/16-plan-php85.md).

Independiente de todo lo demás.

---

## 4. `strftime()` antes de PHP 9

El único hallazgo con fecha de caducidad. Tres usos en `localeDateFormat()`
(`Utilities.php:1681, 1686, 1694`), alimentados por **47 puntos de llamada**.

Hoy no molesta porque están escritas como `@strftime()` y el handler respeta la
supresión. Cuando PHP 9 la elimine, el `@` no salva: será
`Call to undefined function`. El reemplazo natural es `IntlDateFormatter`, que respeta
locale — que es justo lo que esa función busca — y `intl` ya está cargado.

Hacerlo **con la ventana 2 hecha**, porque 47 puntos de llamada sin pruebas es una
apuesta.

---

## 5. Que PHPStan diga la verdad — **y no es lo que este documento decía**

> **Corrección del 2026-08-21.** La versión anterior afirmaba que el 91% de los errores
> venía de que PHPStan no entiende el `__get`/`__set` de los mappers, y que una
> `PropertiesClassReflectionExtension` los eliminaría. **Se midió y es falso.** No hace
> falta ninguna extensión.

Reparto real de los 1.060 errores visibles, medido causa a causa:

| Causa | Errores | % |
| :-- | --: | --: |
| Unión con `null` sin comprobar | 541 | 51% |
| Unión con `false` sin comprobar (funciones nativas) | 144 | 14% |
| Otras uniones sin estrechar | 157 | 15% |
| Tipo `object` pelado | 60 | 6% |
| `@property` más estrecho que la asignación | 21 | 2% |
| Cola larga sin patrón | 137 | 13% |

**El 79% es una sola cosa: uniones que no se estrechan antes de usarse.**

### Y no son ruido

Este documento los trataba como el precio inevitable de un ORM dinámico, buenos solo
como detector de regresión. **Es al revés.** Son 541 sitios donde algo puede ser `null`
y nadie lo comprueba — y en este framework, con `E_WARNING` promovido a excepción
(ver [03-ciclo-de-vida.md](./03-ciclo-de-vida.md)), un `null->metodo()` no es un aviso:
**mata la petición**.

Es exactamente la clase de defecto contra la que hay que blindarse. PHPStan llevaba
tiempo señalándolos.

### Los arreglos, por premio sobre esfuerzo

| Qué | Errores | Cómo |
| :-- | --: | :-- |
| ~~`getBy()` declarado `@return static\|object\|null`~~ **HECHO** | −36 | Fueron **36 docblocks en 29 archivos**, no 26: el patrón también estaba en `lastModifiedElement` (7), `getByMultipleCriteries` (2) y `getExactAttachment` (1). El flag **no** se llama igual en todos —`$as_mapper` y `$asMapper`—, así que hubo que ajustarlo uno a uno |
| `getLoggedFrameworkUser()` encadenado sin comprobar | 123 | **Contrato, no parches**: añadir `getLoggedFrameworkUserOrFail(): UserDataPackage` y usarla donde `requireLogin = true` garantiza sesión |
| ~~`@property` que mienten sobre lo asignado~~ **HECHO** | −15 | Fueron **6**, no 21: el resto de `assign.propertyType` no son `@property` de mapper. Cada corrección se contrastó contra el `$fields` del mapper. **Cinco casos se dejaron sin tocar a propósito**: ahí el docblock no miente, es el código el que asigna algo que el esquema no admite; ensancharlos habría documentado el bug |
| ~~Regex sin escapar en el ignore de `BaseEntityMapper`~~ **HECHO** | +2 | Escaparlo destapa **2**, no 8. El 8 salía de quitar la regla entera; los otros 6 son `undefined method` genuinos que la rama correcta sigue silenciando, que es para lo que la regla existe |
| `BaseController::$model` con unión de tres tipos | 6 | Docblock o genérico. La escala era mucho menor de lo estimado |
| El resto de los 541 `null` | ~500 | Triaje por categorías, con pruebas hechas |

---

## 5bis. Triaje de la nulabilidad — 2026-08-21

Estado tras la primera tanda: **514 errores con `null`** (eran 541; 27 resueltos).

### Por origen

| Origen | Errores | Qué son |
| :-- | --: | :-- |
| Buscador de mapper que no encuentra | **137** | `Mapper::getBy()` y hermanos usados sin comprobar |
| Escalar opcional | **136** | `string\|null`, `int\|null`… de propiedades, parámetros o retornos |
| Otro objeto nulable | 92 | Colaboradores opcionales |
| Sesión: `getLoggedFrameworkUser()` | 87 | Lo que queda tras la primera tanda |
| Fila cruda de consulta | 39 | `object\|null` / `stdClass\|null` |
| `Database` no inicializada | 20 | **Todos del mismo defecto**, ver abajo |
| Sin clasificar | 3 | |

### Por respuesta del marco

| Grupo | Errores | Criterio |
| :-- | --: | :-- |
| **1. No puede ser null por contrato** | **36** | Manejadores de ruta con login. **HECHOS**: pasan a `getLoggedFrameworkUserOrFail()` |
| 2. Puede ser null, comportamiento definido | ~350 | Mayoría de B, D y F: hay que decidir caso a caso |
| **3. Puede ser null y nadie lo pensó** | **ver abajo** | Defectos |

**Cómo se clasificó el grupo 1, y es reutilizable**: el framework expone su listado
resuelto de rutas en `/configurations/routes/`, con clase, método y roles. Las públicas
dicen **«No requiere autenticación»**; cualquier otro valor implica login. Cruzar los
errores contra ese listado da la respuesta sin adivinar. De los 123 de sesión: **36** en
rutas con login, **0** en rutas públicas, 61 en métodos que no son ruta, 26 en vistas.

---

## 5ter. DEFECTOS ENCONTRADOS — grupo 3

Son el motivo de la ventana. **Ninguno está arreglado**: hay que decidir qué debe hacer
cada uno.

### D1 · ~~`getDatabase()` puede devolver null~~ — **REFUTADO el 2026-08-21**

> **La sospecha era falsa.** Se escribió una prueba para reproducirla y no se reprodujo:
> `piecesphp/database`, suite `core/database/db-fallback`.

Se sospechaba que `restoreInstancesDb()`, al actuar solo
`if (!isset(self::$db[$key]))`, dejaría sin conexión a las instancias que pidieran
`getDatabase()` **después** de que otra repoblara el pool.

El escenario montado —dos instancias, `destroyDb()`, A pide primero y B después— **no
falla**: B se recupera con normalidad.

**Error de lectura**: `restoreInstancesDb()` no restaura a quien llama. Recorre
`self::$instances` y reconfigura la **cohorte entera** con esa clave, así que la
restauración que dispara A alcanza también a B. La guarda `if (!isset(...))` evita
restaurar dos veces; no limita a quién se restaura.

**Los 20 errores `Database|null` no son un defecto de ejecución**: vienen de que
`ActiveRecord::$database` se declara `@var Database|null` en la clase base y el override
hereda esa firma. Estrechar el docblock a `@return Database` sería la respuesta 1 del
marco, pero **no se hizo**: no se puede demostrar que sea imposible en toda la jerarquía,
y afirmarlo sin prueba es exactamente el «callar al analizador» que este trabajo evita.

La suite se conservó: el comportamiento de cohorte no lo cubría nada y es fácil de romper.

### D2 · `OTPHandler::toExpireOTP()` desreferencia sin comprobar, en el camino de login

`Authentication/OTPHandler.php:68-71`:

```php
$otpData = OTPSecretsUsersMapper::getOTPData($userData->id, ...METHOD_ONE_USE_CODE);
$otpData->maxDate = new \DateTime('2000-01-01');   // <- sin comprobar
```

La guarda `if ($userDataPackage !== null)` protege al **usuario**, no al resultado de
`getOTPData()`, que declara `OTPSecretsUsersMapper|null` y devuelve null si su `save()`
interno falla (`return $mapper->id !== null ? $mapper : null;`).

**Dónde duele**: el único llamante es `UsersController.php:909`, dentro del login, justo
**después** de validar el segundo factor y **antes** de emitir el token de sesión. Si
ocurre, el usuario se autentica correctamente y la petición aborta con un
«Attempt to read property on null».

**Decisión pendiente**: `toExpireOTP()` es una rutina de invalidación. Si no hay nada que
invalidar, lo razonable es no hacer nada y seguir, no tumbar un login válido. Pero eso
hay que decidirlo, no asumirlo.

### D3 · `UsersModel` desreferenciaba el usuario de sesión antes de comprobarlo — **CORREGIDO**

Cuatro métodos aceptaban `?UserDataPackage = null` y caían a `getLoggedFrameworkUser()`,
y tres leían `->organization` en la línea siguiente. Corregido con la forma que el propio
`getBy()` ya usaba. Latente: ningún llamante llegaba al fallback. Ver commit.

---

## 5quater. Triaje de los `false` — por MECANISMO DE FALLA — 2026-08-21

**148 errores.** Ordenarlos por función no basta: lo que determina la respuesta es **cómo
falla** cada una en ESTE framework, donde `bootstrap.php` promueve `E_WARNING` a
excepción.

### Verificado, no supuesto

Ejercitando el handler real del framework en modo producción:

| Función | Qué hace al fallar |
| :-- | :-- |
| `file_get_contents()` ruta inexistente | **ABORTA** — `ErrorException` |
| `fopen()` ruta inexistente | **ABORTA** |
| `imagecreatefromstring()` con basura | **ABORTA** |
| `unlink()` ruta inexistente | **ABORTA** |
| `createFromFormat()` que no casa | devuelve `false` |
| `realpath()` ruta inexistente | devuelve `false` |
| `json_encode()` UTF-8 inválido | devuelve `false` |
| `strpos()` no encontrado | devuelve `false` |

La separación es limpia: **o abortan siempre, o devuelven `false` siempre.**

### GRUPO A — fallan con warning: la petición ya murió (40)

| Función | Err | Arch |
| :-- | --: | --: |
| `file_get_contents()` | 19 | 9 |
| `fopen()` | 8 | 3 |
| `finfo_open()` | 4 | 2 |
| `imagecopyresampled()`, `unlink()`, `glob()`, `opendir()`, `readdir()`, `imagecreatetruecolor()`, `imagesavealpha()` | 9 | 6 |

**Aquí el `false` nunca llega.** La pregunta en cada sitio no es cómo silenciarlo, sino
**¿queremos que esto sea un 500?** Para una operación de usuario casi nunca: un adjunto
que no está no debería tumbar la petición. La respuesta es **manejo explícito real**
—`is_file()` antes, o `@` más comprobación—, no una anotación.

### GRUPO B — fallan en silencio: el `false` llega y es información (80)

| Función | Err | Arch | Respuesta canónica |
| :-- | --: | --: | :-- |
| `json_encode()` | 24 | 14 | `JSON_THROW_ON_ERROR` donde el sitio pueda permitirse la excepción |
| **`createFromFormat()`** | **15** | **13** | Casi siempre entrada de usuario mal formada: **necesita rama** |
| `realpath()` | 10 | 6 | Suele ser un `is_dir()`/`is_file()` mal ordenado |
| `fetch()` | 6 | 5 | **No es error**: `false` significa «no hay más filas» |
| `strpos()` / `mb_strpos()` | 9 | 5 | `!== false`, nunca `if ($pos)` |
| `ob_get_contents()`, `array_search()`, `strftime()`, `gzencode()`, `json_decode()`, `ini_get()`, `saveHTML()`, `preg_match()` | 16 | — | Cola |

### GRUPO C — no pueden devolver `false` (11)

`query()` y `prepare()`. **Resuelto en `piecesphp/database` v3.1.1**: se estrecha el tipo
de retorno con tipo nativo. `false` es el retorno del modo `ERRMODE_SILENT`, y desde
**PHP 8.0 el default de PDO es `ERRMODE_EXCEPTION`**; el paquete declara `>=8.4 <9.0`.

> **`fetch()` NO entra aquí**, aunque lo pareciera: `ERRMODE_EXCEPTION` cubre los errores,
> pero `fetch()` devuelve `false` legítimamente cuando se acaban las filas. Va al grupo B.

### Sin identificar (17)

Bajaron de 63 al resolver un artefacto propio: **PHPStan trunca las rutas largas** en su
salida de tabla, así que 18 errores apuntaban a archivos inexistentes y no se les podía
leer el contexto. Resueltas contra el árbol real.

Los 17 que quedan necesitan lectura directa; no son una categoría.

### Por dónde empezar

**Grupo B, `createFromFormat`**: 15 errores en 13 archivos, y un `false` ahí casi siempre
significa una fecha que no encaja con el formato esperado —entrada de usuario o dato de
BD—, que es justo el caso que nadie previó.

---

## 6. Las supresiones: el 74% está oculto

**67 entradas**, no 55. Auditadas una a una quitándolas y midiendo: **48 vivas, 19
muertas**. Silencian **3.083 errores** frente a los 1.078 visibles.

> **Actualización del 2026-08-21 — las muertas ya están borradas.** 12 entradas
> completamente muertas, más **5 rutas `**/*` redundantes** que no estaban muertas por
> casualidad: en esta configuración `../src/app/*` ya casa de forma recursiva, así que su
> hermana `**/*` nunca llega a ignorar nada. Comprobado dejando solo la `**/*`: silencia
> igual. Se separó además la entrada de las vistas, porque los tres identificadores
> compartían lista de rutas y en `BaseMongoModel.php` solo casa `class.notFound`.
>
> **Quedan 0 patrones muertos.** Nueva línea base: **1.011** errores.
> Las 48 vivas siguen intactas; las 357 de la familia `alwaysTrue`/`alwaysFalse` son la
> siguiente ventana.

| Grupo | Errores | Entradas | Veredicto |
| :-- | --: | --: | :-- |
| Vistas con `extract()` | 1.350 | 1 | Legítima, estructural |
| `missingType.*` (nivel 8 exigiendo `array<K,V>`) | 946 | 5 | Legítima, decisión de rigor |
| **Siempre-cierto / siempre-falso / inalcanzable** | **357** | **19** | **Sospechosa: son ramas muertas** |
| Idioma del ORM (`fieldsToSelect`, `where in empty`) | 233 | 6 | Legítima |
| Diseño estricto (`unused`, `return.void`) | 19 | 4 | Menor |
| Resto | 178 | 13 | Mixto |

Las **19 muertas** se borran sin más: no casan con nada, y PHPStan cuenta cada patrón
muerto como error, así que borrarlas *baja* el total.

Los **357 sospechosos** merecen su propia ventana: cada uno señala una rama que nunca se
ejecuta, o una comprobación que el autor creía necesaria y no lo es. Puede haber relación
con los 541 `null`: si una comprobación «siempre es cierta», a veces es que el tipo
declarado miente.

---

## 6b. Rector, medido en seco

Propuestas por set, sin aplicar nada:

| Repo | CODE_QUALITY | DEAD_CODE | TYPE_DECLARATION | PRIVATIZATION |
| :-- | --: | --: | --: | --: |
| piecesphp | 130 | 127 | 117 | 0 |
| database | 16 | 7 | 25 | 0 |
| datastructures | 5 | 2 | 1 | 0 |
| html | 10 | 4 | 7 | 0 |
| geojson | 7 | 2 | 7 | 0 |

**`PRIVATIZATION` no propone nada en ningún repo**: fuera del `sets()`.

Sobre `DEAD_CODE`, que era la sospecha principal: se verificaron una a una las tres
reglas capaces de romper con despacho dinámico y **no apareció ningún falso positivo**.
`RemoveUnusedPrivateMethodRector` propone borrar `AdminPanelController::showPermissionsRoles()`,
que parece un endpoint pero no tiene ni una referencia en PHP, JS ni TS: se escribió y
nunca se cableó.

> **Hallazgo estructural**: ese resultado limpio es engañoso. El Rector del framework toma
> su lista de archivos de `PHPStanResult.txt`, y las vistas están silenciadas por el
> ignore de 1.350 errores. **Rector no mira las vistas porque PHPStan no las reporta**, no
> porque sea seguro ahí. Si algún día se levanta ese ignore, Rector empezará a ver justo
> los archivos donde `extract()` hace su análisis inservible.

Desconfiar de aplicar a ciegas: `RemoveNullPropertyInitializationRector` (53 archivos) y
`RemoveUselessParamTagRector` (98). `database` ya los tiene en su `skip`; el framework no.

---

## 7. Limpieza de módulos

Todo [14-deuda-y-limpieza.md](./14-deuda-y-limpieza.md). Por orden de riesgo:

- **Bajo**: `Importers` (duplicado), 24 `HelperController` triviales, `Components`,
  `scssphp`, `PDFManager` + `mpdf`.
  **Webflow SALE de esta lista y se conserva**: ver la tabla de código contra material en
  [14-deuda-y-limpieza.md](./14-deuda-y-limpieza.md).
- **Medio**: `ImagesRepository` / `FileManager` / `Banner` — tres formas de gestionar
  imágenes; `Newsletter`; `EventsLog`; el temporizador
- **Alto**: invertir la dependencia `BaseEntityMapper` → `SystemApprovals`, y separar la
  capa de proyecto del framework base

Con la ventana 2 hecha, el riesgo alto deja de serlo tanto.

---

## 8. Suelto, sin dependencias

| Qué | Dónde |
| :-- | :-- |
| Trait para `routeName()` / `allowedRoute()` — 44 controladores lo reimplementan | [05-routing-y-permisos.md](./05-routing-y-permisos.md) |
| Guzzle 8 — topado por `hubspot/api-client`; se aborda cuando dé errores | [16-plan-php85.md](./historico/16-plan-php85.md) |
| `DataTablesHelper.php:230/1090` — `columns` con defecto `null` y exigido `array` | [14-deuda-y-limpieza.md](./14-deuda-y-limpieza.md) |
| `/admin/reports-access/` da 404 pese a estar registrada | [14-deuda-y-limpieza.md](./14-deuda-y-limpieza.md) |
| `E_WARNING` y `E_NOTICE` siguen abortando en producción | [03-ciclo-de-vida.md](./03-ciclo-de-vida.md) |
| Humo de correo: nunca se ejercitó, no había cola pendiente | — |

---

## El orden, en una línea

```
A  Que PHPStan diga la verdad     docblocks y configuración, cero runtime
B  El null (541 + 123)            contrato antes que parches. Necesita pruebas
C  Las 357 ramas muertas
   Pruebas unitarias              en paralelo desde ya: B es la primera que toca lógica
   Azure, strftime, sueltos       cuando convenga
   Limpieza de módulos            la última, cuando ya haya red
```

## T6 · Mapa de destino de los módulos — **léelo antes de arreglar nada**

Arreglar código que va a desaparecer es trabajo tirado, y **reescribir un módulo que en
realidad hay que fusionar es peor**: se pierde lo que había que conservar. Este mapa decide
qué trato recibe cada error antes de mirarlo.

| Destino | Módulos | Trato |
| :-- | :-- | :-- |
| **Se borran completos** | `ImagesRepository`, `ApplicationCalls`, `InterestResearchAreas` | **Nada.** Ni un ignore cosmético. Se anota que el error muere con el módulo. |
| **Se borran parciales** | `MySpace`, `ContentNavigationHub`, `ReportsManage` | Solo se retira lo relacionado con los de arriba. El resto, trato completo. |
| **Se reescribe** | `DataImportExportUtility` | Atención **solo si el error es un defecto real**, no si es un tipo mal declarado. |
| **Trato completo** | todo lo demás | Las cuatro respuestas: contrato / manejo explícito / defecto / protocolo. |

### Experiencias previas — **se borran, y el trabajo con riesgo NO es el borrado**

`PreviousExperiencesMapper` y `OrganizationPreviousExperiencesMapper` se van, con sus dos
tablas: **`previous_experiences`** y **`organization_previous_experiences`**.

#### Frontera limpia: el directorio entero desaparece

`PiecesPHP/UserSystem/Profile/SubMappers/` tiene **exactamente tres archivos**, y los tres
mueren juntos:

| Archivo | Líneas |
| :-- | --: |
| `PreviousExperiencesMapper.php` | 768 |
| `OrganizationPreviousExperiencesMapper.php` | 768 |
| `InterestResearchAreasMapper.php` | 30 |

**El directorio se borra completo.** No queda nada que reubicar.

#### AVISO: LOS MAPPERS NO VIVEN EN `MySpace`

Los controladores, vistas y JS sí están en `MySpace`. **Los mappers están en el núcleo del
sistema de usuarios.** Quien borre los parciales de `MySpace` sin saber esto deja
**1.536 líneas huérfanas** en `PiecesPHP/UserSystem/` que nadie va a echar de menos hasta
que alguien pregunte qué hace ese directorio.

#### Inventario medido

**Se borran (9 archivos):**

```
PiecesPHP/UserSystem/Profile/SubMappers/            (los 3, el directorio entero)
MySpace/Controllers/Util/PreviousExperiencesController.php              (283)
MySpace/Controllers/Util/OrganizationPreviousExperiencesController.php  (296)
MySpace/Views/my-profile/util/experience-list-card.php
MySpace/Views/my-organization-profile/util/experience-list-card.php
MySpace/Statics/js/experience/delete-config.js
MySpace/Statics/js/experience-organization/delete-config.js
```

**Se EDITAN (9 archivos) — aquí está el riesgo del lote:**

```
SystemApprovals/Views/forms/approval-profile-user.php          ← módulo que SE CONSERVA
SystemApprovals/Views/forms/approval-profile-organization.php  ← módulo que SE CONSERVA
MySpace/Controllers/MyProfileController.php
MySpace/Controllers/MyOrganizationProfileController.php
MySpace/Controllers/Util/ProfileTasksUtilities.php
MySpace/Views/my-profile/my-profile.php
MySpace/Views/my-organization-profile/my-organization-profile.php
MySpace/Views/profile/profile.php
MySpace/Views/profile-organization/profile.php
```

**Dos correcciones al inventario que circulaba**, comprobadas archivo a archivo:

1. **`ProfileTasksUtilities.php` NO se borra: se edita.** Además de las experiencias genera
   el SQL de `UserProfileMapper`, que se queda. Borrarlo se lleva por delante la creación
   del esquema de perfiles.
2. **`MyProfileController` y `MyOrganizationProfileController` también se editan**, y no
   estaban señalados. Importan el mapper, lo instancian, llaman a `getBy()` y registran
   rutas hacia los controladores que sí se borran. Son los controladores principales de
   `MySpace`: se quedan.

#### La parte delicada

`SystemApprovals` **SE CONSERVA** y su acoplamiento es **estructural, no un import**:
`approval-profile-user.php` y `approval-profile-organization.php` llaman a
`allBy('profile', ...)`, instancian el mapper y tienen bucles de render. **Hay que editar un
módulo que se queda.** Ese es el trabajo con riesgo del lote, no el borrado — borrar no
puede romper lo que no existe, editar sí.

#### Nota de diseño, para el día que vuelva

Los dos mappers son **gemelos de copia y pega**: 768 líneas cada uno, diferenciándose en un
`reference_table` (`UserProfileMapper::TABLE` contra `OrganizationMapper::TABLE`). **Si la
funcionalidad vuelve, vuelve como una clase parametrizada, no como dos.**

#### `CountryMapper` y `CityMapper`: comprobado, no quedan huérfanos

Ambos los referencian los dos mappers que se van, pero **los dos conservan consumidores que
sobreviven al lote**: `App/Locations/` (su propio módulo), `Organizations/OrganizationMapper`
y `PiecesPHP/UserSystem/Profile/UserProfileMapper`. **No hay nada que borrar detrás.**

#### Efecto inmediato

Los archivos entran **ya** en el cubo «se borra»: el grupo B no los trabaja y Rector tampoco.
Se llevan **17 errores de PHPStan** que nadie tiene que arreglar — **ninguno** de la familia
`false`, así que la contabilidad de T8 no se mueve.

### URGENCIA DE ORDEN — lo único de esta ventana que tiene fecha

**`FieldTranslationUtility` tiene HOY tres copias, y dos están en módulos condenados**
(`ApplicationCalls` e `InterestResearchAreas`). Después de las eliminaciones queda **una**, y
parecerá un caso único en vez de un patrón.

> **La evidencia de que es un patrón se va con los módulos.** Si se va a abstraer, se **mide
> y se extrae ANTES** de las eliminaciones, no después.

Lo mismo, en menor grado, con `AttachmentPackage`: dos copias, una en `ApplicationCalls`.

Ver T25 para la medición completa.
### REQUISITOS de la centralización de utilidades clonadas — no son notas

Medición completa en T25. Lo que sigue **condiciona el diseño** y hay que cumplirlo, no
tenerlo en cuenta.

#### 1 · La clase centralizada usa `static::CODES`, y hay una prueba que lo demuestra

El constructor de `SafeException` y `DuplicateException` valida
`in_array($code, self::CODES)` y **degrada a `UNDEFINED_CODE`** lo que no esté en la lista.
Hoy funciona porque cada módulo tiene su propia clase con su propia lista.

> **Con una clase centralizada y subclases por módulo, `self::` seguiría resolviendo a la
> lista del PADRE, y el código específico del módulo se degradaría A SILENCIO.** Sin error,
> sin aviso: la excepción se lanza igual y llega con el código equivocado.

**Requisito**: `static::CODES` —enlace estático tardío—, y **una prueba con una subclase que
añade un código propio** y comprueba que sobrevive al constructor. Sin esa prueba el
requisito es una intención.

Los dos módulos que hoy añaden códigos son los que la prueba tiene que reproducir:
`PiecesPHP\UserSystem` (`USER_NOT_EXISTS`) y `Publications` (`PUBLICATION_CODE`,
`CATEGORY_CODE`).

#### 2 · Antes de centralizar hace falta la SEXTA comprobación de integridad

**Toda clase nombrada en un `catch` debe resolver a una clase existente.**

Sin ella la centralización **no se puede hacer con seguridad**, porque el modo de fallo es
mudo: cada módulo captura su clase por el nombre corto que resuelve su `use` de cabecera, y
si uno se queda sin actualizar, **PHP no falla — el `catch` simplemente deja de capturar** y
la excepción sube hasta el manejador global. Un 500 en vez de un mensaje de validación, y
nada que lo explique.

La superficie, medida:

| | |
| :-- | --: |
| Sitios que capturan `SafeException` | **35** |
| Sitios que capturan `DuplicateException` | **12** |
| `throw new` de las dos | **48** |
| Archivos que las importan con `use` | **25** |

**No la cubre ninguna comprobación actual.** La tercera valida que las clases DECLARADAS se
carguen, y su propio docblock ya avisa del hueco: *«un `use` que falta y solo se referencia
dentro del cuerpo de un método se le escapa»* — que es exactamente este caso.
### La fusión `DataImportExportUtility` + `Importers` es una REFACTORIZACIÓN PLANIFICADA

**No borres ninguno de los dos.** El reparto de piezas:

- **`PiecesPHP\Core\Importer` (1.586 líneas, en el núcleo) SE QUEDA.** Es el motor
  extensible y no es código de módulo.
- **`Importers` SE QUEDA.** Es la única implementación viva del motor.
- **`DataImportExportUtility` se FUSIONA en `Importers`**, no se elimina: hay que llevarse
  lo que aporta antes de que desaparezca su carpeta.

Quien llegue después y vea `DataImportExportUtility` en una lista de «módulos a limpiar»
tiene que leer esto primero. Borrarlo sin fusionar pierde trabajo que nadie va a echar de
menos hasta que haga falta.

### Cuánta cola se lleva el filtro

De los **157** errores de la familia `false` (el conteo anterior de 148 salía de la tabla
truncada de PHPStan y estaba contaminado, ver T2):

| Destino | Errores | Archivos |
| :-- | --: | --: |
| Se borra completo — `ApplicationCalls` | 8 | 3 |
| Se borra completo — `ImagesRepository` | 4 | 1 |
| Se borra completo — `InterestResearchAreas` | 3 | 2 |
| Se reescribe — `DataImportExportUtility` | 2 | 2 |
| Se reescribe — `Importers` | 1 | 1 |
| **Trato completo** | **139** | **52** |

**15 errores mueren con su módulo sin que nadie los toque.** Ninguno de los tres módulos
de borrado parcial aporta errores a esta familia.

## T7 · Ventana de pruebas de correo — **planificada, no empezada**

Nueve o diez sitios envían correo. **Medido: 10 llamadas en 7 archivos** —
`RecoveryPasswordController` tiene 3 y `UserProblemsController` 2, de ahí que un recuento
por archivo dé menos. Transporte vivo: **`phpmailer/phpmailer` v7.1.1**. Ya existe
`Mailer::checkSMTP(string $host, int $port)` como sonda, y `checkSettedSMTP()` para saber
si hay configuración.

| Archivo | Sitios |
| :-- | --: |
| `controller/RecoveryPasswordController.php` | 3 |
| `controller/UserProblemsController.php` | 2 |
| `classes/API/Controllers/APIController.php` | 1 |
| `classes/PiecesPHP/UserSystem/Authentication/OTPHandler.php` | 1 |
| `classes/SystemApprovals/Controllers/SystemApprovalsController.php` | 1 |
| `controller/ContactFormsController.php` | 1 |
| `controller/GenericTokenController.php` | 1 |

### Tres capas, y solo la tercera necesita a un humano

**1 · Composición — el grueso del trabajo.** Plantilla, sustitución de variables, grupo de
traducción correcto, y enlaces generados con `get_route()` y no concatenados. Suite normal
del framework, **sin red**: se sustituye el transporte por uno que capture el mensaje en vez
de enviarlo. Aquí cabe casi todo lo que de verdad se rompe.

**2 · Transporte — sumidero SMTP local.** Mailpit o MailHog. Tienen API HTTP, así que la
suite **afirma sobre el mensaje recibido sin que nadie mire**. Es la capa que demuestra que
el sobre sale bien formado, no solo que el cuerpo se compuso bien.

**3 · Entrega real — un puñado de casos.** Buzones temporales de mailinator, autorizados por
el propietario, que confirma la recepción por ojo. Solo lo que no se puede afirmar de otra
forma.

### REGLA DE SEGURIDAD — INNEGOCIABLE

**Los buzones gratuitos de mailinator son PÚBLICOS.** Cualquiera los lee adivinando el
nombre; no hay contraseña que valga.

**Tres de los emisores mandan credenciales vivas:**

| Emisor | Qué manda |
| :-- | :-- |
| `OTPHandler` | Código de un solo uso **que autentica por sí solo** — ver T3: la condición de login es `password_verify(...) \|\| $otpIsValid` |
| `RecoveryPasswordController` | Enlace de recuperación de contraseña |
| `GenericTokenController` | Token genérico |

Esos tres van a mailinator **SOLO con cuentas desechables y sin privilegios**. Nunca con una
cuenta real, y nunca con una administrativa. Un código de un uso publicado en un buzón
público no es una prueba: es una toma de cuenta esperando a que alguien pase por ahí.

Las capas 1 y 2 **no tienen este problema** y por eso deben absorber todo lo que puedan: lo
que se pueda afirmar sin salir de la máquina, no sale de la máquina.

### Lastre detectado de paso

`Core/Email/Mailgun.php` y `Core/MailjetHandler.php` tienen **cero consumidores** — verificado
por búsqueda en todo `src/app`. Mismo patrón que `scssphp` y `mpdf`: integraciones que se
dejaron a medias y nadie retiró. Pertenecen a `14-deuda-y-limpieza.md`; se anotan aquí porque
salieron al inventariar los emisores, y porque **decidir su suerte antes de escribir las
pruebas evita escribir pruebas para código que se va a borrar** — el mismo criterio de T6.

## T8 · CONTABILIDAD DE LOS ERRORES `false` — **fuente única, se actualiza, no se recalcula**

Las cifras se han movido tres veces (148 → 144 → 157) y cada movimiento obligó a
re-verificar. Esta tabla corta ese gasto: **es la única fuente**. Cuando se resuelva un
grupo, se edita aquí; no se vuelve a contar desde cero.

### Cómo mapea el 148 viejo al 157

| | |
| :-- | :-- |
| **148** | Medido parseando `PHPStanResult.txt`, la tabla. **No es reproducible ni reconciliable exactamente**: las rutas venían recortadas a 73 caracteres, así que había errores mal atribuidos y errores descartados por apuntar a archivos inexistentes. |
| **157** | Medido sobre `PHPStanResult.json` en la MISMA ejecución de 968 errores. Salida de máquina, sin recorte. |
| **Diferencia** | +9. No se reconstruye la procedencia exacta de cada uno: la medición vieja se hizo sobre una salida corrupta y fabricar una reconciliación sería inventarla. **El 148 queda derogado.** |

**La cifra de partida autorizada es 157.**

### ¿Sigue siendo comparable el baseline?

**Sí en lo que importa, no en todo.** El recorte afectaba a la cabecera de archivo, nunca
a las filas de error: la misma ejecución da **968 leída como tabla y 968 leída como JSON**.
Lo que sí venía mal era el conteo de ARCHIVOS —192 en vez de 195—, porque cuatro vistas de
`ContentNavigationHub` comparten los primeros 73 caracteres de su ruta y colapsaban en una.

`PHPStanResult.Summary.baseline.txt` lleva ahora una nota de medición con esto y el conteo
de archivos corregido. **El total de errores no se regenera: 968 nunca fue otro número.**

### El método de la columna «Con `false`» — escrito, porque no lo estaba

El «157» se anotó como «medido sobre `PHPStanResult.json`», que es **descriptivo, no
reproducible**: no dice qué se contaba dentro del JSON. Bajo el punto 5 de T0 eso ya no
vale. La expresión autorizada de aquí en adelante:

```bash
php -r '$j=json_decode(file_get_contents("PHPStanResult.json"),true);
$n=0; foreach($j["files"] as $d) foreach($d["messages"] as $m)
if (str_contains($m["message"],"false")) $n++; echo $n,"\n";'
```

Cuenta **instancias** cuyo mensaje menciona `false`. Hoy da **100**, repartidas en cinco
identificadores —`argument.type` (74), `method.nonObject` (14), `return.type` (6),
`foreach.nonIterable` (4), `assign.propertyType` (2)—, todos coherentes con «un `false` se
coló en un valor».

**Advertencia de comparabilidad:** las filas anteriores de la tabla (157 → 107) se midieron
antes de fijar esta expresión, así que **son indicativas, no aptas para el trinquete**. La
serie apta empieza aquí. No se reconstruyen hacia atrás: sería inventar la reconciliación,
el mismo error que derogó el 148.
### Recorrido, medido commit a commit

| Commit | Errores *(instancias)* | Con `false` *(instancias)* | Qué resolvió |
| :-- | --: | --: | :-- |
| `89030cb6` | 968 | **157** | *(punto de partida bajo el pipeline corregido)* |
| `61a0a474` | 968 | 157 | D2 — nulabilidad, no `false` |
| `14d0886a` | 950 | 157 | Migración fuera de `routes()` |
| `6a33b77e` | 933 | **140** | Retipar `PDO` → `Database` (−17) |
| `7f5f00a2` | 916 | **123** | `JSON_THROW_ON_ERROR` (−17) |
| `7ac91445` | 907 | **114** | Familia `strpos` (−9) |
| `d6924b39` | 900 | **107** | `realpath` y `createFromFormat` (−7) |

**Resueltos: 50 de 157. Quedan 107.** Todas las cifras de esta tabla son **instancias**,
no tripletas: se midieron sobre `totals.file_errors` y sobre el conteo de mensajes, sin
deduplicar. La serie apta para el trinquete empieza donde el método quedó escrito, más
arriba.

### Los 107 que quedan, por grupo y destino

Las columnas de abajo cuentan **instancias** agrupadas por función de origen, no archivos:

| Grupo | Trato completo | Se reescribe | Se borra | Total |
| :-- | --: | --: | --: | --: |
| `file_get_contents` / `file` | 44 | 3 | 7 | **54** |
| `DateTime` / `strtotime` | 11 | 0 | 3 | **14** |
| Retorno propio declarado con `\|false` | 12 | 0 | 1 | **13** |
| GD (`imagecreate*`, `imagecolor*`) | 11 | 0 | 1 | **12** |
| Recursos (`fopen`, `opendir`) | 5 | 0 | 0 | **5** |
| `readdir` | 2 | 0 | 0 | **2** |
| `finfo_file` | 1 | 0 | 1 | **2** |
| `fputcsv` | 1 | 0 | 0 | **1** |
| `stream_get_contents` | 1 | 0 | 0 | **1** |
| Otros | 1 | 0 | 2 | **3** |
| **TOTAL** | **89** | **3** | **15** | **107** |

**15 mueren con su módulo y no reciben nada** (ver T6). Los 3 de reescritura solo reciben
atención si son defecto real, no si son un tipo mal declarado. **El trabajo real son 89.**

### Dónde mirar primero

- **Los 12 «retorno propio con `|false`»** son la apuesta principal. Un retorno propio que
  declara `|false` es **una decisión nuestra, no una herencia de PHP**: lo probable es que
  la firma sea lo que está mal, no el sitio de llamada.
- **Los 11 de `DateTime`/`strtotime`** son los siguientes en probabilidad de esconder un
  defecto: una fecha mal parseada no revienta, produce un valor equivocado.
- **Los 44 de `file_get_contents`** son grupo A —el manejador de `bootstrap.php` promueve
  el `E_WARNING` a excepción, así que abortan en vez de devolver `false`—, de modo que son
  volumen mecánico, no caza.

## T9 · Rector — lote 1 aplicado, y las 98 reglas que faltan

Tras arreglar el truncado (T2) y excluir los módulos condenados (T6), Rector ve **175
archivos**. El primer lote —17 reglas mecánicas en 103 archivos— ya está aplicado.

**Quedan 98 reglas en 103 archivos.** No se aplican en bloque: están dominadas por
categorías que cambian comportamiento observable, y en este código base concreto hay dos
trampas que las hacen peligrosas.

### Trampa 1 · Los tipos de retorno y la covarianza

| Regla | Archivos |
| :-- | --: |
| `AddVoidReturnTypeWhereNoReturnRector` | 37 |
| `StringReturnTypeFromStrictStringReturnsRector` | 35 |
| `ClosureReturnTypeRector` | 32 |
| `ReturnTypeFromStrictParamRector` | 32 |
| `BoolReturnTypeFromBooleanStrictReturnsRector` | 28 |
| `AddArrowFunctionReturnTypeRector` | 19 |
| `ReturnTypeFromStrictNewArrayRector` | 18 |
| `AddClosureVoidReturnTypeWhereNoReturnRector` | 17 |
| `ReturnTypeFromStrictFluentReturnRector` | 14 |
| `ReturnUnionTypeRector` | 10 |

**Es la misma lección que costó la minor de `piecesphp/database` v3.2.0**: estrechar el
retorno de un método rompe a quien lo hereda **al declarar la clase, no al llamarla**. Y
este código base es especialmente vulnerable: `routeName()` y `allowedRoute()` están
**copiadas en 36 controladores** a propósito (ver `07-modulos.md`), y los buscadores de
mapper están copiados en 26. Si Rector tipa una copia y no las demás, el fallo aparece al
cargar la clase, lejos del cambio.

**Antes de aplicar cualquiera de estas hay que comprobar, por cada método tocado, si
alguien lo redeclara.**

### Trampa 2 · Las propiedades tipadas contra `__get`/`__set`

| Regla | Archivos |
| :-- | --: |
| `RemoveNullPropertyInitializationRector` | 40 |
| `TypedPropertyFromStrictConstructorRector` | 29 |
| `ClassPropertyAssignToConstructorPromotionRector` | 12 |

Las tres están **explícitamente en el `skip()` del Rector de `piecesphp/database`**, y por
un motivo que aplica igual aquí: los mappers usan `__get`/`__set` con intensidad. Una
propiedad tipada **sin inicializar lanza `Error` al leerla** en vez de devolver `null`, y
quitar la inicialización `= null` de una propiedad tipada la deja justo en ese estado.
Cambia el comportamiento en tiempo de ejecución sin tocar ninguna línea de lógica.

**Propuesta: alinear el `skip()` de este repositorio con el del paquete.**

### El resto, por decidir

| Regla | Archivos | Por qué no es automática |
| :-- | --: | :-- |
| `RemoveUselessParamTagRector` | 83 | Retira `@param`/`@return` en masa. Es una decisión de estilo, no una corrección; el Rector del paquete la salta. |
| `RemoveUselessReturnTagRector` | 72 | Igual. |
| `SimplifyUselessVariableRector` | 61 | Reestructura flujo; seguro en general, conviene lote propio. |
| `UseIdenticalOverEqualWithSameTypeRector` | 55 | `==` → `===`. Si la inferencia de tipo falla, cambia el resultado. |
| `RemoveAlwaysElseRector` | 52 | Reestructura flujo. |
| `ClosureToArrowFunctionRector` | 49 | Cambia la semántica de captura. |
| `SimplifyEmptyCheckOnEmptyArrayRector` | 48 | `empty($a)` → `$a === []`; difieren si `$a` no es array. |
| `NewMethodCallWithoutParenthesesRector` | 36 | Emite **sintaxis de PHP 8.4** (`new Foo()->bar()`). Legal en el piso actual, pero es un cambio visual grande y ata al piso. |
| `RemoveUnusedVariableAssignRector` | 35 | Borra según inferencia: si falla, borra código vivo. |
| `RemoveAlwaysTrueIfConditionRector` | 25 | Igual, sobre condiciones. |

## T10 · NO SE EDITA PHP POR LÍNEA, SE EDITA POR ESTRUCTURA

> **PRECISIÓN QUE A ESTA REGLA LE FALTABA, Y ES LA QUE LA VUELVE ÚTIL: ES LA PRIMERA OPCIÓN,
> NO EL PLAN B.** Los cuatro incidentes de abajo comparten algo peor que la causa técnica:
> **en los cuatro se empezó por el atajo y se llegó a la estructura DESPUÉS de romper algo.**
> La regla escrita como diagnóstico —«esto pasó por editar por posición»— no impide el quinto;
> escrita como orden de preferencia, sí.
>
> **Si la herramienta estructural cuesta veinte minutos más, cuesta menos que revertir
> dieciocho archivos.** Y ese no es un cálculo optimista: es exactamente lo que pasó en el
> cuarto incidente, y el tiempo de escribir el contador de llaves fue menor que el de
> diagnosticar el estropicio.

**Cuatro incidentes de la misma familia, todos silenciosos:**

| Incidente | Causa | Qué lo delató |
| :-- | :-- | :-- |
| `OrganizationMapper` con un docblock sin cerrar | Un script partió por `\r\n` un archivo con 1.342 CRLF **y un LF suelto**: todos los índices siguientes se desplazaron | Nada automático. Se encontró a mano |
| 32 falsos positivos en `verify-integrity` | Contaba `/*` contra `*/` en texto plano, y `'image/*'` aparece dentro de cadenas | La propia revisión de los resultados |
| `SyncOTPRecordsTask` sin `namespace` ni `use` | Un corte por posición casó con el docblock **de archivo** en vez del **de clase** y se llevó la cabecera | Ejecutar la tarea |
| **18 mappers rotos** al borrar el hueco de plantilla `foreach.emptyArray` | Un `preg_replace` con `(.*?)` y `/s` para casar el cuerpo de un bloque: **la expresión regular no sabe dónde cierra una llave** y se llevó el `}` equivocado | **`php -l`, esta vez sí** — se comprobó archivo a archivo antes de commitear, y salió en rojo |

**La regla: cuando haya que preguntarle algo al código fuente, se tokeniza.** `token_get_all()`
lleva cuatro apariciones —`verify-integrity` en dos comprobaciones,
`unit-tests:core/otp-write-separation`, y `declaredClass()`—. Es patrón del proyecto.

Y su corolario, que vale igual: **cortar por índice de cadena es editar por posición**. El
tercer incidente no partió por líneas, partió por `str.index()`, y falló por la misma razón:
buscó una silueta —`/**\n * SyncOTPRecordsTask.`— que casaba dos veces.

**El cuarto añade el caso de la expresión regular, que es el más tentador de todos** porque
parece estructura y no lo es: `foreach \(…\) \{(.*?)\}` *parece* que casa un bloque. No casa un
bloque — casa **hasta la primera llave de cierre que le cuadre al motor**, que en código
anidado no es la del bloque. Reescrito contando llaves, los 20 archivos salieron bien a la
primera.

> **La diferencia entre las dos herramientas no es la elegancia, es qué saben:** un contador de
> llaves —o `token_get_all()`— sabe qué es un bloque; una expresión regular sabe qué es una
> silueta de texto. **Cuando la pregunta es «¿dónde termina esto?», la silueta no puede
> responder**, y responderá igualmente con algo que parece razonable.
### La tercera comprobación de integridad

`bin/cli verify-integrity` comprueba ahora, además de docblocks y firmas, que **toda clase
bajo una raíz PSR-4 se llame como su ruta manda y se pueda cargar**:

1. **Ruta contra namespace.** El FQCN esperado sale de la RUTA; el declarado, del archivo.
   Es la única forma de detectar un `namespace` perdido — derivar el nombre del propio
   archivo es circular y no detecta nada. *(Primer intento hecho así: no detectaba nada.)*
2. **Carga real** con `class_exists($fqcn, true)`, que atrapa un padre, interfaz o trait
   que no resuelve.

**`composer dump-autoload --strict-psr` NO sirve para esto, y conviene saber por qué:** el
`psr-4` de `src/composer.json` declara únicamente `PiecesPHP\Core\`. Todo `src/app/classes`
—donde vive la mayoría del código propio— lo resuelve el autoloader del framework
(`config/autoloads.php`), que registra esa carpeta como raíz **sin prefijo**. Composer no ve
nada de ahí. Por eso la comprobación se implementa directamente, con las dos raíces en
`VerifyIntegrityTask::PSR4_ROOTS`. **Si se añade una raíz nueva, va ahí o deja de
comprobarse.**

**Probada contra el fallo real**, no solo escrita: una clase con el namespace equivocado da
`declara Espacio\Equivocado\Broken y su ruta exige ZzIntegrityProbe\Broken`, y un padre
inexistente da `Class "…" not found`. Las dos con salida 1.

### LO QUE ESTA PUERTA NO ATRAPA

- **Un `use` que falta y solo se referencia dentro del cuerpo de un método.** La clase se
  declara y se carga sin problema; el fallo aparece al ejecutar esa línea. Eso solo lo caza
  una prueba que la ejecute.
- **Clases que se cargan durante el arranque** —tareas de terminal, mappers referenciados
  al registrar rutas—. Si una de esas se rompe, el CLI muere ANTES de que la puerta corra.
  No es grave: el fallo es ruidoso e inmediato. Pero significa que la puerta protege sobre
  todo el código de carga tardía, que es justo donde el fallo sí sería silencioso.

## T11 · `declare(strict_types=1)` en CERO archivos — la raíz que explica la familia

**Medido: 0 de 782 en `piecesphp`, 0 de 61 en los cuatro paquetes.**

Ahí está el porqué estructural de toda la familia `false`. Sin `strict_types`, un `false` que
entra donde se espera `string` se convierte en `''` **en silencio**:

| Sitio | Qué producía |
| :-- | :-- |
| `sha1(json_encode($checksumData))` | `sha1('')` — todo dato corrupto con el MISMO ETag |
| `setDataCache(json_encode($result), …)` | Un archivo de cero bytes servido como caché válido |
| `base64_encode(json_encode(...))` | Una carga vacía con apariencia de dato |

**No son tres accidentes: son el mismo mecanismo tres veces.**

### NO SE HACE UN BARRIDO

Activarlo en código existente cambia la coerción de **todas** las llamadas de ese archivo, y
un `"5"` donde se espera `int` empieza a lanzar. Cambiaríamos 843 riesgos silenciosos por un
número desconocido de fallos ruidosos, de golpe y sin red.

### La regla, desde hoy

**Todo archivo NUEVO lleva `declare(strict_types=1)`.** Cuesta cero en un archivo que se
escribe con la regla puesta.

### La ventana para el código existente

De uno en uno, **empezando por los archivos que tengan cobertura de pruebas**, y nunca en
lote. El orden natural es: primero lo que cubren las suites del framework y la del
exportador; después el núcleo; los módulos, al final, y los condenados nunca.

## T12 · `routeName` / `allowedRoute` — **CERRADO**: medido, normalizado y unificado en dos traits

**La duplicación era deliberada; las diferencias no.** Ese es el corte que decide el lote.

### Medición por tokens (no por sangría)

| Método | Cuerpos distintos ANTES | Archivos | Cuerpos DESPUÉS del paso B |
| :-- | --: | --: | --: |
| `routeName` | 10 | 44 | **9** (dominante: 21 → **26** archivos) |
| `allowedRoute` | 5 | 38 | **5** (dominante: 22 → **28** archivos) |
| `_allowedRoute` | **26** | 32 | 26 (no se toca) |

**La estimación anterior con `awk` se quedaba CORTA en `routeName`** —decía 6, son 10—, no
inflada como se temía. Cortar en la primera llave a cuatro espacios pierde cuerpos con
bloques anidados en vez de inventarlos.

### Diferencias clasificadas

**DERIVA — normalizadas en el paso B, todas demostrablemente equivalentes:**

| Forma encontrada | Forma canónica | Por qué es equivalente |
| :-- | :-- | :-- |
| `!is_null($name) ? $name : ''` | `$name ?? ''` | Idénticas por definición de `??` |
| `strlen($name) > 0` | `$name !== ''` | Va tras `trim()`: `$name` es string |
| `mb_strlen($name) > 0` | `$name !== ''` | `mb_strlen` vale 0 solo con cadena vacía |
| `strlen($route) > 0` | `(string) $route !== ''` | Mismo predicado |
| `is_string($route) ? $route : ''` | `!is_string($route) ? '' : $route` | Ternario invertido |

**Parte de esa divergencia la introdujo el lote 1 de Rector**: antes había 39 archivos con
`strlen($route) > 0`; Rector convirtió 25 y dejó 14, porque solo pudo probar el tipo en
esos. Un lote mecánico partió en dos un grupo homogéneo. El paso B lo deshace.

**INTENCIONAL — se quedan como están, y el trait NO las cubre:**

| Grupo | Archivos | Qué hace distinto |
| :-- | :-- | :-- |
| Controladores públicos | `PublicationsPublicController`, `ApplicationCallsPublicController`, `BuiltInBannerPublicController`, `GoogleReCaptchaV3Controller`, `FileManagerController`, `AppConfigController` | **No llaman a `_allowedRoute()`**: devuelven la ruta sin pasar por el hook |
| `App/Locations/*` (5) | `City`, `Country`, `Point`, `Region`, `State` | Prefijo de **dos niveles**: `$prefixParentEntity . '-' . $prefixEntity` |
| `ContactFormsController`, `PublicAreaController` | 2 | Usan `self::$prefixNameRoutes` en vez de `self::$baseRouteName` |
| `TerminalController` | 1 | **Otra firma**: `routeName(?string, bool)` sin `$params`, y usa `self::routeID()` |
| `DataImportExportUtilityController` | 1 | `get_config('current_user')` y `!= false` en vez de `getLoggedFrameworkUser()` y `!== null`. Legado; el módulo se reescribe (T6) |

**`TerminalController` merece una nota**: su `allowedRoute` llama a `self::routeName($name, true)`
con dos argumentos, lo que parece un error hasta que se mira su firma — que efectivamente
tiene dos parámetros. **Es coherente consigo mismo, no un defecto.**

### El destino aprobado: `RouteNamingTrait`

`_allowedRoute()` como **hook `protected` con implementación por defecto `return true;`**.

El razonamiento: hoy quien clona un módulo copia sesenta líneas sin saber cuáles debe tocar,
porque son todas ruido idéntico. Con el trait, lo único escrito en su controlador es
`_allowedRoute()` — **exactamente la parte que sí debe pensar**. La convención no se pierde,
se afila.

Es **trait y no clase base** porque los controladores públicos extienden `BaseController`,
no `AdminPanelController`: componen en vez de heredar. Dentro del trait, `self::` y `static::`
siguen resolviendo a la clase que lo usa, así que las constantes por módulo siguen
funcionando.

### Pasos pendientes

- **C.1 · HECHO.** Dos traits en `PiecesPHP\Core\Routing`: `RouteNamingTrait` —`routeName()`
  más el hook `_allowedRoute()` con `return true;` por defecto— en los **44**, y
  `RouteGuardTrait` —`allowedRoute()`— en los **38** que exponen guardián. Los otros seis
  nombran rutas y no guardan: los cinco de `App\Locations` y `ContactFormsController`.

  **Medido con C.1 solo, antes de borrar nada: 877 instancias, 868 tripletas, 190 archivos.
  Idéntico al estado anterior, sin mover un punto.** Suites 20/20, 13/13, 6/6, 12/12.

  El hook vive en el trait de NOMBRADO y no en el de guarda por funcionamiento, no por
  gusto: `routeName()` lo llama siempre, así que un controlador que usara el trait sin
  declararlo tendría un fatal.

- **C.2 · HECHO.** Borradas **26** copias de `routeName` y **28** de `allowedRoute`. El
  criterio fue **identidad por TOKENS contra el cuerpo del trait** —firma incluida,
  comentarios fuera—, comprobada archivo a archivo justo antes de borrar; la herramienta se
  niega y deja el archivo intacto si no coincide. Por eso **18 y 10 se conservaron**, y son
  exactamente los grupos no dominantes ya clasificados arriba.

  **Después de borrar 54 métodos: 877 / 868 / 190 otra vez.** Con **606 sitios de llamada**
  a `routeName(` / `allowedRoute(` en el código, que PHPStan resuelve en nivel 8: si alguno
  hubiera quedado sin método, habría salido como `staticMethod.notFound`. Los dos únicos
  `method.notFound` del informe son de `TerminalController` y ya estaban.

  `verify-integrity` reportó **54 firmas desaparecidas**, que es justo `26 + 28`. La
  instantánea se regeneró: las firmas no desaparecieron, se mudaron al trait.

- **C.3** *(no autorizado)* Las variantes intencionales conservan su método local. **Su `use`
  del trait no las cambia**: el método declarado en la clase gana siempre. Comprobado en
  aislamiento, no supuesto — una clase con método propio devuelve el suyo y una sin él, el
  del trait.
- **D** Tipar. **`AdminPanelController` es base de varios controladores: la base y todos sus
  descendientes se tipan EN EL MISMO COMMIT.** Si se tipa la base y un hijo redeclara sin
  tipo, el hijo ensancha y **PHP falla al declarar la clase**, no al llamarla.

### SEGUNDA PASADA — el criterio de la primera era el equivocado

**«Idéntico por tokens al cuerpo del trait» dejó vivas dieciséis copias que no hacían nada.**
Era un criterio de FORMA, y el andamio no se distingue por la forma: se distingue por si
decide algo.

**El criterio nuevo es una sola pregunta: ¿ESTE MÉTODO DECIDE ALGO?**

| Método | Copias borradas · 1.ª pasada | Copias borradas · 2.ª pasada | Sobreviven |
| :-- | --: | --: | --: |
| `routeName` | 26 | **9** | **9** |
| `allowedRoute` | 28 | **9** | **1** |
| `_allowedRoute` | 0 | **17** | **15** |
| **TOTAL** | 54 | **35** | **25** |

**89 copias borradas entre las dos pasadas.**

Lo que las diecisiete de `_allowedRoute` tenían dentro: `$allow = $route !== ''` con
variaciones de forma —`strlen($route) > 0`, `(string) $route !== ''`—, un closure `$getParam`
que nadie llamaba, `$currentUserType` y `$currentUserID` asignados y **jamás leídos**, y un
`if` de relleno comparando contra `'SAMPLE'`, `'sample'` o `'NOMBRE_RUTA'`.

> **No eran variantes de un comportamiento. Eran estratos de una plantilla y huecos que nadie
> rellenó.**

#### El susto de `'SAMPLE'`, que casi conserva cuatro cuerpos muertos

Hay literales `'SAMPLE'` **vivos** en `view/webflow/layout/menu.php`, llamando a
`genericViewRoute('SAMPLE')`. Parecía que la rama sí se alcanzaba.

**No se alcanza:** esa función pasa `'SAMPLE'` dentro de `$params['name']` y llama a
`PublicAreaController::routeName('generic', …)`, así que `$name` vale `'generic'`. Y
comprobado además que **ninguna llamada del proyecto pasa `'SAMPLE'`, `'sample'` ni
`'NOMBRE_RUTA'` como primer argumento** de `routeName()` o `allowedRoute()`.

Sin esa comprobación se habrían conservado cuatro cuerpos muertos por miedo. **T20 en la
dirección contraria: una medición que asusta también hay que verificarla.**

#### Los tres traits pasan a ser UNO

`ControllerRoutingTrait`, con los tres métodos. La separación entre nombrado y guarda **era
una frontera inventada**: `routeName()` llama SIEMPRE a `_allowedRoute()`, y `allowedRoute()`
no hace más que preguntarle a `routeName()` si devolvió cadena.

#### La puerta: `KNOWN_ROUTE_OVERRIDES`

Quinta comprobación de `verify-integrity`. **Tres direcciones, las tres probadas
provocándolas:**

| Dirección | Mutación | Resultado |
| :-- | :-- | :-- |
| Declaración sin registrar | Añadir un `allowedRoute()` a `NewsController` | Salida **1**, nombrando `News\Controllers\NewsController::allowedRoute` |
| Entrada que deja de decidir | Vaciar el `_allowedRoute` de `SystemApprovals` | Salida **1**: *«está registrado pero YA NO DECIDE NADA»* |
| Entrada cuya declaración no existe | Meter `Zz\Sonda::routeName` en el registro | Salida **1**: *«ya no se declara»* |

El veredicto lo da **el mismo clasificador con el que se construyó el registro**, de modo que
la puerta no puede separarse del criterio. Y es **conservador a propósito**: solo dice «no
decide» para un conjunto cerrado de formas: un falso «decide» deja un método de más, un falso
«no decide» **borra una regla de autorización**.

**Lo que la puerta NO atrapa**, escrito en su propio docblock: el clasificador razona sobre
el cuerpo, no sobre quién llama. Un `if ($name == 'SAMPLE') { $allow = false; }` cuenta como
que decide aunque ninguna ruta se llame así.

#### Efecto medido sobre las ramas muertas

**373 → 325 tripletas, −48.** *Menos de las 89 que se habían anunciado*, y la razón es que
**89 contaba el andamio de los 32 `_allowedRoute`, y quince sobreviven** porque deciden. De
los que quedan dentro de los métodos supervivientes: **41 tripletas**, casi todas el
`isset($_POST)` / `isset($_GET)` del closure `$getParam` — que **9 de los 10 que lo declaran
sí usan**, así que ahí lo que sobra es el `isset`, no el closure.
## T13 · Ramas muertas — TRIAJE HECHO y VUELTO A MEDIR; supresión NO autorizada

**Son 464, no ~357.** Medidas destapando los 27 identificadores silenciados en `phpstan.neon`:
1.347 errores con ellos fuera contra 878 con ellos dentro.

### Un dato que reduce el miedo

**`treatPhpDocTypesAsCertain: false` está activo.** PHPStan **no se fía de nuestros
docblocks** para decir «siempre verdadero»: solo usa tipos nativos e inferencia real. Eso
recorta estructuralmente la clase (b) —«el tipo declarado miente»— porque los tipos nativos
los impone PHP en ejecución.

Donde (b) **sí** puede darse en este código base es donde la inferencia es incompleta por
diseño: `__get`/`__set` de los mappers, y variables inyectadas en vistas por `extract()`.

### Reparto estimado

| Clase | Cantidad | % | Trato |
| :-- | --: | --: | :-- |
| **(a)** Resto defensivo de la era PHP 5/7 | **300** | 64,7 % | Se borra la rama |
| **(b)** Vistas y `extract()`, variable inyectada | **105** | 22,6 % | **No tocar**: la variable existe, PHPStan no puede verlo |
| **(b)** Clase con `__get`/`__set` o mapper | **59** | 12,7 % | **Mirar una a una**: aquí es donde la rama puede estar protegiendo de la realidad |

**Dos tercios son (a).** El tercio restante no se suprime en bloque: en las vistas la
supresión ya existe por ruta y es correcta; en los mappers cada una es una pregunta.

Archivos con más ramas de la clase magia: `ImagesRepositoryMapper` (4, **condenado**),
`BuiltInBannerMapper` (4), `ApplicationCallsMapper` (3, **condenado**), `CategoriesMapper` (3),
`DocumentTypesMapper` (3), `NewsMapper` (3).

### APLICADO · `dynamicConstantNames`, con el delta contado aparte

**No son 27 constantes: son 25.** El «27» eran los **identificadores** de ignore del bloque
de código muerto, que es otra cosa. Las constantes, con su comando:

| Cifra | Comando |
| --: | :-- |
| **24** interruptores booleanos | `grep -oE "define\('([A-Z0-9_]+)',\s*(true\|false)\)" src/app/config/constants.php` |
| **+1** `ORGANIZATIONS_MODULE` | Se define desde `CRITICAL_CONSTANTS`, no con un literal, así que el patrón anterior no la ve |
| **27** identificadores de ignore | Las reglas del bloque son 28: 27 `identifier` y un `message` (`left side of ?? always exists`) |

**Esto NO es una supresión.** No oculta un error: le dice a PHPStan la verdad, que el valor
de esas constantes no se conoce en análisis porque **cada despliegue las configura**. Por eso
no lleva condición de retirada — la única que la retiraría es que el framework dejase de ser
una plantilla que se clona.

**Los cuatro paquetes NO llevan el bloque.** No ven `constants.php` y **ninguno menciona
estas constantes**: `grep -rlE "PUBLICATIONS_MODULE|NEWS_MODULE|…"` sobre los cuatro `src/`
da **cero archivos**. Ponérselo sería escribir en un registro algo que allí no significa
nada, que es justo lo que el punto 3 de T0 quiere evitar.

#### El delta, contado aparte

| Medición | Antes | Después | Delta |
| :-- | --: | --: | --: |
| **Baseline visible** (instancias / tripletas) | 877 / 868 | **877 / 868** | **0** |
| Ramas muertas, instancias | 470 | **373** | **−97** |
| Ramas muertas, tripletas | 465 | **373** | **−92** |

**El baseline visible no se mueve, y tenía que no moverse**: esas ramas ya estaban
silenciadas por el bloque de ignores, así que retirarles el veredicto falso no cambia nada
de lo que se ve. **El movimiento entero vive en la medición que hoy está tapada.**

Contraste con la estimación previa, que aguanta: la muestra decía **21,7 %** de la clase (a);
la medición da **92 de 465 = 19,8 %** del conjunto entero. Dos instrumentos distintos y el
mismo orden de magnitud.

**Método, para que la cifra sea comparable la próxima vez:**

```bash
bin/phpstan-deadcode
```

Deriva la configuración sin el bloque a partir de `bin/phpstan.neon` en cada ejecución —no
la duplica, así que no puede quedarse atrás— y resta las dos corridas. Cuenta **tripletas**
(ruta, línea, mensaje).

#### Un susto que era del instrumento, otra vez

La primera lectura dio **877 → 878** y estuvo a un paso de escribirse como «destapar las
constantes sacó a la luz un error nuevo». **No era eso.** El +1 era un error de mi propia
suite recién añadida —`UnitTest-MetaPropertyHybrid.php:70`, un `ReflectionClass` sobre un
`string` sin estrechar—, que entró en la medición por estar bajo `src/app`.

El segundo método que lo destapó: correr con una copia de la configuración **sin** el bloque
de constantes dinámicas. Dio **869 tripletas en las dos direcciones**, así que el cambio no
podía ser el culpable. **T20, la quinta vez.**

### El reparto cambió solo, como se sospechaba — el «235» queda derogado

| Familia | Antes *(estimado por muestra)* | Ahora *(medido por ruta)* |
| :-- | --: | --: |
| **Total** | 465 | **373** |
| Vistas y `extract()` | 105 | **22** |
| Mappers | 59 | **43** |
| Suites de caracterización | — | **3** |
| Resto | ~300 | **305** |

> **Cuidado con las columnas: no son el mismo instrumento.** La de la izquierda es una
> estimación por muestreo; la de la derecha, un conteo por ruta de archivo. **Solo el total
> es comparable.** El **235 «borrables sin tocar configuración» queda derogado**: nacía de
> restar 65 a 300, y ninguna de las dos cifras sobrevive a la medición nueva.

Las de arriba por identificador, que es por donde conviene atacarlas —**por familia entera,
nunca sueltas** (T17):

| Instancias | Identificador | Qué es |
| --: | :-- | :-- |
| **129** | `function.alreadyNarrowedType` | `is_array()` sobre un `array`, `is_string()` sobre un `string`. Resto defensivo puro |
| **50** | `isset.variable` | `isset($x)` sobre una variable que PHPStan ya sabe definida |
| **42** | `notIdentical.alwaysTrue` | `!==` que nunca puede ser falso |
| **24** | `foreach.emptyArray` | Recorrer un array que la inferencia da por vacío |
| **21 / 20** | `if.alwaysTrue` / `if.alwaysFalse` | La rama entera sobra |

**Nada de esto se borra todavía.** El encargo era medir antes de tocar, y la medición dice
que el mapa anterior ya no sirve.
## T14 · `scan-invalid-utf8` VIAJA CON EL FRAMEWORK

`bin/cli scan-invalid-utf8` es **comprobación previa a actualizar**, no una herramienta de
esta ventana.

Cualquier despliegue congelado que vaya a descongelarse puede mirarse antes de hacerlo:
desde que los sitios de codificación llevan `JSON_THROW_ON_ERROR`, un texto con UTF-8
inválido en base de datos deja de servir un dato ligeramente mal y pasa a **cortar la
petición con un 500**. Esta tarea dice si hay pólvora **antes** de actualizar.

Es de **solo lectura** y comprueba con `mb_check_encoding()`, no en SQL: quien tiene que
aceptar el dato es PHP.

**El entregable es la tarea, no su resultado.** El cero que da aquí no prueba nada —763 filas
en 41 tablas es una base de juguete—; el valor está en poder correrla contra una base real.

## T15 · CANDIDATO FUTURO — registrar `src/app/classes` en el `psr-4` de Composer

**No hacer todavía. Ventana propia, con su riesgo evaluado.**

El `psr-4` de `src/composer.json` declara únicamente `PiecesPHP\Core\`. **Composer no ve
`src/app/classes`, donde vive el código de negocio entero.** Por eso todas las herramientas
estándar necesitan aquí trabajo a medida: fue el motivo de que `--strict-psr` no sirviera
para la tercera comprobación de integridad (T10).

Registrarlo como **prefijo vacío** devolvería visibilidad a todo el instrumental y podría
sustituir parte de esa comprobación por `--strict-psr` nativo.

**El riesgo a evaluar antes**: hoy conviven el autoloader de Composer y el propio del
framework; registrar la misma carpeta en los dos puede provocar resoluciones ambiguas —ya hay
una, `PiecesPHP\Core\Database\Meta\MetaProperty`, declarada en `src/app` y en el paquete
`piecesphp/database`—.

**Ese riesgo ya no es ciego**: `bin/cli verify-integrity` falla si el núcleo eclipsa una
clase de cualquier paquete, con los eclipses aceptados registrados en `KNOWN_ECLIPSES`. Si
registrar la carpeta creara colisiones nuevas, la puerta las nombra en vez de dejarlas pasar.

## T16 · COLISIÓN DE CLASE `MetaProperty` — verificada, viva y silenciosa

> **RESUELTA EN EL PLAN POR T22, y con una corrección de diagnóstico.** Lo que sigue
> describe bien la colisión y su mecanismo, pero la palabra «HÍBRIDO» que se usa más abajo
> **es imprecisa**: `EntityMapper` del paquete no usa `MetaProperty` —cero menciones—, la
> usa `ExtensibleORM`. Los dos repositorios están internamente bien y el único defecto es
> el FQCN compartido. **Lee T22 antes que esto.**

**No es una «resolución ambigua». Son dos clases distintas con el mismo FQCN.**

`PiecesPHP\Core\Database\Meta\MetaProperty` está declarada en dos sitios y **los archivos
difieren de raíz**:

| Dónde | Líneas | Se construye sobre | Tiene en exclusiva |
| :-- | --: | :-- | :-- |
| `src/app/core/psr4/.../Meta/MetaProperty.php` | 617 | `EntityMapper` | `$internalName`, `setInternalName()`, `getInternalName()`, `getType()`, excepciones que nombran el campo |
| `src/vendor/piecesphp/database/src/.../Meta/MetaProperty.php` | 636 | `ORM` | `TYPE_DATE` con `'now'`, `validateType()` propia, `call_user_func($mapper, 'getInstance', ...)` |

### Cuál gana — AVERIGUADO, no deducido

`(new \ReflectionClass(MetaProperty::class))->getFileName()` devuelve **la del núcleo**. La
del paquete queda eclipsada dentro de piecesphp.

### El mecanismo, y por qué es general

| Repositorio | Prefijo PSR-4 |
| :-- | :-- |
| Los cuatro paquetes | `PiecesPHP\` → `src/` |
| **piecesphp (raíz)** | **`PiecesPHP\Core\`** → `./app/core/psr4/PiecesPHP/Core` |

PSR-4 resuelve por **prefijo más largo**. **Cualquier clase que el núcleo declare bajo
`PiecesPHP\Core\` eclipsa a la del paquete, siempre.** No es un accidente de este archivo:
es la regla.

### Barrido de los cuatro paquetes

| Paquete | Clases bajo `Core/` | Eclipsadas y **distintas** |
| :-- | --: | --: |
| `database` | 32 | **1** — `Database/Meta/MetaProperty.php` |
| `datastructures` | 5 | 0 |
| `html` | 16 | 0 |
| `geojson` | — | sin `Core/` |

**Una sola colisión en los cinco repositorios**: un archivo suelto y no un subárbol
duplicado, que es justo lo que la hace difícil de sospechar.

### Lo que corre en piecesphp es un HÍBRIDO

`ExtensibleORM.php` —del **paquete**— usa `MetaProperty` y recibe la del **núcleo**. Y la del
núcleo llama a `EntityMapper::validateType()`, clase que **solo existe en el paquete**
(verificado por reflexión).

Es decir: **`MetaProperty` del núcleo + `EntityMapper` del paquete**, una combinación que
**ninguno de los dos repositorios prueba**. Las suites del paquete (`UnitTest-MetaUtil`,
`UnitTest-ActiveRecord`) corren con su propio autoloader y validan el `MetaProperty` **del
paquete**, que dentro de piecesphp no se ejecuta nunca.

### Consecuencia concreta, ya medida

El arreglo de la deprecación de PHP 8.5 —`new \DateTime($value ?? 'now')`— se aplicó a los
**dos** archivos del paquete. En `MetaProperty` **no tiene ningún efecto en piecesphp**; en
`EntityMapper` sí, porque esa clase no está eclipsada y es la que el núcleo acaba llamando.

**Esta vez el arreglo llegó por el otro camino. La próxima puede no llegar.**

### Cuál es la buena — NO DECIDIDO. No se borra nada todavía

No son dos versiones de lo mismo: son **dos linajes**. `EntityMapper` contra `ORM` no es un
detalle de estilo, es otra jerarquía. Antes de tocar nada hay que responder:

1. ¿`ORM` y `EntityMapper` son el mismo concepto con dos nombres, o dos abstracciones vivas?
2. `$internalName` (solo núcleo) y `TYPE_DATE` con `'now'` (solo paquete): ¿alguna es
   funcionalidad viva que se perdería al unificar?
3. En piecesphp hay **17 archivos** con **39** llamadas a `addMetaProperty()`; **todos**
   reciben la del núcleo. *(El «28» de la primera redacción queda derogado: ver más abajo.)*

**Lo que NO se puede hacer**: borrar la del paquete «porque no se usa». Sí se usa —en el
paquete, con sus propias pruebas— y borrarla lo rompe como librería independiente.

### 2a · CUÁL ES EL CORRECTO **PARA CÓMO SE EJECUTA AQUÍ** — respondido

**La del núcleo, y no por poco.** No porque esté mejor escrita: porque es la única
compatible con el linaje que hay poblado en piecesphp.

Si mañana ganara la del paquete —basta con que alguien borre el archivo del núcleo
«porque está duplicado»— rompería por **cuatro** sitios distintos:

| # | Qué rompe | Evidencia |
| :-- | :-- | :-- |
| 1 | **Fatal al arrancar cualquier mapper.** `EntityMapperExtensible::addMetaProperty()` llama a `getInternalName()` y `setInternalName()` | `EntityMapperExtensible.php:81-83`. Esos métodos **solo existen en la copia del núcleo** |
| 2 | **Todo campo `TYPE_MAPPER` / `TYPE_ARRAY_MAPPER` dejaría de validar.** La copia del paquete comprueba `is_subclass_of($value, ORM::class)` | En `src/app`: **35** clases extienden el linaje `EntityMapper`, **0** extienden `ORM` |
| 3 | **La conversión de un valor a mapper llamaría a un método inexistente.** La copia del paquete hace `call_user_func($mapper, 'getInstance', …)` | `ORM::getInstance()` existe (`ORM.php:123`). **`EntityMapper` no tiene `getInstance()`** |
| 4 | **`null` en un campo mapper anulable.** El núcleo envuelve todo el bloque en `if ($value !== null)` y fuerza `$value = null; $existsOnMapper = true`. La copia del paquete no: el `null` entra en la conversión | Hay campos así declarados: `OrganizationMapper.php:332`, `UserProfileMapper.php:187` |

**Y al revés, la del paquete es la correcta EN SU CASA**: `ORM` no tiene `validateType()`,
así que su `MetaProperty` tuvo que traerse una propia; `EntityMapper` sí la tiene
(`EntityMapper.php:1307`), así que la del núcleo delega. **Ninguna es «la buena versión»:
cada una está adaptada a su clase base.** Por eso el diff es de 567 líneas y no de veinte.

### Las tres preguntas abiertas de T16, ya con respuesta

1. **¿`ORM` y `EntityMapper` son el mismo concepto con dos nombres?** **No: son dos
   abstracciones vivas.** Dentro del paquete coexisten y **ninguna extiende a la otra**.
   En piecesphp solo una está poblada (35 contra 0). `DataTablesHelper` es el único sitio
   que acepta las dos, y lo hace con un `instanceof`, no con una jerarquía común.
2. **¿`$internalName` y `TYPE_DATE` con `'now'` son funcionalidad viva?**
   - `$internalName` **sí**: se añadió a propósito en `06a491e7` («Mejor debug de meta
     properties»), lo consume `EntityMapperExtensible` y nombra el campo en **tres**
     mensajes de excepción. Unificar sin él degrada el diagnóstico.
   - `TYPE_DATE` con `'now'` **aquí no tiene consumidores**: `grep -rn
     "MetaProperty::TYPE_DATE" src/app --include=*.php` da **0**. No puede tenerlos, porque
     la copia que se ejecuta no ofrece esa semántica.
3. **Cuántos mappers dependen de esto** — *cifra corregida bajo el punto 5 de T0.* El «28»
   anotado antes **no es reproducible**: ninguna medición lo devuelve. Las cifras con su
   comando:

   | Cifra | Comando |
   | --: | :-- |
   | **17** archivos llaman a `addMetaProperty()` | `grep -rln "addMetaProperty" src/app --include=*.php \| wc -l` |
   | **39** llamadas en total | `grep -rc "addMetaProperty" $(grep -rln …) ` sumando la segunda columna |
   | **16** archivos construyen `new MetaProperty` | `grep -rln "new MetaProperty" src/app --include=*.php \| wc -l` |
   | **19** declaraciones de tipo mapper | `grep -rn "TYPE_MAPPER\|TYPE_ARRAY_MAPPER" src/app --include=*.php \| wc -l` |

   **El «28» queda derogado.** No se reconstruye: no consta con qué se midió.

### Por qué el arreglo de 8.5 llegó de rebote — el rastro completo

El commit del paquete `41a3e9d` («fix(php85): pasar null a `DateTime::__construct()`»)
tocó **tres** archivos: `EntityMapper.php`, `Meta/MetaProperty.php` y
`ORM/Fields/DataProcess.php`.

De esos tres, en piecesphp solo tiene efecto el primero. Y la copia del núcleo **no
construye ninguna fecha**: `grep -n "DateTime\|strtotime"` sobre ella da **cero**. Delega
en `EntityMapper::validateType()`, que no está eclipsada. **Ese es el único hilo por el que
llegó el arreglo, y nadie lo tendió a propósito.**

### Nadie prueba lo que aquí se ejecuta — demostrado

`unit-tests/UnitTest-MetaUtil.php` del paquete llama a
`MetaProperty::validateType('TEXT', 'world')`. **Ese método estático no existe en la copia
que corre en el framework** (verificado por reflexión: `hasMethod('validateType')` → `NO`).
La suite pasa en el paquete y sería un fatal aquí. Eso es 2c.

### Qué NO se decide aquí

**Cuál se borra sigue sin decidirse**, y este análisis no lo empuja hacia ningún lado: la
del paquete se usa en el paquete, con sus pruebas, y borrarla lo rompe como librería
independiente. Lo que este análisis sí cierra es que **la del núcleo no puede irse sin
reescribir el linaje entero**.
## T17 · REGLA — una regla mecánica se aplica a TODA la familia o a ninguna

**La mejor lección del lote, y salió de un error propio.**

Antes del primer lote de Rector había **39 archivos** con `strlen($route) > 0`. Rector
convirtió **25** y dejó **14**, porque solo pudo probar el tipo en esos.

**Cada cambio individual era correcto. El resultado agregado fue peor:** un lote mecánico
**partió en dos un grupo homogéneo**, justo en el código que se estaba a punto de unificar.

> **Antes de correr una regla mecánica, cuenta a cuántos sitios de la familia alcanza. Si no
> los cubre todos: o completas el resto a mano EN EL MISMO COMMIT, o no la corres.**

La aplicación parcial **fabrica divergencia** aunque acierte caso por caso. Y en una plantilla
que se clona, la divergencia fabricada se hereda.

### Corolario medido en la misma sesión

Los scripts de edición en Python convirtieron **CRLF a LF en 20 archivos**, entre ellos
`OrganizationMapper.php` (1.342 líneas) y `PublicationsController.php` (1.935). PHP no
distingue, pero el commit del paso B declaraba **4.185 inserciones para 41 líneas de cambio
real**: imposible de revisar. **Hay que abrir con `newline=''`.** Es la misma familia que
T10 —editar por estructura y no por posición—, aplicada a los finales de línea.

## T18 · Las 300 ramas de clase (a) — MUESTRA VERIFICADA, EL LOTE NO SE APLICA

Muestra determinista de 20, repartida por todo el conjunto y **verificada a mano**.
**No sale limpia**, y el motivo invalida el borrado en lote.

### El hallazgo: 6 de 20 dependen de configuración por despliegue

| Muestra | Código | Por qué PHPStan dice «siempre verdadero» |
| :-- | :-- | :-- |
| `APIRoutes.php:43` | `if (self::ENABLE \|\| self::ENABLE_TRANSLATIONS \|\| …)` | Las constantes valen `true` **en este despliegue** |
| `FileManagerRoutes.php:42` | `if (self::FILE_MANAGER_ENABLE)` | Ídem |
| `NewsletterRoutes.php:43` | `if (self::ENABLE)` | `NEWSLETTER_MODULE` está en `true` |
| `ImagesRepositoryRoutes.php:35` | `const ENABLE = IMAGES_REPOSITORY && LOCATIONS_ENABLED` | Las dos en `true` |
| `HelpersSystemRoutes.php:43` | `if (self::ENABLE)` | Ídem |
| `APIController.php:1828` | `if (APIRoutes::ENABLE_USERS)` | Ídem |

**Esas constantes son los interruptores de módulo** (`config/constants.php`, regla 8 de
`CLAUDE.md`), y **cada despliegue las configura**. PHPStan las resuelve al valor de *este*
árbol. **Borrar esos `if` cablearía todos los módulos en ENCENDIDO**, y un despliegue que
apague `NEWSLETTER_MODULE` se rompería sin que nadie pueda ver por qué.

**Es exactamente «embarcar una trampa»** (T0).

### Reparto corregido

| | Cantidad |
| :-- | --: |
| Clase (a) medida | **300** |
| **De ellas, la condición depende de una constante de configuración** | **65 (21,7 %)** |
| Borrables sin tocar configuración | **235** |

### Qué hacer con cada parte

- **Las 65 NO se borran nunca.** Van a **supresión documentada** en el `.neon` con su razón:
  *«la condición depende de una constante de `config/constants.php`, que cada despliegue
  configura; PHPStan la resuelve al valor de este árbol»*. Esa razón es permanente, no
  temporal, así que **no lleva condición de retirada**.
- **Las 235 restantes**: la muestra de 20 dejó 14 verificadas como resto defensivo real
  —`is_array()` sobre `array`, `is_null()` sobre `string`, `if (false)`, `Dead catch`—.
  Borrables, pero **por familia y con una muestra por familia**, no las 235 de una vez.

### Reconciliación de la resta, que estaba pendiente

| | |
| :-- | --: |
| Errores con los ignores **dentro** | 878 (869 claves únicas) |
| Errores con los ignores **fuera** | 1.347 (1.333 claves únicas) |
| **Resta de totales** | **469** |
| **Resta de claves únicas** | **464** |

**Las dos cifras son correctas en unidades distintas**: 469 son *instancias* de error y 464
son *tripletas distintas* (ruta, línea, mensaje). La diferencia de 5 son duplicados exactos:
14 en la corrida nueva contra 9 en la vieja.

## T19 · Los hooks `_allowedRoute` — comprobado, NO hay ninguno muerto

La sospecha era razonable: un `_allowedRoute()` declarado que nadie invoca sería **una
restricción escrita y jamás cumplida**, es decir un hallazgo de seguridad y no limpieza.

**Medido: de los 32 archivos que declaran `_allowedRoute()`, los 32 lo llaman. Cero muertos.**

Y una corrección a lo que se dijo antes: los seis controladores públicos **no tienen el hook
muerto — no tienen hook**. Ni lo declaran ni lo llaman, así que son coherentes consigo mismos.
Eso refuerza el diseño de dos traits: nombrar una ruta es universal, guardarla no.

## T20 · CUANDO UNA MEDICIÓN SORPRENDE, SE SOSPECHA PRIMERO DE LA MEDICIÓN

**Cuatro veces en esta campaña el defecto estaba en el INSTRUMENTO, no en lo medido.**

| Instrumento | Qué medía mal | Qué llegó a afirmarse |
| :-- | :-- | :-- |
| `awk` cortando en la primera llave a cuatro espacios | Perdía los cuerpos con bloques anidados | «`routeName` tiene 6 variantes». Por tokens son **10** |
| Contar `/*` contra `*/` en texto plano | Contaba las apariciones dentro de cadenas — `'image/*'` | **32 falsos positivos** en la primera `verify-integrity` |
| La tabla de PHPStan | Recorta la ruta al ancho de terminal | «192 archivos con errores». Son **195**, y Rector no veía 34 |
| `git diff --name-only --ignore-cr-at-eol` | **Lista el archivo aunque el filtro no encuentre diferencias**; el filtro lo aplica `--stat`, no `--name-only` | «58 de 59 archivos del paquete cambian de contenido». Cambian **cero** |
| Una extracción PARCIAL presentada como censo | Trece cuerpos de `_allowedRoute` sacados a mano, de treinta y dos | «doce de trece no deciden nada, solo una decide». **Son quince de treinta y dos** |
| La memoria, sobre lo que hay en git | Nadie miró el historial | «el directorio queda en la historia». **Nunca estuvo en git**: `git log` sobre él no devuelve nada |
| Tres `grep` distintos en el mismo mensaje | Uno solo miraba `core/`; otro no anclaba a inicio de línea y **volvió a contar las funciones globales como métodos**; el tercero no cubría propiedades tipadas | «117 funciones, 143 métodos, 14 propiedades». Por tokens: **145, 1 y 90** |
| El histórico de un log que se limpia de rutina | Se dedujo de una AUSENCIA sin comprobar si el instrumento podía contener el dato | «Apache quizá sirve 8.4». Sirve **8.5.9**, y eso era justo la causa del fallo |
| **`phpVersion: {min: 80400, max: 80500}`** en `phpstan.neon` | **UN RANGO REPORTA LA INTERSECCIÓN, NO LA UNIÓN**: solo lo que es error en TODAS las versiones del rango | «cubre las dos versiones». Cegó la pata estática a **toda** deprecación exclusiva de 8.5 durante la campaña entera |
| Resolver una URL relativa a mano en un probe | El navegador la resuelve contra el `<base href>`; mi probe la resolvía contra el directorio de la página | «el marcador del cropper resuelve a `…/agregar/img-gen/…`». Resuelve a la raíz, y funcionaba |
| `curl` sin `Referer` ni `Origin` | `requestIsSameDomain()` **exige esas cabeceras**; sin ellas la ruta no se registra | «`img-gen` da 404 siempre». Con ellas da **200** |
| Una regex de `src="…"` con comillas dobles | El framework emite **comillas simples** | «el iframe de SurveyJS no carga ningún recurso». Carga **tres**, y los tres a 200 |
| `head` sobre la salida de un `grep` | Cortó la lista antes de las líneas que importaban | «el layout del panel no tiene `<base>`». Lo tiene, en `header.php:13` |
| `grep -c 'Preview' .gitignore` | Miraba el `.gitignore` de la RAÍZ; la entrada vive en `bin/.gitignore` | «los cuatro paquetes no lo tienen». `database` **sí** lo tenía |
| Un shim de navegador escrito en Node | No es un navegador: falló con `HTMLElement is not defined` porque el bundle define un *custom element* | Nada, porque **esta vez se paró a tiempo**: el límite se escribió en vez de rellenarlo |
| **`git blame` sin `--ignore-revs-file`** | Un commit de renormalización tocó la última línea de **1.126 archivos**: para `blame`, la campaña escribió casi todo | «**114 bloques de comentario nuestros contra 18 viejos**». Son **44 contra 88** |

### LA FORMA MÁS AFILADA DE ESTA REGLA, y es el último caso de la tabla

**UN ERROR DE MEDICIÓN QUE COINCIDE CON TU HIPÓTESIS ES EL ÚNICO QUE NO VAS A ATRAPAR.**

Mira la diferencia con el resto de la tabla. Todos los demás instrumentos fallaron dando un
número **raro** —«cero archivos cambian», «6 variantes cuando se ven 10»—, y la rareza fue lo
que disparó la comprobación. El `git blame` falló dando un número **esperado**.

El encargo del censo nació de una sospecha: *«los comentarios están volviendo a crecer y son
nuestros»*. El instrumento respondió **114 nuestros contra 18 viejos** — el 87 %. Eso no es
una respuesta: **es un eco.** Confirmaba la sospecha del propietario, confirmaba la mía, y
encajaba con lo que los dos habíamos visto en el docblock del 2FA.

Lo real es **44 contra 88**: un tercio nuestro, dos tercios heredados. **La conclusión
operativa cambia entera** —de «recortamos lo nuestro y listo» a «hay una lista cerrada que
mantener»— y el número falso habría pasado sin que nadie lo mirara, porque **nadie audita lo
que ya creía**.

> **LA CONSECUENCIA PRÁCTICA, que es lo que hay que recordar de esta entrada:**
>
> **EL SEGUNDO MÉTODO INDEPENDIENTE SE EXIGE SOBRE TODO CUANDO EL PRIMERO CONFIRMA LO QUE
> CREÍAS.** La sorpresa ya dispara la sospecha sola —es lo que dice el título de T20 y funciona
> —. **La confirmación no dispara nada**, y por eso es más peligrosa. La regla no es «duda de
> lo que te sorprende»: es **«duda de lo que te da la razón»**, que cuesta mucho más.

Y hay una ironía que conviene no perder: **lo que impidió la conclusión falsa fue
`.git-blame-ignore-revs`**, un archivo que se creó por **legibilidad** —para que `blame` no
señalara al commit de finales de línea al leer historia—. No se hizo pensando en mediciones.
**Una decisión de higiene tomada por otro motivo fue la que salvó el dato**, y solo porque
alguien se acordó de que existía.

**Corolario para el resto de la campaña**: toda cifra que confirme una hipótesis previa lleva
**dos métodos**, no uno. Es más caro y es exactamente donde hay que gastarlo.

### Y un caso del propietario, que cierra la simetría de esta entrada

T20 lleva nueve instrumentos míos y dos mediciones suyas. **Falta el tercero, y es de la misma
familia que el `git blame`: un arreglo pedido para un problema que no existía.**

El encargo decía: *«`getLangGroupData` ante un grupo desconocido devuelve algo que rompe un
`Object.assign` — arregla la función, no la página»*. **El razonamiento es impecable** —un
grupo inexistente es un estado normal, cualquier módulo nuevo lo tiene antes de traducirse, y
una función que lance ahí es una mina para todo consumidor futuro—. Tan impecable que invita a
implementarlo sin mirar.

`configurations.js:1335`, definición única en todo el proyecto, **ya empieza en `{}`** y solo
lo sustituye si el grupo existe **y** es un objeto. Ejecutada con el mismo `Proxy` que usa el
código real:

```
  grupo inexistente -> {} | typeof: object
  Object.assign con él -> {"pageNextText":"Siguiente"}    (no-op)
```

**Lo que se comparte con el caso del `git blame` es la forma, no el error:** una hipótesis bien
construida, coherente con todo lo observable, que **no se comprobó porque no hacía falta
comprobarla**. La diferencia es que aquí se comprobó, y costó treinta segundos.

> **Y por eso «arregla la función, no la página» sigue siendo la instrucción correcta aunque el
> arreglo sobrara.** Lo que se pedía era el comportamiento; el comportamiento ya estaba. La
> lección no es «no propongas arreglos», es: **antes de arreglar el hueco, mira si está
> tapado.**| **La caché de opcodes del servidor web** | Apache seguía sirviendo la versión COMPILADA anterior del archivo | «el `chr()` viejo también da 200, así que no era eso». Sí era: con la caché invalidada da **500** |
| **Un PRINCIPIO DE ARQUITECTURA que no está escrito** | Sin él, cada patrón que lo aplica parece un defecto | «la inicialización del SEO es un defecto, conviértela en tarea de CLI» — era **exactamente lo contrario** del diseño. Tres diagnósticos equivocados por la misma causa |
| **No preguntar a la persona que tiene el contexto** | Se construyó una hipótesis sobre un cambio de estado que no habíamos hecho nosotros | «tres tablas vacías, sin explicar». Las había vaciado el propietario a mano, y estaba delante |

### La regla

> **EL INSTRUMENTO MÁS BARATO SUELE SER LA PERSONA QUE TIENE EL CONTEXTO.** Antes de
> inferir sobre un cambio de estado que no hiciste tú, pregunta quién lo hizo. Una consulta
> cuesta una frase; reconstruirlo por deducción cuesta media sesión y puede acabar en una
> hipótesis elegante y falsa.

> **Antes de reportar un hallazgo inesperado, verifícalo con un SEGUNDO MÉTODO
> INDEPENDIENTE.** Una cifra que sorprende es, con más frecuencia de la que parece, una
> herramienta que no mide lo que uno cree.

«Independiente» significa que no comparta mecanismo con el primero: `--stat` contra
`--name-only` sirve porque aplican el filtro en sitios distintos; repetir el mismo comando
con otra bandera cosmética, no.

### REGLA: una comparación A/B contra la aplicación web viva es MENTIRA hasta invalidar el opcache

`opcache.enable => On` en el SAPI de Apache. Cambiar un archivo y pedir la página **no
compara lo que crees**: puede seguir ejecutándose el bytecode anterior.

**Cómo se invalidó aquí**, que es lo que hay que repetir:

```bash
cp version-vieja.php ruta/del/archivo.php
touch ruta/del/archivo.php          # que cambie la marca de tiempo
sleep 3                              # esperar a opcache.revalidate_freq
curl -sk … && echo $?
```

Sin el `touch` y la espera, el primer A/B del `chr()` dio **200 con el código viejo** y
estuvo a punto de descartar la causa correcta. Con la caché invalidada: **500 con el viejo,
200 con el arreglo.**

**El caso del `phpVersion` es de una TERCERA clase, y es el más caro de todos**: no fue una
medición mal hecha ni una medición ajena dada por buena. Fue un **INSTRUMENTO MAL
CONFIGURADO que devolvía menos de lo que parecía**, y encima con un nombre que sugería lo
contrario — un rango suena a «cubre más» y cubre menos.

> **No faltaban stubs: se le había dicho a PHPStan que no mirara.** Y la configuración estaba
> comentada, con una explicación razonada de por qué el rango era mejor que un escalar. La
> explicación decía justo lo contrario de lo que hace la opción, y nadie la probó.

**Los dos casos anteriores son de otra clase y por eso están en la tabla:** no falló una
herramienta, falló **dar por buena una medición ajena**. Una extracción parcial se presentó
como censo y una afirmación sobre el historial de git se escribió sin mirar el historial.

> **Que la medición la haya hecho otro no la exime de verificación.** Al contrario: la
> propia no se sabe de dónde salió, la ajena tampoco — y encima no se puede reconstruir de
> memoria. En los dos casos bastó un comando: contar los treinta y dos cuerpos por tokens, y
> `git log --diff-filter=A` sobre el directorio.

### Por qué importa aquí más que en otro sitio

Una alarma falsa cuesta el doble: se pierde la confianza en la medición **y** en las que
vengan después. En una campaña que se sostiene sobre cifras —877 errores, 464 ramas, 107
`false`, 44 controladores—, un instrumento que miente contamina todo el razonamiento
construido encima. Ya obligó a derogar el «148» y a reconciliar «469 contra 464».

**Corolario**: cada cifra que se publique en un documento debería poder decir con qué se
midió. Ver T8 y T18, donde la unidad y el método están escritos junto al número.

Ese corolario **subió al criterio de cierre**: es el punto 5 de T0. La razón no es la
simetría con la regla de las supresiones, es que **el trinquete no funciona sin él**:
comparar dos baselines solo significa algo si se midieron igual.

### LA VARIANTE MÁS PELIGROSA: CUANDO UNA MEDICIÓN TE AHORRA TRABAJO, VERIFÍCALA CON MÁS GANAS, NO CON MENOS

Ya estaba escrito que el segundo método independiente se exige sobre todo cuando el primero
**confirma** lo que creías (el caso del `git blame`). **Esta es la hermana, y es peor**: cuando el
primer método te dice que **no hay nada que hacer**.

**El caso**: fui a construir el mecanismo del opcache y miré los archivos de configuración.

```
ls /etc/php/8.5/fpm/conf.d/ | grep -i opcache   -> nada
```

Conclusión aparente: **opcache no está cargado, luego mis tres «me mordió el opcache» eran
falsos y no hay mecanismo que construir.** Estuve a punto de escribir esa retractación. Lo que lo
evitó fue preguntarle al binario que sirve de verdad en vez de a los archivos que lo describen:

```
php-fpm8.5 -m | grep -i opcache   -> Zend OPcache
```

**Viene compilado dentro del binario**, así que los `.ini` no lo mencionan. El instrumento
equivocado daba la respuesta **cómoda**, y una respuesta cómoda no despierta las mismas ganas de
comprobar que una incómoda. **Ahí está el sesgo, y no es de conocimiento: es de esfuerzo.**

> **El patrón, dicho en general**: preguntarle a la *descripción* de un sistema en vez de al
> sistema. Los `.ini` describen lo que se carga; el binario **es** lo que se carga. Igual que
> `information_schema` es la base y el mapper solo dice lo que cree — y ahí, la discrepancia eran
> 38 columnas.

### REGLA MAYOR — DOS MEDICIONES QUE COINCIDEN NO SE VERIFICAN ENTRE SÍ SI NO SE SABE QUÉ DEJA FUERA CADA UNA

Es T20 aplicado **al acuerdo** y no a la sorpresa. Ya estaba escrito que el segundo método se
exige sobre todo cuando el primero **confirma**, y que una medición que **ahorra trabajo** hay que
mirarla con más ganas. Falta la tercera de la familia, y es la más silenciosa: **la coincidencia
también es cómoda.**

**El caso que la funda** (T57): el propietario contó 38 declaraciones y yo conté 38. El número
coincidía y **los conjuntos no**:

| | Cuenta | Qué perdía |
| :-- | --: | :-- |
| Su método (leyendo) | 38 | `LoginAttemptsModel` y `TimeOnPlatformModel` |
| Mi método (descubrimiento) | 38 | `UserProfileMapper` y sus 3 declaraciones |
| **Reconciliados** | **39** | — |

Uno perdía 2 y el otro perdía 3, y la suma cuadraba en el punto medio. **Dos métodos que dan el
mismo número no siempre miden lo mismo.**

**Cómo se aplica**: cuando dos mediciones coincidan, no se da por buena la cifra — se comparan
los **conjuntos**, elemento a elemento. Si no se pueden comparar los conjuntos, la coincidencia no
verifica nada. Y **el conjunto excluido necesita la misma prueba que el incluido**: fue el
propietario quien lo exigió, y de ahí salió la comprobación de las 20 claves ajenas descartadas
contra `information_schema`.

> **Y una segunda cosa, que es peor**: el aviso ya estaba impreso. La herramienta decía
> *«`PreviousExperiencesMapper::profile` -> `user_system_profile` (tabla sin mapper
> descubierto)»* en cada corrida. **No falló el instrumento: falló leerlo.** Una salida que nadie
> lee es exactamente igual de útil que una que no se emite.

## T21 · PEDIR LA DEMOSTRACIÓN NO ES CEREMONIA, ES UN DETECTOR

**El peor defecto de esta campaña no apareció revisando. Apareció al intentar ENSEÑAR.**

> **Caso añadido, y es el más caro de los tres: T35.** Dos hipótesis sobre el 2FA —una mía y
> una del propietario—, las dos coherentes con **todos** los síntomas observables, las dos
> falsas. Ninguna sobrevivió a poner el estado en la tabla y preguntarle a la puerta.
>
> **Y las dos cifras quedan corregidas aquí, con la medición y no con la hipótesis:**
>
> 1. **Los caminos de lectura que escriben son DOS, no tres.** El «tercero» que reporté no
>    existe: `get-current-totp-qr-data` es GET y **solo lee**; los que escribían son
>    `configure-totp` y `mark-current-totp-qr-as-viewed`, **los dos POST**. Mi recorrido fue
>    solo-GET, así que no pudo activar el 2FA de root: **lo activó el propietario revisando**,
>    igual que había vaciado las tres tablas a mano. Segunda vez que el instrumento más barato
>    era preguntarle a la persona con el contexto.
> 2. **El estado transitorio NO pedía código.** Reproducido el estado antiguo exacto, la puerta
>    de login exige `isEnabled2FA` **y** `wasViewedQRData`, y la segunda seguía en `0`. El
>    defecto real era otro —un flujo que no se podía terminar—, y está en T35 §4.
>
> **La conclusión aguanta entera pese a las dos correcciones**, y por eso gobierna el diseño de
> E2: **«solo GET» no es una propiedad de seguridad en este código.** Es una convención de
> HTTP, no algo que aquí alguien haga cumplir. Dos no es cero, y son dos que nadie habría
> buscado.

Los scripts que normalizaron los cuerpos de `routeName` convirtieron de paso CRLF a LF en
**20 archivos**. El commit declaraba **4.185 inserciones para 41 líneas reales** —
irrevisable, y por eso mismo invisible en una revisión: nadie lee cuatro mil líneas para
comprobar que sobran. Salió cuando el propietario dijo **«enséñame el Paso B»** y hubo que
producir el diff.

### REGLA MAYOR — NUESTRAS PUERTAS NO PUEDEN VERIFICAR PROSA. NINGUNA. NUNCA

**Todas nuestras puertas verifican PROPIEDADES EJECUTABLES**: que un archivo compile, que una
clase cargue, que una firma no cambie, que un contador no suba, que una suite pase.

**Un comentario, un documento, un nombre de variable, una entrada del `CHANGELOG` — su verdad
es SEMÁNTICA**, y está fuera del alcance de las ocho comprobaciones de `verify-integrity`, del
trinquete y de las suites. No es que hoy no lo detecten: es que **no pueden**.

El caso que lo demostró (T40): ocho comentarios acabaron describiendo un código que no era el
suyo, y **todo estaba en verde** — compilaban, las suites pasaban, y la puerta nueva contaba
menos bloques, que era justo lo que se buscaba. Ninguna señal miraba si lo que quedaba era
cierto, porque ninguna sabe hacerlo.

> **PROCEDIMIENTO, no anécdota: TODO CAMBIO SOBRE PROSA —comentarios, documentos, `CHANGELOG`—
> SE ENTREGA COMO PARES ANTES/DESPUÉS, no como conteo.** «De 132 bloques a 91» no dice
> absolutamente nada sobre si los 91 son ciertos. El par impreso al lado sí, y cuesta un minuto.

**Y es la contraparte exacta de la foto de E2**, que conviene ver junta porque es la misma idea
aplicada a las dos mitades del repositorio:

| | Qué se compara | Cómo |
| :-- | :-- | :-- |
| **Código** | el antes y el después de un cambio | un recorrido de rutas reproducible |
| **Prosa** | el antes y el después de un cambio | el par impreso, leído por una persona |

En los dos casos la red es **un antes reproducible**. La diferencia es quién lo compara: en uno
una herramienta, en el otro **necesariamente** alguien leyendo.

### UNA COMPROBACIÓN QUE MIRA EL CUERPO DE UN MÉTODO ES CIEGA A LO QUE ESE MÉTODO DELEGA

**Si el defecto que vigila puede esconderse tras una llamada, la comprobación tiene que
EJECUTAR, no leer.** Dos casos, y el primero es el más serio de la campaña:

- **`otp-write-separation` pasaba en verde con D2 reintroducido** (T46). Buscaba `->save(` en el
  cuerpo del método; D2 escribía **una llamada más abajo**, que es exactamente su forma.
- **`checkRouteOverrides` pide borrar una sobreescritura viva** si su decisión se muda a un
  método que ella llama (T47). El veredicto no era una duda: era «Bórralo».

El afinado, que es la forma corta de recordarlo:

> **«¿CONTIENE X?» SE PUEDE LEER; «¿HACE X?» HAY QUE EJECUTARLO.**

| Lo que se pregunta | ¿Basta leer el cuerpo? |
| :-- | :-- |
| «¿contiene X?» — una llamada deprecada, un `die($string)` | **Sí.** Está en el texto o no está |
| «¿HACE X?» — escribe, decide, valida | **No.** Hacer se delega; contener no |

**Y la evidencia que la sostiene, que es lo que la hace creíble:** de las ocho comprobaciones
de `verify-integrity`, **las que preguntan lo primero están sanas** —deprecadas, docblocks sin
cerrar, PSR-4, eclipses, marcas del instrumental, comentarios narrativos— y **las dos que
preguntaban lo segundo son exactamente las dos que fallaron**: la separación de escrituras del
OTP y el clasificador de sobreescrituras de rutas.

#### Y el matiz del veredicto, que también enseña

`«YA NO DECIDE NADA: Bórralo»` **no es una duda: es un imperativo.**

> **UNA COMPROBACIÓN QUE EMITE ÓRDENES TIENE QUE SER MÁS FIABLE QUE UNA QUE EMITE AVISOS**,
> porque **nadie verifica una orden antes de obedecerla**. Un aviso invita a mirar; una orden
> invita a ejecutar.
>
> Donde no se pueda garantizar ese nivel —y una comprobación que lee el cuerpo no puede—, **el
> veredicto tiene que ser una pregunta y no un mandato**.

### CUANDO UNA PUERTA APRUEBA ALGO QUE LUEGO RESULTA DIVERGENTE, EL DEFECTO ES SU ALCANCE

**Y se amplía en el MISMO commit en que se arregla lo que se le escapó**, o la próxima
divergencia vuelve a pasar por delante de ella.

Van dos veces:

| Puerta | Qué aprobó | Qué no miraba |
| :-- | :-- | :-- |
| `verify-integrity`, primera versión | Un archivo al que un corte por posición le había arrancado el `namespace` | Comprobaba docblocks y firmas, **no que la clase declarada correspondiera a su ruta** |
| `shared-toolchain` | Cuatro repositorios cuyo estado de seguimiento divergía | Comprobaba que los archivos contuvieran las MARCAS, **no el estado de lo que las herramientas PRODUCEN** |

El segundo caso, detallado, está en T42. Lo que importa aquí es la forma: **una puerta verde
sobre algo divergente no es una puerta que falló, es una puerta que no estaba mirando ahí** — y
la reacción correcta no es arreglar la divergencia y seguir, sino preguntarse **qué más cae
fuera del alcance** y meterlo dentro antes de cerrar.

### No fue una casualidad. Pasa cada vez

| Lo que se afirmaba | Qué lo tumbó |
| :-- | :-- |
| «El Paso B son 41 líneas» | **Enseñar el diff**: 4.185 inserciones |
| «Con `.gitattributes` ya está arreglado» | **Correrlo de verdad**: `dos2unix` + `git add` seguía preparando 1.181 líneas. Faltaba renormalizar |
| «Con `-X renormalize` no habrá conflictos» | **La fusión de prueba en un clon aislado**: 2 conflictos sin la opción, 0 con ella. El propietario lo exigió con «no lo escribas porque yo lo diga» |
| «La tercera comprobación de integridad detecta un `namespace` perdido» | **Provocar el fallo**: la primera versión derivaba el FQCN del propio archivo y no detectaba nada |
| «Hay hooks `_allowedRoute` muertos» | **Contarlos**: de 32 que lo declaran, los 32 lo llaman |
| «El guardián de eclipses funciona» | **Fabricar una colisión**: la primera sonda tumbó la aplicación entera antes de llegar a la comprobación — que demostró el mecanismo, pero no el guardián |

### La regla

> **Antes de dar algo por hecho, produce el artefacto que lo demostraría** —el diff que
> enseñarías, la corrida que lo prueba, el fallo provocado a mano— **aunque nadie lo haya
> pedido.** Si no puedes producirlo, no está hecho: está supuesto.

Y para los cambios que se prueban con una puerta —una comprobación, un test, un guardián—,
la demostración tiene **dos direcciones y hacen falta las dos**: que **pase** cuando debe
pasar, y que **falle** cuando debe fallar. Una puerta que solo se ha visto en verde no se ha
visto funcionar; solo se ha visto callar.

### Por qué funciona

Revisar comprueba **la afirmación que uno hace**. Demostrar obliga a fabricar el artefacto, y
**el artefacto trae consigo todo lo que la afirmación omitía** — incluido lo que uno no sabía
que estaba omitiendo. Por eso pedir la demostración detecta una clase de fallo que ninguna
revisión alcanza: **la de los errores que uno no sabe que cometió.**

### Y el reverso: UN CAMBIO DE TUBERÍA SE LLEVA POR DELANTE LO QUE NADIE DECLARÓ COMO REQUISITO

Al pasar el resumen de PHPStan de **parsear la tabla** a **leer el JSON**, desapareció el
prefijo **`project://`** que llevaba en el informe desde 2022. Lo aplicaba una expresión
regular sobre la cabecera `Line   <ruta>` del formato de tabla; con el formato nuevo esa
expresión **dejó de encajar y calló**. El propietario lo usa con un plugin para saltar al
archivo desde el informe: una función real, sin una sola prueba y sin figurar en ningún
requisito.

> **El regex no falló: dejó de encajar.** Un detector que no encuentra nada y uno que no
> tiene nada que encontrar producen exactamente la misma salida.

**La regla**: cuando cambies el FORMATO DE ENTRADA de algo, **enumera qué leía el formato
viejo** antes de tocarlo — no qué escribía, qué *leía*. Aquí eran tres cosas y solo se
migraron dos: el conteo, el reparto por archivo… y el prefijo del editor, que no era ninguna
de las dos.

*(Repuesto sobre el JSON, que trae la ruta como dato y no necesita expresión regular. Formato
idéntico al anterior, comprobado contra el resumen de antes del cambio.)*

### El corolario que salió de aplicarla hacia atrás

> **PROVOCAR EL FALLO DE UNA PUERTA NO VALIDA LA PUERTA: VALIDA LO QUE HAY DETRÁS.**

El defecto del `encoding="utf8mb4"` —XML exportado que **ningún parser podía leer**— no lo
encontró nadie mirando el exportador. Lo encontró **la comprobación que se añadió para
probar que la puerta no fallaba**: al descubrir que la suite daba 23/23 con un JSON corrupto,
se le añadió validación de sintaxis, y esa validación destapó el XML.

La puerta era el pretexto. **El hallazgo estaba detrás de ella, y llevaba ahí desde siempre.**

### «TOCA TODO» ES UNA HIPÓTESIS, NO UNA MEDICIÓN

H1 —que `bin/cli` devolviera 0 con la aplicación muerta— se reportó como *«no corregido: toca
a la CLI entera»*. **Era una línea**: `die($content)` sale con código cero.

La estimación de coste se dio por buena sin medirla, y una estimación es una afirmación como
cualquier otra.

> **Antes de descartar un arreglo por caro, mide su coste igual que medirías cualquier otra
> cosa que fueras a escribir.** Abrir el archivo y buscar dónde sale el proceso costaba dos
> minutos; el precio de no hacerlo fue dejar en pie un día más la puerta que decía «todo
> bien» sin haber mirado.

Y el patrón se repite: `dynamicConstantNames` iba a ser «una ventana propia» y fue un bloque
de config; el trinquete del baseline iba a ser «infraestructura» y fueron cuarenta líneas.
**Las tres veces el coste real estaba un orden de magnitud por debajo del estimado.**

**Relación con T20**: T20 dice de qué desconfiar cuando un número sorprende. T21 dice qué
hacer cuando **nada** sorprende, que es el caso peligroso.

### REGLA MAYOR — UNA PRUEBA VALE MÁS CUANDO QUIEN JUZGA NO ES QUIEN PRODUJO EL RESULTADO

**Donde exista un juez externo —la base de datos, el compilador, el navegador, el propio PHP—
se le pregunta a él antes que a nuestra lógica.** Una comprobación que confronta lo que produjo
el generador contra lo que el generador debería producir solo demuestra que sabemos repetir
nuestro propio razonamiento.

**El caso que la funda**: la prueba de `dropScript()` no validó el orden contra nuestro grafo.
Lo invirtió y dejó que **MariaDB** dijera `Cannot delete or update a parent row`. Y la de
`createScript()` hace lo mismo del otro lado: aplica el script al revés y **exige el errno
150**. Si la base lo aceptara, el orden no demostraría nada.

**Esa misma prueba encontró después lo que ninguna lectura había visto**: 20 de las 33 tablas
del proyecto **no se pueden crear desde sus propios mappers** (T52). Nadie lo había notado en
años de leer el código.

#### Registro de suites: quién juzga cada una

*Método: por archivo, se cuentan las llamadas que salen del proceso —conexión a base, HTTP,
sistema de archivos— frente a las que solo leen código con reflexión o expresiones regulares.*

| Suite | Quién juzga | Cuidado |
| :-- | :-- | :-- |
| `core/scheme-sql-round-trip` | **MariaDB** — aplica los dos scripts de verdad | — |
| `core/db-backup-round-trip` | **MariaDB** + `password_verify()` | — |
| `core/mapper-finders` | **MariaDB** | — |
| `core/otp-fresh-user` | **MariaDB** — cuenta filas y estados reales | — |
| `core/helpers-directories` | **El sistema de archivos** | — |
| `core/http-client` | **Un servidor HTTP real** | **Depende de un `webhook.site` ajeno que caducará.** Backlog |
| `core/database-exporter` | Mixto: base + archivo | — |
| `core/otp-write-separation` | **Mixto** — tres comprobaciones leen el cuerpo del método | Las que leen ya pasaron en verde con el defecto puesto (T46). La que discrimina es la que cuenta filas |
| `core/meta-property-hybrid` | **Se juzga sola** — reflexión sobre dos copias | Responde «¿contiene X?», no «¿hace X?» |
| `core/session-user` | **Se juzga sola** — compara valores devueltos | Es la forma en que se coló el inverso del 2FA sin efecto (T50) |
| `functions/systemOutFormatted` | **Se juzga sola** | — |
| `verify-integrity` | **Se juzga sola**, salvo el analizador léxico de PHP | Emite ÓRDENES: por eso se le exige más que a las demás |

**Las cinco últimas filas son las que hay que mirar con más cuidado**, y las dos con nota
propia ya han dejado pasar un defecto cada una.

## T22 · `MetaProperty` — DECIDIDO: el defecto es el FQCN compartido, y se arregla mudando

**Decisión del propietario. Es plan, no está ejecutado.** Nada de código todavía.

### Primero, una corrección: el diagnóstico anterior era impreciso

Se dijo que el paquete emparejaba `EntityMapper` con un `MetaProperty` de sabor `ORM`.
**Es falso, y se comprobó:** `EntityMapper.php` del paquete **no menciona `MetaProperty`
ni una vez** (`grep -c` → 0). Quien la usa es **`ExtensibleORM`**, que `extends ORM`.

Así que **los dos repositorios están internamente bien**:

| Repositorio | Quién usa `MetaProperty` | Contra qué linaje está escrita |
| :-- | :-- | :-- |
| piecesphp | `EntityMapperExtensible` (linaje `EntityMapper`) | `EntityMapper` — delega en `EntityMapper::validateType()` |
| `piecesphp/database` | `ExtensibleORM` (linaje `ORM`) | `ORM` — trae su propia `validateType()` porque `ORM` no ofrece ninguna |

Cada copia está escrita para su base, y su base es la que la usa. **El único defecto es que
las dos ocupan el mismo FQCN.** La palabra «híbrido» sobraba: no hay un Frankenstein de dos
linajes, hay **dos casas correctas discutiéndose un nombre**.

### La decisión

1. **`ORM` se queda: tiene futuro.** `EntityMapperExtensible` **no** va al paquete y **nadie
   migra ningún mapper**.
2. **La copia del framework se queda** ocupando `PiecesPHP\Core\Database\Meta\MetaProperty`.
3. **La del paquete se muda a `PiecesPHP\Core\Database\ORM\Meta\MetaProperty`.** Mismo
   nombre de clase: **lo que estaba mal era la ubicación, no el nombre.** Se actualizan los
   `use` de `ExtensibleORM.php`, `unit-tests/UnitTest-MetaUtil.php` y
   `unit-tests/UnitTest-ActiveRecord.php`.
4. **Desaparecen** la entrada de `KNOWN_ECLIPSES` y la suite `core/meta-property-hybrid`:
   sin colisión, no tienen nada que vigilar ni que probar. La puerta seguirá ahí para la
   siguiente.

### Por qué esto DESBLOQUEA a `ORM` en vez de enterrarlo — demostrado, no supuesto

Hoy `Meta\MetaProperty` resuelve **siempre** a la copia del núcleo. Un mapper nuevo montado
sobre `ExtensibleORM` **dentro de piecesphp** recibiría esa copia. Y no falla de forma
evidente:

```
validateValue(instancia de ExtensibleORM) -> false
setValue LANZÓ: ExtensibleORM::__construct(): Argument #1 ($findValue) must be of type
                ?int, ExtensibleORM given, llamado en .../Meta/MetaProperty.php:207
```

Dos cosas a la vez. La validación de tipo mapper **rechaza el linaje `ORM`** —comprueba
`is_subclass_of($value, EntityMapper::class)` y `ExtensibleORM` no lo es—; y al rechazarlo
entra en la conversión de estilo `EntityMapper`, `new $mapper($value)`, que **revienta con
un `TypeError`** porque la copia del paquete habría hecho ahí
`call_user_func($mapper, 'getInstance', …)`.

> **Mientras compartan nombre, `ORM` no se puede usar dentro de piecesphp.** Mudar el
> archivo no es higiene: es lo único que devuelve `ORM` al terreno de juego.

### Las validaciones NO se homologan

**Descartada la base abstracta que se había sugerido.** Cada una se completa con lo que le
falta y le sirve de la otra, **sin mezclarlas**:

| | Le falta | De dónde sale |
| :-- | :-- | :-- |
| La de `ORM` | `internalName` y `getType()` | De la del framework |
| La del framework | `TYPE_DATE` con `'now'` | De la de `ORM` |

**La validación se queda distinta a propósito**: la del framework delega en
`EntityMapper::validateType()`; la de `ORM` carga la suya **porque `ORM` no ofrece
ninguna**. Homologarlas sería inventar un acoplamiento que hoy no existe.

**Cada archivo lleva una línea diciendo que son paralelos a propósito**, para que el
siguiente que las vea no crea que descubrió una duplicación.

### Pregunta aparcada

**La viabilidad de migrar de `EntityMapper` a `ORM`.** No se responde aquí y no bloquea nada
de lo anterior.

### El principio, que vale para las dos mitades de esta ventana

`routeName()` eran **copias de LA MISMA COSA escrita distinto**: se unifican.
`MetaProperty` son **COSAS DISTINTAS que se parecen**: se separan.

> **SE UNIFICA LO QUE ES LO MISMO; SE SEPARA LO QUE SOLO LO PARECE.**

Y las dos veces **lo distinguió la medición, no la intuición**: en `routeName`, comparar
cuerpos por tokens; en `MetaProperty`, mirar quién usa a quién y contra qué clase base está
escrito cada archivo. La intuición decía «duplicado» en los dos casos y acertó en uno.

## T23 · T21 APLICADO HACIA ATRÁS — todas las puertas con su fallo provocado

**Encargo: «una puerta que solo se ha visto en verde no se ha visto funcionar» no vale solo
para las nuevas.** Se provocó el fallo de cada puerta que llevaba meses en verde, con la
mutación exacta anotada para que se pueda repetir.

### Las puertas que sí gritan

| Puerta | Mutación exacta | Qué hizo |
| :-- | :-- | :-- |
| `verify-integrity` · docblocks **y** firmas | Quitar el `*/` del docblock de `ExifHelper::__construct` | **Reproduce el incidente original**: `php -l` dice *No syntax errors*, `method_exists()` dice **false** —el método desapareció— y la tarea sale **1** avisando por partida doble, `DOCBLOCK` y `FIRMA` |
| `verify-integrity` · rutas PSR-4 | Dos sondas en `src/app/classes/ZzProbe/`: una con `namespace Espacio\Equivocado`, otra `extends \No\Existe\Padre` | Salida **1**: *«declara `Espacio\Equivocado\Broken` y su ruta exige `ZzProbe\Broken`»* y *«Class "No\Existe\Padre" not found»* |
| `verify-integrity` · eclipses, ida | Crear `Core/HTML/Exceptions/MalformedChildException.php`, que ya existe en el paquete `html` | Salida **1**, nombrando el paquete |
| `verify-integrity` · eclipses, vuelta | Añadir a `KNOWN_ECLIPSES` una entrada que no colisiona con nada | Salida **1**: *«la entrada sobrevivió a su motivo»* |
| `bin/phpstan-deadcode` | Cambiar el texto del delimitador del bloque en `bin/phpstan.neon` | Salida **1**: *«No se encontraron los delimitadores»*. **No mide de menos: se niega a medir** |
| `unit-tests:core/mapper-finders` | `NewsMapper::getBy()` devuelve `false` en vez de `null` cuando no encuentra | **19/20**, señalando el mapper |
| `unit-tests:core/session-user` | `SessionToken::getJWTReceived()` devuelve `null` antes de nada | **2 fallos**, los dos del contrato de cadena vacía |
| `unit-tests:core/otp-write-separation` | Meter `if (false) { $probe->save(); }` dentro de `getOTPData()` | **5/6**. Caza una escritura **inalcanzable**, que es lo correcto: ahí no puede haber una escritura ni escrita |
| `unit-tests:core/meta-property-hybrid` | Quitar el bautizo de `EntityMapperExtensible::addMetaProperty()` | **10/12** |

### Los cinco hallazgos

**H1 · `bin/cli` devolvía 0 aunque la aplicación muriera al arrancar — CORREGIDO, y era lo
más grave del lote.**

Con un `ParseError` en un archivo de `src/app`, la CLI escupía el JSON del manejador de
excepciones **y salía con código 0**; `verify-integrity` no llegaba a ejecutarse. **Cada vez
que en esta campaña se dijo «verify-integrity en verde» se apoyaba en un código de salida que
no distinguía «pasé» de «no me ejecuté».**

**No hacía falta rediseñar la CLI: era UNA LÍNEA.** `global_custom_exception_handler()`
terminaba en `die($content)`, y **`die()` con un argumento de tipo cadena imprime y sale con
código CERO**. Comprobado antes de tocar nada:

```bash
php -r 'die("hola\n");' ; echo $?     # -> 0
php -r 'echo "hola\n"; exit(1);'; echo $?   # -> 1
```

Ahora imprime y hace `exit(PHP_SAPI === 'cli' ? 1 : 0)`. **En HTTP no cambia nada**: ahí el
código de salida no lo lee nadie y quien manda es el 500 que ya se envió.

**Provocado y demostrado**, inyectando un error de sintaxis real en un archivo que la
aplicación carga al arrancar:

| Comando | Antes | Ahora |
| :-- | --: | --: |
| `bin/cli verify-integrity` con el árbol roto | **0** | **1** |
| `bin/cli unit-tests:core/session-user` con el árbol roto | **0** | **1** |
| Los mismos con el árbol sano | 0 | **0** |

**Lo que NO cubre, para que nadie confíe de más**: un fatal incatchable de verdad —agotar la
memoria, por ejemplo— no pasa por este manejador. Ahí PHP sale con su propio código, que
tampoco es cero, así que la puerta tampoco miente. **No hay `register_shutdown_function` en
todo el proyecto**: si algún día quiere cubrirse ese hueco con un mensaje propio, ese es el
sitio.

**H2 · `unit-tests:functions/systemOutFormatted` fallaba 7 de 10 y decía que todo iba bien.**
No hizo falta mutarla: **ya estaba roja**. `systemOutFormatted()` tiene **dos contratos** —con
terminal emite ANSI, sin terminal los suprime a propósito (`Cli.php`, rama `$isTty`)— y la
suite afirmaba el primero siempre, así que fallaba en cuanto la salida se redirigía. Y como
no contaba nada, **devolvía éxito igual**.

Comprobado con un segundo método, como manda T20: bajo pseudo-terminal
(`script -qec … /dev/null`) pasa **10/10**; redirigida, **7 fallos**. *Corregido*: las siete
comprobaciones que exigen ANSI se **omiten con su razón** sin terminal, y la suite tiene
balance y resultado real. Probada después: mutar `'red' => 32` en `Cli.php` la deja en
**8/10**.

**H3 · El trinquete del baseline NO EXISTÍA.** `CLAUDE.md` mandaba comparar contra
`PHPStanResult.Summary.baseline.txt` y **nada lo comparaba**: era una instrucción para un
humano, no una puerta. No podía fallar porque no se ejecutaba nunca.

*Corregido*: `bin/phpstan-process-result.php` compara al final, **instancias contra
instancias**, y sale con **1** si el total sube. Probado en las dos direcciones —bajar el
baseline a 800 da *TRINQUETE ROTO (+77)*; romper su cabecera da *«la comparación NO se
hizo»*—. Esa segunda dirección es la que importa: **una puerta que no puede medir no puede
aprobar.**

El baseline pasa de **968 a 877**, con su nota de método dentro del propio archivo.

**H4 · `unit-tests:core/database-exporter` no miraba la sintaxis.** Comprobaba el
**contenido** —filtro `WHERE`, enmascarado GDPR, `include_data`, hex-blob— y **jamás si el
archivo estaba bien formado**. Añadiendo `"ROTO"` a cada fila de `JsonFormat`, el JSON
generado quedó ilegible —799 marcas, `json_decode` → *Syntax error*— y la suite dio
**23/23**.

*Corregido*: valida JSON con `json_decode()` y XML con `simplexml_load_string()`. Probado:
con la mutación puesta, **21/23**.

**H5 · Y esa comprobación nueva destapó un defecto real de producción: el XML exportado no
lo puede leer ningún parser.** Dos defectos encadenados:

- **H5a — `encoding="utf8mb4"`.** El plugin metía el charset de MySQL en la declaración XML.
  `utf8mb4` **no es un encoding XML**, así que un parser estándar rechaza el documento
  **entero** por la primera línea. Confirmado por dos parsers independientes —`libxml` y
  `xmllint`—. **Corregido**: `XmlFormat` traduce los nombres de MySQL a los nombres IANA.
- **H5b — bytes de control crudos. CORREGIDO.** Con la cabecera ya arreglada, el XML seguía
  siendo inválido: *«PCDATA invalid Char value 26»*, *«Char 0x0 out of allowed range»*, por
  una columna BLOB escrita tal cual.

**La detección era incorrecta de raíz.** El plugin preguntaba
`!mb_check_encoding($val, 'UTF-8')` para decidir si un valor era binario, y eso **no responde
esa pregunta**: los bytes de un PNG son UTF-8 perfectamente válido, así que la rama de
hexadecimal no se activaba nunca.

> **Lo que decide que una columna es binaria es SU TIPO, y está en los metadatos del
> esquema.** `SqlFormat` ya lo hacía —`SHOW COLUMNS` más un `preg_match` sobre
> `binary|blob|varbinary`—; `XmlFormat` no. Ahora sí, con el mismo criterio.

Y por debajo, **una red que no depende de `hex_blob`**: si un valor conserva algún carácter
que XML 1.0 prohíbe —los controles `0x00–0x08`, `0x0B`, `0x0C`, `0x0E–0x1F`, que
`htmlspecialchars()` no toca porque no son caracteres *especiales* sino *prohibidos*—, ese
valor también sale en hexadecimal. Una columna de texto con basura dentro no puede tumbar el
documento entero.

**Verificado con dos parsers independientes**: `xmllint --noout` y `simplexml_load_file()`
dan válido, y el BLOB aparece como `0x3f504e47…`. La suite vuelve a **23/23**.

**No se dejó en rojo a propósito, y la razón para no tocarlo no se sostenía**: el dato
exportado hoy **no se puede leer**. No se rompía algo que funcionaba; se arreglaba algo roto.

### HALLAZGO NUEVO, ABIERTO: se pierde un byte entre escribir y leer el BLOB

Al comprobar el hexadecimal apareció otra cosa. La semilla inserta un PNG real:

```php
$binaryData = "\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDR\x00\x00\x00\x01";
```

y **las dos** exportaciones —SQL y XML, que leen con el mismo `SELECT *`— devuelven
`0x3f504e470d0a1a0a…`. El primer byte es **`3f`, que es `?`**: el `\x89` no sobrevivió al
viaje.

**No es un defecto del exportador**: los dos formatos coinciden, así que el byte ya venía
perdido. Apunta a la capa de base de datos —el `charset` de la conexión aplicándose a un
parámetro que debería viajar como binario—.

> **Ventana propia.** Afecta a cualquier dato binario del framework, no solo al exportador, y
> comprobarlo bien exige leer de vuelta lo insertado y comparar byte a byte. **No se toca sin
> decidirlo**: si el defecto está en la escritura, los datos ya guardados en los despliegues
> están dañados y eso cambia por completo qué significa «arreglarlo».

### Lo que enseña el ejercicio

De doce puertas, **nueve funcionaban**, **una no existía** (H3), **una llevaba meses roja
diciendo que iba bien** (H2) y **una tenía un agujero por el que se coló un defecto de
producción** (H4 → H5). **Una de cada cuatro.**

Y el orden importa: **H5 no lo encontró nadie mirando el exportador. Lo encontró la
comprobación que se añadió al probar que la puerta no fallaba.** Provocar el fallo de una
puerta no valida la puerta: valida lo que hay detrás.

## T24 · MAPA DE LAS 373 RAMAS — **DEROGADO por T29**

> Se hizo sobre 373 y antes de cerrar el punto del trait. **El mapa vigente es T29**, sobre
> las 309 que quedan. Esto se conserva por la parte que sigue valiendo: el dato de que 89
> ramas vivían dentro de los tres métodos de ruta, que fue lo que decidió el orden.

**El «235 borrables» está derogado** (ver T13). Este es el mapa sobre la medición nueva.

### El instrumento, columna por columna

| Columna | Con qué se midió |
| :-- | :-- |
| **Ramas** | `bin/phpstan-deadcode`: resta de la corrida con el bloque de ignores y sin él. **Unidad: tripletas** (ruta, línea, mensaje) |
| **Zona** | La RUTA del archivo: `/view/` o `/Views/` → vistas; `*Mapper.php` → mappers; `local-tests/` → suites; `src/app/config/` → config; `src/app/core/` → núcleo; `*Controller.php` → controladores; el resto, otros |
| **Dentro del andamio** | Rango de líneas de `routeName`, `allowedRoute` y `_allowedRoute` calculado **por tokens**, no por sangría |

### EL DATO QUE MANDA EN EL ORDEN

> **89 de las 373 ramas —el 24 %— viven DENTRO de `routeName`, `allowedRoute` o
> `_allowedRoute`.** Es decir, **dentro del andamio que el punto 2 va a borrar**.

| Identificador | Ramas dentro del andamio |
| :-- | --: |
| `function.alreadyNarrowedType` | 44 |
| `isset.variable` | 44 |
| `if.alwaysTrue` | 1 |

De los 50 `isset.variable`, **44 son el `isset($_POST)` / `isset($_GET)` del closure
`$getParam`** que casi todos los `_allowedRoute` copian y que casi ninguno usa.

**Conclusión de orden: el mapa se rehace DESPUÉS del punto 2, no antes.** Un cuarto del
trabajo desaparece solo, y medirlo ahora es medir algo que va a dejar de existir.

### Reparto por familia — el mapa, para cuando toque

| Ramas | Identificador | Zona dominante | Veredicto de la muestra verificada a mano |
| --: | :-- | :-- | :-- |
| **129** | `function.alreadyNarrowedType` | controladores 58, núcleo 36 | 44 mueren con el andamio. Del resto, `is_array()` sobre `array`: resto defensivo puro |
| **50** | `isset.variable` | controladores 45 | **44 mueren con el andamio.** Quedan 6 |
| **42** | `notIdentical.alwaysTrue` | controladores 35 | `$userMapper !== null` sobre un valor ya estrechado. Borrable **con cuidado**: es una condición viva, no un hueco |
| **24** | `foreach.emptyArray` | mappers 24 | **Hueco de plantilla puro.** `$defaultPropertiesValues = []` seguido de su `foreach`. Se borran el array y el bucle |
| **21** | `if.alwaysTrue` | otros 9, controladores 6 | Mezclado. Sin veredicto de familia |
| **20** | `if.alwaysFalse` | otros 12, controladores 7 | **10 de 20 son el `$showSQL` de los `<Modulo>Routes`: NO SE TOCAN** |
| **13** | `function.impossibleType` | — | Sin verificar |
| **12** | `ternary.alwaysTrue` | — | Sin verificar |
| **11** | `nullCoalesce.offset` | — | Sin verificar |
| **10** | `booleanNot.alwaysTrue` | — | Sin verificar |
| **5** | `catch.neverThrown` | — | **NO SE BORRAN sin comprobar el manejador de errores.** Ver abajo |
| **4** | `deadCode.unreachable` | `SystemApprovalManager` (3) | Sin verificar |
| **32** | *(las 14 familias restantes, ≤3 cada una)* | — | Sin verificar |

### Las dos familias que parecen mecánicas y NO lo son

**`if.alwaysFalse` — la mitad es una herramienta documentada.** `$showSQL = false;` seguido
de `if ($showSQL)` es el bloque que la **regla 7 de `CLAUDE.md`** manda usar para sacar el
DDL de un módulo. Borrarlo no limpia: **quita la forma sancionada de generar el SQL de las
tablas**. Diez de las veinte.

**`catch.neverThrown` — «nunca se lanza» según PHPStan NO es «nunca se lanza» aquí.**
`AppHelpers.php:2600` envuelve un `return $roles[$e];` en un `try/catch (\Throwable)`. Un
índice inexistente emite un **`E_WARNING`, no una excepción**, así que PHPStan tiene razón
mirando solo el lenguaje. Pero **el manejador de `bootstrap.php` promueve `E_WARNING` a
excepción**, así que en ejecución ese `catch` **sí es alcanzable**.

> **Es el caso más peligroso de todo el lote**: un análisis correcto y una conclusión falsa,
> porque la herramienta no ve la configuración del manejador de errores. Los cinco se miran
> uno a uno, y ninguno se borra sin comprobar qué puede emitir el bloque `try`.

### Regla de trabajo para esta ventana

1. **Primero el punto 2.** Se lleva 89 ramas por delante.
2. **Volver a medir** con `bin/phpstan-deadcode`.
3. **Familia por familia**, y cada una con su muestra verificada a mano **antes** de tocarla —
   nunca la familia entera de golpe por parecerse a otra que sí era andamio.
4. Y sigue valiendo T17: **una regla mecánica se aplica a toda la familia o a ninguna.**

## T25 · UTILIDADES CLONADAS — medido, NO ejecutado

**La misma enfermedad que `routeName`, a mayor escala.** Solo medición: la ejecución va en su
propia ventana.

### Identidad POR CONTENIDO, no por número de líneas

Que coincida el conteo de líneas no es que coincida el archivo (T20). Se comparan **tokens**,
descartando comentarios y espacios, y **neutralizando el `namespace`** —que es lo único que
TIENE que diferir—:

| Utilidad | Archivos | Contenidos DISTINTOS | Reparto |
| :-- | --: | --: | :-- |
| `SafeException` | **17** | **2** | 16 idénticas + 1 distinta (`PiecesPHP\UserSystem`) |
| `DuplicateException` | **13** | **2** | 12 idénticas + 1 distinta (`Publications`) |
| `FieldTranslationUtility` | **3** | **3** | las tres distintas |
| `AttachmentPackage` | **2** | **2** | las dos distintas |
| **TOTAL** | **35** | | ~1.750 líneas |

Comando: `token_get_all()` por archivo, fuera `T_COMMENT`/`T_DOC_COMMENT`/`T_WHITESPACE`, el
`namespace` sustituido por un marcador, y `md5` del resultado.

### Las dos raras SÍ añaden algo real, y deciden el diseño

| Archivo | Qué añade |
| :-- | :-- |
| `PiecesPHP\UserSystem\Exceptions\SafeException` | `const USER_NOT_EXISTS = 1;` y su entrada en `CODES` |
| `Publications\Exceptions\DuplicateException` | `const PUBLICATION_CODE = 1;`, `const CATEGORY_CODE = 2;` y sus entradas en `CODES` |

**Lo que hacen esos códigos**: el constructor valida `in_array($code, self::CODES)` y, si no
está, lo degrada a `UNDEFINED_CODE`. Es decir, **la lista de códigos es el contrato de la
excepción**, no decoración.

> **TRAMPA DE DISEÑO, y es silenciosa.** El constructor usa **`self::CODES`**. Hoy funciona
> porque cada módulo tiene su propia clase. En una clase centralizada con subclases por
> módulo, `self::` seguiría resolviendo a la lista del PADRE, así que **el código específico
> del módulo se degradaría a `UNDEFINED_CODE` sin un solo error**. La clase centralizada
> **tiene que usar `static::CODES`** —enlace estático tardío—, y eso tiene que ir probado.

### `FieldTranslationUtility`: el tipo del mapper ES la única variación — comprobado

Los tres archivos difieren **solo** en: `namespace`, el `use` del mapper, la línea `@package`
del docblock, y el tipo del mapper en **tres** sitios (`@var`, el constructor y `mapper()`).
Nada más.

Y **los tres mappers extienden `EntityMapperExtensible`**:

| Mapper | Padre |
| :-- | :-- |
| `PublicationMapper` | `EntityMapperExtensible` |
| `ApplicationCallsMapper` | `EntityMapperExtensible` |
| `InterestResearchAreasMapper` | `EntityMapperExtensible` |

> **La abstracción es tipar por ahí y la clase se vuelve UNA.** Sin genéricos, sin
> configuración: cambiar tres firmas de `XMapper` a `EntityMapperExtensible`.

`AttachmentPackage` es el mismo caso con un grado más de trabajo: además del tipo del mapper
de adjuntos, **renombra una propiedad** (`$publicationID` contra `$applicationCallID`).

### EL RIESGO QUE HACE ESTO DELICADO PESE A PARECER TRIVIAL

**Cada módulo captura SU clase por FQCN**, resuelto por el `use` de cabecera. Medido:

| | |
| :-- | --: |
| Sitios que **capturan** `SafeException` | **35** (22 sueltos, 12 en `SafeException \| DuplicateException`, 1 con otro nombre de variable) |
| Sitios que **capturan** `DuplicateException` | **12** (todos dentro de esa unión) |
| `throw new SafeException` | **33** |
| `throw new DuplicateException` | **15** |
| Archivos que las importan con `use` | **25** |

> **Si se unifican y queda un `use` viejo sin actualizar, PHP NO FALLA.** El `catch`
> simplemente deja de capturar y la excepción sigue subiendo, en silencio, hasta el
> manejador global. Un 500 en vez de un mensaje de validación, y ni un aviso que lo explique.

**La puerta que cualquier plan tiene que incluir**: *toda clase nombrada en un `catch` debe
resolver a una clase existente*. Y **no la cubre nada de lo que hay hoy**: la tercera
comprobación de `verify-integrity` valida que las clases DECLARADAS se carguen, y su propio
docblock ya avisa de que **un `use` que falta y solo se usa dentro de un método se le
escapa** — que es exactamente este caso.

### Lo que NO cura borrar los módulos condenados

Borrar `ApplicationCalls`, `ImagesRepository` e `InterestResearchAreas` se lleva unas cuantas
copias **y no cura nada**: el próximo módulo clonado desde `Publications` las trae otra vez.
**La enfermedad es la plantilla, no las copias.**

## T26 · DOS PREGUNTAS DE SEGURIDAD ABIERTAS SOBRE EL ENRUTADO

**Las dos son PREEXISTENTES y las dos viven ahora en `ControllerRoutingTrait`, o sea en los
44 controladores a la vez.** No se tocan sin decisión explícita: **cambiar cualquiera de las
dos cambia quién ve qué en toda la aplicación**.

Que estén escritas aquí es el punto. Antes estaban repartidas en 44 copias y nadie las había
mirado juntas.

### 1 · Generar una URL EJECUTA la comprobación de permiso

`routeName()` llama a `Roles::hasPermissions()` y a `_allowedRoute()` **cada vez que se
construye una cadena**. Hay **606 sitios de llamada** en el código.

Es lo que hace que `allowedRoute()` funcione —una ruta no permitida devuelve cadena vacía, y
de ahí sale la visibilidad de menús y botones—, así que **no es un accidente**. Pero merece
revisarse algún día: `_allowedRoute()` de los módulos con reglas de propiedad **consulta la
base de datos** (`getBy($id)`, y en varios además `UsersModel::getBy` y
`OrganizationMapper::getBy`). Pintar un listado con veinte botones de editar puede disparar
sesenta consultas para decidir si se pintan.

### 2 · SIN USUARIO, CONCEDE

El cuerpo canónico dice:

```php
$current_user = getLoggedFrameworkUser();

if ($current_user !== null) {
    $allowed = Roles::hasPermissions($name, $current_user->type);
} else {
    $allowed = true;          // <-- aquí
}
```

**Anónimo ⇒ permitido.** Para la zona pública tiene sentido: sus rutas no tienen restricción
de rol y el control real está en el middleware de sesión, no aquí.

**Lo que lo vuelve una pregunta y no una nota** es cómo se obtiene ese `null`:

> `getLoggedFrameworkUser()` devuelve `null` **también cuando el constructor de
> `UserDataPackage` lanza** —`AppHelpers.php:3047-3053`: el `catch` deja `$currentUser` en
> `null` y registra la excepción con `log_exception()`, así que **queda rastro en el log,
> pero quien llama no distingue ese caso de «no hay sesión»**—. Es decir: un fallo al
> construir al usuario se trata como «no hay usuario», y **«no hay usuario» concede**.

No es una vulnerabilidad demostrada: para explotarla habría que provocar ese fallo, y aun
concediendo el nombre de la ruta queda el control de acceso de la petición real. Pero **la
dirección del fallo es la equivocada**: ante un error, un sistema de permisos debería cerrar,
no abrir.

**Una buena noticia primero, que conviene decir**: **antes del trait esto estaba en 44 sitios
y ahora está en uno.** El trait no creó el problema — lo volvió **arreglable**. Corregirlo era
antes una campaña de 44 archivos; hoy es una línea en un sitio.

#### PRECONDICIÓN: no se arregla cambiando el `else` a `false`

**Hoy `getLoggedFrameworkUser()` NO PUEDE distinguir los dos casos.** Devuelve `null` tanto
si no hay sesión como si el constructor lanzó, así que cerrar sin distinguir **negaría el
acceso a los anónimos en las rutas públicas** — y eso hoy es correcto: la zona pública no
tiene restricción de rol y su control real está en el middleware de sesión.

> **Primero hay que hacer los dos casos DISTINGUIBLES**, y solo entonces se puede cerrar el
> borde sin romper lo público. Dos formas, las dos aceptables:
>
> - **Un accesor aparte** que diga si hubo fallo de construcción —o que devuelva el estado en
>   vez de solo el usuario—.
> - **Que el fallo de construcción se propague** en vez de convertirse en `null`, y que quien
>   quiera tolerarlo lo capture explícitamente.
>
> **Sin esa precondición, la pregunta no se puede responder**: cualquier respuesta rompe uno
> de los dos casos.

Relacionado: `DataImportExportUtility` conserva su `routeName()` propio precisamente porque
toma el usuario por otra vía y **no cae en este borde** — es más restrictivo. Está en
`KNOWN_ROUTE_OVERRIDES` con esa razón.

### Opción futura, NO implementar: una interfaz que haga obligatorio el contrato

**Sola no sirve.** Una interfaz no lleva implementación por defecto, así que obligaría a cada
controlador a escribir su copia de los tres métodos y **volveríamos al andamio, ahora
obligatorio**. Además `_allowedRoute()` es `private static` y las interfaces solo declaran
miembros públicos, y `BaseController` lo extienden también controladores que no son de rutas.

**La forma correcta es interfaz MÁS trait**: la interfaz declara el contrato público
—`routeName()` y `allowedRoute()`—, el trait lo implementa, y el controlador declara las dos
cosas. Se añade encima de lo que hay **sin conflicto y sin tocar ningún cuerpo**.

## T27 · `catch.neverThrown` y `if.alwaysFalse` — dos familias que NO eran familias

**El encargo era buscar la expresión, no arreglar cinco casos.** La conclusión fue mejor y
peor que eso: en configuración **no se puede expresar**, pero **tampoco hacía falta**, porque
los cinco no eran el mismo caso.

### La configuración: probada y descartada, con el comando

PHPStan 2.2.8 ofrece, bajo `parameters.exceptions`:

```
implicitThrows: true
reportUncheckedExceptionDeadCatch: true
uncheckedExceptionClasses / uncheckedExceptionRegexes
checkedExceptionClasses / checkedExceptionRegexes
```

Se probó `reportUncheckedExceptionDeadCatch: false` y **no mueve ni uno de los cinco**.

> **Y la razón explica por qué ninguna opción va a servir:** el desajuste no es entre
> excepciones comprobadas y no comprobadas. Es que **PHPStan modela el LENGUAJE**, y aquí
> manda además el **manejador de errores**. Ninguna opción de un analizador estático puede
> saber que `bootstrap.php` convierte un `E_WARNING` en excepción.

### Los cinco, mirados uno a uno: TRES eran muertos de verdad

| Caso | Qué había en el `try` | Veredicto |
| :-- | :-- | :-- |
| `BundleTask.php:205` | Una asignación de cadena | **Muerto. Borrado** |
| `api-keys.php:32` | El cuerpo **entero comentado** | **Muerto. Borrado** |
| `CustomSlimErrorHandler.php:79` | `$exception->getCode()` | **Muerto. Borrado** |
| `LoginAttemptsModel.php:122` | `$mapper->extra_data = …` | **VIVO** |
| `AppHelpers.php:2600` | `return $roles[$e];` | **VIVO** |

**Y los dos vivos lo son por razones DISTINTAS**, lo cual es el hallazgo:

1. **`LoginAttemptsModel`** — la asignación pasa por **`__set()`**, que lanza
   `DatabaseClassesExceptions` (`EntityMapper.php:529-534`). PHPStan no ve a través de los
   métodos mágicos. **No tiene nada que ver con el manejador de errores.**
2. **`AppHelpers`** — `$roles[$e]` sobre una clave inexistente emite un **`E_WARNING`**, y el
   manejador lo **promueve a excepción**. Este sí es el desajuste del manejador.

> **«Los cinco casos de una familia» eran tres muertos y dos vivos por dos motivos que no se
> parecen.** Suprimir la familia entera —que es lo que había— tapaba las tres oportunidades
> de limpieza reales **y** los dos avisos genuinos, con el mismo silencio.

**Resultado**: el `- identifier: catch.neverThrown` de familia entera desaparece del bloque
de código muerto y se convierte en **una supresión por ruta, para dos archivos, con las dos
razones escritas**. La próxima que aparezca tendrá que mirarse en vez de heredar el silencio.

### `if.alwaysFalse`: 11 son interruptores y 9 son candidatos

Medido **por ruta**, no por contexto de texto —la primera cuenta dijo «10 de 20» con un grep
de tres líneas; el conteo por ruta da **11 de 20**—:

| Cuántos | Dónde | Qué son |
| --: | :-- | :-- |
| **10** | `*Routes.php` | El bloque `$showSQL = false; … if ($showSQL)` que **la regla 7 de `CLAUDE.md` manda usar** para sacar el DDL |
| **1** | `DataImportExportUtilityRoutes.php:42` | `const ENABLE = false;` **literal**: el módulo está apagado en árbol |
| **9** | Controladores y núcleo | Candidatos de verdad, quedan en el mapa de T24 |

**Los 11 pasan a supresión PERMANENTE por ruta, sin condición de retirada.** No es deuda:
borrarlos quitaría la forma sancionada de generar el SQL de las tablas.

**Los 9 quedan en supresión TEMPORAL**, dentro del bloque de código muerto y por tanto
todavía contados como ramas muertas, con su condición de retirada escrita: cuando se hayan
mirado uno a uno.

### EL TRINQUETE SE DISPARÓ, Y ERA CONTRA MÍ

Al sacar `if.alwaysFalse` del bloque de familia, los 9 de fuera de `*Routes.php` quedaron sin
silenciar y el total subió:

```
TRINQUETE ROTO: 886 errores contra un baseline de 877 (+9).
```

**La puerta que se construyó hace unas horas atrapó una regresión propia el mismo día**, y en
el paso siguiente al que la creó. Es la mejor demostración de por qué H3 —que no existiera—
era grave: sin ella, esos nueve errores se habrían colado en el baseline sin que nadie los
notase.

### LA REGLA QUE FALTABA: comprobar la HOMOGENEIDAD antes de suprimir una familia

Es tentador leer esto como «prefiere mirar caso a caso antes que configurar». **No es eso**,
y la prueba está a dos secciones de distancia:

| Familia | ¿Homogénea? | Qué funcionó |
| :-- | :-- | :-- |
| Los 25 interruptores de módulo (T13) | **SÍ**: todos constantes de `config/constants.php`, todos por la misma razón | Un bloque de configuración, `dynamicConstantNames` |
| Los 5 `catch.neverThrown` | **NO**: tres muertos, uno por `__set()`, uno por el manejador de errores | Mirarlos uno a uno |
| Los 20 `if.alwaysFalse` | **NO**: 11 interruptores documentados, 9 candidatos | Partirla en dos |

> **Antes de suprimir una familia entera, comprueba que sus miembros están ahí POR LA MISMA
> RAZÓN.** Si las razones difieren, la supresión de familia no simplifica: **tapa con el
> mismo silencio los casos que había que arreglar y los que había que respetar.**

Es la otra cara de T17. T17 dice que una regla mecánica se aplica a toda la familia o a
ninguna; esta dice **qué es una familia**: no los que comparten identificador, sino los que
comparten motivo.

#### Un tercer caso, y este es el que más enseña: lo incumplió QUIEN LA APROBÓ

Las **22 `offsetAccess.invalidOffset`** se aprobaron como «una familia, las 22 o ninguna»
**la misma semana en que se escribió esta regla**. Al mirarlas de cerca eran **dos**:

- **13** son `$options[$id]` — el constructor de listas desplegables, donde la clave vacía es
  la opción de marcador. `(string)` es la migración prescrita.
- **9** son `$_FILES[$nameOnFiles]['name']` — leer `$_FILES` con una clave nulable. Ahí
  `(string)` daría `$_FILES['']`, que **no es la intención**: falta una guarda.

> **No fue un fallo de medición: fue de criterio.** La medición estaba bien y decía
> «`offsetAccess.invalidOffset`, 22». Lo que falló fue tratar el identificador como si fuera
> el motivo — que es exactamente lo que esta regla prohíbe.

**Escribir una regla no inmuniza contra ella.** Hay que aplicarla en el momento de decidir, y
ese momento no se anuncia.

### Efecto medido

| | Antes | Después |
| :-- | --: | --: |
| Baseline visible (instancias) | 877 | **877** |
| Ramas muertas (tripletas) | 325 | **309** |

De las 16 que salen: **3 borradas** de verdad y **13 reclasificadas** —2 `catch.neverThrown`
y 11 `if.alwaysFalse`— que **nunca fueron ramas muertas** y ahora están donde debían, en el
registro documentado.

## T28 · EL BYTE DEL PNG — respondido: **el daño está en la ESCRITURA**

**Todo lo de aquí es de SOLO LECTURA.** Ninguna consulta tocó una tabla para escribir, y la
segunda sonda no tocó ninguna tabla en absoluto.

### La prueba que separaba los dos escenarios

`HEX()` se resuelve en el servidor y no pasa por la conversión del cliente:

```sql
SELECT HEX(avatar_blob) AS serverHex, avatar_blob AS clientValue
FROM pcs_unit_tests_core_database_exporter_v1 LIMIT 1
```

| | |
| :-- | :-- |
| **Servidor** (`HEX()`) | `3f504e470d0a1a0a…` |
| **Cliente** (`bin2hex()` en PHP) | `3f504e470d0a1a0a…` |
| Longitud | **20 bytes en los dos**, igual que el original |

> **El dato guardado YA ESTÁ DAÑADO.** No es un defecto del camino de lectura: el `0x89` no
> llegó nunca a la tabla. La longitud se conserva y solo cambia un byte — la firma clásica
> de una conversión de juego de caracteres, no de un truncado.

Y la columna está bien declarada: `avatar_blob` es **`blob`**, tipo binario.

### El mecanismo, demostrado SIN TOCAR NINGUNA TABLA

Una ida y vuelta del literal, con `SELECT HEX(?)` sobre ninguna tabla:

| Cómo se envía el parámetro | Qué vuelve |
| :-- | :-- |
| Original en PHP | `89504e470d0a1a0a0000000d4948445200000001` |
| **`PARAM_STR`** *(el de por defecto)* | **`3f`**`504e470d0a1a0a0000000d4948445200000001` |
| `PARAM_LOB` | `89504e47…` **intacto** |
| `SET NAMES binary` | `89504e47…` **intacto** |

**`PDO::ATTR_EMULATE_PREPARES` está en `true`.** Con emulación, PDO **interpola el parámetro
en la cadena SQL dentro de PHP** y lo entrecomilla según el juego de caracteres que PDO cree
que tiene la conexión.

### La causa raíz, y está en el PAQUETE

`Database::instance()` acepta un `$charset` y **NUNCA lo pone en el DSN**:

```php
$dsn = "{$driver}:dbname={$database};host={$host}";   // <-- sin charset
…
$instance->prepare("SET character set {$charset}; …");
```

Dos consecuencias, las dos medidas:

1. **PDO no se entera.** El `charset` del DSN es lo único que fija cómo entrecomilla PDO al
   emular. Ejecutar `SET character set` después cambia el servidor, **no a PDO**.
2. **`SET character set` deja la conexión descuadrada.** Pone `character_set_client` y
   `character_set_results` al valor pedido, pero `character_set_connection` **al de la base
   de datos**. Medido en esta instalación:

   ```
   character_set_client     = utf8mb4
   character_set_connection = utf8mb3   <-- el de la base, no el pedido
   character_set_results    = utf8mb4
   ```

### CUÁNTO ALCANZA — la parte que decide qué contarle a los despliegues

**El ORM del framework NO PUEDE crear una columna binaria.** Los tipos que `SchemeCreator`
admite son `varchar, text, mediumtext, longtext, int, bigint, float, double, json, datetime,
date` — **no hay `blob` ni `binary`**. Y **cero mappers** declaran un campo binario:
`grep -rlniE "'(blob|longblob|mediumblob|varbinary)'" src/app/classes src/app/model` da **0**.

| | |
| :-- | :-- |
| Datos del framework en riesgo | **Ninguno por la vía del ORM**: no existe el tipo |
| Quién sí está en riesgo | Un despliegue que haya añadido una columna binaria **a mano** y escriba en ella con `prepare()/execute()` |
| El dato dañado que se encontró | La semilla de la suite del exportador, **tabla de pruebas que se regenera en cada corrida** |

> **No se corrige aquí, y no por prudencia sino porque la decisión tiene dos ramas.** Poner
> `charset=` en el DSN cambia el entrecomillado de TODAS las escrituras de TODOS los
> despliegues, y es un paquete que consumen cinco repositorios. La alternativa acotada
> —`bindValue(..., PARAM_LOB)` donde haya binario— **no tiene sitio donde aplicarse hoy**,
> porque el framework no escribe binario.

### Lo que hay que decidir

1. **¿Se pone `charset=` en el DSN de `Database::instance()`?** Es la corrección de fondo y
   deja la conexión coherente. Riesgo: cambia el entrecomillado en todos los despliegues.
2. **¿Se desactiva `ATTR_EMULATE_PREPARES`?** Corrige la clase entera de problema —los
   preparados de verdad no interpolan— pero cambia el comportamiento de todas las consultas.
3. **¿O solo se documenta**, dado que el ORM no ofrece columnas binarias, y se añade el tipo
   `blob` a `SchemeCreator` el día que alguien lo necesite, ya con el binding correcto?

**Ninguna es obvia y las tres son del propietario.** Lo que sí está cerrado es el
diagnóstico: **el daño ocurre al escribir, la causa es la emulación de preparados sobre una
conexión cuyo charset PDO desconoce, y el alcance real es cero por la vía del ORM.**

## T29 · MAPA DE LAS 309 RAMAS — el de T24 queda derogado, y NO SE BORRA NADA

T24 se hizo sobre 373 y **antes** de cerrar el punto del trait. Este es sobre las **309** que
quedan. Sigue sin borrarse nada.

### El instrumento, columna por columna

| Columna | Con qué se midió |
| :-- | :-- |
| **Ramas** | `bin/phpstan-deadcode`. **Unidad: tripletas** (ruta, línea, mensaje) |
| **Zona** | La RUTA del archivo: `/view/` o `/Views/` → vistas; `*Mapper.php` → mappers; `local-tests/` → suites; `src/app/config/` → config; `src/app/core/` → núcleo; `*Controller.php` → controladores; el resto, otros |
| **Veredicto** | **Lectura a mano** de una muestra de cada familia. Donde no la hay, dice «sin verificar» |

### Por qué bajó de 373 a 309

| | |
| :-- | --: |
| Punto del trait: 35 métodos borrados | **−48** |
| `catch.neverThrown`: 3 borrados y 2 reclasificados | **−5** |
| `if.alwaysFalse`: 11 interruptores reclasificados | **−11** |

**De las 64, solo 38 eran borrado real. Las otras 26 nunca fueron ramas muertas.**

### El mapa

| Ramas | Identificador | Zona dominante | Veredicto de la muestra verificada |
| --: | :-- | :-- | :-- |
| **105** | `function.alreadyNarrowedType` | núcleo 36, controladores 34 | `is_array()` sobre un `array`. **Resto defensivo puro**, borrable por familia |
| **42** | `notIdentical.alwaysTrue` | controladores 35 | `$userMapper !== null` sobre un valor ya estrechado. Borrable **con cuidado**: es una condición viva, no un hueco |
| **26** | `isset.variable` | controladores 21 | `isset($_POST)` dentro del closure `$getParam` de los `_allowedRoute` **que sobreviven**. Sobra el `isset`, **no el closure**: 9 de los 10 que lo declaran lo usan |
| **24** | `foreach.emptyArray` | mappers 24 | **Hueco de plantilla puro**: `$defaultPropertiesValues = []` seguido de su `foreach`. Se borran el array y el bucle |
| **21** | `if.alwaysTrue` | otros 9, controladores 6 | Sin verificar |
| **13** | `function.impossibleType` | núcleo 8 | `is_scalar($x) && !is_null($x)` — guarda redundante. Resto defensivo |
| **12** | `ternary.alwaysTrue` | **vistas 10** | **NO SE TOCA. Ver abajo** |
| **11** | `nullCoalesce.offset` | config 9 | `$array['clave'] ?? null` sobre un array literal donde la clave existe. Defensivo, borrable |
| **10** | `booleanNot.alwaysTrue` | controladores 7 | **6 de 10 son un INTERRUPTOR. Ver abajo** |
| **9** | `if.alwaysFalse` | controladores 7 | Los que quedaron fuera de los `<Modulo>Routes`. Sin verificar |
| **7** | `booleanAnd.leftAlwaysTrue` | mappers 4 | Sin verificar |
| **5** | `booleanAnd.rightAlwaysFalse` | vistas 2 | Sin verificar |
| **4** | `deadCode.unreachable` | `SystemApprovalManager` 3 | `break;` detrás de un `return` dentro de un `switch`. Inofensivo y real |
| **30** | *(las 12 familias restantes, ≤3 cada una)* | — | Sin verificar |

### DOS FAMILIAS MÁS QUE PARECEN MECÁNICAS Y NO LO SON

Van ya tres, contando el `$showSQL` de T27. **El patrón se repite y conviene reconocerlo: un
interruptor de desarrollo tiene esta forma exacta** —una variable local puesta a un literal,
con su comentario al lado, y un `if` que la lee—.

**`booleanNot.alwaysTrue` — `$showAlways`, 6 de 10.**

```php
$showAlways = false; //Define si se muestra siempre aunque no tenga traducción
…
if (!$showAlways) {
    // añade el criterio que filtra por idioma
}
```

Borrar ese `if` **cablearía «mostrar solo lo traducido»** y quitaría el interruptor. Es
exactamente `$showSQL` con otro nombre.

**`ternary.alwaysTrue` — constantes de módulo, 10 de 12 en vistas.**

```php
$withAttachments = PublicationMapper::WITH_ATTACHMENTS;   // en la vista
…
style="<?= $withAttachments ? '' : 'display:none;' ?>"
```

**Corrección de una lectura propia**: la primera hipótesis fue que eran variables inyectadas
por `extract()` y que PHPStan no las ve. **Falso, y bastó un `grep` para saberlo**: se asignan
en la propia vista desde una **constante de clase**. Es la misma trampa que los interruptores
de módulo de T13, pero con constantes que **no están en `config/constants.php`**, así que
`dynamicConstantNames` no las cubre hoy.

> **Opción a evaluar, NO implementar**: `dynamicConstantNames` admite constantes de clase
> (`PublicationMapper::WITH_ATTACHMENTS`). Habría que inventariar qué constantes de mapper son
> configuración por despliegue y cuáles no — y esa distinción no se deduce del código.

### Regla de trabajo

1. **Empezar por `foreach.emptyArray`** (24, todas en mappers, todas la misma forma): es la
   única familia con veredicto homogéneo verificado y sin sorpresas.
2. Después `function.alreadyNarrowedType` (105) y `function.impossibleType` (13).
3. **`ternary.alwaysTrue` y `booleanNot.alwaysTrue` NO se tocan** hasta decidir lo de las
   constantes de módulo.
4. **Cada familia, con su muestra verificada a mano ANTES de tocarla** — nunca por parecerse a
   otra que sí era andamio.
5. Y antes de suprimir una familia entera, **comprobar que sus miembros están ahí por la misma
   razón** (T27). Tres familias de este mapa ya han demostrado que no basta con compartir
   identificador.

## T30 · LA MIGRACIÓN A 8.5 NO ESTABA TERMINADA — por qué, y qué se hizo

### La causa raíz: TODAS las puertas corren en un PHP que producción no usa

| | |
| :-- | :-- |
| Apache sirve | **PHP 8.5.9** |
| `bin/cli`, `bin/phpstan`, `bin/rector`, `bin/phpstan-deadcode` | **php8.4** |

Y las funciones que quedaban **se deprecan EN 8.5, no en 8.0**. Medido llamándolas:

```
PHP 8.5.9: Function imagedestroy() is deprecated since 8.5, as it has no effect since PHP 8.0
PHP 8.5.9: Function finfo_close() is deprecated since 8.5, as finfo objects are freed automatically
PHP 8.5.9: Function curl_close()  is deprecated since 8.5, as it has no effect since PHP 8.0
PHP 8.4  : (nada)
```

**El detector de ejecución no podía verlas** porque las pruebas se corrían en 8.4.

### El segundo motivo: `phpVersion` como RANGO reporta la INTERSECCIÓN

El comentario del `.neon` decía que un escalar dejaría pasar las deprecaciones de la otra
versión. **Es exactamente al revés**, y está medido: con `min: 80500, max: 80500` sobre este
mismo árbol aparecen **38 errores que hoy no se ven** —el total pasa de **875 a 900**—, entre
ellos las 3 llamadas a `strftime()` y el `imagedestroy()` de sonda.

> **Un rango solo reporta lo que es error en TODAS las versiones del rango.** Una deprecación
> introducida en 8.5 no lo es en 8.4, así que se descarta en silencio.

**PHPStan SÍ ve `imagedestroy()`** cuando se le fija 8.5 — `Call to deprecated function
imagedestroy()`—, así que **no hay punto ciego de stubs**: hay una configuración que mira a
la versión equivocada. La versión instalada, 2.2.8, **es la última**; `composer outdated` solo
señala `rector/rector`.

**No se cambia el rango aquí**: subir el piso mueve el baseline +23 y eso es decisión del
propietario. Lo correcto sería **dos pasadas y la unión**, no la intersección.

### Lo que se hizo

- **Las nueve llamadas, borradas.** `imagedestroy` ×4, `finfo_close` ×4, `curl_close` ×1.
  Barrido también `xml_parser_free`, `shmop_close`, `enchant_broker_free`,
  `enchant_dict_free` y `pspell_free`: **cero en todo el árbol**.
- **`img-gen` arreglado, y la causa demostrada** poniendo la llamada de vuelta:

  | | |
  | :-- | :-- |
  | Con `imagedestroy()` | **HTTP 400**, `text/html` |
  | Sin ella | **HTTP 200**, `image/jpeg`, 6.553 bytes, JPEG 400×300 real |

  *(La sospecha del `.ttf` queda descartada: la fuente estaba bien.)*
- **Sexta comprobación de `verify-integrity`**: registro de funciones deprecadas en
  `files/dev/deprecated-functions.json`, con la versión en que cada una se deprecó y rutas
  permitidas con su razón. **Determinista: mira el código, no la ejecución.** Probada en
  tres direcciones —llamada nueva, entrada muerta de `allowedPaths`, registro ilegible—.
- **Y esa puerta destapó un falso positivo del detector de docblocks**: marcaba como
  «tragada» cualquier declaración que un docblock solo MENCIONARA. Ahora exige que la línea
  no empiece por `*`, que es lo que distingue una declaración tragada de una mención.

### El recorredor de rutas

`bin/cli route-inventory` + `bin/walk-routes`. El inventario sale **del propio framework**
—`get_routes()`, 347 rutas—, no de una lista escrita a mano.

**Solo GET**, y descarta por nombre **y** por URL `-actions-`, `-forms-add`, `-forms-edit`,
`-add`, `-edit`, `-delete`, `delete`, `destroy`, `remove`, `logout`. **No escribe nada.**
Recorre además **todos los assets** de las páginas visitadas, que es donde un paseo humano no
mira: un asset que revienta no rompe la página.

## T31 · CERRANDO 8.5: dos pasadas, el `chr()` del cifrado, y el recorrido con sesión

### 1 · El baseline pasa a ser LA UNIÓN de 8.4 y 8.5

`bin/phpstan` corre ahora **dos pasadas** —`phpVersion` fijado en 80400 y en 80500, con
configuraciones **derivadas** de `bin/phpstan.neon`, no duplicadas— y el baseline es la unión.

| | |
| --: | :-- |
| **875** | instancias en la pasada de 8.4 |
| **−9** | duplicados exactos, que la unión deduplica |
| **866** | tripletas comunes a las dos versiones |
| **+22** | tripletas que **solo existen en 8.5** |
| **888** | **tripletas. El baseline nuevo** |

**Solo en 8.4: cero.** Y las 22 son todas `offsetAccess.invalidOffset` — la familia de
*«Using null as an array offset»*, que **el recorrido encontró también en ejecución**
(`CountryMapper.php:276`). El análisis estático y el dinámico apuntan al mismo sitio.

**CAMBIO DE UNIDAD, y hay que decirlo**: 888 son **tripletas**; 875 y las anteriores eran
**instancias**. El resumen lo lleva escrito en un bloque `[UNIDAD]` propio.

Y de paso, `phpstan-process-result.php` **deja de parsear la tabla**: construye el resumen
leyendo el JSON de la unión. Parsear la tabla fue lo que hizo perder 34 archivos a Rector.

### 2 · Las herramientas corrían en la versión equivocada

`bin/cli`, `bin/phpstan`, `bin/rector` y `bin/phpstan-deadcode` usaban `php8.4` mientras
Apache sirve **8.5.9**: **las suites nunca habían pisado la versión de producción.**

Ahora el binario es explícito y configurable —`PCSPHP_PHP_BIN`— y **por defecto es la que
sirve el navegador**. Corridas en las dos:

| | 8.5 | 8.4 |
| :-- | :-- | :-- |
| `verify-integrity` | OK | OK |
| `mapper-finders` | 20/20 | 20/20 |
| `session-user` | 13/13 | 13/13 |
| `otp-write-separation` | 6/6 | 6/6 |
| `meta-property-hybrid` | 12/12 | 12/12 |
| `database-exporter` | 23/23 | — |

**Nada se cayó.** Lo que faltaba no era arreglar las suites: era ejecutarlas donde importa.

### 3 · `chr()` en `BaseHashEncryption` — no era una deprecación, era el cifrado

`encrypt()` y `decrypt()` suman y restan bytes y **dependen de que `chr()` dé la vuelta en
256**. Desde 8.5 eso avisa, y en local —donde el manejador promueve la deprecación a
excepción— **lanza**. Por eso dejó de descifrarse la configuración de correo.

> **El riesgo no era la deprecación: era que alguien lo «mejorara».** Cualquier cambio en esa
> aritmética vuelve **indescifrable** todo lo ya cifrado en cada despliegue congelado.

El arreglo es `& 0xFF` y **nada más**. Comprobado valor a valor entre −600 y 600 contra el
`chr()` de 8.4: **cero diferencias**. `% 256` NO sirve: en PHP el módulo de un negativo es
negativo.

**Ida y vuelta demostrada**, con el valor a la vista:

```
cifrado por el código VIEJO : nNLax9_MotbGkNk1KNOCxZLj9Jyr1dHQsIXw9cab1DUv
descifrado por el NUEVO     : configuración de correo: Ñandú
```

Cinco casos —texto, acentos, bytes altos, clave larga—: **cifrado byte a byte idéntico**, el
nuevo descifra lo viejo y el viejo descifra lo nuevo. El archivo lleva una nota que prohíbe
tocar esa aritmética.

**Y esto explica los cinco 500 públicos**: `users/recovery/`, `user-blocked`, `user-forget`,
`problems` y `other-problems` leen la configuración de correo. Demostrado poniendo el código
viejo de vuelta: **500 con él, 200 con el arreglo.**

**Corrección propia**: dije que «no dejaron log». Sí lo dejaron — eran las 12 entradas de
`chr()` que había contado como un hallazgo aparte. Eran el mismo.

Y un aviso de instrumento: la primera comparación A/B dio 200 con el código viejo, porque
**opcache seguía sirviendo la versión compilada anterior**. Hubo que tocar el archivo y
esperar antes de medir. **T20: el instrumento era la caché del servidor web.**

### 4 · Los 23 quinientos de `*/statics/` eran UN defecto

`{params:.*}` es **opcional** en la ruta y `ServerStatics` lo leía como obligatorio en tres
sitios. Pedir `/statics/` a secas → `E_WARNING` → excepción → 500, en los 23 módulos a la
vez. Con `?? ''` responden **404**, que es lo correcto, y los estáticos reales siguen
sirviéndose.

### 5 · El recorrido con sesión

| | Sin sesión | Con sesión de root |
| :-- | --: | --: |
| Rutas pedidas | 184 | 184 |
| **2xx** | 51 | **114** |
| 302 | 93 | 1 |
| 500 | 1 | 27 |
| **Páginas derivadas de enlaces reales** | — | **122**, 118 en 2xx |
| **Assets** | 46, 2 no-2xx | **38, todos 2xx** |

**Los parámetros no se inventan: se cosechan.** Las rutas con `{id}` se piden por los enlaces
reales que las páginas ya visitadas contienen — lo que alcanzaría alguien haciendo clic. Sin
base de datos y sin adivinar. Eso añadió **122 páginas** que antes no se miraban.

**De los 27 quinientos con sesión, 24 son endpoints `-datatables` o `-ajax-all`** llamados
sin los parámetros que DataTables genera: defecto de robustez ya documentado
(`DataTablesHelper.php:230` y `:1090`), preexistente y ajeno a la versión de PHP.

**Los que no son de esa familia, y son los que hay que mirar:**

| Qué | Dónde |
| :-- | :-- |
| `Unknown column 'interest_research_area.startDate' in 'WHERE'` | `PageQuery.php:147` — SQL contra una columna que no existe |
| `TOTPStandard::getQRCodeUrl(): Argument #2 ($issuer) must be of type string, null given` | `OTPHandler.php:175` |
| `ImagesRepositoryController::_all(): Argument #1 ($description) must be of type…` | módulo condenado |
| **`Using null as an array offset is deprecated`** | `CountryMapper.php:276` — **deprecación de 8.5 EN EJECUCIÓN** |
| `404` en `admin/reports-access/`, `locations/points/datatables/`, `/elements/`, `/tabs-sample/` | enlaces o rutas que no resuelven |

### Corrección: son 3 `strftime()`, no 6

Las otras tres menciones son el docblock del conversor de formatos. Las tres llamadas viven
en `Utilities.php`, van con `@`, y ahora **PHPStan sí las reporta en las dos pasadas** — con
el rango no las reportaba.

## T32 · UN GET QUE ESCRIBE, EL INSTRUMENTAL COMÚN, Y LOS HALLAZGOS DEL RECORRIDO

### 1 · `GET /configurations/seo/` escribe en disco Y en base de datos

**Bisecado, no leído.** Se repitieron las 272 URL del recorrido una a una vigilando la fecha
de `src/statics/images/`, y la culpable salió sola:

```
!!! https://…/configurations/seo/
    CREA: open_graph_de.jpg, open_graph_fr.jpg, open_graph_it.jpg, open_graph_pt.jpg
```

**NO ES PÚBLICA**, y esa es la diferencia con D2: `requireLogin: true`, roles `[0, 1]`, y sin
sesión responde **302**. No hay escritura no autenticada.

Pero **sí es el mismo patrón**: renderizar una página materializa datos.
`AppConfigController`, al pintar la configuración SEO, recorre los idiomas y para cada uno
que no tenga fila:

```php
$newConfig = new AppConfigModel();
$newConfig->name = $nameLang;
…
if (@copy(basepath($defaultValue), basepath($relativePath))) {
    $newConfig->save();          // <-- escritura en base de datos, en un GET
}
```

Comprobado en la base: existen `open_graph_image_de`, `_fr`, `_it` y `_pt`, **creadas por el
recorrido**. Los cuatro archivos se devolvieron a su sitio porque esas filas les apuntan.

> **No se arregla aquí**: el propietario pidió saber primero si era pública. No lo es, así
> que decide él. Lo que sí queda escrito es que **el recorredor tiene un efecto lateral
> conocido**: pide GET, pero la aplicación escribe.

### 2 · El instrumental común a los cinco repositorios

Los cuatro paquetes tenían **exactamente** la configuración que había cegado a piecesphp
—`phpVersion: {min: 80400, max: 80500}`— y se declararon verdes sobre ella. `database` está
publicado en Packagist y lo consume el framework.

**Propagado a los cuatro**: las dos pasadas y la unión, `PCSPHP_PHP_BIN`, el trinquete
leyendo JSON, y el baseline con su nota de método.

| Paquete | 8.4 | 8.5 | Unión | **Solo en 8.5** |
| :-- | --: | --: | --: | --: |
| `database` | 21 | 21 | **21** | **0** |
| `datastructures` | 0 | 0 | **0** | **0** |
| `html` | 3 | 3 | **3** | **0** |
| `geojson` | 0 | 0 | **0** | **0** |

**Delta por configuración: cero en los cuatro.** El rango no ocultaba nada aquí — pero ahora
eso está **medido, no supuesto**, que era justo el problema.

*(Un detalle que corrobora lo de la tabla: el resumen anterior de `database` decía **20** y el
JSON dice **21**. El parser de la tabla contaba de menos, igual que en T2.)*

`dynamicConstantNames` **no se propaga**: allí no hay constantes de módulo y declararlo sería
escribir en un registro algo que no significa nada.

#### La séptima comprobación: `files/dev/shared-toolchain.json`

Lista, por archivo, las **marcas** que los cuatro paquetes deben contener — no se comparan
byte a byte porque legítimamente difieren en rutas. `verify-integrity` falla si un paquete se
desvía o si le falta un archivo. **Probada en las dos direcciones**: quitando `PCSPHP_PHP_BIN`
de `html/bin/phpstan` y quitando el baseline de `geojson`, las dos salen con 1.

Si los paquetes no están clonados al lado, **lo dice y no aprueba en silencio**.

### 3 · Los hallazgos del recorrido con sesión

#### a) `offsetAccess.invalidOffset` — 22 sitios, UNA sola forma

El estático y la ejecución coinciden, que es la mejor pista que había. Todas son
`$options[<expresión que puede ser null>] = …`, el constructor de listas desplegables copiado
por todo el proyecto:

| Forma | Cuántas |
| :-- | --: |
| `Possibly invalid array key type int\|null` | 11 |
| `Possibly invalid array key type string\|null` | 9 |
| `Possibly invalid array key type int\|string\|null` | 2 |

**21 archivos**, de `App\Locations` a `UsersModel`, pasando por `config/functions.php`.

**El arreglo es un `(string)` en la clave y nada más** — que es literalmente lo que dice la
deprecación de 8.5: *«use an empty string instead»*. PHP ya convertía `null` a `''` y
renormaliza las cadenas numéricas a entero, así que **no hay cambio de comportamiento**.

> **NO APLICADO: son 21 archivos y la regla T0bis exige enseñar el plan antes.** Y por T17,
> o se aplica a las 22 o a ninguna.

#### b) `getQRCodeUrl(): Argument #2 ($issuer) must be of type string, null given`

`OTPHandler.php:175` pasa `$userDataPackage->TOTPData->twoAuthFactorAlias` a un parámetro
`string`. **El null viene del DATO, no de configuración ausente**: es el alias que el usuario
pone a su segundo factor, y está vacío mientras no lo nombre — que es el estado normal de
cualquier fila recién creada.

Efecto: `users/user-system-features/get-current-totp-qr-data/` da **500 para todo usuario que
no haya bautizado su 2FA**. Toca el módulo de D2, pero no lo causó D2: la columna siempre fue
nulable.

**Qué decidir**: qué emisor mostrar cuando no hay alias. Aparece en la aplicación de
autenticación del usuario, así que no es una elección técnica.

#### c) `Unknown column 'interest_research_area.startDate'` — muere con el módulo

`InterestResearchAreasController` filtra por `startDate` y **el mapper no declara esa
columna**: el controlador se clonó de `ApplicationCalls`, que sí tiene fechas, y el campo no
se quitó. Las únicas referencias a `startDate` en todo el proyecto están dentro de
`InterestResearchAreas`, que está **condenado** (T6).

**Confirmado: el error muere con el módulo. No se arregla.**

#### d) Los 24 quinientos de `-datatables` / `-ajax-all`

Llamados sin los parámetros que genera DataTables. `DataTablesHelper.php:230` toma `columns`
con defecto `null` y `:1090` lo exige `array`. **Defecto de robustez conocido, preexistente y
ajeno a la versión de PHP.** No es de esta ventana.

#### e) Los 404: dos clases distintas

| Qué | Clase |
| :-- | :-- |
| `/elements/`, `/tabs-sample/` | **Enlaces muertos en vistas.** Salen de `view/layout/menu.php`, que pinta vistas genéricas cuyo archivo no existe. No hay ruta que perder: nunca la hubo |
| `admin/reports-access/` | Enlace de menú a un módulo que no responde ahí |
| `locations/points/datatables/` | **Ruta registrada que responde 404 desde el controlador**, no un fallo de enrutado: sin sesión da 302, con sesión da 404 |

## T33 · LA GEMELA DE D2, EL ORDEN DE LA DOCUMENTACIÓN, Y UNA FAMILIA QUE NO LO ERA

### 1 · El arreglo de D2 NO dejó a nadie fuera — pero destapó su gemela

**La exposición era más ancha de lo planteado**: no son los importados, son **todos los
usuarios nuevos**. `getTOTPData()` era get-or-create y nunca devolvía `null`; ahora la
creación vive en `toggle2FA()`, así que cualquiera que no haya activado su 2FA no tiene fila
— y `UserDataPackage` se construye en casi cada petición autenticada.

**Censo de consumidores: TODOS aguantan `null`.**

| Consumidor | Aguanta |
| :-- | :-- |
| `OTPHandler` ×6 (`:91`, `:159`, `:173`, `:193`, `:232`, y `:43`/`:68` para el de un uso) | Sí, todos con `!== null` |
| `UserSystemFeaturesController:129` | Sí, y devuelve un mensaje propio |
| `MySpace/Views/user-security.php` y `example-resources.php` | Sí, y **caen en `get_config('owner')`** |

**Y `setOTP()` SÍ crea la fila que falta** (`setOneUseCode()`, rama `if (!$exists)`), así que
**un usuario importado a mano puede pedir su código y entrar**. No hay bloqueo de acceso.

Comprobado con una suite permanente que **inserta un usuario de verdad** sin filas OTP:
`bin/cli unit-tests:core/otp-fresh-user`. Recorre lectores, `UserDataPackage`, `OTPHandler`,
el código de un solo uso de punta a punta y el `toggle2FA` en los dos sentidos. Crea el
usuario con un nombre irrepetible y **lo borra siempre**, también si algo falla.

#### LA GEMELA, y está una línea más abajo

```php
UserDataPackage.php:243   $this->TOTPData = OTPSecretsUsersMapper::getTOTPData($this->id);   // arreglado
UserDataPackage.php:244   $this->profile  = UserProfileMapper::getProfile($this->id);        // ← get-or-create
```

`UserProfileMapper::getProfile()` **crea y guarda** cuando no encuentra
(`UserProfileMapper.php:649-651`). Y el constructor de `UserDataPackage` se alcanza **sin
autenticar** desde `OTPHandler::checkValidityOTP()`, que es la ruta del formulario de login.

> **Mismo camino, misma primitiva de escritura acotada, misma enumeración débil de usuarios.
> Es D2 otra vez, en la línea siguiente.**

Medido: el usuario de prueba terminó con **una fila en `user_system_profile`** que nadie pidió
— y la delató la clave ajena al intentar borrarlo. La suite lo comprueba y **falla a
propósito**: 19/20. El rojo es el defecto, no la prueba.

**No se arregla aquí**: el arreglo es el mismo que se le hizo al OTP —partir el buscador en
lector y creador— pero toca el subsistema de perfiles y merece su decisión.

### 2 · El SEO: retirada la sospecha, y lo que sí se arregló

**No es «refrescar escribe»**: materializa la configuración de los idiomas **activos** y
converge. Está autenticada, con roles `[0, 1]`, y es el camino de activación de idiomas —
añadir un idioma simplemente funciona. **Es el principio de autoinicialización, no un
defecto.** Los cuatro archivos y sus filas se quedan.

Lo que sí era un defecto y se arregla: **los dos `@copy()` tragaban el fallo**. Sin fila y sin
registro, el siguiente render reintenta en silencio para siempre. Ahora **registran qué
archivo y a dónde**. La lógica no se toca.

Y la vista lleva escrito su propósito en una línea que frena: *«esta vista materializa la
configuración por idioma al pintarse, a propósito — es el camino de activación de idiomas, no
un efecto lateral. No lo muevas a una tarea de CLI»*.

### 3 · Las 22 `offsetAccess` NO son una familia: son DOS

**Y esto es T27 aplicándose contra mí.** Dije «una sola forma» y son dos motivos distintos:

| Cuántas | Forma | Qué es | Trato |
| --: | :-- | :-- | :-- |
| **13** | `$options[$e->id] = …` | Constructor de listas desplegables. **La clave vacía ES la opción de marcador**: `$options[$defaultValue]` con `$defaultValue = ''` por defecto, y su etiqueta es el «Ciudades» / «Regiones» de cabecera | `(string)` — la migración prescrita |
| **9** | `$name = $_FILES[$nameOnFiles]['name'];` | **No es una lista de opciones.** Es leer `$_FILES` con una clave que puede ser `null` | **NO se castea.** `$_FILES['']` no es la intención: ahí falta una guarda, no un `(string)` |

**Las 13 quedan aprobadas y listas; las 9 son otra pregunta y no entran.** Aplicar el cast a
las nueve habría sido callar al analizador, que es justo lo que el propietario quería evitar.

### 4 · Los 404, resueltos: ninguno es una ruta perdida

| Qué | Veredicto |
| :-- | :-- |
| `admin/reports-access/` | **404 deliberado por falta de parámetro.** Con `?attempts=yes` responde **200**. El nombre del menú es correcto y root sí tiene permiso. **Va con los 24** |
| `locations/points/datatables/` | Mismo caso. **Va con los 24** |
| `/elements/`, `/tabs-sample/` | **CORRECCIÓN a lo que reporté**: no son enlaces muertos que se rendericen. Las entradas de `view/layout/menu.php` **ya se guardan** con `genericViewRouteExists()`, así que no aparecen cuando la vista no existe. Son andamio de plantilla, de la misma clase que `Components/Views/sample`. **No los quito sin decisión**: el test de «esto no resuelve» ya nos engañó una vez con Webflow |

### 5 · El orden de la documentación — decidido

- **`source-docs/` se reestructura AL FINAL DE TODO**, después de la limpieza y del estado
  final. Aterrizar las guías a una realidad que todavía se mueve es escribirlas dos veces, y
  la limpieza va a borrar módulos, tareas y rutas.
- **`.agents/context/` NO entra en ese aplazamiento**: es el estado de trabajo y se mantiene
  al día siempre.
- **Y la regla de que ningún documento mienta NO SE SUSPENDE para ninguno de los dos.**
  Distinguir: **reestructurar espera; corregir una mentira va en el mismo commit que la
  produce.** Si se aplaza, se llega al final con veinte documentos falsos y nadie sabrá
  cuáles.

### 6 · Aparcado para el final: una puerta única `system-tasks`

**No hacer todavía.** Una sola línea que un despliegue pueda poner en su crontab —colas,
cronjobs—, **no obligatoria**, bien documentada y lo más perezosa posible. Hoy ya existen
`run-cronjobs` y `process-queue`, hay 14 tareas, y la documentación está repartida entre
`cronjobs.md:52`, `terminal.md:226` y `10-cli-y-tareas.md:71` diciendo cosas distintas.

**Va después de la limpieza**: construir un agregador sobre una lista que está a punto de
encoger es prematuro.

Diseño cuando toque: **agregador, no trabajador** —despacha a las tareas existentes y cada
una decide si tiene algo que hacer— y **las tareas se declaran periódicas ellas mismas** en
vez de que el agregador lleve la lista escrita. Es el patrón que ya funcionó con el inventario
de rutas y con `shared-toolchain`: **la lista escrita a mano siempre se queda atrás.**

### 7 · La línea de crontab documentada apuntaba a la base equivocada

```
* * * * * php …/src/index.php cli --local run-cronjobs run     ← MAL
```

**`--local` decide a qué base de datos se conecta la aplicación.** En terminal, `is_local()`
devuelve literalmente lo que diga el flag (`Utilities.php:607-610`), y `config/database.php`
elige credenciales, usuario y nombre de base según ese valor. Una línea así en un servidor
**apunta los cronjobs a la base de desarrollo**.

Corregido en `10-cli-y-tareas.md` y en `source-docs/…/cronjobs.md`, con el aviso al lado. Y lo
mismo para el worker de colas. `bin/cli` sí lo añade solo, y está bien: es el atajo local.

## T34 · LA ESCALERA — el plan acordado, y la regla que lo sostiene

| | Etapa | Cierra cuando |
| :-- | :-- | :-- |
| **E0** | Cierre de la migración a 8.5 y del instrumental | **Al empujar.** Hecho |
| **E1** | Cierre de calidad | Ver abajo |
| **E2** | **LA FOTO** — recorrido completo **más ciclo CRUD por módulo**, contra base restaurable | La foto existe y es reproducible |
| **E3** | Limpieza en **seis lotes**, cada uno con **restauración de la base + foto** antes y después | Los seis lotes aplicados |
| **E4** | Pruebas unitarias de lo pruebaunitariable, más la ventana de correo | — |
| **E5** | Las refactorizaciones del propietario | — |
| **E6** | Reestructurar `source-docs/` y el `system-tasks` de T33 | — |

> **E3, condición innegociable por la LEY 12: CADA LOTE RESTAURA LA BASE ANTES DE SU FOTO.**
> Una base reutilizada entre lotes invalida las fotos siguientes, porque el propio recorrido
> dispara los rellenos perezosos que venía a medir. Ya pasó dos veces en E2.

> **E6 hereda además**: extraer del documento 18 las leyes que hoy viven dentro —T0, T10, T17,
> T20, T21 y las LEY 8 a 12— y llevarlas a documentos permanentes, separando la ley de la
> anécdota que la funda sin perder ninguna de las dos.

### LA REGLA QUE SOSTIENE LA ESCALERA

> **EL ALCANCE QUEDA CONGELADO.** Un hallazgo nuevo **se anota y espera turno**.

Solo se salta la fila:

1. Algo que **el propietario ve roto hoy**.
2. Una **regresión que causamos nosotros**.

**Cualquier otra excepción se justifica POR ESCRITO.** Ya hay una: la gemela de D2 se adelantó
**por ADYACENCIA, no por gravedad** —mismo defecto, mismo archivo, línea siguiente, análisis
fresco y una prueba que ya fallaba apuntándole—. Aplazarla obligaba a reconstruir todo el
contexto dentro de tres sesiones para un cambio de treinta líneas. Es el mismo argumento con
el que se adelantó D2.

### E2 · EL DISEÑO, escrito ahora y construido cuando toque

**Todo lo verificado hasta hoy es de SOLO LECTURA.** El recorrido omitió **118 rutas de
escritura** —87 no-GET, 13 `GET|POST`, 18 por nombre—, y ahí viven los mappers, los
validadores, las subidas y el charset de T28.

**Tres cosas solo aparecen escribiendo**: los 9 de `$_FILES`, el emoji y el `0x89` de T28, y
`SchemeCreator` con las meta-propiedades en JSON.

> **Y el motivo de peso para que vaya en E2 y no después: LA RED DE SEGURIDAD DE LA LIMPIEZA
> ES LA FOTO, Y UNA FOTO DE SOLO LECTURA TIENE UN AGUJERO JUSTO DONDE VIVEN LOS MAPPERS.** Si
> E3 rompe el guardado de una publicación, un recorrido GET no se entera.

Lo que lo vuelve tratable es **la convención de sufijos**: `-forms-add`, `-actions-add`,
`-forms-edit`, `-actions-edit`, `-actions-delete`. **Un ciclo CRUD genérico se DERIVA de los
nombres de ruta**, sin escribir un guion por módulo — el mismo patrón que ya funcionó con el
inventario de rutas y con `shared-toolchain`.

**LA BASE RESTAURABLE HACE FALTA TAMBIÉN PARA LA PASADA DE LECTURA.** Se dijo aquí que E2 la
necesitaba «porque esto sí escribe», y **eso ya no se sostiene**: el propietario lo corrigió
después de que aparecieran caminos de solo-lectura que escriben. Un recorrido GET **también**
puede dejar rastro, así que la base desechable deja de ser una condición de la pasada de
escritura y pasa a ser condición de **las dos**.

**Y DE AHÍ SALE UNA SALIDA NUEVA DEL RECORREDOR, que es lo que convierte la lección en
instrumento:** comparar **la base entera y el árbol de archivos** antes y después de cada
pasada, y **entregar la lista de qué rutas de lectura escriben**. No es un extra del informe:
es la única forma de que «solo GET» deje de ser una creencia y pase a ser un dato.

- Antes de la pasada: volcado de todas las tablas y censo del árbol de archivos servido.
- Ruta a ruta, o al menos por lotes pequeños, para poder **atribuir** cada diferencia.
- Después: mismo volcado, mismo censo, y **el diff atribuido a la ruta que lo causó**.
- Salida: una tabla `ruta → tabla/fila/archivo tocado`. Vacía es el resultado esperado;
  cualquier fila es un hallazgo que hay que justificar o arreglar.

**Ojo con el ruido**: hay escrituras legítimas en caminos de lectura —sesiones, caché, logs,
contadores de visita—. Se declaran **antes** de medir, con su motivo escrito, y todo lo que no
esté en esa lista es un hallazgo. Declarar después de ver el diff es adaptar la regla al
resultado.

**CONDICIONES INNEGOCIABLES, porque esto sí escribe:**

1. **Base de datos desechable**, restaurada antes y después. `db-backup` ya existe. **Nunca
   contra la base que usa el propietario**: la manipula por su cuenta, como acaba de
   demostrar vaciando tres tablas.
2. **Salidas cortadas.** Correo y APIs externas neutralizados: hay rutas que mandan correo de
   verdad y otras que llaman a HubSpot, Mailjet o los traductores de IA. Un barrido a ciegas
   quema créditos o le escribe a alguien.
0. **Y una ganancia que no es una condición sino un efecto, y conviene tenerla escrita porque
   cambia qué se puede preguntar después: EL CICLO CRUD POBLA LA BASE.** Hoy hay **89 filas en
   36 tablas, 22 de ellas vacías**, y eso convierte en no concluyente toda medición que dependa
   de datos (T39). Después de E2 hay filas reales escritas por los caminos reales, y un grupo
   concreto de preguntas pasa a ser contestable: qué columnas reciben binario, qué pasa con
   `$_FILES[$key]` nulo, si el emoji sobrevive un formulario entero. **Ninguna se intenta
   antes.**

3. **Ciclo completo por módulo**: crear, leer, editar, borrar. **Se limpia solo si el borrado
   funciona**; si no funciona, eso es un hallazgo **y** un residuo, y las dos cosas hay que
   verlas.
4. **Prefijo reconocible** en todo lo que cree, para que cualquier resto sea identificable.
   **Y esto aplica también a las tablas que crea una suite**, no solo a las filas: una tabla de
   prueba viviendo en la base de la aplicación **falsea cualquier medición de esquema**. Ya
   pasó: el censo de columnas de C2 tuvo que descontar a mano una tabla del exportador. La
   regla queda en dos mitades — **quien cree tablas las nombra con prefijo reconocible, y toda
   medición de esquema las descuenta explícitamente**. La segunda mitad no es opcional: sin
   ella la primera solo hace el ruido más fácil de reconocer, no lo quita de la cifra.

### E1 · CIERRE DE CALIDAD — lo que queda

- **(a)** Bloque B: la URL del cropper construida a mano sin `baseurl()` —y buscar si hay más
  así en las vistas del panel, porque si la regla 3 se saltó una vez se saltó más— y el iframe
  de SurveyJS, que **se diagnostica abriéndolo y leyendo la consola**, no leyendo el código.
- **(b)** La medición del emoji. **Solo medir**: escribir un emoji por el ORM en una columna
  `text` y leerlo con `HEX()`. Decide el alcance de T28. **No arreglarlo.**
- **(c)** `foreach.emptyArray`: las 24, familia homogénea verificada, muestra a mano y
  adelante.
- **(d)** **Y el resto SE CIERRA, NO SE TRIA.** Todo lo que quede de las 309 ramas y del grupo
  B va a **supresión documentada con su razón escrita, agrupada POR MOTIVO**. T0 nunca exigió
  cero errores: exige **cero defectos reales y el resto justificado**. Si al escribir una razón
  se descubre que no la hay porque es un defecto, ese sale de la supresión y se arregla —
  pero **no se abre un triaje por familia**.

### Anotado para E2: los 9 de `$_FILES`

`$name = $_FILES[$nameOnFiles]['name'];` con clave nulable significa que **el camino de subida
puede indexar por `null`**. No son cosméticos y **no se arreglan con un cast**: necesitan una
guarda, y probablemente destapen qué pasa hoy cuando el formulario no manda ese campo.

**Se ejercitan en el ciclo CRUD de E2**, que es donde de verdad se van a manifestar.

## T35 · EL 2FA AL VER EL QR — dos diagnósticos falsados, y el defecto que sí había

**El propietario trajo evidencia nueva que contradecía mi informe, y tenía razón en dudar. Al
medirla, resultó que *ninguno* de los dos diagnósticos —ni el mío ni el suyo— era el correcto,
y que el defecto real era un tercero.** Esto se cuenta en ese orden a propósito: la parte
valiosa no es el arreglo, es cómo se cayeron las dos hipótesis.

### 1. El dato que lo destrabó todo: `twoAuthFactorQRViewed` NO es una columna muerta

El propietario preguntó si esa columna sirve para algo, porque en sus dos capturas está en `0`
mientras yo había reportado que el controlador la escribe a `1`. **Sirve, y es exactamente la
pieza que faltaba.** Censo, no impresión:

| Dónde | Qué decide |
| :-- | :-- |
| `UsersController.php:903` | **La puerta de login**: `$require2FA = isEnabled2FA && wasViewedQRData` |
| `UserSystemFeaturesController.php:247` | La respuesta de `2fa-required` al formulario de acceso |
| `UserSystemFeaturesController.php:173` | Si se entrega el QR o se responde «El QR ya caducó» |
| `MySpace/Views/user-security.php:16` | La reversión al recargar |

Y las capturas del propietario estaban en `0` porque **la escritura a `1` no es del GET**: es
del POST `mark-current-totp-qr-as-viewed`, el botón de confirmar. Nunca lo había pulsado.

### 2. CORRECCIÓN MÍA: mi recorrido NO activó el 2FA de root, y los GET que escriben son DOS

Reporté que un GET escribía aquí. **Es falso, y la comprobación es de treinta segundos**: los
métodos HTTP están en el propio `routes()`.

- `get-current-totp-qr-data` → **GET**, y solo lee: llama a `getCurrentUserQRData()`.
- `configure-totp` → **POST**. Es lo que armaba la cuenta.
- `mark-current-totp-qr-as-viewed` → **POST**. Es lo que escribía `qrViewed = 1`.

Mi recorrido fue solo-GET, así que **no pudo ser él**. Fue el propietario usando la pantalla.
**La cuenta de caminos de lectura que escriben baja de tres a dos**: el SEO, y el
*get-or-create* de perfil que se alcanza en toda petición autenticada (D2, ya arreglado).

**Que la cifra baje no rescata la creencia.** Dos siguen sin ser cero, y siguen siendo los dos
que nadie habría buscado. La lección aguanta entera.

### 3. CORRECCIÓN DEL PROPIETARIO: el estado transitorio NO pedía código

La hipótesis era que entre «ver el QR» y «confirmar» la cuenta exigía un código que nadie
tenía. **Es razonable, y es falsa.** Reproducido el estado antiguo exacto en un usuario
desechable y evaluada la misma expresión que usa el login:

```
  estado antiguo reproducido: 2FA=ENABLED qrViewed=0
  la puerta de login exige código: NO
  con qrViewed=1 exige código: SÍ
```

La puerta pide **las dos** columnas. Con `qrViewed = 0` no había bloqueo de acceso.

**Y esto es T20 otra vez, aplicado a una hipótesis en vez de a una medición**: la explicación
encajaba con todos los síntomas visibles, así que costaba nada creerla. Lo que la tumbó no fue
leer más código, fue **poner el estado y preguntarle a la puerta**.

### 4. Entonces, ¿qué era? Un bucle sin salida, por una condición que se saltaba a sí misma

`UserSystemFeaturesController.php:283` — `if (!$isCurrentlyEnabled) { …genera securityCode… }`.
Con el código antiguo, en cuanto se pulsaba «Activar» la cuenta ya estaba `ENABLED`, y a partir
de ahí:

1. Volver a pulsar «Activar» entraba por el `else` implícito: **no regeneraba nada y devolvía
   `securityCode` vacío**, pero respondía «Activado.» igual.
2. `user-security.js` solo muestra el QR `if (enable && … && securityCode.length > 0)`. Vacío
   el código, **no hay QR**.
3. Y al recargar, la vista revertía y **regeneraba el secreto**, matando en silencio cualquier
   QR ya escaneado.

Resultado: «Activado», sin QR, sin código de seguridad, y el secreto cambiando debajo. No era
un bloqueo de acceso — **era un flujo que no se podía terminar**. Peor de contar y menos grave
de sufrir que la hipótesis, que es justo por qué había que medirlo.

### 5. EL ARREGLO — **PREPARAR NO ES ACTIVAR**

`OTPSecretsUsersMapper::toggle2FA()` deja de escribir `ENABLED`. Guarda el secreto, el alias y
el hash del código de seguridad, y **deja el estado en `DISABLED`**. Se añade
`confirm2FA()`, que es lo único que escribe `ENABLED` —junto con `qrViewed = 1`— y que **no
toca el secreto**, para que el QR que se escaneó siga siendo el bueno.
`markQRDataAsViewed()` es quien la llama, y **propaga su fallo** en vez de responder éxito.

Comprobado **en las dos direcciones, con el estado de la tabla a la vista**:

```
DIRECCIÓN 1 — llegar al QR y ABANDONAR:
  tras preparar (ver QR)   2FA=DISABLED  alias=Mi Alias  qrViewed=0  secreto=D2CPL6ABMG  codigo=60
  ¿la cuenta pide código ahora? NO  <-- correcto

DIRECCIÓN 2 — completar el flujo:
  tras confirmar           2FA=ENABLED   alias=Mi Alias  qrViewed=1  secreto=D2CPL6ABMG  codigo=60
  ¿la cuenta pide código ahora? SÍ  <-- correcto
  ¿el secreto es el mismo que se escaneó? SÍ
```

Las tres comprobaciones quedan **permanentes** en `UnitTest-OTPFreshUser` (25/25), no en un
guion de usar y tirar: una puerta que solo se vio una vez no es una puerta.

De regalo, el bucle del punto 4 se cierra solo: como `$isCurrentlyEnabled` ya no se pone a
`true` antes de tiempo, volver a pulsar «Activar» **sí** genera código y **sí** muestra el QR.

**La rama de reversión de `user-security.php:16` se deja en su sitio a propósito.** Con el
código nuevo ya no puede dispararse —`confirm2FA()` escribe las dos columnas juntas—, pero
**las filas que quedaron armadas antes del arreglo siguen existiendo** y esa rama es su camino
de salida. Se quita cuando no queden.

### 6. LO QUE QUEDA ABIERTO, y va a E2 — confirmar no exige demostrar el código

`mark-current-totp-qr-as-viewed` **no comprueba ningún TOTP**: gira la columna y ya. El
formulario de `check-totp` existe y está en la misma pantalla, pero es **independiente** — nada
obliga a pasar por él. Es decir: **quien confirme sin haber escaneado bien se deja fuera de
verdad**, y ese sí es el bloqueo que buscábamos, solo que por la puerta de al lado.

No se arregla ahora porque cambia el contrato de la pantalla y toca el JS. **Va a E2**, que es
donde se ejercitan los caminos de escritura.

### 7. LA LECCIÓN, que es de método y por eso sube a T21

> **«SOLO GET» NO ES UNA PROPIEDAD DE SEGURIDAD EN ESTE CÓDIGO.**

Se diseñó el recorrido solo-GET creyendo que era inocuo, y aparecieron caminos de lectura que
escriben. La creencia venía de la semántica de HTTP, que es una **convención**, no una garantía
que alguien haga cumplir aquí.

Lo que se hace con eso no es desconfiar más, es **medir**: la base restaurable pasa a hacer
falta también en la pasada de lectura, y el recorredor gana una salida que compara la base y el
árbol de archivos antes y después y **entrega la lista de qué rutas de lectura escriben**.
Diseño en T34 · E2.

**Y el corolario incómodo, que es el que hay que recordar:** las dos hipótesis de este apartado
—la mía y la del propietario— eran coherentes con todo lo observable. Ninguna sobrevivió a
poner el estado en la tabla y preguntarle al código. **Coherente con los síntomas no es lo
mismo que cierto**, y la diferencia solo la da el instrumento.

## T36 · E1(b) · LA URL DEL CROPPER Y EL IFRAME DE SURVEYJS — los dos, medidos en el navegador

### 1. Primero, una corrección de método que se repitió TRES veces en este apartado

Las tres veces el fallo fue el mismo: **medir con un instrumento que no reproduce lo que hace
el navegador**, y creerme el resultado.

| Instrumento | Qué dijo | Por qué mentía |
| :-- | :-- | :-- |
| `grep '<base '` con `head` | «el panel no tiene `<base>`» | Lo tenía; **`head` cortó la salida** justo antes |
| Resolución de URL a mano en el probe | «resuelve a `…/agregar/img-gen/…`» | El navegador usa el `<base href>`, mi probe no |
| `curl` sin `Referer` | «`img-gen` da 404 siempre» | `requestIsSameDomain()` **exige `Referer`/`Origin`**; con ellos da 200 |
| Regex `src="…"` con comillas dobles | «la página no carga ningún recurso» | El framework emite **comillas simples** |

**Ninguno era un fallo del código: los cuatro eran fallos míos de medición.** T20 otra vez, y
esta vez cuatro seguidos en la misma hora. La regla operativa que queda: **cuando se mide algo
que un navegador interpreta, el instrumento tiene que interpretar lo mismo que el navegador**
—`<base>`, `Referer`, comillas— o no está midiendo eso.

### 2. La URL del cropper: era la ÚNICA de su especie, y estaba viva de milagro

`view/panel/built-in/utilities/cropper/workspace.php:32` construía
`src="<?="img-gen/$referenceW/$referenceH";?>"` — relativa, sin `baseurl()`, saltándose la
regla 3.

**Funcionaba**, y ahí está lo interesante: funcionaba solo porque
`view/panel/layout/header.php:13` emite `<base href="<?= baseurl(); ?>">`, que normaliza
cualquier relativa a la raíz. **Es decir: la regla 3 no se estaba saltando impunemente, se
estaba saltando a cuenta de una etiqueta que otra vista pone.** Quitar ese `<base>` —o incluir
el cropper desde una vista que no lo tenga— rompe las tres pantallas que lo usan
(`Documents` add y edit, y `usuarios/form.php`) sin que nadie relacione una cosa con la otra.

**Eso es exactamente una trampa embarcada**: no falla hoy, falla en el clon de dentro de un año.

El barrido buscó más de lo mismo en **todas** las vistas (`src/app/view` y
`src/app/classes/**/Views`), y hay que contarlo en dos mitades porque el primer barrido estaba
mal calibrado:

- **200 atributos** de URL delegan en una variable (`<?= $action; ?>`, `<?= $addLink; ?>`…).
  **Esos NO son hallazgos**: la variable se construye con `routeName()` en el controlador, que
  es justo lo que manda la regla 3.
- **18 literales relativos** del tipo `statics/images/…` en páginas de error, correo y algún
  parcial. **Todas esas páginas llevan su propio `<base href>`**, comprobado uno a uno. Quedan
  anotadas, no tocadas: son la misma dependencia que el cropper, pero en vistas que sí
  controlan su propia cabecera.
- **1 sola** construía la URL con una cadena dentro de PHP: la del cropper. Con la hermana de
  `view/panel/pages/test-cropper.php:38`, que pasa `'img-gen/1920/1080'` como valor, son **dos
  sitios de la misma familia** — y por T17 se arreglan los dos o ninguno.

Arregladas las dos con `baseurl()`, que es **la forma que ya usaban las otras ocho apariciones
de `img-gen`** en el proyecto. Se eligió `baseurl()` y no `get_route('img-gen', …)`, que sería
más canónico, precisamente por T27: la familia se define por el motivo, y aquí el motivo es
«así se construye la URL del generador de imágenes». Dejar una de nueve distinta es sembrar la
próxima duda. Medido antes de decidir: **las dos formas devuelven la misma URL.**

Comprobado en el navegador, con sesión, antes y después:

```
antes:  src=img-gen/300/300                    (relativa, dependiente del <base>)
ahora:  src=https://…/src/img-gen/300/300  ->  HTTP 200
        src=https://…/src/img-gen/400/400  ->  HTTP 200
```

**Dos anotados, no arreglados**, porque no son de esta familia y merecen su propia decisión:
`MySpace/…/my-organization-profile-assign-administrator.php:36` tiene `action="."` y
`view/layout/menu.php:119` tiene `href="./"`. Los dos resuelven a la raíz **gracias al
`<base>`**, o sea que hoy hacen lo que parece que quieren hacer. Con `<base>` distinto, no.

### 3. El iframe de SurveyJS: la causa es una sola, y las dos hipótesis eran la misma

El propietario propuso dos: que faltara registrar el grupo de idioma `'SurveyJS'`, o que el
borrado de assets se llevara `configurations.js`. **Son la misma, y la segunda es la causa.**

`MySpace/Views/resources/survey-js-form.php` empieza vaciando **toda** la configuración de
assets —`global_assets`, `custom_assets`, `default_assets`, `global_requireds_assets`,
`imported_assets`— y luego añade solo los dos JS y el CSS de SurveyJS. Abierta la página con
sesión, eso es exactamente lo que sirve, y los tres cargan bien:

```
=== survey-js-form -> HTTP 200
  200  statics/plugins/surveyjs/survey-core/defaultV2.min.css
  200  statics/plugins/surveyjs/survey-core/survey.core.min.js
  200  statics/plugins/surveyjs/survey-js-ui/survey-js-ui.min.js
```

**Y ahí está el problema: eso es TODO lo que carga.** El arranque de la propia página es

```js
window.addEventListener('PiecesPHP-Configurations-And-Window-Load', function() { surveyJSTest() })
```

y ese evento lo dispara **un único archivo en todo el proyecto**:
`statics/core/js/configurations.js`, que vive en `$assets['app_libraries']['js']`
(`config/assets.php:442`) — **una de las claves que la vista acaba de vaciar**.

Sin él, el evento no se dispara nunca y `surveyJSTest()` no llega a ejecutarse. El iframe se
queda en blanco, y **no hay error en consola que lo delate**: no falla nada, simplemente no
ocurre nada. Por eso costaba verlo.

La hipótesis del grupo de idioma cae dentro de la misma causa: `getLangGroupData('SurveyJS')`
—línea 97— también se define en ese archivo. Con el borrado, ni siquiera se llega ahí.

**Queda para decisión del propietario, porque las dos salidas cambian cosas distintas:**

| Salida | Qué cuesta |
| :-- | :-- |
| **(a)** No vaciar `default_assets`: que el iframe cargue el núcleo | El iframe engorda con jQuery, Fomantic y el resto. Funciona todo, incluido el grupo de idioma |
| **(b)** Que la página deje de depender del framework: `window.addEventListener('load', …)` y las cadenas de idioma escritas ahí | El iframe se queda ligero y aislado, que es lo que parecía buscar el borrado. Hay que resolver `getLangGroupData` a mano |

**No se arregla sin esa decisión**: el borrado de assets es deliberado —alguien lo escribió
entero, cinco `set_config` seguidos— y deshacerlo por mi cuenta es tirar una intención que no
conozco. **Es la regla de T27 aplicada antes de romper nada: si alguien lo dejó así a
propósito, primero se pregunta por el propósito.**

### 4. SurveyJS, resuelto — con una corrección al tercer punto del propietario

**Decisión aplicada: opción (a) pero estrecha.** No se restaura el conjunto global de assets;
se restaura **un solo archivo**, con su línea de razón en la propia vista:

```php
//El borrado de arriba se lleva configurations.js, que dispara el evento de abajo.
baseurl("statics/core/js/configurations.min.js"),
```

Y resulta que un archivo basta, porque `configurations.min.js` **no es `configurations.js`
minificado: es un bundle**. `gulpfile.js:97-103` concatena `helpers-lib/*.js`,
`translations/*.js`, `configurations.js` y `helpers.js`. Trae dentro los
`PCSPHP_TRANSLATIONS_*` de los que depende, así que no arrastra una cadena de dependencias.
jQuery no entra, y no hace falta: el bloque que la necesita está guardado con
`"undefined" != typeof $`, mientras que el `dispatchEvent` del evento está fuera de esa guarda.

Servido y comprobado, en orden:

```
  200  statics/plugins/surveyjs/survey-core/defaultV2.min.css
  200  statics/core/js/configurations.min.js
  200  statics/plugins/surveyjs/survey-core/survey.core.min.js
  200  statics/plugins/surveyjs/survey-js-ui/survey-js-ui.min.js
```

> **Hasta aquí llega lo comprobado, y el límite se dice en vez de disimularse:** está
> verificado que el bundle **se entrega, en el orden correcto y con 200**, y leído que es el
> único emisor del evento. **NO está verificado ejecutándolo**: no hay navegador aquí, e
> intentar emularlo en Node falló con `HTMLElement is not defined` porque el bundle define un
> *custom element*. Un emulador que no es el navegador ya me mintió cuatro veces en T36 · 1.
> **La confirmación final es abrir el iframe y mirar.**

**`survey-js-creator.php` NO se toca, y esto se midió antes de decidirlo**: arranca con
`window.addEventListener('load', …)`, no con el evento del framework, y no llama a
`getLangGroupData`. No depende de `configurations.js`, así que añadírselo sería peso sin
motivo. Se le aplicó el cambio y se revirtió al comprobarlo.

#### Y el tercer punto: `getLangGroupData` YA hace lo que se pedía

El propietario pidió arreglar la función para que un grupo desconocido devuelva un objeto
vacío en vez de lanzar o romper un `Object.assign`. **El razonamiento es correcto —un grupo
inexistente es un estado normal, cualquier módulo nuevo lo tiene antes de traducirse— pero la
función ya se comporta así.** `configurations.js:1335`, definición única en todo el proyecto:

```js
let groupData = {}
if (typeof pcsphpGlobals.messages[selectedLang] == 'object') {
    if (typeof pcsphpGlobals.messages[selectedLang][langGroup] == 'object') {
        groupData = pcsphpGlobals.messages[selectedLang][langGroup]
    }
}
return groupData
```

Empieza en `{}` y solo lo sustituye si el grupo existe **y** es un objeto. Ejecutado —esto sí
se puede ejecutar, es una función pura— con el mismo `Proxy` que usa el código real:

```
  grupo inexistente -> {} | typeof: object
  Object.assign con él -> {"pageNextText":"Siguiente"}    (no-op, no rompe)
  grupo existente -> {"a":"A"}
```

**No hay mina que desactivar aquí.** Que el grupo `'SurveyJS'` no esté registrado es cierto y
no es un defecto: la vista funcionará con las cadenas por defecto de SurveyJS, y la
localización aparecerá el día que alguien cree el grupo. **Que era justo el resultado que se
buscaba**, solo que ya estaba.

## T37 · E1(c) · EL EMOJI — medido, y resuelve el alcance de T28 partiéndolo en dos

**Encontrada la causa raíz, y no está en este repositorio: está en `piecesphp/database`, en una
palabra.**

### 1. La medición que se pidió

Escrito un emoji **por el ORM** en una columna `text` y releído con `HEX()`:

```
  cadena en PHP  : PRUEBA 😀 ❤️ ñ
  bytes en PHP   : 50525545424120 F09F9880 20E29DA4EFB88F 20C3B1
  guardado por el ORM: sí            <-- save() devuelve true
  valor en tabla : PRUEBA ? ❤️ ñ
  HEX()          : 50525545424120   3F     20E29DA4EFB88F 20C3B1
  ¿bytes idénticos? NO  <-- SE PIERDE
```

`F09F9880` (😀, cuatro bytes) entra y sale como `3F`, que es un **interrogante literal**. El
corazón `❤️` y la `ñ` sobreviven: son de tres y dos bytes. **Se pierde exactamente lo de cuatro
bytes**, y `save()` devuelve `true` mientras lo hace.

### 2. La causa: `SET CHARACTER SET` no es `SET NAMES`

El censo del servidor deja el mecanismo a la vista:

| Variable | Valor |
| :-- | :-- |
| `character_set_client` | `utf8mb4` |
| **`character_set_connection`** | **`utf8mb3`** |
| `character_set_results` | `utf8mb4` |
| `character_set_database` | `utf8mb3` |

Y las columnas **no son el problema**: **189 de 193** columnas de texto ya son `utf8mb4`. La
tabla puede guardar el emoji; **la conexión no lo deja llegar**.

`database/src/Core/Database/Database.php:240`:

```php
$prepareStatement = $instance->prepare("SET character set {$charset}; SET time_zone='{$timezone}';");
```

**Y aquí hay que ser justo con quién hizo qué, porque el propietario lo señaló y tenía razón:
TODO LO QUE ÉL CONTROLA ESTÁ BIEN.** La cadena entera, comprobada eslabón a eslabón:

| Eslabón | Qué hace | ¿Correcto? |
| :-- | :-- | :-- |
| `config/database.php:31,38` | declara `charset = 'utf8mb4'` | **sí** |
| `BaseEntityMapper.php:70` | lee ese valor y lo pasa hacia abajo | **sí** |
| `ActiveRecordModel.php:175` | se lo entrega a `Database::instance()` | **sí** |
| Las tablas | **35 de 35 tablas reales en `utf8mb4`** (las otras 6 son 5 vistas y la tabla de una suite) | **sí** |
| `Database.php:240` | ejecuta `SET CHARACTER SET` con ese valor | **NO** |

El valor `utf8mb4` llega **intacto** hasta la última línea, y ahí se pierde el efecto. No es una
configuración mal puesta: es una configuración correcta aplicada con la sentencia equivocada.

**`SET CHARACTER SET` fija `client` y `results`, pero pone `connection` al juego de la BASE DE
DATOS**, no al que se le pasa.

Demostrado sobre la misma conexión, ejecutando las dos y mirando las tres variables:

```
  charset que pide config/database.php : utf8mb4
  tal cual llega hoy              client=utf8mb4  connection=utf8mb3  results=utf8mb4
  tras SET CHARACTER SET utf8mb4  client=utf8mb4  connection=utf8mb3  results=utf8mb4   <-- no cambia
  tras SET NAMES utf8mb4          client=utf8mb4  connection=utf8mb4  results=utf8mb4   <-- cambia
  juego por defecto de la BASE DE DATOS: utf8mb3
```

**La segunda mitad de la causa es esa última línea**: la base de datos se creó sin juego
explícito, así que quedó en el del servidor (`utf8mb3`), y `SET CHARACTER SET` hereda
justamente de ahí. Las tablas sí lo declaran, porque `SchemeCreator` lo emite por tabla; el
`CREATE DATABASE` no pasa por ahí. **Dos arreglos posibles, y son independientes**: cambiar la
sentencia en el paquete, o `ALTER DATABASE … CHARACTER SET utf8mb4`. Cualquiera de los dos
basta; los dos juntos dejan de depender de cómo se cree la base en el próximo despliegue —y eso
importa, porque **este framework se clona**.

> **Corroborante de la regla del prefijo (doc 12):** la ÚNICA tabla real que no está en
> `utf8mb4` es `pcs_unit_tests_core_database_exporter_v1`, la de una suite. La misma que hubo
> que descontar a mano en el censo de columnas. Una tabla de prueba en la base de la aplicación
> no falsea una medición: falsea todas las que la toquen. `SET NAMES` es el que fija los tres. Por eso el
`$config['database']['default']['charset'] = 'utf8mb4'` de este repositorio es correcto **y da
igual**: el paquete lo degrada a `utf8mb3` en la línea siguiente. El DSN tampoco lleva
`charset=`, así que no hay una segunda vía que lo salve.

Demostrado en las dos direcciones sobre la misma conexión, sin tocar el paquete:

```
sin tocar nada         HEX() = …203F20…              ¿idénticos? NO
SET NAMES utf8mb4      HEX() = …20F09F988020…        ¿idénticos? SÍ
```

Una sentencia. **No se arregla aquí**: el propietario pidió medir, y además vive en otro
repositorio, con su commit pendiente de la credencial.

### 3. Y ESTO RESUELVE EL ALCANCE DE T28 — pero no como esperaba: son DOS defectos

T28 decía que el `0x89` inicial del PNG se pierde **en la escritura**. Escrita la firma PNG por
el mismo camino:

```
conexión tal cual      bytes: 89504E470D0A1A0A   ->   HEX(): 3F504E470D0A1A0A
```

**Mismo síntoma, mismo `3F`.** Pero con la conexión arreglada el resultado NO es que funcione:

```
SET NAMES utf8mb4      SQLSTATE[22007] 1366: Incorrect string value: '\x89PNG\x0D\x0A…'
```

Y ahí está el reparto, que es lo que había que decidir:

| | Hoy (`utf8mb3`) | Con la conexión arreglada |
| :-- | :-- | :-- |
| **Emoji** (UTF-8 válido de 4 bytes) | se pierde en silencio | **funciona** |
| **PNG** (bytes que no son UTF-8) | se corrompe en silencio | **falla con error 1366** |

- **El emoji es un defecto de la CONEXIÓN.** Se arregla con `SET NAMES`, y queda arreglado.
- **El PNG es un defecto de MODELADO**: son bytes binarios en una columna de texto, y eso no lo
  arregla ningún juego de caracteres. Necesita `BLOB` o base64.

**El arreglo de la conexión no arregla el PNG: lo vuelve ruidoso.** Y eso es mejor —un error
1366 se ve, un `3F` no— **pero hay que decirlo antes de aplicarlo**, porque el día que se
cambie esa palabra, todo camino que hoy mete binario en una columna de texto **empieza a
lanzar**. Es justo la clase de cosa que convierte un arreglo correcto en una llamada del
cliente: **no hay que embarcar la trampa, pero tampoco desactivarla a ciegas un viernes.**

**Orden propuesto, y va con E2**, que es donde se ejercitan las escrituras: primero encontrar
qué columnas reciben binario hoy —solo aparece escribiendo—, luego arreglar la conexión.

### 4. Y una lección de método que salió de mi propia sonda

La sonda dejó **una fila viva** en `newsletter_sucribers`. Su `finally` borraba por `$id`, y
`$id` se calculaba **dentro del `try`**: en la ejecución que falló antes de calcularlo, el
`finally` corrió y no borró nada.

> **Un `finally` que limpia usando un valor calculado dentro del `try` no es una limpieza: es
> una limpieza condicional a que no falle lo de antes.** Lo que hay que capturar para poder
> borrar se calcula **antes** de entrar, o se borra por un criterio que no dependa del `try`
> —aquí, el prefijo `zz_emoji_`—.

Y solo se descubrió porque **se comprobó que no quedaban restos**, en vez de darlo por hecho.
Esa comprobación es la que se le exige a E2 en su condición 4; queda demostrada aquí de que
hace falta.

## T28bis · EL ALCANCE, RESUELTO — son DOS arreglos y una limitación de fondo

**T28 preguntaba dónde se pierde el byte del PNG. La respuesta completa reparte el problema en
tres piezas que hay que tratar por separado**, porque tienen causas, arreglos y dueños
distintos. Ver la medición en T37.

### (a) LA CONEXIÓN — arreglo de paquete, va como `piecesphp/database` v3.2.1

`Database.php:240` pasa de `SET CHARACTER SET` a `SET NAMES`. **Una palabra.**

Arregla la conexión **para todos, incluidas las bases ya creadas en `utf8mb3`**, porque
`SET NAMES` manda sobre el valor por defecto de la base en vez de heredarlo. Comprobado de
punta a punta escribiendo un emoji **por el ORM real** con el paquete parcheado:

```
  character_set_connection = utf8mb4
  bytes en PHP : 50525545424120F09F988020C3B1
  HEX() tabla  : 50525545424120F09F988020C3B1
  ¿idénticos?  : SÍ
```

**Es parche, no minor**: no cambia ninguna firma, los datos ya mal escritos siguen igual, y
ninguna lectura de datos sanos cambia de resultado.

> **`src/vendor/` quedó devuelto a 3.2.0 a propósito.** El parche se probó copiándolo ahí y se
> revirtió en cuanto se midió: la regla 8 dice que `src/vendor/` no se edita, y un parche a
> mano que `composer install` borrará deja este entorno comportándose distinto a todos los
> demás **sin que se vea**. El arreglo vive en el repositorio del paquete.

### (b) LA BASE SE CREÓ SIN JUEGO EXPLÍCITO — es de despliegue, no del paquete

`character_set_database` es `utf8mb3`. **No es un descuido de los mappers**: `SchemeCreator`
emite el juego por tabla —y por eso las 35 tablas reales están en `utf8mb4`— pero el
`CREATE DATABASE` no pasa por `SchemeCreator`.

Con (a) puesto esto es cinturón y tirantes. **Sin (b), cualquier cosa que lea el valor por
defecto de la base sigue mintiendo**: un `CREATE TABLE` a mano, una herramienta externa, un
volcado. Y este framework **se clona**, así que la próxima instalación nace igual salvo que se
arregle donde se crea.

- **Instalaciones existentes**: `ALTER DATABASE … CHARACTER SET utf8mb4 COLLATE utf8mb4_bin;`
  documentado en el `CHANGELOG` del paquete.
- **Instalaciones nuevas**: corregir el script o la guía de instalación para que nazcan en
  `utf8mb4`.

### (c) LA CAUSA DE FONDO — `SchemeCreator` NO ADMITE TIPOS BINARIOS

Y esto es lo que explica por qué el problema del PNG existe siquiera. Los tipos que acepta,
leídos de `SchemeCreator::$typeEquivalences`, son **doce y ninguno es binario**:

```
varchar  text  mediumtext  longtext  int  bigint
float    double  json  datetime  date  serialized_object
```

Ni `blob`, ni `binary`, ni `varbinary`. **Quien necesite guardar binario no tiene más remedio
que usar una columna de texto**, porque el generador no le ofrece otra cosa y la regla 7 dice
que el SQL no se escribe a mano.

**No es descuido de nadie: es una limitación del generador.** Queda como **candidato futuro**
—añadir tipos binarios a `SchemeCreator`—, **no se hace ahora**.

### Lo que hay que saber antes de publicar v3.2.1

Con la conexión arreglada, **lo que hoy se corrompe en silencio pasa a fallar**: escribir bytes
que no son UTF-8 en una columna de texto devuelve `SQLSTATE[22007]`, error 1366.

`bin/cli scan-invalid-utf8` responde quién está en riesgo, y **se corrió**:

```
  INFO: esquema 'piecesphp': 36 tabla(s) con columnas de texto.
  Filas leídas: 88
  OK: todo el texto analizado es UTF-8 válido.
```

> **Y aquí la cifra lleva su método, y el método incluye su límite: 88 filas es TODA la base
> de desarrollo.** 22 de las 36 tablas están vacías; el esquema entero tiene 89 filas. **Un
> resultado limpio sobre una base vacía no dice que no haya riesgo: dice que no hay datos.**
>
> Así que el escaneo no es la respuesta, es **la puerta**: se corre **en la base de cada
> despliegue** antes de subir el paquete, y va escrito como tal en el `CHANGELOG` de v3.2.1.
> Aquí no puede decidir nada, y decir lo contrario sería justo el error de T20.

## T38 · EL CENSO DE COMENTARIOS — medido antes de tocar nada, y el instrumento mintió dos veces

**Criterio mecánico, el que pidió el propietario**: bloque de comentario con **más de dos
líneas de prosa** y **ninguna anotación** (`@param`, `@return`, `@var`, `@package`, `@author`,
`@throws`). Los docblocks de API siempre traen anotaciones; los relatos no.

**Método reproducible** (T0 · 5): recorrido de `src/app` y `bin` —880 archivos `.php`, `.js`,
`.ts`; fuera `vendor`, `node_modules`, `bin/tools`, `.min.` y los estáticos del núcleo—,
agrupando líneas de comentario consecutivas y contando las que tienen texto tras quitar
`/**`, `*`, `//`. Clasificación por `git blame --ignore-revs-file`, campaña = commits desde
**2026-08-20**.

### Los números

| | Bloques | Líneas de prosa |
| :-- | --: | --: |
| **De esta campaña** (desde 2026-08-20) | **44** | **276** |
| **Ya estaban** | **88** | **453** |
| **TOTAL** | **132** | **729** |

### Y antes de leerlos, dos correcciones del instrumento, porque sin ellas el veredicto era otro

1. **`bin/tools/phpstan-src/` se coló entero.** La lista de exclusión decía `/bin/tools/` con
   barra inicial y la ruta real es `bin/tools/…`, así que no casaba. Metía **438 bloques de
   código de terceros** —un `constantToFunctionParameterMap.php` con 38 él solo— en un censo
   que pretendía medir código propio.
2. **`git blame` atribuía TODO a la campaña.** El commit `0ac751b9` renormalizó los finales de
   línea de **1.126 archivos**: para `blame`, la campaña tocó la última línea de casi todo.
   Sin `--ignore-revs-file`, el censo decía **114 bloques nuestros y 18 viejos**. Con él dice
   **44 y 88**.

> **La segunda es la grave, y merece quedarse escrita: el número equivocado no era ruidoso,
> era COHERENTE.** «El 87% de los comentarios narrativos son de esta campaña» encajaba
> perfectamente con la sospecha que originó el encargo, y por eso habría pasado sin que nadie
> lo cuestionara. **Un dato que confirma lo que ya se sospechaba es el que menos se audita**, y
> es justo el que más hay que auditar. T20, otra vez, y esta vez el instrumento era mío y el
> resultado me daba la razón.

### Qué dicen los números buenos

**No es una deriva nuestra: es un tercio nuestro y dos tercios heredados.** 44 contra 88.

Eso descarta la lectura fácil —«recortamos lo nuestro y ya»— y también la contraria —«esto
venía así»—. Aplicando la regla de decisión del propietario, 132 no son pocos, así que:

- **Los 44 nuestros se recortan**, uno a uno, al reparto de T0 · 7: la guarda en una línea, el
  relato al `CHANGELOG`.
- **Los 88 viejos van a una lista cerrada** con su razón escrita, misma forma que
  `KNOWN_ECLIPSES`.
- **La puerta falla ante cualquiera nuevo que no esté en la lista**, y se prueba en las dos
  direcciones antes de darla por buena (T21).

**Los nuestros se concentran donde era previsible**, que es la parte tranquilizadora: cuatro
en `bootstrap.php`, cuatro en `ServerStatics.php`, cuatro en `VerifyIntegrityTask.php`, cuatro
en `UnitTest-MapperFinders.php`, tres en `UnitTest-OTPFreshUser.php`. Son los archivos donde
más se explicó por qué algo está como está — y es exactamente el sitio donde el relato debía
haber ido al documento.

**PENDIENTE DE DECISIÓN DEL PROPIETARIO**: se midió y no se tocó nada, que era el encargo.

## T39 · LIMITACIÓN PERMANENTE DEL ENTORNO — esta base no puede responder preguntas sobre datos

**Ya nos ha pasado dos veces, así que deja de ser una anécdota y pasa a ser una propiedad
conocida del entorno que hay que consultar ANTES de diseñar una medición.**

### La medición

```
tablas base: 36 | vacías: 22 | con filas: 14
filas totales en el esquema: 89
```

Las cinco tablas más pobladas: `locations_cities` (24), `pcsphp_app_config` (22),
`login_attempts` (14), `system_approvals_elements` (8), `pcsphp_users` (6).

*Método: `SELECT COUNT(*)` sobre cada `BASE TABLE` de `information_schema.TABLES`.*

### La regla

> **CUALQUIER MEDICIÓN QUE DEPENDA DEL CONTENIDO DE LA BASE ES NO CONCLUYENTE EN ESTE ENTORNO,
> Y HAY QUE MARCARLA COMO TAL EN EL SITIO DONDE SE ESCRIBA LA CIFRA.**

No es que dé un resultado equivocado: **da el resultado correcto de una pregunta distinta.**
`scan-invalid-utf8` no mintió — analizó las 88 filas que hay y todas son UTF-8 válido. Lo que
no puede decir es nada sobre un despliegue con datos. **«Limpio» y «vacío» se leen igual en la
salida**, y esa es toda la trampa.

### Los dos casos que la establecieron

| Caso | Qué se preguntó | Por qué no se pudo responder |
| :-- | :-- | :-- |
| Las tres tablas vacías (T33) | «¿por qué están vacías?» | No había rastro porque no había datos. **Las había vaciado el propietario a mano** |
| `scan-invalid-utf8` (T28bis) | «¿quién escribe binario en columnas de texto?» | 88 filas. Un resultado limpio sobre una base vacía **no dice que no haya riesgo: dice que no hay datos** |

**Lo que sí se puede responder aquí son preguntas sobre CÓDIGO y sobre ESQUEMA**, y de hecho
las dos veces la respuesta útil salió de ahí: el censo de columnas contra
`information_schema`, y la limitación de `SchemeCreator` leída de su tabla de tipos. **La
pregunta se reformula de «qué datos hay» a «qué puede escribir el código», que es la que este
entorno sí sabe contestar.**

### Y la consecuencia buena, que va al diseño de E2

**El ciclo CRUD de E2 va a POBLAR la base.** Crear, leer, editar y borrar por cada módulo deja
filas reales escritas por los caminos reales — que es justo el material que hoy falta.

Así que esta limitación **se reduce después de E2**, y con ella un grupo concreto de preguntas
pasa de no contestables a contestables:

- **Qué columnas de texto reciben binario** — la que bloquea la publicación tranquila de
  `database` v3.2.1.
- **Qué pasa cuando `$_FILES[$key]` es nulo** — los 9 anotados para E2.
- **Si el emoji sobrevive el viaje entero** por un formulario real, no solo por el ORM.
- **Si `SchemeCreator` y las meta-propiedades en JSON hacen lo que dice su documentación.**

> **Anotado como orden, no como observación: las preguntas de la lista de arriba NO se intentan
> responder antes de E2.** Intentarlo produce exactamente el resultado de `scan-invalid-utf8`:
> una respuesta limpia que no significa nada, y que además **parece** que significa algo.

## T40 · EL RECORTE Y LA PUERTA — hecho, con dos errores míos por el camino

### Lo que se hizo

| | Antes | Después |
| :-- | --: | --: |
| Bloques narrativos | **132** | **91** |
| Líneas de prosa | **729** | **486** |
| De ellos, nuestros | 46 (284 líneas) | **5 (45 líneas)** |

*Método (T0 · 5): `bin/cli verify-integrity list-narrative=yes` para los bloques; autoría por
`git blame --ignore-revs-file` con los dos commits de renormalización, campaña = commits desde
2026-08-20.*

**Se recortaron 41 bloques y 243 líneas**, todos nuestros, al reparto de T0 · 7: la guarda en
una línea, el relato al `CHANGELOG` o a este documento. Ejemplo del reparto, sobre el 2FA:

```php
//No pongas twoAuthFactor en ENABLED aquí: preparar no es activar. Lo activa confirm2FA().
```

Doce líneas pasaron a una, y **la única que impedía romper algo es la que se quedó**.

### La puerta

`bin/cli verify-integrity` gana su **octava** comprobación, y el registro es
`files/dev/narrative-comments.json` — **66 archivos, 91 bloques, 486 líneas**.

Dos decisiones de diseño que valen más que el código:

1. **Guarda las LÍNEAS DE PROSA, no solo el número de bloques.** Sin eso, un bloque que crece
   de 4 a 30 líneas **mantiene el conteo de entradas** y el archivo empeora sin que la puerta
   se entere. «La lista solo puede encoger» tiene que ser medible en la unidad que importa.
2. **Se ancla por ARCHIVO, no por línea.** Anclar por línea convertiría la puerta en un
   generador de ruido en cuanto alguien edite algo encima.

Probada en las **tres** direcciones en las que puede fallar, y en verde después (T21):

```
(1) bloque nuevo en archivo no registrado  -> FALLOS: 1
(2) la prosa de un archivo registrado CRECE -> «CRECIÓ de 3 a 6 líneas» -> FALLOS: 1
(3) entrada registrada que ya no tiene bloques -> «quita la entrada» -> FALLOS: 1
(4) sin tocar nada -> OK
```

La tercera no es celo: sin ella el registro se queda con entradas fantasma y **deja de poder
encoger de verdad**, porque nadie limpia lo que no falla.

### Los 5 nuestros que NO se recortaron, y por qué son un hallazgo

La firma mecánica tiene **falsos positivos, y son de un tipo concreto**: documentación de API
en un sitio donde no cabe una anotación.

`config/roles.php` documenta el formato de `$config['roles']` con su ejemplo de uso —23 líneas
de prosa, cero anotaciones—. **No es un relato: es la referencia del archivo**, y un `@param`
no cabe porque no hay función que anotar. Igual `config/lang.php`, `database.php`, `Config.php`
y las notas de dominio de `OrganizationMapper` sobre qué puede hacer cada tipo de usuario.

> **Que la firma tenga falsos positivos no la invalida: los mete en el registro con su razón,
> que es exactamente lo que hay que hacer con ellos.** Una puerta que solo acierta no necesita
> registro; el registro existe **porque** la firma es mecánica y a veces se equivoca. Lo que no
> vale es afinar la firma hasta que deje de dar falsos positivos: eso la vuelve tan específica
> que deja de atrapar lo que se buscaba.

### Y los dos errores míos, que son el mismo error dos veces

**(a) Recorté por número de línea contra un mapa ya desplazado.** La lista de bloques se sacó
ANTES del primer lote; el primer lote movió líneas; el segundo lote usó las líneas viejas. Ocho
comentarios acabaron **describiendo un código que no era el suyo**. Uno decía «comprueba el
desbordamiento de `chr()`» encima de una detección de TTY.

**Es T10 otra vez**, y esta vez ni siquiera por regex: por índice contra una foto caducada. La
corrección fue rehacerlo **anclando por el CONTENIDO de la primera línea del bloque**.

**(b) Lo grave no es el error, es que casi lo doy por bueno.** Los 29 recortes compilaban, las
suites pasaban en verde y la puerta contaba menos bloques. **Todas las señales decían que había
salido bien**, porque ninguna de ellas mira si un comentario dice la verdad.

> **NINGUNA PUERTA AUTOMÁTICA DETECTA UN COMENTARIO QUE MIENTE.** `php -l` no, PHPStan no, las
> suites no, y la puerta nueva tampoco — cuenta líneas, no verdades. Lo que lo detectó fue
> **imprimir el ANTES y el DESPUÉS de cada bloque uno al lado del otro** y leerlos.
>
> Y de ahí la regla operativa: **todo recorte de comentarios se audita enseñando el par
> ANTES/AHORA, entero, sin muestrear.** Es la única comprobación posible, cuesta un minuto, y
> sin ella este trabajo habría metido ocho mentiras en el código **con todas las puertas en
> verde** — que es la definición exacta de embarcar una trampa.

### El trinquete también hizo su trabajo

La comprobación nueva subió PHPStan de **874 a 875**: un `str_replace` cuyo tipo de retorno
PHPStan ve como `array<string>|string`. Arreglado en la raíz —castear el argumento, no el
resultado— y de vuelta a **874**. Segunda vez en la campaña que el trinquete atrapa una
regresión mía el mismo día que la introduzco.

## T41 · E1 SE CIERRA — las 285 por supresión con motivo, y el grupo B que NO se pudo cerrar

### 1. Las 285 ramas: cerradas, y el `.neon` deja de ser una lista

El bloque existía y **silenciaba veintiséis identificadores sin una sola razón escrita**, que
es exactamente el «cajón de sastre» que T0 · 3 prohíbe. Ahora son **cinco motivos**, cada uno
con lo que lo sostiene y su condición de retirada:

| Motivo | Ramas | Por qué se queda |
| :-- | --: | :-- |
| **1 · Defensa sobre datos que el tipo no describe** | 129 | La comprobación es redundante **para el tipo declarado**, no para el valor que llega. Peticiones, filas y JSON entran con el tipo que les da la gana |
| **2 · Interruptores de módulo** | 65 | PHPStan resuelve la constante al valor **de este árbol**. Borrarlas cablearía todos los módulos en ENCENDIDO. **Sin condición de retirada** |
| **3 · Variables que inyecta el renderizador** | 38 | La vista las recibe por `extract()`; PHPStan analiza el archivo sin saber que existen |
| **4 · Estrechamientos que en ejecución no lo están** | 46 | El tipo viene de un docblock, y esos mienten con más facilidad que el código |
| **5 · Inofensivo y real** | 4 | `break;` detrás de un `return`. Quitarlo no cambia nada; el ruido cuesta más |

Y **se retira una supresión muerta**: `foreach.emptyArray` ya no silencia nada porque las 24 se
arreglaron. Una supresión que no suprime es la misma deuda que un `ignoreErrors` de más.

> **Una trampa evitada por poco, y merece quedar escrita.** Al agrupar, añadí
> `identifier: nullCoalesce.offset` junto al mensaje del `??` que ya estaba. El baseline bajó
> a 873 — **y no había arreglado nada**: el identificador entero se llevaba un caso que el
> mensaje no cubría. **Bajar el baseline ensanchando una supresión es exactamente lo que el
> trinquete no puede premiar**, porque lo lee igual que un arreglo. Revertido; vuelta a 874.

### 2. El grupo B: se cerró la parte que tenía razón, y NO la que no la tenía

**99 errores visibles mencionan `false`.** El encargo era cerrarlos por supresión agrupada por
motivo. **No se pudo, y decirlo es parte del trabajo.**

Al separar por gravedad —no por identificador— aparecen dos poblaciones distintas:

**Los 14 graves: llamar a un método sobre un `false` es un FATAL, no un aviso de tipos.**
Leídos los 14, se parten en dos y **no eran una familia**:

- **12 seguros.** `createFromFormat()` cuyo sujeto es un literal o lo produce `date()` con un
  formato compatible: `createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:00'))`. El productor y
  el formato están **en la misma línea y bajo nuestro control**; el `false` es inalcanzable.
  Suprimidos con su razón. *Pedir una rama aquí es pedir un `if` que nunca se cumple.*
- **2 defectos, que salen de la supresión y se arreglan**, tal como manda la regla:
  - **`src/index.php`** (3 errores): el sujeto salía del **NOMBRE DE UN ARCHIVO** del
    directorio de sesiones expiradas. Un archivo que no encajara con el patrón devolvía
    `false` y **reventaba la petición**. Guarda añadida.
  - **`PublicationsController`**: el sello del mapper podía llegar **sin hidratar**, y
    `getTimestamp()` sobre una cadena es un fatal. Guarda `instanceof`, y el valor por defecto
    pasa a `new DateTime()`, que no puede devolver `false`.

**Quedan 0 errores visibles de la forma «llamar a un método sobre un `false`».**

**Los 85 restantes NO se suprimen, y este es el punto que hay que defender:**

> El encargo decía «supresión con razón escrita, y si al escribir la razón descubres que es un
> defecto, ese sale y se arregla». **Aplicada literalmente a estos 85, la regla se muerde la
> cola**: son `false` que llegan como argumento o como retorno, y **no se puede saber cuál es
> defecto sin leerlos uno a uno** — que es el triaje que el mismo encargo prohíbe.
>
> Los 14 graves se pudieron cerrar porque **la gravedad era mecánicamente separable**: «llamar
> a un método sobre `false`» es un patrón de mensaje, no un juicio. Para los 85 no existe ese
> corte, **y no lo hay porque la respuesta no está en el código: está en los datos.** Si
> `realpath()` devuelve `false` en producción o nunca, esta base de 89 filas no lo sabe (T39).
>
> **Van a E2 con nombre y apellidos**, no a una cola indefinida: el ciclo CRUD es donde una
> fecha mal formada, una ruta que no existe o un `json_encode` que falla llegan de verdad.

### 3. Estado de cierre de E1

| | |
| :-- | --: |
| **Baseline PHPStan** | **859** (era 874: −4 arreglos, −11 supresión documentada) |
| **Ramas muertas** | **285**, todas con motivo escrito |
| **Fatales-si-`false`** | **0** |
| **Bloques narrativos** | **91** (eran 132), registro cerrado y puerta |
| **Comprobaciones de `verify-integrity`** | **8** |
| **Suites** | 25/25, 13/13+5, 13/13, 6/6, 12/12, 23/23 |

**Y la cifra del baseline lleva su reparto escrito en `PHPStanResult.Summary.baseline.txt`**,
porque «bajó 15» y «arreglamos 15» no son lo mismo, y el trinquete no distingue solo.

## T42 · LA PUERTA QUE APROBÓ CUATRO REPOSITORIOS DIVERGENTES

`shared-toolchain` comprobaba que los archivos del instrumental **contuvieran sus marcas**, y
aprobaba en verde sobre cuatro repositorios que **no estaban sincronizados**. El defecto no era
la divergencia: era el alcance. Ampliada en el mismo commit, como manda T21.

### Lo que se le escapaba, medido

| Divergencia | Estado antes | Por qué la puerta no la veía |
| :-- | :-- | :-- |
| **`PHPStanResult.json`** | versionado en `piecesphp`, **ni versionado ni ignorado** en los cuatro | Miraba el CONTENIDO de los archivos, no el estado de seguimiento de lo que las herramientas PRODUCEN |
| **`bin/Preview/`** | ignorado en `piecesphp`, **generado y suelto** en `html` (dos `.md` dentro) | Ídem |
| **`bin/cli` de `database`** | fijaba `php8.4` a mano, **sin `PCSPHP_PHP_BIN`** | `bin/cli` no estaba en el registro: solo se vigilaban `phpstan`, `process-result`, el `.neon` y el baseline |
| **El bit de ejecución de `bin/phpstan`** | `100755` en dos paquetes, **`100644` en `database` y `geojson`** | El modo del archivo no es contenido, y nadie lo miraba |

**El último es el más ilustrativo, y salió solo al ir a usar la herramienta**: `./bin/phpstan`
respondía **«Permiso denegado»** en dos de los cuatro. Un fallo que nadie relaciona con una
divergencia de repositorio — se lee como un problema de la máquina, se arregla con un `chmod`
local, y **vuelve en el siguiente clon**.

> **Y el patrón detrás de las cuatro es el mismo, que es lo que hay que llevarse:** las líneas
> de `.gitignore` de los archivos intermedios SÍ se habían propagado a los cuatro. **La
> decisión sobre el archivo de la unión, no.** Se propagó lo que se escribió en un archivo
> compartido, y no se propagó lo que se decidió sin escribirlo en ninguno. **Una política que
> vive en la cabeza de alguien se propaga a exactamente un repositorio.**

### Lo que vigila ahora

Tres capas, y las tres se probaron **provocándoles el fallo**:

1. **Marcas** (lo que ya hacía) — más `bin/cli`, marcada como opcional: solo se le exige a los
   paquetes que lo tengan.
2. **Seguimiento**: qué debe estar versionado (`PHPStanResult.json`, porque el resumen se
   construye leyéndolo y sin él el método escrito en el baseline no es reproducible desde el
   repositorio) y qué debe estar ignorado (los dos intermedios y `bin/Preview`). **Distingue
   los tres estados**: versionado, ignorado, y *ni una cosa ni la otra* — que era justamente el
   estado en el que estaban.
3. **Bit de ejecución** de `bin/phpstan` y `bin/cli`.

```
(1) PHPStanResult.json sin versionar en geojson  -> «NO está versionado y debería estarlo»
(2) un intermedio versionado en html             -> «está VERSIONADO y debería estar ignorado»
(3) sin la regla de Preview en datastructures    -> «no está ignorado ni versionado»
(4) bin/cli sin PCSPHP_PHP_BIN                   -> «se ha desviado: no contiene PCSPHP_PHP_BIN»
(5) bin/phpstan en 100644                        -> «está en git sin el bit de ejecución»
(6) sin tocar nada                               -> OK
```

### La política, escrita de una vez para los cinco

| Archivo | Estado | Por qué |
| :-- | :-- | :-- |
| `PHPStanResult.json` | **versionado** | Es de donde sale el dato del resumen. Sin él, el método del baseline no es reproducible |
| `PHPStanResult.8.4.json`, `.8.5.json` | **ignorados** | Intermedios de cada pasada. Se regeneran |
| `bin/Preview/` | **ignorado** | Salida generada por `phpstan-process-result.php` |
| `bin/phpstan`, `bin/cli` | **`100755`** | Un script que no se puede ejecutar no es una herramienta |

## T43 · v3.2.1 VERIFICADA CONTRA EL VENDOR REAL, Y LO QUE SE DESCARGA UN CONSUMIDOR

### 1. La re-verificación, que era lo urgente

Lo medido hasta ayer se midió parcheando `src/vendor/` a mano, **sobre un entorno que ya no
existe**. Con la versión publicada y `composer update piecesphp/database`:

```
  instalado: v3.2.1 ref d18c5b6b        (el commit exacto, desde el remoto)
  character_set_connection = utf8mb4
  character_set_database   = utf8mb3     <-- la base SIGUE en utf8mb3
  bytes en PHP : 50525545424120F09F988020E29DA4EFB88F20C3B1
  HEX() tabla  : 50525545424120F09F988020E29DA4EFB88F20C3B1
  ¿idénticos?  : SÍ
```

**Y el dato que lo cierra es la tercera línea**: la base sigue en `utf8mb3` y la conexión ya es
`utf8mb4`. Eso es exactamente lo que se afirmó y no se había demostrado sobre el paquete
publicado — que `SET NAMES` **manda sobre el valor por defecto de la base**, así que el arreglo
funciona sin tocar ninguna instalación existente.

### 2. La prueba que le faltaba, y por qué la primera versión no valía

Añadida a `unit-tests/UnitTest-Database.php` del paquete. **La primera versión que escribí
pasaba sin demostrar nada, y lo dijo ella misma**:

```
INFO: Pedido: utf8mb4 | conexión: utf8mb4 | por defecto de la base: utf8mb4
[OK] character_set_connection es el juego PEDIDO.
INFO: La base por defecto coincide con lo pedido: la comprobación de arriba NO discrimina en este servidor.
```

**La base de pruebas del paquete ya es `utf8mb4`, así que `SET CHARACTER SET` habría dado el
mismo resultado.** Una comprobación que pasa igual con el defecto puesto no es una
comprobación.

**El arreglo es cambiar la pregunta**: no «¿la conexión es `utf8mb4`?» sino **«¿la conexión
sigue lo PEDIDO?»**, pidiendo a propósito un juego **distinto del de la base**:

```
INFO: Por defecto de la base: utf8mb4 | pedido a propósito distinto: latin1 | conexión: latin1
[OK] character_set_connection sigue lo PEDIDO y no lo de la base.
```

Y con el defecto puesto de vuelta —`SET CHARACTER SET`— cae:

```
INFO: ... pedido: latin1 | conexión: utf8mb4
[FALLÓ] character_set_connection es 'utf8mb4' y se pidió 'latin1': la conexión está heredando
        el juego de la base.
```

> **La lección, que no es sobre charsets: UNA COMPROBACIÓN QUE DEPENDE DE UNA COINCIDENCIA DEL
> ENTORNO NO COMPRUEBA NADA, Y NO SE NOTA PORQUE SALE EN VERDE.** Aquí se notó solo porque la
> propia comprobación imprime en qué condiciones está midiendo. **Que una puerta declare cuándo
> NO discrimina es tan importante como que falle cuando debe.**

La ida y vuelta del emoji se conserva además de la anterior, y tiene el mismo matiz al revés:
**en este servidor no discrimina** —la base es `utf8mb4`— pero sí lo hace en una base `utf8mb3`
como la de la aplicación. Las dos juntas cubren los dos escenarios.

### 3. Lo que un consumidor se descarga hoy — medido sobre el vendor real

**El paquete instalado son 1,1 MB en 132 archivos, y ~304 KB de eso es instrumental de
desarrollo. Un 28 %.**

| Qué | Tamaño |
| :-- | --: |
| `bin/` (nuestro PHPStan, Rector y su configuración) | 80 KB |
| `unit-tests/` | 76 KB |
| `demos/` | 120 KB |
| Los cuatro `PHPStanResult*` | 28 KB |

Los otros tres paquetes están más limpios **solo porque sus versiones publicadas son
anteriores** a los commits de esta campaña: hoy llevan `bin/` (16 KB) y nada más, pero en
cuanto se publiquen arrastrarán lo mismo.

**`export-ignore` SÍ aplica aquí, y esto había que comprobarlo antes de proponerlo:** solo
surte efecto en instalaciones por **dist**, porque es `git archive` quien lo respeta; en una
instalación por *source* (un clon) no hace nada. Medido:

```
  database         .git presente: no   -> instalación dist (zip)
  lock: piecesphp/database v3.2.1  dist:zip  source:git
```

Los cuatro entran por `dist:zip`, y Bitbucket sirve ese zip con `git archive`. **Así que
funcionaría.**

**NO SE HA TOCADO**, a la espera de decisión, y con dos cosas que conviene decidir con ello:

- **`demos/` son 120 KB y puede que se quieran distribuir**: es documentación ejecutable, no
  instrumental. Es la única de las cuatro que no es obviamente descartable.
- **`export-ignore` no es retroactivo**: las versiones ya publicadas seguirán trayéndolo todo.
  Solo limpia de la próxima etiqueta en adelante.

## T44 · E2 — LA FOTO: primera pieza construida, `bin/cli snapshot`

**El propósito, que no se puede perder de vista**: E3 borra trece mil quinientas líneas y edita
módulos que se quedan. **La única red es poder comparar contra un antes reproducible**, y todo
lo que E2 construya se usa seis veces en E3, una por lote.

### Lo que hace

Fotografía **la base entera y el árbol servido**, y compara dos fotos atribuyendo cada
diferencia a lo que corrió entre ellas.

```bash
bin/cli snapshot label=antes
… lo que sea que se quiera medir …
bin/cli snapshot label=despues
bin/cli snapshot compare=antes,despues
```

**Base de datos**: por tabla, el conteo, un hash agregado y **un hash por fila indexado por su
clave primaria** —sacada de `information_schema`, no adivinada—. Sin la clave el diff sabría
que algo cambió pero no **qué**, y para atribuir escrituras a rutas eso es justo lo que hace
falta.

**Árbol de archivos**: tamaño, `mtime` y hash de cada archivo bajo `src/`, fuera `vendor`,
`node_modules`, `dumps`, `tmp`, `logs` y las propias fotos.

Salida, comprobada provocando las dos cosas:

```
── BASE DE DATOS ──
  ~ newsletter_sucribers — 0 -> 1 (+1)
      + fila 8
── ÁRBOL DE ARCHIVOS ──
  + src/statics/zz-foto-prueba.txt

DIFERENCIAS: 2 — cada una hay que justificarla o arreglarla.
```

### Tres decisiones, y las tres vienen de errores anteriores

1. **Nada de recortes silenciosos.** Por encima de 20.000 filas no guarda hashes por fila —el
   diff dejaría de caber en pantalla—, pero **lo dice, y nombra las tablas**. Un recorte que no
   se declara se lee como cobertura completa, que es media docena de casos de T20.
2. **El censo tiene que aguantar un árbol que se mueve.** Falló a la primera con
   `sha1_file(): Failed to open stream`: la aplicación **escribe y borra archivos temporales
   mientras se la mide** —`missing-lang-messages` lo hace en cada petición—. Un archivo que
   desaparece entre el listado y la lectura no es un fallo del censo, y tratarlo como tal
   convierte la herramienta en un generador de falsos positivos.
3. **Las fotos NO se versionan** (`files/dev/snapshots/.gitignore`). Son de una máquina y de un
   momento. Lo que se versiona es la herramienta y el informe que sale de compararlas.

### Y ya encontró algo, sin ir a buscarlo

Entre dos invocaciones de `bin/cli` sin nada en medio:

```
~ time_on_platform — 1 -> 1 (mismas filas, contenido distinto)
    ~ fila 1
```

**Una tabla que cambia sola en cada arranque de la CLI.** No es un defecto —parece
contabilidad de tiempo— pero **es exactamente el tipo de escritura que hay que declarar antes
de medir**, o aparecerá en las 184 comparaciones del recorrido y las ensuciará todas.

> De ahí la condición que ya estaba escrita en el diseño de E2 y que esto confirma: **las
> escrituras legítimas de los caminos de lectura se declaran ANTES de medir, con su motivo.
> Declararlas después de ver el diff es adaptar la regla al resultado.**

Una petición pública a la portada, medida entera: **cero diferencias**. La primera línea de la
tabla que E2 tiene que entregar.

### Lo que falta de E2

| | Estado |
| :-- | :-- |
| **(a)** Base restaurable | **Pendiente.** `db-backup` existe **pero cifra `password` al exportar**: un restaurado volvería a cifrar lo ya cifrado. Hay que resolver eso antes de usarlo como red |
| **(b)** Recorrido con la salida nueva | **La herramienta está**; falta engancharla al recorredor ruta a ruta para poder ATRIBUIR |
| **(c)** Ciclo CRUD por módulo | Pendiente |
| **(d)** Salidas cortadas | Pendiente |
| **(e)** Prefijo reconocible | Convención ya en uso (`zz_`, `pcs_unit_tests_`) |
| **(f)** Árbol limpio y etiqueta | Al final |

## T45 · `db-backup` CIFRABA LAS CONTRASEÑAS Y NADIE LAS DESCIFRABA — severidad alta

**Una restauración dejaba a TODOS los usuarios sin poder entrar.** Está embarcado en todos los
despliegues, y el peor momento para descubrirlo es el día que hace falta el respaldo.

### El viaje de ida y vuelta, medido y no deducido

`DbBackupTask` aplicaba una transformación al exportar:

```php
'password' => function ($val) { return BaseHashEncryption::encrypt($val, 'ENCRYPTION_KEY'); }
```

Volcado, restaurado en una base de usar y tirar, e intentado el login:

```
  password de root VIVO      : $2y$10$5KEzolPgoFt/ZwykXvzJ9usmCzFgcY8H5UiyJV5rmHPJkrZoHl20u
  password de root EN VOLCADO: fXfHZ4OJdImUlMjOt5XAtJS3gbPHzbSnxNmVfs64u4bMn7e3ooeWlKCu0o-…
  password_verify("123456", VIVO)    : TRUE   <- se entra
  password_verify("123456", VOLCADO) : false  <- NO SE ENTRA

  password de root RESTAURADO: fXfHZ4OJdImUlMjOt5XAtJS3gbPHzbSnxNmVfs64u4bM…
  INTENTO DE LOGIN password_verify("123456", …) -> FALSE — NO SE PUEDE ENTRAR
```

### Por qué cifraba, que era la pregunta correcta

**La intención se entiende**: un volcado es un archivo que puede acabar en un correo o en un
disco compartido, y proteger los hashes ahí parece razonable. **La asimetría es el defecto, no
el cifrado.**

Pero al mirarlo de cerca, el cifrado tampoco protegía nada:

- **La clave es la cadena literal `'ENCRYPTION_KEY'`**, escrita en el propio archivo. No es una
  constante, no sale de la configuración: es texto en el repositorio.
- **Aparece UNA sola vez en todo el código** — en la llamada que cifra. **No hay ni un
  `decrypt` con esa clave en ninguna parte**, ni en los importadores ni en el módulo de
  importación de datos.

Es decir: cualquiera con el código descifra el volcado en una línea, y nadie con el código
puede restaurarlo. **Protección cero, restauración rota.**

Comprobado además que es perfectamente reversible:

```
  decrypt(VOLCADO, "ENCRYPTION_KEY")  : $2y$10$5KEzolPgoFt/ZwykXvzJ9usmCzFgcY8H5UiyJV5rmHPJkrZoHl20u
  ¿coincide con el hash vivo?         : SÍ
```

**Y esa es la vía de recuperación para quien tenga volcados viejos**: no están perdidos.

### El arreglo, y la prueba que lo sostiene

La transformación se retira. Un volcado nuevo lleva el hash tal cual, que es lo que hace
`mysqldump` y lo que permite restaurar.

`UnitTest-DbBackupRoundTrip` fija el viaje: crea un usuario propio con contraseña conocida,
lanza `db-backup`, busca su fila en el archivo y comprueba que **`password_verify` contra lo
que hay EN EL VOLCADO devuelve true**. Borra el usuario y el volcado siempre.

**Y se validó rompiéndola** (T21 aplicado a una prueba): reintroducida la transformación,

```
   [FALLÓ] el hash viaja INTACTO al volcado — en el volcado hay «fXfHZ4OLdMabwMTDfq66l5R7…»
   [FALLÓ] password_verify contra lo que hay EN EL VOLCADO devuelve true
   BALANCE FINAL: 2/4 PASADAS, 2 FALLIDAS
```

### Lo que esto desbloquea

Era la condición (a) de E2 —la base restaurable como red de la limpieza— y estaba rota antes de
empezar. **Y no era trabajo de E2**: era un defecto embarcado que E2 se encontró de paso.

## T46 · UNA PRUEBA QUE PASARÍA CON EL DEFECTO PUESTO NO ES UNA PRUEBA

**Corolario de T21, aplicado a las pruebas en vez de a las puertas. Y ya tiene dos casos, uno
de ellos grave.**

### Caso 1 — la prueba del `SET NAMES` (T43)

Preguntaba «¿la conexión es `utf8mb4`?». La base de pruebas ya lo es por defecto, **así que
`SET CHARACTER SET` habría dado el mismo resultado**. Se detectó solo porque la propia
comprobación imprime en qué condiciones mide.

Arreglo: cambiar la pregunta a **«¿la conexión sigue lo PEDIDO?»**, pidiendo un juego distinto
del de la base. Probada rompiéndola.

### Caso 2 — `otp-write-separation` no habría cazado a D2. GRAVE

**La suite que existe para fijar el defecto D2 pasa en verde con D2 reintroducido.**

Sus cuatro comprobaciones eran **textuales**: `grep` de `->save(`, `->update(` y `->delete(`
sobre el cuerpo del método. Reintroducido el defecto **delegando la escritura a otro método**
—que es EXACTAMENTE la forma que tenía D2, donde el buscador llamaba a un creador—:

```
   [PASÓ] getOTPData() no contiene ninguna escritura del ORM
   [PASÓ] getTOTPData() no contiene ninguna escritura del ORM
```

**Un grep sobre el cuerpo no ve una escritura que ocurre una llamada más abajo.**

> **La lección general: una comprobación sobre el TEXTO del código verifica una propiedad del
> texto, no del comportamiento.** Sirve para lo que sirve —es barata y no necesita datos— pero
> **no se puede presentar como prueba de que algo no ocurre**. Lo que no ocurre solo se
> demuestra ejecutándolo.

**Arreglo**: la suite gana una comprobación de comportamiento. Crea un usuario propio sin filas
de OTP, llama a los dos buscadores **de verdad**, y cuenta filas antes y después. Sube a 7/7.

### La regla

> **TODA PRUEBA NUEVA SE VALIDA ROMPIENDO LO QUE DICE PROTEGER**, igual que las puertas. Si al
> reintroducir el defecto la prueba sigue verde, la prueba no existe — existe la sensación de
> que existe, que es peor, porque ocupa su sitio.

Y el corolario para elegir la forma:

| Forma de comprobar | Qué verifica | Cuándo NO basta |
| :-- | :-- | :-- |
| `grep` sobre el código | una propiedad del **texto** | cuando el defecto puede delegarse una llamada más abajo |
| Ejecutar y medir el estado | una propiedad del **comportamiento** | cuando el entorno hace cierta la afirmación por otro motivo (caso 1) |

**Las dos formas fallan de maneras distintas, así que la respuesta no es «usa siempre la
segunda»: es validar cada una rompiendo lo que protege.**

### Y una retractación: `time_on_platform` NO es volátil

Reporté que cambiaba sola entre dos invocaciones de `bin/cli`. **No se reproduce**: dos fotos
seguidas sin nada en medio salen idénticas, y con una petición web de por medio también.

```
=== dos fotos seguidas, NADA en medio ===
── BASE DE DATOS ──
(sin diferencias)
```

Leído el código, `TimeOnPlatformModel::addTime()` solo se alcanza desde `TimerController`, que
**exige `seconds` y `user_id` por POST**: una invocación de la CLI no puede dispararlo.

**La observación original estaba contaminada** —había un login por medio— y la di por buena con
una sola medición **porque encajaba con lo que ya esperaba encontrar**: que los caminos de
lectura escriben. Es el patrón de T20 en su forma afilada, esta vez cometido por mí en el mismo
día en que lo escribí.

### La volatilidad que SÍ está medida

`files/dev/volatile-state.json`, con dos entradas y las dos comprobadas:

| Qué | Por qué es legítimo |
| :-- | :-- |
| `login_attempts` | Cada intento de acceso escribe su fila. Es el registro de auditoría para el que existe el módulo |
| `src/app/lang/missing-lang-messages/` | El framework anota cada cadena sin traducir para que `scan-missing-lang` la encuentre. **Es una escritura en un camino de lectura, y es deliberada**: pedir `/en/` crea archivos aquí |

La segunda es el **tercer camino de lectura que escribe** confirmado, y esta vez no salió por
accidente: salió porque había una herramienta mirando.

`bin/cli snapshot compare` los separa del resto y **falla con salida 1 ante cualquier cambio no
declarado**. Probado en las dos direcciones.

## T47 · LA CEGUERA POR DELEGACIÓN, AUDITADA CON MUTACIONES

Que `otp-write-separation` pasara en verde con D2 puesto (T46) no era un caso aislado: era una
**clase** de defecto. Auditados con mutaciones reales los otros clasificadores que juzgan por el
cuerpo de un método.

### 1. El hueco de T46, cerrado — y por qué no se había podido antes

Quedó abierto que la comprobación de comportamiento nueva **no se había visto fallar**. Cerrado,
y el obstáculo no era el que parecía:

```
=== CON la mutación mínima ===   antes=0 despues=1  ->  FALLA <- el buscador creo filas
=== SIN ella ===                 antes=0 despues=0  ->  PASA
```

Segundos, no minutos. **Las dos ejecuciones anteriores no se iban de tiempo porque la
comprobación fuera lenta: se iban porque mi mutación provocaba una RECURSIÓN INFINITA.**
`createOTPData()` llama a `getOTPData()` para ser idempotente; hacer que el buscador llame al
creador cierra el ciclo.

> **Y eso es un aviso que vale por sí solo: el creador depende del buscador para no duplicar.
> El día que alguien vuelva a hacer que el buscador cree, no tendrá un defecto: tendrá un
> cuelgue.** La mutación correcta es una inserción EN LÍNEA, que es lo que hace la de arriba.

### 2. El clasificador de sobreescrituras de rutas — el agujero existe, en una dirección

`checkRouteOverrides` decide «¿este método decide algo?» leyendo su cuerpo, y ese registro se
va a usar en E3 para borrar. Probado con dos mutaciones sobre una sobreescritura registrada:

| Mutación | Veredicto | ¿Correcto? |
| :-- | :-- | :-- |
| **A** — el cuerpo **delega** en un ayudante privado que decide | «decide» | **Sí.** El cuerpo deja de parecerse al canónico, y ante la duda clasifica como que decide |
| **B** — el cuerpo queda **canónico** y la decisión se muda a un método que él llama | **«YA NO DECIDE NADA: Bórralo.»** | **NO. Y es el peligroso** |

**B es la dirección que hace daño**, porque el veredicto no es una duda: es un imperativo.
Seguirlo borraría una sobreescritura viva.

**El tapón**: un cuerpo inerte no significa un método inerte **si lo que llama está
sobreescrito en la misma clase**. Antes de declarar inerte a `allowedRoute`, se mira si la
clase declara también el `routeName` al que llama; si lo declara, la decisión puede estar ahí y
**no se toca**. Comprobado: con el tapón puesto, la mutación B deja de pedir el borrado, y
retirar una sobreescritura registrada se sigue detectando.

### 3. La regla, que sube a T21

> **UNA COMPROBACIÓN QUE MIRA EL CUERPO DE UN MÉTODO ES CIEGA A LO QUE ESE MÉTODO DELEGA.**
> Si el defecto que vigila **puede esconderse tras una llamada**, la comprobación tiene que
> EJECUTAR, no leer.

Y el matiz que hace la regla utilizable, porque no todas las comprobaciones de texto sobran:

| Pregunta que se hace la comprobación | ¿Basta leer? |
| :-- | :-- |
| «¿este código contiene X?» — una llamada deprecada, un `die($string)` | **Sí.** Lo que se busca ESTÁ en el texto o no está |
| «¿este código HACE X?» — escribe, decide, valida | **No.** Hacer algo se puede delegar; contener algo, no |

Las comprobaciones de `verify-integrity` que preguntan lo primero —deprecadas, docblocks sin
cerrar, PSR-4, eclipses, marcas del instrumental— **están bien como están**. Las dos que
preguntaban lo segundo eran justo las dos que fallaron.

## T48 · `missing-lang-messages`: escribe TAMBIÉN EN PRODUCCIÓN, y no es una curiosidad

**Tercer camino de lectura que escribe confirmado — y el primero que aparece porque había una
herramienta mirando, no por accidente.** Eso es exactamente lo que E2 venía a conseguir.

### La respuesta a la pregunta

**No está limitado a local.** `Config::__()` construye la ruta del archivo y escribe sin
ninguna guarda de entorno: no hay `is_local()`, ni constante, ni bandera. Comprobado leyendo la
función entera —líneas 560-680 de `src/app/core/Config.php`—: **cero apariciones** de
`is_local`, `isLocal` o `ENVIRONMENT`.

Es decir: **un despliegue en producción escribe en disco cuando sirve una página con una cadena
sin traducir**.

### Pero está acotado por dos cosas, y el reparto importa

| Acotación | Qué hace |
| :-- | :-- |
| **`no_scan_langs`** (`config/lang.php`) | Hoy excluye `es`, `fr`, `de`, `it`, `pt`. **Solo se escanea `en`** |
| **`if (!file_exists(...))`** | Cada mensaje se escribe **UNA vez**, no en cada petición |

**Así que no es «una escritura por página servida», que era lo que preocupaba.** Lo que sí hay
en cada página, en cualquier entorno, es **un `file_exists()` por cada llamada a `__()`** cuyo
idioma esté en la lista de escaneo — y una página del panel hace decenas.

Y hay un efecto acumulativo que sí es real: **el directorio crece y nadie lo vacía** salvo que
alguien corra `bin/cli scan-missing-lang`. En este árbol de desarrollo van **1.586 archivos en
61 grupos**.

### Lo que NO se ha tocado

Nada. Se pidió averiguarlo, no cambiarlo. Queda anotado con su medición para cuando se decida,
y con las tres salidas evidentes por si sirven: acotarlo a local, apagarlo con una constante de
módulo como el resto, o dejarlo y añadir la limpieza a `clean-all`.

> **Y lo que sí cambia hoy: entra en el registro de volatilidad declarada** con esta razón
> escrita, para que las 184 comparaciones del recorrido de E2 no se ensucien con él.

## T49 · AUDITORÍA DE INVERSOS — qué operaciones tienen vuelta y cuáles están sin estrenar

**La ley que la motiva está en T0 · LEY 9.** Aquí está la tabla que pidió el propietario, con
las dos preguntas por operación: **¿está documentado el inverso?** y **¿está probado el viaje
completo?**

*Método: inventario de las 15 tareas de `bin/cli help`, más las parejas del código
(`encrypt`/`decrypt` por clave con un analizador de argumentos, exportador contra importador,
`toggle2FA` en las dos direcciones, `SchemeCreator`).*

**La tabla tiene TRES estados, no dos.** Lo pidió el propietario después de la retractación de
T50, y distingue dos cosas muy distintas: un inverso que nadie ha probado, y **una pareja que
nunca existió**. Un inverso sin estrenar es deuda; una pareja inventada es un error de
diagnóstico nuestro, y merece decirse con otro nombre.

| Operación | Su inverso | ¿Documentado? | ¿Viaje completo probado? |
| :-- | :-- | :-- | :-- |
| **`db-backup`** | Restaurar (`mysql < archivo.sql`) | **Sí, desde hoy** — antes NO, y ahí estuvo el defecto | **Sí** — `db-backup-round-trip`, 5/5, validada rompiéndola |
| **`encrypt`** | `decrypt` | Parcial | **Sí, auditado por clave**: `$key`, `$this->password`, `self::TABLE`, `self::class` y la de por defecto **tienen todas su inverso**. La que no lo tenía era `'ENCRYPTION_KEY'`, y ya no existe |
| **`snapshot`** | `snapshot compare` | Sí | Sí |
| **Activar 2FA** (`toggle2FA(true)` + `confirm2FA`) | `toggle2FA(false)` | Parcial | **Sí, desde hoy** — la suite comprueba el EFECTO: que la cuenta deja de pedir código. Validada rompiéndola: con un inverso que devuelve `true` sin desactivar, fallan 2 de 27 (T50 · 4) |
| **Exportar usuarios** (`UsersExporter`) | — | — | **NO SON INVERSOS** (medido, T50 · 3). El exportador emite un informe para personas —ID, nombres, correo, colegio, grado, grupo— y **no incluye la columna `password`**. El importador tiene su propia plantilla. No hay viaje que romper |
| **`bundle`** | Desempaquetar / desplegar | No | **NO** |
| **Crear tablas** (`SchemeCreator`) | Borrarlas | **Sí, desde hoy** | **Sí** — `core/database/scheme-sql`, 9/9, con MariaDB de juez en las dos direcciones. **Pero la ida no se puede aplicar hoy en este proyecto**: 20 de 33 tablas la rechazan (T52) |
| **Activar módulo** (constante en `constants.php`) | Apagarlo | Sí (doc 04) | **Sí, desde hoy** — medido en las dos direcciones con la invalidación de caché dentro del arnés: 349 → 330 → 349 rutas, y `/admin/news/list/` 200 → 404 → 200 (T50 · 2, remedido en T51) |
| **Encolar** | `process-queue` | Parcial | **NO** |
| **`scan-missing-lang`** | Aplicar las traducciones | No | **NO** |
| **Emitir token** (`SessionToken::generateToken`) | Expirar / invalidar | No | **NO** — no hay método de invalidación explícita; la salida es `setMinimumDateCreated`, que invalida **todas** las sesiones a la vez |
| **`clean-*`** | — | — | Sin inverso por naturaleza |

### Lo que enseña el recuento

**De DIEZ operaciones con inverso real —una de las once resultó no ser pareja—, SEIS tienen
el viaje probado y CUATRO no.** Las seis lo están porque las hemos tocado esta campaña, no
porque el proyecto las cuidara.

> **Y una fila más para el backlog, sin tocarla**: `UsersExporter` emite las columnas
> **Colegio, Grado y Grupo**. Son campos de un proyecto concreto dentro de un framework que se
> clona: un despliegue que no sea un colegio recibe cabeceras sin sentido. No es un defecto de
> inversos, pero es de la misma familia que todo esto.

*(Recuento original, antes de las correcciones de hoy: de once operaciones, tres probadas y
ocho no. Se deja escrito porque la mejora se mide contra él.)*

**Los tres que más me preocupaban, y en qué quedaron:**

1. **`SchemeCreator` sin `DROP`.** **Cerrado** (T50 · 1), y la ida centralizada después
   (T52) — que además destapó por qué la regla 7 no se puede cumplir hoy.
2. **Exportar/importar usuarios.** **Retractado**: no son pareja. Detectado por leer y
   desmentido por medir, que es justo lo que decía la nota.
3. **Desactivar el 2FA.** **Cerrado** (T50 · 4): la suite comprueba el efecto y está validada
   rompiéndola.

**Los cuatro que quedan sin estrenar** —`bundle`, encolar, `scan-missing-lang` y la
invalidación de un token de sesión— **se quedan en backlog por decisión del propietario.** No
se tocan.

## T50 · LOS DOS INVERSOS QUE E3 NECESITA — construidos y medidos

De la tabla de T49, los dos que eran **requisito de E3** y no ampliación de alcance.

### 1. `SchemeCreator::dropScript()` — `piecesphp/database` v3.3.0

**El generador de esquemas solo existía hacia adelante.** La regla 7 dice que el SQL de las
tablas se genera y no se escribe a mano; deshacer un módulo obligaba a escribir a mano justo
eso. Y no solo aquí: **cada despliegue que actualice necesita ese SQL**, así que E3 tenía que
embarcar un script de migración que nadie podía generar.

**El orden sale del grafo que los mappers ya declaran**: `reference_table` en `$fields`. No hay
lista que mantener aparte — el dato ya estaba, solo que nadie lo leía en esa dirección.

**Y la propiedad que define la herramienta: EMITE, NO EJECUTA.** Va a despliegues ajenos; una
herramienta que borra tablas por su cuenta es lo contrario de lo que se está construyendo. El
script se genera, lo revisa una persona, y se aplica deliberadamente.

Probado con un esquema de juguete de tres niveles —nieta → hija → padre—, pasando los mappers
**desordenados a propósito**:

```
[OK] Cada mapper declara de quién depende, y se lee del propio $fields.
[OK] Orden correcto: nieta, hija, padre.
[OK] Generar el script NO borró nada: las tres siguen ahí.
[OK] El script se aplicó entero sin violar ninguna clave ajena.
[OK] No queda ninguna de las tres.
```

**Validado rompiéndolo** (T21): invertido el orden, **falla 3 de 6**, y una de las tres es
MariaDB devolviendo `Cannot delete or update a parent row`. La comprobación no cree al
generador: le pregunta a la base de datos.

Si hay un **ciclo** de claves ajenas no se inventa un orden: se emite igual y **se declara en
el propio script**, porque quien lo revise tiene que saber que ahí hay que decidir.

Del lado de la aplicación, `bin/cli scheme-drop module=<Nombre>` reúne los mappers del módulo y
emite el script. **Espera a que se publique v3.3.0**: hasta entonces avisa y sale con 1, en vez
de reventar.

### 2. Apagar un módulo — el viaje entero, y NO deja restos

Nunca se había probado, y E3 lo va a hacer varias veces con los módulos parciales. Medido con
`NEWS_MODULE`, que se queda:

| | Encendido | Apagado |
| :-- | --: | --: |
| Rutas del módulo en el inventario | **19** | **0** |
| `/admin/news/list/` | **200** | **404** |
| `/admin/` (el panel) | 200 | **200** |
| El menú menciona `news` | sí | **no** |

**Y la vuelta es exacta**: reencendido, el inventario devuelve **el mismo conjunto de rutas,
nombre por nombre** —346 contra 346, comparados como conjuntos y no como cifras—, y
`/admin/news/list/` vuelve a 200.

**No quedan restos**: ni ruta viva, ni entrada de menú, ni 500 en ninguna de las rutas
probadas. **Apagar un módulo es seguro**, y ahora está medido en vez de supuesto.

> **Dos avisos para quien repita esto, que me costaron a mí:**
>
> 1. **El inventario tiene ruido propio.** Dos rutas de prueba se nombran con `uniqid()`, así
>    que **cambian de nombre en cada ejecución** y aparecen como «desaparece una, aparece otra»
>    en cualquier comparación. Hay que descontarlas o el diff nunca sale limpio.
> 2. **OPCACHE, POR TERCERA VEZ.** La primera medición dijo que apagar el módulo **no retiraba
>    la ruta** —`/admin/news/list/` seguía dando 200—, y era la constante vieja en caché. Con
>    `touch` sobre `constants.php` y `routes.php` más una espera, el resultado se invirtió.
>    **Sabía la regla —la escribí yo en T20— y no la apliqué.** Una regla escrita no es una
>    regla aplicada, y esta ya va por la tercera vez.

### 3. Y una retractación: exportar/importar usuarios NO está roto

En T49 puse que el importador aplicaría `password_hash()` a un hash exportado. **Lo detecté
leyendo, y medido resulta falso en la parte que importa:**

**`UsersExporter` no exporta la columna `password`.** Cero apariciones en todo el archivo. Sus
columnas son ID, Documento, Nombre 1 y 2, Apellido 1 y 2, Usuario, Email, Colegio, Grado y
Grupo — **es un informe para personas, no un formato de intercambio**. El importador tiene su
propia plantilla y pide una contraseña **en claro**, que es coherente.

**No son un par inverso, así que no hay viaje que romper.** Lo que sí es cierto, medido, es lo
que pasaría si alguien metiera un hash en esa columna:

```
password_verify(clave, hash original)   = TRUE
password_verify(clave, hash rehasheado) = FALSE  <- no se entra
```

Pero eso es un error de quien rellena la hoja, no un defecto de la pareja de herramientas.

**Y el censo que pedía el propietario —¿hay una tercera herramienta que transforme `password`?—
sale limpio**: revisados todos los sitios que tocan la columna fuera del login, cada uno hace
una de tres cosas correctas: la hashea al fijar una contraseña nueva, la verifica al entrar, o
la `unset()` antes de exponer el usuario. La única que transformaba era `db-backup`, y ya no.

**Así que la regla que proponía el propietario se queda, pero con el conteo honesto: fue UNA
herramienta, no dos.** Y sigue valiendo la pena escribirla, porque es exactamente la que la
habría evitado.


## T51 · EL OPCACHE DEJA DE SER REGLA Y PASA A SER CÓDIGO

**La ley está en T0 · LEY 11.** Aquí está el mecanismo, y de paso la lección de método, porque
por poco escribo una retractación falsa.

### El instrumento que casi me hace retractarme de algo cierto

Al ir a construir el mecanismo, lo primero fue comprobar si opcache estaba encendido. Miré los
archivos de configuración:

```
ls /etc/php/8.5/fpm/conf.d/ | grep -i opcache   -> nada
grep -rn opcache /etc/php/8.5/                  -> solo la sección [opcache] comentada
```

**Conclusión aparente: opcache no está cargado, luego mis tres «me mordió el opcache» eran
falsos.** Estuve a un paso de escribirlo así. Lo que lo evitó fue preguntarle al binario que
sirve de verdad, en vez de a los archivos que describen lo que debería servir:

```
php-fpm8.5 -m | grep -i opcache   -> Zend OPcache
```

**Está compilado dentro del binario.** Los `.ini` no lo mencionan porque no hace falta cargarlo.
Leerlos daba la respuesta contraria — y **la contraria era la cómoda**: «no era opcache, era
otra cosa». Es T20 otra vez, y de la variante peor: el instrumento equivocado confirmaba lo que
me convenía creer.

### La ventana medida, con su número

| Ajuste (de `php-fpm8.5 -i`) | Valor |
| :-- | :-- |
| `opcache.enable` | On |
| `opcache.validate_timestamps` | On |
| `opcache.revalidate_freq` | **2 s** |
| `opcache.file_update_protection` | **2 s** |

De ahí sale la espera: **`max(revalidate_freq, file_update_protection) + 1` = 3 segundos desde
la última edición.** opcache no vuelve a mirar un archivo ya cacheado hasta que pasan
`revalidate_freq` segundos desde la última comprobación, y no cachea uno cuya `mtime` sea más
joven que `file_update_protection`. Tres segundos cubren las dos.

**No es folclore: es el número que declara el propio binario.** Y la trampa se reprodujo
**3 de 3**: petición → editar → petición inmediata devuelve el código VIEJO; sin la petición
previa dentro de la ventana, devuelve el nuevo. Eso explica exactamente por qué la primera
medición de apagar un módulo dijo que la ruta seguía ahí.

### El mecanismo

| Pieza | Qué hace |
| :-- | :-- |
| `bin/tools/live-cache.php` | Averigua qué PHP sirve una URL base —del `ServerName` de Apache, o de `PCSPHP_WEB_PHP`—, le pregunta los ajustes **al binario**, y espera lo que toca. **Aborta con código 1** si no puede averiguarlo, si `validate_timestamps` está en `Off` (ninguna espera invalida nada: hay que recargar FPM), o si se declara como editado un archivo que no existe |
| `bin/live-cache --report` | Enseña el SAPI, los ajustes y la ventana |
| `bin/live-cache --invalidate` | Invalida, diciendo cuánto espera y por qué |
| `bin/live-cache --self-test` | **Provoca la trampa y después la desactiva** |
| `bin/walk-routes` | Llama a la invalidación **al arrancar**. Nadie puede recorrer sin invalidar |

**La autoprueba es la pieza que importa**, porque una puerta vista solo en verde no se ha visto
funcionar. Comprueba las dos direcciones:

```
[PASÓ] la sonda responde y muestra lo que se acaba de escribir  (escrito ALFA, visto ALFA)
[PASÓ] SIN invalidar, la aplicación sirve el código VIEJO       (escrito BETA, visto ALFA)
[PASÓ] INVALIDANDO, la misma edición se ve                      (escrito BETA, visto BETA)
[PASÓ] la vista de sonda queda como estaba
```

**Y los tres caminos de aborto se provocaron uno a uno**: host que no resuelve a ningún vhost,
`PCSPHP_WEB_PHP=9.9` (binario inexistente) y `--file=/no/existe.php`. Los tres salen con 1 y
con el mismo mensaje: *«una comparación contra la web sin invalidar la caché MIENTE, y miente
en la dirección tranquilizadora»*.

### La medición de apagar un módulo, rehecha por el mecanismo

Las cifras de T50 · 2 se obtuvieron con un procedimiento a mano. Rehechas pasando por
`bin/live-cache --invalidate` entre la edición y la medición:

| | Encendido | Apagado | De vuelta |
| :-- | --: | --: | --: |
| Rutas en el inventario | 349 | **330** | 349 |
| `/admin/news/list/` (con sesión) | 200 | **404** | 200 |

**19 rutas, las mismas que antes.** El número aguanta; lo que cambia es que ahora no depende de
que alguien se acuerde.

> *(El inventario trae hoy 349 y no los 347 de la semana pasada: son las dos tareas nuevas,
> `snapshot` y `scheme-drop`, que registran su propia ruta. Cuadrado, no supuesto.)*

### La segunda regla convertida en mecanismo, el mismo día

Al commitear `bin/live-cache` salió que git lo había guardado como `100644` aunque el `chmod +x`
había funcionado. **El repositorio tiene `core.fileMode = false`**: el bit del disco no se
registra. El guion corre aquí y llega **sin permisos de ejecución a quien clone**. Otra regla
que solo se cumple si alguien se acuerda.

Es ahora la **comprobación 9 de `verify-integrity`**: todo archivo bajo `bin/` que empiece por
`#!` tiene que estar en el índice como `100755`.

**En su primera corrida encontró cuatro, todos anteriores a hoy:**

| Archivo | Estado |
| :-- | :-- |
| `bin/rector` | **Ni siquiera era ejecutable en el disco.** `bin/rector` devolvía «Permiso denegado», salida **126** — y está documentado en `CLAUDE.md` como una de las cuatro herramientas del proyecto |
| `bin/package-css` | Ejecutable en el disco, `100644` en git |
| `bin/pieces-completion.bash` | Ejecutable en el disco, `100644` en git |
| `bin/node/copyDependencies.sh` | Ejecutable en el disco, `100644` en git |

Los cuatro arreglados con `git update-index --chmod=+x`. **Y la puerta, validada rompiéndola**:
devuelto `bin/rector` a `100644`, la comprobación falla y lo nombra; restaurado, vuelve a verde.

**Esto es T21 otra vez**: una puerta nueva que en su primera corrida encuentra defectos reales
es una puerta que estaba haciendo falta. Una que nace en verde no ha demostrado nada.

### Lo que NO cubre, dicho aquí para que no se dé por cubierto

El recorredor de E2 invalida **una vez, al arrancar**. Es suficiente porque durante el
recorrido **no se edita código**: lo que cambia es la base de datos y el árbol de archivos, que
opcache no toca. Si algún día un recorrido edita código a mitad, tendrá que invalidar en cada
edición, y eso todavía no está.


## T52 · LA IDA, POR EL MISMO CAMINO QUE LA VUELTA — y lo que apareció debajo

El propietario lo señaló y tenía razón: con el `DROP` centralizado y por descubrimiento, y el
`CREATE` repartido en once literales `$showSQL`, **habíamos invertido la asimetría en vez de
cerrarla**. La regla 7 decía «el SQL sale de `SchemeCreator` y no se escribe a mano» cuando la
única forma de invocarla era **editar el código fuente** para poner un literal en `true`. Eso no
es una herramienta: es un interruptor escondido.

### La familia

| Pieza | Dónde | Qué |
| :-- | :-- | :-- |
| `SchemeCreator::resolveOrder()` | paquete, v3.4.0 | **Un solo resolvedor** del grafo de `reference_table`. Dos listas serían dos verdades |
| `SchemeCreator::createScript()` | paquete, v3.4.0 | Padres antes que hijas. **Emite, no ejecuta** |
| `SchemeCreator::dropScript()` | paquete, v3.3.0 | Hijas antes que padres. El mismo recorrido del revés |
| `Terminal\Tasks\SchemeSqlTask` | app | **El descubrimiento**, compartido. Lee `Mappers/`, `SubMappers/` y `ORM/` de todos los módulos, más `app/model` |
| `bin/cli scheme-create module=X\|all` | app | Emite el `CREATE` |
| `bin/cli scheme-drop module=X\|all` | app | Emite el `DROP` |

**Por descubrimiento las dos**, que es el patrón que ya funcionó cuatro veces: una lista escrita
a mano siempre se queda atrás. Y de hecho lo estaba: de los módulos con mappers, **solo once
tenían bloque `$showSQL`**.

> **Un defecto del framework encontrado por el camino, y arreglado**: `TerminalController`
> instancia por reflexión todo lo que encuentre en `Tasks/` con un método `route()`. Una clase
> **abstracta** cumple `method_exists()` y revienta `call_user_func`. Compartir código entre dos
> tareas **tumbaba la CLI entera**. Ahora se comprueba `isAbstract()`.

> **Y una cifra que mentía**: la tarea decía «34 tabla(s) en el script» contando *mappers*
> cuando el script traía 33 sentencias — dos mappers comparten tabla y el resolvedor los funde.
> Ahora cuenta las sentencias y **avisa** de la diferencia en vez de callarla.

### Lo que apareció debajo: la regla 7 NO SE PUEDE CUMPLIR HOY

Con la herramienta hecha, la primera pregunta era la obvia: ¿el esquema generado se aplica? Se
montó `unit-tests:core/scheme-sql-round-trip`, que crea una base de usar y tirar y **le pide a
MariaDB que aplique el script entero**.

**No se aplica. 20 de las 33 tablas son rechazadas.**

| Causa | Cuántas | Qué es |
| :-- | --: | :-- |
| `errno 150` — clave ajena incompatible | **19** | La columna declara `int` y la que referencia es `bigint` |
| `Unknown data type: 'test'` | **1** | `SystemApprovalsMapper` línea 56 declara `'type' => 'test'`. Es **'text' mal escrito** |

**Y la causa raíz, medida leyendo los mappers —segundo método independiente—: 38 claves ajenas
declaran un tipo distinto del de la columna que referencian**, repartidas en 19 archivos. Casi
todas son `createdBy` / `modifiedBy` apuntando a `pcsphp_users.id`, que es `bigint`.

**Los once `$showSQL` eran justo el parche de eso.** Todos hacen lo mismo:

```php
echo strReplaceTemplate(implode("\r\n", $sqlCreate), [
    'createdBy` int' => 'createdBy` bigint',
    'modifiedBy` int' => 'modifiedBy` bigint',
]);
```

Un reemplazo de cadenas sobre el SQL ya generado, módulo a módulo, que **arregla la salida y
deja el mapper mintiendo**. Y solo en once módulos: los demás —`Documents`, `Forms`,
`ImagesRepository`, `EventsLog`— no tienen parche ninguno.

### Por qué NO se han quitado los once `$showSQL` — RESUELTO en T58, y eran DIEZ

Era el premio del encargo, y **está bloqueado, no olvidado**. Hoy los once bloques producen SQL
**que sí se aplica** (por el parche) y la tarea central produce SQL **que no**. Quitarlos ahora
sería un retroceso.

**El orden correcto es al revés: primero arreglar las 38 declaraciones, después quitar los
bloques.** Y eso son 19 archivos, así que se enseña antes de tocarlo — es la regla de escala del
propietario.

> **AUDITADO (T54 · 3): las 38 son arreglo de papel. CERO migraciones.** El propietario
> sospechaba que el esquema real estuviera inconsistente entre tablas, porque once módulos
> parcheaban y cuatro no. **No lo está**: las 38 columnas ya son `bigint(20)` en la base.

**La comprobación previa que pedía el propietario, hecha**: nadie depende de que el volcado
salga por la página web. `$showSQL` no se lee de configuración, ni de la petición, ni de una
variable de entorno: **son once literales en `false`**, y los únicos consumidores son tres
documentos (`11-base-de-datos.md`, `07-modulos.md`, `13-recetas.md`) que describen el
procedimiento manual. Cuando los bloques se vayan, esos tres se corrigen en el mismo commit.

### Dos cosas más que salieron del mismo tirón, sin tocar

1. **`SchemeCreator` genera `CHARSET=utf8 COLLATE=utf8_bin`, escrito a fuego** (línea 198 del
   paquete). Es `utf8mb3`. **Esto matiza lo que dijimos en T37**: la configuración pone la
   *conexión* en `utf8mb4`, pero **el DDL que el framework genera crea tablas `utf8mb3`**, así
   que una tabla recién generada no admite emojis por mucho que la conexión sí. No se toca:
   cambiarlo afecta al DDL de todos los despliegues.
2. **`login_attempts` y `time_on_platform` usan `snake_case`** (`user_id`, `username_attempt`)
   contra la convención `camelCase` del proyecto. Backlog.

### La suite queda EN ROJO, y es a propósito

`unit-tests:core/scheme-sql-round-trip` marca **5 de 8**. Las tres que fallan son las tres que
describen el defecto real. **No se declara como ruido**: la regla del registro de volatilidad
dice que si al escribir la razón resulta que no la hay, es un defecto y va arreglado, no
declarado. Se queda roja hasta que el propietario decida sobre las 38 declaraciones.


## T53 · LAS DOS RUTAS CON NOMBRE `uniqid()` — NO ES RUIDO, ES UN DEFECTO

El propietario pidió declararlas en el registro de volatilidad **y mirar si tienen arreglo**.
Miradas: **tienen arreglo, así que no se declaran.** La regla 2 de `files/dev/volatile-state.json`
lo dice: *«una entrada legítima describe algo que el sistema hace A PROPÓSITO; si al escribir la
razón resulta que no la hay, es un defecto y va arreglado, no declarado»*. **No se ha tocado
nada**, según lo pedido.

### Qué son

`src/app/core/system-controllers/Test.php`, líneas 238-239:

```php
new PiecesRoute('queue-request[/]',        TestQueueRequest::class . ':form',   uniqid(TestQueueRequest::class), 'GET',  false),
new PiecesRoute('queue-request/handle[/]', TestQueueRequest::class . ':handle', uniqid(TestQueueRequest::class), 'POST', false),
```

El nombre cambia en cada arranque: `…TestQueueRequest6a8a731201624`.

### Por qué es un defecto y no una curiosidad

En este framework **el nombre de la ruta *es* el identificador de permiso** (regla 2) y **es la
forma de generar su URL** (regla 3). Un nombre que cambia en cada arranque no sirve para ninguna
de las dos cosas:

1. **No se puede referenciar.** `get_route()` y `Controller::routeName()` buscan por nombre.
2. **No se le puede asignar un rol**, porque `config/roles.php` autoriza por nombre. Hoy está
   tapado porque las dos van con `requireLogin = false`.
3. **Y ya obligó a saltarse la regla 3, dos líneas más allá.** La vista escribe la URL a mano:

   ```php
   <form action="./pcsphp-testing/queue-request/handle" method="POST" …>
   ```

   Esa concatenación no es descuido: **es la consecuencia forzosa** de que el nombre no se pueda
   usar. Es la prueba de que el defecto ya costó algo.

### Y hay una segunda cosa, PERO NO ES LA MISMA — corregido

> **CORRECCIÓN, señalada por el propietario.** Escribí esto mezclando dos cosas, y mezclarlas
> infla el problema: **confundir un defecto activo con uno dormido.**

`RouteAdapter::__construct()`, línea 90:

```php
$this->name($name == null ? uniqid() : $name);
```

El framework asigna `uniqid()` a cualquier ruta registrada **sin nombre**. Pero el `uniqid()` de
`Test.php:238-239` **estaba escrito a mano** como tercer argumento: no era esta línea
disparándose. Son dos cosas distintas.

**Medido: NINGUNA ruta del proyecto deja el nombre en `null`.** Este valor por defecto es una
**trampa latente con cero usuarios**. Se anota y **no se toca**, por decisión del propietario.

### Además, y no es de nombres

Las dos rutas se registran **también en producción**. `config/routes.php` línea 125 solo las
guarda tras `requestIsSameDomain()`, no tras `is_local()`. Son públicas y sin login. Se dice
aquí porque salió de la misma lectura; **no se ha tocado**.

### Consecuencia práctica mientras no se arregle

El ruido **no** contamina `bin/cli snapshot` —esa foto mira la base y `src/`, no el inventario—
pero **sí** cualquier comparación de inventarios de rutas, que es lo que E3 va a hacer seis
veces. Dos diferencias falsas por lote enseñan a ignorar las verdaderas.

> **RESUELTO en T55.** El propietario decidió: son sus vistas para probar colas y `FreezeRequest`,
> así que **solo en local**. Y entonces la ocultación por `uniqid()` sobra.


## T54 · EL VOCABULARIO DE TIPOS VIVÍA EN SEIS SITIOS — ASÍ SOBREVIVIÓ `'test'`

Lo señaló el propietario antes de dejarme tocar las 38 declaraciones: *no basta con corregir el
mapper, el ORM tiene que soportarlo*. La respuesta a eso era buena — `bigint` está en las dos
listas que hacen falta— **pero al medirlo apareció lo de debajo**.

### 1 · La tabla de las seis listas

*Método: `$supportedTypes`, `$supportedTypesComments` y `$typeEquivalences` por reflexión sobre
las propiedades; `MetaProperty::TYPES` por constante. Las de `validateType()` y
`castPHPToSQLTypes()` **son variables locales dentro del método**, así que no hay más remedio que
sacarlas del código fuente — es el punto flojo de la puerta y queda dicho.*

| Tipo | A · `$supportedTypes` | B · `$supportedTypesComments` | C · `validateType()` | D · `castPHPToSQLTypes()` | E · `SchemeCreator` | F · `MetaProperty` |
| :-- | :-: | :-: | :-: | :-: | :-: | :-: |
| `varchar` | X | X | X | X | X | · |
| `text` | X | X | X | X | X | X |
| `mediumtext` | X | · | X | X | X | · |
| `longtext` | X | · | X | X | X | · |
| `int` | X | X | X | X | X | X |
| `bigint` | X | X | X | X | X | · |
| `float` | X | X | X | X | X | · |
| `double` | X | X | X | X | X | X |
| `json` | X | X | X | X | X | X |
| `datetime` | X | X | X | X | X | · |
| `date` | X | X | X | X | X | X |
| `serialized_object` | X | · | X | X | X | · |
| `string` `number` `array` `mixed` `bool` `null` | · | · | **X** | · | · | parcial |
| `mapper` `array_mapper` | · | · | · | · | · | **X** |
| | **12** | **9** | **18** | **12** | **12** | **9** |

### 2 · Qué diferencia es legítima y cuál es deriva

| Comparación | Veredicto | Por qué |
| :-- | :-- | :-- |
| **A = D = E** | **Ninguna diferencia — y ese es el problema** | Tres listas, dos repositorios, **diciendo exactamente lo mismo**. No divergen hoy; nada impide que diverjan mañana |
| **B ⊂ A**, faltan `mediumtext`, `longtext`, `serialized_object` | **DERIVA** | La documentación del campo `type` remite explícitamente a `supportedTypesComments`: quien la lea **no se entera de que esos tres existen** |
| **C ⊃ A**, sobran `string number array mixed bool null` | **LEGÍTIMA, pero no estaba dicha** | `validateType()` valida también la **estructura de `$fields`**, cuyos valores se declaran con tipos de PHP (`'mapper' => ['string','null']`). Es otro dominio dentro del mismo método |
| **F aparte** | **LEGÍTIMA** | `MetaProperty` vive **dentro de una columna JSON**: no necesita anchuras de SQL —no hay `bigint` en JSON— y sí necesita `mapper` y `array_mapper`, que SQL no tiene |

**La puerta**: `core/database/type-vocabulary`, 8 comprobaciones. Exige que A, D y E digan lo
mismo; que el hueco de B **no crezca** más allá de los tres conocidos; que los extras de C sean
**exactamente** los seis de configuración; y que el dominio de F no cambie. **Validada
rompiéndola**: añadido `tinyint` a una sola lista, fallan 3 de 8.

### 3 · Qué hace cada capa con un tipo desconocido — MEDIDO, y es peor que «lo ignora»

| Capa | Qué hace |
| :-- | :-- |
| `EntityMapper::validateType()` | **Devuelve `true` PARA TODO** — cadenas, enteros, arrays, objetos, `null`, booleanos. El control (`'int'` con «no soy un número») sí devuelve `false` |
| `EntityMapper::castPHPToSQLTypes()` | **No convierte**: el valor sale tal como entró. Un array sigue siendo un array |
| `SchemeCreator` | **Lo copia tal cual al DDL**: emitía `` `reason` test `` |
| `MetaProperty` | **LANZA.** La única capa que lo rechaza |

**No es que se ignore en silencio: es que el campo deja de validarse.** `reason` de
`SystemApprovalsMapper` llevaba años aceptando cualquier cosa.

> Y la guarda que el ORM ya tiene, `$onlySupportedTypes`, **no la activa ningún mapper del
> proyecto** —cero apariciones fuera del núcleo—, y además **solo salta al ASIGNAR un valor**,
> no al construir el mapper. Medido en las dos direcciones.

**La letra, corregida**: `'test'` → `'text'`. Comprobado antes de tocarla que la columna real es
`text NULL`, así que el mapper pasa a decir la verdad y no hace falta migrar nada.

**La puerta que impide el siguiente**: comprobación **10** de `verify-integrity`. Todo
`'type' => '…'` declarado en un `$fields` tiene que estar en el vocabulario. **370 tipos
comprobados**, uno cazado. Se lee del código, sin instanciar: instanciar un mapper abre conexión.

> **Lo que NO se ha hecho, y es decisión del propietario**: que un tipo desconocido sea un
> **error duro** en el ORM. La medición apoya que lo sea —hoy el efecto es «no se valida nada»—
> pero cambiaría el comportamiento de cualquier despliegue que use un tipo propio a sabiendas.
> La puerta estática cubre el caso real sin romper a nadie.

### 4 · La auditoría de las 38: TODAS son arreglo de papel

*Método: `information_schema.COLUMNS` de la base de desarrollo contra el `$fields` de cada
mapper, columna a columna, comparando también contra la columna referenciada.*

| Reparto | Cuántas |
| :-- | --: |
| La columna real **YA es `bigint(20)`** → el mapper miente, arreglo de papel | **38** |
| La columna real es `int` → exigiría `ALTER TABLE` | **0** |
| La tabla no existe en esta base | **0** |

**El propietario sospechaba inconsistencia entre tablas. No la hay.** El esquema real es
uniformemente `bigint(20)`; son los mappers los que están uniformemente mal.

**Segundo método independiente, y coincide exactamente**: `databases/piecesphp_structure.sql`
—el archivo que se aplica en cada despliegue nuevo— declara `bigint(20)` en las 38:
17 `createdBy` + 15 `modifiedBy` + 2 `user_id` + `user` + `readerUser` + `author` + `approvalBy`
= **38**.

**Y hay un tercer argumento, este por construcción**: una tabla con la clave ajena en `int`
apuntando a un `bigint` **no se puede crear** —es el `errno 150`—, así que ningún despliegue
puede tener esas columnas en `int` *con la clave ajena puesta*. La rama de migración no está
vacía por suerte: está vacía porque no puede llenarse.

**Plan para los 19 archivos, pendiente de tu visto bueno:** cambiar `'type' => 'int'` por
`'type' => 'bigint'` en las 38 declaraciones marcadas, sin tocar nada más; correr
`scheme-sql-round-trip`, que debe pasar de 5/8 a 8/8; y **solo entonces** quitar los once
`$showSQL` y corregir los tres documentos que describen el procedimiento manual.

### 5 · Y la tercera pata de T37, cerrada

`SchemeCreator` emitía `CHARSET=utf8 COLLATE=utf8_bin` **escrito a fuego**. Arreglado en
**v3.5.0**, con el juego configurable por constructor o por defecto global.

**Las tres patas eran independientes y arreglar una no arreglaba las otras** —conexión (v3.2.1),
valor por defecto de la base (documentado), DDL generado (v3.5.0)—. **Es la LEY 8 en su forma
más cara**: la decisión «esto va en utf8mb4» vivía en tres archivos distintos y ninguno sabía de
los otros dos.

> Y al tocarlo salieron **dos defectos acoplados**: `typesCollations` es una LISTA consultada con
> `array_key_exists()`, que compara contra los índices `0..3` — o sea, **siempre `false`**, código
> muerto. Y al revivir esa rama aparecía que la variante para columnas **nulables** emitía
> `COLLATE … NOT NULL`. El segundo defecto solo era inofensivo porque el primero lo tapaba.


## T55 · LAS RUTAS DE PRUEBA, SOLO EN LOCAL — y la regla 3 deja de estar rota

Decidido por el propietario: son sus vistas para probar colas y `FreezeRequest`, conoce la URL, y
la aplicación no necesita llegar a ellas. **`is_local()` vale de sobra**: ya elige las
credenciales de base de datos en `config/database.php:25`, que es el uso de más peso que tiene.

**Y entonces la ocultación por `uniqid()` sobra.** Las dos rutas pasan a tener nombre normal
—`pcsphp-testing-queue-request` y `pcsphp-testing-queue-request-handle`— y la vista deja de
escribir la URL a mano:

```php
<form action="<?= get_route('pcsphp-testing-queue-request-handle'); ?>" …>
```

### Medido en las dos direcciones

| | Guarda normal (local) | Guarda invertida (simula NO-local) |
| :-- | --: | --: |
| Rutas en el inventario | **350** | **348** |
| `/pcsphp-testing/queue-request/` | **200** | **404** |
| `/img-gen/40/40/` | **200** | **200** |
| Nombres con ruido hexadecimal | **0** | 0 |

**`img-gen` no se toca, y se comprobó**: son dos grupos distintos en el mismo archivo, y el
generador de imágenes se queda con su guarda de `requestIsSameDomain()`, que evita hotlinking.

**El inventario de rutas queda estable**: cero nombres que cambien entre arranques. Era la
condición para que las seis comparaciones de E3 no arrastren dos diferencias falsas cada una.

### Dos bordes de `is_local()`, anotados y NO arreglados

`src/app/core/Utilities.php:599`:

```php
$host = $_SERVER['HTTP_HOST'];
$isLocal = $host === 'localhost' || mb_substr($host, -10) === '.localhost';
```

1. **`HTTP_HOST` incluye el puerto.** `85.localhost:8080` **no** termina en `.localhost`, así que
   `is_local()` devolvería `false` — y como `config/database.php:25` elige las credenciales con
   esta función, **la aplicación pediría las de producción**. No es hipotético: cualquier
   despliegue local en un puerto no estándar lo tiene.
2. **El dato lo manda el cliente.** `HTTP_HOST` es una cabecera. Para decidir «estoy en
   desarrollo» es aceptable; queda escrito qué se está apoyando en qué.

### Lo que se propone y no se hace: apagar el área pública con una constante

`PublicAreaController:443` tiene esto comentado:

```php
//self::$startSegmentRoutes = uniqid(); //Para ocultar este controlador
```

El motivo del propietario es legítimo —el home cambia por proyecto, algún despliegue no tendrá
área pública, y no quiere borrar código base— y **el código se queda**. Pero ese idioma
**desactiva algo como efecto secundario de ocultarlo**: quien lo lee no distingue «esto está
apagado» de «esto no tiene nombre». Y las rutas siguen existiendo y respondiendo: no está
apagado, está escondido.

**El framework ya tiene el mecanismo que lo dice bien, y es su propio patrón** (regla 8): una
constante de módulo en `config/constants.php`, leída como `const ENABLE = …` y envolviendo
`routes()`. Apaga de verdad, se ve en `constants.php` junto a las demás, y conserva todo el
código.

**Si prefieres el idioma actual**, que la línea lleve su guarda diciendo qué significa — hoy dice
«para ocultar este controlador» y lo que hace es dejarlo servido en una URL impronunciable.

### Anotado también, sin tocar

`Test::registerRoutes()` incluye **todos los archivos de `local-tests/`** en cada petición del
mismo dominio, también en producción. Registran acciones de CLI, así que en web no hacen nada —
pero se leen y se compilan en cada petición. Se dice aquí; **no se ha cambiado**, porque las
suites de `bin/cli` dependen de ese include y la decisión es tuya.


## T56 · E2(b) · LA TABLA DE QUÉ RUTAS DE LECTURA ESCRIBEN — ya no es una serie de accidentes

`bin/walk-routes` responde «¿revienta algo?». **`bin/walk-attribute` responde la otra pregunta**,
que es la que E3 necesita antes de borrar nada: **qué ruta de lectura escribe, y dónde.**

Los dos casos que conocíamos —el registro de intentos de acceso y los mensajes sin traducir—
aparecieron **por accidente, uno cada vez**. Esto los busca.

**Cómo**: foto de la base y del árbol antes, y otra después de **cada** petición. La diferencia se
le atribuye a la ruta que la provocó, no al recorrido entero. Una foto cuesta **0,39 s** con 36
tablas y 3.669 archivos, así que 184 rutas salen por poco más de un minuto de fotos.

**La invalidación de caché va dentro** y aborta si no puede: es una comparación contra la
aplicación viva, y es exactamente el caso que la LEY 11 vino a cubrir.

### El resultado

*Método: `bin/walk-attribute --base=… ` con sesión de root, 184 rutas GET pedidas, 166 omitidas
por exigir parámetros o por ser de escritura. Cada omisión se cuenta y se dice su razón.*

| Ruta | Qué toca | ¿Declarado? |
| :-- | :-- | :-- |
| `user-system-features-generate-otp` | `login_attempts` **+1 fila** | **Sí**, es el registro de auditoría |
| `news-category-admin-ajax-all` | `news_categories` **2 filas MODIFICADAS** | **NO** |
| `helpers-system-generic-content-admin-forms-home-image` | `pcsphp_app_config` **+1 fila** | **NO** |

**Las dos no declaradas escriben UNA SOLA VEZ**: repetida cada petición tres veces más, no vuelve
a haber diferencia. Es la misma forma que `missing-lang`: rellenan algo que faltaba.

### Y las causas, que son las de siempre

**1 · `NewsCategoryMapper::objectToMapper()` llama a `update()`.** Un **convertidor** —de objeto a
mapper— que escribe en la base para rellenar `preferSlug` si está vacío. Listar categorías
actualiza filas.

```php
if ($mapper->preferSlug === null && $mapper->name !== null) {
    $mapper->preferSlug = self::getEncryptIDForSlug($mapper->id);
    $mapper->update();     // <-- dentro de objectToMapper()
}
```

> **Y NO es un caso aislado: `objectToMapper()` llama a `update()` en 14 de los 21 mappers que
> lo implementan.** El recorrido solo cazó el de News porque en las demás tablas el campo ya
> estaba relleno **o la tabla está vacía**. Con datos de verdad, hasta catorce listados
> escribirían. Es la limitación de T39 mordiendo otra vez: **esta base no puede responder
> preguntas sobre datos**, y aquí eso significa que el recorrido SUBESTIMA.

**2 · `GenericContentPseudoMapper::__construct()` llama a `save()`.** Construir el objeto crea la
fila si no existe. **Es exactamente la forma del defecto D2 del 2FA**: `UserDataPackage` también
escribía al construirse.

```php
$this->orm = new AppConfigModel($this->contentName);
if ($this->orm->id === null) {
    $defaultDataSaved = false;
    $this->orm->name = $this->contentName;
    if ($setDefaultData) {
        //Do something          <-- rama vacía
    }
    if (!$defaultDataSaved) {   <-- $defaultDataSaved SIEMPRE es false
        $this->save();
    }
}
```

De paso, dos ramas muertas: `$setDefaultData` no hace nada y `$defaultDataSaved` nunca cambia.

### Lo que esto confirma y lo que no

**Confirma la regla que ya escribimos**: *la creación vive en los caminos de escritura, nunca en
los de lectura*. Los tres defectos encontrados hasta hoy —`UserDataPackage`, `getOTPData`, y estos
dos— **son el mismo**: un constructor o un convertidor que escribe.

**No lo declara nada de esto como volátil.** La regla 2 del registro dice que si al escribir la
razón resulta que no la hay, es un defecto y va arreglado, no declarado. **No se ha tocado
ninguno**: son material de E3.

**Y el aviso honesto que el propio informe imprime**: la atribución supone que nada más toca la
base durante el recorrido. Un cronjob o una petición ajena en paralelo se le colgarían a la ruta
equivocada. En esta máquina no había nada más corriendo; en otra habría que asegurarlo.

### Lo que salió de refilón y no es de este apartado

Del mismo recorrido: **`*-datatables` devuelve 500 en 18 rutas** y tres `ajax-all` también. Es
esperable —un endpoint de DataTables pedido sin sus parámetros no tiene por qué funcionar— pero
**no está comprobado que sea solo eso**, y `walk-routes` ya los venía listando. Queda anotado, sin
investigar.


## T57 · EL DESCUBRIMIENTO SE DEJABA MAPPERS FUERA — lo destapó medir por mi cuenta

El propietario pidió medir tres cosas antes de tocar las declaraciones y **no confirmar las
suyas**. Al hacerlo con un método propio, los dos métodos dieron números distintos, y la
discrepancia era un defecto en la herramienta que yo mismo había escrito.

| Método | Cuenta | Qué se le escapa |
| :-- | --: | :-- |
| Expresión regular sobre `src/app`, buscando `reference_table => UsersModel::TABLE` | **37** | `LoginAttemptsModel` y `TimeOnPlatformModel`, que escriben `'pcsphp_users'` **como literal** en vez de `UsersModel::TABLE` |
| Descubrimiento + reflexión (`SchemeSqlTask::discover`) | **38** | **`UserProfileMapper`**, y con él 3 declaraciones y la tabla `user_system_profile` entera |
| **Los dos, reconciliados** | **39 declaraciones en 21 archivos** | — |

**El defecto**: `discover()` buscaba mappers solo en carpetas llamadas `Mappers`, `SubMappers` u
`ORM`. `UserProfileMapper` vive suelto en `Profile/`. **La lista blanca se quedaba corta**, que es
el mismo fallo que la campaña lleva corrigiendo desde el principio.

> Y la herramienta **ya lo estaba diciendo** y yo no lo había leído: el plan imprimía
> *«`PreviousExperiencesMapper::profile` -> `user_system_profile` (tabla sin mapper
> descubierto)»*. El aviso estaba; faltaba mirarlo.

**Arreglado**: lista NEGRA en vez de blanca —`Views`, `Statics`, `lang`, `lang-public`,
`Exceptions`, `Controllers`—, podando el subárbol entero. Efecto medido:

| | Antes | Después |
| :-- | --: | --: |
| Mappers descubiertos | 34 | **35** |
| Tablas en el script de `scheme-create module=all` | 33 | **34** |
| Desajustes de tipo en claves ajenas | 38 | **41** |
| Tablas que MariaDB rechaza | 20 de 33 | **21 de 34** |

> **Dos afinados que hicieron falta al pasar de blanca a negra**, y los dos por medir:
> podar solo la carpeta `Views` seguía entrando en `Views/forms` y `Views/mailing` —hay que
> podar el subárbol—, y reportar como «descartado» todo archivo sin clase convertía un aviso
> honesto en **cien líneas de ruido**. Ahora solo se reporta lo que se llama como un mapper.

**La lección**: el 38 del propietario y el mío coincidían **por casualidad**. Dos métodos que dan
el mismo número no siempre miden lo mismo; aquí uno perdía 2 y el otro perdía 3, y la suma
cuadraba de casualidad en el punto medio.

### La condición que puso el propietario, y lo que midió

*«Las 20 que dejas fuera necesitan la misma prueba que las 39 que metes.»* El conjunto incluido
estaba reconciliado por tres métodos y el excluido por uno solo — **ese desequilibrio es
exactamente de donde salió `UserProfileMapper`**.

| Excluidas | Cómo se probó | Resultado |
| :-- | :-- | --: |
| **20 claves ajenas** cuya tabla referenciada se creía `int` | `information_schema.COLUMNS` sobre la **tabla y columna referenciadas** de cada una | **20 de 20 son `int(11)`. Cero conflictos** |
| **65 campos `int` que no son clave ajena** | `reference_table` es **null** en los 65 | Ninguno referencia nada |

> El detalle que casi me engaña en la segunda: `EntityMapper` **rellena todas las claves de
> configuración con su valor por defecto**, así que `array_keys()` las devuelve todas y parecía
> que los 65 tenían `mapper`. Lo tienen: vale `'\stdClass'`, que es el valor por defecto de
> `EntityMapper:72` y que el propio ORM trata como «sin mapper» en las líneas 702 y 725. Ningún
> mapper de la aplicación lo declara a mano. **La clave que decide es `reference_table`, y es
> null en los 65.**

### Aplicado: las tres puertas

| Puerta | Resultado |
| :-- | :-- |
| `scheme-sql-round-trip` | **8/8**, desde 5/8. El esquema entero se crea y se deshace desde los mappers |
| PHPStan | **859 exactas, sin reparto** |
| Inercia de datos | **Idéntico** en las 3 columnas con filas reales |

**La inercia, con su método**: se leyó una fila real **por el mapper**, no por SQL, antes y
después del cambio, comparando el valor y su tipo de PHP.

```
login_attempts.user_id             id=1  crudo=1  mapper=App\Model\UsersModel#1 (object)
pcsphp_users_otp_secrets.user      id=5  crudo=1  mapper=App\Model\UsersModel#1 (object)
system_approvals_elements.createdBy id=1 crudo=1  mapper=App\Model\UsersModel#1 (object)
```

De las seis candidatas, **tres no tenían ninguna fila con valor** —`news_elements.createdBy`,
`news_readed_relationship.readerUser` y `publications_elements.createdBy`— y se dice, en vez de
elegir tres que salieran bien.

> **Y un susto que resultó ser el instrumento, otra vez.** La primera corrida de PHPStan dio
> **863, +4 sobre el baseline**, y la regla decía parar. Los cuatro errores eran de
> `ZzTempInercia.php`, el archivo temporal con el que estaba **midiendo la inercia**: entraba en
> el barrido. Retirado el instrumento, 859 exactas. La regla funcionó: paré, miré, y no era el
> cambio.


## T58 · LOS `$showSQL` FUERA — y eran diez, y nunca contaron como deuda

Con las 39 declaraciones corregidas, el interruptor escondido sobra. Quitados **los diez
bloques** —13 archivos de imports huérfanos incluidos— y corregidos **cinco documentos**, la
regla 7 de `CLAUDE.md` y la skill.

### Dos correcciones de contabilidad, las dos mías

**Eran DIEZ, no once.** Lo dije mal desde T52 y lo repetí cinco veces. Medido con
`grep -c '\$showSQL = false;'`: **10 bloques en 10 archivos**. El «once» venía de contar mal una
tabla del propio documento, que ya decía diez.

**Y la corrección que importa: los diez NUNCA contaron como ramas muertas.** Esto es lo que
esperaba y lo que salió:

| Medición | Antes de quitarlos | Después |
| :-- | --: | --: |
| PHPStan (instancias) | 859 | **859** |
| Ramas muertas (tripletas) | 285 | **285** |
| `if.alwaysFalse` en el informe de ramas muertas | 9 | **9** |
| `if.alwaysFalse` en cualquier `*Routes.php` del baseline | **0** | 0 |

**Comprobado en las dos direcciones**: restaurados los diez bloques desde `HEAD` y medido otra
vez, salen los mismos 285 y los mismos 9. Y en el `PHPStanResult.json` de `HEAD` **no hay ni un
solo `if.alwaysFalse` en ningún `*Routes.php`**.

**La causa**: hay **DOS** entradas `if.alwaysFalse` en `bin/phpstan.neon`, y la que cubría los
diez está **FUERA del bloque de ramas muertas** —después del delimitador `── PHPDoc ──`—, así que
`bin/phpstan-deadcode` **no la retira** al medir. Los diez estaban silenciados en las dos
pasadas: ni aparecían en el baseline ni contaban como deuda.

> **Lo que esto invalida**: la fila del documento que decía *«10 de 20 son el `$showSQL`: NO SE
> TOCAN»* daba a entender que eran diez ramas muertas conocidas y toleradas. **No lo eran: eran
> diez ramas invisibles.** Una supresión colocada fuera del rango que mide la deuda no reduce la
> deuda: **la esconde del instrumento que la cuenta.**
>
> Y de ahí sale la comprobación que falta: **ninguna supresión debería poder vivir fuera del
> rango que mide `bin/phpstan-deadcode`.** Hoy nada lo impide. Queda anotado, sin arreglar.

### Lo que sí cambia

La entrada del `.neon` cubría **11** casos y ahora cubre **UNO**: `DataImportExportUtilityRoutes`,
que declara `const ENABLE = false;` literal. **Se estrecha del comodín `**/*Routes.php` a ese
archivo**, porque un comodín taparía cualquier rama muerta que apareciera mañana en otro módulo.

El baseline no se mueve —los diez nunca estuvieron en él— y **el trinquete lo confirmó**: al
intentar borrar la entrada entera saltó con *«se declaran 11 supresiones y el bloque de
ignoreErrors NO creció»*. La puerta hizo su trabajo.

### Lo adyacente que NO se ha tocado

Del mismo barrido salieron cuatro sitios más con la misma forma, y **no entran** porque el
encargo era los `$showSQL`:

| Dónde | Qué es |
| :-- | :-- |
| `Documents/DocumentsRoutes.php:50`, `Forms/DocumentTypes/DocumentTypesRoutes.php:40`, `Forms/Categories/CategoriesRoutes.php:41` | Un volcado de una línea **comentado**, con su `$sqlCreate` encima |
| `MySpace/Controllers/Util/ProfileTasksUtilities::generateSQL()` | **Es un método de verdad**, con parámetro `$echo` y valor de retorno. No es un interruptor escondido |

Los cuatro llevan un `strReplaceTemplate` de `int`→`bigint` que **ya no sustituye nada**, porque
los mappers declaran `bigint`. Es código inerte, no dañino. Anotado.


## T59 · EL APÓSTROFO SUELTO DE `UNSUSCRIBE_TEXT` — entra por ADYACENCIA, no por gravedad

**La justificación por escrito que exige T34**, y se escribe entera porque no se da por supuesta:

> **Entra por ADYACENCIA, no por gravedad.** No es grave: un `href` con un apóstrofo de más sigue
> funcionando en todo cliente de correo conocido. Entra porque **estaba delante**: apareció al
> renderizar la plantilla para medir la trampa de `public-unsubscribe` (C.2), en el mismo
> fragmento que había que mirar de todos modos, y el arreglo es **un carácter por archivo**. Si
> hubiera exigido abrir un archivo que no tocaba, o entender algo que no estaba ya entendido, no
> entraría.

**Y una corrección de mi propio informe**: dije «mismo archivo, un carácter». Son **seis
archivos** —los seis idiomas de `mailingGeneral`— con un carácter cada uno. La adyacencia se
mantiene; el conteo estaba mal.

```
ANTES : <a href='{{url}}'' target='_blank'>haga clic aquí</a>
AHORA : <a href='{{url}}' target='_blank'>haga clic aquí</a>
```

Sale hacia el usuario **en cada correo que manda el sistema**, en los seis idiomas.


## T60 · EL ÁREA PÚBLICA SE APAGA CON DOS INTERRUPTORES — y por qué la primera forma estaba mal

### Dos previsiones corregidas por la medición, que se quedan

1. **El comodín NO se come la raíz.** `{$startRoute}/{name}` y `{$startRoute}/{folder}/{name}`
   quedan en `/{name}` y `/{folder}/{name}`, pero `genericViews` devuelve **404** cuando no hay
   vista: `/zzz-no-existe/` 404, `/una/dos/` 404, `/publications/` 404, mientras `/` y `/contact/`
   dan 200.
2. **La trampa NO era `href=""`.** `routeName()` devuelve `''` solo cuando la ruta **existe** y el
   rol no la tiene. Con la ruta **sin registrar**, `$silentOnNotExists` vale `false` por defecto y
   las cinco plantillas la pedían sin él:

   ```
   RuntimeException — Named route does not exist for name: public-unsubscribe
   ```

   **Reventaba al renderizar el correo**, dentro de una cola o de un cronjob — el peor sitio para
   enterarse. No emitía un enlace vacío.
3. **`contact-forms-general` es un POST**, el destino del formulario de contacto. No aparece en
   ningún listado de vistas, así que apagarlo por accidente no se vería: el formulario seguiría
   pintándose y dejaría de enviar.

### La primera forma estaba mal, y el error de diseño fue del propietario

Se implementó y se deshizo (`git reset --hard`, sin empujar, sin commit de reversión). Era una
**lista** —`PUBLIC_AREA_ROUTES`— que alimentaba `$ignoreRoutes`. El defecto:

> **La enumeración de los cinco nombres vivía DOS VECES**: en `constants.php` y en el literal del
> `foreach` de `PublicAreaController`. Añadir una sexta ruta obligaba a tocar las dos, **y nada
> detectaba la divergencia.**

**Es T54 reintroducido por el arreglo de otra cosa, en la misma semana.** El vocabulario de tipos
vivía en seis sitios y por eso sobrevivió `'test'`; esto habría empezado igual, con dos.

**Se retiró también la comprobación 11**, que parecía cubrirlo y no cubría:

> Solo detectaba plantillas que llamaran **literalmente** a `PublicAreaController::routeName(`.
> Una plantilla que reciba `$unsuscriptionURL` de quien la invoca quedaba **invisible**. Verde no
> significaba seguro. Y es una lista blanca por patrón — el mismo fallo que acaba de costar
> `UserProfileMapper`.

### La forma buena: dos interruptores booleanos, y nada más

```php
//Apagarlo se lleva por delante el enlace de baja de los correos.
define('PUBLIC_AREA_VIEWS', true);
define('PUBLIC_AREA_CONTACT_FORMS', true);
```

- **Ninguna lista de rutas**, ni en `constants.php` ni en el controlador. No hay enumeración que
  pueda divergir de sí misma.
- **`$ignoreRoutes` vuelve exactamente a como estaba**: la lista manual con `-SAMPLE`. Sigue
  siendo el mecanismo para excepciones puntuales, y es del propietario. El interruptor ni la
  alimenta ni la sustituye.
- **`$startSegmentRoutes` no se toca**: es un PREFIJO, y `ContactFormsController` lo usa con
  `'contact'`.
- **La línea `//self::$startSegmentRoutes = uniqid();` se queda borrada**: con el interruptor, el
  escondite tras un segmento impronunciable deja de tener función.

### Medido, las cuatro combinaciones

| | Rutas | `public-*` | `contact-forms-*` |
| :-- | --: | --: | --: |
| Los dos encendidos (por defecto) | **350** | 5 | 1 |
| Vistas apagadas, contacto encendido | **345** | **0** | **1** |
| Los dos apagados | **344** | 0 | 0 |
| Restaurado | 350 | 5 | 1 |

**Por defecto no cambia nada**, que era la condición.

### Lo único que de verdad evitaba la excepción, y es independiente de las constantes

Las cinco plantillas piden la URL con **`$silentOnNotExists = true`** y **omiten el bloque entero**
cuando no hay URL:

```
CON la ruta : … por favor <a href='http://localhost/unsubscribe/NmE4…/' target='_blank'>haga clic aquí</a>
SIN la ruta : (el bloque de baja NO aparece)
```

**Ni enlace vacío ni excepción.** Y vale con cualquier constante puesta como sea, porque no
depende de ninguna: es la plantilla la que deja de suponer que la ruta existe.

### Lo que se queda como deuda, con condición de disparo

**El mensaje del ORM ante un tipo desconocido** (T54, B) nombra el campo y el tipo, pero **no la
clase dueña**. No se puede añadir hoy: `grep -rn "new Field("` sobre el paquete entero devuelve
**cero**, así que no hay ningún sitio donde el ORM construya campos.

> **Disparo**: cuando exista el primer mapper real sobre `ORM`, ahí habrá por fin un sitio donde
> se construyen campos, y ahí se añade la clase al mensaje. **No antes.**


## T61 · LOS 14 DE 21 — el inventario completo, sin corregir nada

La tabla de T56 era una **muestra**, no un inventario: el recorrido solo cazó `News`. Aquí está
la lista entera, y **no se ha tocado ningún mapper**.

*Método: por cada uno de los 21 mappers que implementan `objectToMapper()`, se extrae el cuerpo
del método por conteo de llaves y se busca `->update()` o `->save()` dentro. La condición se lee
del propio `if`.*

### Quién escribe, qué y cuándo

**Los 14 escriben LO MISMO**: `preferSlug`, cuando está a null y el campo que da nombre no lo
está. Uno solo cambia la condición.

| Mapper | Condición del `update()` |
| :-- | :-- |
| `ApplicationCallsMapper` | `preferSlug === null && title !== null` |
| `DocumentsMapper` | `preferSlug === null && documentName !== null` |
| `CategoriesMapper` (Forms) | `preferSlug === null` |
| `DocumentTypesMapper` (Forms) | `preferSlug === null` |
| `ImagesRepositoryMapper` | `preferSlug === null && id !== null` |
| `InterestResearchAreasMapper` | `preferSlug === null && areaName !== null` |
| `NewsCategoryMapper` | `preferSlug === null && name !== null` |
| `NewsMapper` | `preferSlug === null && newsTitle !== null` |
| `OrganizationMapper` | `preferSlug === null && name !== null` |
| `OrganizationPreviousExperiencesMapper` | `preferSlug === null && experienceName !== null` |
| `PreviousExperiencesMapper` | `preferSlug === null && experienceName !== null` |
| `UserProfileMapper` | `preferSlug === null && belongsTo !== null` |
| `PublicationCategoryMapper` | `preferSlug === null && name !== null` |
| `PublicationMapper` | `preferSlug === null && title !== null` |

**Los 7 que NO escriben**: `AttachmentApplicationCallsMapper`, `LogsMapper`, `NewsReadedMapper`,
`NewsletterSuscriberMapper`, `BuiltInBannerMapper`, `AttachmentPublicationMapper`,
`SystemApprovalsMapper`. Ninguno tiene columna de slug que rellenar.

### Cuáles se pueden comprobar hoy, y cuáles NO

*Método: filas de la tabla y filas con `preferSlug IS NULL`.*

| Veredicto | Cuántos | Cuáles |
| :-- | --: | :-- |
| **NO COMPROBABLE — la tabla está vacía** | **9** | `application_calls_elements`, `documents_elements`, `forms_categories`, `forms_document_types`, `image_repository_images`, `interest_research_area`, `news_elements`, `organization_previous_experiences`, `previous_experiences`, `publications_elements` |
| **No escribiría hoy** — ninguna fila con slug nulo | **5** | `news_categories` (2 filas), `organizations_elements` (1), `user_system_profile` (3), `publications_categories` (1) |

> **Los nueve vacíos NO se dan por limpios.** Es T39 en su forma más directa: esta base no puede
> responder preguntas sobre datos, y aquí eso significa que **nueve de los catorce caminos no se
> han ejercitado nunca**. Se dicen como no comprobados, no como correctos.

> **Y una vuelta que cierra el círculo**: `news_categories` sale hoy con **cero** slugs nulos. Los
> tenía. **Los rellenó nuestro propio recorrido de T56**, que fue quien cazó el caso. La prueba de
> que el camino escribe es que ya no hay nada que escribir.

### Lo que esto significa para E3

**Hoy, ninguno de los catorce escribiría** —nueve por tablas vacías, cinco porque el campo ya está
relleno—. En un despliegue con datos de verdad y filas antiguas sin slug, **listar cualquiera de
esas catorce entidades escribe en la base**. El recorrido de E2(b) **subestima por construcción**,
y esta tabla es la corrección.

**No se ha corregido ninguno**, según lo pedido.


## T62 · TRES MÓDULOS GENERABAN SU `CREATE TABLE` EN CADA PETICIÓN — y lo tiraban

`DocumentsRoutes`, `DocumentTypesRoutes` y `CategoriesRoutes` tenían el volcado comentado, sí,
**pero el `$sqlCreate` de encima NO**. Dentro de `routes()`, o sea en **cada petición**:
instanciaban el mapper, construían un `SchemeCreator` y generaban el DDL completo. El único
consumidor era el `//header(…)` de la línea siguiente.

Otros tres módulos —`Newsletter:45`, `ImagesRepository:46`, `EventsLog:43`— ya lo tenían en una
sola línea comentada.

> **CORREGIDO: los seis se BORRAN, no se comentan.** Aquí escribí que quedaban comentados y ese
> estado ya no existe. El propietario lo corrigió con una razón mejor que la mía:
> **`bin/cli scheme-create` ya genera el DDL recorriendo los mappers del módulo, y no se calla si
> uno no se puede instanciar.** El `$sqlCreate` en línea es una versión peor de una herramienta
> que ya existe, y código comentado que duplica una herramienta que funciona es residuo.
>
> Y salen **los seis**, no solo los tres que estaban vivos: es T17, la regla se aplica a toda la
> familia o a ninguna. Tratar distinto a los tres que ya estaban comentados habría sido conservar
> el mismo residuo por haber llegado antes.

### Lo que cuesta, medido

*Método: el contador `Questions` de la propia sesión de MariaDB, descontando el ruido de la
medición, y `microtime` alrededor de cada paso.*

| Módulo | `new Mapper()` | `SchemeCreator + getSQL()` | SQL generado y tirado |
| :-- | --: | --: | --: |
| `Documents` | 0 consultas · 0,132 ms | 0 consultas · 0,166 ms | 726 caracteres |
| `Forms\DocumentTypes` | 0 consultas · 0,145 ms | 0 consultas · 0,390 ms | 534 caracteres |
| `Forms\Categories` | 0 consultas · 0,137 ms | 0 consultas · 0,258 ms | 526 caracteres |
| **Total por petición** | | **0 consultas · 1,228 ms** | |

**CERO consultas. Es desperdicio, no defecto vivo**, así que no cambia de sitio en la escalera.

> **Con un matiz que hay que decir**: `ActiveRecordModel::configDb()` llama a
> `Database::instance()` si no hay conexión para esa clave, así que en una petición fría estas
> líneas **podrían ser las que abren la conexión**. Aquí no se ve porque la medición se hizo con
> el pool ya caliente.

### Y el A/B contra la aplicación viva, con su honestidad

| | Mediana de 30 peticiones |
| :-- | --: |
| **A** · con las tres líneas | 68,8 ms |
| **B** · sin ellas | **60,8 ms** |
| **A'** · con ellas otra vez | 65,7 ms |

**A y A' difieren en 3,1 ms entre sí**, así que la diferencia real está en el mismo orden que el
ruido de esta máquina. **La cifra sólida es la otra: 0 consultas y 1,228 ms de trabajo puro.**
Se deja escrito el A/B con su repetición precisamente porque, de haber medido solo A y B una vez,
la media decía 15,5 ms y habría sido una cifra inventada.


## T63 · EL ENDPOINT DE CRONJOBS — cuatro observaciones, verificadas y SIN ARREGLAR

Las señaló el propietario y **se comprueban antes de anotarlas**, no se copian.

### 4.1 · La llave se acepta también por parámetro GET

`APIController:1506-1508`:

```php
$cronJobKeyOnRequest = $request->getHeaderLine('Cron-Job-Key');
$cronJobKeyOnGet = $request->getQueryParam('Cron-Job-Key');
$cronJobKeyIsValid = $cronJobKeyOnRequest === $CronJobKey || $cronJobKeyOnGet === $CronJobKey;
```

Los parámetros de URL quedan en registros de acceso, en el historial del navegador y en el
`Referer`. **Hoy nadie enlaza esa URL: es latente.** Deja de serlo el día que un botón del panel
la enlace — que es justo lo que propone la IDEA 2 del registro de ideas.

### 4.2 · Dos variables que se calculan y no se usan

`APIController:1517-1518` calcula `$currentUser` y `$isLogged`. **Comprobado: no vuelven a
aparecer en el resto del método.**

### 4.3 · NO hay libro de ejecución

`CronJobTask::shouldExecute()` es esto entero:

```php
return (bool) call_user_func($this->executionCondition);
```

**Cero coincidencias** de `lock`, `lastRun`, `hasRun` o equivalente en todo el archivo. Sin
cerrojo y sin registro de «esto ya corrió», **dos disparadores en el mismo minuto ejecutan la
tarea DOS VECES**.

### 4.4 · El disparador HTTP solo equivale al CLI si se llama CADA MINUTO

`dailyAt()` compara la hora **exacta**:

```php
return $this->getEvalDate()->format('H:i') === $time;
```

Pinchado cada hora, una tarea con `dailyAt("03:00")` no se ejecuta casi nunca. Y no falla:
`execute()` devuelve `['success' => false, 'message' => 'Omitida…', 'skipped' => true]`.
**Silencio, no error.**

> **Las cuatro se quedan sin arreglar**, según lo pedido. La 4.3 es la pieza que comparten las
> dos ideas del registro: arreglada una vez, sirve a las dos.


## T64 · EL ACUÑADO DEL SLUG: atómico, visible y declarado

Queda establecido, y por eso el relleno **no se elimina**: existe para las filas que entran por
**IMPORTACIÓN** o por **ALTA DIRECTA EN BASE**, que no pasan por el alta de la aplicación y por
tanto nadie les pone slug. Y `getEncryptIDForSlug()` mete un `uniqid()`, así que el valor **no es
derivable del id**: hay que persistirlo.

### Los 21 no eran uniformes: dos formas

*Método: se extrae el cuerpo de `objectToMapper()` por conteo de llaves, se normalizan los nombres
de campo y se comparan las formas resultantes.*

| Forma | Cuántos | Condición |
| :-- | --: | :-- |
| **1** | **12** | `preferSlug === null && <nombre> !== null` |
| **2** | **2** | `preferSlug === null` — **sin la guarda del nombre** |

Se unifican en la 1. **La condición que puso el propietario, comprobada antes de tocar**:

```sql
SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('forms_categories','forms_document_types')
  AND COLUMN_NAME IN ('categoryName','documentTypeName')
```
```
forms_categories       categoryName      text   IS_NULLABLE=NO
forms_document_types   documentTypeName  text   IS_NULLABLE=NO
```

**Las dos son NOT NULL, así que unificar no cambia el comportamiento de esos dos módulos.**

### Los cuatro cambios

**3.1 · ATÓMICO.** El `UPDATE` va condicionado a que el slug siga nulo:

```php
$model->update(['preferSlug' => $slug])->where("id = {$id} AND preferSlug IS NULL")->execute();
```

> **Y un defecto que apareció al probarlo**: `execute()` devuelve lo que devuelve
> `PDO::execute()`, que es **`true` aunque no cambie ninguna fila**. La primera versión creía
> haber ganado siempre. **Quién ganó no lo dice el `UPDATE`: lo dice releer**, y eso es lo que
> hace ahora. La suite lo cazó en la primera corrida.

**3.2 · NO ESCONDIDO.** La escritura sale del cuerpo del convertidor a
`mintPreferSlugIfMissing()`, y el docblock de `objectToMapper()` lo declara con
*«ATENCIÓN: ESTE CONVERTIDOR ESCRIBE»*.

> **Vive en un TRAIT, no en `EntityMapperExtensible`.** En la clase base habría que anotar
> `@property $preferSlug`, que la mayoría de los mappers **no tiene** — escribir una mentira para
> callar al analizador. El trait solo lo usan los 14 que sí lo tienen, así que ahí la anotación es
> cierta. Lo dijo PHPStan: `Access to an undefined property EntityMapperExtensible::$preferSlug`.

**Y el campo de nombre lo declara cada mapper UNA vez**, en `SLUG_NAME_FIELD`, que leen los dos
caminos —el perezoso y el masivo—. Si cada uno lo supiera por su cuenta, serían dos verdades.

**3.3 · DECLARADO ANTES DE MEDIR.** Las 14 tablas entran en `files/dev/volatile-state.json` con su
motivo, **antes** de la próxima pasada del recorredor.

> **Y el hecho que lo hizo evidente**: `news_categories` sale hoy con **cero** slugs nulos. Los
> tenía. **Los rellenó nuestro propio recorrido de T56**, que fue quien cazó el caso. **El
> instrumento completó la migración que medía.**

> **Una segunda verdad, y queda dicha**: la lista de las 14 tablas del registro **no se escribió a
> mano** —sale de `PreferSlugsFiller::mappersWithSlug()`— pero **una vez copiada ahí, nada
> detecta si diverge**. Si alguien añade un módulo con `preferSlug`, la lista se queda corta en
> silencio. No hay puerta para eso.

**3.4 · LA TAREA EXPLÍCITA.** `Terminal\Jobs\PreferSlugsFiller`, registrada como `CronJobTask`
(«Rellenar slugs pendientes»). **No sustituye al perezoso: lo complementa** — el perezoso cubre la
fila suelta, la tarea cubre la importación entera. Y **usa exactamente el mismo método**, guarda
de nombre incluida: dos implementaciones del mismo acuñado serían dos verdades.

### La suite, y las dos direcciones provocadas

`bin/cli unit-tests:core/prefer-slug`, **12/12**. Validada rompiéndola por partida doble:

| Qué se rompe | Qué falla |
| :-- | :-- |
| Se quita `AND preferSlug IS NULL` | **2 de 12**: los dos acuñan (`A=true B=true`) y el del ganador ya no coincide con la base — **la URL repartida muere** |
| Se anula la guarda del nombre | **2 de 12**: una fila sin nombre recibe URL permanente |

> **Y una corrección de mi propia suite**: la primera versión daba **10/10 con la guarda del
> nombre rota**. No la cubría. Una comprobación que pasa con el defecto puesto no es una
> comprobación (T46), así que se añadió — con una subclase de solo-pruebas, porque `name` es
> NOT NULL y el mapper no deja ponerlo a null.


## T65 · `'dafault'` — 19 apariciones, efecto CERO, y la misma familia que `'test'`

**Anotado, NO arreglado.** Son 19 archivos: va al lote de E3 que corresponda.

### La medición

| | |
| :-- | --: |
| Apariciones de `'dafault' => null` | **19** |
| Archivos | **19** (una por mapper) |
| Que NO son `=> null` | **0** |
| **Efecto real** | **CERO** |

El efecto es cero porque los 19 son `=> null` y **el valor por defecto de `'default'` ya es
`null`** (`EntityMapper:63`, en `$defaultFieldConfig`). Escribir la opción bien o mal escrita da
exactamente el mismo resultado.

### Por qué nadie se enteró: la validación recorre lo CONOCIDO

`EntityMapper:990`:

```php
foreach ($options as $name => $types) {          // $options = $fieldOptionsStructure
    if (array_key_exists($name, $config)) {      // ¿está la opción CONOCIDA en la config?
```

Se itera **el catálogo**, no lo que el mapper escribió. Una clave que no esté en el catálogo
**no se examina jamás**: se ignora en silencio.

### La relación de familia

**Es el mismo mecanismo por el que sobrevivió `'type' => 'test'`** (T54): una estructura abierta
que acepta lo que sea. Allí el efecto era grave —el campo dejaba de validarse—; aquí es nulo. **Lo
que comparten no es el daño: es que nadie lo mira.**

### La pregunta que abre, sin responder

> Si las claves desconocidas se ignoran en silencio, **¿cuántas otras opciones mal escritas hay
> que parecen decir algo?** Una `'nulll' => true` o una `'lenght' => 50` se ignorarían igual, y
> esas sí cambiarían el comportamiento esperado por quien las escribió.

**Medirlo es barato**: comparar las claves usadas en todos los `$fields` contra
`fieldOptionsStructure`. Es exactamente la misma forma que la comprobación 10 —los tipos contra el
vocabulario—, cambiando el eje. **Pero es trabajo de E3**, no de ahora.


## T66 · E2(b), SEGUNDA PASADA — la tabla sale limpia, y hay que leerla con cuidado

*Método: `bin/walk-attribute` con sesión de root, 184 rutas GET pedidas, 166 omitidas por exigir
parámetros o por ser de escritura. Foto de la base y del árbol después de CADA petición.*

| Ruta | Qué toca | ¿Declarado? |
| :-- | :-- | :-- |
| `user-system-features-generate-otp` | `login_attempts` **+1 fila** | **Sí** |

**184 rutas pedidas, 1 escribe, 0 diferencias NO declaradas.**

### Lo que la tabla limpia NO significa

**`GenericContentPseudoMapper` sigue escribiendo al construirse. No se ha tocado.** Desapareció
del informe porque **su fila ya existe**: lo comprobé por su clave exacta —
`sha1(GenericContentPseudoMapper::class) . '|homeImage'`— y está ahí, id 24 de 24 filas de
`pcsphp_app_config`. **La creó nuestra propia pasada anterior.**

> **Segunda vez en el mismo apartado, y ahora con nombre**: el instrumento completa la migración
> que mide. Pasó con `news_categories` —los slugs nulos los rellenó el recorrido de T56— y ha
> vuelto a pasar con `pcsphp_app_config`. **Un recorrido de solo-lectura que dispara rellenos
> perezosos se agota a sí mismo: la segunda pasada siempre sale más limpia que la primera, y no
> porque se haya arreglado nada.**
>
> Consecuencia práctica para E3: **la primera pasada sobre una base recién restaurada es la única
> que ve estos casos.** Repetir el recorrido sobre la misma base no los encuentra.

### Lo que queda, y no es de este apartado

Del mismo recorrido, sin investigar: **18 rutas `*-datatables` devuelven 500** y tres `ajax-all`
también. Es esperable —un endpoint de DataTables pedido sin sus parámetros no tiene por qué
funcionar— pero **no está comprobado que sea solo eso**.


## T67 · QUÉ SIGNIFICA EL RETORNO DE `update()` — el censo, y el grupo defectuoso está VACÍO

**La semántica durable está en [`06-orm-mappers.md`](./06-orm-mappers.md)**, que es donde vive lo
que no caduca. Aquí queda el censo, que sí es de esta campaña.

### El conteo, y por qué difiere del tuyo

*Método: `grep` sobre `src/app` por `->save()`, `->update()` y `->delete()`, separando los que
descartan el retorno —la llamada es la sentencia entera— de los que lo usan.*

| Método | Llamadas | Descartan el retorno | **Lo usan** |
| :-- | --: | --: | --: |
| `save()` | 63 | 21 | **42** |
| `update()` | 66 | 22 | **44** |
| `delete()` | 2 | 2 | **0** |
| | **131** | **45** | **86** |

**Tu conteo era 58; el mío da 86 sobre los tres métodos, y 44 si se cuenta solo `update()` y
`delete()`, que son los únicos con la semántica engañosa.** `save()` sobre una fila nueva no
engaña: un `INSERT` que se ejecuta siempre inserta.

### La clasificación de los 44

| Grupo | Cuántos | Veredicto |
| :-- | --: | :-- |
| `$updated = $mapper->update();` + `setSuccessOnSingleOperation($updated)` — formularios de edición del panel | **21** | **Legítimo** |
| `$success = $success && …->update()` | **3** | **Legítimo** |
| Ternario `id !== null ? update() : save()` | **6** | **Legítimo** |
| El resto, leídos **uno a uno** | **14** | **Legítimos los 14** |

**El grupo defectuoso está vacío.** Ninguno de los 44 necesita saber «¿cambió una fila?»: todos
preguntan «¿se guardó lo que se pidió?», y a esa pregunta `true` es la respuesta correcta aunque
el usuario no cambiara nada — la fila contiene lo pedido.

**El único sitio del proyecto que sí necesitaba la otra semántica era el acuñado del slug**, y ahí
lo descubrimos y lo arreglamos releyendo (T64). **No hay familia que tratar.**

### La consecuencia que sí queda escrita

`BaseEntityMapper::update()` dispara `updated` cuando el retorno es `true` — o sea **también
cuando no cambió nada**. Y `SystemApprovalManager` **escucha ese evento**, así que **un guardado
sin cambios reevalúa la aprobación del elemento**. No es un defecto que se arregle aquí; es un
efecto que conviene conocer antes de tocar aprobaciones.

## T68 · `GenericContentPseudoMapper` — leído, no medido, y **no es lo mismo** que el slug

Ya no lo caza el recorrido: **su fila existe y la creó nuestra propia pasada** (LEY 12). Así que
esto sale de leer el código, no de medir.

### Qué escribe, y cuándo

`GenericContentPseudoMapper::__construct()`, líneas 119-141:

```php
$this->contentName = sha1(GenericContentPseudoMapper::class) . "|{$this->userSetContentName}";
$this->orm = new AppConfigModel($this->contentName);
if ($this->orm->id === null) {
    $defaultDataSaved = false;
    $this->orm->name = $this->contentName;
    if ($setDefaultData) {
        //Do something                 <-- rama VACÍA
    }
    if (!$defaultDataSaved) {          <-- SIEMPRE false
        $this->save();
    }
}
```

**Condición: que no exista la fila con esa clave.** La clave del caso que cazamos es
`sha1(GenericContentPseudoMapper::class) . '|homeImage'`, y en esta base es la fila **id 24 de 24**
de `pcsphp_app_config`.

**En una base vacía**, cada `new GenericContentPseudoMapper($nombre)` crearía su fila — tantas como
contenidos genéricos distintos se construyan, y **desde una petición GET**, porque el constructor
lo llama la vista del formulario.

### ¿Es la misma forma que el relleno de slugs? NO

| | Relleno de slugs | `GenericContentPseudoMapper` |
| :-- | :-- | :-- |
| Qué hace | **Completa** una fila que ya existe | **Crea** una fila que no existía |
| Por qué | El valor no es derivable: lleva `uniqid()` y hay que persistirlo | El valor **es** derivable: la clave sale de `sha1(clase) . '|' . nombre` |
| Si no se hiciera | La fila importada se queda sin URL pública | **Nada**: la fila se podría crear al guardar, que es cuando hay algo que guardar |
| Motivo escrito | Sí: importación y alta directa en base | **Ninguno que yo pueda leer** |

**Mi lectura: no es una migración perezosa con motivo, es una creación en un camino de lectura.**
Un formulario que se abre y no se envía deja una fila. La alternativa —crear al guardar— no pierde
nada, porque el objeto puede vivir en memoria hasta que haya algo que guardar.

**Y dos ramas muertas de propina**: `$setDefaultData` no hace nada y `$defaultDataSaved` nunca
cambia de valor.

**No lo he tocado.** De tu lectura depende si se declara o se arregla, y yo veo lo segundo.

### La tercera puerta: buscada, y no está

*Método: se extraen por conteo de llaves los cuerpos de todos los `__construct()` y de los
convertidores (`objectToMapper`, `arrayToMapper`, `fromArray`, `toMapper`) de `src/app`, y se
busca `->save()`, `->update()` o `->insert()` dentro.*

| | Encontrados |
| :-- | --: |
| Constructores que escriben | **2** |
| Convertidores que escriben | **0** (tras el arreglo de T64) |

Los dos constructores son `GenericContentPseudoMapper` —el de arriba— y
`SystemApprovalManager::__construct()`, que **no escribe**: registra escuchadores del evento
`updated` que escriben cuando el evento salta. Es un falso positivo del censo, y es el mismo
`updated` de T67.

> **Y una limitación del censo, dicha**: ahora que el acuñado del slug vive en
> `mintPreferSlugIfMissing()`, un `grep` por `->update()` dentro del convertidor **ya no lo ve**.
> El propio arreglo volvió ciego al instrumento que lo encontró. Si mañana se busca «convertidores
> que escriben», hay que buscar también las llamadas a métodos que escriben, no solo las
> escrituras literales.
>
> **Cómo se repite bien esa búsqueda**, para que no haya que redescubrirlo: primero se localizan
> los métodos que escriben —los que contienen `->save()`, `->update()` o `->insert()`—, y después
> se buscan **las llamadas a esos métodos** dentro de constructores y convertidores. Dos pasadas,
> no una. Con una sola pasada el censo dice cero y el cero es falso.

## PENDIENTE MEDIDO POR ARQUITECTO — 2026-08-24

*Entradas bajadas a archivo desde la conversación, donde llevaban vivas varias vueltas sin
existir en ningún sitio. **Autor: ARQUITECTO, no CODER.** Todo lo de aquí es LECTURA de código,
sin ejecutar nada: donde hay una afirmación fuerte lleva su `archivo:línea`, y donde hay una
hipótesis lo dice.*

> **Por qué esta sección existe.** Se contaron once acuerdos de una sola sesión que solo vivían
> en la conversación. La causa no era el olvido: era que bajar a archivo dependía de que un
> acuerdo viajara ARQUITECTO → PROPIETARIO → CODER, y ese recorrido tiene un punto donde se recorta.
> Desde ahora se escribe primero y se instruye después: si algo se pierde, que sea la
> instrucción, que se rehace, y no el acuerdo, que no.

### A · Las estrategias de serialización — seis hallazgos, ninguno tocado

**A1 · `addslashes`/`stripslashes` sobre valores que PDO ya liga.**
`ActiveRecord::insert()` arma marcadores `:INSERT<uniqid>_CAMPO` y `ActiveRecord.php:1109` hace
`$this->prepareStatement->execute($this->replacePrepareValues)`. `ActiveRecordModel extends
ActiveRecord`, así que **las escrituras de `EntityMapper` también ligan**. No queda interpolación
en el camino de ejecución — lo de `ActiveRecord.php:841` es el volcado de SQL para depurar.
Pese a eso, `EntityMapper::castPHPToSQLTypes()` (rama de texto) y `DataProcess::stringParse()`
aplican `stripslashes()` y después `addslashes()`. Consecuencias:

- Capa de escape duplicada sobre un valor que PDO va a escapar otra vez.
- **El `stripslashes` corre ANTES**, así que una barra invertida legítima se destruye al escribir:
  `Ruta C:\temp` se almacena como `Ruta C:temp`. El `stripslashes` de lectura hace que el
  ida-y-vuelta parezca correcto, y por eso lleva años invisible.

*Demostrado sobre una reproducción de las ramas citadas, no sobre la clase viva.* **Falta
comprobarlo contra una fila real**: insertar `C:\temp` y mirar la fila.

**A2 · `humanReadable()` aplica la conversión de ida y no la deshace.**
`EntityMapper.php:1192` llama a `castPHPToSQLTypes($type, $data[$field])` con `$revert = false`.
Se usa en la API pública (`APIController.php:220` y `:401`), en `UsersController.php:921` y en
`DataTablesHelper.php:443`. Un método llamado «legible por humanos» devuelve texto escapado para
SQL. `ORM::humanReadable()` (`ORM.php:479`) tiene el mismo origen: usa `getSQLValue()`.

**A3 · La lectura de `json` del ORM pierde datos, y la de `EntityMapper` no.**
`DataProcess::isValidJsonToCast()` contesta «¿esto se puede *codificar*?» y se usa para decidir si
*decodificar*. Para cualquier cadena dice que sí. Resultado en una columna `json`:

| Valor almacenado | `EntityMapper` devuelve | ORM devuelve |
| :-- | :-- | :-- |
| `'hola'` | `'hola'` | **`NULL`** |
| `''` | `''` | **`NULL`** |
| `'{"a":1}'` | objeto | objeto |

`EntityMapper` comprueba `json_last_error()` después de decodificar y devuelve el original si
falla; `DataProcess::jsonParse()` no. **Hay 35 columnas `json` declaradas en mappers reales de
`src/app`.** Si alguna guarda una cadena plana o vacía, migrarla al ORM la convierte en `NULL` en
silencio. El arreglo es una línea: el mismo guard que ya existe. **Esto tiene que decidirse ANTES
de que alguien migre una columna `json` al ORM.**

**A4 · `serialized_object` es incompatible en una dirección entre las dos implementaciones.**
`EntityMapper` escribe `{classname, serialized, isJsonSerialized}` y sabe usar la variante JSON
cuando la clase es `JsonSerializable` y tiene `instanceFromArray()` estático.
`DataProcess::serializedObjectParse()` escribe solo `{classname, serialized}` y al leer llama
`unserialize()` sin condición: no conoce la tercera clave. Una fila escrita por `EntityMapper` con
`isJsonSerialized = true` y leída por el ORM va a `unserialize()` sobre un JSON.
Hoy es inofensivo: **`serialized_object` tiene cero usuarios** en `src/app` — solo aparece en los
dos demos del paquete. Y `instanceFromArray` está definido una sola vez en todo el framework
(`RouteAdapter`), que nunca va a una columna. Decidir su suerte ahora es lo más barato que va a
ser nunca.

**A5 · `EntityMapper::jsonSerialize()` publica el esquema.** (`EntityMapper.php:1103`)
Devuelve los datos **más** `table`, `primaryKey`, `foreingsKeys` y la configuración completa de
`fields`. No se encontró ninguna llamada en `src/app` que lo mande a una respuesta: es latente, no
está sangrando. `ORM::jsonSerialize()` (`ORM.php:459`) es bastante mejor.

**A6 · `ORM::jsonSerialize()` devuelve valores PHP** (`getPHPValue()`), así que un `datetime` sale
como `{"date":"…","timezone_type":3,…}`, mientras `humanReadable()` devuelve la cadena. Dos formas
de salida para el mismo campo, y `json_encode()` elige automáticamente la incómoda.

**Lo que está bien, y conviene que también esté escrito**: la separación validar / convertir /
presentar; que `DataProcess` sea una clase con un solo trabajo y sin base de datos, frente a la
escalera de 200 líneas dentro de `EntityMapper`; que `serialized_object` guarde el `classname`
junto al dato; que `MetaProperty` guarde la clave foránea y no el objeto; y sobre todo que
`SQLTypesEnum::getType()` **lance** ante un tipo desconocido mientras `EntityMapper` devuelve el
valor sin tocarlo — el ORM ya había arreglado la clase de fallo por la que sobrevivió `'test'`.

### B · El paquete `database` no sabe evolucionar un esquema

Sabe **crear** (`createScript()`, v3.4.0) y ahora **destruir** (`dropScript()`, v3.3.0). No hay
`ALTER` ni migraciones de ningún tipo. Es exactamente lo que E3 y las correcciones de tipos
necesitaban, y por eso la única salida fue que la declaración siguiera a la realidad y no al
revés. Mientras no exista, **cualquier corrección de esquema en un despliegue ya desplegado es
manual**.

### C · Huecos de `database` como paquete público

Verificado: `require` no trae **ningún** paquete —solo PHP ≥8.4 y extensiones—, no usa un solo
ayudante del framework, y solo referencia espacios de nombres de SPL y del núcleo. **Ya es
autónomo.** Lo que le falta para ser un paquete en toda regla:

| Hueco | Estado |
| :-- | :-- |
| El prefijo `PiecesPHP\` es demasiado ancho para lo que ocupa | Y es la causa de que el núcleo lo eclipse por prefijo más largo |
| Ninguna superficie pública declarada, ningún `@internal` | Todo es público de hecho |
| Sin README | — |
| Sin CI | — |
| `phpunit` en `require-dev` pero las suites corren por su propio ejecutor | **Sin verificar** si hace falta |
| `ext-zip`, `ext-sqlite3`, `ext-mysqli` | **Sin verificar** si hacen falta |

### D · Sucesión del ORM — el camino, y por qué ese

El dato que ordena todo: **convertir un mapper no es un cambio al mapper, es un cambio a todos sus
llamantes.** Así que la pregunta decisiva no es si el ORM está listo, sino si `ExtensibleORM`
ofrece la misma superficie pública que `EntityMapperExtensible` — `getBy()`, `allBy()`,
`getByMultipleCriteries()`. **Esa medición no se ha hecho** (ver E).

Camino propuesto, y nada se rompe mientras tanto porque **hoy nada usa el ORM**:

1. **T22 primero** — mudar `MetaProperty` del paquete a `…\ORM\Meta\`. Hasta que eso pase, el ORM
   literalmente no se puede usar desde `piecesphp`.
2. **Construir UN mapper real nuevo sobre ORM**, no migrar uno existente. Sirve para aprender y
   para que salgan los huecos con un caso delante.
3. **Solo entonces** decidir sobre los 35 que tienen datos.

Y el riesgo de la migración no está en la superficie de llamada: está en el **ida-y-vuelta del
valor** (ver A1 y A3). Dos mappers con los mismos campos pueden devolver cosas distintas bajo
`EntityMapper` y bajo ORM. Eso se mide **sin base de datos**, que es lo mejor que tiene.

### E · Medición acordada y no hecha

Superficie pública de `ExtensibleORM` contra la de `EntityMapperExtensible`, método a método. Es
el dato que decide si la sucesión es un cambio de una palabra o un refactor de cada llamante.

### F · Los 19 `dafault` son el requisito previo de endurecer `$fields`

19 apariciones de `'dafault' => null` en 19 mappers. Efecto **cero**: el valor por defecto de
`'default'` ya es `null` (`EntityMapper.php:63`). La causa es que la validación recorre el
**catálogo** de opciones conocidas y pregunta `array_key_exists($name, $config)`, así que una clave
desconocida **no se examina jamás**. Misma familia que `'test'`.

**No es deuda cosmética aparcada**: el arreglo bueno —que una clave desconocida deje de ignorarse—
**no se puede hacer mientras existan los 19**, porque al endurecer la validación esos 19 mappers
empezarían a lanzar. Los dos van en el mismo lote, y por eso esta entrada existe: suelta, la
corrección de los 19 se aplaza para siempre; atada a lo que desbloquea, tiene una razón.

### G · `BaseEventDispatcher` no está documentado, y acaba de perder su ejemplo visible

El despachador tiene **dos APIs**, no una:

- `defaultListen`/`defaultDispatch` para tres eventos del framework: `EVENT_INIT_ROUTES`,
  `EVENT_ADD_DYNAMIC_TRANSLATIONS`, `EVENT_CLI_ROUTE_NOT_FOUND`.
- `listen`/`dispatch` con contexto, para eventos por clase. **`BaseEntityMapper` emite `saving`,
  `saved`, `updating` y `updated`** (`BaseEntityMapper.php:123, 126, 139, 142`) con la clase del
  mapper como contexto.

O sea que **los mappers ya emiten eventos de ciclo de vida**, y eso tiene un solo usuario en todo
el proyecto: `SystemApprovalManager.php:55`. Una capacidad real, a una supresión de quedarse
invisible, y sin documentar en ninguna parte.

Con el borrado de `QueueJobMapper::migrate()`, el único oyente que queda de `EVENT_INIT_ROUTES` es
el de `config.php:131`, que es un `//Do something` vacío. **El evento se queda sin ningún ejemplo
vivo.**

> **La regla que sale de aquí** (propuesta por el propietario): cuando una supresión se lleva el
> último ejemplo —o el más visible— de un mecanismo del framework, **el mecanismo se documenta en
> el MISMO commit**. La documentación por ejemplo es documentación, y borrarla es una pérdida
> aunque el código que se va sobre. Destino: `03-ciclo-de-vida.md`.

### H · `general.md` manda al operador a archivos que no existen

`source-docs/project/docs/piecesphp/content/general.md` dice `src/app/database.php` y
`src/app/constants.php` en tres sitios. Los archivos están en **`src/app/config/`**. Comprobado.
Y la lista del `rm -Rf` de ese mismo documento nombra `guides`, que **no existe** en el
repositorio: la lista ya se pudrió.


## T69 · `GenericContentPseudoMapper` — arreglado: leer ya no crea

**Decidido por el PROPIETARIO** sobre la lectura de T68: no era una migración perezosa con
motivo, era una creación en un camino de lectura.

### La medición que faltaba: qué dependía de que la fila existiera

**Nada.** Medido apartando la fila de un contenido real —`tokensUsed`— y devolviéndola idéntica
después:

```
apartada la fila de tokensUsed (id=22)
getContentData('tokensUsed') SIN fila devolvió: ['OpenAI' => 0, 'Mistral' => 0]
filas: antes=23  después=24   <-- LA LECTURA ESCRIBIÓ
restaurada: IDÉNTICA
```

**El valor por defecto vive en las propiedades de la clase, no en la fila.** Y `save()` ya
distingue los dos casos: inserta si `$this->orm->id === null`, actualiza si no. La fila no hacía
falta para leer, y para escribir se crea sola.

> **Un borde que apareció midiendo**: `getContentData()` con un nombre de contenido **que la clase
> no declara como propiedad** lanza `SafeException`, con fila o sin ella. La dependencia real del
> camino de lectura es la **declaración**, no el registro.

### Lo que se fue

| Qué | Por qué |
| :-- | :-- |
| `$this->save()` del constructor | Abrir un formulario y no enviarlo dejaba rastro |
| La rama `if ($setDefaultData) { //Do something }` | Vacía |
| `$defaultDataSaved`, siempre `false` | Una condición que nunca decide nada |
| **El parámetro `$setDefaultData` entero**, y sus 5 llamadas | Lo dejé «por compatibilidad de firma» y PHPStan lo cazó: `constructor.unusedParameter`. Un parámetro que no hace nada es exactamente lo que llevamos toda la campaña quitando |

### La suite, provocada en las dos direcciones

`bin/cli unit-tests:core/generic-content`, **6/6**. Repuesta la escritura en el constructor —de
forma fiel, después de asignar el nombre— **fallan 3 de 6**: construir deja fila, leer deja fila, y
guardar ya no la crea porque estaba.

**Y el panel comprobado**: las tres vistas de contenido genérico —`home-image`, `mapbox-keys`,
`tokens-limit`— responden **200** con sesión.


## T70 · EL EVENTO `updated` QUE SALTA SIN CAMBIOS — es DEFECTO, no ruido

> **CORREGIDO EN T72 por el PROPIETARIO**: reabrir un rechazo **al editar** es intención
> declarada y se respeta. El defecto es que «al editar» está implementado como «al guardar». El
> escuchador no se toca; lo que tiene que decir la verdad es el evento.

**Medido, no arreglado.** Decide el PROPIETARIO.

### 2.1 · Qué hace realmente el escuchador

`SystemApprovalManager.php:55-80`. Cuando salta `updated` sobre un mapper con aprobaciones:

```php
if ($approvalElement !== null && $approvalElement->status != SystemApprovalsMapper::STATUS_APPROVED) {
    $approvalElement->referenceAlias = $contentTypeName;
    //Si está rechazado pasa a pendiente al editar
    if ($approvalElement->status == SystemApprovalsMapper::STATUS_REJECTED) {
        $approvalElement->status = SystemApprovalsMapper::STATUS_PENDING;
    }
    $approvalElement->update();
}
$class::onUpdatedRecord($payload, $approvalElement);
```

**No reevalúa hasta el mismo estado: MUEVE la aprobación.**

| Estado de la aprobación | Qué le pasa con un guardado que no cambia nada |
| :-- | :-- |
| **APROBADO** | Nada. La guarda `!= STATUS_APPROVED` lo protege |
| **RECHAZADO** | **Pasa a PENDIENTE** y vuelve a la cola de revisión |
| **PENDIENTE** | Se reescribe el `referenceAlias` y se vuelve a guardar |
| *(cualquiera)* | `onUpdatedRecord()` corre igualmente |

**El caso concreto**: un elemento que un revisor rechazó. Alguien abre su formulario de edición y
pulsa guardar **sin tocar nada**. `update()` devuelve `true` porque la sentencia corrió, salta
`updated`, y **el rechazo se convierte en pendiente**. Nadie editó nada.

**Y el camino está vivo**: los cuatro manejadores declarados en `SystemApprovals/Util/configurations.php`
—Organizaciones, Usuarios, Publicaciones y Convocatorias— tienen `$APPROVALS_ALLOW = true`.

> **Es defecto, no ruido.** Ruido sería reevaluar y llegar al mismo sitio. Esto cambia un estado
> que una persona decidió.

### 2.2 · Cuántos escuchadores hay de los cuatro eventos

| Evento | Escuchadores |
| :-- | --: |
| `saving` | **0** |
| `saved` | **0** |
| `updating` | **0** |
| `updated` | **1** |

**Es ese y solo ese.** Los cuatro eventos existen, se emiten en cada `save()` y cada `update()` de
cualquier mapper, y **tres de los cuatro no los escucha nadie**.


## T71 · `bin/cli db-restore` — el inverso que faltaba, y el mecanismo de la LEY 12

`db-backup` existía como tarea; restaurar era `mysql < archivo.sql`: **a mano y sin registro**. Era
un inverso sin pareja de los de T49, y era lo que bloqueaba el mecanismo de la LEY 12.

```bash
bin/cli db-restore file=dumps/x.sql confirm=yes [database=<nombre>]
```

| Salvaguarda | Qué hace |
| :-- | :-- |
| `confirm=yes` **obligatorio** | Sin él no toca nada y sale con 1. **Destruye datos**: la confirmación no es ceremonia |
| Rol `TYPE_USER_ROOT` | El mismo que `db-backup` |
| El archivo tiene que existir | Y contener al menos una sentencia |
| Cada fallo se nombra | No se cuenta en silencio: sale la sentencia que falló |

**El rastro** queda en `files/dev/last-restore.json` —fecha, marca de tiempo, archivo de origen,
base destino y cuántas sentencias se aplicaron—. **No se versiona**: es de una máquina y un
momento.

### El recorredor lo exige — AVISO, no aborto

`bin/walk-attribute` compara ese rastro con la marca de su última pasada:

```
AVISO: no hay rastro de restauración (files/dev/last-restore.json). Esta pasada NO es una primera
       pasada, así que los rellenos perezosos que el propio recorrido ya disparó no volverán a
       verse. Ver LEY 12.
```

Y si la restauración es **anterior** a la última pasada, lo dice también. **Avisa y sigue**: el
operador puede tener razones —una foto de control, una repetición a propósito—, y aquí abortar
sería quitarle una herramienta por una regla que él conoce mejor que el guion.

### El viaje probado de verdad

`bin/cli unit-tests:core/db-restore`, **9/9**: se parte de un valor conocido, se cambia, se
restaura, y **se comprueba que volvió**. Más el rastro, con su archivo y su base.

**Validada rompiéndola dos veces**: quitada la guarda de confirmación falla 1 de 9 —restaura sin
que nadie lo confirme—; quitado el rastro fallan 3 de 9.

## T72 · CONTAR LAS FILAS CAMBIADAS DESDE DONDE SE DESPACHA `updated` — SE PUEDE, PERO NO AQUÍ

El diagnóstico de T70 queda **corregido por el PROPIETARIO, y la corrección es suya**: el
escuchador lleva escrito `//Si está rechazado pasa a pendiente al editar`. **Reabrir un rechazo al
editar es intención declarada y se respeta.** El defecto es que «al editar» está implementado como
«al guardar». No se toca el escuchador: se hace que el evento diga la verdad.

Antes de tocar nada, la medición de la que depende todo lo demás.

### La medición: ¿se puede saber cuántas filas cambiaron?

*Método: una tabla de usar y tirar en la base real, un UPDATE que no cambia nada y otro que sí,
sobre la MISMA fila, y dos maneras distintas de preguntar cuánto cambió. Se destruye al terminar.*

| Pregunta | UPDATE sin cambio real | UPDATE con cambio real |
| :-- | :-- | :-- |
| `PDOStatement::rowCount()` | **0** | **1** |
| `PDOStatement::rowCount()` después de `closeCursor()` | — | **1** |
| `SELECT ROW_COUNT()` | **0** | **1** |

**El dato es el que se necesitaba.** La fila del primer UPDATE **existía y casaba con el `WHERE`**
—lo demuestra el segundo, que sobre esa misma fila devolvió 1—, y aun así el conteo fue 0: la
conexión cuenta filas **cambiadas**, no coincidentes. Confirmado el punto de partida del
PROPIETARIO: `Database::DEFAULT_PDO_OPTIONS` solo trae `ATTR_PERSISTENT`, sin
`MYSQL_ATTR_FOUND_ROWS`.

**Aviso de método, por la REGLA MAYOR de T20**: las dos filas de arriba coinciden, y **eso no las
verifica entre sí**: `rowCount()` y `ROW_COUNT()` leen el mismo contador de MariaDB y se
equivocarían juntas. Lo que discrimina no es que coincidan, sino el diseño de la prueba: **la
misma fila, primero sin cambiar y después cambiando**. Ahí no cabe la confusión entre «no casó» y
«casó y no cambió».

*(De paso: `PDO::MYSQL_ATTR_FOUND_ROWS` está DEPRECADA en 8.5 y aquí las deprecaciones abortan. La
constante no hace falta para nada de esto, pero quien vaya a consultarla se lleva un aborto.)*

### Y aun así, desde el emisor no se alcanza

`BaseEntityMapper::update()` despacha después de `parent::update()`. Para entonces:

| Obstáculo | Comprobado |
| :-- | :-- |
| El `PDOStatement` ya no existe | `ActiveRecord::execute()` hace `$this->prepareStatement = null` **y** `resetAll()`. Medido por reflexión tras un `execute()` real: **NULL** |
| Quien podía contarlo es privado | `_executeUpdate()` está declarado `private` en `ORM/ActiveRecord.php:1120`, y es el único punto donde el statement sigue vivo |
| No hay costura del lado de la aplicación | `EntityMapper::__construct()` hace `$this->model = new ActiveRecordModel(...)` **con la clase del paquete escrita a mano**. `BaseModel` —que sí es nuestra— no participa: sobreescribir `prepare()` ahí no alcanza a ningún mapper |

**Conclusión: el conteo solo se puede capturar dentro del paquete `piecesphp/database`**, en
`_executeUpdate()`, guardándolo antes de devolver y exponiéndolo con un accesor nuevo. Sería un
cambio **aditivo** —ninguna firma existente cambia—, pero es la superficie pública del ORM.

### PARADA, y qué haría falta

**No se ha tocado el emisor.** Hay dos razones, y la segunda es la que manda:

1. **Toca la API pública del ORM**, que es el supuesto en que el encargo mandaba parar.
2. **Y no se podría probar hoy.** `src/vendor` tiene `piecesphp/database` **v3.2.1**;
   v3.3.0 a v3.6.0 ya están commiteadas y etiquetadas en su repositorio **esperando el push**. Un
   accesor nuevo llegaría en v3.7.0 y la aplicación **no lo vería** hasta `composer update`.
   Llamarlo sin guarda revienta cada `update()` de hoy; llamarlo con guarda deja el arreglo
   **inerte y dependiente de la versión instalada** —una trampa para el clon— y la suite de 1.3
   no podría estar verde.

**Los dos rodeos que NO se han tomado**, y por qué:

| Rodeo | Por qué no |
| :-- | :-- |
| `SELECT ROW_COUNT()` después de `parent::update()`, del lado de la aplicación | Funciona hoy y no toca el paquete, **pero es de MariaDB**: el paquete también soporta `sqlite`, donde no existe. Un clon sobre sqlite tendría un evento que miente sin que nada lo diga |
| Releer la fila antes de escribirla y comparar | Una consulta extra por cada `update()` de todo el sistema, para deducir lo que la base ya sabe |

**Lo que hace falta, en orden:**

1. **Push de `piecesphp/database`** con lo que ya está etiquetado (v3.3.0 → v3.6.0), que además
   desbloquea `scheme-create` y `scheme-sql-round-trip`, hoy autoguardados.
2. **v3.7.0**: `_executeUpdate()` guarda `rowCount()` antes de cerrar el cursor, y un accesor
   —`getLastAffectedRows()`— lo devuelve. `resetAll()` **no** lo borra: el valor pertenece a la
   última ejecución, no a la consulta en construcción.
3. **`composer update` aquí**, y entonces `BaseEntityMapper::update()` despacha `updated` solo si
   el conteo es mayor que cero, con su suite de tres casos.

### Lo que la medición de T70 no cubría, y hay que decir

`SystemApprovalManager` llama a `$class::onUpdatedRecord($payload, $approvalElement)` **fuera de la
guarda de estado**. O sea: un guardado sin cambios no solo movía el rechazo a pendiente — disparaba
**los efectos de cada manejador**. **El alcance real era mayor que «mueve rechazos»**, y sigue
abierto mientras el paso 3 no ocurra.

### Y los tres eventos sin escuchadores no se tocan

`saving`, `saved` y `updating` son **API del framework**: un clon puede engancharse a ellos. Que
hoy no los escuche nadie aquí no es motivo para quitarlos. Ya están documentados en
`03-ciclo-de-vida.md`, que era lo que de verdad faltaba.


## T73 · `$forbidden` — la primera de las cinco listas, cerrada

**Por qué esta y no la más grande.** El criterio no es el tamaño: es **qué cuesta que diverja**.
`bin/phpstan.neon` tiene más superficie, pero su divergencia produce ruido o una cifra corta. Esta
produce **una escritura silenciosa creyendo que solo se lee**: el recorredor pide una ruta de
escritura, la atribuye a una ruta de lectura, y deja inservible la foto de la que depende E3.
**Corrompe el dato y la medición a la vez.**

### El diff previo: no habían divergido — todavía

*Método: se extraen los literales del bloque `$forbidden` de los dos archivos y se comparan como
listas ordenadas.*

| | `bin/walk-routes` | `bin/walk-attribute` |
| :-- | :-- | :-- |
| Patrones | **17** | **17** |
| Mismo contenido y mismo orden | **Sí** | **Sí** |
| Bloque completo, comentarios incluidos | — | **No** |

**Corrección de una cifra mía**: en el barrido de la LEY 11 las llamé «14 patrones». Son **17**;
conté por líneas de agrupación en vez de por literales. Corregida también allí.

**Y el hallazgo, que no es «no había nada»**: los datos coincidían, pero **el comentario que
explica por qué se mira también la URL —el incidente de `/forms/add/`— solo estaba en una de las
dos copias.** *La razón había divergido antes que el dato.* Quien tocara `walk-attribute` no tenía
delante el motivo de la mitad de la lista.

### Dónde vive ahora

| Pieza | Qué es |
| :-- | :-- |
| `files/dev/forbidden-routes.json` | **Los datos.** JSON, legible por cualquier herramienta futura, en PHP o no. Con su `why` y sus reglas |
| `bin/tools/forbidden-routes.php` | **La función de comparación**, para que tampoco la regla de emparejado esté dos veces |
| Los dos recorredores | `require_once` y nada más. **Ninguno conserva copia** |

**Sin lista no se recorre**: si el JSON falta o no declara `patterns`, la función escribe en
`STDERR` y sale con 1. Seguir sin ella significaría pedirlo todo, escrituras incluidas.

### Que decide lo mismo que antes

*Método: se aplica la lista de `HEAD` y la nueva a las **351 rutas** del inventario real, y se
comparan los veredictos uno a uno.*

```
rutas evaluadas       : 351
vetadas               : 103
veredictos que CAMBIAN: 0
```

### La comprobación 12, provocada en tres direcciones

`verify-integrity` falla si alguien vuelve a copiarla:

| Se provoca | Qué dice |
| :-- | :-- |
| Reponer un `$forbidden = [...]` literal en un guion | «`bin/walk-attribute` vuelve a declarar la lista con `$forbidden = [`» |
| Quitar el `require_once` de un recorredor | «`bin/walk-routes` filtra rutas prohibidas sin cargar `bin/tools/forbidden-routes.php`: está decidiendo con una lista propia» |
| Borrar el JSON | «no existe `files/dev/forbidden-routes.json`: la comprobación no pudo mirar nada» |

Las tres salieron con **FALLOS: 1**. Con todo en su sitio, **verde con las doce**.

**Y el límite de este instrumento, dicho antes de que engañe a nadie**: la comprobación 12 **lee el
cuerpo de los archivos**, no cuenta nada contra el mundo. Reconoce la copia por su forma
—`$forbidden = [`— y el uso por el nombre `$isForbidden`. Quien reintroduzca la lista con otro
nombre de variable pasa por delante sin que salte. Es la misma clase de instrumento que la
comprobación 5 de las sobreescrituras, que ya lleva su aviso: **avisa, no demuestra.** Lo que sí
demuestra es la equivalencia de arriba, que se juzga contra las 351 rutas del inventario.

**Lo que NO se hizo, y a propósito**: no se ha corrido ningún recorrido contra el sitio vivo. Un
recorrido escribe en `login_attempts` y dispara rellenos perezosos, y la LEY 12 dice que eso
ensucia la base antes de la foto de E3. La equivalencia se probó sobre el inventario, que es el
mismo dato que consume el recorredor.

### Las otras cuatro, ordenadas por lo que cuesta que diverjan

**No se arregla ninguna. El orden lo aprueba el PROPIETARIO.**

| # | Lista | Qué cuesta que diverja |
| :-- | :-- | :-- |
| **1** | `bin/phpstan.neon` — 15 bloques `paths:` con 23 rutas | **Un archivo que no está en `paths:` no se analiza, y el total no baja: sale igual de verde.** Es la única de las cuatro que puede hacer que una cifra del trinquete signifique menos de lo que dice, y ya nos pasó: 34 de 195 archivos descartados en silencio (T20) |
| **2** | `shared-toolchain.json` — 4 paquetes, sus archivos y marcadores | **Un paquete que se sale del instrumental común deja de vigilarse.** La comprobación 7 aprueba en verde justo lo que dejó de mirar; la comprobación de eclipses nació de ese mismo error |
| **3** | `volatile-state.json` — las entradas que no son del slug | **Un volátil de más enseña a ignorar un diff real; uno de menos convierte un cambio normal en hallazgo.** Las 14 del slug ya están cerradas por la comprobación 11; quedan `login_attempts` y `missing-lang` |
| **4** | `deprecated-functions.json` — 11 funciones vigiladas | **Una deprecada nueva que nadie añada pasa sin verse.** Es la de menor coste: el fallo es una omisión, no una afirmación falsa, y PHP acaba diciéndolo por su cuenta |

**El criterio que las ordena**: arriba, las que **afirman algo falso** —un verde que no cubre lo
que dice cubrir—. Abajo, las que **callan algo cierto**. Un instrumento que miente es peor que uno
que no mira, porque el segundo no da permiso para dejar de mirar.

## T74 · `bin/cli gates` — el corredor que no deja pasar el silencio

**Qué hace**: corre todas las suites, imprime una línea por cada una, y **termina en 1 si alguna
no dijo si pasó**. No hay que leer la salida línea por línea, que era el encargo.

### Las dos cosas que se derivan, y ninguna se enumera

Es la LEY 11 aplicada al propio instrumento: un mecanismo alimentado por una lista a mano sigue
siendo memoria.

| Qué | De dónde sale |
| :-- | :-- |
| **Qué suites hay** | `CliActions::listActionNames()`, filtrado por el prefijo `unit-tests:core/`. Una suite nueva entra sola; no hay lista que se pueda quedar corta |
| **Si una suite corrió** | **De que imprima su balance.** Sin balance no llegó al final, y el motivo da igual |

**Por qué el balance y no un mensaje de omisión**: buscar los textos de omisión conocidos sería
otra lista a mano, corta el día que alguien escriba uno nuevo. Y hay una razón más honda: **desde
fuera, una suite que se omitió y una que terminó sin decir nada son indistinguibles.** Las dos
callan. Por eso se exige el veredicto, no la ausencia de excusas.

Cada suite corre en **su propio proceso**: varias llaman a `exit()` —se llevarían por delante al
corredor— y `db-restore` restaura la base entera.

### Provocado en las dos direcciones

| Se provoca | Qué sale |
| :-- | :-- |
| Una suite devuelve «omitida» antes de empezar | `[SIN VEREDICTO] prefer-slug — no dice si pasó: no imprimió balance`, salida **1** |
| El paquete instalado no trae `createScript()` | `[FALLÓ] scheme-sql-round-trip — Total: 1 \| Pasaron: 0 \| Fallaron: 1`, salida **1** |
| Todo en su sitio | `[PASÓ]`, salida **0** |

La segunda dirección es el arreglo en el origen: `UnitTest-SchemeSqlRoundTrip` **ya no se omite**.
Cuando el paquete se queda corto **falla, con su balance impreso**, que es lo que la LEY 13 pide.

### Lo que el corredor destapó a la primera

**Tres suites registradas no imprimen NINGÚN veredicto**: `core/database-exporter`,
`core/helpers-directories` y `core/http-client`. Corren, imprimen cosas, y **nunca dicen si
pasaron**. No estaban en ninguna lista de puertas —por eso nadie lo había notado—, y para efectos
de puerta valen lo mismo que una suite omitida.

**No se arreglan aquí**: son tres suites ajenas a este bloque y decide el PROPIETARIO si se les
añade balance o se declaran fuera de las puertas.

> **Y UN AVISO QUE NO ES MENOR**: `core/http-client` **hace una petición a un servicio externo**
> —termina diciendo «Revisa logs de terminal y Webhook.site»—. Al correr el corredor completo la
> primera vez, esa llamada salió. **Un corredor de puertas cuyo comportamiento por defecto habla
> con el exterior es un problema en sí mismo**, y no lo decido yo. `unit-tests:mautic-batch-send`
> se salva solo porque no lleva el prefijo `core/`: si alguien lo renombra, el corredor manda
> correos.
>
> Lo que propongo, **sin implementarlo**: que la suite lo declare **en su propia descripción** —no
> en una lista central, que volvería a ser memoria— y que el corredor la salte diciéndolo.

## T75 · EL PAQUETE SUBE A v3.6.0 — y no era el push: era el PHP con el que corre Composer

**Dato leído, no supuesto**, de `src/vendor/composer/installed.json`:

```
piecesphp/database        v3.2.1  ->  v3.6.0
piecesphp/datastructures  v3.1.0      (sin cambio)
piecesphp/geojson         v2.1.0      (sin cambio)
piecesphp/html            v2.1.0      (sin cambio)
```

`src/composer.lock` pasa de la referencia `d18c5b6b` a `0a919288`, que es exactamente el commit
que la etiqueta `v3.6.0` señala en `origin`.

### Mi diagnóstico anterior era el equivocado, y la causa real es otra

Dije que faltaba el **push**. **No faltaba**: `git ls-remote --tags` enseña v3.3.0 a v3.6.0 en
`origin` desde antes. Lo que fallaba es más tonto y más caro:

```
$ composer update piecesphp/database
    - Root composer.json requires php >=8.4.1 <8.6 but your php version (8.1.34)
      does not satisfy that requirement.
```

**`composer` del PATH corre con el PHP por defecto, que aquí es 8.1.34** —por debajo del piso
declarado—, así que Composer se niega a resolver y **no toca nada**. La forma que funciona es
decirle con qué PHP correr:

```bash
php8.5 /usr/bin/composer update piecesphp/database
```

Es la misma trampa que ya documentamos para `bin/cli` —de ahí el selector de binario que lleva—,
y **vuelve a aparecer en la herramienta de al lado**. La regla ya escrita no bastó: la herramienta
que sí la aplica es `bin/cli`, y Composer se quedó fuera.

### El 8/8, corrido de verdad, hoy

Primera vez desde que existe la corrección de las 39 declaraciones de clave ajena:

```
   [PASÓ] el descubrimiento encuentra mappers
   [PASÓ] ningún mapper se queda fuera en silencio
   [PASÓ] cada clave ajena declara el MISMO tipo que la columna que referencia
   [PASÓ] la ida y la vuelta cubren las MISMAS tablas
   [PASÓ] el script de creación se aplica ENTERO en el orden que emite
   [PASÓ] quedan creadas todas las tablas del script
   [PASÓ] el script de borrado se aplica ENTERO sin violar claves ajenas
   [PASÓ] no queda ninguna tabla
   Total: 8 | Pasaron: 8 | Fallaron: 0
```

**Con MariaDB de juez**: el esquema entero se crea en el orden que el generador emite y se deshace
sin violar una sola clave ajena. **La corrección de las 39 queda cerrada de verdad el 2026-08-25**,
no el día de su commit — que es cuando se escribió, no cuando se demostró.

### 1.3 · Qué más llevaba dormido por la versión del paquete

*Método: se buscan las guardas `method_exists` sobre clases del paquete y se fecha cada una por el
commit que la introdujo.*

| Qué | Guarda | Dormido desde | Ahora |
| :-- | :-- | :-- | :-- |
| `unit-tests:core/scheme-sql-round-trip` | `createScript` / `dropScript` | `9f1b11b0`, **24-08 11:06** | **8/8** |
| `bin/cli scheme-create` | `createScript` | `9f1b11b0`, **24-08 11:06** | Emite el DDL |
| `bin/cli scheme-drop` | `dropScript` | `bcb0a980`, **24-08 09:59** | Emite el DDL |

**Tres, no una.** La suite era la única que alguien miraba; las dos tareas se guardaban en silencio
y **nadie las volvió a correr para comprobarlo**. Ahora las tres funcionan, verificadas una a una.

**Matiz sobre la fecha, para no exagerarla**: entre el 24-08 a las 15:47 y las 15:48 la suite sí
corrió —con **5 pasadas y 2 fallos**, que fue lo que llevó a corregir las 39—, así que en ese
hueco el árbol de `vendor` tenía una copia buena. Desde las **15:50** vuelve a omitirse en todas
las corridas registradas. Lo que se puede afirmar con evidencia: **la omisión duró desde el 24-08
a las 15:50 hasta hoy**, y el **8/8 nunca se había observado**.

## T76 · EL EVENTO `updated` DICE LA VERDAD — y el arreglo NO cura el síntoma en 3 de los 4

**El escuchador no se toca.** Su comentario —«Si está rechazado pasa a pendiente al editar»— es
intención declarada del PROPIETARIO. Lo que se arregla es que «editar» dejara de significar
«guardar».

### El paquete: v3.7.0

`_executeUpdate()` guarda `rowCount()` **mientras el statement sigue vivo** —`execute()` lo anula
y `resetAll()` lo borra en cuanto devuelve— y `getLastChangedRowsCount()` lo expone.

| Decisión | Por qué |
| :-- | :-- |
| **CAMBIADAS**, no coincidentes, en el nombre | Es justo la distinción que se pierde |
| **-1** mientras no haya corrido ningún UPDATE | «No ha corrido» no es «no cambió nada» |
| `resetAll()` **no** lo borra | Pertenece a la última EJECUCIÓN, igual que `getLastSQLExecuted()` |
| Documentado que depende de **no** tener `MYSQL_ATTR_FOUND_ROWS` | Con esa bandera la cuenta pasa a ser de coincidentes y **el nombre pasa a mentir** |
| Documentada la diferencia de **SQLite** | Ahí `rowCount()` cuenta filas **tocadas**. La suite lo comprueba en MySQL y lo **informa** en SQLite, en vez de fingir que son iguales |

`core/database/active-record` sube de 7 a **11**. Quitada la captura, fallan **2 de 11**.

### Aquí: `BaseEntityMapper::update()`

```php
if ($updated && $this->lastUpdateChangedSomething()) {
    BaseEventDispatcher::dispatch(get_class($this), 'updated', $this);
}
```

Sin el accesor —paquete viejo— se mantiene la conducta anterior: **anunciar de menos sería peor**,
porque se perdería la reapertura de rechazos, que es intención. Quien avisa de que el paquete se
quedó corto es la suite, **que falla en vez de omitirse** (LEY 13).

### EL HALLAZGO: el arreglo es correcto y NO BASTA

*Método: se pide a un mapper que se guarde sin tocar nada y se lee de la base cuántas filas
cambiaron y en qué quedó la aprobación.*

| Sujeto | Guardado sin tocar nada | Aprobación RECHAZADA queda en |
| :-- | :-- | :-- |
| `UsersModel` | **0 filas cambiadas** | **RECHAZADO** ✔ |
| `OrganizationMapper` | **1 fila cambiada** | **PENDIENTE** ✘ |

**Por qué**: `OrganizationMapper::update()` hace `$this->updatedAt = new \DateTime()` antes de
llamar al padre. **El mapper cambia la fila él mismo**, así que la base tiene razón al decir que
cambió una: lo que ya no es cierto es que «no se tocó nada».

```
updatedAt 2026-08-25 09:20:01 -> 2026-08-25 09:21:38   filas cambiadas = 1   SE REABRIÓ
```

**A cuántos alcanza**: de los cuatro manejadores de aprobación, **tres** sellan `updatedAt`
—`OrganizationMapper`, `PublicationMapper`, `ApplicationCallsMapper`— y **uno no**, `UsersModel`.
En todo `src/app` hay **16** mappers con ese sellado.

**Detalle fino, medido**: dos guardados **dentro del mismo segundo** sí cuentan 0, porque la marca
de tiempo no llega a cambiar. O sea que el síntoma es intermitente por reloj, que es la peor forma
de que un defecto se manifieste.

**Lo que esto significa, dicho sin adornos**: el evento ya no miente —`updated` significa «cambió
una fila»— pero **la queja original sigue viva en 3 de los 4 casos**. Arreglarla del todo pide una
decisión que no es mía:

| Camino | Qué costaría |
| :-- | :-- |
| Que el mapper **no selle** `updatedAt` si no hay otro cambio | Toca los 16 mappers y cambia el significado de la columna |
| Que el escuchador **compare contenido** en vez de fiarse del evento | Toca lo que el PROPIETARIO dijo no tocar |
| **Aceptarlo**: el evento es honesto y `updatedAt` es un cambio real | No cuesta nada, y deja el síntoma |

### La suite, y las dos direcciones

`unit-tests:core/updated-event`, **7 comprobaciones**, con la base de juez y sobre una fila **viva**
que se aparta y se devuelve.

| Caso | Resultado |
| :-- | :-- |
| RECHAZADO guardado sin cambios | Sigue RECHAZADO |
| RECHAZADO guardado cambiando algo | Pasa a PENDIENTE — **la intención se conserva, y se demuestra** |
| PENDIENTE guardado sin cambios | No se reescribe: el centinela del alias sobrevive |
| Un mapper que sella `updatedAt` | **Cambia una fila igualmente** — el límite, fijado para que nadie lo pierda |

**Rota reponiendo el despacho incondicional: fallan 2 de 7**, y son exactamente las dos que
describen el defecto. **La tercera no puede fallar por ahí, y hay que decirlo**: «RECHAZADO con
cambios pasa a PENDIENTE» pasa con arreglo y sin él —es una guarda contra pasarse de corrección,
no contra la conducta vieja—.

**Rota por el otro lado**: con el paquete instalado sin el accesor, la suite **no se omite**, falla
con su balance impreso, y el corredor sale con 1.

### ESTADO: la puerta queda ROJA, y es lo correcto

`src/vendor` tiene **v3.6.0**. La v3.7.0 está commiteada y etiquetada en su repositorio, **sin
empujar**. Verificado de verdad instalando el paquete local a mano y midiendo con él —de ahí salen
todas las cifras de arriba—, y después **devuelto a v3.6.0**, que es lo que un clon encontraría.

**Para cerrarla**: push de `v3.7.0` y `php8.5 /usr/bin/composer update piecesphp/database`.

## T77 · ¿QUÉ MIDE EL 859? — el universo, contado

### 3.1 · Los dos números

*Método: los archivos que PHPStan analiza salen de **él mismo**, con `--debug`, que imprime uno por
línea. El universo sale de `git ls-files`, contando `.php` **y** los guiones sin extensión que
empiezan por almohadilla-admiración: `bin/walk-routes` es PHP y no lo parece.*

| | Archivos |
| :-- | --: |
| **PHP versionado en el repositorio** | **835** |
| **Que PHPStan analiza** | **802** |
| Excluidos a propósito en el neon (`src/adminer`, de terceros) | 22 |
| **Fuera del árbol al que PHPStan apunta** | **11** |

**802 declarados = 802 analizados.** Los dos números coinciden, y no comparten mecanismo: uno sale
del propio PHPStan y el otro del sistema de archivos. **Dentro del árbol declarado no se cae nada
en silencio**, que era la primera sospecha.

### 3.2 · La respuesta, con las palabras que pediste

**El 859 se midió sobre el universo REDUCIDO.** La cifra de referencia de toda la campaña mide 802
de 835 archivos: **mide menos de lo que creíamos.**

Ahora bien, hay que decir de qué tamaño es el hueco antes de que suene peor de lo que es: **22 de
los 33 que faltan son `src/adminer`**, un panel de terceros que no escribimos, y su exclusión está
declarada en el propio neon desde siempre. **Los que de verdad no estaban declarados en ninguna
parte son 11:**

```
bin/live-cache                              bin/tools/forbidden-routes.php
bin/node/copyDependencies.php               bin/tools/live-cache.php
bin/phpstan-process-result.php              bin/tools/refactorization/Rector.php
bin/walk-attribute                          bin/walk-routes
files/CliScripts/CorregirTiempoDuraciónWebm.php
files/CliScripts/GeneradorIntegralTipoUsuario.php
tasks/TasksManager.php
```

**Y lo incómodo no es el número, es CUÁLES**: `bin/tools/live-cache.php` y
`bin/tools/forbidden-routes.php` son los mecanismos de la LEY 11 —lo que construimos para que las
reglas no dependan de la memoria— y `tasks/TasksManager.php` corre en **cada `composer install`**.
**El instrumento que mide la calidad no mira las herramientas que hacen cumplir las reglas.**

**No se cambia `paths` en este bloque**, que era el encargo. Meterlos dentro es un movimiento con
su propio coste —esos guiones no comparten el autoload de la aplicación— y merece su medición.

### 3.3 · La comprobación 13, y nada más

`files/dev/phpstan-universe.json` declara **prefijos con su razón**, no archivos. `verify-integrity`
exige que **todo PHP versionado esté dentro de `paths`, dentro de `excludePaths`, o declarado ahí**.

| Se provoca | Qué dice |
| :-- | :-- |
| Un `.php` nuevo en un sitio no declarado | «`zz-fuera/Zz.php` — ni lo analiza PHPStan ni está declarado fuera. **El baseline no lo cuenta y aun así sale verde**» |
| Quitar `../src/app` de `paths` | Lo mismo, 801 veces |

**Un defecto encontrado al construirla, y es de los que enseñan**: `git ls-files` **entrecomilla los
nombres con acentos**, así que `CorregirTiempoDuraciónWebm.php` se perdía en silencio — la
comprobación contaba 834 en vez de 835. **El instrumento nuevo tenía dentro el mismo defecto que
venía a cazar.** Arreglado con `-c core.quotePath=false`.

### La forma barata de derivar el universo, dicha y NO implementada

**Apuntar `paths` a la raíz del repositorio y dejar que `excludePaths` lleve las excepciones.**
Entonces el neon sería la única declaración, este JSON sobraría, y **lo que quedara fuera tendría
que escribirse en el sitio donde se nota**. Coste a medir antes: los guiones de `bin/` no cargan el
autoload de la aplicación, así que entrarían con errores propios que hoy nadie cuenta.

