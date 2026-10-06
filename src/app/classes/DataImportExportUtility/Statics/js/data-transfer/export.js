/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
window.addEventListener('load', function () {

	const form = document.querySelector('[data-transfer-export]')
	const errors = document.querySelector('[data-transfer-export-errors]')
	if (form === null || errors === null) {
		return
	}

	if (typeof $ !== 'undefined' && typeof $.fn.dropdown === 'function') {
		$(form).find('.ui.dropdown').dropdown()
	}

	//Los errores pueden traer lo que escribió el usuario: se pintan como texto, nunca como HTML.
	const showErrors = function (list) {
		errors.replaceChildren()
		const box = document.createElement('div')
		box.className = 'ui negative message'
		const items = document.createElement('ul')
		items.className = 'list'
		for (const text of list) {
			const item = document.createElement('li')
			item.textContent = String(text)
			items.appendChild(item)
		}
		box.appendChild(items)
		errors.appendChild(box)
	}

	//Las casillas viajan en el orden del DOM: mover el elemento es mover la columna.
	form.querySelectorAll('[data-transfer-column]').forEach(function (item) {
		const move = function (up) {
			const sibling = up ? item.previousElementSibling : item.nextElementSibling
			if (sibling === null) {
				return
			}
			if (up) {
				sibling.before(item)
			} else {
				sibling.after(item)
			}
			const button = item.querySelector(up ? '[data-transfer-column-up]' : '[data-transfer-column-down]')
			if (button !== null) {
				button.focus()
			}
		}
		const up = item.querySelector('[data-transfer-column-up]')
		const down = item.querySelector('[data-transfer-column-down]')
		if (up !== null) {
			up.addEventListener('click', function () { move(true) })
		}
		if (down !== null) {
			down.addEventListener('click', function () { move(false) })
		}
	})

	const withQuery = function (base) {
		return base + (base.includes('?') ? '&' : '?') + new URLSearchParams(new FormData(form)).toString()
	}

	//Vista previa: los datos se pintan con textContent, nunca como HTML.
	const previewButton = form.querySelector('[data-transfer-preview]')
	const previewResult = document.querySelector('[data-transfer-preview-result]')
	if (previewButton !== null && previewResult !== null) {
		previewButton.addEventListener('click', function () {
			errors.replaceChildren()
			previewResult.replaceChildren()
			previewButton.classList.add('loading', 'disabled')
			fetch(withQuery(previewButton.getAttribute('data-transfer-preview')), { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
				.then(function (response) {
					return response.json().then(function (data) {
						if (!response.ok) {
							showErrors(Array.isArray(data.errors) ? data.errors : [data.error || response.statusText])
							return
						}
						const table = document.createElement('table')
						table.className = 'ui celled compact table'
						const head = table.createTHead().insertRow()
						for (const label of data.columns) {
							const cell = document.createElement('th')
							cell.textContent = String(label)
							head.appendChild(cell)
						}
						const body = table.createTBody()
						for (const row of data.rows) {
							const line = body.insertRow()
							for (const value of row) {
								line.insertCell().textContent = String(value)
							}
						}
						previewResult.appendChild(table)
						if (data.truncated) {
							const note = document.createElement('small')
							note.textContent = _i18n('DataTransfer', 'Se muestran solo las primeras filas.')
							previewResult.appendChild(note)
						}
					})
				})
				.catch(function () {
					showErrors([_i18n('DataTransfer', 'No se pudo cargar la vista previa.')])
				})
				.finally(function () {
					previewButton.classList.remove('loading', 'disabled')
				})
		})
	}

	//Filtros guardados: cargar rellena el formulario aquí; guardar y borrar van al servidor con el usuario de la sesión.
	const presets = form.querySelector('[data-transfer-presets]')
	if (presets !== null) {
		const select = presets.querySelector('[data-transfer-preset-select]')
		const fill = function (query) {
			form.querySelectorAll('input[name], select[name]').forEach(function (field) {
				if (field === select || field.name === 'columns[]') {
					return
				}
				const key = field.name.replace(/\[\]$/, '')
				const value = query[key]
				if (field.multiple) {
					const list = Array.isArray(value) ? value.map(String) : []
					Array.from(field.options).forEach(function (option) { option.selected = list.includes(option.value) })
				} else if (field.type !== 'checkbox') {
					field.value = value === undefined || value === null ? '' : String(value)
				}
				if (typeof $ !== 'undefined' && field.tagName === 'SELECT' && $(field).parent().hasClass('dropdown')) {
					$(field).dropdown('set exactly', field.multiple ? Array.from(field.selectedOptions).map(function (o) { return o.value }) : field.value)
				}
			})
			const chosen = Array.isArray(query.columns) ? query.columns.map(String) : (typeof query.columns === 'string' && query.columns !== '' ? query.columns.split(',') : [])
			const list = form.querySelector('[data-transfer-columns] ol')
			if (list !== null) {
				const items = Array.from(list.querySelectorAll('[data-transfer-column]'))
				items.forEach(function (item) {
					const box = item.querySelector('input[name="columns[]"]')
					box.checked = chosen.length === 0 || chosen.includes(box.value)
				})
				chosen.slice().reverse().forEach(function (key) {
					const item = items.find(function (i) { return i.querySelector('input[name="columns[]"]').value === key })
					if (item !== undefined) {
						list.prepend(item)
					}
				})
			}
		}
		const refresh = function (data) {
			select.querySelectorAll('option[value]:not([value=""])').forEach(function (option) { option.remove() })
			for (const name of Object.keys(data.presets || {})) {
				const option = document.createElement('option')
				option.value = name
				option.textContent = name
				option.setAttribute('data-query', JSON.stringify(data.presets[name]))
				select.appendChild(option)
			}
		}
		const send = function (url, body) {
			errors.replaceChildren()
			return fetch(url, { method: 'POST', body: body, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
				.then(function (response) {
					return response.json().then(function (data) {
						if (!response.ok) {
							showErrors(Array.isArray(data.errors) ? data.errors : [data.error || response.statusText])
							return
						}
						refresh(data)
					})
				})
				.catch(function () {
					showErrors([_i18n('DataTransfer', 'No se pudieron guardar los filtros.')])
				})
		}
		select.addEventListener('change', function () {
			const option = select.selectedOptions[0]
			if (option === undefined || option.value === '') {
				return
			}
			try {
				fill(JSON.parse(option.getAttribute('data-query') || '{}'))
			} catch (e) {
				showErrors([_i18n('DataTransfer', 'No se pudieron cargar los filtros.')])
			}
		})
		presets.querySelector('[data-transfer-preset-save]').addEventListener('click', function () {
			const name = window.prompt(_i18n('DataTransfer', 'Nombre para estos filtros'), select.value || '')
			if (name === null) {
				return
			}
			const body = new FormData(form)
			body.delete(select.name || '')
			body.set('presetName', name)
			send(presets.getAttribute('data-save-url'), body)
		})
		presets.querySelector('[data-transfer-preset-delete]').addEventListener('click', function () {
			if (select.value === '') {
				return
			}
			const body = new FormData()
			body.set('presetName', select.value)
			send(presets.getAttribute('data-delete-url'), body)
		})
	}

	const fileName = function (disposition) {
		const match = /filename="([^"]+)"/.exec(disposition || '')
		return match !== null ? match[1] : 'export'
	}

	form.addEventListener('submit', function (event) {
		event.preventDefault()
		errors.replaceChildren()
		const button = form.querySelector('button[type="submit"]')
		button.classList.add('loading', 'disabled')
		const url = withQuery(form.action)
		fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
			.then(function (response) {
				if (response.ok) {
					return response.blob().then(function (blob) {
						const link = document.createElement('a')
						link.href = URL.createObjectURL(blob)
						link.download = fileName(response.headers.get('Content-Disposition'))
						document.body.appendChild(link)
						link.click()
						link.remove()
						URL.revokeObjectURL(link.href)
					})
				}
				return response.json().then(function (data) {
					showErrors(Array.isArray(data.errors) ? data.errors : [data.error || response.statusText])
				})
			})
			.catch(function () {
				showErrors([_i18n('DataTransfer', 'No se pudo completar la exportación.')])
			})
			.finally(function () {
				button.classList.remove('loading', 'disabled')
			})
	})
})
