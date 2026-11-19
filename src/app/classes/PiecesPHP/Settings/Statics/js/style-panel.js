window.addEventListener('load', () => {

	const view = document.querySelector('[data-style-panel]')
	if (view === null || typeof $ === 'undefined') {
		return
	}

	//La pestaña activa vive en la URL (#colores, #tipografia, #accesibilidad) y se recuerda; la dirección manda.
	const STORAGE_KEY = 'pcs-style-panel-tab'
	const tabItems = $(view).find('[data-style-panel-tabs] [data-tab]')
	const remember = (tabName) => {
		try {
			window.localStorage.setItem(STORAGE_KEY, tabName)
		} catch (error) {
			//Sin almacenamiento (privado, bloqueado): la pestaña solo vive en la URL.
		}
	}
	const recalled = () => {
		try {
			return String(window.localStorage.getItem(STORAGE_KEY) || '')
		} catch (error) {
			return ''
		}
	}
	const exists = (tabName) => tabName !== '' && tabItems.filter('[data-tab="' + CSS.escape(tabName) + '"]').length > 0

	const frames = Array.from(view.querySelectorAll('iframe[data-style-preview]'))
	//Cada muestra es un panel de escritorio: a menos de 768 el tema la apilaría como en el móvil.
	const DESKTOP_WIDTH = 1024
	//La escala mínima legible es 0,40: en una columna más estrecha que DESKTOP_WIDTH × 0,40 va el panel móvil.
	const MIN_LEGIBLE_SCALE = 0.4
	//El min-width del body del tema.
	const MOBILE_WIDTH = 320
	const innerWidth = (box) => box.clientWidth >= DESKTOP_WIDTH * MIN_LEGIBLE_SCALE ? DESKTOP_WIDTH : MOBILE_WIDTH
	//Escalada al ancho de su columna, nunca por encima de 1; la caja toma el alto escalado y recorta el resto del iframe.
	const scale = (frame) => {
		const box = frame.parentElement
		const width = innerWidth(box)
		if (frame.width !== String(width)) {
			//Otro ancho interior cambia el alto natural: el ResizeObserver del contenido vuelve a llamar a fit().
			frame.width = String(width)
		}
		const factor = Math.min(1, box.clientWidth / width)
		frame.style.transform = `scale(${factor})`
		box.style.height = `${Math.ceil(Number(frame.height) * factor)}px`
	}
	//Al alto de su contenido, sin barra interior: con la fuente, el tamaño, el radio o el espaciado cambia el alto.
	const fit = (frame) => {
		const doc = frame.contentDocument
		const content = doc !== null ? doc.querySelector('[data-style-preview-content]') : null
		if (content === null) {
			return
		}
		const style = doc.defaultView.getComputedStyle(doc.body)
		const bottom = content.getBoundingClientRect().bottom + doc.defaultView.scrollY + parseFloat(style.marginBottom || '0') + parseFloat(style.paddingBottom || '0')
		frame.height = String(Math.ceil(bottom))
		scale(frame)
	}
	frames.forEach((frame) => {
		const doc = frame.contentDocument
		const content = doc !== null ? doc.querySelector('[data-style-preview-content]') : null
		if (content === null) {
			return
		}
		//Sin JS, el iframe se queda al 100 % sin escalar.
		const box = frame.parentElement
		frame.width = String(innerWidth(box))
		//La muestra no carga JS propio: la barra de progreso la inicia esta página sobre su documento.
		$(content).find('.ui.progress').progress()
		if (typeof ResizeObserver === 'function') {
			new ResizeObserver(() => fit(frame)).observe(content)
			new ResizeObserver(() => scale(frame)).observe(box)
		}
		fit(frame)
	})

	const tabs = tabItems.tab({
		//Las de colores van dentro de su form y la de accesibilidad fuera: no son hermanas, y 'siblings' dejaría dos activas.
		deactivate: 'all',
		onVisible: function (tabName) {
			//Sobre la dirección de la página: el panel lleva <base href>, y un «#…» suelto se resuelve contra la portada.
			const url = new URL(window.location.href)
			url.hash = tabName
			history.replaceState(null, '', url)
			remember(tabName)
			frames.forEach(fit)
		},
	})
	const fromURL = window.location.hash.replace('#', '')
	const initialTab = exists(fromURL) ? fromURL : (exists(recalled()) ? recalled() : '')
	if (initialTab !== '') {
		tabs.tab('change tab', initialTab)
	}

	//Restablecer: lo de fábrica de la pestaña (y lo guardado del resto del formulario) por la ruta de guardado de siempre.
	const texts = {
		intro: view.getAttribute('data-reset-intro') || '',
		confirm: view.getAttribute('data-reset-confirm') || '',
		cancel: view.getAttribute('data-reset-cancel') || '',
		lost: view.getAttribute('data-reset-lost') || '',
		error: view.getAttribute('data-reset-error') || '',
	}
	$(view).on('click', '[style-reset]', function () {
		let reset = null
		try {
			reset = JSON.parse(this.getAttribute('style-reset') || '')
		} catch (error) {
			reset = null
		}
		const form = $(this).closest('form')
		if (reset === null || typeof reset.values !== 'object' || form.length === 0) {
			return
		}
		const title = String(reset.title || '')
		const list = $('<ul class="ui list"></ul>').append((Array.isArray(reset.items) ? reset.items : []).map((item) => $('<li></li>').text(String(item))))
		const message = $('<div></div>').append($('<p></p>').text(texts.intro), list, $('<p></p>').text(texts.lost)).html()
		confirmAction({
			title: title,
			message: message,
			confirmText: texts.confirm,
			cancelText: texts.cancel,
			confirmClass: 'red',
		}).then((confirmed) => {
			if (!confirmed) {
				return
			}
			const formData = new FormData()
			Object.keys(reset.values).forEach((name) => formData.set(name, String(reset.values[name])))
			showGenericLoader('style-panel-reset')
			postRequest(form.attr('action'), formData).done((response) => {
				if (response && response.success === true) {
					//La URL ya lleva la pestaña: recargar vuelve a ella con lo guardado.
					window.location.reload()
					return
				}
				removeGenericLoader('style-panel-reset')
				errorMessage(title, response && typeof response.message === 'string' && response.message !== '' ? response.message : texts.error)
			}).fail(() => {
				removeGenericLoader('style-panel-reset')
				errorMessage(title, texts.error)
			})
		})
	})

})
