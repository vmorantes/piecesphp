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
		days: ['D', 'L', 'M', 'M', 'J', 'V', 'S'],
		months: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
		monthsShort: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
		today: 'Hoy',
		now: 'Ahora',
		am: 'AM',
		pm: 'PM',
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
	semantic_search: {
		error: {
			logging: "Error en el registro de depuración, saliendo.",
			maxResults: "Los resultados deben ser una matriz para usar la configuración maxResults",
			method: "El método al que llamó no está definido.",
			noEndpoint: "No se especificó ningún punto final de búsqueda",
			noNormalize: "Se ignorará la configuración \"ignoreDiacritics\". El navegador no es compatible con String().normalize(). Puede considerar incluir <https://cdn.jsdelivr.net/npm/unorm@1.4.1/lib/unorm.min.js> como un polyfill.",
			noResults: "Su búsqueda no produjo resultados",
			noResultsHeader: "No hay resultados",
			noTemplate: "No se especificó un nombre de plantilla válido.",
			oldSearchSyntax: "La configuración de searchFullText se ha renombrado como fullTextSearch para mantener la coherencia, ajuste su configuración.",
			serverError: "Hubo un problema al consultar el servidor.",
			source: "No se puede buscar. No se usó ninguna fuente y no se incluyó el módulo API semantic",
		},
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
		days: ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'],
		daysLetter: ['D', 'L', 'M', 'M', 'J', 'V', 'S'],
		months: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
		monthsShort: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
		today: 'Hoy',
		now: 'Ahora',
		am: 'AM',
		pm: 'PM',
	},
	loginForm: {
		'CONTRASEÑA_INVÁLIDA': '<span class="text">Contraseña</span> <span class="mark">inválida</span>',
		'USUARIO_BLOQUEADO': '<span class="text">Usuario</span> <span class="mark">bloqueado</span>',
		'USUARIO_INEXISTENTE': '<span class="text">El usuario</span> <span class="mark">%r</span> <span class="text">no existe</span>',
		'ERROR_AL_INGRESAR': 'Error al ingresar',
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
		days: ['S', 'M', 'T', 'W', 'T', 'F', 'S'],
		months: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
		monthsShort: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
		today: 'Today',
		now: 'Now',
		am: 'AM',
		pm: 'PM',
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
		days: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
		daysLetter: ['S', 'M', 'T', 'W', 'T', 'F', 'S'],
		months: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
		monthsShort: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
		today: 'Today',
		now: 'Now',
		am: 'AM',
		pm: 'PM',
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
	public: {
		'Ver más': 'Read more',
	},
}

if (typeof pcsphpGlobals.messages[pcsphpGlobals.lang] == 'undefined') {
	pcsphpGlobals.messages[pcsphpGlobals.lang] = pcsphpGlobals.messages[pcsphpGlobals.defaultLang]
}

//────────────────────────────────────────────────────────────────────────────────────────

pcsphpGlobals.cacheStamp = (function () {
	let cacheStamp = document.querySelector('html head meta[name="cache-stamp"]')
	if (cacheStamp !== null) {
		cacheStamp = cacheStamp.getAttribute('value')
	}
	if (cacheStamp === null) {
		cacheStamp = 'none'
	}
	return cacheStamp
})()

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
		},
		datetime: function (date, settings) {
			if (!(date instanceof Date)) return ''
			return formatDate(date, 'd-m-Y h:i A')
		},
		month: 'MMMM YYYY',
		monthHeader: 'YYYY',
		time: 'h:mm A',
		year: 'YYYY',
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

/**
 * Medidas (en pixeles) y otros datos útiles para responsive
 */
pcsphpGlobals.responsive = {
	sizes: {
		rsXLarge: 1700,
		rsLarge: 1550,
		rsLarge1: 1400,
		rsLarge2: 1250,
		rsLarge3: 1200,
		rsMedium: 1000,
		rsTablet: 780,
		rsMobileMedium: 540,
		rsMobile: 480,
	},
	class: {
		rsXLarge: 'rs-xl-arge',
		rsLarge: 'rs-large',
		rsLarge1: 'rs-large1',
		rsLarge2: 'rs-large2',
		rsLarge3: 'rs-large3',
		rsMedium: 'rs-medium',
		rsTablet: 'rs-tablet',
		rsMobileMedium: 'rs-mobile-medium',
		rsMobile: 'rs-mobile',
	},
}

if (typeof $ !== 'undefined') {
	window.addEventListener('load', function (e) {

		configCalendars()
		configMessagesSemantic()
		configDataTables()
		configColorPickers()
		pcsAdminSideBar('.ui-pcs.sidebar')
		pcsAdminTopbars()
		genericFormHandler()
		configRichEditor()

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

		//Poner etiqueta alt a imágenes que no la tengan
		let notAltImages = document.querySelectorAll(`img:not([alt])`)
		notAltImages.forEach(function (element) {
			element.setAttribute('alt', element.src.indexOf('/') !== -1 ? element.src.split('/').reverse()[0] : element.src)
		})

	})

}

/**
 * configCalendars
 * @returns {void}
 */
function configCalendars() {

	try {
		configSinglesCalendar('calendar-js')
		configGroupCalendar('calendar-group-js')
	} catch (error) {
		if ($('').calendar !== undefined) {
			console.error(error)
		}
	}
}

/**
 * @param {Object} options
 * @returns {void}
 */
function configSinglesCalendar(selectorAttr, options = {}) {

	selectorAttr = typeof selectorAttr == 'string' && selectorAttr.length > 0 ? selectorAttr : null
	let calendars = $(`[${selectorAttr}]`).toArray()

	for (let calendar of calendars) {
		calendar = $(calendar)
		let calendarType = calendar.attr('calendar-type')
		calendarType = typeof calendarType == 'string' && calendarType.trim().length > 0 ? calendarType.trim() : 'date'
		calendarType = [
			'date',
			'datetime',
			'month',
		].indexOf(calendarType) !== -1 ? calendarType : 'datetime'
		let calendarOptions = Object.assign({}, pcsphpGlobals.configCalendar)
		calendarOptions.type = calendarType

		for (const customOption in options) {
			calendarOptions[customOption] = options[customOption]
		}
		$(calendar).calendar(calendarOptions)
	}

}

/**
 * @param {Object} options
 * @returns {Object[]}
 */
function configGroupCalendar(selectorAttr, selectorAttrName, options = {}) {

	selectorAttr = typeof selectorAttr == 'string' && selectorAttr.length > 0 ? selectorAttr : null
	selectorAttrName = typeof selectorAttrName == 'string' && selectorAttrName.length > 0 ? selectorAttrName : selectorAttr
	let groupCalendars = $(`[${selectorAttr}]`).toArray()

	const result = []
	let groups = []

	for (let groupCalendar of groupCalendars) {
		let groupName = $(groupCalendar).attr(selectorAttrName)
		if (groups.indexOf(groupName) == -1 && typeof groupName == 'string' && groupName.trim().length > 0) {
			groups.push(groupName)
		}
	}

	for (let group of groups) {

		let start = $($(`[${selectorAttrName}='${group}'][start]`)[0])
		let end = $($(`[${selectorAttrName}='${group}'][end]`)[0])

		const originalStartHTML = start.get(0).outerHTML
		const originalEndHTML = end.get(0).outerHTML

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

		let endType = end.attr('calendar-type')
		endType = typeof endType == 'string' && endType.trim().length > 0 ? endType.trim() : 'datetime'

		let baseConfig = Object.assign({}, pcsphpGlobals.configCalendar)

		for (const customOption in options) {
			baseConfig[customOption] = options[customOption]
		}

		let optStart = Object.assign({}, baseConfig)
		let optEnd = Object.assign({}, baseConfig)

		optStart.type = typeof options.type == 'string' ? options.type : startType
		optStart.minDate = minDate
		optStart.maxDate = maxDate
		optEnd.type = typeof options.type == 'string' ? options.type : endType
		optEnd.maxDate = maxDate

		optStart.endCalendar = end
		optEnd.startCalendar = start

		result[group] = {
			originalStartHTML: originalStartHTML,
			originalEndHTML: originalEndHTML,
			start: start.calendar(optStart),
			end: end.calendar(optEnd),
		}
		result[group].restart = function () {
			result[group].start.calendar('clear')
			result[group].end.calendar('clear')
		}
	}

	return result

}

/**
 * @returns {void}
 */
function configMessagesSemantic() {

	const lang = pcsphpGlobals.lang
	const messages = pcsphpGlobals.messages

	if (typeof messages[lang] != 'undefined') {

		if (
			$ !== undefined &&
			$.fn !== undefined
		) {

			if (
				$.fn.form !== undefined &&
				$.fn.form.settings !== undefined &&
				$.fn.form.settings.prompt !== undefined &&
				$.fn.form.settings.text !== undefined
			) {
				pcsphpGlobals.messages.en.semantic_form = {
					prompt: $.fn.form.settings.prompt,
					text: $.fn.form.settings.text,
				}

				$.fn.form.settings.prompt = messages[lang].semantic_form.prompt
				$.fn.form.settings.text = messages[lang].semantic_form.text

			}
			if (
				$.fn.search !== undefined &&
				$.fn.search.settings !== undefined &&
				$.fn.search.settings.error !== undefined
			) {
				pcsphpGlobals.messages.en.semantic_search = {
					error: $.fn.search.settings.error,
				}
				$.fn.search.settings.error = messages[lang].semantic_search.error
			}

		}
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
		if (typeof RichEditorAdapterComponent == 'function') {

			let elementRichEditorSelector = '[rich-editor-js]'
			let elementRichEditor = $(elementRichEditorSelector)

			if (elementRichEditor.length > 0) {

				new RichEditorAdapterComponent({
					containerSelector: elementRichEditorSelector,
					textareaTargetSelector: elementRichEditor.attr('editor-target'),
				})

			}

		}

	} catch (error) {
		console.log(error)
		if (error.name == 'ReferenceError') {
			console.log("RichEditorAdapterComponent no está definido.")
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

		const defaultConfigPicker = {
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
		}
		const validFormats = [
			'hex',
			'hex3',
			'hsl',
			'rgb',
			'name',
		]
		const pickersSize = colorPickers.length

		for (let i = 0; i < pickersSize; i++) {
			const picker = $(colorPickers.get(i))
			const configPicker = Object.assign({}, defaultConfigPicker)

			const withAlpha = picker.data('color-picker-alpha') === 'yes'
			const format = picker.data('color-picker-format')

			if (typeof format !== 'undefined' && validFormats.indexOf(format) !== -1) {
				configPicker.preferredFormat = format
			}
			configPicker.showAlpha = withAlpha

			picker.spectrum(configPicker)
		}

	} catch (error) {
		if (colorPickers.spectrum !== undefined) {
			console.error(error)
		}
	}
}

/**
 * Configura la barra lateral de PiecesPHP
 *
 * @param {HTMLElement|JQuery|string} selector Selector o elemento de la barra
 * @returns {void}
 */
function pcsAdminSideBar(selector) {

	let menu = $(selector)
	menu = menu.find(".content")

	if (menu.length > 0) {

		let groups = menu.find('.group')

		if (groups.length > 0) {

			let titlesGroups = groups.find('.title-group').not('[href]')

			if (titlesGroups.length > 0) {

				titlesGroups.click(function (e) {

					e.preventDefault()

					let ancester = $(this).parent()
					let items = ancester.find('> .items')

					if (!ancester.offsetParent().hasClass('contrack')) {
						if (items.length > 0) {
							if (ancester.hasClass('active')) {
								ancester.removeClass('active')
								items.hide(500)
							} else {
								ancester.addClass('active')
								items.show(500)
							}
						}
					} else {
						const elementPress = $(e.target)
						if (
							elementPress.hasClass('tool-item') &&
							elementPress[0].nodeName === 'A'
						) {
							window.location.href = elementPress.attr('href')
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

			toggle.on('click', function (e) {

				if (menu.is(':visible')) {

					menu.fadeOut(500, function () {
						menu.attr('style', '')
						$(menu).removeClass('overlay')
					})

					$(this).removeClass('active')

				} else {

					menu.attr('style', '')
					$(menu).addClass('overlay')
					$(this).addClass('active')
				}
			})
		}
	}

	const barController = $('[bar-controller]')

	barController.on('click', function (evt) {
		evt.stopPropagation()
		evt.preventDefault()
		transformAside()
		const activeGroups = menu.find('.group.active, .group .active')
		activeGroups.removeClass('active')
		activeGroups.find('>.items').hide(500)
	})

	const transformAside = () => {
		rotateLogo()

		const mainAside = $('[main-aside]')

		if (mainAside.hasClass('contrack')) {
			mainAside.removeClass('contrack')
			showOnexpand()
			$('.ui-pcs.container-sidebar').removeClass('no-expanded')
		} else {
			hideOnTrack()
			$('.ui-pcs.container-sidebar').addClass('no-expanded')
			mainAside.addClass('contrack')
		}
	}

	const hideOnTrack = () => {
		const toHide = $('[only-expanded]')
		toHide.addClass('inSide')
		toHide.on('animationend', () => {
			toHide.addClass('remove')
			toHide.removeClass('inSide')
		})
	}

	const showOnexpand = () => {
		const toHide = $('[only-expanded]')
		toHide.removeClass('remove')
		toHide.addClass('outSide')
		toHide.on('animationend', () => {
			toHide.removeClass('outSide')
			toHide.removeClass('remove')
		})
	}

	const rotateLogo = () => {
		const containerImage = $('[menu-footer-images]')

		if (containerImage.hasClass('close')) {
			containerImage.removeClass('close')
		} else {
			containerImage.addClass('close')
		}
	}

	// Controlador de los tooltips

	const menuItems = menu.find('.title-group')

	menuItems.each((index, item) => {
		$(item).on('mouseover', (e) => {
			e = $(e.target)

			const currentMenuItem = searchTitleGroup(e)

			let tooltip = currentMenuItem.find('.tool-tip')

			const rect = currentMenuItem[0].getBoundingClientRect()

			tooltip[0].style.top = rect.top + 'px'
		})
	})

	const searchTitleGroup = (e) => {
		if (e.hasClass('title-group')) {
			return e
		} else {
			return searchTitleGroup(e.parent())
		}
	}
}

/**
 * Configura los menús de configuraciones
 *
 * @returns {void}
 */
function pcsAdminTopbars() {

	const userOptionsMenu = $('.ui-pcs.topbar-options.user-options')
	const adminOptionsMenu = $('.ui-pcs.topbar-options.admin-options')
	const notificationsOptionsMenu = $('.ui-pcs.topbar-options.notifications-options')
	const profileContainer = $('.profile-content')

	const userOptionsMenuCloseButton = userOptionsMenu.find('.close')
	const adminOptionsMenuCloseButton = adminOptionsMenu.find('.close')
	const notificationsOptionsMenuCloseButton = notificationsOptionsMenu.find('.close')

	const userOptionsToggles = $('.ui-pcs.topbar-toggle.user-options')
	const adminOptionsToggles = $('.ui-pcs.topbar-toggle.admin-options')
	const notificationsOptionsToggles = $('.ui-pcs.topbar-toggle.notifications-options')

	const hasUserOptions = userOptionsMenu.length > 0 && userOptionsToggles.length > 0
	const hasAdminOptions = adminOptionsMenu.length > 0 && adminOptionsToggles.length > 0
	const hasNotificationsOptions = notificationsOptionsMenu.length > 0 && notificationsOptionsToggles.length > 0

	const hasProfile = profileContainer.length > 0

	if (hasUserOptions) {
		userOptionsToggles.on('click', function (e) {
			e.preventDefault()
			if (!isOpen(userOptionsMenu)) {
				open(userOptionsMenu)
			} else {
				close(userOptionsMenu)
			}
		})

		userOptionsMenuCloseButton.on('click', function (e) {
			e.preventDefault()
			close(userOptionsMenu)
		})
	}

	if (hasNotificationsOptions) {
		notificationsOptionsToggles.on('click', function (e) {
			e.preventDefault()
			if (!isOpen(notificationsOptionsMenu)) {
				open(notificationsOptionsMenu)
			} else {
				close(notificationsOptionsMenu)
			}
		})

		notificationsOptionsMenuCloseButton.on('click', function (e) {
			e.preventDefault()
			close(notificationsOptionsMenu)
		})
	}

	if (hasAdminOptions) {
		adminOptionsToggles.on('click', function (e) {
			e.preventDefault()
			if (!isOpen(adminOptionsMenu)) {
				open(adminOptionsMenu)
			} else {
				close(adminOptionsMenu)
			}
		})

		adminOptionsMenuCloseButton.on('click', function (e) {
			e.preventDefault()
			close(adminOptionsMenu)
		})
	}

	userOptionsMenu.find('[edit-account]').on('click', () => {
		close(userOptionsMenu)
		profileContainer.addClass('activated')
		tabsController('account')
	})
	userOptionsMenu.find('[change-password]').on('click', () => {
		close(userOptionsMenu)
		profileContainer.addClass('activated')
		tabsController('password')
	})
	profileContainer.find('[close-profile]').on('click', closeProfile)

	if (
		hasUserOptions || hasAdminOptions || hasNotificationsOptions || hasProfile
	) {
		window.addEventListener('click', function (e) {

			if (hasUserOptions) {
				const isUserToggle = userOptionsToggles[0] == e.target || userOptionsToggles[0].contains(e.target)
				if (!userOptionsMenu[0].contains(e.target) && !isUserToggle) {
					close(userOptionsMenu)
				}
			}

			if (hasAdminOptions) {
				const isAdminToggle = adminOptionsToggles[0] == e.target || adminOptionsToggles[0].contains(e.target)
				if (!adminOptionsMenu[0].contains(e.target) && !isAdminToggle) {
					close(adminOptionsMenu)
				}
			}

			if (hasNotificationsOptions) {
				const isNotificationsToggle = notificationsOptionsToggles[0] == e.target || notificationsOptionsToggles[0].contains(e.target)
				if (!notificationsOptionsMenu[0].contains(e.target) && !isNotificationsToggle
				) {
					close(notificationsOptionsMenu)
				}
			}

			if (hasProfile) {
				if (!profileContainer[0].contains(e.target) && !userOptionsMenu[0].contains(e.target) && profileContainer.hasClass('activated')) {
					closeProfile()
				}
			}
		})
	}

	function open(menu) {
		menu.parent().removeClass('close')
		menu.addClass('active')
	}

	function close(menu) {
		menu.parent().addClass('close')
		if (isOpen(menu)) {
			menu.removeClass('active')
			if (!menu.hasClass('deactive')) {
				menu.addClass('deactive')
			}
		}
	}

	function isOpen(menu) {
		return menu.hasClass('active')
	}

	function closeProfile() {
		profileContainer.removeClass('activated')
		profileContainer.addClass('desactivated')
		profileContainer.on('animationend', () => {
			profileContainer.removeClass('desactivated')
		})
	}

	const tabsController = (strDefauld = '') => {
		const tabs = profileContainer.find('[data-tab]')
		const views = profileContainer.find('[data-view]')

		tabs.on('click', (e) => {
			var target = $(e.target)

			if (target[0].nodeName === 'I' || target[0].nodeName === 'SPAN') {
				target = target.parent()
			}

			const tab = target.data('tab')

			views.each((index, view) => {
				const $view = $(view)

				if ($(tabs[index]).data('tab') === tab) {
					$(tabs[index]).addClass('current')
				} else {
					$(tabs[index]).removeClass('current')
				}

				if ($view.data('view') === tab) {
					$view.addClass('current')
				} else {
					$view.removeClass('current')
				}
			})
		})

		if (strDefauld != '') {
			tabs.filter(`[data-tab='${strDefauld}']`).trigger('click')
		}
	}

	const formAction = () => {
		const LOADER_NAME = 'editUser'

		const mainForm = $(".profile-content").find("form")

		mainForm.on('submit', (e) => {
			e.preventDefault()

			showGenericLoader(LOADER_NAME)

			const formData = new FormData(e.target)

			formData.set('is_profile', 'yes')

			postRequest('users/edit/', formData)
				.done((res) => {
					if (res.success) {
						successMessage(res.message)
						setTimeout(() => location.reload(), 2000)
					} else {
						errorMessage(res.message)
					}
				})
				.always(() => {
					removeGenericLoader(LOADER_NAME)
				})
		})
	}

	const loadNews = () => {

		const mainContainerSelector = '[news-toolbar-container]'
		const mainContainer = $(mainContainerSelector)
		const newsModal = $('[news-modal]')
		const url = $(mainContainerSelector).parent().data('url')

		if (mainContainer.length > 0 && typeof NewsAdapter !== 'undefined') {
			const newsManager = new NewsAdapter({
				requestURL: url,
				page: 1,
				perPage: 10,
				containerSelector: mainContainerSelector,
				onDraw: (item, parsed) => {
					parsed.on('click', () => {
						newsModal.find('.header').text(item.newsTitle).css('color', item.category.color)
						newsModal.find('.content').html(item.content)
						newsModal.modal('show')
						closeProfile()
					})
					return parsed
				},
				onEmpty: (container) => {
					container.html('...')
				},
			})

			newsManager.loadItems()
		}
	}

	const imageModalProfile = () => {
		const actionModal = $("[action-image-profile]")
		const modal = $("[profile-image-modal]")

		actionModal.on('click', () => {
			closeProfile()

			const instantiateCropper = (selector, ow = 400, ar = 400 / 400) => {
				return new SimpleCropperAdapter(selector, {
					aspectRatio: ar,
					format: 'image/jpeg',
					quality: 0.8,
					fillColor: 'white',
					outputWidth: ow,
				})
			}

			const cropper = instantiateCropper(`[simple-cropper-profile]`)

			cropper.onCropped(() => {
				const LOADER_NAME = 'updatePhotoLoader'
				const formData = new FormData()
				const userId = modal.find('.content').attr('user-id')
				const url = modal.find('.content').attr('action-url')

				formData.set('user_id', userId)
				formData.set('image', cropper.getFile())

				showGenericLoader(LOADER_NAME)

				postRequest(url, formData)
					.done((resp => {
						if (resp.success) {
							successMessage(resp.message)
							setTimeout(() => location.reload(), 2000)
						} else {
							errorMessage(resp.message)
						}
					}))
					.always(() => {
						removeGenericLoader(LOADER_NAME)
					})

			})

			cropper.onCancel(() => {
				modal.modal('hide')
			})

			modal.modal('show')
		})
	}

	imageModalProfile()
	loadNews()
	formAction()
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
