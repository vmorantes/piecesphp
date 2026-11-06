var NewsCategories = {}

NewsCategories.configNewsCategoryForm = function (onSuccess = null, ignoreRedirection = false, ignoreReload = false) {

	showGenericLoader('configNewsCategoryForm')

	let isEdit = false
	let formSelector = `.ui.form.news-categories`

	//La tarjeta de imagen: lo aplicado en el editor se envía al guardar.
	let iconImage = null
	new ContentImageEditor().onApply('category-icon', (file) => iconImage = file)

	let form = genericFormHandler(formSelector, {
		onSuccess: typeof onSuccess == 'function' ? onSuccess : () => { },
		onSetFormData: function (formData) {
			if (iconImage !== null) {
				formData.set('iconImage', iconImage)
			}
			return formData
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
