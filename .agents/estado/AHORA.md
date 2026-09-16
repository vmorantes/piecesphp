# Ahora

- **Actualizado:** 2026-09-16 14:58 (medido con `date`).
- **Mandato vigente del PO (A-031):** trabajar sin parar hasta cerrar los lotes 7 a 11, con todo lo
  que va antes en el mapa, y **parar antes del 12**. Detalle en `../docs/pendientes.md`, bloque del
  2026-09-16.
- **Tramo en curso:** [`tramos/2026-09-16-0908-lotes-4d-a-11.md`](tramos/2026-09-16-0908-lotes-4d-a-11.md).
- **Último mensaje:** `#219 · ARQ` (en vuelo). Próximo: `#220 · COD`. **Último al PO:** A-048.
- **Sesiones:** arquitecto `PiecesPHPUpgrade-Arquitecto-Main`, coder `PiecesPHPUpgrade-Coder-Main`.
  Las dos se reabrieron el 2026-09-16, así que cuentan como compactadas.
- **Rama:** `dev`. El hash de HEAD no se escribe aquí, porque se pudre entre rondas: se mira con
  `git --no-optional-locks log --oneline -1`.
- **Este archivo se rehízo corto el 2026-09-16.** Tenía 567 líneas: era historia, no estado. La
  historia está en git, en `tramos/2026-09-15-1022-lote-4-y-estudio-4b.md` y en `pendientes.md`.

## En curso

**Lote 10 (residuos con nombre), ronda D2 (#217)**: ADR 0020, PHPStan mide solo PHP 8.5 en los cinco repositorios; cinco líneas base con [REPARTO]; los paquetes reciben la puerta de patrones sin casar. D1 cerrada en #215-#216 (7a63c3b2, fa91abd6, ef231226). Ronda B cerrada en #209-#210 (ece73e15, 6f87870c, e873b8d3, d97ff6fe). A1 y A2 cerradas (#205-#208). R13 (alta por API con organización nueva) pasa al PO: arreglarlo abre una vía pública para crear organizaciones con administrador. **Lote 9 cerrado** salvo 9.8 (P36).

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
Cada punto dice qué se pregunta, por qué importa y qué hace el arquitecto si no hay respuesta. El número `P<n>` no
cambia mientras la pregunta siga abierta. Espejo para leer sin el repositorio: la guía personal, «Lo que espera de ti».

### Acciones tuyas

- **Empujar.** Todo está en local.
  - Framework: `dev`, `master`, `last-stable` y las etiquetas `v8.0.0-alpha.1` a `v8.0.0-alpha.4`.
  - Paquetes `database`, `datastructures`, `geojson` y `html`: `master` y `dev` de cada uno (README en español, sin
    versión nueva).
- **Mirar los tres buzones de Mailinator** (`zz-prueba-recuperacion-55e5ee`, `zz-prueba-codigo-55e5ee` y
  `zz-prueba-problemas-55e5ee`, @mailinator.com). En el de «otros problemas», `<b>zz mensaje de prueba</b>` tiene que
  verse escrito, no en negrita. Mailinator los borra en unas horas: si ya no están, no pasa nada.

### Preguntas que frenan trabajo

- **P36 · La tríada de rutas en los seis controladores del sistema** (P4). `UsersController`, `LoginAttemptsController`,
  `AdminPanelController`, `GenericTokenController`, `ImporterController` y `TimerController` no tienen
  `routeName()`/`allowedRoute()`/`_allowedRoute()`. Sus 48 rutas no comparten prefijo y el nombre es el permiso.
  A: renombrar rutas (ruptura). **B: que el trait acepte controladores sin prefijo, sin renombrar nada (recomendada).**
  C: dejarlos como excepción. Detalle: `propuesta-2026-09-16-p4-seis-controladores.md`.
  *Predeterminado:* la ronda 9.8 espera.
- **P33 · `generate_code()` usa `rand()`** (`src/app/core/Utilities.php:455`) para los códigos del 2FA, la recuperación,
  los problemas de usuario y los tokens. Pasarlo a `random_int()`, mismo formato. Es un helper compartido.
  *Predeterminado:* no se toca hasta que respondas; la propuesta es sí.
- **P-a a P-d · Lote 8, el importador** (`propuesta-2026-09-16-lote-8.md`, §6).
  - **P-a** · El motor de importación vive en el núcleo con espacio de nombres nuevo y se retira `Core\Importer`.
    *Predeterminado:* sí.
  - **P-b** · Las fichas imprimibles con contraseñas generadas. *Predeterminado:* se retiran; el CLI entrega las
    credenciales una vez, en un archivo fuera de `src/` y no versionado.
  - **P-c** · Atomicidad. *Predeterminado:* cada fila por su cuenta, como hoy. Alternativa: todo o nada por archivo.
  - **P-d** · ¿El administrador general sigue importando? *Predeterminado:* sí, pero solo usuarios generales.
  *El lote 8 entero espera estas cuatro.*
- **P31 · reCAPTCHA v3.** Google no publica claves de prueba para v3. (a) Las claves pasan a la configuración, vacías;
  sin claves, el formulario de contacto rechaza y deja una línea en el log. (b) Pasar el módulo a v2, que sí tiene
  claves de prueba. *Predeterminado:* (a). **Aparte, recomendado:** borra o regenera la clave actual en
  `google.com/recaptcha/admin`, porque sigue en el historial de git; la pública, para buscarla, está en
  `src/statics/js/contact-form.js:16`.
- **P26 · Los archivos del editor enriquecido (elFinder).** Se guardan en `src/statics/filemanager`, fuera de `uploads`,
  y el servidor web los sirve a cualquiera con la URL; `verify-integrity` no lo vigila. La misma carpeta sirve a
  publicaciones públicas y a contenidos internos. Propuesta: los archivos de cada registro en su carpeta, con su
  visibilidad; el gestor general, con sesión; lo existente, declarado para no romper URL guardadas. **Sin resolver:**
  cómo casa lo privado de elFinder con la protección por sufijo. *Predeterminado:* no se toca; queda `4b-4`.

### Llegan del lote 10 (residuos): decisiones de producto o de núcleo

- **P37 · Alta pública por API con organización nueva.** Hoy falla siempre. Arreglarla abre una vía pública para crear
  una organización y quedar como su administrador. ¿Se arregla, se retira esa vía, o se deja cerrada?
  *Predeterminado:* se deja como está (falla) y se documenta.
- **P38 · El desplegable «Tipo de contenido» de Aprobaciones es global**: un usuario de una organización ve etiquetas de
  otras (no registros). Posible fuga de metadatos entre organizaciones. *Predeterminado:* revisión con el auditor de
  seguridad y propuesta antes de tocar.
- **P39 · El login escribe `organization = -10`** en los usuarios sin organización, al entrar. ¿Es intencionado?
  *Predeterminado:* no se toca.
- **P40 · `piecesphp/html` no escapa nada.** El framework lo usa para pintar menús con textos traducidos y URL de rutas
  (riesgo que se cree bajo, sin medir). ¿Escape por defecto en el paquete (ruptura del paquete) o revisar los usos?
  *Predeterminado:* revisión con el auditor antes de proponer.
- **P41 · `piecesphp/geojson` saca `[latitud, longitud]` por defecto**, que no es el estándar. Cambiarlo rompe a quien lo
  usa hoy. *Predeterminado:* no se toca; documentado en su README.
- **P42 · `SchemeCreator::createScript()`/`dropScript()` con mappers en vez de `SchemeCreator`** devuelven un script
  vacío sin error. Hacer que lancen es un cambio del paquete `database`. *Predeterminado:* sí, en la próxima versión del
  paquete.
- **P43 · `API_CRONJOBS` sola no registra la ruta del cron**: hace falta otra bandera de la API encendida. Es registro de
  rutas. *Predeterminado:* se arregla en el lote 10 si no dices lo contrario.
- **P44 · `login_attempts.date` no tiene `DEFAULT` en la base**, aunque el mapper lo declara. Es un cambio de esquema.
  *Predeterminado:* se propone el `ALTER` con su prueba antes de aplicarlo.

### Sin prisa (tienen predeterminado y no frenan nada)

- **Alcance del ADR 0017** (el framework actualiza sus paquetes). *Predeterminado:* toda la campaña.
- **Detectar por máquina cuándo se compacta una sesión.** *Predeterminado:* cuando lo nombres.
- **El SQL y las filas crudas de los listados viajan al navegador** (`DataTablesHelper`, núcleo). *Predeterminado:*
  aparcado.
- **«Perfeccionar geovisor».** *Predeterminado:* espera a que digas qué.
- **Después de la MAJOR:** la aprobación encendible por módulo, la rutina de instalación y la vista «Sistema».
- **P35 · La ruta `external` de la API.** Aplicado el predeterminado: documentada como extensión apagada. Solo si
  prefieres retirarla.

## Para una sesión nueva

1. Este archivo.
2. El tramo en curso.
3. `.agents/README.md`.
4. `../docs/roadmap.md` y el bloque del 2026-09-16 de `../docs/pendientes.md`.
