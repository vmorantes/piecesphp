# Ahora

- **Actualizado:** 2026-09-14 14:52 (medido con `date`)
- **Último mensaje:** `#016 · ARQ`, en vuelo: lote BD. El próximo número es `#017`.
- **Tramo en curso:** [`tramos/2026-09-14-1441-mapa-a-la-major.md`](tramos/2026-09-14-1441-mapa-a-la-major.md).
- **Tramo anterior:** [`tramos/2026-09-14-1105-traspaso-y-andamiaje.md`](tramos/2026-09-14-1105-traspaso-y-andamiaje.md),
  cerrado.
- **Informe del estado del proyecto:** [`informe-2026-09-14-estado-del-proyecto.md`](informe-2026-09-14-estado-del-proyecto.md)
- **Sesiones:** arquitecto `PiecesPHPUpgrade-Arquitecto-Main`, coder `PiecesPHPUpgrade-Coder-Main`.
- **Rama:** `dev`, con `HEAD` en `bb58e8d8` antes de `#016`. Hay 29 commits sin empujar.

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

No bloquea nada:

1. **Subir cuando quieras**: las dos ramas `dev` nuevas de los paquetes y los commits de hoy.
2. **P22**: `TokenModel` firma sus tokens genéricos con una constante del código y no con
   `app_key` (las sesiones sí usan `app_key`). *Predeterminado*: queda anotado y no se toca.

Siguen abiertas en `docs/pendientes.md`: qué es el geovisor, el francés, el rol 50 con nombre
`null` y `Components`.

## En curso

**`#016` — lote BD**: los cuatro paquetes pasan a phpstan 2.2.12 y rector 2.6.6, se miden
contra su línea base (21, 0, 0 y 3) y se actualiza `analyzers` en
`files/dev/shared-toolchain.json`. El coder commitea primero lo del arquitecto (ADR 0007,
guarda, reglas, mapa, pendientes y estado).

Si se corta ahora: puede quedar un paquete en `dev` con el `vendor/` actualizado y sin
registrar. Se ve con `bin/cli verify-integrity` (comprobación 7).

## Siguiente

Al recibir `#017`: bitácora 0005, BD sale del mapa y se pasa al lote 2, identificadores de SQL
(`18` T167 y T168).

## Para una sesión nueva

1. **Lo primero que se da al PO** al empezar o retomar el trabajo, cada día, son las dos órdenes
   de renombrado (regla 30, «Nombres de sesión»):
   `/rename PiecesPHPUpgrade-Arquitecto-Main` y `/rename PiecesPHPUpgrade-Coder-Main`. Después
   se comprueba en la lista de sesiones que están puestas.
2. Lee `../HERENCIA.md` y el informe del estado del proyecto.
3. **Las horas de este archivo salen de `date`**, no de una estimación.
