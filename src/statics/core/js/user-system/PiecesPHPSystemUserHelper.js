class PiecesPHPSystemUserHelper {

	constructor(urlAuthenticate, urlVerification, urlTwoFactorAuthStatus, expectedLang) {

		this.extraData = null
		this.expectedLang = typeof expectedLang == 'string' && expectedLang.trim().length > 0 ? expectedLang : null

		if (typeof urlAuthenticate == 'string') {
			this.urlAuthenticate = urlAuthenticate
		}
		if (typeof urlVerification == 'string') {
			this.urlVerification = urlVerification
		}
		if (typeof urlVerification == 'string') {
			this.urlTwoFactorAuthStatus = urlTwoFactorAuthStatus
		}

	}

	authenticateWithUsernamePassword(username, password, twoFactorCode = '') {

		let instance = this
		let formData = new FormData()
		formData.set('username', username)
		formData.set('password', password)
		formData.set('twoFactor', twoFactorCode)

		return new Promise((resolve, reject) => {
			let urlAuthenticateObject
			try {
				urlAuthenticateObject = new URL(instance.urlAuthenticate)
			} catch {
				urlAuthenticateObject = new URL(instance.urlAuthenticate, document.baseURI)
			}
			urlAuthenticateObject.searchParams.set('vp-w', `${screen.width}`)
			urlAuthenticateObject.searchParams.set('vp-h', `${screen.height}`)
			urlAuthenticateObject.searchParams.set('user-agent', `${btoa(navigator.userAgent)}`)
			let urlRequest = urlAuthenticateObject.href

			instance
				.post(urlRequest, formData, {})
				.then(res => res.json())
				.then(function (res) {

					if (typeof res.auth == 'boolean') {

						if (res.auth) {
							instance.extraData = typeof res.extraData == 'object' ? res.extraData : null
							instance.setJWT(res.token)
						}

						resolve(res)

					} else {
						reject(res)
					}

				})
				.catch(function (res) {
					reject(res)
				})
		})

	}

	verify(onSuccess, onNotAuth) {

		let instance = this

		return new Promise((resolve, reject) => {
			instance
				.post(instance.urlVerification)
				.then(res => res.json())
				.then(function (res) {

					if (typeof res.isAuth == 'boolean') {

						if (res.isAuth == true) {

							instance.extraData = instance.getExtraData()
							instance.setJWT(instance.getJWT())

							if (typeof onSuccess == 'function') {
								onSuccess()
							}

						} else {
							instance.deleteSession()
							if (typeof onNotAuth == 'function') {
								onNotAuth()
							}
						}

						resolve(res)

					} else {
						reject(res)
					}

				})
				.catch(function (res) {
					reject(res)
				})
		})

	}

	/**
	 * 
	 * @param {String} username
	 * @param {{(required: boolean)}} onResult
	 * @returns 
	 */
	verifyTwoFactorStatus(username, onResult) {

		let instance = this

		return new Promise((resolve, reject) => {
			let formData = new FormData()
			formData.set('username', username)
			instance
				.post(instance.urlTwoFactorAuthStatus, formData)
				.then(res => res.json())
				.then(function (res) {
					onResult(res.required)
					resolve(res)
				})
				.catch(function (res) {
					reject(res)
				})
		})

	}

	setJWT(JWT) {
		if (typeof JWT != 'string') {
			JWT = ''
		}
		document.cookie = `JWTAuth=${encodeURI(JWT)};path=/`;
		localStorage.setItem('JWTAuth', encodeURI(JWT))
		localStorage.setItem('JWTAuthExtraData', this.extraData !== null ? JSON.stringify(this.extraData) : null)
	}

	getJWT() {
		let JWT = localStorage.getItem('JWTAuth')
		if (typeof JWT != 'string') {
			JWT = ''
		}
		return JWT
	}

	getExtraData() {
		let extraData = localStorage.getItem('JWTAuthExtraData')
		extraData = extraData !== null ? JSON.parse(extraData) : null
		return extraData
	}

	setExtraDataProperty(property, value) {
		let extraData = this.getExtraData()
		extraData = typeof extraData == 'object' && extraData !== null ? extraData : {}

		extraData[property] = value

		localStorage.setItem('JWTAuthExtraData', JSON.stringify(extraData))
	}

	setTriggerLogout(selector) {

		let instance = this

		if (typeof selector == 'string') {

			let elements = Array.from(document.querySelectorAll(selector))

			for (let element of elements) {

				let clone = element.cloneNode(true)

				element.parentNode.replaceChild(clone, element)

				clone.addEventListener('click', function (e) {
					e.stopPropagation()
					e.preventDefault()
					instance.deleteSession()
				})

			}

		}

		return this
	}

	deleteSession() {

		let now = new Date().getTime()
		let JWT = this.getJWT()
		let JWTFromCookie = this.getCookie('JWTAuth')
		JWTFromCookie = typeof JWTFromCookie == 'string' ? JWTFromCookie.trim() : ''
		JWT = JWT.length > 0 ? JWT : JWTFromCookie

		if (JWT.length > 0) {
			document.cookie = `JWTAuth=;expires=${now};path=/`;
			localStorage.removeItem('JWTAuth')
			localStorage.removeItem('JWTAuthExtraData')
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
		url = url instanceof URL ? url.href : url
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

		let urlObject
		try {
			urlObject = new URL(url)
		} catch {
			urlObject = new URL(url, document.baseURI)
		}

		if (data instanceof HTMLFormElement) {

			let formData = new FormData(data)

			for (let key of formData.keys()) {
				urlObject.searchParams.append(key, formData.get(key))
			}

			url = urlObject.href

		} else if (typeof data == 'string' && data.length > 0) {

			const urlLength = url.length
			const indexOfCharQuery = url.lastIndexOf('?')
			const hasCharQuery = indexOfCharQuery != -1
			const charQueryIsLastChar = indexOfCharQuery !== -1 && indexOfCharQuery + 1 == urlLength
			if (hasCharQuery) {
				url += charQueryIsLastChar ? `${data}` : `&${data}`
			} else {
				url += `?${data}`
			}
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

		mapHeaders.set('JWTAUTH', this.getJWT())
		mapHeaders.set('X-Requested-With', 'XMLHttpRequest')
		if (this.expectedLang !== null) {
			mapHeaders.set('PCSPHP-Response-Expected-Language', this.expectedLang)
		}

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
