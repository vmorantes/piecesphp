# El centro de mando de la IA

Cómo usa la IA un módulo del framework: qué es una capacidad, por qué todo pasa por una puerta única y dónde se anota lo
que se gasta. Lo que ve quien administra está en el manual, «Configuración de las integraciones», sección «Inteligencia artificial».

Los ajustes se cambian en **Configuración › IA** (manual: «Configuración de las integraciones», «Inteligencia
artificial»): el interruptor general, los proveedores y sus claves, y una sección por capacidad con su interruptor, quién
la usa, su cuota, su periodo, su consumo y «Reiniciar el contador».

!!! note "La cuota del informe no limita"
    El «Límite de tokens de IA» del final de esa pantalla (`CONTENT_TOKENS_LIMIT`) solo lo usa el informe de consumo. La
    cuota que limita es la de cada capacidad, y una capacidad nueva empieza sin límite.

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
- Los periodos son `PERIOD_ACCUMULATED`, `PERIOD_MONTHLY`, `PERIOD_EVERY_DAYS` y `PERIOD_EVERY_MONTHS` (ver «Los
  controles»).
- Un octavo argumento opcional, una función que devuelve un array con las mismas claves que `ai_center` (ver «Los
  controles»), lleva a la capacidad los ajustes que tu módulo ya tenía con otro nombre.

## Los controles

Antes de crear el adaptador, `AIGateway::chatHandler()` pregunta a `AIGateway::denialReason()`. Si hay motivo, lanza
`PiecesPHP\AI\AIUnavailableException` con ese motivo ya traducido y **no llama al proveedor**. Se comprueba en este orden
y gana el primero que falla:

1. el interruptor general (`AIGateway::isEnabled()`): «La inteligencia artificial está apagada.»;
2. el de la capacidad: «Esta función de inteligencia artificial está apagada.»;
3. que haya usuario y su tipo esté en «quién la usa» (**root siempre puede**): «No tienes permiso para usar esta función
   de inteligencia artificial.»;
4. la cuota, si es mayor que 0: con lo gastado en el periodo vigente igual o por encima, «Se ha alcanzado la cuota de
   esta función de inteligencia artificial en este periodo.»

Los valores de cada capacidad (`AIGateway::settingsFor()`) se forman en tres capas, y cada una pisa a la anterior:

1. los **por omisión** que declara su `AICapability`;
2. los **heredados**: el último parámetro de `AICapability`, una función que devuelve los ajustes que el módulo ya
   tenía con otro nombre (la traducción lee así «Activar traducción con IA» y «Quién puede traducir con IA»; se leen
   hasta la 11.0.0);
3. los **guardados** en la opción `ai_center` (JSON), que escribe la pantalla:

```json
{
    "enabled": true,
    "capabilities": {
        "invoice-summary": {
            "enabled": true,
            "userTypes": [1, 12],
            "quota": 50000,
            "period": "every-months",
            "periodLength": 3,
            "periodAnchor": "2026-01-31 00:00:00"
        }
    }
}
```

Todo es opcional. Un campo que no vale se ignora (vale la capa de debajo) y queda **una vez** en el registro de errores.

`get_config('ai_center')` puede llegar como array, como texto JSON o como objeto (así la decodifica `SettingsModel` al
arrancar): léela siempre con `AIGateway::normalizeCenter()`, que devuelve un array o `null`.

«Reiniciar el contador» (la ruta `configurations-integrations-ai-usage-reset`) pone `periodAnchor` en el momento actual: la
cuota vuelve a contar desde cero y las filas de antes se quedan para los informes.

**El periodo** (`AIGateway::periodStart()`), contado desde `periodAnchor` (por omisión, `2000-01-01 00:00:00`):
`accumulated`, desde el ancla; `monthly`, desde el día 1 del mes (o desde el ancla, si es posterior); `every-days`,
cada `periodLength` días desde el ancla; `every-months`, cada `periodLength` meses desde el ancla, con el día del ancla o,
si el mes no lo tiene, su último día (31 de enero → 28 de febrero → 31 de marzo). Lo gastado en el periodo vigente lo da
`AIGateway::usage()`.

Si tu módulo pide el manejador, captura la excepción y enseña su mensaje:

```php
use PiecesPHP\AI\AIGateway;
use PiecesPHP\AI\AIUnavailableException;

try {
    $handler = AIGateway::chatHandler('invoice-summary');
} catch (AIUnavailableException $e) {
    return $response->withJson(['success' => false, 'message' => $e->getMessage()]);
}
```

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
  si no hay ninguno. Lanza `InvalidArgumentException` si la capacidad no está registrada o no es de chat, y
  `AIUnavailableException` si un control lo impide. **Cierra antes la conexión a la base de
  datos** (como siempre hizo la traducción), porque la petición al proveedor puede tardar; el siguiente acceso a la
  base la vuelve a abrir.
- `recordUsage()` suma los tokens y una petición en la fila del periodo vigente de esa capacidad y ese proveedor, y
  también en el acumulado por proveedor que enseña Configuración › IA.

## Lo que se guarda

`pcsphp_ai_usage` (`AIUsageMapper`): una fila por capacidad, proveedor y comienzo del periodo, con `tokens`,
`requests`, `createdAt` y `updatedAt`. Cada periodo nuevo empieza en una fila nueva; el acumulado sin ancla propia empieza en
`AIUsageMapper::ACCUMULATED_PERIOD_START` (`2000-01-01 00:00:00`).

Una instalación que ya existe crea la tabla con `bin/cli db-update file=2026-11-13-consumo-de-la-ia apply=yes` (con un
`bin/cli db-backup` antes; ver [Terminal](terminal.md#10-db-update)).

## Las pruebas

La suite `core/ai-gateway` cubre el registro, la puerta y sus cuatro controles con su orden (sin llamar nunca a un
proveedor real), las tres capas de los ajustes, los cuatro periodos con relojes fijos y la suma en la tabla. Para probar
un control, cambia `ai_center` con `set_config()` en memoria y repónlo al terminar.
Para registrar capacidades de prueba sin dejar rastro, `AICapabilityRegistry::swapForTesting()` guarda el registro y lo
repone después.
