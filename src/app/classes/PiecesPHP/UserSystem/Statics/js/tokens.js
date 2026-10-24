/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
/// <reference path="../../../../../../statics/core/js/helpers-lib/confirmAction.js" />
window.addEventListener('load', function () {

	//Registros › Tokens y códigos: los vigentes y su historia, por servidor.
	const tables = []
	for (const selector of ['table[data-tokens-table]', 'table[data-tokens-history]']) {
		const table = $(selector)
		if (table.length > 0) {
			tables.push(dataTableServerProccesing(table, table.attr('url'), 20, { responsive: false }).DataTable())
		}
	}
	const refresh = () => tables.forEach((table) => table.ajax.reload(null, false))

	//El valor va como TEXTO: el aviso de Fomantic pinta HTML.
	const escape = (text) => String(text).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c])

	const send = function (url, onDone) {
		postRequest(url, new FormData())
			.done(function (response) {
				const title = typeof response.name === 'string' ? response.name : ''
				const message = typeof response.message === 'string' ? response.message : ''
				if (response.success === true) {
					onDone(response, title, message)
				} else {
					errorMessage(title, message)
				}
			})
			.fail(function (jqXHR) {
				const response = jqXHR.responseJSON || {}
				errorMessage(typeof response.name === 'string' ? response.name : '', typeof response.message === 'string' ? response.message : '')
			})
	}

	//Delegado: DataTables vuelve a pintar las filas en cada página.
	document.addEventListener('click', function (event) {
		const reveal = event.target.closest('[data-token-reveal]')
		const action = event.target.closest('[data-token-action]')
		const button = reveal || action
		if (button === null) {
			return
		}
		event.preventDefault()
		const url = button.getAttribute(reveal !== null ? 'data-token-reveal' : 'data-token-action')
		confirmAction({ message: button.getAttribute('data-confirmation-message') || '' }).then(function (confirmed) {
			if (!confirmed) {
				return
			}
			send(url, function (response, title, message) {
				if (reveal !== null) {
					const value = response.values && typeof response.values.value === 'string' ? response.values.value : ''
					confirmAction({
						title: title,
						message: '<p><strong>' + escape(value) + '</strong></p><p>' + escape(message) + '</p>',
						confirmText: _i18n('confirmAction', 'Aceptar'),
						cancelText: null,
					})
					refresh()
					return
				}
				successMessage(title, message)
				refresh()
			})
		})
	})

})
