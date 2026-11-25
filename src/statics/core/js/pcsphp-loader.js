//El cargador suelto, sin jQuery ni el resto del panel: va detrás de helpers-lib/GenericLoaderElement.js y, si falta, lo pide él.
(function () {
	//GenericLoaderElement.js resuelve las imágenes contra pcsphpGlobals.baseURL, que fuera del panel no existe.
	window.pcsphpGlobals = window.pcsphpGlobals || {}
	if (!window.pcsphpGlobals.baseURL) {
		window.pcsphpGlobals.baseURL = document.baseURI
	}

	const ready = () => typeof window.GenericLoaderElement === 'function'
	const normalizedName = (name) => Boolean(name) ? name : 'DEFAULT'
	//Lo pedido antes de que llegue el elemento: se muestra al cargar, salvo que lo hayan quitado antes.
	let pending = null

	const own = document.currentScript
	if (!ready() && own !== null && own.src) {
		const script = document.createElement('script')
		script.src = new URL('helpers-lib/GenericLoaderElement.js', own.src).href
		script.addEventListener('load', () => {
			if (pending !== null && ready()) {
				const args = pending
				pending = null
				window.GenericLoaderElement.show(...args)
			}
		})
		document.head.appendChild(script)
	}

	//La misma firma que helpers.js:1064; si el panel ya las definió, mandan las suyas.
	if (typeof window.showGenericLoader !== 'function') {
		/**
		 * Muestra un modal de carga en el body
		 *
		 * @param {String} [name='DEFAULT'] Nombre del loader
		 * @param {String} [classPrefix='ui-pcs-'] Prefijo de la clase
		 * @param {Boolean} [withProgress=false] Indica si se debe mostrar el progreso
		 * @param {Object} [moreOptions={}] Opciones adicionales
		 * @returns {GenericLoaderElement|null} Elemento del loader; null mientras el elemento no ha cargado
		 */
		window.showGenericLoader = function (name = null, classPrefix = null, withProgress = false, moreOptions = {}) {
			if (ready()) {
				return window.GenericLoaderElement.show(name, classPrefix, withProgress, moreOptions)
			}
			pending = [name, classPrefix, withProgress, moreOptions]
			return null
		}
	}

	if (typeof window.removeGenericLoader !== 'function') {
		/**
		 * Oculta un modal de carga en el body
		 *
		 * @param {String} [name='DEFAULT'] Nombre del loader
		 * @param {String} [classPrefix='ui-pcs-'] Prefijo de la clase
		 * @returns {void}
		 */
		window.removeGenericLoader = function (name = null, classPrefix = null) {
			if (ready()) {
				window.GenericLoaderElement.remove(name, classPrefix)
			} else if (pending !== null && normalizedName(pending[0]) === normalizedName(name)) {
				pending = null
			}
		}
	}
})()
