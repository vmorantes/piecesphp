/// <reference path="../../js/helpers.js" />
/// <reference path="../../own-plugins/SimpleCropperAdapter.js" />
showGenericLoader('seo')
window.addEventListener('load', () => {

	const onInvalidHandler = function (event) {

		let element = event.target
		let validationMessage = element.validationMessage
		let jElement = $(element)
		let field = jElement.closest('.field')
		let nameOnLabel = field.find('label').html()

		errorMessage(`${nameOnLabel}: ${validationMessage}`)

		event.preventDefault()

	}
	const instantiateCropper = (selector, ow = 1200, ar = 1200 / 630) => {
		return new SimpleCropperAdapter(selector, {
			aspectRatio: ar,
			format: 'image/jpeg',
			quality: 0.8,
			fillColor: 'white',
			outputWidth: ow,
		})
	}
	/**
	 * @param {FormData} formData 
	 * @param {SimpleCropperAdapter} cropper 
	 * @param {String} name 
	 */
	const onSetFormData = function (formData, cropper, name) {
		formData.set(name, cropper.getFile())
		return formData
	}

	let langs = $('form[lang]').toArray().map((e) => e.getAttribute('lang'))

	for (let lang of langs) {

		let firstDraw = true
		let cropperOpenGraph = instantiateCropper(`form.seo[lang="${lang}"] [simple-cropper-seo]`)
		let form = genericFormHandler(`form.seo[lang="${lang}"]`, {
			onSetFormData: (formData) => {
				return onSetFormData(formData, cropperOpenGraph, `open-graph`)
			},
			onInvalidEvent: onInvalidHandler,
			onSuccess: () => location.reload()
		})

		form.find('.ui.dropdown.keywords').dropdown({
			allowAdditions: true,
		})

		const itemImage = $(`[seo-logo-item-${lang}]`)
		const modalImage = $(`[seo-logo-modal-${lang}]`)

		itemImage.on('click', (e) => {
			e.preventDefault()
			modalImage.modal({
				onVisible: function () {
					if (firstDraw) {
						cropperOpenGraph.refresh()
						firstDraw = false
					}
				}
			}).modal('show')
		})

		cropperOpenGraph.onCropped(() => {
			form.trigger('submit')
		})

		cropperOpenGraph.onCancel(() => {
			modalImage.modal('hide')
		})
	}

	$('.menu .item').tab()

	removeGenericLoader('seo')

})
