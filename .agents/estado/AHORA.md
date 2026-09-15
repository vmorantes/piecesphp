# Ahora

- **Actualizado:** 2026-09-15 10:56 (medido con `date`).
- **Actualizado:** 2026-09-15 11:09 (medido con `date`). **Decisión del PO sobre `process()` y
  las pruebas:**
  - aplicar las mejoras y probarlo todo contra la aplicación local, con navegador simulado,
    credenciales de prueba y registros creados;
  - todo tiene que seguir funcionando como está programado;
  - las mejoras de rendimiento y seguridad son bienvenidas.
  Queda en el **ADR 0010** y en la regla 40 §2. En vuelo: **`#045`**, el buscador de
  `process()` con la opción B, probado de punta a punta.
- **Actualizado:** 2026-09-15 12:11 (medido con `date`).
- **Último mensaje enviado:** `#049 · ARQ`, SystemApprovals y LoginAttempts, fase 2: aplicar la
  propuesta revisada, retirar `generateHaving()` y poner `@deprecated` en `escapeString()`, con
  la foto de después comparada con la de antes. El próximo número es `#050`.
- **`#048 · COD`:** la propuesta es equivalente en 72 de 72 casos, y la foto de antes está hecha
  (50 capturas). El arquitecto la revisó: C1 agrupado con el `IS NULL` conservado, C3 con
  `(int)`, y LoginAttempts incluido para poder retirar `generateHaving()`.
- **`#046 · COD`** (recibido a las 11:40, medido con `date`):
  - **Completado:** el buscador de `process()` por marcador en 18 de 21 listados, con la misma
    respuesta en las búsquedas normales. El arquitecto lo verificó por su cuenta: 105
    comparaciones sin diferencias.
  - **Queda:** el filtro de SystemApprovals, que son reglas de acceso y se hablan con el PO.
  - El coder espera instrucción.
- **Respuestas del PO (11:47, medido con `date`):**
  1. **SystemApprovals: aceptado.** Confía en que no se perderá funcionalidad. Va en `#047`
     como una propuesta que el arquitecto revisa antes de aplicarla.
  2. **P28:** el PO entiende que las traducciones son estáticas, y dinámicas en
     `configurations.js`. El arquitecto verificó que esa función de `configurations.js` (línea
     1501) es la que llama a la ruta de guardado. Se le explica con contexto.
  3. **La poda:** la decide el arquitecto. Irá al cierre de este tramo, con el curador.
  4. **Queja del PO:** los resúmenes al detenerse no seguían la forma acordada. Queda en la
     regla 30, en «Tramos y rondas».
- **`#044 · COD`** (11:04):
  - T1 de `#042`, commiteada (`b52172f8`, `dd07e06e`).
  - T2 solo llegó a medir, con una sonda temporal que se borró: `generateHaving()` y
    `generateHavingGroup()` dan **las mismas filas** en `locations_countries`,
    `locations_states` y `publications_elements`, y también en una búsqueda sin resultados.
  - No se tocó ningún archivo de producción. El coder espera instrucción.
- **`#043 · ARQ`** retiró T2 y T3 de `#042`.
  - `#042` tocaba `DataTablesHelper::process()`, un elemento transversal del núcleo, sin haberlo
    hablado con el PO.
  - El PO: esos cambios se conversan primero. Queda en la regla 30, puntos serios.
  - Solo sigue T1 de `#042`, los commits de documentación.
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
   - **✔ RESUELTA por el PO (2026-09-15): opción B aprobada.**
     - El servidor traduce y guarda en una sola petición. El navegador solo pide las claves
       que faltan; el servidor saca los textos originales de sus archivos de idioma, llama a la
       IA, comprueba que vuelvan esas mismas claves y guarda. `saveGroup` deja de aceptar texto
       del navegador.
     - Sin límite de uso de la IA por ahora: el PO está centrado en la MAJOR y sus problemas.
     - Es el lote 3b. Se instruye después de `#048`, con pruebas en el navegador simulado para
       cada idioma.
     - Al recibir `#048` se deposita en `pendientes.md` y en el mapa.
   - **El predeterminado anterior («solo administración») queda DESCARTADO.** La ruta la usa
     la traducción automática de `configurations.js`: el navegador de cualquier usuario guarda
     lo que traduce la IA. Restringirla rompería esa función.
   - *Predeterminado nuevo, que se habla con el PO*: el servidor deja de aceptar el texto del
     navegador. Solo acepta claves que existen, en idiomas y grupos reales, o, mejor, traduce
     él mismo. El HTML se conserva.
   - Es el lote 3b del mapa y espera la respuesta.
2. **El plan de `process()`: se conversa con el PO antes de instruirlo** (regla 30, núcleo
   transversal). Hay dos propuestas: la del coder (migrar llamador a llamador, 18 archivos en
   tres tandas) y la del arquitecto (`process()` crea el segmento; un archivo más los
   llamadores con `having_string`).
3. **P26 (FileManager), P25 (candidata) y Locations:** con predeterminado.
4. **Subir cuando quiera:** 33 commits.

## En curso

**`#042` con `#043`:** solo T1, que commitea el `CHANGELOG.md`, `pendientes.md`, el mapa y el
estado. T2 y T3 están retiradas hasta hablarlo con el PO. Si el coder ya había empezado, deja el
árbol como esté, sin commitear ni revertir, y lo reporta en `#044`.

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
