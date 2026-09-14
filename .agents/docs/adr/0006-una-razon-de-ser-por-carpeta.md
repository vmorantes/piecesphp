# 0006 — Una razón de ser por carpeta, y fuera el build de la documentación de la API

- **Estado:** Aceptada
- **Fecha:** 2026-09-14
- **Decide:** Product Owner (acepta la propuesta, P21) · Arquitecto (el alcance, tras medirlo)
- **Estructural:** sí (dónde vive algo)
- **Reemplaza:** 0002, solo en la ubicación de `PENDIENTES.md` y del roadmap posterior a la MAJOR

## En cristiano

`files/` se había vuelto un cajón de sastre: datos que leen las máquinas, documentos del PO y la
documentación de la API con su build compilado. Ahora cada carpeta tiene un solo motivo:
- la documentación para personas va a `source-docs/`, incluida la de la API;
- lo que sigue el PO y lo de agentes va a `.agents/`;
- `files/dev/` guarda solo datos de instrumentos.

El build de la documentación de la API, que nadie leía y estaba desactualizado, deja de
versionarse. Los resultados de PHPStan se quedan en la raíz, porque así están en los cinco
repositorios.

## Contexto

- El PO, el 2026-09-14: *«siento que ese `files/*` y demás se está enredando»*. Aceptó la
  propuesta del tramo de ese día y la de quitar los builds innecesarios, y pidió hacerlo antes de
  trabajar en el framework (P21).
- Censo del 2026-09-14 (subagente, solo lectura, 1.464 archivos de texto; universo sin
  `src/vendor/`, `node_modules/` ni `src/statics/plugins/`):
  - **`files/API/docs-dist/`**: 50 archivos, 7,8 MB, versionado.
    - Nadie lo lee en el repositorio.
    - Está rancio: publica 4 de los 7 módulos, y el último commit que lo tocó es de 2024-12-10.
    - Lo genera `cd src && gulp api-build` (`src/gulpfile.js:407-412`), y esa tarea falla en
      silencio: su callback llama a `done()` sin mirar el error.
  - **`files/dev/`** mezclaba datos que leen instrumentos (JSON de líneas base, inventarios,
    `snapshots/`) con documentos para personas: `PENDIENTES.md`, los 16 de `roadmap/` y
    `tests.md`. Ningún instrumento lee esos documentos: todas sus referencias son documentales.
  - **`PHPStanResult.*`**: cuatro versionados en la raíz.
    - Moverlos rompe 14 líneas en cuatro instrumentos (`bin/phpstan`,
      `bin/phpstan-process-result.php`, `bin/tools/refactorization/Rector.php` y la comprobación
      26 de `VerifyIntegrityTask.php`).
    - Los cuatro paquetes tienen la misma disposición y ninguno tiene `files/`. El
      «instrumental común» (`files/dev/shared-toolchain.json`, comprobación 7) la da por
      compartida.
  - **`tests.md` en `source-docs/`** haría que `bin/censo-rutas-doc` pasara de 0 a 10 rutas
    rotas, porque cita rutas relativas a `src/app/classes/`. Simulado, no ejecutado.
  - **Los otros JS compilados** que se versionan (`UtilPieces.js`, `FormJsonSchema.js`) los
    necesita en ejecución quien clone sin compilar, y las fuentes de `FormJsonSchema.js` no
    están en el repositorio.

## Decisión

Cada carpeta de primer nivel tiene una sola razón de ser, y el único build que sobra sale del
repositorio.

| Antes | Después |
| --- | --- |
| `files/API/docs/`, `files/API/mkdocs.yml`, `files/API/PiecesPHP.postman_collection.json` | `source-docs/api/` (el mismo árbol) |
| `files/API/docs-dist/` | Fuera de git e ignorado. Se genera en `source-docs/api/docs-dist/` |
| `files/dev/PENDIENTES.md` | `.agents/docs/pendientes.md` |
| `files/dev/roadmap/` (16 documentos) | `.agents/docs/roadmap-posterior/` |
| `files/dev/tests.md` | `.agents/context/21-pruebas-y-puertas.md` |
| `PHPStanResult.*` en la raíz | **Se quedan**: instrumental común de los cinco repositorios |
| `files/Webflow/`, `files/CliScripts/` | **Se quedan**: recursos para quien clona. `CliScripts/` además asume profundidad 2 |
| `permissions-and-property.sh`, `package.json`, `jsconfig.json`, `skills-lock.json` | **Se quedan**: el guion asume la raíz, y los otros son configuración de herramientas que los buscan ahí |

Y con esto `files/` queda así: recursos que acompañan al framework, más `files/dev/`, que guarda
solo datos de instrumentos.

**Los registros históricos conservan la ruta de su fecha**: el 18, el §7 del 20, `historico/`,
las entradas ya publicadas del `CHANGELOG.md`, los cuerpos de los ADR, la bitácora y los tramos.
La tabla de arriba es la puerta entre las rutas viejas y las nuevas. Los documentos vivos
(reglas, personas, skills, `context/` fuera del §7 del 20, `README.md`, `source-docs/`, el mapa,
`pendientes.md` y `HERENCIA.md`) se corrigen en la misma tanda.

## Alternativas descartadas

| Alternativa | Por qué no |
| --- | --- |
| Mover los `PHPStanResult.*` a `files/dev/` solo aquí | Rompería la convención del instrumental común de los cinco repositorios, y la comprobación 7 no lo vería: no se examina a sí misma |
| Moverlos en los cinco repositorios | Serían cinco cambios, con una carpeta `files/dev/` nueva en cada paquete, solo por despejar la raíz. El coste no compensa |
| `tests.md` a `source-docs/` | Pondría rojo `bin/censo-rutas-doc`. Además, su lector es quien mantiene el framework, la audiencia de `.agents/context/` |
| `.agents/docs/roadmap/` para el roadmap posterior | Choca en el nombre con `.agents/docs/roadmap.md`, el mapa a la MAJOR |
| Borrar también `UtilPieces.js` y `FormJsonSchema.js` | Los necesita en ejecución quien clone sin compilar, y de `FormJsonSchema.js` no hay fuentes |
| Dejar `files/` como está | Es el enredo que el PO señaló |

## Consecuencias

**Buenas:**
- `files/dev/` pasa a ser solo de máquina: lo que hay ahí lo escriben o lo leen instrumentos.
- Una herramienta de despliegue futura podrá dejar fuera `.agents/` y `source-docs/` con dos
  reglas.
- El repositorio pierde 7,8 MB de build rancio.

**Malas:**
- Una ruta que conocía el PO y la memoria de los agentes (`files/dev/PENDIENTES.md`) cambia.
  Once notas de la memoria nativa nombran `PHPStanResult` o estas rutas: son caché, y se
  actualizan cuando se tocan.
- Los registros históricos citan rutas que ya no existen. La tabla de este ADR hace de puente.
- `pendientes.md` y el roadmap posterior quedan fuera de la guarda de atribución, que solo mira
  `files/` y `source-docs/`. Son documentos del PO y de agentes, donde hablar de agentes está
  permitido; la regla 40 §5 sigue valiendo para ellos.
- Para regenerar la documentación de la API hace falta `mkdocs` con el tema `readthedocs` en la
  máquina. Ya hacía falta antes.

## Reversión

1. `git mv` de vuelta de cada fila de la tabla, en orden inverso.
2. Devolver a su valor la ruta de `src/gulpfile.js` (tarea `api-build`) y los dos docblocks de
   `VerifyIntegrityTask.php`.
3. Quitar de `.gitignore` la línea de `source-docs/api/docs-dist/`. Regenerar `docs-dist/` con
   `cd src && gulp api-build` y volver a versionarlo.
4. Corregir los documentos vivos, con la tabla como guía.

Reversión completa. El build borrado se puede regenerar o recuperar de la historia.

## Verificación

- `git ls-files files/API` y `git ls-files files/dev | grep -v '\.json$'` no devuelven nada,
  salvo `files/dev/snapshots/` si tuviera algo versionado.
- `git grep -nE 'files/dev/(PENDIENTES|tests)\.md|files/dev/roadmap|files/API'` solo encuentra
  registros históricos (el 18, el §7 del 20, el CHANGELOG publicado, los ADR, la bitácora y los
  tramos) y este ADR.
- `node --check src/gulpfile.js` pasa.
- `bin/cli verify-integrity` sigue en verde, con sus 26 comprobaciones.
