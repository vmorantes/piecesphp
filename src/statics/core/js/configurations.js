/**
 * Datos accesibles globalmente
 * @namespace
 */
var pcsphpGlobals = {}

/**
 * Lenguaje
 * 
 * @property lang
 * @type {Object}
 */
pcsphpGlobals.lang = (function () {
	let langHTML = document.querySelector('html').getAttribute('lang')

	let lang = 'es'

	if (langHTML != null && langHTML.length > 0) {
		lang = langHTML
	}

	return lang
})()

/**
 * Mensajes
 * 
 * @property messages
 * @type {Object}
 */

pcsphpGlobals.messages = {
	es: {
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
	},
}

pcsphpGlobals.messages.en = pcsphpGlobals.messages.es

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
			if (!date) return ''
			let util = new UtilPieces()
			return util.date.formatDate(date, 'd-m-y')
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
		pcsAdminSideBar('.ui-pcs.sidebar')
		genericFormHandler()

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

			let startType = start.attr('calendar-type')
			startType = typeof startType == 'string' && startType.trim().length > 0 ? startType.trim() : 'datetime'
			startType = startType == 'datetime' || startType == 'date' ? startType : 'datetime'

			let endType = end.attr('calendar-type')
			endType = typeof endType == 'string' && endType.trim().length > 0 ? endType.trim() : 'datetime'
			endType = endType == 'datetime' || endType == 'date' ? endType : 'datetime'

			let optStart = Object.assign({}, pcsphpGlobals.configCalendar)
			let optEnd = Object.assign({}, pcsphpGlobals.configCalendar)

			optStart.type = startType
			optEnd.type = endType

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
		if(typeof QuillAdapterComponent == 'function'){

			let elementRichEditorSelector = '[rich-editor-js]'
			let elementRichEditor = $(elementRichEditorSelector)

			if(elementRichEditor.length > 0){

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
		if(error.name == 'ReferenceError'){
			console.log("QuillAdapterComponent no está definido.")
		}else{
			console.log(error)
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

			let titlesGroups = groups.find('.title-group')
			if (titlesGroups.length > 0) {
				titlesGroups.click(function (e) {

					let ancester = $(this).parent()
					let items = ancester.find('.items')

					if (items.length > 0) {
						if (ancester.hasClass('active')) {
							ancester.removeClass('active')
							items.hide(500)
						} else {
							ancester.addClass('active')
							items.show(500)
						}
					}

					let ancesterOthers = titlesGroups.parent().not(ancester)
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
	let lang = pcsphpGlobals.lang

	let exists = false

	let existsLang = messages[lang] !== undefined

	if (existsLang) {

		let existsType = messages[lang][type] !== undefined

		if (existsType) {
			let existsMessage = messages[lang][type][message] !== undefined

			if (existsMessage) {
				exists = true
			}

		}

	}

	if (exists) {
		return messages[lang][type][message]
	} else {
		return message
	}
}
