/**
 * Las pestañas de una pantalla de Configuración con la activa en la dirección (#nombre), recordada en localStorage: la
 * dirección manda sobre lo recordado. Las usan «Estilo del panel» e «Imágenes de marca».
 *
 * @param {JQuery} items Los `.item[data-tab]` del menú
 * @param {String} storageKey Dónde se recuerda la última
 * @param {Function} [onVisible] Recibe el nombre de la pestaña que se enseña
 * @returns {JQuery} Las pestañas de Fomantic
 */
function pcsSettingsTabs(items, storageKey, onVisible = () => { }) {
	const remember = (tabName) => {
		try {
			window.localStorage.setItem(storageKey, tabName)
		} catch (error) {
			//Sin almacenamiento (privado, bloqueado): la pestaña solo vive en la URL.
		}
	}
	const recalled = () => {
		try {
			return resolve(String(window.localStorage.getItem(storageKey) || ''))
		} catch (error) {
			return ''
		}
	}
	//Los fragmentos en español de antes abren su pestaña (y se reescriben al inglés): en desuso hasta la 11.0.0.
	const LEGACY_TABS = { imagenes: 'images', cargador: 'loader', 'vista-previa': 'preview', colores: 'colors', tipografia: 'typography', accesibilidad: 'accessibility' }
	const resolve = (tabName) => Object.prototype.hasOwnProperty.call(LEGACY_TABS, tabName) ? LEGACY_TABS[tabName] : tabName
	const showInURL = (tabName) => {
		//Sobre la dirección de la página: el panel lleva <base href>, y un «#…» suelto se resuelve contra la portada.
		const url = new URL(window.location.href)
		url.hash = tabName
		history.replaceState(null, '', url)
	}
	const exists = (tabName) => tabName !== '' && items.filter('[data-tab="' + CSS.escape(tabName) + '"]').length > 0
	const tabs = items.tab({
		//Los paneles no siempre son hermanos (Estilo del panel tiene dos dentro de su form): 'siblings' dejaría dos activos.
		deactivate: 'all',
		onVisible: function (tabName) {
			showInURL(tabName)
			remember(tabName)
			onVisible(tabName)
		},
	})
	const fromURL = resolve(window.location.hash.replace('#', ''))
	const initialTab = exists(fromURL) ? fromURL : (exists(recalled()) ? recalled() : '')
	if (initialTab !== '') {
		tabs.tab('change tab', initialTab)
		//Si ya estaba activa, onVisible puede no correr: el alias o el recuerdo viejo se reescriben aquí.
		if (initialTab === fromURL) {
			showInURL(initialTab)
		}
		remember(initialTab)
	}
	//Un enlace o el usuario cambian el fragmento sin recargar: la pestaña lo sigue (replaceState de onVisible no lo dispara).
	window.addEventListener('hashchange', () => {
		const fromHash = resolve(window.location.hash.replace('#', ''))
		if (exists(fromHash)) {
			tabs.tab('change tab', fromHash)
			showInURL(fromHash)
		}
	})
	return tabs
}

/**
 * Muestras de páginas en `<iframe srcdoc>`, cada una en su caja (el padre del iframe, `.theme-preview-frame`), escaladas
 * a su columna y nunca por encima de 1. Lo que lee de cada iframe:
 * - `data-preview-width`: el ancho interior; `auto` (por omisión) 1024 si la columna da una escala legible y 320 si no,
 *   `fluid` el de la columna, o un número fijo.
 * - `data-preview-height`: un alto interior fijo (las páginas que miden la ventana, como el menú o el acceso); sin él, el
 *   del contenido hasta el final de `data-preview-fit` (o del cuerpo).
 * - `data-preview-crop`: la caja enseña solo hasta el borde derecho (y, sin alto fijo, el inferior) de ese elemento.
 * - `data-preview-scroll`: ese elemento se enseña desplazado hasta el final (en cada medida: oculto no se desplaza).
 *
 * @param {HTMLIFrameElement[]} frames
 * @param {Object} [options]
 * @param {Boolean} [options.inert] Nada de dentro se puede pulsar ni enfocar
 * @param {Function} [options.onReady] Recibe (iframe, documento) cuando el documento está listo, antes de medir
 * @returns {{fit: Function, fitAll: Function, add: Function}}
 */
function pcsThemePreviews(frames, options = {}) {

	const all = []
	//Los ResizeObserver de cada iframe: al quitarlo de la página (la recarga de la vista previa) se desconectan.
	const observers = new Map()
	//Cada muestra de escritorio es un panel de escritorio: a menos de 768 el tema la apilaría como en el móvil.
	const DESKTOP_WIDTH = 1024
	//La escala mínima legible es 0,40: en una columna más estrecha que DESKTOP_WIDTH × 0,40 va el panel móvil.
	const MIN_LEGIBLE_SCALE = 0.4
	//El min-width del body del tema.
	const MOBILE_WIDTH = 320

	//El ancho de la columna sin su relleno: la caja recortada no puede medirse a sí misma.
	const available = (box) => {
		const column = box.parentElement
		const style = window.getComputedStyle(column)
		return Math.max(0, column.clientWidth - parseFloat(style.paddingLeft || '0') - parseFloat(style.paddingRight || '0'))
	}
	const innerWidth = (frame, box) => {
		const mode = frame.dataset.previewWidth || 'auto'
		const column = available(box)
		if (mode === 'fluid') {
			return Math.max(MOBILE_WIDTH, Math.floor(column))
		}
		const fixed = parseInt(mode, 10)
		if (fixed > 0) {
			return fixed
		}
		return column >= DESKTOP_WIDTH * MIN_LEGIBLE_SCALE ? DESKTOP_WIDTH : MOBILE_WIDTH
	}
	const documentOf = (frame) => {
		const doc = frame.contentDocument
		//El documento inicial de un iframe (about:blank) también está «complete»: solo vale el del srcdoc.
		return doc !== null && doc.URL === 'about:srcdoc' && doc.body !== null ? doc : null
	}

	const fit = (frame) => {
		const doc = documentOf(frame)
		const box = frame.parentElement
		if (doc === null || box === null || available(box) === 0) {
			return
		}
		const width = innerWidth(frame, box)
		if (frame.width !== String(width)) {
			//Otro ancho interior cambia el alto natural: el ResizeObserver del contenido vuelve a llamar a fit().
			frame.width = String(width)
		}
		const fixedHeight = parseInt(frame.dataset.previewHeight || '', 10)
		const crop = frame.dataset.previewCrop ? doc.querySelector(frame.dataset.previewCrop) : null
		const target = crop ?? (frame.dataset.previewFit ? doc.querySelector(frame.dataset.previewFit) : null) ?? doc.body
		const view = doc.defaultView
		let height = fixedHeight
		if (!(fixedHeight > 0)) {
			height = target.getBoundingClientRect().bottom + view.scrollY
			if (crop === null) {
				const style = view.getComputedStyle(doc.body)
				height += parseFloat(style.marginBottom || '0') + parseFloat(style.paddingBottom || '0')
			}
		}
		height = Math.max(1, Math.ceil(height))
		frame.height = String(height)
		const regionWidth = crop !== null ? Math.max(1, Math.ceil(crop.getBoundingClientRect().right + view.scrollX)) : width
		const factor = Math.min(1, available(box) / regionWidth)
		frame.style.transform = `scale(${factor})`
		box.style.width = crop !== null ? `${Math.ceil(regionWidth * factor)}px` : ''
		box.style.height = `${Math.ceil(height * factor)}px`
		const scroller = frame.dataset.previewScroll ? doc.querySelector(frame.dataset.previewScroll) : null
		if (scroller !== null) {
			scroller.scrollTop = scroller.scrollHeight
		}
	}

	const ready = (frame) => {
		const doc = documentOf(frame)
		if (doc === null || frame.dataset.previewReady === 'yes') {
			return
		}
		frame.dataset.previewReady = 'yes'
		if (options.inert === true) {
			doc.body.inert = true
		}
		//Sin JS, el iframe se queda al 100 % sin escalar.
		frame.width = String(innerWidth(frame, frame.parentElement))
		if (typeof options.onReady === 'function') {
			options.onReady(frame, doc)
		}
		if (typeof ResizeObserver === 'function') {
			const watched = (frame.dataset.previewCrop ? doc.querySelector(frame.dataset.previewCrop) : null) ?? (frame.dataset.previewFit ? doc.querySelector(frame.dataset.previewFit) : null) ?? doc.body
			const contentObserver = new ResizeObserver(() => fit(frame))
			contentObserver.observe(watched)
			const columnObserver = new ResizeObserver(() => fit(frame))
			columnObserver.observe(frame.parentElement.parentElement)
			observers.set(frame, [contentObserver, columnObserver])
		}
		fit(frame)
	}

	//Olvida los iframes que ya no están en la página: sus observadores y su sitio en la lista.
	const prune = () => {
		for (let index = all.length - 1; index >= 0; index--) {
			const frame = all[index]
			if (!frame.isConnected) {
				(observers.get(frame) ?? []).forEach((observer) => observer.disconnect())
				observers.delete(frame)
				all.splice(index, 1)
			}
		}
	}

	const add = (newFrames) => {
		prune()
		Array.from(newFrames).forEach((frame) => {
			all.push(frame)
			//Las imágenes del documento cambian su alto al cargar: se vuelve a medir.
			frame.addEventListener('load', () => {
				ready(frame)
				fit(frame)
			})
			ready(frame)
		})
	}
	add(frames)

	return {
		fit: fit,
		fitAll: () => {
			prune()
			all.forEach(fit)
		},
		add: add,
	}
}
