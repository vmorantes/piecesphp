showGenericLoader('ai')
window.addEventListener('load', () => {

	/**
	 * @param {FormData} formData
	 */
	const onSetFormData = function (formData) {
		//Un toggle sin marcar no viaja: se manda siempre, 1 o 0.
		form.find('[data-ai-toggle]').each(function () {
			formData.set(this.name, $(this).parent().checkbox('is checked') ? 1 : 0)
		})
		//Sin ningún tipo elegido el campo no viaja: se manda un elemento vacío, que es la lista vacía («solo root»).
		form.find('select[data-ai-user-types]').each(function () {
			if (!formData.has(this.name)) {
				formData.append(this.name, '')
			}
		})
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

	//«N» solo cuenta con «Cada N días» y «Cada N meses». Sin JS se ve siempre, y no hace daño.
	const showLength = function (select) {
		const capability = $(select).closest('[data-ai-capability]')
		capability.find('[data-ai-period-length]').toggle(['every-days', 'every-months'].includes($(select).val()))
	}
	form.find('select[data-ai-period]').each(function () {
		showLength(this)
	}).on('change', function () {
		showLength(this)
	})

	//«Reiniciar el contador»: el periodo empieza ahora; lo gastado se conserva en su fila.
	form.find('[data-ai-usage-reset]').on('click', function () {
		const button = $(this)
		confirmAction({ message: $('<p></p>').text(button.data('confirm-message')).prop('outerHTML') }).then(function (confirmed) {
			if (!confirmed) {
				return
			}
			const data = new FormData()
			data.set('capability', button.attr('data-ai-usage-reset'))
			postRequest(button.data('url'), data)
				.done(function (response) {
					const title = typeof response.name === 'string' ? response.name : ''
					const message = typeof response.message === 'string' ? response.message : ''
					if (response.success === true) {
						const line = button.closest('[data-ai-capability]').find('[data-ai-usage]')
						const quota = parseInt(line.data('quota'), 10) || 0
						const template = String(quota > 0 ? line.data('template-quota') : line.data('template'))
						//Y-m-d H:i:s → d-m-Y H:i, como SettingsController::aiPeriodStartText() en la vista.
						const start = /^(\d{4})-(\d{2})-(\d{2}) (\d{2}:\d{2})/.exec(String(response.periodStart))
						const startText = start ? `${start[3]}-${start[2]}-${start[1]} ${start[4]}` : String(response.periodStart)
						line.text(template.replace('%usage%', String(response.usage)).replace('%quota%', String(quota)).replace('%start%', startText))
						successMessage(title, message)
					} else {
						errorMessage(title, message)
					}
				})
				.fail(function (jqXHR) {
					const response = jqXHR.responseJSON || {}
					errorMessage(typeof response.name === 'string' ? response.name : '', typeof response.message === 'string' ? response.message : '')
				})
		})
	})

	removeGenericLoader('ai')

})
