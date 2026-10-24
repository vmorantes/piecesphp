# La clave de la aplicación

`app_key` es el secreto del que sale todo lo que PiecesPHP protege: firma los tokens de sesión, y de ella se derivan las
llaves con las que se cifran la configuración del correo y de Mailjet, el secreto del doble factor (TOTP), el cuerpo de
los correos enviados y los datos de los tokens. **Quien la tiene puede fabricar una sesión de cualquiera y leer todo lo
cifrado. Quien la pierde, pierde lo cifrado.** Desde la `9.0.0` vive fuera de git, se genera sola y se puede cambiar sin
perder nada (ADR 0061).

## Dónde vive

En el **llavero**: `secure-keys/app-key.json`, fuera de git y con la protección de la carpeta `secure-keys/` (la misma
que las claves de API). Guarda la clave **actual** y las **anteriores**.

- Si no hay llavero, se lee `$config['app_key']` de `config.php`, como antes de la 9, y root ve un aviso: **la `app_key` está en `config.php`, que se versiona**. Para moverla al llavero **sin cambiarla**: `bin/cli store-app-key`.
- La clave de relleno (`'TODO:secret'`, o `'secret'` si falta) cuenta como **no puesta**.

## Cómo nace

**En el primer arranque**, si la clave es la de relleno y `secure-keys/` se puede escribir, la aplicación genera una
aleatoria (64 caracteres), la guarda en el llavero y deja la de relleno como **anterior** (así lo que se hubiera cifrado
con ella se sigue leyendo). Pasa igual en local y en un servidor. Si no puede escribir, el aviso dice qué hacer.

A mano: `bin/cli generate-app-key` (solo si todavía no hay llavero).

## Al actualizar desde la 8: cifrar lo que quedó en el formato viejo

La configuración de correo y de Mailjet se vuelve a guardar cifrada sola, la primera vez que se carga. **El secreto del
doble factor (TOTP) y el cuerpo de los correos enviados se leen en su formato viejo, pero siguen así en la base** hasta
que los cifres. Hazlo una vez, sin cambiar la clave ni cerrar sesiones:

```bash
bin/cli db-backup
bin/cli seal-legacy-data
```

## Cómo se cambia sin perder nada

```bash
bin/cli db-backup
bin/cli rotate-app-key
```

`rotate-app-key` hace, en este orden:

1. un respaldo de la base;
2. una clave nueva;
3. cada dato cifrado **declarado** (ver abajo) se lee con su clave y se vuelve a cifrar con la nueva, dentro de una
   transacción;
4. el llavero se escribe de golpe, con la nueva como actual y la vieja como **anterior**;
5. un recuento por sitio.

Si algo falla antes de confirmar, no cambia nada: ni la base ni el llavero. Lo que no se puede leer se deja como estaba
y se cuenta.

**Al cambiar la clave, todos vuelven a iniciar sesión**: los tokens se firman con ella. Es lo esperado.

## El llavero, o por qué no se pierde lo que nadie declaró

Cada valor cifrado empieza por `v2:<huella>:`, y la huella dice con qué clave se cifró (son los primeros caracteres del
sha256 de la clave: no la revelan). Al leer, el framework busca esa clave en el llavero, aunque sea una anterior. Así,
**lo que un módulo cifró sin declararlo se sigue leyendo** después de rotar, y se vuelve a cifrar con la clave nueva la
próxima vez que se guarde.

**Cuándo retirar una clave anterior**: cuando ya no la usa ningún dato.

```bash
bin/cli retire-app-key huella=<huella>
```

La huella son 8 caracteres hexadecimales; la imprimen `rotate-app-key`, `generate-app-key` y `seal-legacy-data`. Se niega mientras algún dato declarado siga cifrado con ella, o mientras quede algo en un formato anterior a la 9 (que
no dice su clave). Lo no declarado no se puede comprobar: por eso hay que declararlo.

## Para quien programa: cifrar un dato propio

Un secreto se cifra con `PiecesPHP\Core\Crypto\Sealed` (AES-256-GCM: detecta cualquier manipulación) y **un propósito
declarado**. El propósito es **una constante de tu clase**, nunca un texto suelto: así la comprobación puede auditarlo.

```php
namespace MyModule;

class MyModuleIntegrations
{
    const PURPOSE_API_TOKEN = 'my-module-api-token'; // de él sale su llave, desde app_key
}
```

En el `*Routes` de tu módulo, declara dónde lo guardas:

```php
use PiecesPHP\Core\Crypto\EncryptedData;
use PiecesPHP\Core\Crypto\EncryptedDataRegistry;

EncryptedDataRegistry::register(new EncryptedData(
    'my-module-api-token',                          // clave del sitio, en kebab-case
    MyModuleIntegrations::PURPOSE_API_TOKEN,
    EncryptedData::KIND_COLUMN,                     // o KIND_OPTION (una opción de configuración), o KIND_NOT_STORED
    'my_module_integrations',                       // la tabla (o el nombre de la opción)
    'apiToken',                                     // la columna
    'id'                                            // la clave primaria
));
```

Y al guardar y al leer, la misma constante:

```php
use PiecesPHP\Core\Crypto\Sealed;

$stored = Sealed::seal($token, MyModuleIntegrations::PURPOSE_API_TOKEN);
$token = Sealed::open($stored, MyModuleIntegrations::PURPOSE_API_TOKEN); // null si no se puede abrir: nunca basura
```

- `bin/cli verify-integrity` **falla** si algo cifra con un propósito que no está declarado o que no es una constante. Es lo que garantiza que
  `rotate-app-key` no se deja nada.
- `BaseHashEncryption::obfuscate()` **no cifra**: solo ofusca (lo usan las direcciones cortas de los contenidos y la
  lectura de los datos de antes de la 9). La
  misma comprobación falla si se usa fuera de su lista.
- Nunca imprimas, registres ni guardes en claro una clave, un token o un secreto, tampoco «para depurar».
