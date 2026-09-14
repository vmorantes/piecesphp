# Ahora

- **Actualizado:** 2026-09-14 14:40
- **Último mensaje:** `#008 · ARQ`, lote 0b: orden de directorios (ADR 0006) y commit de la
  documentación pendiente del arquitecto. En vuelo.
- **Tramo en curso:** [`tramos/2026-09-14-1105-traspaso-y-andamiaje.md`](tramos/2026-09-14-1105-traspaso-y-andamiaje.md)
- **Sesiones:** arquitecto `PiecesPHPUpgrade-Arquitecto-Main`, coder `PiecesPHPUpgrade-Coder-Main`.
- **Rama:** `dev`, `HEAD` en `1a2d1ec8` antes de `#008`.

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

- ~~el commit del andamiaje~~, hecho;
- el lote 0b, orden de directorios y builds (P21). **En curso.**
- después, el mapa `../docs/roadmap.md`, cuando el PO dé la orden de trabajar sobre el
  framework.

## Espera al PO

Nada que bloquee.

**Aviso, no pregunta:** el censo cambió dos puntos de la propuesta de carpetas que el PO aceptó.

- **`PHPStanResult.*` se quedan en la raíz.** Moverlos rompía 14 líneas en cuatro instrumentos y
  el instrumental común de los cinco repositorios.
- **`tests.md` va a `.agents/context/` y no a `source-docs/`.** En `source-docs/` pondría rojo
  un censo.

Motivos en el ADR 0006. Si el PO prefiere lo contrario, se reabre.

Siguen abiertas en `docs/pendientes.md` (tras `#008`), sin bloquear nada: qué es el geovisor, el
francés, el rol 50 con nombre `null` y `Components`.

## En curso

`#008`, que el coder hace en este orden:

1. mueve los archivos del ADR 0006;
2. saca del repositorio el build de la documentación de la API;
3. arregla el fallo silencioso de `gulp api-build`;
4. commitea la documentación del arquitecto que quedó pendiente.

Si se corta a medias, habrá commits hechos y archivos aún sin preparar. `git log` y
`git status` dicen dónde quedó.

## Siguiente

1. Evaluar el reporte de `#008`.
2. **Lote 0a**: `dev` en `datastructures` y `html`.
3. El mapa, cuando el PO dé la orden de trabajar sobre el framework.

## Para una sesión nueva

1. **Lo primero que se da al PO** al empezar o retomar el trabajo, cada día, son las dos órdenes
   de renombrado (regla 30, «Nombres de sesión»):
   `/rename PiecesPHPUpgrade-Arquitecto-Main` y `/rename PiecesPHPUpgrade-Coder-Main`. Después
   se comprueba en la lista de sesiones que están puestas.
2. Lee `../HERENCIA.md`: explica el traspaso.
