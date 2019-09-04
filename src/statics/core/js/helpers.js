/**
 * @method successMessage
 * @description Mensaje modal con tipo success (SweetAlert2)
 * @param {String} title Título del mensaje
 * @param {String} message Mensaje
 * @param {Function} onClose Callback llamado al cerrar
 */
function successMessage(title, message, onClose = null) {
	title = title !== undefined ? title : ''
	message = message !== undefined ? message : ''
	iziToast.success({
		title: title,
		message: message,
		position: 'topCenter',
		onClosed: () => {
			if (typeof onClose == 'function') {
				onClose()
			}
		},
	});
}

/**
 * @method warningMessage
 * @description Mensaje modal con tipo warning (SweetAlert2)
 * @param {String} title Título del mensaje
 * @param {String} message Mensaje
 * @param {Function} onClose Callback llamado al cerrar
 */
function warningMessage(title, message, onClose = null) {
	title = title !== undefined ? title : ''
	message = message !== undefined ? message : ''
	iziToast.warning({
		title: title,
		message: message,
		position: 'topCenter',
		onClosed: () => {
			if (typeof onClose == 'function') {
				onClose()
			}
		},
	});
}

/**
 * @method infoMessage
 * @description Mensaje modal con tipo info (SweetAlert2)
 * @param {String} title Título del mensaje
 * @param {String} message Mensaje
 * @param {Function} onClose Callback llamado al cerrar
 */
function infoMessage(title, message, onClose = null) {
	title = title !== undefined ? title : ''
	message = message !== undefined ? message : ''
	iziToast.info({
		title: title,
		message: message,
		position: 'topCenter',
		onClosed: () => {
			if (typeof onClose == 'function') {
				onClose()
			}
		},
	});
}

/**
 * @method errorMessage
 * @description Mensaje modal con tipo error (SweetAlert2)
 * @param {String} title Título del mensaje
 * @param {String} message Mensaje
 * @param {Function} onClose Callback llamado al cerrar
 */
function errorMessage(title, message, onClose = null) {
	title = title !== undefined ? title : ''
	message = message !== undefined ? message : ''
	iziToast.error({
		title: title,
		message: message,
		position: 'topCenter',
		onClosed: () => {
			if (typeof onClose == 'function') {
				onClose()
			}
		},
	});
}

/**
 * @method cropperToDataURL
 * @description Obtiene el string DataURL de la imagen de un objeto cropper
 * @param {Cropper} cropper Objeto Cropper
 * @param {Object} config Configuraciones
 * @param {Boolean} isJPG Convertir en JPEG
 * @return {String} DataURL del Cropper
 */
function cropperToDataURL(cropper, config = {}, isJPG = true) {
	if (isJPG === true) {
		return cropper.getCroppedCanvas(config).toDataURL("image/jpeg")
	} else {
		return cropper.getCroppedCanvas(config).toDataURL()
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
 * pcsSideBar
 * 
 * Configura la barra lateral de PiecesPHP
 * 
 * @param {HTMLElement|JQuery|string} selector Selector o elemento de la barra
 * @returns {void}
 */
function pcsSideBar(selector) {
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
 * pcsTopBar
 * 
 * Configura la barra superior de PiecesPHP
 * 
 * @param {HTMLElement|JQuery|string} selector Selector o elemento de la barra
 * @returns {void}
 */
function pcsTopBar(selector) {
	let topbar = $(selector)
	if (topbar.length > 0) {
		let arrow = topbar.find('.user-info')
		let menu = topbar.find('.user-info .info-menu')
		if (arrow.length > 0 && menu.length > 0) {
			arrow.click(function (e) {
				e.stopPropagation()
				if (menu.is(':visible')) {
					menu.hide(500)
					menu.parent().removeClass('active')
				} else {
					menu.show(500)
					menu.parent().addClass('active')
				}
			})
			$('body').click(function (e) {
				if (menu.is(':visible')) {
					menu.hide(500)
					menu.parent().removeClass('active')
				}
			})
		}
	}
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
 * @param {FormData} formData Información enviada
 * @returns {jqXHR}
 */
function postRequest(url, formData) {
	return $.ajax({
		url: url,
		method: 'POST',
		processData: false,
		enctype: "multipart/form-data",
		contentType: false,
		cache: false,
		data: formData
	})
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
 * @param {string} url URL que se consultará
 * @param {string|HTMLElement|JQuery} form Formulario
 * @returns {jqXHR}
 */
function getRequest(url, form) {

	if (form !== undefined) {
		return $.ajax({
			url: url,
			method: 'GET',
			enctype: "application/x-www-form-urlencoded",
			data: $(form).serialize()
		})
	} else {
		return $.ajax({
			url: url,
			method: 'GET',
			enctype: "application/x-www-form-urlencoded"
		})
	}

}

/**
 * templateResolver
 * 
 * Compila y genera las plantillas handlebars indicadas, luego las introduce en el
 * objetivo relacionado por medio del identificador de la plantilla
 * requiere handlebars.js y jquery
 * 
 * Notas: 
 * - El contenedor de la plantilla debe tener la plantilla dentro de las etiqueta template-structure.
 * - Los datos que se usarán en la plantilla deben estar situados en el contenedor dentro de las etiquetas data y deben ser
 * una cadena que sea válida para JSON.parse
 * 
 * Ejemplo:
 * ```html
<pcs-template style="display:none;" template-id='1'>
    <data>
        {"types":{"KEY_NAME":"Valor del texto"}}
    </data>
    <template-structure>
        <form method="POST" class="ui form importador" enctype="multipart/form-data">
            <div class="field required">
                <select required name="type" class="ui dropdown search">
                    <option value="">Seleccione el tipo de documento</option>
                    {{#each types}}
                    <option value="{{@key}}">{{this}}</option>
                    {{/each}}
                </select>
            </div>
            <div class="field">
                <label>Subir archivo excel</label>
                <input type="file" name="archivo">
            </div>
            <div class="field">
                <button type="submit" class="ui button green button-other">Subir</button>
            </div>
        </form>
    </template-structure>
</pcs-template>```
 * 
 * @param {string} templateContainerSelector El selector del elemento que contiene la plantilla, por defecto pcs-template
 * @param {string} templateIDAttr El atributo que contiene el identificador de la plantilla, por defecto template-id
 * @param {string} templateTargetAttr El atributo que contiene el identificador de la plantilla en el objetivo de la plantilla, por defecto pcs-template-target
 * @returns {void}
 */
function templateResolver(templateContainerSelector = 'pcs-template', templateIDAttr = 'template-id', templateTargetAttr = 'pcs-template-target', allDataSelector = null) {

	let modelTemplates = $(templateContainerSelector)
	let dataTemplates = null

	if (allDataSelector !== null) {
		let _tmp = $(allDataSelector)
		if (_tmp.length > 0) {
			_tmp.hide()
			dataTemplates = JSON.parse(_tmp.text())
		}
	}

	if (modelTemplates.length > 0) {

		let modelTemplatesArray = modelTemplates.toArray()

		modelTemplates.hide()

		for (let modelTemplate of modelTemplatesArray) {

			modelTemplate = $(modelTemplate)

			let id = modelTemplate.attr(templateIDAttr)
			let target = $(`[${templateTargetAttr}='${id}']`)

			let structure = modelTemplate.find('template-structure').html()
			let textData = modelTemplate.find('data').text()
			let data = dataTemplates !== null ? dataTemplates : JSON.parse(textData)

			let handleBarsTemplate = Handlebars.compile(structure)
			let result = handleBarsTemplate(data)

			target.html(result)

		}
	}
}

/**
 * filterSorterResolver
 * 
 * @param {string} containerSelector El selector del elemento que contiene los botones de ordenamiento y filtrado
 * @returns {void}
 */
function filterSorterResolver(containerSelector = 'pcs-sorter-filter') {

	let pluginSign = containerSelector

	let containerSelectorAttr = pluginSign
	let containerIDSelectorAttr = `${pluginSign}-id`
	let targetSelectorAttr = `${pluginSign}-target`
	let sortDataSelectorAttr = `sort-by`
	let filterDataSelectorAttr = `filter-by`
	let elementsSelectorAttr = pluginSign + '-element'

	let containersButtons = $(`[${containerSelectorAttr}]`)
	let containersButtonsArray = containersButtons.toArray()

	let statusSorting = {}
	let statusFiltering = {}

	for (let containersButton of containersButtonsArray) {

		containersButton = $(containersButton)
		let id = containersButton.attr(containerIDSelectorAttr)
		let target = $(`[${targetSelectorAttr}='${id}']`)

		let triggersSort = containersButton.find(`[${sortDataSelectorAttr}]`)
		let triggersFilter = containersButton.find(`[${filterDataSelectorAttr}]`)

		triggersSort.css({
			cursor: 'pointer'
		})

		let elementsSelector = `[${elementsSelectorAttr}]`
		let elements = target.find(elementsSelector).toArray()
		let sortTypes = []
		let sortData = {}

		let sorterDefaultSign = pluginSign + '-data-sort-'


		for (let element of elements) {
			let attrs = element.attributes
			for (let attr of attrs) {
				let name = attr.name
				if (name.indexOf(sorterDefaultSign) !== -1) {
					let nameCritery = name.replace(sorterDefaultSign, '')
					if (sortTypes.indexOf(nameCritery) === -1) {
						sortTypes.push(nameCritery)
					}
				}
			}
		}

		for (let sortType of sortTypes) {
			sortData[sortType] = `[${sorterDefaultSign}${sortType}]`
		}

		let dataContainer = target.isotope({
			itemSelector: elementsSelector,
			layoutMode: 'fitRows',
			getSortData: sortData
		})


		triggersSort.on('click', function () {

			triggersSort.removeClass('active')
			$(this).addClass('active')

			let sortAscending = true

			let sortBy = $(this).attr(sortDataSelectorAttr)

			if (typeof statusFiltering[sortBy] == 'undefined') {
				statusFiltering[sortBy] = sortAscending
			} else {
				if (statusFiltering[sortBy]) {
					sortAscending = false
				} else {
					sortAscending = true
				}

				statusFiltering[sortBy] = sortAscending
			}

			dataContainer.isotope('updateSortData').isotope()
			dataContainer.isotope({
				sortBy: sortBy,
				transitionDuration: 0,
				sortAscending: sortAscending
			})
		})

		triggersFilter.on('click', function () {

			triggersFilter.removeClass('active')
			$(this).addClass('active')

			let filterBy = $(this).attr(filterDataSelectorAttr)

			dataContainer.isotope('updateSortData').isotope()
			dataContainer.isotope({
				filter: filterBy,
				transitionDuration: 0,
			})
		})

	}
}

/**
 * Formatea un elemento html y devuelve el elemento formateado como
 * una cadena
 * 
 * @param {HTMLElement} element 
 * @param {Number} level 
 * @param {Boolean} returnInner 
 * @param {Boolean} debug 
 * @returns {string|null}
 */
function formatHTML(element, level = 0, returnInner = false, debug = false) {

	if (!element instanceof HTMLElement) {
		return null
	}

	level = typeof level == 'number' ? parseInt(level) : 0
	returnInner = typeof returnInner == 'boolean' ? returnInner : false
	debug = typeof debug == 'boolean' ? debug : false

	let tabsRoot = ""

	for (let i = 0; i < level; i++) {
		tabsRoot += "\t"
	}

	tabsRoot = debug ? level : tabsRoot

	let root = tabsRoot + element.outerHTML.replace(element.innerHTML, `{{CONTENT}}`)

	let inner = ''

	let childs = element.children

	if (!returnInner) {
		level++
	}

	let tabsInner = ""
	for (let i = 0; i < level; i++) {
		tabsInner += "\t"
	}
	tabsInner = debug ? level : tabsInner

	for (let child of childs) {

		let subChilds = child.children
		let numChilds = subChilds.length
		let hasChilds = numChilds > 0

		if (!hasChilds) {
			inner += `\n${tabsInner}${child.outerHTML}`
		} else {
			inner += `\n${tabsInner}` + formatHTML(child, level)
		}
	}

	if (returnInner) {
		root = inner.trim()
	} else {
		root = root.replace('{{CONTENT}}', inner + `\n${tabsRoot}`).trim()
	}

	return root
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

/**
 * pcsFormatNumberString
 * 
 * @param {string|number} input 
 * @param {Object} options 
 * @param {string} options.thousands 
 * @param {string} options.decimals 
 * @param {boolean} options.inverse 
 * @returns {number|string}
 */
function pcsFormatNumberString(input, options = {}) {

	let thousandsSeparator = typeof options.thousands == 'string' ? options.thousands : '.'
	let decimalsSeparator = typeof options.decimals == 'string' ? options.decimals : ','
	let inverse = typeof options.inverse == 'boolean' ? options.inverse : false

	if (typeof input == 'number') {
		input = input.toString().replace('.', decimalsSeparator)
	}

	let decimalSeparatorExists = false
	input = input.split('')

	for (let i = 0; i < input.length; i++) {

		let char = input[i]
		let allowed = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, '0', '1', '2', '3', '4', '5', '6', '7', '8', '9']

		if (allowed.indexOf(char) == -1 && char != decimalsSeparator) {

			input[i] = ''

		} else if (char == decimalsSeparator) {

			if (decimalSeparatorExists) {

				input[i] = ''

			} else {

				decimalSeparatorExists = true

			}

		}
	}

	input = input.join('')

	input = parseFloat(input.toString().replace(decimalsSeparator, '.'))
		.toString()
		.replace('.', decimalsSeparator)

	if (!inverse) {

		input = input.split(decimalsSeparator)

		let number = String(input[0]).replace(/(.)(?=(\d{3})+$)/g, '$1' + thousandsSeparator)
		let decimals = input.length > 1 ? `${decimalsSeparator}${input[1]}` : ''

		return `${number}${decimals}`

	} else {

		input = input.replace(decimalsSeparator, '.')

		return parseFloat(input)
	}
}

/**
 * simulateInputNumberFormat
 * 
 * @param {HTMLInputElement|$|string} input 
 * @param {Object} options 
 * @param {string} options.thousands 
 * @param {string} options.decimals 
 * @param {number} options.step 
 * @returns {number|string}
 */
function simulateInputNumberFormat(input, options = {}) {

	if (input instanceof HTMLInputElement) {
		input = $(input)
	} else if (typeof input == 'string') {
		input = $(input)
	}

	if (input instanceof $ && input.length > 0) {

		let thousands = typeof options.thousands == 'string' ? options.thousands : '.'
		let decimals = typeof options.decimals == 'string' ? options.decimals : ','
		let step = typeof options.step == 'number' ? options.step : 1
		let thousandInPattern = thousands == '.' ? "\\." : thousands
		let decimalInPattern = decimals == '.' ? "\\." : decimals
		let pattern = `^[0-9]{1,3}(${thousandInPattern}[0-9]{3})*(\\${decimalInPattern}{1}|\\${decimalInPattern}[0-9]+)*$`

		input.on('keyup', function (e) {

			let value = $(e.target).val()
			let valueLen = value.length
			let key = e.key

			if (valueLen > 0) {

				value = value.trim()
				let caretPosition = lastPositionCaret
				let currentCaretPosition = input[0].selectionStart
				let caretPreDecimal = false


				value = pcsFormatNumberString(value, {
					thousands: thousands,
					decimals: decimals,
					inverse: false
				})

				let regexp = new RegExp(pattern, 'gmi')

				if (key == decimals) {
					value = `${value}${decimals}`
				}

				if (regexp.test(value)) {
					$(e.target).val(value)
				} else {
					$(e.target).val("0")
				}

			}

		})

		input.on('keydown', function (e) {

			lastPositionCaret = e.target.selectionStart

			let key = e.key
			let value = $(e.target).val()
			value = value == 'NaN' ? 0 : value

			if (key == 'ArrowUp' || key == 'ArrowDown') {
				if (value.trim().length > 0) {
					value = pcsFormatNumberString(value, {
						thousands: thousands,
						decimals: decimals,
						inverse: true
					})
					value = key == 'ArrowUp' ? value + step : value - step
					value = pcsFormatNumberString(value, {
						thousands: thousands,
						decimals: decimals,
						inverse: false
					})
					$(e.target).val(value)
				}
			}

		})

	}

	function setCaretPosition(ctrl, pos) {
		// Modern browsers
		if (ctrl.setSelectionRange) {
			ctrl.focus();
			ctrl.setSelectionRange(pos, pos);

			// IE8 and below
		} else if (ctrl.createTextRange) {
			var range = ctrl.createTextRange();
			range.collapse(true);
			range.moveEnd('character', pos);
			range.moveStart('character', pos);
			range.select();
		}
	}
}

/**
 * @function friendlyURL
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
