/**
 * La única forma de preguntar en el panel: un `ui mini modal` de Fomantic (`header`, `content` y `actions`), con el foco
 * inicial en cancelar. Esc o un clic fuera son «no». Puede abrirse sobre otra ventana sin cerrarla. Nunca window.confirm ni
 * alert: los vigila verify-integrity. Los textos por omisión salen del grupo de traducción 'confirmAction'
 * (translations/es.js y en.js).
 *
 * @param {Object} options
 * @param {String} [options.title] Por omisión, «Confirmación»
 * @param {String} [options.message] Por omisión, «¿Está seguro de realizar esta acción?». Es HTML (tokens.js pasa
 * `<p><strong>`): quien pase datos del usuario, los escapa.
 * @param {String} [options.confirmText] Por omisión, «Sí»
 * @param {String|null} [options.cancelText] Por omisión, «No»; con null hay un solo botón (avisar, no preguntar)
 * @param {String} [options.confirmClass] La clase de Fomantic del botón de confirmar; por omisión, «blue», que es
 * `primary`, y `red`, `basic negative` (el patrón del tema para lo destructivo)
 * @returns {Promise<Boolean>} true si se confirma; false si se cancela o no se puede preguntar
 */
function confirmAction(options = {}) {

	//Sin _i18n (una página sin configurations.js), el texto en español.
	const text = (key) => typeof _i18n == 'function' ? _i18n('confirmAction', key) : key
	const title = typeof options.title == 'string' ? options.title : text('Confirmación')
	const message = typeof options.message == 'string' ? options.message : text('¿Está seguro de realizar esta acción?')
	const confirmText = typeof options.confirmText == 'string' ? options.confirmText : text('Sí')
	const cancelText = options.cancelText === null ? null : (typeof options.cancelText == 'string' ? options.cancelText : text('No'))
	const confirmClass = typeof options.confirmClass == 'string' ? options.confirmClass : 'blue'
	const buttonClass = { blue: 'primary', red: 'basic negative' }[confirmClass] || confirmClass

	return new Promise((resolve) => {

		//Sin el modal de Fomantic no hay a quién preguntar: se responde «no», que nunca ejecuta una acción sin permiso.
		if (typeof $ != 'function' || typeof $.fn.modal != 'function') {
			console.error(`${title}: ${message}`)
			resolve(false)
			return
		}

		let answer = false
		const actions = $('<div class="actions"></div>')
		if (cancelText !== null) {
			actions.append($('<button type="button" class="ui basic cancel button"></button>').text(cancelText))
			actions.append(' ')
		}
		actions.append($('<button type="button"></button>').addClass(`ui ${buttonClass} approve button`).text(confirmText))

		const modal = $('<div class="ui mini modal"></div>')
			.append($('<div class="header"></div>').text(title))
			.append($('<div class="content"></div>').html(message))
			.append(actions)

		modal.appendTo('body').modal({
			allowMultiple: true,
			onApprove: () => {
				answer = true
			},
			onVisible: () => {
				modal.find(cancelText !== null ? '.cancel' : '.approve').trigger('focus')
			},
			//Sin .modal('destroy'): destruiría también el dimmer de la página, el de la ventana que pueda haber debajo.
			onHidden: () => {
				modal.remove()
				resolve(answer)
			},
		}).modal('show')
	})
}
