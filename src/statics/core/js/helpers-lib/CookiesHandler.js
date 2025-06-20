class CookiesHandler {
	constructor() { }
}

/**
 * Obtiene todos los cookies
 * @returns {Object} Objeto con los cookies
 */
CookiesHandler.getCookies = function () {

	let cookies = document.cookie
	let parsedCookies = {}
	let hasValues = false
	let valueParsed = null

	cookies = cookies.split(';')

	if (Array.isArray(cookies)) {

		for (let cookie of cookies) {

			let pair = cookie.split('=')

			if (Array.isArray(pair) && pair.length === 2) {

				let name = pair[0].trim()
				let value = pair[1]

				if (typeof parsedCookies[name] === 'undefined') {
					parsedCookies[name] = []
				}

				parsedCookies[name].push(value)
				hasValues = true

			}

		}

		for (let name in parsedCookies) {

			let values = parsedCookies[name]

			if (Array.isArray(values) && values.length === 1) {

				parsedCookies[name] = values[0]

			}

		}

	}

	if (hasValues) {
		valueParsed = parsedCookies
	}

	return valueParsed
}

/**
 * Establece una cookie con opciones configurables
 * @param {string} name - Nombre de la cookie
 * @param {string} value - Valor de la cookie
 * @param {Object} options - Opciones de configuración
 * @param {number} [options.expires] - Tiempo de expiración en días
 * @param {string} [options.path='/'] - Ruta de la cookie
 * @param {string} [options.domain] - Dominio de la cookie
 */
CookiesHandler.setCookie = function (name, value, options = {}) {

	if (typeof name !== 'string' || !name.trim()) {
		console.error('El nombre de la cookie es requerido y debe ser una cadena no vacía')
		return
	}

	const defaultOptions = {
		path: '/',
		domain: location.hostname,
	}

	const cookieOptions = { ...defaultOptions, ...options }

	let cookieString = `${encodeURIComponent(name)}=${encodeURIComponent(value)}`

	if (Number.isInteger(cookieOptions.expires) && cookieOptions.expires > 0) {
		const date = new Date()
		date.setTime(date.getTime() + (cookieOptions.expires * 24 * 60 * 60 * 1000))
		cookieString += `;expires=${date.toUTCString()}`
	}

	cookieString += `;path=${cookieOptions.path}`


	// Establecer cookie 
	document.cookie = `${cookieString}`
	document.cookie = `${cookieString};domain=${cookieOptions.domain}`
	document.cookie = `${cookieString};domain=.${cookieOptions.domain}`
}

/**
 * Elimina una cookie específica
 * @param {string} name - Nombre de la cookie a eliminar
 * @param {Object} [options={}] - Opciones adicionales para la eliminación
 */
CookiesHandler.deleteCookie = function (name, options = {}) {
	const deleteOptions = {
		...options,
		expires: -1
	}
	this.setCookie(name, '', deleteOptions)
}

/**
 * Obtiene un cookie por su nombre
 * @param {String} name Nombre del cookie
 * @returns {String|null} Valor del cookie o null si no existe
 */
CookiesHandler.getCookie = function (name) {

	let cookies = this.getCookies()
	let value = null

	if (cookies !== null && typeof name == 'string' && typeof cookies[name] != 'undefined') {
		value = cookies[name]
	}

	return value
}
