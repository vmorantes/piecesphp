window.addEventListener('load', function () {

	const catalog = document.querySelector('[data-mail-catalog]')
	if (catalog === null) {
		return
	}
	const sendURL = catalog.getAttribute('data-send-test-url')
	const layout = catalog.querySelector('[data-mail-catalog-layout]')
	const sticky = catalog.querySelector('[data-mail-catalog-sticky]')
	const cards = Array.from(catalog.querySelectorAll('[data-mail-catalog-card]'))
	const links = Array.from(catalog.querySelectorAll('[data-mail-catalog-link]'))
	const jump = catalog.querySelector('[data-mail-catalog-jump]')

	//Cada vista previa toma el alto de su correo: el desplazamiento es el de la página, no el del marco (#2449). Se puede
	//medir porque el marco es del mismo origen (allow-same-origin); sin allow-scripts, el correo no ejecuta nada.
	const fitPreview = function (frame) {
		const doc = frame.contentDocument
		if (doc === null || doc.documentElement === null) {
			return
		}
		//Más el borde del marco: con border-box, el alto lo incluye. Se mide encogido: si no, el documento del marco nunca
		//es más bajo que el propio marco y no encoge.
		const border = frame.offsetHeight - frame.clientHeight
		frame.style.height = border + 'px'
		frame.style.height = (Math.max(doc.documentElement.scrollHeight, doc.body !== null ? doc.body.scrollHeight : 0) + border) + 'px'
	}
	catalog.querySelectorAll('[data-mail-catalog-preview]').forEach(function (frame) {
		frame.addEventListener('load', function () {
			fitPreview(frame)
		})
		fitPreview(frame)
	})
	window.addEventListener('resize', function () {
		catalog.querySelectorAll('[data-mail-catalog-preview]').forEach(fitPreview)
	})

	const desktop = window.matchMedia('(min-width: 992px)')
	const barHeight = function () {
		const bar = document.querySelector('header.bar')
		return bar !== null ? bar.getBoundingClientRect().height : 0
	}
	//Lo que tapa el borde de arriba: la barra superior del panel y, por debajo de escritorio, la propia navegación fija.
	const topOffset = function () {
		let offset = barHeight()
		if (!desktop.matches && sticky !== null) {
			offset += sticky.parentElement.getBoundingClientRect().height
		}
		return offset + 16
	}
	//En escritorio, el módulo sticky del tema dentro de la rejilla; por debajo, la columna apilada se fija con CSS
	//(position: sticky), porque el módulo no la sigue cuando la rejilla se apila.
	let stickyOn = false
	const layoutChanged = function () {
		document.documentElement.style.setProperty('--mail-catalog-bar-height', barHeight() + 'px')
		document.documentElement.style.setProperty('--mail-catalog-anchor-offset', topOffset() + 'px')
		if (sticky === null || layout === null || typeof $ === 'undefined' || typeof $.fn.sticky !== 'function') {
			return
		}
		if (desktop.matches && !stickyOn) {
			$(sticky).sticky({ context: layout, offset: barHeight() + 16 })
			stickyOn = true
		} else if (!desktop.matches && stickyOn) {
			$(sticky).sticky('destroy')
			sticky.removeAttribute('style')
			stickyOn = false
		}
	}
	layoutChanged()
	window.addEventListener('resize', layoutChanged)

	const goTo = function (id, smooth) {
		const card = document.getElementById(id)
		if (card === null) {
			return
		}
		const top = card.getBoundingClientRect().top + window.scrollY - topOffset()
		window.scrollTo({ top: top, behavior: smooth ? 'smooth' : 'auto' })
		history.replaceState(null, '', '#' + id)
	}
	links.forEach(function (link) {
		link.addEventListener('click', function (event) {
			event.preventDefault()
			goTo(link.getAttribute('href').slice(1), true)
		})
	})
	if (jump !== null) {
		jump.addEventListener('change', function () {
			goTo(jump.value, true)
		})
	}

	//La tarjeta que se está viendo: la última cuyo borde de arriba ya pasó bajo la barra; al tocar fondo, la última, que
	//quizá nunca llegue arriba.
	const markCurrent = function () {
		const limit = topOffset() + 8
		let current = cards.length > 0 ? cards[0] : null
		cards.forEach(function (card) {
			if (card.getBoundingClientRect().top <= limit) {
				current = card
			}
		})
		if (cards.length > 0 && window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 2) {
			current = cards[cards.length - 1]
		}
		if (current === null) {
			return
		}
		const key = current.getAttribute('data-mail-catalog-card')
		links.forEach(function (link) {
			const active = link.getAttribute('data-mail-catalog-link') === key
			link.classList.toggle('active', active)
			if (active) {
				link.setAttribute('aria-current', 'true')
			} else {
				link.removeAttribute('aria-current')
			}
		})
		if (jump !== null && jump.value !== current.id) {
			jump.value = current.id
		}
	}
	let pending = false
	window.addEventListener('scroll', function () {
		if (!pending) {
			pending = true
			window.requestAnimationFrame(function () {
				pending = false
				markCurrent()
			})
		}
	}, { passive: true })
	markCurrent()
	if (location.hash.length > 1) {
		goTo(location.hash.slice(1), false)
	}

	//«Enviar prueba»: con el destinatario del campo; el servidor vuelve a comprobar que la entrega está retenida.
	const to = catalog.querySelector('[data-mail-catalog-to]')
	catalog.querySelectorAll('[data-mail-catalog-send]').forEach(function (button) {
		button.addEventListener('click', function (event) {
			event.preventDefault()
			if (button.classList.contains('disabled')) {
				return
			}
			const body = new FormData()
			body.set('key', button.getAttribute('data-mail-catalog-send'))
			body.set('to', to !== null ? to.value : '')
			button.classList.add('loading', 'disabled')
			button.setAttribute('aria-disabled', 'true')
			fetch(sendURL, { method: 'POST', body: body, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
				.then(function (response) {
					return response.json().then(function (data) {
						if (!response.ok || data.success !== true) {
							throw new Error(typeof data.error === 'string' ? data.error : String(response.status))
						}
						return data
					})
				})
				.then(function (data) {
					successMessage(_i18n('mail-catalog', 'Enviar prueba'), data.message)
				})
				.catch(function (error) {
					errorMessage(_i18n('mail-catalog', 'Enviar prueba'), error.message)
				})
				.finally(function () {
					button.classList.remove('loading', 'disabled')
					button.removeAttribute('aria-disabled')
				})
		})
	})

})
