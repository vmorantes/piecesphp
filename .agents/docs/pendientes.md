# PENDIENTES — semilla

> **Vive en `.agents/docs/pendientes.md` desde el 2026-09-14** (ADR 0006); antes estaba en
> `files/dev/PENDIENTES.md`. Los registros históricos citan la ruta vieja.

*Creado el 2026-09-13 por ARQUITECTO, en aplicacion de LEY 33 y en su primera oportunidad.*

> **QUE ES ESTO Y QUE NO ES.** Es una SEMILLA, no el inventario. El inventario completo sale del
> cruce de los 42.543 renglones de la sesion del CODER contra el corpus, y eso es trabajo de E6.
> Aqui se anota, HOY, solo lo que se recupero hoy — porque lo que no se escribe el mismo dia esta
> a una compactacion de no haber existido nunca. Eso es exactamente LEY 33.

> **TRES CATEGORIAS, y la del medio es la que un resumen siempre se come:**
> **TRABAJO PLANIFICADO** · **DECISIONES ESPERANDO AL PROPIETARIO** · **DECLARADO Y APARCADO**.

---

## RECUPERADO EL 2026-09-13 — lo que el PROPIETARIO nombro y no estaba escrito

El PROPIETARIO reclamo tres cosas ausentes del mapa. Comprobado sobre el corpus ENTERO:

| Que | En el corpus | Veredicto |
| :-- | :-- | :-- |
| **Migrar el modulo de usuarios a `classes/`** | **CERO** apariciones | **PERDIDO Y RECUPERADO HOY** |
| **Las rupturas / el bloque de CAMBIOS INCOMPATIBLES** | el bloque existe en `CHANGELOG.md:41`; **su mantenimiento no** | **PARCIAL** |
| **«Perfeccionar geovisor»** | `20-contrato…md`, nota final | sobrevivio |

### 1 · EL MODULO DE USUARIOS SIGUE EN LA DISPOSICION VIEJA — TRABAJO, y es rompedor

Medido el 2026-09-13:

    src/app/controller/      12 controladoras  (Users, Login, Recovery, Token, Avatar, Panel…)
    src/app/model/            9 modelos
    src/app/view/usuarios/   35 vistas
    src/app/classes/         21 modulos con la disposicion moderna

**El corazon del framework vive fuera del patron que el propio framework ensena.** Es una
plantilla que se clona y lo primero que abre quien la clona es justo lo que no sigue el patron.
ROMPE, asi que su sitio es esta campana o ninguna. **Sin bloque asignado, sin medir el coste.**

### 2 · EL `CHANGELOG` LLEVA CINCO BLOQUES SIN TOCARSE — TRABAJO, y la causa es de ARQUITECTO

`CHANGELOG.md:41` tiene el bloque «⚠ CAMBIOS INCOMPATIBLES — agrupados a proposito, para una
MAJOR», con doce rupturas escritas. **Ultima modificacion: `b870cc98`, que es AW.** Despues:
AX, AY, BA, AZ y BB, ninguno escribe ahi. Y en ellos hay rupturas reales — la guarda de AP
retirada, `having_segment` cambiando de semantica, y **`logMailer` fuera de la respuesta JSON de
una ruta publica**, que es visible para cualquiera que consuma esa API.

**CAUSA: `CHANGELOG.md` no aparece en el `previsto` de ninguno de los ultimos seis recuadros de
ARQUITECTO.** Dejo de pedirlo y nadie lo noto. **Vuelve al `previsto` desde BC.**

Y es el mismo error del que ARQUITECTO dio una leccion dos dias antes con `column-renames.json`:
aplazar la herramienta es gratis, aplazar el REGISTRO no. Reconstruir veinte bloques de rupturas
el dia de la MAJOR cuesta dias; escribirlas al pasar cuesta cuatro lineas por bloque.

### 3 · «PERFECCIONAR GEOVISOR» ES UN POST-IT, NO UN PLAN — DECISION PENDIENTE

Aparece UNA vez en todo el corpus: la nota final del contrato. **Cero contenido, cero alcance,
cero medicion.** Lo unico medido cerca es `GeoJSONManager` en `14-deuda-y-limpieza.md`: 1.286
lineas, acoplado en un solo archivo, aislable. **Pero eso es el gestor de capas; el GEOVISOR no
esta descrito en ninguna parte.** Necesita que el PROPIETARIO diga que es antes de poder medirlo.

---

## DECISIONES ESPERANDO AL PROPIETARIO — al 2026-09-13

| # | Que | Desde | Predeterminado |
| :-- | :-- | :-- | :-- |
| ~~`P15`~~ | `tags.txt` en la raiz | 02-09 | **RESUELTO**: era un archivo de demostracion del PROPIETARIO para corregir al arquitecto sobre el versionado (dicho el 2026-09-14). Ya no existe |
| ~~`P15`~~ | `source-docs/…/vps/index.md`, modificado sin dueno | bloque AN | **RESUELTO el 2026-09-14**: el PROPIETARIO lo deja «entre ustedes». Es documentacion, asi que es del arquitecto: se adopta (avisos de seguridad correctos sobre el acceso de root por SSH), corrigiendo su remision a una «seccion 5» que no existe, y entra en el commit de documentacion |
| ~~`P16`~~ | **BD** — nivelar los analizadores de los 4 paquetes | 02-09 | **YA NO ESPERA AL PROPIETARIO**: el 2026-09-02 a las 17:42 delegó la instrumentación de análisis en ARQUITECTO («Todo la instrumentación de analisís en desarrollo está en tus manos»). Recuperado en el cruce del 2026-09-14 |
| ~~`P19`~~ | `master` y etiquetas en los cuatro paquetes | 14-09 | **RESUELTO el 2026-09-14**: en los paquetes se etiqueta, y «esta bien»; `master` sigue siendo su estable; todos llevan `dev`, se homologan `dev` y `master` y desde ahi se trabaja en `dev`. Regla 30 y guarda al dia |
| ~~`P23`~~ | **RESUELTO el 2026-09-14, por delegación** («Resuelve P22 y P23 como tu prefieras»): ADR 0008, y html se nivela en `#022`. Texto original: **html se queda sin nivelar**: su `composer.lock` local (ignorado por git, de antes de su 3.0.0) fija `piecesphp/datastructures` v3.1.0, y su `composer.json` pide `^4.0`. Un update parcial de las herramientas no resuelve. Hace falta actualizar también `piecesphp/datastructures`, que no es herramienta de análisis (ADR 0007) | 14-09 | **Predeterminado**: html sigue midiendo con phpstan 2.1.42 contra datastructures 3.1.0, declarado así en `shared-toolchain.json`. Opciones: el PO autoriza al coder una vez, o lo ejecuta él |
| — | Los 9 selectores: DOCUMENTAR, decidido el 30-08, **sin lote asignado** | 30-08 | entra en E6 |
| — | El frances: `profiles-translation-config.js`, `dynamic-translations/fr/`, carpetas `de/it/pt` | — | «Del PROPIETARIO» en §7 |
| — | El rol 50 con nombre `null` (`roles.php:112`) | — | — |
| ~~—~~ | La unificacion de `allowedRoute` / `_allowedRoute` / `routeName` «por abstraccion o por unificacion de estilo» | 31-08 | **EJECUTADA** en AI (T149): la triada en las 41 controladoras y `_allowedRoute` como plantilla. Si «sentido» pedia mas (p. ej. `current_user` frente a `getLoggedFrameworkUser`), eso sigue sin decidir. Corregido en el cruce del 2026-09-14 |
| — | **Que es el geovisor** | — | bloquea el punto 3 |

## RESTRICCIONES SUYAS QUE CONDICIONAN TRABAJO YA PLANIFICADO

- **OTP**: *«se usa en apps externas que consumen el framework headless»*. Condiciona el bloque de
  OTP y no estaba anotado.
- **ORM**: *«dejemoslo para despues de la major, quizas en otra mayor; me preocupa como reemplazar
  el lenguaje de `ActiveRecordModel`, lo uso mucho»*.
- **Subidas**: *«hace falta auditar seguridad de DATOS de uploads de modulos privados como
  documents, news»*. El lote planificado mira LA PUERTA; el pidio auditar LOS DATOS.
- **`see-more`**: **SI ESTA DECIDIDO** desde el 31-08 — *«si no tiene proposito ni utilidad clara,
  muere; si esta danada se corrige»*. §7 lo daba por no decidido: **eso era un error de
  ARQUITECTO**, corregido aqui.

## EL PRIMER ENCARGO DE LA CAMPANA — recuperado hoy, y explica el corpus entero

> *«Sabes que es este proyecto? Conocelo y entiendelo. Como pista (aunque no necesariamente
> actualizada): https://deepwiki.com/vmorantes/piecesphp — Genera todo el contexto de base
> necesario y puedes ponerlo en `.agents/context/`.»*

**`.agents/context/` existe por esa linea.** Las 24.792 lineas del corpus, las 33 leyes y el
contrato salen de un encargo de dos frases. Queda escrito porque un dia alguien preguntara por
que el corpus vive ahi y no en `docs/`.

---

## LO QUE NO ESTA AQUI, Y DONDE MIRAR

El mapa de lotes hasta la MAJOR y el roadmap posterior viven en `20-contrato-de-trabajo.md` §7 y
en `.agents/docs/roadmap-posterior/` (16 documentos). **Esta hoja no los duplica**: duplicar un inventario es
fabricar dos verdades, y eso es lo que la campana lleva un mes retirando.

**Lo que falta de verdad**: el cruce completo de la sesion del CODER
—`96249ca4-…-e0a93dd90331.jsonl`, 42.543 lineas desde el 20-08— contra el corpus. Ahi estan
TODOS los recuadros de ARQUITECTO verbatim, y con ellos las decisiones del PROPIETARIO citadas de
vuelta. Es trabajo de E6 y **no urge desde que ese archivo tenga copia de seguridad.**

---

## RECUPERADO EL 2026-09-14 — del registro completo de eventos

El PROPIETARIO encontro el registro de eventos de la campana entera: **13.999 eventos del
2026-08-19 al 2026-09-14, todos los dias cubiertos, 458 turnos suyos y 1.071.281 caracteres.**
Es la ventana que se daba por perdida.

Primera pasada, acotada a proposito: **los diez turnos que dicen «recuerdame», «anota para el
final» o «para el final»**. Dieron esto:

### NUEVO · Una herramienta de despliegue en Packagist — 2026-08-24

> *«Quiero una herramienta en packagist para desplegar mi framework. Asi yo mantengo el framework
> pero quien quiera usarlo simplemente usa esa herramienta que se lo despliega **sin ciertos
> archivos que son basura para un despliegue** como el `source-docs`, `adminer` y demas
> cuestiones de desarrollo. Es solo una idea, si no es muy compleja vamos, si no hay que pensarlo
> mejor. **Esa herramienta se documentaria en `source-docs`.**»*

Hay una entrada de roadmap cercana —«El framework como paquete y su despliegue.md»— **pero la
peticion concreta, con su criterio (que el clon no reciba lo que es basura para desplegar) y su
condicion (si no es muy compleja), no estaba escrita.** Y enlaza con el migrador: son la misma
familia, herramientas de distribucion.

### NUEVO · El informe final tiene una TERCERA parte que faltaba — 2026-08-24

> *«Luego en el informe personal que te pedi para el final me tendras que **explicar todos esos
> artefactos como `verify-integrity` y compania**.»*

El informe de cierre estaba anotado con dos mitades —calidad del criterio de diseno y
legibilidad de mercado—. **Falta la tercera, y es la mas util para el: que le expliquen sus
propios instrumentos.** 26 comprobaciones, 10 censos, 25 suites y 33 leyes que se construyeron
mientras el hacia de mensajero. Va con el documento de aterrizaje, y **fuera del registro del
repo**, como el resto del informe.

### ENRIQUECIDO · Las cuatro revisiones, ahora CON NOMBRE — 2026-08-29

El roadmap decia «las cuatro revisiones de seguridad y operacion» sin enumerarlas. **Son estas,
textuales:**

1. **Revisar como mejorar el sistema de reporte y registro de errores.**
2. **Revisar y mejorar el sistema de encriptacion.**
3. **Revisar y mejorar el sistema de autenticacion.**
4. **Revisar posibilidad de un sistema de API Tokens.**

La primera **se solapa con el lote de los cuatro registros** —el registro de ERRORES es
exactamente eso—, asi que no son cuatro trabajos independientes: uno ya tiene sitio.

### CONFIRMADO · Su plantilla de creacion de modulos — 2026-08-19

La plantilla de expresiones regulares para basar un modulo nuevo en `publications` esta viva en
`15-plantilla-clonar-publications.md`. **No se perdio.**

### METODO, y se dice porque la primera cifra estaba mal

La primera extraccion dio «490 turnos». **Contaminada**: los eventos de tipo `user` arrastran
ecos del propio texto de ARQUITECTO. Restando los textos que aparecen como `assistant` y los
duplicados exactos quedan **458**. LEY 22 — cuando una medicion sorprende, primero el
instrumento.

**Lo que queda del cruce**: 448 turnos sin mirar, y las sondas por palabra clave NO bastan —
«migrar usuarios a `classes/`» no aparece con esas palabras en ningun turno anterior al 13-09,
asi que lo dijo de otra forma. **El cruce completo se hace leyendo por tramos, no grepeando**, y
es trabajo de E6.

> **SUPERADO el mismo 2026-09-14**: el PROPIETARIO lo quiso ya y completo (*«QUiero que mires
> TODO para dar una buena herencia a tu sucesor»*). Hecho: ver la seccion siguiente.

---

## RECUPERADO EL 2026-09-14 (II) — el cruce completo de los 466 turnos

Hecho por el arquitecto entrante (bitacora 0001), leyendo por tramos, no grepeando. **Metodo**:
los turnos del PROPIETARIO, deduplicados (**466**), en seis tramos por fecha; cada tramo leido
entero y cada encargo, decision, restriccion o preferencia cruzado contra `.agents/context/`
(con `historico/`), este archivo, `files/dev/roadmap/`, `files/dev/tests.md` y `CHANGELOG.md`.
**Resultado**: 353 items; 251 presentes (71 %), 50 parciales, 43 ausentes, y el resto
reemplazados dentro del propio tramo o preguntas sin respuesta visible. **Limite**: «ausente»
significa que no aparece con terminos distintivos y sus sinonimos; no es prueba de que falte.

Las **preferencias de trabajo** que faltaban estan ya en `.agents/rules/30-protocolo-coder.md`,
«El PO, en sus palabras». Aqui va el resto, en las tres categorias.

### Trabajo planificado que no estaba escrito

- **`SOLO_PROPIAS`** (P2, aceptada el 2026-09-02). La rama `&& false` de
  `PublicationsController` («si no es el administrador, solo ver las propias») ni se borra ni se
  queda muda: pasa a ser la constante de clase `SOLO_PROPIAS = false`, con dos lineas que digan
  que enciende. Media linea dentro de un bloque posterior.
- **Los seis modulos sin punto de extension de acceso** (P4, aceptada el 2026-09-02) reciben la
  triada en **E6**, junto a la guia de creacion de modulos: son rompedores y baratos.
- **El modulo de usuarios a `classes/`**: su sitio es **al final** de la campaña, con lo rompedor
  (2026-09-13).
- **Retirar `aminyazdanpanah/php-ffmpeg-video-streaming`**: aprobado el 2026-08-20 («si estas
  seguro que no uso nada de esa libreria, ok») y nunca ejecutado; sigue en `src/composer.json`.
  Tocar Composer lo autoriza el PROPIETARIO en su bloque.
- **PHPStan cierra en E6**, y el entregable no es un numero: las ~40 supresiones en dos listas,
  PERMANENTE y TEMPORAL CON SU CONDICION (respuesta dada el 2026-09-01).
- **El proposito de `processFromQuery`** (subconsultas y tablas derivadas, dicho el 2026-09-02)
  solo vive en el 18: hay que llevarlo a `06-orm-mappers.md` o `13-recetas.md` antes de que el
  18 se disuelva.
- **`source-docs/` completo, «como la que tendria cualquier framework», y TODA la documentacion
  ajustada a la verdad** (2026-08-24). E6 decia «reestructurar»; el encargo es mas ancho.
- **Skill `full-stack-php-senior`**: el PROPIETARIO la quiere 100 % orientada a su trabajo con
  PiecesPHP (2026-08-20), aplazo su evaluacion (2026-08-20), y su regla de «no tocar los paquetes
  `piecesphp/*`» sobra, porque la skill es para trabajar EN el framework (2026-08-27). Es
  configuracion de agentes: trabajo del arquitecto.
- **Requisito de entorno**: la suite `core/database-exporter` necesita `zlib`, `bz2` y `zip`; sin
  ellas se salta en vez de fallar. El PROPIETARIO instalo `bz2` el 2026-08-21. Va a
  `.agents/context/21-pruebas-y-puertas.md`.
- **La puerta de columnas** que el PROPIETARIO aplazo el 2026-08-22: tras el renombrado, cero
  columnas con guion bajo en `$fields`. Va con el lote de renombrado.

**Despues de la MAJOR** (extienden; van a `.agents/docs/roadmap-posterior/`, donde hoy faltan):

- **Registros: la pieza 0 es el inventario de TODO lo logueable** —que sucesos merecen fila y en
  que registro, cuales no, su clave de catalogo y su retencion—, y va **antes** que el
  instrumental comun (2026-09-13). `Los registros — CUATRO…md` sigue poniendo el instrumental
  primero.
- **El sistema de errores**, delegado en ARQUITECTO (2026-08-29): que errores rompen y cuales se
  degradan a aviso; el aviso en `head.php`; una bandera de tres modos (siempre se registra y se
  cuenta; lo que cambia es si rompe y si se ve); y una interfaz de depuracion para
  `admin-error-log`, que hoy vuelca un JSON sin formato. `Seguridad y operacion…md` §1 solo
  trae el enunciado.
- **Traducciones**: un flujo guiado por artefactos para dar de alta traducciones, facil de
  entender para agentes (2026-08-29; pidio ideas); el GUI de traducciones con sus condiciones
  (la interfaz escribe datos, nunca PHP; el zip es una promocion revisable); y la semantica del
  multilenguaje documentada (fusion por clave, caida a `default`, orden de carga).
- **La skill de aterrizaje** («descubrir proyectos PiecesPHP», 2026-08-27): para no quemar
  tokens, agnostica de version, igual que un agente sabe que algo «ES LARAVEL».
- **Revision del ORM en funcionamiento y en estetica**, con `EntityMapper` nombrado (2026-08-24).
  Las guias de migracion y de uso viven en el repositorio `database`, referenciadas desde la
  documentacion de PiecesPHP (2026-08-31).

### Decisiones y restricciones que no estaban escritas

- **`Importers` se conserva** y se consolida con `DataImportExportUtility` (2026-08-21 y
  2026-08-29). `14-deuda-y-limpieza.md` decia «eliminar»: corregido el 2026-09-14.
- **`Components`**: el PROPIETARIO se inclina por conservarlo porque se usa (2026-08-21); el 14
  lo sigue listando como «eliminar o promover». **Sin decidir.**
- **PHPStan: solo se silencia un falso positivo real** (2026-08-26), mas estricto que la LEY 2;
  la decision de cada caso, delegada.
- **Versionado**: una version estable apunta siempre a `last-stable`, ya fusionada; el
  PROPIETARIO aun no esta listo para versionar y la conversacion queda pendiente con el
  (2026-08-26).
- **`isset`**: delegado en ARQUITECTO con la condicion de explicarle las implicaciones
  (2026-08-25). El 18 dice lo contrario (bloque M).
- **HttpClient**: «lo importante es que funcione»; la forma, del arquitecto (2026-08-25).
- **Azure y HubSpot**: prioridad baja, delegados en el arquitecto (2026-08-20, 2026-08-24).
- **ORM**: ademas del lenguaje de `ActiveRecordModel`, el PROPIETARIO temia migrar por lo profundo
  que es el uso de `EntityMapper` (2026-08-21).

### Declarado y aparcado, o perdido

- **Una lista de lo que el PROPIETARIO pensaba «eliminar total y parcialmente»**, perdida en una
  compactacion antes del 2026-08-24. No se recupero, y su memoria no es fuente. Si reaparece,
  entra aqui.
- **`Locations`: la tarjeta de puntos esta oculta** por `$ignoreRouteNames` en
  `Locations/Views/locations/main.php`; las rutas, `PointMapper` y la tabla siguen vivos. Trampa
  de clon sin documentar.
- **`DYNAMIC_TRANSCALTIONS` es una errata portante**: es la clave con la que esta guardado el
  valor y no se corrige sin migrar lo almacenado. `08-i18n.md` no lo avisa.

### Correcciones al registro — documentos que mienten hoy

| Donde | Que dice | Que es verdad | Estado |
| :-- | :-- | :-- | :-- |
| `14-deuda-y-limpieza.md` (Importers) | «Duplicado. Eliminar» | se conserva y se consolida | **CORREGIDO** 2026-09-14 |
| `18` T60, encabezado | «el error de diseño fue del propietario» | fue de ARQUITECTO, y el PROPIETARIO lo rechazo | **CORREGIDO** 2026-09-14 |
| Esta tabla, P16 y la unificacion | «espera al PROPIETARIO», «sin responder» | delegada; ejecutada en T149 | **CORREGIDO** 2026-09-14 |
| `20` §7, «Los 9 selectores: DECIDIDO» | `see-more`: SIN DECIDIR | decidido el 2026-08-31 | pendiente |
| `20` §7, «LAS ETIQUETAS» | P14, pendiente | aprobada el 2026-09-02 (`12-convenciones.md`, convencion de etiquetas) | pendiente |
| `20` §7, comentarios que cuentan la campaña | «pendiente de decision del PROPIETARIO» | aplicado por predeterminado desde AY | sin verificar |
| `roadmap/EventsLog…md:120` | «LA DECISION YA ESTA TOMADA: (a)» | fue (b) (su linea 63) y despues cuatro registros | pendiente |
| `roadmap/Los registros…md` | el registro unico, propuesta del arquitecto | lo impulso el PROPIETARIO (2026-09-02) | pendiente |
| `12-convenciones.md:291` | «PHP 8.1+ compatible hasta 8.4» | 100 % 8.5; 8.4 es un extra (T122) | pendiente |
| `.agents/context/21-pruebas-y-puertas.md:109` (antes `files/dev/tests.md`) | `core/http-client` sin cobertura | probablemente rancio (T130 da 10/10) | sin verificar |
| `18` T34 | «E0 cierra al empujar» | nada de push (20 §3) | pendiente |
| `18` bloque M | `isset`: «no lo decide ARQUITECTO» | delegado con condicion | pendiente |
| `14-deuda-y-limpieza.md` (Components) | «Stub. Eliminar o promover» | el PROPIETARIO se inclina por conservar | pendiente |

### Encargos del PROPIETARIO al arquitecto entrante — 2026-09-14

| Encargo | Estado |
| :-- | :-- |
| Montar el modelo arquitecto-coder con la skill, tomando lo mejor de lo copiado de otro proyecto y adaptandolo | hecho y commiteado: diez commits, de `1c92eee0` a `1a2d1ec8` (`#006`→`#007`, bitacora 0001) |
| El PROPIETARIO reabrio el chat del coder y lo renombro: para el es la misma sesion | escrito en la regla 30, «Reabrir un chat no es la misma sesion tecnica». Solo avisa si lo reabre con un mensaje sin responder |
| Recordarle las dos ordenes de `/rename` en cada inicio de dia de trabajo | ya estaba en la skill y en la regla 30; ademas, primer paso de «Para una sesion nueva» en `.agents/estado/AHORA.md` |
| Sin commits en este proceso inicial | cumplido: todo queda sin commitear hasta su permiso |
| Usar la conversacion entera del arquitecto saliente para la herencia; el archivo muere tras su uso | hecho: fuera del repositorio, no se versiona |
| Decidir si hace falta un coder nuevo | hecho: el PO abrio la sesion `PiecesPHPUpgrade-Coder-Main` (P17) |
| P18: el commit del andamiaje lo autoriza justo antes de empezar a trabajar; **nada empieza sin su orden** | **RESUELTO el 2026-09-14**: «Comitea andamiaje». Y autorizacion permanente de commits atomicos para el trabajo que nombre; el push y lo demas reservado siguen siendo suyos. Registrado como excepcion de este repositorio en el ADR 0005 |
| P21: el orden de directorios, antes de trabajar en el framework | **RESUELTO el 2026-09-14**: «Asi es». El lote 0b va primero, justo despues del commit del andamiaje |
| **No se trabaja sobre el framework hasta terminar de ponerse de acuerdo** («primero salgamos de todos estos detallitos») | vigente |
| P20: la contraseña de prueba del 18 no afecta a nadie (nadie sube esa pareja a produccion); borrarla por higiene | hecho: sustituida en el 18. La historia de git la conserva |
| ¿Se perdio lo de unificar y optimizar los importadores como bases boilerplateables con ejemplos funcionales? | **No se perdio, y es cierto.** Es el lote E5 del mapa. Lo que mentia era `14-deuda-y-limpieza.md`, corregido el 2026-09-14. Queda una contradiccion: el 18 dice fusionar `DataImportExportUtility` EN `Importers` (2026-08-21), y el PROPIETARIO dijo el 2026-08-29 agruparlo EN `DataImportExportUtility`. Vale la del 29 salvo que diga lo contrario |
| Borrar lo copiado que no aplique, al terminar | hecho |
| Recibir el reporte de BC del coder saliente | hecho: `#002`, evaluado en la bitacora 0002 |
| Informe final: lo pedido, lo hecho y lo que hay por delante | en el chat y en `.agents/estado/` |
| Recordatorio: el trabajo incluye los cuatro paquetes hermanos (`database`, `datastructures`, `geojson`, `html`) | escrito en `.agents/rules/30-protocolo-coder.md`, «Los cuatro paquetes hermanos». Su andamiaje de agentes es el viejo: llevarles el modelo nuevo es trabajo que el PROPIETARIO puede nombrar |
| Recordatorio: nunca abrio sesion en los paquetes; el arquitecto y el coder anteriores trabajaban en ellos desde aqui | coincide con la regla 30: se trabaja en ellos desde la sesion de este repositorio |
| Politica de ramas: en este repositorio `master` es la estable sin versionar y `last-stable` la estable con etiqueta; el resto son de trabajo. En los paquetes, `master` es su estable y pueden tener las ramas que quieran. **Ninguna rama se crea sin su permiso** | escrito en las reglas 30 y 40; la guarda bloquea crear ramas (ADR 0003) |
| Proponer un orden de directorios: «siento que ese `files/*` y demas se esta enredando. Es solo un comentario» | **HECHO el 2026-09-14**: lote 0b, ADR 0006, bitacora 0003 |
| «Comitea todo» (2026-09-14, al cerrar el tramo) | hecho: `#012`→`#013`, cuatro commits hasta `56d47137` |
| Leer, en solo lectura y como fuente independiente de ideas (no para unificar), los proyectos hechos con versiones anteriores del framework que hay en la maquina | **El PROPIETARIO los nombro el 2026-09-14**, en solo lectura: (1) el geovisor de `/var/www/html/espacio-publico/espacio-publico-backend`, que es **el geovisor de su pendiente «perfeccionar geovisor»**; (2) «alguna cosita interesante» del backoffice de `/var/www/html/STC/stc-website-2026`; (3) como resuelve el log de tokens la rama `logs-personas-habilitadas-inicio-y-log-tokens` de `/var/www/html/STC/localizometro-stc`, frente a los cuatro registros planificados. En curso, con tres subagentes de solo lectura. Antes de nombrarlos:  El arquitecto se adelanto: lanzo la lectura de los 28 que encontro en `/var/www/html/` sin preguntar, y la paro el mismo dia al senalarlo el PROPIETARIO («Ni siquiera te he dicho que proyectos»). Solo uno de los cinco subagentes llego a terminar (glu-dashboard, zegu-platform y KataApp); su informe esta en el scratchpad de la sesion, fuera del repositorio |
| Informe detallado del estado antes de trabajar: plan, lo que se lleva, lo que falta, lo que son solo ideas, fases y tareas previstas | hecho: `.agents/estado/informe-2026-09-14-estado-del-proyecto.md`. Al hacerlo aparecio que el mapa heredado omitia dos trabajos de E4 (lote 2 de guardas y ventana de correo); anadidos como lotes 7b y 7c |
| «Resuelve P22 y P23 como tu prefieras» (2026-09-14) | **HECHO**. P23: ADR 0008, y la nivelación de html va en `#022`. P22: la clave constante no es la frontera; lo serio está en `GenericTokenController`, que va al lote 5b del mapa. Detalle en «Lecturas de proyectos derivados», P22 |
| ¿Que pasa con `files/` y `files/dev/`? ¿Se quedan y se documentan bien en algun lado? | Se quedan (ADR 0006): `files/` guarda los recursos para quien clona y `files/dev/` solo datos de instrumentos. Documentado en `.agents/context/02-estructura.md`, «`files/` y `files/dev/`», con una tabla de que instrumento usa cada archivo |

### Hallazgos de BC — 2026-09-14 (bitacora 0002)

Trabajo planificado, sin bloque todavia:

- **H3 · El trinquete de declaradas solo falla por exceso** (`bin/censo-sql-concatenado`, bucle
  `foreach ($declaradas as $clave)`): con menos concatenaciones vistas que el `count` declarado,
  pasa en silencio, y la holgura deja entrar nuevas gratis. Debe exigir que la cota baje. Va con
  el lote de identificadores.
- **H6 · El mecanismo de la LEY 33 no existe como comprobacion**: «nada declarado abierto en
  `.agents/context/` puede faltar en `PENDIENTES.md`».
- **Comprobacion 26, latente**: lee la cabecera solo dentro de `[NOTA DE MEDICION]`. Si la seccion
  desaparece, no mira nada e informa igual «la cabecera no nombra cifra». Debe fallar si no
  encuentra la seccion (LEY 18).
- **H7**: `files/dev/integrity-signatures.json` esta en LF con `eol=crlf` declarado. Git lo
  normaliza; `bin/normaliza-eol` lo señala.
- **H2 · El bloque «CAMBIOS INCOMPATIBLES» del CHANGELOG esta partido** (`---` entre la ruptura 8
  y la 9). Documentacion: del arquitecto.

### Lecturas de proyectos derivados — 2026-09-14 (pedidas por el PROPIETARIO, solo lectura)

Para aprender de la experiencia, **no para copiar**: el PROPIETARIO lo preciso asi.

**Hallazgos que afectan al framework actual** (comprobados en su codigo, salvo lo marcado):

- **P22 · `TokenModel` firma con una clave propia escrita en el codigo, y no con `app_key`**:
  `src/app/model/TokenModel.php:23`, `const KEY_BASE_JWT`.
  - **Corregido tras la pregunta del PROPIETARIO**: las sesiones NO la usan. Firman con
    `app_key` de `config.php` (`src/app/core/bootstrap.php:313`, `BaseToken::setSecretKey(Config::app_key())`;
    tambien `SessionTokenIsolated`). El primer aviso del arquitecto («se pueden fabricar sesiones»)
    era falso.
  - Lo que queda: los tokens genericos de `TokenModel` (`GenericTokenController`,
    `TokenController`) usan una constante del codigo, que es publica y la misma en todos los
    despliegues. Se guardan en base de datos y se comprueba que existan, lo que limita el
    impacto (**sin verificar**).
  - **Medido el 2026-09-14, al resolverla por delegación** («Resuelve P22 y P23 como tu
    prefieras»):
    - **La constante no es la frontera.** El JWT se lee siempre de la fila de la base de datos,
      nunca de la petición:
      - `TokenModel.php:136-178` usa `getRecord()`;
      - `GenericTokenController.php:79-108` busca la fila por `id`.
      Fabricar un JWT con la clave conocida no da nada. Pasar `KEY_BASE_JWT` y `KEY_JWT`
      (`'GenericTokenController'`) a `app_key` es higiene.
    - **La recuperación de contraseña es segura.** El enlace entero tiene que existir en la base
      de datos, se borra al usarse (`RecoveryPasswordController.php:280-291`) y el JWT se firma
      con la clave por defecto, que es `app_key` (`bootstrap.php:313`).
    - **Lo serio está en `GenericTokenController`:**
      - La URL lleva el `id` pasado por `BaseHashEncryption::encrypt($id, self::class)`
        (`:414`; se descifra en `:82`), que es aditivo carácter a carácter (`BaseHashEncryption.php:90-102`) y con
        una clave pública: los `id` se pueden calcular.
      - La ruta es pública: `commentary` tiene `validate_session` en falso.
      - `entryPoint()` no comprueba el TIPO de la fila y, si el JWT no verifica o ha caducado,
        la BORRA (`:198` y `:205`).
      - `BaseToken::isExpire()` devuelve `$exp` crudo cuando no hay `exp`.
      - **SOSPECHA fuerte**: un anónimo podría borrar filas de tokens de cualquier tipo,
        incluidas las recuperaciones de contraseña pendientes.
      - En el framework nadie crea tokens genéricos (`createTokenURL()` no tiene llamadores),
        pero la función viaja a cada clon.
      - Es el **lote 5b** del mapa.
  - Arreglo candidato: que usen `app_key`.
  - `config.php` esta versionado. Sus claves (`app_key`, `CronJobKey`, `apiKey`, `secretKey`)
    miden de 7 a 11 caracteres: por la longitud parecen valores de ejemplo que cada despliegue
    cambia (no se miro el contenido).
  - **Espera al PROPIETARIO.**
- **SOSPECHA · `RouteAdapter`** (`src/app/core/psr4/PiecesPHP/Core/Routing/RouteAdapter.php`,
  hacia la linea 225) extrae los parametros con `/\{[a-z|A-Z|0-9|_|-]*\}/`, que no admite dos
  puntos. No reconoce `{params:.*}`, que el framework usa en muchas rutas de estaticos. En
  `stc-website-2026` eso da un 500 en `/configurations/routes/`. **Aqui, sin verificar.**
- **SOSPECHA · Decodificar imagenes por su extension**: el recorte (`AppConfigController`,
  `Utilities::` hacia la linea 1581) elige `imagecreatefromjpeg/png/webp` por la extension. En STC,
  un `.jpg` que en realidad es WebP tumba la pagina. Va con el lote de subidas.
- **SOSPECHA · `GeoJsonManagerController::withPersonsProfiles`** lee `featuresType` de la
  peticion y arma el `where` uniendo criterios. No se verifico si el valor llega sin validar, y el
  censo de SQL da 0. Va con el lote de identificadores.

**Ideas y lecciones para la campaña y el roadmap:**

- **Geovisor** (`espacio-publico-backend`: la vista de mapa de `FileDossier`, con Mapbox GL
  3.4.0). En el cliente gana:
  - agrupa en clusteres;
  - crea marcadores solo para los puntos sueltos que estan en pantalla;
  - recibe datos sin HTML.

  El framework no agrupa, crea un marcador por punto, y el servidor pinta dos vistas HTML por
  cada punto. En el servidor el geovisor no gana: no pagina, no carga por encuadre y no tiene
  cache ni limite. Aporta tambien capas GeoJSON subidas, capas fijas en JSON, el cambio de
  satelital a vectorial que conserva las capas, reparacion de geometrias y contadores de lo
  visible. En su SQL de busqueda hay concatenacion probable (no es nuestro codigo). **Sirve para
  «perfeccionar el geovisor»**, sin fecha.
- **Log de tokens** (`localizometro-stc`, en su rama; es una propuesta, no desarrollo).
  - Aporta la idea de registrar el **ciclo de vida de cada credencial** emitida (emitida,
    usada, caducada, revocada), que el plan de cuatro registros no contempla. Tambien trae un
    productor real para el registro de acceso: acceso concedido, denegado y correo no registrado.
  - Lo que NO hay que hacer:
    - mostrar codigos OTP vigentes en claro;
    - registrar un evento de acceso a nombre del usuario 1 cuando no hay usuario;
    - escribir «codigo emitido» antes de saber si el correo salio;
    - mezclar los accesos con el registro de acciones.
  - Va al documento de los cuatro registros cuando se trabaje.
- **Backoffice** (`stc-website-2026`):
  - un modulo `Mailing` en el que la vista previa usa el mismo codigo que envia, con envio de
    prueba registrado y un escaner de correos sin catalogar. Va con el correo;
  - un cuadro de mando que solo consulta lo que el rol puede ver;
  - `addLogSafe()`, borrado suave con una sola condicion activa, consentimiento con version del
    documento aceptado, limitador por IP (su docblock admite que no es atomico), interruptor de
    captcha completo y un comparador de esquema.
  - Su nucleo es un fork antiguo: casi todo lo demas, el framework ya lo tiene mejor.

Los informes completos quedaron en el scratchpad de la sesion, fuera del repositorio.

### Hallazgos del lote 0b — 2026-09-14 (bitacora 0003)

Declarado y aparcado, sin bloque:

- `TODO.md` sigue en la raiz: es una lista del PROPIETARIO (PayU, modulos por rehacer, encuestas).
- `files/TraduccionesPublicas.json` vale `{}` y nadie lo nombra literalmente. Sin verificar si
  se carga por un nombre compuesto.
- `bin/Preview/`: su limpieza (`bin/phpstan-process-result.php`) solo borra los `*.md`. Las
  copias sin extension y las carpetas vacias no se borran nunca.
- `permissions-and-property.sh:77` busca `bin/node/copyDependencies.sh` despues de hacer `cd` a
  `bin/`, asi que nunca lo encuentra.
- Conviven los lockfiles de npm y de pnpm (ambos ignorados). Sin verificar cual es el canonico.
- `files/CliScripts/CorregirTiempoDuraciónWebm.php` podria duplicar `FixWebmDurationTask`. Sin
  verificar.
- `.agents/context/21-pruebas-y-puertas.md` esta desfasado: dice «dieciseis» comprobaciones y
  hay 26. Va con E6.

**Y un secreto, RESUELTO**: `18-siguientes-ventanas.md` llevaba en claro la contrasena de prueba
del usuario root local y su hash (bloque de `password_verify`, hacia la linea 4790). El
PROPIETARIO dijo que no afecta y que se borrara por higiene (P20): sustituidos el 2026-09-14. La
historia de git los conserva.

### Hallazgos de BD — 2026-09-14 (tramo 2026-09-14-1441)

- **Los cuatro paquetes tienen el mismo 04-desarrollo.md** en su .agents/context: el mismo
  `md5sum`.
  - El texto describe las diez suites de la carpeta unit-tests de database.
  - datastructures y html usan phpunit.xml, y geojson no tiene pruebas: para esos tres, el
    documento no es cierto.
  - Los .agents de los paquetes son del modelo anterior (ADR 0003).
  - Va con E6, o cuando el modelo de trabajo se instale en los paquetes.
- **Composer y el PHP del sistema.**
  - `/usr/bin/composer` arranca con el php del sistema, el 8.1.34.
  - Los paquetes resuelven contra 8.5 porque lo fijan en la clave config.platform de su
    composer.json.
  - Hueco en la guarda: no veía Composer lanzado a través de PHP (`php8.5 /usr/bin/composer …`).
    Cerrado con el ADR 0007.
- **El procesador de resultados de PHPStan de los paquetes iba por detrás del de piecesphp.**
  - El archivo es el mismo en los cuatro paquetes, y su trinquete no aceptaba «destapados» ni
    «murieron». Así, una bajada causada por el analizador no se podía registrar sin mentir.
  - Se nivela en `#018`, solo ese bloque.
  - El resto del archivo sigue sin comparar con el de piecesphp: 493 líneas de diff, en parte
    por finales de línea.
- **database: con phpstan 2.2.12 desaparecen tres errores sin tocar el código.**
  - Los tres son `function.alreadyNarrowedType`: `return is_string($e);` dentro de callbacks de
    array_filter, en ActiveRecord.php, líneas 709, 728 y 785.
  - Las comprobaciones redundantes siguen en el código; es el analizador el que dejó de verlas.
  - Causa en PHPStan: SIN VERIFICAR.
- **PHPStan 2.2.12 imprime un bloque nuevo**, «Instructions for interpreting errors», dirigido a
  quien lea la salida.
- **El mensaje del trinquete no distingue el motivo.** Imprime «murieron con el código borrado»
  también cuando mueren por el analizador. Es texto de piecesphp, y desde `#018` está en los
  cinco repositorios. Se arregla en una ronda de instrumental, en los cinco a la vez.
- **Corrección:** `files/dev/shared-toolchain.json` **no** es ASCII. El arquitecto lo afirmó en
  `#016` sin medirlo: la sección `analyzers` sí es ASCII, pero 16 de sus 116 líneas llevan
  tildes, rayas o comillas angulares. Lo detectó el coder en `#019`.
- **En los paquetes, los resúmenes de PHPStan se escriben en LF** aunque tienen `crlf`
  declarado. Git lo normaliza, así que es cosmético. La línea base, en CRLF, no coincide con el
  resumen byte a byte.
- **La cota del `.neon`**, que ya está en los paquetes, solo actúa si su línea base declara
  `[ENTRADAS-NEON]`, y ninguna lo declara. Queda inerte hasta que alguien la cablee.

### Hallazgos del lote 2, bloque 1 — 2026-09-14 (`#021`)

- **`metodos()` cuenta dos veces lo que va dentro de una función anónima.** Devuelve también la
  anónima, y su rango está dentro del método que la contiene.
  - En `censo-sql-identificadores`: 2 posiciones duplicadas, las dos literales (138 vistas, 136
    distintas).
  - En `censo-sql-concatenado`: 248 registros y 247 distintos, porque se repite
    `ImporterUsers.php:144`. SIN VERIFICAR si el duplicado está en REVISAR o en DESCARTADO.
  - Se arregla en el bloque 3 del lote 2, con los trinquetes. Mueve cifras registradas.
- **`familias_sin_censar` dice «`select($campos)` (194)» sin método.** Por texto hoy salen 196
  `->select(`, 65 de ellas con argumento. SIN VERIFICAR si la diferencia es de unidad o de
  árbol.
- **Los 15 controladores que pasan `custom_order` usan ASC o DESC literales**, así que normalizar
  la dirección no cambia nada de lo que funciona. Medido por el arquitecto. El censo cuenta 16
  sitios con la clave: los 15 y uno de una prueba (`UnitTest-ReadPathsSurvive.php:59`).
  `UsersExporter.php:52` tiene una `$customOrder` de otra forma, una lista `'idPadding ASC'`,
  que no llega a esa clave.

### Hallazgos del lote 2, bloque 2 — 2026-09-14 (`#023`)

- **`bin/cli unit-tests:<suite>` sale con 0 aunque la suite falle.** En la provocación de `#022`
  dio 56/58 con salida 0. `gates` lee el balance, no el código de salida (LEY 19), así que la
  puerta no se engaña. Un guion que mirase `$?` daría verde.
- **`generateOrderBy()` descarta una entrada de `custom_order` si su columna es una SUBCADENA
  de algún orden ya puesto**: `mb_strpos($order_item, $column)`. Un `custom_order` con `id` se
  pierde si la petición ordena por `user_id`. El orden por defecto no se aplica y no da error.
  No es inyección. SIN VERIFICAR si alguno de los 15 controladores combina columnas así. Sin
  lote asignado; candidato al barrido de residuos (lote 10).
- **html, 3 → 1 al nivelar:** desaparecen los dos `function.alreadyNarrowedType` de
  `Attribute.php:73` sin cambio de código. Cambiaron a la vez el analizador (2.1.42 → 2.2.12) y
  la API analizada (datastructures 3.1.0 → 4.0.0). Cuál los mata está SIN VERIFICAR. Se
  registran como «murieron» en `#024`.
