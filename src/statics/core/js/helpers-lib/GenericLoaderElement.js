///<reference path="../helpers.js" />
/**
 * Clase que representa un elemento de cargador genérico y expone la API para gestionar cargadores genéricos
 * Hereda de HTMLElement y configura todos los elementos del cargador
 */
class GenericLoaderElement extends HTMLElement {

	/**
	 * @param {String} [name='DEFAULT'] Nombre del cargador
	 * @param {String} [classPrefix='ui-pcs-'] Prefijo de la clase
	 * @param {Boolean} [withProgress=false] Indica si se debe mostrar el progreso
	 * @param {Object} [options={}] Opciones adicionales
	 * @param {String} options.textMessage Texto del mensaje
	 */
	constructor(name = null, classPrefix = null, withProgress = false, options = {}) {

		super()

		this.name = Boolean(name) ? name : 'DEFAULT'
		this.classPrefix = typeof classPrefix == 'string' ? classPrefix.trim() : 'ui-pcs-'
		this.withProgress = withProgress === true
		this.options = {
			...options,
		}

		//Crear elementos del cargador
		this.boxLoader = document.createElement('div')
		this.loaderElement = document.createElement('div')
		this.textMessage = document.createElement('div')

		this.configureElement()

	}

	/**
	 * Actualiza el texto del mensaje del cargador
	 * 
	 * @param {String} text Texto del mensaje
	 * @returns {GenericLoaderElement} Instancia del cargador
	 */
	updateTextMessage(text) {
		if (this.textMessage instanceof HTMLElement && typeof text == 'string') {
			this.textMessage.innerHTML = text
			if (text) {
				this.textMessage.removeAttribute('style')
			} else {
				this.textMessage.setAttribute('style', 'display: none !important;')
			}
		}
		return this
	}

	/**
	 * Agrega un elemento de progreso al cargador
	 * 
	 * @param {Number|null} percent Porcentaje inicial
	 * @param {HTMLElement} parent Elemento padre donde agregar el progreso
	 * @returns {GenericLoaderElement} Instancia del cargador
	 */
	addProgress(percent, parent) {
		const handleVisibility = function (progress, percent) {
			if (percent === null) {
				progress.setAttribute('style', 'opacity: 0 !important;')
			} else {
				progress.removeAttribute('style')
			}
		}
		let progress = document.createElement('span')
		progress.innerHTML = `${percent}%`
		progress.classList.add(`${this.classPrefix}progress`)
		parent.appendChild(progress)
		progress.update = function (percent) {
			progress.innerHTML = `${percent}%`
			handleVisibility(progress, percent)
		}
		handleVisibility(progress, percent)
		return this
	}

	/**
	 * Actualiza el porcentaje de progreso del cargador
	 * 
	 * @param {Number|null} percent Porcentaje a mostrar (0-100), null para ocultar el progreso
	 * @returns {GenericLoaderElement} Instancia del cargador
	 */
	updatePercent(percent) {

		percent = percent !== null ? parseInt(percent) : null
		percent = !isNaN(percent) || percent === null ? percent : 0
		const previousPercent = this.getProgressElement()
		const hasProgress = this.hasProgress()

		if (hasProgress) {
			previousPercent.update(percent)
		} else {
			this.addProgress(percent, this.loaderElement)
		}

		if (percent === 100) {
			this.remove()
		}
		return this
	}

	/**
	 * Verifica si el cargador tiene un elemento de progreso
	 * 
	 * @returns {Boolean} True si tiene progreso, false en caso contrario
	 */
	hasProgress() {
		return this.querySelector(`.${this.classPrefix}progress`) !== null
	}

	/**
	 * Obtiene el elemento de progreso del cargador
	 * 
	 * @returns {HTMLElement} Elemento de progreso
	 */
	getProgressElement() {
		return this.querySelector(`.${this.classPrefix}progress`)
	}

	/**
	 * Configura la estructura del elemento cargador
	 * @returns {GenericLoaderElement} Instancia del cargador
	 */
	configureElement() {

		//Configurar el elemento principal
		this.classList.add(`${this.classPrefix}global-loader`, 'active')
		this.setAttribute('data-name', this.name)

		//Configurar clases
		this.boxLoader.classList.add(`${this.classPrefix}box`)
		this.loaderElement.classList.add(`${this.classPrefix}loader`)
		this.textMessage.classList.add(`${this.classPrefix}text-message`)

		//Ensamblar estructura
		this.boxLoader.appendChild(this.loaderElement)
		this.appendChild(this.boxLoader)
		if (this.options.textMessage) {
			this.textMessage.innerHTML = this.options.textMessage
			this.boxLoader.appendChild(this.textMessage)
		}

		//Agregar progreso si se requiere		
		this.addProgress(this.withProgress ? 0 : null, this.loaderElement)

		return this
	}

	/**
	 * Muestra un modal de carga en el body
	 * 
	 * @param {String} [name='DEFAULT'] Nombre del cargador
	 * @param {String} [classPrefix='ui-pcs-'] Prefijo de la clase
	 * @param {Boolean} [withProgress=false] Indica si se debe mostrar el progreso
	 * @param {Object} [moreOptions={}] Opciones adicionales
	 * @param {String} moreOptions.textMessage Texto del mensaje
	 * @returns {GenericLoaderElement}
	 */
	static show(name = null, classPrefix = null, withProgress = false, moreOptions = {}) {

		//Buscar si ya existe un cargador activo con el mismo nombre
		let currentActive = GenericLoaderElement.currentActive(name, classPrefix)

		if (!(currentActive instanceof HTMLElement)) {
			//Crear nueva instancia del cargador
			const genericLoaderElement = new GenericLoaderElement(name, classPrefix, withProgress, moreOptions)
			document.body.appendChild(genericLoaderElement)
			return genericLoaderElement
		} else {
			//Retornar la instancia existente
			return currentActive
		}
	}

	/**
	 * Oculta un modal de carga en el body
	 * 
	 * @param {String} [name='DEFAULT'] Nombre del cargador
	 * @param {String} [classPrefix='ui-pcs-'] Prefijo de la clase
	 * @returns {void} 
	 */
	static remove(name = 'DEFAULT', classPrefix = 'ui-pcs-') {

		name = Boolean(name) ? name : 'DEFAULT'
		classPrefix = typeof classPrefix == 'string' ? classPrefix.trim() : 'ui-pcs-'

		let currentActive = document.querySelector(`.${classPrefix}global-loader[data-name="${name}"]`)

		if (currentActive instanceof HTMLElement) {
			currentActive.remove()
		}

	}

	/**
	 * Obtiene el cargador activo con el nombre especificado
	 * 
	 * @param {String} [name='DEFAULT'] Nombre del cargador
	 * @param {String} [classPrefix='ui-pcs-'] Prefijo de la clase
	 * @returns {GenericLoaderElement|null} Cargador activo
	 */
	static currentActive(name = 'DEFAULT', classPrefix = 'ui-pcs-') {
		name = Boolean(name) ? name : 'DEFAULT'
		classPrefix = typeof classPrefix == 'string' ? classPrefix.trim() : 'ui-pcs-'
		return document.querySelector(`.${classPrefix}global-loader[data-name="${name}"]`)
	}
}

//Registrar el elemento personalizado
customElements.define('generic-loader-element', GenericLoaderElement)