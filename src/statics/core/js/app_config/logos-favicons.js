/// <reference path="../../js/helpers.js" />
/// <reference path="../../own-plugins/SimpleCropperAdapter.js" />
showGenericLoader('logos-favicon')
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
	const instantiateCropper = (selector, ow = 400, ar = 400 / 400) => {
		return new SimpleCropperAdapter(selector, {
			aspectRatio: ar,
			format: 'image/png',
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

	let firstFavPublicDraw = true
	let firstFavBackDraw = true
	let firstLogoDraw = true
	let firstPartnersDraw = true
	let firstPartnersVerticalDraw = true

	let cropperPublicFavicon = instantiateCropper(`form.public-favicon [fav-cropper]`)
	genericFormHandler(`form.public-favicon`, {
		onSetFormData: (formData) => onSetFormData(formData, cropperPublicFavicon, `favicon`),
		onInvalidEvent: onInvalidHandler,
	})

	let cropperBackFavicon = instantiateCropper(`form.back-favicon [admin-fav-cropper]`)
	genericFormHandler(`form.back-favicon`, {
		onSetFormData: (formData) => onSetFormData(formData, cropperBackFavicon, `favicon-back`),
		onInvalidEvent: onInvalidHandler,
	})

	let cropperLogo = instantiateCropper(`form.logo [logo-cropper]`)
	genericFormHandler(`form.logo`, {
		onSetFormData: (formData) => onSetFormData(formData, cropperLogo, `logo`),
		onInvalidEvent: onInvalidHandler,
	})

	let cropperPartners = instantiateCropper(`form.partners [partners-cropper]`, 280, 280 / 50)
	genericFormHandler(`form.partners`, {
		onSetFormData: (formData) => onSetFormData(formData, cropperPartners, `partners`),
		onInvalidEvent: onInvalidHandler,
	})
	let cropperPartnersVertical = instantiateCropper(`form.partners-vertical [partners-vertical-cropper]`, 50, 50 / 280)
	genericFormHandler(`form.partners-vertical`, {
		onSetFormData: (formData) => onSetFormData(formData, cropperPartnersVertical, `partnersVertical`),
		onInvalidEvent: onInvalidHandler,
	})

	// Modals
	const logoModal = $("[logo-modal]")
	const adminFavModal = $("[admin-favicon-modal]")
	const favModal = $("[favicon-modal]")
	const partnersModal = $("[partners-modal]")
	const partnersVerticalModal = $("[partners-vertical-modal]")
	// Image card tag
	const logoCard = $("[card-logo]")
	const adminFavCard = $("[admin-fav-card]")
	const favCard = $("[fav-card]")
	const partnersCard = $("[partners-card]")
	const partnersVerticalCard = $("[partners-vertical-card]")
	// Se muestran los modales
	logoCard.on('click', () => {
		logoModal.modal({
			onVisible: function () {
				if (firstLogoDraw) {
					cropperLogo.refresh()
					firstLogoDraw = false
				}
			}
		}).modal('show')
	})
	adminFavCard.on('click', () => {
		adminFavModal.modal({
			onVisible: function () {
				if (firstFavBackDraw) {
					cropperBackFavicon.refresh()
					firstFavBackDraw = false
				}
			}
		}).modal('show')
	})
	favCard.on('click', () => {
		favModal.modal({
			onVisible: function () {
				if (firstFavPublicDraw) {
					cropperPublicFavicon.refresh()
					firstFavPublicDraw = false
				}
			}
		}).modal('show')
	})
	partnersCard.on('click', () => {
		partnersModal.modal({
			onVisible: function () {
				if (firstPartnersDraw) {
					cropperPartners.refresh()
					firstPartnersDraw = false
				}
			}
		}).modal('show')
	})
	partnersVerticalCard.on('click', () => {
		partnersVerticalModal.modal({
			onVisible: function () {
				if (firstPartnersVerticalDraw) {
					cropperPartnersVertical.refresh()
					firstPartnersVerticalDraw = false
				}
			}
		}).modal('show')
	})
	//Enviar los formularios
	cropperLogo.onCropped((blobImage, settedImage) => {
		logoModal.find('form').trigger('submit')
		logoCard.find('>img').attr('src', settedImage)
		logoModal.modal('hide')
	})
	cropperBackFavicon.onCropped((blobImage, settedImage) => {
		adminFavModal.find('form').trigger('submit')
		adminFavCard.find('>img').attr('src', settedImage)
		adminFavModal.modal('hide')
	})
	cropperPublicFavicon.onCropped((blobImage, settedImage) => {
		favModal.find('form').trigger('submit')
		favCard.find('>img').attr('src', settedImage)
		favModal.modal('hide')
	})
	cropperPartners.onCropped((blobImage, settedImage) => {
		partnersModal.find('form').trigger('submit')
		partnersCard.find('>img').attr('src', settedImage)
		partnersModal.modal('hide')
	})
	cropperPartnersVertical.onCropped((blobImage, settedImage) => {
		partnersVerticalModal.find('form').trigger('submit')
		partnersVerticalCard.find('>img').attr('src', settedImage)
		partnersVerticalModal.modal('hide')
	})
	// Acciones de cancelar
	cropperLogo.onCancel(() => {
		logoModal.modal('hide')
	})
	cropperBackFavicon.onCancel(() => {
		adminFavModal.modal('hide')
	})
	cropperPublicFavicon.onCancel(() => {
		favModal.modal('hide')
	})
	cropperPartners.onCancel(() => {
		partnersModal.modal('hide')
	})
	cropperPartnersVertical.onCancel(() => {
		partnersVerticalModal.modal('hide')
	})

	removeGenericLoader('logos-favicon')

})
