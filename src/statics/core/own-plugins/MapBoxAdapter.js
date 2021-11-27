/**
 * @function MapBoxAdapter
 */
function MapBoxAdapter(mapStyle = MapBoxAdapter.styles.MapboxStreets) {

	const langGroup = 'MapBoxAdapter'

	MapBoxAdapter.registerDynamicMessages(langGroup)

	/**
	 * @typedef {Object} ResponseConfigurate
	 * @property {mapboxgl.Map} map 
	 * @property {mapboxgl.Marker} marker 
	 * @property {MapboxGeocoder} geocoder 
	 * @property {mapboxgl.GeolocateControl} geolocator 
	 * @property {String|null} mapID
	 */
	/**
	 * @typedef {Object} ConfigurationObject
	 * @property {number} [defaultLongitude] 
	 * @property {number} [defaultLatitude] 
	 * @property {string} [idMapContainer=map] 
	 */
	/**
	 * @typedef {Object} ConfigurationFormObject
	 * @property {HTMLElement|$} longitudeInput 
	 * @property {HTMLElement|$} latitudeInput 
	 * @property {HTMLElement|$} altitudeInput 
	 * @property {HTMLElement|$} accuracyInput 
	 * @property {HTMLElement|$} selectCountry 
	 * @property {HTMLElement|$} selectState
	 * @property {HTMLElement|$} selectCity 
	 * @property {HTMLElement|$} triggerDrawView 
	 * @property {HTMLElement|$} triggerSatelitalView 
	 * @property {HTMLElement|$} triggerCenterView 
	 */
	/**
	 * @typedef {Object} ConfigurationMapBox
	 * @property {boolean} withMarker 
	 * @property {boolean} withGeolocator 
	 * @property {Number} [zoom=7] 
	 */
	let ignore;

	/**
	 * @property {string} key
	 */
	let instanceKey = 'pk.eyJ1Ijoic2lydmFtYiIsImEiOiJjamt1YjBzeXEwZWlvM3FxbDBuZDZmZWFtIn0.jv_5-3mX1kWLrk1ffvV2zQ'

	/**
	 * @property {string} style
	 */
	let instanceStyle = typeof mapStyle == 'string' && mapStyle.trim().length > 0 ? mapStyle.trim() : MapBoxAdapter.styles.MapboxStreets

	/**
	 * @type {MapBoxAdapter} instance
	 */
	let instance = this

	/**
	 * @type {Object} events
	 */
	let events = {}

	/**
	 * @type {HTMLElement} eventer
	 */
	let eventer = document.createElement('div')

	MapBoxAdapter.instances.push(instance)

	/**
	 * @type {ResponseConfigurate}
	 */
	this.currentMapElements = null

	/** 
	 * @param {String} eventName 
	 * @param {Function} callback 
	 * @returns {MapBoxAdapter}
	 */
	this.on = function (eventName, callback) {

		eventName = typeof eventName == 'string' && eventName.trim().length > 0 ? eventName.trim() : null
		callback = typeof callback == 'function' ? callback : null

		if (eventName !== null && callback !== null) {

			if (typeof events[eventName] == 'undefined') {
				events[eventName] = {}
			}

			const codeCallback = generateUniqueID()
			events[eventName][codeCallback] = function () {
				if (eventName === MapBoxAdapter.events.ChangeMarkerPosition) {
					callback(instance.currentMapElements.marker)
				} else {
					callback()
				}
			}

			eventer.addEventListener(`MapBoxAdapterCustomEvent-${eventName}`, events[eventName][codeCallback])
		}

		return this

	}

	/** 
	 * @param {String} eventName
	 * @returns {MapBoxAdapter}
	 */
	this.off = function (eventName) {

		eventName = typeof eventName == 'string' && eventName.trim().length > 0 ? eventName.trim() : null

		if (eventName !== null) {

			if (typeof events[eventName] == 'object') {

				for (const codeCallback in events[eventName]) {
					const callback = events[eventName][codeCallback]
					eventer.removeEventListener(`MapBoxAdapterCustomEvent-${eventName}`, callback)
				}

			}

		}

		return this

	}

	/** 
	 * @param {String} eventName 
	 * @returns {MapBoxAdapter}
	 */
	this.dispatch = function (eventName) {

		eventName = typeof eventName == 'string' && eventName.trim().length > 0 ? eventName.trim() : null

		if (eventName !== null) {
			eventer.dispatchEvent(new Event(`MapBoxAdapterCustomEvent-${eventName}`))
		}

		return this

	}

	/**
	 * @method setKey
	 * @description Configura la llave de la API MapBox
	 * @param {string} key
	 * @returns {MapBoxAdapter}
	 */
	this.setKey = function (key) {
		instanceKey = key
		return instance
	}

	/**
	 * @method getElevation
	 * @description Configura la llave de la API MapBox
	 * @param {Number} lng
	 * @param {Number} lat
	 * @returns {Promise}
	 */
	this.getElevation = function (lng, lat) {

		return new Promise(function (resolve) {

			const loaderName = generateUniqueID('getElevation')
			showGenericLoader(loaderName)

			const apiTileURL = `https://api.mapbox.com/v4/mapbox.mapbox-terrain-v2/tilequery/${lng},${lat}.json?layers=contour&limit=50&access_token=${instanceKey}`

			const query = fetch(apiTileURL, {
				method: 'GET',
			})

			query.then(res => res.json()).then(function (data) {
				const allFeatures = data.features
				const elevations = allFeatures.map((feature) => feature.properties.ele)
				let highestElevation = Math.max(...elevations)
				highestElevation = isFinite(highestElevation) && !isNaN(highestElevation) ? highestElevation : 0
				resolve(highestElevation.toFixed(3))
			}).finally(() => removeGenericLoader(loaderName))

		})

	}

	/**
	 * @method configurate
	 * @description Configura los parámetros iniciales de MapBox
	 * @param {ConfigurationObject} configurations
	 * @param {ConfigurationMapBox} config
	 * @returns {Promise<ResponseConfigurate>}
	 */
	this.configurate = function (configurations = {}, config = {}) {

		return new Promise(function (resolve, reject) {

			let options = {
				withMarker: true,
				withGeolocator: true,
				zoom: 7,
			}

			let defaultLongitude = typeof configurations.defaultLongitude != 'undefined' ? configurations.defaultLongitude : null
			let defaultLatitude = typeof configurations.defaultLatitude != 'undefined' ? configurations.defaultLatitude : null
			let idContainer = typeof configurations.idMapContainer != 'undefined' ? configurations.idMapContainer : 'map'

			let container = $('#' + idContainer)
			let mapID = container.data('map-id')
			mapID = typeof mapID != 'undefined' && mapID !== null ? mapID.toString() : null
			mapID = mapID !== null && mapID.length > 0 ? mapID : null

			for (let property in options) {
				if (config[property] !== undefined) {
					options[property] = config[property]
				}
			}

			container.parent().css({
				position: 'relative',
				height: '500px',
				width: '100%'

			})

			container.css({
				position: 'absolute',
				top: '0',
				bottom: '0',
				width: '100%'
			})

			if (container.length > 0) {
				mapboxgl.accessToken = instanceKey

				let longitude = defaultLongitude === null ? -74.8199524 : defaultLongitude
				let latitude = defaultLatitude === null ? 4.6854957 : defaultLatitude

				let style = instanceStyle
				let zoom = options.zoom

				let mapboxInstance = new mapboxgl.Map({
					container: idContainer,
					style: style,
					doubleClickZoom: false,
					zoom: zoom
				})

				let marker = new mapboxgl.Marker({
					draggable: true
				})

				let scale = new mapboxgl.ScaleControl({
					maxWidth: 80,
					unit: 'metric'
				})

				let nav = new mapboxgl.NavigationControl()

				let geolocator = new mapboxgl.GeolocateControl({
					positionOptions: {
						enableHighAccuracy: true
					},
					trackUserLocation: true
				})

				let fullscreen = new mapboxgl.FullscreenControl()

				let geocoderOptions = {
					accessToken: mapboxgl.accessToken,
					autocomplete: true,
					localGeocoder: (query) => {
						// match anything which looks like a decimal degrees coordinate pair
						var matches = query.match(/^[ ]*(?:Lat: )?(-?\d+\.?\d*)[, ]+(?:Lng: )?(-?\d+\.?\d*)[ ]*$/i);
						if (!matches) {
							return null;
						}

						function coordinateFeature(lng, lat) {
							return {
								center: [lng, lat],
								geometry: {
									type: "Point",
									coordinates: [lng, lat]
								},
								place_name: 'Lat: ' + lat + ', Lng: ' + lng, // eslint-disable-line camelcase
								place_type: ['coordinate'], // eslint-disable-line camelcase
								properties: {},
								type: 'Feature'
							};
						}

						var coord1 = Number(matches[1]);
						var coord2 = Number(matches[2]);
						var geocodes = [];

						if (coord1 < -90 || coord1 > 90) {
							// must be lng, lat
							geocodes.push(coordinateFeature(coord1, coord2));
						}

						if (coord2 < -90 || coord2 > 90) {
							// must be lat, lng
							geocodes.push(coordinateFeature(coord2, coord1));
						}

						if (geocodes.length === 0) {
							// else could be either lng, lat or lat, lng
							geocodes.push(coordinateFeature(coord1, coord2));
							geocodes.push(coordinateFeature(coord2, coord1));
						}

						return geocodes;
					},
					zoom: zoom,
					placeholder: "Ej.: El Prado, Barranquilla, Colombia"
				}

				let geocoder = new MapboxGeocoder(geocoderOptions)

				mapboxInstance.addControl(nav, 'top-left')
				mapboxInstance.addControl(scale)
				mapboxInstance.addControl(geocoder)
				if (options.withGeolocator) {
					mapboxInstance.addControl(geolocator)
				}
				mapboxInstance.addControl(fullscreen)

				mapboxInstance.setCenter([longitude, latitude])

				if (options.withMarker) {
					marker.setLngLat([longitude, latitude]).addTo(mapboxInstance)
					instance.dispatch(MapBoxAdapter.events.ChangeMarkerPosition)
				}

				mapboxInstance.on('load', (e) => {
					if (defaultLatitude == null && defaultLongitude == null) {

						if (options.withGeolocator) {
							geolocator.trigger()
						}

					}
				})

				if (options.withGeolocator) {
					geolocator.on('geolocate', (pos) => {
						let coordinates = pos.coords
						let lat = coordinates.latitude
						let lng = coordinates.longitude
						marker.setLngLat([lng, lat])
						instance.dispatch(MapBoxAdapter.events.ChangeMarkerPosition)
					})
				}

				let returnObject = {}

				returnObject.map = mapboxInstance
				returnObject.marker = marker
				returnObject.geocoder = geocoder
				returnObject.geolocator = geolocator
				returnObject.mapID = mapID

				resolve(returnObject)

				instance.currentMapElements = returnObject
			}

		})
	}

	/**
	 * @method configurateWhitForm
	 * @description Configura los parámetros iniciales de MapBox y asocia con entradas de un formulario
	 * que controla cambios dinámicos en el mapa
	 * @param {ConfigurationFormObject} controls
	 * @param {ConfigurationObject} defaultValues
	 * @param {ConfigurationMapBox} configMapBox
	 * @returns {Promise}
	 */
	this.configurateWhitForm = function (controls = {}, defaultValues = {}, configMapBox = {}) {

		let longitudeInput = $('.ELEMENT_NOT_EXISTS#ELEMENT_NOT_EXISTS')
		let latitudeInput = $('.ELEMENT_NOT_EXISTS#ELEMENT_NOT_EXISTS')
		let altitudeInput = $('.ELEMENT_NOT_EXISTS#ELEMENT_NOT_EXISTS')
		let accuracyInput = $('.ELEMENT_NOT_EXISTS#ELEMENT_NOT_EXISTS')
		let triggerDrawView = $('.ELEMENT_NOT_EXISTS#ELEMENT_NOT_EXISTS')
		let triggerSatelitalView = $('.ELEMENT_NOT_EXISTS#ELEMENT_NOT_EXISTS')
		let triggerCenterView = $('.ELEMENT_NOT_EXISTS#ELEMENT_NOT_EXISTS')
		let selectCountry = $('.ELEMENT_NOT_EXISTS#ELEMENT_NOT_EXISTS')
		let selectState = $('.ELEMENT_NOT_EXISTS#ELEMENT_NOT_EXISTS')
		let selectCity = $('.ELEMENT_NOT_EXISTS#ELEMENT_NOT_EXISTS')

		if (controls.longitudeInput instanceof HTMLElement || controls.longitudeInput instanceof $) {
			longitudeInput = controls.longitudeInput
		}
		if (controls.latitudeInput instanceof HTMLElement || controls.latitudeInput instanceof $) {
			latitudeInput = controls.latitudeInput
		}
		if (controls.altitudeInput instanceof HTMLElement || controls.altitudeInput instanceof $) {
			altitudeInput = controls.altitudeInput
		}
		if (controls.accuracyInput instanceof HTMLElement || controls.accuracyInput instanceof $) {
			accuracyInput = controls.accuracyInput
		}
		if (controls.triggerDrawView instanceof HTMLElement || controls.triggerDrawView instanceof $) {
			triggerDrawView = controls.triggerDrawView
		}
		if (controls.triggerSatelitalView instanceof HTMLElement || controls.triggerSatelitalView instanceof $) {
			triggerSatelitalView = controls.triggerSatelitalView
		}
		if (controls.triggerCenterView instanceof HTMLElement || controls.triggerCenterView instanceof $) {
			triggerCenterView = controls.triggerCenterView
		}
		if (controls.selectCountry instanceof HTMLElement || controls.selectCountry instanceof $) {
			selectCountry = controls.selectCountry
		}
		if (controls.selectState instanceof HTMLElement || controls.selectState instanceof $) {
			selectState = controls.selectState
		}
		if (controls.selectCity instanceof HTMLElement || controls.selectCity instanceof $) {
			selectCity = controls.selectCity
		}

		let longitudeSetted = ''
		let latitudeSetted = ''

		let hasInputsControl = longitudeInput.length > 0 && latitudeInput.length > 0
		let hasAltitudeInput = altitudeInput.length > 0
		let hasAccuracyInput = accuracyInput.length > 0

		let hasData = false

		if (hasInputsControl) {

			hasData = longitudeInput.val().length > 0 && latitudeInput.val().length > 0

			if (hasData) {
				longitudeSetted = longitudeInput.val()
				latitudeSetted = latitudeInput.val()
			}

		}

		let config = null

		if (hasData) {

			defaultValues.defaultLongitude = longitudeSetted
			defaultValues.defaultLatitude = latitudeSetted
			config = instance.configurate(defaultValues, configMapBox)

		} else {

			config = instance.configurate(defaultValues, configMapBox)

		}

		let drawView = 'mapbox://styles/mapbox/streets-v10'
		let satelitalView = 'mapbox://styles/mapbox/satellite-v9'

		return new Promise(function (resolve) {

			config.then((res) => {

				let mapboxInstance = res.map
				let marker = res.marker
				let geocoder = res.geocoder
				let geolocator = res.geolocator
				let mapID = res.mapID

				if (triggerDrawView.length > 0 && triggerSatelitalView.length > 0) {

					triggerDrawView.off('click')
					triggerDrawView.on('click', (e) => {
						e.preventDefault()
						mapboxInstance.setStyle(drawView)
					})

					triggerSatelitalView.off('click')
					triggerSatelitalView.on('click', (e) => {
						e.preventDefault()
						mapboxInstance.setStyle(satelitalView)
					})

				}

				if (hasInputsControl) {

					let coords = marker.getLngLat()
					let lng = coords.lng
					let lat = coords.lat

					longitudeInput.val(lng)
					latitudeInput.val(lat)

					longitudeInput.off('change')
					longitudeInput.on('change', function (e) {
						let lng = $(this).val()
						let lat = latitudeInput.val()
						mapboxInstance.setCenter([lng, lat])
						marker.setLngLat([lng, lat])
						instance.dispatch(MapBoxAdapter.events.ChangeMarkerPosition)
						instance.getElevation(lng, lat).then(function (altitude) {
							altitudeInput.val(altitude)
						})
					})

					latitudeInput.off('change')
					latitudeInput.on('change', function (e) {
						let lng = longitudeInput.val()
						let lat = $(this).val()
						mapboxInstance.setCenter([lng, lat])
						marker.setLngLat([lng, lat])
						instance.dispatch(MapBoxAdapter.events.ChangeMarkerPosition)
						instance.getElevation(lng, lat).then(function (altitude) {
							altitudeInput.val(altitude)
						})
					})

				}

				if (hasAltitudeInput) {
					let coords = marker.getLngLat()
					instance.getElevation(coords.lng, coords.lat).then(function (altitude) {
						altitudeInput.val(altitude)
					})
				}

				geolocator.on('geolocate', (pos) => {

					let coordinates = pos.coords
					let lat = coordinates.latitude
					let lng = coordinates.longitude
					let accuracy = coordinates.accuracy.toFixed(3)

					if (hasAccuracyInput) {
						accuracyInput.val(accuracy)
					}

					if (hasInputsControl) {
						longitudeInput.val(lng)
						latitudeInput.val(lat)
					}

					instance.getElevation(lng, lat).then(function (altitude) {
						altitudeInput.val(altitude)
					})

				})

				triggerCenterView.off('click')
				triggerCenterView.on('click', function (e) {

					e.preventDefault()
					let coords = marker.getLngLat()

					let lng = coords.lng
					let lat = coords.lat
					mapboxInstance.setCenter([lng, lat])

				})

				if (selectCity.length > 0) {

					let countryText = ''
					let stateText = ''
					let cityText = ''
					let query = ''

					selectCity.on('change', function (e) {

						let element = $(e.target)
						let optionCity = element.find(`option`).filter(':selected')

						countryText = selectCountry.find(`option`).filter(':selected').html()
						stateText = selectState.find(`option`).filter(':selected').html()
						cityText = optionCity.val().trim().length > 0 ? optionCity.html() : ''

						query = []
						query.push(cityText)
						query.push(stateText)
						query.push(countryText)
						query = query.map(e => e.trim()).filter(e => e.length > 0)
						query = query.join(', ').trim()

						geocoder.query(query)

					})

					geocoder.on('result', function (r) {

						let result = r.result
						let coordinates = result.geometry.coordinates
						let lat = coordinates[1]
						let lng = coordinates[0]
						let placeName = result.place_name
						let placeType = result.place_type
						//console.log(result)

						let wasFound = true
						let countLocations = query.split(', ').length
						let findForCountry = countLocations == 1
						let findForState = countLocations == 2
						let findForCity = countLocations == 3

						if (findForCountry && placeType != 'country') {
							wasFound = false
						} else if (findForState && placeType != 'region') {
							wasFound = false
						} else if (findForCity && placeType != 'place') {
							wasFound = false
						}

						if (placeName.indexOf(countryText) == -1) {
							wasFound = false
						}

						if (!wasFound) {

							if (findForCity || findForState) {
								infoMessage(_i18n('location', 'Información'), formatStr(
									_i18n('location', `La ubicación "%r" no se encontró en el mapa, se usará una posición aproximada.`),
									[
										query,
									]
								))
							} else {
								infoMessage(_i18n('location', 'Información'), formatStr(
									_i18n('location', `La ubicación "%r" no se encontró en el mapa.`),
									[
										query,
									]
								))
							}

							query = []

							if (findForCity) {
								query.push(stateText)
								query.push(countryText)
							} else if (findForState) {
								query.push(countryText)
							}

							if (findForCity || findForState) {
								query = query.map(e => e.trim()).filter(e => e.length > 0)
								query = query.join(', ').trim()
								geocoder.query(query)
							}

						} else {

							mapboxInstance.flyTo({
								center: [
									lng,
									lat
								]
							})

							marker.setLngLat([lng, lat])
							instance.dispatch(MapBoxAdapter.events.ChangeMarkerPosition)

							if (hasInputsControl) {
								longitudeInput.val(lng)
								latitudeInput.val(lat)
							}

						}
					})
				}

				mapboxInstance.on('dblclick', (e) => {

					let coords = e.lngLat

					let lng = coords.lng
					let lat = coords.lat

					marker.setLngLat([lng, lat])
					instance.dispatch(MapBoxAdapter.events.ChangeMarkerPosition)

					if (hasInputsControl) {
						longitudeInput.val(lng)
						latitudeInput.val(lat)
						longitudeInput.trigger('change')
						latitudeInput.trigger('change')
					}

					if (hasAltitudeInput) {
						instance.getElevation(lng, lat).then(function (altitude) {
							altitudeInput.val(altitude)
						})
					}

					if (hasAccuracyInput) {
						accuracyInput.val(0)
					}

				})

				marker.on('dragend', (e) => {

					let coords = marker.getLngLat()

					let lng = coords.lng
					let lat = coords.lat

					if (hasInputsControl) {
						longitudeInput.val(lng)
						latitudeInput.val(lat)
						longitudeInput.trigger('change')
						latitudeInput.trigger('change')
					}

					if (hasAltitudeInput) {
						instance.getElevation(lng, lat).then(function (altitude) {
							altitudeInput.val(altitude)
						})
					}

					if (hasAccuracyInput) {
						accuracyInput.val(0)
					}

				})

				resolve(res)

			})

		})
	}

}
/**
 * @type {MapBoxAdapter[]}
 */
MapBoxAdapter.instances = []
/**
 * @param {String} id
 * @returns {MapBoxAdapter|null}
 */
MapBoxAdapter.instanceByID = function (id) {
	const instances = MapBoxAdapter.instances
	let instance = null
	for (const i of instances) {
		if (i.currentMapElements.mapID == id) {
			instance = i
			break
		}
	}
	return instance
}
/**
 * @property {String} ChangeMarkerPosition Devuelve los parámetros: marker
 */
MapBoxAdapter.events = {
	ChangeMarkerPosition: 'ChangeMarkerPosition',
}
/**
 * @see https://docs.mapbox.com/api/maps/styles/
 */
MapBoxAdapter.styles = {
	MapboxStreets: 'mapbox://styles/mapbox/streets-v11',
	MapboxOutdoors: 'mapbox://styles/mapbox/outdoors-v11',
	MapboxLight: 'mapbox://styles/mapbox/light-v10',
	MapboxDark: 'mapbox://styles/mapbox/dark-v10',
	MapboxSatellite: 'mapbox://styles/mapbox/satellite-v9',
	MapboxSatelliteStreets: 'mapbox://styles/mapbox/satellite-streets-v11',
	MapboxNavigationDay: 'mapbox://styles/mapbox/navigation-day-v1',
	MapboxNavigationNight: 'mapbox://styles/mapbox/navigation-night-v1',
}

/**
 * @param {String} name 
 * @returns {void}
 */
MapBoxAdapter.registerDynamicMessages = function (name) {

	if (typeof pcsphpGlobals != 'object') {
		pcsphpGlobals = {}
	}
	if (typeof pcsphpGlobals.messages != 'object') {
		pcsphpGlobals.messages = {}
	}
	if (typeof pcsphpGlobals.messages.es != 'object') {
		pcsphpGlobals.messages.es = {}
	}
	if (typeof pcsphpGlobals.messages.en != 'object') {
		pcsphpGlobals.messages.en = {}
	}

	let es = {
	}

	let en = {
		'Información': 'Information',
		'La ubicación "%r" no se encontró en el mapa, se usará una posición aproximada.': 'The location "%r" was not found on the map, an approximate position will be used.',
		'La ubicación "%r" no se encontró en el mapa.': 'The location "%r" was not found on the map.',
	}

	for (let i in es) {
		if (typeof pcsphpGlobals.messages.es[name] == 'undefined') pcsphpGlobals.messages.es[name] = {}
		pcsphpGlobals.messages.es[name][i] = es[i]
	}

	for (let i in en) {
		if (typeof pcsphpGlobals.messages.en[name] == 'undefined') pcsphpGlobals.messages.en[name] = {}
		pcsphpGlobals.messages.en[name][i] = en[i]
	}

}
