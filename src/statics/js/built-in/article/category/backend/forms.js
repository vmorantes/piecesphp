window.addEventListener('load', () => {

	$('.ui.top.attached.tabular.menu .item').tab({
		context: 'parent'
	})
	
	let form = genericFormHandler('.ui.form.category', {
		onInvalidEvent: function (event) {

			let element = event.target
			let validationMessage = element.validationMessage
			let jElement = $(element)
			let lang = jElement.parents('[data-tab]').attr('data-tab')
			let field = jElement.parents('.field')
			let nameOnLabel = field.find('label').html()
			
			errorMessage(`${nameOnLabel}: ${validationMessage} (${_i18n('lang', lang)})`)

			event.preventDefault()

		}
	})

})
