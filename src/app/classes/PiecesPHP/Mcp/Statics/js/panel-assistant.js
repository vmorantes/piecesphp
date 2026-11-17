/**
 * La ventana del asistente (ADR 0075): textos en data-* desde topbar.php; la respuesta, SIEMPRE como texto, y como
 * acciones solo los enlaces del mismo origen.
 */
class PanelAssistant {

	/**
	 * @param {HTMLElement} root El contenedor .ui.chat con [panel-assistant]
	 */
	constructor(root) {
		this.root = root
		this.url = root.dataset.url || ''
		this.limitMessage = root.dataset.limitMessage || ''
		this.limitShown = false
		this.texts = PanelAssistant.parse(root.dataset.text, {})
		const suggestions = PanelAssistant.parse(root.dataset.suggestions, [])
		const networkError = typeof this.texts.networkError === 'string' ? this.texts.networkError : ''
		delete this.texts.networkError
		this.networkError = networkError
		//La conversación, en el navegador (H329): por usuario, y se olvida tras forgetAfterMinutes sin uso.
		this.storageKey = PanelAssistant.STORAGE_PREFIX + String(root.dataset.userId || '0')
		const minutes = parseInt(String(root.dataset.forgetAfterMinutes || ''), 10)
		this.forgetAfterMs = (Number.isFinite(minutes) && minutes > 0 ? minutes : 60) * 60000
		this.$chat = $(root).chat({
			position: root.dataset.position || 'right',
			suggestions: Array.isArray(suggestions) ? suggestions : [],
			text: this.texts,
			onSubmit: (text) => this.ask(text),
			onNew: () => this.forget(),
			onOpen: () => this.showLimit(),
			onClose: () => this.restoreFocus(),
		})
		this.restore()
	}

	/**
	 * El prefijo de las claves de localStorage; salir de la sesión borra todas (PiecesPHPSystemUserHelper).
	 */
	static STORAGE_PREFIX = 'pcsphp-assistant-'

	/**
	 * Cuántas intervenciones se guardan como mucho; al pasar, se quitan las más viejas por pares.
	 */
	static MAX_TURNS = 20

	/**
	 * Los turnos, tal como los da «get history» (role, text, meta con la firma), con el tope por pares.
	 *
	 * @param {Array<{role: string, text: string, meta: *}>} turns
	 * @returns {Array<{role: string, text: string, meta: *}>}
	 */
	static capped(turns) {
		const kept = turns.slice()
		while (kept.length > PanelAssistant.MAX_TURNS) {
			kept.splice(0, 2)
		}
		return kept
	}

	/**
	 * Guarda la conversación y la hora de la última actividad. Sin almacenamiento, no hace nada.
	 *
	 * @returns {void}
	 */
	save() {
		try {
			const turns = (this.$chat.chat('get history') || []).map((turn) => ({ role: turn.role, text: turn.text, meta: turn.meta === undefined ? null : turn.meta }))
			localStorage.setItem(this.storageKey, JSON.stringify({ at: Date.now(), turns: PanelAssistant.capped(turns) }))
		} catch (e) {
			//Navegador privado o almacenamiento bloqueado: el chat sigue, sin guardar.
		}
	}

	/**
	 * Borra la conversación guardada («Nueva conversación»).
	 *
	 * @returns {void}
	 */
	forget() {
		try {
			localStorage.removeItem(this.storageKey)
		} catch (e) {
			//Sin almacenamiento no hay nada que borrar.
		}
	}

	/**
	 * Repinta la conversación guardada, sin pedir nada al servidor; si caducó o no se puede leer, la borra.
	 *
	 * @returns {void}
	 */
	restore() {
		let saved = null
		try {
			saved = PanelAssistant.parse(localStorage.getItem(this.storageKey) || '', null)
		} catch (e) {
			return
		}
		if (saved === null) {
			return
		}
		const valid = typeof saved === 'object' && Number.isFinite(saved.at) && Array.isArray(saved.turns)
		if (!valid || Date.now() - saved.at > this.forgetAfterMs) {
			this.forget()
			return
		}
		saved.turns.forEach((turn) => {
			if (turn === null || typeof turn !== 'object' || typeof turn.text !== 'string' || !['user', 'assistant'].includes(turn.role)) {
				return
			}
			this.$chat.chat(turn.role === 'user' ? 'add user' : 'add assistant', turn.text, turn.meta === null ? undefined : turn.meta)
		})
	}

	/**
	 * @param {string|undefined} json
	 * @param {*} fallback
	 * @returns {*}
	 */
	static parse(json, fallback) {
		try {
			return json ? JSON.parse(json) : fallback
		} catch (e) {
			return fallback
		}
	}

	/**
	 * Solo los enlaces que llevan al mismo origen que la página: el modelo no puede ofrecer un botón hacia fuera.
	 *
	 * @param {*} links
	 * @param {string} base La dirección de la página (location.href)
	 * @returns {{title: string, url: string}[]}
	 */
	static sameOriginLinks(links, base) {
		if (!Array.isArray(links)) {
			return []
		}
		const origin = new URL(base).origin
		return links.filter((link) => {
			if (link === null || typeof link !== 'object' || typeof link.title !== 'string' || typeof link.url !== 'string') {
				return false
			}
			try {
				return new URL(link.url, base).origin === origin
			} catch (e) {
				return false
			}
		}).map((link) => ({ title: link.title, url: link.url }))
	}

	/**
	 * @returns {void}
	 */
	showLimit() {
		if (this.limitMessage !== '' && !this.limitShown) {
			this.limitShown = true
			this.$chat.chat('add notice', 'unavailable', this.limitMessage)
		}
	}

	/**
	 * Abierto desde el menú de usuario (a 390), el lanzador queda oculto al cerrar el menú: el foco vuelve al menú.
	 *
	 * @returns {void}
	 */
	restoreFocus() {
		const active = document.activeElement
		//Si el foco sigue en la ventana que se cierra, en body o en algo oculto (el item del menú), no volvió a ningún sitio.
		if (active === null || active === document.body || active.offsetParent === null || this.root.contains(active)) {
			const menu = document.querySelector('[user-options-dropdown]')
			if (menu !== null) {
				menu.focus()
			}
		}
	}

	/**
	 * El historial que se manda: lo pintado menos el último turno, que es la pregunta recién enviada.
	 *
	 * @returns {{role: string, content: string, signature: (string|null)}[]}
	 */
	history() {
		const turns = this.$chat.chat('get history') || []
		return turns.slice(0, -1).map((turn) => ({
			role: turn.role,
			content: turn.text,
			signature: turn.meta && typeof turn.meta === 'object' && typeof turn.meta.signature === 'string' ? turn.meta.signature : null,
		}))
	}

	/**
	 * @param {string} text
	 * @returns {void}
	 */
	ask(text) {
		const history = this.history()
		this.save()
		this.$chat.chat('set thinking', true)
		fetch(this.url, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
			body: JSON.stringify({ question: text, history: history }),
		})
			.then((response) => response.json().catch(() => null))
			.then((data) => {
				if (data === null || typeof data !== 'object') {
					this.$chat.chat('add notice', 'error', this.networkError)
					return
				}
				if (data.ok === true) {
					this.$chat.chat('add assistant', String(data.answer || ''), { signature: data.signature || null })
					this.save()
					const links = PanelAssistant.sameOriginLinks(data.links, location.href)
					if (links.length > 0) {
						this.$chat.chat('add actions', links)
					}
					return
				}
				const kind = data.kind === 'unavailable' ? 'unavailable' : 'error'
				this.$chat.chat('add notice', kind, typeof data.error === 'string' && data.error !== '' ? data.error : this.networkError)
			})
			.catch(() => {
				this.$chat.chat('add notice', 'error', this.networkError)
			})
	}
}

window.addEventListener('load', () => {
	const root = document.querySelector('[panel-assistant]')
	if (root !== null && typeof $.fn.chat === 'function') {
		window.pcsPanelAssistant = new PanelAssistant(root)
	}
})
