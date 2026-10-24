/// <reference path="../../core/js/helpers.js" />
/// <reference path="../../core/js/configurations.js" />
//Fuera del `load`: los list.js de los módulos inician DataTables en su propio `load`, que va después.
contentTablesStackable()

window.addEventListener('load', function (e) {
	timeOnPlatform()
	adminZoneSupportForm()
	adminAppKeyNag()
	contentFormsInitial()
	contentModals()
})

/**
 * @description Los formularios hechos con las piezas de contenido (`data-content-form`) nacen con la clase `initial` de
 * Fomantic, que apaga el rojo de lo obligatorio vacío, y la pierden al intentar guardar: el rojo sale solo entonces.
 * Los demás formularios del panel no se tocan.
 * @returns {void}
 */
function contentFormsInitial() {
	$('form[data-content-form]').each(function () {
		const form = $(this)
		const intentar = () => form.removeClass('initial')
		form.on('submit', intentar)
		form.find('[type="submit"]').on('click', intentar)
	})
}

/**
 * @description Los listados de contenido (`table.content-table`), como tarjetas en el móvil (diseño de contenidos, §2.2).
 * La integración de DataTables con Semantic marca TODA tabla `unstackable` (dataTables.semanticui.min.js): aquí se le
 * quita solo a las de contenido, al iniciarse, y cada dibujado pone en cada celda el `data-label` de su cabecera, que el
 * CSS del móvil enseña delante del valor (_content-screens.scss). Las demás tablas no se tocan.
 * @returns {void}
 */
function contentTablesStackable() {
	$(document).on('init.dt draw.dt', function (event, settings) {
		const table = $(settings.nTable)
		if (!table.is('table.content-table')) {
			return
		}
		table.removeClass('unstackable')
		//La paginación del contenedor de DataTables, para que envuelva en el móvil (_content-screens.scss).
		table.closest('.dt-container').addClass('content-table-container')
		const labels = table.find('> thead > tr > th').map((index, th) => $(th).text().trim()).get()
		table.find('> tbody > tr').each(function () {
			$(this).children('td').each(function (index) {
				if (typeof labels[index] == 'string') {
					this.setAttribute('data-label', labels[index])
				}
			})
		})
	})
}

/**
 * @description La ventana de formulario de las pantallas de contenido (panel/built-in/content/form-modal.php): la abre un
 * `[data-content-modal="<id>"]`, vacía (alta) o con `data-content-modal-values` (edición, por `name`). Guarda con
 * genericFormHandler sin su redirección ni su recarga (sus opciones ignoreRedirection e ignoreReload) y, al acabar
 * bien, cierra la ventana y recarga las tablas de la página. Un disparador sin su ventana en la página sigue su enlace.
 * @returns {void}
 */
function contentModals() {
	$('form[data-content-modal-form]').each(function () {
		const form = $(this)
		const modal = form.closest('.ui.modal')
		form.data('contentModalDefaults', {
			action: form.attr('action'),
			title: modal.find('[data-content-modal-header]').text(),
		})
		form.find('[required]').attr('data-content-modal-required', '')
		//Un `hidden` cambia su valor por defecto al asignarlo, y reset() ya no lo devuelve: se guarda el de partida.
		form.find('input[type="hidden"]').each(function () {
			$(this).data('contentModalInitial', this.value)
		})
		genericFormHandler(form, {
			ignoreRedirection: true,
			ignoreReload: true,
			onSuccess: function () {
				modal.modal('hide')
				$.fn.dataTable.tables({ api: true }).ajax.reload(null, false)
			},
		})
	})

	$(document).on('click', '[data-content-modal]', function (event) {
		const modal = $(document.getElementById(this.getAttribute('data-content-modal') || ''))
		const form = modal.find('form[data-content-modal-form]')
		if (form.length === 0) {
			return
		}
		event.preventDefault()
		let values = {}
		try {
			values = JSON.parse(this.getAttribute('data-content-modal-values') || '{}') || {}
		} catch (error) {
			values = {}
		}
		const defaults = form.data('contentModalDefaults')
		form[0].reset()
		form.find('input[type="hidden"]').each(function () {
			this.value = $(this).data('contentModalInitial') ?? ''
		})
		form.addClass('initial')
		form.attr('action', this.getAttribute('data-content-modal-action') || defaults.action)
		modal.find('[data-content-modal-header]').text(this.getAttribute('data-content-modal-title') || defaults.title)
		//Lo que solo va en la edición (el id) se envía si los valores lo traen; un campo deshabilitado no viaja.
		form.find('[data-content-modal-when-value]').each(function () {
			this.disabled = !Object.prototype.hasOwnProperty.call(values, this.name)
		})
		//`__required`: qué campos son obligatorios en esta apertura (el nombre del idioma base de ESA categoría).
		const required = Array.isArray(values.__required) ? values.__required : null
		form.find('[data-content-modal-required], [data-content-modal-requirable]').each(function () {
			this.required = required === null ? this.hasAttribute('data-content-modal-required') : required.indexOf(this.name) !== -1
		})
		for (const name in values) {
			if (name.indexOf('__') !== 0) {
				form.find('[name]').filter((index, element) => element.name === name).val(values[name])
			}
		}
		modal.modal('show')
	})

	$(document).on('click', '[data-content-modal-cancel]', function () {
		$(this).closest('.ui.modal').modal('hide')
	})
}

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

/**
 * @description Inicializa los avisos flotantes del sistema: cada uno descartable por su data-key (y su data-version, si
 * la tiene), y el descarte se recuerda 7 días en este navegador. El de app_key conserva su cookie de siempre (pcsphp-app-key-nag).
 * @returns {void}
 */
function adminAppKeyNag() {
	if (typeof $.fn.nag == 'undefined') {
		return
	}
	$('[system-alert-nag]').each(function () {
		const key = String(this.getAttribute('data-key') || '')
		//Con versión (p. ej. la hora del último error), cerrarlo vale hasta que cambie: otra cookie.
		const version = String(this.getAttribute('data-version') || '')
		$(this).nag({
			persist: false,
			storageMethod: 'cookie',
			key: key === 'app-key-placeholder' ? 'pcsphp-app-key-nag' : 'pcsphp-system-alert-nag-' + key + (version !== '' ? '-' + version : ''),
			value: 'dismiss',
			expires: 7,
			path: '/',
		})
	})
}
