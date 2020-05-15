class PiecesPHPSystemUserHelper {

	constructor(urlAuthenticate, urlVerification) {

		this.$ = window.PiecesPHPSystemUserHelperJQuery //jQuery JavaScript Library v3.4.1
		this.extraData = null

		if (typeof urlAuthenticate == 'string') {
			this.urlAuthenticate = urlAuthenticate
		}
		if (typeof urlVerification == 'string') {
			this.urlVerification = urlVerification
		}

	}

	authenticateWithUsernamePassword(username, password) {

		let instance = this
		let formData = new FormData()
		formData.set('username', username)
		formData.set('password', password)

		return new Promise((resolve, reject) => {

			let urlRequest = instance.urlAuthenticate + `?`
			urlRequest += `vp-w=${screen.width}&`
			urlRequest += `vp-h=${screen.height}&`
			urlRequest += `user-agent=${btoa(navigator.userAgent)}`

			instance
				.post(urlRequest, formData, {})
				.done(function (res) {
					if (res.auth) {
						instance.extraData = typeof res.extraData == 'object' ? res.extraData : null
						instance.setJWT(res.token)
					}
					resolve(res)
				})
				.fail(function (res) {
					reject(res)
				})
		})

	}

	verify(onSuccess) {

		let instance = this

		return new Promise((resolve, reject) => {
			instance
				.post(instance.urlVerification)
				.done(function (res) {
					if (res.isAuth == true) {

						instance.extraData = instance.getExtraData()
						instance.setJWT(instance.getJWT())

						if (typeof onSuccess == 'function') {
							onSuccess()
						}

					} else {
						instance.deleteSession()
					}
					resolve(res)
				})
				.fail(function (res) {
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

	setTriggerLogout(selector) {
		let instance = this
		if (typeof selector == 'string') {
			let element = this.$(selector)
			element.off('click')
			element.on('click', function (e) {
				e.stopPropagation()
				e.preventDefault()
				instance.deleteSession()
			})
		}

		return this
	}

	deleteSession() {
		let now = new Date().getTime()
		let JWT = this.getJWT()
		let JWTCookie = this.getCookie('JWTAuth')

		if (typeof JWTCookie == 'string' && JWTCookie.trim().length == 0) {
			JWTCookie = null
		}

		if (JWT.length > 0 || JWTCookie !== null) {
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

		if (typeof name == 'string' && typeof cookies[name] != 'undefined') {
			value = cookies[name]
		}

		return value
	}

	post(url, data, headers = {}) {

		let options = {
			url: url,
			method: 'POST',
		}

		if (data instanceof FormData) {

			options.processData = false
			options.enctype = "multipart/form-data"
			options.contentType = false
			options.cache = false
			options.data = data

		} else if (typeof data == 'object') {

			options.data = data

		}

		let parsedHeaders = this.parseHeaders(headers)

		if (parsedHeaders.size > 0) {

			options.beforeSend = function (request) {

				for (let key of parsedHeaders.keys()) {
					let value = parsedHeaders.get(key)
					request.setRequestHeader(key, value)
				}

			}

		}

		return this.$.ajax(options)
	}

	get(url, data, headers = {}) {

		let options = {
			url: url,
			method: 'GET',
			enctype: "application/x-www-form-urlencoded",
		}

		if (data instanceof HTMLFormElement) {

			options.data = this.$(data).serialize()

		} else if (typeof data == 'string') {

			options.data = data

		}

		let parsedHeaders = this.parseHeaders(headers)

		if (parsedHeaders.size > 0) {

			options.beforeSend = function (request) {

				for (let key of parsedHeaders.keys()) {
					let value = parsedHeaders.get(key)
					request.setRequestHeader(key, value)
				}

			}

		}

		return this.$.ajax(options)
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

				mapHeaders.set(name, valueString)

			}

		}

		mapHeaders.set('JWTAUTH', this.getJWT())
		mapHeaders.set('X-Requested-With', 'XMLHttpRequest')

		return mapHeaders

	}

}
