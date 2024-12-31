class PiecesPHPGenericHandlerSession {

	constructor(tokenName) {
		this.tokenName = tokenName
		this.verifyPairLocalStorageAndCookieSession()
	}

	verifyPairLocalStorageAndCookieSession() {

		const currentJWT = this.getJWT()
		let currentJWTFromCookie = this.getCookie(this.tokenName)

		/**
		 * Dado que pueden diferir los valores del cookie en "domain" puede replicarse el cookie con el mismo nombre.
		 * De modo que se convierte siempre en un array de strings.
		 * Si ya es un array porque se dió la duplicación se valida que solo contenga strings.
		 * Finalmente se valida que todos los valores sean idénticos (un mismo token) y se reduce a uno solo.
		 * Si es null, no se hace nada
		 */
		currentJWTFromCookie = typeof currentJWTFromCookie == 'string' ? [currentJWTFromCookie] : currentJWTFromCookie
		currentJWTFromCookie = Array.isArray(currentJWTFromCookie) ? currentJWTFromCookie.filter((value) => { return typeof value === "string" && value.length > 0}) : currentJWTFromCookie
		currentJWTFromCookie = Array.isArray(currentJWTFromCookie) && currentJWTFromCookie.every(value => value === currentJWTFromCookie[0]) ? currentJWTFromCookie[0] : null

		if (currentJWT == '' && currentJWTFromCookie !== null) {
			//Si hay JWT en cookies pero no en localstorage se copia el de cookie
			this.setJWT(currentJWTFromCookie)
		} else if (currentJWT != currentJWTFromCookie) {
			//Si hay JWT en ambos pero difieren se borra
			this.deleteSession()
		}
	}

	setJWT(JWT) {
		if (typeof JWT != 'string') {
			JWT = ''
		}
		document.cookie = `${this.tokenName}=${encodeURI(JWT)};path=/`;
		document.cookie = `${this.tokenName}=${encodeURI(JWT)};path=/;domain=${location.hostname}`;
		document.cookie = `${this.tokenName}=${encodeURI(JWT)};path=/;domain=.${location.hostname}`;
		localStorage.setItem(this.tokenName, encodeURI(JWT))
	}

	getJWT() {
		let JWT = localStorage.getItem(this.tokenName)
		if (typeof JWT != 'string') {
			JWT = ''
		}
		return JWT
	}

	deleteSession() {

		let now = new Date().getTime()
		let JWT = this.getJWT()
		let JWTFromCookie = this.getCookie(this.tokenName)
		JWTFromCookie = typeof JWTFromCookie == 'string' ? JWTFromCookie.trim() : ''
		JWT = JWT.length > 0 ? JWT : JWTFromCookie

		if (JWT.length > 0) {
			document.cookie = `${this.tokenName}=;expires=${now};path=/`;
			document.cookie = `${this.tokenName}=;expires=${now};path=/;domain=${location.hostname}`;
			document.cookie = `${this.tokenName}=;expires=${now};path=/;domain=.${location.hostname}`;
			localStorage.removeItem(this.tokenName)
			window.location.reload()
		}

	}

	getCookies() {

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

	getCookie(name) {

		let cookies = this.getCookies()
		let value = null

		if (cookies !== null && typeof name == 'string' && typeof cookies[name] != 'undefined') {
			value = cookies[name]
		}

		return value
	}

	/**
	 * @param {String|URL} url
	 * @param {String|HTMLFormElement} data
	 * @param {Object} headers
	 * @returns {Promise<Response>}
	 */
	post(url, data, headers = {}) {

		url = (typeof url == 'string' && url.trim().length > 0) || url instanceof URL ? url : ''
		data = typeof data == 'object' || data instanceof FormData ? data : {}
		headers = typeof headers == 'object' ? headers : {}

		let options = {
			method: 'POST',
			headers: new Headers(),
			mode: 'cors',
			credentials: 'include',
		}

		let parsedHeaders = this.parseHeaders(headers)

		if (data instanceof FormData) {

			options.cache = 'no-store'
			options.body = data

		} else if (typeof data == 'object') {

			options.body = JSON.stringify(data)
			parsedHeaders.set('Content-Type', 'application/json')

		}

		for (let name of parsedHeaders.keys()) {
			options.headers.append(name, parsedHeaders.get(name))
		}

		return this.ajax(url, options)
	}

	/**
	 * @param {String|URL} url
	 * @param {String|HTMLFormElement} data
	 * @param {Object} headers
	 * @returns {Promise<Response>}
	 */
	get(url, data, headers = {}) {

		url = (typeof url == 'string' && url.trim().length > 0) || url instanceof URL ? url : ''
		data = typeof data == 'string' || data instanceof HTMLFormElement ? data : ''
		headers = typeof headers == 'object' ? headers : {}

		let options = {
			method: 'GET',
			headers: new Headers(),
			mode: 'cors',
			credentials: 'include',
		}

		let parsedHeaders = this.parseHeaders(headers)
		parsedHeaders.set('Content-Type', 'application/x-www-form-urlencoded')

		let urlParams = new URLSearchParams()

		if (data instanceof HTMLFormElement) {

			let formData = new FormData(data)

			for (let key of formData.keys()) {
				urlParams.append(key, formData.get(key))
			}

			url += `?${urlParams.toString()}`

		} else if (typeof data == 'string') {
			url += `?${data}`
		}

		for (let name of parsedHeaders.keys()) {
			options.headers.append(name, parsedHeaders.get(name))
		}

		return this.ajax(url, options)
	}

	parseHeaders(headers = {}) {

		let mapHeaders = new Map()

		if (typeof headers == 'object') {

			for (let name in headers) {

				let value = headers[name]
				let valueString = ''

				if (Array.isArray(value)) {

					let length = value.length
					let lastIndexValue = 0

					if (length == 1) {
						lastIndexValue = 0
					} else if (length > 1) {
						lastIndexValue = length - 1
					}

					for (let i = 0; i < length; i++) {
						if (i == lastIndexValue) {
							valueString += value[i]
						} else {
							valueString += value[i] + "\r\n"
						}
					}

				} else if (typeof value == 'string') {
					valueString = value
				}

				let normalizedName = this.normalizeHeaderName(name)
				mapHeaders.set(normalizedName, valueString)

			}

		}

		mapHeaders.set(this.tokenName, this.getJWT())
		mapHeaders.set('X-Requested-With', 'XMLHttpRequest')

		return mapHeaders

	}

	normalizeHeaderName(name) {
		return typeof name == 'string' ? name = name.trim().replace(/\s{1,}/gmi, '').split('-').map(e => this.capitalize(e)).join('-') : name
	}

	capitalize(str) {
		return typeof str == 'string' ? str.split('').map(e, i => i == 0 ? e.toUpperCase() : e).join('') : ''
	}

	/**
	 * @param {RequestInfo} url
	 * @param {RequestInit} [options]
	 * @returns {Promise<Response>}
	 */
	ajax(url, options) {
		return fetch(url, options)
	}

}
