//Fuera del `load`: los list.js de los módulos inician DataTables en su propio `load`, que va después.
contentTablesStackable()
dataTablesTooltips()
contentRowActionsMenus()

window.addEventListener('load', function (e) {
	timeOnPlatform()
	adminZoneSupportForm()
	panelSearch()
	panelHelpCenter()
	adminAppKeyNag()
	contentFormsInitial()
	contentModals()
	contentColorFields()
	contentPasswordFields()
	contentTabsAria()
	contentLiveReadings()
	contentSectionNav()
})

/**
 * La navegación por secciones (section-nav.php, `data-section-nav`): en escritorio, el `ui sticky` del tema dentro de su
 * rejilla; con la rejilla apilada, la columna se fija con CSS (position: sticky), porque el módulo no la sigue. Las anclas
 * desplazan bajo la barra superior (y bajo la propia navegación, apilada), la que se ve se marca al desplazar y el
 * desplegable la sigue. Los `ui accordion` con `data-section-accordion` se inician aquí, sin cerrar los demás al abrir uno:
 * así, abrir el elemento de un ancla no mueve lo que está encima. Si ninguno viene abierto (el primero puede estar oculto
 * para quien lee), se abre el primero que hay. Lo que se pulsa de cada título es su `[role="button"]`: aria-expanded y
 * Intro o Espacio, aquí.
 * @returns {void}
 */
function contentSectionNav() {
	const stickies = []
	if (typeof $ !== 'undefined' && typeof $.fn.accordion === 'function') {
		$('[data-section-accordion]').each(function () {
			const accordion = $(this)
			if (accordion.children('.title.active').length === 0) {
				accordion.children('.title').first().addClass('active').next('.content').addClass('active')
			}
			const syncExpanded = function () {
				accordion.children('.title').each(function () {
					$(this).find('> [role="button"]').attr('aria-expanded', String($(this).hasClass('active')))
				})
			}
			accordion.accordion({
				exclusive: false,
				//Al abrir o cerrar cambia el alto del contexto del sticky: se vuelve a medir.
				onChange: function () {
					syncExpanded()
					stickies.forEach((sticky) => $(sticky).sticky('refresh'))
				},
			})
			syncExpanded()
			accordion.on('keydown', '> .title > [role="button"]', function (event) {
				if (event.key === 'Enter' || event.key === ' ') {
					event.preventDefault()
					$(this).trigger('click')
				}
			})
		})
	}
	document.querySelectorAll('[data-section-nav]').forEach(function (sticky) {
		const layout = sticky.closest('.ui.grid')
		const links = Array.from(sticky.querySelectorAll('[data-section-nav-anchor]'))
		const targets = links.map((link) => document.getElementById(link.getAttribute('data-section-nav-anchor'))).filter((target) => target !== null)
		const jump = sticky.querySelector('[data-section-nav-jump]')
		const desktop = window.matchMedia('(min-width: 992px)')
		const barHeight = function () {
			const bar = document.querySelector('header.bar')
			return bar !== null ? bar.getBoundingClientRect().height : 0
		}
		//Lo que tapa el borde de arriba: la barra superior del panel y, por debajo de escritorio, la propia navegación fija.
		const topOffset = function () {
			let offset = barHeight()
			if (!desktop.matches && sticky.parentElement !== null) {
				offset += sticky.parentElement.getBoundingClientRect().height
			}
			return offset + 16
		}
		let stickyOn = false
		const layoutChanged = function () {
			document.documentElement.style.setProperty('--section-nav-bar-height', barHeight() + 'px')
			//Al llegar por su ancla (también con el hash de la dirección), la sección no queda bajo lo fijo.
			targets.forEach((target) => target.style.setProperty('scroll-margin-top', topOffset() + 'px'))
			if (layout === null || typeof $ === 'undefined' || typeof $.fn.sticky !== 'function') {
				return
			}
			if (desktop.matches && !stickyOn) {
				//observeChanges: el contexto crece después (las vistas previas del banco de correos, un plegable abierto).
				$(sticky).sticky({ context: layout, offset: barHeight() + 16, observeChanges: true })
				stickyOn = true
				stickies.push(sticky)
			} else if (!desktop.matches && stickyOn) {
				$(sticky).sticky('destroy')
				sticky.removeAttribute('style')
				stickyOn = false
				stickies.splice(stickies.indexOf(sticky), 1)
			}
		}
		layoutChanged()
		window.addEventListener('resize', layoutChanged)

		//Un ancla dentro de un plegable cerrado lo abre antes de desplazar. Devuelve lo que dura la animación del tema: mientras
		//el contenido crece, la página puede no tener aún alto para dejar la sección arriba, y hay que volver a desplazar.
		const openFolded = function (target) {
			const title = target.closest('[data-section-accordion] > .title')
			if (title === null || title.classList.contains('active') || typeof $ === 'undefined') {
				return 0
			}
			const accordion = $(title.parentElement)
			accordion.accordion('open', title)
			const duration = Number(accordion.accordion('setting', 'duration'))
			return Number.isFinite(duration) ? duration : 500
		}
		const scrollToTarget = function (target, smooth) {
			const top = target.getBoundingClientRect().top + window.scrollY - topOffset()
			window.scrollTo({ top: top, behavior: smooth ? 'smooth' : 'auto' })
		}
		const goTo = function (id, smooth) {
			const target = document.getElementById(id)
			if (target === null) {
				return
			}
			const opening = openFolded(target)
			scrollToTarget(target, smooth)
			if (opening > 0) {
				setTimeout(() => scrollToTarget(target, smooth), opening + 50)
			}
			history.replaceState(null, '', '#' + id)
		}
		links.forEach(function (link) {
			link.addEventListener('click', function (event) {
				event.preventDefault()
				goTo(link.getAttribute('data-section-nav-anchor'), true)
			})
		})
		if (jump !== null) {
			//Con las flechas sobre el desplegable cerrado, algunos navegadores disparan `change` en cada paso: se espera a Intro
			//o a que pierda el foco, para no sacar a nadie de la página a medio elegir.
			let stepping = false
			const follow = function () {
				stepping = false
				if (jump.value.startsWith('#')) {
					goTo(jump.value.slice(1), true)
				} else if (jump.value !== '') {
					window.location.href = jump.value
				}
			}
			jump.addEventListener('keydown', function (event) {
				if (['ArrowUp', 'ArrowDown', 'PageUp', 'PageDown', 'Home', 'End'].includes(event.key)) {
					stepping = true
				} else if (event.key === 'Enter' && stepping) {
					follow()
				}
			})
			jump.addEventListener('blur', function () {
				if (stepping) {
					follow()
				}
			})
			jump.addEventListener('change', function () {
				if (!stepping) {
					follow()
				}
			})
		}
		if (targets.length === 0) {
			return
		}

		//La sección que se está viendo: la última cuyo borde de arriba ya pasó bajo lo fijo; al tocar fondo, la última,
		//que quizá nunca llegue arriba.
		const markCurrent = function () {
			const limit = topOffset() + 8
			let current = targets[0]
			targets.forEach(function (target) {
				if (target.getBoundingClientRect().top <= limit) {
					current = target
				}
			})
			//Solo si la página desplaza: en una que cabe entera, al cargar se marcaría la última.
			const scrollable = document.documentElement.scrollHeight > window.innerHeight + 2
			if (scrollable && window.scrollY > 0 && window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 2) {
				current = targets[targets.length - 1]
			}
			links.forEach(function (link) {
				const active = link.getAttribute('data-section-nav-anchor') === current.id
				link.classList.toggle('active', active)
				if (active) {
					link.setAttribute('aria-current', 'true')
				} else {
					link.removeAttribute('aria-current')
				}
			})
			if (jump !== null && jump.querySelector('option[value="#' + CSS.escape(current.id) + '"]') !== null && jump.value !== '#' + current.id) {
				jump.value = '#' + current.id
			}
		}
		let pending = false
		window.addEventListener('scroll', function () {
			if (!pending) {
				pending = true
				window.requestAnimationFrame(function () {
					pending = false
					markCurrent()
				})
			}
		}, { passive: true })
		markCurrent()
		if (location.hash.length > 1 && targets.some((target) => target.id === location.hash.slice(1))) {
			goTo(location.hash.slice(1), false)
		}
	})
}

/**
 * @description El campo de color (color-field.php): el selector del tema, sin botones, para que el cambio se vea en vivo.
 * @returns {void}
 */
function contentColorFields() {
	pcsColorPickerTexts()
	//Los de configColorPickers() ya están iniciados, con su formato y su transparencia.
	$('.ui.colorpicker.input > input:not([color-picker-js])').colorpicker({ buttons: false })
}

/**
 * @description El campo de contraseña (password-field.php): el ojo alterna el tipo del input, su icono y su texto. Delegado
 * en el documento: vale también para un campo pintado después de cargar la página.
 * @returns {void}
 */
function contentPasswordFields() {
	$(document).on('click', '[data-password-toggle]', function () {
		const button = $(this)
		const input = button.siblings('input')
		const show = input.attr('type') === 'password'
		input.attr('type', show ? 'text' : 'password')
		button.find('.icon').toggleClass('slash', show)
		button.attr('aria-pressed', show ? 'true' : 'false')
		button.attr('aria-label', button.attr(show ? 'data-hide-text' : 'data-show-text'))
	})
}

/**
 * @description La lectura en vivo de una cifra: `input[data-live-reading="days|seconds"]` escribe su equivalente legible en
 * `[data-live-reading-for="<id del input>"]`; con 0, el texto de su `data-live-reading-zero`.
 * @returns {void}
 */
function contentLiveReadings() {
	const unit = (count, one, many) => `${count} ${_i18n('liveReadings', count === 1 ? one : many)}`
	const readings = {
		days: (days) => {
			if (days % 365 === 0) {
				return unit(days / 365, 'año', 'años')
			}
			if (days >= 28 && days <= 31) {
				return unit(1, 'mes', 'meses')
			}
			if (days % 30 === 0) {
				return unit(days / 30, 'mes', 'meses')
			}
			return days % 7 === 0 ? unit(days / 7, 'semana', 'semanas') : unit(days, 'día', 'días')
		},
		seconds: (seconds) => {
			const hours = Math.floor(seconds / 3600)
			const minutes = Math.floor((seconds % 3600) / 60)
			const parts = []
			if (hours > 0) {
				parts.push(unit(hours, 'hora', 'horas'))
			}
			if (minutes > 0) {
				parts.push(unit(minutes, 'minuto', 'minutos'))
			}
			return parts.length > 0 ? parts.join(` ${_i18n('liveReadings', 'y')} `) : unit(seconds, 'segundo', 'segundos')
		},
	}
	const paint = (input) => {
		const target = document.querySelector(`[data-live-reading-for="${input.id}"]`)
		const reading = readings[input.getAttribute('data-live-reading')]
		const value = parseInt(input.value, 10)
		if (target !== null && typeof reading == 'function') {
			target.textContent = value > 0 ? reading(value) : (input.getAttribute('data-live-reading-zero') || '')
		}
	}
	document.querySelectorAll('input[data-live-reading]').forEach(paint)
	$(document).on('input', 'input[data-live-reading]', function () {
		paint(this)
	})
}

/**
 * @description El patrón ARIA de las pestañas (design.md §3.13) sobre las `.ui.menu` con `.item[data-tab]`, sin tocar a
 * quien las inicia con .tab(): roles y relaciones entre ítem y panel, `aria-selected` y `tabindex` al cambiar la clase
 * `active`, y el teclado. Lo que no lleva `data-tab` dentro del menú no se toca.
 * @returns {void}
 */
function contentTabsAria() {
	let tabIndex = 0
	$('.ui.menu').each(function () {
		const items = $(this).children('.item[data-tab]')
		if (items.length === 0) {
			return
		}
		this.setAttribute('role', 'tablist')
		items.each(function () {
			tabIndex++
			this.id = this.id || `content-tab-${tabIndex}`
			this.setAttribute('role', 'tab')
			//Se busca en todo el documento: los paneles no siempre son hermanos del menú.
			const panel = document.querySelector(`.ui.tab[data-tab="${CSS.escape(this.getAttribute('data-tab'))}"]`)
			if (panel !== null) {
				panel.id = panel.id || `content-tabpanel-${tabIndex}`
				panel.setAttribute('role', 'tabpanel')
				panel.setAttribute('aria-labelledby', this.id)
				this.setAttribute('aria-controls', panel.id)
			}
		})
		const sync = () => items.each(function () {
			const active = this.classList.contains('active')
			this.setAttribute('aria-selected', active ? 'true' : 'false')
			this.tabIndex = active ? 0 : -1
		})
		sync()
		new MutationObserver(sync).observe(this, { subtree: true, attributes: true, attributeFilter: ['class'] })
		$(this).on('keydown', '> .item[data-tab]', function (event) {
			//Solo las que se ven: una pestaña puede estar oculta por configuración («Anexos», en publicaciones).
			const visible = items.filter(':visible')
			const index = visible.index(this)
			const targets = { ArrowRight: index + 1, ArrowLeft: index - 1, Home: 0, End: visible.length - 1 }
			if (event.key === 'Enter' || event.key === ' ') {
				event.preventDefault()
				this.click()
			} else if (typeof targets[event.key] == 'number') {
				event.preventDefault()
				const target = visible.get((targets[event.key] + visible.length) % visible.length)
				target.focus()
				target.click()
			}
		})
	})
}

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
 * @description Los listados de contenido (`table.content-table`). Una tabla es una tabla: la integración de DataTables con
 * Semantic marca TODA tabla `unstackable` (dataTables.semanticui.min.js), y en el móvil se desplaza. Con la clase
 * `content-table-cards`, opcional, cada fila es una tarjeta: aquí se le quita `unstackable`, al iniciarse, y cada dibujado
 * pone en cada celda el `data-label` de su cabecera, que el CSS del móvil enseña delante del valor (_content-screens.scss).
 * La columna de `RowActions`, cabecera y celdas, va a la derecha. Las demás tablas no se tocan.
 * @returns {void}
 */
function contentTablesStackable() {
	$(document).on('init.dt draw.dt', function (event, settings) {
		const table = $(new DataTable.Api(settings).table().node())
		if (!table.is('table.content-table')) {
			return
		}
		//La paginación del contenedor de DataTables, para que envuelva en el móvil (_content-screens.scss).
		table.closest('.dt-container').addClass('content-table-container')
		const actions = table.find('> tbody > tr > td:last-child:has(.row-actions)')
		if (actions.length > 0) {
			actions.addClass('right aligned')
			table.find('> thead > tr > th:last-child').addClass('right aligned')
		}
		if (!table.hasClass('content-table-cards')) {
			return
		}
		table.removeClass('unstackable')
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
 * @description El menú «⋯» de las acciones de fila (RowActions, `[row-actions-menu]`). Lo inicia el panel, el que viene en
 * la página y el que pinta DataTables en cada dibujado, una vez cada uno: un módulo no lo inicia. Al abrirse sale con
 * `position: fixed` a la altura de su botón (por qué, en admin_app_base.scss, junto a `.container-standard-table`); hacia
 * arriba si abajo no cabe, y nunca fuera de la ventana por la izquierda. Se coloca al ponerse `active`, también si otro lo
 * inició con sus ajustes, y se recoloca al desplazar cualquier cosa o redimensionar; se cierra si su botón deja de verse.
 * Dentro de un antepasado con `transform` o `will-change: transform` (una `ui modal`), `fixed` no es relativo a la
 * ventana y el menú saldría desplazado: hoy ningún «⋯» va en una ventana.
 * @returns {void}
 */
function contentRowActionsMenus() {
	const selector = '[row-actions-menu]'
	const edge = 8
	const init = (root) => {
		$(root).find(selector).each(function () {
			if ($(this).data('moduleDropdown') === undefined) {
				//Con `auto` el tema le pone al menú la clase `left`, con `!important`: la dirección la decide place(). Y en
				//`onShow`, antes de la animación: el tema elige `slide up` o `slide down` leyendo `upward` justo después.
				$(this).dropdown({
					action: 'hide',
					direction: 'downward',
					onShow: function () {
						place(this)
					},
				})
			}
		})
	}
	//Con `!important`: las clases del tema (`upward`, `left`) colocan el menú con `!important` en algunos casos. El
	//`min-width: 100%` del tema, con `fixed`, sería el ancho de la ventana.
	const fix = (style, values) => Object.entries(values).forEach(([name, value]) => style.setProperty(name, value, 'important'))
	const place = (dropdown) => {
		const menu = dropdown.querySelector(':scope > .menu')
		if (menu === null) {
			return
		}
		const style = menu.style
		//`fixed` ANTES de medir: medido aún `absolute`, la caja saca su barra vertical, la columna de acciones se corre y
		//el menú queda lejos del botón cuando la barra se va.
		fix(style, { position: 'fixed', top: '0px', left: '0px', right: 'auto', bottom: 'auto', margin: '0', 'min-width': 'max-content' })
		let width = menu.offsetWidth
		let height = menu.offsetHeight
		if (height === 0) {
			const previous = style.cssText
			style.setProperty('display', 'block', 'important')
			style.setProperty('visibility', 'hidden', 'important')
			width = menu.offsetWidth
			height = menu.offsetHeight
			style.cssText = previous
		}
		//El hueco de 1em es el del menú `pointing` del tema, que deja sitio a su flecha.
		const gap = parseFloat(getComputedStyle(menu).fontSize) || 14
		const rect = dropdown.getBoundingClientRect()
		const viewport = document.documentElement
		const nags = parseFloat(getComputedStyle(document.body).getPropertyValue('--pcs-bottom-nags')) || 0
		const below = viewport.clientHeight - nags - edge - rect.bottom - gap
		const above = rect.top - gap - edge
		const upward = height > below && above > below
		const top = upward ? Math.max(edge, rect.top - gap - height) : rect.bottom + gap
		const left = Math.max(edge, Math.min(rect.right - width, viewport.clientWidth - width - edge))
		dropdown.classList.toggle('upward', upward)
		fix(style, { top: `${top}px`, left: `${left}px` })
	}
	//Con `fixed` nada lo recorta: si su botón queda tapado o fuera (desplazado bajo la caja o bajo la barra), se cierra.
	const placeOpen = () => document.querySelectorAll(`${selector}.active`).forEach((dropdown) => {
		const rect = dropdown.getBoundingClientRect()
		const hit = document.elementFromPoint(rect.left + rect.width / 2, rect.top + rect.height / 2)
		if (hit === null || !dropdown.contains(hit)) {
			$(dropdown).dropdown('hide')
			return
		}
		place(dropdown)
	})

	new MutationObserver((records) => records.forEach((record) => {
		const target = record.target
		if (!target.hasAttribute('row-actions-menu') || !target.classList.contains('active')) {
			return
		}
		if (!String(record.oldValue || '').split(/\s+/).includes('active')) {
			place(target)
		}
	})).observe(document.documentElement, { subtree: true, attributes: true, attributeFilter: ['class'], attributeOldValue: true })
	document.addEventListener('scroll', placeOpen, { capture: true, passive: true })
	window.addEventListener('resize', placeOpen)
	$(document).on('draw.dt', function (event, settings) {
		init(new DataTable.Api(settings).table().container())
	})
	$(() => init(document))
}

/**
 * @description Los tooltips dentro de una tabla de DataTables, con el módulo popup (design.md §3.10): el de CSS es un
 * `::after` y el desplazamiento horizontal de la tabla lo recorta; el popup se pinta en `body`. Se quitan los atributos del
 * de CSS para que no salgan dos. El `aria-label` se queda.
 * @returns {void}
 */
function dataTablesTooltips() {
	$(document).on('draw.dt', function (event, settings) {
		$(new DataTable.Api(settings).table().container()).find('[data-tooltip]').each(function () {
			const element = $(this)
			const content = element.attr('data-tooltip')
			const position = element.attr('data-position') || 'top center'
			element.removeAttr('data-tooltip data-position data-inverted')
			element.popup({ content: content, variation: 'inverted tiny', position: position })
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

/**
 * @description El buscador de la barra (topbar-search.php): busca en local, en lo que el servidor ya filtró por permisos. El
 * atajo Ctrl K es del shell del tema.
 * @returns {void}
 */
function panelSearch() {
	const search = $('[panel-search]')
	if (search.length < 1) {
		return
	}
	search.search({
		type: 'category',
		source: JSON.parse(search.attr('data-source')),
		searchFields: ['title', 'category'],
		fullTextSearch: 'exact',
		ignoreDiacritics: true,
		minCharacters: 1,
		maxResults: 12,
		selectFirstResult: true,
		error: {
			noResultsHeader: search.attr('data-no-results-header'),
			noResults: search.attr('data-no-results'),
		},
	})
}

/**
 * @description El botón de ayuda de la barra: un popup con el centro de ayuda (topbar.php).
 * @returns {void}
 */
function panelHelpCenter() {
	const toggle = $('[help-center-toggle]')
	toggle.popup({ popup: '[help-center-menu]', on: 'click', position: 'bottom right', lastResort: 'bottom right', movePopup: false })
	$('[help-center-menu] [support-button-js]').on('click', () => toggle.popup('hide'))
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
