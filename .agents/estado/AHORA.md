# Ahora

- **Actualizado:** 2026-09-15 10:56 (medido con `date`).
- **Actualizado:** 2026-09-15 11:09 (medido con `date`). **Decisión del PO sobre `process()` y
  las pruebas:**
  - aplicar las mejoras y probarlo todo contra la aplicación local, con navegador simulado,
    credenciales de prueba y registros creados;
  - todo tiene que seguir funcionando como está programado;
  - las mejoras de rendimiento y seguridad son bienvenidas.
  Queda en el **ADR 0010** y en la regla 40 §2. En vuelo: **`#045`**, el buscador de
  `process()` con la opción B, probado de punta a punta.
- **Actualizado:** 2026-09-15 12:53 (medido con `date`). **El PO se fue: «Sigue sin parar».**
  - Decidió LF en los cinco repositorios, porque es lo más universal. El ADR 0012 está en el
    scratchpad y la ronda irá tras `#055`.
- **Último mensaje enviado:** `#057 · ARQ`. El próximo número es `#058`.
  - `#056 · COD`: `#055` cerrado (`ae869246`, `1eb497a3`, `77c32b17` y `f352f3a9`).
    - `fieldsToSelect()` ya no selecciona `password`. Los informes de accesos pasan de 6 a 0
      hashes y de 9 a 0.
    - El censo de los 44 accesos a `password`: ninguno lo lee de una fila de `fieldsToSelect()`.
      El login, el cambio de contraseña y el 2FA cargan el mapper completo.
    - **⚠ GRAVE, abierto:** `/users/all/` (`users-ajax-all`, `UsersController::_all()`
      1852-1890) hace `SELECT pcsphp_users.*` y devuelve 20 hashes. Según el inventario, la
      piden los tipos 0, 1, 12, 2, 3 y 4. No tiene consumidores en el repositorio.
  - `#057`: `_all()` deja de devolver `password`, se mide con la sesión de un usuario general y
    se censan otros `SELECT` de la tabla entera de usuarios que acaben en una respuesta.
  - `#054 · COD`: `#053` y `#051` cerrados, con 5 commits y `gates` 26/0 al final.
    **⚠ GRAVE:** los informes de accesos mandaban al navegador el hash de cada contraseña. Se
    avisó al PO al móvil.
  - `#055`: `fieldsToSelect()` deja de seleccionar `password`, y se barren las respuestas JSON
    en busca de hashes.
- **Cola del tirón, en orden:**
  1. `#055`;
  2. LF (ADR 0012);
  3. 3b, las traducciones;
  4. 4c, Aprobaciones;
  5. 4b, con el cron;
  6. OTP;
  7. 5b, los tokens;
  8. E3;
  9. los avatares;
  10. 7b y 7c.
  Ronda del tirón: 1 de 20, contando desde `#053`.
- **Borradores listos en el scratchpad del arquitecto**, por si la sesión muere: el ADR 0012
  (LF), `instruccion-lf.md`, `instruccion-3b.md` (opción B: acción `translateGroup`, solo POST,
  sin valores del navegador, sin sobrescribir y con validación de las etiquetas) e
  `instruccion-4c.md` (el tipo 12 en las rutas, `canManage()` en el servidor y P25 en
  `isVisibleToPublic()`).
  - Una sesión nueva no puede leerlos: son una ayuda, no la fuente. Las decisiones que llevan
    están en `pendientes.md` y en el mapa.
  - El diseño del cron del 4b está en `diseno-4b-cron.md`: franjas, estado en
    `app/cache/cronjobs/`, reintentos, ventana de recuperación y `flock`.
  - **⚠ Hallazgo del arquitecto, verificado por lectura:** la ruta HTTP del cron falla
    abierta.
    - Sin `secure-keys/cronjob`, `getKeyFromSecureKeys()` devuelve `''`, y una petición sin
      cabecera la iguala: el cron corre sin clave.
    - En esta instalación el archivo existe; no se leyó.
    - Se arregla en el 4b (fallar cerrado y `hash_equals`). Se registra en `pendientes.md` al
      recibir `#056`.
- *(histórico)* **Último mensaje enviado:** `#053 · ARQ`.
  - `#052 · COD`: `#051` **bloqueado en el PASO 0**, con criterio. `gates` da 135/136 porque
    `0bff44c4` retiró la entrada de SystemApprovals de `sql-concat-declared.json` después de
    correr las pruebas, y la sección 7 de la suite exige que esté.
  - `#053`: invertir esa comprobación (exige que ya NO esté), con provocación y en su propio
    commit. Después sigue `#051` (documentación, LoginAttempts sin mover la foto y la medición de
    H1), con el plan del coder.
  - Ya están depositados en el árbol: tus respuestas en `pendientes.md`, el mapa (con el lote
    nuevo 4c, Aprobaciones) y la regla 30 (`A-NNN` y verificar después del último cambio).
- **`#050 · COD`:** SystemApprovals commiteado (`0bff44c4`), con 50/50 y 72/72. LoginAttempts
  parado con criterio, porque la foto vieja no servía por los datos nuevos.
- **Actualizado:** 2026-09-15 12:36 (medido con `date`). **El PO contestó la batería para 20
  rondas** (2.1-2.9). Se depositan en `pendientes.md`, el mapa y la regla 30 al recibir `#052`;
  el borrador está en el scratchpad (`deposito-tras-052.md`). En corto:
  - 2.1: los lotes de núcleo con diseño acordado (3b, 4b y 5b) van sin reconsultar;
  - 2.2: sí al 4b, más un cron con reintentos y ventanas de recuperación, documentado; el diseño
    lo decide el arquitecto;
  - 2.3: OTP con bloqueo y respuesta uniforme, los dos configurables;
  - 2.4: sí a los tokens genéricos; 2.5: sí a E3, y si pasa de diez archivos el plan se enseña
    al final, sin commitear; 2.6: sí a los avatares y a `see-more`;
  - 2.7: Mailpit o MailHog, si es local, seguro y sin registro (se verifica al instruir 7c);
  - 2.8: P25 cambia (lo no aprobado no se ve por su enlace); Locations, a criterio del
    arquitecto y documentado; idea del PO: la aprobación, encendible por módulo;
  - 2.9: las condiciones de parada, como se propusieron.
  **El tirón de hasta 20 rondas arranca al recibir `#052`.**
- **Nueva convención del PO:** cada mensaje del arquitecto al PO lleva un identificador `A-NNN`
  en su primera línea, para que pueda citarlo. **Último usado: `A-001`.**
- **Respuestas del PO (2026-09-15, con `#049` en vuelo).** Se depositan en `pendientes.md` al
  recibir `#050`; los borradores están en el scratchpad.
  1. **5.1: se mantiene el soporte legado de `having_string`.** `process()` lo sigue aceptando
     para los clones, y `escapeString()` vive solo en ese camino. Se documenta como legado en el
     `CHANGELOG.md` y en el docblock, con la guía para pasar a `having_segment`.
  2. **5.2: los administradores de organización (tipo 12) pueden entrar a Aprobaciones y
     administrar lo que les compete.** Es trabajo nuevo, que se mide y se instruye después de
     `#050`. Con eso C3 y C5 pasan a actuar. La acción de aprobar tiene que limitarse en el
     servidor a su organización, no solo en el listado.
  3. **3.3: el correo real se permite, solo a direcciones `@mailinator.com`,** y el coder dice a
     cuáles para que el PO los revise. Irá en el ADR 0011, cuyo borrador está en el scratchpad,
     y sirve también para la ventana de correo (7c). La idea de un «Mailinator propio» queda
     como idea.
  4. **Comentario, no trabajo inmediato:**
     - las vistas de LoginAttempts se rehacen de cero, con la estética del resto y dentro de la
       unificación de los registros;
     - lo mismo con todas las vistas de configuración (SMTP, SEO, etc.). SMTP tiene que poder
       probarse.
- **`#048 · COD`:** la propuesta es equivalente en 72 de 72 casos, y la foto de antes está hecha
  (50 capturas). El arquitecto la revisó: C1 agrupado con el `IS NULL` conservado, C3 con
  `(int)`, y LoginAttempts incluido para poder retirar `generateHaving()`.
- **`#046 · COD`** (recibido a las 11:40, medido con `date`):
  - **Completado:** el buscador de `process()` por marcador en 18 de 21 listados, con la misma
    respuesta en las búsquedas normales. El arquitecto lo verificó por su cuenta: 105
    comparaciones sin diferencias.
  - **Queda:** el filtro de SystemApprovals, que son reglas de acceso y se hablan con el PO.
  - El coder espera instrucción.
- **Respuestas del PO (11:47, medido con `date`):**
  1. **SystemApprovals: aceptado.** Confía en que no se perderá funcionalidad. Va en `#047`
     como una propuesta que el arquitecto revisa antes de aplicarla.
  2. **P28:** el PO entiende que las traducciones son estáticas, y dinámicas en
     `configurations.js`. El arquitecto verificó que esa función de `configurations.js` (línea
     1501) es la que llama a la ruta de guardado. Se le explica con contexto.
  3. **La poda:** la decide el arquitecto. Irá al cierre de este tramo, con el curador.
  4. **Queja del PO:** los resúmenes al detenerse no seguían la forma acordada. Queda en la
     regla 30, en «Tramos y rondas».
- **`#044 · COD`** (11:04):
  - T1 de `#042`, commiteada (`b52172f8`, `dd07e06e`).
  - T2 solo llegó a medir, con una sonda temporal que se borró: `generateHaving()` y
    `generateHavingGroup()` dan **las mismas filas** en `locations_countries`,
    `locations_states` y `publications_elements`, y también en una búsqueda sin resultados.
  - No se tocó ningún archivo de producción. El coder espera instrucción.
- **`#043 · ARQ`** retiró T2 y T3 de `#042`.
  - `#042` tocaba `DataTablesHelper::process()`, un elemento transversal del núcleo, sin haberlo
    hablado con el PO.
  - El PO: esos cambios se conversan primero. Queda en la regla 30, puntos serios.
  - Solo sigue T1 de `#042`, los commits de documentación.
- **`#041`: completado.**
  - `sqlStringLiteral()` en 11 etiquetas de seis mappers (`c250c2ee`); PHPStan 737.
  - El plan de `process()`, medido.
  - El estudio del 4b, hecho.
  - **H1: las traducciones dinámicas tienen el control de acceso roto y un XSS almacenado**
    (P28).
- **Tramo en curso:** [`tramos/2026-09-15-1022-lote-4-y-estudio-4b.md`](tramos/2026-09-15-1022-lote-4-y-estudio-4b.md).
- **Informe del estado del proyecto:** [`informe-2026-09-14-estado-del-proyecto.md`](informe-2026-09-14-estado-del-proyecto.md).
- **Sesiones:** arquitecto `PiecesPHPUpgrade-Arquitecto-Main`, coder `PiecesPHPUpgrade-Coder-Main`.
- **Rama:** `dev`, en `4b3d3d58`. Hay 33 commits sin empujar en piecesphp.

## Autorización de commits del PO (ADR 0005)

**Vigente desde el 2026-09-14.** El coder prepara y commitea, **en commits atómicos**, el trabajo
que el PO nombra y el arquitecto instruye, sin pedir permiso commit a commit.

**Reservado al PO:**

- `git push` y todo lo que toca un remoto;
- etiquetar `piecesphp` y tocar su `master` o su `last-stable`;
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

0. **⚠ GRAVE — `app_key` con valor de relleno (verificado por el arquitecto, 2026-09-15).**
   - `config.php:81` la trae con un texto de relleno público. `Config::app_key()`
     (`Config.php:1063-1067`) la devuelve tal cual, y `bootstrap.php:313/316` firma con ella
     las sesiones (`BaseToken`) y el cifrado (`BaseHashEncryption`). Nada la sobrescribe.
   - **Todo clon que no la cambie tiene sesiones falsificables:** cualquiera puede fabricar una
     sesión de administrador. En esta instalación local vale el relleno.
   - Toca `Config` y `bootstrap`, que son núcleo transversal: **se habla con el PO antes.**
   - *Predeterminado propuesto:*
     1. leerla de `secure-keys/app_key` como las otras claves (`api-keys.php`);
     2. fuera de `is_local()`, si está vacía o es el relleno, la aplicación se niega a arrancar
        con un mensaje claro;
     3. una tarea `bin/cli` para generarla.
   - Cambiarla cierra todas las sesiones abiertas. Encaja con 5b, cuya decisión es derivar las
     claves JWT fijas de `app_key`.
1. **H1 de `#050`: el SQL y las filas crudas viajan al navegador** en todos los listados de
   `DataTablesHelper`. Es núcleo transversal y su diseño no está acordado: se le presenta con la
   medición de `#052` delante.
2. **La aprobación encendible por módulo** (su idea en 2.8). *Predeterminado:* va después de la
   MAJOR, porque amplía una capacidad. Si la quiere dentro, lo dice.
3. **Subir cuando quiera:** los commits sin empujar de `dev`.

P28 y el plan de `process()` ya están resueltos (opción B en los dos).

## En curso

**`#042` con `#043`:** solo T1, que commitea el `CHANGELOG.md`, `pendientes.md`, el mapa y el
estado. T2 y T3 están retiradas hasta hablarlo con el PO. Si el coder ya había empezado, deja el
árbol como esté, sin commitear ni revertir, y lo reporta en `#044`.

## Siguiente

- Con P28: el lote 3b (traducciones).
- El ADR del 4b, con el estudio de `#041` delante: la decisión por el nombre (el sufijo
  `.protected`), `Core/Statics/`, `Range`, `Cache-Control: private`, `Vary` y elFinder.

## Para una sesión nueva

1. **Lo primero que se da al PO** al empezar o retomar el trabajo, cada día, son las dos órdenes
   de renombrado (regla 30, «Nombres de sesión»):
   `/rename PiecesPHPUpgrade-Arquitecto-Main` y `/rename PiecesPHPUpgrade-Coder-Main`. Después
   se comprueba en la lista de sesiones que están puestas.
2. Lee `../HERENCIA.md`, el informe del estado del proyecto y el último tramo.
3. **Las horas de este archivo salen de `date`**, no de una estimación.
