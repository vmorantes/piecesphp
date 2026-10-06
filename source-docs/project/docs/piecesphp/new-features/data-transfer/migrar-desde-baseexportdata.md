# Migrar desde `BaseExportData`

Hasta la `v8.0.0`, los exportadores de un proyecto se escribían extendiendo
`DataImportExportUtility\Controllers\ExportHandlers\BaseExportData`, y el controlador del módulo leía los filtros, montaba
el XLSX con PhpSpreadsheet y escribía las cabeceras de la respuesta. **Esa clase ya no existe** (ruptura 31 del
`CHANGELOG`). Esta página explica cómo pasar uno de esos exportadores al motor nuevo.

## Qué cambia de sitio

| Antes, con `BaseExportData` | Ahora, en una `ExportDefinition` |
| :-- | :-- |
| El controlador lee `startDate`, `endDate`, estados… de la petición y los valida a mano | `parameters()`: filtros declarados, que el motor valida y convierte |
| Un rango de fechas con horas puestas a mano (00:00:00 y 23:59:59), a veces intercambiando si llegan al revés | `ExportParameter::dateRange()`: lo hace el motor |
| Listas blancas de valores comparadas a mano | `ExportParameter::choice()` y `multiChoice()` |
| La consulta, con el WHERE concatenado dentro de `having()` | `rows()`: la misma consulta, **por marcadores**, paginada y con `yield` |
| Closures `data($element)` por columna | `ExportColumn(...)->transform(fn($fila) => …)` |
| Todo escrito como texto (`TYPE_STRING2`); un importe como `number_format(...).' COP'` | Columnas con tipo: `asMoney('COP')`, `asDateTime()`… (se pueden sumar y ordenar) |
| Anchos, centrado, ajuste de texto, encabezado en negrita, paneles congelados y autofiltro, repetidos a mano | `ExportStyle::report()`, que es el estilo por defecto |
| El nombre del archivo en la cabecera, sin codificar tildes ni espacios | `fileName()`; el motor lo limpia y lo codifica bien |
| Guardar el XLSX entero en una cadena y escribirla en la respuesta | El motor escribe en un temporal y lo envía por trozos |
| Marcar los registros como exportados tras generar el archivo | `afterExport()`, que solo se llama si el archivo salió entero |
| La ruta y sus roles, declarados en el controlador | `allowedUserTypes()` y el registro con `DataImportExportUtilityRoutes::exporter()` |

## Pasos

1. **Crea la definición** en tu módulo, extendiendo `ExportDefinition`, con `key()`, `title()` y
   `allowedUserTypes()`. Toma los roles de la ruta vieja.
2. **Pasa los filtros a `parameters()`.** Cada lectura manual de la petición se convierte en un `ExportParameter`. Los
   valores por defecto de negocio («si no llega, solo lo no exportado») van en `->defaultValue()`.
3. **Pasa la consulta a `rows()`.** Copia la selección y los joins, pero **cambia cada valor concatenado por un
   marcador** (`WhereItem`, `WhereSegment`), pagina por id y entrega cada fila con `yield`. Si el exportador viejo hacía
   una consulta por fila (por ejemplo, buscar el usuario de cada registro), guarda los resultados en una caché dentro
   de la definición.
4. **Pasa las columnas a `columns()`.** Cada closure de columna se convierte en `transform()`. Donde había un número
   formateado como texto, pon el tipo que corresponde.
5. **Borra el estilo a mano.** `ExportStyle::report()` ya pone encabezado, bordes, paneles congelados, autofiltro y
   anchos. Si tu informe usaba otros colores, `headerColors()`.
6. **Nombre del archivo:** pasa la lógica a `fileName()`, sin extensión.
7. **Efectos tras exportar:** muévelos a `afterExport()`, en una transacción.
8. **Registra** la definición en el `routes()` de tu módulo y **borra** la ruta, la acción del controlador y la clase
   vieja.
9. **Prueba**, sobre todo el filtro de fechas (sus extremos) y el efecto de `afterExport()`.

El resultado se parece al informe de ejemplo de [Crear un exportador o un informe](exportador.md), que usa los mismos
elementos: rango de fechas, lista de tipos, sí/no, búsqueda de texto, dos hojas, totales y logo.

## Un fallo que conviene revisar al migrar

Si el exportador viejo filtraba por fechas o estados **concatenando** valores en el SQL, hoy era seguro solo porque esos
valores salían de listas cerradas o de un `DateTime`. Al pasar a `rows()`, cámbialos por marcadores aunque parezcan
inofensivos: el día que alguien añada un filtro de texto, la consulta seguirá siendo segura.
