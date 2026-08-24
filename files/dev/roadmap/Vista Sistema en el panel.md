# IDEA · Vista «Sistema» en el panel

**Intención declarada por el PROPIETARIO — 2026-08-24. SIN RESOLVER.** No es una decisión cerrada
y no autoriza nada.

Al estilo de la pantalla de salud de sitio de WordPress: una página que diga en qué estado está
este despliegue.

## Dónde vive

**En `app_configurations`, junto a `routes`.** No en «Acerca de», que es estática — esto no lo es.

## Los cuatro puntos que la gobiernan

### (a) DERIVAR, no escribir a mano

Que **renderice la salida de lo que ya se mide**: `verify-integrity`, `snapshot`, el inventario de
rutas, `scheme-sql-round-trip`, `live-cache`. **Una página de estado es documentación, y la que no
se deriva se pudre** — es la misma ley que ya nos ha mordido con `.gitattributes` y con
`volatile-state`.

### (b) El dato que hoy NO se puede ver

**Qué versión del framework es este despliegue, qué versiones tiene de los cuatro paquetes, y
cuánto se ha desviado del original.** Eso no lo da WordPress y es justo lo que hace falta en un
framework que se clona y cada copia envejece por su cuenta.

### (c) Control por rol

Como el resto de `app_configurations`, **sin excepción**.

### (d) REGLA: hechos, nunca valores

Muestra **hechos sobre versiones y estado**, **nunca valores de configuración**.

> `CronJobKey: definida (sí)` — **jamás la llave**.

## Inventario de qué mostrar, agrupado

| Grupo | Qué |
| :-- | :-- |
| **Entorno de ejecución** | Versión de PHP del SAPI que sirve, extensiones cargadas, ajustes de OPcache, límites (`memory_limit`, `max_execution_time`, tamaño de subida) |
| **Base de datos** | Motor y versión, juego de caracteres de la conexión y de la base, número de tablas, y si el esquema declarado coincide con el real |
| **Framework y paquetes** | `APP_VERSION`, versión de los cuatro paquetes `piecesphp/*`, y **cuánto se ha desviado este despliegue** |
| **Salud propia** | Última corrida de `verify-integrity` y su resultado, baseline de PHPStan, última restauración, última foto |
| **Sistema de archivos** | Permisos de escritura donde hacen falta, tamaño de `dumps/`, `files/`, `bundle/` |

## La tensión, dicha

**Cuanto más exhaustiva sea la vista, mayor la tentación de escribir filas a mano.** Por eso:
**cada fila debe declarar su origen** — de qué comando o de qué constante sale. Una fila sin origen
declarado es una que alguien tecleó, y esa es la que mentirá primero.
