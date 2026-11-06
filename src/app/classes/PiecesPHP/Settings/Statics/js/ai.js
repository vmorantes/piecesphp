showGenericLoader('ai')
window.addEventListener('load', () => {

	/**
	 * @param {FormData} formData 
	 */
	const onSetFormData = function (formData) {
		formData.set('translationAIEnable', form.find(`[name="translationAIEnable"]`).parent().checkbox('is checked') ? 1 : 0)
		//Sin ningún tipo elegido el campo no viaja: se manda un elemento vacío, que es la lista vacía («solo root»).
		if (!formData.has('translationAIUserTypes[]')) {
			formData.append('translationAIUserTypes[]', '')
		}
		return formData
	}
	genericFormHandler(`form.tokens-limit`)
	let form = genericFormHandler(`form.ai`, {
		onSetFormData: (formData) => {
			return onSetFormData(formData)
		},
	})

	form.find('.ui.checkbox').checkbox()
	form.find('select.ui.dropdown').dropdown()

	removeGenericLoader('ai')

})
