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
		//«Otro…» manda lo escrito en su campo.
		form.find('select[data-ai-model-select][name]').each(function () {
			if ($(this).val() === OTHER) {
				formData.set(this.name, String($(this).closest('.field').find('[data-ai-model-other]').val() || '').trim())
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
	form.find('select.ui.dropdown').not('[data-ai-model-select]').dropdown()

	//Las pestañas (General, Proveedores, Funciones): la activa vive en la URL (#general, #proveedores, #funciones), y la
	//cuota de tokens, que es otro formulario, solo se ve con General.
	const quotaForm = $('form[data-ai-quota]')
	const tabItems = form.find('[data-ai-tabs] [data-tab]')
	const tabs = tabItems.tab({
		onVisible: function (tabName) {
			//Sobre la dirección de la página: el panel lleva <base href>, y un «#…» suelto se resuelve contra la portada.
			const url = new URL(window.location.href)
			url.hash = tabName
			history.replaceState(null, '', url)
			quotaForm.prop('hidden', tabName !== 'general')
		},
	})
	const tabFromURL = window.location.hash.replace('#', '')
	if (tabFromURL !== '' && form.find('[data-ai-tabs] [data-tab="' + CSS.escape(tabFromURL) + '"]').length > 0) {
		tabs.tab('change tab', tabFromURL)
	}
	quotaForm.prop('hidden', (form.find('[data-ai-tabs] .item.active').attr('data-tab') || 'general') !== 'general')
	//Un campo que no vale en una pestaña oculta: antes del aviso, se abre su pestaña y se le da el foco. El foco va a la
	//vuelta siguiente: genericFormHandler cancela el aviso del navegador (que no lo da) y pinta el suyo en el mismo turno.
	const showInvalid = function () {
		const invalid = form.get(0).querySelector('.ui.tab :invalid')
		if (invalid === null) {
			return
		}
		const panel = invalid.closest('.ui.tab[data-tab]')
		if (panel !== null && !panel.classList.contains('active')) {
			tabs.tab('change tab', panel.getAttribute('data-tab'))
		}
		setTimeout(function () {
			invalid.focus()
		}, 0)
	}
	form.on('click', 'button[type="submit"]', showInvalid)
	form.get(0).addEventListener('invalid', function (event) {
		const panel = event.target instanceof Element ? event.target.closest('.ui.tab[data-tab]') : null
		if (panel !== null && !panel.classList.contains('active')) {
			tabs.tab('change tab', panel.getAttribute('data-tab'))
		}
	}, true)

	//Los modelos: lista cerrada con las listas que trae la página (por registro: las del proveedor o las de respaldo, y las
	//recordadas), el valor actual y, al final, «Otro…», que enseña el campo para escribirlo. Cambiar de proveedor repuebla.
	const OTHER = '__other__'
	const providers = form.find('[data-ai-providers]')
	let modelLists = {}
	try {
		modelLists = JSON.parse(String(providers.attr('data-ai-models') || '{}')) || {}
	} catch (error) {
		modelLists = {}
	}
	const otherText = String(providers.attr('data-other-text') || '…')
	const fillModels = function (select, providerID, current) {
		const capability = String(select.attr('data-ai-model-capability') || 'chat')
		const list = modelLists[providerID] || {}
		const names = []
		const add = (name) => {
			if (typeof name === 'string' && name !== '' && !names.includes(name)) {
				names.push(name)
			}
		}
		const offered = Array.isArray(list[capability]) ? list[capability] : []
		const remembered = Array.isArray(list.remembered) ? list.remembered : []
		offered.forEach(add)
		remembered.forEach(add)
		add(current)
		const other = select.closest('.field').find('[data-ai-model-other]')
		select.empty()
		names.forEach((name) => select.append($('<option></option>').val(name).text(name)))
		select.append($('<option></option>').val(OTHER).text(otherText))
		select.val(current !== '' ? current : (names[0] || OTHER))
		select.attr('data-ai-model-provider', providerID)
		if (select.parent().hasClass('dropdown')) {
			select.dropdown('destroy')
		}
		select.dropdown({
			fullTextSearch: true,
			onChange: function (value) {
				other.prop('hidden', value !== OTHER)
			},
		})
		select.dropdown('set selected', select.val())
		other.prop('hidden', select.val() !== OTHER)
	}
	form.find('select[data-ai-model-select]').each(function () {
		const select = $(this)
		fillModels(select, String(select.attr('data-ai-model-provider') || ''), String(select.attr('data-current') || ''))
	})
	form.on('change', 'select[data-ai-capability-provider]', function () {
		const select = $(this).closest('[data-ai-capability]').find('select[data-ai-model-select]')
		fillModels(select, String($(this).val() || ''), '')
	})

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
	//El origen de la clave: escrita (campo y «Quitar la guardada») o del archivo (su nombre).
	form.on('change', 'select[data-ai-key-source]', function () {
		const row = $(this).closest('[data-ai-provider]')
		const fromFile = $(this).val() === 'file'
		row.find('[data-ai-key-stored]').prop('hidden', fromFile)
		row.find('[data-ai-key-file]').prop('hidden', !fromFile)
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
		row.find('select.ui.dropdown').not('[data-ai-model-select]').dropdown()
		row.find('select[data-ai-model-select]').each(function () {
			fillModels($(this), '', '')
		})
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
		data.set('keySource', field('keySource'))
		data.set('keyFile', field('keyFile'))
		const testSelect = row.find('select[data-ai-provider-test-model]')
		data.set('model', testSelect.val() === OTHER ? String(row.find('[data-ai-model-other]').val() || '').trim() : String(testSelect.val() || ''))
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

	//«Actualizar modelos»: pide la lista al proveedor de la fila guardada y repuebla los desplegables que lo usan.
	const modelsURL = String(providers.attr('data-models-url') || '')
	if (modelsURL === '') {
		form.find('[data-ai-provider-models-refresh]').remove()
	}
	form.on('click', '[data-ai-provider-models-refresh]', function () {
		const button = $(this)
		const row = button.closest('[data-ai-provider]')
		const providerID = String(row.attr('data-ai-provider-id') || '')
		const source = row.find('[data-ai-models-source]')
		const data = new FormData()
		data.set('id', providerID)
		button.addClass('loading disabled')
		postRequest(modelsURL, data)
			.done(function (response) {
				const message = typeof response.message === 'string' ? response.message : ''
				if (response.ok === true && Array.isArray(response.models)) {
					const list = modelLists[providerID] || {}
					list.chat = response.models
					list.speech = response.models
					list.source = 'provider'
					list.fetchedAt = response.fetchedAt
					modelLists[providerID] = list
					//Y-m-d H:i:s → d-m-Y H:i, como SettingsController::aiPeriodStartText() en la vista.
					const at = /^(\d{4})-(\d{2})-(\d{2}) (\d{2}:\d{2})/.exec(String(response.fetchedAt))
					const atText = at ? `${at[3]}-${at[2]}-${at[1]} ${at[4]}` : String(response.fetchedAt)
					source.text(String(providers.attr('data-provider-list-template') || '').replace('%fecha%', atText))
					form.find('select[data-ai-model-select]').each(function () {
						const select = $(this)
						if (String(select.attr('data-ai-model-provider') || '') === providerID) {
							const current = select.val() === OTHER ? '' : String(select.val() || '')
							fillModels(select, providerID, current)
						}
					})
					successMessage(typeof response.name === 'string' ? response.name : '', message)
				} else {
					errorMessage(typeof response.name === 'string' ? response.name : '', message)
				}
			})
			.fail(function (jqXHR) {
				const response = jqXHR.responseJSON || {}
				errorMessage(typeof response.name === 'string' ? response.name : '', typeof response.message === 'string' ? response.message : '')
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
