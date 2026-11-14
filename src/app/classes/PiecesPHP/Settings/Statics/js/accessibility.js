showGenericLoader('accessibility')
window.addEventListener('load', () => {

	const form = $('form[accessibility-form]')
	const selects = form.find('select[accessibility-select]')
	selects.dropdown()

	/** @returns {Object} Lo que hay en el formulario: a esto vuelve Cancelar */
	const currentValues = () => {
		const values = {}
		selects.each(function () {
			values[this.name] = $(this).val()
		})
		return values
	}

	let initial = currentValues()

	form.find('[accessibility-cancel]').on('click', function () {
		selects.each(function () {
			$(this).dropdown('set selected', initial[this.name])
		})
	})

	genericFormHandler(form, {
		ignoreRedirection: true,
		ignoreReload: true,
		onSuccess: function () {
			initial = currentValues()
		},
	})

	removeGenericLoader('accessibility')

})
