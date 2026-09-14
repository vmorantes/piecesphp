# Ahora

- **Actualizado:** 2026-09-14 13:45
- **Último mensaje:** `#006 · ARQ`, commit del andamiaje. En vuelo.
- **Tramo en curso:** [`tramos/2026-09-14-1105-traspaso-y-andamiaje.md`](tramos/2026-09-14-1105-traspaso-y-andamiaje.md)
- **Sesiones:**
  - Arquitecto: `PiecesPHPUpgrade-Arquitecto-Main`.
  - Coder: `PiecesPHPUpgrade-Coder-Main`.
  - La sesión anterior del coder, `piecesphp-trabajador-experimentado`, está retirada.
- **Rama:** `dev`, `HEAD` en `0c1af05a` (BC) antes de `#006`.

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
- dependencias, builds, servidores y credenciales.

Si la herramienta del coder pide confirmación al commitear, la da el PO en esa sesión.

**Trabajo nombrado por el PO:**

- el commit del andamiaje (2026-09-14: «Comitea andamiaje»);
- el lote 0b, orden de directorios y builds (P21: «Así es», va antes de trabajar en el
  framework);
- después, el mapa `../docs/roadmap.md`, cuando dé la orden de trabajar sobre el framework.

## Espera al PO

Nada.

Siguen abiertas en `files/dev/PENDIENTES.md`, sin bloquear nada: qué es el geovisor, el
francés, el rol 50 con nombre `null` y `Components`.

## En curso

`#006`: el coder commitea el andamiaje en commits atómicos, sin push.

Si se corta a medias, habrá commits hechos y archivos aún sin preparar. El reporte, o
`git log` y `git status`, dicen dónde quedó; nada se pierde.

## Siguiente

1. Evaluar el reporte de `#006`.
2. **Lote 0b**, orden de directorios y builds: medir las rutas que se mueven, escribir el ADR y
   después instruir. Lo mido ya, en solo lectura, mientras el coder trabaja.
3. **Lote 0a**: `dev` en `datastructures` y `html`.
4. El mapa, cuando el PO dé la orden de trabajar sobre el framework.

## Para una sesión nueva

Lee `../HERENCIA.md` antes que nada: explica el traspaso.
