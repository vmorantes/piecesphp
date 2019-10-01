window.addEventListener('load', () => {

	let loaderName = 'Formularios de creación/edición de artículos'

	showGenericLoader(loaderName)

	$('.ui.top.attached.tabular.menu .item').tab({
		context: 'parent'
	})

	let isEdit = false
	let imagenMainReady = false
	let imageThumbReady = false
	let imageOpenGraphReady = false
	let imagesTabHided = false

	let imagenMain = new CropperAdapterComponent({
		minWidth: 800,
		containerSelector: '[cropper-image-main]',
		onInitiealize: (cropper, canvas) => {
			imagenMainReady = true
			ready()
		},
		cropperOptions: {
			aspectRatio: 4 / 3,
			minCropBoxWidth: 800,
		},
	})

	let imageThumb = new CropperAdapterComponent({
		minWidth: 400,
		containerSelector: '[cropper-image-thumb]',
		onInitiealize: (cropper, canvas) => {
			imageThumbReady = true
			ready()
		},
		cropperOptions: {
			aspectRatio: 4 / 3,
			minCropBoxWidth: 400,
		},
	})

	let imageOpenGraph = new CropperAdapterComponent({
		minWidth: 120,
		outputWidth: 1200,
		containerSelector: '[cropper-image-og]',
		onInitiealize: (cropper, canvas) => {
			imageOpenGraphReady = true
			ready()
		},
		cropperOptions: {
			aspectRatio: 2 / 1,
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
				if (imageOpenGraph.wasChanged()) {
					formData.set('image-og', imageOpenGraph.getFile())
				}

			} else {
				formData.set('image-main', imagenMain.getFile())
				formData.set('image-thumb', imageThumb.getFile())		
				if (imageOpenGraph.wasChanged()) {
					formData.set('image-og', imageOpenGraph.getFile())
				}
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
     */
	function ready() {
		if (imagenMainReady && imageThumbReady && imageOpenGraphReady && !imagesTabHided) {
			let tabs = form.find(".ui.tab[data-tab='images'], .ui.tab[data-tab='seo']")
			tabs.removeClass('active')
			removeGenericLoader(loaderName)
			form.find(`[type="submit"][disabled]`).attr('disabled', false)
		}
	}

})
