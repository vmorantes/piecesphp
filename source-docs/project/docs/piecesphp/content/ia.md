# El centro de mando de la IA

Cómo usa la IA un módulo del framework: qué es una capacidad, por qué todo pasa por una puerta única y dónde se anota lo
que se gasta. Lo que ve quien administra está en el manual, «Configuración de las integraciones», sección «Inteligencia artificial».

!!! warning "Provisional: primera de varias entregas"
    Esta página describe la **primera entrega** del centro de mando: el registro de capacidades, la puerta y la tabla
    del consumo. **La puerta todavía no controla nada por capacidad**: ni interruptor, ni permisos por tipo de usuario,
    ni cuota. Los valores por omisión que declara una capacidad se guardan en su definición, pero **nadie los aplica
    aún**. La traducción conserva sus controles de siempre (encenderla y quién puede usarla, en Configuración › IA), y
    esa pantalla enseña lo mismo que antes.

## La regla

- **En el producto, solo la puerta crea los adaptadores de los proveedores** (OpenAI, Mistral) y solo ella anota lo
  gastado. Un módulo pide un manejador a `AIGateway` en lugar de construir un `OpenAIHandlerAdapter` o un
  `MistralHandlerAdapter` por su cuenta. Así, cuando lleguen los controles, valdrán para todos sin tocar cada módulo.
- **Cada uso de la IA es una capacidad registrada**, con una clave estable. La puerta lanza
  `InvalidArgumentException` si se le pide una capacidad que no existe: es un error de programación, no de ejecución.
- **Si la tabla del consumo falla, la operación sigue** (por ejemplo, porque una instalación vieja aún no la ha creado):
  el fallo queda en el registro de errores. El acumulado de Configuración › IA se escribe aparte, como siempre.

## Registrar una capacidad

En el `Routes` de tu módulo, al registrar sus rutas (así lo hace `APIRoutes` con la traducción):

```php
use PiecesPHP\AI\AICapability;
use PiecesPHP\AI\AICapabilityRegistry;
use PiecesPHP\UserSystem\ORM\UsersModel;

AICapabilityRegistry::register(new AICapability(
    'invoice-summary',                                    //clave: minúsculas, números y guiones
    fn(): string => __(MiModuloLang::LANG_GROUP, 'Resumen de facturas con IA'),
    AICapability::PROVIDER_CHAT,                          //o PROVIDER_SPEECH
    true,                                                 //encendida por omisión
    array_keys(UsersModel::TYPES_USERS),                  //quién la usa por omisión
    0,                                                    //cuota por omisión: 0 = sin límite
    AICapability::PERIOD_ACCUMULATED,                     //periodo de la cuota
));
```

- El nombre es una **función** que devuelve el texto ya traducido: así se traduce al pintarse, en el idioma de quien
  mira, y no al registrarse.
- Una clave repetida lanza. `AICapabilityRegistry::replace()` cambia una que ya existe (conservando su sitio) y
  `remove()` la quita; `all()` las devuelve en el orden de registro y `get()` una por su clave.
- Los periodos declarados son `PERIOD_ACCUMULATED`, `PERIOD_MONTHLY`, `PERIOD_EVERY_DAYS` y `PERIOD_EVERY_MONTHS`. En
  esta entrega solo se usa el acumulado.

## Usar la puerta

```php
use PiecesPHP\AI\AIGateway;

$handler = AIGateway::chatHandler('invoice-summary');
if ($handler === null) {
    //No hay proveedor configurado en Configuración › IA.
} else {
    //... hablar con el proveedor con $handler ...
    AIGateway::recordUsage('invoice-summary', (string) get_config('translationAI'), $tokensUsed);
}
```

- `chatHandler()` devuelve el adaptador del proveedor elegido en Configuración › IA, con su modelo y su clave, o `null`
  si no hay ninguno. Lanza si la capacidad no está registrada o no es de chat. **Cierra antes la conexión a la base de
  datos** (como siempre hizo la traducción), porque la petición al proveedor puede tardar; el siguiente acceso a la
  base la vuelve a abrir.
- `recordUsage()` suma los tokens y una petición en la fila del periodo vigente de esa capacidad y ese proveedor, y
  también en el acumulado por proveedor que enseña Configuración › IA.

## Lo que se guarda

`pcsphp_ai_usage` (`AIUsageMapper`): una fila por capacidad, proveedor y comienzo del periodo, con `tokens`,
`requests`, `createdAt` y `updatedAt`. En esta entrega el periodo es siempre el acumulado, que se guarda con un comienzo
fijo (`AIUsageMapper::ACCUMULATED_PERIOD_START`, el `2000-01-01 00:00:00`): una sola fila por capacidad y proveedor.

Una instalación que ya existe crea la tabla con `bin/cli db-update file=2026-11-13-consumo-de-la-ia apply=yes` (con un
`bin/cli db-backup` antes; ver [Terminal](terminal.md#10-db-update)).

## Las pruebas

La suite `core/ai-gateway` cubre el registro, la puerta (sin llamar nunca a un proveedor real) y la suma en la tabla.
Para registrar capacidades de prueba sin dejar rastro, `AICapabilityRegistry::swapForTesting()` guarda el registro y lo
repone después.
