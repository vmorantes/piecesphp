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
| Proponer un orden de directorios: «siento que ese `files/*` y demas se esta enredando. Es solo un comentario» | **ACEPTADA el 2026-09-14**, junto con eliminar los builds innecesarios. Se hace al terminar el acuerdo: lote 0b del mapa. Es estructural, con su ADR. La propuesta esta en `.agents/estado/tramos/2026-09-14-1105-traspaso-y-andamiaje.md` |

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

**Y un secreto**: `18-siguientes-ventanas.md` lleva en claro la contrasena de prueba del usuario
root local y su hash (bloque de `password_verify`, hacia la linea 4790). Espera al PROPIETARIO:
ver `.agents/estado/AHORA.md`.
