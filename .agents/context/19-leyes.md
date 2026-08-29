# 19 — Las leyes de esta campaña

> **DOCUMENTO DURABLE, Y DE MANTENEDOR.** Esto NO caduca y NO se disuelve con el registro.
> Vivía dentro de `## T0` del [18](./18-siguientes-ventanas.md) —un contenedor que nació para
> morir— y por eso el 18 no ha podido morir nunca: lo único permanente del archivo estaba dentro
> de lo temporal. Movido aquí el 2026-08-26, íntegro y con sus casos.
>
> **Quién debería leerlo**: quien MANTIENE el framework o trabaja sobre él con un agente. Quien
> viene a USARLO no pasa por aquí — para eso están los documentos `01` a `15`.

## Qué es una ley aquí

Una regla que **ya falló al menos una vez y costó dinero**. No son buenas prácticas: cada una
trae el caso que la funda, y sin ese caso no se entiende por qué es tan tajante. Se numeran por
orden de aparición.

> **DE DÓNDE SALEN LAS SIETE PRIMERAS, porque durante un tiempo pareció que faltaban.** La
> numeración empezaba en la 8 y las leyes 1 a 7 no aparecían en ningún archivo. **No es que se
> perdieran: es que se llamaban de otra manera.** Son los **siete puntos del criterio de cierre
> de `T0`**, y la LEY 8 se escribió literalmente a continuación del punto 7, continuando esa
> numeración — el commit `66a49f42` lo dice en su propio mensaje: *«T0 gana la LEY 8. Con los
> siete casos que explican media campaña»*. Buscadas en los **2.056 commits** de los cinco
> repositorios, «LEY 1» a «LEY 7» no existen en ninguna versión de ningún archivo. Recuperadas
> aquí íntegras en el bloque V, con su procedencia anotada. Ver T132.

> **EL NÚMERO DE LEY NO GUARDA NINGUNA RELACIÓN CON EL NÚMERO DE BLOQUE `T`.** La LEY 17 no sale
> de T17, ni la 20 de T20. Cada ley que viene de un bloque lo dice debajo de su título, y ese es
> el único vínculo que hay. **Las leyes se numeran correlativas por orden de entrada aquí**, y no
> se renumeran nunca.

| # | En una línea |
| --: | :-- |
| **1** | Cero errores que señalen un defecto real |
| **2** | Lo demás se arregla o se suprime con razón escrita, y suprimir exige que la razón EXISTA |
| **3** | Un `.neon` no es un cajón de sastre, es un registro documentado |
| **4** | El baseline solo baja |
| **5** | Toda cifra que sobrevive a la sesión lleva su método escrito |
| **6** | Una fase se cierra con una comprobación que se ha visto FALLAR |
| **7** | Un comentario que frena algo cabe en una línea |
| **8** | Una decisión que no vive en un archivo no se propaga |
| **9** | Una operación documentada en una sola dirección tiene un inverso sin estrenar |
| **10** | `password` es opaco para toda herramienta que mueva filas de usuario |
| **11** | Una regla que falla tres veces no se repite: se convierte en mecanismo |
| **12** | Una pasada de atribución solo es válida sobre una base recién restaurada |
| **13** | Una suite omitida es una puerta FALLADA, no un dato neutro |
| **14** | Un error en el registro se propaga a la compactación y se vuelve premisa |
| **15** | Un instrumento informa sobre el universo que MIRA, no sobre el que dice cubrir |
| **16** | Ningún censo reporta un cero sin haber probado que el instrumento ve |
| **17** | El alcance de una instrucción no se hereda de la conversación: se mide |
| **18** | Ningún mecanismo de control reporta «todo bien» sin haber probado que se ejecutó |
| **19** | Una afirmación sobre el consumidor no se deduce del productor |
| **20** | No se edita código por posición, se edita por estructura |
| **21** | Una regla mecánica se aplica a toda la familia o a ninguna |
| **22** | Se sospecha del instrumento cuando sorprende, y MÁS cuando confirma |
| **23** | Pedir la demostración no es ceremonia, es un detector |
| **24** | Una prueba que pasaría con el defecto puesto no es una prueba |
| **25** | Una red se prueba con el procedimiento con el que se va a usar, no en reposo |
| **26** | Un valor que significa «no medí» no puede participar en una igualdad |
| **27** | Una suite no puede medir su propio efecto |

---
### LEY 1 — CERO ERRORES QUE SEÑALEN UN DEFECTO REAL

*Punto 1 del criterio de cierre de `T0`.*

**Cero errores que señalen un defecto real.** No cero errores: cero **defectos**. La cifra
del baseline mide trabajo pendiente, no salud; lo que no puede quedar es un error que, leído,
describa algo que se rompe.

### LEY 2 — LO DEMÁS SE ARREGLA O SE SUPRIME CON RAZÓN ESCRITA, Y SUPRIMIR EXIGE QUE LA RAZÓN EXISTA

*Punto 2 del criterio de cierre de `T0`.*

**Todo lo demás arreglado, o suprimido CON RAZÓN ESCRITA** y, si la supresión es temporal,
**con la condición que la retira**.

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

### LEY 3 — UN `.neon` NO ES UN CAJÓN DE SASTRE, ES UN REGISTRO DOCUMENTADO

*Punto 3 del criterio de cierre de `T0`.*

**Los `.neon` dejan de ser cajón de sastre y pasan a ser registro documentado.** La auditoría
de los 67 `ignoreErrors` —48 vivos silenciando 3.083 errores, 19 muertos— es el formato de
referencia. **Los cinco repositorios al mismo estándar.**

### LEY 4 — EL BASELINE SOLO BAJA

*Punto 4 del criterio de cierre de `T0`.*

**TRINQUETE: el baseline solo baja.** Un error nuevo se arregla o se justifica por escrito.
El que no cumpla ninguna de las dos cosas, no entra.

### LEY 5 — TODA CIFRA QUE SOBREVIVE A LA SESIÓN LLEVA SU MÉTODO ESCRITO

*Punto 5 del criterio de cierre de `T0`.*

**No es un paralelismo con la LEY 2: es lo que hace funcionar el trinquete.** Comparar dos
baselines solo significa algo si se midieron igual, y ya falló una vez — cuando el pipeline
pasó de tabla a JSON, la pregunta «¿es comparable?» no tuvo respuesta hasta que alguien fue a
averiguarla. Un trinquete que compara peras con manzanas no impide nada.

Con dos límites, para que no se vuelva burocracia:

- **Solo las cifras que SOBREVIVEN a la sesión**: las que entran en un documento, en un
  baseline o en el `CHANGELOG`. Un número dicho en un reporte se explica por su contexto y
  muere con él; ese no lleva método.
- **El método debe ser REPRODUCIBLE, NO DESCRIPTIVO**: el comando o la herramienta nombrada,
  no una frase. «Medido sobre la salida de PHPStan» no sirve; «`bin/phpstan`, contando
  instancias en `PHPStanResult.json`» sí.

### LEY 6 — UNA FASE SE CIERRA CON UNA COMPROBACIÓN QUE SE HA VISTO FALLAR

*Punto 6 del criterio de cierre de `T0`.*

**UNA FASE NO SE CIERRA POR CONSENSO NI POR CHECKLIST.** Se cierra cuando existe una
comprobación que falla si deja de ser cierta, **Y ESA COMPROBACIÓN SE HA VISTO FALLAR.**

**Escrita porque la migración a 8.5 se dio por terminada y no lo estaba.** Había DOS
detectores —PHPStan y la promoción de `E_DEPRECATED` a excepción— y **no se probó ninguno**.
Los dos fallaban a la vez, cada uno por su motivo, y quedaron nueve llamadas deprecadas en el
árbol; una tumbaba la generación de imágenes con un 400.

Plantar un `imagedestroy()` el primer día y correr los dos habría costado **cinco minutos** y
habría enseñado que el estático no lo veía con el rango de versiones puesto, y que el de
ejecución solo dispara si alguien pisa la línea.

### LEY 7 — UN COMENTARIO QUE FRENA ALGO CABE EN UNA LÍNEA

*Punto 7 del criterio de cierre de `T0`.*

**SI NECESITA UN PÁRRAFO, EL PÁRRAFO NO ES EL COMENTARIO.** La regla anterior —«¿impide romper
algo?»— era correcta y **no frenaba la deriva**, porque no decía nada del TAMAÑO: un relato de
doce líneas siempre encuentra una frase suya que sí impide romper algo, y con esa se justifica
entero.

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

Eso frena el borrado. **Todo lo demás —la ventana de bloqueo, el código de seguridad vacío, el
secreto que se regeneraba— es historia, y la historia va al `CHANGELOG` y a T35.**

**Y hay un motivo que no es estético: un comentario largo ENVEJECE MAL.** El del `chr()` explica
un arreglo de compatibilidad; el día que ese arreglo sea irrelevante, el comentario **miente**, y
nadie va a ir a buscarlo. Un documento fechado puede envejecer sin mentir porque se lee como
historia; un comentario en el código se lee como el presente.

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

#### AMPLIACIÓN — LA RAZÓN DIVERGE ANTES QUE EL DATO, Y NADA LA COMPARA

**El caso que la funda** (T73): `$forbidden` estaba copiada en los dos recorredores. Al comparar,
**los 17 patrones coincidían**, y una comprobación automática habría dicho «no hay divergencia».

Lo que había divergido era **el comentario**: la copia de `bin/walk-routes` explicaba por qué se
mira también la URL —el incidente de `/forms/add/`— y la de `bin/walk-attribute` no. Quien tocara
la segunda no tenía delante el motivo de la mitad de la lista.

**Una copia se detecta comparando datos. El motivo se pierde sin que ninguna comparación lo note**,
porque nadie compara prosa —y por la REGLA MAYOR de T21, nuestras puertas tampoco pueden—. De ahí
que la razón no se duplique nunca: **vive UNA vez, en el sitio del que todos leen.**

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
| «`git add` con rutas explícitas, y cuadrar el conteo» | Comprobación que compare lo preparado contra lo modificado antes de commitear. **Y ya falló una vez con la regla escrita: ver abajo** |
| «No editar PHP por línea ni por expresión regular» (T10) | **Sin mecanismo.** Hoy solo hay la regla escrita |
| «Un guion de `bin/` tiene que ser ejecutable» | **YA ES MECANISMO**: comprobación 9 de `verify-integrity`. Ver más abajo |
| «Los comentarios de prosa se recortan al pasar» | `verify-integrity` ya lo mide y ordena; falta que el registro no pueda crecer |

**Las dos últimas filas son las que más me preocupan**, porque las dos ya han fallado una vez.

> **Y la del `git add` también, el 2026-08-25.** Al commitear v3.7.0 en el paquete el conteo salió
> **6 modificados / 3 preparados**, lo imprimí, y **commiteé igual**. Eran los artefactos de
> PHPStan del paquete —fueron después, en su propio commit—, así que el daño fue ninguno. Eso no
> cambia lo que pasó: **la regla dice PARAR cuando no cuadra, y no paré.** Queda anotado por quien
> lo hizo, que es lo que la mantiene viva.
>
> Es exactamente el argumento de la LEY 11 aplicado a mí: mientras cumplirla dependa de que alguien
> se acuerde en el momento, **la regla es una intención**. La fila de la tabla de arriba deja de
> ser hipotética.

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

#### AMPLIACIÓN — 2026-08-26 · LA MISMA LEY EN EL ÁRBOL DE ARCHIVOS

La ley se escribió mirando la base. **Vale igual para el árbol, y ahí el agujero es mayor**, porque
en el árbol ni siquiera existe el «restaurar» que la mitad de la base ya tiene.

**Dos ceguera distintas, y hay que separarlas:**

1. **`db-restore` restaura la base, NO el árbol.** Una escritura de archivo que ya ocurrió no
   vuelve a ocurrir por mucho que se restaure la base: el archivo sigue ahí. Es exactamente la
   LEY 12 —el sujeto reacciona a la medición y se agota— pero sin el mecanismo que la base sí
   tiene desde T71. **Caso medido:** la escritura de estáticos dio **0** dos veces seguidas; la
   primera por el instrumento, la segunda porque la corrida anterior ya había creado los enlaces.
2. **El comparador no ve un enlace RECREADO.** `SnapshotTask::snapshotFiles()` firma cada archivo
   con `tamaño:mtime:sha1`, y las tres cosas **siguen el enlace hasta su destino**: `is_file()`
   sobre un enlace devuelve `true`, y `filemtime()` y `sha1_file()` leen el archivo apuntado.
   Borrar el enlace y rehacerlo apuntando al mismo sitio deja **la misma firma**. Medido en T104:
   33 recreaciones seguidas, y el comparador no registró ninguna.

**Un cero se lee igual venga de donde venga.** «No hay nada» y «ya no queda nada que hacer» dan la
misma pantalla, y esta ley existe porque la segunda se disfraza de la primera.

#### Lo que haría falta para cerrarlo — DISEÑO, no código

**Nada de esto está construido.** Se escribe antes para que se pueda discutir sin haber gastado en
implementarlo.

**a) Comparar los enlaces por su destino, no por su contenido.** En `snapshotFiles()`, mirar
`is_link()` **antes** que `isFile()` y, cuando lo sea, firmar con `lstat`:
`'enlace:' . readlink($ruta) . ':' . $ctimeDelEnlace`. Eso distingue las tres cosas que hoy se
confunden en una: **el enlace apareció**, **el enlace apunta ahora a otro sitio**, y **el enlace se
rehízo apuntando al mismo sitio**. Dos cuidados: el iterador **no debe seguir enlaces de
directorio** —`RecursiveDirectoryIterator::FOLLOW_SYMLINKS` es opcional y hay que dejarlo apagado,
o un enlace que apunte hacia arriba cuelga el censo—, y **un enlace roto deja de ser invisible**,
que es una mejora, no un efecto colateral.

**b) Qué significaría «restaurar el árbol».** No hay pareja backup/restore como en la base, y las
tres lecturas posibles no valen lo mismo:

| Lectura | Qué haría | Por qué no, o por qué sí |
| :-- | :-- | :-- |
| Con git | `git checkout .` más `git clean -xfd` | **No.** `server-delegated/` está en `.gitignore`, así que solo lo borraría `-x`, y `-x` se lleva por delante `vendor/`, `node_modules/` y los logs. Además los archivos son de `www-data`. Demasiado romo |
| Copiando el subárbol | Guardar y volcar el directorio entero | **No aporta**: un enlace no tiene contenido que copiar, y el coste crece con el árbol |
| Con la lista ya declarada | `bin/cli tree-restore` borra exactamente las rutas del bloque `files` de `volatile-state.json` | **Sí.** Es el inverso que le falta al par, y **reutiliza una declaración que la regla 1 ya obliga a mantener**: una sola verdad con dos usos, no una lista nueva que envejezca sola (LEY 11) |

**c) Y la marca, igual que en la base.** `db-restore` deja `files/dev/last-restore.json` y por eso
el paso 2 de arriba es posible. Un `tree-restore` tendría que dejar la suya, y
`bin/walk-attribute` exigir las **dos** —base y árbol— antes de dar por válida una pasada de
atribución. Sin la marca, la exigencia no se puede escribir.

**El obstáculo real, que no es de diseño:** los archivos de `server-delegated/` los crea Apache y
son de `www-data`. Una tarea de CLI corriendo como el usuario del desarrollador **no puede
borrarlos**. O la tarea corre como `www-data`, o el directorio se vuelve escribible por el grupo.
Es una decisión de despliegue y **no la tomo yo**.

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

#### EL ALCANCE REAL DEL CASO: no era una puerta, eran tres, y ninguna gritó

La suite era lo único que alguien miraba. Con la misma guarda de versión, y **guardándose en
silencio**, había dos tareas más:

| Qué | Dormido desde | Hasta | Tiempo dormido |
| :-- | :-- | :-- | --: |
| `bin/cli scheme-drop` | 24-08, **09:59** | 25-08, la instalación de v3.6.0 | **~23 h** |
| `bin/cli scheme-create` | 24-08, **11:06** | ídem | **~22 h** |
| `unit-tests:core/scheme-sql-round-trip` | 24-08, **15:50** | ídem | **~17 h** |

**Las dos tareas no informaban a nadie.** Una suite que se omite al menos imprime la línea que
nadie leyó; una tarea que se guarda a sí misma **no se vuelve a invocar**, así que su silencio no
llega ni a ser una línea sin leer. Verificadas una a una al instalar la versión buena: las tres
funcionan.

**La lección, que es la que da su tamaño a la ley**: la guarda de versión estaba bien escrita en
los tres sitios. Lo que faltaba no era la guarda —era que alguien se enterara de que había saltado.

#### LA FORMA MÁS PURA DE LA LEY: `helpers-directories` decía verde mientras enseñaba rojo

La suite `unit-tests:core/helpers-directories` **devolvía `success => true` pasara lo que
pasara**: su `return` llevaba el éxito escrito a mano, sin mirar ningún contador. Y a la vez
imprimía `[FALLÓ]` por pantalla en cada comprobación que no pasaba.

**Es el tercer estado de la ley en su forma más pura.** Una suite omitida *calla*, que ya es
malo. Esta **afirmaba lo contrario de lo que enseñaba**: quien leyera la pantalla veía rojo,
quien leyera el veredicto veía verde. Y lo que se propaga aguas abajo —a un corredor, a un
script que encadena tareas, a un CI— **es el veredicto, no la pantalla**.

**Lo que la distingue del caso que funda la ley**: la omisión de `scheme-sql-round-trip` al
menos dejaba una línea que alguien podía leer. Aquí no había nada que leer, había una mentira
bien formada. Corregida en T85: cuenta y devuelve la verdad, **19/19**.

### LEY 14 — UN ERROR EN EL REGISTRO SE PROPAGA A LA COMPACTACIÓN Y SE VUELVE PREMISA

**Los documentos no tienen puertas.** Ninguna suite falla cuando el registro miente, ningún
análisis estático lo señala y ningún revisor lo tropieza, porque un documento se lee para
*saber*, no para *comprobar*. Es la única parte de este repositorio sin un mecanismo detrás.

**El caso que la funda.** El bloque M afirmó que `$m->campo ?? $otro` sobre una propiedad mágica
«devuelve SIEMPRE `$otro`», y lo llamó el más peligroso de los tres, «porque parece un valor por
defecto inofensivo mientras descarta el valor real». **Es falso**: `??` y `?:` consultan `__get`
y devuelven el valor real. Se escribió de memoria, dentro del documento cuyo primer principio es
que toda cifra lleva su método, y sin necesitar ninguna herramienta: quince líneas y un
intérprete. Ver M-bis.

**Pero el error no se quedó en el documento, y eso es lo que la ley nombra:**

| Paso | Qué le pasa a la afirmación |
| :-- | :-- |
| 1 | ARQUITECTO la escribe en el bloque M **como hallazgo**, indistinguible de lo medido |
| 2 | CODER la lee y la arrastra a su contexto de trabajo |
| 3 | La conversación se compacta y el resumen la destila a «conceptos técnicos clave» |
| 4 | **Ya es una premisa**: un hecho sin autor, sin fecha y sin método |

**La compactación es un amplificador de errores del registro.** Comprime, y al comprimir borra
justamente lo que permitía dudar —quién lo dijo, cuándo, con qué lo midió—. Lo que entra como
«ARQUITECTO afirma» sale como «se sabe». Y a partir de ahí se planifican bloques enteros: el
censo de este bloque se dimensionó incluyendo 31 sitios que no tenían nada roto.

**La ley, en dos mitades:**

1. **Una afirmación sobre el comportamiento de una herramienta** —PHP, MySQL, git, composer—
   **no se escribe sin su método**, exactamente igual que una cifra. La regla de las cifras ya
   existía; lo que faltaba era darse cuenta de que una semántica es una cifra.
2. **Cuando una medición desmiente una entrada anterior, la corrección se escribe APARTE y se
   enlaza**, nunca fundida con el original. Si se funde, desaparece que hubo un error —y con él
   la única señal de que ese autor puede volver a cometerlo—. Por eso M-bis va en commit propio.

**Qué lo atrapó, y por qué no consuela.** El `??` lo corrigió ARQUITECTO al medirlo por fin; la
cadena `isset($m->x->y)` la corrigió CODER midiendo en vez de creerse el bloque. **Ninguna de
las dos correcciones vino de un mecanismo**: vinieron de que alguien, tarde, hizo lo que el
documento pedía desde el principio. Y la LEY 11 ya dice qué hacer con las reglas que dependen de
que alguien se acuerde.


### LEY 15 — UN INSTRUMENTO INFORMA SOBRE EL UNIVERSO QUE MIRA, NO SOBRE EL QUE DICE CUBRIR

**Y el universo que dice cubrir está escrito en un comentario, que no tiene puertas (LEY 14).**

Tres casos, y tres no es anécdota:

| # | Instrumento | Lo que decía | Lo que miraba de verdad |
| :-- | :-- | :-- | :-- |
| 1 | La lista de puertas | «8/8» | 7 corrían; `scheme-sql-round-trip` se omitía a sí misma. **LEY 13** |
| 2 | `bin/walk-attribute` | «sin cambios» sobre las rutas de lectura | 40 de 79 ejercitadas; las demás respondieron 4xx o 5xx y **nunca llegaron al código que podría escribir**. Ver T98 |
| 3 | `bin/walk-routes` | «Recorre **todos los assets**» en su propio encabezado | Cero `.css` y cero `.js` en toda la campaña: el extractor solo aceptaba comillas dobles y el framework emite simples. Ver T102 |

**Por qué los tres se leen en verde.** Ninguno miente en su salida: los tres informan
correctamente **sobre lo que miraron**. La mentira está en el denominador, y el denominador vive
donde nadie lo comprueba —una constante, un encabezado, una lista de puertas escrita a mano—.
Un instrumento con el denominador equivocado **es más peligroso que uno roto**, porque el roto se
nota.

**Cómo se distingue de la LEY 13.** La 13 habla de una puerta que **no corre**; esta, de una que
**corre entera y sobre menos de lo que promete**. La 13 se cierra ejecutando; esta no se cierra
ejecutando, porque ejecutar es justamente lo que produce el verde.

**El mecanismo, y hoy solo lo tiene uno.** Un instrumento **dice su propia cobertura**: imprime
cuántos elementos cubrió contra los que declara cubrir, **lista los que se quedaron fuera con su
razón** y **sale con código distinto de cero cuando la cobertura es parcial**. Es lo que se le
puso a `bin/walk-attribute` en T98, y es lo único que convierte el denominador en algo
comprobable. Lo demás —leer el encabezado y creerlo— es la LEY 14 con otra ropa.

#### La pregunta que abre, y NO se responde aquí

**¿Qué otros instrumentos nuestros declaran en su encabezado un alcance que nadie ha comprobado
que tengan?** Los candidatos evidentes, para cuando toque mirarlos, sin prejuzgar ninguno:

- `bin/phpstan` y su universo de archivos —ya falló una vez, por el truncado de rutas: T2—.
- `bin/cli scan-missing-lang`, acotado por `no_scan_langs`: hoy solo escanea `en`.
- `bin/cli verify-integrity` y sus dieciséis comprobaciones: cada una declara qué cubre.
- `SnapshotTask::EXCLUDED_PATHS`, que decide qué parte del árbol entra en la foto.
- `bin/rector` y su configuración de rutas, hermana del caso de PHPStan.
- Las suites propias: cada una declara qué prueba, y esa declaración no la comprueba nadie.

**No se abre aquí.** Se deja escrito para que exista la pregunta, que es lo que faltaba.

### LEY 16 — NINGÚN CENSO REPORTA UN CERO SIN HABER PROBADO ANTES QUE EL INSTRUMENTO VE

**Un cero y un instrumento roto se leen igual.** Es la LEY 15 llevada al sitio peor: no al
universo que el instrumento mira, sino a si mira algo.

**El caso que la funda, y no era de la máquina del PROPIETARIO.** En este entorno `grep` es una
**función de shell instalada por el propio agente** —`CLAUDE_CODE_EXECPATH`— que re-ejecuta el
binario de `claude` como **`ugrep` con `-G`**. `/usr/bin/grep` sí es GNU 3.11, pero no es el que
corre al escribir `grep`.

Medido sobre `src/app`, con y sin `-F`:

| Patrón | shell `-F` | GNU `-F` | shell **regex** | GNU **regex** |
| :-- | --: | --: | --: | --: |
| `isset($_FILES` | 3 | 3 | **0** | 3 |
| `$_FILES` | 49 | 49 | **0** | 49 |
| `$showSQL` | 1 | 1 | **0** | 1 |

**Cualquier búsqueda sin `-F` de un patrón con `$` devolvía CERO.** No solo con el `$` a mitad de
patrón: también al principio.

**Y hay un segundo estrechamiento, más silencioso todavía.** Esa función añade `--ignore-files`,
así que **salta lo que está en `.gitignore`**. Contando un literal en todo el árbol: **164 archivos
con el `grep` del shell contra 228 con GNU**. Los 64 que faltan están todos bajo `src/vendor/`. Para
casi todos nuestros censos eso es lo que queremos —pero nadie lo decidió, y no se veía.

**Dónde NO llega la contaminación, medido**: dentro de un guion `#!/bin/bash` y dentro de un
`exec()` de PHP, `grep` resuelve a GNU 3.11. Es decir, **todo lo que mide el utillaje
—`verify-integrity`, los recorredores, `bin/phpstan`— usó GNU**. Lo sospechoso es solo lo que se
tecleó a mano en la conversación y acabó escrito aquí.

**La ley.** Todo censo lleva por delante una **búsqueda de control con resultado conocido y
distinto de cero**. Si el control da cero, **el censo aborta** diciendo que el instrumento está
roto. No reporta cero. Y toda cifra sale con la **ruta resuelta** del binario y su versión, no con
el nombre que se tecleó — porque el nombre puede ser una función que envuelve otra cosa.

**El mecanismo, no el recordatorio**: `bin/censo`. Acordarse de escapar es memoria, y la memoria
ya falló (LEY 11).

### LEY 17 — EL ALCANCE DE UNA INSTRUCCIÓN NO SE HEREDA DE LA CONVERSACIÓN: SE MIDE

**La escribió ARQUITECTO y la fundó ARQUITECTO.** El bloque S decía «borrar `CAN_ADD_ALL` y
`CAN_VIEW_ALL` de **los cuatro Mappers**». El cuatro no salió de ningún censo: salió de que
cuatro módulos eran los que se habían estado discutiendo. El censo del CODER midió **ocho**
declaraciones de `CAN_VIEW_ALL`, y en cuatro de ellas la constante **sí restringe por
organización**. Ejecutar la instrucción al pie de la letra habría **abierto cuatro listados**.

**Por qué no es la LEY 15 otra vez.** Allí el instrumento miraba menos de lo que decía cubrir.
Aquí el instrumento estaba bien: lo que venía con el universo mal era **la orden**. Cada elemento
del alcance estaba medido —los cuatro módulos eran reales y sus ocho ramas también— y por eso el
alcance **se siente medido**. Lo que nadie miró es lo que quedaba fuera. Un alcance heredado de la
conversación trae el denominador de la conversación, no el del árbol.

**La ley.** Antes de escribir una instrucción que **borre o mueva un símbolo**, el alcance se
mide sobre el árbol entero, no sobre el subconjunto del que se venía hablando. Y si la
instrucción nombra un número —«los cuatro», «los trece», «los veinticuatro»— ese número
**lleva al lado el censo que lo produjo**, o no se escribe.

**El corolario que ya estaba funcionando.** El CODER censa antes de ejecutar aunque la
instrucción le dé el número hecho, y eso es lo que evitó el daño. Esa costumbre deja de ser
costumbre: **es la puerta de esta ley**, y se escribe en las instrucciones como paso, no como
virtud del que las cumple.

### LEY 18 — NINGÚN MECANISMO DE CONTROL REPORTA «TODO BIEN» SIN HABER PROBADO QUE SE EJECUTÓ

**Es la LEY 16 sacada de los censos y llevada a las guardas.** Allí el problema era que un cero es
indistinguible de un instrumento roto; aquí es que **el silencio es indistinguible de la
conformidad**. Una guarda que no corre no dice nada, y nada se lee como que todo va bien.

**Los tres casos, y son la misma forma tres veces:**

| # | El mecanismo | Cómo calló | Cómo se leyó |
| :-- | :-- | :-- | :-- |
| 1 | El `grep` de los censos | ugrep devolvía **0** para cualquier patrón con `$` sin escapar | «no hay ninguno» |
| 2 | `bin/cli gates` | una suite se omitía a sí misma y no aparecía | «8/8» |
| 3 | La guarda del `git add` | se escribió con una variable llamada `añad`; **bash rechaza los identificadores con `ñ`**, la asignación nunca ocurrió y la comparación tampoco | «el conteo cuadró» |

**El tercero es el más limpio de los tres** porque no hubo error de juicio: la guarda estaba
escrita, era correcta y decía lo que había que decir. Simplemente **no se ejecutó**, y en la salida
eso se ve igual que si hubiera pasado. Se comprobó después a mano y estaba bien; durante ese rato,
una guarda muda se leyó como una guarda satisfecha.

**La ley, en tres exigencias:**

1. **Toda guarda emite una línea que prueba que corrió.** `guarda ejecutada: 4·4·4`. **La ausencia
   de esa línea es un fallo**, no un silencio: si no está, la guarda no corrió y se para.
2. **La guarda vive en un archivo, no en la línea de órdenes.** Escrita en línea se reescribe cada
   vez, y cada reescritura puede tener su propia `ñ`. En `bin/guarda-add` se escribe una vez.
3. **Los guiones de guarda llevan `set -e`, `set -u` y `set -o pipefail`.**

#### Y aquí hay una medición que corrige lo que se pidió

El bloque U pedía `set -u` y `set -o pipefail`. **Ninguna de las dos caza el caso que funda la
ley.** Medido:

| Opción | Ante `añad=2` |
| :-- | :-- |
| `set -u` | **sigue adelante** (código 127, «orden no encontrada») |
| `set -o pipefail` | **sigue adelante** |
| **`set -e`** | **ABORTA** |

Un identificador que bash no acepta no es una variable indefinida —que es lo que `set -u` vigila—
sino **una orden que no existe**. Las tres opciones van puestas, pero **la que hace el trabajo es
`set -e`**, y decirlo importa: quien copie las dos primeras creyendo que está cubierto, no lo está.

#### Lo que la ley NO puede exigir todavía

Que alguien LEA la línea. `gates` y `verify-integrity` fallan solos; `bin/guarda-add` sale con
código distinto de cero, pero **quien lo invoca puede ignorar ese código**. La cadena termina en
una persona, y eso queda dicho.

### LEY 19 — UNA AFIRMACIÓN SOBRE EL CONSUMIDOR NO SE DEDUCE DEL PRODUCTOR

**Cinco casos, todos del mismo tramo de campaña, todos de ARQUITECTO, y todos con la misma
forma**: se leyó lo que un archivo *contiene* y se afirmó lo que otra herramienta *hace*
con él, sin abrir la línea que lo implementa.

| Se leyó (el productor) | Se afirmó (el consumidor) | Lo que hacía de verdad |
| :-- | :-- | :-- |
| El SCSS, con sus `@import` | «van a salir avisos de deprecación» | La consola los tenía **silenciados**: `gulpfile.js` los apaga uno por uno |
| El `return` de una suite | «esa suite no puede ponerse roja» | `gates` **parsea la línea de balance impresa**, no el valor devuelto |
| El manual de `set -u` / `pipefail` | «con esto la guarda aborta» | El caso que funda la LEY 18 es una **orden inexistente**: solo aborta con `set -e` |
| Un ejemplo de mensaje de commit | «la convención es `docs(t114)`, así que el bloque tiene procedencia en git» | Esa forma se usó **una vez en 2.056 commits**. La procedencia existe, pero en el commit que aplicó el cambio |
| El aviso `LF will be replaced by CRLF` | «los artefactos dan ruido de diff» | El aviso habla del **checkout**. Con `text` puesto git normaliza en los dos sentidos y **no hay diff**; los cambios eran de contenido |

El error no es de descuido: en los cinco casos el razonamiento era **correcto sobre el
productor**. Lo que faltaba era ir a mirar quién lo consume.

**Los dos últimos son de la misma familia y merecen su nombre**: el cuarto es **generalizar
desde un solo ejemplo** —se vio `docs(t114)` una vez y se dedujo una convención—, y el quinto
es **heredar una premisa de un informe sin volver a medirla**, que es la LEY 14 y ésta actuando
juntas: un error del registro se convirtió en una tarea del bloque siguiente.

> **LA PUERTA: toda afirmación sobre el comportamiento de una herramienta se acompaña de la
> línea del consumidor que lo implementa, o se escribe como HIPÓTESIS A MEDIR.**

Y su forma más útil, porque es la que se olvida: **el productor y el consumidor casi nunca
están en el mismo repositorio ni en el mismo lenguaje.** El SCSS lo consume Gulp; la suite la
consume `GatesTask`; el guion lo consume bash. **Cruzar esa frontera de memoria es lo que falla.**

Emparenta con la LEY 15 —un instrumento informa sobre el universo que MIRA— pero no es la
misma: la 15 va del **alcance** de una medición, esta va de la **cadena** entre quien produce
un dato y quien lo interpreta.

### LEY 20 — NO SE EDITA CÓDIGO POR POSICIÓN, SE EDITA POR ESTRUCTURA

*Sale de T10 del registro, y de ninguna otra parte. El número de ley NO tiene relación con el
número de bloque.*

> **LA PRECISIÓN QUE LA VUELVE ÚTIL: ES LA PRIMERA OPCIÓN, NO EL PLAN B.** Los cuatro
> incidentes comparten algo peor que la causa técnica: **en los cuatro se empezó por el atajo y
> se llegó a la estructura DESPUÉS de romper algo.** Escrita como diagnóstico —«esto pasó por
> editar por posición»— no impide el quinto; escrita como orden de preferencia, sí.
>
> **Si la herramienta estructural cuesta veinte minutos más, cuesta menos que revertir
> dieciocho archivos.** No es un cálculo optimista: es lo que pasó en el cuarto incidente.

| Incidente | Causa | Qué lo delató |
| :-- | :-- | :-- |
| `OrganizationMapper` con un docblock sin cerrar | Un script partió por `\r\n` un archivo con 1.342 CRLF **y un LF suelto**: todos los índices siguientes se desplazaron | Nada automático. Se encontró a mano |
| 32 falsos positivos en `verify-integrity` | Contaba `/*` contra `*/` en texto plano, y `'image/*'` aparece dentro de cadenas | La propia revisión de los resultados |
| `SyncOTPRecordsTask` sin `namespace` ni `use` | Un corte por posición casó con el docblock **de archivo** en vez del **de clase** | Ejecutar la tarea |
| **18 mappers rotos** | Un `preg_replace` con `(.*?)` y `/s`: **la expresión regular no sabe dónde cierra una llave** | `php -l`, archivo a archivo antes de commitear |

**Cuando haya que preguntarle algo al código fuente, se tokeniza.** `token_get_all()` lleva
cuatro apariciones en el proyecto: es patrón, no excepción.

> **La diferencia entre las dos herramientas no es la elegancia, es qué saben:** un contador de
> llaves —o `token_get_all()`— sabe qué es un bloque; una expresión regular sabe qué es una
> silueta de texto. **Cuando la pregunta es «¿dónde termina esto?», la silueta no puede
> responder**, y responderá igualmente con algo que parece razonable.

Corolario: **cortar por índice de cadena también es editar por posición**, y abrir un archivo
sin `newline=''` también — 20 archivos pasaron de CRLF a LF y un commit declaró **4.185
inserciones para 41 líneas reales**.

### LEY 21 — UNA REGLA MECÁNICA SE APLICA A TODA LA FAMILIA O A NINGUNA

*Sale de T17 del registro.*

Antes del primer lote de Rector había **39 archivos** con `strlen($route) > 0`. Rector convirtió
**25** y dejó **14**, porque solo pudo probar el tipo en esos.

**Cada cambio individual era correcto. El resultado agregado fue peor:** un lote mecánico
**partió en dos un grupo homogéneo**, justo en el código que se estaba a punto de unificar.

> **Antes de correr una regla mecánica, cuenta a cuántos sitios de la familia alcanza. Si no
> los cubre todos: o completas el resto a mano EN EL MISMO COMMIT, o no la corres.**

La aplicación parcial **fabrica divergencia** aunque acierte caso por caso. Y en una plantilla
que se clona, **la divergencia fabricada se hereda**.

### LEY 22 — CUANDO UNA MEDICIÓN SORPRENDE SE SOSPECHA DEL INSTRUMENTO, Y CUANDO CONFIRMA, MÁS

*Sale de T20 del registro.*

**Dieciséis veces en esta campaña el defecto estaba en el INSTRUMENTO, no en lo medido.** El
inventario completo, con lo que cada uno llegó a afirmar, está en T20; aquí va lo que no puede
perderse.

| Instrumento | Se afirmó | Era |
| :-- | :-- | :-- |
| La tabla de PHPStan, que recorta rutas al ancho de terminal | «192 archivos con errores» | **195**, y Rector no veía 34 |
| `git diff --name-only --ignore-cr-at-eol` | «58 de 59 archivos cambian» | **cero**: el filtro lo aplica `--stat`, no `--name-only` |
| `phpVersion` como RANGO en `phpstan.neon` | «cubre las dos versiones» | Un rango reporta la **intersección**: cegó toda deprecación exclusiva de 8.5 |
| `git blame` sin `--ignore-revs-file` | «114 comentarios nuestros contra 18 viejos» | **44 contra 88** |

#### La forma afilada, y es la que cuesta

**UN ERROR DE MEDICIÓN QUE COINCIDE CON TU HIPÓTESIS ES EL ÚNICO QUE NO VAS A ATRAPAR.**

Todos los demás instrumentos fallaron dando un número **raro**, y la rareza disparó la
comprobación. El `git blame` falló dando un número **esperado**: confirmaba la sospecha del
propietario y la mía. Eso no es una respuesta, **es un eco**. Y la conclusión operativa cambiaba
entera —de «recortamos lo nuestro» a «hay una lista cerrada que mantener»—.

> **EL SEGUNDO MÉTODO INDEPENDIENTE SE EXIGE SOBRE TODO CUANDO EL PRIMERO CONFIRMA LO QUE
> CREÍAS.** La sorpresa dispara la sospecha sola. **La confirmación no dispara nada.** La regla
> no es «duda de lo que te sorprende»: es **«duda de lo que te da la razón»**.

### LEY 23 — PEDIR LA DEMOSTRACIÓN NO ES CEREMONIA, ES UN DETECTOR

*Sale de T21 del registro.*

**El peor defecto de esta campaña no apareció revisando. Apareció al intentar ENSEÑAR.** El
commit de 4.185 inserciones para 41 líneas reales era irrevisable, y por eso invisible: nadie
lee cuatro mil líneas para comprobar que sobran. Salió cuando el propietario dijo **«enséñame
el Paso B»** y hubo que producir el diff.

El caso más caro es T35: dos hipótesis sobre el 2FA —una mía y una del propietario—, las dos
coherentes con **todos** los síntomas observables, **las dos falsas**. Ninguna sobrevivió a
poner el estado en la tabla y preguntarle a la puerta.

#### Su mitad estructural: NUESTRAS PUERTAS NO PUEDEN VERIFICAR PROSA. NINGUNA. NUNCA

Todas verifican **propiedades ejecutables**: que un archivo compile, que una clase cargue, que
una firma no cambie, que un contador no suba, que una suite pase. **Un comentario, un documento,
un nombre de variable, una entrada del `CHANGELOG` — su verdad es SEMÁNTICA**, y está fuera del
alcance de `verify-integrity`, del trinquete y de las suites. No es que hoy no lo detecten: es
que **no pueden**.

Lo demostró T40: ocho comentarios acabaron describiendo un código que no era el suyo, y **todo
estaba en verde**, porque la puerta contaba menos bloques y eso era justo lo que se buscaba.

> **TODO CAMBIO SOBRE PROSA SE ENTREGA COMO PARES ANTES/DESPUÉS, no como conteo.**

### LEY 24 — UNA PRUEBA QUE PASARÍA CON EL DEFECTO PUESTO NO ES UNA PRUEBA

*Sale de T46 del registro. Corolario de la LEY 23, aplicado a las pruebas en vez de a las puertas.*

| Caso | La prueba preguntaba | Por qué pasaba igual con el defecto |
| :-- | :-- | :-- |
| `SET NAMES` (T43) | «¿la conexión es `utf8mb4`?» | La base de pruebas **ya lo es por defecto** |
| `otp-write-separation` (D2) | `grep` de `->save(` sobre el cuerpo del método | **GRAVE**: D2 delegaba la escritura a otro método, una llamada más abajo |
| El evento `updated` | «¿cambió `updatedAt`?» | Las dos escrituras caían **en el mismo segundo**, y el campo tiene resolución de segundo |

> **Una comprobación sobre el TEXTO del código verifica una propiedad del texto, no del
> comportamiento.** Sirve para lo que sirve —es barata y no necesita datos— pero **no se puede
> presentar como prueba de que algo no ocurre**. Lo que no ocurre solo se demuestra ejecutándolo.

El tercer caso es el que más enseña, porque **la provocación se hizo**: salió verde y se leyó
como «la guarda no era el motivo», cuando el motivo era el reloj. **Se validó la prueba sin
enterarse de que no se había validado.** Arreglo: envejecer la marca por SQL antes de la segunda
escritura, para que el cambio no dependa de cuánto tarde la máquina en cruzar el siguiente
segundo.

#### AMPLIACIÓN — 2026-08-29 · NO ES SOLO DE LAS PRUEBAS

**CUALQUIER VERIFICACIÓN QUE PASARÍA CON EL DEFECTO PRESENTE NO ES UNA VERIFICACIÓN.** La ley
nació mirando pruebas; el bloque AA la encontró dos veces en código que no es de prueba:

| Dónde | Qué verificaba | Por qué pasaba con el defecto puesto |
| :-- | :-- | :-- |
| `db-backup` | `file_exists($outputPath)` | **El archivo existe aunque la exportación reviente a mitad.** Dio «Operación exitosa» sobre 7 tablas de 33 |
| Los guiones de permisos | los `[+]` que imprime y su salida 0 | `sudo chown` y luego `chmod` SIN `sudo`: si el propietario elegido no es quien ejecuta, **todos** los `chmod` fallan y el guion sigue imprimiendo su parte |

> La forma es siempre la misma: **se comprueba un efecto colateral del intento en vez del
> resultado del intento.** Que el archivo exista es consecuencia de haber empezado, no de haber
> terminado. Que el guion llegue al final es consecuencia de no abortar, no de haber aplicado
> nada. Confirmada por ARQUITECTO en el bloque AB. Ver T141 y T142.

### LEY 25 — UNA RED SE PRUEBA CON EL PROCEDIMIENTO CON EL QUE SE VA A USAR, NO EN REPOSO

*Sale de T138. Confirmada por ARQUITECTO en el bloque Z.*

La foto de E3 —`bin/cli snapshot`— se iba a usar para atribuir el efecto de cada lote. Antes de
usarla se le pidió una comparación **en reposo**: dos fotos seguidas sin tocar nada. Salió
**diferencia vacía**, que es lo que tenía que salir.

Y estaba rota por los dos lados a la vez:

| Defecto | Qué hacía | Por qué no salía en reposo |
| :-- | :-- | :-- |
| El `mtime` decidía la igualdad | Un `cp` de restauración marcaba **todo** como cambiado | En reposo nadie copia nada |
| 59 archivos sin `sha1` | `'grande' == 'grande'` se leía como «idéntico» | En reposo esos 59 tampoco cambian |

**Ruido propio y punto ciego, y ninguno de los dos se asoma quieto.** Salieron al hacerle *lo
mismo que le iba a hacer el lote*: restaurar la base desde un volcado y borrar archivos de verdad.

> Arreglar uno sin ver el otro habría cambiado ruido por ceguera, que es peor: el ruido se nota.

**La forma corta**: *una red en reposo no está probada, está guardada.*

---

### LEY 26 — UN VALOR QUE SIGNIFICA «NO MEDÍ» NO PUEDE PARTICIPAR EN UNA IGUALDAD

*Sale de T138. Confirmada por ARQUITECTO en el bloque Z.*

La foto guardaba por archivo `tamaño:mtime:hash`. Para los archivos grandes no calculaba el hash
y escribía el literal `'grande'`. La comparación era una igualdad de cadenas, así que dos
archivos **completamente distintos** de más de ese tamaño daban `'grande' == 'grande'` y la foto
los declaraba **idénticos**.

**El centinela no decía «no lo sé»: decía «son iguales».** Un valor que significa ausencia de
medición, metido en una comparación, se convierte en una afirmación positiva y falsa.

Arreglo, y es de forma, no de umbral: la ausencia se representa con `NO_MEDIDO` y **se ramifica
antes de comparar**, produciendo un tercer estado —`NO COMPARABLE`— que cuenta como hallazgo y no
como coincidencia. La foto además dice cuánto le cuesta (`272 MB hasheados en 598 ms`), que es lo
que hacía atractivo el corte.

**La forma corta**: *«no lo sé» y «son iguales» no son el mismo valor, y una igualdad no sabe
distinguirlos.*

---

### LEY 27 — UNA SUITE NO PUEDE MEDIR SU PROPIO EFECTO

*Sale de T138 y cobra su segunda pieza en T140. Confirmada por ARQUITECTO en el bloque Z.*

| Suite | Efecto que dejaba | Su propio veredicto |
| :-- | :-- | :-- |
| `core/database-exporter` | Una tabla de andamiaje sin borrar | **23/23 PASADAS**, con la fuga y sin ella |
| `core/db-backup-round-trip` | Una fila huérfana en `system_approvals_elements` **por corrida** | **5/5 PASADAS**, con la fuga y sin ella |

Las dos recogían *algo* —la primera su tabla, la segunda su usuario— y las dos se dejaban un
rastro que **no está entre lo que ellas miran**. Ninguna podía cazarlo: una suite comprueba lo
que afirma, y su propia basura no es una de sus afirmaciones.

Las cazó **la foto**, que no participa en la corrida. Y el caso de `db-backup-round-trip` afila la
ley un grado más: **la fila no la escribe la suite**. La escribe `SystemApprovalManager::init()`
al arrancar la app, porque la suite lanza un subproceso mientras su usuario de prueba existe.
*El efecto de una suite incluye lo que otro escribe por haber corrido ella.*

> **Consecuencia operativa**: el instrumento que mide los efectos de un lote tiene que ser
> **externo a las suites**, y hay que correrlo aunque todas estén en verde. Verde no es limpio.

**La forma corta**: *quien participa no arbitra.*

#### AMPLIACIÓN — 2026-08-29 · el efecto incluye lo que otro escribe por haber corrido ella

`db-backup-round-trip` dejaba una fila huérfana en `system_approvals_elements`. **La suite no
la escribe.** La escribe `SystemApprovalManager::init()` al arrancar la aplicación, porque la
suite lanza `bin/cli db-backup` en un subproceso mientras su usuario de prueba existe.

Buscar «quién hace el INSERT» dentro de la suite no lo habría encontrado nunca: el INSERT
está en el arranque. **Lo que una suite tiene que recoger no es lo que ella escribe, es lo
que queda distinto por haber corrido.** Confirmado por ARQUITECTO en el bloque AA. Ver T141.
