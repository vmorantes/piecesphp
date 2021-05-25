/**
 * @function SimpleUploadPlaceholder
 *  
 * @param {SimpleUploadPlaceholderOptions} configurations  
 */
function SimpleUploadPlaceholder(configurations = {}) {

	const LANG_GROUP = 'SimpleUploadPlaceholder'

	SimpleUploadPlaceholder.registerDynamicMessages(LANG_GROUP)

	/**
	 * @typedef SimpleUploadPlaceholderOptions
	 * @property {String} [containerSelector=[simple-upload-placeholder]]
	 * @property {function(Event)} [onReady]
	 * @property {function(FileList,$,SimpleUploadPlaceholder,Event)} [onChangeFile] Devuelve el evento, el componente $ y la instancia
	 */

	//──── Valores de configuración ──────────────────────────────────────────────────────────
	/** @type {SimpleUploadPlaceholderOptions} Configuración por defecto de la clase */
	let defaultSimpleUploadPlaceholderOptions = {
		containerSelector: '[simple-upload-placeholder]',
		onReady: (e) => { },
		onChangeFile: (files, component, instance, event) => { },
	}

	//──── Variables ─────────────────────────────────────────────────────────────────────────

	/** @type {SimpleUploadPlaceholder} */ let instance = this
	/** @type {SimpleUploadPlaceholderOptions} */ let SimpleUploadPlaceholderOptions

	/** @type {Boolean} */ let fileInputIsRequired = false
	/** @type {Boolean} */ let fileInputIsMultiple = false

	/** @type {$} */ let container
	/** @type {$} */ let fileInput
	/** @type {$} */ let triggerButton
	/** @type {$|null} */ let overlayElement
	/** @type {$|null} */ let placeholderIcon

	/** @type {String} */ let triggerButtonDefaultText = _i18n(LANG_GROUP, 'Argregar archivo')

	//──── Eventos personalizados ────────────────────────────────────────────────────────────	

	/** @type {String} */ let eventsPrefix = 'event-simple-upload-placeholder'
	/** @type {HTMLElement} */ let eventer = document.createElement('eventer-simple-upload-placeholder')

	/** @type {Event[]} */ let events = {
		ready: {
			event: new Event(`${eventsPrefix}-ready`),
			data: {},
		},
	}

	/**
	 * @method on
	 * @param {String} eventName
	 * @param {Function} callback
	 * @returns {void}
	 */
	this.on = (eventName, callback) => {

		let eventPrefixed = `${eventsPrefix}-${eventName}`

		if (typeof events[eventName] !== 'undefined' && typeof callback == 'function') {

			eventer.addEventListener(eventPrefixed, function (e) {
				let eventConfig = events[eventName]
				callback(e, eventConfig.data)
			})

		}

	}

	/**
	 * @method dispatch
	 * @param {String} eventName
	 * @returns {void}
	 */
	this.dispatch = (eventName) => {

		if (typeof events[eventName] !== 'undefined') {
			let eventConfig = events[eventName]
			eventer.dispatchEvent(events[eventName].event)
		}

	}

	prepare(configurations)

	/**
	 * @function prepare
	 * @param {SimpleUploadPlaceholderOptions} configurations 
	 */
	function prepare(configurations = {}) {

		try {

			configOptions(configurations)

			triggerButton.on('click', function (e) {

				e.preventDefault()
				fileInput.click()

			})

			fileInput.on('change', function (e) {

				e.preventDefault()

				let files = e.target.files

				SimpleUploadPlaceholderOptions.onChangeFile(files, container, instance, e)

				let restoreOverlay = () => {
					if (overlayElement.hasClass('image')) {
						overlayElement.removeClass('image')
						overlayElement.removeAttr('style')
					}
				}

				if (files.length > 0) {

					let file = files[0]
					restoreOverlay()

					if (fileInputIsMultiple) {
						triggerButton.find('.text').text(formatStr(_i18n(LANG_GROUP, '%r Archivo(s) seleccionado(s)'), [files.length]))
					} else {

						let isImage = file.type.indexOf('image/') !== -1
						let fileName = file.name

						if (isImage) {
							showGenericLoader('loadImage...')

							triggerButton.find('.text').text(_i18n(LANG_GROUP, 'Cambiar imagen'))
							let imagePreview = new Image()
							imagePreview.src = URL.createObjectURL(file)

							imagePreview.addEventListener('load', function () {
								overlayElement.addClass('image')
								overlayElement.attr('style', `background-image: url(${imagePreview.src})`)
								removeGenericLoader('loadImage...')
							})

						} else {
							triggerButton.find('.text').text(fileName.length > 28 ? fileName.substring(0, 25) + '...' : fileName)
						}

					}


				} else {
					fileInput.val('')
					triggerButton.find('.text').text(triggerButtonDefaultText)
					restoreOverlay()
				}

				triggerButton.blur()

			})

			instance.dispatch('ready')

		} catch (error) {
			errorMessage(_i18n(LANG_GROUP, 'Error'), _i18n(LANG_GROUP, 'Ha ocurrido un error al configurar SimpleUploadPlaceholder'))
			console.error(error)
		}

	}

	/**
	 * @function configOptions
	 * @param {SimpleUploadPlaceholderOptions} configurations 
	 */
	function configOptions(configurations = {}) {

		//Configuraciones de SimpleUploadPlaceholderOptions 
		SimpleUploadPlaceholderOptions = processByDefaultValues(defaultSimpleUploadPlaceholderOptions, configurations)

		if (typeof SimpleUploadPlaceholderOptions.onReady == 'function') {
			instance.on('ready', SimpleUploadPlaceholderOptions.onReady)
		}

		if (typeof SimpleUploadPlaceholderOptions.onChangeFile != 'function') {
			SimpleUploadPlaceholderOptions.onChangeFile = defaultSimpleUploadPlaceholderOptions.onChangeFile
		}

		//Establecer valores
		let containerSelector = SimpleUploadPlaceholderOptions.containerSelector
		container = document.querySelector(containerSelector)

		if (container instanceof HTMLElement) {

			//──── Buscar elementos del componente ───────────────────────────────────────────────────

			container = $(container)
			fileInput = document.querySelector(`${containerSelector} input[type="file"]`)

			if (fileInput instanceof HTMLElement) {
				fileInputIsRequired = fileInput.required
				fileInputIsMultiple = fileInput.multiple
				fileInput = $(fileInput)
			} else {
				throw new Error(_i18n(LANG_GROUP, 'No se ha encontrado ningún input de tipo file.'))
			}

			triggerButton = document.querySelector(`${containerSelector} .trigger-file`)

			if (triggerButton instanceof HTMLElement) {
				triggerButton = $(triggerButton)
				let text = triggerButton.find('.text')
				triggerButtonDefaultText = text.length > 0 ? text.text() : triggerButtonDefaultText
			} else {
				throw new Error(_i18n(LANG_GROUP, 'No se ha el botón de cargar archivo.'))
			}

			overlayElement = document.querySelector(`${containerSelector} .overlay-element`)

			if (overlayElement instanceof HTMLElement) {
				overlayElement = $(overlayElement)
			} else {
				overlayElement = null
			}

			placeholderIcon = document.querySelector(`${containerSelector} .placeholder-icon`)

			if (placeholderIcon instanceof HTMLElement) {
				placeholderIcon = $(placeholderIcon)
			} else {
				placeholderIcon = null
			}

		} else {
			throw new Error(formatStr(_i18n(LANG_GROUP, 'No existe ningún elemento con el selector %r.'), [containerSelector]))
		}


	}

	/**
	 * @function processByStructure
	 * @param {Object} defaultValues 
	 * @param {Object} data 
	 * @returns {Object}
	 */
	function processByDefaultValues(defaultValues, data) {

		for (let option in defaultValues) {
			let defaultOption = defaultValues[option]
			if (typeof data[option] == 'undefined') {
				data[option] = defaultOption
			} else {
				if (typeof data[option] != typeof defaultOption) {
					data[option] = defaultOption
				}
			}
		}

		return data

	}

	return this
}
/**
 * @param {String} name 
 * @returns {void}
 */
SimpleUploadPlaceholder.registerDynamicMessages = function (name) {

	if (typeof pcsphpGlobals != 'object') {
		pcsphpGlobals = {}
	}
	if (typeof pcsphpGlobals.messages != 'object') {
		pcsphpGlobals.messages = {}
	}
	if (typeof pcsphpGlobals.messages.es != 'object') {
		pcsphpGlobals.messages.es = {}
	}
	if (typeof pcsphpGlobals.messages.en != 'object') {
		pcsphpGlobals.messages.en = {}
	}

	let es = {
	}

	let en = {
	}

	for (let i in es) {
		if (typeof pcsphpGlobals.messages.es[name] == 'undefined') pcsphpGlobals.messages.es[name] = {}
		pcsphpGlobals.messages.es[name][i] = es[i]
	}

	for (let i in en) {
		if (typeof pcsphpGlobals.messages.en[name] == 'undefined') pcsphpGlobals.messages.en[name] = {}
		pcsphpGlobals.messages.en[name][i] = en[i]
	}

}
