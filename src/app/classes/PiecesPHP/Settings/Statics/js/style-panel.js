window.addEventListener('load', () => {

	const view = document.querySelector('[data-style-panel]')
	if (view === null || typeof $ === 'undefined') {
		return
	}

	//La muestra no carga JS propio: la barra de progreso la inicia esta página sobre su documento (theme-preview.js escala).
	const previews = pcsThemePreviews(view.querySelectorAll('iframe[data-style-preview]'), {
		onReady: (frame, doc) => $(doc.querySelector('[data-style-preview-content]')).find('.ui.progress').progress(),
	})

	//La pestaña activa vive en la URL (#colors, #typography, #accessibility) y se recuerda; la dirección manda.
	pcsSettingsTabs($(view).find('[data-style-panel-tabs] [data-tab]'), 'pcs-style-panel-tab', () => previews.fitAll())

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
