# Ahora

- **Actualizado:** 2026-09-14 15:21 (medido con `date`), tras la interrupción de las 15:18, en
  la que no se perdió nada.
- **Último mensaje:** `#020 · ARQ`, en vuelo: lote 2, bloque 1. El próximo número es `#021`.
- **Tramo en curso:** [`tramos/2026-09-14-1441-mapa-a-la-major.md`](tramos/2026-09-14-1441-mapa-a-la-major.md).
- **Tramo anterior:** [`tramos/2026-09-14-1105-traspaso-y-andamiaje.md`](tramos/2026-09-14-1105-traspaso-y-andamiaje.md),
  cerrado.
- **Informe del estado del proyecto:** [`informe-2026-09-14-estado-del-proyecto.md`](informe-2026-09-14-estado-del-proyecto.md)
- **Sesiones:** arquitecto `PiecesPHPUpgrade-Arquitecto-Main`, coder `PiecesPHPUpgrade-Coder-Main`.
- **Rama:** `dev`, en `7b45c761`. Hay 36 commits sin empujar.
- **Paquetes:** los cuatro, en `dev`, cada uno con commits de instrumental sin empujar:
  - database, 2;
  - datastructures, 1;
  - geojson, 1;
  - html, 1.

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
- dependencias, builds, servidores y credenciales. Excepción: las herramientas de análisis
  (ADR 0007).

Si la herramienta del coder pide confirmación al commitear, la da el PO en esa sesión.

**Trabajo nombrado por el PO:** el mapa, `../docs/roadmap.md`, en su orden. Orden del PO:
«Trabaja. Adelante.» (2026-09-14).

## Espera al PO

1. **P23 — html sin nivelar.**
   - El lock local de html, que no se versiona, fija `piecesphp/datastructures` v3.1.0, y su
     `composer.json` pide `^4.0` desde su versión 3.0.0.
   - Para pasar html a phpstan 2.2.12 hay que actualizar también `piecesphp/datastructures`,
     que no es una herramienta de análisis.
   - *Predeterminado*: html sigue con phpstan 2.1.42, declarado así en el registro. Es lo único
     que queda de BD.
2. **Subir cuando quieras**: los commits de hoy aquí y en los cuatro paquetes, y las dos ramas
   `dev` nuevas.
3. **P22**: `TokenModel` firma sus tokens genéricos con una constante del código y no con
   `app_key` (las sesiones sí usan `app_key`). *Predeterminado*: queda anotado y no se toca.

Siguen abiertas en `docs/pendientes.md`: qué es el geovisor, el francés, el rol 50 con nombre
`null` y `Components`.

## En curso

**`#020` — lote 2, bloque 1: `bin/censo-sql-identificadores`.**
- Mide las posiciones de identificador que ningún censo mira: `select`, `get` (argumentos 2 y
  3), `setTable`, `rowCount`, la tabla de `join` y sus variantes, y las claves `select_fields`,
  `columns_order` y `custom_order`.
- Sin trinquete y sin arreglos. Queda registrado en `sql-concat-baseline.json`.
- El PASO 1 commitea lo del arquitecto: la bitácora 0005, el mapa, `pendientes.md` y el estado.

Si se corta ahora: puede quedar el censo a medio escribir en `bin/`, o el registro a medias.
Nada del producto.

## Siguiente

Con la cifra del censo: decidir los arreglos del lote 2. Candidato claro: la dirección de
`custom_order`. Y decidir si el lote cruza al paquete database, donde viven `select`, `get`,
`rowCount` y `setTable`.

## Para una sesión nueva

1. **Lo primero que se da al PO** al empezar o retomar el trabajo, cada día, son las dos órdenes
   de renombrado (regla 30, «Nombres de sesión»):
   `/rename PiecesPHPUpgrade-Arquitecto-Main` y `/rename PiecesPHPUpgrade-Coder-Main`. Después
   se comprueba en la lista de sesiones que están puestas.
2. Lee `../HERENCIA.md` y el informe del estado del proyecto.
3. **Las horas de este archivo salen de `date`**, no de una estimación.
