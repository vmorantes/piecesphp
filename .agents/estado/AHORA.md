# Ahora

- **Actualizado:** 2026-09-17 10:17 (medido con `date`).
- **Mandato vigente del PO (A-031):** trabajar sin parar hasta cerrar los lotes 7 a 11, con todo lo
  que va antes en el mapa, y **parar antes del 12**. Detalle en `../docs/pendientes.md`, bloque del
  2026-09-16.
- **Tramo en curso:** [`tramos/2026-09-17-0846-lote-10-continua.md`](tramos/2026-09-17-0846-lote-10-continua.md).
- **Último mensaje:** `#259 · ARQ` (en vuelo). Próximo: `#260 · COD`. **Último al PO:** A-064.
- **Sesiones:** arquitecto `PiecesPHPUpgrade-Arquitecto-Main`, coder `PiecesPHPUpgrade-Coder-Main`.
  Las dos se reabrieron dos veces el 2026-09-17 (cierre de los IDE); tras la segunda, el PO renombró al coder.
- **Rama:** `dev`. El hash de HEAD no se escribe aquí, porque se pudre entre rondas: se mira con
  `git --no-optional-locks log --oneline -1`.
- **Este archivo se rehízo corto el 2026-09-16.** Tenía 567 líneas: era historia, no estado. La
  historia está en git, en `tramos/2026-09-15-1022-lote-4-y-estudio-4b.md` y en `pendientes.md`.

## En curso

**2026-09-17. Lote 8, ronda R3b (#255):** importador de usuarios (R3a cerrada, 71901195). Después: (importador de usuarios con credenciales de entrega única), R4 (exportador y exógena por CLI), R5 (retirada: plan al PO, 27 archivos) y R6 (documentación). **Lote 10:** lo que no depende del PO, hecho (#205-#244); P45 con su predeterminado pendiente de aplicar. Lote 11 con P36 después del 8.

Cerrado: **`#175`→`#176`, `v8.0.0-alpha.4`** (`4c928396`, etiquetada); `master` → `4c928396`. **Pendiente del PO: revisar
los tres buzones de Mailinator** (A-046). **Sin empujar**: `dev`, `master`, `last-stable` y las etiquetas `alpha.1` a
`alpha.4`.

**Después del lote 9:** los lotes 9, 10 y 11. El lote 8 espera P-a..P-d.

## Orden del tramo

1. `#102`, la limpieza.
2. **El aviso de `app_key` con `nag`**: descartable, que recuerde el descarte y apagable, flotando
   sobre el contenido sin romper el diseño (`pendientes.md`, «Encargos y correcciones del PO tras
   cerrar el tirón», punto 1).
3. **`4d`**: lo ya guardado mal se da por perdido; lo que se guarde a partir de ahora no falla nunca.
4. `4e`, `4f` y `4b-4`.
5. Lotes 7, 7c, 8, 9, 10 y 11. **El lote 7 toca unos 168 archivos: la regla de los diez obliga a
   enseñar el plan al PO antes de commitear.** Mientras espera, se sigue con lo que no dependa de él.
6. **PARAR antes del 12.**

**La guía personal del PO, alcance fijado por él (2026-09-16):** desde el 19-08-2026 **hasta el
presente, siempre**; de este repositorio y de los paquetes `piecesphp/*`, en lo relevante. Tema
`readthedocs` (elegido por él). Obligación continua: **al cerrar cada lote, el arquitecto actualiza
la guía** con lo que afecte al PO, y la commitea en su repositorio (ADR 0016).

En paralelo, sin ocupar al coder: la guía personal del PO (ADR 0016). Hay que elegir el tema,
cubrir desde el 19 de agosto de 2026, y que le sirva sin IA.

## Autorización de commits del PO (ADR 0005)

**Vigente desde el 2026-09-14.** El coder prepara y commitea, **en commits atómicos**, el trabajo
que el PO nombra y el arquitecto instruye, sin pedir permiso commit a commit.

**Reservado al PO:**

- `git push` y todo lo que toca un remoto;
- publicar una versión MAYOR estable de `piecesphp` (hoy, `v8.0.0`); las pre-versiones, sus etiquetas y el
  avance de `master` y `last-stable` son de arquitecto y coder (ADR 0019);
- crear ramas, salvo `dev` en los paquetes;
- reescribir historia;
- escribir o destruir datos de una base de datos, salvo lo que nombre la instrucción con su
  autorización;
- dependencias, builds, servidores y credenciales. Excepciones: las herramientas de análisis
  (ADR 0007) y `piecesphp/*` dentro de los paquetes hermanos (ADR 0008).

Si la herramienta del coder pide confirmación al commitear, la da el PO en esa sesión.

**Trabajo nombrado por el PO:** el mapa, `../docs/roadmap.md`, en su orden. Orden del PO:
«Trabaja. Adelante.» (2026-09-14) y «Trabajen» (2026-09-15).

## Espera al PO

Lista única de lo que solo el PO puede decidir o hacer. **Solo lo abierto**: lo decidido sale a `../docs/pendientes.md`.
Cada punto dice qué se pregunta, por qué importa y qué hace el arquitecto si no hay respuesta. El número no cambia
mientras siga abierto. Para contestar basta el número: «P37 a», «S2 no». Espejo: la guía personal, «Lo que espera de ti».

### Acciones tuyas

- **Empujar.** Todo está en local.
  - Framework: `dev`, `master`, `last-stable` y las etiquetas `v8.0.0-alpha.1` a `v8.0.0-alpha.4`.
  - Paquetes `database`, `datastructures`, `geojson` y `html`: `master` y `dev` de cada uno; en `database`, además,
    las etiquetas `v5.1.0` y `v5.1.1`.
- **Mirar los tres buzones de Mailinator** (`zz-prueba-recuperacion-55e5ee`, `zz-prueba-codigo-55e5ee`,
  `zz-prueba-problemas-55e5ee`, @mailinator.com). Si ya no están, no pasa nada.

### Preguntas abiertas

- **P37 · Alta pública por API con organización nueva.** Hoy falla siempre. Arreglarla abre una vía pública para crear
  una organización y quedar como su administrador. (a) arreglarla, (b) retirar esa vía, (c) dejarla cerrada como está.
  *Predeterminado:* (c), documentado.
- **P40 · `piecesphp/html` no escapa nada.** El framework lo usa en los menús con textos traducidos y URL de rutas
  (riesgo que se cree bajo, sin medir). *Predeterminado:* revisión con el auditor de seguridad y propuesta antes de
  tocar.
- **P41 · `piecesphp/geojson` saca `[latitud, longitud]` por defecto**, que no es el estándar. Cambiarlo rompe a quien
  lo use. *Predeterminado:* no se toca; documentado en su README.

- **P45 · Traducciones dinámicas guardadas en el mismo segundo.** `add-dynamic-translations.php` vuelca las
  traducciones de la base al JSON solo si la fecha guardada es MAYOR que la del último volcado, con resolución de
  segundos: dos guardados en el mismo segundo dejan el segundo pendiente hasta el siguiente cambio. El arreglo es
  pequeño (comparar con `>=` o guardar con más resolución), pero es el sistema de traducciones, que usan todos los
  módulos. *Predeterminado:* se arregla con `>=` y una prueba, salvo que digas que no.
- **P46 · Una sesión con cookie pero sin `localStorage` se cierra sola.** El arnés JS de sesión
  (`PiecesPHPSystemUserHelper.js`) envía un JWT vacío si `localStorage` no lo tiene, y `deleteSession()` recarga: la
  segunda carga ya es anónima. Pasa, por ejemplo, si el navegador borra el almacenamiento local pero conserva la
  cookie. No sé si es intencionado (más seguro) o un fallo (sesiones que se pierden). *Predeterminado:* no se toca y se
  revisa con el auditor de seguridad antes de proponer.

- **P38 · Aprobaciones: qué ve un administrador de organización (tipo 12) de las demás.** Auditado (solo lectura):
  - El desplegable «Tipo de contenido» sale de toda la tabla, sin filtro: muestra qué tipos existen en el sistema
    (Organización, Perfil, Usuario independiente, Publicación). No muestra ningún registro, nombre ni recuento.
    Severidad baja.
  - **Más relevante:** la respuesta de la tabla lleva `recordsTotal`, el total de aprobaciones de TODAS las
    organizaciones, y el texto del SQL ejecutado. Un administrador de una organización ve el tamaño y la actividad del
    resto. El fallo está en `DataTablesHelper` (núcleo) y probablemente afecta a todo listado que filtra por organización.
  - La tabla en sí sí filtra bien por organización, y las acciones lo vuelven a comprobar en el servidor.
  - **(a)** el desplegable se construye con la lista fija de tipos del código, igual para todos (mínimo, solo en el
    módulo) y, en el núcleo, `recordsTotal` se calcula con los mismos filtros que la tabla y dejan de enviarse las
    claves `SQL_*`. **(b)** solo el desplegable, y el núcleo se deja para S3. **(c)** nada.
  *Predeterminado:* (a); el cambio del núcleo se te enseña antes de commitear, con las pruebas de los listados.
- **P39 · El `organization = -10`.** Medido: no está en el login sino en un middleware que corre en **cada petición**
  (`src/app/config/containers.php:89-99`), y lo escribe a **cualquier** usuario con organización nula, sin mirar su
  tipo. Alta y edición de usuarios sí respetan `TYPES_USER_DONT_REQUIRE_ORGANIZATION` (root, administrador general y
  Google Play): a esos les dejan la organización nula. Así que para esos tres el `-10` es lo que tú dices; para un
  administrador de organización, general, institucional o de comunicaciones sin organización (solo si alguien la vació
  en la base), el middleware lo mete en silencio en la organización global. **(a)** el `-10` solo para los tipos de
  `TYPES_USER_DONT_REQUIRE_ORGANIZATION`; los demás sin organización no se tocan y se registra en el log. **(b)** como
  hoy. *Predeterminado:* (a).

- **P47 · La validación de subidas rechaza CSV normales.** `FileValidator` (núcleo, lo usan todas las subidas) no
  admite para CSV el tipo `text/plain`, que es como el sistema reconoce un CSV corriente. Resultado: cualquier módulo que
  valide un CSV con `TYPE_CSV` lo rechaza siempre. El importador nuevo lo evita por su cuenta (comprueba extensión y que
  el contenido sea texto). **(a)** añadir `text/plain` a los tipos de CSV del núcleo (afloja la comprobación para todos;
  la extensión se sigue exigiendo). **(b)** dejarlo y que cada módulo haga como el importador. *Predeterminado:* (b)
  hasta que decidas.

- **P48 · Al guardar un registro nuevo, el objeto no recibe su id.** El ORM guarda el id insertado aparte
  (`getInsertIDOnSave()`) y no en el propio objeto. Muchos modelos lo corrigen a mano en su `save()`; `UsersModel` no, y
  por eso **el alta de usuarios por formulario no creaba el perfil** (confirmado con un alta real). Ya se arregla solo en
  `UsersModel` (no toca el núcleo). Además, `QueueTask::dispatch()` devuelve siempre null por lo mismo. **(a)** arreglarlo
  en el núcleo (`EntityMapperExtensible::save()` deja el id en el objeto; ningún código medido depende de lo contrario) y
  corregir `QueueTask`. **(b)** dejar el arreglo en `UsersModel` y corregir `QueueTask` aparte. **(c)** solo lo de
  `UsersModel`. *Predeterminado:* (b).

### Propuestas que prepara el arquitecto (no tienes que hacer nada aún)

- **P36 · Los seis controladores al estándar**, con `roles.php` y el veto a `get_route()` directo: el plan se te enseña
  antes de commitear (lote 11, regla de los diez).

### Sin prisa (tienen predeterminado y no frenan nada)

- **S1 · Alcance del ADR 0017** (el framework actualiza sus paquetes con Composer). *Predeterminado:* toda la campaña.
- **S2 · Detectar por máquina cuándo se compacta una sesión.** *Predeterminado:* cuando lo nombres.
- **S3 · El SQL y las filas crudas de los listados viajan al navegador** (`DataTablesHelper`, núcleo). *Predeterminado:*
  aparcado.
- **S4 · «Perfeccionar geovisor».** *Predeterminado:* espera a que digas qué.
- **S5 · Después de la MAJOR:** la aprobación encendible por módulo, la rutina de instalación y la vista «Sistema».
- **S6 · P35, la ruta `external` de la API:** documentada como extensión apagada. Solo si prefieres retirarla.

## Para una sesión nueva

1. Este archivo.
2. El tramo en curso.
3. `.agents/README.md`.
4. `../docs/roadmap.md` y el bloque del 2026-09-16 de `../docs/pendientes.md`.
