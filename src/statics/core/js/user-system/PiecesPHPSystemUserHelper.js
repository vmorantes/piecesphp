class PiecesPHPSystemUserHelper {

	/** ULTIMO RECURSO. La fuente es el servidor: «sessionTokenName» de las configuraciones del frente. */
	static defaultJWTAuthName = 'JWTAuth'

	/** El nombre que manda el servidor, o el de ultimo recurso si no llego. */
	static get sessionTokenName() {
		let fromBackend = null
		try {
			fromBackend = pcsphpGlobals.frontConfigurationsFromBackend.sessionTokenName
		} catch (e) {
			fromBackend = null
		}
		const valid = typeof fromBackend == 'string' && /^[A-Za-z0-9_]{1,64}$/.test(fromBackend)
		return valid ? fromBackend : PiecesPHPSystemUserHelper.defaultJWTAuthName
	}

	static get localJWTAuthName() {
		return PiecesPHPSystemUserHelper.sessionTokenName
	}

	static get localJWTAuthExtraDataName() {
		return PiecesPHPSystemUserHelper.sessionTokenName + 'ExtraData'
	}

	static get localUserDataName() {
		return PiecesPHPSystemUserHelper.sessionTokenName + 'UserData'
	}

	static get remoteJWTAuthName() {
		return PiecesPHPSystemUserHelper.sessionTokenName
	}

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
							//El servidor NO manda nada por este canal: el login dejó de enviarlo. Queda null salvo que un clon
							//rellene `extraData` en su respuesta, y entonces se guardará; mientras no, no se escribe nada.
							instance.extraData = typeof res.extraData == 'object' ? res.extraData : null
							instance.setUserData(res.userData ?? {})
							//El token ya no se guarda aquí: el servidor puso la cookie HttpOnly. setJWT() guarda los datos extra.
							instance.setJWT()
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
							instance.setJWT()

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

	/**
	 * Si este navegador tiene una sesión según lo último que dijo el servidor (los datos del usuario que guardan el acceso y
	 * verify()). El token es una cookie HttpOnly que el JavaScript no ve: la respuesta cierta es la de verify(), que
	 * pregunta al servidor; esta es síncrona y no puede. Con `destroyIfInvalid`, sin sesión borra lo que quede de una vieja.
	 * @param {Boolean} destroyIfInvalid
	 * @param {Boolean} reloadOnDestroy
	 * @returns {Boolean}
	 */
	hasSession(destroyIfInvalid = false, reloadOnDestroy = false) {
		const userData = this.getUserData()
		const validSession = userData !== null && typeof userData === 'object' && typeof userData.id === 'number'
		if (destroyIfInvalid && !validSession && this.hasLegacyToken()) {
			this.deleteSession(reloadOnDestroy)
		}
		return validSession
	}

	/**
	 * Guarda los datos extra de la sesión. El token NO: lo pone el servidor en una cookie HttpOnly (H188). Borra el que
	 * guardaba aquí la versión anterior.
	 */
	setJWT() {
		this.forgetLegacyToken()
		//Sin datos extra NO se escribe la clave: `setItem(clave, null)` guarda la CADENA "null", y quien la leyera
		//recibiría un texto donde espera un objeto. Si quedó una de antes, se retira.
		if (this.extraData !== null) {
			localStorage.setItem(PiecesPHPSystemUserHelper.localJWTAuthExtraDataName, JSON.stringify(this.extraData))
		} else {
			localStorage.removeItem(PiecesPHPSystemUserHelper.localJWTAuthExtraDataName)
		}
	}

	/**
	 * @deprecated 10.x: el token es una cookie HttpOnly que el JavaScript no ve; devuelve ''. Sale en la 11.0.0. Las
	 * peticiones al propio sitio llevan la cookie solas; la API por cabecera es para integradores, no para la página.
	 * @returns {String}
	 */
	getJWT() {
		return ''
	}

	/**
	 * Si queda el token que guardaba la versión anterior en el localStorage o en una cookie legible.
	 * @returns {Boolean}
	 */
	hasLegacyToken() {
		const stored = localStorage.getItem(PiecesPHPSystemUserHelper.localJWTAuthName)
		const cookie = this.getCookie(PiecesPHPSystemUserHelper.localJWTAuthName)
		return (typeof stored == 'string' && stored.length > 0) || (typeof cookie == 'string' && cookie.trim().length > 0)
	}

	/**
	 * Borra el token que guardaba la versión anterior: el del localStorage y las cookies legibles que escribía (sin
	 * dominio, con el host y con .host). La HttpOnly del servidor no se puede tocar desde aquí, y así debe ser.
	 */
	forgetLegacyToken() {
		const name = PiecesPHPSystemUserHelper.localJWTAuthName
		//Una fecha pasada en formato de fecha: un número de milisegundos no lo es, y la cookie se quedaba.
		const expired = new Date(0).toUTCString()
		const secure = location.protocol === 'https:' ? ';Secure' : ''
		localStorage.removeItem(name)
		for (const domain of ['', `;domain=${location.hostname}`, `;domain=.${location.hostname}`]) {
			document.cookie = `${name}=;expires=${expired};path=/;SameSite=Lax${secure}${domain}`
		}
	}

	getExtraData() {
		const extraData = getLocalStorageData(PiecesPHPSystemUserHelper.localJWTAuthExtraDataName)
		return extraData
	}

	setExtraDataProperty(property, value) {
		let extraData = this.getExtraData()
		extraData = typeof extraData == 'object' && extraData !== null ? extraData : {}
		extraData[property] = value
		setLocalStorageData(PiecesPHPSystemUserHelper.localJWTAuthExtraDataName, extraData)
	}

	/**
	 * Obtiene los datos del usuario almacenados.
	 * @returns {Object|null} Datos del usuario o null si no existe.
	 */
	getUserData() {
		const userData = getLocalStorageData(PiecesPHPSystemUserHelper.localUserDataName)
		return userData
	}

	/**
	 * Guarda los datos del usuario en localStorage.
	 * @param {Object} data - Datos del usuario.
	 */
	setUserData(data) {
		setLocalStorageData(PiecesPHPSystemUserHelper.localUserDataName, data)
	}

	logout() {
		this.logoutOnServer()
	}

	/**
	 * Cierra la sesión EN EL SERVIDOR (su fila; ADR 0026, fase B), que borra la cookie, y después lo que guarda este
	 * navegador, salga bien o mal. La dirección la da el servidor en <meta name="logout-url">; sin ella, solo lo del
	 * navegador.
	 * @param {Boolean} reload
	 */
	logoutOnServer(reload = true) {
		const instance = this
		const meta = document.querySelector('html head meta[name="logout-url"]')
		let url = meta !== null ? meta.getAttribute('value') : null
		url = typeof url === 'string' && url.trim().length > 0 ? atob(url.trim()) : ''
		if (url === '') {
			instance.deleteSession(reload)
			return
		}
		//La cookie de sesión va sola: el servidor cierra la fila y la borra.
		fetch(url, { method: 'POST', credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
			.catch(() => null)
			.finally(() => instance.deleteSession(reload))
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
					instance.logoutOnServer()
				})

			}

		}

		return this
	}

	/**
	 * Borra la sesión de este navegador: los datos guardados y el token viejo. La cookie HttpOnly la borra el servidor (al
	 * cerrar sesión, o en verify() si ya no vale). Recarga si había una sesión que borrar.
	 * @param {Boolean} reload
	 */
	deleteSession(reload = true) {

		const hadSession = this.getUserData() !== null || this.hasLegacyToken()

		if (hadSession) {
			this.forgetLegacyToken()
			localStorage.removeItem(PiecesPHPSystemUserHelper.localJWTAuthExtraDataName)
			localStorage.removeItem(PiecesPHPSystemUserHelper.localUserDataName)
			//En el login no se recarga: la recarga perdería la URL a la que volver, que llega en un aviso de un solo uso.
			const isLoginPage = document.querySelector('[login-form-js]') !== null
			if (reload && !isLoginPage) {
				window.location.reload()
			}
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
	 * @param {String|FormData} data
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

		//Sin cabecera del token: la cookie de sesión (HttpOnly) va sola en las peticiones al propio sitio.
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
		return typeof str == 'string' ? str.split('').map((e, i) => i == 0 ? e.toUpperCase() : e).join('') : ''
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
