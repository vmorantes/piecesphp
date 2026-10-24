/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
//El inicio del panel: plegar y desplegar una tarjeta se guarda por usuario y recarga, porque plegada no trae su contenido.
window.addEventListener('load', function () {

	const dashboard = document.querySelector('[data-dashboard]')
	if (dashboard === null) {
		return
	}
	dashboard.addEventListener('click', function (event) {
		const button = event.target.closest('[data-dashboard-card-toggle]')
		if (button === null) {
			return
		}
		button.classList.add('loading', 'disabled')
		const body = new FormData()
		body.set('key', button.getAttribute('data-dashboard-card-toggle'))
		body.set('collapsed', button.getAttribute('data-collapsed'))
		fetch(dashboard.getAttribute('data-collapse-url'), { method: 'POST', body: body, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
			.then((response) => response.json())
			.then(function (data) {
				if (data.success === true) {
					location.reload()
				} else {
					button.classList.remove('loading', 'disabled')
					errorMessage(_i18n('adminZone', 'No se pudo guardar.'))
				}
			})
			.catch(function () {
				button.classList.remove('loading', 'disabled')
				errorMessage(_i18n('adminZone', 'No se pudo guardar.'))
			})
	})
})
