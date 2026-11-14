
const GEO_LANG_GROUP = 'geo-lang'
//El centro por omisión es una opción (Configuración › Sistema › Llaves de Mapas); si no llega, Barranquilla, el de la página vieja.
const GEO_DEFAULT_CENTER = pcsphpGlobals.mapDefaultCenter !== null ? [pcsphpGlobals.mapDefaultCenter.longitude, pcsphpGlobals.mapDefaultCenter.latitude] : [-74.8065913846496, 11.0021516003209]
const GEO_DEFAULT_ZOOM = pcsphpGlobals.mapDefaultCenter !== null ? pcsphpGlobals.mapDefaultCenter.zoom : 11
const GEO_LABELS_MIN_ZOOM = 14
const GEO_MOVE_WAIT = 300
const GEO_TEXT_WAIT = 400
const GEO_AVATARS_WAIT = 120
//El mismo corte que el @media de geo-viewer.scss: por encima, los paneles van en columna y caben los dos.
const GEO_NARROW = '(max-width: 767.98px)'
const GEO_STYLES = {
	streets: 'mapbox://styles/mapbox/streets-v12',
	satellite: 'mapbox://styles/mapbox/satellite-streets-v12',
}

window.addEventListener('load', function () {
	const root = document.querySelector('[data-geo-viewer]')
	if (root instanceof HTMLElement) {
		registerDynamicLocalizationMessages(GEO_LANG_GROUP)
		new GeoViewer(root)
	}
})

/**
 * El geovisor sobre mapbox-gl, sin MapBoxAdapter. Todo dato del servidor entra por textContent o setAttribute: nunca como HTML.
 */
class GeoViewer {

	/**
	 * @param {HTMLElement} root
	 */
	constructor(root) {
		this.root = root
		this.config = JSON.parse(root.dataset.geoViewer || '{}')
		/** @type {Object<string,{key:string,title:string,marker:string,colorVariable:string,color:string|null}>} */
		this.sources = {}
		for (const source of this.config.sources || []) {
			this.sources[source.key] = source
		}
		this.enabled = new Set(Object.keys(this.sources))
		/** @type {Object<string,{total:number,returned:number,truncated:boolean}>} */
		this.summary = {}
		/** @type {Object<string,Object>} Fuente => FeatureCollection */
		this.data = {}
		/** @type {Map<string,Object>} 'fuente:id' => mapboxgl.Marker */
		this.avatars = new Map()
		this.colors = {}
		this.map = null
		this.ready = false
		//Entre setStyle() y su style.load no se puede añadir ni quitar nada; isStyleLoaded() también da false mientras
		//cargan teselas, y no sirve para decidirlo.
		this.styleReady = false
		this.style = 'streets'
		this.pointsAbort = null
		this.cardAbort = null
		this.card = null
		this.popup = null
		this.moveTimer = null
		this.textTimer = null
		this.avatarsTimer = null
		this.ignoreNextMove = false
		this.resetting = false
		this.layerEventsBound = new Set()

		this.mapElement = root.querySelector('[data-geo-map]')
		this.counter = root.querySelector('[data-geo-counter]')
		this.truncated = root.querySelector('[data-geo-truncated]')
		this.cardHolder = root.querySelector('[data-geo-card-holder]')

		if (pcsAdminSideBarIsOpen()) {
			pcsAdminSideBarToggle(true)
		}

		this.initPanels()
		this.initFilters()
		this.initLegend()
		this.initCard()
		this.setCounter(null)
		if (this.enabled.size === 0) {
			this.counter.closest('.geo-counter').hidden = true
		}
		this.initMap()
	}

	initPanels() {
		const narrow = window.matchMedia(GEO_NARROW).matches
		for (const button of this.root.querySelectorAll('[data-geo-toggle]')) {
			button.addEventListener('click', () => this.togglePanel(button.dataset.geoToggle))
		}
		for (const button of this.root.querySelectorAll('[data-geo-close]')) {
			button.addEventListener('click', () => this.togglePanel(button.dataset.geoClose, false))
		}
		for (const panel of this.root.querySelectorAll('[data-geo-panel]')) {
			this.togglePanel(panel.dataset.geoPanel, !narrow)
		}
	}

	/**
	 * @param {string} name
	 * @param {boolean} [open] Sin él, alterna.
	 */
	togglePanel(name, open) {
		const panel = this.root.querySelector(`[data-geo-panel="${CSS.escape(name)}"]`)
		if (panel === null) {
			return
		}
		open = typeof open == 'boolean' ? open : panel.hidden
		panel.hidden = !open
		const button = this.root.querySelector(`[data-geo-toggle="${CSS.escape(name)}"]`)
		if (button !== null) {
			button.setAttribute('aria-expanded', open ? 'true' : 'false')
			button.classList.toggle('active', open)
		}
		//En el móvil los paneles ocupan el ancho: uno a la vez.
		if (open && window.matchMedia(GEO_NARROW).matches) {
			for (const other of this.root.querySelectorAll('[data-geo-panel]')) {
				if (other !== panel && !other.hidden) {
					this.togglePanel(other.dataset.geoPanel, false)
				}
			}
		}
	}

	initFilters() {
		const form = this.root.querySelector('[data-geo-filters]')
		if (form === null) {
			return
		}
		form.addEventListener('submit', (event) => event.preventDefault())
		for (const input of form.querySelectorAll('[data-geo-filter-type="text"]')) {
			input.addEventListener('input', () => {
				clearTimeout(this.textTimer)
				this.textTimer = setTimeout(() => this.refresh(), GEO_TEXT_WAIT)
			})
		}
		//El panel se desplaza por dentro y recortaba la lista: con la lista fija sale entera (H290).
		for (const select of form.querySelectorAll('select[data-geo-filter-type="options"]')) {
			$(select).dropdown({
				menuPosition: 'fixed',
				fullTextSearch: true,
				clearable: true,
				onChange: () => {
					if (!this.resetting) {
						this.refresh()
					}
				},
			})
		}
		//Las opciones remotas se buscan al escribir (GeoController::filterOptions): la página no trae ninguna.
		for (const select of form.querySelectorAll('select[data-geo-filter-type="remoteOptions"]')) {
			const url = new URL(select.dataset.searchUrl)
			url.searchParams.set('search', 'SEARCH_QUERY')
			$(select).dropdown({
				menuPosition: 'fixed',
				clearable: true,
				minCharacters: 1,
				saveRemoteData: true,
				apiSettings: {
					url: url.href.replace('SEARCH_QUERY', '{query}'),
					cache: false,
				},
				onChange: () => {
					if (!this.resetting) {
						this.refresh()
					}
				},
			})
		}
		//El panel se desplaza por dentro y corta lo que sobresale: el calendario se pinta en el body (H286).
		configSinglesCalendar('geo-calendar-js', { context: 'body' })
		for (const input of form.querySelectorAll('[data-geo-filter-type="dateRange"] input')) {
			input.addEventListener('change', () => this.refresh())
		}
		form.addEventListener('reset', () => {
			this.resetting = true
			setTimeout(() => {
				for (const select of form.querySelectorAll('select[data-geo-filter-type="options"], select[data-geo-filter-type="remoteOptions"]')) {
					$(select).dropdown('clear')
				}
				this.resetting = false
				this.refresh()
			})
		})
	}

	/**
	 * Los filtros como pares de la petición: filters[<clave>]=… o filters[<clave>][]=….
	 *
	 * @returns {Array<[string,string]>}
	 */
	filterParams() {
		const params = []
		for (const element of this.root.querySelectorAll('[data-geo-filter]')) {
			const key = element.dataset.geoFilter
			const type = element.dataset.geoFilterType
			if (type === 'text') {
				const value = element.value.trim()
				if (value.length > 0) {
					params.push([`filters[${key}]`, value])
				}
			} else if (type === 'options') {
				for (const option of element.selectedOptions) {
					if (option.value !== '') {
						params.push([`filters[${key}][]`, option.value])
					}
				}
			} else if (type === 'remoteOptions') {
				//Una opción elegida de la búsqueda no queda en el <select>: el valor lo da el dropdown.
				for (const value of $(element).dropdown('get values') || []) {
					if (value !== '') {
						params.push([`filters[${key}][]`, value])
					}
				}
			} else if (type === 'dateRange') {
				const from = element.querySelector('[data-geo-date="from"]').value.trim()
				const to = element.querySelector('[data-geo-date="to"]').value.trim()
				if (from.length > 0 || to.length > 0) {
					params.push([`filters[${key}][]`, from])
					params.push([`filters[${key}][]`, to])
				}
			}
		}
		return params
	}

	initLegend() {
		for (const sample of this.root.querySelectorAll('[data-geo-legend-sample]')) {
			const template = this.root.querySelector(`template[data-geo-marker="${CSS.escape(sample.dataset.geoLegendSample)}"]`)
			if (template !== null) {
				sample.appendChild(template.content.cloneNode(true))
			}
		}
		for (const input of this.root.querySelectorAll('[data-geo-source-toggle]')) {
			input.addEventListener('change', () => this.toggleSource(input.dataset.geoSourceToggle, input.checked))
		}
		for (const button of this.root.querySelectorAll('[data-geo-style]')) {
			button.addEventListener('click', () => this.setStyle(button.dataset.geoStyle))
		}
	}

	/**
	 * Apagar una fuente la quita del mapa, de la petición y del contador; encenderla vuelve a consultar.
	 *
	 * @param {string} key
	 * @param {boolean} on
	 */
	toggleSource(key, on) {
		if (on) {
			this.enabled.add(key)
			this.refresh()
			return
		}
		this.enabled.delete(key)
		delete this.summary[key]
		delete this.data[key]
		this.removeSourceLayers(key)
		for (const [id, marker] of this.avatars) {
			if (id.startsWith(`${key}:`)) {
				marker.remove()
				this.avatars.delete(id)
			}
		}
		if (this.card !== null && this.card.source === key) {
			this.closeCard()
		}
		this.updateCounter()
	}

	/**
	 * @param {string} name
	 */
	setStyle(name) {
		if (!(name in GEO_STYLES) || name === this.style || this.map === null) {
			return
		}
		this.style = name
		//El estilo activo es el primario del panel; el otro, secundario.
		for (const button of this.root.querySelectorAll('[data-geo-style]')) {
			button.classList.toggle('primary', button.dataset.geoStyle === name)
			button.setAttribute('aria-pressed', button.dataset.geoStyle === name ? 'true' : 'false')
		}
		//Al cambiar de estilo mapbox-gl borra fuentes y capas: style.load las reconstruye con los datos que hay.
		this.styleReady = false
		this.map.setStyle(GEO_STYLES[name], { diff: false })
	}

	initCard() {
		if (this.cardHolder === null) {
			return
		}
		this.cardHolder.querySelector('[data-geo-card-close]').addEventListener('click', () => this.closeCard())
	}

	initMap() {
		fetch(pcsphpGlobals.mapboxKeyURL, { credentials: 'same-origin' })
			.then((response) => response.ok ? response.text() : '')
			.catch(() => '')
			.then((key) => {
				if (typeof key != 'string' || key.trim().length === 0) {
					//El mismo aviso que el adaptador del núcleo: sin llave, mapbox-gl lanza en su constructor.
					const message = document.createElement('div')
					message.className = 'ui warning visible message geo-no-key'
					message.textContent = _i18n(GEO_LANG_GROUP, 'Falta la llave de MapBox: configúrela en Configuración › Sistema › Llaves de Mapas.')
					this.mapElement.replaceChildren(message)
					this.counter.closest('.geo-counter').hidden = true
					return
				}
				mapboxgl.accessToken = key.trim()
				this.map = new mapboxgl.Map({
					container: this.mapElement,
					style: GEO_STYLES[this.style],
					center: GEO_DEFAULT_CENTER,
					zoom: GEO_DEFAULT_ZOOM,
				})
				this.map.addControl(new mapboxgl.NavigationControl({ showCompass: false }), 'bottom-right')
				//Plegar o desplegar el menú cambia el ancho del contenedor, no el de la ventana.
				new ResizeObserver(() => this.map.resize()).observe(this.mapElement)
				this.map.on('style.load', () => {
					this.styleReady = true
					this.resolveColors()
					for (const key of this.enabled) {
						this.addSourceLayers(key)
					}
					this.scheduleAvatars(true)
				})
				this.map.on('load', () => {
					this.ready = true
					this.firstQuery()
				})
				//Pulsar fuera de un punto cierra la tarjeta; los avatares son DOM y no llegan aquí.
				this.map.on('click', (event) => {
					if (!this.styleReady) {
						return
					}
					const layers = Object.keys(this.sources).flatMap((key) => [`geo-${key}-clusters`, `geo-${key}-points`]).filter((layer) => this.map.getLayer(layer))
					if (layers.length === 0 || this.map.queryRenderedFeatures(event.point, { layers: layers }).length === 0) {
						this.closeCard()
					}
				})
				this.map.on('moveend', () => {
					if (this.ignoreNextMove) {
						this.ignoreNextMove = false
						return
					}
					clearTimeout(this.moveTimer)
					this.moveTimer = setTimeout(() => this.refresh(), GEO_MOVE_WAIT)
					this.scheduleAvatars(true)
				})
				this.map.on('render', () => this.scheduleAvatars(false))
			})
	}

	/**
	 * El color de cada fuente, leído de su variable CSS: mapbox-gl pinta clústeres y puntos con un color, no con var().
	 */
	resolveColors() {
		const probe = document.createElement('span')
		probe.hidden = true
		this.root.appendChild(probe)
		const read = (variable, fallback) => {
			probe.style.color = ''
			probe.style.color = `var(${variable}, ${fallback})`
			//El tema da oklch() y Mapbox no lo entiende: un canvas lo devuelve en sRGB.
			const canvas = document.createElement('canvas').getContext('2d')
			canvas.fillStyle = getComputedStyle(probe).color
			canvas.fillRect(0, 0, 1, 1)
			const [red, green, blue] = canvas.getImageData(0, 0, 1, 1).data
			return `rgb(${red}, ${green}, ${blue})`
		}
		this.colors = {
			text: read('--geo-text-color', 'currentColor'),
			halo: read('--geo-panel-background', 'currentColor'),
			onColor: read('--geo-on-color', 'currentColor'),
		}
		for (const key in this.sources) {
			//Sin la variable definida, el color que declara la fuente (GeoSourceRegistry::colorValue()) y, si no, el del texto.
			const variable = this.sources[key].colorVariable
			const fallback = typeof this.sources[key].color == 'string' ? this.sources[key].color : this.colors.text
			this.colors[key] = /^--[A-Za-z0-9_-]+$/.test(variable) ? read(variable, fallback) : this.colors.text
		}
		probe.remove()
	}

	firstQuery() {
		if (this.enabled.size === 0) {
			return
		}
		this.query(false).then((features) => {
			if (!Array.isArray(features)) {
				return
			}
			if (features.length === 0) {
				return
			}
			const bounds = new mapboxgl.LngLatBounds()
			for (const feature of features) {
				bounds.extend(feature.geometry.coordinates)
			}
			this.ignoreNextMove = true
			this.map.fitBounds(bounds, { padding: 60, maxZoom: 15, duration: 0 })
		})
	}

	refresh() {
		if (!this.ready) {
			return
		}
		this.query(true)
	}

	/**
	 * @param {boolean} withBounds Con el encuadre y el acercamiento del mapa, o el mundo (al entrar).
	 * @returns {Promise<Object[]|null>} Las features recibidas, o null si se canceló o falló.
	 */
	query(withBounds) {
		if (this.pointsAbort !== null) {
			this.pointsAbort.abort()
		}
		if (this.enabled.size === 0) {
			this.updateCounter()
			return Promise.resolve([])
		}
		const url = new URL(this.config.pointsURL, document.baseURI)
		for (const key of this.enabled) {
			url.searchParams.append('sources[]', key)
		}
		if (withBounds) {
			const bounds = this.map.getBounds()
			const west = Math.max(-180, bounds.getWest())
			const east = Math.min(180, bounds.getEast())
			const south = Math.max(-90, bounds.getSouth())
			const north = Math.min(90, bounds.getNorth())
			if (west < east && south < north) {
				url.searchParams.set('bbox', [west, south, east, north].map((n) => n.toFixed(6)).join(','))
			}
			url.searchParams.set('zoom', String(Math.min(24, Math.max(0, Math.round(this.map.getZoom())))))
		}
		for (const [name, value] of this.filterParams()) {
			url.searchParams.append(name, value)
		}
		const controller = new AbortController()
		this.pointsAbort = controller
		this.counter.classList.add('loading')
		return fetch(url, { credentials: 'same-origin', signal: controller.signal, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
			.then((response) => response.json().then((json) => ({ ok: response.ok, json: json })))
			.then(({ ok, json }) => {
				if (!ok) {
					console.error(json)
					errorMessage(_i18n(GEO_LANG_GROUP, 'Mapa'), _i18n(GEO_LANG_GROUP, 'No se pudieron cargar los puntos.'))
					return null
				}
				this.setData(json)
				return json.features
			})
			.catch((error) => {
				if (error.name !== 'AbortError') {
					console.error(error)
					errorMessage(_i18n(GEO_LANG_GROUP, 'Mapa'), _i18n(GEO_LANG_GROUP, 'No se pudieron cargar los puntos.'))
				}
				return null
			})
			.finally(() => {
				if (this.pointsAbort === controller) {
					this.pointsAbort = null
					this.counter.classList.remove('loading')
				}
			})
	}

	/**
	 * @param {{sources:Object, features:Object[]}} json
	 */
	setData(json) {
		const collections = {}
		for (const key of this.enabled) {
			collections[key] = { type: 'FeatureCollection', features: [] }
		}
		for (const feature of json.features || []) {
			const key = feature.properties.source
			if (key in collections) {
				collections[key].features.push(feature)
			}
		}
		for (const key in collections) {
			this.data[key] = collections[key]
			this.summary[key] = json.sources[key] || { total: 0, returned: 0, truncated: false }
			const source = this.map.getSource(`geo-${key}`)
			if (source) {
				source.setData(collections[key])
			} else if (this.styleReady) {
				this.addSourceLayers(key)
			}
		}
		this.updateCounter()
		this.scheduleAvatars(true)
	}

	/**
	 * @param {string} key
	 */
	addSourceLayers(key) {
		const source = this.sources[key]
		if (!source || this.map.getSource(`geo-${key}`)) {
			return
		}
		const color = this.colors[key] || this.colors.text
		this.map.addSource(`geo-${key}`, {
			type: 'geojson',
			data: this.data[key] || { type: 'FeatureCollection', features: [] },
			cluster: true,
			//Más que el diámetro de un avatar: dos sueltos no se pisan.
			clusterRadius: 80,
		})
		this.map.addLayer({
			id: `geo-${key}-clusters`,
			type: 'circle',
			source: `geo-${key}`,
			filter: ['has', 'point_count'],
			paint: {
				'circle-color': color,
				'circle-opacity': 0.85,
				'circle-radius': ['step', ['get', 'point_count'], 16, 50, 22, 500, 30],
				'circle-stroke-width': 2,
				'circle-stroke-color': this.colors.onColor,
			},
		})
		this.map.addLayer({
			id: `geo-${key}-cluster-count`,
			type: 'symbol',
			source: `geo-${key}`,
			filter: ['has', 'point_count'],
			layout: {
				'text-field': ['get', 'point_count_abbreviated'],
				'text-size': 12,
				'text-allow-overlap': true,
			},
			paint: {
				'text-color': this.colors.onColor,
			},
		})
		//Los `avatar` se pintan como marcadores DOM; el círculo transparente solo da dónde pulsar mientras se crean.
		const isDot = source.marker === 'dot'
		this.map.addLayer({
			id: `geo-${key}-points`,
			type: 'circle',
			source: `geo-${key}`,
			filter: ['!', ['has', 'point_count']],
			paint: {
				'circle-color': color,
				'circle-radius': isDot ? 6 : 14,
				'circle-opacity': isDot ? 1 : 0,
				'circle-stroke-width': isDot ? 2 : 0,
				'circle-stroke-color': this.colors.onColor,
			},
		})
		this.map.addLayer({
			id: `geo-${key}-labels`,
			type: 'symbol',
			source: `geo-${key}`,
			filter: ['!', ['has', 'point_count']],
			minzoom: GEO_LABELS_MIN_ZOOM,
			layout: {
				'text-field': ['get', 'title'],
				'text-size': 12,
				'text-anchor': 'top',
				'text-offset': [0, isDot ? 0.8 : 1.6],
				'text-max-width': 12,
			},
			paint: {
				'text-color': this.colors.text,
				'text-halo-color': this.colors.halo,
				'text-halo-width': 1.5,
			},
		})
		this.bindLayerEvents(key)
	}

	/**
	 * Los eventos van por id de capa: sobreviven al cambio de estilo y se atan una sola vez.
	 *
	 * @param {string} key
	 */
	bindLayerEvents(key) {
		if (this.layerEventsBound.has(key)) {
			return
		}
		this.layerEventsBound.add(key)
		const clusters = `geo-${key}-clusters`
		const points = `geo-${key}-points`
		this.map.on('click', clusters, (event) => {
			const feature = event.features[0]
			this.map.getSource(`geo-${key}`).getClusterExpansionZoom(feature.properties.cluster_id, (error, zoom) => {
				if (!error) {
					this.map.easeTo({ center: feature.geometry.coordinates, zoom: zoom })
				}
			})
		})
		this.map.on('click', points, (event) => {
			const feature = event.features[0]
			this.openCard(key, feature.properties.id, feature.geometry.coordinates)
		})
		for (const layer of [clusters, points]) {
			this.map.on('mouseenter', layer, () => this.map.getCanvas().style.cursor = 'pointer')
			this.map.on('mouseleave', layer, () => this.map.getCanvas().style.cursor = '')
		}
	}

	/**
	 * @param {string} key
	 */
	removeSourceLayers(key) {
		if (this.map === null || !this.styleReady) {
			return
		}
		for (const layer of ['labels', 'points', 'cluster-count', 'clusters']) {
			if (this.map.getLayer(`geo-${key}-${layer}`)) {
				this.map.removeLayer(`geo-${key}-${layer}`)
			}
		}
		if (this.map.getSource(`geo-${key}`)) {
			this.map.removeSource(`geo-${key}`)
		}
	}

	/**
	 * Los avatares se recalculan como mucho cada GEO_AVATARS_WAIT ms mientras el mapa se mueve, y siempre al pararse.
	 *
	 * @param {boolean} now
	 */
	scheduleAvatars(now) {
		if (now) {
			clearTimeout(this.avatarsTimer)
			this.avatarsTimer = null
			this.updateAvatars()
			return
		}
		if (this.avatarsTimer === null) {
			this.avatarsTimer = setTimeout(() => {
				this.avatarsTimer = null
				this.updateAvatars()
			}, GEO_AVATARS_WAIT)
		}
	}

	/**
	 * Un marcador DOM por punto suelto a la vista de cada fuente `avatar`; los que salen de la vista se retiran.
	 */
	updateAvatars() {
		if (this.map === null || !this.styleReady) {
			return
		}
		const bounds = this.map.getBounds()
		const visible = new Map()
		for (const key of this.enabled) {
			const source = this.sources[key]
			if (!source || source.marker !== 'avatar' || !this.map.getSource(`geo-${key}`)) {
				continue
			}
			for (const feature of this.map.querySourceFeatures(`geo-${key}`, { filter: ['!', ['has', 'point_count']] })) {
				const coordinates = feature.geometry.coordinates
				const id = `${key}:${feature.properties.id}`
				if (!visible.has(id) && bounds.contains(coordinates)) {
					visible.set(id, { key: key, feature: feature })
				}
			}
		}
		for (const [id, marker] of this.avatars) {
			if (!visible.has(id)) {
				marker.remove()
				this.avatars.delete(id)
			}
		}
		for (const [id, { key, feature }] of visible) {
			if (!this.avatars.has(id)) {
				this.avatars.set(id, this.createAvatar(key, feature).addTo(this.map))
			}
		}
	}

	/**
	 * @param {string} key
	 * @param {Object} feature
	 * @returns {Object} mapboxgl.Marker
	 */
	createAvatar(key, feature) {
		const template = this.root.querySelector('template[data-geo-marker="avatar"]')
		const element = template.content.firstElementChild.cloneNode(true)
		const variable = this.sources[key].colorVariable
		if (/^--[A-Za-z0-9_-]+$/.test(variable)) {
			const color = this.sources[key].color
			element.style.setProperty('--geo-marker-color', typeof color == 'string' ? `var(${variable}, ${color})` : `var(${variable})`)
		}
		const title = typeof feature.properties.title == 'string' ? feature.properties.title : ''
		const thumbnail = feature.properties.thumbnail
		const image = element.querySelector('[data-geo-marker-image]')
		const initials = element.querySelector('[data-geo-marker-initials]')
		if (GeoViewer.isSafeURL(thumbnail)) {
			image.setAttribute('src', thumbnail)
			const dense = GeoViewer.denseThumbnail(thumbnail)
			if (dense !== null && GeoViewer.isSafeURL(dense)) {
				image.setAttribute('srcset', `${thumbnail} 1x, ${dense} 2x`)
			}
			image.hidden = false
			initials.hidden = true
		} else {
			initials.textContent = GeoViewer.initialsOf(title)
		}
		element.setAttribute('title', title)
		element.addEventListener('click', (event) => {
			event.stopPropagation()
			this.openCard(key, feature.properties.id, feature.geometry.coordinates)
		})
		return new mapboxgl.Marker({ element: element }).setLngLat(feature.geometry.coordinates)
	}

	/**
	 * Una dirección del servidor que el cliente pone en src o href: http(s)://… o una ruta con UNA barra que se quede en
	 * este sitio. «//dominio» (y «/\dominio», que el navegador lee igual) es otra web, y «javascript:» ejecuta.
	 *
	 * @param {*} url
	 * @returns {boolean}
	 */
	static isSafeURL(url) {
		if (typeof url != 'string') {
			return false
		}
		let parsed = null
		try {
			parsed = new URL(url, window.location.href)
		} catch (error) {
			return false
		}
		if (parsed.protocol !== 'http:' && parsed.protocol !== 'https:') {
			return false
		}
		return /^https?:\/\//i.test(url) || (/^\/(?![\/\\])/.test(url) && parsed.origin === window.location.origin)
	}

	/**
	 * La misma miniatura a 128 px para una pantalla de densidad 2 o más (el avatar mide 36 px y 64 no la cubre), con su
	 * query, que es el sello de versión. null si no es nuestra ruta de miniaturas (el logo público de una organización).
	 *
	 * @param {string} url
	 * @returns {string|null}
	 */
	static denseThumbnail(url) {
		let parsed = null
		try {
			parsed = new URL(url, document.baseURI)
		} catch (error) {
			return null
		}
		if (!/\/64\/?$/.test(parsed.pathname)) {
			return null
		}
		parsed.pathname = parsed.pathname.replace(/\/64(\/?)$/, '/128$1')
		return parsed.href
	}

	/**
	 * @param {string} title
	 * @returns {string}
	 */
	static initialsOf(title) {
		return title.trim().split(/\s+/).filter((word) => word.length > 0).slice(0, 2).map((word) => Array.from(word)[0]).join('').toUpperCase()
	}

	/**
	 * La tarjeta de un punto, pedida al pulsarlo y rellenada desde el <template> de su fuente.
	 *
	 * @param {string} key
	 * @param {number} id
	 * @param {number[]} coordinates
	 */
	openCard(key, id, coordinates) {
		if (this.cardHolder === null || !Number.isInteger(Number(id))) {
			return
		}
		if (this.cardAbort !== null) {
			this.cardAbort.abort()
		}
		this.card = { source: key, id: Number(id), coordinates: coordinates }
		const loading = this.cardHolder.querySelector('[data-geo-card-loading]')
		const message = this.cardHolder.querySelector('[data-geo-card-message]')
		const body = this.cardHolder.querySelector('[data-geo-card-body]')
		body.replaceChildren()
		message.hidden = true
		loading.hidden = false
		this.cardHolder.hidden = false
		if (this.popup === null) {
			this.popup = new mapboxgl.Popup({ closeButton: false, closeOnClick: false, maxWidth: '300px', offset: 18, className: 'geo-popup' })
			this.popup.on('close', () => {
				this.card = null
				if (this.cardAbort !== null) {
					this.cardAbort.abort()
				}
			})
		}
		this.popup.setLngLat(coordinates).setDOMContent(this.cardHolder)
		if (!this.popup.isOpen()) {
			this.popup.addTo(this.map)
		}

		const url = new URL(this.config.pointURL, document.baseURI)
		url.pathname = url.pathname.split('/').map((segment) => {
			if (segment === this.config.sourcePlaceholder) {
				return encodeURIComponent(key)
			}
			return segment === this.config.idPlaceholder ? String(Number(id)) : segment
		}).join('/')
		const controller = new AbortController()
		this.cardAbort = controller
		const card = this.card
		fetch(url, { credentials: 'same-origin', signal: controller.signal, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
			.then((response) => response.json().then((json) => ({ status: response.status, json: json })))
			.then(({ status, json }) => {
				if (this.card !== card) {
					return
				}
				loading.hidden = true
				if (status === 404) {
					message.textContent = _i18n(GEO_LANG_GROUP, 'Ese punto ya no está disponible')
					message.hidden = false
					return
				}
				if (status !== 200 || typeof json.detail != 'object' || json.detail === null) {
					message.textContent = _i18n(GEO_LANG_GROUP, 'No se pudo cargar el punto.')
					message.hidden = false
					return
				}
				body.replaceChildren(this.fillCard(key, json.detail))
			})
			.catch((error) => {
				if (error.name !== 'AbortError' && this.card === card) {
					loading.hidden = true
					message.textContent = _i18n(GEO_LANG_GROUP, 'No se pudo cargar el punto.')
					message.hidden = false
				}
			})
			.finally(() => {
				if (this.cardAbort === controller) {
					this.cardAbort = null
				}
			})
	}

	/**
	 * @param {string} key
	 * @param {Object} detail
	 * @returns {DocumentFragment}
	 */
	fillCard(key, detail) {
		const template = this.root.querySelector(`template[data-geo-card="${CSS.escape(key)}"]`)
		const fragment = template !== null ? template.content.cloneNode(true) : document.createDocumentFragment()
		for (const element of fragment.querySelectorAll('[data-geo-field]')) {
			const value = detail[element.dataset.geoField]
			if (value === null || value === undefined || String(value).trim().length === 0) {
				element.hidden = true
			} else {
				element.textContent = String(value)
			}
		}
		for (const link of fragment.querySelectorAll('[data-geo-href]')) {
			const value = detail[link.dataset.geoHref]
			if (GeoViewer.isSafeURL(value)) {
				link.setAttribute('href', value)
			} else {
				link.remove()
			}
		}
		return fragment
	}

	closeCard() {
		this.card = null
		if (this.cardAbort !== null) {
			this.cardAbort.abort()
		}
		if (this.popup !== null) {
			this.popup.remove()
		}
	}

	/**
	 * @param {string|null} text Sin texto, el de cargando.
	 */
	setCounter(text) {
		if (this.counter === null) {
			return
		}
		this.counter.textContent = text === null ? _i18n(GEO_LANG_GROUP, 'Cargando los puntos…') : text
	}

	updateCounter() {
		let returned = 0
		let total = 0
		let truncated = false
		for (const key of this.enabled) {
			const summary = this.summary[key]
			if (summary) {
				returned += summary.returned
				total += summary.total
				truncated = truncated || summary.truncated
			}
		}
		const lang = typeof pcsphpGlobals.lang == 'string' ? pcsphpGlobals.lang : undefined
		this.setCounter(formatStr(_i18n(GEO_LANG_GROUP, 'Viendo %r de %r'), [returned.toLocaleString(lang), total.toLocaleString(lang)]))
		this.truncated.hidden = !truncated
	}

}
