//Expresión y no declaración: una segunda carga (el paquete y la suelta, pcsphp-loader.js) no redeclara la clase.
/**
 * Clase que representa un elemento de cargador genérico y expone la API para gestionar cargadores genéricos
 * Hereda de HTMLElement y configura todos los elementos del cargador
 */
window.GenericLoaderElement = window.GenericLoaderElement || customElements.get('generic-loader-element') || class GenericLoaderElement extends HTMLElement {

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
		this.logoLoader = document.createElement('div')
		this.loaderElement = document.createElement('div')
		this.textMessage = document.createElement('div')
		this.progressContainer = document.createElement('div')

		this.configureElement()

	}

	connectedCallback() {
		this.screenWatchers?.connect()
	}

	disconnectedCallback() {
		this.screenWatchers?.disconnect()
	}

	/**
	 * Actualiza el texto del mensaje del cargador
	 * 
	 * @param {String} text Texto del mensaje
	 * @returns {GenericLoaderElement} Instancia del cargador
	 */
	updateTextMessage(text) {
		//Solo el texto: la región (role="status") ya está en la caja desde que se creó, y un lector anuncia el cambio.
		if (this.textMessage instanceof HTMLElement && typeof text == 'string') {
			this.textMessage.innerHTML = text
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
		//Oculto con opacidad para no mover la caja; aria-hidden, para que un lector de pantalla no lea «null%».
		const handleVisibility = (progress, percent) => {
			if (percent === null) {
				progress.setAttribute('style', 'opacity: 0 !important;')
				progress.setAttribute('aria-hidden', 'true')
			} else {
				progress.removeAttribute('style')
				progress.removeAttribute('aria-hidden')
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
		this.logoLoader.classList.add(`${this.classPrefix}logo`)
		this.loaderElement.classList.add(`${this.classPrefix}loader`)
		this.textMessage.classList.add(`${this.classPrefix}text-message`)
		//Solo el mensaje se anuncia (role="status" ya es aria-live="polite"; el foco no cambia). El porcentaje y lo que se
		//añada a la caja (los botones del área de pruebas) quedan fuera: cada paso del porcentaje no es un aviso.
		this.textMessage.setAttribute('role', 'status')
		this.progressContainer.classList.add(`${this.classPrefix}progress-container`)

		//Las imágenes salen de los `data-loader-*` del <html> (SettingsController::loaderHtmlAttributes()): sin ellos (un clon
		//con su propia cabecera), las rutas fijas de siempre. Con oscura, las dos y helpers.scss enseña una según el tema.
		const root = document.documentElement
		//Los interruptores de «Qué enseña el cargador» (`data-loader-<capa>-enabled`): solo un «0» apaga; sin el atributo, encendida.
		const layerOff = (layer) => root.getAttribute(`data-loader-${layer}-enabled`) === '0'
		const brandImages = (name, fallback = null) => {
			//Apagada no se pinta ni se descarga: la imagen guardada sigue ahí.
			if (layerOff(name)) {
				return []
			}
			const source = (root.getAttribute(`data-loader-${name}`) ?? '').trim()
			//Con el atributo y vacío, la capa apagada; sin el atributo, la ruta fija de siempre (sin ruta fija, nada).
			if (source === '' && (root.hasAttribute(`data-loader-${name}`) || fallback === null)) {
				return []
			}
			const dark = source !== '' ? (root.getAttribute(`data-loader-${name}-dark`) ?? '').trim() : ''
			const invert = source !== '' && root.getAttribute(`data-loader-${name}-invert`) === '1'
			const images = [[source !== '' ? source : fallback, dark !== '' ? 'brand-image-light' : (invert ? 'invert-on-dark' : '')]]
			if (dark !== '') {
				images.push([dark, 'brand-image-dark'])
			}
			return images.map(([path, className]) => {
				const image = new Image()
				image.alt = ''
				if (className !== '') {
					image.classList.add(className)
				}
				const loaded = new Promise((resolve, reject) => {
					image.onload = () => resolve()
					image.onerror = () => reject()
				})
				image.src = new URL(path, pcsphpGlobals.baseURL).href
				return { image, loaded }
			})
		}
		//Configurar logo
		const logoImages = brandImages('logo', 'statics/images/loader-logo.png')
		logoImages.forEach(({ image }) => this.logoLoader.appendChild(image))
		if (logoImages.length === 0) {
			this.logoLoader.hidden = true
		}

		//Movimiento reducido (la preferencia del panel o la del sistema): sin animación ni aro y queda el logo quieto; sin
		//logo, la animación se queda para que el cargador no salga vacío (un SVG respeta además la preferencia del sistema).
		const motionReduced = root.dataset.motion === 'reduce' || (typeof window.matchMedia == 'function' && window.matchMedia('(prefers-reduced-motion: reduce)').matches)
		const reduceMotion = logoImages.length > 0 && motionReduced

		//Configurar loader
		const loaderImages = reduceMotion ? [] : brandImages('animation', 'statics/images/loader-animation.gif')
		loaderImages.forEach(({ image }) => this.loaderElement.appendChild(image))
		if (reduceMotion) {
			this.loaderElement.classList.add(`${this.classPrefix}loader-reduced`)
		}

		//La pantalla de carga (H417), al fondo y solo con sus `data-loader-screen*` (de fábrica, las SVG animadas). Se descarga
		//UNA, la del modo y la orientación de ahora (hasta 3 MB cada una), y se cambia al girar o al cambiar de modo.
		const screenSource = (name) => {
			const light = (root.getAttribute(`data-loader-${name}`) ?? '').trim()
			if (light === '') {
				return null
			}
			const dark = (root.getAttribute(`data-loader-${name}-dark`) ?? '').trim()
			return { light, dark, invert: dark === '' && root.getAttribute(`data-loader-${name}-invert`) === '1' }
		}
		const screens = layerOff('screen') ? { desktop: null, mobile: null } : { desktop: screenSource('screen'), mobile: screenSource('screen-mobile') }
		const hasScreen = screens.desktop !== null || screens.mobile !== null
		const screenLayer = document.createElement('div')
		screenLayer.classList.add(`${this.classPrefix}screen`)
		const media = (query) => typeof window.matchMedia == 'function' ? window.matchMedia(query) : null
		const portraitQuery = media('(orientation: portrait)')
		const darkQuery = media('(prefers-color-scheme: dark)')
		//Oscuro como en helpers.scss: «dark», o «auto» con el sistema en oscuro; cualquier otro valor, claro.
		const isDark = () => {
			const theme = root.getAttribute('data-theme')
			return theme === 'dark' || (theme === 'auto' && darkQuery !== null && darkQuery.matches)
		}
		const failedScreens = new Set()
		//En orden: la orientación de ahora y, sin ella, la otra (recortada); en cada una, la del modo y luego la clara.
		const screenCandidates = () => {
			const order = portraitQuery !== null && portraitQuery.matches ? ['mobile', 'desktop'] : ['desktop', 'mobile']
			const dark = isDark()
			const candidates = []
			order.forEach((kind) => {
				const source = screens[kind]
				if (source === null) {
					return
				}
				if (dark && source.dark !== '') {
					candidates.push({ kind, path: source.dark, invert: false })
				}
				candidates.push({ kind, path: source.light, invert: source.invert })
			})
			return candidates.filter(({ path }) => !failedScreens.has(path))
		}
		let screenCurrent = null
		let screenRequest = 0
		let screenFallback = false
		const renderScreen = () => {
			const next = screenCandidates()[0] ?? null
			const path = next !== null ? next.path : null
			if (path === screenCurrent) {
				return
			}
			screenCurrent = path
			const request = ++screenRequest
			if (next === null) {
				//Ninguna buena en este modo: como sin pantalla, pero la capa se queda en el DOM para que la del otro modo cargue en ella.
				screenLayer.replaceChildren()
				screenLayer.hidden = true
				this.classList.remove(`${this.classPrefix}with-screen`)
				if (loaderImages.length === 0 && logoImages.length === 0) {
					this.loaderElement.setAttribute('class', `${this.classPrefix}loader-fallback`)
					screenFallback = true
				}
				return
			}
			//Lo que deshace el caso de arriba cuando por fin carga una: la capa a la vista y, si se puso por ella, fuera el aro.
			const showScreen = () => {
				screenLayer.hidden = false
				this.classList.add(`${this.classPrefix}with-screen`)
				if (screenFallback) {
					this.loaderElement.setAttribute('class', `${this.classPrefix}loader`)
					screenFallback = false
				}
			}
			const image = new Image()
			image.alt = ''
			image.classList.add(`${this.classPrefix}screen-${next.kind}`)
			if (next.invert) {
				image.classList.add('invert-on-dark')
			}
			//La anterior se queda hasta que la nueva carga: al girar no hay un parpadeo en blanco.
			image.onload = () => {
				if (request !== screenRequest) {
					return
				}
				showScreen()
				if (!motionReduced) {
					screenLayer.replaceChildren(image)
					return
				}
				//Con movimiento reducido, el primer fotograma: un <canvas> pinta el fotograma por omisión de un GIF o un
				//WebP animado (el primero). La imagen ni entra en la página, para que no llegue a moverse.
				const canvas = document.createElement('canvas')
				canvas.width = image.naturalWidth || window.innerWidth
				canvas.height = image.naturalHeight || window.innerHeight
				canvas.className = image.className
				canvas.getContext('2d').drawImage(image, 0, 0, canvas.width, canvas.height)
				screenLayer.replaceChildren(canvas)
			}
			//La que falla no se pinta rota: la siguiente candidata (la oscura rota deja la clara).
			image.onerror = () => {
				failedScreens.add(next.path)
				if (request === screenRequest) {
					renderScreen()
				}
			}
			image.src = new URL(next.path, pcsphpGlobals.baseURL).href
		}
		if (hasScreen) {
			this.classList.add(`${this.classPrefix}with-screen`)
			this.appendChild(screenLayer)
			renderScreen()
			//El modo cambia por el atributo del <html> (el selector de modo, theme-toggle.js, el área de pruebas) o, en
			//«auto», por el sistema; la orientación, por su consulta. connectedCallback() los engancha y disconnectedCallback() los suelta.
			const themeObserver = new MutationObserver(renderScreen)
			this.screenWatchers = {
				connect: () => {
					themeObserver.observe(root, { attributes: true, attributeFilter: ['data-theme'] })
					portraitQuery?.addEventListener?.('change', renderScreen)
					darkQuery?.addEventListener?.('change', renderScreen)
					renderScreen()
				},
				disconnect: () => {
					themeObserver.disconnect()
					portraitQuery?.removeEventListener?.('change', renderScreen)
					darkQuery?.removeEventListener?.('change', renderScreen)
				},
			}
		}

		//Todo apagado (animación y logo vacíos a propósito, y sin pantalla): el aro de CSS.
		if (loaderImages.length === 0 && logoImages.length === 0 && !hasScreen) {
			this.loaderElement.setAttribute('class', `${this.classPrefix}loader-fallback`)
		}

		//Ensamblar estructura: animación, logo, mensaje y porcentaje, en ese orden también en el DOM (el lector de pantalla lo sigue).
		//El mensaje va SIEMPRE, vacío si no hay: una región que se crea ya con el texto no se anuncia (helpers.scss la oculta vacía).
		this.boxLoader.appendChild(this.loaderElement)
		this.boxLoader.appendChild(this.logoLoader)
		this.textMessage.innerHTML = this.options.textMessage ? this.options.textMessage : ''
		this.boxLoader.appendChild(this.textMessage)
		this.boxLoader.appendChild(this.progressContainer)
		this.appendChild(this.boxLoader)

		//Verificar carga del logo: una que falla no se pinta rota; si fallan todas, sin logo.
		logoImages.forEach(({ image, loaded }) => loaded.catch(() => image.remove()))
		Promise.allSettled(logoImages.map(({ loaded }) => loaded)).then((results) => {
			if (results.every((result) => result.status === 'rejected')) {
				this.logoLoader.style.display = 'none'
			}
		})

		//Verificar carga del loader: si una falla, el aro (nunca una animación rota en un tema y no en el otro).
		Promise.all(loaderImages.map(({ loaded }) => loaded)).catch(() => {
			loaderImages.forEach(({ image }) => image.remove())
			this.loaderElement.setAttribute('class', `${this.classPrefix}loader-fallback`)
		})

		//Agregar progreso si se requiere		
		this.addProgress(this.withProgress ? 0 : null, this.progressContainer)

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

//Registrar el elemento personalizado, una sola vez aunque el archivo se cargue dos
if (!customElements.get('generic-loader-element')) {
	customElements.define('generic-loader-element', window.GenericLoaderElement)
}
