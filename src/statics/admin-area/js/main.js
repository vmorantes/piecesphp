/// <reference path="../../core/js/helpers.js" />
/// <reference path="../../core/js/configurations.js" />
window.addEventListener('load', function (e) {
	timeOnPlatform()
	adminZoneSupportForm()
	adminSitemapUpdate()
	adminClearCache()
})

/**
 * @description Registra el tiempo de uso de la aplicación en LocalStorage y lo envía al servidor.
 * @param {Number} registerUsedTimeSecondsBase Segundos base para el registro del tiempo de uso.
 * @param {Number} registerUsedTimeSendSecondsBase Segundos base para el envío del tiempo de uso al servidor.
 * @param {Number} registerUsedTimeRetrySeconds Segundos base para el reintento del envío del tiempo de uso al servidor.
 */
function timeOnPlatform(registerUsedTimeSecondsBase = 5, registerUsedTimeSendSecondsBase = 240, registerUsedTimeRetrySeconds = 2) {

	let attr = 'timer-platform-js'
	let selector = `[${attr}]`
	let timerElement = $(selector)
	let data = timerElement.attr(attr)
	let url = null
	let userID = null
	try {
		data = JSON.parse(atob(data))
		url = data.url
		userID = data.user_id
	} catch (parseError) {
		data = null
	}
	if (timerElement.length == 0 || data == null || url == null || userID == null || !(pcsphpGlobals.globalAuthenticator instanceof PiecesPHPSystemUserHelper)) {
		return
	}

	/**
	 * Configuración base para el registro del tiempo de uso de la aplicación.
	 * Separa la recolección local del tiempo de uso y el envío al servidor.
	 */
	let registerUsedTimeConfiguration = {
		intervalCollectID: null, // Identificador del recolector
		intervalSendID: null, // Identificador del enviador
		acumulatedRecordName: 'pcsPHP:usedTime:acumulatedSeconds', // Llave en LocalStorage
		retrySeconds: registerUsedTimeRetrySeconds, // Estado actual del backoff incremental
		isSending: false, // Flag para evitar envíos cruzados

		/**
		 * Suma segundos a los ya guardados en el dispositivo mediante LocalStorage.
		 */
		addAcumulatedSeconds: function (seconds) {
			let existingSeconds = this.getAcumulatedSeconds()
			setLocalStorageData(this.acumulatedRecordName, existingSeconds + seconds)
		},

		/**
		 * Recupera la cantidad de segundos no enviados desde LocalStorage.
		 */
		getAcumulatedSeconds: function () {
			let seconds = getLocalStorageData(this.acumulatedRecordName)
			return seconds !== null && !isNaN(seconds) && isFinite(seconds) ? seconds : 0
		},

		/**
		 * Resta una cantidad específica de segundos del acumulado.
		 */
		subtractAcumulatedSeconds: function (seconds) {
			let existingSeconds = this.getAcumulatedSeconds()
			let newSeconds = Math.max(0, existingSeconds - seconds) // Evita números negativos
			if (newSeconds === 0) {
				removeLocalStorageData(this.acumulatedRecordName)
			} else {
				setLocalStorageData(this.acumulatedRecordName, newSeconds)
			}
		},

		/**
		 * Proceso 1: Recolección Local Constante
		 * Se ejecuta cada N segundos. Solo alimenta la variable local de tiempo de uso.
		 */
		collectTimeHandler: function () {
			registerUsedTimeConfiguration.addAcumulatedSeconds(registerUsedTimeSecondsBase)
		},

		/**
		 * Proceso 2: Envío al Servidor
		 * Verifica si hay tiempo acumulado y trata de enviarlo. Si falla programa un reintento.
		 */
		sendTimeHandler: function () {
			// Si ya hay un envío en curso, espera.
			if (registerUsedTimeConfiguration.isSending) return

			const accumulatedSeconds = registerUsedTimeConfiguration.getAcumulatedSeconds()

			// Si no hay tiempo que enviar o no hay sesión o no hay internet, no pasa nada
			if (accumulatedSeconds <= 0 || !pcsphpGlobals.globalAuthenticator.hasSession() || !isOnline()) {
				return
			}
			registerUsedTimeConfiguration.isSending = true

			// Congelamos la cantidad a enviar en esta petición específica
			const secondsToSend = accumulatedSeconds
			let formData = new FormData()
			formData.set('user_id', userID)
			formData.set('seconds', secondsToSend)
			pcsphpGlobals.globalAuthenticator.post(url, formData).then(response => {
				if (response.status == 200) {
					// Éxito: Restamos exactamente lo que enviamos (no reseteamos a 0 por si el recolector sumó más durante el fetch)
					registerUsedTimeConfiguration.subtractAcumulatedSeconds(secondsToSend)
					// Reiniciamos el tiempo de backoff ya que funcionó
					registerUsedTimeConfiguration.retrySeconds = registerUsedTimeRetrySeconds
					registerUsedTimeConfiguration.isSending = false
				} else {
					// Errores de API (403, etc): Forzar ejecución del backoff (catch)
					throw new Error(`Server returned status: ${response.status}`)
				}
			}).catch(error => {
				// Hubo un error. Puede ser 403, 500, o de red/timeout.
				// Activa el Backoff Programando el reintento usando setTimeout
				setTimeout(() => {
					registerUsedTimeConfiguration.isSending = false
					// Trata de enviar de nuevo
					registerUsedTimeConfiguration.sendTimeHandler()
				}, registerUsedTimeConfiguration.retrySeconds * 1000)

				// Prepara el próximo retraso (Incremental). Si falla de nuevo, esperará un segundo más.
				registerUsedTimeConfiguration.retrySeconds += 1
			})
		}
	}

	// Intento de envío en la carga inicial (Si hay tiempo viejo acumulado)
	setTimeout(registerUsedTimeConfiguration.sendTimeHandler, 2000)

	// Configura la ejecución inicial del temporizador de recolección local (cada 5s)
	registerUsedTimeConfiguration.intervalCollectID = setInterval(registerUsedTimeConfiguration.collectTimeHandler, registerUsedTimeSecondsBase * 1000)

	// Configura el temporizador de envíos principal al servidor
	registerUsedTimeConfiguration.intervalSendID = setInterval(registerUsedTimeConfiguration.sendTimeHandler, registerUsedTimeSendSecondsBase * 1000)

}

function adminZoneSupportForm() {

	let buttonSupportSelector = '[support-button-js]'
	let modalSupportSelector = '.ui.modal[support-js]'
	let formSupportSelector = modalSupportSelector + ' form'
	let modalSupport = $(modalSupportSelector)
	let buttonSupport = $(buttonSupportSelector)

	if (buttonSupport.length > 0) {
		genericFormHandler(formSupportSelector)

		buttonSupport.on('click', (e) => {
			e.preventDefault()
			modalSupport.modal('show')
			return false
		})
	}

}

function adminSitemapUpdate() {

	let button = document.querySelector('[sitemap-update-trigger]')

	if (button !== null) {

		let formSitemap = document.createElement('form')
		formSitemap.method = 'POST'
		formSitemap.action = button.dataset.url
		formSitemap = $(formSitemap)

		genericFormHandler(formSitemap)

		button.addEventListener('click', function () {
			formSitemap.submit()
		})

	}

}

function adminClearCache() {

	let button = document.querySelector('[clear-cache-update-trigger]')

	if (button !== null) {

		let formClearCache = document.createElement('form')
		formClearCache.method = 'POST'
		formClearCache.action = button.dataset.url
		formClearCache = $(formClearCache)

		genericFormHandler(formClearCache)

		button.addEventListener('click', function () {
			formClearCache.submit()
		})

	}

}
