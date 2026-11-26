//El cargador por zona (H513): el atenuador del tema sobre la parte que se recarga, no la pantalla entera. Expresiones y no
//declaraciones, como GenericLoaderElement.js: una segunda carga no pisa las funciones de la primera.

/**
 * Cubre una zona con el atenuador del tema mientras se recarga. El indicador va al centro de la parte VISIBLE de la zona
 * y se recoloca al desplazar o redimensionar: en una zona más alta que la ventana, se sigue viendo.
 * Dos llamadas seguidas dejan un solo atenuador. No bloquea nada fuera de la zona.
 *
 * @param {HTMLElement|String|JQuery} target La zona: un elemento, un selector o un jQuery (se toma el primero)
 * @param {Object} [options={}]
 * @param {String} [options.text] Lo que oye un lector de pantalla (por omisión, «Cargando…»)
 * @returns {HTMLElement|null} El atenuador, o null si la zona no existe
 */
window.showZoneLoader = window.showZoneLoader || function (target, options = {}) {

	const zone = window.zoneLoaderElementOf(target)
	if (zone === null) {
		return null
	}

	const current = zone.zoneLoaderState
	if (typeof current == 'object' && current !== null) {
		return current.dimmer
	}

	options = typeof options == 'object' && options !== null ? options : {}
	const text = typeof options.text == 'string' && options.text.trim() !== ''
		? options.text
		: (typeof _i18n == 'function' ? _i18n('zoneLoader', 'Cargando…') : 'Cargando…')

	//Oscuro como en GenericLoaderElement: «dark», o «auto» con el sistema en oscuro. El atenuador `inverted` del tema es
	//blanco también en oscuro; sin `inverted`, oscuro.
	const theme = document.documentElement.getAttribute('data-theme')
	const darkSystem = typeof window.matchMedia == 'function' && window.matchMedia('(prefers-color-scheme: dark)').matches
	const dark = theme === 'dark' || (theme === 'auto' && darkSystem)

	const dimmer = document.createElement('div')
	dimmer.className = dark ? 'ui active dimmer' : 'ui active inverted dimmer'
	dimmer.setAttribute('data-zone-loader', '')

	const loader = document.createElement('div')
	loader.className = 'ui loader'
	loader.setAttribute('role', 'status')
	loader.setAttribute('aria-live', 'polite')

	//Solo para lectores de pantalla: el tema no trae una clase para esto.
	const label = document.createElement('span')
	label.textContent = text
	Object.assign(label.style, {
		position: 'absolute', width: '1px', height: '1px', margin: '-1px', padding: '0', overflow: 'hidden',
		clipPath: 'inset(50%)', whiteSpace: 'nowrap', border: '0',
	})
	loader.appendChild(label)
	dimmer.appendChild(loader)

	const addedDimmable = !zone.classList.contains('dimmable')
	zone.classList.add('dimmable', 'dimmed')
	zone.setAttribute('aria-busy', 'true')
	zone.appendChild(dimmer)

	//El centro de la intersección de la zona con la ventana, en coordenadas del atenuador.
	let frame = null
	const place = () => {
		frame = null
		const area = zone.getBoundingClientRect()
		const top = Math.max(area.top, 0)
		const bottom = Math.min(area.bottom, window.innerHeight)
		const left = Math.max(area.left, 0)
		const right = Math.min(area.right, window.innerWidth)
		if (bottom <= top || right <= left) {
			return
		}
		const box = dimmer.getBoundingClientRect()
		const centerY = (top + bottom) / 2 - box.top
		const centerX = (left + right) / 2 - box.left
		loader.style.top = `${centerY}px`
		loader.style.left = `${centerX}px`
		//El tema puede trasladar el indicador: se corrige con lo que de verdad ocupa.
		const drawn = loader.getBoundingClientRect()
		if (drawn.width > 0 && drawn.height > 0) {
			const offsetY = (top + bottom) / 2 - (drawn.top + drawn.height / 2)
			const offsetX = (left + right) / 2 - (drawn.left + drawn.width / 2)
			loader.style.top = `${centerY + offsetY}px`
			loader.style.left = `${centerX + offsetX}px`
		}
	}
	const schedule = () => {
		if (frame === null) {
			frame = window.requestAnimationFrame(place)
		}
	}

	window.addEventListener('scroll', schedule, { passive: true, capture: true })
	window.addEventListener('resize', schedule, { passive: true })
	//La zona cambia de tamaño o pasa de oculta a visible (una pestaña) sin que la ventana se mueva.
	const observer = typeof ResizeObserver == 'function' ? new ResizeObserver(schedule) : null
	if (observer !== null) {
		observer.observe(zone)
	}
	place()

	zone.zoneLoaderState = {
		dimmer,
		addedDimmable,
		release: () => {
			window.removeEventListener('scroll', schedule, { capture: true })
			window.removeEventListener('resize', schedule)
			if (observer !== null) {
				observer.disconnect()
			}
			if (frame !== null) {
				window.cancelAnimationFrame(frame)
				frame = null
			}
		},
	}

	return dimmer
}

/**
 * Quita el cargador de una zona. Sin cargador, no hace nada.
 *
 * @param {HTMLElement|String|JQuery} target
 * @returns {void}
 */
window.removeZoneLoader = window.removeZoneLoader || function (target) {

	const zone = window.zoneLoaderElementOf(target)
	if (zone === null) {
		return
	}

	const state = zone.zoneLoaderState
	if (typeof state == 'object' && state !== null) {
		state.release()
		state.dimmer.remove()
		if (state.addedDimmable) {
			zone.classList.remove('dimmable')
		}
		delete zone.zoneLoaderState
	}
	zone.querySelectorAll(':scope > [data-zone-loader]').forEach((dimmer) => dimmer.remove())
	zone.classList.remove('dimmed')
	zone.removeAttribute('aria-busy')
}

/**
 * La zona de las dos funciones: un elemento, el primero de un selector o el primero de un jQuery.
 *
 * @param {HTMLElement|String|JQuery} target
 * @returns {HTMLElement|null}
 */
window.zoneLoaderElementOf = window.zoneLoaderElementOf || function (target) {
	if (target instanceof HTMLElement) {
		return target
	}
	if (typeof target == 'string') {
		const found = target.trim() !== '' ? document.querySelector(target) : null
		return found instanceof HTMLElement ? found : null
	}
	if (target !== null && typeof target == 'object' && typeof target.get == 'function' && typeof target.jquery == 'string') {
		const first = target.get(0)
		return first instanceof HTMLElement ? first : null
	}
	return null
}
