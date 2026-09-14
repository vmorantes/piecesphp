# Ahora

- **Actualizado:** 2026-09-14 15:05 (medido con `date`)
- **Último mensaje:** `#018 · ARQ`, en vuelo: cierre de BD. El próximo número es `#019`.
- **Tramo en curso:** [`tramos/2026-09-14-1441-mapa-a-la-major.md`](tramos/2026-09-14-1441-mapa-a-la-major.md).
- **Tramo anterior:** [`tramos/2026-09-14-1105-traspaso-y-andamiaje.md`](tramos/2026-09-14-1105-traspaso-y-andamiaje.md),
  cerrado.
- **Informe del estado del proyecto:** [`informe-2026-09-14-estado-del-proyecto.md`](informe-2026-09-14-estado-del-proyecto.md)
- **Sesiones:** arquitecto `PiecesPHPUpgrade-Arquitecto-Main`, coder `PiecesPHPUpgrade-Coder-Main`.
- **Rama:** `dev`, con `HEAD` en `7332f4b9` antes de `#018`. Hay 32 commits sin empujar.
- **Paquetes:** los cuatro, en `dev` desde `#016` (antes estaban en `master`). Sin commits nuevos.

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
   - *Predeterminado*: html sigue con phpstan 2.1.42, declarado así en el registro, y BD se
     cierra con html pendiente.
2. **Subir cuando quieras**: las dos ramas `dev` nuevas de los paquetes y los commits de hoy.
3. **P22**: `TokenModel` firma sus tokens genéricos con una constante del código y no con
   `app_key` (las sesiones sí usan `app_key`). *Predeterminado*: queda anotado y no se toca.

Siguen abiertas en `docs/pendientes.md`: qué es el geovisor, el francés, el rol 50 con nombre
`null` y `Components`.

## En curso

**`#018` — cierre de BD.** Consta de:
- el arreglo de la guarda (redirecciones);
- el bloque del trinquete de los cuatro paquetes, igualado con el de piecesphp;
- la línea base de database a 18, con «3 murieron» por el analizador;
- `shared-toolchain.json` con database, datastructures y geojson en 2.2.12 y html en 2.1.42
  (P23).

Si se corta ahora: `verify-integrity` sigue con 3 fallos en la comprobación 7, y database tiene
los tres `PHPStanResult.*` modificados sin commitear. Los dos arreglan el paso 4 y el paso 3
de `#018`.

## Siguiente

Al recibir `#019`: bitácora 0005 y BD sale del mapa. Si P23 sigue abierta, queda como línea
propia. Después, el lote 2: identificadores de SQL (`18` T167 y T168).

## Para una sesión nueva

1. **Lo primero que se da al PO** al empezar o retomar el trabajo, cada día, son las dos órdenes
   de renombrado (regla 30, «Nombres de sesión»):
   `/rename PiecesPHPUpgrade-Arquitecto-Main` y `/rename PiecesPHPUpgrade-Coder-Main`. Después
   se comprueba en la lista de sesiones que están puestas.
2. Lee `../HERENCIA.md` y el informe del estado del proyecto.
3. **Las horas de este archivo salen de `date`**, no de una estimación.
