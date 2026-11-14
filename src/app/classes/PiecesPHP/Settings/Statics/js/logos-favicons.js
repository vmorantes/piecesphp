showGenericLoader('logos-favicon')
window.addEventListener('load', () => {

	new ContentImageEditor()

	//El texto sobre el patrocinador: un campo por idioma activo, en un solo formulario y sin recorte.
	genericFormHandler(`form[partners-text-form]`)

	//La casilla «Invertir colores en modo oscuro»: se guarda al cambiarla y la vista previa la refleja, también en claro.
	const action = document.querySelector('[data-image-card-invert]')?.closest('section.logos-favicons-view')?.dataset.action
	$('[data-image-card-invert]').each(function () {
		const input = this
		const holder = input.closest('[data-image-card]').querySelector('.checkered.image')
		const reflect = () => {
			holder.classList.toggle('inverted', input.checked)
			holder.classList.toggle('content-image-card-inverted', input.checked)
		}
		//Sin opción guardada manda la imagen: una propia no se invierte hasta marcarla.
		input.closest('[data-image-card]').addEventListener('image-card-replaced', () => {
			if (input.dataset.saved !== 'yes') {
				$(input).closest('.checkbox').checkbox('set unchecked')
				reflect()
			}
		})
		$(input).closest('.checkbox').checkbox({
			onChange: () => {
				input.dataset.saved = 'yes'
				reflect()
				const formData = new FormData()
				formData.set('invertOnDark', input.dataset.imageCardInvert)
				formData.set('checked', input.checked ? '1' : '0')
				postRequest(action, formData).done((response) => {
					if (!response.success) {
						errorMessage(response.name, response.message)
					}
				}).fail(() => errorMessage('', _i18n('errors', 'unexpected_error')))
			},
		})
	})

	removeGenericLoader('logos-favicon')

})
