/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
/// <reference path="../../../../../../statics/core/own-plugins/LocationsAdapter.js" />
var NewsCategories = {}

NewsCategories.configNewsCategoryForm = function (onSuccess = null, ignoreRedirection = false, ignoreReload = false) {

	showGenericLoader('configNewsCategoryForm')

	let isEdit = false
	let formSelector = `.ui.form.news-categories`

	const images = {}

	let indexAttachment = 1
	for (const imageElement of Array.from(document.querySelectorAll('[image-element]'))) {
		imageElement.setAttribute(`image-${indexAttachment}`, '')
		images[indexAttachment] = new SimpleUploadPlaceholder({
			containerSelector: `[image-element][image-${indexAttachment}]`,
			onReady: function () {
			},
			onChangeFile: (files, component, instance, event) => {
				const fileInput = files[0]
				if (isEdit) {
					const previewContainer = $(`[image-element][image-${indexAttachment}] [preview]`)
					if (fileInput.type.indexOf('image/') !== -1) {
						const reader = new FileReader()
						reader.readAsDataURL(fileInput)
						reader.onload = function (e) {
							previewContainer.html(`<img src="${e.target.result}"/>`)
						}
					} else {
						previewContainer.html('')
					}
				}
			},
		})
		indexAttachment++
	}

	let form = genericFormHandler(formSelector, {
		onSuccess: typeof onSuccess == 'function' ? onSuccess : () => { },
		onInvalidEvent: function (event) {

			let element = event.target
			let validationMessage = element.validationMessage
			let jElement = $(element)
			let field = jElement.closest('.field')

			let title = field.find('label').length > 0 ? field.find('label').html() : null

			if (title === null) {
				let placeholder1 = jElement.attr('placeholder')
				placeholder1 = typeof placeholder1 == 'string' && placeholder1.length > 0 ? placeholder1 : ''
				let placeholder2 = field.attr('placeholder')
				placeholder2 = typeof placeholder2 == 'string' && placeholder2.length > 0 ? placeholder2 : ''
				title = placeholder1.length > 0 ? placeholder1 : placeholder2
			}

			errorMessage(`${title.replace('*', '').trim()}: ${validationMessage}`)

			event.preventDefault()

		},
		ignoreRedirection: ignoreRedirection,
		ignoreReload: ignoreReload,
	})

	form.find('input, select, textarea').attr('autocomplete', 'off')
	form.find('.ui.dropdown').dropdown()

	isEdit = form.find(`[name="id"]`).length > 0

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
				const url = new URL(window.location.href)
				url.searchParams.set('lang', value)
				window.location.href = url.href
			},
		})

	}

	removeGenericLoader('configNewsCategoryForm')

}
