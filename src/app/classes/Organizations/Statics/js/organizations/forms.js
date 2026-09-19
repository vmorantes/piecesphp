/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
/// <reference path="../../../../../../statics/core/own-plugins/SimpleCropperAdapter.js" />
/// <reference path="../../../../../../statics/core/own-plugins/AttachmentPlaceholder.js" />
/// <reference path="../../../../../../statics/core/own-plugins/LocationsAdapter.js" />
showGenericLoader('_CARGA_INICIAL_')
window.addEventListener('load', function () {

	window.dispatchEvent(new Event('canDeleteOrganization'))

	let isEdit = false
	let formSelector = `.ui.form.organizations`
	let langGroup = 'appOrganizationsLang'
	let attachmentLogo = new AttachmentPlaceholder($('.attach-placeholder.logo'))
	let attachmentRut = new AttachmentPlaceholder($('.attach-placeholder.rut'))
	let cropperLogo = new SimpleCropperAdapter(`[logo-cropper]`, {
		aspectRatio: 1 / 1,
		format: 'image/jpeg',
		quality: 0.8,
		fillColor: 'white',
		outputWidth: 400,
	})

	//Dropdowns en general
	configFomanticDropdown('.ui.dropdown:not(.langs):not(.no-auto)') //Debe inciarse antes de genericFormHandler para la validación

	//Líneas de acción
	configFomanticDropdown('.ui.dropdown[name="actionLines[]"]', {
		allowAdditions: true,
	})

	//Ubicación	
	let locations = new LocationsAdapter()
	locations.fillSelectWithCountriesToCities()

	//Formulario
	let form = genericFormHandler(formSelector, {
		onSetFormData: function (formData) {

			if (isEdit) {
				formData.set('rut', attachmentRut.getSelectedFile())
				if (cropperLogo.wasChange()) {
					formData.set('logo', cropperLogo.getFile(null, null, null, null, true))
				}
			} else {
				formData.set('rut', attachmentRut.getSelectedFile())
				if (cropperLogo.wasChange()) {
					formData.set('logo', cropperLogo.getFile())
				}
			}

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

	//Adjuntos
	attachmentLogo.scopeAction(function (instance, elements) {

		const modal = $(`[modal="logo-cropper"]`)
		let cropperLogoFirstDraw = true

		cropperLogo.onCancel(() => modal.modal('hide'))
		cropperLogo.onCropped((blobImage, settedImage) => {
			instance.setFile(blobImage)
			modal.modal('hide')
		})

		instance.onClick(function (instance, elements, event) {
			event.preventDefault()
			modal.modal({
				onVisible: function () {
					if (cropperLogoFirstDraw) {
						cropperLogo.refresh()
						cropperLogoFirstDraw = false
					}
				},
			}).modal('show')
		})

	})

	//Tabs
	const tabs = $('.tabs-controls [data-tab]').tab({
		onVisible: function (tabName) {
		}
	})

	//Otros
	form.find('input, select, textarea').attr('autocomplete', 'off')
	form.find('.checkbox').checkbox()
	$('.ui.accordion').accordion()

	isEdit = form.find(`[name="id"]`).length > 0

	//Idiomas
	configLangChange('.ui.dropdown.langs')
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

	removeGenericLoader('_CARGA_INICIAL_')

})


