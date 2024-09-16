/// <reference path="../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../statics/core/js/helpers.js" />
window.addEventListener('load', function () {

	let langGroup = 'userSecurity'
	registerDynamicMessages(langGroup)

	showGenericLoader(langGroup)

	//Tabs
	const tabs = $('.tabs-controls [data-tab]').tab({})

	configureForm()

	removeGenericLoader(langGroup)

	function configureForm() {

		const qrActivationContainer = $('[qr-activation-container]')
		qrActivationContainer.hide()

		genericFormHandler("form[totp]")
		genericFormHandler("form[configure-2af]", {
			onSuccess: function (form, formData, response) {

				response = typeof response == 'object' ? response : {}
				const responseValues = typeof response.values == 'object' ? response.values : {}
				const enable = typeof responseValues.enable == "boolean" ? responseValues.enable : false
				const securityCode = typeof responseValues.securityCode == "string" ? responseValues.securityCode : ''

				const qrContainer = qrActivationContainer.find('[qr-container]')
				const securityCodeContainer = qrActivationContainer.find('[security-code]')
				const activateDoButton = qrActivationContainer.find('[activate-do]')
				const requestQRDataURL = qrContainer.data('qr-url')
				const activateURL = qrContainer.data('activate-url')

				if (enable) {

					if (typeof requestQRDataURL == 'string' && requestQRDataURL.trim().length > 0 && typeof activateURL == 'string' && activateURL.trim().length > 0 && securityCode.length > 0) {

						const loader1 = 'qr'
						showGenericLoader(loader1)

						securityCodeContainer.html(securityCode)
						activateDoButton.on('click', function (e) {
							e.preventDefault()
							$('body').addClass('wait-to-action')
							$.toast({
								title: _i18n(langGroup, 'Confirmar'),
								message: _i18n(langGroup, '¿Está seguro?'),
								displayTime: 0,
								class: 'white',
								position: 'top center',
								classActions: 'top attached',
								actions: [
									{
										text: _i18n(langGroup, 'Sí'),
										class: 'red',
										click: function () {
											const loader2 = 'qr'
											showGenericLoader(loader2)
											fetch(activateURL, {
												method: 'POST',
											}).then(function () {
												window.location.reload()
											}).finally(function () {
												removeGenericLoader(loader2)
											})
											$('body').removeClass('wait-to-action')
										}
									},
									{
										text: _i18n(langGroup, 'No'),
										class: 'blue',
										click: function () {
											$('body').removeClass('wait-to-action')
											return true
										}
									}
								]
							})
						})

						fetch(requestQRDataURL).then(res => res.json()).then(function (response) {

							if (response.success) {

								new QRCode(qrContainer.get(0), {
									text: response.values.QRDataURL,
									width: 180,
									height: 180,
									colorDark: "#000000",
									colorLight: "#FFFFFF",
									correctLevel: QRCode.CorrectLevel.H,
								})

								qrActivationContainer.show()

							} else {
								errorMessage(response.name, response.message)
							}

						}).finally(function () {
							removeGenericLoader(loader1)
						})

					}

				} else {
					qrActivationContainer.hide()
				}

			}
		})
	}

	function registerDynamicMessages(name) {

		if (typeof pcsphpGlobals != 'object') {
			window.pcsphpGlobals = {}
		}
		if (typeof pcsphpGlobals.messages != 'object') {
			pcsphpGlobals.messages = {}
		}
		if (typeof pcsphpGlobals.messages.es != 'object') {
			pcsphpGlobals.messages.es = {}
		}
		if (typeof pcsphpGlobals.messages.en != 'object') {
			pcsphpGlobals.messages.en = {}
		}


		let en = {
			'Confirmar': 'Confirm',
			'¿Está seguro?': 'Are you sure?',
			'Sí': 'Yes',
			'No': 'No',
		}

		//Registrar español a partir de inglés
		for (let i in en) {
			if (typeof pcsphpGlobals.messages.es[name] == 'undefined') pcsphpGlobals.messages.es[name] = {}
			pcsphpGlobals.messages.es[name][i] = i
		}

		for (let i in en) {
			if (typeof pcsphpGlobals.messages.en[name] == 'undefined') pcsphpGlobals.messages.en[name] = {}
			pcsphpGlobals.messages.en[name][i] = en[i]
		}
	}
})
