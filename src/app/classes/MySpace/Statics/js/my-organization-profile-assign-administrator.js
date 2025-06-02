/// <reference path="../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../statics/core/js/helpers.js" />
showGenericLoader('my-organization-profile-assign-administrator')
window.addEventListener('load', function () {

	removeGenericLoader('my-organization-profile-assign-administrator')

	const langGroup = 'global'
	registerDynamicLocalizationMessages(langGroup)

	/* Configuraciones iniciales */

	//Dropdowns
	configFomanticDropdown('.ui.dropdown.auto')
	configFomanticDropdown('.ui.dropdown.auto.additions', {
		allowAdditions: true,
	})

	//Tabs
	const tabs = $('.tabs-controls [data-tab]').tab({
		onVisible: function (tabName) {
		},
	})

	//Tooltip
	$('[data-tooltip]').popup()

	//Formulario
	profileForm()

	function profileForm() {

		//Cambio de administrador
		let changeAdminModalSelector = '.ui.modal[change-organization-admin-modal]'
		let changeAdminButton = $('.ui.form.my-organization-profile-assign-administrator button[change-organization-admin-trigger]')
		let changeAdminModal = $(changeAdminModalSelector).modal()
		changeAdminButton.on('click', function (e) {
			e.preventDefault()
			changeAdminModal.modal({
				onVisible: function () {

					const formModalSelector = `${changeAdminModalSelector} form.ui.form`
					let form = genericFormHandler(formModalSelector, {
						onSetFormData: function (formData) {
							return formData
						},
						onInvalidEvent: function (event) {

							let element = event.target
							let validationMessage = element.validationMessage
							let jElement = $(element)
							let field = jElement.closest('.field')
							let nameOnLabel = field.find('label').text().trim()

							errorMessage(`${nameOnLabel}: ${validationMessage}`)

							event.preventDefault()

						},
						confirmation: {
							title: _i18n(langGroup, 'Confirmación'),
							message: _i18n(langGroup, '¿Está seguro de cancelar la operación?'),
							positive: _i18n(langGroup, 'Sí'),
							negative: _i18n(langGroup, 'No'),
							selector: `${formModalSelector}`,
						},
					})
				}
			}).modal('show')
		})

	}


})
