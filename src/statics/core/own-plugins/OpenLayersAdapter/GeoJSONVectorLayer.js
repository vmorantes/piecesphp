/// <reference path="./olImports.js" />
/// <reference path="./MapManager.js" />
/// <reference path="./WMSTileLayer.js" />
/// <reference path="./OpenLayersAdapter.js" />
/**
 * @function GeoJSONVectorLayer
 * @param {Object} parameters 
 * @param {String} parameters.url 
 * @param {String} parameters.layerName 
 * @param {String} parameters.name 
 * @param {Number} [parameters.maxFeatures=null] 
 * @returns {GeoJSONVectorLayer}
 */
function GeoJSONVectorLayer(parameters) {

	parameters = typeof parameters == 'object' ? parameters : {}

	let options = {}

	let optionsDefault = {
		url: {
			required: true,
			default: null,
			validation: (v) => {
				return typeof v == 'string' || v instanceof URL
			}
		},
		layerName: {
			required: false,
			default: '',
			validation: (v) => {
				return typeof v == 'string'
			}
		},
		name: {
			required: false,
			default: '',
			validation: (v) => {
				return typeof v == 'string'
			}
		},
		maxFeatures: {
			required: false,
			default: null,
			validation: (v) => {
				return typeof v == 'number' || v == null
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
					`El valor de la opción ${option} es erróneo en GeoJSONVectorLayer`,
					inputOption,
				]
				alert('Error en GeoJSONVectorLayer')
				console.error(text)
			}

		} else {

			if (!required) {
				options[option] = defaultValue
			} else {
				let text = [
					`La opción ${option} es obligatoria para GeoJSONVectorLayer`,
				]
				alert('Error en GeoJSONVectorLayer')
				console.error(text)
			}

		}

	}

	/** @type {URL} */ let url = new URL(options.url)
	let layerName = options.layerName
	let name = options.name.length > 0 ? options.name : options.layerName

	let displayName = options.name
	let maxFeatures = options.maxFeatures
	/** @type {SourceVector} */ let source = null
	/** @type {LayerVector} */ let layer = null
	let instance = this

	init()

	function init() {

		url.searchParams.set('service', 'WFS')
		url.searchParams.set('version', '1.0.0')
		url.searchParams.set('request', 'GetFeature')
		url.searchParams.set('typeName', layerName)
		if (maxFeatures !== null) {
			url.searchParams.set('maxFeatures', maxFeatures)
		}
		url.searchParams.set('outputFormat', encodeURI('application/json'))

		source = new SourceVector({
			format: new FormatGeoJSON(),
			url: url,
		})

		layer = new LayerVector({
			source: source,
		})

		layer.setStyle(generateStyle())

		layer.on('change', function () {

		})

	}

	this.getFeatures = () => {
		return source.getFeatures()
	}

	this.getURL = () => {
		return url
	}

	this.getSource = () => {
		return source
	}

	this.getLayer = () => {
		return layer
	}

	this.addToMap = (mapManager) => {
		mapManager.addLayer(layerName, layer)
		return instance
	}

	this.removeFromMap = (mapManager) => {
		mapManager.removeLayer(layerName)
		return instance
	}

	this.styleLayer = (fillColor = "rgba(255,255,255, 1)", strokeColor = "#3399CC", strokeWidth = 3, radius = 6) => {
		layer.setStyle(generateStyle(fillColor, strokeColor, strokeWidth, radius))
		return instance
	}

	this.setDisplayName = (display) => {
		displayName = display
		return instance
	}

	this.getDisplayName = () => {
		return displayName
	}

	this.getLayerName = () => {
		return layerName
	}

	this.getName = () => {
		return name
	}

	function generateStyle(fillColor = "rgba(255,255,255, 1)", strokeColor = "#3399CC", strokeWidth = 0.5, radius = 6) {

		let styles = []

		// --------------------------------------------

		styles[GeometryType.POLYGON] = [
			new Style({
				fill: new StyleFill({
					color: fillColor
				})
			})
		]
		styles[GeometryType.MULTI_POLYGON] = styles[GeometryType.POLYGON]

		// --------------------------------------------

		styles[GeometryType.LINE_STRING] = [
			new Style({
				stroke: new StyleStroke({
					color: strokeColor,
					width: strokeWidth + 0.5
				})
			}),
			new Style({
				stroke: new StyleStroke({
					color: strokeColor,
					width: strokeWidth
				})
			})
		]

		styles[GeometryType.MULTI_LINE_STRING] = styles[GeometryType.LINE_STRING]

		// --------------------------------------------

		styles[GeometryType.POINT] = [
			new Style({
				image: new StyleCircle({
					radius: radius,
					fill: new StyleFill({
						color: fillColor
					}),
					stroke: new StyleStroke({
						color: strokeColor,
						width: strokeWidth
					})
				}),
				zIndex: Infinity
			})
		]
		styles[GeometryType.MULTI_POINT] = styles[GeometryType.POINT]
		styles[GeometryType.GEOMETRY_COLLECTION] = styles[GeometryType.POLYGON].concat(
			styles[GeometryType.LINE_STRING],
			styles[GeometryType.POINT]
		)

		// --------------------------------------------

		let stylesArray = []

		for (let type in styles) {

			let styles_ = styles[type]
			for (let style_ of styles_) {
				stylesArray.push(style_)
			}
		}

		return stylesArray

	}

}
