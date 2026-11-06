window.addEventListener('load', function () {

	const table = $('table[data-actions-log-table]')
	const baseURL = table.attr('url')
	const form = document.querySelector('[data-actions-log-filters]')
	$(form).find('select.ui.dropdown').dropdown({ fullTextSearch: true })

	//Los filtros van en la dirección de los datos: el servidor los aplica con marcadores.
	const urlWithFilters = function () {
		const params = new URLSearchParams()
		for (const [name, value] of new FormData(form).entries()) {
			if (typeof value === 'string' && value.trim() !== '') {
				params.set(name, value.trim())
			}
		}
		const query = params.toString()
		return query === '' ? baseURL : baseURL + (baseURL.includes('?') ? '&' : '?') + query
	}

	const dataTable = dataTableServerProccesing(table, baseURL, 20, {
		responsive: false,
	}).DataTable()

	form.addEventListener('submit', function (event) {
		event.preventDefault()
		dataTable.ajax.url(urlWithFilters()).load()
	})
	form.addEventListener('reset', function () {
		setTimeout(function () {
			$(form).find('select.ui.dropdown').dropdown('clear')
			dataTable.ajax.url(baseURL).load()
		}, 0)
	})

	const modal = document.querySelector('[data-actions-log-modal]')
	const fill = function (data) {
		for (const cell of modal.querySelectorAll('[data-detail]')) {
			const key = cell.getAttribute('data-detail')
			const value = data[key]
			//Como TEXTO, nunca como marcado.
			cell.textContent = (key === 'variables' || key === 'meta') ? JSON.stringify(value || {}, null, 2) : (value || '')
		}
		$(modal).modal('show')
	}

	//Delegado: DataTables vuelve a pintar las filas en cada página.
	table.get(0).addEventListener('click', function (event) {
		const button = event.target.closest('[data-actions-log-detail]')
		if (button === null) {
			return
		}
		event.preventDefault()
		fetch(button.getAttribute('data-actions-log-detail'), {
			credentials: 'same-origin',
			headers: { 'X-Requested-With': 'XMLHttpRequest' },
		})
			.then((response) => response.json())
			.then(function (data) {
				if (typeof data.error === 'string') {
					errorMessage(data.error)
					return
				}
				fill(data)
			})
			.catch(() => errorMessage(modal.getAttribute('data-error-text') || ''))
	})

})
