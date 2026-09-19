/// <reference path="./olImports.js" />
/// <reference path="./GeoJSONVectorLayer.js" />
/// <reference path="./WMSTileLayer.js" />
/// <reference path="./OpenLayersAdapter.js" />
/**
 * @function MapManager
 * @param {Object} parameters
 * @param {String} parameters.baseURL
 * @param {String} parameters.username
 * @param {String} parameters.password
 * @returns {MapManager}
 */
function MapManager(parameters) {

	//──── Parámetros de entrada ─────────────────────────────────────────────────────────────

	parameters = typeof parameters == 'object' ? parameters : {}

	let optionsDefault = {
		baseURL: {
			required: false,
			default: null,
			validation: (v) => {
				return typeof v == 'string' || v instanceof URL
			}
		},
		username: {
			required: false,
			default: null,
			validation: (v) => {
				return typeof v == 'string'
			}
		},
		password: {
			required: false,
			default: null,
			validation: (v) => {
				return typeof v == 'string'
			}
		},
	}

	parameters = proccessOptions(parameters, optionsDefault)

	//──── Variables ─────────────────────────────────────────────────────────────────────────

	/** @type {URL} */ let baseURL = new URL('http://localhost/')
	/** @type {URL} */ let username = ''
	/** @type {URL} */ let password = ''
	/** @type {Map} */ let layersMapStore = new Map()
	/** @type {OlView} */ let view
	/** @type {OlMap} */ let map
	/** @type {Number} */ let zoom

	/** @type {MapManager} */ let instance = this

	/**
	 * @function initMap
	 * @returns {void}
	 */
	this.initMap = (
		mapID,
		center = [
			-75.67872965512649,
			8.907716088896592,
		],
		zoomParam = 10
	) => {

		instance.setBaseURL(parameters.baseURL)
		instance.setUsername(typeof parameters.username == 'string' ? parameters.username : '')
		instance.setPassword(typeof parameters.password == 'string' ? parameters.password : '')

		zoom = zoomParam

		view = new OlView({
			projection: olProj.get('EPSG:4326'),
			center: center,
			zoom: zoom,
		})

		map = new OlMap({
			controls: controlDefaults().extend([
				new ControlFullScreen(),
				new ControlZoomSlider(),
			]),
			layers: layersArray(),
			target: mapID,
			view: view,
		})

	}

	/**
	 * @function on
	 * @returns {void}
	 */
	this.on = (type, callback) => {

		callback = typeof callback == 'function' ? callback : () => { }

		if (typeof type == 'string') {
			map.on(type, callback)
		}

	}

	/**
	 * @function addLayer
	 * @param {String} name
	 * @param {Layer} layer
	 * @returns {void}
	 */
	this.addLayer = (name, layer) => {
		instance.removeLayer(name)
		layersMapStore.set(name, layer)
		layer.set('manager-name', name)
		map.addLayer(layer)
	}

	/**
	 * @function removeLayer
	 * @param {String} name
	 * @returns {void}
	 */
	this.removeLayer = (name) => {
		if (layersMapStore.has(name)) {
			let layer = layersMapStore.get(name)
			map.removeLayer(layer)
			layersMapStore.delete(name)
		}
	}

	/**
	 * @function getMap
	 * @returns {OlMap}
	 */
	this.getMap = () => {
		return map
	}

	/**
	 * @function setBaseURL
	 * @param {String} url
	 * @returns {MapManager|void}
	 */
	this.setBaseURL = (url) => {

		let validInput = typeof url == 'string'

		if (validInput) {

			baseURL = new URL(url)

		} else {

			throw new Error('Error')

		}

		return instance

	}

	/**
	 * @function setUsername
	 * @param {String} value
	 * @returns {MapManager|void}
	 */
	this.setUsername = (value) => {

		let validInput = typeof value == 'string'

		if (validInput) {

			username = value.trim()

		} else {

			throw new Error('Error')

		}

		return instance

	}

	/**
	 * @function setPassword
	 * @param {String} value
	 * @returns {MapManager|void}
	 */
	this.setPassword = (value) => {

		let validInput = typeof value == 'string'

		if (validInput) {

			password = value

		} else {

			throw new Error('Error')

		}

		return instance

	}

	/**
	 * @function getBaseURL
	 * @returns {URL}
	 */
	this.getBaseURL = () => {

		return baseURL

	}

	/**
	 * @function getUsername
	 * @returns {String}
	 */
	this.getUsername = () => {

		return username

	}

	/**
	 * @function getPassword
	 * @returns {String}
	 */
	this.getPassword = () => {

		return password

	}

	/**
	 * @function baseURL
	 * @param {String} relativeURL
	 * @returns {URL}
	 */
	this.baseURL = (relativeURL) => {

		let validInput = typeof relativeURL == 'string'

		if (validInput) {

			return new URL(relativeURL, baseURL)

		} else {

			throw new Error('Error')

		}

	}

	/**
	 * @function getOSMRaster
	 * @returns {LayerTile}
	 */
	this.getOSMRaster = () => {
		return new LayerTile({
			source: new SourceOSM()
		})
	}

	/**
	 * @function layersArray
	 * @returns {Array}
	 */
	function layersArray() {

		let array = []
		let iterator = layersMapStore.values()

		for (let layer of iterator) {
			array.push(layer)
		}

		return array

	}

	/**
	 * @function proccessOptions
	 * @param {Object} input
	 * @param {String} input.baseURL
	 * @param {String} input.landDataURL
	 * @param {String} input.username
	 * @param {String} input.password
	 * @param {Object} optionsDefault 
	 * @returns {Object}
	*/
	function proccessOptions(input, optionsDefault) {

		let options = {}

		for (let option in optionsDefault) {

			let defaults = optionsDefault[option]
			let required = defaults.required
			let defaultValue = defaults.default
			let validation = defaults.validation

			if (typeof input[option] != 'undefined') {

				let inputOption = input[option]

				if (validation(inputOption)) {
					options[option] = inputOption
				} else {
					let text = [
						`El valor de la opción ${option} es erróneo en MapManager`,
						inputOption,
					]
					alert('Error en MapManager')
					console.error(text)
				}

			} else {

				if (!required) {
					options[option] = defaultValue
				} else {
					let text = [
						`La opción ${option} es obligatoria para MapManager`,
					]
					alert('Error en MapManager')
					console.error(text)
				}

			}

		}

		return options
	}

}
