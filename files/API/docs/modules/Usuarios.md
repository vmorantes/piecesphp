# Usuarios

## GET

### {{baseURL}}/core/api/users/get-data-user/
- Autorización: Sí
- Descripción: Ruta que devuelve los datos de usuario
- Parámetros:
	- id: int (requerido) ID del usuario
- Devolución:
	- Tipo: JSON
	- Propiedades: 
		- userData: JSON Datos del usuario
	- Ejemplo:
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

requestURL.searchParams.set('id', 50);

xhr.open("GET", requestURL.href);

xhr.send(data);

//Respuesta
{
    "userData": {
        "id": 50,
        "username": ...,
        "firstname": ...,
        "secondname": ...,
        "first_lastname": ...,
        "second_lastname": ...,
        "email": ...,
        "type": ...,
        "status": ...,
        "failed_attempts": ...,
        "created_at": ...,
        "modified_at": ...,
        "misc": {
            "avatar": ...,
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
			- MISSING_OR_UNEXPECTED_PARAMS: No se recibieron los parámetros indicados
			- GENERIC_ERROR: Puede ser indicador de varios errores, debe atenderse al mensaje
		- user: text Nombre de usuario que se autenticó
		- message: text Mensaje de error, si corresponde
		- extras: array Array con contenidos adicionales que pueden ser útiles en caso de errores
		- userData: array Array con los datos del usuario conectado
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
	'auth' => true,
	'isAuth' => false,
	'token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJPbmxpbmUgSldUIEJ1aWxkZXIiLCJpYXQiOjE2NzY1Nzk3MTcsImV4cCI6MTcwODExNTcxNywiYXVkIjoid3d3LmV4YW1wbGUuY29tIiwic3ViIjoianJvY2tldEBleGFtcGxlLmNvbSIsIkdpdmVuTmFtZSI6IkpvaG5ueSIsIlN1cm5hbWUiOiJSb2NrZXQiLCJFbWFpbCI6Impyb2NrZXRAZXhhbXBsZS5jb20iLCJSb2xlIjpbIk1hbmFnZXIiLCJQcm9qZWN0IEFkbWluaXN0cmF0b3IiXX0.uTYf1Bga2DeeZfFYpt6tKGnq7sQPBASL5qx2qc13N9w',
	'error' => 'NO_ERROR',
	'user' => 'test_user',
	'message' => '',
	'extras' => [],
	'userData' => [],
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
- Descripción: Ruta para editar el perfil de usuario
- Parámetros:
	- id: int (requerido) ID del usuario que se está editando
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
		- success: bool Indica si se creó o no el usuario
		- extras: array Array con contenidos adicionales que pueden ser útiles en caso de errores
	- Ejemplo:
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
- Descripción: Ruta para editar la foto de perfil
- Parámetros:
	- id: int (requerido) ID del usuario que se está editando
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
- Descripción: Ruta para recuperar contraseña, envía un correo con un código para la recuperación
- Parámetros:
	- username: string (requerido) El email del usuario
- Devolución:
	- Tipo: JSON
	- Propiedades: 
		- send_mail: bool Indica si el correo fue enviado o no
		- error: text Código de error
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
    "message": "A message has been sent to the email provided."
}
```

### {{baseURL}}/core/api/users/change-password-code/
- Autorización: No
- Descripción: Ruta para cambiar la contraseña usando un código de recuperación
- Parámetros:
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




















