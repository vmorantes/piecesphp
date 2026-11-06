showGenericLoader('_CARGA_INICIAL_')
window.addEventListener('load', function () {

	window.dispatchEvent(new Event('canDeleteOrganization'))

	//Tarjetas de imagen: solo viaja la que se cambió, con el nombre de su campo.
	const editedImages = {}
	const imageEditor = new ContentImageEditor()
	document.querySelectorAll('[data-image-card]').forEach((card) => {
		imageEditor.onApply(card.id, (file) => editedImages[card.dataset.field] = file)
	})

	//Formulario
	genericFormHandler(`.ui.form.generic`, {
		onSetFormData: function (formData) {
			for (const field in editedImages) {
				formData.set(field, editedImages[field])
			}
			return formData
		},
	})

	removeGenericLoader('_CARGA_INICIAL_')

})
