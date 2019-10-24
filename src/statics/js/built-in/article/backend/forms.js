window.addEventListener('load', () => {

	$('.ui.top.attached.tabular.menu .item').tab({
		context: 'parent'
	})
	$('.ui.selection.dropdown.lang-selector').dropdown({
		onChange: (value, text, item)=>{
			try{
				let url = new URL(value)
				window.location = url
			}catch(e){
				console.warn(e)
			}
		}
	})

	let isEdit = false

	let imagenMain = new CropperAdapterComponent({
		containerSelector: '[cropper-image-main]',
		minWidth: 800,
		outputWidth: 800,
		cropperOptions: {
			aspectRatio: 4 / 3,
		},
	})

	let imageThumb = new CropperAdapterComponent({
		containerSelector: '[cropper-image-thumb]',
		minWidth: 400,
		outputWidth: 400,
		cropperOptions: {
			aspectRatio: 4 / 3,
		},
	})

	let imageOpenGraph = new CropperAdapterComponent({
		containerSelector: '[cropper-image-og]',
		minWidth: 800,
		outputWidth: 1200,
		cropperOptions: {
			aspectRatio: 2 / 1,
		},
	})

	let form = genericFormHandler('[pcsphp-articles]', {
		onSetFormData: function (formData) {

			if (isEdit) {

				formData.set('image-main', imagenMain.getFile(null, null, null, null, true))
				formData.set('image-thumb', imageThumb.getFile(null, null, null, null, true))
				if (imageOpenGraph.wasChanged()) {
					formData.set('image-og', imageOpenGraph.getFile(null, null, null, null, true))
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

})
