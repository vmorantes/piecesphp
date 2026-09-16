# Ahora

- **Actualizado:** 2026-09-16 10:28 (medido con `date`).
- **Mandato vigente del PO (A-031):** trabajar sin parar hasta cerrar los lotes 7 a 11, con todo lo
  que va antes en el mapa, y **parar antes del 12**. Detalle en `../docs/pendientes.md`, bloque del
  2026-09-16.
- **Tramo en curso:** [`tramos/2026-09-16-0908-lotes-4d-a-11.md`](tramos/2026-09-16-0908-lotes-4d-a-11.md).
- **Último mensaje:** `#130 · ARQ`. Próximo: `#131`. **Último al PO:** A-035.
- **Sesiones:** arquitecto `PiecesPHPUpgrade-Arquitecto-Main`, coder `PiecesPHPUpgrade-Coder-Main`.
  Las dos se reabrieron el 2026-09-16, así que cuentan como compactadas.
- **Rama:** `dev`. El hash de HEAD no se escribe aquí, porque se pudre entre rondas: se mira con
  `git --no-optional-locks log --oneline -1`.
- **Este archivo se rehízo corto el 2026-09-16.** Tenía 567 líneas: era historia, no estado. La
  historia está en git, en `tramos/2026-09-15-1022-lote-4-y-estudio-4b.md` y en `pendientes.md`.

## En curso

**`#126`: `7c`, parte A** — Mailpit en local, una suite que prueba el código de recuperación de contraseña
contra él, y el estudio de los otros nueve envíos. `#127 · COD`: parado con razón, porque Mailpit comprueba
versiones por internet por defecto (lo delató `LatestVersion` en `/api/v1/info`); la descarga, verificada
contra el `digest` de GitHub. `#128 · ARQ`: arranque con `--disable-version-check`, y fe de erratas en el ADR
0015.

En segundo plano, un subagente de arquitectura prepara la propuesta del **lote 8** (`Importers` hacia
`DataImportExportUtility`).

**Esperando el push del PO** de `database` (`master` `4fc608d` y `v5.0.0`) para la parte B de `4d`
(comprobado a las 10:20: `origin/master` seguía en `e3e55d1`). `4b-4` sigue bloqueado por P26.

Cerrado: **el lote 7** (`#119`→`#125`). `952eea90` (línea base 734), `8404e6c6` (la instantánea congela
~70 firmas nunca registradas desde `7b6209d2`), `6a4e467c` (fuera el creador de avatares: 173 archivos),
`acdb796f` (vuelve «Ver más»), `0055b24d` (PHPStan **731**: murieron 3 con `listFiles()`), `6207cb0c`
(documentación) y `27dbe5b1` (estado). «Ver más» en navegador: SIN VERIFICAR, porque no hay credencial.

**Fallos de redacción del arquitecto en el tramo, cinco:** la API sin verificar de `#108`, el `git add`
de `#116`, la cifra de `#119`, el §3b contradictorio de `#122` y el `git rm` preparado antes de tiempo en
`#119`. Todos los cazaron paradas escritas antes de que hicieran daño. Van a la regla 30 como oficio.

**Para la parte B de `4d` (medido por el arquitecto el 2026-09-16): son 10 compensaciones, no 8.** El
censo de `#104` solo buscó `stripslashes`. Con otras formas aparecen dos más, que borran TODAS las
barras del contenido de las noticias:
- `src/app/classes/News/Views/news/public/util/item.php:14`: `str_replace("\\", '', $content)`;
- `src/statics/core/js/configurations.js:1091`: `item.content.replace(/\\/g, '')`, en el modal de
  noticias del panel. Va compilado: `gulp js-vendor` (ADR 0013).
Descartadas por no tocar datos: las normalizaciones `\\` → `/` de rutas en las tareas de `bin/cli`.

**Lote 7, medido de nuevo el 2026-09-16 (LEY 17), cuadra con el 20 §7:** 162 imágenes de
`src/statics/images/avatares/` y 3 fuentes de `src/statics/features/avatars/`; `AvatarController::avatar()`
y `listFiles()`; la ruta `avatars` (`routes.php:109`) y su permiso (`roles.php:50`); 9 líneas en 3
formularios de `UsersController` (499-505, 599-605 y 715-721); `configAvatar()` en `users-forms.js`
(27 y 100-); en `gulpfile.js`, las entradas 152-153, 171-172 y 181, `sassCompileAvatars()` (226-) y la
tarea `sass-compile-avatars` (256). Se queda la foto de perfil: `register()`, `push-avatars` y
`AvatarModel`. `see-more`: falta el botón `[see-more]` en `News/…/item.php`, y el modal busca `>.header`
cuando la tarjeta tiene `.head`. El plan va al PO por la regla de los diez (A-035).

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

0. **Push de `database`** (`master` `4fc608d` y `v5.0.0`). Bloquea la parte B de `4d`.
0b. **Alcance del ADR 0017** (lo planteó el coder en `#115`): ¿vale solo para `4d` o para toda la
   campaña? *Predeterminado:* toda la campaña, porque cada actualización exige antes el push del PO.
1. **Detectar las compactaciones por máquina**, leyendo el `.jsonl` desde `verificar.sh`.
   *Predeterminado:* se hace cuando lo nombre.
2. **El SQL y las filas crudas de los listados viajan al navegador** (H1 de `#050`, en
   `DataTablesHelper`, que es núcleo transversal). *Predeterminado:* aparcado hasta que lo nombre.
3. **La recuperación de contraseña envía una contraseña nueva en claro** por correo
   (`RecoveryPasswordController::mailNewPassword()`). *Predeterminado:* se mantiene y se documenta.
   El arquitecto recomienda sustituirla por el enlace o el código de recuperación, que ya existen.
4. **«Perfeccionar geovisor»**: ya se sabe cuál es, pero no qué quiere perfeccionar.
   *Predeterminado:* espera a que lo diga.
5. **Después de la MAJOR**, salvo que diga lo contrario: la aprobación encendible por módulo, la
   rutina de instalación y la vista «Sistema».
6. **Subir cuando quiera** los commits sin empujar de `dev`.

## Para una sesión nueva

1. Este archivo.
2. El tramo en curso.
3. `.agents/README.md`.
4. `../docs/roadmap.md` y el bloque del 2026-09-16 de `../docs/pendientes.md`.
