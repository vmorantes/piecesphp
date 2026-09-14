# PENDIENTES — semilla

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
| `P15` | `tags.txt` en la raiz | 02-09 | borrarlo |
| `P15` | `source-docs/…/vps/index.md`, modificado sin dueno | bloque AN | sin predeterminado: solo el PROPIETARIO sabe que cambio |
| `P16` | **BD** — nivelar los analizadores de los 4 paquetes | 02-09 | despues de AZ |
| — | Los 9 selectores: DOCUMENTAR, decidido el 30-08, **sin lote asignado** | 30-08 | entra en E6 |
| — | El frances: `profiles-translation-config.js`, `dynamic-translations/fr/`, carpetas `de/it/pt` | — | «Del PROPIETARIO» en §7 |
| — | El rol 50 con nombre `null` (`roles.php:112`) | — | — |
| — | La unificacion de `allowedRoute` / `_allowedRoute` / `routeName` «por abstraccion o por unificacion de estilo» | 31-08 | pregunta suya SIN RESPONDER |
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
en `files/dev/roadmap/` (16 documentos). **Esta hoja no los duplica**: duplicar un inventario es
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
