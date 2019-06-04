<?php

namespace App\LangMessages;

$lang = [];
$lang['lang'] = [];
$lang['general'] = [];
$lang['base_token_exceptions'] = [];
$lang['errors'] = [];
$lang['users'] = [];
$lang['messages-platform'] = [];

$lang['lang'] = [
    'es' => 'Español',
    'es-short' => 'ES',
    'en' => 'Inglés',
    'en-short' => 'EN',
];

$lang['general']['dashboard'] = 'Panel de administración';
$lang['general']['institution'] = 'Institución';
$lang['general']['home'] = 'Inicio';
$lang['general']['welcome'] = 'Bienvenido';
$lang['general']['logged_as'] = 'Iniciado como:';
$lang['general']['profile'] = 'Perfil';
$lang['general']['profiles'] = 'Perfiles';
$lang['general']['user'] = 'Usuario';
$lang['general']['users'] = 'Usuarios';
$lang['general']['admin'] = 'Administrador';
$lang['general']['admins'] = 'Administradores';
$lang['general']['logout'] = 'Cerrar sesión';
$lang['general']['should_be_logged'] = 'Debe iniciar sesión.';
$lang['general']['loging'] = 'Iniciar sesión.';
$lang['general']['end_session'] = 'Sesión terminada.';
$lang['general']['developed_by'] = 'Desarrollado por';
$lang['general']['list'] = 'Listado';
$lang['general']['create'] = 'Crear';
$lang['general']['edit'] = 'Editar';
$lang['general']['create_from_file'] = 'Crear desde un archivo';
$lang['general']['firstname'] = 'Primer nombre';
$lang['general']['secondname'] = 'Segundo nombre';
$lang['general']['name'] = 'Nombre';
$lang['general']['names'] = 'Nombres';
$lang['general']['username'] = 'Nombre de usuario';
$lang['general']['lastname'] = 'Apellido';
$lang['general']['lastnames'] = 'Apellidos';
$lang['general']['first-lastname'] = 'Primer apellido';
$lang['general']['second-lastname'] = 'Segundo apellido';
$lang['general']['code'] = 'Código';
$lang['general']['codes'] = 'Códigos';
$lang['general']['id-document'] = 'Documento de identificación';
$lang['general']['id-document-short'] = 'Doc. de identificación';
$lang['general']['email'] = 'Correo electrónico';
$lang['general']['email-short'] = 'Correo-e.';
$lang['general']['email-standard'] = 'Email';
$lang['general']['password'] = 'Contraseña';
$lang['general']['confirm-password'] = 'Confirmar contraseña';
$lang['general']['old-password'] = 'Contraseña anterior';
$lang['general']['current-password'] = 'Contraseña actual';
$lang['general']['actions'] = 'Acciones';
$lang['general']['id'] = 'ID';
$lang['general']['description'] = 'Descripción';
$lang['general']['state'] = 'Estado';
$lang['general']['status'] = 'Estado';
$lang['general']['type'] = 'Tipo';
$lang['general']['save'] = 'Guardar';
$lang['general']['send'] = 'Enviar';
$lang['general']['active'] = 'Activo';
$lang['general']['inactive'] = 'Inactivo';
$lang['general']['select_element'] = 'Seleccione un elemento';
$lang['general']['pending'] = 'Pendiente';
$lang['general']['support'] = 'Soporte';
$lang['general']['messages'] = 'Mensajes';
$lang['general']['message'] = 'Mensaje';

$lang['general']['your_password_was_restored'] = 'Su contraseña ha sido restaurada.';
$lang['general']['new_password_is'] = 'Su nueva contraseña es: ';
$lang['general']['click_for_restore_password'] = 'Click para restablecer su contraseña';
$lang['general']['link_expire_on_1_day'] = 'El enlace expira en 1 día';
$lang['general']['password_recovery'] = 'Recuperación de contraseña';
$lang['general']['password_restored'] = 'Contraseña nueva';
$lang['general']['verification_code'] = 'Código de verificación';
$lang['general']['user_forget'] = 'Usuario olvidado';
$lang['general']['user_blocked'] = 'Usuario bloqueado';
$lang['general']['enter_the_code'] = 'Ingresar el código';

$lang['general']['for'] = 'para';

$lang['base_token_exceptions']['INVALID_USER_LOGGIN'] = 'Usuario inválido.';
$lang['base_token_exceptions']['EXPIRED_TOKEN'] = 'El token ha expirado';
$lang['base_token_exceptions']['INVALID_TOKEN_SUPPLIED'] = 'Token suministrado inválido.';
$lang['base_token_exceptions']['NOT_SUPPORTED_ALGORITHM'] = 'Algoritmo no soportado.';
$lang['base_token_exceptions']['OPEN_SSL_UNABLE_SIGN'] = 'Firma open ssl inválida.';
$lang['base_token_exceptions']['EMPTY_KEY'] = 'Llave vacía.';
$lang['base_token_exceptions']['WRONG_NUMBER_SEGMENTS'] = 'Cantidad de segmentos equivocada.';
$lang['base_token_exceptions']['INVALID_ENCODING_HEADER'] = 'Codificación de header inválida.';
$lang['base_token_exceptions']['INVALID_ENCODING_CLAIMS'] = 'Codificación de claims inválida.';
$lang['base_token_exceptions']['INVALID_ENCODING_SIGNATURE'] = 'Codificación de signature inválida.';
$lang['base_token_exceptions']['EMPTY_ALGORITHM'] = 'Algoritmo vació.';
$lang['base_token_exceptions']['UNSUPPORTED_ALGORITHM'] = 'Algoritmo no soportado.';
$lang['base_token_exceptions']['NOT_ALLOWED_ALGORITHM'] = 'Algoritmo no permitido.';
$lang['base_token_exceptions']['INVALID_KEY_ID'] = 'ID de la llave inválida.';
$lang['base_token_exceptions']['EMPTY_KEY_ID'] = 'ID de llave vacía.';
$lang['base_token_exceptions']['SIGNATURE_VERIFICATION_FAILED'] = 'Fallo en la verificación de la firma.';
$lang['base_token_exceptions']['NOT_USED_YET'] = 'No puede usarse aún.';

$lang['errors']['NO_ERROR'] = '';
$lang['errors']['not_login'] = 'No hay ninguna sesión activa';
$lang['errors']['expired_login'] = 'La sesión ha expirado';
$lang['errors']['MISSING_OR_UNEXPECTED_PARAMS'] = 'Parámetros faltantes o inesperados.';
$lang['errors']['UNEXPECTED_ACTION'] = 'Acción inesperada.';
$lang['errors']['INVALID_API_KEY'] = 'El dominio no es aceptado o la llave de la api no es válida';
$lang['errors']['ROLE_NOT_EXISTS'] = 'El rol no existe';
$lang['errors']['PERMISSIONS_ERROR'] = 'No tiene permitido realizar esta acción.';
$lang['errors']['TOKEN_EXPIRED'] = 'Token expirado.';
$lang['errors']['RESTRICTED_AREA'] = 'Intenta acceder a un área restringida. Para la que no tiene permisos.';

//-----------------------------USUARIOS---------------------------------------//
$lang['users']['NO_ERROR'] = '';
$lang['users']['DUPLICATE_USER'] = 'El usuario "%1$s" está en uso.';
$lang['users']['DUPLICATE_EMAIL'] = 'El email "%1$s" está en uso.';
$lang['users']['CODE_DUPLICATE'] = 'El código "%1$s" está en uso.';
$lang['users']['INCORRECT_PASSWORD'] = 'Contraseña incorrecta.';
$lang['users']['USER_NO_EXISTS'] = 'El usuario "%1$s" no existe.';
$lang['users']['UNEXPECTED_ACTION'] = 'Acción inesperada.';
$lang['users']['ACTIVE_SESSION'] = 'Ya hay una sesión activa.';
$lang['users']['BLOCKED_FOR_ATTEMPTS'] = 'El usuario "%1$s" ha sido bloqueado porque ha llegado al límite de intentos fallidos.';
$lang['users']['DUPLICATE_DOCUMENT'] = 'El documento de identificación "%1$s" ya está registrado.';
$lang['users']['USER_CREATED'] = 'Usuario creado';
$lang['users']['USER_EDITED'] = 'Usuario editado';
$lang['users']['USER_DELETED'] = 'Usuario eliminado';
$lang['users']['USER_ERROR_ON_CREATION'] = 'Error al crear el usuario, intente más tarde.';
$lang['users']['USER_NOT_BELONG_INSTITUTION'] = 'El usuario no está asociado a la institución seleccionada.';
$lang['users']['EXPIRED_OR_NOT_EXIST_CODE'] = 'El código ha expirado o no existe.';
$lang['users']['NOT_MATCH_PASSWORDS'] = 'Las contraseñas no coinciden.';

//-----------------------------DÍAS---------------------------------------//
$lang['day'] = [
    'Domingo',
    'Lunes',
    'Martes',
    'Miércoles',
    'Jueves',
    'Viernes',
    'Sábado',
];
$lang['month'] = [
    'Enero',
    'Febrero',
    'Marzo',
    'Abril',
    'Mayo',
    'Junio',
    'Julio',
    'Agosto',
    'Septiembre',
    'Octubre',
    'Noviembre',
    'Diciembre',
];

$lang['users'] = array_merge( //Se combina con los mensajes de 'errors'
    $lang['errors'],
    $lang['users']
);

return $lang;
