/// <reference path="../../js/helpers.js" />
window.addEventListener('load', () => {
	//Brand colors
	let formBrandColors = $('form[brand-colors]')
	formBrandColors.on('submit', function (e) {

		e.preventDefault()
		const elements = Array.from(formBrandColors.find('[form-element]'))

		for (let element of elements) {
			const inputValue = element.querySelector("[name='value']")
			const inputName = element.querySelector("[name='name']")
			const inputParse = element.querySelector("[name='parse']")
			const uniqueIDForm = generateUniqueID()
			let internalForm = $(
				`<form id="${uniqueIDForm}" action='${formBrandColors.attr('action')}' method='${formBrandColors.attr('method')}'>
					${inputValue.outerHTML}
					${inputName.outerHTML}
					${inputParse.outerHTML}
				</form>`
			)
			internalForm.find("[name='value']").val(inputValue.value)
			$('body').append(internalForm)
			internalForm = genericFormHandler(`form#${uniqueIDForm}`, {
				onSuccess: function () {
					internalForm.remove()
				},
			})
			internalForm.trigger('submit')
		}

	})

	//Menu colors
	let formMenuColors = $('form[menu-colors]')
	formMenuColors.on('submit', function (e) {

		e.preventDefault()
		const elements = Array.from(formMenuColors.find('[form-element]'))

		for (let element of elements) {
			const inputValue = element.querySelector("[name='value']")
			const inputName = element.querySelector("[name='name']")
			const inputParse = element.querySelector("[name='parse']")
			const uniqueIDForm = generateUniqueID()
			let internalForm = $(
				`<form id="${uniqueIDForm}" action='${formMenuColors.attr('action')}' method='${formMenuColors.attr('method')}'>
					${inputValue.outerHTML}
					${inputName.outerHTML}
					${inputParse.outerHTML}
				</form>`
			)
			internalForm.find("[name='value']").val(inputValue.value)
			$('body').append(internalForm)
			internalForm = genericFormHandler(`form#${uniqueIDForm}`, {
				onSuccess: function () {
					internalForm.remove()
				},
			})
			internalForm.trigger('submit')
		}

	})
})
