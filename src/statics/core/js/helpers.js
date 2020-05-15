/**
 * @method successMessage
 * @description Mensaje modal con tipo success (iziToast|alert)
 * @param {String} title Título del mensaje
 * @param {String} message Mensaje
 * @param {Function} onClose Callback llamado al cerrar
 */
function successMessage(title, message, onClose = null) {
	title = title !== undefined ? title : ''
	message = message !== undefined ? message : ''

	if (typeof iziToast !== 'undefined') {
		iziToast.success({
			title: title,
			message: message,
			position: 'topCenter',
			onClosed: () => {
				if (typeof onClose == 'function') {
					onClose()
				}
			},
		})
	} else {
		window.alert(`${title}:\r\n${message}`)
		if (typeof onClose == 'function') {
			onClose()
		}
	}

}

/**
 * @method warningMessage
 * @description Mensaje modal con tipo warning (iziToast|alert)
 * @param {String} title Título del mensaje
 * @param {String} message Mensaje
 * @param {Function} onClose Callback llamado al cerrar
 */
function warningMessage(title, message, onClose = null) {
	title = title !== undefined ? title : ''
	message = message !== undefined ? message : ''

	if (typeof iziToast !== 'undefined') {
		iziToast.warning({
			title: title,
			message: message,
			position: 'topCenter',
			onClosed: () => {
				if (typeof onClose == 'function') {
					onClose()
				}
			},
		})
	} else {
		window.alert(`${title}:\r\n${message}`)
		if (typeof onClose == 'function') {
			onClose()
		}
	}
}

/**
 * @method infoMessage
 * @description Mensaje modal con tipo info (iziToast|alert)
 * @param {String} title Título del mensaje
 * @param {String} message Mensaje
 * @param {Function} onClose Callback llamado al cerrar
 */
function infoMessage(title, message, onClose = null) {
	title = title !== undefined ? title : ''
	message = message !== undefined ? message : ''

	if (typeof iziToast !== 'undefined') {
		iziToast.info({
			title: title,
			message: message,
			position: 'topCenter',
			onClosed: () => {
				if (typeof onClose == 'function') {
					onClose()
				}
			},
		})
	} else {
		window.alert(`${title}:\r\n${message}`)
		if (typeof onClose == 'function') {
			onClose()
		}
	}
}

/**
 * @method errorMessage
 * @description Mensaje modal con tipo error (iziToast|alert)
 * @param {String} title Título del mensaje
 * @param {String} message Mensaje
 * @param {Function} onClose Callback llamado al cerrar
 */
function errorMessage(title, message, onClose = null) {
	title = title !== undefined ? title : ''
	message = message !== undefined ? message : ''

	if (typeof iziToast !== 'undefined') {
		iziToast.error({
			title: title,
			message: message,
			position: 'topCenter',
			onClosed: () => {
				if (typeof onClose == 'function') {
					onClose()
				}
			},
		})
	} else {
		window.alert(`${title}:\r\n${message}`)
		if (typeof onClose == 'function') {
			onClose()
		}
	}
}

/**
 * @method setCountdown
 * @description Crea una cuenta regresiva y lanza un evento
 * 'util-countdown' según el tiempo establecido
 * @param {String} dateLimit Fecha límite con los formatos:
 * AAAA-MM-DD HH:MM:SS == 2000-01-01 00:00:00
 * AAAA-MM-DD HH:MM == 2000-01-01 00:00
 * AAAA-MM-DD == 2000-01-01
 * @param {Number} time Tiempo de refresco en milisegundos
 * @return {void}
 */
function setCountdown(dateLimit, time = 1000) {

	var fechaLimite = new Date(dateLimit)//Fecha límite

	function lanzar(fechaLimite, interval) {

		var event = new Event('util-countdown')

		// Fecha actual
		var ahora = new Date()

		// Diferencia entre la fecha límite y la actual
		var tiempoFaltante = fechaLimite.getTime() - ahora.getTime()

		// Cálculo de dias, horas, minutos y segundos faltantes
		var dias = Math.floor(tiempoFaltante / (1000 * 60 * 60 * 24))
		var horas = Math.floor((tiempoFaltante % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))
		var minutos = Math.floor((tiempoFaltante % (1000 * 60 * 60)) / (1000 * 60))
		var segundos = Math.floor((tiempoFaltante % (1000 * 60)) / 1000)

		var finalizado = tiempoFaltante <= 0 || (dias + horas + minutos + segundos) == 0

		// Datos del evento            
		event.tiempoFaltante = {
			dias: dias,
			horas: horas,
			minutos: minutos,
			segundos: segundos,
			string: dias + "d " + horas + "h " + minutos + "m " + segundos + "s",
			finalizado: finalizado
		}

		//Lanzar evento
		dispatchEvent(event)

		// Terminar
		if (finalizado) {
			clearInterval(interval)
		}
	}

	//Intervalo de un segundo
	var interval = setInterval(function () {

		lanzar(fechaLimite, interval)

	}, time)

	lanzar(fechaLimite, interval)

}

/**
 * postRequest
 * 
 * Realiza una petición AJAX POST (JQuery.ajax) y devuelve el objeto jqXHR
 * que es un objeto Deferred, por lo que tiene los métodos:
 * done(data, textStatus, jqXHR),
 * fail(jqXHR, textStatus, errorThrown) y
 * always(data|jqXHR, textStatus, jqXHR|errorThrown)
 * 
 * @param {string} url URL que se consultará
 * @param {FormData|Object} [data] Información enviada
 * @param {Object} [headers] Cabeceras
 * @returns {jqXHR}
 */
function postRequest(url, data, headers = {}) {

	let options = {
		url: url,
		method: 'POST',
	}

	if (data instanceof FormData) {

		options.processData = false
		options.enctype = "multipart/form-data"
		options.contentType = false
		options.cache = false
		options.data = data

	} else if (typeof data == 'object') {

		options.data = data

	}

	let parsedHeaders = parseHeaders(headers)

	if (parsedHeaders.size > 0) {

		options.beforeSend = function (request) {

			for (let key of parsedHeaders.keys()) {
				let value = parsedHeaders.get(key)
				request.setRequestHeader(key, value)
			}

		}

	}

	function parseHeaders(headers = {}) {

		let mapHeaders = new Map()

		if (typeof headers == 'object') {

			for (let name in headers) {

				let value = headers[name]
				let valueString = ''

				if (Array.isArray(value)) {

					let length = value.length
					let lastIndexValue = 0

					if (length == 1) {
						lastIndexValue = 0
					} else if (length > 1) {
						lastIndexValue = length - 1
					}

					for (let i = 0; i < length; i++) {
						if (i == lastIndexValue) {
							valueString += value[i]
						} else {
							valueString += value[i] + "\r\n"
						}
					}

				} else if (typeof value == 'string') {
					valueString = value
				}

				mapHeaders.set(name, valueString)

			}

		}

		mapHeaders.set('X-Requested-With', 'XMLHttpRequest')

		return mapHeaders

	}

	return this.$.ajax(options)

}

/**
 * getRequest
 * 
 * Realiza una petición AJAX GET (JQuery.ajax) y devuelve el objeto jqXHR
 * que es un objeto Deferred, por lo que tiene los métodos:
 * done(data, textStatus, jqXHR),
 * fail(jqXHR, textStatus, errorThrown) y
 * always(data|jqXHR, textStatus, jqXHR|errorThrown)
 * 
 * @param {String} url URL que se consultará
 * @param {String|HTMLElement|JQuery} [data] Formulario
 * @param {Object} [headers] Cabeceras
 * @returns {jqXHR}
 */
function getRequest(url, data, headers = {}) {

	let options = {
		url: url,
		method: 'GET',
		enctype: "application/x-www-form-urlencoded",
	}

	if (data instanceof HTMLFormElement) {

		options.data = $(data).serialize()

	} else if (data instanceof $) {

		options.data = data.serialize()

	} else if (typeof data == 'string') {

		options.data = data

	}

	let parsedHeaders = parseHeaders(headers)

	if (parsedHeaders.size > 0) {

		options.beforeSend = function (request) {

			for (let key of parsedHeaders.keys()) {
				let value = parsedHeaders.get(key)
				request.setRequestHeader(key, value)
			}

		}

	}

	function parseHeaders(headers = {}) {

		let mapHeaders = new Map()

		if (typeof headers == 'object') {

			for (let name in headers) {

				let value = headers[name]
				let valueString = ''

				if (Array.isArray(value)) {

					let length = value.length
					let lastIndexValue = 0

					if (length == 1) {
						lastIndexValue = 0
					} else if (length > 1) {
						lastIndexValue = length - 1
					}

					for (let i = 0; i < length; i++) {
						if (i == lastIndexValue) {
							valueString += value[i]
						} else {
							valueString += value[i] + "\r\n"
						}
					}

				} else if (typeof value == 'string') {
					valueString = value
				}

				mapHeaders.set(name, valueString)

			}

		}

		mapHeaders.set('X-Requested-With', 'XMLHttpRequest')

		return mapHeaders

	}

	return this.$.ajax(options)

}

/**
 * formatNumberString
 * 
 * @param {string} input 
 * @param {string} thousandsSeparator 
 * @param {string} decimalsSeparator 
 * @param {boolean} inverse 
 * @returns {number|string}
 */
function formatNumberString(input, thousandsSeparator = '.', decimalsSeparator = ',', inverse = false) {

	if (typeof input == 'number') {
		console.log(input)
		input = input.toString().replace('.', ',')
		console.log(input)
	}

	if (!inverse) {
		input = input
			.replace('.', '')
			.replace(/\s{1,}/gm, '')

		if (new RegExp('\,{2,}').test(input)) {
			input = input.replace(/\,{2,}/gmi, ',')
		}

		let commas = input.match(/\,/gmi)

		if (Array.isArray(commas) && commas.length > 0) {
			for (let i = 0; i < commas.length - 1; i++) {
				let positionLastComma = input.lastIndexOf(',')
				input = input.split('')
				input[positionLastComma] = ''
				input = input.join('')
			}
		}

		input = input
			.replace(/[^0-9|\,|\s]/gmi, '')

		input = input.split(',')

		let number = String(input[0]).replace(/(.)(?=(\d{3})+$)/g, '$1' + thousandsSeparator)
		let decimals = input.length > 1 ? `${decimalsSeparator}${input[1]}` : ''

		return `${number}${decimals}`
	} else {

		if (thousandsSeparator == '.') {
			input = input
				.replace(new RegExp(`\\.{1,}`, 'gm'), '')
		} else {
			input = input
				.replace(new RegExp(`${thousandsSeparator}{1,}`, 'gm'), '')
		}
		if (decimalsSeparator == '.') {
			input = input
				.replace(new RegExp(`\\.{1,}`, 'gm'), '.')
		} else if (decimalsSeparator == ',') {
			input = input
				.replace(new RegExp(`\,{1,}`, 'gm'), '.')
		} else {
			input = input
				.replace(new RegExp(`${decimalsSeparator}{1,}`, 'gm'), '')
		}

		return parseFloat(input)
	}
}

/**
 * @function strReplace
 * @param {string[]|string} search Elementos a buscar
 * @param {strin[]|string} replace Elementos de reemplazo
 * @param {string} subject Cadena de entrada
 * @returns {string}
 */
function strReplace(search, replace, subject) {

	if (typeof search == 'string') {
		search = [search]
	} else if (!Array.isArray(search)) {
		return null
	}

	if (typeof replace != 'string' && !Array.isArray(replace)) {
		return null
	}

	if (typeof subject != 'string') {
		return null
	}

	let searchLength = search.length

	for (let i = 0; i < searchLength; i++) {

		let searchString = search[i]
		let replaceString = ''

		if (Array.isArray(replace)) {
			if (typeof replace[i] == 'string') {
				replaceString = replace[i]
			}
		} else {
			replaceString = replace
		}

		let replacedString = subject

		while (replacedString.indexOf(searchString) !== -1) {
			replacedString = replacedString.replace(searchString, replaceString)
		}

		subject = replacedString

	}

	return subject
}

/**
 * @function friendlyURL
 * @param {string} str Cadena para formatear
 * @param {number} maxWords Cantidad máxima de palabras
 * @returns {string} Cadena formateada
 */
function friendlyURL(str, maxWords) {

	if (typeof str != 'string') {
		return null
	}

	str = str.trim()

	let dictionary = [
		'á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä', 'Ã',
		'é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë',
		'í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î',
		'ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô',
		'ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü',
		'ñ', 'Ñ', 'ç', 'Ç',
		'  ', ' ',
	]

	let replace_dictionary = [
		'a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A', 'A',
		'e', 'e', 'e', 'e', 'E', 'E', 'E', 'E',
		'i', 'i', 'i', 'i', 'I', 'I', 'I', 'I',
		'o', 'o', 'o', 'o', 'O', 'O', 'O', 'O',
		'u', 'u', 'u', 'u', 'U', 'U', 'U', 'U',
		'nn', 'NN', 'c', 'C',
		' ', '-',
	]

	let other_characters = [
		"\\", "¨", "º", "~", '±',
		"#", "@", "|", "!", "\"",
		"·", "$", "%", "&", "/",
		"(", ")", "?", "'", "¡",
		"¿", "[", "^", "`", "]",
		"+", "}", "{", "¨", "´",
		">", "<", ";", ",", ":",
		".", 'º',
	]

	str = str.replace(/(\t|\r\n|\r|\n){1,}/gmi, '')
	str = str.replace(/(\u00a0){1,}/gmi, ' ')
	str = strReplace(dictionary, replace_dictionary, str)
	str = strReplace(other_characters, '', str)
	str = str.replace(/-{2,}/gmi, '')
	str = str.toLowerCase()

	if (typeof maxWords == 'number') {

		maxWords = parseInt(maxWords)

		let words = str.split('-')

		let wordsLimitied = []
		let countWords = words.length

		for (let $i = 0; $i < maxWords && $i < countWords; $i++) {
			let word = words[$i]
			wordsLimitied.push(word)
		}

		str = wordsLimitied.join('-')

	}

	return str
}

/**
 * dataTableServerProccesing
 * @description Requiere datatables y jquery
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

		let configDataTable = Object.assign({}, pcsphpGlobals.configDataTables)

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
 * dataTablesServerProccesingOnCards
 * @description Requiere datatables y jquery
 * @param {String} containerSelector 
 * @param {Number} perPage 
 * @param {Object} options 
 * @returns {Object}
 */
function dataTablesServerProccesingOnCards(containerSelector, perPage, options) {

	containerSelector = typeof containerSelector == 'string' ? containerSelector : null
	perPage = typeof perPage == 'number' ? perPage : 10
	options = typeof options == 'object' ? options : {}

	let container = containerSelector !== null ? $(containerSelector) : null

	if (container !== null && container.length > 0) {

		let nameLoader = 'PROCCESSING_dataTablesServerProccesingOnCards'
		let table = container.find('table')
		let cardsContainer, cards

		showGenericLoader(nameLoader)

		table.hide()

		let processURL = table.attr('url')

		let initComplete = typeof options.initComplete == 'function' ? options.initComplete : () => { }
		let preDrawCallback = typeof options.preDrawCallback == 'function' ? options.preDrawCallback : () => { }
		let drawCallback = typeof options.drawCallback == 'function' ? options.drawCallback : () => { }

		let optionsDataTables = {
			dom: `<"component-wrapper"t<"component-pagination"p>>`,
			initComplete: function (settings, json) {

				initComplete(settings, json)

				let thisDataTable = table.DataTable()

				let columns = []

				for (let i in settings.aoColumns) {

					let columnSettings = settings.aoColumns[i]
					columns.push({
						index: i,
						name: columnSettings.name,
						visible: columnSettings.bVisible,
						orderable: columnSettings.orderable,
						searchable: columnSettings.searchable,
						htmlElement: columnSettings.nTh,
					})

				}

				//Creación del contenedor de fichas y otras manipulaciones de html
				let wrapper = container.find('.component-wrapper')

				wrapper.prepend(`<br><div class="ui cards"></div><br><br>`)

				//──── Controles ─────────────────────────────────────────────────────────────────────────
				let controls = container.find('.component-controls')
				let selectionOrder = controls.find('select[options-order]')
				let selectionOrderType = controls.find('select[options-order-type]')
				let search = controls.find(`[type="search"]`)
				let lengthPagination = controls.find(`[type="number"][length-pagination]`)

				//Ordenamiento				
				columns.map((e, i) => {

					if (e.orderable) {
						selectionOrder.append(`<option value="${i}">${e.name}</option>`)
					}

				})

				selectionOrder.dropdown()
				selectionOrderType.dropdown()

				let orderEvent = function () {

					let orderColumn = columns[selectionOrder.val()]
					let orderType = selectionOrderType.val()

					orderType = typeof orderType == 'string' && orderType.trim().length > 0 ? orderType.trim() : 'asc'
					orderType = orderType.toLowerCase()
					orderType = orderType == 'asc' || orderType == 'desc' ? orderType : 'asc'

					thisDataTable.column(orderColumn.index).order(orderType).draw()

				}

				selectionOrder.change(orderEvent)
				selectionOrderType.change(orderEvent)

				//Buscador
				search.on('keyup', function () {
					thisDataTable.search(search.val()).draw()
				})

				//Cantidad de elementos por página
				lengthPagination.attr('min', 1)
				lengthPagination.attr('step', 1)
				lengthPagination.val(perPage)
				lengthPagination.on('change', function () {
					let length = lengthPagination.val()
					length = parseInt(length)
					length = !isNaN(length) ? length : perPage
					length = length > 0 ? length : perPage
					thisDataTable.page.len(length).draw()
				})

				thisDataTable.draw()

				removeGenericLoader(nameLoader)

			},
			preDrawCallback: function (settings) {

				preDrawCallback(settings)

				cardsContainer = container.find('.ui.cards')
				cards = cardsContainer.find('.card')

				cardsContainer.html('')

			},
			drawCallback: function (settings) {

				drawCallback(settings)

				this.find('tbody').remove()

				let json = settings.json
				let rawData = json.rawData

				for (let data of rawData) {
					cardsContainer.append(data)
				}

				cards = cardsContainer.find('.card')

				if (cards.length == 0) {
					cardsContainer.html(`<h3>${pcsphpGlobals.messages[pcsphpGlobals.lang].datatables.lang.emptyTable}</h3>`)
				}

			},
		}

		options.dom = optionsDataTables.dom
		options.initComplete = optionsDataTables.initComplete
		options.preDrawCallback = optionsDataTables.preDrawCallback
		options.drawCallback = optionsDataTables.drawCallback

		table = dataTableServerProccesing(table, processURL, perPage, options)

		table.on('processing.dt', function (e, settings, proccesing) {
			if (proccesing) {

				if (!activeGenericLoader(nameLoader)) {
					showGenericLoader(nameLoader)
				}

			} else {
				removeGenericLoader(nameLoader)
			}
		})

		return table

	}

	return null

}

/**
 * genericFormHandler
 * 
 * Manejador genérico de formularios, requiere jquery
 * 
 * @param {String|$} selectorForm 
 * @param {genericFormHandler.Options} options
 * @returns {$} 
 */
function genericFormHandler(selectorForm = 'form[pcs-generic-handler-js]', options = {}) {

	/**
	 * @typedef genericFormHandler.Options
	 * @property {genericFormHandler.Options.ConfirmationOption} [confirmation]
	 * @property {Function} [onSetFormData]
	 * @property {Function} [onSetForm]
	 * @property {Function} [validate]
	 * @property {Function} [onSuccess]
	 * @property {Function} [onError]
	 * @property {Function} [onInvalidEvent]
	 * @property {Boolean} [toast]
	 */
	/**
	 * @typedef genericFormHandler.Options.ConfirmationOption
	 * @property {String} selector Selector del elemento
	 * @property {String} [title] Título
	 * @property {String} [message]	Mensaje de advertencia
	 * @property {String} [positive] Texto afirmativo
	 * @property {String} [negative] Texto negativo
	 * @property {Function} [condition]
	 */
	let ignore;

	if (!(selectorForm instanceof $)) {
		selectorForm = typeof selectorForm == 'string' && selectorForm.trim().length > 0 ? selectorForm.trim() : `form[pcs-generic-handler-js]`
	}

	let form = selectorForm instanceof $ ? selectorForm : $(`${selectorForm}`)

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
	let onSuccess = function () {
	}
	let onError = function () {
	}
	let onInvalidEvent = function (event) {
	}
	let toast = true

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
		if (typeof options.onSuccess == 'function') {
			onSuccess = options.onSuccess
		}
		if (typeof options.onError == 'function') {
			onError = options.onError
		}
		if (typeof options.onInvalidEvent == 'function') {
			onInvalidEvent = options.onInvalidEvent
		}
		if (typeof options.toast == 'boolean') {
			toast = options.toast
		}
	}

	if (form.length > 0) {

		form.off('invalid')
		form.find('input,textarea,select').on('invalid', onInvalidEvent)

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

				let processFormData = onSetFormData(formData, form)

				if (typeof processFormData.then !== 'undefined') {
					processFormData.then(function (formData) {
						request = postRequest(action, formData)
						handlerRequest(request, form, formData)
					})
				} else {
					request = postRequest(action, processFormData)
					handlerRequest(request, form, formData)
				}

			} else {
				let processForm = onSetForm(form)
				if (typeof processForm.then !== 'undefined') {
					processForm.then(function (form) {
						request = getRequest(action, form)
						handlerRequest(request, form, formData)
					})
				} else {
					request = getRequest(action, processForm)
					handlerRequest(request, form, formData)
				}
			}

		} else {

			console.error('No se ha definido ninguna acción')

			if (toast) {
				errorMessage('Error', 'Ha ocurrido un error desconocido, intente más tarde.')
			}

		}
	}

	function handlerRequest(request, formProcess, formData) {

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

				if (toast) {
					successMessage(response.name, response.message)
				}

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

				onSuccess(formProcess, formData, response)

			} else {

				if (toast) {
					errorMessage(response.name, response.message)
				}

				form.find('button').attr('disabled', false)

				onError(formProcess, formData, response)

			}

		})

		request.fail(function (error) {

			form.find('button').attr('disabled', false)

			if (toast) {
				errorMessage('Error', 'Ha ocurrido un error al conectar con el servidor, intente más tarde.')
			}

			onError(formProcess, formData, error)

			console.error(error)

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

	return form

}

/**
 * showGenericLoader
 * 
 * Muestra un modal de carga en el body
 * 
 * @returns {void} 
 */
function showGenericLoader(name = 'DEFAULT') {

	if (typeof window.uiPcsActivityGenericLoader != 'object') {
		window.uiPcsActivityGenericLoader = {}
	}

	window.uiPcsActivityGenericLoader[name] = {
		html: $(
			`
				<div class="ui-pcs-activity-loader">
					<div loader>
						<div class="ui loader active"></div>
					</div>
				</div>
			`
		),
		active: true,
	}

	window.uiPcsActivityGenericLoader[name].html.css({
		"position": `fixed`,
		"z-index": `1000`,
		"top": `0px`,
		"left": `0px`,
		"display": `block`,
		"width": `100%`,
		"height": `100%`,
		"background-color": `rgba(255, 255, 255, 0.4)`,
	})
	window.uiPcsActivityGenericLoader[name].html.find('[loader]').css({
		"position": `fixed`,
		"top": `50%`,
		"left": `50%`,
		"transform": `translate(-50%,-50%)`,
		"display": `block`,
		"width": `300px`,
		"max-width": `100%`,
		"height": `100px`,
	})

	window.uiPcsActivityGenericLoader[name].html.attr('data-name', name)

	$(document.body).append(window.uiPcsActivityGenericLoader[name].html)

	window.uiPcsActivityGenericLoader[name].html = $(document.body).find(`.ui-pcs-activity-loader[data-name="${name}"]`)

}

/**
 * removeGenericLoader
 * 
 * Oculta un modal de carga en el body
 * 
 * @returns {void} 
 */
function removeGenericLoader(name = 'DEFAULT') {

	if (typeof window.uiPcsActivityGenericLoader == 'object') {
		if (typeof window.uiPcsActivityGenericLoader[name] == 'object') {
			let timeout = function () {
				if (window.uiPcsActivityGenericLoader[name].html instanceof $) {
					window.uiPcsActivityGenericLoader[name].html.remove()
					window.uiPcsActivityGenericLoader[name].active = false
				}
			}
			setTimeout(timeout, 500)
		}
	}

}

/**
 * activeGenericLoader
 * 
 * Verifica si está activo el loader
 * 
 * @returns {void} 
 */
function activeGenericLoader(name = 'DEFAULT') {

	let active = false

	if (typeof window.uiPcsActivityGenericLoader == 'object') {

		if (typeof window.uiPcsActivityGenericLoader[name] == 'object') {
			active = (window.uiPcsActivityGenericLoader[name].active == true)
		}

	}

	return active

}

/**
 * @function formatDate
 *
 * Formatea una fecha.
 *
 * @param {Date} date Fecha
 * @param {string} format Formato de la fecha
 * - d	Día del mes, 2 dígitos con ceros iniciales	01 a 31
 * - Y	Una representación numérica completa de un año, 4 dígitos	Ejemplos: 1999 o 2003
 * - m	Representación numérica de un mes, con ceros iniciales	01 hasta 12
 * - g	Formato de 12 horas de una hora sin ceros iniciales	1 hasta 12
 * - G	Formato de 24 horas de una hora sin ceros iniciales	0 hasta 23
 * - h	Formato de 12 horas de una hora con ceros iniciales	01 hasta 12
 * - H	Formato de 24 horas de una hora con ceros iniciales	00 hasta 23
 * - A	Ante meridiem y Post meridiem en mayúsculas	AM o PM
 * - i	Minutos con ceros iniciales	00 hasta 59
 * - s	Segundos con ceros iniciales	00 hasta 59
 * @returns {string}
 */
function formatDate(date, format) {

	format = typeof format == 'string' && format.length > 0 ? format : 'd-m-Y'
	if (!(date instanceof Date)) {
		date = new Date()
		console.warn('Fecha actual asignada en formatDate')
	}

	let d = date.getDate()
	d = d < 10 ? `0${d}` : d

	let m = date.getMonth() + 1
	m = m < 10 ? `0${m}` : m

	let Y = date.getFullYear().toString()

	let hours = {
		0: 12,
		1: 1,
		2: 2,
		3: 3,
		4: 4,
		5: 5,
		6: 6,
		7: 7,
		8: 8,
		9: 9,
		10: 10,
		11: 11,
		12: 12,
		13: 1,
		14: 2,
		15: 3,
		16: 4,
		17: 5,
		18: 6,
		19: 7,
		20: 8,
		21: 9,
		22: 10,
		23: 11,
	}
	let hour = date.getHours()
	let g = hours[hour]
	let G = hour
	let h = g < 10 ? `0${g}` : g
	let H = G < 10 ? `0${G}` : G
	let A = hour > 12 ? 'PM' : 'AM'

	let i = parseInt(date.getMinutes())
	i = i < 10 ? `0${i}` : i
	let s = parseInt(date.getSeconds())
	s = s < 10 ? `0${s}` : s

	let replacesPattern = {
		'Y': Y,
		'm': m,
		'd': d,
		'A': A,
		'g': g,
		'G': G,
		'h': h,
		'H': H,
		'i': i,
		's': s,
	}

	for (let pattern in replacesPattern) {
		let value = replacesPattern[pattern]
		format = format.replace(pattern, value)
	}

	return format
}

/**
 * 
 * @param {String} str Remplaza %r por los valores pasados
 * @param {Array<String>} values 
 */
function formatStr(str, values) {

	if (Array.isArray(values)) {

		if (typeof str == 'string') {

			for (let value of values) {

				let indexReplaceElement = str.indexOf('%r')

				if (indexReplaceElement != -1) {

					str = str.replace('%r', value)

				} else {

					break

				}

			}

		}

	}

	return typeof str == 'string' ? str : ''

}

/**
 * 
 * @param {String} prefix
 * @param {Boolean} moreEntropy
 * @param {String}
 */
function generateUniqueID(prefix, moreEntropy) {

	if (typeof prefix === 'undefined') {
		prefix = ''
	}

	var retId
	var _formatSeed = function (seed, reqWidth) {
		seed = parseInt(seed, 10).toString(16) // to hex str
		if (reqWidth < seed.length) {
			// so long we split
			return seed.slice(seed.length - reqWidth)
		}
		if (reqWidth > seed.length) {
			// so short we pad
			return Array(1 + (reqWidth - seed.length)).join('0') + seed
		}
		return seed
	}

	var $global = (typeof window !== 'undefined' ? window : global)
	$global.$locutus = $global.$locutus || {}
	var $locutus = $global.$locutus
	$locutus.php = $locutus.php || {}

	if (!$locutus.php.uniqidSeed) {
		// init seed with big random int
		$locutus.php.uniqidSeed = Math.floor(Math.random() * 0x75bcd15)
	}
	$locutus.php.uniqidSeed++

	// start with prefix, add current milliseconds hex string
	retId = prefix
	retId += _formatSeed(parseInt(new Date().getTime() / 1000, 10), 8)
	// add seed hex string
	retId += _formatSeed($locutus.php.uniqidSeed, 5)
	if (moreEntropy) {
		// for more entropy we add a float lower to 10
		retId += (Math.random() * 10).toFixed(8).toString()
	}

	return retId
}

/**
 * @function addObjectToFormData
 * 
 * @param {FormData} formData
 * @param {Object} inputValue
 * @param {String} name
 * @param {Bool} isFirstArray
 * @param {FormData} 
 */
function addObjectToFormData(formData, inputValue, name, isFirstArray = true) {

	if (typeof inputValue == 'object') {

		for (let property in inputValue) {

			let value = inputValue[property]
			let subName = `${name}[${property}]`

			if (typeof value == 'string' || typeof value == 'number' || value == null) {

				formData.append(subName, value)

			} else if (Array.isArray(value)) {

				for (let i in value) {
					if (isFirstArray) {
						formData = addObjectToFormData(formData, value, `${subName}`, false)
					} else {
						formData = addObjectToFormData(formData, value[i], `${subName}[${i}]`, false)
					}
				}

			} else if (typeof value == 'object') {

				formData = addObjectToFormData(formData, value, subName)

			}

		}

	} else {
		formData.append(name, inputValue)
	}

	return formData

}
