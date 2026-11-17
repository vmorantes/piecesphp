# El centro de mando de la IA

Cómo usa la IA un módulo del framework: qué es una capacidad, por qué todo pasa por una puerta única y dónde se anota lo
que se gasta. Lo que ve quien administra está en el manual, «Configuración de las integraciones», sección «Inteligencia artificial».

Los ajustes se cambian en **Configuración › IA** (manual: «Configuración de las integraciones», «Inteligencia
artificial»): el interruptor general, los proveedores y sus claves, y una sección por capacidad con su interruptor, quién
la usa, su cuota, su periodo, su consumo y «Reiniciar el contador».

!!! note "La cuota del informe no limita"
    El «Límite de tokens de IA» del final de esa pantalla (`CONTENT_TOKENS_LIMIT`) solo lo usa el informe de consumo. La
    cuota que limita es la de cada capacidad, y una capacidad nueva empieza sin límite.

**Las capacidades del framework**: `translation` («Traducción con IA», encendida por omisión, la del módulo de la API)
y `assistant` («Asistente del panel», **apagada por omisión**: ver [Ofrecer herramientas al
asistente](../new-features/herramientas-del-asistente.md), «El asistente»). Las dos usan el proveedor de «IA para
traducciones».

## La regla

- **En el producto, solo la puerta crea los adaptadores de los proveedores** (OpenAI, Mistral) y solo ella anota lo
  gastado. Un módulo pide un manejador a `AIGateway` en lugar de construir un `OpenAIHandlerAdapter` o un
  `MistralHandlerAdapter` por su cuenta. Así los controles valen para todos sin tocar cada módulo.
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

**`chatHandler()` está en desuso hasta la `11.0.0`** y lee la configuración de antes. Para lo nuevo, usa
`chatProvider()` o `translator()` (más abajo).

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

## Los proveedores y `chatProvider()`

!!! warning "Provisional"

    Lo que viene: los modelos como lista (la que dé el propio proveedor), el origen de cada clave (escrita o del archivo
    de claves), sin proveedores fijos, y el coste estimado con su «Registro».

Un **registro de proveedor** es un tipo (OpenAI, Mistral, Groq, DeepSeek, xAI, OpenRouter o «Personalizado»), un
sobrenombre y una clave. Varios del mismo tipo pueden convivir. Cada función elige un registro y un modelo
(`capabilities.<clave>.provider` y `.model` en `ai_center`). Las claves no van en `ai_center`: van selladas en la opción
`ai_provider_keys`, que solo abre la puerta.

```php
use PiecesPHP\AI\AIGateway;
use PiecesPHP\AI\AIUnavailableException;

try {
    $provider = AIGateway::chatProvider('invoice-summary');
    $answer = $provider->chatWithTools([
        ['role' => 'user', 'content' => 'Resume esta factura: …'],
    ]);
    AIGateway::recordUsage('invoice-summary', AIGateway::usageProviderName($provider), $answer['usage']);
} catch (AIUnavailableException $e) {
    //$e->getMessage(): el motivo, traducido (la función apagada, sin proveedor elegido, sin clave…).
}
```

- `chatProvider()` resuelve la función → su registro → su modelo, y devuelve un `AIChatProvider`. Una función sin
  registro elegido no está disponible. La excepción es una instalación que viene de antes y todavía no ha guardado
  Configuración › IA: ahí la función usa el proveedor de «IA para traducciones», como antes (ver «Los proveedores de antes»).
- Lanza `AIUnavailableException` con un motivo traducible si un control lo impide, si el registro no existe o no tiene
  clave, o si su tipo no sabe hacer lo que la función necesita. Una función que use herramientas lo declara con el último
  argumento de `AICapability` (`usesTools: true`), y entonces solo valen tipos con herramientas.
- **Cuota por registro**: además de la de cada función, cada registro guardado tiene la suya (sumando todas las funciones
  que lo usan); manda la primera que se alcance.
- **Probar** (Configuración › IA) hace una llamada mínima con `AIGateway::testProvider()`, sin controles, sin consumo y sin
  guardar nada.
- `chatWithTools()` devuelve `content`, `toolCalls`, `usage`, `inputTokens` y `outputTokens`, con 30 segundos de tiempo
  máximo. Un error del proveedor lanza `RuntimeException` sin la clave ni la respuesta del proveedor.
- Como `chatHandler()`, cierra antes la conexión a la base.
- El tipo «Personalizado» habla el formato de OpenAI con la dirección que se le dé: solo `https://`, o
  `http://localhost` y `http://127.0.0.1` para un modelo local.

### Los proveedores de antes

Hasta la `10.28.0`, OpenAI, Mistral y Groq eran fijos, con sus opciones sueltas: `OpenAIApiKey`, `MistralAIApiKey`,
`GroqAPIKey`, `modelOpenAI`, `modelMistral`, `modelGroq` y `translationAI`. **Desde la `10.29.0` no hay proveedores
fijos.** Esa configuración se convierte en registros normales:

- **En memoria, hasta el primer guardado.** Mientras `ai_center.legacyConverted` no es `true`
  (`AIProviderRegistry::CONVERTED_FLAG`), `AIProviderRegistry::all()` añade un registro por cada uno de los tres que tenga
  clave, sin escribir nada:
  - ids `openai`, `mistral` y `groq` (`AIProviderRegistry::CONVERTED_*`);
  - sobrenombres «OpenAI», «Mistral» y «Groq»;
  - su modelo suelto como modelo;
  - la clave del archivo de claves si la fija, o la de la base.

  Una función sin registro elegido usa el de `translationAI` (las de chat) o Groq (la de voz), con el modelo de antes. Hace
  exactamente lo mismo que antes.
- **El primer guardado de Configuración › IA** escribe esos registros como cualquier otro, deja cada función con su
  registro y su modelo, y pone la marca. Desde ahí, ni el registro ni la puerta leen las siete opciones de antes. Siguen en
  la base sin tocar, para quien las lea, hasta la `11.0.0`.
  - Solo el formulario de esta versión pone la marca (manda `converted_sent`). Una pestaña abierta antes de actualizar
    guarda sin convertir.
  - Quitar un convertido (en el primer guardado o después), o «Quitar la guardada» en él, vacía también su opción de antes (`OpenAIApiKey`,
    `MistralAIApiKey` o `GroqAPIKey`). Así la clave no sigue viva en los caminos en desuso que todavía la leen.
- **Los ids `legacy-openai`, `legacy-mistral` y `legacy-groq`** (`AIProviderRegistry::LEGACY_*`) quedan en desuso hasta la
  `11.0.0`. Una función guardada con uno de ellos se resuelve a su registro convertido.
- `usageProviderName()` sigue anotando «OpenAI» y «Mistral» para los convertidos en la fila de cada función, para no
  partir la serie del consumo. La cuota del registro se cuenta por su id.

### De dónde sale la clave

Cada registro dice de dónde sale su clave (`keySource`):

- **`stored`**, por omisión: escrita en Configuración › IA, sellada en `ai_provider_keys` y de solo escritura.
- **`file`**: del archivo de claves, por su nombre (`keyFile`).
  - **El nombre va atado al tipo del registro** (`AIProviderRecord::isValidKeyFile($nombre, $tipo)`):
    - el nombre del propio tipo, solo para OpenAI, Mistral y Groq (`openai`, `mistral`, `groq`: los de siempre);
    - o `ai-<tipo>-<lo que quieras>`, en minúsculas, cifras y guiones; por ejemplo, `ai-openai-marketing`,
      `ai-deepseek-pruebas` o `ai-custom-local`.
  - El límite existe porque un «Personalizado» manda la clave a la dirección que se le escriba. Sin él, quien administra
    podría mandar a otra dirección la clave de OpenAI del servidor, u otra llave del archivo.
  - «Probar» con una clave del archivo solo funciona con el proveedor ya guardado.
  - La pantalla dice si ese nombre está en el archivo, sin enseñar nada de la clave.

Para que un registro use una clave del archivo, guárdala en la carpeta de llaves con un nombre de esos y elige «Del
archivo de claves» en su fila.

**Cambiar el tipo, la dirección base o la cabecera de un proveedor guardado borra su clave escrita**: hay que volver a
escribirla. Así una clave no puede acabar en otro servicio sin que alguien la escriba de nuevo.

### La lista de modelos

- **`AIGateway::providerModels($record, $capability)`** da la lista que enseña la pantalla, sin llamar a la red:
  - la del proveedor, si alguna vez se pidió (guardada en la opción `ai_provider_models`, `AIGateway::MODELS_CONFIG_NAME`);
  - si no, la de respaldo del catálogo (`fallbackModels` de `AIProviderCatalog`), medida en la documentación oficial y
    fechada en `fallbackMeasured`.
- **`AIGateway::refreshProviderModels($record)`** la pide al proveedor y la guarda: un `GET` a la dirección base más
  `modelsPath`, con la clave del registro y 10 segundos de tiempo máximo. Un error deja la lista anterior y da el motivo,
  nunca la respuesta del proveedor.
- **En la pantalla**, cada proveedor guardado tiene «Actualizar modelos». El modelo de cada función y el de «Probar» se
  eligen de la lista, y «Otro…» deja escribir cualquiera: el servidor lo acepta como texto.

## Enseñar una función solo si se puede usar

Una pantalla que ofrece una función de IA (un botón, un campo) pregunta antes con `AIGateway::availability()`. Así no
enseña lo que no se puede usar, y avisa con claridad cuando se alcanzó un límite.

```php
use PiecesPHP\AI\AIGateway;

$ai = AIGateway::availability('invoice-summary');
if ($ai['state'] === AIGateway::AVAILABILITY_HIDDEN) {
    //No se pinta nada: ni el botón ni su hueco.
} elseif ($ai['state'] === AIGateway::AVAILABILITY_LIMIT) {
    //Se pinta desactivado, con $ai['message'] a la vista (no solo en un tooltip).
} else {
    //AIGateway::AVAILABILITY_AVAILABLE: como siempre.
}
```

- **`hidden`**, oculta: la IA general o la función están apagadas; quien mira no tiene permiso; la capacidad no está
  registrada; o no hay proveedor que sirva (ninguno elegido, no existe, sin clave, su tipo no sabe hacer lo que pide la
  función, o sin modelo). `message` y `renewsAt` son `null`: el motivo no se le enseña a quien no puede usarla.
- **`limit`**, en su límite: se alcanzó la cuota de la función o la del proveedor.
  - `message` dice cuál, traducido, y cuándo se renueva.
  - `renewsAt` es esa fecha (`Y-m-d H:i:s`), o `null` si el periodo es acumulado; entonces el mensaje dice que quien
    administra puede reiniciar el contador.
  - El fin del periodo también se puede pedir aparte, con `AIGateway::periodEnd()` y `AIGateway::providerPeriodEnd()`.
- **`available`**, disponible.
- **No llama a la red ni cierra la base**: se puede usar al pintar cualquier vista.
- **Una sola fuente**: `denialReason()` usa la misma resolución, y es `null` si y solo si `availability()` da `available`.
  `chatHandler()`, en desuso, conserva sus controles de antes y no mira el proveedor.

El botón «Traducir» de Publicaciones es el ejemplo:
- `src/app/classes/Publications/Views/publications/forms/inc/translation-button.php` recibe el resultado y no decide nada por su cuenta;
- `edit.php` conserva sus condiciones de sitio (la traducción con IA activada en `constants.php`, `API_AI_TRANSLATIONS_ACTIVE`, que viene en `false`, y no ser el idioma base).

## Traducir con `translator()`

`AIGateway::translator()` devuelve un `AITranslator` con el proveedor y el modelo de la función `translation` (sin
elegir, ver «Los proveedores de antes»): `translate($input, $from, $to)`, `lastUsage()`, `getTokensUsed()` y
`getLastAskToChatOriginalResponse()`, los mismos métodos que tenían los adaptadores. El texto de instrucciones es uno solo,
`AITranslator::prompt()`; cada tipo manda los parámetros que admite (OpenAI, sin temperatura; Mistral, Groq, DeepSeek y
OpenRouter, con respuesta JSON), y una traducción tiene 120 segundos de tiempo máximo. **`chatHandler()` queda en desuso
hasta la `11.0.0`**: usa `chatProvider()` o `translator()`.

## Voz a texto con `speechProvider()`

`AIGateway::speechProvider('speech-to-text')` devuelve un `AISpeechProvider` (la función «Voz a texto», apagada por
omisión; sin proveedor elegido, el Groq de antes mientras la conversión no se haya guardado): `transcribe($rutaDelAudio, $idioma)` devuelve `text` y, si el proveedor lo
cuenta, el consumo (`usage`, `inputTokens`, `outputTokens`) o la duración (`seconds`). Groq no devuelve consumo. 120
segundos de tiempo máximo. **`SpeechToTextGroqAdapter` queda en desuso hasta la `11.0.0`.**

## Lo que se guarda

`pcsphp_ai_usage` (`AIUsageMapper`): una fila por capacidad, proveedor, **modelo** y comienzo del periodo, con `tokens`,
`inputTokens`, `outputTokens`, `requests`, `createdAt` y `updatedAt`. `recordUsage($capacidad, $proveedor, $tokens,
$entrada, $salida, $modelo)`: los tres últimos son opcionales. Cada periodo nuevo empieza en una fila nueva. **El consumo de cada registro de proveedor guardado** va además en su propia fila, con `capability = '@provider'` (`AIGateway::PROVIDER_USAGE_ROW`, que ninguna capacidad puede llevar) y el periodo del registro, porque el de cada función puede ser otro; el acumulado sin ancla propia empieza en
`AIUsageMapper::ACCUMULATED_PERIOD_START` (`2000-01-01 00:00:00`).

Una instalación que ya existe crea la tabla con `bin/cli db-update file=2026-11-13-consumo-de-la-ia apply=yes` y le añade
el modelo, la entrada y la salida con `bin/cli db-update file=2026-11-14-consumo-de-la-ia-por-modelo apply=yes` (las dos con
un `bin/cli db-backup` antes; ver [Terminal](terminal.md#10-db-update)).

## Las pruebas

La suite `core/ai-gateway` cubre el registro, la puerta y sus cuatro controles con su orden (sin llamar nunca a un
proveedor real), las tres capas de los ajustes, los cuatro periodos con relojes fijos y la suma en la tabla. Para probar
un control, cambia `ai_center` con `set_config()` en memoria y repónlo al terminar.
Para registrar capacidades de prueba sin dejar rastro, `AICapabilityRegistry::swapForTesting()` guarda el registro y lo
repone después.

**Ninguna prueba puede llegar a un proveedor real.**
- `AIProviderCatalog::defaultHttpClient()` es el único sitio que crea el cliente HTTP de verdad (chat, voz, «Probar» y la
  lista de modelos). Lanza `LogicException` si `AIProviderCatalog::inTestRun()`, es decir, dentro de `gates`, de
  `bin/con-marca` o de una acción `unit-tests:`.
- Una suite pone su cliente simulado con `AIGateway::setProviderHttpClientForTesting()` y lo quita al terminar.
- Los adaptadores en desuso (`OpenAIHandlerAdapter`, `MistralHandlerAdapter`, `SpeechToTextGroqAdapter`) no pasan por esta
  guarda: no los uses en una suite.
