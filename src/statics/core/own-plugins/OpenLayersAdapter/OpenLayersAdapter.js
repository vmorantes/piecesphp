/// <reference path="./olImports.js" />
/// <reference path="./MapManager.js" />
/// <reference path="./GeoJSONVectorLayer.js" />
/// <reference path="./WMSTileLayer.js" />
/**
 * @function OpenLayersAdapter
 * @param {Object} parameters 
 * @param {String} [parameters.containerSelector]
 * @param {String} [parameters.mapID]
 * @param {Number[]} [parameters.center]
 * @param {Number} [parameters.zoom]
 * @returns {OpenLayersAdapter}
 */
function OpenLayersAdapter(parameters) {

	//──── Parámetros de entrada ─────────────────────────────────────────────────────────────

	parameters = typeof parameters == 'object' ? parameters : {}

	let options = {}

	let optionsDefault = {
		containerSelector: {
			required: false,
			default: '[map-component]',
			validation: (v) => {
				return typeof v == 'string'
			}
		},
		mapID: {
			required: false,
			default: 'main-map',
			validation: (v) => {
				return typeof v == 'string'
			}
		},
		center: {
			required: false,
			default: [
				-75.67872965512649,
				8.907716088896592,
			],
			validation: (v) => {
				return Array.isArray(v)
			}
		},
		zoom: {
			required: false,
			default: 10,
			validation: (v) => {
				v = parseInt(v)
				return typeof v == 'number' && !isNaN(v) && isFinite(v)
			}
		},
	}

	for (let option in optionsDefault) {

		let defaults = optionsDefault[option]
		let required = defaults.required
		let defaultValue = defaults.default
		let validation = defaults.validation

		if (typeof parameters[option] != 'undefined') {

			let inputOption = parameters[option]

			if (validation(inputOption)) {
				options[option] = inputOption
			} else {
				let text = [
					`El valor de la opción ${option} es erróneo en OpenLayersAdapter`,
					inputOption,
				]
				alert('Error en OpenLayersAdapter')
				console.error(text)
			}

		} else {

			if (!required) {
				options[option] = defaultValue
			} else {
				let text = [
					`La opción ${option} es obligatoria para OpenLayersAdapter`,
				]
				alert('Error en OpenLayersAdapter')
				console.error(text)
			}

		}

	}

	parameters = options

	//──── Variables ─────────────────────────────────────────────────────────────────────────

	let namespace = {}

	//Generales
	namespace.guiContainer = $(parameters.containerSelector)
	namespace.mapElement = namespace.guiContainer.find('#' + parameters.mapID)
	namespace.mapManager = null
	namespace.map = null
	namespace.allLayersAdded = []

	//URLs
	namespace.baseURLMaps = namespace.mapElement.data('baseurl')

	//──── Configuración general del mapa ────────────────────────────────────────────────────

	namespace.mapElement.html('')
	namespace.mapManager = new MapManager({
		baseURL: namespace.baseURLMaps,
	})
	namespace.mapManager.initMap(parameters.mapID, parameters.center, parameters.zoom) //Inicializar el mapa
	namespace.map = namespace.mapManager.getMap() //Mapa

	/**
	 * @type {OpenLayersAdapter}
	 */
	let instance = this

	/**
	 * @function init
	 * @returns {void}
	 */
	this.init = () => {
	}

	/**
	 * @function init
	 * @param {Function} callback
	 * @returns {void}
	 */
	this.beforeInit = (callback) => {

		if (typeof callback == 'function') {

			callback(instance)

		}

	}

	/**
	 * @function addOSMLayer
	 * @param {String} [display] 
	 * @returns {void}
	 */
	this.addOSMLayer = (display = 'Open Street Map') => {

		let layerData = {
			layer: namespace.mapManager.getOSMRaster(),
			display: typeof display == 'string' ? display : null,
		}

		configLayer(layerData)

	}



	/**
	 * @function addLayer
	 * @param {GeoJSONVectorLayer|WMSTileLayer} layer 
	 * @returns {void}
	 */
	this.addLayer = (layer) => {

		//new WMSTileLayer({
		//	url: namespace.mapManager.getBaseURL(),
		//	workspace: workspace,
		//	layerName: layerName,
		//})
		//new GeoJSONVectorLayer({
		//	url: namespace.mapManager.baseURL(`${workspace}/ows`),
		//	name: `${workspace}:${layerName}`,
		//	maxFeatures: maxFeatures,
		//})

		let layerData = {
			layer: layer,
		}

		configLayer(layerData)

	}

	/**
	 * @function getMapElement
	 * @returns {$}
	 */
	this.getMapElement = () => {
		return namespace.mapElement.get(0)
	}

	/**
	 * @function getMapManager
	 * @returns {MapManager}
	 */
	this.getMapManager = () => {
		return namespace.mapManager
	}

	/**
	 * @function getMap
	 * @returns {OlMap}
	 */
	this.getMap = () => {
		return namespace.mapManager.getMap()
	}

	/**
	 * @function configLayer
	 * @param {Object} layerData 
	 * @returns {void}
	 */
	function configLayer(layerData) {

		let layer = layerData.layer

		let objectLayer = typeof layer.getName == 'function' ? layer.getLayer() : layer

		let index = namespace.allLayersAdded.length

		//──── Adición ───────────────────────────────────────────────────────────────────────────		

		//Configuraciones
		objectLayer.setVisible(true)

		//Añadir
		if (typeof layer.getLayerName == 'function' && typeof layer.getLayer == 'function') {
			namespace.mapManager.addLayer(layer.getLayerName(), layer.getLayer())
		} else {
			namespace.mapManager.addLayer(index, layer)
		}

		namespace.allLayersAdded.push(layerData)

	}

}
