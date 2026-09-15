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
| ~~`P24`~~ | **APROBADO el 2026-09-14 tal como está el predeterminado.** El PO añadió que los archivos de Publications no pueden ser privados y que Publications debe servir de arquetipo, como en todo lo demás. Publications se hace primero y queda como el ejemplo que copian los demás y la guía de módulos. Texto original: **Qué control de acceso lleva cada carpeta de subidas.** El 20 §7 dice que el validador de cada módulo lo decide el PROPIETARIO. Tabla en «Hallazgos del lote 3, bloque 1» | 14-09 | **Predeterminado del arquitecto**: documents, organizations y news-categories, sesión activa; publications, el archivo se sirve si su publicación es visible al público (activa, en fecha y aprobada) o si hay sesión; built-in-banner, sin proteger, porque la portada lo muestra, pero sin devolver borrados; el homeImage de generic, sin proteger. Los tres UPLOAD_DIR sin archivos se retiran. Y una puerta que falle si un UPLOAD_DIR no está protegido ni declarado público con su motivo |
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
| **Guía de «parches» de seguridad para versiones viejas del framework** (2026-09-14, al saber de la inyección del lote 3a). Hay muchos despliegues viejos y sin soporte, en los que el PO puede hacer poco. Propone, por cortesía con los clientes, una guía de parches de seguridad para versiones antiguas; más adelante se verá a cuáles aplica | **IDEA, después de la MAJOR.** Límite que él mismo señala: el registro tiene la historia del framework, pero no las implementaciones de cada cliente. Material de partida: los «⚠ Corregido» de seguridad del `CHANGELOG.md`, cada uno con desde qué versión existe el defecto (sale de `git log`/`git blame`). **Precisado por el PO el mismo día**: un documento para agentes que no les obligue a cargar todo lo producido en la campaña, pero que les informe de los hallazgos de seguridad críticos que pueden resolver en sus propios proyectos. Tiene que ser extensible e independiente de las versiones, para que el PO lo lleve de un proyecto a otro. Forma que se deduce: un solo archivo autocontenido, portátil entre proyectos. Por hallazgo, **cómo detectarlo** en el código (el patrón que hay que buscar, no un número de versión), por qué es crítico, **cómo arreglarlo** y cómo comprobar que quedó arreglado. Extensible: se añade una entrada por hallazgo nuevo |
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
  la API analizada (datastructures 3.1.0 → 4.0.0). Cuál los mata está SIN VERIFICAR.
  Registrado como «murieron» en `#024` (html `8d61814`).

### Hallazgos del lote 2, bloque 3 — 2026-09-14 (`#025`)

- **Corrección del arquitecto.** La decisión D-c de `#024` decía que las seis C de
  `processFromQuery()` llegaban por `columns`. Tres llegan por `columns` (982, 996 y 1142) y
  tres por `order` (987, 997 y 1151), vía `generateOrderBy()`, que toma la columna del índice
  en `columns_order` y normaliza la dirección. Las dos vías están acotadas, y el `why` del
  registro ya nombra las dos.
- **Todo commit que añade líneas por encima de un error de PHPStan desplaza el artefacto.** Sin
  volver a ejecutar `bin/phpstan`, `PHPStanResult.*` queda rancio en silencio. Es la memoria
  «el artefacto puede quedar rancio». Nada ata el artefacto al código.
- **Al quitar las anónimas anidadas, queda el registro del método externo**, cuyo mapa de
  asignaciones incluye el cuerpo de la anónima. Los 10 del interpolado tenían su gemelo
  externo. SIN VERIFICAR si algún veredicto cambia por el ámbito en otro sitio.
- **`verify-integrity` tiene 28 comprobaciones.** Los documentos que dicen otra cifra se corrigen
  al depositar el cierre del lote 2. El `21-pruebas-y-puertas.md`, que enumera las de hace
  tiempo, sigue yendo con E6.
- **En la provocación 27, «FALLOS: 2» para un solo sitio.** El recuento suma la línea TRINQUETE
  y cada línea de sitio, igual que ya hacía la 24.

### Hallazgos del lote 3, bloque 1 — 2026-09-14 (`#027`, auditoría de subidas en solo lectura)

**⚠ Fuera del alcance de las subidas, y lo más grave:**

- **H1. INYECCIÓN SQL EN DOS RUTAS PÚBLICAS: CONFIRMADA POR LECTURA**, por el coder y después
  por el arquitecto. Sin provocar: no hay permiso de HTTP ni de base de datos.
  - Rutas: `publications-ajax-all` y `built-in-banner-ajax-all`, las dos con
    `requireLogin: false`.
  - El recorrido:
    - el parámetro `title` se valida como «escalar no vacío» y se parsea con
      `(string) $value` (`PublicationsController.php:1067-1077`;
      `BuiltInBannerController.php:806-815`);
    - `_all()` lo interpola en `UPPER(…) LIKE UPPER('%{$title}%')`
      (`PublicationsController.php:1438`; `BuiltInBannerController.php:996`, y su `PageQuery` en
      `:1038-1044`);
    - la cadena va a `new PageQuery(...)`, que hace `prepare()` y `execute()` SIN valores
      (`PageQuery.php:92-96`, `116-120` y `146-147`).
  - **Por qué ningún censo lo vio:** `$title` entra en `_all()` como parámetro, y la traza no
    cruza de método (la cota, LEY 15). `PageQuery` no es un sumidero de ningún censo. Las
    comprobaciones 24, 27 y 28 en verde no cubren esta forma.
  - Va al lote 3a del mapa (`#028`).
- **H1 bis, medido por el arquitecto antes de `#030`.**
  - **Hay una tercera vía en la misma ruta pública:** `ignoreSlugs`.
    - Su validador (`PublicationsController.php:1090-1100`) solo pide escalares no vacíos, y el
      bucle sobrescribe `$valid`, así que solo mira el ÚLTIMO elemento.
    - `_all()` lo mete entre comillas dobles en `preferSlug NOT IN ("…")`, sin escapar.
  - `category`, `status` y `featured` pasan por `(int)`: cerrados.
  - **`new PageQuery(` aparece en 14 controladores**, no solo en los cinco del reporte: Banner,
    Newsletter, NewsCategory, News, Organizations, Point, City, State, Documents, DocumentTypes,
    Categories, PublicationsCategory, Publications y Users. Todos tienen la misma forma. `#030`
    los audita todos.
- **CERRADO el 2026-09-14 en `#030`/`#031`** (bitácora 0007). Van por marcador las siete vías:
  - `title` de publications y del banner, e `ignoreSlugs` de publications, que son públicas;
  - `newsTitle` e `ignoreSlugs` de news, `name` de organizations y `search` de GeoJSON, tras
    sesión.
  Lo que sigue abierto:
  - **Los comodines `%` y `_` del LIKE**, con el lote 4.
  - **Un instrumento que vea esta forma**: el censo interpolado no toma `PageQuery` como
    sumidero y la traza no cruza de método. Hoy la cierran las pruebas de la sección 14.
    Candidato: un censo por FORMA, «un valor interpolado ENTRE COMILLAS en una cadena SQL»,
    venga de donde venga, con trinquete a 0 o declarado.
  - **H1 de `#031`:** `DocumentsController.php:1026-1028` pasa `["status" => …]` por
    `implode(' ', …)` y deja solo el valor: `/documents/all` lista también los inactivos. Es un
    fallo de lógica, no una inyección. SIN VERIFICAR por el arquitecto.
  - **H2 de `#031`:** la clave de caché de `PublicationsController::all` (`:1155-1164`) no
    incluye `ignoreSlugs` ni `random`, así que dos peticiones que solo difieren en eso comparten
    respuesta. Va en `#032`.
  - **H3 de `#031`, DECISIÓN DEL PO (P25):** `/points/all`, `/cities/all` y `/states/all` listan
    tablas enteras sin sesión (`Locations.php:295`). No son inyectables. **Predeterminado**: se
    quedan públicas, porque son datos de referencia geográfica que usan los formularios
    públicos. SIN VERIFICAR qué formularios los usan.
  - **H4 de `#031`:** la base local no tiene filas con las que una prueba por resultado
    discrimine. Una semilla de lectura lo permitiría, pero escribir en la base pide
    autorización.
- **`#032`/`#033`, 2026-09-14: cerrados H3 de `#027` y H2 y H1 de `#031`, y H10 de `#027`.**
  - Las rutas públicas de publications y banner fuerzan lo publicado sin sesión con permiso.
  - La clave de caché de publications refleja los valores efectivos. Hoy la caché está apagada
    (`ENABLE_CACHE = false`, `PublicationsController.php:109`), pero con la clave vieja, al
    activarla, un listado privilegiado se habría servido a un anónimo.
  - `protect()` crea la carpeta que falta y exige el separador al comparar.
  - Documents filtra por estado.
  Quedan dos cosas:
  - **Banner:** su listado de admin admite `$allRoles`, así que el permiso de «ver borrados»
    equivale a «cualquier sesión». Es la política del propio módulo; no se toca.
  - **Los universos de los censos no están declarados** desde hace varios bloques (retornos:
    790 al congelar, 678 hoy; los de SQL, 675 → 676 por la suite nueva). La cifra sale igual,
    pero el instrumento dice que no es comparable hasta declararlo (LEY 15). Va en `#034`.
- **`#034`/`#035`, 2026-09-14: lote 3, bloque 2 hecho (P24).**
  - Los archivos de publications se sirven sin sesión solo si la publicación es visible:
    `isVisibleToPublic()`, extraído de `singleView()`.
  - documents, organizations y news-categories piden sesión.
  - banner y generic, declarados públicos en `files/dev/upload-dirs.json`.
  - Retirados los tres `UPLOAD_DIR` muertos. Con ellos murieron 6 errores de PHPStan (744 →
    738) y 6 retornos ignorados (193 → 187).
  - La comprobación 29 está activa. Los universos de los censos, declarados.
  Lo abierto:
  - **H1 de `#035`, SIN MEDIR:** si alguna vista PÚBLICA muestra archivos de organizations,
    documents o news-categories, por ejemplo un logo. Si lo hace, desde `4ca2e99d` da 403 sin
    sesión. Se mide en `#036`.
  - **H2 de `#035`, DECISIÓN PENDIENTE (candidata a P25):** `singleView()` NO mira la aprobación
    de SystemApprovals, y el listado público sí (`_all()`, con `validateSystemApprovals`). Una
    publicación sin aprobar no sale en la lista, pero se ve por su enlace directo y sus
    archivos se sirven. `isVisibleToPublic()` copia a `singleView()` a propósito. Si la
    aprobación debe contar, cambia en los dos sitios a la vez. **Predeterminado**: se queda como
    está, anotado, hasta que el PO lo diga.
  - **H3 de `#035`:** `DocumentsMapper::folderRemove()` no tiene llamadores. Va con el lote 10.
  - **H4 de `#035`:** `protect()` corre en cada arranque, también en `bin/cli`, y crea el
    `.htaccess` que falte en `src/statics/uploads`, que git ignora.
  - **H5 de `#035`:** `UPLOAD_DIR_TMP`, `uploadTmpDir` y `uploadDirTmpURL` siguen en ocho
    controladores: se asignan y nadie los lee. Van con el lote 10.
  - **D2 de `#035`:** `files/dev/integrity-signatures.json` llevaba varias rondas sin regenerarse
    porque añadir firmas no hace fallar nada: entraron unas 30 de golpe. Es el mismo hueco de
    «el count mayor pasa en silencio», en otro instrumento.
- **`#036`/`#037`, 2026-09-14.**
  - **Lote 3 CERRADO** (bitácora 0008). H1 de `#035`, medido: ninguna ruta ni vista pública
    muestra archivos de documents, organizations ni news-categories; protegerlos no rompió nada
    visible. SIN VERIFICAR: enlaces a esos archivos guardados DENTRO de la base (contenido de
    publicaciones, correos, boletines).
  - **Lote 4, bloque 1** (bitácora 0009): 17 de 22 usos de `escapeString()`, por marcador. Lo
    abierto:
    - **⚠ El mensaje del commit `a5e5e231` dice «la funcion queda obsoleta», y NO lo está.**
      Corregido aquí, en la bitácora 0009 y en el `CHANGELOG.md`. La historia no se reescribe.
    - **Los dos sitios parados:** `OrganizationMapper.php:664-667` (etiquetas del servidor en un
      JSON literal del `SELECT`) y `DataTablesHelper.php:1324` (la búsqueda de `process()`; hace
      falta migrar sus 14 llamadores, así que el PO ve el plan antes, por la regla de los diez).
    - **`@deprecated`, al final.** Decisión del arquitecto: primero se resuelven los 5 usos. Ni
      supresiones ni subir la línea base (camino «a» del reporte).
    - **Comodines `%` y `_`** en la búsqueda del panel (`DataTablesHelper:1324` y su gemelo
      `generateHavingGroup()`). **Predeterminado: se quedan**, porque es una búsqueda de
      administración con sesión. Se revisa si el PO lo pide.
    - **`OrganizationMapper::getLogoURL()` (`:356`)** sin llamadores. Lote 10.
- **Rendimiento de los archivos protegidos** (encargo del PO, 2026-09-15). Lote 4b del mapa.
  - **El problema:** desde `95758ca3`, cada archivo de una carpeta protegida arranca el
    framework, y sin sesión los de Publications hacen además una consulta por archivo. Antes
    los servía Apache directamente.
  - **El encargo:** incluir lo que dé mejor rendimiento y sea comprensible para quien use el
    framework.
  - **El diseño, propuesto por el PO y aceptado:** el `.htaccess` de protección se genera por
    registro. La carpeta de una publicación visible no lo lleva y Apache la sirve directamente;
    la de una no visible sí, y pasa por el validador. Se regenera al crear, editar o borrar, y
    con un cronjob para lo que cambia por fecha. El validador actual sigue como red y falla
    cerrado.
  - **Por atar:**
    - la ventana del cron: una publicación que caduca sigue pública hasta la siguiente pasada;
    - una comprobación que compare la base con los `.htaccess` y los corrija;
    - solo sirve con Apache, igual que el resto del framework.
  - **Descartado por ahora:** caché HTTP de las respuestas, caché de visibilidad y X-Sendfile.
    Se reevalúan si la medición lo pide.
  - **La postura del PO sobre Publications:** es el arquetipo porque ha cubierto muchos casos
    con el tiempo. Una propuesta mejor se acepta.
  - **Revisión de `ServerStatics.php` por el arquitecto (2026-09-15), por lectura.** Van con el
    4b:
    - **⚠ CONFIRMADO: los archivos protegidos salen con `Cache-Control: public`.**
      `buildCacheHeaders()` (`:709-713`) no distingue, y `verifyFile()` solo añade
      `PiecesPHP-Protected-File` (`:557`). Una caché compartida (un proxy o una CDN) podría
      guardar un documento privado y dárselo a otro. Lo protegido debe salir `private`.
    - **CONFIRMADO: no hay ningún `Vary`.** La conversión a WebP (`:875-883`) cambia el cuerpo
      según `Accept`, y una caché intermedia podría dar WebP a quien no lo admite.
    - **`readFile()` carga el archivo entero en memoria** (`file_get_contents`, `:801`) antes de
      decidir si hace streaming. Un PDF o un vídeo grande protegido ocupa su tamaño en RAM en
      cada petición.
    - **La conversión a WebP se repite en cada petición** (`convertImageToWebP`, `:900`), con GD
      y sin guardar el resultado. Es CPU por cada imagen.
    - **El `ETag` es `sha1` de la fecha de modificación, sin ruta ni tamaño** (`:700`): dos
      archivos con la misma fecha comparten ETag. Y si no casa, la respuesta sale con
      `no-store`, así que la versión nueva tampoco se guarda en caché (`:752-756` y `:776-779`).
    - **Lo delegado cuesta un salto más**: arranque de PHP, redirección 302 y enlace simbólico
      (`:436-468`). Y el enlace se reescribe en cada petición (memoria «server-delegated escribe
      al servir»).
    - **Lo bueno:** la delegación a Apache para lo no protegido, el enlace simbólico atómico
      (T108) y el streaming cuando el archivo no cambia.
  - **⚠ HestiaCP cambia el diseño del 4b (2026-09-15).**
    - La guía del framework ya lo decía (`source-docs/project/docs/environments/content/hestiacp/index.md:277-304`):
      Nginx sirve directamente las extensiones de su lista («Proxy Static Extensions») y NO lee
      el `.htaccess`.
    - **Consecuencia:** en un servidor HestiaCP, si `jpg`, `png` o `pdf` están en esa lista, la
      protección del lote 3 (el `.htaccess` de `protect()`) no actúa: Nginx sirve el archivo
      antes. Y el diseño del «`.htaccess` por registro» tampoco serviría allí. SIN VERIFICAR
      qué extensiones trae la lista por defecto.
    - **Restricción del PO:** no modificar la configuración de Nginx ni tocar los archivos uno a
      uno, y no perder el rendimiento de Nginx.
    - **Dirección propuesta por el arquitecto: que decida DÓNDE está el archivo, no un
      `.htaccess`.**
      - Lo privado, fuera de la raíz web: Nginx no puede servir lo que no está, y lo sirve una
        ruta de PHP que valida.
      - Lo público, dentro: Nginx lo sirve a toda velocidad.
      - Lo mixto (Publications): el archivo vive en la zona privada y se expone en la raíz solo
        mientras el registro es visible.
      Depende de que la plantilla de Nginx de Hestia pase a Apache cuando el archivo no existe
      (`try_files $uri @fallback`), lo que está SIN VERIFICAR. Si no pasa, los archivos privados
      necesitan una URL que no acabe en una extensión estática.
    - **Una comprobación que el PO puede hacer en un servidor, en solo lectura:** mirar en la
      configuración de Nginx de un dominio si el bloque de extensiones estáticas tiene
      `try_files`.
  - **Decisión del PO sobre dónde vive (2026-09-15):** todo lo que salga del 4b (servir
    estáticos, archivos protegidos, delegación al servidor web) va reunido en **su propia
    carpeta dentro de `src/app/core/psr4/PiecesPHP/Core/`**. Nombre por decidir, en inglés como
    todo identificador; candidato: `Statics/`, con su espacio de nombres
    `PiecesPHP\Core\Statics`.
    - Hoy esas piezas están repartidas: `ServerStatics.php` en la raíz de `Core/` y
      `ProtectFileMiddleware.php` en `Helpers/Directories/`.
    - Moverlas cambia su espacio de nombres, así que es una ruptura para los clones: la usan los
      21 `*Routes.php` de los módulos y `protected-files.php`. Va al CHANGELOG, con
      `class_alias` para la transición si hace falta.
  - **Precisiones del PO (2026-09-15), que ajustan la dirección:**
    - **El `try_files` de Hestia existe, deducido.** Los estáticos de los módulos
      (`/statics/<módulo>/…js|css`) no existen físicamente en esa ruta y los resuelve PHP. Si
      Nginx no pasara a Apache el archivo que no encuentra, darían 404 en todos los servidores
      Hestia, y funcionan. Además, sin eso el `.htaccess` no serviría para las reescrituras.
    - **El framework es 100 % autocontenido: nada sale de `public_html`.** Se descarta sacar lo
      privado fuera de la raíz web.
      - **Nueva dirección del arquitecto: que decida el NOMBRE del archivo, no su ubicación.**
        Un archivo privado se guarda con un sufijo que Nginx no sirve (por ejemplo,
        `foto.jpg.protected`). Nginx no lo encuentra con su nombre público y pasa a Apache. Un
        `.htaccess` en `uploads` niega el acceso directo a ese sufijo, y la ruta de PHP lo sirve
        tras validar.
      - Uno visible lleva su nombre real, y Nginx lo sirve a toda velocidad.
      - Lo mixto (Publications) se renombra al cambiar de visibilidad, y con un cronjob para lo
        que cambia por fecha. La URL no cambia.
    - **Si algo tiene una carpeta especial, es la carpeta `uploads` entera**: el subsistema de
      `Core/` gobierna todo `uploads`, no carpeta a carpeta.
  - **Requisito del PO para el diseño del nombre (2026-09-15):** le gusta, con tres
    condiciones. Tiene que quedar bien documentado para las máquinas (`.agents/context/`) y para
    quien desarrolle (`source-docs/`), y tiene que ser configurable.
  - **FileManager (pregunta del PO, 2026-09-15).** Medido por el arquitecto:
    - elFinder guarda en `src/statics/filemanager` (`FileManagerController.php:236`,
      `structureOptions()`), FUERA de `uploads`. Está ignorado por git
      (`src/statics/filemanager/.gitignore`).
    - Sus rutas de gestión piden sesión (`require_login` true, `:424-460`), pero los archivos
      los sirve Apache o Nginx a cualquiera con la URL.
    - **La comprobación 29 no lo ve**, porque no es un `UPLOAD_DIR`. Es un punto ciego de su
      universo (LEY 15).
    - Su uso principal, el editor enriquecido, incrusta imágenes en contenido público. Moverlo
      rompería las URL ya guardadas dentro de la base. Por decidir con el PO: declararlo en el
      registro (y el universo de la 29 lo incluye), o llevarlo a `uploads` con la migración de
      esas URL.
    - **El PO no sabe qué decidir, y el motivo es el importante:** elFinder con CKEditor se usa
      en Publications, que es pública, pero también en noticias internas y en cualquier módulo
      que integre esa pareja. Una sola política para toda la carpeta no sirve. **P26 abierta**,
      con la propuesta del arquitecto en el chat del 2026-09-15:
      - los archivos del editor de cada registro van a la carpeta del propio registro y heredan
        su visibilidad;
      - el FileManager general, con sesión;
      - lo que ya existe, declarado, para no romper las URL guardadas.
    - **Medido para P26 (2026-09-15):**
      - Hoy el editor tiene UNA sola raíz para todos los módulos: «Documentos»,
        `statics/filemanager/documents` (`FileManagerController::fileManagerConfigurationRichEditor()`,
        `:196-225`).
      - `structureOptions()` ya acepta la carpeta base como parámetro (`$base`, `:235`).
      - La URL del conector la pone la vista (`data-route`, `file-manager-rich-editor.js:21`), y
        el archivo elegido vuelve a CKEditor por `getFileCallback` con `file.url` (`:26-29`).
      - **Viable sin romper la integración:** cada módulo pasa su contexto en la URL del conector,
        y el conector elige la raíz. **Condición de seguridad:** el contexto se valida en el
        servidor, con la lista de módulos y el permiso sobre ese registro. Si no, quien use el
        editor podría apuntar a carpetas ajenas.
      - **Compartir en público:** elFinder admite varias raíces. Una raíz «Público», anunciada así
        en su vista, junto a las privadas.
      - **SIN RESOLVER:** lo privado de elFinder choca con el diseño del sufijo. elFinder lista y
        gestiona los nombres reales, y bajo Nginx un archivo privado con extensión estática
        dentro de `public_html` se sirve si se adivina su ruta. Hay que estudiar las opciones de
        elFinder (servir por el conector, plugins de nombres) antes de prometer nada.
      - SIN VERIFICAR qué módulos integran hoy el editor: la búsqueda de sus rutas fuera de
        FileManager no dio resultados, y puede que lo haga un componente genérico.
    - **Visto por el PO en el panel (2026-09-15): el FileManager general tiene acceso a todo.**
      - Raíces: Archivos; Documentos (el editor de texto); Cargas (`uploads`: documents,
        news-categories, organizations y publications); Temporales (database-exporter-tests,
        mpdf y process_queue_locks); y Papelera.
      - Lo configuró así a propósito, y cada cosa conserva su visibilidad al servirse.
    - **Encargo del PO: estudiar el caso de los privados de `uploads` vistos desde FileManager**
      con el diseño del sufijo. A estudiar en el 4b:
      - **La vista previa.** elFinder decide el tipo por la extensión, y con `.protected` la
        perdería. Su opción de detectar el tipo por contenido (`mimeDetect`, con `finfo`) podría
        conservarla. SIN VERIFICAR.
      - **Que un renombrado desde FileManager no rompa la protección.** Si alguien renombra
        `foto.jpg.protected` a `foto.jpg`, bajo Nginx pasa a ser pública. elFinder permite
        bloquear por patrón (`attributes`: `locked` o `hidden`). SIN VERIFICAR.
      - **Si conviene mostrar el nombre sin el sufijo:** exigiría un controlador de volumen
        propio. Solo si lo anterior no basta.
  - **Los enlaces de `server-delegated` se desincronizan: no es una impresión del PO.**
    - Se crean al servir (memoria «server-delegated escribe al servir»).
    - Si el destino se mueve o se borra, quedan colgando. `verify-integrity` detecta los enlaces
      rotos del árbol servido, pero no los arregla.
    - Si un despliegue copia sin conservar los enlaces (un zip, o `rsync` sin `-l`), se vuelven
      copias que ya no siguen al original.
    - Y `createDynamicSymlink()` aparta como `.backup` un archivo real que encuentre en su sitio.
- **Decisión del PO sobre el buscador de `process()` y las pruebas (2026-09-15).** Tras la
  explicación del arquitecto (opciones A, B y C), el PO pidió aplicar las mejoras necesarias y
  probarlo todo exhaustivamente contra la aplicación local: navegador simulado, credenciales de
  prueba y creación de registros, porque la base es de prueba. Todo tiene que seguir funcionando
  como está programado, y las mejoras de rendimiento y seguridad son bienvenidas.
  - **ADR 0010** y la regla 40 §2.
  - **Se aplica la opción B** (`process()` usa la vía segura) en `#045`, con la equivalencia
    medida en los 19 listados antes de cambiar nada.
  - **P28 sigue abierta:** restringir quién edita traducciones cambia lo que hoy pueden hacer
    usuarios que no son administradores, y el PO pide que todo siga funcionando como está
    programado.
- **`#045`/`#046`, 2026-09-15: el buscador de `process()` pasa a marcador** (`48d00822`,
  `8eed02f0`).
  - **Comprobado dos veces:** 189 peticiones HTTP antes y 189 después, con sesión; el
    arquitecto repitió la comparación por su cuenta sobre las mismas salidas. Resultado: 105
    comparaciones de las búsquedas 1 a 5, sin diferencias. Solo cambia la búsqueda con barra
    invertida, en 16 listados. Ningún error 500.
  - **La barra invertida, antes:** `stripslashes()` la convertía en una búsqueda vacía y el
    buscador no filtraba nada. **Corrección del arquitecto al reporte:** el coder lo describió
    como ver «todas las filas saltándose el filtro», pero el resultado era el mismo de la
    búsqueda vacía, que ese usuario ya tiene permiso para ver. No era un salto de permisos.
  - **La barra invertida, ahora:** va por marcador, y MySQL la usa como carácter de escape del
    `LIKE`. Buscar `\` encuentra en realidad un `%` literal. Es un detalle de los comodines, que
    van con el resto del lote 4 (`%` y `_`).
  - **Queda abierto:**
    - **SystemApprovals:508.** Su `having_string` agrupa cinco criterios, cuatro de ellos
      incondicionales y de control de acceso (visibilidad de la referencia, ocultar lo propio,
      ocultar organizaciones aprobadas, filtro por organización), más `elapsedDays`, que viene
      de la petición y va concatenado. Pasarlo a marcador es rediseñar reglas de acceso:
      **se habla con el PO antes** (regla 30). Mientras tanto, `generateHaving()` y un
      `escapeString()` siguen vivos.
    - **LoginAttemptsModel:** `wasLogged = 1` y `wasLogged = 0` son criterios reales, no vacíos.
      La tabla de `#041` se equivocaba. `getAttempts()` ya se beneficia cuando su filtro queda
      vacío.
    - **Sin datos con los que discriminar:** Point, porque crear un punto pide una ciudad
      válida; Logs; y SystemApprovals con filas.
  - **Datos de prueba creados en la base local (ADR 0010):**
    - el usuario `zz-prueba-root` (id 485, tipo ROOT). Su contraseña aleatoria se borró, así que
      no se puede usar sin restablecerla;
    - un registro `zz-prueba-*` en tipos de documento, categorías, noticias, banners,
      publicaciones y documentos.
    Copia previa: `src/dumps/15-09-2026_11-13-49-AM.sql.gz`.
- **`#087`/`#088`, 2026-09-15: E3 cerrado; H-L parado con la causa medida.**
  - **E3, commiteado:** `16bd8a39` (el JS sin sujeto), `bd8ddfa7` (los SCSS), `27a6b8b4` (las dos
    tablas del esquema versionado) y `ea6e514d` (el skip de Rector).
    - 8 archivos, así que la regla de los diez no se activó.
    - Las dos tablas NO existen en la base local: no hubo DROP.
    - Navegador simulado antes y después en los dos perfiles: HTML idéntico una vez enmascarados
      los identificadores que se generan en cada carga, y los mismos 2 errores ajenos (Mapbox).
    - Documentado en el `CHANGELOG` («Eliminado — los restos del módulo de experiencias») y en
      `11-base-de-datos.md`.
  - **⚠⚠ H-R, PUNTO SERIO, verificado y DEMOSTRADO por el arquitecto. Espera al PO.**
    - `EntityMapper::castPHPToSQLTypes()` (paquete `piecesphp/database`,
      `src/Core/Database/EntityMapper.php:1678-1690`) aplica a todo campo de texto (`varchar`,
      `text`, `mediumtext`, `longtext`), al ESCRIBIR:
      `$value = stripslashes($value); return addslashes($value);`
      y al LEER (`:1780`, con `$revert`), solo `stripslashes`. Lo mismo en
      `ORM/Fields/DataProcess.php:134-156`.
    - **El INSERT y el UPDATE ya ligan valores** (`ActiveRecord::insert():212-221`, con
      `InsertSegment` y marcadores `:INSERT…`). El escape es DOBLE y sobra.
    - **Demostrado por el arquitecto** (`php` sobre las mismas dos funciones, en seco):

      | entrada | en disco | al leer |
      | --- | --- | --- |
      | `O'Brien` | `O\'Brien` | `O'Brien`, igual |
      | `C:\ruta\archivo.txt` | `C:rutaarchivo.txt` | **se pierden las barras** |
      | `patron \d+ y \w` | `patron d+ y w` | **se pierden** |

    - **Dos daños distintos:**
      1. **Pérdida de datos irreversible:** el `stripslashes` previo borra las barras invertidas
         legítimas al guardar. Afecta a rutas, expresiones regulares y fragmentos de código
         dentro de cualquier texto de cualquier mapper;
      2. **la columna guarda `O\'Brien`**, así que todo lo que lea por fuera del mapper (SQL
         crudo, exportaciones, listados que seleccionan la columna) ve la barra de más, y toda
         comparación por valor exacto falla. **H-L es un caso de esto**, no la causa.
    - **Por qué no se instruye:** es el ORM (núcleo transversal, punto serio de la regla 30) y
      vive en un paquete hermano, que solo se toca si la instrucción lo nombra. **Lo decide el
      PO.**
    - En esta base no quedan filas con el valor escapado (0 de 85 en `login_attempts`).
  - **H-L queda abierto** hasta que se decida H-R: el límite por usuario del OTP no casa con
    nombres con comilla. El límite por IP sí actúa.
  - Hallazgos que van a los residuos:
    - **H-T:** quedan tres entradas muertas más en el skip de Rector (`:85-87`), y ese skip ya no
      es idéntico en los cinco repositorios, aunque su comentario dice que lo es;
    - **H-U:** el CSS compilado de MySpace conserva las reglas muertas hasta que el PO recompile.
      Hay además un `my-organization-profile copy.css` ignorado por git;
    - **H-V:** una instalación que tenga las dos tablas las conserva: no hay migración;
    - **H-W:** los eventos `canDeletePreviousExperience` y su gemelo se quedaron sin emisor y sin
      oyente;
    - **H-X, útil para las pruebas:** el navegador simulado necesita la sesión en la CABECERA
      `JWTAuth`; con la cookie sola, el servidor sirve la página de login.
  - **Desviación declarada del coder:** repuso las claves `zz-prueba-` (escritura en la base) sin
    hacer un `db-backup` nuevo en esa ronda, apoyándose en el de `#085`. Lo declara él mismo.
- **`#085`/`#086`, 2026-09-15: la inyección de `OTPHandler` y el lote 5b, cerrados.**
  - Commits:
    - `fce8ec9c`: `getUserDataByUsername()` por marcador (`WhereSegment` + `WhereItem::isEqual`);
    - `7d4c723e`: tres más del barrido (DocumentTypes, Categories y
      `TokenModel::deleteByToken`);
    - `fa911716`: el selector opaco y el borrado solo del suyo;
    - `700d5167`: las claves derivadas de `app_key`;
    - `66305d39`: el aviso de `app_key` y `generate-app-key`;
    - `8fde8816`: la suite `generic-tokens`, 15/15, con las cuatro guardas provocadas a la vez.
  - **El barrido, con método declarado** (grep de las tres formas más el censo de interpolación,
    leyendo el origen de cada variable): 4 casos de petición, los 4 arreglados; el resto son
    nombres de tabla, idiomas del servidor o constantes.
    - `sql-placeholders` pasa de 141 a 148, con la sección 19 provocada: con la forma vieja,
      `' OR '1'='1` devolvía una fila y `zz-no-existe\` daba error 1064.
  - **El punto del censo NO se hizo**, con razón: declarar este caso pide rediseñar el
    instrumento, porque el valor entra por un parámetro y la traza no cruza de método. Lo vigila
    la suite. **Queda como mejora del censo, en los residuos.**
  - **H-J y H-K, cerrados de paso:** la URL del token era el `id` cifrado con el nombre de la
    clase, calculable por cualquiera; y el POST de `commentary` cargaba y borraba cualquier token
    por su `id`.
  - **⚠ H-L, defecto de lo que entregamos en `#083`:** `login_attempts` guarda
    `username_attempt` ESCAPADO, y `OTPRateLimiter` compara con el nombre crudo. Con nombres que
    llevan comilla o barra invertida, **el límite por usuario no casa nunca**; el de IP sí.
    - **Decisión:** se arregla al principio de la ronda 18. Primero se mide DÓNDE se escapa al
      escribir: si es un `escapeString()` heredado en el camino de escritura, sobra, porque el
      ORM ya liga los valores, y se quita ahí. Si no se puede, el limitador compara con la misma
      transformación.
  - Hallazgos que van a los residuos:
    - **H-M:** `createTokenURL()`, `TOKEN_PASSWORD_RECOVERY_CODE` y los ayudantes JWT de
      `TokenModel` no tienen llamadores;
    - **H-N:** `AttachmentPublicationMapper::existsByPublication()` es código muerto con
      interpolación;
    - **H-O:** el token de recuperación se firma con `app_key` tal cual, que en local es la de
      relleno. Lo cubre el aviso;
    - **H-P:** si `app/cache` no es escribible, el aviso del log sale en cada petición;
    - **H-Q:** cambiar `app_key` invalida sesiones y tokens. Está en el `CHANGELOG`.
- **`#083`/`#084`, 2026-09-15: H-B, H-C y el lote 5 (OTP), cerrados.**
  - Commits:
    - `0f4de0b7`: las imágenes reemplazadas van a su carpeta, y la visibilidad cubre lo
      referenciado fuera de ella;
    - `a06eaa71`: las subidas privadas se mueven directo a su nombre protegido, con
      `moveUploadedToPrivate`, porque `moveFileTo` corta la extensión a 8 caracteres;
    - `0b1113a0`: el OTP, con `OTPRateLimiter`, la configuración `otp_security` y la respuesta
      uniforme;
    - `46d2bbf8`: la suite, 23/23 y provocada.
  - Documentado en la ruptura 23 del `CHANGELOG`.
  - **⚠ GRAVE, H-D, anterior a la campaña, verificado por el arquitecto:**
    `OTPHandler::getUserDataByUsername()` (`OTPHandler.php:251`) hace
    `where("username = '{$username}' OR email = '{$username}'")` con el usuario recibido.
    - Lo alcanzan sin sesión `generate-otp`, `check-totp`, `two-factor-auth-status` y el login
      (`checkValidityOTP`, `UsersController.php:928`).
    - El censo no lo ve, porque el valor entra por un parámetro de función.
    - Se avisó al PO al móvil (2.9). **Se arregla primero en la ronda 17**, por marcador
      (`WhereSegment`), con un barrido de la misma forma en todo el sistema de usuarios.
    - El barrido del arquitecto dio un solo candidato más: `AttachmentPublicationMapper.php:533`
      (`lang = '{$lang}'`), de origen por medir.
  - Hallazgos que van a los residuos:
    - **H-E:** `extra_data` no está en el esquema declarado. Donde falte, el limitador cuenta
      todos los fallos, pero falla cerrado;
    - **H-F:** el tiempo de respuesta de `generate-otp` sigue revelando si el usuario existe,
      porque con un existente se envía un correo;
    - **H-G:** detrás de un proxy, la IP es compartida (documentado en el `CHANGELOG`);
    - **H-H:** un archivo compartido por dos publicaciones;
    - **H-I:** las filas con `user_id` NULL las ve cualquier administrador de organización.
- **`#081`/`#082`, 2026-09-15: el 4b-3, parte B, cerrado.**
  - Commits:
    - `215d91d7`: `src/.htaccess` solo texto;
    - `df18e20c`: `protect()` falla cerrado y ya no escribe el `.htaccess`;
    - `0bcd07f4`: las subidas nacen protegidas;
    - `fdde5571`: Publications, por visibilidad;
    - `254cd961`: el cron de fechas;
    - `a624e929`: `statics-protect-migrate`;
    - `d7b89b91`: elFinder bloquea `.protected`;
    - `f89c91cd`: las pruebas (34/34, provocada la guarda del orden).
  - **Migración real en local:** copia de 229 MB con sha256; simulacro, ida, vuelta e ida otra
    vez. Quedan 1.655 archivos con sufijo, 0 servibles entre pasos y el contenido intacto. La
    copia sigue en `/tmp/4b3-uploads-antes.tgz` y se borra al cerrar el tirón.
  - Documentado en la guía para desarrolladores (`source-docs/…/protected-files.md`, reescrita),
    en la ruptura 22 del `CHANGELOG` y en `10-cli-y-tareas.md`.
  - **⚠ H-B, pérdida de funcionalidad, anterior al lote:** al reemplazar la imagen de una
    publicación (`PublicationsController.php:744`), la nueva va a la RAÍZ de `publications/`,
    porque `handlerUpload` recibe la carpeta vacía.
    - Desde el lote 3 (validador sin carpeta) esas imágenes ya daban 403 sin sesión, aunque la
      publicación fuera pública.
    - **Decisión:** se arregla al principio de la ronda 16. La subida va a la carpeta de la
      publicación, y la sincronización cubre también los archivos que la publicación referencia
      fuera de su carpeta (datos existentes), sin cambiar URLs.
  - **H-C:** entre `moveTo()` y el `rename()` a `.protected`, el archivo existe milisegundos con su
    nombre público. **Decisión:** `moveTo()` directo al nombre privado. Va en la ronda 16.
  - Hallazgos que van a los residuos:
    - **H-A:** `--revert` deja público lo que ya nació privado. Documentado. Mejora posible: un
      registro de lo que renombró `--run`;
    - **H-D:** el alta llama a `SystemApprovalManager::init()`, que inserta todas las
      aprobaciones que falten y autoaprueba las de root y admin. El efecto es más ancho que la
      publicación creada;
    - **H-E:** volver a PENDING también quita la visibilidad. Es correcto.
  - **El coder volvió a compactarse sin mandar su resumen antes de seguir** (desviación 6).
    Lo declara la línea de compactación y lo manda en el reporte.
- **`#079`/`#080`, 2026-09-15: el 4b-3, partes A0 y A.**
  - Commits:
    - `18f86d32`: solo se comprime el texto;
    - `c9bac28c`: la caché WebP con umask 0002 y la purga en `clean-cache`;
    - `7b6209d2`: `Core/Statics`, con alias;
    - `2695277f`: la resolución por sufijo, `ProtectedUploads`, las políticas de carpeta y el
      `.htaccess` de `uploads`;
    - `f9681853`: la suite 25/25, provocada.
  - Parado antes de la parte B, con criterio: renombra 1.649 archivos reales.
  - **Decisiones del arquitecto para B:**
    - **D1:** `src/.htaccess` (94-126) solo comprime texto. Fuera `SetOutputFilter DEFLATE` y el
      DEFLATE de los tipos binarios. Afecta a todo lo que Apache sirve, pero sin pérdida:
      comprimir binarios no ahorra nada y anula el streaming;
    - **H1:** `protect()` deja de escribir el `.htaccess` de «reescribir todo» de cada carpeta; si
      no, la migración lo retira y el siguiente arranque lo recrea;
    - **H2:** los `handlerUpload()` que borran el archivo viejo lo buscan con
      `ProtectedUploads::resolve()`, porque el privado no está con su nombre público;
    - **H5:** las 204 carpetas huérfanas de publications (sin publicación que las nombre) se
      migran como PRIVADAS;
    - **H8:** `protect()` sin validador falla CERRADO. Hoy concede todo, y nadie lo usa así.
  - **H6, para el PO:** la carpeta `src/app/cache/statics-webp` que creó Apache en `#077`
    (www-data, 2755) no la puede borrar la CLI. Si quiere limpiarla:
    `sudo rm -r src/app/cache/statics-webp`. No es urgente: la purga nueva la vacía cuando
    los permisos lo dejen.
  - D3: la instantánea de firmas estaba rancia desde `#065` (solo altas); regenerada.
  - H7: el 2777 de `server-delegated`, de origen sin verificar. Va a los residuos.
- **`#077`/`#078`, 2026-09-15: el 4b-2, cerrado.**
  - Commits:
    - `0b10eeaa`: streaming, `Range`, caché privada con `Vary`, ETag por archivo y WebP en disco;
    - `6128eb33`: el CORS añade `Origin` al `Vary`, en vez de sustituirlo;
    - `f98110a1`: la suite `UnitTest-ServerStatics`, 28/28, provocada;
    - `076d7abb`: la tarea «Ejemplo» del cron, comentada.
  - Medido: cuerpos idénticos por sha256; 304 con el ETag nuevo; sin sesión, lo protegido sigue
    en 403.
  - **H1, decidido por el arquitecto dentro del diseño acordado:** con un navegador se
    comprimía TODO, y así el streaming y `Range` no actuaban nunca. Un PDF de 6 MB con gzip
    salía mayor y 2,8 veces más lento. **Solo se comprime el texto** (css, js, json, csv, svg,
    txt, html, xml); ni los binarios ya comprimidos ni lo desconocido. Va al principio del 4b-3.
  - **H2:** `selectCompressionAlgorithm()` prefiere gzip, al contrario de lo que dice su
    comentario. Se arregla el comentario en el 4b-3.
  - **H4:** la caché WebP no se purga, y la crea Apache con 0755, así que la CLI no puede
    borrarla. Se arregla en el 4b-3: umask como en `createDynamicSymlink()` y purga en
    `clean-cache`.
  - Hallazgos que van a los residuos:
    - **H5:** `shouldDelegateToWebServer()` usa la ruta por defecto;
    - **H6:** la mediana de los archivos pequeños sube entre 3 y 9 ms, sin aislar;
    - **H7:** `$start >= $size` es redundante;
    - **H8:** el formato del ETag cambió (va en el `CHANGELOG`).
- **`#075`/`#076`, 2026-09-15: el 4b-1, el cron, cerrado.**
  - Commits:
    - `dc0b8def`, `c4958ac9` y `c0beea4c`: el andamiaje del ADR 0014 y los documentos;
    - `2a803de7`: franjas, reintentos, ventana, bloqueo y estado;
    - `e5bca02f`: la clave que falla cerrada con `hash_equals`;
    - `efedad93`: `cronjobs-status`;
    - `0586a1b1`: la suite `UnitTest-CronJobs`, 39/39, provocada en franja, ventana, intentos,
      bloqueo y clave.
  - Documentado en `10-cli-y-tareas.md` y en la ruptura 21 del `CHANGELOG`. La guía para
    desarrolladores en `source-docs` va en el lote 9.
  - Hallazgos:
    - **H1:** la ruta HTTP devolvía la traza de la pila de una excepción. Corregido con `run()`;
    - **H2:** con el crontab que documentaba la ruta, `0 * * * *`, «Rellenar slugs pendientes»
      (00:10) no corría nunca;
    - **H4:** la tarea «Ejemplo» (`cronjobs.php:42-63`) está registrada de verdad y corre a diario
      sin hacer nada. **Decisión del arquitecto:** se deja comentada como ejemplo documentado;
      va con el 4b-2;
    - **H5:** cada corrida de la suite escribe unas 12 líneas en el log real. Va a los residuos:
      una opción para silenciar en pruebas;
    - **H6:** `weeklyOn()` con un día fuera de 0-6. Residuos;
    - **H7:** el censo de retornos no cuenta un `@unlink` marcado; sin verificar por qué.
      Residuos;
    - **H8:** `CronJobTaskAdapter` está vacío y sin usos. Residuos.
- **A-015 a A-017 (2026-09-15): los clones y las capas.** El PO acepta la propuesta:
  - **capas:**
    - A, la metodología;
    - B, desarrollar sobre PiecesPHP (`.agents/context/` 01-15 y el arquetipo);
    - C, mantener el framework (estado, tramos, bitácora, mapa, `pendientes`, los ADR de campaña,
      18/19/20, `historico/` y los censos de campaña).
  - Un manifiesto (`.agents/capas.json`) con comprobación, en el lote 9.
  - Una orden de clonado, después de la MAJOR, con la rutina de instalación. Monta el `.agents/`
    del clon con A, B y el estado vacío.
  - **Distribución:** repositorio privado más un `.zip` por versión con las capas A y B (opción
    B), porque el PO es hoy el usuario principal y sus clones fusionan desde el privado. Cuando
    haya terceros, un repositorio público de distribución generado por la misma orden (opción A).
  - Hay que actualizar `roadmap-posterior/El framework como paquete y su despliegue.md`:
    - dice que `.agents/` viaja entero, y ya no;
    - dice que el repositorio va en CRLF, falso desde el ADR 0012.
- **`#073`/`#074`, 2026-09-15: el 4c, cerrado, más la guarda y el hook.**
  - `b8728777` la guarda, `fcf5acf6` el hook a 100755 (con `git update-index --chmod=+x`,
    porque `core.fileMode` no deja ver el chmod), `2fcb1f5b` P25, `17ca919d` `canManage` con
    estados, 404 y un correo por cambio, y `1132ea87` las pruebas.
  - P25: sin sesión, 404 en la página y 403 en el archivo; aprobada, 200; con `CAN_VIEW_DRAFT`,
    200 como vista previa. Suites 60/60 y 33/33, provocadas.
  - El hook `commit-msg` ya se ejecuta en cada commit (el PO activó `core.hooksPath`).
  - Hallazgos, a los residuos:
    - **H2:** `bin/guarda-add` dejó pasar «1·4·0» (añadido 0 con previsto 1), porque los
      pendientes cuadraban. Debería parar si añadido != previsto;
    - **H3:** `singleView()` suma una visita también en la vista previa de una pendiente;
    - **H4:** el desplegable «Tipo de contenido» de Aprobaciones es global. El tipo 12 ve
      etiquetas de tipos de otras organizaciones, no registros.
- **Principio del PO (2026-09-15, sobre A-012): `AGENTS.md` y `.agents/` son el estándar;
  `CLAUDE.md` se espeja.** Aplicado en el ADR 0014.
- **A-011: `verificar.sh` comprueba `core.hooksPath`** y el modo del hook. La orden va en
  `AGENTS.md`.
- **Aviso del arquitecto de `andamiaje-arquitecto-coder` (2026-09-15), verificado aquí:**
  1. **El hook `commit-msg` no es ejecutable** (`100644` en git y `-rw-rw-r--` en disco): git lo
     ignoraría. Además, `core.hooksPath` NO está puesto, así que hoy no actúa de ninguna forma.
     Se arregla el modo en `#073`. **Activarlo es del PO** (`40-salvaguardas.md` §5):
     `git config core.hooksPath .agents/scripts/git-hooks`.
  2. **La guarda dejaba borrar la raíz de las zonas escribibles:** el repositorio, `/tmp`,
     `~/.claude/projects` y los hermanos. Comprobado con la guarda y el JSON por stdin, que da
     código 0. **Arreglado por el arquitecto** en `guardia.py`
     (`RAICES_PROTEGIDAS` y `revisar_rm`), siguiendo la plantilla. Borrar dentro sigue
     permitido.
  3. **`secure-keys/` se podía leer desde Bash** (`cat`, con `..` o tras un `cd`). **Arreglado**
     con `SECRETOS`, `_toca_secretos()` y el seguimiento de `cd` en `revisar_bash`. La
     herramienta Read la sigue negando `settings.json`.
  - `probar_guardia.py`: 198/198, con los casos nuevos y sus parejas legítimas. Antes del arreglo,
    esos casos pasaban (medido).
  - **Opción de fondo, del PO:** adoptar la plantilla completa
    (`/var/www/html/vicsen/andamiaje-arquitecto-coder/docs/adoptar.md`).
- **`#071`/`#072`, 2026-09-15: el 4c, a medias.**
  - **Cerrado:** `cf6803dd` y `cadf95c3`. El tipo 12 entra y `canManage()` aplica C3 y C5 en el
    servidor, con 404 fuera de su alcance. Probado por HTTP con 7 elementos y 5 perfiles, y con
    un correo a Mailinator. La suite queda en 53/53, provocada por C3 y por C5.
  - **D1, P25 parado:** la instrucción se contradecía con `singleView()` (163-179). Una
    publicación ACTIVE va por `isVisibleToPublic()` para todos, con sesión o sin ella.
    **Decisión del arquitecto:** la rama else de `singleView()` admite también `CAN_VIEW_DRAFT`,
    que la ve como vista previa, igual que un borrador. Sin sesión, 404.
  - **H1:** `canManage()` no exige PENDING, C1 ni C4, así que un tipo 12 puede volver a resolver
    lo ya resuelto. **Decisión:** para los usuarios limitados por C5, `canManage()` exige
    además PENDING, C1 y C4, con las mismas condiciones del listado. Para 0, 1 y 3, igual que hoy.
  - **H2:** un POST repetido reenvía el correo. **Decisión:** el correo solo se envía si el
    estado cambia.
  - **H3:** `approvalForm` con un id inexistente da 500. **Decisión:** 404.
  - **H4 (SOSPECHA):** los desplegables de `listView` salen de consultas globales. Se mide.
  - **H5:** `_allowedRoute` (652) compara ids distintos. Solo restringe. Va a los residuos.
  - **T4 (H2 de `#070`):** la doble carga era del arnés. `verify()` manda un JWT vacío si
    `localStorage` no lo tiene, `deleteSession()` recarga (`PiecesPHPSystemUserHelper.js:246`) y la
    segunda carga ya es anónima. En uso normal no pasa; pero una sesión con cookie y sin
    `localStorage` se cierra y recarga sola. Va a los residuos.
  - **El coder se compactó una vez sin mandar su resumen** (entre `#069` y `#070`). Queda
    declarado.
- **`#069`/`#070`, 2026-09-15: el 3b, cerrado.**
  - `7c9e0126`: `saveGroup` responde 410. `gulp js-vendor` compiló en local (sin versionar) y
    `translateGroup` ya está en el `.min.js`.
  - En navegador automático: 0 llamadas a `translate` y a `saveGroup`, nada guardado, la página
    no se rompe y 0 tokens. La clave de IA local es de relleno, así que solo se probó el camino de
    fallo.
  - Datos de prueba fuera. `current-translations.json` sigue igual a HEAD tras varios arranques.
  - **H2, abierto:** en el navegador automático la página se cargó DOS veces y hubo dos POST a
    `translateGroup`. La segunda dio 403 (capa 8 de `index.php`, sin sesión). Causa sin
    verificar; sospecha, la cookie de idioma en el modo headless. Se investiga en la ronda
    siguiente, solo midiendo.
- **Respuesta del PO a A-005 (2026-09-15):**
  1. Fomantic-UI, sí, **pero conservando la estética que ya hay**. La referencia es
     Publications, que usa Fomantic con retoques pequeños: breadcrumbs y una forma fija de
     botones y títulos. Todo pensado para documentarse bien.
  2. **Capas de documentación**, todas en el lote 9:
     - para agentes: existe (`.agents/context/`);
     - para desarrolladores y mantenedores, que enseñe a extender el framework: NO existe;
     - para desarrolladores e implementadores: parcial, en `source-docs/project/docs/`;
     - las guías pequeñas (Hestia en `environments/content/hestiacp/`, LAMP en
       `environments/content/lamp/`, y `performance/`): existen, hay que revisarlas. La de Hestia,
       al cerrar el 4b.
  3. **(A-007) Documentar el árbol del proyecto** para quien desarrolla. Para agentes existe
     (`02-estructura.md`); en `source-docs/` no hay nada.
- **A-008, sobre las compactaciones:** el arquitecto no mandó su resumen tras compactarse. El PO:
  no hace falta ahora; se tiene en cuenta. Queda en la regla 30: cada instrucción y cada reporte
  dicen si su sesión se compactó.
- **Directriz del PO para el backoffice (2026-09-15): Fomantic-UI primero.**
  - El backoffice usa la mayor cantidad posible de componentes y elementos estándar de
    Fomantic-UI, que es la base del front, salvo donde no aplica: los sidebars de las
    herramientas y lo demás que ya existe con su propio diseño.
  - Motivos:
    - reducir al mínimo el código de estética personalizado;
    - poder documentar bien los recursos gráficos;
    - facilitar la migración a otro framework de front cuando haga falta.
  - Se relaciona con lo que ya pidió: rehacer las vistas de LoginAttempts dentro de la
    unificación de los registros, y todas las vistas de configuración (SMTP, SEO…).
  - **Cómo se aplica:**
    - toda vista del panel nueva o rehecha se construye con componentes de Fomantic-UI;
    - el CSS propio solo va donde Fomantic no llega, y se justifica;
    - se documenta en `.agents/context/09-frontend-assets.md` y en la guía de front (lote 9,
      `16-frontend-arquitectura.md`), con el catálogo de componentes que usa el panel.
  - Las rondas que toquen vistas del panel (4c, el formulario de usuario del lote 7) la
    respetan desde ya.
- **`#067`/`#068`, 2026-09-15: el cierre del 3b, parado con criterio.**
  - **H1:** los compilados de JS no se versionan (`src/statics/core/js/.gitignore`). El
    ADR 0013 lo suponía al revés, y se le añade una fe de erratas.
    - **Decisión del arquitecto:** el 410 de `saveGroup` va en el código, se compila en local
      con `gulp js-vendor` para probar, y el `CHANGELOG` avisa de que hay que recompilar al
      actualizar.
    - Los clones ya compilan para tener el `.min.js`, y la traducción automática viene apagada
      por defecto.
  - **H2:** el compilado arrastra `6df4e815`, que borra los mensajes de cliente de fr, de, it y
    pt. Es la decisión del PO del 2026-08-29 de dejar la aplicación en es y en (ruptura 8):
    entra tal cual.
  - **H3:** `current-translations.json` está versionado y la aplicación lo reescribe en cualquier
    arranque, web o CLI, si hay algo pendiente en la base más nuevo que el JSON. Es a propósito:
    es donde el clon guarda sus traducciones para versionarlas.
    - **Decisión del arquitecto** (corrige la de `#066`, que decía que la clave de prueba se
      quedaba): la clave `zz-prueba-3b` sale de la base y el JSON vuelve a HEAD.
    - Los datos de prueba no pueden acabar en un archivo versionado de la plantilla.
  - **Respuestas del PO a A-003 (2026-09-15):**
    - gulp, como haga falta;
    - `app_key`: cambiarla es cosa del clon; si no se cambia, **opción B** (avisar, sin negarse a
      arrancar);
    - **idea del PO:** una rutina de instalación que genere `app_key` y pregunte colores,
      títulos, propietario, etc., recomendada en la documentación. Va después de la MAJOR, salvo
      que diga lo contrario.
- **`#063`-`#066`, 2026-09-15: lote 3b en el servidor.**
  - **Cerrado:** `19efdc1a`, `0bd37936`, `a6cbb1aa` y `b6e2f5f8`.
    - `translateGroup` es solo POST: el servidor traduce, valida con `acceptTranslations()` y
      guarda sin sobrescribir.
    - `saveGroup` sigue vivo en transición, pero valida y filtra lo que recibe y no sobrescribe.
    - La fuente de `configurations.js` ya usa `translateGroup`; el `.min.js` no, hasta compilar.
    - Suite `UnitTest-DynamicTranslations` 11/11, provocada. PHPStan de 737 a 735.
    - Topes: 1.400 caracteres por clave y 100 claves por grupo, el doble de lo medido en los 17
      JSON guardados. Grupo: `^[A-Za-z0-9_\\-]{1,64}$`.
  - **Decisión del PO (A-003): la compilación con gulp no necesita permiso.** Queda en el
    ADR 0013 y en `40-salvaguardas.md` §3. El 3b se cierra compilando `jsTask`, con `saveGroup`
    a 410 y la prueba en navegador.
  - Hallazgos:
    - **H1:** la clave de OpenAI en local es un marcador falso, así que la prueba con la IA real
      no se puede hacer aquí;
    - **H2:** `add-dynamic-translations.php` solo vuelca la base al JSON si la fecha de la base
      es MAYOR, al segundo. Dos guardados en el mismo segundo dejan el segundo pendiente.
      Anterior al lote. Va a los residuos;
    - **D1:** `src/app/lang/dynamic-translations/current-translations.json` está versionado y la
      aplicación lo reescribe al volcar. Decisión del arquitecto: se restaura desde HEAD y se
      declara volátil en `files/dev/volatile-state.json`.
  - **Pregunta al PO (A-003 §3): `app_key`.** Las opciones son A (falla cerrada fuera de local,
    `secure-keys` y generador), B (solo aviso) o C (solo documentarlo). El 5b espera.
- **`#057`/`#058`, 2026-09-15: `/users/all/` sin el hash.**
  - **Cerrado:** `445713f9`. Con root y con un usuario general, 30→0 hashes.
    - **La fuga estaba confirmada por HTTP:** antes, el usuario general
      `zz-prueba-general-sinorg` recibía los hashes de los 15 usuarios, en `elements` y en
      `parsedElements`.
    - El resto de la respuesta es idéntico.
    - Prueba de rechazo en `UnitTest-AccessGuards` [9/9].
  - **El censo del PASO 4 no encuentra otra ruta que mande contraseñas.** Revisó:
    - el login, que quita la contraseña en `humanReadable()`;
    - los `SELECT *` de las exportaciones, que solo escriben en `src/dumps`, y ese directorio da
      403;
    - `EntityMapper::jsonSerialize()`, que nadie usa con un usuario;
    - `UserDataPackage::$password`, que es protegida.

    Sin medir por HTTP: las acciones GET de la API de publicaciones y noticias, que por lectura
    no devuelven usuarios.
  - **H1 de `#058`: el login escribe la organización.** Un usuario sin organización pasa a
    `organization = -10` al entrar (visto en 497: NULL → -10). Explica el H6 de `#048`. Es una
    escritura en el camino del login. Se estudia en los residuos: ¿es intencionado?
  - **H3 de `#058`:** cada corrida de `gates` deja un volcado nuevo en `src/dumps`. SIN VERIFICAR
    qué suite. Residuos.
- **`#055`/`#056`, 2026-09-15: el hash fuera de `fieldsToSelect()`.**
  - **Cerrado:** `ae869246`. Los informes de accesos pasan de 6 a 0 hashes y de 9 a 0, y
    `data`, `recordsTotal` y `recordsFiltered` no cambian.
    - Censo: 44 accesos a `password`, analizados por tokens; ninguno lo lee de una fila de
      `fieldsToSelect()`.
    - El login (`UsersController.php:929`), el cambio de contraseña (1636) y el 2FA
      (`UserDataPackage.php:218`) cargan el mapper completo.
    - Prueba de rechazo en `UnitTest-AccessGuards` [8/8], con discriminante.
  - **⚠ GRAVE, H1 de `#056`:** `/users/all/` (`users-ajax-all`, `UsersController::_all()`
    1852-1890) arma `SELECT pcsphp_users.*` y lo devuelve paginado con `PageQuery`: 20 hashes
    en la respuesta.
    - La ruta pide sesión y admite `$allRoles` (`UsersController.php:2006-2014`, verificado por
      el arquitecto). Cualquier usuario con sesión obtiene los hashes y los correos de todos.
    - No tiene consumidores en el repositorio.
    - Se arregla en `#057`: deja de devolver `password`.
  - **Pregunta de producto, con predeterminado:** ¿quién debe poder pedir `/users/all/`?
    - Hoy puede cualquiera con sesión, y aun sin el hash devuelve el correo y los datos de todos
      los usuarios.
    - **Predeterminado:** se deja con los mismos roles, para no romper a un clon que la use, y se
      documenta. El arquitecto recomienda limitarla a la administración (tipos 0 y 1) en la
      MAJOR, como cambio incompatible.
  - **⚠ Hallazgo del arquitecto (verificado por lectura): la ruta HTTP del cron falla abierta.**
    - `api-keys.php` sobrescribe siempre `CronJobKey` con `getKeyFromSecureKeys('cronjob')`, que
      devuelve `''` si el archivo no existe o está vacío (`AppHelpers.php`).
    - `APIController::cronJobs()` (1504-1507) compara con `===` la cabecera, que ausente vale
      `''`.
    - En un clon sin `secure-keys/cronjob`, **cualquiera ejecuta el cron sin clave**, respaldo de
      la base incluido. En esta instalación el archivo existe; no se leyó.
    - Se arregla en el 4b: fallar cerrado y `hash_equals`.
  - Otros hallazgos de `#056`:
    - H2: `time_on_platform` cambia sola, aunque `volatile-state.json` la tiene como retirada;
    - H3: `/admin/my-organization-profile/` sin parámetro da 500;
    - H4: `/admin/reports-access/` da 404 con root;
    - H5, de proceso: un `bin/…` relativo en segundo plano falló en silencio con rc 127.

    Van a los residuos (lote 10).
- **`#053`/`#054`, 2026-09-15: LoginAttempts y la medición de H1.**
  - Cerrado: LoginAttempts va por marcador (`7a8d0ca6`), y la prueba rota de `0bff44c4` está
    arreglada (`d9b5f3a8`). `gates` da 26/0 sobre el estado final.
  - **⚠ GRAVE, H1 de `#054`:** los informes de accesos (`admin/reports-access/logged/` y
    `not-logged/`) mandaban al navegador, en `rawData`, el hash bcrypt de la contraseña de
    cada usuario listado (15 de 15 filas con esa forma, contadas sin imprimirlas).
    - La causa: `UsersModel::fieldsToSelect()` (`UsersModel.php:912`) selecciona todas las
      columnas de `getFields()`, contraseña incluida.
    - **Decisión del arquitecto:** se arregla en la raíz. `fieldsToSelect()` deja de
      seleccionar `password`, con censo de consumidores y parada si alguno lo lee. Se instruye
      en `#055`, junto con un barrido de todas las respuestas JSON en busca de hashes.
    - Por lectura, ningún consumidor usa ese hash:
      - la línea 818 recarga al usuario por su id;
      - `getAllUsers()` devuelve filas;
      - el inicio de sesión no pasa por ahí.
    - Se avisó al PO con una notificación, según 2.9.
  - **H2, que se habla con el PO porque toca el núcleo transversal:** los 21 listados de
    `DataTablesHelper` mandan `SQL_*` y `rawData`, y `rawData` lleva datos personales o de
    negocio (el email del newsletter, la IP de los intentos, y el teléfono, la dirección, el NIT
    y los correos de las organizaciones).
    - `SQL_*` no tiene ningún consumidor.
    - `rawData` tiene 5: las vistas de tarjetas, que usan `dataTablesServerProccesingOnCards()`
      en `helpers.js:1047`, y cuyos controladores lo reescriben con HTML.
    - Opciones medidas:
      1. quitar `SQL_*` fuera de `is_local()`;
      2. filtrar `rawData` por una lista blanca de columnas en cada mapper;
      3. mandar `rawData` solo en los listados de tarjetas.
    - **Predeterminado del arquitecto:** la 1 ya, porque no tiene consumidores, y la 3 con una
      opción explícita en `process()` que activen solo los listados de tarjetas. Espera al PO.
  - H4: `$config['developer']` es un texto de relleno, no un interruptor de entorno. El que
    existe es `is_local()` (`Utilities.php:599`).
- **Respuestas del PO a la batería para 20 rondas sin él (2026-09-15, durante `#051`):**
  1. **2.1, sí.** Los lotes del núcleo con diseño ya acordado (3b, 4b y 5b) se ejecutan sin
     volver a consultar. El trabajo se detiene solo si aparece algo que cambie ese diseño.
  2. **2.2, sí al diseño del 4b**: el sufijo `.protected`, `Core/Statics/` con alias de
     transición, cabeceras de caché privadas, `Vary`, streaming, rangos y P26 en FileManager.
     - **Además, el cron del sistema** (`cronjob.php`):
       - tiene que estar bien documentado para quien desarrolla;
       - tiene que llevar reintentos y ventanas de recuperación. Hoy, si una tarea programada a
         las 12:00 falla, no se recupera a las 12:01 aunque el crontab corra cada minuto.
     - El diseño lo decide el arquitecto, y el PO lo acepta de antemano.
  3. **2.3, OTP:** el bloqueo tras N intentos por usuario e IP y la respuesta uniforme, **los
     dos configurables**.
  4. **2.4, sí a los tokens genéricos.** Los enlaces ya emitidos dejan de valer.
  5. **2.5, sí a E3.** Si se pasa de diez archivos, el plan se enseña al final del tirón,
     preparado pero sin commitear.
  6. **2.6, sí:** muere el creador de avatares y `see-more` se restaura.
  7. **2.7, sí a Mailpit o MailHog**, siempre que sea local, seguro y no pida registrarse en
     nada.
     - El arquitecto propone Mailpit porque, por lo que sabe, tiene licencia MIT, es un solo
       binario, no pide cuenta y escucha solo en `localhost`. **SIN VERIFICAR:** se comprueba en
       su repositorio oficial al instruir 7c, y si algo no cuadra, se mira MailHog.
     - Irá en `/tmp` o en el proyecto, sin instalación global ni servicio del sistema, y
       arrancado solo mientras duren las pruebas.
     - Las pruebas le indican al Mailer el SMTP de Mailpit en tiempo de ejecución, sin tocar la
       configuración SMTP guardada. Lleva su propio ADR.
  8. **2.8 a, P25 cambia: una publicación SIN APROBAR no debe verse**, ni en su página ni en sus
     archivos, cuando SystemApprovals está activo.
  9. **2.8 b, Locations:** lo que el arquitecto considere óptimo, documentado para quien
     desarrolla. Predeterminado: los listados de puntos, ciudades y estados se quedan públicos,
     porque son datos de referencia de los formularios públicos. Lo que devuelven se limita a
     los campos que esos formularios necesitan. Se mide al instruirlo.
  10. **Idea del PO (2.8):** que el requisito de aprobación se pueda encender o apagar por
      módulo. El código lo deja preparado y el cliente decide. Hoy hay un interruptor global,
      `SystemApprovalsRoutes::ENABLE`. **Predeterminado:** amplía una capacidad, así que va
      después de la MAJOR, salvo que el PO diga lo contrario.
  11. **2.9, entendido.** El trabajo se detiene solo en estos casos:
      - algo cambia un diseño acordado;
      - se pierde funcionalidad sin remedio;
      - aparece un hallazgo grave (se le avisa y se sigue);
      - surge una decisión de producto nueva;
      - se llega a 20 rondas.
  12. **Mensajes con identificador:** cada mensaje del arquitecto al PO lleva `A-NNN` en su
      primera línea, para que pueda citarlo. Regla 30, «El PO, en sus palabras».
- **Dudas del PO del 2026-09-15, para el final del tirón:**
  - **Finales de línea.** Pregunta por qué hay tantos problemas y cómo se corrige su
    `.editorconfig`. La respuesta está en el chat (A-001 §3). Resumen:
    - la causa es la política CRLF en una máquina Linux: el índice guarda LF, la copia de
      trabajo CRLF, y cada herramienta que escribe LF deja archivos mezclados;
    - la propuesta es pasar los cinco repositorios a LF. No produce diff de contenido, porque el
      índice ya está en LF. **Lo decide él**: es su configuración y afecta a los cinco
      repositorios.
    - Defectos del `.editorconfig`, medidos por lectura:
      - `[{yaml,neon}]` casa con archivos llamados `yaml` o `neon`, no con sus extensiones;
        debería ser `[*.{yaml,neon}]`;
      - el comentario de `[*]` dice «Unix-style newlines» y declara CRLF.
  - **Formato del código.** El PO formatea con VS Code (`.vscode/settings.json`):
    - PHP, con `kokororin.vscode-phpfmt` (PSR-2 y siete pasadas);
    - PHP con HTML, a mano, con «Format HTML in PHP» (`rifi2k.format-html-in-php`);
    - JS, al guardar, con el formateador de VS Code y sin punto y coma;
    - SCSS y CSS, con `michelemelluso.code-beautifier`.
    Los agentes escriben sin pasar por esos formateadores. **Encargo para el final:** dar un
    modo de formatear SOLO los archivos que tocó la campaña, en commits `style:` aparte, sin
    mezclarlos con lógica. Si esas extensiones se pueden reproducir por línea de órdenes está
    SIN VERIFICAR; se estudia al llegar.
- **Respuestas del PO del 2026-09-15 (durante `#049`):**
  - **Soporte legado de `having_string`: se mantiene.**
    - `process()` lo sigue aceptando para los clones, y para ellos el buscador va por
      `generateHaving()` y `escapeString()`, solo en ese camino.
    - Se documenta como legado en el docblock de `process()` y de `escapeString()` y en el
      `CHANGELOG.md`, con la guía para pasar a `having_segment`.
    - Sin `@deprecated`, que sumaría un error de PHPStan por la llamada interna.
  - **Los administradores de organización (tipo 12) pueden entrar a Aprobaciones y administrar
    lo que les compete.** Trabajo nuevo:
    - la ruta admite el tipo 12, y su listado queda limitado a su organización (C5 activo);
    - `approvalAction` y el resto de acciones se limitan en el SERVIDOR a su organización;
    - C3 pasa a actuar para los tipos que no se autoaprueban.
    Se mide antes qué rutas y acciones tiene el módulo y qué correos envían (ADR 0011).
  - **ADR 0011:** el correo real solo va a `@mailinator.com`, y se informa al PO de las
    direcciones.
  - **Comentarios del PO, no trabajo inmediato:**
    - **Las vistas de LoginAttempts se rehacen desde cero**, bonitas, con la estética del resto
      y dentro de la unificación de los registros (`roadmap-posterior/`, «Los registros»).
    - **Se rehacen todas las vistas de configuración** (SMTP, SEO, etc.). **SMTP tiene que
      poder probarse** desde su vista.
    - **Idea:** un «Mailinator propio» algún día.
- **`#049`/`#050`, 2026-09-15: SystemApprovals, fase 2.**
  - **SystemApprovals va por marcador y es equivalente** (`0bff44c4`): 50 de 50 peticiones
    HTTP y 72 de 72 casos de la sonda contra el controlador real.
  - **LoginAttempts está hecho, medido (140/140) y sin commitear.** La foto de `#045` quedó
    vieja por los datos de `#047`. El arquitecto acepta la foto nueva,
    `/tmp/process-antes-049`, tomada con el código sin cambiar: la diferencia es de datos, no
    de código. Se aplica en `#051`.
  - **⚠ H1 de `#050`:** la respuesta JSON de los listados de `process()` lleva al navegador el
    SQL ejecutado (`SQL_MAIN_EXECUTED`, `SQL_FILTER_COUNT_EXECUTED` y
    `SQL_TOTAL_COUNT_EXECUTED`) y `rawData`, las filas crudas. Es una fuga de información si
    ocurre fuera de local. Se mide en `#051` si depende de un modo de depuración. Es núcleo
    transversal: se habla con el PO antes de cambiarlo.
  - **Otros hallazgos:**
    - H2: `AllProfilesController.php:169` pasa `having_string` a `processFromQuery()`, que no
      tiene `having_segment`;
    - H3: `LoginAttemptsModel::all()` usa `having($cadena)` con la organización de la sesión;
    - H4: el `where_string` de LoginAttempts lleva la organización de la sesión;
    - H8: el recuento de `sql-concat-declared.json` cita líneas viejas.
    Todos son valores del servidor o de la sesión; van con los residuos del lote 4.
- **`#047`/`#048`, 2026-09-15: SystemApprovals, fase 1.**
  - **La propuesta de traducción a marcador de sus cinco criterios es equivalente en 72 de 72
    casos**: seis usuarios reales, tres valores de `elapsedDays` y cuatro búsquedas, por una
    sonda que ejecuta las dos formas.
  - **Decisiones del arquitecto:**
    - C1 se agrupa explícitamente, `(A IS NULL OR A = 1) AND …`, conservando el `IS NULL`. Hoy,
      sin paréntesis, se lee `A IS NULL OR (A = 1 AND todo lo demás)`, pero `A` nunca es NULL
      por su `IF()`;
    - C3 compara con `(int) $currentUserType`;
    - LoginAttempts (`wasLogged = 1`, `wasLogged = 0` y el filtro por organización de
      `getAttempts()`) también pasa a `having_segment`, para que `generateHaving()` pueda
      retirarse. Todo va en `#049`, la fase 2.
  - **Pregunta de producto para el PO, sin prisa:** C3 y C5 (ocultar lo propio a quien no se
    autoaprueba; limitar a los administradores de organización a su organización) nunca actúan
    por HTTP. La ruta solo admite los tipos 0, 1 y 3, que lo ven todo. ¿Deberían entrar a esa
    pantalla los administradores de organización (tipo 12)? **Predeterminado:** se queda como
    está.
  - **Datos de prueba:**
    - usuarios `zz-prueba-*` del 495 al 501;
    - organizaciones del 1 al 4;
    - publicaciones del 153 al 156;
    - aprobaciones del 175 al 189.
    Copia previa: `src/dumps/15-09-2026_11-55-24-AM.sql.gz`.
    - `approvalAction` no se usó, porque envía un correo real. Los estados se fijaron por el
      mapper.
- **⚠ H1 de `#041`: TRADUCCIONES DINÁMICAS. Control de acceso roto y XSS almacenado.
  CONFIRMADO POR LECTURA, SIN PROVOCAR** (verificado por el coder y por el arquitecto).
  - **Quién puede:** CUALQUIER usuario con sesión, de cualquier rol. La ruta es
    `api-admin-translations-actions` con `actionType=saveGroup`, y su lista de roles es
    `$translations = $allRoles` (`APIController.php:1611`).
  - **Qué puede:** `saveGroup` (`:748-850`) acepta cualquier grupo (`saveGroup`), cualquier
    idioma (`to`) y cualquier texto (`text`, un JSON clave → valor). Lo mezcla con lo existente
    y lo guarda sin lista blanca ni limpieza. El único control es `requestIsSameDomain()`, que
    mira Origin y Referer y no autoriza nada.
  - **Por qué es grave:** `Config::i18n()` devuelve el texto guardado tal cual
    (`Config.php`, `$str = $groupData[$message]`), y las vistas lo imprimen sin escapar
    (`<?= __(…) ?>`, 1.371 veces en `src/app`). Un usuario del rol más bajo puede meter HTML o
    JavaScript en cualquier texto de la interfaz, y lo ejecuta todo el que lo vea,
    administradores incluidos.
  - **La inyección SQL por esta vía ya quedó cerrada** en `c250c2ee` (literales hexadecimales).
    El control de acceso y el XSS siguen abiertos.
  - **Por qué admite todos los roles (verificado el 2026-09-15, tras la pregunta del PO):** es
    la función de traducción automática de `src/statics/core/js/configurations.js`
    (`:1440-1530`).
    - Cuando alguien navega en un idioma que no es el predeterminado, el NAVEGADOR reúne los
      textos que faltan, pide la traducción a la IA y guarda el resultado por grupo con
      `core/api/translations/saveGroup` (`:1501-1507`).
    - Por eso cualquier rol puede guardar: la traducción la persiste el navegador de quien
      navega. **Restringirlo a administración rompería la función**, así que el predeterminado
      anterior no vale.
    - **El agujero de fondo:** el servidor guarda el texto que le manda el navegador, sin
      comprobar que sea la traducción de un texto que existe.
    - **Diseño propuesto por el arquitecto (se habla con el PO: núcleo transversal):**
      - el servidor acepta solo claves que existen en el grupo de origen, en idiomas permitidos
        y en grupos reales;
      - y, mejor todavía, traduce él mismo: el navegador solo pide «traduce el grupo X al idioma
        Y», y el servidor llama a la IA y guarda. Así nadie puede inyectar un texto propio. El
        HTML de las traducciones se conserva, porque viene de la IA sobre el texto original.
  - **✔ P28 RESUELTA por el PO (2026-09-15): opción B.**
    - El servidor traduce y guarda en una sola petición. El navegador solo pide las claves que
      faltan de un grupo; el servidor saca los textos originales de sus archivos de idioma,
      llama a la IA, comprueba que vuelvan esas mismas claves y guarda. `saveGroup` deja de
      aceptar texto del navegador.
    - El HTML se conserva, porque la IA traduce el original con sus etiquetas.
    - Sin límite de uso de la IA por ahora: el PO está centrado en la MAJOR.
    - Es el lote 3b, con pruebas en el navegador simulado para cada idioma.
  - **P28 al PO (historia):** ¿quién debe poder editar traducciones?
    - **Corrección del PO (2026-09-15):** `__()` tiene que poder generar HTML. Verificado por el
      arquitecto:
      - 15 valores de los archivos de idioma llevan etiquetas a propósito, como encabezados
        (`<h1>¡Hola!</h1>`) o iconos (`<i class="globe icon"></i>`);
      - existen traducciones en archivos `.html` (`src/app/lang/files/usersProblems/PROBLEMS_LIST-{es,en}.html`).
      Quitar el HTML de lo guardado rompería usos legítimos. **La propuesta de `strip_tags` del
      arquitecto era un error**: la hizo sin comprobar ese uso.
    - **Predeterminado corregido:** el problema no es el HTML, sino QUIÉN lo escribe. Solo los
      roles de administración (root y admin) pueden guardar traducciones, con una lista blanca
      de grupos y de idiomas (los configurados). El HTML se sigue permitiendo para ellos, como
      en un gestor de contenido.
    - **Filtrar el HTML con una lista de etiquetas permitidas** (por ejemplo, con HTMLPurifier)
      sería una dependencia nueva: la decide el PO, y no entra por defecto.
    - Es el lote 3b del mapa: urgente, antes del 4b.
- **`#040`/`#041`, 2026-09-15.**
  - **Hecho:** `sqlStringLiteral()` y las 11 etiquetas de seis mappers (`c250c2ee`).
    sql-placeholders pasa a 121/121. PHPStan pasa a 737: muere el `addslashes()` de
    `UsersModel`. Solo queda un `escapeString()`, en `DataTablesHelper::generateHaving()`.
  - **El plan de `process()` (T3), medido por el coder:** 21 llamadas en 18 archivos. **El
    buscador de DataTables va por `generateHaving()`, con `escapeString()`, en 19 de 21.** Solo
    Organizations y Publications pasan `having_segment`, y `process()` les une el grupo de
    búsqueda por marcador (`DataTablesHelper.php:312-327`). El eje del `WHERE` es otro asunto,
    y ahí los valores son del servidor o están validados (lo vigila el censo concatenado).
    - **El coder propuso** tres lotes (A con 6 archivos, B con 4 y C con 8) que migran cada
      llamador.
    - **Contrapropuesta del arquitecto, más pequeña:** cuando el llamador no pasa un
      `having_string` con contenido, `process()` crea él mismo el `HavingSegment` y le une el
      grupo de búsqueda: el buscador pasa a marcador en todos esos llamadores tocando UN
      archivo. Solo migran los que pasan un `having_string` con contenido:
      SystemApprovals:508 (`elapsedDays`, de la petición y concatenado: H3 de `#041`) y los que
      lo pasan vacío o muerto (LoginAttempts ×3, Banner, NewsCategory y News).
      `generateHaving()` y su `escapeString()` mueren cuando no quede ninguno. Pendiente de
      verificar que `generateHavingGroup()` da el mismo resultado que `generateHaving()` (la
      sección 11 de la suite prueba el AND frente al OR).
  - **El estudio del 4b (T4), verificado en el vendor por el coder:**
    - elFinder detecta el tipo por el contenido (`finfo`) antes que por la extensión, así que
      `foto.jpg.protected` conserva la vista previa;
    - `attributes` con `locked` por patrón impide renombrar o quitar el sufijo;
    - con la opción `URL` del volumen vacía, sirve por el conector y pasa por PHP;
    - las raíces se calculan en el controlador en cada petición, así que P26 se valida allí sin
      tocar el vendor.
    - **`ServerStatics`:** `Cache-Control: private` va en `verifyFile()` cuando `$access ===
      true` (`:544-558`). Hace falta `Vary` por `Accept` y `Accept-Encoding`. El streaming real
      obliga a decidir por extensión ANTES de leer. Y no hay soporte de `Range`: sin él, un
      vídeo o un PDF protegidos no permiten saltar a una posición.
    - **Bosquejo de `Core/Statics/`:** `RangeRequest`, `RangeAwareFileStream`,
      `StaticCacheDirectives` y `ProtectedFileResponder`, con `ServerStatics` como el que decide
      si delega.
- **Criterio del PO sobre el código muerto de andamiaje** (2026-09-15, a propósito de `4ca2e99d`,
  que retiró `handlerUpload()` y `folderRemove()` de DocumentTypes, Categories y
  SystemApprovals): el andamiaje sin uso se retira cuando lo que enseña ya se puede deducir del
  módulo de referencia (Publications). Solo se conserva si aporta algo que no esté allí.
- **H2. El mismo patrón tras sesión**, verificado en el código por el arquitecto:
  - `NewsController.php:1225` (`newsTitle`), en `news-admin-ajax-all`;
  - `OrganizationsController.php:1350` (`name`), en `organizations-admin-ajax-all`;
  - `GeoJsonManagerController.php:143-144` y `250-251` (`search`, que va a
    `->having($havingString)`), en `geojson-manager-admin-contents-geojson-features`.
- **H3. Exposición por estado en rutas públicas:**
  - `publications-ajax-all` acepta `status=ANY/0/2` sin mirar el usuario y devuelve borradores
    y borradas;
  - `built-in-banner-ajax-all` acepta `status=ANY/0` y devuelve banners borrados.
  SIN MEDIR qué consumidores públicos usan ese parámetro.

**Subidas (la tabla completa está en el reporte de `#027`):**

| módulo | qué guarda | nombre en disco | quién emite la URL | cómo se sirve hoy | sensibilidad |
| :-- | :-- | :-- | :-- | :-- | :-- |
| documents | cualquier archivo (`TYPE_ANY`) y una imagen | carpeta `uniqid`, nombre ORIGINAL | solo rutas con sesión | Apache directo | PRIVADA |
| organizations | RUT y logo, cualquier archivo | carpeta `uniqid`, nombre ORIGINAL | solo rutas con sesión | Apache directo | PRIVADA |
| news-categories | icono (imágenes, SVG incluido) | carpeta `uniqid`, archivo `random_bytes` | solo rutas con sesión | Apache directo | PRIVADA |
| helpers-system/generic | `homeImage` por idioma | `uniqid` | solo el formulario del admin | Apache directo | PRIVADA aquí; en los clones, SIN VERIFICAR |
| publications | tres imágenes y adjuntos (pdf, doc, xls) | carpeta `uniqid`, nombre ORIGINAL | la zona pública y el admin | `protect()`, pero el validador devuelve `true` | MIXTA: depende de estado, fechas y aprobación |
| built-in-banner | dos imágenes | carpeta `uniqid`, nombre ORIGINAL | la portada (pública) | Apache directo | PÚBLICA, pero devuelve borrados (H3) |
| document-types · categories · system-approval | nada: `handlerUpload()` sin llamadores | — | — | — | andamiaje muerto |

- **H4.** Los PRIVADOS se sirven por Apache directo, y publications está protegido solo de
  nombre.
- **H5.** Los nombres se pueden adivinar: `uniqid` va por tiempo y el nombre original es
  predecible.
- **H6. Tipos.**
  - `TYPE_ANY` en documents y organizations.
  - SVG servido desde el mismo origen en banner, news-categories, publications y generic.
  - `src/.htaccess:59` bloquea solo 8 extensiones. SIN VERIFICAR si `.phtml` se ejecuta.
- **H7.** Ningún `UPLOAD_DIR_TMP` se escribe ni se limpia: es configuración muerta.
- **H8. Huérfanos.**
  - Al borrar, ningún módulo borra su archivo, que sigue servible.
  - Publications no deja borrar un adjunto.
  - Generic borra el archivo anterior antes de persistir el nuevo.
- **H9.** Andamiaje muerto: `handlerUpload()` sin llamadores en document-types, categories,
  system-approval y generic.
- **H10. `ProtectFileMiddleware`.**
  - `protect()` no registra nada si la carpeta no existe al arrancar.
  - `isProtected()` y `validateAccess()` casan por PREFIJO sin separador: `…/publications`
    cubre también `…/publications-x`.
- **H11.** El tamaño por defecto compara cadenas de `php.ini` (`FileValidator.php:399-404`): un
  `1G` se lee como 1 MB. Por lectura.
- **H12.** Traídos por subagentes, SIN VERIFICAR:
  - `UploadedFileAdapter::validate` sin la rama final (un código de error desconocido deja el
    archivo como válido);
  - `unlink()` sobre un directorio en documents.
