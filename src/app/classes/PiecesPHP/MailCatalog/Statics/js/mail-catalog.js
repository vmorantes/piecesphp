window.addEventListener('load', function () {

	const catalog = document.querySelector('[data-mail-catalog]')
	if (catalog === null) {
		return
	}
	const sendURL = catalog.getAttribute('data-send-test-url')

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
