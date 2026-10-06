/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
window.addEventListener('load', function () {

	const post = function (url, body) {
		return fetch(url, { method: 'POST', body: body, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
			.then(function (response) {
				return response.json().then(function (data) {
					if (!response.ok) {
						throw new Error(typeof data.error === 'string' ? data.error : String(response.status))
					}
					return data
				})
			})
	}

	//Avisos: ocultar o mostrar un aviso ocultable. Si el servidor no lo confirma, vuelve a como estaba.
	const alerts = document.querySelector('[data-system-alerts]')
	if (alerts !== null) {
		const toggleURL = alerts.getAttribute('data-toggle-url')
		alerts.querySelectorAll('[data-system-alert-toggle]').forEach(function (input) {
			input.addEventListener('change', function () {
				const hidden = input.checked
				const body = new FormData()
				body.set('key', input.getAttribute('data-key'))
				body.set('hidden', hidden ? 'yes' : 'no')
				input.disabled = true
				post(toggleURL, body)
					.then(function () {
						const state = input.closest('tr').querySelector('[data-system-alert-state]')
						if (state !== null) {
							state.textContent = hidden ? _i18n('system-status', 'Oculto') : _i18n('system-status', 'Activo')
						}
					})
					.catch(function (error) {
						input.checked = !hidden
						errorMessage(_i18n('system-status', 'Error'), error.message)
					})
					.finally(function () {
						input.disabled = false
					})
			})
		})
	}

	//Mantenimiento: cada acción pide confirmación y enseña su resultado como texto.
	const maintenance = document.querySelector('[data-system-maintenance]')
	if (maintenance !== null) {
		const result = maintenance.querySelector('[data-system-maintenance-result]')
		const show = function (lines, negative) {
			result.replaceChildren()
			const box = document.createElement('div')
			box.className = negative ? 'ui negative message' : 'ui positive message'
			const list = document.createElement('ul')
			list.className = 'list'
			for (const line of lines) {
				const item = document.createElement('li')
				item.textContent = String(line)
				list.appendChild(item)
			}
			box.appendChild(list)
			result.appendChild(box)
		}
		//El mismo formato que la vista (maintenance.php): MB con un decimal a partir de 1048576, si no KB.
		const tamano = function (bytes) {
			const n = Number(bytes) || 0
			return n >= 1048576 ? (n / 1048576).toFixed(1) + ' MB' : (n / 1024).toFixed(1) + ' KB'
		}
		const pintarEstado = function (status) {
			const celda = function (selector, texto) {
				const nodo = maintenance.querySelector(selector)
				if (nodo !== null) {
					nodo.textContent = texto
				}
			}
			celda('[data-system-maintenance-links]', String(status.linksTotal) + ' · ' + _i18n('system-status', 'rotos') + ': ' + String(status.linksBroken))
			celda('[data-system-maintenance-webp]', tamano(status.webpCacheBytes))
			celda('[data-system-maintenance-publications]', tamano(status.publicationsCacheBytes))
			celda('[data-system-maintenance-stamp]', String(status.staticsStamp))
		}
		maintenance.querySelectorAll('[data-system-maintenance-action]').forEach(function (button) {
			button.addEventListener('click', function () {
				if (!window.confirm(button.getAttribute('data-confirm') || '')) {
					return
				}
				button.classList.add('loading', 'disabled')
				const cuerpo = new FormData()
				const destino = button.getAttribute('data-system-maintenance-target')
				//Los dos botones de siempre no traen destino, así que su petición es idéntica a la de antes.
				if (destino !== null) {
					cuerpo.append('target', destino)
				}
				post(button.getAttribute('data-system-maintenance-action'), cuerpo)
					.then(function (data) {
						if (data.status) {
							pintarEstado(data.status)
						}
						if (typeof data.target === 'string') {
							const lineas = [typeof data.message === 'string' ? data.message : _i18n('system-status', 'Hecho')]
							if (typeof data.freed === 'number' && data.freed > 0) {
								lineas.push(_i18n('system-status', 'Espacio liberado') + ': ' + tamano(data.freed))
							}
							show(lineas, data.success === false)
						} else if (Array.isArray(data.deleted)) {
							//Solo los recuentos: la lista de rutas es para quien audita, y va al registro de acciones.
							const lines = [
								_i18n('system-status', 'Accesos rotos borrados') + ': ' + data.deleted.length,
								_i18n('system-status', 'Carpetas vacías borradas') + ': ' + (data.emptiedDirectories || []).length,
							]
							if (typeof data.failed === 'number' && data.failed > 0) {
								lines.push(_i18n('system-status', 'Sin permiso para borrar') + ': ' + data.failed)
							}
							show(lines, false)
						} else {
							show([typeof data.message === 'string' ? data.message : _i18n('system-status', 'Hecho')], data.success === false)
						}
					})
					.catch(function (error) {
						show([error.message], true)
					})
					.finally(function () {
						button.classList.remove('loading', 'disabled')
					})
			})
		})
	}
})
