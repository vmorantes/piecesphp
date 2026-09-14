# Correo: log, configuracion y salida profesional

*Pedido por el PROPIETARIO el 2026-09-02, «para luego». Medido por ARQUITECTO el mismo dia.*

Lo pedido, literal:

> «Deberiamos tener un log de correos especifico. Para TODO lo que pase por las distintas
> metodologias de envio del framework. Visible para root, completo, filtrable, cabeceras,
> respuestas de errores, etc.»
>
> «Y la vista donde se configura el SMTP deberia ser mas solida. Lo mismo mis integraciones, no
> se que otras cosas puede integrar para envios con apariencia profesional. Hacer prueba de
> credenciales en la misma vista.»

---

## ESTADO MEDIDO — 2026-09-02

**CUATRO VIAS DE SALIDA**, y ninguna sabe de las otras:

| Via | Archivo |
| :-- | :-- |
| PHPMailer / SMTP | `core/psr4/PiecesPHP/Core/Mailer.php` (152 lineas, extiende PHPMailer) |
| Mailjet | `core/psr4/PiecesPHP/Core/MailjetHandler.php` |
| Mailgun | `core/psr4/PiecesPHP/Core/Email/Mailgun.php` |
| Mautic | `classes/API/Adapters/MauticEmailAdapter.php` |

**SIETE CONSUMIDORES de `Mailer`**, y los dos primeros son criticos:

    classes/PiecesPHP/UserSystem/Authentication/OTPHandler.php
    controller/RecoveryPasswordController.php
    classes/API/Controllers/APIController.php
    classes/SystemApprovals/Controllers/SystemApprovalsController.php
    controller/UserProblemsController.php
    controller/ContactFormsController.php
    controller/GenericTokenController.php

**EL LOG EXISTE Y MUERE CON LA PETICION.** `Mailer:28` guarda `$log` en memoria, alimentado por
el `Debugoutput` de PHPMailer (`:65-71`), con un getter en `:82`. **Un solo sitio lo lee en toda
la aplicacion**: `ContactFormsController:265`. No hay tabla, no hay archivo, no hay vista.

**Y ese unico sitio lo devuelve AL CLIENTE.** Ver el defecto de abajo.

**ARQUITECTO PROPUSO `EventsLog` COMO ALMACEN Y SE EQUIVOCO. Corregido el 2026-09-02, y lo
corrige la MEDICION, no la deferencia.** `actions_log` no puede ser el log de correo:

- **`createdBy` es `bigint NOT NULL` con clave ajena a `pcsphp_users`.** Un correo enviado por
  cron, o el OTP de quien AUN NO HA ENTRADO, **no tiene usuario**. No cabe en la tabla.
- **Todo es `text`/`longtext`** —`textMessage`, `textMessageVariables`, `meta`—. Un log guardado
  como texto libre no es filtrable, y «filtrable, cabeceras, respuestas de errores» era
  literalmente lo pedido.
- **El unico indice es `createdBy`.** No hay indice por fecha, y las columnas de referencia son
  `text`, que no se indexa sin prefijo.

**El correo va a TABLA PROPIA, con columnas tipadas.** Lo que se reutiliza de `EventsLog` es el
PATRON DE MODULO y la vista, no el almacen. El PROPIETARIO tenia razon al pedir un log
especifico; ARQUITECTO propuso reciclar y la medicion le da la razon a el.

---

## LO QUE **SI** ENTRA EN CAMPANA — un defecto, no una capacidad

`ContactFormsController:262-267`, dentro del `catch`:

```php
} catch (\Exception $e) {
    $resultOperation->setMessage($e->getMessage());
    $resultOperation->setValue('logMailer', $mailer->log());
    log_exception($e);
}
```

y en `:287`, `return $res->withJson($resultOperation);`.

**Cuando el SMTP falla, la conversacion SMTP entera se devuelve en el JSON de respuesta** — el
banner del servidor, el host, y el texto del error de autenticacion. La ruta es
`contact-forms-general`, declarada con `new Route(path, handler, name, 'POST')`: **cuatro
argumentos**, sin `require_login` ni `roles_allowed`.

**COTA, Y SE DICE**: ARQUITECTO ha medido EL CODIGO, no la respuesta. Falta por ejecutar:
(a) el cuerpo HTTP real con el SMTP roto a proposito, y (b) el veredicto de acceso de esa ruta
—`ContactFormsController` usa `ControllerRoutingTrait` y tiene `_allowedRoute:346`, y la
comprobacion 23 esta VERDE sin declarar esta ruta entre sus cuatro excepciones, asi que **algo
la esta dando por cubierta y hay que ver QUE**. No se deduce la pertenencia: se comprueba (§5).

El arreglo es de una linea —el log no viaja en la respuesta—, pero **el arreglo correcto es que
tenga donde ir**, y eso ya es el log de abajo.

---

## LO QUE EXTIENDE — va despues de la MAJOR

### 1. El log de correo

- **UNA sola puerta de salida.** Hoy hay cuatro clases que envian y ninguna registra. Mientras
  cada una escriba por su cuenta, el log tendra agujeros por definicion. Lo que se registra es
  el ENVIO, no el proveedor.
- **Que guarda**: fecha, via, remitente, destinatarios (to/cc/bcc), asunto, cabeceras completas,
  cuerpo (o su hash y tamano — decision del PROPIETARIO: guardar cuerpos es guardar datos
  personales), id de mensaje del proveedor, codigo y texto de respuesta, intentos, y el modulo y
  la ruta que lo pidio.
- **Visible para root, filtrable** por fecha, via, destinatario, asunto y estado.
- **Y su retencion**, que es la pregunta que nadie hace hasta que la tabla pesa 4 GB.

### 2. La vista de configuracion SMTP, mas solida

- **Probar credenciales desde la propia vista**: ya hay media pieza —`Mailer::checkSettedSMTP()`
  (`:131`) y `Mailer::checkSMTP(host, port)` (`:147`)—. Falta el boton, el envio de prueba real
  a una direccion que se escriba ahi, y **ensenar el log de esa prueba en la vista**, que es
  justo lo que hoy se le manda al visitante del formulario de contacto.
- Validacion de campos, TLS/SSL por puerto, y decir QUE fallo: DNS, conexion, TLS o AUTH son
  cuatro fallos distintos y hoy son un mensaje.

### 3. «Apariencia profesional» — lo que de verdad la produce

El PROPIETARIO pregunta que mas se puede integrar. La respuesta honesta es que **la apariencia
profesional de un correo es un 20% plantilla y un 80% autenticacion y reputacion**:

- **SPF, DKIM y DMARC** sobre un dominio de envio propio. Sin DKIM, el mejor HTML del mundo cae
  en spam o sale con el aviso «enviado en nombre de».
- **Return-Path y bounces**: recoger rebotes y quejas por webhook y **dejar de escribir a quien
  rebota**. Una lista que no se limpia degrada la reputacion del dominio entero.
- **`List-Unsubscribe` y `List-Unsubscribe-Post`** en cabecera: obligatorio de facto para los
  grandes proveedores en envios masivos, y es una cabecera.
- **Proveedor transaccional separado del de marketing.** Un OTP y un boletin no deben compartir
  reputacion de IP.
- Proveedores que encajan con lo que ya hay: **Mailjet y Mailgun ya estan integrados**;
  del resto, los que dan webhook de rebote y log propio son Amazon SES, Postmark, Resend y
  SendGrid. **Ninguna decision aqui: es la lista para que el PROPIETARIO elija.**
- **Plantillas**: una sola base con tablas y CSS en linea, probada en los tres clientes que
  rompen (Outlook, Gmail recortando a 102 KB, y modo oscuro). Hoy hay al menos cinco carpetas de
  vistas de correo distintas —`UserSystem/Views/mails`, `SystemApprovals/Views/mailing`,
  `view/usuarios/mail`, `view/mailing`, `core/system-views/mailing`—.

---

## TAMANO

Es una **etapa**, no un bloque: dos o tres bloques despues de la MAJOR, mas el defecto de
`ContactFormsController`, que se lleva media linea en cuanto se toque ese archivo.
