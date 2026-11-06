window.addEventListener('load', function () {

	let dataTable = null

	//La tabla de Registros › Sesiones abiertas, con sus filtros en la dirección de los datos.
	const table = $('table[data-sessions-table]')
	const form = document.querySelector('[data-sessions-filters]')
	if (table.length > 0 && form !== null) {
		const baseURL = table.attr('url')
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
		dataTable = dataTableServerProccesing(table, baseURL, 20, { responsive: false }).DataTable()
		form.addEventListener('submit', function (event) {
			event.preventDefault()
			dataTable.ajax.url(urlWithFilters()).load()
		})
		form.addEventListener('reset', function () {
			setTimeout(() => dataTable.ajax.url(baseURL).load(), 0)
		})
	}

	//Tras una acción: la tabla se recarga; una lista pintada en el servidor, la página.
	const refresh = function () {
		if (dataTable !== null) {
			dataTable.ajax.reload(null, false)
		} else {
			window.location.reload()
		}
	}

	const send = function (url, data, onDone) {
		postRequest(url, data)
			.done(function (response) {
				const title = typeof response.name === 'string' ? response.name : ''
				const message = typeof response.message === 'string' ? response.message : ''
				if (response.success === true) {
					successMessage(title, message)
					onDone(response)
				} else {
					errorMessage(title, message)
				}
			})
			.fail(function (jqXHR) {
				const response = jqXHR.responseJSON || {}
				errorMessage(typeof response.name === 'string' ? response.name : '', typeof response.message === 'string' ? response.message : '')
			})
	}

	const confirmAndSend = function (message, url, data, onDone) {
		confirmAction({ message: message }).then(function (confirmed) {
			if (confirmed) {
				send(url, data, onDone)
			}
		})
	}

	const modal = $('[data-session-extend-modal]').first()
	let extendURL = null
	if (modal.length > 0) {
		modal.modal({ autofocus: true })
		modal.find('[data-session-extend-cancel]').on('click', () => modal.modal('hide'))
		modal.find('[data-session-extend-confirm]').on('click', function () {
			const data = new FormData(modal.find('[data-session-extend-form]').get(0))
			send(extendURL, data, function () {
				modal.modal('hide')
				refresh()
			})
		})
	}

	//Delegado: DataTables vuelve a pintar las filas en cada página.
	document.addEventListener('click', function (event) {
		const close = event.target.closest('[data-session-close]')
		if (close !== null) {
			event.preventDefault()
			confirmAndSend(close.getAttribute('data-confirmation-message') || '', close.getAttribute('data-session-close'), new FormData(), function (response) {
				//Cerrar la sesión de esta pestaña es cerrar sesión: la cookie fuera y al acceso.
				if (response.values && response.values.current === true) {
					typeof pcsphp === 'object' && pcsphp.authenticator ? pcsphp.authenticator.deleteSession() : window.location.reload()
					return
				}
				refresh()
			})
			return
		}
		const others = event.target.closest('[data-session-close-others]')
		if (others !== null) {
			event.preventDefault()
			confirmAndSend(others.getAttribute('data-confirmation-message') || '', others.getAttribute('data-session-close-others'), new FormData(), refresh)
			return
		}
		const extend = event.target.closest('[data-session-extend]')
		if (extend !== null && modal.length > 0) {
			event.preventDefault()
			extendURL = extend.getAttribute('data-session-extend')
			//Como TEXTO: el techo lo pinta el servidor, pero no se mete como marcado.
			modal.find('[data-session-ceiling]').text(extend.getAttribute('data-session-ceiling') || '')
			modal.modal('show')
		}
	})

})
