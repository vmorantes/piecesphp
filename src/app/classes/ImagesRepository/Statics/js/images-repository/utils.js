/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
/// <reference path="../../../../../../statics/core/own-plugins/SimpleUploadPlaceholder.js" />
/// <reference path="../../../../../../statics/core/own-plugins/LocationsAdapter.js" />
var ImagesRepository = {}

ImagesRepository.configAddImageRepository = function () {

	showGenericLoader('configAddImageRepository')

	let formSelector = `.ui.form[add-image-repository]`
	let langGroup = 'appImagesRepositoryLang'
	let isEdit = false

	configFomanticDropdown('.ui.dropdown.auto') //Debe inciarse antes de genericFormHandler para la validación

	let locations = new LocationsAdapter()

	locations.fillSelectWithStates(1)

	locations.setOnChangeStateDropdown(function (value, text, $element) {
		configForm()
	})

	let form = null
	let placeholderImage = null
	let placeholderFile = null

	configForm()

	if (form.length > 0) {

		placeholderImage = new SimpleUploadPlaceholder({
			containerSelector: '[simple-upload-placeholder-image]',
			onReady: function () {
			},
			onChangeFile: (files, component, instance, event) => {

				let file = files.length > 0 ? files[0] : null

				if (file !== null) {

					let sizeFileMB = (file.size / (1024 * 1024)).toFixed(2)
					let sizeDisplay = form.find('[size-display]')

					if (sizeDisplay.length > 0) {
						sizeDisplay.find('.text').text(sizeFileMB)
					}

				}

			},
			onImagePreview: (image) => {

				let width = image.width
				let height = image.height
				let aspectRatio = simplify(width, height)
				let resolutionDisplay = form.find('[resolution-display]')
				let resolutionInput = form.find('[name="resolution"]')

				if (resolutionDisplay.length > 0) {
					resolutionDisplay.find('.text').text(`${width}x${height} px (${aspectRatio.numerator}:${aspectRatio.denominator})`)
				}

				resolutionInput.val(`${width}x${height}`)

			},
		})

		placeholderFile = new SimpleUploadPlaceholder({
			containerSelector: '[simple-upload-placeholder-file]',
			onReady: function () {
			},
			onChangeFile: (files, component, instance, event) => {
			},
		})

	}

	isEdit = form.find(`[name="id"]`).length > 0

	form.find('input, select, textarea').attr('autocomplete', 'off')
	$('.tabular.menu .item').tab()

	configLangChange('.ui.dropdown.langs')

	function configForm() {
		form = genericFormHandler(formSelector, {
			onSetFormData: function (formData) {
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

			}
		})
	}

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

	removeGenericLoader('configAddImageRepository')

}
