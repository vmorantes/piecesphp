# Ahora

- **Actualizado:** 2026-09-15 10:22 (medido con `date`).
- **Último mensaje:** `#040 · ARQ`, en vuelo: lote 4, bloque 2, y el estudio del 4b. El próximo
  número es `#041`.
- **Tramo en curso:** [`tramos/2026-09-15-1022-lote-4-y-estudio-4b.md`](tramos/2026-09-15-1022-lote-4-y-estudio-4b.md).
- **Tramo anterior:** [`tramos/2026-09-14-1726-desatendido-lote-3a.md`](tramos/2026-09-14-1726-desatendido-lote-3a.md),
  cerrado.
- **Informe del estado del proyecto:** [`informe-2026-09-14-estado-del-proyecto.md`](informe-2026-09-14-estado-del-proyecto.md).
- **Sesiones:** arquitecto `PiecesPHPUpgrade-Arquitecto-Main`, coder `PiecesPHPUpgrade-Coder-Main`,
  los dos renombrados hoy.
- **Rama:** `dev`, en `08a3c2e6`. Hay 29 commits sin empujar en piecesphp.

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

Nada bloquea. Abiertas, con predeterminado, en `docs/pendientes.md`:
1. **P26 — FileManager.** Los archivos del editor irían a la carpeta de su registro, el
   FileManager general pediría sesión y lo existente quedaría declarado.
2. **P25 (candidata)** — una publicación sin aprobar se ve por su enlace directo.
3. **Locations** — los listados públicos se quedan públicos.
4. **El plan de los 14 llamadores de `process()`** (regla de los diez): se le presenta al
   recibir `#041`.
5. **Subir cuando quiera**: 29 commits.

## En curso

**`#040`**, en cuatro tareas:
- T1: commitear lo del arquitecto: `pendientes.md`, el mapa y el estado.
- T2: la familia de las etiquetas en literal JSON del `SELECT`, en seis mappers, con un ayudante
  que emite literales hexadecimales. Salen los cuatro `escapeString()` de `OrganizationMapper`.
- T3: medir, sin cambiar nada, el plan de los 14 llamadores de `DataTablesHelper::process()`.
- T4: estudiar el 4b en solo lectura: las opciones de elFinder (detectar el tipo por contenido,
  bloquear por patrón, servir por el conector) y lo que `ServerStatics` necesita para lo
  protegido.

Si se corta ahora: puede quedar código a medio cambiar en los mappers. `git status` y
`verify-integrity` lo dicen.

## Siguiente

- Con `#041`: presentar al PO el plan de los 14 llamadores; con el estudio, el ADR del 4b.
- Después, el lote 5 (OTP) y el 5b (tokens genéricos).

## Para una sesión nueva

1. **Lo primero que se da al PO** al empezar o retomar el trabajo, cada día, son las dos órdenes
   de renombrado (regla 30, «Nombres de sesión»):
   `/rename PiecesPHPUpgrade-Arquitecto-Main` y `/rename PiecesPHPUpgrade-Coder-Main`. Después
   se comprueba en la lista de sesiones que están puestas.
2. Lee `../HERENCIA.md`, el informe del estado del proyecto y el último tramo.
3. **Las horas de este archivo salen de `date`**, no de una estimación.
