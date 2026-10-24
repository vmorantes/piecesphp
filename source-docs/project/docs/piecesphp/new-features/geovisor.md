# El geovisor

Un mapa del panel (**Mapa de contenidos**, ruta `geo-admin-viewer`, `admin/geo/`) al que **cada módulo le registra
sus puntos**. El geovisor no sabe de personas ni de organizaciones: pinta lo que le dan sus **fuentes**. El framework
trae dos, personas (`Directory\Geo\PersonsGeoSource`) y organizaciones (`Organizations\Geo\OrganizationsGeoSource`);
un clon añade las suyas sin tocar JavaScript.

## Qué hace la pantalla

- Pide los puntos de todas las fuentes que el usuario puede ver, los agrupa en círculos con su cifra y los separa al
  acercarse; de cerca, cada punto con su imagen (o sus iniciales) y su nombre.
- «Viendo X de Y» avisa cuando hay más puntos de los que caben en una petición (la cota).
- Filtros y leyenda en paneles flotantes; apagar una fuente en la leyenda la quita del mapa.
- Al pulsar un punto pide su detalle y lo pinta en la tarjeta de su fuente.
- Calles o satélite. El centro y el acercamiento iniciales se configuran en Configuración › Integraciones › Llaves de
  MapBox, «Centro del mapa por omisión».

Sin llave de MapBox no hay mapa: la pantalla enseña el aviso `mapbox-key-missing`.

## Tus datos en el mapa

Una clase que implementa `PiecesPHP\Geo\GeoSource`, una vista para su tarjeta y una línea en el `*Routes` de su módulo.
El ejemplo pone en el mapa los puntos de Ubicaciones (`PiecesPHP\App\Locations\Mappers\PointMapper`, tabla
`locations_points`), que existen en cualquier instalación. **Cada clase va donde la cargue su espacio de nombres**: con `App\Geo`, en la carpeta Geo de la carpeta App
de las clases de la aplicación, y la tarjeta a su lado (probado copiando el ejemplo tal cual el 2026-10-21):

```php
<?php

namespace App\Geo;

use PiecesPHP\App\Locations\Mappers\PointMapper;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Geo\GeoPoint;
use PiecesPHP\Geo\GeoPointsResult;
use PiecesPHP\Geo\GeoQuery;
use PiecesPHP\Geo\GeoSource;
use PiecesPHP\Geo\GeoSourceSql;

class LocationPointsGeoSource implements GeoSource
{
    public function key(): string { return 'location-points'; }
    public function title(): string { return __(LOCATIONS_LANG_GROUP, 'Ubicaciones'); }
    public function permissionRoute(): string { return 'locations-points-list'; }
    public function marker(): string { return GeoSource::MARKER_DOT; }
    public function colorVariable(): string { return '--geo-location-points-color'; }
    public function filters(): array { return []; }

    public function points(GeoQuery $query): GeoPointsResult
    {
        $table = PointMapper::PREFIX_TABLE . PointMapper::TABLE;
        $model = PointMapper::model();
        $model->select(["{$table}.id", "{$table}.name", "{$table}.longitude", "{$table}.latitude"]);
        $model->where(GeoSourceSql::addBboxWhere(new WhereSegment(), "{$table}.longitude", "{$table}.latitude", $query));
        [$total, $rows] = GeoSourceSql::countAndFetch($model, 'id ASC', $query->limit());

        $points = [];
        foreach ($rows as $row) {
            $points[] = new GeoPoint((int) $row['id'], (float) $row['longitude'], (float) $row['latitude'], (string) $row['name']);
        }
        return new GeoPointsResult($points, $total);
    }

    public function pointDetail(int $id): ?array
    {
        $point = new PointMapper($id);
        return $point->id !== null ? ['name' => $point->name, 'address' => $point->address] : null;
    }

    public function thumbnailSource(int $id): ?string { return null; }
    public function cardView(): string { return __DIR__ . '/card.php'; }
}
```

La tarjeta (`card.php`) es **marcado fijo**: el geovisor escribe cada clave de `pointDetail()` como texto en el
elemento con su `data-geo-field`, y pone en `href` la de `data-geo-href`. Nada de PHP con datos dentro:

```php
<div class="geo-card">
    <div class="geo-card-title" data-geo-field="name"></div>
    <div class="geo-card-line" data-geo-field="address"></div>
</div>
```

Y el registro, en el `*Routes` de tu módulo, junto a sus rutas (así lo hacen `DirectoryRoutes` y
`OrganizationsRoutes`):

```php
\PiecesPHP\Geo\GeoSourceRegistry::register(new \App\Geo\LocationPointsGeoSource());
```

El color se declara como variable CSS en una hoja del módulo: `--geo-location-points-color: var(--main-brand-color);`.

## El contrato, método a método

| Método | Qué devuelve | Lo que no se puede olvidar |
| :-- | :-- | :-- |
| `key()` | La clave única de la fuente | Solo letras, números, `-` y `_`: viaja en las URL |
| `title()` | El nombre en la leyenda, ya traducido | Dentro de `__()` |
| `permissionRoute()` | El nombre de una ruta existente | Quien no tiene permiso sobre ESA ruta no ve la fuente ni pide sus puntos, su detalle ni sus miniaturas |
| `marker()` | `GeoSource::MARKER_DOT` o `GeoSource::MARKER_AVATAR` | Con `MARKER_AVATAR`, cada punto lleva su imagen o, sin ella, las iniciales |
| `colorVariable()` | El nombre de una variable CSS (`--geo-…`) | Nunca un color escrito |
| `filters()` | `GeoFilter[]` (texto u opciones) | Los que no declara, los ignora; un valor que no sirve lanza `GeoQueryException` |
| `points()` | `GeoPointsResult` con los puntos del encuadre y el total | El encuadre en `WHERE` con `GeoSourceSql::addBboxWhere()`, y total y página en UNA consulta con `GeoSourceSql::countAndFetch()`; respeta `$query->limit()` |
| `pointDetail()` | Las claves de la tarjeta, o `null` | `null` si el punto no saldría en `points()` (oculto, inactivo, sin permiso): si no, la tarjeta filtra lo que el mapa esconde |
| `thumbnailSource()` | El archivo ORIGINAL de la imagen en disco, o `null` | Nunca una URL. Misma visibilidad que `pointDetail()` |
| `cardView()` | La ruta absoluta de la vista de su tarjeta | Solo marcado fijo con `data-geo-field` y `data-geo-href` |

**La imagen de un punto** (solo con `MARKER_AVATAR`) va como último argumento de `GeoPoint`: la URL que da
`PiecesPHP\Geo\GeoThumbnail::url($clave, $id, $archivoOriginal)`. La miniatura de 64 px (128 en una pantalla de alta densidad,
que el visor pide sola) se genera una vez en `app/cache/geo-thumbnails/`, se regenera si el original cambia —otro
archivo, otra fecha u otro tamaño— y se sirve por `geo-admin-thumbnail` con el permiso de la
fuente, así que un archivo privado no se publica.

## Para cambiar lo que viene

- **Otra tarjeta para personas u organizaciones**: hereda su fuente, cambia `cardView()` y registra la tuya con
  `GeoSourceRegistry::replace()`.
- **Quitar una fuente**: `GeoSourceRegistry::remove('persons')` después de que su módulo la registre.
- **Colores y tamaños**: las variables CSS del geovisor.

## Para medirlo

`bin/cli geo-load-fixture escribir=yes cantidad=<n>` (de 1 a 20.000, solo en local) escribe personas y organizaciones
`zz-carga-` repartidas por Barranquilla; `limpiar=yes` las borra. Con 10.000 puntos (medido el 2026-10-21): mover y
acercar no bloquean; la entrada tarda alrededor de un segundo, porque la vista general pide todos los puntos para
agruparlos.

## Trampas

- **El marcador de una condición se fabrica con su texto**: una expresión con `$`, `^`, `?` o `+` dentro de
  `addBboxWhere()` rompe la consulta («mixed named and positional parameters»). Escribe `$` como `CHAR(36)`, como
  hace la fuente de organizaciones.
- **Las columnas de coordenadas, con índice**: sin él, el encuadre lee la tabla entera. Personas usa el índice `geo`
  (`longitude`, `latitude`) de `user_system_profile`.
