/**
 * La lista de notificaciones (ADR 0073) de la campana y de la vista central. Sin textos: llegan en data-* desde
 * notifications-list.php. La de la campana carga al abrirse; las demás, al cargar la página.
 */
class NotificationsList {

	/**
	 * Los colores del tema para cada nivel.
	 */
	static LEVEL_COLORS = { info: 'blue', success: 'green', warning: 'orange', error: 'red' }

	/**
	 * @param {HTMLElement} root El elemento con [notifications-list]
	 */
	constructor(root) {
		this.root = root
		this.texts = JSON.parse(root.dataset.texts || '{}')
		this.perPage = parseInt(root.dataset.perPage || '10', 10)
		this.filter = 'all'
		this.page = 1
		this.loaded = false
		this.request = 0
		this.items = root.querySelector('[notifications-items]')
		this.empty = root.querySelector('[notifications-empty]')
		this.more = root.querySelector('[notifications-more]')
		this.panel = root.querySelector('[role="tabpanel"]')
		this.tabs = Array.from(root.querySelectorAll('[notifications-filter]'))
		this.markAllButtons = Array.from(document.querySelectorAll(`[notifications-mark-all="${root.id}"]`))
		this.tabs.forEach((tab) => {
			tab.addEventListener('click', (event) => {
				event.preventDefault()
				this.select(tab.getAttribute('notifications-filter'))
			})
			tab.addEventListener('keydown', (event) => this.moveSelection(event, tab))
		})
		if (this.more !== null) {
			this.more.addEventListener('click', () => this.load(false))
		}
		this.markAllButtons.forEach((button) => button.addEventListener('click', (event) => {
			event.preventDefault()
			this.markAll()
		}))
	}

	/**
	 * @param {string} filter
	 * @returns {void}
	 */
	select(filter) {
		this.filter = filter
		this.tabs.forEach((tab) => {
			const active = tab.getAttribute('notifications-filter') === filter
			tab.classList.toggle('active', active)
			tab.setAttribute('aria-selected', active ? 'true' : 'false')
			tab.setAttribute('tabindex', active ? '0' : '-1')
			if (active && this.panel !== null) {
				this.panel.setAttribute('aria-labelledby', tab.id)
			}
		})
		this.load(true)
	}

	/**
	 * El teclado del patrón filtros-chips: las flechas, Inicio y Fin mueven el foco y eligen el chip.
	 *
	 * @param {KeyboardEvent} event
	 * @param {HTMLElement} tab
	 * @returns {void}
	 */
	moveSelection(event, tab) {
		const current = this.tabs.indexOf(tab)
		const next = { ArrowRight: current + 1, ArrowDown: current + 1, ArrowLeft: current - 1, ArrowUp: current - 1, Home: 0, End: this.tabs.length - 1 }[event.key]
		if (next === undefined) {
			return
		}
		event.preventDefault()
		const index = (next + this.tabs.length) % this.tabs.length
		this.tabs[index].focus()
		this.select(this.tabs[index].getAttribute('notifications-filter'))
	}

	/**
	 * @param {boolean} reset true: la primera página del filtro; false: la siguiente («Cargar más»)
	 * @returns {void}
	 */
	load(reset = true) {
		this.page = reset ? 1 : this.page + 1
		//Una respuesta que llega tarde (otra pestaña ya pedida) no pisa la lista.
		const request = ++this.request
		this.root.classList.add('loading')
		//getRequest() solo manda texto, un formulario o jQuery: un objeto plano se perdería.
		getRequest(this.root.dataset.listUrl, $.param({ filter: this.filter, page: this.page, perPage: this.perPage }))
			.done((response) => {
				if (request !== this.request) {
					return
				}
				if (reset) {
					this.items.replaceChildren()
				}
				(response.items || []).forEach((item) => this.items.appendChild(this.draw(item)))
				this.loaded = true
				this.refresh(response.counts || {}, response.hasMore === true)
			})
			.fail(() => errorMessage(this.texts.error || ''))
			.always(() => this.root.classList.remove('loading'))
	}

	/**
	 * Los contadores de las pestañas, la insignia de la barra, el estado vacío, «Cargar más» y «Marcar todas».
	 *
	 * @param {Object<string,number>} counts
	 * @param {boolean} hasMore
	 * @returns {void}
	 */
	refresh(counts, hasMore) {
		this.tabs.forEach((tab) => {
			const number = tab.querySelector('[notifications-count]')
			const value = counts[tab.getAttribute('notifications-filter')]
			if (number !== null && typeof value === 'number') {
				number.textContent = String(value)
			}
		})
		const empty = this.items.children.length === 0
		this.items.hidden = empty
		if (this.empty !== null) {
			this.empty.hidden = !empty
		}
		if (this.more !== null) {
			this.more.hidden = !hasMore
		}
		if (typeof counts.unread === 'number') {
			NotificationsList.badge(counts.unread)
			this.markAllButtons.forEach((button) => { button.hidden = counts.unread === 0 })
		}
	}

	/**
	 * Una notificación, con la fila del patrón popup-notificaciones, pintada con textContent: el título y el texto vienen
	 * de la base. En la campana el texto va en una línea; en la vista, entero.
	 *
	 * @param {Object} item
	 * @returns {HTMLElement}
	 */
	draw(item) {
		const element = document.createElement('div')
		element.className = item.read ? 'item' : 'unread item'
		element.setAttribute('role', 'listitem')
		element.tabIndex = 0
		const row = document.createElement('div')
		row.className = 'ui top aligned nowrap relaxed flex'
		const icon = document.createElement('i')
		icon.className = `${NotificationsList.LEVEL_COLORS[item.level] || 'blue'} bordered ${item.icon} icon`
		icon.setAttribute('aria-hidden', 'true')
		const content = document.createElement('div')
		content.className = 'fluid content'
		const header = document.createElement('div')
		header.className = 'header'
		header.textContent = item.title
		content.appendChild(header)
		if (item.body) {
			const description = document.createElement('div')
			description.className = this.root.dataset.loadOnOpen === 'yes' ? 'description ui truncated text' : 'description'
			description.textContent = item.body
			content.appendChild(description)
		}
		const small = document.createElement('small')
		small.className = 'ui muted text'
		const time = document.createElement('time')
		time.dateTime = item.createdAt
		time.textContent = NotificationsList.relative(item.createdAt)
		time.title = new Date(item.createdAt).toLocaleString(document.documentElement.lang || undefined)
		small.appendChild(time)
		content.appendChild(small)
		const dot = document.createElement('i')
		dot.className = 'tiny primary circle icon'
		dot.setAttribute('role', 'img')
		dot.setAttribute('aria-label', this.texts.unread || '')
		dot.setAttribute('notifications-unread-dot', '')
		dot.hidden = item.read
		row.append(icon, content, dot)
		element.appendChild(row)
		const open = (event) => {
			if (event.type === 'keydown' && event.key !== 'Enter') {
				return
			}
			event.preventDefault()
			this.open(item, element)
		}
		element.addEventListener('click', open)
		element.addEventListener('keydown', open)
		return element
	}

	/**
	 * La marca leída y, si lleva a algún sitio, navega; una noticia abre su ventana.
	 *
	 * @param {Object} item
	 * @param {HTMLElement} element
	 * @returns {void}
	 */
	open(item, element) {
		const marked = item.read ? $.Deferred().resolve({}).promise() : postRequest(this.root.dataset.markUrl, { source: item.source, id: item.id })
		marked.done((response) => {
			if (!item.read) {
				item.read = true
				element.classList.remove('unread')
				const dot = element.querySelector('[notifications-unread-dot]')
				if (dot !== null) {
					dot.hidden = true
				}
				if (response && response.counts) {
					this.refresh(response.counts, this.more !== null && !this.more.hidden)
				}
			}
			if (item.news) {
				NotificationsList.showNews(item.news)
			} else if (item.url) {
				window.location.href = item.url
			}
		})
	}

	/**
	 * @returns {void}
	 */
	markAll() {
		postRequest(this.root.dataset.markAllUrl, {}).done(() => this.load(true))
	}

	/**
	 * La insignia de la campana y su etiqueta: oculta con 0; «99+» desde 100, como Notifications::badgeText(). La
	 * etiqueta lleva la cifra real.
	 *
	 * @param {number} unread
	 * @returns {void}
	 */
	static badge(unread) {
		document.querySelectorAll('[notifications-badge]').forEach((badge) => {
			badge.textContent = unread > 99 ? '99+' : String(unread)
			badge.hidden = unread === 0
		})
		document.querySelectorAll('[notifications-toggle]').forEach((toggle) => {
			const template = toggle.dataset.labelTemplate || ''
			if (template !== '') {
				toggle.setAttribute('aria-label', template.replace('${n}', String(unread)))
			}
		})
	}

	/**
	 * La ventana de noticia de la barra (topbar.php, [news-modal]).
	 *
	 * @param {{title:string,content:string,color:string}} news
	 * @returns {void}
	 */
	static showNews(news) {
		const modal = $('[news-modal]')
		if (modal.length === 0) {
			return
		}
		modal.find('.header').text(news.title).css('color', news.color)
		modal.find('.content').html(news.content)
		modal.modal('show')
	}

	/**
	 * «hace 5 minutos», en el idioma de la página.
	 *
	 * @param {string} iso
	 * @returns {string}
	 */
	static relative(iso) {
		const seconds = Math.round((new Date(iso).getTime() - Date.now()) / 1000)
		const format = new Intl.RelativeTimeFormat(document.documentElement.lang || undefined, { numeric: 'auto' })
		const steps = [['year', 31536000], ['month', 2592000], ['week', 604800], ['day', 86400], ['hour', 3600], ['minute', 60]]
		for (const [unit, size] of steps) {
			if (Math.abs(seconds) >= size) {
				return format.format(Math.round(seconds / size), unit)
			}
		}
		return format.format(seconds, 'second')
	}

	/**
	 * Monta las listas de la página. La de la campana se carga al abrirla; la de la vista, ya.
	 *
	 * @returns {Object<string,NotificationsList>}
	 */
	static mountAll() {
		const lists = {}
		document.querySelectorAll('[notifications-list]').forEach((root) => {
			const list = new NotificationsList(root)
			lists[root.id] = list
			if (root.dataset.loadOnOpen !== 'yes') {
				list.load(true)
			}
		})
		return lists
	}
}

window.addEventListener('load', () => {
	window.pcsphpNotificationsLists = NotificationsList.mountAll()
	const toggle = $('[notifications-toggle]')
	const popup = $('[notifications-popup]')
	if (toggle.length > 0 && popup.length > 0) {
		toggle.popup({
			popup: popup,
			on: 'click',
			position: 'bottom right',
			lastResort: 'bottom right',
			movePopup: false,
			onShow: () => {
				toggle.attr('aria-expanded', 'true')
				//En la campana, el popup es la propia lista (notifications-list.php).
				const list = window.pcsphpNotificationsLists[popup.attr('id')]
				if (list !== undefined && !list.loaded) {
					list.load(true)
				}
			},
			onHide: () => {
				toggle.attr('aria-expanded', 'false')
			},
		})
	}
})
