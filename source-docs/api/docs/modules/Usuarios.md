# Usuarios

## GET

### {{baseURL}}/core/api/users/get-data-user/
- Autorización: Sí
- Descripción: Ruta que devuelve los datos del usuario **en sesión**. Solo acepta su propio id: con el de otro usuario, o sin sesión, responde **403**.
- Parámetros:
	- id: int (requerido) ID del usuario en sesión
- Devolución:
	- Tipo: JSON
	- Propiedades: 
		- userData: JSON Datos del usuario. Las claves van en camelCase: `id`, `organization`, `username`, `firstname`, `secondname`, `firstLastname`, `secondLastname`, `email`, `type`, `status`, `failedAttempts`, `createdAt`, `modifiedAt`, `sessionsValidFrom` y `misc` (el avatar y las meta-propiedades del usuario). No incluye la contraseña. A diferencia del paquete `userData` del login, **no es una lista corta**: trae todos esos campos.
		- Formato de cada clave (sale de `UsersModel::humanReadable()`, que convierte cada campo según su tipo en el mapper):
			- `id`, `type`, `status`, `failedAttempts`: número entero.
			- `organization`: número entero, o `null` si el usuario no pertenece a ninguna organización.
			- `username`, `firstname`, `firstLastname`, `email`: texto.
			- `secondname`, `secondLastname`: texto (vacío por defecto), o `null`.
			- `createdAt`, `modifiedAt`: texto con fecha y hora en formato `AAAA-MM-DD hh:mm:ss`.
			- `sessionsValidFrom`: texto con el mismo formato, o `null` si nunca se revocaron las sesiones del usuario.
			- `misc.avatar`: URL absoluta del avatar (`<baseURL>/statics/uploads/avatars/<id>/avatar.jpg`), o `null` si el usuario no tiene avatar. `misc` lleva además una clave por cada meta-propiedad registrada en el mapper de usuarios; el framework no registra ninguna.
	- Ejemplo (valores de muestra):
```js
//Solicitud
var data = new FormData();

var xhr = new XMLHttpRequest();
xhr.withCredentials = true;

xhr.addEventListener("readystatechange", function() {
  if(this.readyState === 4) {
    console.log(this.responseText);
  }
});

var requestURL = new URL("https://domain.tld/core/api/users/get-data-user");

requestURL.searchParams.set('id', 50); //Tiene que ser el id del usuario en sesión

xhr.open("GET", requestURL.href);

xhr.send(data);

//Respuesta
{
    "userData": {
        "id": 50,
        "organization": null,
        "username": "test_user",
        "firstname": "Nombre",
        "secondname": "",
        "firstLastname": "Apellido",
        "secondLastname": "",
        "email": "usuario@domain.tld",
        "type": 2,
        "status": 1,
        "failedAttempts": 0,
        "createdAt": "2026-01-01 10:00:00",
        "modifiedAt": "2026-01-02 10:00:00",
        "sessionsValidFrom": null,
        "misc": {
            "avatar": "https://domain.tld/statics/uploads/avatars/50/avatar.jpg"
        }
    }
}
```

## POST

### {{baseURL}}/users/login/
- Autorización: No
- Descripción: Ruta para autenticación (devuelve un JWT para usarse en las solicitudes que requieran autorización)
- Parámetros:
	- username: text (requerido) Nombre de usuario
	- password: text (requerido) Contraseña
	- twoFactor: text (opcional) Código del segundo factor (TOTP). Obligatorio solo si el usuario tiene activado el 2FA y ya vio su código QR; si falta o es inválido, el login responde `INVALID_TWO_FACTOR_CODE`. Con el usuario o la IP bloqueados por el límite del segundo factor, la ruta responde **429** con `Retry-After`
	- overwriteSession: text (opcional) Define si se sobreescribirá la sesión actual para generar un nuevo JWT
		- Valores:
			- yes
			- no (opción por defecto)
- Devolución:
	- Tipo: JSON
	- Propiedades: 
		- auth: bool Indica si la autenticación se llevó a cabo exitosamente o no
		- isAuth: bool Indica si ya se estaba autenticado al momento de intentar autenticarse
		- token: text El JWT
		- error: text
			- NO_ERROR: No hay ningún error
			- INCORRECT_PASSWORD: Contraseña equivocada
			- BLOCKED_FOR_ATTEMPTS: Bloquado por intentos fallidos
			- INACTIVE_USER: Usuario inactivo/bloqueado
			- USER_NO_EXISTS: El usuario no existe
			- ACTIVE_SESSION: Ya hay una sesión activa
			- INVALID_TWO_FACTOR_CODE: El usuario tiene 2FA y el código `twoFactor` falta o no es válido
			- ORGANIZATION_IS_NOT_ACTIVE: La organización del usuario no está activa
			- APPROVED_PENDING: El usuario está pendiente de aprobación (solo cuando la instalación exige aprobación para entrar)
			- NO_EXTERNAL_LOGIN_AVAILABLE: Con la cabecera `isExternalLogin: yes`, el tipo de usuario no está habilitado para este método de inicio de sesión
			- MISSING_OR_UNEXPECTED_PARAMS: No se recibieron los parámetros indicados
			- GENERIC_ERROR: Puede ser indicador de varios errores, debe atenderse al mensaje
		- user: text Nombre de usuario que se autenticó
		- message: text Mensaje de error, si corresponde
		- userData: array Datos del usuario conectado: `id`, `username`, `email`, `firstname`, `secondname`, `firstLastname`, `secondLastname`, `type`, `organization` y `misc` (avatar y meta-propiedades). Vacío si no hubo autenticación. No lo des por estable (ver la documentación del proyecto, «Consumir el framework sin el panel»)

La respuesta es solo ese objeto: no lleva `extras`.
	- Ejemplo:
```js
//Solicitud
var data = new FormData();
data.append("username", "test_user");
data.append("password", "123456");
data.append("overwriteSession", "no");

var xhr = new XMLHttpRequest();
xhr.withCredentials = true;

xhr.addEventListener("readystatechange", function() {
  if(this.readyState === 4) {
    //Something
  }
});

xhr.open("POST", "https://domain.tld/users/login/");

xhr.send(data);

//Respuesta
{
	"auth": true,
	"isAuth": false,
	"token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJPbmxpbmUgSldUIEJ1aWxkZXIiLCJpYXQiOjE2NzY1Nzk3MTcsImV4cCI6MTcwODExNTcxNywiYXVkIjoid3d3LmV4YW1wbGUuY29tIiwic3ViIjoianJvY2tldEBleGFtcGxlLmNvbSIsIkdpdmVuTmFtZSI6IkpvaG5ueSIsIlN1cm5hbWUiOiJSb2NrZXQiLCJFbWFpbCI6Impyb2NrZXRAZXhhbXBsZS5jb20iLCJSb2xlIjpbIk1hbmFnZXIiLCJQcm9qZWN0IEFkbWluaXN0cmF0b3IiXX0.uTYf1Bga2DeeZfFYpt6tKGnq7sQPBASL5qx2qc13N9w",
	"error": "NO_ERROR",
	"user": "test_user",
	"message": "",
	"userData": {
		"id": 50,
		"username": "test_user",
		"email": "usuario@domain.tld",
		"firstname": "Nombre",
		"secondname": "",
		"firstLastname": "Apellido",
		"secondLastname": "",
		"type": 2,
		"organization": null,
		"misc": {
			"avatar": "https://domain.tld/statics/uploads/avatars/50/avatar.jpg"
		}
	}
}
```

### {{baseURL}}/core/api/users/register/
- Autorización: No
- Descripción: Ruta para registrar usuario
- Parámetros:
	- username: text (requerido) Nombre de usuario
	- email: text (requerido) Correo electrónico
	- password: text (requerido) Contraseña
	- password2: text (requerido) Contraseña confirmación
	- firstname: text (requerido) Nombre
	- first_lastname: text (requerido) Apellido
	- type: text (opcional) Tipo de usuario. Solo se aceptan «usuario general» y «administrador de organización»; cualquier otro valor cae a «usuario general»
	- organizationID: text (opcional) Organización a la que se une. Cifrado con `BaseHashEncryption`. El valor literal `NONE` pide crear una organización nueva
	- organizationName: text (obligatorio si `organizationID` es `NONE`) Nombre de la organización que se va a crear
	- phoneCode / phoneNumber: text (opcional) Se guardan en el perfil
- Alta con organización nueva:
	- con `organizationID=NONE` y `organizationName`, el alta **crea primero la organización** y después el usuario;
	- ese usuario queda como **administrador de esa organización**, y su tipo pasa a «administrador de organización» aunque `type` diga otra cosa;
	- la organización nace **pendiente de aprobación**, y su NIT queda con un marcador `SIN_INFORMACION_…` que se corrige después desde el panel;
	- **si la organización no se puede crear, no se crea el usuario** y la organización a medias se retira. La respuesta trae `success: false` y un mensaje genérico con un **código de referencia** (`ERR-AAAAMMDD-XXXXXX`) que identifica el error en los logs de la instalación;
	- un usuario cuyo tipo exige organización **no se queda en la organización global por descuido**: si no hay organización válida, el alta falla.
- El usuario nace **pendiente de aprobación**: puede iniciar sesión y completar su perfil, pero no opera hasta que lo aprueben. Recibe un correo avisándolo.
- Devolución:
	- Tipo: JSON
	- Propiedades: 
		- name: text Nombre de la operación
		- message: text Mensaje de resultado
		- minimumRequiredSuccess: bool Indica si cumple o no los requisitos mínimos de parámetros esperados
		- success: bool Indica si se creó o no el usuario
		- extras: array Array con contenidos adicionales que pueden ser útiles en caso de errores
	- Ejemplo:
```js
//Solicitud
var data = new FormData();
data.append("username", "usuario");
data.append("email", "usuario@localhost");
data.append("password", "123456");
data.append("password2", "123456");
data.append("firstname", "Nombre");
data.append("first_lastname", "Apellido");
//Alta con organización nueva (opcional):
//data.append("organizationID", "NONE");
//data.append("organizationName", "Nombre de la organización");

var xhr = new XMLHttpRequest();
xhr.withCredentials = true;

xhr.addEventListener("readystatechange", function() {
  if(this.readyState === 4) {
    console.log(this.responseText);
  }
});

xhr.open("POST", "https://domain.tld/core/api/users/register/");

xhr.send(data);

//Respuesta
{
    "name": "User creation",
    "message": "User created",
    "minimumRequiredSuccess": true,
    "success": true,
    "extras": [],
}
```

### {{baseURL}}/core/api/users/edit/
- Autorización: Sí
- Descripción: Ruta para que el usuario en sesión edite **su propio** perfil. Con el `id` de otro usuario, o sin sesión, responde **403**. Los campos que no se envíen conservan su valor
- Parámetros:
	- id: int (requerido) ID del usuario en sesión
	- username: text (opcional) Nombre de usuario
	- email: text (opcional) Correo electrónico
	- current-password: text (opcional) Contraseña actual, solo en caso de que se quiera hacer un cambio de contraseña (debe ir junto con password y password2)
	- password: text (opcional) Contraseña nueva (debe ir junto con password2 y current-password)
	- password2: text (opcional) Confirmación de contraseña nueva (debe ir junto con password y current-password)
	- firstname: text (opcional) Nombre
	- first_lastname: text (opcional) Apellido
- Devolución:
	- Tipo: JSON
	- Propiedades: 
		- name: text Nombre de la operación
		- message: text Mensaje de resultado
		- minimumRequiredSuccess: bool Indica si cumple o no los requisitos mínimos de parámetros esperados
		- success: bool Indica si se editó el usuario
		- extras: array Array con contenidos adicionales que pueden ser útiles en caso de errores
	- Ejemplo (el `id` tiene que ser el del usuario en sesión):
```js
//Solicitud
var data = new FormData();
data.append("id", "3");
data.append("username", "username_new");

var xhr = new XMLHttpRequest();
xhr.withCredentials = true;

xhr.addEventListener("readystatechange", function() {
  if(this.readyState === 4) {
    console.log(this.responseText);
  }
});

xhr.open("POST", "https://domain.tld/core/api/users/edit/");

xhr.send(data);

//Respuesta
{
    "name": "User Edition",
    "message": "User edited",
    "minimumRequiredSuccess": true,
    "success": true,
    "extras": [],
}
```

### {{baseURL}}/core/api/users/profile-image/
- Autorización: Sí
- Descripción: Ruta para que el usuario en sesión cambie **su propia** foto de perfil. Con el `id` de otro usuario, o sin sesión, responde **403**
- Parámetros:
	- id: int (requerido) ID del usuario en sesión
	- image: File (requerido) Imagen.
- Devolución:
	- Tipo: JSON
	- Propiedades: 
		- success: bool Indica si se cargo la imagen
		- error: text Informa de algún error
		- message: text Mensaje de resultado
	- Ejemplo:
```js
//Solicitud
var data = new FormData();
data.append("id", "3");
data.append("image", fileInput.files[0], "profile-image.jpg");
 
var xhr = new XMLHttpRequest();
xhr.withCredentials = true;

xhr.addEventListener("readystatechange", function() {
  if(this.readyState === 4) {
    console.log(this.responseText);
  }
});

xhr.open("POST", "https://domain.tld/core/api/users/profile-image/");

xhr.send(data);

//Respuesta
{
    "success": true,
    "error": "NO_ERROR",
    "message": "Profile image updated"
}
```

### {{baseURL}}/core/api/users/recovery-password/
- Autorización: No
- Descripción: Ruta para recuperar contraseña, envía un correo con un código para la recuperación. **Responde
  igual exista o no el usuario**: no sirve para saber si una cuenta existe.
- Parámetros:
	- username: string (requerido) El email o el nombre del usuario
- Devolución:
	- Tipo: JSON
	- Propiedades: 
		- send_mail: bool Siempre `true` cuando los parámetros son correctos, exista o no el usuario
		- error: text Código de error (`NO_ERROR`, o `MISSING_OR_UNEXPECTED_PARAMS`)
		- message: text Mensaje de resultado
	- Ejemplo:
```js
//Solicitud
var data = new FormData();
data.append("username", "mail@domain.tld");
 
var xhr = new XMLHttpRequest();
xhr.withCredentials = true;

xhr.addEventListener("readystatechange", function() {
  if(this.readyState === 4) {
    console.log(this.responseText);
  }
});

xhr.open("POST", "https://domain.tld/core/api/users/recovery-password/");

xhr.send(data);

//Respuesta
{
    "send_mail": true,
    "error": "NO_ERROR",
    "message": "If the user exists, a code will be sent to their email."
}
```

### {{baseURL}}/core/api/users/change-password-code/
- Autorización: No
- Descripción: Ruta para cambiar la contraseña usando un código de recuperación. El código solo vale para su
  usuario, y tras varios fallos la ruta responde **429** con la cabecera `Retry-After` (límite de `otp_security`).
- Parámetros:
	- username: string (requerido) El email o el nombre del usuario al que se envió el código
	- code: string (requerido) El código
	- password: string (requerido) Contraseña
	- repassword: string (requerido) Contraseña confirmación
- Devolución:
	- Tipo: JSON
	- Propiedades: 
		- success: bool Indica si la solicitud tuvo éxito
		- error: text Código de error
		- message: text Mensaje de resultado
		- updated: (opcional) bool Informa si la contraseña fue cambiada o no.
	- Ejemplo:
```js
//Solicitud
var data = new FormData();
data.append("username", "mail@domain.tld");
data.append("code", "45678");
data.append("password", "123456");
data.append("repassword", "123456");
 
var xhr = new XMLHttpRequest();
xhr.withCredentials = true;

xhr.addEventListener("readystatechange", function() {
  if(this.readyState === 4) {
    console.log(this.responseText);
  }
});

xhr.open("POST", "https://domain.tld/core/api/users/change-password-code/");

xhr.send(data);

//Respuesta
{
    "success": true,
    "error": "NO_ERROR",
    "message": "Password changed.",
    "updated": true
}
```




















