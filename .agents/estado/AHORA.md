# Ahora

- **Actualizado:** 2026-09-15 10:56 (medido con `date`).
- **Último mensaje:** `#042 · ARQ`, en vuelo: el buscador de `process()` pasa a marcador. El
  próximo número es `#043`.
- **`#041`: completado.**
  - `sqlStringLiteral()` en 11 etiquetas de seis mappers (`c250c2ee`); PHPStan 737.
  - El plan de `process()`, medido.
  - El estudio del 4b, hecho.
  - **H1: las traducciones dinámicas tienen el control de acceso roto y un XSS almacenado**
    (P28).
- **Tramo en curso:** [`tramos/2026-09-15-1022-lote-4-y-estudio-4b.md`](tramos/2026-09-15-1022-lote-4-y-estudio-4b.md).
- **Informe del estado del proyecto:** [`informe-2026-09-14-estado-del-proyecto.md`](informe-2026-09-14-estado-del-proyecto.md).
- **Sesiones:** arquitecto `PiecesPHPUpgrade-Arquitecto-Main`, coder `PiecesPHPUpgrade-Coder-Main`.
- **Rama:** `dev`, en `4b3d3d58`. Hay 33 commits sin empujar en piecesphp.

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

1. **⚠ P28 — quién puede editar las traducciones.**
   - Hoy puede cualquier usuario con sesión, de cualquier rol (`APIController.php:1611`).
   - Lo que guarda se imprime sin escapar, así que es un XSS almacenado.
   - *Predeterminado*: solo administración (root y admin), una lista blanca de grupos y de
     idiomas, y el texto sin HTML salvo en los grupos declarados.
   - Es el lote 3b del mapa y espera la respuesta.
2. **El plan de `process()`**, presentado en el chat: la contrapropuesta del arquitecto toca un
   archivo, más los llamadores con `having_string`. Va en `#042` salvo que el PO diga lo
   contrario.
3. **P26 (FileManager), P25 (candidata) y Locations:** con predeterminado.
4. **Subir cuando quiera:** 33 commits.

## En curso

**`#042`**, en tres tareas:
- T1: commitear lo del arquitecto: el `CHANGELOG.md`, `pendientes.md`, el mapa y el estado.
- T2: el buscador de `DataTablesHelper::process()` pasa a marcador. Si el llamador no pasa un
  `having_string` con contenido, `process()` crea el `HavingSegment` y le une el grupo de
  búsqueda. Primero se comprueba que `generateHavingGroup()` da lo mismo que `generateHaving()`.
- T3: los llamadores con `having_string`: SystemApprovals (`elapsedDays`, de la petición, a
  marcador) y los que lo pasan vacío o muerto.

Si se corta ahora: puede quedar `DataTablesHelper` a medio cambiar. `git status` y `gates` lo
dicen.

## Siguiente

- Con P28: el lote 3b (traducciones).
- El ADR del 4b, con el estudio de `#041` delante: la decisión por el nombre (el sufijo
  `.protected`), `Core/Statics/`, `Range`, `Cache-Control: private`, `Vary` y elFinder.

## Para una sesión nueva

1. **Lo primero que se da al PO** al empezar o retomar el trabajo, cada día, son las dos órdenes
   de renombrado (regla 30, «Nombres de sesión»):
   `/rename PiecesPHPUpgrade-Arquitecto-Main` y `/rename PiecesPHPUpgrade-Coder-Main`. Después
   se comprueba en la lista de sesiones que están puestas.
2. Lee `../HERENCIA.md`, el informe del estado del proyecto y el último tramo.
3. **Las horas de este archivo salen de `date`**, no de una estimación.
