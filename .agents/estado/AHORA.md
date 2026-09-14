# Ahora

- **Actualizado:** 2026-09-14 13:55 (medido con `date`)
- **Último mensaje:** `#014 · ARQ`, commitear las lecturas de proyectos derivados, P22 y la regla
  «una pregunta se contesta, no se ejecuta». En vuelo. Antes de ella iban 26 commits sin empujar
  en `dev` desde BC.
- **Tramo anterior:** [`tramos/2026-09-14-1105-traspaso-y-andamiaje.md`](tramos/2026-09-14-1105-traspaso-y-andamiaje.md),
  cerrado.
- **Informe del estado del proyecto:** [`informe-2026-09-14-estado-del-proyecto.md`](informe-2026-09-14-estado-del-proyecto.md)
- **Sesiones:** arquitecto `PiecesPHPUpgrade-Arquitecto-Main`, coder `PiecesPHPUpgrade-Coder-Main`.
- **Rama:** `dev`, `HEAD` en `dc389e1f` antes de `#012`.

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

- «Comitea todo» (2026-09-14). **En curso**.
- El mapa `../docs/roadmap.md`, **cuando el PO dé la orden de trabajar sobre el framework**.

## Espera al PO

1. **La orden de trabajar sobre el framework.** El mapa empieza por BD (delegado en el
   arquitecto) y por los identificadores de SQL.
2. **Subir cuando quieras**: las dos ramas `dev` nuevas de los paquetes y los commits de hoy.
3. **P22**: `TokenModel` firma sus tokens genéricos con una constante del código y no con
   `app_key` (las sesiones sí usan `app_key`). *Predeterminado*: queda anotado y no se toca.

Siguen abiertas en `docs/pendientes.md`, sin bloquear nada: qué es el geovisor, el francés, el
rol 50 con nombre `null` y `Components`.

## En curso

`#014`: el coder commitea `pendientes.md`, el informe, la regla 30 y este archivo.

## Siguiente

Con la orden del PO: medir BD, instruirlo y seguir el mapa en su orden.

## Para una sesión nueva

1. **Lo primero que se da al PO** al empezar o retomar el trabajo, cada día, son las dos órdenes
   de renombrado (regla 30, «Nombres de sesión»):
   `/rename PiecesPHPUpgrade-Arquitecto-Main` y `/rename PiecesPHPUpgrade-Coder-Main`. Después
   se comprueba en la lista de sesiones que están puestas.
2. Lee `../HERENCIA.md` y el informe del estado del proyecto.
3. **Las horas de este archivo salen de `date`**, no de una estimación.
