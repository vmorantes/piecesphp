class PiecesPHPSystemUserHelper {

	constructor(urlAuthenticate, urlVerification) {

		this.triggerLogout = null
		this.$ = window.PiecesPHPSystemUserHelperJQuery //jQuery JavaScript Library v3.4.1

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
			instance
				.post(instance.urlAuthenticate, formData, {})
				.done(function (res) {
					if (res.auth) {
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
	}

	getJWT() {
		let JWT = localStorage.getItem('JWTAuth')
		if (typeof JWT != 'string') {
			JWT = ''
		}
		return JWT
	}

	setTriggerLogout(selector) {
		let instance = this
		if (typeof selector == 'string') {
			let element = document.querySelector(selector)
			if (element instanceof HTMLElement) {
				this.triggerLogout = element
				this.triggerLogout.addEventListener('click', function (e) {
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

		if (JWT.length > 0) {
			document.cookie = `JWTAuth=;expires=${now};path=/`;
			localStorage.removeItem('JWTAuth')
			window.location.reload()
		}
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

		if (typeof headers == 'object' && headers.length > 0) {

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
					valueString = JSON.stringify(value)
				}

				mapHeaders.set(name, valueString)

			}

		}

		mapHeaders.set('JWTAUTH', this.getJWT())

		return mapHeaders

	}

}
