
/// <reference path="./translations/es.js" />
/// <reference path="./translations/en.js" />
/// <reference path="./translations/fr.js" />
/// <reference path="./translations/de.js" />
/// <reference path="./translations/it.js" />
/// <reference path="./translations/pt.js" />
/// <reference path="./helpers.js" />
/**
 * Datos accesibles globalmente
 * @namespace
 */
var pcsphpGlobals = {
	events: {
		configurationsLoad: 'PiecesPHP-Configurations-Load',
		configurationsAndWindowLoad: 'PiecesPHP-Configurations-And-Window-Load',
	}
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

pcsphpGlobals.messages = {
	es: PCSPHP_TRANSLATIONS_ES,
	en: PCSPHP_TRANSLATIONS_EN,
	fr: PCSPHP_TRANSLATIONS_FR,
	de: PCSPHP_TRANSLATIONS_DE,
	it: PCSPHP_TRANSLATIONS_IT,
	pt: PCSPHP_TRANSLATIONS_PT,
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
	"responsive": true,
	"language": _i18n('datatables', 'lang'),
	"order": [],
	"initComplete": function (settings, json) {
		let searchContainer = $('.dataTables_filter').parent()
		searchContainer.addClass('ui form')
	},
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
		pcsAdminSideBar('.ui-pcs.sidebar')
		pcsAdminTopbars()
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
		console.log(error)
		if (error.name == 'ReferenceError') {
			console.log("RichEditorAdapterComponent no está definido.")
		} else {
			console.log(error)
		}
	}
}

/**
 * configColorPickers
 * @returns {void}
 */
function configColorPickers() {

	let selector = 'input[color-picker-js]'
	let colorPickers = $(selector)

	try {

		const defaultConfigPicker = {
			color: null,
			preferredFormat: 'hex',
			showInput: true,
			showInitial: true,
			showAlpha: false,
			clickoutFiresChange: false,
			allowEmpty: true,
			flat: false,
			disabled: false,
			showButtons: true,
			chooseText: 'Aceptar',
			cancelText: 'Cancelar',
			showPalette: false,
			showSelectionPalette: false,
			togglePaletteOnly: true,
			togglePaletteMoreText: '+',
			togglePaletteLessText: '−',
			palette: [
				"red",
				"green",
				"blue",
				"purple",
				"yellow",
				"brown",
				"white",
				"gray",
				"black",
				"pink",
				"coral",
			],
		}
		const validFormats = [
			'hex',
			'hex3',
			'hsl',
			'rgb',
			'name',
		]
		const pickersSize = colorPickers.length

		for (let i = 0; i < pickersSize; i++) {
			const picker = $(colorPickers.get(i))
			const configPicker = Object.assign({}, defaultConfigPicker)

			const withAlpha = picker.data('color-picker-alpha') === 'yes'
			const format = picker.data('color-picker-format')

			if (typeof format !== 'undefined' && validFormats.indexOf(format) !== -1) {
				configPicker.preferredFormat = format
			}
			configPicker.showAlpha = withAlpha

			picker.spectrum(configPicker)
		}

	} catch (error) {
		if (colorPickers.spectrum !== undefined) {
			console.error(error)
		}
	}
}

/**
 * Configura la barra lateral de PiecesPHP
 *
 * @param {HTMLElement|JQuery|string} selector Selector o elemento de la barra
 * @returns {void}
 */
function pcsAdminSideBar(selector) {

	let menu = $(selector)
	menu = menu.find(".content")

	if (menu.length > 0) {

		let groups = menu.find('.group')

		if (groups.length > 0) {

			let titlesGroups = groups.find('.title-group').not('[href]')

			if (titlesGroups.length > 0) {

				titlesGroups.click(function (e) {

					e.preventDefault()

					let ancester = $(this).parent()
					let items = ancester.find('> .items')

					if (!ancester.offsetParent().hasClass('contrack')) {
						if (items.length > 0) {
							if (ancester.hasClass('active')) {
								ancester.removeClass('active')
								items.hide(500)
							} else {
								ancester.addClass('active')
								items.show(500)
							}
						}
					} else {
						const elementPress = $(e.target)
						if (
							elementPress.hasClass('tool-item') &&
							elementPress[0].nodeName === 'A'
						) {
							window.location.href = elementPress.attr('href')
						}
					}

					let ancesterOthers = titlesGroups.parent().not(ancester).not($(this).parents('.group'))
					let itemsOthers = ancesterOthers.find('.items')
					ancesterOthers.removeClass('active')
					itemsOthers.hide(500)
				})

			}

		}

		let toggle = $('.ui-pcs.sidebar-toggle')

		if (toggle.length > 0) {

			toggle.on('click', function (e) {

				if (menu.is(':visible')) {

					menu.fadeOut(500, function () {
						menu.attr('style', '')
						$(menu).removeClass('overlay')
					})

					$(this).removeClass('active')

				} else {

					menu.attr('style', '')
					$(menu).addClass('overlay')
					$(this).addClass('active')
				}
			})
		}
	}

	const barController = $('[bar-controller]')

	barController.on('click', function (evt) {
		evt.stopPropagation()
		evt.preventDefault()
		transformAside()
		const activeGroups = menu.find('.group.active, .group .active')
		activeGroups.removeClass('active')
		activeGroups.find('>.items').hide(500)
	})

	const transformAside = () => {
		rotateLogo()

		const mainAside = $('[main-aside]')

		if (mainAside.hasClass('contrack')) {
			mainAside.removeClass('contrack')
			showOnexpand()
			$('.ui-pcs.container-sidebar').removeClass('no-expanded')
		} else {
			hideOnTrack()
			$('.ui-pcs.container-sidebar').addClass('no-expanded')
			mainAside.addClass('contrack')
		}
	}

	const hideOnTrack = () => {
		const toHide = $('[only-expanded]')
		toHide.addClass('inSide')
		toHide.on('animationend', () => {
			toHide.addClass('remove')
			toHide.removeClass('inSide')
		})
	}

	const showOnexpand = () => {
		const toHide = $('[only-expanded]')
		toHide.removeClass('remove')
		toHide.addClass('outSide')
		toHide.on('animationend', () => {
			toHide.removeClass('outSide')
			toHide.removeClass('remove')
		})
	}

	const rotateLogo = () => {
		const containerImage = $('[menu-footer-images]')

		if (containerImage.hasClass('close')) {
			containerImage.removeClass('close')
		} else {
			containerImage.addClass('close')
		}
	}

	// Controlador de los tooltips

	const menuItems = menu.find('.title-group')

	menuItems.each((index, item) => {
		$(item).on('mouseover', (e) => {
			e = $(e.target)

			const currentMenuItem = searchTitleGroup(e)

			let tooltip = currentMenuItem.find('.tool-tip')

			const rect = currentMenuItem[0].getBoundingClientRect()

			tooltip[0].style.top = rect.top + 'px'
		})
	})

	const searchTitleGroup = (e) => {
		if (e.hasClass('title-group')) {
			return e
		} else {
			return searchTitleGroup(e.parent())
		}
	}
}

/**
 * Configura los menús de configuraciones
 *
 * @returns {void}
 */
function pcsAdminTopbars() {

	const userOptionsMenu = $('.ui-pcs.topbar-options.user-options')
	const adminOptionsMenu = $('.ui-pcs.topbar-options.admin-options')
	const notificationsOptionsMenu = $('.ui-pcs.topbar-options.notifications-options')
	const profileContainer = $('.profile-content')

	const userOptionsMenuCloseButton = userOptionsMenu.find('.close')
	const adminOptionsMenuCloseButton = adminOptionsMenu.find('.close')
	const notificationsOptionsMenuCloseButton = notificationsOptionsMenu.find('.close')

	const userOptionsToggles = $('.ui-pcs.topbar-toggle.user-options')
	const adminOptionsToggles = $('.ui-pcs.topbar-toggle.admin-options')
	const notificationsOptionsToggles = $('.ui-pcs.topbar-toggle.notifications-options')

	const hasUserOptions = userOptionsMenu.length > 0 && userOptionsToggles.length > 0
	const hasAdminOptions = adminOptionsMenu.length > 0 && adminOptionsToggles.length > 0
	const hasNotificationsOptions = notificationsOptionsMenu.length > 0 && notificationsOptionsToggles.length > 0

	const hasProfile = profileContainer.length > 0

	if (hasUserOptions) {
		userOptionsToggles.on('click', function (e) {
			e.preventDefault()
			if (!isOpen(userOptionsMenu)) {
				open(userOptionsMenu)
			} else {
				close(userOptionsMenu)
			}
		})

		userOptionsMenuCloseButton.on('click', function (e) {
			e.preventDefault()
			close(userOptionsMenu)
		})
	}

	if (hasNotificationsOptions) {
		notificationsOptionsToggles.on('click', function (e) {
			e.preventDefault()
			if (!isOpen(notificationsOptionsMenu)) {
				open(notificationsOptionsMenu)
			} else {
				close(notificationsOptionsMenu)
			}
		})

		notificationsOptionsMenuCloseButton.on('click', function (e) {
			e.preventDefault()
			close(notificationsOptionsMenu)
		})
	}

	if (hasAdminOptions) {
		adminOptionsToggles.on('click', function (e) {
			e.preventDefault()
			if (!isOpen(adminOptionsMenu)) {
				open(adminOptionsMenu)
			} else {
				close(adminOptionsMenu)
			}
		})

		adminOptionsMenuCloseButton.on('click', function (e) {
			e.preventDefault()
			close(adminOptionsMenu)
		})
	}

	userOptionsMenu.find('[edit-account]').on('click', () => {
		close(userOptionsMenu)
		profileContainer.addClass('activated')
		tabsController('account')
	})
	userOptionsMenu.find('[change-password]').on('click', () => {
		close(userOptionsMenu)
		profileContainer.addClass('activated')
		tabsController('password')
	})
	profileContainer.find('[close-profile]').on('click', closeProfile)

	if (
		hasUserOptions || hasAdminOptions || hasNotificationsOptions || hasProfile
	) {
		window.addEventListener('click', function (e) {

			if (hasUserOptions) {
				const isUserToggle = userOptionsToggles[0] == e.target || userOptionsToggles[0].contains(e.target)
				if (!userOptionsMenu[0].contains(e.target) && !isUserToggle) {
					close(userOptionsMenu)
				}
			}

			if (hasAdminOptions) {
				const isAdminToggle = adminOptionsToggles[0] == e.target || adminOptionsToggles[0].contains(e.target)
				if (!adminOptionsMenu[0].contains(e.target) && !isAdminToggle) {
					close(adminOptionsMenu)
				}
			}

			if (hasNotificationsOptions) {
				const isNotificationsToggle = notificationsOptionsToggles[0] == e.target || notificationsOptionsToggles[0].contains(e.target)
				if (!notificationsOptionsMenu[0].contains(e.target) && !isNotificationsToggle
				) {
					close(notificationsOptionsMenu)
				}
			}

			if (hasProfile) {
				if (!profileContainer[0].contains(e.target) && !userOptionsMenu[0].contains(e.target) && profileContainer.hasClass('activated')) {
					closeProfile()
				}
			}
		})
	}

	function open(menu) {
		menu.parent().removeClass('close')
		menu.addClass('active')
	}

	function close(menu) {
		menu.parent().addClass('close')
		if (isOpen(menu)) {
			menu.removeClass('active')
			if (!menu.hasClass('deactive')) {
				menu.addClass('deactive')
			}
		}
	}

	function isOpen(menu) {
		return menu.hasClass('active')
	}

	function closeProfile() {
		profileContainer.removeClass('activated')
		profileContainer.addClass('desactivated')
		profileContainer.on('animationend', () => {
			profileContainer.removeClass('desactivated')
		})
	}

	const tabsController = (strDefauld = '') => {
		const tabs = profileContainer.find('[data-tab]')
		const views = profileContainer.find('[data-view]')

		tabs.on('click', (e) => {
			var target = $(e.target)

			if (target[0].nodeName === 'I' || target[0].nodeName === 'SPAN') {
				target = target.parent()
			}

			const tab = target.data('tab')

			views.each((index, view) => {
				const $view = $(view)

				if ($(tabs[index]).data('tab') === tab) {
					$(tabs[index]).addClass('current')
				} else {
					$(tabs[index]).removeClass('current')
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

		const mainForm = $(".profile-content").find("form")

		mainForm.on('submit', (e) => {
			e.preventDefault()

			showGenericLoader(LOADER_NAME)

			const formData = new FormData(e.target)

			formData.set('is_profile', 'yes')

			postRequest('users/edit/', formData)
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
	}

	const loadNews = () => {

		const mainContainerSelector = '[news-toolbar-container]'
		const mainContainer = $(mainContainerSelector)
		const newsModal = $('[news-modal]')
		const url = $(mainContainerSelector).parent().data('url')

		if (mainContainer.length > 0 && typeof NewsAdapter !== 'undefined') {
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
						closeProfile()
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

	const imageModalProfile = () => {
		const actionModal = $("[action-image-profile]")
		const modal = $("[profile-image-modal]")

		actionModal.on('click', () => {
			closeProfile()

			const instantiateCropper = (selector, ow = 400, ar = 400 / 400) => {
				return new SimpleCropperAdapter(selector, {
					aspectRatio: ar,
					format: 'image/jpeg',
					quality: 0.8,
					fillColor: 'white',
					outputWidth: ow,
				})
			}

			const cropper = instantiateCropper(`[simple-cropper-profile]`)

			cropper.onCropped(() => {
				const LOADER_NAME = 'updatePhotoLoader'
				const formData = new FormData()
				const userId = modal.find('.content').attr('user-id')
				const url = modal.find('.content').attr('action-url')

				formData.set('user_id', userId)
				formData.set('image', cropper.getFile())

				showGenericLoader(LOADER_NAME)

				postRequest(url, formData)
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

			cropper.onCancel(() => {
				modal.modal('hide')
			})

			modal.modal('show')
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
 * @param {String} langGroup
 * @param {Boolean} repeat Repite la solicitud aunque haya sido hecho previamente
 */
function registerDynamicLocalizationMessages(langGroup, repeat = false) {
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
			url.searchParams.set('group', langGroup)

			if (!pcsphpGlobals.langMessagesFromServerURLRequested.includes(langGroup)) {
				pcsphpGlobals.langMessagesFromServerURLRequested.push(langGroup)
			} else {
				if (!repeat) {
					return null
				}
			}

			getRequest(url, '', {}, {
				async: false,
			}).done(function (response) {

				const defaultLangData = typeof response['default'] == 'object' ? response['default'] : {}
				const existentsMessages = []

				//Añadir idiomas regulares
				for (const langName in response) {

					if (langName !== 'default') {

						const langData = response[langName]

						//Si no existe en el objeto se crea
						if (typeof pcsphpGlobals.messages[langName] != 'object') {
							pcsphpGlobals.messages[langName] = {}
						}
						if (typeof pcsphpGlobals.messages[langName][langGroup] != 'object') {
							pcsphpGlobals.messages[langName][langGroup] = {}
						}

						//Añadir solo los que no estén presenten en JS
						for (const langMessage in langData) {
							const translateMessage = langData[langMessage]

							if (typeof pcsphpGlobals.messages[langName][langGroup][langMessage] == 'undefined') {
								pcsphpGlobals.messages[langName][langGroup][langMessage] = translateMessage
							} else {
								existentsMessages.push(`Ya hay una traducción para [ ${langMessage} ] en ${langGroup}:${langName}`)
							}

						}

					}

				}

				//Añadir valores por defecto en todos los lugares donde no haya
				for (const langName in response) {

					if (langName !== 'default') {

						//Iterar sobre default
						for (const langMessage in defaultLangData) {
							const defaultTranslation = defaultLangData[langMessage]
							if (typeof pcsphpGlobals.messages[langName][langGroup][langMessage] == 'undefined') {
								pcsphpGlobals.messages[langName][langGroup][langMessage] = defaultTranslation
							}
						}

					}

				}

				if (existentsMessages.length > 0) {
					console.info(existentsMessages)
				}

			}).done(function () {
				resolve()
			})

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

		registerDynamicLocalizationMessages(langGroup)
		translationsObject[langGroup][`${element.innerHTML}`] = element.innerHTML

	}

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

				const currentLangIsDefault = pcsphpGlobals.defaultLang == pcsphpGlobals.lang
				const ignoreLang = ignoreLangs.includes(pcsphpGlobals.lang)

				if (!currentLangIsDefault) {
					errors.push(`Hay elementos de pendientes de traducción: ${JSON.stringify(translationsObject, null, 4)}`)
				}

				const translationURL = new URL('core/api/translations', pcsphpGlobals.baseURL)
				const formData = new FormData()
				formData.set('text', JSON.stringify(translationsObject))
				formData.set('from', _i18n('lang', pcsphpGlobals.defaultLang))
				formData.set('to', _i18n('lang', pcsphpGlobals.lang))
				let translationPromise = Promise.resolve({
					success: true,
					message: '',
					result: {
						text: formData.get('text'),
						from: formData.get('from'),
						to: formData.get('to'),
						translation: translationsObject,
					},
					error: null,
					AI: {
						provider: '',
						modelOpenAI: '',
						modelMistral: '',
					},
				})

				if (!currentLangIsDefault && !ignoreLang) {

					translationPromise = new Promise(function (translationResolve) {
						const loaderName = 'translationsLoader'
						showGenericLoader(loaderName)
						postRequest(translationURL, formData, {
							'PCSPHP-Response-Expected-Language': pcsphpGlobals.lang,
						}).done(function (response) {
							translationResolve(response)
						}).always(function () {
							removeGenericLoader(loaderName)
						})
					})

				}

				if (!currentLangIsDefault) {

					translationPromise.then(function (response) {

						const success = response.success
						const message = response.message
						const result = response.result
						const error = response.error

						if (success) {

							const translations = result.translation

							//Guardar las traducciones en el grupo de idioma actual en el backend
							const saveTranslationsGroupPromises = []

							const loaderName = 'saveTranslationGroupLoader'
							showGenericLoader(loaderName)

							for (const langGroup in translations) {

								if ((Array.isArray(translations[langGroup]) && translations[langGroup].length == 0) || Object.keys(translations[langGroup]).length == 0) {
									continue
								}

								saveTranslationsGroupPromises.push(new Promise(function (resolveSaveGroup) {

									const saveTranslationGroupURL = new URL('core/api/translations/saveGroup', pcsphpGlobals.baseURL)
									const formData = new FormData()
									formData.set('text', JSON.stringify(translations[langGroup]))
									formData.set('to', pcsphpGlobals.lang)
									formData.set('saveGroup', langGroup)
									formData.set('database', 'yes')

									postRequest(saveTranslationGroupURL, formData, {
										'PCSPHP-Response-Expected-Language': pcsphpGlobals.lang,
									}).done(function (response) {

										const success = response.success
										const message = response.message
										const error = response.error

										if (success) {
											console.info(`Traducción guardada: ${langGroup}`)
										}

										resolveSaveGroup()

										if (error !== null) {
											console.error(error)
										}

									}).fail(function () {
										resolveSaveGroup()
									})

								}))
							}

							Promise.all(saveTranslationsGroupPromises).finally(function () {
								removeGenericLoader(loaderName)
								resolve(translations)
							})

						}

						if (error !== null) {
							console.error(error)
						}

					}).catch(function (error) {
						console.error(error)
					})

				}

				if (currentLangIsDefault) {
					if (ignoreLang) {
						resolve(null)
					}
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
}

window.dispatchEvent(new Event(pcsphpGlobals.events.configurationsLoad))
window.addEventListener('load', function () {
	window.dispatchEvent(new Event(pcsphpGlobals.events.configurationsAndWindowLoad))
	autoTranslateFromLangGroupHTML()
})
