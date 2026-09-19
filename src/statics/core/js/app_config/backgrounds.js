/// <reference path="../../js/helpers.js" />
/// <reference path="../../own-plugins/SimpleCropperAdapter.js" />
showGenericLoader('backgrounds')
window.addEventListener('load', () => {

	//Fondos de inicio de sesión
	/**
	 * @type {SimpleCropperAdapter[]} croppersLogin
	 */
	let croppersBgLogin = []
	let formsBgLogin = []
	let backgroundLoginQty = 5

	const imageBgLoginWidth = 650
	const imageBgLoginHeight = 730

	for (let i = 1; i <= backgroundLoginQty; i++) {

		let firstDraw = true
		croppersBgLogin[i] = new SimpleCropperAdapter(`[back-login-${i}]`, {
			aspectRatio: imageBgLoginWidth / imageBgLoginHeight,
			format: 'image/jpeg',
			quality: 0.8,
			fillColor: 'white',
			outputWidth: imageBgLoginWidth,
		})

		formsBgLogin[i] = genericFormHandler(`form[bg="${i}"]`, {
			onSetFormData: function (formData) {
				formData.set(`background-${i}`, croppersBgLogin[i].getFile())
				return formData
			},
			onInvalidEvent: function (event) {

				let element = event.target
				let validationMessage = element.validationMessage
				let jElement = $(element)
				let field = jElement.closest('.field')
				let nameOnLabel = field.find('label').html()

				errorMessage(`${nameOnLabel}: ${validationMessage}`)

				event.preventDefault()

			},
			onSuccess: () => {
				$modal.modal('hide')
			},
		})

		// Modales
		const $form = $(`form[bg="${i}"]`)
		const $modal = $(`[modal="${i}"]`)
		const imageCard = $form.find(`[image-card][modal-index="${i}"]`)

		imageCard.on('click', () => {
			$modal.modal({
				onVisible: function () {
					if (firstDraw) {
						croppersBgLogin[i].refresh()
						firstDraw = false
					}
				},
			}).modal('show')
		})

		croppersBgLogin[i].onCancel(() => $modal.modal('hide'))
		croppersBgLogin[i].onCropped((blobImage, settedImage) => {
			imageCard.find('>img').attr('src', settedImage)
			$form.trigger('submit')
		})


	}

	//Fondo de problemas de inicio
	const imageBgProblemsWidth = 1920
	const imageBgProblemsHeight = 1080
	let identifierBgProblems = 'background-problems'
	let formBgProblems = genericFormHandler(`form.ui.form.${identifierBgProblems}`, {
		onSetFormData: function (formData) {
			formData.set(`problems-background`, cropperBgProblems.getFile())
			return formData
		},
		onInvalidEvent: function (event) {

			let element = event.target
			let validationMessage = element.validationMessage
			let jElement = $(element)
			let field = jElement.closest('.field')
			let nameOnLabel = field.find('label').html()

			errorMessage(`${nameOnLabel}: ${validationMessage}`)

			event.preventDefault()

		},
		onSuccess: () => {
			modalBgProblems.modal('hide')
		},
	})
	let firstBgProblemsDraw = true
	let cropperBgProblems = new SimpleCropperAdapter(`[background-problems-cropper]`, {
		aspectRatio: imageBgProblemsWidth / imageBgProblemsHeight,
		format: 'image/jpeg',
		quality: 0.8,
		fillColor: 'white',
		outputWidth: imageBgProblemsWidth,
	})
	const modalBgProblems = $(`[modal="${identifierBgProblems}"]`)
	const imageCardBgProblens = formBgProblems.find(`[image-card][modal-index="${identifierBgProblems}"]`)
	imageCardBgProblens.on('click', () => {
		modalBgProblems.modal({
			onVisible: function () {
				if (firstBgProblemsDraw) {
					cropperBgProblems.refresh()
					firstBgProblemsDraw = false
				}
			},
		}).modal('show')
	})
	cropperBgProblems.onCancel(() => modalBgProblems.modal('hide'))
	cropperBgProblems.onCropped((blobImage, settedImage) => {
		imageCardBgProblens.find('>img').attr('src', settedImage)
		formBgProblems.trigger('submit')
	})


	removeGenericLoader('backgrounds')

})
