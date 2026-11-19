showGenericLoader('logos-favicon')
window.addEventListener('load', () => {

	new ContentImageEditor()

	//El texto sobre el patrocinador: un campo por idioma activo, en un solo formulario y sin recorte.
	genericFormHandler(`form[partners-text-form]`)

	//«En modo oscuro» se guarda al cambiarlo; la vista oscura y su «Descargar», lo de SettingsController::brandImageDarkView().
	const action = document.querySelector('section.logos-favicons-view')?.dataset.action
	document.querySelectorAll('[data-brand-dark-mode]').forEach((select) => {
		const card = select.closest('article[data-image-card]')
		const preview = card.querySelector('[data-brand-dark-preview]')
		const previewImage = preview.querySelector('.checkered.image img')
		const previewEdit = preview.querySelector('[data-image-card-edit]')
		const upload = preview.querySelector('[data-brand-dark-upload]')
		const hint = card.querySelector('[data-brand-dark-invert-hint]')
		const darkEmpty = preview.querySelector('[data-brand-dark-empty]')
		//Solo la imagen del botón: el damero vacío (`[data-image-card-empty]`) también lleva un <img>, transparente.
		const lightSource = () => Array.from(card.querySelectorAll('.checkered.image [data-image-card-edit] img')).find((image) => image.closest('[data-image-card]') === card)?.getAttribute('src') ?? ''
		const reflect = () => {
			const mode = select.value
			const shown = mode === 'image' && preview.dataset.darkImage === '' ? preview.dataset.darkFallbackMode : mode
			const source = shown === 'image' ? preview.dataset.darkImage : lightSource()
			if (source !== '') {
				previewImage.src = source
			}
			previewImage.hidden = source === ''
			if (darkEmpty !== null) {
				darkEmpty.hidden = source !== ''
			}
			previewImage.classList.toggle('content-image-card-dark-invert', shown === 'invert')
			const download = Array.from(preview.querySelectorAll('[data-image-card-download]')).find((link) => link.closest('[data-image-card]') === preview)
			if (download !== undefined && source !== '') {
				download.href = source
			}
			upload.hidden = mode !== 'image'
			previewEdit.disabled = mode !== 'image'
			hint.hidden = mode !== 'invert'
		}
		const save = () => {
			select.dataset.saved = 'yes'
			const formData = new FormData()
			formData.set('darkMode', select.dataset.brandDarkMode)
			formData.set('mode', select.value)
			postRequest(action, formData).done((response) => {
				if (!response.success) {
					errorMessage(response.name, response.message)
				}
			}).fail(() => errorMessage('', _i18n('errors', 'unexpected_error')))
		}
		//Sin modo guardado manda la imagen: una propia toma el que le corresponde sin la de fábrica.
		card.addEventListener('image-card-replaced', () => {
			//La oscura de fábrica solo acompaña a la clara de fábrica.
			if (preview.dataset.darkImageFactory === 'yes') {
				preview.dataset.darkImage = ''
				preview.dataset.darkImageFactory = 'no'
			}
			//Y la vuelta a invertir de «Otra imagen» sin oscura, también: la clara ya no es la de fábrica.
			preview.dataset.darkFallbackMode = 'same'
			if (select.dataset.saved !== 'yes') {
				$(select).dropdown('set selected', select.dataset.onReplace, true)
			}
			reflect()
		})
		//Subir una oscura es elegir «Otra imagen»: el servidor ya guardó el modo en la misma petición.
		preview.addEventListener('image-card-replaced', () => {
			preview.dataset.darkImage = previewImage.getAttribute('src') ?? ''
			preview.dataset.darkImageFactory = 'no'
			select.dataset.saved = 'yes'
			if (select.value !== 'image') {
				$(select).dropdown('set selected', 'image', true)
			}
			reflect()
		})
		select.addEventListener('brand-dark-refresh', reflect)
		$(select).dropdown({
			onChange: () => {
				reflect()
				save()
			},
		})
	})

	//«Eliminar» del logo del cargador (H345): lo apaga (no vuelve el de fábrica) y el cargador enseña solo la animación.
	const view = document.querySelector('section.logos-favicons-view')
	const loaderLogo = document.querySelector('#image-loader-logo')
	if (view !== null && loaderLogo !== null) {
		const text = (name) => view.getAttribute(`data-text-${name}`) ?? ''
		const holder = loaderLogo.querySelector('[data-image-card-delete-holder]')
		const refreshDark = () => loaderLogo.querySelector('[data-brand-dark-mode]')?.dispatchEvent(new Event('brand-dark-refresh'))
		loaderLogo.addEventListener('image-card-replaced', () => {
			holder.hidden = false
		})
		loaderLogo.addEventListener('click', (event) => {
			const button = event.target.closest('[data-image-card-delete]')
			if (button === null || button.disabled || button.closest('[data-image-card]') !== loaderLogo) {
				return
			}
			confirmAction({ message: $('<p></p>').text(text('delete-confirm')).prop('outerHTML'), confirmText: text('delete'), confirmClass: 'red' }).then((confirmed) => {
				if (!confirmed) {
					return
				}
				const formData = new FormData()
				formData.set('deleteBrandImage', button.dataset.value)
				button.classList.add('loading', 'disabled')
				postRequest(view.dataset.action, formData).done((response) => {
					button.classList.remove('loading', 'disabled')
					if (response.success) {
						const light = Array.from(loaderLogo.querySelectorAll('[data-image-card-edit]')).find((edit) => edit.closest('[data-image-card]') === loaderLogo)
						const lightHolder = light?.parentElement
						light?.remove()
						//El damero vacío, de la plantilla de la tarjeta: el editor lo quita al subir otra.
						const emptyTemplate = lightHolder?.querySelector(':scope > [data-image-card-empty-template]')
						if (lightHolder && emptyTemplate && lightHolder.querySelector(':scope > [data-image-card-empty]') === null) {
							lightHolder.appendChild(emptyTemplate.content.cloneNode(true))
						}
						//El servidor borró su oscura y su modo: sin ellos, `same` con la clara vacía.
						const darkPreview = loaderLogo.querySelector('[data-brand-dark-preview]')
						const darkSelect = loaderLogo.querySelector('[data-brand-dark-mode]')
						if (darkPreview !== null && darkSelect !== null) {
							darkPreview.dataset.darkImage = ''
							darkPreview.dataset.darkImageFactory = 'no'
							darkPreview.dataset.darkFallbackMode = 'same'
							darkSelect.dataset.saved = 'no'
							$(darkSelect).dropdown('set selected', 'same', true)
						}
						const download = Array.from(loaderLogo.querySelectorAll('[data-image-card-download]')).find((link) => link.closest('[data-image-card]') === loaderLogo)
						download?.setAttribute('href', '#')
						holder.hidden = true
						refreshDark()
						successMessage(text('deleted'), '')
					} else {
						errorMessage(text('error'), response.message)
					}
				}).fail(() => {
					button.classList.remove('loading', 'disabled')
					errorMessage(text('error'), '')
				})
			})
		})
	}

	removeGenericLoader('logos-favicon')

})
