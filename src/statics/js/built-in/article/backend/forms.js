window.addEventListener('load', () => {

	let loaderName = 'Formularios de creación/edición de artículos'

	showGenericLoader(loaderName)

	$('.ui.top.attached.tabular.menu .item').tab({
		context: 'parent'
	})

	let isEdit = false
	let imagenMainReady = false
	let imageThumbReady = false
	let imagesTabHided = false

	let imagenMain = new CropperAdapterComponent({
		minWidth: 800,
		containerSelector: '[cropper-image-main]',
		onInitiealize: (cropper, canvas) => {
			imagenMainReady = true
			ready(canvas)
		},
		cropperOptions:{
			aspectRatio: 4 / 3,
			minCropBoxWidth: 800,
		},
	})

	let imageThumb = new CropperAdapterComponent({
		minWidth: 400,
		containerSelector: '[cropper-image-thumb]',
		onInitiealize: (cropper, canvas) => {
			imageThumbReady = true
			ready(canvas)
		},
		cropperOptions:{
			aspectRatio: 4 / 3,
			minCropBoxWidth: 400,
		},
	})

	let form = genericFormHandler('[pcsphp-articles]', {
		onSetFormData: function (formData) {

			if (isEdit) {

				if (imagenMain.wasChanged()) {
					formData.set('image-main', imagenMain.getFile())
				}
				if (imageThumb.wasChanged()) {
					formData.set('image-thumb', imageThumb.getFile())
				}

			} else {
				formData.set('image-main', imagenMain.getFile())
				formData.set('image-thumb', imageThumb.getFile())
			}

			return formData

		},
		onInvalidEvent: function (event) {

			let element = event.target
			let validationMessage = element.validationMessage
			let jElement = $(element)
			let field = jElement.parents('.field')
			let nameOnLabel = field.find('label').html()

			errorMessage(`${nameOnLabel}: ${validationMessage}`)

			event.preventDefault()

		}
	})

	isEdit = form.find(`[name="id"]`).length > 0

	let quillAdapter = new QuillAdapterComponent({
		containerSelector: '[quill-editor]',
		textareaTargetSelector: "textarea[name='content']",
		urlProcessImage: form.attr('quill'),
		nameOnRequest: 'image',
	})

    /**
     * @function hideImagesTab
     * @param {HTMLElement} canvas
     */
	function ready(canvas) {
		if (imagenMainReady && imageThumbReady && !imagesTabHided) {
			let tabParent = $(canvas).parents('.ui.bottom.attached.tab')
			tabParent.removeClass('active')
			removeGenericLoader(loaderName)
			form.find(`[type="submit"][disabled]`).attr('disabled', false)
		}
	}

})
