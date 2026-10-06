/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
window.addEventListener('load', function () {

	const hub = document.querySelector('[data-transfer-hub]')
	if (hub === null || typeof $ === 'undefined') {
		return
	}

	//La pestaña activa vive en la URL (#importar / #exportar): recargar no la pierde.
	const tabs = $('.tabs-controls [data-tab]').tab({
		onVisible: function (tabName) {
			history.replaceState(null, '', '#' + tabName)
		},
	})
	const fromURL = window.location.hash.replace('#', '')
	if (fromURL !== '' && hub.querySelector('.tabs-controls [data-tab="' + CSS.escape(fromURL) + '"]') !== null) {
		tabs.tab('change tab', fromURL)
	}

	//Interruptor de root: si el servidor no lo confirma, vuelve a como estaba.
	const toggleURL = hub.getAttribute('data-toggle-url')
	hub.querySelectorAll('[data-transfer-toggle]').forEach(function (input) {
		input.addEventListener('change', function () {
			const enabled = input.checked
			const body = new FormData()
			body.set('kind', input.getAttribute('data-kind'))
			body.set('key', input.getAttribute('data-key'))
			body.set('enabled', enabled ? 'yes' : 'no')
			input.disabled = true
			fetch(toggleURL, { method: 'POST', body: body, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
				.then(function (response) {
					return response.json().then(function (data) {
						if (!response.ok || data.enabled !== enabled) {
							throw new Error(typeof data.error === 'string' ? data.error : String(response.status))
						}
						const row = input.closest('tr')
						row.querySelectorAll('td').forEach(function (cell) {
							if (!cell.contains(input)) {
								cell.classList.toggle('disabled', !enabled)
							}
						})
					})
				})
				.catch(function (error) {
					input.checked = !enabled
					errorMessage(_i18n('DataTransfer', 'Error'), error.message)
				})
				.finally(function () {
					input.disabled = false
				})
		})
	})
})
