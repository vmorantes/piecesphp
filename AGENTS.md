# AGENTS.md

Punto de entrada y **fuente de las reglas del proyecto** para cualquier agente o herramienta
(ADR 0014). `CLAUDE.md` de la raíz solo importa este archivo. Lo propio de Claude Code está en
`.claude/CLAUDE.md`.

**Proyecto**: PiecesPHP, framework PHP modular propio sobre Slim 4. Es una plantilla que se
clona: cada despliegue es un consumidor futuro, así que la regla no es «no rompas
producción», sino «no embarques una trampa».

## Lee, en este orden

1. `.agents/estado/AHORA.md` — qué está en curso, qué espera al Product Owner, qué número de
   mensaje toca.
2. Este archivo, entero.
3. `.agents/README.md` — mapa de la documentación para agentes y orden de lectura. Antes de
   tocar código, `.agents/context/README.md`: el índice de arquitectura, convenciones y recetas.
   Lo ya ejecutado y cerrado vive en `.agents/context/historico/` y no es trabajo pendiente.
4. `.agents/rules/` — **todas**. Si tu herramienta no las carga sola, léelas a mano.

## Ruta rápida según la tarea

| Vas a… | Lee primero |
| :-- | :-- |
| Crear o modificar un módulo | `.agents/context/07-modulos.md` y `13-recetas.md` |
| Tocar rutas o permisos | `.agents/context/05-routing-y-permisos.md` |
| Tocar mappers o base de datos | `.agents/context/06-orm-mappers.md` y `11-base-de-datos.md` |
| Clonar un módulo desde Publications | `.agents/context/15-plantilla-clonar-publications.md` |
| Entender la migración de PHP, ya ejecutada | `.agents/context/historico/` |
| Entender por qué algo está como está | `.agents/context/14-deuda-y-limpieza.md` |
| Trabajar como arquitecto o coder de este repositorio | `.agents/estado/AHORA.md` y `.agents/rules/30-protocolo-coder.md` |

## No se negocia (trabajo)

- Tres roles: el arquitecto decide y documenta; el coder implementa, verifica y commitea
  (`.agents/rules/30-protocolo-coder.md`).
- Ningún cambio de estado de git sin orden. Nunca `git add .`: rutas explícitas y
  `bin/guarda-add`. `git push`, nunca. Ni etiquetas ni ramas nuevas en este repositorio sin el
  PO (en los paquetes, la regla 30).
- Nunca imprimir `.git/config` ni `git remote -v`: los remotos llevan credenciales.
- Ninguna conexión a servidores ni a bases de datos sin permiso. Ninguna dependencia nueva sin
  proponerla con alternativas.
- Cero atribución a IA en commits, código y documentación para personas.
- Nada inventado: lo que no se verificó, se dice.
- El repositorio manda sobre la memoria de la sesión (`.agents/rules/10-memory-contract.md`).

## Reglas que no se negocian (código)

1. **Idioma**: código, clases, métodos, variables, tablas y columnas en **inglés**.
   UI, mensajes y validaciones en **español**, siempre dentro de `__($grupo, 'Texto')`.
2. **Rutas**: solo con `PiecesPHP\Core\Route` y `RouteGroup`. Nunca `$app->get(...)`
   directo — el nombre de la ruta *es* el identificador de permiso.
3. **URLs**: `Controller::routeName('sufijo', $params)` o `get_route()`. Nunca
   concatenar cadenas.
4. **Visibilidad en menús**: `Controller::allowedRoute(...)` o `Roles::hasPermissions(...)`.
5. **Todo método de ruta devuelve un `Response`.**
6. **Assets**: `add_global_asset` / `set_custom_assets` / `import_*`. Nunca `<script src>`
   suelto en la vista.
7. **Tablas**: se definen en `$fields` del mapper y el SQL sale de
   `bin/cli scheme-create module=<Nombre>` (su inverso, `scheme-drop`). No se escribe
   `CREATE TABLE` a mano.
8. **No se edita `src/vendor/`** ni los paquetes `piecesphp/*` desde aquí: son repos
   aparte.
9. **La memoria de un agente es una caché del registro.** Lo que guardes en tu memoria
   persistente solo puede ser algo que YA VIVA en `.agents/context/`, más el puntero a su
   sección. Si la memoria contiene algo que el registro no tiene son dos verdades sin puerta
   entre ellas: **eso es el hallazgo**, y se resuelve subiéndolo al registro, no borrándolo
   de la memoria. Ver `.agents/context/20-contrato-de-trabajo.md` §6 y T103 del 18.

## Módulo de referencia

**`src/app/classes/Publications`** es la referencia canónica: zona admin y pública,
sub-entidad con CRUD propio, adjuntos, traducción de campos, caché y aprobaciones.
Cuando dudes de un patrón, búscalo ahí. `News` sirve solo como ejemplo mínimo
solo-admin y está marcado «por renovar»: no lo uses como referencia de estilo.

## Lo imprescindible de esta máquina y este repositorio

- `php` a secas es 8.1.34; el proyecto va con 8.5. `bin/cli` y `bin/phpstan` lo eligen solos.
- `grep` es ugrep: el `$` ancla incluso en medio del patrón. Literales con `grep -F`.
- Verificación del andamiaje: `bash .agents/scripts/verificar.sh`. Del producto: la que dicte
  la instrucción (por defecto `bin/phpstan`, `bin/cli verify-integrity` y `bin/cli gates`).
- **El hook `commit-msg` rechaza la atribución a IA en los commits de cualquier herramienta.
  Actívalo en cada clon y en cada máquina:** `git config core.hooksPath .agents/scripts/git-hooks`.
  `verificar.sh` falla si falta (A-011).

## Herramientas

```bash
bin/cli <acción>        # CLI del framework (help lista las acciones)
bin/phpstan             # análisis estático (nivel 8) -> PHPStanResult.Summary.txt
bin/phpstan-deadcode    # mide las ramas muertas que phpstan.neon silencia
bin/rector              # refactor automatizado, configurado en bin/tools/refactorization
cd src && gulp init-project   # compilar SASS y TypeScript (el JS de configuración: gulp js-vendor)
bash .agents/scripts/verificar.sh   # andamiaje de agentes: guarda, generados, enlaces, hook
```

Ejecuta `bin/phpstan` antes de dar por cerrado cualquier cambio de tamaño y compara
contra `PHPStanResult.Summary.baseline.txt`.

## Al terminar una funcionalidad

Añade la entrada en `CHANGELOG.md` con el formato existente (en el modelo de tres roles la
escribe el arquitecto: `.agents/rules/30-protocolo-coder.md`). Si el cambio invalida un
documento de `.agents/context/` o de `source-docs/`, corrígelo **en el mismo commit**:
ninguno de los dos puede mentir.
