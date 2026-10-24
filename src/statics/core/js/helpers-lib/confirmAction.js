/**
 * La única forma de preguntar en el panel: la confirmación de Fomantic que ya usaba genericFormHandler (un toast con
 * acciones). Nunca window.confirm ni alert: los vigila verify-integrity. Los textos por omisión salen del grupo de
 * traducción 'confirmAction' (translations/es.js y en.js).
 *
 * @param {Object} options
 * @param {String} [options.title] Por omisión, «Confirmación»
 * @param {String} [options.message] Por omisión, «¿Está seguro de realizar esta acción?»
 * @param {String} [options.confirmText] Por omisión, «Sí»
 * @param {String|null} [options.cancelText] Por omisión, «No»; con null hay un solo botón (avisar, no preguntar)
 * @param {String} [options.confirmClass] La clase de Fomantic del botón de confirmar; por omisión, «blue»
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

	return new Promise((resolve) => {

		//Sin el toast de Fomantic no hay a quién preguntar: se responde «no», que nunca ejecuta una acción sin permiso.
		if (typeof $ != 'function' || typeof $.toast != 'function') {
			console.error(`${title}: ${message}`)
			resolve(false)
			return
		}

		const done = (answer) => {
			$('body').removeClass('wait-to-action')
			resolve(answer)
			return true
		}

		const actions = [{
			text: confirmText,
			class: confirmClass,
			click: () => done(true),
		}]
		if (cancelText !== null) {
			actions.push({
				text: cancelText,
				class: 'gray',
				click: () => done(false),
			})
		}

		$('body').addClass('wait-to-action')
		$.toast({
			title: title,
			message: message,
			displayTime: 0,
			class: 'white',
			position: 'top center',
			classActions: 'top attached',
			actions: actions,
		})
	})
}
