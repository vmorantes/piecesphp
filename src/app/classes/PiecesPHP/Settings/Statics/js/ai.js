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
		const owner = $(select).closest('[data-ai-capability], [data-ai-provider]')
		owner.find('[data-ai-period-length]').toggle(['every-days', 'every-months'].includes($(select).val()))
	}
	form.on('change', 'select[data-ai-period]', function () {
		showLength(this)
	})
	form.find('select[data-ai-period]').each(function () {
		showLength(this)
	})

	//Los proveedores: la dirección y la cabecera solo con «Personalizado»; añadir copia la plantilla; quitar la saca del
	//formulario (al guardar, el servidor rechaza quitar uno que una función usa).
	const showCustom = function (select) {
		$(select).closest('[data-ai-provider]').find('[data-ai-provider-custom]').prop('hidden', $(select).val() !== 'custom')
	}
	form.on('change', 'select[data-ai-provider-type]', function () {
		showCustom(this)
	})
	const list = form.find('[data-ai-providers-list]')
	const addButton = form.find('[data-ai-provider-add]')
	const template = form.find('template[data-ai-provider-template]').get(0)
	addButton.on('click', function () {
		const index = parseInt(addButton.attr('data-next-index'), 10) || 0
		addButton.attr('data-next-index', String(index + 1))
		const row = $(template.innerHTML.replaceAll('__INDEX__', `new${index}`))
		list.append(row)
		form.find('[data-ai-providers-empty]').remove()
		row.find('select.ui.dropdown').dropdown()
		row.find('.ui.checkbox').checkbox()
		row.find('select[data-ai-period]').each(function () {
			showLength(this)
		})
		row.find('input[type="text"]').first().trigger('focus')
	})
	form.on('click', '[data-ai-provider-remove]', function () {
		$(this).closest('[data-ai-provider]').remove()
	})

	//«Probar»: una llamada mínima con los campos de la fila (o su id, si está guardada); no guarda nada.
	const testURL = String(form.find('[data-ai-providers]').attr('data-test-url') || '')
	if (testURL === '') {
		form.find('[data-ai-provider-test]').remove()
	}
	form.on('click', '[data-ai-provider-test]', function () {
		const button = $(this)
		const row = button.closest('[data-ai-provider]')
		const result = row.find('[data-ai-provider-test-result]')
		const field = (name) => String(row.find(`[name$="[${name}]"]`).val() || '')
		const data = new FormData()
		data.set('id', field('id'))
		data.set('type', field('type'))
		data.set('alias', field('alias'))
		data.set('key', field('key'))
		data.set('baseUrl', field('baseUrl'))
		data.set('keyHeader', field('keyHeader'))
		data.set('model', String(row.find('[data-ai-provider-test-model]').val() || ''))
		button.addClass('loading disabled')
		result.text('')
		postRequest(testURL, data)
			.done(function (response) {
				result.text(typeof response.message === 'string' ? response.message : '')
				result.toggleClass('green', response.ok === true).toggleClass('red', response.ok !== true)
			})
			.fail(function (jqXHR) {
				const response = jqXHR.responseJSON || {}
				result.text(typeof response.message === 'string' ? response.message : '').addClass('red').removeClass('green')
			})
			.always(function () {
				button.removeClass('loading disabled')
			})
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
