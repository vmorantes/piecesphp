/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
showGenericLoader('_CARGA_INICIAL_')

window.addEventListener('load', function () {

	window.dispatchEvent(new Event('canDeletePublicationCategory'))

	let isEdit = false
	let isDetailMode = false
	let formSelector = `.ui.form.publications-categories`

	//Formulario
	let form = genericFormHandler(formSelector, {
		onSetFormData: function (formData) {
			if (isEdit) {
				return formData
			} else {
				const processedFormData = new FormData()
				processedFormData.set('baseLang', formData.get('baseLang'))
				processedFormData.set(`name[${formData.get('baseLang')}]`, formData.get('name'))
				return processedFormData
			}
		},
		onInvalidEvent: function (event) {

			let element = event.target
			let validationMessage = element.validationMessage
			let jElement = $(element)
			let field = jElement.closest('.field')
			let nameOnLabel = field.find('label').html()

			errorMessage(`${nameOnLabel}: ${validationMessage}`)

			event.preventDefault()

		}
	})

	form.find('input, select, textarea').attr('autocomplete', 'off')
	form.find('.ui.dropdown').dropdown()

	isEdit = form.find(`[name="id"]`).length > 0

	configLangChange('.ui.dropdown.langs')
	checkDetailMode()

	function configLangChange(dropdownSelector) {

		let dropdown = $(dropdownSelector)

		dropdown.dropdown({
			/**
			 * 
			 * @param {Number|String} value 
			 * @param {String} innerText 
			 * @param {$} element 
			 */
			onChange: function (value, innerText, element) {
				showGenericLoader('redirect')
				window.location.href = value
			},
		})

	}

	function checkDetailMode() {

		isDetailMode = form.hasClass('detail-mode')

		if (isDetailMode) {

			const fields = Array.from(form.find('.field'))
			const fieldsButtons = form.find('.field .ui.buttons')
			const submitButtons = fieldsButtons.find("button[type='submit']")

			for (const field of fields) {
				const $field = $(field)
				$field.addClass('disabled')
				$field.filter(':visible').attr('style', 'opacity: 0.80;')
				$field.find('label,input,select:not([simulator]),textarea').filter(':visible').attr('style', 'opacity: 1;')
			}

			if (submitButtons.length > 0) {
				fieldsButtons.remove()
			}

		}

	}

	removeGenericLoader('_CARGA_INICIAL_')

})


