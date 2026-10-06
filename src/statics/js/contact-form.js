/// <reference path="./CustomNamespace.js" />
/// <reference path="../core/js/helpers.js" />
window.addEventListener(pcsphpGlobals.events.configurationsAndWindowLoad, function (e) {

	let formSelector = '[contact-form]'

	let captchaResponse = {}
	let captchaResult = false
	let captchaMessage = ''
	let captchaToken = ''
	let captchaAdapterDefined = typeof GoogleCaptchaV3Adapter !== 'undefined'
	//La clave de sitio llega del servidor (api-keys.php). Vacía: sin adaptador, y el servidor rechaza el envío.
	let captchaSiteKey = pcsphpGlobals.frontConfigurationsFromBackend.GoogleReCaptchaV3SiteKey
	captchaSiteKey = typeof captchaSiteKey == 'string' ? captchaSiteKey.trim() : ''
	let recaptchaEval = () => { }

	if (captchaAdapterDefined && captchaSiteKey.length > 0) {
		let captchaAdapter = new GoogleCaptchaV3Adapter({
			key: captchaSiteKey,
		})
		recaptchaEval = function () {
			captchaAdapter.execute(function (response, success, message, token) {
				captchaResponse = response
				captchaResult = success
				captchaMessage = message
				captchaToken = token
			})
		}
		captchaAdapter.on('prepare', function () {
			recaptchaEval()
			setInterval(recaptchaEval, 120 * 1000)
		})
	} else {
		captchaResult = true
	}

	let form = genericFormHandler(formSelector, {
		onSetFormData: function (formData, form) {
			return new Promise(function (resolve) {
				formData.set('tokenCaptcha', captchaToken)
				resolve(formData)
			})
		},
		validate: function (form) {
			let valid = true
			if (!captchaResult) {
				valid = false
				errorMessage(`${captchaMessage}`, '')
			}
			return valid
		},
		onSuccess: function () {
			recaptchaEval()
			form[0].reset()
		},
		onInvalidEvent: function (event) {
			/** @type {HTMLElement} */ let element = event.target
			let validationMessage = element.validationMessage
			let label = element.parentElement.querySelector('label')
			let nameOnLabel = label.innerHTML
			errorMessage(`${nameOnLabel}: ${validationMessage}`)
			event.preventDefault()
		}
	})

})
