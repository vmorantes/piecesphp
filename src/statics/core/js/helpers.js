/**
 * @description Mensaje modal con tipo success (toast fomantic|alert)
 * @param {String} title Título del mensaje
 * @param {String} message Mensaje
 * @param {Function} onClose Callback llamado al cerrar
 * @param {Object} options Opciones
 * @see https://fomantic-ui.com/modules/toast.html#/settings
 */
function successMessage(title, message, onClose = null, options) {

	title = typeof title == 'string' ? title : ''
	message = typeof message == 'string' ? message : ''
	options = typeof options === 'object' ? options : {}

	const typeMessage = 'success'
	const defaultOptions = {
		class: typeMessage,
		title: title,
		message: message,
		onHidden: () => {
			if (typeof onClose == 'function') {
				onClose()
			}
		},
	}

	if (typeof $ !== 'undefined' && typeof $('body').toast !== 'undefined') {

		for (const property in defaultOptions) {
			const defaultValue = defaultOptions[property]
			options[property] = defaultValue
		}

		if (typeof options.position !== 'string') {
			options.position = 'top center'
		}

		$('body').toast(options)

	} else {
		window.alert(`${title}:\r\n${message}`)
		if (typeof onClose == 'function') {
			onClose()
		}
	}

}

/**
 * @description Mensaje modal con tipo warning (toast fomantic|alert)
 * @param {String} title Título del mensaje
 * @param {String} message Mensaje
 * @param {Function} onClose Callback llamado al cerrar
 * @param {Object} options Opciones
 * @see https://fomantic-ui.com/modules/toast.html#/settings
 */
function warningMessage(title, message, onClose = null, options) {

	title = typeof title == 'string' ? title : ''
	message = typeof message == 'string' ? message : ''
	options = typeof options === 'object' ? options : {}

	const typeMessage = 'warning'
	const defaultOptions = {
		class: typeMessage,
		title: title,
		message: message,
		onHidden: () => {
			if (typeof onClose == 'function') {
				onClose()
			}
		},
	}

	if (typeof $ !== 'undefined' && typeof $('body').toast !== 'undefined') {

		for (const property in defaultOptions) {
			const defaultValue = defaultOptions[property]
			options[property] = defaultValue
		}

		if (typeof options.position !== 'string') {
			options.position = 'top center'
		}

		$('body').toast(options)

	} else {
		window.alert(`${title}:\r\n${message}`)
		if (typeof onClose == 'function') {
			onClose()
		}
	}

}

/**
 * @description Mensaje modal con tipo info (toast fomantic|alert)
 * @param {String} title Título del mensaje
 * @param {String} message Mensaje
 * @param {Function} onClose Callback llamado al cerrar
 * @param {Object} options Opciones
 * @see https://fomantic-ui.com/modules/toast.html#/settings
 */
function infoMessage(title, message, onClose = null, options) {

	title = typeof title == 'string' ? title : ''
	message = typeof message == 'string' ? message : ''
	options = typeof options === 'object' ? options : {}

	const typeMessage = 'info'
	const defaultOptions = {
		class: typeMessage,
		title: title,
		message: message,
		onHidden: () => {
			if (typeof onClose == 'function') {
				onClose()
			}
		},
	}

	if (typeof $ !== 'undefined' && typeof $('body').toast !== 'undefined') {

		for (const property in defaultOptions) {
			const defaultValue = defaultOptions[property]
			options[property] = defaultValue
		}

		if (typeof options.position !== 'string') {
			options.position = 'top center'
		}

		$('body').toast(options)

	} else {
		window.alert(`${title}:\r\n${message}`)
		if (typeof onClose == 'function') {
			onClose()
		}
	}

}

/**
 * @description Mensaje modal con tipo error (toast fomantic|alert)
 * @param {String} title Título del mensaje
 * @param {String} message Mensaje
 * @param {Function} onClose Callback llamado al cerrar
 * @param {Object} options Opciones
 * @see https://fomantic-ui.com/modules/toast.html#/settings
 */
function errorMessage(title, message, onClose = null, options) {

	title = typeof title == 'string' ? title : ''
	message = typeof message == 'string' ? message : ''
	options = typeof options === 'object' ? options : {}

	const typeMessage = 'error'
	const defaultOptions = {
		class: typeMessage,
		title: title,
		message: message,
		onHidden: () => {
			if (typeof onClose == 'function') {
				onClose()
			}
		},
	}

	if (typeof $ !== 'undefined' && typeof $('body').toast !== 'undefined') {

		for (const property in defaultOptions) {
			const defaultValue = defaultOptions[property]
			options[property] = defaultValue
		}

		if (typeof options.position !== 'string') {
			options.position = 'top center'
		}

		$('body').toast(options)

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
 * @param {Object} [options] Opciones de $.ajax
 * @returns {jqXHR}
 */
function postRequest(url, data, headers = {}, options = {}) {

	options.url = url
	options.method = 'POST'

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
 * @param {Object} [options] Opciones de $.ajax
 * @returns {jqXHR}
 */
function getRequest(url, data, headers = {}, options = {}) {

	options.url = url
	options.method = 'GET'
	options.enctype = 'application/x-www-form-urlencoded'

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
		input = input.toString().replace('.', ',')
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
		let columnWidth = e.getAttribute('column-width')
		let className = e.getAttribute('class-name')
		let withContainer = e.getAttribute('with-container')

		if (searchable != null) {
			columnDefinition.searchable = searchable == 'true'
		}
		if (orderable != null) {
			columnDefinition.orderable = orderable == 'true'
		}
		if (name != null) {
			columnDefinition.name = name
		}
		if (columnWidth != null) {
			columnDefinition.width = columnWidth
		}
		if (className != null) {
			columnDefinition.className = className
		}

		columnDefinition.render = function (data, type, row, meta) {
			if (withContainer != null && withContainer == 'true') {
				return `<div class="cell-container">${data}</div>`
			} else {
				return data
			}
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

		if (typeof options.ajax !== 'undefined') {

			configDataTable.ajax = options.ajax

			if (typeof options.ajax.url == 'undefined') {
				configDataTable.ajax.url = ajaxURL
			}

		} else {
			configDataTable.ajax = ajaxURL
		}

		configDataTable.processing = true
		configDataTable.serverSide = true
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
 * @param {Object} customClassesCards
 * @param {String} customClassesCards.containerCardsClass
 * @param {String} customClassesCards.containerCardsSelector
 * @param {String} customClassesCards.cardsSelector
 * @param {Function} options.initComplete
 * @param {function($):void} options.initCompleteEnd
 * @param {Function} options.preDrawCallback
 * @param {Function} options.drawCallback
 * @param {function($):void} options.drawCallbackEnd
 * @returns {Object}
 */
function dataTablesServerProccesingOnCards(containerSelector, perPage, options, customClassesCards) {

	containerSelector = typeof containerSelector == 'string' ? containerSelector : null
	perPage = typeof perPage == 'number' ? perPage : 10
	options = typeof options == 'object' ? options : {}
	customClassesCards = typeof customClassesCards == 'object' ? customClassesCards : {}
	if (typeof customClassesCards.containerCardsClass != 'string') {
		customClassesCards.containerCardsClass = 'ui cards'
	}
	if (typeof customClassesCards.containerCardsSelector != 'string') {
		customClassesCards.containerCardsSelector = '.ui.cards'
	}
	if (typeof customClassesCards.cardsSelector != 'string') {
		customClassesCards.cardsSelector = '.card'
	}

	let container = containerSelector !== null ? $(containerSelector) : null

	if (container !== null && container.length > 0) {

		let nameLoader = 'PROCCESSING_dataTablesServerProccesingOnCards'
		let table = container.find('table')
		let cardsContainer, cards

		showGenericLoader(nameLoader)

		table.hide()

		let processURL = table.attr('url')

		let initComplete = typeof options.initComplete == 'function' ? options.initComplete : () => { }
		let initCompleteEnd = typeof options.initCompleteEnd == 'function' ? options.initCompleteEnd : () => { }
		let preDrawCallback = typeof options.preDrawCallback == 'function' ? options.preDrawCallback : () => { }
		let drawCallback = typeof options.drawCallback == 'function' ? options.drawCallback : () => { }
		let drawCallbackEnd = typeof options.drawCallbackEnd == 'function' ? options.drawCallbackEnd : () => { }

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

				wrapper.prepend(`<br><div class="${customClassesCards.containerCardsClass}"></div><br><br>`)

				//──── Controles ─────────────────────────────────────────────────────────────────────────
				let controls = container.find('.component-controls')
				let selectionOrder = controls.find('select[options-order]')
				let selectionOrderType = controls.find('.ui.dropdown[options-order-type]')
				let search = controls.find(`[type="search"]`)
				let lengthPagination = controls.find(`[type="number"][length-pagination]`)

				//Ordenamiento				
				columns.map((e, i) => {

					if (e.orderable) {
						selectionOrder.append(`<option value="${i}">${e.name}</option>`)
					}

				})

				let orderEvent = function () {

					let orderColumn = columns[selectionOrder.val()]
					let orderType = selectionOrderType.dropdown('get value')

					orderType = typeof orderType == 'string' && orderType.trim().length > 0 ? orderType.trim() : 'asc'
					orderType = orderType.toLowerCase()
					orderType = orderType == 'asc' || orderType == 'desc' ? orderType : 'asc'

					thisDataTable.column(orderColumn.index).order(orderType).draw()

				}

				selectionOrder.dropdown({
					onChange: orderEvent,
				})
				selectionOrderType.dropdown({
					onChange: orderEvent,
				})

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

				initCompleteEnd(wrapper)

				removeGenericLoader(nameLoader)

			},
			preDrawCallback: function (settings) {

				preDrawCallback(settings)

				cardsContainer = container.find(customClassesCards.containerCardsSelector)
				cards = cardsContainer.find(customClassesCards.cardsSelector)

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

				cards = cardsContainer.find(customClassesCards.cardsSelector)

				if (cards.length == 0) {
					cardsContainer.html(`<h3>${pcsphpGlobals.messages[pcsphpGlobals.lang].datatables.lang.emptyTable}</h3>`)
				}

				drawCallbackEnd(cards)

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
 * Manejador genérico de formularios, requiere jquery
 * 
 * @param {String|$} selectorForm 
 * @param {genericFormHandler.Options} options
 * @param {Boolean} [overwrite=true] Si es true aplica .off al evento submit
 * @param {Boolean} [defaultInvalidHandler=true] Si es true aplica un manejador de invalidez del formulario predefinido (options.onInvalidEvent lo sobreescribe)
 * @returns {$} 
 */
function genericFormHandler(selectorForm = 'form[pcs-generic-handler-js]', options = {}, overwrite = true, defaultInvalidHandler = true) {

	/**
	 * @typedef genericFormHandler.Options
	 * @property {genericFormHandler.Options.ConfirmationOption} [confirmation]
	 * @property {{Function(formData: FormData, form: $):FormData}} [onSetFormData]
	 * @property {{Function(form: $):$|Promise}} [onSetForm]
	 * @property {{Function(form: $):Boolean}} [validate]
	 * @property {{Function(form: $, formData: FormData, response: Object):Promise|void}} [onSuccess]
	 * @property {{Function(form: $, formData: FormData, response: Object):void}} [onError]
	 * @property {{Function(event: Event):void}} [onInvalidEvent]
	 * @property {Boolean} [toast]
	 * @property {Boolean} [ignoreRedirection]
	 * @property {Boolean} [ignoreReload]
	 */
	/**
	 * @typedef genericFormHandler.Options.ConfirmationOption
	 * @property {String} selector Selector del elemento
	 * @property {String} [title] Título
	 * @property {String} [message]	Mensaje de advertencia
	 * @property {String} [positive] Texto afirmativo
	 * @property {String} [negative] Texto negativo
	 * @property {{Function(buttonConfirmation: $):Boolean}} [condition]
	 */
	let ignore;

	if (!(selectorForm instanceof $)) {
		selectorForm = typeof selectorForm == 'string' && selectorForm.trim().length > 0 ? selectorForm.trim() : `form[pcs-generic-handler-js]`
	}

	overwrite = overwrite === true
	defaultInvalidHandler = defaultInvalidHandler === true

	let form = selectorForm instanceof $ ? selectorForm : $(`${selectorForm}`)

	let hasConfirmation = false
	let buttonConfirmation = null
	let waitForConfirmation = false
	/**
	 * @param {FormData} formData
	 * @param {$} form
	 * @returns {FormData}
	 */
	let onSetFormData = function (formData, form) {
		return formData
	}
	/**
	 * @param {$} form
	 * @returns {$|Promise}
	 */
	let onSetForm = function (form) {
		return form
	}
	/**
	 * @param {$} form
	 * @returns {Boolean}
	 */
	let validate = function (form) {
		return true
	}
	/**
	 * @param {$} form
	 * @param {FormData} formData
	 * @param {Object} response
	 * @returns {Promise|void}
	 */
	let onSuccess = function (form, formData, response) {
	}
	/**
	 * @param {$} form
	 * @param {FormData} formData
	 * @param {Object} response
	 * @returns {void}
	 */
	let onError = function (form, formData, response) {
	}

	let onInvalidEventOnToTopAnimation = false
	/**
	 * @param {Event} event
	 * @returns {void}
	 */
	let onInvalidEvent = function (event) {

		let element = event.target
		let validationMessage = element.validationMessage
		let jElement = $(element)
		let field = jElement.closest('.field')
		let nameOnLabel = field.find('label').html()
		let parentForm = jElement.closest('form')

		//Si es un dropdown con simulador se ignora
		if (typeof jElement.attr('data-simulator') == 'string' && jElement.attr('data-simulator').length > 1) {
			event.preventDefault()
			return
		}

		field.addClass('error')
		field.find('input,select,textarea').map((i, e) => e.blur())
		let removeErrorClass = function () {
			field.removeClass('error')
			form.find(`li[data-name="${dataID}"]`).remove()
			const messageContainer = form.find(`[${errorMessageContainerAttr}]`)
			if (messageContainer.find('.list li').length < 1) {
				messageContainer.remove()
			}
		}

		field.off('focus change', '*', removeErrorClass)
		field.on('focus change', '*', removeErrorClass)

		//Agregar errores
		const dataID = configElementDataID()
		const errorMessageContainerAttr = 'error-form-container'

		configErrorMessageContainer(parentForm)

		event.preventDefault()

		function configElementDataID() {
			let dataID = jElement.data('id')
			jElement.data('id')
			const hasDataID = typeof dataID == 'string' && dataID.trim().length > 0
			dataID = hasDataID ? dataID.trim() : generateUniqueID().trim()
			if (!hasDataID) {
				jElement.attr('data-id', dataID)
			}
			return dataID
		}

		function configErrorMessageContainer(form) {

			let messageContainer = form.find(`[${errorMessageContainerAttr}]`)

			if (messageContainer.length < 1) {
				let html = `<div class="ui error message" ${errorMessageContainerAttr}>`
				html += `<i class="close icon"></i><br>`
				html += `<div class="content"><ul class="list"></ul></div>`
				html += `</div>`
				form.prepend(html)
				messageContainer = form.find(`[${errorMessageContainerAttr}]`)
				messageContainer.find('.close').off('click')
				messageContainer.find('.close').on('click', function () {
					$(this).closest('.message').transition('fade')
				})
			}

			let toTop = () => {
				if (!onInvalidEventOnToTopAnimation) {
					onInvalidEventOnToTopAnimation = true
					$('body,html,.ui-pcs.container-sidebar>.content').animate({
						scrollTop: form.offset().top
					}, {
						easing: 'linear',
						complete: function () {
							onInvalidEventOnToTopAnimation = false
						}
					})
				}
			}

			if (!messageContainer.is(':visible')) {
				messageContainer.transition('fade', {
					onComplete: function () {
						if (!visibleInViewPort(messageContainer)) {
							toTop()
						}
						messageContainer.find('.content').css('min-height', 'min-content')
						messageContainer.find('.content').css('overflow', 'auto')
						messageContainer.find('.content').css('max-height', '220px')
					}
				})
			} else {
				toTop()
			}

			configIndividualError(messageContainer)
			removeOrphanIndividualErrors(messageContainer)

			function configIndividualError(messageContainer) {

				const liErrorSelector = `[data-name="${dataID}"]`
				let liError = messageContainer.find(liErrorSelector)
				liError.remove()

				messageContainer.find('.list').append(`<li data-name="${dataID}"></li>`)
				liError = messageContainer.find(liErrorSelector)
				liError.html(`<strong>${nameOnLabel}</strong>: ${validationMessage}`)

				messageContainer.find('.list').append(liError)

			}

			function removeOrphanIndividualErrors() {
				const liErrors = messageContainer.find('.list li')
				liErrors.map(function (i, element) {
					let dataID = element.dataset.name
					let inputElement = form.find(`[data-id="${dataID}"]`)
					if (inputElement.length < 1) {
						$(element).remove()
					}
				})
			}

			return messageContainer

		}

	}
	onInvalidEvent = defaultInvalidHandler ? onInvalidEvent : function (event) {
	}
	let toast = true
	let ignoreRedirection = false
	let ignoreReload = false

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
		if (typeof options.ignoreRedirection == 'boolean') {
			ignoreRedirection = options.ignoreRedirection
		}
		if (typeof options.ignoreReload == 'boolean') {
			ignoreReload = options.ignoreReload
		}
	}

	if (form.length > 0) {

		form.off('invalid')
		form.find('input,textarea,select').off('invalid')
		form.find('input,textarea,select').on('invalid', onInvalidEvent)

		if (overwrite) {
			form.off('submit')
		}
		form.on('submit', function (e) {

			e.preventDefault()

			let thisForm = $(e.target)

			if (validate(form)) {
				if (!hasConfirmation) {

					submit(thisForm)

				} else {

					if (!waitForConfirmation) {
						$('body').addClass('wait-to-action')
						waitForConfirmation = true
						$.toast({
							title: options.confirmation.title,
							message: options.confirmation.message,
							displayTime: 0,
							class: 'white',
							position: 'top center',
							classActions: 'top attached',
							actions: [{
								text: `${options.confirmation.positive}`,
								class: 'blue',
								click: function () {
									submit(thisForm)
									$('body').removeClass('wait-to-action')
									waitForConfirmation = false
								}
							}, {
								text: `${options.confirmation.negative}`,
								class: 'gray',
								click: function () {
									$('body').removeClass('wait-to-action')
									waitForConfirmation = false
									return true
								}
							}]
						})
					}
				}
			}

			return false

		})
	}

	function submit(form) {

		let formData = new FormData(form[0])
		form.find('button[type="submit"]').attr('disabled', true)

		let action = form.attr('action')
		let method = form.attr('method')
		let validAction = typeof action == 'string' && action.trim().length > 0
		let validMethod = typeof method == 'string' && method.trim().length > 0
		method = validMethod ? method.trim().toUpperCase() : 'POST'

		if (validAction) {

			let request = null

			let loaderElement = showLoader()

			if (method == 'POST') {

				let processFormData = onSetFormData(formData, form)
				let optionsPost = {
					xhr: function () {

						let xhr = new XMLHttpRequest()

						xhr.upload.addEventListener("progress", function (e) {

							if (e.lengthComputable) {
								let percentComplete = ((e.loaded / e.total) * 100);
								loaderElement.updatePercent(percentComplete >= 100 ? 99 : percentComplete)
							}

						}, false)

						return xhr

					}
				}

				if (typeof processFormData.then !== 'undefined') {
					processFormData.then(function (formData) {
						request = postRequest(action, formData, {}, optionsPost)
						handlerRequest(request, form, formData)
					})
				} else {
					request = postRequest(action, processFormData, {}, optionsPost)
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

			form.find('button').attr('disabled', false)
			console.error('No se ha definido ninguna acción')

			if (toast) {
				errorMessage('Error', 'Ha ocurrido un error desconocido, intente más tarde.')
			}

		}
	}

	/**
	 * @param {JQueryXHR} request 
	 * @param {$} formProcess 
	 * @param {FormData} formData 
	 * @returns {void}
	 */
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
				form.find('button').attr('disabled', false)
				console.error(`La respuesta debe ser un objeto`)
				return
			}

			for (let option in responseStructure) {
				let config = responseStructure[option]
				let optional = config.optional
				let validateResponseValue = config.validate
				let parse = config.parse
				let value = config.default
				let optionExists = typeof response[option]
				if (optionExists) {
					let inputValue = response[option]
					if (validateResponseValue(inputValue)) {
						value = parse(inputValue)
					}
					response[option] = value
				} else if (optional) {
					response[option] = value
				} else {
					form.find('button').attr('disabled', false)
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

				if (ignoreRedirection) {
					hasRedirection = false
				}

				if (ignoreReload) {
					hasReload = false
				}

				let promiseOnSuccess = onSuccess(formProcess, formData, response)

				if (!(promiseOnSuccess instanceof Promise)) {
					promiseOnSuccess = Promise.resolve()
				}

				promiseOnSuccess.finally(function () {

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

				})

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
		return showGenericLoader('genericFormHandler')
	}

	function removeLoader() {
		removeGenericLoader('genericFormHandler')
	}

	return form

}

/**
 * showGenericLoader
 * 
 * Muestra un modal de carga en el body
 * 
 * @returns {HTMLElement} 
 */
function showGenericLoader(name = 'DEFAULT', classPrefix = 'ui-pcs-', withProgress = false) {

	classPrefix = typeof classPrefix == 'string' && classPrefix.length ? classPrefix : 'ui-pcs-'

	name = typeof name == 'string' && name.length > 0 ? name : 'DEFAULT'

	let contentLoader = document.createElement('div')
	let boxLoader = document.createElement('div')
	let loader = document.createElement('div')

	contentLoader.classList.add(`${classPrefix}global-loader`, 'active')
	boxLoader.classList.add(`${classPrefix}box`)
	loader.classList.add(`${classPrefix}loader`)

	contentLoader.setAttribute('data-name', name)

	contentLoader.updatePercent = function (percent) {

		percent = parseInt(percent)
		percent = !isNaN(percent) ? percent : 0

		if (this.hasProgress()) {
			let progress = this.getProgressElement()
			progress.update(percent)
		} else {
			addProgress(percent, this.querySelector(`.${classPrefix}box`))
		}

		if (percent == 100) {
			this.remove()
		}

	}

	contentLoader.hasProgress = function () {
		return contentLoader.querySelector(`.${classPrefix}progress`) !== null
	}

	contentLoader.getProgressElement = function () {
		return contentLoader.querySelector(`.${classPrefix}progress`)
	}

	let currentActive = document.querySelector(`.${classPrefix}global-loader[data-name="${name}"]`)

	if (!(currentActive instanceof HTMLElement)) {
		boxLoader.appendChild(loader)
		contentLoader.appendChild(boxLoader)
		document.body.appendChild(contentLoader)
		if (withProgress) {
			addProgress(0, boxLoader)
		}
		return contentLoader
	} else {
		return currentActive
	}

	function addProgress(percent, parent) {
		let progress = document.createElement('span')
		progress.innerHTML = `${percent}%`
		progress.classList.add(`${classPrefix}progress`)
		parent.appendChild(progress)
		progress.update = function (percent) {
			progress.innerHTML = `${percent}%`
		}
	}

}

/**
 * removeGenericLoader
 * 
 * Oculta un modal de carga en el body
 * 
 * @returns {void} 
 */
function removeGenericLoader(name = 'DEFAULT', classPrefix = 'ui-pcs-') {

	classPrefix = typeof classPrefix == 'string' && classPrefix.length ? classPrefix : 'ui-pcs-'

	name = typeof name == 'string' && name.length > 0 ? name : 'DEFAULT'

	let contentLoader = document.createElement('div')
	let boxLoader = document.createElement('div')
	let loader = document.createElement('div')

	contentLoader.classList.add(`${classPrefix}global-loader`, 'active')
	boxLoader.classList.add(`${classPrefix}box`)
	loader.classList.add(`${classPrefix}loader`)

	contentLoader.setAttribute('data-name', name)

	let currentActive = document.querySelector(`.${classPrefix}global-loader[data-name="${name}"]`)

	if (currentActive instanceof HTMLElement) {
		currentActive.remove()
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
 * - M	Una representación textual corta de un mes, tres letras
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
	let M = _i18n('semantic_calendar', 'monthsShort')[date.getMonth()]

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
	let A = hour >= 12 ? 'PM' : 'AM'

	let i = parseInt(date.getMinutes())
	i = i < 10 ? `0${i}` : i
	let s = parseInt(date.getSeconds())
	s = s < 10 ? `0${s}` : s

	let replacesPattern = {
		'Y': Y,
		'm': m,
		'M': M,
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

/**
 * Detección de evento swipe
 * @param {HTMLElement} el 
 * @param {Function} callback Función que devuelve como parámetro alguna de las siguientes opciones: u, r, d, l, none y el evento
 * @link http://www.javascriptkit.com/javatutors/touchevents2.shtml
 */
function swipedetect(el, callback) {

	var touchsurface = el,
		swipedir,
		startX,
		startY,
		distX,
		distY,
		threshold = 150, //required min distance traveled to be considered swipe
		restraint = 100, // maximum distance allowed at the same time in perpendicular direction
		allowedTime = 300, // maximum time allowed to travel that distance
		elapsedTime,
		startTime,
		handleswipe = callback || function (swipedir) { }

	touchsurface.addEventListener('touchstart', function (e) {
		var touchobj = e.changedTouches[0]
		swipedir = 'none'
		dist = 0
		startX = touchobj.pageX
		startY = touchobj.pageY
		startTime = new Date().getTime() // record time when finger first makes contact with surface
	}, {
		passive: true,
	})

	touchsurface.addEventListener('touchend', function (e) {
		var touchobj = e.changedTouches[0]
		distX = touchobj.pageX - startX // get horizontal dist traveled by finger while in contact with surface
		distY = touchobj.pageY - startY // get vertical dist traveled by finger while in contact with surface
		elapsedTime = new Date().getTime() - startTime // get time elapsed
		if (elapsedTime <= allowedTime) { // first condition for awipe met
			if (Math.abs(distX) >= threshold && Math.abs(distY) <= restraint) { // 2nd condition for horizontal swipe met
				swipedir = (distX < 0) ? 'l' : 'r' // if dist traveled is negative, it indicates left swipe
			}
			else if (Math.abs(distY) >= threshold && Math.abs(distX) <= restraint) { // 2nd condition for vertical swipe met
				swipedir = (distY < 0) ? 'u' : 'd' // if dist traveled is negative, it indicates up swipe
			}
		}
		handleswipe(swipedir, e)
	}, false)
}

/**
 * Configura un scroll x espejo en la parte de arriba
 * @param {String} [namespace]
 * @param {String} [selector]
 */
function configMirrorScrollX(namespace = 'default', selector = null) {

	let mirrorScrollX = typeof selector == 'string' ? $(selector) : $('.mirror-scroll-x')

	if (mirrorScrollX.length > 0) {

		let mirrorScrollXContent = mirrorScrollX.find('.mirror-scroll-x-content')
		let mirrorTarget = $(mirrorScrollX.attr('mirror-scroll-target'))

		let checkScrollX = function () {

			let scrollXWidth = mirrorTarget.get(0).scrollWidth
			let targetWidth = mirrorTarget.get(0).clientWidth
			let isVisibleScrollX = targetWidth < scrollXWidth

			if (isVisibleScrollX) {
				mirrorScrollXContent.width(scrollXWidth)
				mirrorTarget.addClass('with-mirror-scroll-x')
				mirrorScrollX.show()
			} else {
				mirrorTarget.removeClass('with-mirror-scroll-x')
				mirrorScrollX.hide()
			}

			mirrorScrollX.get(0).onscroll = function () {
				mirrorTarget.get(0).scrollLeft = mirrorScrollX.get(0).scrollLeft
			}

			mirrorTarget.get(0).onscroll = function () {
				mirrorScrollX.get(0).scrollLeft = mirrorTarget.get(0).scrollLeft
			}

		}

		$(window).off(`resize.${namespace}`, checkScrollX)
		$(window).on(`resize.${namespace}`, checkScrollX)
		setTimeout(function () {
			$(window).trigger(`resize.${namespace}`)
		}, 500)
	}

}

/**
 * Configura un dropdown
 * @param {String} selectSelector 
 * @param {Object} defaultOptions 
 * @param {Booloan} cacheOnAPI
 * @returns {$[]} 
 */
function configFomanticDropdown(selectSelector, defaultOptions = {}, cacheOnAPI = false) {
	selectSelector = typeof selectSelector == 'string' ? selectSelector : 'NONE_SELECTOR'
	defaultOptions = typeof defaultOptions == 'object' ? defaultOptions : {}

	let selects = Array.from(document.querySelectorAll(selectSelector))
	let dropdowns = []

	for (let select of selects) {


		const originalSelectHTML = select.outerHTML
		select = $(select)
		let searchURL = select.data('search-url')
		searchURL = typeof searchURL == 'string' && searchURL.trim().length > 0 ? searchURL.trim() : null

		let options = Object.assign({}, defaultOptions)

		if (searchURL) {
			let apiSettings = typeof options.apiSettings == 'object' ? options.apiSettings : {}
			const URLSearch = new URL(searchURL)
			URLSearch.searchParams.set('search', 'SEARCH_QUERY')
			apiSettings.url = URLSearch.href.replace('SEARCH_QUERY', '{query}')
			options.apiSettings = apiSettings
			options.apiSettings.cache = cacheOnAPI === true ? true : false
		}

		let dropdown = null

		//Input para simular el error de validación
		let uniqueID = generateUniqueID()
		let selectSimulator = document.createElement('select')
		if (select.get(0).required) {
			selectSimulator.setAttribute('required', true)
		}
		select.attr('data-simulator', uniqueID)
		selectSimulator.setAttribute('simulator', uniqueID)
		selectSimulator.setAttribute('style', [
			"display: block !important;",
			"height: 0px !important;",
			"width: 0px !important;",
			"margin: 0px !important;",
			"padding: 0px !important;",
			"outline: none !important;",
			"border: none !important;",
		].join(' '))

		let onChange = function (value, text, $selectedItem) {
			let selectValidator = document.querySelector(`select[simulator="${uniqueID}"]`)

			if (Array.isArray(value)) {

				selectValidator.innerHTML = ''
				for (let i of value) {
					if (typeof i == 'string' && i.length > 0 && selectValidator !== null) {
						selectValidator.innerHTML += `<option value="${i}" selected></option>`
					}
				}

			} else {
				if (typeof value == 'string' && value.length > 0 && selectValidator !== null) {
					selectValidator.innerHTML = `<option value="${value}" selected></option>`
				}
			}
		}

		if (typeof options.onChange == 'function') {
			let onChangeOption = options.onChange
			options.onChange = function (value, text, $selectedItem) {
				onChange(value, text, $selectedItem)
				onChangeOption(value, text, $selectedItem)
			}
		} else {
			options.onChange = onChange
		}

		dropdown = $(select).dropdown(options)
		dropdown.getText = function (value, text = null) {
			return dropdown.dropdown('get text')
		}
		dropdown.getValue = function (value, text = null) {
			return dropdown.dropdown('get value')
		}
		dropdown.setValue = function (value, text = null) {
			if (text !== null) {
				dropdown.dropdown('set value', value)
				dropdown.dropdown('set text', text)
				dropdown.dropdown('set selected', value)
				dropdown.dropdown('refresh')
			} else {
				dropdown.dropdown('set selected', value)
				dropdown.dropdown('refresh')
			}
			onChange(dropdown.dropdown('get value'), dropdown.dropdown('get text'))
		}
		dropdown.removeValues = function () {
			const defaultPlaceholder = dropdown.dropdown("get placeholder text")
			dropdown.dropdown("clear")
			dropdown.find('select').html("")
			dropdown.find('select').append(`<option value="">${defaultPlaceholder}</option>`)
			dropdown.dropdown("refresh")
			selectSimulator.innerHTML = ''
		}
		dropdown.addValue = function (value, text, selected = false) {
			if (selected) {
				dropdown.find('select').append(`<option selected value="${value}">${text}</option>`)
				onChange(dropdown.dropdown('get value'), dropdown.dropdown('get text'))
			} else {
				dropdown.find('select').append(`<option value="${value}">${text}</option>`)
			}
			dropdown.dropdown("refresh")
		}
		dropdown.setRequired = function (required = false) {
			toggleRequiredSemanticDropdown(dropdown, required)
			dropdown.find('select[data-simulator]').attr('required', false).removeAttr('required')
		}
		dropdown.recreate = function (removeItems = false, searchURL = null, options = {}) {

			dropdown.simulatorNode.remove()
			const recreated = $(originalSelectHTML)

			if (searchURL !== null) {
				recreated.attr('data-search-url', searchURL)
			}

			if (removeItems) {
				recreated.find('option').remove()
			}

			dropdown.replaceWith(recreated)

			const optionsRecreate = Object.assign({}, defaultOptions)

			for (const option in options) {
				optionsRecreate[option] = options[option]
			}

			dropdown = configFomanticDropdown(selectSelector, optionsRecreate)[0]

		}
		dropdown.getOriginalSelect = function () {
			return $(originalSelectHTML)
		}

		//Duplicar los atributos data-* en el dropdown
		const originalSelect = $(originalSelectHTML)
		const originalDataSet = originalSelect.get(0).dataset

		for (const dataName in originalDataSet) {
			const dataValue = originalDataSet[dataName]
			dropdown.get(0).dataset[dataName] = dataValue
		}

		//Añadir input para simular el error de validación
		dropdown.simulatorNode = dropdown.parent().get(0).insertBefore(selectSimulator, dropdown.get(0))
		dropdowns.push(dropdown)
		onChange(dropdown.dropdown('get value'), dropdown.dropdown('get text'))

	}

	return dropdowns
}

/**
 * @param {$} dropdown 
 * @param {Boolean} [required] 
 * @param {Boolea} [hideOnNoRequire] 
 */
function toggleRequiredSemanticDropdown(dropdown, required = true, hideOnNoRequire = false) {
	let mainSelect = dropdown.find('select')
	let simulatorSelect = $(`select[simulator="${mainSelect.attr('data-simulator')}"]`)
	simulatorSelect.val('')
	dropdown.dropdown('restore defaults', '')
	dropdown.dropdown('refresh', '')
	dropdown.dropdown('set value', '')
	if (required) {
		mainSelect.attr('required', true)
		simulatorSelect.attr('required', true)
		if (!dropdown.closest('.field').is(':visible')) {
			dropdown.closest('.field').show()
		}
	} else {
		mainSelect.removeAttr('required')
		simulatorSelect.removeAttr('required')
		if (hideOnNoRequire) {
			dropdown.closest('.field').hide()
		}
	}
}

/**
 * @param {$} input 
 * @param {Boolean} [required] 
 * @param {Boolea} [hideOnNoRequire] 
 * @param {Boolea} [disableOnNoRequire] 
 */
function toggleRequiredSemanticInput(input, required = true, hideOnNoRequire = false, disableOnNoRequire = false) {

	let prevValue = input.attr('data-prev-value')
	prevValue = typeof prevValue == 'string' && prevValue.trim().length > 0 ? prevValue : input.val()

	input.attr('data-prev-value', prevValue)

	let fieldContainer = input.closest('.field')

	if (required) {
		input.attr('required', true)
		input.val(prevValue)
		if (!fieldContainer.is(':visible')) {
			fieldContainer.show()
		}
		fieldContainer.removeClass('disabled')
		input.removeAttr('disabled')
	} else {
		input.removeAttr('required')
		input.val('')
		if (hideOnNoRequire) {
			fieldContainer.hide()
		}
		if (disableOnNoRequire) {
			fieldContainer.addClass('disabled')
			input.attr('disabled', true)
		}
	}
}

/**
 * @param {$} dropdown 
 * @param {String} value 
 * @param {String} text 
 */
function changeValueSemanticDropdown(dropdown, value, text = null) {
	if (text !== null) {
		dropdown.dropdown('set value', value)
		dropdown.dropdown('set text', text)
		dropdown.dropdown('set selected', value)
		dropdown.dropdown('refresh')
	} else {
		dropdown.dropdown('set selected', value)
		dropdown.dropdown('refresh')
	}
}

/**
 * Simplifica una fracción
 * @param {Numbar} numerator 
 * @param {Numbar} denominator 
 * @returns {Object} Un objeto con las promiedades numerator, denominator
 */
function simplify(numerator, denominator) {

	for (var i = Math.max(numerator, denominator); i > 1; i--) {

		if ((numerator % i == 0) && (denominator % i == 0)) {
			numerator /= i
			denominator /= i
		}

	}

	return {
		numerator: numerator,
		denominator: denominator,
	}
}

/**
 * @param {$} element
 * @returns {Boolean}
 */
function visibleInViewPort(element) {
	const elementTop = element.offset().top
	const elementBottom = elementTop + element.outerHeight()
	const viewportTop = $(window).scrollTop()
	const viewportBottom = viewportTop + $(window).height()
	return elementBottom > viewportTop && elementTop < viewportBottom
}

/**
 * @param {Date} date
 * @return {String}
 */
function formatDateAlternative(date = new Date(), format = '%d/%m/%Y %H:%i:%s') {

	let year = date.getFullYear()
	let month = (date.getMonth() + 1).toString()
	let day = date.getDate().toString()
	let hour = date.getHours().toString()
	let minutes = date.getMinutes().toString()
	let seconds = date.getSeconds().toString()

	month = month.length == 1 ? `0${month}` : month
	day = day.length == 1 ? `0${day}` : day
	hour = hour.length == 1 ? `0${hour}` : hour
	minutes = minutes.length == 1 ? `0${minutes}` : minutes
	seconds = seconds.length == 1 ? `0${seconds}` : seconds

	let monthFullName = date.toLocaleString('es-CO', {
		timeZone: 'America/Bogota',
		month: 'long',
	})

	monthFullName = monthFullName.split('')
	monthFullName[0] = monthFullName[0].toUpperCase()
	monthFullName = monthFullName.join('')

	let formats = {
		'Y': year,
		'm': month,
		'd': day,
		'H': hour,
		'i': minutes,
		's': seconds,
		'F': monthFullName,
	}

	for (let i in formats) {
		let value = formats[i]
		format = format.replace(new RegExp(`\%${i}`, 'gim'), value)
	}

	return date != 'Invalid Date' ? format : null

}

/**
 * Observa los cambios en el tamaño de un elemento y le agrega las clases
 * según corresponda por el ancho. Si el ancho es menor o igual que los dispuestos
 * en pcsphpGlobals.responsive.sizes agregará la clase del más pequeño, las clases
 * pueden verse en pcsphpGlobals.responsive.class
 * @param {HTMLElement} observedElement 
 * @param { (element: HTMLElement,elementOffsetWidth: Number) => void } onChange 
 * @param { Array<{size:Number, class:String}> } [customSizes] Si se quiere que las dimensiones y clases sean
 * personalizadas debe definirse este array de objetos siguiendo la estructura adecuada
 */
function responsiveObserver(observedElement, onChange, customSizes = []) {

	if (observedElement instanceof HTMLElement) {

		const resizeObserver = new ResizeObserver(/** @type {ResizeObserverEntry[]} */function (entries) {

			const width = observedElement.offsetWidth
			let sizes = pcsphpGlobals.responsive.sizes
			let sizesClasses = pcsphpGlobals.responsive.class

			//Validar tamaños personalizados
			if (Array.isArray(customSizes)) {
				const validatedCustomSizes = {
					sizes: {},
					class: {},
				}
				for (const customSize of customSizes) {
					const size = typeof customSize.size == 'number' && !isNaN(customSize.size) ? customSize.size : null
					const classSize = typeof customSize.class == 'string' && customSize.class.trim().length > 0 ? customSize.class : null
					if (size !== null && classSize !== null) {
						const sizeID = generateUniqueID()
						validatedCustomSizes.sizes[sizeID] = size
						validatedCustomSizes.class[sizeID] = classSize
					}
				}

				if (Array.from(Object.values(validatedCustomSizes.sizes)).length > 0) {
					sizes = validatedCustomSizes.sizes
					sizesClasses = validatedCustomSizes.class
				}
			}

			const sizeClassesValues = Array.from(Object.values(sizesClasses))
			const activesClassesBySize = new Map()

			for (const sizeName in sizes) {

				const size = sizes[sizeName]
				const sizeClass = sizesClasses[sizeName]

				if (width <= size) {
					activesClassesBySize.set(size, sizeClass)
				} else {
					activesClassesBySize.delete(size)
				}

			}

			const classArrayToReduce = Array.from(activesClassesBySize.entries())
			const classToAdd = classArrayToReduce.length > 0 ? classArrayToReduce.reduce(function (a, b) {
				return a[0] < b[0] ? a : b
			}) : []

			if (classToAdd.length > 0) {
				observedElement.classList.add(classToAdd[1])
			}

			for (const classToDelete of sizeClassesValues) {
				if (classToDelete != classToAdd[1]) {
					observedElement.classList.remove(classToDelete)
				}
			}

			if (typeof onChange == 'function') {
				onChange(observedElement, width)
			}

		})

		resizeObserver.observe(observedElement)

	}

}
