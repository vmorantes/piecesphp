# PiecesPHP — instrucciones de proyecto

Framework PHP modular propio sobre Slim 4. Antes de tocar código, lee
**[`.agents/context/README.md`](./.agents/context/README.md)**: es el índice de los
documentos que cubren arquitectura, convenciones y recetas. Lo ya ejecutado y cerrado
vive en `.agents/context/historico/`, y no es trabajo pendiente.

## Ruta rápida según la tarea

| Vas a… | Lee primero |
| :-- | :-- |
| Crear o modificar un módulo | `.agents/context/07-modulos.md` y `13-recetas.md` |
| Tocar rutas o permisos | `.agents/context/05-routing-y-permisos.md` |
| Tocar mappers o base de datos | `.agents/context/06-orm-mappers.md` y `11-base-de-datos.md` |
| Clonar un módulo desde Publications | `.agents/context/15-plantilla-clonar-publications.md` |
| Entender la migración de PHP, ya ejecutada | `.agents/context/historico/` |
| Entender por qué algo está como está | `.agents/context/14-deuda-y-limpieza.md` |

## Reglas que no se negocian

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

## Herramientas

```bash
bin/cli <acción>        # CLI del framework (help lista las acciones)
bin/phpstan             # análisis estático (nivel 8) -> PHPStanResult.Summary.txt
bin/phpstan-deadcode    # mide las ramas muertas que phpstan.neon silencia
bin/rector              # refactor automatizado, configurado en bin/tools/refactorization
cd src && gulp init-project   # compilar SASS y TypeScript
```

Ejecuta `bin/phpstan` antes de dar por cerrado cualquier cambio de tamaño y compara
contra `PHPStanResult.Summary.baseline.txt`.

## Al terminar una funcionalidad

Añade la entrada en `CHANGELOG.md` con el formato existente. Si el cambio invalida un
documento de `.agents/context/` o de `source-docs/`, corrígelo **en el mismo commit**:
ninguno de los dos puede mentir.
