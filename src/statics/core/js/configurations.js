/**
 * Datos accesibles globalmente
 * @namespace
 */
var pcsphpGlobals = {}

//──── Lenguaje ──────────────────────────────────────────────────────────────────────────
pcsphpGlobals.lang = (function () {
	let langHTML = document.querySelector('html').getAttribute('lang')

	let lang = 'es'

	if (langHTML != null && langHTML.length > 0) {
		lang = langHTML
	}

	return lang
})()
pcsphpGlobals.defaultLang = (function () {
	let defaultLangHTML = document.querySelector('html').getAttribute('dlang')

	let lang = 'es'

	if (defaultLangHTML != null && defaultLangHTML.length > 0) {
		lang = defaultLangHTML
	}

	return lang
})()

pcsphpGlobals.messages = {}

pcsphpGlobals.messages.es = {
	lang: {
		'es': 'Español',
		'en': 'Inglés',
		'fr': 'Francés',
	},
	langShort: {
		'es': 'ES',
		'en': 'EN',
		'fr': 'FR',
	},
	titles: {
		error: 'Error',
		success: 'Exito',
		created: 'Creado',
		edited: 'Editado',
	},
	errors: {
		pass_not_match: 'Error: las contraseñas deben coincidir.',
		unexpected_error: 'Ha ocurrido un error inesperado.',
		unexpected_error_try_later: 'Ha ocurrido un error inesperado, intente más tarde.',
		name_is_required: 'El nombre es requerido.',
		name_should_be_string: 'El nombre debe ser un string.',
		lastname_is_required: 'El apellido es requerido.',
		lastname_should_be_string: 'El apellido debe ser un string.',
		email_is_required: 'El email es requerido.',
		email_should_be_string: 'El email debe ser un string.',
		user_is_required: 'El nombre de usuario es requerido.',
		user_should_be_string: 'El nombre de usuario debe ser un string.',
		password_is_required: 'La contraseña es requerida.',
		password_should_be_string: 'La contraseña debe ser un string.',
	},
	semantic_calendar: {
		days: [
			'D',
			'L',
			'M',
			'M',
			'J',
			'V',
			'S',
		],
		months: [
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
		],
		monthsShort: [
			'Ene',
			'Feb',
			'Mar',
			'Abr',
			'May',
			'Jun',
			'Jul',
			'Ago',
			'Sep',
			'Oct',
			'Nov',
			'Dic',
		],
		today: 'Hoy',
		now: 'Ahora',
		am: 'AM',
		pm: 'PM'
	},
	semantic_form: {
		text: {
			unspecifiedRule: 'Por favor, introduza un valor válido',
			unspecifiedField: 'Este campo'
		},
		prompt: {
			empty: '{name} debe tener un valor',
			checked: '{name} debe estar marcado',
			email: '{name} debe ser un email válido',
			url: '{name} debe ser una url válida',
			regExp: '{name} no tiene el formato correcto',
			integer: '{name} debe ser un número entero',
			decimal: '{name} debe ser un número decimal',
			number: '{name} debe ser un número',
			is: '{name} debe ser \'{ruleValue}\'',
			isExactly: '{name} debe ser exactamente \'{ruleValue}\'',
			not: '{name} No puede ser \'{ruleValue}\'',
			notExactly: '{name} No puede ser exactamente \'{ruleValue}\'',
			contain: '{name} No puede contener \'{ruleValue}\'',
			containExactly: '{name} No puede contener exatamente \'{ruleValue}\'',
			doesntContain: '{name} debe contener  \'{ruleValue}\'',
			doesntContainExactly: '{name} debe contener exactamente \'{ruleValue}\'',
			minLength: '{name} debe contener al menos {ruleValue} caracteres',
			length: '{name} debe contener al menos {ruleValue} caracteres',
			exactLength: '{name} debe contener exatamente {ruleValue} caracteres',
			maxLength: '{name} no puede contener más de {ruleValue} caracteres',
			match: '{name} debe coincidir con el campo {ruleValue}',
			different: '{name} debe tener un valor diferente que el campo {ruleValue}',
			creditCard: '{name} debe ser un número de tarjeta de crédito válido',
			minCount: '{name} Debe tener al menos {ruleValue} elecciones',
			exactCount: '{name} Debe tener exatamente {ruleValue} elecciones',
			maxCount: '{name} Debe tener {ruleValue} o menos elecciones'
		}
	},
	datatables: {
		lang: {
			"decimal": "",
			"emptyTable": "No hay información disponible",
			"info": "Viendo desde _START_ hasta  _END_ de _TOTAL_ elementos",
			"infoEmpty": "Viendo desde 0 hasta 0 de 0 elementos",
			"infoFiltered": "(filtrado desde _MAX_ elementos)",
			"infoPostFix": "",
			"thousands": ".",
			"lengthMenu": "Ver _MENU_ elementos",
			"loadingRecords": "Cargando...",
			"processing": "Procesando...",
			"search": "",
			"searchPlaceholder": "Buscar...",
			"zeroRecords": "No se encontraron coincidencias",
			"paginate": {
				"first": "Primero",
				"last": "Último",
				"next": "Próximo",
				"previous": "Anterior"
			},
			"aria": {
				"sortAscending": ": activar ordenamiento de columnas ascendentemente",
				"sortDescending": ": activar ordenamiento de columnas descendentemente"
			}
		}
	},
	date: {
		days: [
			'Domingo',
			'Lunes',
			'Martes',
			'Miércoles',
			'Jueves',
			'Viernes',
			'Sábado',
		],
		daysLetter: [
			'D',
			'L',
			'M',
			'M',
			'J',
			'V',
			'S',
		],
		months: [
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
		],
		monthsShort: [
			'Ene',
			'Feb',
			'Mar',
			'Abr',
			'May',
			'Jun',
			'Jul',
			'Ago',
			'Sep',
			'Oct',
			'Nov',
			'Dic',
		],
		today: 'Hoy',
		now: 'Ahora',
		am: 'AM',
		pm: 'PM',
	},
	articles: {
		"Título": "Título",
		"Miniatura": "Miniatura",
		"Categoría": "Categoría",
		"Mes (número)": "Mes (número)",
		"Mes (texto)": "Mes (texto)",
		"Día (número)": "Día (número)",
		"Día (texto)": "Día (texto)",
		"Año": "Año",
		"Autor": "Autor",
		"Visitas": "Visitas",
		"URL": "URL",
		"Ha ocurrido un error al cargar los artículos.": "Ha ocurrido un error al cargar los artículos.",
		"Error": "Error",
		"Ha ocurrido un error al cargar las categorías.": "Ha ocurrido un error al cargar las categorías.",
		"Nombre": "Nombre",
		"Descripción": "Descripción",
		"No hay artículos.": "No hay artículos.",
		"No hay categorías.": "No hay categorías.",
	},
	news: {
		'Error': 'Error',
		'Ha ocurrido un error desconocido.': 'Ha ocurrido un error desconocido.',
		'Cargar más.': 'Cargar más.',
		'No hay noticias': 'No hay noticias',
		'Nombre': 'Nombre',
		'Inicio': 'Inicio',
		'Fin': 'Fin',
		'Creado': 'Creado',
		'Dirigido a': 'Dirigido a',
		'Acciones': 'Acciones',
		'Editar': 'Editar',
		'Eliminar': 'Eliminar',
		'Confirmación': 'Confirmación',
		'¿Quiere eliminar la noticia?': '¿Quiere eliminar la noticia?',
		'Sí': 'Sí',
		'No': 'No',
	},
	cropper: {
		'Agregar imagen': 'Agregar imagen',
		'Cambiar imagen': 'Cambiar imagen',
		'imagen': 'imagen',
		'Error': 'Error',
		'Ha ocurrido un error al configurar CropperAdapterComponent': 'Ha ocurrido un error al configurar CropperAdapterComponent',
		'Tamaño real de la máscara de corte %rx%r(px)': 'Tamaño real de la máscara de corte %rx%r(px)',
		'El ancho mínimo de la imagen debe ser: %rpx': 'El ancho mínimo de la imagen debe ser: %rpx',
		'Seleccione una imagen, por favor.': 'Seleccione una imagen, por favor.',
		'No hay imágenes seleccionadas.': 'No hay imágenes seleccionadas.',
		'No se ha encontrado ningún input de tipo file.': 'No se ha encontrado ningún input de tipo file.',
		'No se ha encontrado ningún canvas.': 'No se ha encontrado ningún canvas.',
	},
	quill: {
		'Falta(n) el componente o el textarea en el DOM.': 'Falta(n) el componente o el textarea en el DOM.',
		'Error en QuillAdapterComponent': 'Error en QuillAdapterComponent',
		'Ha ocurrido un error al instanciar.': 'Ha ocurrido un error al instanciar.',
		'Ha ocurrido un error al instanciar QuillAdapterComponent.': 'Ha ocurrido un error al instanciar QuillAdapterComponent.',
		'El componente "%r" ya está en uso.': 'El componente "%r" ya está en uso.',
		'Error': 'Error',
		'Ha ocurrido un error al carga la imagen.': 'Ha ocurrido un error al carga la imagen.',
		'Ha ocurrido un error al cargar la imagen.': 'Ha ocurrido un error al cargar la imagen.',
	},
	location: {
		'Seleccione una opción': 'Seleccione una opción',
		'Atención': 'Atención',
		'No hay departamentos registrados.': 'No hay departamentos registrados.',
		'No hay ciudades registradas en el/los departamento(s) seleccionado(s).': 'No hay ciudades registradas en el/los departamento(s) seleccionado(s).',
		'No hay locaciones registradas en la(s) ciudad(es) seleccionada(s).': 'No hay locaciones registradas en la(s) ciudad(es) seleccionada(s).',
		'Información': 'Información',
		'La ubicación "%r" no se encontró en el mapa, se usará una posición aproximada.': 'La ubicación "%r" no se encontró en el mapa, se usará una posición aproximada.',
		'La ubicación "%r" no se encontró en el mapa.': 'La ubicación "%r" no se encontró en el mapa.',
	},
	messenger: {
		'Error': 'Error',
		'Ha ocurrido un error desconocido.': 'Ha ocurrido un error desconocido.',
		'¡Listo!': '¡Listo!',
	},
	loginForm: {
		'Error': 'Error',
		'Ha ocurrido un error inesperado, intente más tarde.': 'Ha ocurrido un error inesperado, intente más tarde.',
		'Si continua con problemas para ingresar, por favor utilice la ayuda.': 'Si continua con problemas para ingresar, por favor utilice la ayuda.',
		'Por favor, verifique los datos de ingreso y vuelva a intentar.': 'Por favor, verifique los datos de ingreso y vuelva a intentar.',
		'Por favor, ingrese al siguiente enlace para desbloquear su usuario.': 'Por favor, ingrese al siguiente enlace para desbloquear su usuario.',
		'Se ha presentado un error al momento de ingresar, por favor intente nuevamente.': 'Se ha presentado un error al momento de ingresar, por favor intente nuevamente.',
		'CONTRASEÑA_INVÁLIDA': '<span class="text">Contraseña</span> <span class="mark">inválida</span>',
		'USUARIO_BLOQUEADO': '<span class="text">Usuario</span> <span class="mark">bloqueado</span>',
		'USUARIO_INEXISTENTE': '<span class="text">El usuario</span> <span class="mark">%r</span> <span class="text">no existe</span>',
		'ERROR_AL_INGRESAR': 'Error al ingresar',
	},
	userProblems: {
		'Será solucionada muy pronto, por favor verifique su correo en las próximas horas. <br> El correo puede estar en "No deseado", por favor revise la carpeta de Spam. El remitente del correo es <strong>%r</strong>.': 'Será solucionada muy pronto, por favor verifique su correo en las próximas horas. <br> El correo puede estar en "No deseado", por favor revise la carpeta de Spam. El remitente del correo es <strong>%r</strong>.',
		'Ingrese el código enviado a su correo, el correo puede estar en "No deseado", por favor revise la carpeta de Spam. El remitente del correo es <strong>%r</strong>.': 'Ingrese el código enviado a su correo, el correo puede estar en "No deseado", por favor revise la carpeta de Spam. El remitente del correo es <strong>%r</strong>.',
		'El correo ingresado no está asociado a ningún usuario, por favor ingrese otra cuenta de correo o puede crear una solicitud de soporte para asociar ese correo a su cuenta.': 'El correo ingresado no está asociado a ningún usuario, por favor ingrese otra cuenta de correo o puede crear una solicitud de soporte para asociar ese correo a su cuenta.',
		'El código ingresado está errado, por favor vuelva a ingresar el código, solicite uno nuevo o cree una solicitud de soporte para informar del error.': 'El código ingresado está errado, por favor vuelva a ingresar el código, solicite uno nuevo o cree una solicitud de soporte para informar del error.',
		'Ingrese con su usuario y la nueva contraseña': 'Ingrese con su usuario y la nueva contraseña',
		'Las contraseñas no coinciden': 'Las contraseñas no coinciden',
	},
	avatar: {
		'Confirmación': 'Confirmación',
		'¿Seguro de guardar el avatar?': '¿Seguro de guardar el avatar?',
		'Cargando...': 'Cargando...',
		'¿Seguro de guardar la foto de perfil?': '¿Seguro de guardar la foto de perfil?',
		'Sí': 'Sí',
		'No': 'No',
	},
	
	biShopProducts: {
		'Confirmación': 'Confirmación',
		'¿Quiere eliminar la imagen?': '¿Quiere eliminar la imagen?',
		'Sí': 'Sí',
		'No': 'No',
	},
}

pcsphpGlobals.messages.en = {
	lang: {
		'es': 'Spanish',
		'en': 'English',
		'fr': 'French',
	},
	langShort: {
		'es': 'ES',
		'en': 'EN',
		'fr': 'FR',
	},
	titles: {
		error: 'Error',
		success: 'Success',
		created: 'Created',
		edited: 'Edited',
	},
	errors: {
		pass_not_match: 'Error: passwords must match.',
		unexpected_error: 'An unexpected error has occurred.',
		unexpected_error_try_later: 'An unexpected error has occurred, try again later.',
		name_is_required: 'The name is required.',
		name_should_be_string: 'The name must be a string.',
		lastname_is_required: 'The last name is required.',
		lastname_should_be_string: 'The last name must be a string.',
		email_is_required: 'The email is required.',
		email_should_be_string: 'The email must be a string.',
		user_is_required: 'The username is required.',
		user_should_be_string: 'The username must be a string.',
		password_is_required: 'The password is required.',
		password_should_be_string: 'The password must be a string.',
	},
	semantic_calendar: {
		days: [
			'S',
			'M',
			'T',
			'W',
			'T',
			'F',
			'S',
		],
		months: [
			'January',
			'February',
			'March',
			'April',
			'May',
			'June',
			'July',
			'August',
			'September',
			'October',
			'November',
			'December',
		],
		monthsShort: [
			'Jan',
			'Feb',
			'Mar',
			'Apr',
			'May',
			'Jun',
			'Jul',
			'Aug',
			'Sep',
			'Oct',
			'Nov',
			'Dec',
		],
		today: 'Today',
		now: 'Now',
		am: 'AM',
		pm: 'PM',
	},
	semantic_form: {
		text: {
			unspecifiedRule: 'Please enter a valid value',
			unspecifiedField: 'This field',
		},
		prompt: {
			empty: `{name} must have a value`,
			checked: `{name} must be marked`,
			email: `{name} must be a valid email`,
			url: `{name} must be a valid url`,
			regExp: `{name} does not have the correct format`,
			integer: `{name} must be an integer`,
			decimal: `{name} must be a decimal number`,
			number: `{name} must be a number`,
			is: `{name} must be '{ruleValue}'`,
			isExactly: `{name} must be exactly '{ruleValue}'`,
			not: `{name} Cannot be '{ruleValue}'`,
			notExactly: `{name} It can't be exactly '{ruleValue}'`,
			contain: `{name} Cannot contain '{ruleValue}'`,
			containExactly: `{name} Cannot contain exactly '{ruleValue}'`,
			doesntContain: `{name} must contain '{ruleValue}'`,
			doesntContainExactly: `{name} must contain exactly '{ruleValue}'`,
			minLength: `{name} must contain at least {ruleValue} characters`,
			length: `{name} must contain at least {ruleValue} characters`,
			exactLength: `{name} must contain exactly {ruleValue} characters`,
			maxLength: `{name} cannot contain more than {ruleValue} characters`,
			match: `{name} must match the {ruleValue} field`,
			different: `{name} must have a different value than the {ruleValue} field`,
			creditCard: `{name} must be a valid credit card number`,
			minCount: `{name} You must have at least {ruleValue} choices`,
			exactCount: `{name} You must have exactly {ruleValue} choices`,
			maxCount: `{name} You must have {ruleValue} or less choose`,
		}
	},
	datatables: {
		lang: {
			"decimal": "",
			"emptyTable": "No information available",
			"info": "Viewing from _START_ to _END_ of _TOTAL_ elements",
			"infoEmpty": "Displaying 0 to 0 of 0 items",
			"infoFiltered": "(filtered from _MAX_ elements)",
			"infoPostFix": "",
			"thousands": ".",
			"lengthMenu": "See _MENU_ elements",
			"loadingRecords": "Loading...",
			"processing": "Processing ...",
			"search": "",
			"searchPlaceholder": "Look for...",
			"zeroRecords": "No matches found",
			"paginate": {
				"first": "First",
				"last": "Latest",
				"next": "Next",
				"previous": "Previous",
			},
			"aria": {
				"sortAscending": ": activate ascending column sorting",
				"sortDescending": ": activate descending column ordering"
			}
		}
	},
	date: {
		days: [
			'Sunday',
			'Monday',
			'Tuesday',
			'Wednesday',
			'Thursday',
			'Friday',
			'Saturday',
		],
		daysLetter: [
			'S',
			'M',
			'T',
			'W',
			'T',
			'F',
			'S',
		],
		months: [
			'January',
			'February',
			'March',
			'April',
			'May',
			'June',
			'July',
			'August',
			'September',
			'October',
			'November',
			'December',
		],
		monthsShort: [
			'Jan',
			'Feb',
			'Mar',
			'Apr',
			'May',
			'Jun',
			'Jul',
			'Aug',
			'Sep',
			'Oct',
			'Nov',
			'Dec',
		],
		today: 'Today',
		now: 'Now',
		am: 'AM',
		pm: 'PM',
	},
	articles: {
		"Título": "Title",
		"Miniatura": "Miniature",
		"Categoría": "Category",
		"Mes (número)": "Month (number)",
		"Mes (texto)": "Month (text)",
		"Día (número)": "Day (number)",
		"Día (texto)": "Day (text)",
		"Año": "Year",
		"Autor": "Author",
		"Visitas": "Visits",
		"URL": "URL",
		"Ha ocurrido un error al cargar los artículos.": "An error occurred while loading the items.",
		"Error": "Error",
		"Ha ocurrido un error al cargar las categorías.": "An error occurred while loading the categories.",
		"Nombre": "Name",
		"Descripción": "Description",
		"No hay artículos.": "There are no items.",
		"No hay categorías.": "There are no categories.",
	},
	news: {
		'Error': 'Error',
		'Ha ocurrido un error desconocido.': 'An unknown error has occurred.',
		'Cargar más.': 'Load more.',
		'No hay noticias': 'No news',
		'Nombre': 'Name',
		'Inicio': 'Start',
		'Fin': 'End',
		'Creado': 'Created',
		'Dirigido a': 'To',
		'Acciones': 'Actions',
		'Editar': 'Edit',
		'Eliminar': 'Remove',
		'Confirmación': 'Confirmation',
		'¿Quiere eliminar la noticia?': 'Do you want to delete the news?',
		'Sí': 'Yes',
		'No': 'No',
	},
	cropper: {
		'Agregar imagen': 'Add image',
		'Cambiar imagen': 'Change image',
		'imagen': 'image',
		'Error': 'Error',
		'Ha ocurrido un error al configurar CropperAdapterComponent': 'An error occurred configuring CropperAdapterComponent',
		'Tamaño real de la máscara de corte %rx%r(px)': 'Actual size of cutting mask %rx%r(px)',
		'El ancho mínimo de la imagen debe ser: %rpx': 'The minimum width of the image must be: %rpx',
		'Seleccione una imagen, por favor.': 'Select an image, please.',
		'No hay imágenes seleccionadas.': 'There are no images selected.',
		'No se ha encontrado ningún input de tipo file.': 'No file type input was found.',
		'No se ha encontrado ningún canvas.': 'No canvas found.',
	},
	quill: {
		'Falta(n) el componente o el textarea en el DOM.': 'The component or textarea is missing in the DOM.',
		'Error en QuillAdapterComponent': 'Error in QuillAdapterComponent',
		'Ha ocurrido un error al instanciar.': 'An error occurred while instantiating.',
		'Ha ocurrido un error al instanciar QuillAdapterComponent.': 'An error occurred while instantiating QuillAdapterComponent.',
		'El componente "%r" ya está en uso.': 'The component "%r" is already in use.',
		'Error': 'Error',
		'Ha ocurrido un error al carga la imagen.': 'An error has occurred while loading the image.',
		'Ha ocurrido un error al cargar la imagen.': 'An error occurred while loading the image.',
	},
	location: {
		'Seleccione una opción': 'Select an option',
		'Atención': 'Attention',
		'No hay departamentos registrados.': 'There are no registered departments.',
		'No hay ciudades registradas en el/los departamento(s) seleccionado(s).': 'There are no cities registered in the selected state/states.',
		'No hay locaciones registradas en la(s) ciudad(es) seleccionada(s).': 'There are no localities registered in the selected city(ies).',
		'Información': 'Information',
		'La ubicación "%r" no se encontró en el mapa, se usará una posición aproximada.': 'The location "%r" was not found on the map, an approximate position will be used.',
		'La ubicación "%r" no se encontró en el mapa.': 'The location "%r" was not found on the map.',
	},
	messenger: {
		'Error': 'Error',
		'Ha ocurrido un error desconocido.': 'An unknown error has occurred.',
		'¡Listo!': 'Ready!',
	},
	loginForm: {
		'Error': 'Error',
		'Ha ocurrido un error inesperado, intente más tarde.': 'An unexpected error has occurred, try again later.',
		'Si continua con problemas para ingresar, por favor utilice la ayuda.': 'If you continue to have problems entering, please use the help.',
		'Por favor, verifique los datos de ingreso y vuelva a intentar.': 'Please verify the login details and try again.',
		'Por favor, ingrese al siguiente enlace para desbloquear su usuario.': 'Please enter the following link to unlock your user.',
		'Se ha presentado un error al momento de ingresar, por favor intente nuevamente.': 'An error has occurred at the time of entry, please try again.',
		'CONTRASEÑA_INVÁLIDA': '<span class="mark">Invalid</span> <span class="text">password</span>',
		'USUARIO_BLOQUEADO': '<span class="text">User</span> <span class="mark">blocked</span>',
		'USUARIO_INEXISTENTE': '<span class="text">The user</span> <span class="mark">%r</span> <span class="text">does not exist</span>',
		'ERROR_AL_INGRESAR': 'Login failed',
	},
	userProblems: {
		'Será solucionada muy pronto, por favor verifique su correo en las próximas horas. <br> El correo puede estar en "No deseado", por favor revise la carpeta de Spam. El remitente del correo es <strong>%r</strong>.': 'It will be solved very soon, please check your mail in the next few hours. <br> The email may be in "Spam", please check the spam folder. The sender of the email is <strong>%r</strong>.',
		'Ingrese el código enviado a su correo, el correo puede estar en "No deseado", por favor revise la carpeta de Spam. El remitente del correo es <strong>%r</strong>.': 'Enter the code sent to your email, the email may be in "Spam", please check the spam folder. The sender of the email is <strong>%r</strong>.',
		'El correo ingresado no está asociado a ningún usuario, por favor ingrese otra cuenta de correo o puede crear una solicitud de soporte para asociar ese correo a su cuenta.': 'The email entered is not associated with any user, please enter another email account or you can create a support request to associate that email with your account.',
		'El código ingresado está errado, por favor vuelva a ingresar el código, solicite uno nuevo o cree una solicitud de soporte para informar del error.': 'The code entered is wrong, please re-enter the code, request a new one or create a support request to report the error.',
		'Ingrese con su usuario y la nueva contraseña': 'Login with your username and the new password',
		'Las contraseñas no coinciden': 'Passwords do not match',
	},
	avatar: {
		'Confirmación': 'Confirmation',
		'¿Seguro de guardar el avatar?': 'Are you sure to save the avatar?',
		'Cargando...': 'Loading...',
		'¿Seguro de guardar la foto de perfil?': 'Are you sure to save the profile picture?',
		'Sí': 'Yes',
		'No': 'No',
	},
}

if (typeof pcsphpGlobals.messages[pcsphpGlobals.lang] == 'undefined') {
	pcsphpGlobals.messages[pcsphpGlobals.lang] = pcsphpGlobals.messages[pcsphpGlobals.defaultLang]
}

//────────────────────────────────────────────────────────────────────────────────────────

/**
 * Configuración de los calendarios
 * 
 * @property configCalendar
 * @type {Object}
 */
pcsphpGlobals.configCalendar = {
	type: 'date',
	formatter: {
		date: function (date, settings) {
			if (!(date instanceof Date)) return ''
			return formatDate(date, 'd-m-Y')
		}
	},
	text: {
		days: _i18n('semantic_calendar', 'days'),
		months: _i18n('semantic_calendar', 'months'),
		monthsShort: _i18n('semantic_calendar', 'monthsShort'),
		today: _i18n('semantic_calendar', 'today'),
		now: _i18n('semantic_calendar', 'now'),
		am: _i18n('semantic_calendar', 'am'),
		pm: _i18n('semantic_calendar', 'pm')
	}
}

/**
 * Configuración de las tablas DataTables
 * 
 * @property configCalendar
 * @type {Object}
 */
pcsphpGlobals.configDataTables = {
	"searching": true,
	"pageLength": 10,
	"responsive": true,
	"language": _i18n('datatables', 'lang'),
	"order": [],
	"initComplete": function (settings, json) {
		let searchContainer = $('.dataTables_filter').parent()
		searchContainer.addClass('ui form')
	},
}

/**
 * Configuración de Cropper
 * 
 * @property configCropper
 * @type {Object}
 */
pcsphpGlobals.configCropper = {
	aspectRatio: 4 / 3,
	background: true,
	checkCrossOrigin: false,
	responsive: true,
	minCropBoxWidth: 1000,
	viewMode: 3
}


if (typeof $ !== 'undefined') {
	$(document).ready(function (e) {

		configCalendars()
		configMessagesValidationsSemanticForm()
		configDataTables()
		configColorPickers()
		pcsAdminSideBar('.ui-pcs.sidebar')
		genericFormHandler()

		let toggleDevCSSMode = $('[toggle-dev-css-mode]')
		let toggleDevCSSModeIsActive = typeof toggleDevCSSMode.attr('active') == 'string'

		toggleDevCSSMode.click(function (e) {

			let that = $(e.currentTarget)
			let selector = that.attr('toggle-dev-css-mode')

			if (typeof selector == 'string' && selector.trim().length > 0) {

				let classToAdd = 'dev-css-mode'
				let element = $(selector)

				if (element.hasClass(classToAdd)) {
					element.removeClass(classToAdd)
					that.find(`[type="checkbox"]`).attr('checked', false)
				} else {
					element.addClass(classToAdd)
					that.find(`[type="checkbox"]`).attr('checked', true)
				}

			}

		})

		if (toggleDevCSSModeIsActive) {
			toggleDevCSSMode.click()
		}

	})

	$(window).on('load', function (e) {

		configRichEditor()

	})
}

/**
 * configCalendars
 * @returns {void}
 */
function configCalendars() {
	let calendarios = $('[calendar-js]')
	let calendariosGrupos = $('[calendar-group-js]').toArray()

	try {

		let grupos = []

		calendarios.calendar(pcsphpGlobals.configCalendar)

		for (let calendarioGrupo of calendariosGrupos) {

			let grupo = $(calendarioGrupo).attr('calendar-group-js')

			if (grupos.indexOf(grupo) == -1 && typeof grupo == 'string' && grupo.trim().length > 0) {
				grupos.push(grupo)
			}
		}

		for (let grupo of grupos) {

			let start = $($(`[calendar-group-js='${grupo}'][start]`)[0])
			let end = $($(`[calendar-group-js='${grupo}'][end]`)[0])

			let minDate = start.attr('min')
			minDate = typeof minDate == 'string' && minDate.trim().length > 0 ? minDate.trim() : null
			try {
				minDate = minDate !== null ? new Date(minDate) : null
				if (!(minDate instanceof Date && !isNaN(minDate))) {
					minDate = null
				}
			} catch (error) {
				minDate = null
			}

			let maxDate = start.attr('max')
			maxDate = typeof maxDate == 'string' && maxDate.trim().length > 0 ? maxDate.trim() : null
			try {
				maxDate = maxDate !== null ? new Date(maxDate) : null
				if (!(maxDate instanceof Date && !isNaN(maxDate))) {
					maxDate = null
				}
			} catch (error) {
				maxDate = null
			}

			let startType = start.attr('calendar-type')
			startType = typeof startType == 'string' && startType.trim().length > 0 ? startType.trim() : 'datetime'
			startType = startType == 'datetime' || startType == 'date' ? startType : 'datetime'

			let endType = end.attr('calendar-type')
			endType = typeof endType == 'string' && endType.trim().length > 0 ? endType.trim() : 'datetime'
			endType = endType == 'datetime' || endType == 'date' ? endType : 'datetime'

			let optStart = Object.assign({}, pcsphpGlobals.configCalendar)
			let optEnd = Object.assign({}, pcsphpGlobals.configCalendar)

			optStart.type = startType
			optStart.minDate = minDate
			optStart.maxDate = maxDate
			optEnd.type = endType
			optEnd.maxDate = maxDate

			optStart.endCalendar = end
			optEnd.startCalendar = start

			start.calendar(optStart)
			end.calendar(optEnd)
		}

	} catch (error) {
		if (calendarios.calendar !== undefined) {
			console.error(error)
		}
	}
}

/**
 * configMessagesValidationsSemanticForm
 * @returns {void}
 */
function configMessagesValidationsSemanticForm() {
	if (
		$ !== undefined &&
		$.fn !== undefined &&
		$.fn.form !== undefined &&
		$.fn.form.settings !== undefined &&
		$.fn.form.settings.prompt !== undefined &&
		$.fn.form.settings.text !== undefined
	) {
		$.fn.form.settings.prompt = pcsphpGlobals.messages.es.semantic_form.prompt
		$.fn.form.settings.text = pcsphpGlobals.messages.es.semantic_form.text
	}
}

/**
 * configDataTables
 * @returns {void}
 */
function configDataTables() {
	let tablas = $('[datatable-js]')

	try {
		tablas.DataTable(pcsphpGlobals.configDataTables)
	} catch (error) {
		if (tablas.DataTable !== undefined) {
			console.error(error)
		}
	}
}

/**
 * configRichEditor
 * @returns {void}
 */
function configRichEditor() {

	try {
		if (typeof QuillAdapterComponent == 'function') {

			let elementRichEditorSelector = '[rich-editor-js]'
			let elementRichEditor = $(elementRichEditorSelector)

			if (elementRichEditor.length > 0) {

				new QuillAdapterComponent({
					containerSelector: elementRichEditorSelector,
					textareaTargetSelector: elementRichEditor.attr('editor-target'),
					urlProcessImage: elementRichEditor.attr('image-process'),
					nameOnRequest: elementRichEditor.attr('image-name'),
				})

			}

		}

	} catch (error) {
		console.log(error)
		if (error.name == 'ReferenceError') {
			console.log("QuillAdapterComponent no está definido.")
		} else {
			console.log(error)
		}
	}
}

/**
 * configColorPickers
 * @returns {void}
 */
function configColorPickers() {

	let selector = 'input[color-picker-js]'
	let colorPickers = $(selector)

	try {

		colorPickers.spectrum({
			color: null,
			preferredFormat: 'hex',
			showInput: true,
			showInitial: true,
			showAlpha: false,
			clickoutFiresChange: false,
			allowEmpty: true,
			flat: false,
			disabled: false,
			showButtons: true,
			chooseText: 'Aceptar',
			cancelText: 'Cancelar',
			showPalette: false,
			showSelectionPalette: false,
			togglePaletteOnly: true,
			togglePaletteMoreText: '+',
			togglePaletteLessText: '−',
			palette: [
				"red",
				"green",
				"blue",
				"purple",
				"yellow",
				"brown",
				"white",
				"gray",
				"black",
				"pink",
				"coral",
			],
		})

	} catch (error) {
		if (colorPickers.spectrum !== undefined) {
			console.error(error)
		}
	}
}

/**
 * pcsAdminSideBar
 * 
 * Configura la barra lateral de PiecesPHP
 * 
 * @param {HTMLElement|JQuery|string} selector Selector o elemento de la barra
 * @returns {void}
 */
function pcsAdminSideBar(selector) {

	let menu = $(selector)

	if (menu.length > 0) {

		let groups = menu.find('.group')

		if (groups.length > 0) {

			let titlesGroups = groups.find('.title-group').not('[href]')

			if (titlesGroups.length > 0) {

				titlesGroups.click(function (e) {

					e.preventDefault()

					let ancester = $(this).parent()
					let items = ancester.find('> .items')

					if (items.length > 0) {
						if (ancester.hasClass('active')) {
							ancester.removeClass('active')
							items.hide(500)
						} else {
							ancester.addClass('active')
							items.show(500)
						}
					}

					let ancesterOthers = titlesGroups.parent().not(ancester).not($(this).parents('.group'))
					let itemsOthers = ancesterOthers.find('.items')
					ancesterOthers.removeClass('active')
					itemsOthers.hide(500)
				})

			}

		}

		let toggle = $('.ui-pcs.sidebar-toggle')
		if (toggle.length > 0) {
			toggle.click(function (e) {
				if (menu.is(':visible')) {
					menu.fadeOut(500, function () {
						menu.attr('style', '')
						$(menu).removeClass('overlay')
					})
					$(this).removeClass('active')
				} else {
					$(menu).addClass('overlay')
					$(this).addClass('active')
				}

			})
		}
	}
}

/**
 * Internacionalización de mensajes
 * 
 * @param {string} type Tipo de mensaje
 * @param {*} message Mensaje
 */
function _i18n(type, message) {

	let messages = pcsphpGlobals.messages
	let langs = [
		pcsphpGlobals.lang,
		pcsphpGlobals.defaultLang,
	]
	let lang = ''

	let exists = false

	for (let langToCheck of langs) {

		lang = langToCheck
		let existsLang = messages[lang] !== undefined

		if (existsLang) {

			let existsType = messages[lang][type] !== undefined

			if (existsType) {
				let existsMessage = messages[lang][type][message] !== undefined

				if (existsMessage) {
					exists = true
					break
				}

			}

		}

	}

	if (exists) {
		return messages[lang][type][message]
	} else {
		return message
	}
}
