/**
 * Datos accesibles globalmente
 * @namespace
 */
var globales = {}

/**
 * Lenguaje
 * 
 * @property lang
 * @type {Object}
 */
globales.lang = (function () {
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

globales.messages = {
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

globales.messages.en = globales.messages.es

/**
 * Configuración de los calendarios
 * 
 * @property configCalendar
 * @type {Object}
 */
globales.configCalendar = {
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
globales.configDataTables = {
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
globales.configCropper = {
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
		pcsSideBar('.ui-pcs.sidebar')
		pcsTopBar('.ui-pcs.topbar')
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

		calendarios.calendar(globales.configCalendar)

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

			let optStart = Object.assign({}, globales.configCalendar)
			let optEnd = Object.assign({}, globales.configCalendar)

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
		$.fn.form.settings.prompt = globales.messages.es.semantic_form.prompt
		$.fn.form.settings.text = globales.messages.es.semantic_form.text
	}
}

/**
 * configDataTables
 * @returns {void}
 */
function configDataTables() {
	let tablas = $('[datatable-js]')

	try {
		tablas.DataTable(globales.configDataTables)
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
		let toolbarOptions = [
			[
				'bold',
				'italic',
				'underline',
				'strike'
			],
			[
				{
					'list': 'ordered'
				},
				{
					'list': 'bullet'
				},
				'blockquote'
			],

			[
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
			],

			[
				{
					'script': 'sub'
				},
				{
					'script': 'super'
				}
			],


			[
				{
					'color': []
				}, {
					'background': []
				}
			],
			[
				{
					'font': []
				}
			],
			[
				{
					'align': []
				}
			],
			[
				'image',
				'clean',
				'show-source',
			],
		];

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
}

/**
 * dataTableServerProccesing
 * @param {String} table 
 * @param {String} ajaxURL 
 * @param {Number} perPage 
 * @param {Object} options 
 * @returns {$}
 */
function dataTableServerProccesing(table, ajaxURL, perPage, options) {

	perPage = typeof perPage == 'number' ? parseInt(perPage) : 10
	perPage = perPage < 1 ? 10 : perPage
	ajaxURL = typeof ajaxURL == 'string' && ajaxURL.length > 0 ? ajaxURL : null
	options = typeof options == 'object' ? options : {}

	if (table instanceof HTMLElement) {
		table = $(table)
	}

	let columnsDefinitions = []
	let thElements = table.find('thead th').toArray()

	for (let index in thElements) {

		let e = thElements[index]

		let columnDefinition = {
			targets: parseInt(index),
			title: e.innerHTML,
			name: e.innerHTML,
			searchable: true,
			orderable: true,
		}

		let searchable = e.getAttribute('search')
		let orderable = e.getAttribute('order')
		let name = e.getAttribute('name')

		if (searchable != null) {
			columnDefinition.searchable = searchable == 'true'
		}
		if (orderable != null) {
			columnDefinition.orderable = orderable == 'true'
		}
		if (name != null) {
			columnDefinition.name = name
		}

		columnsDefinitions.push(columnDefinition)
	}

	if (typeof options.columnDefs != 'undefined' && Array.isArray(options.columnDefs)) {

		for (let index in options.columnDefs) {

			let definition = options.columnDefs[index]
			let targets = []

			if (typeof definition.targets != 'undefined') {

				targets = Array.isArray(definition.targets) ? definition.targets : [definition.targets]

				for (let target of targets) {
					if (typeof columnsDefinitions[target] != 'undefined') {
						for (let optionDef in definition) {
							if (optionDef != 'targets') {
								columnsDefinitions[target][optionDef] = definition[optionDef]
							}
						}
					}
				}

			}

		}
	}

	options.columnDefs = columnsDefinitions

	let is_valid = table instanceof $ || table instanceof HTMLElement
	is_valid = is_valid && ajaxURL != null

	if (is_valid) {

		let configDataTable = Object.assign({}, globales.configDataTables)

		for (let option in options) {
			configDataTable[option] = options[option]
		}

		configDataTable.processing = true
		configDataTable.serverSide = true
		configDataTable.ajax = ajaxURL
		configDataTable.pageLength = perPage

		table.DataTable(configDataTable)

		return table

	} else {
		throw new Error('Los parámetros son inválidos')
	}
}

/**
 * genericFormHandler
 * 
 * Manejador genérico de formularios
 * 
 * @param {String} selector 
 * @param {genericFormHandler.Options} options
 * @returns {void} 
 */
function genericFormHandler(selector = 'form[pcs-generic-handler-js]', options = {}) {

	/**
	 * @typedef genericFormHandler.Options
	 * @property {genericFormHandler.Options.ConfirmationOption} [confirmation]
	 * @property {Function} [onSetFormData]
	 * @property {Function} [onSetForm]
	 * @property {Function} [validate]
	 */
	/**
	 * @typedef genericFormHandler.Options.ConfirmationOption
	 * @property {String} selector Selector ddel elemento
	 * @property {String} [title] Título
	 * @property {String} [message]	Mensaje de advertencia
	 * @property {String} [positive] Texto afirmativo
	 * @property {String} [negative] Texto negativo
	 * @property {Function} [condition]
	 */
	let ignore;

	selector = typeof selector == 'string' && selector.trim().length > 0 ? selector.trim() : `form[pcs-generic-handler-js]`

	let form = $(`${selector}`)

	let hasConfirmation = false
	let buttonConfirmation = null
	let onSetFormData = function (formData) {
		return formData
	}
	let onSetForm = function (form) {
		return form
	}
	let validate = function (form) {
		return true
	}

	if (typeof options == 'object') {
		if (typeof options.confirmation == 'object') {

			let confirmationOptions = options.confirmation

			if (typeof confirmationOptions.selector == 'string') {
				buttonConfirmation = $(confirmationOptions.selector)
				hasConfirmation = buttonConfirmation.length > 0
			}
			if (typeof confirmationOptions.title != 'string') {
				options.confirmation.title = 'Confirmación'
			}
			if (typeof confirmationOptions.message != 'string') {
				options.confirmation.message = '¿Está seguro de realizar esta acción?'
			}
			if (typeof confirmationOptions.positive != 'string') {
				options.confirmation.positive = 'Sí'
			}
			if (typeof confirmationOptions.negative != 'string') {
				options.confirmation.negative = 'No'
			}
			if (typeof confirmationOptions.condition != 'function') {
				options.confirmation.condition = () => true
			}

			if (hasConfirmation) {
				hasConfirmation = options.confirmation.condition(buttonConfirmation) === true
			}

		}
		if (typeof options.onSetFormData == 'function') {
			onSetFormData = options.onSetFormData
		}
		if (typeof options.onSetForm == 'function') {
			onSetForm = options.onSetForm
		}
		if (typeof options.validate == 'function') {
			validate = options.validate
		}
	}

	if (form.length > 0) {

		form.submit(function (e) {

			e.preventDefault()

			let thisForm = $(e.target)

			if (validate(form)) {
				if (!hasConfirmation) {

					submit(thisForm)

				} else {

					iziToast.question({
						timeout: 20000,
						close: false,
						overlay: true,
						displayMode: 'once',
						id: 'question',
						zindex: 999,
						title: options.confirmation.title,
						message: options.confirmation.message,
						position: 'center',
						buttons: [
							[
								`<button><b>${options.confirmation.positive}</b></button>`,
								(instance, toast) => {
									submit(thisForm)
									instance.hide({
										transitionOut: 'fadeOut'
									}, toast, 'button')
								},
								true
							],
							[
								`<button>${options.confirmation.negative}</button>`,
								(instance, toast) => {
									instance.hide({
										transitionOut: 'fadeOut'
									}, toast, 'button')
								}
							],
						]
					})

				}
			}

			return false

		})
	}

	function submit(form) {

		let formData = new FormData(form[0])
		form.find('button[submit]').attr('disabled', true)

		let action = form.attr('action')
		let method = form.attr('method')
		let validAction = typeof action == 'string' && action.trim().length > 0
		let validMethod = typeof method == 'string' && method.trim().length > 0
		method = validMethod ? method.trim().toUpperCase() : 'POST'

		if (validAction) {

			let request = null

			showLoader()

			if (method == 'POST') {

				let processFormData = onSetFormData(formData)

				if (typeof processFormData.then !== 'undefined') {
					processFormData.then(function (formData) {
						request = postRequest(action, formData)
						handlerRequest(request)
					})
				} else {
					request = postRequest(action, processFormData)
					handlerRequest(request)
				}

			} else {
				let processForm = onSetForm(form)
				if (typeof processForm.then !== 'undefined') {
					processForm.then(function (form) {
						request = getRequest(action, form)
						handlerRequest(request)
					})
				} else {
					request = getRequest(action, processForm)
					handlerRequest(request)
				}
			}

		} else {
			console.error('No se ha definido ninguna acción')
			errorMessage('Error', 'Ha ocurrido un error desconocido, intente más tarde.')
		}
	}

	function handlerRequest(request) {

		request.done(function (response) {

			let responseStructure = {
				success: {
					optional: true,
					validate: (val) => {
						return typeof val == 'boolean'
					},
					parse: (val) => {
						return val === true
					},
					default: false,
				},
				name: {
					optional: true,
					validate: (val) => {
						return typeof val == 'string' && val.trim().length > 0
					},
					parse: (val) => {
						return val.trim()
					},
					default: 'Acción',
				},
				message: {
					optional: true,
					validate: (val) => {
						return typeof val == 'string' && val.trim().length > 0
					},
					parse: (val) => {
						return val.trim()
					},
					default: '',
				},
				values: {
					optional: true,
					validate: (val) => {
						return typeof val == 'object'
					},
					parse: (val) => {
						return val
					},
					default: {},
				},
			}

			let responseIsObject = typeof response == 'object'

			if (!responseIsObject) {
				console.error(`La respuesta debe ser un objeto`)
				return
			}

			for (let option in responseStructure) {
				let config = responseStructure[option]
				let optional = config.optional
				let validate = config.validate
				let parse = config.parse
				let value = config.default
				let optionExists = typeof response[option]
				if (optionExists) {
					let inputValue = response[option]
					if (validate(inputValue)) {
						value = parse(inputValue)
					}
					response[option] = value
				} else if (optional) {
					response[option] = value
				} else {
					console.error(`Falta la opción ${option} en el cuerpo de la respuesta.`)
					return
				}

			}

			if (response.success) {

				successMessage(response.name, response.message)

				let resposeValues = response.values

				let hasReload = typeof resposeValues.reload != 'undefined' && resposeValues.reload == true
				let hasRedirection = typeof resposeValues.redirect != 'undefined' && resposeValues.redirect == true
				let validRedirection = typeof resposeValues.redirect_to == 'string' && resposeValues.redirect_to.trim().length > 0

				if (hasRedirection && validRedirection) {

					setTimeout(function (e) {

						window.location = resposeValues.redirect_to

					}, 1500)

				} else if (hasReload) {

					setTimeout(function (e) {

						window.location.reload()

					}, 1500)

				} else {

					form.find('button').attr('disabled', false)

				}

			} else {

				errorMessage(response.name, response.message)
				form.find('button').attr('disabled', false)

			}

		})

		request.fail(function (res) {

			form.find('button').attr('disabled', false)
			errorMessage('Error', 'Ha ocurrido un error al conectar con el servidor, intente más tarde.')
			console.error(res)

		})

		request.always(function (res) {
			removeLoader()
		})
	}

	function showLoader() {
		loader = $(
			`
				<div class="ui-pcs-activity-loader">
					<div loader></div>
				</div>
			`
		)
		loader.css({
			"position": `fixed`,
			"z-index": `1000`,
			"top": `0px`,
			"left": `0px`,
			"display": `block`,
			"width": `100%`,
			"height": `100%`,
			"background-color": `rgba(255, 255, 255, 0.4)`,
		})
		loader.find('[loader]').css({
			"position": `fixed`,
			"top": `50%`,
			"left": `50%`,
			"transform": `translate(-50%,-50%)`,
			"display": `block`,
			"width": `300px`,
			"max-width": `100%`,
			"height": `100px`,
		})

		$(document.body).append(loader)

		NProgress.configure({
			parent: `.ui-pcs-activity-loader [loader]`
		})

		NProgress.start()
	}

	function removeLoader() {
		setTimeout(function () {
			NProgress.done()
			if (loader instanceof $) {
				loader.remove()
			}
		}, 500)
	}

}

/**
 * showGenericLoader
 * 
 * Muestra un modal de carga en el body
 * 
 * @returns {void} 
 */
function showGenericLoader() {
	
	window.uiPcsActivityGenericLoader = $(
		`
			<div class="ui-pcs-activity-loader">
				<div loader></div>
			</div>
		`
	)
	window.uiPcsActivityGenericLoader.css({
		"position": `fixed`,
		"z-index": `1000`,
		"top": `0px`,
		"left": `0px`,
		"display": `block`,
		"width": `100%`,
		"height": `100%`,
		"background-color": `rgba(255, 255, 255, 0.4)`,
	})
	window.uiPcsActivityGenericLoader.find('[loader]').css({
		"position": `fixed`,
		"top": `50%`,
		"left": `50%`,
		"transform": `translate(-50%,-50%)`,
		"display": `block`,
		"width": `300px`,
		"max-width": `100%`,
		"height": `100px`,
	})

	$(document.body).append(window.uiPcsActivityGenericLoader)

	NProgress.configure({
		parent: `.ui-pcs-activity-loader [loader]`
	})

	NProgress.start()
}

/**
 * removeGenericLoader
 * 
 * Oculta un modal de carga en el body
 * 
 * @returns {void} 
 */
function removeGenericLoader() {
	setTimeout(function () {
		NProgress.done()
		if (window.uiPcsActivityGenericLoader instanceof $) {
			window.uiPcsActivityGenericLoader.remove()
		}
	}, 500)
}
