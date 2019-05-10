/**
 * Internacionalización de mensajes
 * 
 * @param {string} type Tipo de mensaje
 * @param {*} message Mensaje
 */
function _i18n(type, message) {

	let messages = globales.messages
	let lang = globales.lang

	let exists = false

	let existsLang = messages[lang] !== undefined

	if (existsLang) {

		let existsType = messages[lang][type] !== undefined

		if (existsType) {
			let existsMessage = messages[lang][type][message] !== undefined

			if (existsMessage) {
				exists = true
			}

		}

	}

	if (exists) {
		return messages[lang][type][message]
	} else {
		return message
	}
}

