/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
showGenericLoader('_CARGA_INICIAL_')
window.addEventListener('load', function () {

	window.dispatchEvent(new Event('canDeleteSuscriber'))

	let isEdit = false
	let formSelector = `.ui.form.newsletter`
	let langGroup = 'appNewsletterLang'


	let form = genericFormHandler(formSelector, {
		onSetFormData: function (formData) {
			return formData
		},
	})

	form.find('input, select, textarea').attr('autocomplete', 'off')
	form.find('.checkbox').checkbox()
	form.find('.ui.dropdown').dropdown()

	isEdit = form.find(`[name="id"]`).length > 0

	removeGenericLoader('_CARGA_INICIAL_')

})


