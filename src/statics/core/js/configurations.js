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
		configRichEditor()
		genericFormHandler()

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

		let blockA = [
			'bold',
			'italic',
			'underline',
			'strike'
		]

		let blockB = [
			{
				'list': 'ordered'
			},
			{
				'list': 'bullet'
			},
			'blockquote'
		]

		let blockC = [
			{
				'header': [
					1,
					2,
					3,
					4,
					5,
					6,
					false
				]
			}
		]

		let blockD = [
			{
				'script': 'sub'
			},
			{
				'script': 'super'
			}
		]

		let blockE = [
			{
				'color': []
			}, {
				'background': []
			}
		]

		let blockF = [
			{
				'font': []
			}
		]

		let blockG = [
			{
				'align': []
			}
		]

		let blockH = [
			'image',
			'clean',
			'show-source',
		]

		let toolbarOptions = [
			blockA,
			blockB,
			blockC,
			blockD,
			blockE,
			blockF,
			blockG,
			blockH,
		]

		let elementRichEditor = $('[rich-editor-js]')
		let urlProcessImage = elementRichEditor.attr('image-process')
		let nameOnRequest = elementRichEditor.attr('image-name')
		let target = elementRichEditor.attr('editor-target')
		let quillHandlers = quillsHandlers()

		if (typeof urlProcessImage == 'string' && urlProcessImage.trim().length > 0) {
			urlProcessImage = urlProcessImage.trim()
		} else {
			urlProcessImage = ''
		}
		if (typeof nameOnRequest == 'string' && nameOnRequest.trim().length > 0) {
			nameOnRequest = nameOnRequest.trim()
		} else {
			nameOnRequest = 'image-quill'
		}
		if (typeof target == 'string' && target.trim().length > 0) {
			target = target.trim()
		} else {
			return null
		}

		let targetElement = $(target)

		if (targetElement.length < 1) {
			return null
		}

		targetElement.css({
			height: '0px',
			minHeight: '0px',
			maxHeight: '0px',
			outline: 'none',
			cursor: 'default',
			width: '0px',
			opacity: '0',
		})

		let quill = new Quill(elementRichEditor.get(0), {
			theme: 'snow',
			modules: {
				toolbar: toolbarOptions,
				imageUpload: {
					url: urlProcessImage,
					method: 'POST',
					name: nameOnRequest,
					callbackOK: (serverResponse, next) => {
						if (serverResponse.success) {
							next(serverResponse.values.path)
						}
					},
					// personalize failed callback
					callbackKO: serverError => {
						console.log(serverError);
					}
				},
				imageResize: {}
			}
		})

		quill.on('editor-change', (delta, oldDelta, source) => {
			let html = elementRichEditor.get(0).children[0].innerHTML
			if (quill.getText().trim().length > 0) {
				targetElement.val(html)
			} else {
				targetElement.val('')
			}
		})

		let toolbarModule = quill.getModule('toolbar')
		toolbarModule.addHandler('show-source', quillHandlers.showSource(quill, elementRichEditor))

	} catch (error) {
	}

	/**
	 * Configura handlers personalizados a QuillJS
	 */
	function quillsHandlers() {
		this.showSource = function (quill, elementRichEditor) {

			elementRichEditor = $(elementRichEditor)

			let editor = elementRichEditor[0]

			let customButton = document.querySelector('.ql-show-source')

			customButton.innerHTML = `<i class="code icon"></i>`

			let modalEditor = null
			let textarea = null
			let modalEditorExists = false

			customButton.addEventListener('click', function () {

				if (!modalEditorExists) {
					modalEditor = getEditorHTML()
					textarea = modalEditor.find('textarea')
					modalEditorExists = true

					let html = editor.children[0].innerHTML
					let forFormating = $("<div></div>").html(html).get(0)

					let formatOptions = {
						"indent": "auto",
						"indent-spaces": 4,
						"wrap": 80,
						"markup": true,
						"output-xml": false,
						"numeric-entities": true,
						"quote-marks": true,
						"quote-nbsp": false,
						"show-body-only": true,
						"quote-ampersand": false,
						"break-before-br": true,
						"uppercase-tags": false,
						"uppercase-attributes": false,
						"drop-font-tags": true,
						"tidy-mark": false
					}

					let formatedHTML = tidy_html5(forFormating.innerHTML, formatOptions)

					textarea.val(formatedHTML)

					modalEditor.show(500)
				}

			})

			function getEditorHTML() {

				let modalEditor = document.createElement('div')
				let textarea = document.createElement('textarea')
				let buttonFinish = document.createElement('button')

				let css1 = `
			display:none;
			width: 100%;
			height: 100%;
			position: fixed;
			top: 50%;
			left: 50%;
			transform: translate(-50%, -50%);
			background-color: rgba(0, 0, 0, 0.5);
			text-align: center;
			padding: 3rem;
			max-height: 100%;
			overflow: auto;
			`
				let css2 = `    
			display: block;
			width: 90%;
			padding: 20px;
			line-height: 24px;
			background: rgb(29, 29, 29);
			color: rgb(255, 168, 40);
			font-family: consola;
			font-size: 22px;
			min-height: 500px;
			height: 80%;
			resize: none;
			margin: 0 auto;
			max-width: 1000px;
			`

				modalEditor.style.cssText = css1
				textarea.style.cssText = css2

				modalEditor = $(modalEditor)
				textarea = $(textarea)
				buttonFinish = $(buttonFinish)

				buttonFinish.addClass('ui button green')
				buttonFinish.html('Terminar edición')

				modalEditor.append("<h1 style='color:white;'>Editor de código</h1>")
				modalEditor.append(textarea)
				modalEditor.append("<br><br>")
				modalEditor.append(buttonFinish)
				$('body').append(modalEditor)

				buttonFinish.on('click', function () {
					let html = textarea.val()
					quill.pasteHTML(html)

					modalEditor.hide(500, () => {
						modalEditor.remove()
						modalEditorExists = false
					})
				})

				return modalEditor
			}

		}
		return this
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
