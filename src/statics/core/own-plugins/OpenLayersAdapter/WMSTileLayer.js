/// <reference path="./olImports.js" />
/// <reference path="./MapManager.js" />
/// <reference path="./GeoJSONVectorLayer.js" />
/// <reference path="./OpenLayersAdapter.js" />
/**
 * @function WMSTileLayer
 * @param {Object} parameters 
 * @param {String} parameters.workspace 
 * @param {String} parameters.url 
 * @param {String} parameters.layerName 
 * @param {String} parameters.name 
 * @param {String} [parameters.SRS=EPSG:4326]
 * @param {String} [parameters.version=1.1.1]
 * @returns {WMSTileLayer}
 */
function WMSTileLayer(parameters) {

	parameters = typeof parameters == 'object' ? parameters : {}

	let options = {}

	let optionsDefault = {
		workspace: {
			required: true,
			default: null,
			validation: (v) => {
				return typeof v == 'string'
			}
		},
		url: {
			required: true,
			default: null,
			validation: (v) => {
				return typeof v == 'string' || v instanceof URL
			}
		},
		name: {
			required: false,
			default: '',
			validation: (v) => {
				return typeof v == 'string'
			}
		},
		layerName: {
			required: true,
			default: null,
			validation: (v) => {
				return typeof v == 'string'
			}
		},
		SRS: {
			required: false,
			default: 'EPSG:4326',
			validation: (v) => {
				return typeof v == 'string'
			}
		},
		version: {
			required: false,
			default: '1.1.1',
			validation: (v) => {
				return typeof v == 'string'
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
					`El valor de la opción ${option} es erróneo en WMSTileLayer`,
					inputOption,
				]
				alert('Error en WMSTileLayer')
				console.error(text)
			}

		} else {

			if (!required) {
				options[option] = defaultValue
			} else {
				let text = [
					`La opción ${option} es obligatoria para WMSTileLayer`,
				]
				alert('Error en WMSTileLayer')
				console.error(text)
			}

		}

	}

	let url = new URL(options.url)
	let workspaceName = options.workspace
	let layerName = options.layerName
	let name = options.name.length > 0 ? options.name : options.layerName
	let SRS = options.SRS
	let version = options.version
	let displayName = name
	let source = null
	let layer = null
	let instance = this

	init()

	function init() {

		let urlWMS = new URL(`${workspaceName}/wms`, url).href

		source = new SourceTileWMS({
			url: urlWMS,
			params: {
				VERSION: version,
				LAYERS: layerName,
				SRS: SRS,
				CRS: SRS,
				TRANSPARENT: true,
			},
		})

		layer = new LayerTile({
			source: source,
		})


	}

	this.getSource = () => {
		return source
	}

	this.getLayer = () => {
		return layer
	}

	this.addToMap = (mapManager) => {
		mapManager.addLayer(layerName, layer)
	}

	this.removeFromMap = (mapManager) => {
		mapManager.removeLayer(layerName)
	}

	this.setDisplayName = (display) => {
		displayName = display
	}

	this.getDisplayName = () => {
		return displayName
	}

	this.getWorkspaceName = () => {
		return workspaceName
	}

	this.getLayerName = () => {
		return layerName
	}

	this.getName = () => {
		return name
	}

}
