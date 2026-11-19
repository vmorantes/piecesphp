showGenericLoader('accessibility')
window.addEventListener('load', () => {

	const form = $('form[accessibility-form]')
	const selects = form.find('select[accessibility-select]')
	selects.dropdown()

	//Cada campo, a su data-* en el <html> de las dos muestras (text_size → data-text-size); el modo no: cada una fija el suyo.
	const preview = function () {
		if (this.name === 'accessibility_theme_mode') {
			return
		}
		const attribute = 'data-' + this.name.replace(/^accessibility_/, '').replace(/_/g, '-')
		const value = $(this).val()
		document.querySelectorAll('iframe[data-style-preview]').forEach((frame) => {
			if (frame.contentDocument) {
				frame.contentDocument.documentElement.setAttribute(attribute, value)
			}
		})
	}
	selects.on('change', preview)

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
