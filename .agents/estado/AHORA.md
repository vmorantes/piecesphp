# Ahora

- **Actualizado:** 2026-09-16 09:43 (medido con `date`).
- **Mandato vigente del PO (A-031):** trabajar sin parar hasta cerrar los lotes 7 a 11, con todo lo
  que va antes en el mapa, y **parar antes del 12**. Detalle en `../docs/pendientes.md`, bloque del
  2026-09-16.
- **Tramo en curso:** [`tramos/2026-09-16-0908-lotes-4d-a-11.md`](tramos/2026-09-16-0908-lotes-4d-a-11.md).
- **Último mensaje:** `#112 · ARQ`. Próximo: `#113`. **Último al PO:** A-033.
- **Sesiones:** arquitecto `PiecesPHPUpgrade-Arquitecto-Main`, coder `PiecesPHPUpgrade-Coder-Main`.
  Las dos se reabrieron el 2026-09-16, así que cuentan como compactadas.
- **Rama:** `dev`. El hash de HEAD no se escribe aquí, porque se pudre entre rondas: se mira con
  `git --no-optional-locks log --oneline -1`.
- **Este archivo se rehízo corto el 2026-09-16.** Tenía 567 líneas: era historia, no estado. La
  historia está en git, en `tramos/2026-09-15-1022-lote-4-y-estudio-4b.md` y en `pendientes.md`.

## En curso

`#112`: **`4d`, parte A, en el paquete `database`.** El texto deja de escaparse en
`castPHPToSQLTypes()` y en `DataProcess::stringParse()`, con prueba de ida y vuelta de seis valores,
cuatro provocaciones, fusión a `master` y etiqueta `v5.0.0`. Al final, un commit de documentación en
`piecesphp`.

**Después, el PO tiene que empujar `database`** (`master` y `v5.0.0`) para que la parte B (el framework
a `^5.0`, quitar las 8 compensaciones y la tarea de reparación) pueda instalarse desde Packagist.
Mientras tanto se sigue con `4f`.

Cerrado: **`#104`, el aviso de `app_key` con `nag`** (`226dc26b`, `a35064c9`, `37c939ef`, `7661a2c0`),
tras el incidente de `#107` (seis filas en la base local, borradas en `#111`; tabla idéntica). Aceptado
por el arquitecto con las capturas y una prueba aislada de la «×».

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
