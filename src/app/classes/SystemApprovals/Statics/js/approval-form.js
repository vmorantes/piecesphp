/// <reference path="../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../statics/core/js/helpers.js" />
showGenericLoader('_CARGA_INICIAL_')
window.addEventListener('load', function () {

	/* Traducciones */
	const langGroup = 'system-approval-lang'
	registerDynamicLocalizationMessages(langGroup)

	/* Selectores y elementos de interfaz */
	const formSelector = `.ui.form.system-approval`
	const idMainImage = 'main-image'
	const idThumbImage = 'thumb-image'

	/* Configuraciones iniciales */
	configFomanticDropdown('.ui.dropdown:not(.additions)')
	configFomanticDropdown('.ui.dropdown.additions', {
		allowAdditions: true,
	})
	handleDownloableElements()
	handleOpenableLinksElements()

	let approvalStatus = 'PENDING'
	let form = genericFormHandler(formSelector, {
		onSetFormData: function (formData) {
			formData.set('approvalStatus', approvalStatus)
			return formData
		},
		onInvalidEvent: function (event) {

			let element = event.target
			let validationMessage = element.validationMessage
			let jElement = $(element)
			let field = jElement.closest('.field')
			let nameOnLabel = field.find('label').html()
			if (field.length == 0) {
				field = jElement.closest('.attach-placeholder')
				nameOnLabel = field.find('>label >.text >.header >.title').text()
			}

			errorMessage(`${nameOnLabel}: ${validationMessage}`)

			event.preventDefault()

		}
	})

	//Botones
	const submitButton = form.find('button[type="submit"][save]')
	const approveButton = $('button[approve-trigger]')
	const rejectButton = $('button[reject-trigger]')
	approveButton.on('click', function (e) {
		e.preventDefault()
		handleAction('blue', function () {
			approvalStatus = 'APPROVED'
			submitButton.click()
		})
	})
	rejectButton.on('click', function (e) {
		e.preventDefault()
		handleAction('red', function () {
			approvalStatus = 'REJECTED'
			submitButton.click()
		})
	})
	const handleAction = function (confirmClass, callback) {
		confirmAction({ confirmClass: confirmClass }).then(function (confirmed) {
			if (confirmed) {
				const loaderNameCancel = generateUniqueID('loaderCancel-')
				showGenericLoader(loaderNameCancel)
				callback()
				removeGenericLoader(loaderNameCancel)
			}
		})
	}

	//Configuraciones generales
	function handleDownloableElements() {
		const attrDataSelector = 'trigger-download-link'
		const element = $(`[data-${attrDataSelector}]`)
		element.on('click', function (e) {
			e.preventDefault()
			const link = $(e.currentTarget).data(attrDataSelector)
			if (typeof link == 'string' && link.trim().length > 0) {
				const a = document.createElement('a')
				a.download = ""
				a.href = link
				a.target = '_blank'
				a.click()
				a.remove()
			}
		})
	}
	function handleOpenableLinksElements() {
		const attrDataSelector = 'trigger-open-link'
		const element = $(`[data-${attrDataSelector}]`)
		element.on('click', function (e) {
			e.preventDefault()
			const link = $(e.currentTarget).data(attrDataSelector)
			if (typeof link == 'string' && link.trim().length > 0) {
				const a = document.createElement('a')
				a.href = link
				a.target = '_blank'
				a.click()
				a.remove()
			}
		})
	}

	removeGenericLoader('_CARGA_INICIAL_')

})


