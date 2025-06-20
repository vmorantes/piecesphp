///<reference path="./GenericLoaderElement.js" />
///<reference path="../helpers.js" />
/**
 * Manejador genérico de formularios, requiere jquery
 * 
 * @param {String|$} selectorForm 
 * @param {genericFormHandler.Options} options
 * @param {Boolean} [overwrite=true] Si es true aplica .off al evento submit
 * @param {Boolean} [defaultInvalidHandler=true] Si es true aplica un manejador de invalidez del formulario predefinido (options.onInvalidEvent lo sobreescribe)
 * @returns {$} 
 */
function genericFormHandler(selectorForm = 'form[pcs-generic-handler-js]', options = {}, overwrite = true, defaultInvalidHandler = true) {

	/**
	 * @typedef genericFormHandler.Options
	 * @property {genericFormHandler.Options.ConfirmationOption} [confirmation]
	 * @property {{(formData: FormData, form: $):FormData}} [onSetFormData]
	 * @property {{(form: $):$|Promise}} [onSetForm]
	 * @property {{(form: $):Boolean}} [validate]
	 * @property {{(form: $, formData: FormData, response: Object):Promise|void}} [onSuccess]
	 * @property {{(form: $, formData: FormData, response: Object):void}} [onError]
	 * @property {{(event: Event):void}} [onInvalidEvent]
	 * @property {{(form: $, formData: FormData, response: Object):void}} [onSuccessFinally]
	 * @property {Boolean} [toast]
	 * @property {Boolean} [ignoreRedirection]
	 * @property {Boolean} [ignoreReload]
	 */
	/**
	 * @typedef genericFormHandler.Options.ConfirmationOption
	 * @property {String} selector Selector del elemento
	 * @property {String} [title] Título
	 * @property {String} [message]	Mensaje de advertencia
	 * @property {String} [positive] Texto afirmativo
	 * @property {String} [negative] Texto negativo
	 * @property {{Function(buttonConfirmation: $):Boolean}} [condition]
	 */
	let ignore;

	if (!(selectorForm instanceof $)) {
		selectorForm = typeof selectorForm == 'string' && selectorForm.trim().length > 0 ? selectorForm.trim() : `form[pcs-generic-handler-js]`
	}

	overwrite = overwrite === true
	defaultInvalidHandler = defaultInvalidHandler === true

	let form = selectorForm instanceof $ ? selectorForm : $(`${selectorForm}`)

	let hasConfirmation = false
	let buttonConfirmation = null
	let waitForConfirmation = false
	/**
	 * @param {FormData} formData
	 * @param {$} form
	 * @returns {FormData}
	 */
	let onSetFormData = function (formData, form) {
		return formData
	}
	/**
	 * @param {$} form
	 * @returns {$|Promise}
	 */
	let onSetForm = function (form) {
		return form
	}
	/**
	 * @param {$} form
	 * @returns {Boolean}
	 */
	let validate = function (form) {
		return true
	}
	/**
	 * @param {$} form
	 * @param {FormData} formData
	 * @param {Object} response
	 * @returns {Promise|void}
	 */
	let onSuccess = function (form, formData, response) {
	}
	/**
	 * @param {$} form
	 * @param {FormData} formData
	 * @param {Object} response
	 * @returns {void}
	 */
	let onError = function (form, formData, response) {
	}

	let onInvalidEventOnToTopAnimation = false
	/**
	 * @param {Event} event
	 * @returns {void}
	 */
	let onInvalidEvent = function (event) {

		let element = event.target
		let validationMessage = element.validationMessage
		let jElement = $(element)
		let field = jElement.closest('.field')
		let nameOnLabel = field.find('label').html()
		let parentForm = jElement.closest('form')

		//Si es un dropdown con simulador se ignora
		if (typeof jElement.attr('data-simulator') == 'string' && jElement.attr('data-simulator').length > 1) {
			event.preventDefault()
			return
		}

		field.addClass('error')
		field.find('input,select,textarea').map((i, e) => e.blur())
		let removeErrorClass = function () {
			field.removeClass('error')
			form.find(`li[data-name="${dataID}"]`).remove()
			const messageContainer = form.find(`[${errorMessageContainerAttr}]`)
			if (messageContainer.find('.list li').length < 1) {
				messageContainer.remove()
			}
		}

		field.off('focus change', '*', removeErrorClass)
		field.on('focus change', '*', removeErrorClass)

		//Agregar errores
		const dataID = configElementDataID()
		const errorMessageContainerAttr = 'error-form-container'

		configErrorMessageContainer(parentForm)

		event.preventDefault()

		function configElementDataID() {
			let dataID = jElement.data('id')
			jElement.data('id')
			const hasDataID = typeof dataID == 'string' && dataID.trim().length > 0
			dataID = hasDataID ? dataID.trim() : generateUniqueID().trim()
			if (!hasDataID) {
				jElement.attr('data-id', dataID)
			}
			return dataID
		}

		function configErrorMessageContainer(form) {

			let messageContainer = form.find(`[${errorMessageContainerAttr}]`)

			if (messageContainer.length < 1) {
				let html = `<div class="ui error message" ${errorMessageContainerAttr}>`
				html += `<i class="close icon"></i><br>`
				html += `<div class="content"><ul class="list"></ul></div>`
				html += `</div>`
				form.prepend(html)
				messageContainer = form.find(`[${errorMessageContainerAttr}]`)
				messageContainer.find('.close').off('click')
				messageContainer.find('.close').on('click', function () {
					$(this).closest('.message').transition('fade')
				})
			}

			let toTop = () => {
				if (!onInvalidEventOnToTopAnimation) {
					onInvalidEventOnToTopAnimation = true
					$('body,html,.ui-pcs.container-sidebar>.content').animate({
						scrollTop: form.offset().top
					}, {
						easing: 'linear',
						complete: function () {
							onInvalidEventOnToTopAnimation = false
						}
					})
				}
			}

			if (!messageContainer.is(':visible')) {
				messageContainer.transition('fade', {
					onComplete: function () {
						if (!visibleInViewPort(messageContainer)) {
							toTop()
						}
						messageContainer.find('.content').css('min-height', 'min-content')
						messageContainer.find('.content').css('overflow', 'auto')
						messageContainer.find('.content').css('max-height', '220px')
					}
				})
			} else {
				toTop()
			}

			configIndividualError(messageContainer)
			removeOrphanIndividualErrors(messageContainer)

			function configIndividualError(messageContainer) {

				const liErrorSelector = `[data-name="${dataID}"]`
				let liError = messageContainer.find(liErrorSelector)
				liError.remove()

				messageContainer.find('.list').append(`<li data-name="${dataID}"></li>`)
				liError = messageContainer.find(liErrorSelector)
				liError.html(`<strong>${nameOnLabel}</strong>: ${validationMessage}`)

				messageContainer.find('.list').append(liError)

			}

			function removeOrphanIndividualErrors() {
				const liErrors = messageContainer.find('.list li')
				liErrors.map(function (i, element) {
					let dataID = element.dataset.name
					let inputElement = form.find(`[data-id="${dataID}"]`)
					if (inputElement.length < 1) {
						$(element).remove()
					}
				})
			}

			return messageContainer

		}

	}
	onInvalidEvent = defaultInvalidHandler ? onInvalidEvent : function (event) {
	}
	/**
	 * @param {$} form
	 * @param {FormData} formData
	 * @param {Object} response
	 * @returns {void}
	 */
	let onSuccessFinally = function (form, formData, response) {
	}
	let toast = true
	let ignoreRedirection = false
	let ignoreReload = false

	if (typeof options == 'object') {
		if (typeof options.confirmation == 'object') {

			let confirmationOptions = options.confirmation

			if (typeof confirmationOptions.selector == 'string') {
				buttonConfirmation = $(confirmationOptions.selector)
				hasConfirmation = buttonConfirmation.length > 0
			}
			if (typeof confirmationOptions.title != 'string') {
				options.confirmation.title = 'Confirmación'
			}
			if (typeof confirmationOptions.message != 'string') {
				options.confirmation.message = '¿Está seguro de realizar esta acción?'
			}
			if (typeof confirmationOptions.positive != 'string') {
				options.confirmation.positive = 'Sí'
			}
			if (typeof confirmationOptions.negative != 'string') {
				options.confirmation.negative = 'No'
			}
			if (typeof confirmationOptions.condition != 'function') {
				options.confirmation.condition = () => true
			}

			if (hasConfirmation) {
				hasConfirmation = options.confirmation.condition(buttonConfirmation) === true
			}

		}
		if (typeof options.onSetFormData == 'function') {
			onSetFormData = options.onSetFormData
		}
		if (typeof options.onSetForm == 'function') {
			onSetForm = options.onSetForm
		}
		if (typeof options.validate == 'function') {
			validate = options.validate
		}
		if (typeof options.onSuccess == 'function') {
			onSuccess = options.onSuccess
		}
		if (typeof options.onError == 'function') {
			onError = options.onError
		}
		if (typeof options.onInvalidEvent == 'function') {
			onInvalidEvent = options.onInvalidEvent
		}
		if (typeof options.onSuccessFinally == 'function') {
			onSuccessFinally = options.onSuccessFinally
		}
		if (typeof options.toast == 'boolean') {
			toast = options.toast
		}
		if (typeof options.ignoreRedirection == 'boolean') {
			ignoreRedirection = options.ignoreRedirection
		}
		if (typeof options.ignoreReload == 'boolean') {
			ignoreReload = options.ignoreReload
		}
	}

	if (form.length > 0) {

		form.off('invalid')
		form.find('input,textarea,select').off('invalid')
		form.find('input,textarea,select').on('invalid', onInvalidEvent)
		form.onSuccessFinally = onSuccessFinally

		if (overwrite) {
			form.off('submit')
		}
		form.on('submit', function (e) {

			e.preventDefault()

			let thisForm = $(e.target)

			if (validate(form)) {
				if (!hasConfirmation) {

					submit(thisForm)

				} else {

					if (!waitForConfirmation) {
						$('body').addClass('wait-to-action')
						waitForConfirmation = true
						$.toast({
							title: options.confirmation.title,
							message: options.confirmation.message,
							displayTime: 0,
							class: 'white',
							position: 'top center',
							classActions: 'top attached',
							actions: [{
								text: `${options.confirmation.positive}`,
								class: 'blue',
								click: function () {
									submit(thisForm)
									$('body').removeClass('wait-to-action')
									waitForConfirmation = false
								}
							}, {
								text: `${options.confirmation.negative}`,
								class: 'gray',
								click: function () {
									$('body').removeClass('wait-to-action')
									waitForConfirmation = false
									return true
								}
							}]
						})
					}
				}
			}

			return false

		})
	}

	function submit(form) {

		let formData = new FormData(form[0])
		form.find('button[type="submit"]').attr('disabled', true)

		let action = form.attr('action')
		let method = form.attr('method')
		let validAction = typeof action == 'string' && action.trim().length > 0
		let validMethod = typeof method == 'string' && method.trim().length > 0
		method = validMethod ? method.trim().toUpperCase() : 'POST'

		if (validAction) {

			let request = null

			let loaderElement = showLoader()

			if (method == 'POST') {

				let processFormData = onSetFormData(formData, form)
				let optionsPost = {
					xhr: function () {

						let xhr = new XMLHttpRequest()

						xhr.upload.addEventListener("progress", function (e) {

							if (e.lengthComputable) {
								let percentComplete = ((e.loaded / e.total) * 100);
								loaderElement.updatePercent(percentComplete >= 100 ? 99 : percentComplete)
							}

						}, false)

						return xhr

					}
				}

				if (typeof processFormData.then !== 'undefined') {
					processFormData.then(function (formData) {
						request = postRequest(action, formData, {}, optionsPost)
						handlerRequest(request, form, formData)
					})
				} else {
					request = postRequest(action, processFormData, {}, optionsPost)
					handlerRequest(request, form, formData)
				}

			} else {
				let processForm = onSetForm(form)
				if (typeof processForm.then !== 'undefined') {
					processForm.then(function (form) {
						request = getRequest(action, form)
						handlerRequest(request, form, formData)
					})
				} else {
					request = getRequest(action, processForm)
					handlerRequest(request, form, formData)
				}
			}

		} else {

			form.find('button').attr('disabled', false)
			console.error('No se ha definido ninguna acción')

			if (toast) {
				errorMessage('Error', 'Ha ocurrido un error desconocido, intente más tarde.')
			}

		}
	}

	/**
	 * @param {JQueryXHR} request 
	 * @param {$} formProcess 
	 * @param {FormData} formData 
	 * @returns {void}
	 */
	function handlerRequest(request, formProcess, formData) {

		request.done(function (response) {

			let responseStructure = {
				success: {
					optional: true,
					validate: (val) => {
						return typeof val == 'boolean'
					},
					parse: (val) => {
						return val === true
					},
					default: false,
				},
				name: {
					optional: true,
					validate: (val) => {
						return typeof val == 'string' && val.trim().length > 0
					},
					parse: (val) => {
						return val.trim()
					},
					default: 'Acción',
				},
				message: {
					optional: true,
					validate: (val) => {
						return typeof val == 'string' && val.trim().length > 0
					},
					parse: (val) => {
						return val.trim()
					},
					default: '',
				},
				values: {
					optional: true,
					validate: (val) => {
						return typeof val == 'object'
					},
					parse: (val) => {
						return val
					},
					default: {},
				},
			}

			let responseIsObject = typeof response == 'object'

			if (!responseIsObject) {
				form.find('button').attr('disabled', false)
				console.error(`La respuesta debe ser un objeto`)
				return
			}

			for (let option in responseStructure) {
				let config = responseStructure[option]
				let optional = config.optional
				let validateResponseValue = config.validate
				let parse = config.parse
				let value = config.default
				let optionExists = typeof response[option]
				if (optionExists) {
					let inputValue = response[option]
					if (validateResponseValue(inputValue)) {
						value = parse(inputValue)
					}
					response[option] = value
				} else if (optional) {
					response[option] = value
				} else {
					form.find('button').attr('disabled', false)
					console.error(`Falta la opción ${option} en el cuerpo de la respuesta.`)
					return
				}

			}

			if (response.success) {

				if (toast) {
					successMessage(response.name, response.message)
				}

				let responseValues = response.values

				let hasReload = typeof responseValues.reload != 'undefined' && responseValues.reload == true
				let hasRedirection = typeof responseValues.redirect != 'undefined' && responseValues.redirect == true
				let validRedirection = typeof responseValues.redirect_to == 'string' && responseValues.redirect_to.trim().length > 0

				if (ignoreRedirection) {
					hasRedirection = false
				}

				if (ignoreReload) {
					hasReload = false
				}

				let promiseOnSuccess = onSuccess(formProcess, formData, response)

				if (!(promiseOnSuccess instanceof Promise)) {
					promiseOnSuccess = Promise.resolve()
				}

				promiseOnSuccess.finally(function () {

					if (typeof form.onSuccessFinally == 'function') {
						form.onSuccessFinally(formProcess, formData, response)
					}

					if (hasRedirection && validRedirection) {

						setTimeout(function (e) {

							window.location = responseValues.redirect_to

						}, 1500)

					} else if (hasReload) {

						setTimeout(function (e) {

							window.location.reload()

						}, 1500)

					} else {

						form.find('button').attr('disabled', false)

					}

				})

			} else {

				if (toast) {
					errorMessage(response.name, response.message)
				}

				form.find('button').attr('disabled', false)

				onError(formProcess, formData, response)

			}

		})

		request.fail(function (error) {

			form.find('button').attr('disabled', false)

			if (toast) {
				errorMessage('Error', 'Ha ocurrido un error al conectar con el servidor, intente más tarde.')
			}

			onError(formProcess, formData, error)

			console.error(error)

		})

		request.always(function (res) {
			removeLoader()
		})
	}

	function showLoader() {
		return showGenericLoader('genericFormHandler')
	}

	function removeLoader() {
		removeGenericLoader('genericFormHandler')
	}

	return form

}