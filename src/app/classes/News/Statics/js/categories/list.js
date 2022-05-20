/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
window.addEventListener('load', function () {

	let tableSelector = "table[url]"
	let table = $(tableSelector)
	let tableURLAttr = "url"

	const dataTable = dataTableServerProccesing(table, table.attr(tableURLAttr), 20, {
		responsive: false,
		drawCallback: function () {
			window.dispatchEvent(new Event('canDeleteNewsCategory'))
			configMirrorScrollX()
		},
	}).DataTable()

	formConfig()

	function formConfig() {

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
			onSuccess: function (formProcess) {
				formProcess[0].reset()
				formProcess.find('.ui.dropdown').dropdown('clear')
				formProcess.find('.ui.dropdown').dropdown('refresh')
				dataTable.draw()
			},
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
			ignoreRedirection: true,
			ignoreReload: true,
		})

		form.find('input, select, textarea').attr('autocomplete', 'off')
		form.find('.ui.dropdown').dropdown()
	}

})
