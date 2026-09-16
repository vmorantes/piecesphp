# 0018 — La recuperación de contraseña es una sola: código ligado al usuario, con límite de intentos

- **Estado:** Aceptada
- **Fecha:** 2026-09-16
- **Decide:** Product Owner («soluciona lo que debas sin perder función», P30, A-038), con el diseño del arquitecto
- **Estructural:** sí (cambia el contrato de cinco rutas públicas y de dos acciones de la API)

## En cristiano

Recuperar la contraseña tenía dos caminos, y los dos estaban mal. El del enlace cambiaba la contraseña
al abrirlo y mandaba un correo que no la incluía: el usuario se quedaba fuera. El del código se podía
adivinar probando números, porque no tenía límite de intentos y aceptaba el código de cualquier usuario,
y al acertar devolvía el usuario con el hash de su contraseña. Desde ahora hay un único mecanismo, el
código: el enlace del correo lleva al formulario con el código ya puesto, el código solo vale para su
usuario, y tras unos pocos fallos se bloquea un rato.

## Contexto

Medido por el arquitecto leyendo el código el 2026-09-16 (la suite de la ronda lo confirma):

- **Por enlace.** `recovery-password-request` (POST) crea un token y manda `mailRecoveryPassword()`. El
  enlace abre `new-password-create` (GET `/users/recovery/{url_token}`), que genera una contraseña, la
  guarda y llama a `mailNewPassword()`. Esa plantilla (`usuarios/mail/restored_password.php`) no imprime la
  contraseña y usa una `$url` que no recibe. El usuario pierde el acceso; y como es un GET que escribe, un
  escáner de enlaces del correo lo dispara sin que nadie lo pida.
- **Por código.** `recovery-password-request-code` (POST) guarda un código de 6 cifras (`generate_code(6)`,
  con `rand()`) válido 24 horas y lo manda con un enlace a `recovery-form?code=…`, que el JS ya rellena.
  `new-password-verify-code` y `new-password-create-code` (POST, públicas) buscan el código con
  `RecoveryPasswordModel::exist($code)` **entre los de todos los usuarios**, sin límite de intentos.
  `verifyCode` devuelve el `username`; `newPasswordCreateCode` devuelve `user`, que sale de
  `UsersModel::getByEmail()` con `select()` sin campos: todas las columnas, hash incluido.
- **Las peticiones dicen si el usuario existe** (`USER_NO_EXISTS`, con el nombre dentro del mensaje), y
  guardan el código en claro en `TicketsLogModel`.
- `created` y `expired` son el mismo objeto: `$recoveryPassword->created->modify('+24 hour')` muta los dos.
- **La API** (`APIController::usersActions`, acciones `recovery-password` y `change-password-code`) duplica la
  petición y reutiliza `newPasswordCreateCode`, del que lee `user` para su registro.
- **Ya existe un limitador**: `OTPRateLimiter` cuenta fallos por usuario y por IP en `login_attempts`
  (`otp_security`: 5 por usuario y 20 por IP en 15 minutos, bloqueo de 15) y responde 429 con `Retry-After`.

## Decisión

1. **Un solo mecanismo: el código.**
   - `recovery-password-request` hace lo mismo que `recovery-password-request-code`.
   - `new-password-create` (GET) **no escribe**: redirige al formulario de recuperación. Un enlace viejo que
     siga en un buzón lleva al formulario, donde se pide un código nuevo.
   - Se retiran `mailRecoveryPassword()`, `mailNewPassword()` y sus plantillas `recovery_password.php` y
     `restored_password.php`, que quedan sin uso.
2. **El código se liga a su usuario.** Verificarlo y usarlo exige `username` (nombre de usuario o correo,
   como la petición) además de `code`. Vale solo si hay una fila con ese correo, ese código y sin expirar.
3. **Límite de intentos con `OTPRateLimiter`**, vía nueva `recovery-code`: antes de comprobar nada, si el
   usuario o la IP están bloqueados, 429 con `Retry-After`; cada intento se registra con su resultado.
4. **Ninguna respuesta devuelve datos del usuario**: ni `user` ni `userName`.
5. **Las peticiones responden igual exista o no el usuario** (web y API): `send_mail` true, `error`
   `NO_ERROR` y el mensaje uniforme que ya usa el OTP (`OTPRateLimiter::uniformOTPMessage()`, traducido). Si
   el envío falla para un usuario real, se registra en el log.
6. **El registro de tickets no guarda el código.**
7. **Al cambiar la contraseña se borran todos los códigos pendientes de ese correo.**
8. **`expired` se calcula sobre una copia** de `created`.
9. **El correo con enlace** lleva el correo del usuario además del código
   (`recovery-form?code=…&email=…`), y el formulario lo rellena.
10. **No entra aquí**: `generate_code()` con `random_int()` (P33, pendiente del PO, porque es un helper
    compartido), ni los códigos de «usuario olvidado» y «usuario bloqueado» de `UserProblemsController`.

## Alternativas descartadas

| Alternativa | Por qué no |
| --- | --- |
| Arreglar la plantilla para que imprima la contraseña generada | Mantiene un GET que escribe, que un escáner dispara, y manda la contraseña en claro por correo |
| Retirar la recuperación por enlace | El PO pidió no perder función; el correo del código ya lleva enlace |
| Un formulario propio para el enlace, con token largo | Dos mecanismos que mantener; el código ya cubre el caso |
| Solo límite por IP, sin ligar el código al usuario | Con muchos códigos pendientes y muchas IP, adivinar sigue siendo viable |
| Un limitador nuevo solo para la recuperación | Duplicaría `OTPRateLimiter`, que ya cuenta por usuario e IP y responde 429 |
| Códigos más largos en vez de límite | Cambia lo que el usuario teclea; el límite basta y protege también la API |

## Consecuencias

- **Lo bueno:**
  - la recuperación funciona por los dos caminos, y ninguno deja fuera al usuario;
  - adivinar un código pasa de viable a improbable: con la configuración por defecto, cinco fallos por nombre
    en 15 minutos bloquean 15 minutos, contra un millón de códigos posibles;
  - ninguna ruta pública entrega el hash ni confirma si un usuario existe.
- **Lo malo:**
  - **rompe** a quien llame a la API o a las rutas sin `username` (ruptura 29 del `CHANGELOG`);
  - los fallos de recuperación cuentan en el mismo límite que el OTP: quien falle cinco veces el código
    queda bloqueado también para el segundo factor durante el bloqueo;
  - un atacante puede bloquear 15 minutos la recuperación de una víctima fallando a propósito, igual que ya
    podía con el OTP;
  - la respuesta uniforme dice «enviado» aunque el correo haya fallado; el fallo queda en el log;
  - el enlace del correo lleva la dirección del usuario, que queda en los registros de acceso del servidor.

## Reversión

1. Devolver `RecoveryPasswordController`, `APIController` (acciones `recovery-password` y
   `change-password-code`), la vista `usuarios/problems/password.php` y `recovery-password.js` a su estado
   anterior, y restaurar las dos plantillas retiradas.
2. Quitar `VIA_RECOVERY_CODE` de `OTPRateLimiter::VIAS`.
3. Quitar la suite `core/password-recovery-guards` y la ruptura 29.

Es completa en código, **y reabre la toma de cuenta y la fuga del hash**. Las filas de intentos ya
registradas en `login_attempts` no molestan: caducan con la ventana.

## Verificación

- `unit-tests:core/password-recovery-guards`, que falla si se quita cada guarda: la ligadura al usuario, el
  límite de intentos y la retirada del usuario de la respuesta; y comprueba que el GET del enlace no escribe.
