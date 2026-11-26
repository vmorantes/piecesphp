# Los tipos de usuario

Cada proyecto decide qué tipos de usuario tiene: añade los suyos, ajusta los de fábrica (su nombre, su perfil, su
organización, su prioridad…), deja de ofrecer alguno al crear usuarios o lo retira. Se hace en un archivo del proyecto, `src/app/config/user-types.php`, que solo
lleva **las diferencias** con los de fábrica. Sin ese archivo, o vacío, los tipos son los de fábrica.

Lo lee el registro `PiecesPHP\UserSystem\UserTypes`, que es lo único que el código consulta para saber qué tipos hay
(ADR 0078; los ajustes de los de fábrica, ADR 0090).

## Los de fábrica

| Tipo | Código | Constante | Se puede retirar |
| :-- | --: | :-- | :-- |
| Principal | 0 | `UsersModel::TYPE_USER_ROOT` | No |
| Administrador general | 1 | `UsersModel::TYPE_USER_ADMIN_GRAL` | No |
| Usuario general | 2 | `UsersModel::TYPE_USER_GENERAL` | Sí (avisa si el alta por API o la importación lo usan) |
| Institucional | 3 | `UsersModel::TYPE_USER_INSTITUCIONAL` | Sí (avisa: es autor elegible en Publicaciones) |
| Comunicaciones | 4 | `UsersModel::TYPE_USER_COMUNICACIONES` | Sí (avisa: es autor elegible en Publicaciones) |
| Administrador de organización | 12 | `UsersModel::TYPE_USER_ADMIN_ORG` | Sí (avisa si el alta por API o las organizaciones lo usan) |

Dos códigos están **reservados** y no se pueden declarar para otra cosa: el **99**, el usuario del sistema, y el **50**,
Google Play, que viene apagado y solo se puede encender declarándolo.

## El archivo

```php
<?php
/**
 * @pcsphp-config clon
 * Qué conviene editar aquí: todo. Añade, ajusta o retira tipos de usuario; root y administración general no se retiran.
 */

use PiecesPHP\UserSystem\ORM\UsersModel;

return [
    'types' => [
        //Un tipo propio: id, etiqueta y prioridad son obligatorios.
        [
            'id' => 5,
            'label' => 'Telemarketing',
            'translations' => ['en' => 'Telemarketing'],
            'priority' => 3,
        ],
        //Uno de fábrica: admite lo mismo que uno propio salvo id; root y la administración general, solo textos,
        //creatable, profile y routes.
        ['id' => UsersModel::TYPE_USER_INSTITUCIONAL, 'profile' => true],
    ],
    'retire' => [],
];
```

Es un archivo **solo de datos**: se lee muy pronto en el arranque, antes de las traducciones y de las constantes de los
módulos. No llames a `__()` ni a otros ayudantes, ni uses constantes de módulos.

### Lo que declara un tipo

| Clave | Para qué | Por omisión, en un tipo propio |
| :-- | :-- | :-- |
| `id` | Su código. Entero, no negativo, ni 99; el 50 solo para encender Google Play, con su `label` (admite lo mismo que un tipo de fábrica) | obligatoria |
| `label` | Su nombre en español. Es también el nombre de su rol: no se puede repetir (tampoco cambiando mayúsculas) ni ser un número | obligatoria |
| `translations` | Su nombre en otros idiomas, `['en' => '…']` | ninguna (se ve en español) |
| `description` | El texto de la pantalla de elegir tipo al crear un usuario | el genérico |
| `descriptionTranslations` | Ese texto en otros idiomas, `['en' => '…']` | ninguna |
| `priority` | Su lugar en la jerarquía: tiene autoridad sobre los de prioridad igual o menor. Menor que 500 (la de root). En un tipo propio, desde 400 se aplica con un aviso (queda al nivel de la administración general); en uno de fábrica, desde 400 es un error | obligatoria (en uno de fábrica, si falta, la suya) |
| `organization` | Si pertenece a una organización | `true` |
| `profile` | Si tiene perfil público | `false` |
| `externalLogin` | Si puede entrar con un proveedor externo | `false` |
| `authorityOverSameType` | Si tiene autoridad sobre los de su mismo tipo | `true` |
| `autoApproval` | Si se aprueba solo al darse de alta | `false` |
| `creatable` | Si se ofrece al crear un usuario | `true` |
| `permissions` | Con qué rutas empieza: `generals`, `administratives` o `superiors`, las tres listas de `roles.php` | `generals` |
| `routes` | Nombres de ruta de más, solo para este tipo | `[]` |

**Una clave que no está en esta tabla es un error**: así un nombre mal escrito no pasa en silencio.

### Ajustar un tipo de fábrica

Los que se pueden retirar (2, 3, 4, 12 y 50) admiten **todas las claves de la tabla salvo `id`**, con las mismas reglas
que un tipo propio, más una: su `priority` tiene que quedar por debajo de 400. Lo que no declares conserva su valor de
fábrica.

**Root (0) y la administración general (1) solo admiten lo que añade**: `label`, `translations`, `description`,
`descriptionTranslations`, `creatable`, `profile` y `routes`. Su prioridad, su organización, su aprobación, su acceso
externo, su lista de `permissions` y su autoridad sobre los de su tipo quedan fijos: declararlos es un error del archivo.

**Ningún tipo recibe todas las rutas.** Lo que haga falta se nombra en `routes`. Una ruta que el framework reserva a root
(las que declaran `rolesAllowed` solo con root) no debe ir en `routes`: Avisos del sistema la marca como crítica y
`bin/cli verify-integrity` falla.

Hasta la versión 7 esto se hacía editando constantes de `UsersModel`. Su equivalente:

| Constante de la 7 | Clave en `user-types.php` |
| :-- | :-- |
| `TYPES_USER_PRIORITY` | `priority` |
| `TYPES_USER_DONT_REQUIRE_ORGANIZATION` | `organization` (en `false`) |
| `TYPES_USER_SHOULD_HAVE_PROFILE` | `profile` |
| `TYPES_WITH_EXTERNAL_LOGIN` | `externalLogin` |
| `TYPES_USER_DO_NOT_HAVE_AUTHORITY_OVER_SAME_TYPE` | `authorityOverSameType` (en `false`) |
| `ARE_AUTO_APPROVAL` | `autoApproval` |
| los permisos por tipo de `roles.php` (`allowed_routes`) | `permissions` y `routes` (`all` ya no existe) |

Un ejemplo frecuente: el usuario general suele ser el escalón más bajo del proyecto, y lo natural es darle el nombre del
oficio en vez de crear un tipo propio para lo mismo. Aquí se renombra el 2, se le da perfil y se retiran el institucional
y el administrador de organización:

```php
return [
    'types' => [
        ['id' => UsersModel::TYPE_USER_GENERAL, 'label' => 'Operario', 'translations' => ['en' => 'Operator'], 'profile' => true],
    ],
    'retire' => [UsersModel::TYPE_USER_INSTITUCIONAL, UsersModel::TYPE_USER_ADMIN_ORG],
];
```

**Cambiar un tipo no reescribe a sus usuarios.** Si le das perfil a un tipo que ya tiene usuarios, quedan sin perfil
hasta que lo creen o hasta la acción de `bin/cli` que crea los que faltan (`bin/cli help` la lista). Si le exiges
organización, habrá que elegirla al editar cada uno. Si vas a retirar un tipo que tiene usuarios, root les cambia antes el
tipo desde el panel. Y un tipo con `profile` aparece en el Directorio y en el geovisor.

### Retirar o dejar de ofrecer

- **`creatable => false`** cierra la puerta: el tipo ya no se ofrece al crear usuarios (ni en la pantalla ni en el
  envío), pero los que ya lo tienen siguen entrando como siempre. Es lo que hay que usar si el tipo tiene usuarios.
- **`retire`** hace que el tipo deje de existir. Sus usuarios **no se borran**, pero ya no pueden entrar (su sesión se
  cierra con «Tu cuenta no tiene un tipo de usuario válido») y root ve el aviso «N usuarios tienen un tipo que ya no
  existe». Retira un tipo solo
  cuando ya no tenga usuarios activos. Un código retirado no se puede volver a declarar.

### Si el archivo tiene un error

Si el archivo no se puede leer o algo no cumple las reglas de arriba, **no se aplica nada de él**: el sitio sigue con
los tipos de fábrica, root ve un aviso con el error y `bin/cli verify-integrity` falla. En local, además, las páginas se
paran con el error a la vista; la terminal sigue, y `bin/cli verify-integrity` lo enseña. Sin traducción a un idioma
permitido, `verify-integrity` solo avisa. No se aplica a medias: aceptar las entradas buenas y tirar las malas daría un reparto que
nadie escribió.

## Los permisos de un tipo

El permiso de PiecesPHP es el nombre de la ruta (ver [Permisos](permissions.md)). Un tipo propio, o uno de fábrica que
las ajusta, recibe:

- las rutas de su lista de `permissions` y las de `routes`;
- las rutas que el framework concede «a todos los roles» (las que se declaran con `UserTypes::codes()`), sin hacer nada;
- las de los módulos del proyecto que lo nombren en su `rolesAllowed`.

Las rutas del framework que nombran tipos concretos no lo incluyen.

Al añadir o retirar un tipo cambian los permisos de muchas rutas, así que `bin/cli verify-integrity` marcará esas rutas
como cambiadas: es lo esperado. Revísalo y regenera la foto con `bin/cli verify-integrity update-permissions=yes`.

## El formulario de un tipo

Al crear, editar o ver el perfil de un usuario, el panel pinta el formulario de su tipo si existe y no está vacío, y el
genérico si no. Viven en `src/app/classes/PiecesPHP/UserSystem/Views/usuarios/form-by-type/`, en una subcarpeta por modo
(`create`, `edit` o `profile`) y dentro, una por tipo, con su `form.php`. La carpeta de un tipo de fábrica es `root`, `admin-general`, `admin-organization`, `general`, `institucional` o
`comunicaciones`; la de un tipo propio, `type-<código>`. Los de fábrica incluyen el genérico (`user-form.php`).

Para escribir el de un tipo:

- Conserva `defined("BASEPATH") or die(...)` al principio, las clases `users <modo>` del formulario y los `name` de
  los campos: el JavaScript y la acción que guarda dependen de ellos.
- Escapa con `escape_html()` todo lo que imprimas.
- No vuelques el usuario editado entero: lleva datos que no van a la pantalla.
- El formulario se elige por el tipo del usuario EDITADO, no por quien lo mira: ocultar un campo no es un permiso. Lo
  que alguien no puede cambiar lo impide la acción que guarda, no la vista.

## Leer los tipos desde el código

```php
use PiecesPHP\UserSystem\UserTypes;

$todos = UserTypes::codes();                       //los códigos de todos los tipos con rol
$etiquetas = UserTypes::labels();                  //código => etiqueta, sin traducir
$conPerfil = UserTypes::shouldHaveProfile($tipo);  //y requiresOrganization(), hasExternalLogin(), isAutoApproval()…
```

Para una ruta que deben ver todos los tipos, `rolesAllowed` es `UserTypes::codes()`.

**Las listas `TYPES_*` de `UsersModel` están en desuso** (`TYPES_USERS`, `TYPES_USER_PRIORITY`,
`TYPES_USER_DONT_REQUIRE_ORGANIZATION`, `TYPES_USER_SHOULD_HAVE_PROFILE`, `TYPES_WITH_EXTERNAL_LOGIN`,
`TYPES_USER_DO_NOT_HAVE_AUTHORITY_OVER_SAME_TYPE` y `ARE_AUTO_APPROVAL`). Solo tienen los tipos de fábrica, así que el
código que las lea no ve los del proyecto. `verify-integrity` rechaza leerlas en `src/app`, y salen en la 11.0.0. Las
constantes de cada tipo (`UsersModel::TYPE_USER_ROOT`…) se quedan.

## Lo que un tipo no declara

- **El rango de visibilidad** de sesiones y registros (`UserHierarchy`): un tipo propio tiene el más bajo.
- **Las listas guardadas en Configuración** (quién usa cada función de IA, los tipos que pueden entrar en mantenimiento,
  los perfiles de las noticias) no incluyen un tipo nuevo: se añade desde su pantalla.
