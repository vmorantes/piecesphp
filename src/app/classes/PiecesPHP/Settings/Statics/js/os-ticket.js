/// <reference path="../../js/helpers.js" />
showGenericLoader('os-ticket')
window.addEventListener('load', () => {

	/**
	 * @param {FormData} formData 
	 */
	const onSetFormData = function (formData) {
		return formData
	}
	let form = genericFormHandler(`form.os-ticket`, {
		onSetFormData: (formData) => {
			return onSetFormData(formData)
		},
	})

	removeGenericLoader('os-ticket')

})
