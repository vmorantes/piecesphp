# El correo saliente

Cómo sale un correo del framework: dónde está la configuración, qué hace `Mailer` al enviar, adónde va según la entrega,
qué queda en el registro y quién envía. Probarlo en local (el sumidero, Mailpit, los avisos) está en
[Despliegue, «Correo en desarrollo»](general.md#correo-en-desarrollo).

## La configuración

- **Vive solo en la base**, en la opción `mail`, **cifrada** (`Sealed`). No hay archivo donde poner el SMTP: se edita en
  Configuración › Integraciones › Correo. La lee `MailConfig` (`src/app/core/psr4/PiecesPHP/Core/ConfigHelpers/`).
- Sus claves: `host`, `port`, `protocol`, `auth`, `user`, `password`, `name`, `auto_tls`, `test_host`, `test_port`, y tres
  que la pantalla no edita: `is_smtp`, `smtp_debug` y `smtp_options`. Una clave que falta toma su valor de fábrica
  (`protocol` `ssl`, `port` `465`, `auto_tls` encendido, sumidero en `127.0.0.1:1025`). Sin `name`, el remitente se
  llama como `user`.
- Al guardar, una contraseña vacía conserva la guardada.
- **El protocolo** es uno de tres: `tls` (STARTTLS, que se exige: si el servidor no lo ofrece, el envío falla), `ssl`
  (SSL/TLS desde el primer byte) o vacío (sin cifrado explícito). Al guardar y al enviar se aceptan los nombres
  habituales y se traducen (`SettingsController::normalizeMailProtocol()`: «STARTTLS», «SSL/TLS», «SMTPS», «Ninguno»,
  sin mayúsculas); otro valor se rechaza al guardar.
- **Auto TLS** (`auto_tls`): con el protocolo vacío, PHPMailer sube a STARTTLS si el servidor lo ofrece y el host no es
  `localhost`.
- **La entrega** es otra opción, `mail_delivery` (`MailDelivery`): `real`, `sink` o `auto`. Con `auto`, una instalación
  `local` (o sin `environment.php`) usa el sumidero y cualquier otra entrega de verdad.

## Enviar

Un módulo envía con `PiecesPHP\Core\Mailer`, que extiende PHPMailer:

```php
use PiecesPHP\Core\ConfigHelpers\MailConfig;
use PiecesPHP\Core\Mailer;

$mailConfig = new MailConfig();
$mailer = new Mailer(); //lanza excepciones al fallar la entrega real
$mailer->setFrom($mailConfig->user(), $mailConfig->name());
$mailer->addAddress('persona@example.org');
$mailer->isHTML(true);
$mailer->Subject = __($langGroup, 'Asunto');
$mailer->Body = $html;
if (!$mailer->checkSettedSMTP()) {
    $mailer->asSystemMailer(); //sin SMTP configurado, mail() del sistema (no con el sumidero)
}
$mailer->send();
```

1. **Al construirse**, `Mailer` carga `MailConfig` y la pasa a PHPMailer. **Si la entrega es el sumidero, desvía ahí**:
   host y puerto de prueba, sin autenticación ni cifrado. El desvío vive en `Mailer` y no en `MailConfig` para que la
   pantalla enseñe y guarde siempre los valores reales.
2. **`send()`** marca el correo con la cabecera `X-Tags` (la instalación) y:
   - **entrega real**: lo manda; si falla, anota `failed` y **vuelve a lanzar la excepción**;
   - **sumidero**: lo manda al sumidero; si el sumidero no lo acepta, lo guarda como `.eml` en
     `src/app/logs/mail-outbox/`. **Nunca lanza**: devuelve si quedó entregado o en el buzón.
3. **Es síncrono y de un solo intento**: no hay cola ni reintentos.

**La caída a `mail()` del sistema no es automática**: la pide quien envía, con `checkSettedSMTP()` y
`asSystemMailer()` (así lo hacen los emisores del framework). Con el sumidero no se aplica: su reserva es el buzón en
disco.

## El registro

Cada envío, entregado o no, deja una fila en `pcsphp_mail_log` (`MailLogMapper`): fecha, destinatarios, asunto, origen
(la clase que lo envió, sacada de la traza), entrega, resultado (`delivered`, `outbox` o `failed`), motivo y el
**cuerpo cifrado** (sin adjuntos; fuera de `local` no se guarda si la clave de la aplicación es la de relleno). Anotar
nunca tumba el envío. La tabla se recorta a 20.000 filas; el tope y un filtro del cuerpo se cambian en
`src/app/config/extensions/mail-log.php`.

Se ve en Registros › Correos enviados (solo root), con el cuerpo en una ventana. El aviso del sistema «correos con
fallos» y `bin/cli mail-doctor` leen el resumen de las últimas 24 horas.

## Las plantillas

- La base es `src/app/view/mailing/template_base.php`. Su logo es `mailing_logo()` (ver
  [Las imágenes de Configuración](imagenes-de-configuracion.md)); en `local`, una imagen de muestra.
- El catálogo (`MailCatalog`): una `MailDefinition` por correo, con su asunto, sus datos de ejemplo y su vista previa.
  Se ve en Configuración › «Correos del sitio», y un módulo registra los suyos con `MailCatalog::register()`.
- **El idioma es el de la petición en curso**: ningún correo cambia de idioma por su destinatario.

## Quién envía en el framework

| Correo | Dónde |
| :-- | :-- |
| Recuperar la contraseña | `RecoveryPasswordController` |
| Código para el usuario olvidado o bloqueado; «otros problemas» | `UserProblemsController` |
| Código de acceso de un uso | `OTPHandler` |
| Soporte del panel (a `support_form_recipients`) | `AdminPanelController` |
| Formulario de contacto (a `contact_form_recipients`) | `ContactFormsController` |
| Mensaje con token | `GenericTokenController` |
| Usuario creado por la API | `APIController` |
| Cambio de estado de una aprobación | `SystemApprovalsController` |
| Prueba desde Configuración | `MailTester` |
| Prueba del catálogo (solo con el sumidero) | `MailCatalogController` |
| Demostración desde la terminal | `bin/cli mail-demo` |

**Un envío que falla con entrega real lanza**: quien envía decide qué ve el usuario. La recuperación de contraseña, las
aprobaciones, el contacto y el token lo atrapan y lo dejan en el registro de errores.
