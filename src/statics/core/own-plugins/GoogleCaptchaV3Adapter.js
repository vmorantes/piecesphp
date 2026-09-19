/**
 * @function GoogleCaptchaV3Adapter
 *  
 * @param {AdapterOptions} configurations  
 */
function GoogleCaptchaV3Adapter(configurations = {}) {

	const LANG_GROUP = 'GoogleCaptchaV3Adapter'

	GoogleCaptchaV3Adapter.registerDynamicMessages(LANG_GROUP)

	/**
	 * @typedef AdapterOptions
	 * @property {String} [apiURL=https://www.google.com/recaptcha/api.js?render=reCAPTCHA_site_key]
	 * @property {URL} [backendURL=recaptcha/google-recaptcha-v3/action/recaptcha-verify/]
	 * @property {String} key
	 */

	//──── Valores de configuración ──────────────────────────────────────────────────────────
	/** @type {AdapterOptions} Configuración por defecto de la clase */
	let defaultAdapterOptions = {
		apiURL: 'https://www.google.com/recaptcha/api.js',
		backendURL: new URL('recaptcha/google-recaptcha-v3/action/recaptcha-verify/', document.baseURI),
		key: '',
	}

	//──── Objetos ───────────────────────────────────────────────────────────────────────────
	/** @type {GoogleCaptchaV3Adapter} Instancia */ let instance = this
	/** @type {AdapterOptions} Configuraciones de la clase */ let adapterOptions

	//──── Eventos personalizados ────────────────────────────────────────────────────────────	
	/** @type {String} */ let eventsPrefix = 'event-GoogleCaptchaV3Adapter'
	/** @type {HTMLElement} */ let eventer = document.createElement('eventer-GoogleCaptchaV3Adapter')
	/** @type {Event[]} */ let events = {
		prepare: {
			event: new Event(`${eventsPrefix}-prepare`),
			data: {},
			wasDispatch: false,
			canDispatch: function (element) {
				return element.wasDispatch
			},
		},
	}

	//──── Varios ────────────────────────────────────────────────────────────────────────────
	/** @type {Boolean} */ let isPrepared = false

	prepare(configurations)

	/**
	 * @method execute
	 * @param {Function(response:Object, success:Boolean, message:String, token:String)} callback
	 * @returns {void}
	 */
	this.execute = (callback) => {

		showGenericLoader('grecaptcha-execute-GoogleCaptchaV3Adapter')

		grecaptcha.ready(function () {

			grecaptcha.execute(adapterOptions.key, { action: 'submit' })
				.then(function (token) {

					let url = new URL(adapterOptions.backendURL.href)

					fetch(url, {
						method: 'POST',
						headers: {
							'Content-Type': 'application/json'
						},
						body: JSON.stringify({
							'token': token,
						})
					})
						.then(res => res.json())
						.then(function (res) {
							const success = res.verify.success
							const token = res.verify.token
							let message = !success ? _i18n(LANG_GROUP, 'No ha pasado la prueba captcha anti-spam, intente nuevamente refrescando la página.') : 'OK'
							callback(res, success, message, token)
						})
						.finally(function () {
							removeGenericLoader('grecaptcha-execute-GoogleCaptchaV3Adapter')
						})

				})

		})

	}

	/**
	 * @method on
	 * @param {String} eventName
	 * @param {Function} callback
	 * @returns {void}
	 */
	this.on = (eventName, callback) => {

		let eventPrefixed = `${eventsPrefix}-${eventName}`

		if (typeof events[eventName] !== 'undefined' && typeof callback == 'function') {

			eventer.addEventListener(eventPrefixed, function (e) {

				let eventConfig = events[eventName]

				callback(e, eventConfig.data)

				events[eventName].wasDispatch = true

			})

		}

	}

	/**
	 * @method dispatch
	 * @param {String} eventName
	 * @returns {void}
	 */
	this.dispatch = (eventName) => {

		if (typeof events[eventName] !== 'undefined') {

			let eventConfig = events[eventName]

			let eventObject = eventConfig.event
			let wasDispatch = eventConfig.wasDispatch
			let canDispatch = eventConfig.canDispatch(eventConfig)

			if (canDispatch) {
				eventer.dispatchEvent(events[eventName].event)
			}

		}

	}

	/**
	 * @function prepare
	 * @param {AdapterOptions} configurations 
	 */
	function prepare(configurations = {}) {
		showGenericLoader('PREPARE-GoogleCaptchaV3Adapter')
		try {

			configOptions(configurations)

			const scriptPromise = new Promise((resolve) => {
				const script = document.createElement('script')
				document.body.appendChild(script)
				script.onload = resolve
				script.async = true
				const scriptURL = new URL(adapterOptions.apiURL)
				scriptURL.searchParams.set('render', adapterOptions.key)
				script.src = scriptURL.href
			})

			scriptPromise.then(() => {
				isPrepared = true
				eventer.dispatchEvent(events.prepare.event)
			}).finally(function () {
				removeGenericLoader('PREPARE-GoogleCaptchaV3Adapter')
			})

		} catch (error) {
			errorMessage(_i18n(LANG_GROUP, 'Error'), _i18n(LANG_GROUP, 'Ha ocurrido un error al configurar GoogleCaptchaV3Adapter'))
			console.error(error)
			removeGenericLoader('PREPARE-GoogleCaptchaV3Adapter')
		}

	}

	/**
	 * @function configOptions
	 * @param {AdapterOptions} configurations 
	 */
	function configOptions(configurations = {}) {
		//Configuraciones de adapterOptions
		adapterOptions = processByDefaultValues(defaultAdapterOptions, configurations)
	}

	/**
	 * @function processByStructure
	 * @param {Object} defaultValues 
	 * @param {Object} data 
	 * @returns {Object}
	 */
	function processByDefaultValues(defaultValues, data) {

		for (let option in defaultValues) {
			let defaultOption = defaultValues[option]
			if (typeof data[option] == 'undefined') {
				data[option] = defaultOption
			} else {
				if (typeof data[option] != typeof defaultOption) {
					data[option] = defaultOption
				}
			}
		}

		return data

	}

	return this

}

GoogleCaptchaV3Adapter.registerDynamicMessages = function (name) {

	if (typeof pcsphpGlobals != 'object') {
		pcsphpGlobals = {}
	}
	if (typeof pcsphpGlobals.messages != 'object') {
		pcsphpGlobals.messages = {}
	}
	if (typeof pcsphpGlobals.messages.es != 'object') {
		pcsphpGlobals.messages.es = {}
	}
	if (typeof pcsphpGlobals.messages.en != 'object') {
		pcsphpGlobals.messages.en = {}
	}

	let es = {
		'Error': 'Error',
		'Ha ocurrido un error al configurar GoogleCaptchaV3Adapter': 'Ha ocurrido un error al configurar GoogleCaptchaV3Adapter',
		'No ha pasado la prueba captcha anti-spam, intente nuevamente refrescando la página.': 'No ha pasado la prueba captcha anti-spam, intente nuevamente refrescando la página.',
	}
	let en = {
		'Error': 'Error',
		'Ha ocurrido un error al configurar GoogleCaptchaV3Adapter': 'An error occurred while configuring GoogleCaptchaV3Adapter',
		'No ha pasado la prueba captcha anti-spam, intente nuevamente refrescando la página.': 'Failed the anti-spam captcha test, try again by refreshing the page.',
	}

	for (let i in es) {
		if (typeof pcsphpGlobals.messages.es[name] == 'undefined') pcsphpGlobals.messages.es[name] = {}
		pcsphpGlobals.messages.es[name][i] = es[i]
	}

	for (let i in en) {
		if (typeof pcsphpGlobals.messages.en[name] == 'undefined') pcsphpGlobals.messages.en[name] = {}
		pcsphpGlobals.messages.en[name][i] = en[i]
	}
}
