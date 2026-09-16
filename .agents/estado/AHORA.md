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
- **Último mensaje enviado:** `#091 · ARQ` (ronda **20 de 20, la ÚLTIMA**: arreglar las dos
  guardas que fallan abiertas y cerrar la tanda B). El próximo número es `#092`.
  - `#090 · COD`: la tanda A del 7b, cerrada (`779890c1`, `07068319`, `9af1845e`). 13 guardas
    con prueba y provocación; el censo baja de 19 a 11.
  - **⚠ Dos guardas fallan abiertas** (ver `pendientes.md`): `UploadedFileAdapter::validate()`
    devuelve TRUE sin archivo, y `Roles::addPermission()` con tipo CODE y un nombre concede la
    ruta a ROOT en silencio. Se arreglan en la ronda 20.
  - **El 7c (correo con Mailpit) pasa al tirón siguiente:** es infraestructura de pruebas, no un
    defecto, y solo queda una ronda. Su **ADR 0015 ya está depositado**, con la verificación
    hecha (MIT, sin registro, v1.31.1, sin sumas publicadas).
  - **Al cerrar `#092`: el GRAN RESUMEN del tirón**, con la forma fija de la regla 30 y su
    `A-NNN`. El esqueleto está en el scratchpad (`gran-resumen-esqueleto.md`) y lo esencial ya
    está en este archivo y en `pendientes.md`, por si la sesión se compacta.
- *(histórico)* **Último mensaje enviado:** `#089 · ARQ` (ronda 19).
  - `#088 · COD`: E3 cerrado (`16bd8a39`, `bd8ddfa7`, `27a6b8b4`, `ea6e514d`); H-L parado con
    criterio, porque su causa está en el paquete `database`.
  - **⚠⚠ H-R, PUNTO SERIO QUE ESPERA AL PO** (ver `pendientes.md`): el ORM escapa dos veces todo
    campo de texto, y el INSERT ya liga valores.
    - **Demostrado por el arquitecto:** el `stripslashes` previo BORRA las barras invertidas
      legítimas al guardar (`C:\ruta` → `C:ruta`), y en la columna queda `O\'Brien`.
    - Es el ORM y vive en un paquete hermano: no se instruye sin el PO.
    - **H-L depende de esta decisión** y queda abierto.
    - **Alcance, medido por el arquitecto (2026-09-15), para pasar a `pendientes.md` al cerrar
      la ronda:**
      - **112 campos de texto en 26 mappers** pasan por ese escape doble. Los que más:
        Organization (15), Publication (8), Users (7), UserProfile (7), SystemApprovals (6),
        OTPSecrets (6), Banner (6) y Documents (6);
      - **la copia instalada y la del repositorio del paquete son IDÉNTICAS** byte a byte
        ignorando el retorno de carro (mismo `sha256` normalizado). La instalada está en CRLF
        porque Composer la puso antes del ADR 0012 y `src/vendor` no se versiona. Lo instalado
        es `v4.1.0`, la última etiqueta del paquete: **el paquete no se ha movido**;
      - por tanto, el arreglo va en el repositorio `database`, se etiqueta, y solo llega al
        framework cuando se versiona e instala (regla 30).
    - **Aviso para quien decida:** el escape de escritura y el `stripslashes` de lectura son una
      PAREJA. Quitar solo uno cambia cómo se leen las filas que ya existen. La decisión necesita
      elegir también qué se hace con lo ya guardado.
- *(histórico)* **Último mensaje enviado:** `#087 · ARQ` (ronda 18).
  - `#086 · COD`: la inyección de `OTPHandler` cerrada, y tres más del barrido
    (`fce8ec9c`, `7d4c723e`); el 5b entero (`fa911716`, `700d5167`, `66305d39`, `8fde8816`).
    Suites: `sql-placeholders` 148/148 y `generic-tokens` 15/15, todas provocadas.
  - **La pregunta del PO sobre el ORM (A-020), contestada con el código delante:**
    `ActiveRecord::where()` liga valores solo con `WhereSegment` o array; con una CADENA la
    mete tal cual (`"WHERE ({$where})"`). La interpolación ocurre antes de llegar al ORM, así
    que el valor viaja como SQL. La prueba: con la forma vieja, `zz-no-existe\` daba error 1064,
    y un valor ligado no puede provocar un error de sintaxis.
  - **⚠ H-L:** el límite por usuario del OTP no casa con nombres con comilla, porque
    `login_attempts` los guarda escapados. Se arregla al principio de la ronda 18.
  - Quedan tras `#087`: 2 rondas, que son 7b y 7c. Después, el GRAN RESUMEN.
- *(histórico)* **Último mensaje enviado:** `#085 · ARQ` (ronda 17).
  - `#084 · COD`: H-B, H-C y el OTP, cerrados (`0f4de0b7`, `a06eaa71`, `0b1113a0` y
    `46d2bbf8`).
  - **⚠ GRAVE, 4.º: inyección SQL sin sesión en `OTPHandler::getUserDataByUsername()` (:251).**
    La verificó el arquitecto y se avisó al PO al móvil.
  - Quedan tras `#085`: 3 rondas, que son E3, 7b y 7c.
- *(histórico)* **Último mensaje enviado:** `#083 · ARQ` (ronda 16).
  - `#082 · COD`: el 4b-3 cerrado (`215d91d7` a `f89c91cd`), con la migración real probada de
    ida, vuelta e ida. H-B: al reemplazar una imagen, la nueva iba a la raíz de
    `publications/`.
  - Documentación del 4b depositada: la guía de `protected-files`, la ruptura 22, `10-cli`,
    `13-recetas`, `09-frontend` y la guía de HestiaCP.
  - Quedan tras `#083`: 4 rondas, que son 5b, E3, 7b y 7c.
- *(histórico)* **Último mensaje enviado:** `#081 · ARQ` (ronda 15).
  - `#080 · COD`: el 4b-3 A0 y A, commiteados y en verde (`18f86d32` a `f9681853`). Paró antes
    de B, con criterio.
  - Quedan tras `#081`: 5 rondas, que son OTP, 5b, E3, 7b y 7c. La reserva se consumió en
    partir el 4b-3.
- *(histórico)* **Último mensaje enviado:** `#079 · ARQ` (ronda 14).
  - `#078 · COD`: el 4b-2, cerrado (`0b10eeaa`, `6128eb33` y `f98110a1`). H1 (con un navegador
    se comprimía todo) queda decidido: solo se comprime el texto.
- *(histórico)* **Último mensaje enviado:** `#077 · ARQ` (4b-2).
  - `#076 · COD`: el andamiaje del ADR 0014 y el cron, cerrados (`dc0b8def` a `0586a1b1`).
    Suite 39/39, gates 28/0 y phpstan 735. Documentado en `10-cli-y-tareas.md` y en la ruptura
    21.
  - **Ronda del tirón, RECONTADA el 2026-09-15:** `#077` es la ronda **13** de 20. Las
    instrucciones del tirón son `#053`, `#055`, `#057`, `#059`, `#061`, `#063`, `#065`, `#067`,
    `#069`, `#071`, `#073`, `#075` y `#077`. En `#073`, `#075` y `#077` se apuntó una de más.
    **Quedan 7 rondas tras `#077`.**
  - **El PO pide, al terminar el tirón, un GRAN RESUMEN**: en la forma fija de la regla 30, con
    las secciones numeradas y el identificador `A-NNN`. No se puede olvidar.
  - *(plan anterior, para 6 rondas; con 7 se añade el 7c, el correo)*
  - **Plan para las rondas que quedan del tirón (2.9: se para al llegar a 20):**
    1. `#077`: 4b-2, los archivos servidos por PHP (en vuelo);
    2. 4b-3: el sufijo `.protected`, `Core/Statics/` y la migración. Mapa del explorador en
       curso;
    3. 5: OTP (`recuadro-5-otp.md`);
    4. 5b: tokens y el aviso de `app_key` (`recuadro-5b-tokens.md`);
    5. 6: E3 (`recuadro-6-e3.md`);
    6. 7b: las 19 guardas (`recuadro-7b-guardas.md`).
    - Quedan para el tirón siguiente: 7c (correo con Mailpit, `recuadro-7c-correo.md`), el
      lote 7 (avatares y `see-more`, `plan-7-avatares.md`, con el plan enseñado al PO por la
      regla de los diez), 4b-4 (FileManager) y los residuos.
    - Si una ronda se para y necesita una correctiva, esa correctiva cuenta, y lo último de la
      lista pasa al tirón siguiente.
- *(histórico)* **Último mensaje enviado:** `#075 · ARQ`.
  - `#074 · COD`: el 4c, cerrado (`2fcb1f5b` P25, `17ca919d` y `1132ea87`), más la guarda
    (`b8728777`) y el hook a 100755 (`fcf5acf6`). Suites 60/60 y 33/33. H4: el desplegable de
    tipos es global.
  - **ADR 0014, aplicado por el arquitecto:** `AGENTS.md` es la fuente, `CLAUDE.md` solo
    importa `@AGENTS.md` y `.claude/CLAUDE.md` queda con lo propio de Claude Code. Referencias
    cambiadas y agentes regenerados.
    - `verificar.sh` comprueba ahora el hook (`core.hooksPath` y `100755`) y el espejo. Las
      dos comprobaciones se provocaron y caen como deben. ANDAMIAJE OK.
    - **SIN VERIFICAR:** que Claude Code resuelva `@AGENTS.md`. Se ve en la próxima sesión
      nueva.
  - La ruptura 20 (P25) está en el `CHANGELOG`.
  - Ronda del tirón: 13 de 20.
  - **Comentario del PO (2026-09-15, A-015): los clones.** Un clon no debe heredar el contexto de
    IA que solo sirve para desarrollar y mantener el framework, pero sí la metodología y lo que
    enseña a desarrollar SOBRE el framework. Desplegar solo la plantilla del andamiaje perdería
    lo segundo.
    - Afina `roadmap-posterior/El framework como paquete y su despliegue.md`, que decía que
      `.agents/` viaja entero.
    - Ese documento dice además que el repositorio va en CRLF: falso desde el ADR 0012. Se
      corrige al depositar.
    - Se pasa a `pendientes.md` tras `#076`.
    - **El PO acepta A-015 §3:** capas A, B y C, un manifiesto con comprobación y una orden de
      clonado.
      - Predeterminado aceptado: el marcado por capas en el lote 9, y la orden de clonado
        después de la MAJOR, con la rutina de instalación.
      - **Pregunta del PO:** ¿puede el repositorio ser privado y publicarse un `.zip` clonable?
        Contestada en A-016, con opciones. Lo decide él.
    - Respuesta del PO a A-016:
      - §3: que un `.zip` no se pueda actualizar es un problema para otros, no para él, que es
        hoy el usuario principal;
      - §4 A le gusta, pero vigilar dos repositorios le parece un fastidio.
      - Propuesta de A-017: la B por ahora (el privado más un `.zip` por versión, generado por la
        orden de clonado), y la A cuando haya terceros, automatizada para que no haya que
        vigilar nada. Después de la MAJOR.
    - Último mensaje al PO: **A-017**.
- *(histórico)* **Último mensaje enviado:** `#073 · ARQ`.
  - `#072 · COD`: el tipo 12 entra en Aprobaciones (`cf6803dd` y `cadf95c3`). P25 se paró por
    una contradicción de mi instrucción con `singleView()`. H1: un tipo 12 puede volver a
    resolver lo ya resuelto. La doble carga era del arnés.
  - **Aviso de la plantilla, verificado y arreglado por el arquitecto:** la guarda dejaba borrar
    las raíces de las zonas escribibles y leer `secure-keys/` desde Bash. `probar_guardia.py` da
    198/198.
    - El hook `commit-msg` era `100644`: el coder lo arregla en `#073`.
    - `core.hooksPath` no está puesto: activarlo es del PO.
  - Ronda del tirón: 12 de 20.
  - **El PO activó `core.hooksPath` en esta máquina** (A-010 §1.1; el arquitecto lo comprobó:
    vale `.agents/scripts/git-hooks`).
  - A-011: para no olvidarlo en otras máquinas, **opción A**:
    - `verificar.sh` falla si falta o si el hook no es ejecutable;
    - la orden va en `.claude/CLAUDE.md` y en la guía de clonado (a criterio del arquitecto);
    - el borrador está en `deposito-hooks-a011.md` y se deposita tras `#074`.

    Último mensaje al PO: **A-012**.
  - **Principio del PO (sobre A-012, 2026-09-15):** el estándar es `AGENTS.md` y `.agents/`.
    `CLAUDE.md` es propio de Anthropic: se ESPEJA desde ahí, y solo lleva contenido directo lo
    que no se puede espejar.
    - Hoy está al revés: `AGENTS.md` remite a `CLAUDE.md` como fuente de las reglas.
    - Plan en `plan-agents-md.md` (ADR 0014); se aplica tras `#074`.
    - La línea de `core.hooksPath` va a `AGENTS.md`, no a `.claude/CLAUDE.md`.

    Último mensaje al PO: **A-013**.
- *(histórico)* **Último mensaje enviado:** `#071 · ARQ`.
  - `#070 · COD`: el 3b, cerrado (`8ed8d8b0`, `7c9e0126`, `837dba43` y `88433e44`). `saveGroup`
    responde 410 y el `.min.js` está compilado en local. Queda H2: dos cargas de página y dos
    POST en el navegador headless.
  - Depositados: la ruptura 18 y la entrada «Corregido» del `CHANGELOG`, `08-i18n.md`, la línea de
    compactación de la regla 30 y `pendientes.md`.
  - Ronda del tirón: 10 de 20.
- *(histórico)* **Último mensaje enviado:** `#069 · ARQ`.
  - `#068 · COD`: parado con criterio, con C0 hecho (`1b52f418`).
    - (1) Los compilados de JS no se versionan: el ADR 0013 lo suponía mal, y lleva ya su fe de
      erratas.
    - (2) `current-translations.json` se reescribe mientras quede la clave de prueba pendiente
      en la base.
  - `#069`:
    - el 410 de `saveGroup`, en el código;
    - `gulp js-vendor` en local, sin commitear el compilado, y la prueba en navegador;
    - fuera de la base la clave `zz-prueba-3b` y el JSON a HEAD;
    - `6df4e815` entra tal cual (ruptura 8).
- *(histórico)* **Último mensaje enviado:** `#067 · ARQ`.
  - `#066 · COD`: `#065` cerrado (`19efdc1a`, `0bd37936`, `a6cbb1aa`, `b6e2f5f8` y
    `dc6e767b`). La IA real no se probó, porque la clave local es de relleno.
    `current-translations.json` quedó sucio por la aplicación (D1).
  - Depositados: el ADR 0013 (gulp), `40-salvaguardas.md` §3 y `pendientes.md`.
  - Ronda del tirón: 8 de 20.
- *(histórico)* **Último mensaje enviado:** `#065 · ARQ`.
  - `#064 · COD`: `#063` parado en el PASO 2, con C0 hecho (`7d82e4f7`).
    - El navegador carga `configurations.min.js`, que sale de gulp (`gulpfile.js:88-120`,
      `assets.php:444`); `configurations.js` no se sirve nunca. Compilar necesita la orden del
      PO.
    - En esta instalación, la traducción automática está apagada: todos los idiomas están en
      `autoTranslateFromLangGroupHTMLIgnoreLangs` (`lang.php:53`) y ninguna vista produce
      `lang-group`.
    - Auditoría limpia: 2.402 pares guardados sin carga maliciosa.
  - `#065`: en el servidor, `translateGroup` completo y `saveGroup` todavía vivo pero filtrado
    por `acceptTranslations()`, sin sobrescribir y con el idioma y el grupo validados. Eso
    cierra el agujero sin compilar. La fuente de `configurations.js` se cambia y se commitea.
    El 410 de `saveGroup` y la prueba en navegador esperan a la compilación.
- *(histórico)* **Último mensaje enviado:** `#063 · ARQ`.
  - `#062 · COD`: LF cerrado en los cinco repositorios, con los árboles limpios y todo en verde.
    - piecesphp: `73e39085` (C1), `c90c8e51` (`normaliza-eol` con `-z`, provocado),
      `fb5578e3` (el ADR 0012) y `26c60270` (estado).
    - Paquetes: database `f4358ba`, datastructures `c7c18b9`, geojson `a855039` y html
      `12430ed`, todos en `dev`.
    - `add --renormalize` con `diff --cached` vacío en los cinco.
  - El arquitecto convirtió a LF `.agents/estado/` (4 reescritos, «contenido alterado:
    NINGUNO») y depositó: la corrección del «Cómo actualizar» del `CHANGELOG` (faltaba el
    `add --renormalize`), la trampa en `12-convenciones.md` y la bitácora 0010.
  - Ronda del tirón: 6 de 20, contando `#053`, `#055`, `#057`, `#059`, `#061` y `#063`.
  - **Mailpit (2.7), verificado en su repositorio oficial** (github.com/axllent/mailpit, el
    2026-09-15):
    - licencia MIT (Ralph Slooten), sin cuenta ni registro;
    - un solo binario estático que corre sin root ni servicio, con desarrollo activo;
    - **⚠ por defecto escucha en `0.0.0.0` (SMTP 1025 y web 8025):** se arrancará con
      `--listen 127.0.0.1:8025 --smtp 127.0.0.1:1025`;
    - la ausencia de telemetría NO consta en la página: sin verificar.

    Cumple la condición del PO. Borrador del ADR en `adr-mailpit-borrador.md`, en el
    scratchpad.
  - Instrucciones listas en el scratchpad: 4c, 4b-1 (cron), 4b-2 (archivos) y 5 (OTP). Mapas de
    E3 y de los avatares, en curso.
- *(histórico)* **Último mensaje enviado:** `#061 · ARQ`.
  - `#060 · COD`: `#059` parado a propósito en T3 de piecesphp, sin commits.
    - `bin/normaliza-eol` falla con 10 rutas no ASCII, porque git las cita.
    - `git status` marca 1511 archivos, pero solo 9 cambian de contenido: el índice quedó con
      el `stat` sucio tras cambiar la política.
    - Las pruebas pasan.
  - `#061`: `normaliza-eol` con `-z` y su prueba; `git add --renormalize` solo de lo que no
    cambia de contenido, exigiendo `diff --cached` vacío; después C1 y C2, y los paquetes en la
    misma ronda.
- *(histórico)* **Último mensaje enviado:** `#059 · ARQ` (LF, ADR 0012).
  - `#058 · COD`: `#057` cerrado (`445713f9`, `c047f1c6` y `c5fefcbc`).
    - `/users/all/` pasa de 30 a 0 hashes, también con un usuario general, al que la fuga
      alcanzaba.
    - No hay otras rutas con contraseñas.
    - H1: el login escribe `organization = -10`.
- *(histórico)* **Último mensaje enviado:** `#057 · ARQ`.
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

00. **✔ Contestada por el PO (A-003, 2026-09-15): «la compilación de gulp no necesita
    permiso».**
    - Se registra como excepción del repositorio en un ADR, al depositar: el coder ejecuta las tareas
      de gulp del proyecto cuando la instrucción lo diga, y el diff del compilado se revisa antes de
      commitear.
    - Se aplica al 3b en la ronda siguiente.
    - Último mensaje al PO: **A-003**.
    Pregunta original: **¿Autorizas compilar el JS (gulp, solo `jsTask`)?**
   - El navegador no carga `configurations.js`, sino `configurations.min.js`, que genera gulp.
     Sin compilar, el cambio del 3b en el navegador no llega.
   - El agujero ya queda cerrado en el servidor (`#065`): `saveGroup` filtra lo que recibe.
   - Compilar regenera el `.min.js` desde todas sus fuentes (`helpers-lib`, `translations`,
     `configurations.js` y `helpers.js`). El compilado tiene 25 días más que las fuentes, así que
     arrastraría todo lo cambiado desde entonces. Se revisaría el diff antes de commitear.
   - Con la compilación irían el 410 de `saveGroup` y la prueba en navegador.
   - *Predeterminado:* no se compila; el filtro del servidor protege mientras tanto.
   - Lo mismo pasará con E3 si el CSS de MySpace está compilado y versionado.
- **Respuesta del PO a A-005 (2026-09-15).** Se pasa a `pendientes.md` al recibir `#070`.
  1. **Fomantic-UI, sí, pero conservando la estética que ya hay.** La referencia es Publications,
     que usa Fomantic con retoques pequeños: breadcrumbs y una forma fija de poner botones y
     títulos. Todo pensado para documentarse bien.
  2. **Las capas de documentación:**
     - para agentes: existe (`.agents/context/`);
     - para desarrolladores y mantenedores, que enseñe a extender el framework: NO existe, se hace
       después;
     - para desarrolladores e implementadores;
     - las guías pequeñas asociadas (Hestia, LAMP, etc.): existen, pero hay que revisarlas.

     Encaja con el lote 9 del mapa (`source-docs/` completo).
  3. **(A-007) Hay que documentar el árbol del proyecto**: qué es cada carpeta y qué va en ella,
     para quien desarrolla. Lote 9.
- **A-008, sobre las compactaciones:** el arquitecto no mandó su resumen al coder tras
  compactarse, y del coder no hay constancia. El PO, en respuesta a A-008:
  - 4.1: no hace falta mandarlo ahora; se tiene en cuenta para el futuro;
  - 4.2: el recordatorio fijo le «parece bien», aunque no está seguro. Se hace, porque es barato:
    una línea en cada reporte y en cada instrucción. Va a la regla 30 al recibir `#070`.

  Último mensaje al PO: **A-009**.
- **Directriz del PO (2026-09-15): el backoffice, con Fomantic-UI primero.**
  - Usa todo lo posible los componentes estándar de Fomantic-UI, salvo donde no aplica (los
    sidebars de las herramientas).
  - Así se reduce el CSS propio, se pueden documentar los recursos gráficos y se facilita
    migrar de framework de front.
  - Registrada en `pendientes.md`. Aplica a toda vista del panel nueva o rehecha: 4c, lote 7 y
    las vistas de configuración.
  - Último mensaje al PO: **A-005**.
- **Respuestas del PO a A-003 (2026-09-15).** Se depositan en `pendientes.md` al recibir `#068`.
  1. **§2 gulp:** «es un entorno de pruebas y gulp no es destructivo: úsalo como quieras». Es
     compatible con el ADR 0013, que exige que la instrucción nombre la tarea. Se amplía en su
     línea de `pendientes`.
  2. **§2.3:** de acuerdo; se revisa lo que arrastra el compilado.
  3. **§3 `app_key`:** es cosa de quien haga el clon. **Si no se cambia, opción B**: avisar, sin
     negarse a arrancar.
     - **Idea del PO:** una rutina de «instalación» que genere `app_key` y pregunte colores,
       títulos, propietario, etc., recomendada en la documentación. *Predeterminado:* amplía una
       capacidad, así que va después de la MAJOR, salvo que diga lo contrario.
     - **Consecuencia:** el 5b se desbloquea. Deriva de `app_key` las claves de los tokens, y la
       opción B avisa si `app_key` es el relleno. Último mensaje al PO: **A-004**.
0. **✔ Decidido (B), ver arriba. ⚠ GRAVE — `app_key` con valor de relleno (verificado por el arquitecto, 2026-09-15).**
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
0b. **La recuperación de contraseña puede enviar una contraseña nueva EN CLARO por correo.**
   - `RecoveryPasswordController::mailNewPassword()` (587-611). **Verificado por el arquitecto:**
     hace `render('usuarios/mail/restored_password', ['password' => $password])` y lo envía
     (asunto «Contraseña nueva»).
   - Es una función existente, no una fuga nueva.
   - *Predeterminado:* se mantiene y se documenta. Recomendación del arquitecto: sustituirla por el
     enlace o el código de recuperación, que ya existen.
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
