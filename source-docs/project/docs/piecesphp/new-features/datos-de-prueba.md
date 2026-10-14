# Datos de prueba

`bin/cli seed` llena la base **local** con registros de prueba reconocibles, y los quita después sin tocar nada más.
Sirve para ver una pantalla con contenido —un listado que pagina, un selector con opciones, un mapa con puntos— sin
crear cada registro a mano.

Cada módulo aporta los suyos **sin tocar el framework**: basta una clase en la carpeta `Seeds/` del módulo.

## Cómo se usa

```bash
bin/cli seed module=all                 # SIMULA: enseña qué escribiría, y no escribe nada
bin/cli seed module=News                # simula, enseñando solo lo de ese módulo
bin/cli seed module=all escribir=yes    # escribe en la base LOCAL, en una transacción
bin/cli seed module=all limpiar=yes     # borra por id lo sembrado, en orden inverso
```

| Parámetro | Qué hace |
| :-- | :-- |
| `module=<Nombre>` o `module=all` | Obligatorio. Un módulo de `src/app/classes`, o todos |
| `cantidad=<n>` | Filas por tabla. Sin él, la cantidad por defecto de cada sembrador |
| `escribir=yes` | Escribe de verdad. Solo con `module=all` |
| `limpiar=yes` | Deshace lo sembrado. Solo con `module=all`. No va junto con `escribir=yes` |

> [!IMPORTANT]
> **Lo seguro es lo que pasa por omisión.** Sin `escribir=yes` ni `limpiar=yes`, la orden **solo simula**. Escribir hay
> que pedirlo escribiéndolo.

**Escribir y limpiar van siempre con `module=all`** porque las dependencias cruzan módulos: los usuarios necesitan
organizaciones, las regiones necesitan países.

## Las protecciones

- **Solo en la instalación LOCAL.** Escribir y limpiar exigen las dos cosas a la vez: la terminal con `--local`
  (`bin/cli` lo añade) **y** el `environment.php` de la instalación diciendo `local`. La bandera sola la puede pasar
  cualquiera en cualquier máquina; las dos juntas, no.
- **Todo o nada.** La escritura va en **una** transacción: si una fila falla, no queda ninguna.
- **No siembra dos veces.** Si ya hay algo sembrado, se niega y pide limpiar antes.
- **Todo lo sembrado se reconoce.** Cada fila lleva en una columna de texto el prefijo **`zz-siembra-`**, y el limpiado
  borra **por id** solo las filas que lo llevan.
- **Las contraseñas de los usuarios sembrados son aleatorias y no se guardan**: nadie entra con ellos. Sus correos
  usan el dominio reservado `.invalid`, que no se entrega nunca.
- **Solo la lanza el usuario principal.** La ruta `terminal-seed` exige sesión y solo la alcanza ese tipo de usuario.

## Qué siembra hoy el framework

Dieciséis tablas, con estas cantidades por defecto. Son **80 filas**, más lo que la aplicación crea sola por ellas:
los perfiles de los usuarios y las aprobaciones de organizaciones, usuarios y publicaciones. El limpiado borra las dos
cosas.

| Módulo | Tabla | Filas | Después de |
| :-- | :-- | --: | :-- |
| Organizaciones | `organizations_elements` | 3 | — |
| Usuarios | `pcsphp_users` | 5, uno por tipo salvo el principal | organizaciones |
| Localizaciones | `locations_countries` | 2 | — |
| | `locations_states` | 3 | países |
| | `locations_cities` | 4 | regiones |
| | `locations_points` | 6 | ciudades |
| Noticias | `news_categories` | 3 | — |
| | `news_elements` | 12, para que el listado pagine | categorías de noticias |
| Publicaciones | `publications_categories` | 4 | — |
| | `publications_elements` | 8, con sus tres imágenes | categorías de publicaciones |
| | `publications_attachments` | 6, en las seis primeras publicaciones | publicaciones |
| Banners | `built_in_banner_elements` | 4, con su imagen | — |
| Formularios | `forms_categories` | 3 | — |
| | `forms_document_types` | 3 | — |
| Documentos | `documents_elements` | 6, con su archivo y su imagen | tipos de documento |
| Boletín | `newsletter_sucribers` | 8 | — |

**Un listado del panel hecho con DataTables no trae las filas en la página**: las pide aparte. Si buscas lo sembrado en
el HTML de un listado así no lo vas a encontrar; está en la respuesta de su tabla. Medido en los de noticias,
categorías de noticias, suscriptores, países, publicaciones, banners y documentos.

## Añadir los datos de prueba de tu módulo

Una clase en la carpeta `Seeds/` de tu módulo, dentro de `src/app/classes/<Modulo>/`, que extienda
`Terminal\Seeding\ModuleSeeder`. El sembrador la
encuentra solo: recorre las carpetas de cada módulo igual que `scheme-create`, a cualquier profundidad.

Así es el de las regiones del framework, que depende de los países:

```php
namespace PiecesPHP\App\Locations\Seeds;

use PiecesPHP\App\Locations\Mappers\CountryMapper;
use PiecesPHP\App\Locations\Mappers\StateMapper;
use Terminal\Seeding\ModuleSeeder;
use Terminal\Seeding\SeedContext;

class StatesSeeder extends ModuleSeeder
{

    public function mapper(): string
    {
        return StateMapper::class;
    }

    public function markerColumn(): string
    {
        return 'name';
    }

    public function dependsOn(): array
    {
        return [CountryMapper::class];
    }

    public function defaultCount(): int
    {
        return 3;
    }

    public function row(int $index, SeedContext $context): array
    {
        return [
            'country' => $context->reference(CountryMapper::class, $index),
            'name' => $this->text('region', $index),
            'active' => StateMapper::ACTIVE,
        ];
    }

}
```

Comprueba que funciona **escribiendo y limpiando**, no solo simulando:

```bash
bin/cli db-backup
bin/cli seed module=all escribir=yes
bin/cli seed module=all limpiar=yes
```

Lo que tienes que dar:

| Método | Obligatorio | Qué es |
| :-- | :-- | :-- |
| `mapper()` | sí | El mapper de la tabla que siembra |
| `markerColumn()` | sí | La columna de **texto** que lleva el prefijo. Sin ella el limpiado no encuentra la fila |
| `row($index, $context)` | sí | La fila número `$index` (desde 0), como columna ⇒ valor |
| `dependsOn()` | no | Los mappers que deben estar sembrados antes. El orden lo resuelve el sembrador |
| `ownedRows()` | no | Las filas que **la aplicación** crea sola en otras tablas por cada fila tuya, para borrarlas antes. Ver más abajo |
| `ownedFolder()` | no | La carpeta en disco que es solo de cada fila, para borrarla entera al limpiar. Ver más abajo |
| `defaultCount()` | no | Cuántas filas sembrar si no se pide otra cantidad. Por defecto, 5 |

Y lo que tienes a mano:

- `$this->text('noticia', $index)` da `zz-siembra-noticia-1`, `-2`… Úsalo en la columna marcadora.
- `$context->reference(OtroMapper::class, $index)` da el id de una fila ya sembrada de una dependencia, repartiendo
  entre las que haya. **Nunca un id inventado**: si la dependencia no tiene filas, se para.
- `$context->existingUserId(UsersModel::TYPE_USER_ROOT)` lee de la base un usuario que ya existe. Sirve para columnas
  como el autor, que no pueden apuntar a un usuario sembrado sin cerrar un ciclo.
- `$context->passwordHash()` da el hash de una clave aleatoria que no se guarda.

> [!IMPORTANT]
> **El sembrador comprueba que tu fila cubra los campos obligatorios** antes de escribir: los que no admiten nulo y no
> tienen valor por defecto, sin contar la clave primaria ni los campos declarados con `'meta' => true`, que no son columnas. Es el mismo criterio con el
> que el ORM se negaría a guardar. Si falta uno, se para y lo nombra.

### Lo que la aplicación crea sola por tus filas: `ownedRows()`

Hay tablas que la aplicación llena por su cuenta cuando aparece una fila tuya: el perfil de un usuario, la aprobación de
una publicación. Si no las declaras, al limpiar se quedan huérfanas. Se declaran por tabla, con la columna que apunta al
id de tu fila:

```php
public function ownedRows(): array
{
    return [UserProfileMapper::TABLE => 'belongsTo'];
}
```

**Si esa tabla apunta a varias tablas por número de id**, la columna sola no basta: el usuario 5 y la publicación 5 tienen
el mismo número. Entonces se añade la condición que distingue las tuyas:

```php
public function ownedRows(): array
{
    return [SystemApprovalsMapper::TABLE => ['column' => 'referenceValue', 'where' => ['referenceTable' => PublicationMapper::TABLE]]];
}
```

> [!IMPORTANT]
> **Sin el `where`, el limpiado borraría aprobaciones de otras tablas que comparten número de id.** La suite
> `unit-tests:core/seed-round-trip` lo vigila con una aprobación señuelo que tiene que sobrevivir.

### Si tu tabla tiene archivos: `copyToUploads()` y `ownedFolder()`

**Nunca apuntes una fila sembrada a un archivo del repositorio.** Al cambiar la imagen de un registro desde el panel, el
framework borra la anterior: editar la fila sembrada borraría el archivo del repositorio. El sembrador **copia** una
imagen de muestra a una carpeta que es solo de esa fila, y al limpiar borra la carpeta entera.

```php
public function ownedFolder(): ?array
{
    return ['base' => 'statics/uploads/publications', 'column' => 'folder'];
}

public function row(int $index, SeedContext $context): array
{
    $folder = $this->text('publicacion', $index);
    return [
        // …
        'mainImage' => $context->copyToUploads('statics/images/generic-image-placeholder.png', 'statics/uploads/publications', $folder, 'imagen-principal.png', true),
        'folder' => $folder,
    ];
}
```

- `copyToUploads($origen, $base, $carpeta, $nombre, $privado)` copia el origen y devuelve la cadena que va en la
  columna, la misma forma que guarda una subida de verdad. Con `$privado = true` el archivo va en disco con el sufijo de
  lo protegido (ver [Archivos protegidos](protected-files.md)), **como nacen las subidas de esa tabla**: imita lo que
  hace su controlador, no elijas tú.
- **Las guardas, y no tienen excepción**: el origen tiene que estar en `statics/images/`; la base, en
  `statics/uploads/`; la carpeta, empezar por el prefijo `zz-siembra-`; y si la carpeta ya existía, se para. Al
  limpiar, una carpeta sin el prefijo, que se salga de su base o que contenga un enlace simbólico **no se borra, y no se
  borra nada más**: se para antes de tocar filas o disco.
- **Si la siembra falla a mitad**, retira también lo que ya había copiado. Un fallo no deja archivos.
- Una subcarpeta va con barra tras el prefijo, como los adjuntos de una publicación: `zz-siembra-publicacion-1/attachments`.
  Esa fila no declara `ownedFolder()`: su carpeta vive dentro de la de su publicación, que se borra después.

> [!NOTE]
> **«Privado en disco» no siempre es «no se sirve».** Una publicación creada por el usuario principal se **aprueba
> sola**, y una publicación activa, en fechas y aprobada es visible: sus archivos se sirven sin sesión aunque en disco
> conserven el sufijo de lo protegido. Las publicaciones sembradas están en ese caso. Los documentos no: sin sesión dan 403.

### Lo que el sembrador se comprueba a sí mismo

Antes de hacer nada corre un **canario**: con cantidad 0 no genera filas, una dependencia sobre una tabla sin sembrador
se detecta, un ciclo también, y la regla de campos obligatorios ve los de la tabla de usuarios. Si algo de eso falla,
no siembra. La suite `unit-tests:core/seed-planner` lo vigila.

## Lo que NO hace, y hay que saberlo

- **No siembra tablas de sistema**: configuración, registros, intentos de acceso, colas, aprobaciones, doble factor.
  Sembrar la configuración, por ejemplo, contaminaría claves reales.
- **`bin/cli seed` corre con una sesión del usuario con id 1**, y algunos mappers ponen ese usuario de autor al guardar,
  pisando el que dé tu sembrador.
- **Pero una suite de pruebas NO tiene sesión.** Las suites son acciones de terminal y no pasan por ese inicio de sesión.
  Si tu mapper exige un usuario al guardar, sembrarlo desde una suite falla con «No hay una sesión de usuario activa».
  La suite `unit-tests:core/seed-round-trip` fija la del usuario principal antes de sembrar y la restaura al terminar:
  haz lo mismo en la tuya.
- **Lo que la aplicación crea en el arranque no existe hasta el siguiente arranque.** Las aprobaciones de lo sembrado
  aparecen en la siguiente petición o en la siguiente orden de terminal, no en la misma que siembra. El limpiado las
  encuentra porque corre en otro arranque; una suite que siembre y limpie en el mismo proceso tiene que provocarlas.
- **Simular no prueba que se pueda escribir**: la simulación no llama a `save()`. Lo que el mapper exija al guardar
  —una sesión, un nombre único— solo aparece con `escribir=yes`. Prueba tu sembrador escribiendo y limpiando en local.
