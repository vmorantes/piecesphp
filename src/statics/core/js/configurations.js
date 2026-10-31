
/// <reference path="./translations/es.js" />
/// <reference path="./translations/en.js" />
/// <reference path="./translations/fr.js" />
/// <reference path="./translations/de.js" />
/// <reference path="./translations/it.js" />
/// <reference path="./translations/pt.js" />
/// <reference path="./helpers.js" />
/// <reference path="./user-system/PiecesPHPSystemUserHelper.js" />
/**
 * Datos accesibles globalmente
 * @namespace
 */
var pcsphpGlobals = {}

/**
 * @type {PiecesPHPSystemUserHelper}
 */
pcsphpGlobals.globalAuthenticator = null

//──── Events ────────────────────────────────────────────────────────────────────────────
pcsphpGlobals.events = {
	configurationsLoad: 'PiecesPHP-Configurations-Load',
	configurationsAndWindowLoad: 'PiecesPHP-Configurations-And-Window-Load',
}

//──── URL ───────────────────────────────────────────────────────────────────────────────
pcsphpGlobals.baseURL = document.head.baseURI
pcsphpGlobals.adminURLConfig = (function () {
	let adminURLConfig = document.querySelector('html head meta[name="config-admin-url"]')
	const defaultValue = {
		relative: true,
		url: "admin",
	}
	if (adminURLConfig !== null) {
		adminURLConfig = adminURLConfig.getAttribute('value')
		adminURLConfig = typeof adminURLConfig == 'string' ? atob(adminURLConfig) : null
		try {
			adminURLConfig = typeof adminURLConfig == 'string' ? JSON.parse(adminURLConfig) : null
		} catch (e) {
			adminURLConfig = null
		}
	}
	if (adminURLConfig === null) {
		adminURLConfig = defaultValue
	}
	return adminURLConfig
})()
pcsphpGlobals.frontConfigurationsFromBackend = (function () {
	let containerData = document.querySelector('html head meta[name="front-configurations"]')
	let frontConfigurations = {}
	const defaultValue = {}
	if (containerData !== null) {
		frontConfigurations = containerData.getAttribute('value')
		frontConfigurations = typeof frontConfigurations == 'string' ? atob(frontConfigurations) : null
		try {
			frontConfigurations = typeof frontConfigurations == 'string' ? JSON.parse(frontConfigurations) : null
		} catch (e) {
			frontConfigurations = null
		}
	}

	if (frontConfigurations === null || Array.isArray(frontConfigurations)) {
		frontConfigurations = defaultValue
	}
	return frontConfigurations
})()
pcsphpGlobals.langMessagesFromServerURL = (function () {
	let langMessagesFromServerURL = document.querySelector('html head meta[name="lang-messages-from-server-url"]')
	if (langMessagesFromServerURL !== null) {
		langMessagesFromServerURL = langMessagesFromServerURL.getAttribute('value')
		langMessagesFromServerURL = typeof langMessagesFromServerURL == 'string' ? atob(langMessagesFromServerURL) : null
		try {
			langMessagesFromServerURL = typeof langMessagesFromServerURL == 'string' ? langMessagesFromServerURL : ''
			langMessagesFromServerURL = langMessagesFromServerURL.trim().length > 0 ? langMessagesFromServerURL : null
		} catch (e) {
			langMessagesFromServerURL = null
		}
	}
	return langMessagesFromServerURL
})()
pcsphpGlobals.langMessagesFromServerURLRequested = []
//La dirección de la clave de Mapbox la pone el layout del panel, deducida de su nombre (P51): nada de escribirla aquí.
pcsphpGlobals.mapboxKeyURL = (function () {
	const meta = document.querySelector('html head meta[name="mapbox-key-url"]')
	const value = meta !== null ? meta.getAttribute('value') : null
	const url = typeof value == 'string' ? atob(value).trim() : ''
	return url.length > 0 ? url : null
})()
//El centro por omisión de los mapas (Configuración › Sistema › Llaves de MapBox), por el mismo camino: el layout.
//`null` si no llega, y cada mapa usa entonces el suyo de reserva.
pcsphpGlobals.mapDefaultCenter = (function () {
	const meta = document.querySelector('html head meta[name="map-default-center"]')
	const value = meta !== null ? meta.getAttribute('value') : null
	try {
		const center = typeof value == 'string' ? JSON.parse(atob(value)) : null
		const valid = center !== null && typeof center == 'object' && ['longitude', 'latitude', 'zoom'].every((name) => typeof center[name] == 'number' && isFinite(center[name]))
		return valid ? { longitude: center.longitude, latitude: center.latitude, zoom: center.zoom } : null
	} catch (error) {
		return null
	}
})()

//──── Lenguaje ──────────────────────────────────────────────────────────────────────────
pcsphpGlobals.lang = (function () {
	let langHTML = document.querySelector('html').getAttribute('lang')

	let lang = 'es'

	if (langHTML != null && langHTML.length > 0) {
		lang = langHTML
	}

	return lang
})()
pcsphpGlobals.defaultLang = (function () {
	let defaultLangHTML = document.querySelector('html').getAttribute('dlang')

	let lang = 'es'

	if (defaultLangHTML != null && defaultLangHTML.length > 0) {
		lang = defaultLangHTML
	}

	return lang
})()

pcsphpGlobals.messagesProxySetHandler = function (target, property, value) {
	target[property] = value
	return true
}
pcsphpGlobals.messages = {
	es: new Proxy(PCSPHP_TRANSLATIONS_ES, {
		set: pcsphpGlobals.messagesProxySetHandler,
	}),
	en: new Proxy(PCSPHP_TRANSLATIONS_EN, {
		set: pcsphpGlobals.messagesProxySetHandler,
	}),
	//IDIOMA COMENTADO: la receta completa está en `app/config/lang.php`. Cada entrada de
	//aquí necesita su `translations/<código>.js`, porque la constante se lee al cargar y
	//una que no exista revienta el archivo entero, no solo su idioma.
	//fr: new Proxy(PCSPHP_TRANSLATIONS_FR, {
	//	set: pcsphpGlobals.messagesProxySetHandler,
	//}),
	//de: new Proxy(PCSPHP_TRANSLATIONS_DE, {
	//	set: pcsphpGlobals.messagesProxySetHandler,
	//}),
	//it: new Proxy(PCSPHP_TRANSLATIONS_IT, {
	//	set: pcsphpGlobals.messagesProxySetHandler,
	//}),
	//pt: new Proxy(PCSPHP_TRANSLATIONS_PT, {
	//	set: pcsphpGlobals.messagesProxySetHandler,
	//}),
}

if (typeof pcsphpGlobals.messages[pcsphpGlobals.lang] == 'undefined') {
	pcsphpGlobals.messages[pcsphpGlobals.lang] = pcsphpGlobals.messages[pcsphpGlobals.defaultLang]
}

//────────────────────────────────────────────────────────────────────────────────────────

pcsphpGlobals.cacheStamp = (function () {
	let cacheStamp = document.querySelector('html head meta[name="cache-stamp"]')
	if (cacheStamp !== null) {
		cacheStamp = cacheStamp.getAttribute('value')
	}
	if (cacheStamp === null) {
		cacheStamp = 'none'
	}
	return cacheStamp
})()

/**
 * Configuración de los calendarios
 * 
 * @property configCalendar
 * @type {Object}
 */
pcsphpGlobals.configCalendar = {
	type: 'date',
	formatter: {
		date: function (date, settings) {
			if (!(date instanceof Date)) return ''
			return formatDate(date, 'd-m-Y')
		},
		datetime: function (date, settings) {
			if (!(date instanceof Date)) return ''
			return formatDate(date, 'd-m-Y h:i A')
		},
		month: 'MMMM YYYY',
		monthHeader: 'YYYY',
		time: 'h:mm A',
		year: 'YYYY',
	},
	text: {
		days: _i18n('semantic_calendar', 'days'),
		months: _i18n('semantic_calendar', 'months'),
		monthsShort: _i18n('semantic_calendar', 'monthsShort'),
		today: _i18n('semantic_calendar', 'today'),
		now: _i18n('semantic_calendar', 'now'),
		am: _i18n('semantic_calendar', 'am'),
		pm: _i18n('semantic_calendar', 'pm')
	}
}

/**
 * Configuración de las tablas DataTables
 * 
 * @property configCalendar
 * @type {Object}
 */
pcsphpGlobals.configDataTables = {
	"searching": true,
	"pageLength": 10,
	//Como el prototipo: Anterior · 1–5 · … · N · Siguiente, sin Primero ni Último.
	"pagingType": "simple_numbers",
	//Responsive solo se carga si un listado lo pide (import_datatables(['responsive'])): apagado por omisión.
	"responsive": false,
	"language": _i18n('datatables', 'lang'),
	"order": [],
}

/**
 * Configuración de Cropper
 * 
 * @property configCropper
 * @type {Object}
 */
pcsphpGlobals.configCropper = {
	aspectRatio: 4 / 3,
	background: true,
	checkCrossOrigin: false,
	responsive: true,
	minCropBoxWidth: 1000,
	viewMode: 3
}

/**
 * Medidas (en pixeles) y otros datos útiles para responsive
 */
pcsphpGlobals.responsive = {
	sizes: {
		rsXLarge: 1700,
		rsLarge: 1550,
		rsLarge1: 1400,
		rsLarge2: 1250,
		rsLarge3: 1200,
		rsMedium: 1000,
		rsTablet: 780,
		rsMobileMedium: 540,
		rsMobile: 480,
	},
	class: {
		rsXLarge: 'rs-xl-arge',
		rsLarge: 'rs-large',
		rsLarge1: 'rs-large1',
		rsLarge2: 'rs-large2',
		rsLarge3: 'rs-large3',
		rsMedium: 'rs-medium',
		rsTablet: 'rs-tablet',
		rsMobileMedium: 'rs-mobile-medium',
		rsMobile: 'rs-mobile',
	},
}

if (typeof $ !== 'undefined') {
	window.addEventListener('load', function (e) {

		configCalendars()
		configMessagesSemantic()
		configDataTables()
		configColorPickers()
		pcsAdminShell('.ui.shell.admin-shell')
		pcsAdminTopbars()
		pcsContentTabsActiveInView()
		genericFormHandler()
		configRichEditor()

		let toggleDevCSSMode = $('[toggle-dev-css-mode]')
		let toggleDevCSSModeIsActive = typeof toggleDevCSSMode.attr('active') == 'string'

		toggleDevCSSMode.click(function (e) {

			let that = $(e.currentTarget)
			let selector = that.attr('toggle-dev-css-mode')

			if (typeof selector == 'string' && selector.trim().length > 0) {

				let classToAdd = 'dev-css-mode'
				let element = $(selector)

				if (element.hasClass(classToAdd)) {
					element.removeClass(classToAdd)
					that.find(`[type="checkbox"]`).attr('checked', false)
				} else {
					element.addClass(classToAdd)
					that.find(`[type="checkbox"]`).attr('checked', true)
				}

			}

		})

		if (toggleDevCSSModeIsActive) {
			toggleDevCSSMode.click()
		}

		//Poner etiqueta alt a imágenes que no la tengan
		let notAltImages = document.querySelectorAll(`img:not([alt])`)
		notAltImages.forEach(function (element) {
			element.setAttribute('alt', element.src.indexOf('/') !== -1 ? element.src.split('/').reverse()[0] : element.src)
		})

	})

}

/**
 * @param {String} [name] Si no se provee un nombre se retornan todas las configuraciones
 */
function getPCSPHPConfig(name = null) {
	if (name !== null) {
		name = typeof name == 'string' ? name : ''
		if (typeof pcsphpGlobals[name] !== 'undefined') {
			return pcsphpGlobals[name]
		}
	} else {
		return pcsphpGlobals
	}
	return null
}

/**
 * configCalendars
 * @returns {void}
 */
function configCalendars() {

	try {
		configSinglesCalendar('calendar-js')
		configGroupCalendar('calendar-group-js')
	} catch (error) {
		if ($('').calendar !== undefined) {
			console.error(error)
		}
	}
}

/**
 * @param {Object} options
 * @returns {void}
 */
function configSinglesCalendar(selectorAttr, options = {}) {

	selectorAttr = typeof selectorAttr == 'string' && selectorAttr.length > 0 ? selectorAttr : null
	let calendars = $(`[${selectorAttr}]`).toArray()

	for (let calendar of calendars) {
		calendar = $(calendar)
		let calendarType = calendar.attr('calendar-type')
		calendarType = typeof calendarType == 'string' && calendarType.trim().length > 0 ? calendarType.trim() : 'date'
		calendarType = [
			'date',
			'datetime',
			'month',
			'time',
		].indexOf(calendarType) !== -1 ? calendarType : 'datetime'
		let calendarOptions = Object.assign({}, pcsphpGlobals.configCalendar)
		calendarOptions.type = calendarType

		for (const customOption in options) {
			calendarOptions[customOption] = options[customOption]
		}
		$(calendar).calendar(calendarOptions)
	}

}

/**
 * @param {Object} options
 * @returns {Object[]}
 */
function configGroupCalendar(selectorAttr, selectorAttrName, options = {}) {

	selectorAttr = typeof selectorAttr == 'string' && selectorAttr.length > 0 ? selectorAttr : null
	selectorAttrName = typeof selectorAttrName == 'string' && selectorAttrName.length > 0 ? selectorAttrName : selectorAttr
	let groupCalendars = $(`[${selectorAttr}]`).toArray()

	const result = []
	let groups = []

	for (let groupCalendar of groupCalendars) {
		let groupName = $(groupCalendar).attr(selectorAttrName)
		if (groups.indexOf(groupName) == -1 && typeof groupName == 'string' && groupName.trim().length > 0) {
			groups.push(groupName)
		}
	}

	for (let group of groups) {

		let start = $($(`[${selectorAttrName}='${group}'][start]`)[0])
		let end = $($(`[${selectorAttrName}='${group}'][end]`)[0])

		const originalStartHTML = start.get(0).outerHTML
		const originalEndHTML = end.get(0).outerHTML

		let minDate = start.attr('min')
		minDate = typeof minDate == 'string' && minDate.trim().length > 0 ? minDate.trim() : null
		try {
			minDate = minDate !== null ? new Date(minDate) : null
			if (!(minDate instanceof Date && !isNaN(minDate))) {
				minDate = null
			}
		} catch (error) {
			minDate = null
		}

		let maxDate = start.attr('max')
		maxDate = typeof maxDate == 'string' && maxDate.trim().length > 0 ? maxDate.trim() : null
		try {
			maxDate = maxDate !== null ? new Date(maxDate) : null
			if (!(maxDate instanceof Date && !isNaN(maxDate))) {
				maxDate = null
			}
		} catch (error) {
			maxDate = null
		}

		let startType = start.attr('calendar-type')
		startType = typeof startType == 'string' && startType.trim().length > 0 ? startType.trim() : 'datetime'

		let endType = end.attr('calendar-type')
		endType = typeof endType == 'string' && endType.trim().length > 0 ? endType.trim() : 'datetime'

		let baseConfig = Object.assign({}, pcsphpGlobals.configCalendar)

		for (const customOption in options) {
			baseConfig[customOption] = options[customOption]
		}

		let optStart = Object.assign({}, baseConfig)
		let optEnd = Object.assign({}, baseConfig)

		optStart.type = typeof options.type == 'string' ? options.type : startType
		optStart.minDate = minDate
		optStart.maxDate = maxDate
		optEnd.type = typeof options.type == 'string' ? options.type : endType
		optEnd.maxDate = maxDate

		optStart.endCalendar = end
		optEnd.startCalendar = start

		result[group] = {
			originalStartHTML: originalStartHTML,
			originalEndHTML: originalEndHTML,
			start: start.calendar(optStart),
			end: end.calendar(optEnd),
		}
		result[group].restart = function () {
			result[group].start.calendar('clear')
			result[group].end.calendar('clear')
		}
	}

	return result

}

/**
 * @returns {void}
 */
function configMessagesSemantic() {

	const lang = pcsphpGlobals.lang
	const messages = pcsphpGlobals.messages

	if (typeof messages[lang] != 'undefined') {

		if (
			$ !== undefined &&
			$.fn !== undefined
		) {

			if (
				$.fn.form !== undefined &&
				$.fn.form.settings !== undefined &&
				$.fn.form.settings.prompt !== undefined &&
				$.fn.form.settings.text !== undefined
			) {
				pcsphpGlobals.messages.en.semantic_form = {
					prompt: $.fn.form.settings.prompt,
					text: $.fn.form.settings.text,
				}

				$.fn.form.settings.prompt = messages[lang].semantic_form.prompt
				$.fn.form.settings.text = messages[lang].semantic_form.text

			}

			if ($.fn.dropdown !== undefined && messages[lang].semantic_dropdown !== undefined) {
				Object.assign($.fn.dropdown.settings.message, messages[lang].semantic_dropdown)
			}
			if (
				$.fn.search !== undefined &&
				$.fn.search.settings !== undefined &&
				$.fn.search.settings.error !== undefined
			) {
				pcsphpGlobals.messages.en.semantic_search = {
					error: $.fn.search.settings.error,
				}
				$.fn.search.settings.error = messages[lang].semantic_search.error
			}

		}
	}

}

/**
 * configDataTables
 * @returns {void}
 */
function configDataTables() {
	let tablas = $('[datatable-js]')

	try {
		tablas.DataTable(pcsphpGlobals.configDataTables)
	} catch (error) {
		if (tablas.DataTable !== undefined) {
			console.error(error)
		}
	}
}

/**
 * configRichEditor
 * @returns {void}
 */
function configRichEditor() {

	try {
		if (typeof RichEditorAdapterComponent == 'function') {

			let elementRichEditorSelector = '[rich-editor-js]'
			let elementRichEditor = $(elementRichEditorSelector)

			if (elementRichEditor.length > 0) {

				new RichEditorAdapterComponent({
					containerSelector: elementRichEditorSelector,
					textareaTargetSelector: elementRichEditor.attr('editor-target'),
				})

			}

		}

	} catch (error) {
		if (error.name == 'ReferenceError') {
			console.warn("RichEditorAdapterComponent no está definido.")
		} else {
			console.warn(error)
		}
	}
}

/**
 * Los textos del selector de color del tema, si el idioma los tiene: el tema los trae en inglés.
 * @returns {void}
 */
function pcsColorPickerTexts() {
	const messages = pcsphpGlobals.messages[pcsphpGlobals.lang]
	if (messages !== undefined && messages.colorpicker !== undefined) {
		Object.assign($.fn.colorpicker.settings.text, messages.colorpicker)
	}
}

/**
 * Los campos `input[color-picker-js]`: formato en `data-color-picker-format` y transparencia con
 * `data-color-picker-alpha="yes"`. El selector es del tema del panel: la zona pública no lo trae.
 * @returns {void}
 */
function configColorPickers() {

	if ($.fn.colorpicker === undefined) {
		return
	}

	pcsColorPickerTexts()

	$('input[color-picker-js]').each(function () {
		const picker = $(this)
		const format = picker.data('color-picker-format')
		picker.colorpicker({
			format: ['hex', 'rgb', 'hsl', 'oklch'].includes(format) ? format : 'hex',
			alpha: picker.data('color-picker-alpha') === 'yes',
			allowEmpty: true,
		})
	})
}

/**
 * Configura el armazón del panel (`ui shell` del tema): navegación, riel, cajón y su estado recordado.
 *
 * @param {HTMLElement|JQuery|string} selector Selector o elemento del shell
 * @returns {void}
 */
function pcsAdminShell(selector) {
	const shell = $(selector)
	if (shell.length === 0) {
		return
	}
	shell.shell({
		rememberState: true,
		text: shell.data('shellText') || {},
	})
	//Los avisos fijos de abajo (topbar.php) tapaban el pie de la navegación: su alto, en la variable que descuenta
	//_topbar.scss. Cambia al cerrar uno.
	const nags = document.querySelector('.system-alert-nags')
	if (nags !== null && typeof ResizeObserver === 'function') {
		const update = () => document.body.style.setProperty('--pcs-bottom-nags', nags.offsetHeight + 'px')
		new ResizeObserver(update).observe(nags)
		update()
	}
}

/**
 * Verifica si la navegación del panel está abierta: expandida en escritorio y tableta, o el cajón abierto en el móvil.
 *
 * @returns {boolean}
 */
function pcsAdminSideBarIsOpen() {
	const shell = $('.ui.shell.admin-shell')
	if (shell.length === 0) {
		return false
	}
	if (shell.shell('is drawer')) {
		return shell.hasClass('open')
	}
	return !shell.shell('is collapsed')
}

/**
 * Cambia el estado de la navegación del panel: la contrae o la expande; en el móvil, abre o cierra el cajón.
 *
 * @param {boolean} [noUpdateStatus] No recuerda el estado nuevo (en escritorio el shell lo guarda en el localStorage)
 * @returns {void}
 */
function pcsAdminSideBarToggle(noUpdateStatus = false) {
	const shell = $('.ui.shell.admin-shell')
	if (shell.length === 0) {
		return
	}
	if (shell.shell('is drawer')) {
		shell.shell(shell.hasClass('open') ? 'hide drawer' : 'show drawer')
		return
	}
	if (noUpdateStatus) {
		shell.shell('setting', 'rememberState', false)
	}
	shell.shell(shell.shell('is collapsed') ? 'expand' : 'collapse')
	if (noUpdateStatus) {
		shell.shell('setting', 'rememberState', true)
	}
}

/**
 * El panel de Configuración (topbar.php): el flyout, sus grupos plegables y el filtro, del patrón `panel-configuracion`
 * del tema. El foco va solo al buscador al terminar de abrir (autofocus del flyout).
 *
 * @param {$} toggle El botón de la barra que lo abre
 * @param {$} flyout
 * @returns {void}
 */
function pcsAdminOptionsFlyout(toggle, flyout) {

	const groups = flyout.find('[admin-options-groups]')
	const search = flyout.find('[admin-options-search]')
	const empty = flyout.find('[admin-options-empty]')
	const titles = groups.children('.title')

	flyout.flyout({
		closeIcon: true,
		onVisible: function () {
			toggle.attr('aria-expanded', 'true')
		},
		onHidden: function () {
			toggle.attr('aria-expanded', 'false').trigger('focus')
		},
	})

	const syncExpanded = function () {
		titles.each(function () {
			$(this).attr('aria-expanded', String($(this).hasClass('active')))
		})
	}
	groups.accordion({ exclusive: false, onChange: syncExpanded })
	groups.on('keydown', '> .title', function (event) {
		if (event.key === 'Enter' || event.key === ' ') {
			event.preventDefault()
			$(this).trigger('click')
		}
	})

	const normalize = (text) => text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase()

	//Qué grupos estaban abiertos antes de filtrar: al vaciar el filtro se vuelve a ese estado.
	let openBefore = null
	const filter = function () {
		const query = normalize(search.val().trim())
		if (query !== '' && openBefore === null) {
			openBefore = titles.map(function () {
				return $(this).hasClass('active')
			}).get()
		}
		let anyVisible = false
		titles.each(function (index) {
			const title = $(this)
			const content = title.next('.content')
			const inTitle = query !== '' && normalize(title.find('.texto').text()).includes(query)
			let visibleLinks = 0
			content.find('.item').each(function () {
				const matches = query === '' || inTitle || normalize($(this).text()).includes(query)
				$(this).prop('hidden', !matches)
				visibleLinks += matches ? 1 : 0
			})
			title.add(content).prop('hidden', visibleLinks === 0)
			title.find('.label').text(visibleLinks)
			const open = query !== '' ? visibleLinks > 0 : (openBefore !== null && openBefore[index] === true)
			title.add(content).toggleClass('active', open)
			anyVisible = anyVisible || visibleLinks > 0
		})
		if (query === '') {
			openBefore = null
		}
		syncExpanded()
		empty.prop('hidden', anyVisible).text(anyVisible ? '' : formatStr(_i18n('adminOptions', 'Sin resultados para “%r”'), [search.val().trim()]))
	}
	search.on('input', filter).on('keydown', function (event) {
		if (event.key === 'Escape' && this.value !== '') {
			event.stopPropagation()
			this.value = ''
			filter()
		}
	})

	toggle.on('click', function (e) {
		e.preventDefault()
		flyout.flyout('show')
	})

}

/**
 * Configura la barra superior del panel (topbar-bar.php) y lo que abre (topbar.php): el menú de usuario (`ui dropdown`),
 * las noticias (`ui popup`), Configuración y el perfil en línea (`ui flyout`).
 *
 * @returns {void}
 */
function pcsAdminTopbars() {

	const userDropdown = $('[user-options-dropdown]')
	const newsToggle = $('[news-toggle]')
	const newsPopup = $('[news-popup]')
	const adminToggle = $('[admin-options-toggle]')
	const adminFlyout = $('[admin-options-flyout]')
	const profileFlyout = $('[profile-flyout]')
	const profileContainer = $('.profile-content-system')
	const exitAsLoggedOtherProfileButton = $('[exit-as-logged-options]')

	if (userDropdown.length > 0) {
		userDropdown.dropdown({ action: 'hide' })
	}

	if (newsToggle.length > 0 && newsPopup.length > 0) {
		newsToggle.popup({ popup: newsPopup, on: 'click', position: 'bottom right', lastResort: 'bottom right', movePopup: false })
	}

	if (adminToggle.length > 0 && adminFlyout.length > 0) {
		pcsAdminOptionsFlyout(adminToggle, adminFlyout)
	}

	if (profileFlyout.length > 0) {
		profileFlyout.flyout({ closeIcon: true })
	}

	if (exitAsLoggedOtherProfileButton.length > 0) {
		//La cookie de la suplantación es HttpOnly: la borra el servidor (P204 a).
		exitAsLoggedOtherProfileButton.on('click', function (e) {
			e.preventDefault()
			postRequest($(e.currentTarget).data('url')).always(function () {
				location.reload()
			})
		})
	}

	//El modo de color (topbar-bar.php): se guarda en la meta del usuario y se aplica al momento (light, dark o auto).
	const themeSelector = $('[theme-mode-selector]')
	themeSelector.find('[data-theme-mode]').on('click', function (e) {
		e.preventDefault()
		e.stopPropagation()
		const option = $(this)
		const mode = option.data('themeMode')
		postRequest(themeSelector.data('url'), { mode: mode }).done(function (response) {
			if (response.success) {
				document.documentElement.setAttribute('data-theme', mode)
				themeSelector.find('[data-theme-mode]').removeClass('active').attr({ 'aria-checked': 'false', tabindex: '-1' })
				option.addClass('active').attr({ 'aria-checked': 'true', tabindex: '0' })
			}
		})
	})

	const openProfile = (tab) => {
		userDropdown.dropdown('hide')
		profileFlyout.flyout('show')
		tabsController(tab)
	}

	userDropdown.find('[edit-account]').on('click', (e) => {
		e.preventDefault()
		openProfile('account')
	})
	$('[external-trigger-edit-account]').on('click', (e) => {
		e.preventDefault()
		e.stopPropagation()
		openProfile('account')
	})
	userDropdown.find('[change-password]').on('click', (e) => {
		e.preventDefault()
		openProfile('password')
	})
	profileContainer.find('[close-profile]').on('click', closeProfile)

	function closeProfile() {
		if (profileFlyout.length > 0) {
			profileFlyout.flyout('hide')
		}
	}

	const tabsController = (strDefauld = '') => {
		const tabs = profileContainer.find('[data-tab]')
		const views = profileContainer.find('[data-view]')

		//Se llama cada vez que se abre el lateral: sin el .off, cada apertura sumaba otro manejador a cada pestaña.
		tabs.off('click.profileTabs').on('click.profileTabs', (e) => {
			var target = $(e.target)

			if (target[0].nodeName === 'I' || target[0].nodeName === 'SPAN') {
				target = target.parent()
			}

			const tab = target.data('tab')

			views.each((index, view) => {
				const $view = $(view)

				if ($(tabs[index]).data('tab') === tab) {
					$(tabs[index]).addClass('active')
				} else {
					$(tabs[index]).removeClass('active')
				}

				if ($view.data('view') === tab) {
					$view.addClass('current')
				} else {
					$view.removeClass('current')
				}
			})
		})

		if (strDefauld != '') {
			tabs.filter(`[data-tab='${strDefauld}']`).trigger('click')
		}
	}

	const formAction = () => {
		const LOADER_NAME = 'editUser'

		const mainForm = $(".profile-content-system").find("form")

		mainForm.on('submit', (e) => {
			e.preventDefault()

			showGenericLoader(LOADER_NAME)

			const formData = new FormData(e.target)

			formData.set('is_profile', 'yes')

			postRequest($('.profile-content-system').attr('data-url'), formData)
				.done((res) => {
					if (res.success) {
						successMessage(res.message)
						setTimeout(() => location.reload(), 2000)
					} else {
						errorMessage(res.message)
					}
				})
				.always(() => {
					removeGenericLoader(LOADER_NAME)
				})
		})

		$(".profile-content-system").find('[delete-account-trigger]').off('click')
		$(".profile-content-system").find('[delete-account-trigger]').on('click', (e) => {
			e.preventDefault()
			$('body').addClass('wait-to-action')
			const handler = $('.delete-account-handler').toast({
				position: 'top center',
				classActions: 'top attached',
				displayTime: 0,
				onHidden: function () {
					$('body').removeClass('wait-to-action')
				},
			})
			const inputWord = handler.find('input')
			const deleteAccountButton = handler.find('[delete]')
			const cancelButton = handler.find('[cancel]')
			deleteAccountButton.on('click', () => {
				const expectedWord = deleteAccountButton.data('expected-word').trim().toLowerCase()
				const requestURL = deleteAccountButton.data('url')
				const word = inputWord.val().trim().toLowerCase()
				const isSameWord = expectedWord === word
				if (isSameWord) {
					postRequest(requestURL, {}).then(function (response) {
						location.reload()
					})
				}
			})
			cancelButton.on('click', () => {
				handler.toast('destroy')
				$('body').removeClass('wait-to-action')
			})
		})
	}

	const loadNews = () => {

		const mainContainerSelector = '[news-toolbar-container]'
		const mainContainer = $(mainContainerSelector)
		const newsModal = $('[news-modal]')
		const url = $(mainContainerSelector).parent().data('url')
		const markAsReadURL = pcsphpGlobals.frontConfigurationsFromBackend.NewsModuleMarkAsReadedEndpoint

		if (mainContainer.length > 0 && typeof NewsAdapter !== 'undefined' && typeof url == 'string' && url.trim().length > 1) {
			const newsManager = new NewsAdapter({
				requestURL: url,
				page: 1,
				perPage: 10,
				containerSelector: mainContainerSelector,
				onDraw: (item, parsed) => {
					parsed.on('click', () => {
						newsModal.find('.header').text(item.newsTitle).css('color', item.category.color)
						newsModal.find('.content').html(item.content)
						newsModal.modal('show')
						//Marcar como leída
						if (typeof markAsReadURL === 'string' && item.id) {
							postRequest(markAsReadURL.replace('{ID}', item.id)).done((response) => {
								if (response.success) {
									parsed.remove()
								}
							})
						}
						newsToggle.popup('hide')
					})
					return parsed
				},
				onEmpty: (container) => {
					container.html('...')
				},
			})

			newsManager.loadItems()
		}
	}

	//La foto de perfil abre el editor con la imagen actual (P276 a); sin foto, el selector de archivo.
	const imageModalProfile = () => {
		const photo = document.querySelector('[data-profile-photo]')
		if (photo === null || typeof ContentImageEditor == 'undefined') {
			return
		}

		new ContentImageEditor('profile').onApply('profile-image-card', (file) => {
			const LOADER_NAME = 'updatePhotoLoader'
			const formData = new FormData()

			formData.set('user_id', photo.dataset.userId)
			formData.set('image', file)

			showGenericLoader(LOADER_NAME)

			postRequest(photo.dataset.url, formData)
				.done((resp => {
					if (resp.success) {
						successMessage(resp.message)
						setTimeout(() => location.reload(), 2000)
					} else {
						errorMessage(resp.message)
					}
				}))
				.always(() => {
					removeGenericLoader(LOADER_NAME)
				})
		})

		$("[action-image-profile]").on('click', () => {
			closeProfile()
			const current = photo.querySelector('[data-image-card-edit]')
			const trigger = current !== null ? current : photo.querySelector('[data-input]')
			trigger.click()
		})
	}

	imageModalProfile()
	loadNews()
	formAction()
}

/**
 * Internacionalización de mensajes
 * 
 * @param {string} type Tipo de mensaje
 * @param {*} message Mensaje
 */
function _i18n(type, message) {

	let messages = pcsphpGlobals.messages
	let langs = [
		pcsphpGlobals.lang,
		pcsphpGlobals.defaultLang,
	]
	let lang = ''

	let exists = false

	for (let langToCheck of langs) {

		lang = langToCheck
		let existsLang = messages[lang] !== undefined

		if (existsLang) {

			let existsType = messages[lang][type] !== undefined

			if (existsType) {
				let existsMessage = messages[lang][type][message] !== undefined

				if (existsMessage) {
					exists = true
					break
				}

			}

		}

	}

	if (exists) {
		return messages[lang][type][message]
	} else {
		return message
	}
}

/**
 * Intente tomar desde el servidor las traducciones
 * @param {String|String[]} langGroup
 * @param {Boolean} repeat Repite la solicitud aunque haya sido hecho previamente
 */
function registerDynamicLocalizationMessages(langGroup, repeat = false) {

	const langGroups = Array.isArray(langGroup) ? langGroup : [langGroup]

	return new Promise(function (resolve) {
		const requestURL = pcsphpGlobals.langMessagesFromServerURL

		if (typeof requestURL == 'string' && requestURL.length > 0) {

			if (typeof pcsphpGlobals != 'object') {
				window.pcsphpGlobals = {}
			}
			if (typeof pcsphpGlobals.messages != 'object') {
				pcsphpGlobals.messages = {}
			}
			const url = new URL(requestURL)
			let groupAddedQty = 0
			for (let langGroup of langGroups) {
				let toAdd = true
				if (!pcsphpGlobals.langMessagesFromServerURLRequested.includes(langGroup)) {
					pcsphpGlobals.langMessagesFromServerURLRequested.push(langGroup)
				} else {
					if (!repeat) {
						toAdd = false
					}
				}
				if (toAdd) {
					url.searchParams.append('group[]', langGroup)
					groupAddedQty++
				}
			}

			if (groupAddedQty > 0) {
				getRequest(url, '', {}, {
					async: false,
				}).done(function (response) {

					for (const langGroupName in response) {

						const langGroupData = response[langGroupName]

						const defaultLangData = typeof langGroupData['default'] == 'object' ? langGroupData['default'] : {}
						const existentsMessages = []

						//Añadir idiomas regulares
						for (const langName in langGroupData) {

							if (langName !== 'default') {

								const langData = langGroupData[langName]

								//Si no existe en el objeto se crea
								if (typeof pcsphpGlobals.messages[langName] != 'object') {
									pcsphpGlobals.messages[langName] = {}
								}
								if (typeof pcsphpGlobals.messages[langName][langGroupName] != 'object') {
									pcsphpGlobals.messages[langName][langGroupName] = {}
								}

								//Añadir solo los que no estén presenten en JS
								for (const langMessage in langData) {
									const translateMessage = langData[langMessage]

									if (typeof pcsphpGlobals.messages[langName][langGroupName][langMessage] == 'undefined') {
										pcsphpGlobals.messages[langName][langGroupName][langMessage] = translateMessage
									} else {
										existentsMessages.push(`Ya hay una traducción para [ ${langMessage} ] en ${langGroupName}:${langName}`)
									}

								}

							}

						}

						//Añadir valores por defecto en todos los lugares donde no haya
						for (const langName in langGroupData) {

							if (langName !== 'default') {

								//Iterar sobre default
								for (const langMessage in defaultLangData) {
									const defaultTranslation = defaultLangData[langMessage]
									if (typeof pcsphpGlobals.messages[langName][langGroupName][langMessage] == 'undefined') {
										pcsphpGlobals.messages[langName][langGroupName][langMessage] = defaultTranslation
									}
								}

							}

						}

						if (existentsMessages.length > 0) {
							console.info(existentsMessages)
						}
					}

				}).done(function () {
					resolve()
				})
			} else {
				resolve()
			}

		}

	})
}

/**
 * Devuelve el valor de todo el grupo del lenguaje actual
 * @param {String} langGroup
 * @param {String} lang
 */
function getLangGroupData(langGroup, lang = null) {

	const currentLang = pcsphpGlobals.lang
	const selectedLang = typeof lang == 'string' && lang.length > 0 ? lang : currentLang
	let groupData = {}

	if (typeof pcsphpGlobals.messages[selectedLang] == 'object') {
		if (typeof pcsphpGlobals.messages[selectedLang][langGroup] == 'object') {
			groupData = pcsphpGlobals.messages[selectedLang][langGroup]
		}
	}

	return groupData
}

/**
 * Traduce los elementos de HTML que tengan el atributo lang-group
 * Este método de traducción no toma en cuenta el idioma por defecto
 */
function autoTranslateFromLangGroupHTML() {

	let ignoreLangs = pcsphpGlobals.frontConfigurationsFromBackend.autoTranslateFromLangGroupHTMLIgnoreLangs
	ignoreLangs = typeof ignoreLangs !== 'undefined' && Array.isArray(ignoreLangs) ? ignoreLangs.filter(e => typeof e == 'string') : []
	const elements = document.querySelectorAll('[lang-group]')
	const errors = []
	const translationsObject = {}
	const langGroupsToRequest = []

	for (const element of elements) {

		let internalElementsWithLangGroup = element.querySelectorAll('[lang-group]')

		//Verificar si hay anidados, eliminar los atributos en los anidados e informar de ese error
		while (internalElementsWithLangGroup.length > 0) {
			internalElementsWithLangGroup = element.querySelectorAll('[lang-group]')
			internalElementsWithLangGroup.forEach(e => {
				errors.push(`Hay elementos de traducción anidados: ${element.outerHTML}`)
				e.removeAttribute('lang-group')
			})
		}

		//Crear el objeto de traducciones
		const langGroup = element.getAttribute('lang-group')
		if (langGroup == null) {
			continue
		}
		if (typeof translationsObject[langGroup] == 'undefined') {
			translationsObject[langGroup] = {}
		}
		langGroupsToRequest.push(langGroup)
		translationsObject[langGroup][`${element.innerHTML}`] = element.innerHTML

	}

	registerDynamicLocalizationMessages(langGroupsToRequest).then(function () {

		//Remover los mensajes que ya están traducidos
		const translationsCurrentLang = pcsphpGlobals.messages[pcsphpGlobals.lang]
		for (const langGroup in translationsObject) {
			const groupMessages = translationsObject[langGroup]
			if (typeof translationsCurrentLang == 'object') {
				const translationsCurrentLangGroup = translationsCurrentLang[langGroup]
				if (typeof translationsCurrentLangGroup == 'object') {
					for (const message in groupMessages) {
						if (typeof translationsCurrentLangGroup[message] == 'string') {
							delete translationsObject[langGroup][message]
						}
					}
				}
			}
		}

		//Traducciones automáticas
		if (Object.keys(translationsObject).length > 0) {

			let hasPendingTranslations = false

			for (const langGroup in translationsObject) {
				const groupMessages = translationsObject[langGroup]
				if (Object.keys(groupMessages).length > 0) {
					hasPendingTranslations = true
					break
				}
			}

			new Promise(function (resolve, reject) {

				if (hasPendingTranslations) {

					//NOTE: Ignoro el idioma y creo siempre los modelos de traducción para todo
					//const currentLangIsDefault = pcsphpGlobals.defaultLang == pcsphpGlobals.lang
					const currentLangIsDefault = false
					const ignoreLang = ignoreLangs.includes(pcsphpGlobals.lang)

					if (!currentLangIsDefault) {
						errors.push(`Hay elementos de pendientes de traducción: ${JSON.stringify(translationsObject, null, 4)}`)
					}

					if (!currentLangIsDefault && !ignoreLang) {

						//El servidor traduce y guarda (#063): solo viajan las claves, UNA petición por grupo.
						const translations = {}
						const translateGroupPromises = []
						const loaderName = 'translationsLoader'
						showGenericLoader(loaderName)

						for (const langGroup in translationsObject) {

							const keys = Object.keys(translationsObject[langGroup])
							if (keys.length == 0) {
								continue
							}

							translateGroupPromises.push(new Promise(function (resolveGroup) {

								const translateGroupURL = new URL('core/api/translations/translateGroup', pcsphpGlobals.baseURL)
								const formData = new FormData()
								formData.set('keys', JSON.stringify(keys))
								formData.set('to', pcsphpGlobals.lang)
								formData.set('group', langGroup)

								postRequest(translateGroupURL, formData, {
									'PCSPHP-Response-Expected-Language': pcsphpGlobals.lang,
								}).done(function (response) {

									const groupTranslation = response.success && typeof response.translation == 'object' && response.translation !== null ? response.translation[langGroup] : null

									if (typeof groupTranslation == 'object' && groupTranslation !== null) {
										if (typeof pcsphpGlobals.messages[pcsphpGlobals.lang] != 'object') {
											pcsphpGlobals.messages[pcsphpGlobals.lang] = {}
										}
										if (typeof pcsphpGlobals.messages[pcsphpGlobals.lang][langGroup] != 'object') {
											pcsphpGlobals.messages[pcsphpGlobals.lang][langGroup] = {}
										}
										for (const key in groupTranslation) {
											pcsphpGlobals.messages[pcsphpGlobals.lang][langGroup][key] = groupTranslation[key]
										}
										translations[langGroup] = groupTranslation
										console.info(`Traducción guardada: ${langGroup}`)
									}

									if (typeof response.rejected == 'object' && response.rejected !== null && Object.keys(response.rejected).length > 0) {
										console.error(response.rejected)
									}

								}).always(function () {
									resolveGroup()
								})

							}))
						}

						Promise.all(translateGroupPromises).finally(function () {
							removeGenericLoader(loaderName)
							resolve(translations)
						})

					} else {
						resolve(null)
					}

				} else {
					resolve(null)
				}

			}).then(function (translations) {
				for (const element of elements) {
					const langGroup = element.getAttribute('lang-group')
					if (langGroup == null) {
						continue
					}
					const message = element.innerHTML
					element.innerHTML = _i18n(langGroup, message)
				}

				if (translations !== null) {
					console.info(translations)
				}
			})
		}

		if (errors.length > 0) {
			console.info([
				'====Errores encontrados en traducción automática====\n',
				errors.join('\n\n')
			].join('\n'))
		}
	})
}



/**
 * Maneja el cambio de idioma en el modal de cambio de idioma global.
 * 
 * Esta función se encarga de mostrar el modal de cambio de idioma cuando se hace clic en el elemento con el atributo 'change-system-lang-trigger'.
 * Una vez mostrado el modal, se configura el botón de cambio de idioma para que, al hacer clic en él, actualice la cookie de idioma y recargue la página.
 */
function handleGlobalChangeLangModal() {
	const trigger = $('[change-system-lang-trigger]')
	if (trigger.length > 0) {
		const modal = $('[change-system-lang-modal]').modal()
		const dropdown = modal.find('.ui.dropdown').dropdown()
		const changeButton = modal.find('button[type="submit"]')
		const langCookieName = modal.data('lang-cookie')
		trigger.off('click')
		trigger.on('click', function (e) {
			e.preventDefault()
			modal.modal({
				onVisible: function () {
					changeButton.off('click')
					changeButton.on('click', function (e) {
						e.preventDefault()
						const lang = dropdown.dropdown('get value')
						CookiesHandler.setCookie(langCookieName, lang)
						location.reload()
					})
				},
			}).modal('show')
		})
	}
}

/**
 * Maneja la lógica de conectarse como otro usuario (solo root)
 */
function handleConnectAsAnotherUser() {
	const modalSelector = '[connect-as-another-user-modal]'
	const trigger = $('[connect-as-another-user-trigger]')
	if (trigger.length > 0) {
		const modal = $(modalSelector).modal()
		const dropdown = configFomanticDropdown(`${modalSelector} .ui.dropdown`)
		const changeButton = modal.find('button[type="submit"]')
		const startURL = modal.data('url')
		const refusal = modal.find('[connect-as-another-user-refusal]')
		trigger.off('click')
		trigger.on('click', function (e) {
			e.preventDefault()
			modal.modal({
				onVisible: function () {
					refusal.addClass('hidden').text('')
					changeButton.off('click')
					changeButton.on('click', function (e) {
						e.preventDefault()
						const userID = dropdown[0].getValue()
						//La cookie la escribe el servidor, firmada; un rechazo trae su motivo y se enseña aquí.
						postRequest(startURL, { userID: userID }).done(function (response) {
							location.assign(response.values.redirect)
						}).fail(function (jqXHR) {
							const response = typeof jqXHR.responseJSON == 'object' && jqXHR.responseJSON !== null ? jqXHR.responseJSON : {}
							refusal.text(response.message || _i18n('errors', 'unexpected_error_try_later')).removeClass('hidden')
						})
					})
				},
			}).modal('show')
		})
	}
}

window.dispatchEvent(new Event(pcsphpGlobals.events.configurationsLoad))
/**
 * En el móvil, .content-tabs es una fila que se desliza: la pestaña activa, entera a la vista al cargar y al cambiar.
 * Con scrollLeft y no con scrollIntoView, que también movería la página en vertical.
 * @returns {void}
 */
function pcsContentTabsActiveInView() {
	const show = function (container) {
		const active = container.querySelector('.active')
		if (active === null) {
			return
		}
		const box = container.getBoundingClientRect()
		const tab = active.getBoundingClientRect()
		if (tab.left < box.left) {
			container.scrollLeft -= box.left - tab.left
		} else if (tab.right > box.right) {
			container.scrollLeft += tab.right - box.right
		}
	}
	document.querySelectorAll('.content-tabs').forEach(function (container) {
		show(container)
		container.addEventListener('click', function () {
			//Fomantic cambia la clase activa en el mismo clic: se mira después.
			setTimeout(function () {
				show(container)
			}, 0)
		})
		//Y otra vez al acabar su transición: la activa cambia de ancho mientras dura.
		container.addEventListener('transitionend', function () {
			show(container)
		})
	})
}

window.addEventListener('load', function () {
	window.dispatchEvent(new Event(pcsphpGlobals.events.configurationsAndWindowLoad))
	autoTranslateFromLangGroupHTML()
	handleGlobalChangeLangModal()
	handleConnectAsAnotherUser()
	registerDynamicLocalizationMessages(['global', 'general'])
})
