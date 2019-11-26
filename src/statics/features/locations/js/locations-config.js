$(document).ready(function (e) {

	let locations = new Locations()
	let mapBoxAdapter = new MapBoxAdapter()

	locations.fillSelectWithCountries()

	let controlsMapBox = {
		latitudeInput: $(`[longitude-mapbox-handler]`),
		longitudeInput: $(`[latitude-mapbox-handler]`),
		selectCountry: $(`[locations-component-auto-filled-country]`),
		selectState: $(`[locations-component-auto-filled-state]`),
		selectCity: $(`[locations-component-auto-filled-city]`),
		triggerSatelitalView: $(`[set-satelital-view]`),
		triggerDrawView: $(`[set-draw-view]`),
		triggerCenterView: $(`[set-center-view]`),
	}

	mapBoxAdapter
		.setKey('pk.eyJ1Ijoic2lydmFtYiIsImEiOiJjanV1MGRuYm8wZHBtM3lyejJ3MzQ5bnFnIn0.udY9ENFrQDuXESogeaI19Q')
		.configurateWhitForm(
			controlsMapBox,
			{
				defaultLongitude: -74.8065913846496,
				defaultLatitude: 11.0021516003209,
			},
			{
				zoom: 14,
			}
		)

})

function Locations() {

	let instance = this

	let locationsURL = 'locations'

	let countriesURL = `${locationsURL}/countries`
	let statesURL = `${locationsURL}/states`
	let citiesURL = `${locationsURL}/cities`
	let pointsURL = `${locationsURL}/points`

	let selectAutoFilledCountryAttr = 'locations-component-auto-filled-country'
	let selectAutoFilledStateAttr = 'locations-component-auto-filled-state'
	let selectAutoFilledCityAttr = 'locations-component-auto-filled-city'
	let selectAutoFilledPointAttr = 'locations-component-auto-filled-point'

	let countryFirstTime = true
	let stateFirstTime = true
	let cityFirstTime = true
	let pointFirstTime = true

	let lastStatesSelected = []
	let lastCitiesSelected = []
	let lastPointsSelected = []

	/**
	 * @method fillSelectWithCountries
	 * @description Rellena un select con los países
	 * @returns {bool} true si hay, false si no
 	 */
	this.fillSelectWithCountries = () => {

		let countries = this.getCountries()

		let countriesSelect = $(`[${selectAutoFilledCountryAttr}]`)

		let has = true

		if (countriesSelect.length > 0) {

			has = countries.length > 0

			let firstOption = countriesSelect.find(`option[value=""]`)

			let attrValue = countriesSelect.attr(selectAutoFilledCountryAttr)
			let hasDefault = typeof attrValue == 'string' && attrValue.trim().length > 0 && countryFirstTime
			let defaultValue = hasDefault ? attrValue.trim() : null

			countryFirstTime = false

			if (firstOption.length > 0) {
				firstOption = firstOption.get(0).outerHTML
			} else if (countries.length > 0) {
				firstOption = `<option value="">${_i18n('location', 'Seleccione una opción')}</option>`
			} else {
				firstOption = null
			}

			countriesSelect.html('')

			if (firstOption != null) {
				countriesSelect.append(firstOption)
			}

			//Acciones con valores iniciales

			for (let country of countries) {
				let option = document.createElement('option')
				option.value = country.id
				option.innerHTML = country.name
				if (hasDefault) {
					if (country.id == defaultValue) {
						option.setAttribute('selected', true)
					}
				}
				countriesSelect.append(option)
			}

			let selectedOption = countriesSelect.find('option').filter(':selected')
			let selectedValue = selectedOption.length > 0 ? selectedOption.val().trim() : ''

			if (selectedValue.length > 0) {
				if (!instance.fillSelectWithStates(selectedValue)) {
					infoMessage(_i18n('location', 'Atención'), _i18n('location', 'No hay departamentos registrados.'))
				}
			}

			//Acciones con eventos

			function eventHandler(e) {

				let value = $(e.target).val()
				value = typeof value == 'string' ? value : ''

				if (value.trim().length > 0) {
					if (!instance.fillSelectWithStates(value)) {
						infoMessage(_i18n('location', 'Atención'), _i18n('location', 'No hay departamentos registrados.'))
					}
				} else {
					instance.fillSelectWithStates(-1)
				}

			}
			countriesSelect.off('change', eventHandler)
			countriesSelect.on('change', eventHandler)

			if (typeof countriesSelect.attr('with-dropdown') == 'string') {

				countriesSelect.dropdown()
				if (!hasDefault) {
					instance.fillSelectWithStates(null)
					countriesSelect.dropdown('clear')
				} else {
					countriesSelect.dropdown('refresh')
				}

			}

		}

		return has
	}

	/**
	 * @method fillSelectWithStates
	 * @description Rellena un select con los estados del país provisto
	 * @param {number} country El id del país
	 * @returns {bool} true si hay, false si no
 	 */
	this.fillSelectWithStates = (country) => {

		let states = []

		let statesSelect = $(`[${selectAutoFilledStateAttr}]`)

		let has = true

		if (statesSelect.length > 0) {

			states = country !== null ? this.getStates(country) : []

			has = states.length > 0

			let firstOption = statesSelect.find(`option[value=""]`)

			if (firstOption.length > 0) {
				firstOption = firstOption.get(0).outerHTML
			} else if (states.length > 0) {
				firstOption = `<option value="">${_i18n('location', 'Seleccione una opción')}</option>`
			} else {
				firstOption = null
			}

			statesSelect.html('')

			if (firstOption != null) {
				statesSelect.append(firstOption)
			}

			let hasDefault = false

			if (country !== null) {

				//Acciones cuando comienza con valores definidos

				let attrValue = statesSelect.attr(selectAutoFilledStateAttr)
				let defaultValues = []
				if (typeof attrValue == 'string') {

					if (attrValue.trim().split(', ').length > 0) {

						defaultValues = attrValue.trim().split(', ').filter((i) => {
							return i.trim().length > 0
						})

						hasDefault = defaultValues.length > 0 && stateFirstTime

					}

				}
				stateFirstTime = false

				for (let state of states) {
					let option = document.createElement('option')
					option.value = state.id
					option.innerHTML = state.name
					if (hasDefault) {
						if (defaultValues.indexOf(state.id) !== -1) {
							option.setAttribute('selected', true)
						}
					} else {
						if (lastStatesSelected.indexOf(state.id) !== -1) {
							option.setAttribute('selected', true)
						}
					}
					statesSelect.append(option)
				}

				lastStatesSelected = Array.isArray(statesSelect.val()) ? statesSelect.val() : [statesSelect.val()]

				if (statesSelect.parents('.ui.dropdown').length > 0) {
					let field = statesSelect.parents('.field')
					let htmlSelect = statesSelect.get(0).outerHTML
					statesSelect.parents('.ui.dropdown').remove()
					field.append($(htmlSelect).get(0))
					statesSelect = field.find('select')
				}

				if (hasDefault) {

					if (!instance.fillSelectWithCities(defaultValues)) {
						infoMessage(
							_i18n('location', 'Atención'),
							_i18n('location', `No hay ciudades registradas en el/los departamento(s) seleccionado(s).`)
						)
					}

				}

				//Acciones en eventos

				function eventHandler(e) {

					let that = $(e.currentTarget)
					let value = that.val()
					lastStatesSelected = Array.isArray(value) ? value : [value]

					if (Array.isArray(value)) {

						if (value.length > 0) {

							if (!instance.fillSelectWithCities(value)) {
								infoMessage(
									_i18n('location', 'Atención'),
									_i18n('location', `No hay ciudades registradas en el/los departamento(s) seleccionado(s).`)
								)
							}

						} else {
							instance.fillSelectWithCities(-1)
						}

					} else {

						if (value.trim().length > 0) {
							if (!instance.fillSelectWithCities(value)) {
								infoMessage(
									_i18n('location', 'Atención'),
									_i18n('location', `No hay ciudades registradas en el/los departamento(s) seleccionado(s).`)
								)
							}
						} else {
							instance.fillSelectWithCities(-1)
						}

					}

				}
				statesSelect.off('change', eventHandler)
				statesSelect.on('change', eventHandler)

			}

			if (typeof statesSelect.attr('with-dropdown') == 'string') {

				statesSelect.dropdown()

				if (!hasDefault) {

					instance.fillSelectWithCities(null)

					if (lastStatesSelected.length > 0) {
						statesSelect.dropdown('refresh')
						instance.fillSelectWithCities(lastStatesSelected)
					} else {
						statesSelect.dropdown('clear')
					}

				} else {
					statesSelect.dropdown('refresh')
				}

			}

		}

		return has
	}

	/**
	 * @method fillSelectWithCities
	 * @description Rellena un select con las ciudades del estado provisto
	 * @param {number} state El id del estado
	 * @returns {bool} true si hay, false si no
 	 */
	this.fillSelectWithCities = (state) => {

		let cities = []

		let citiesSelect = $(`[${selectAutoFilledCityAttr}]`)

		let has = true

		if (citiesSelect.length > 0) {

			if (state !== null || Array.isArray(state)) {

				state = Array.isArray(state) ? state : [state]

				for (let i of state) {

					let _cities = this.getCities(i)

					for (let j of _cities) {
						cities.push(j)
					}
				}

			}

			has = cities.length > 0

			let firstOption = citiesSelect.find(`option[value=""]`)

			if (firstOption.length > 0) {
				firstOption = firstOption.get(0).outerHTML
			} else if (cities.length > 0) {
				firstOption = `<option value="">${_i18n('location', 'Seleccione una opción')}</option>`
			} else {
				firstOption = null
			}

			citiesSelect.html('')

			if (firstOption != null) {
				citiesSelect.append(firstOption)
			}

			let hasDefault = false

			if (state !== null) {

				//Acciones cuando comienza con valores definidos

				let attrValue = citiesSelect.attr(selectAutoFilledCityAttr)
				let defaultValues = []
				if (typeof attrValue == 'string') {

					if (attrValue.trim().split(', ').length > 0) {

						defaultValues = attrValue.trim().split(', ').filter((i) => {
							return i.trim().length > 0
						})

						hasDefault = defaultValues.length > 0 && cityFirstTime

					}

				}

				cityFirstTime = false

				for (let city of cities) {
					let option = document.createElement('option')
					option.value = city.id
					option.innerHTML = city.name
					if (hasDefault) {
						if (defaultValues.indexOf(city.id) !== -1) {
							option.setAttribute('selected', true)
						}
					} else {
						if (lastCitiesSelected.indexOf(city.id) !== -1) {
							option.setAttribute('selected', true)
						}
					}
					citiesSelect.append(option)
				}

				lastCitiesSelected = Array.isArray(citiesSelect.val()) ? citiesSelect.val() : [citiesSelect.val()]

				if (citiesSelect.parents('.ui.dropdown').length > 0) {
					let field = citiesSelect.parents('.field')
					let htmlSelect = citiesSelect.get(0).outerHTML
					citiesSelect.parents('.ui.dropdown').remove()
					field.append($(htmlSelect).get(0))
					citiesSelect = field.find('select')
				}

				if (hasDefault) {

					if (!instance.fillSelectWithPoints(defaultValues)) {
						infoMessage(
							_i18n('location', 'Atención'),
							_i18n('location', `No hay locaciones registradas en la(s) ciudad(es) seleccionada(s).`)
						)
					}

				}

				//Acciones en eventos

				function eventHandler(e) {

					let that = $(e.currentTarget)
					let value = that.val()
					lastCitiesSelected = Array.isArray(value) ? value : [value]

					if (Array.isArray(value)) {

						if (value.length > 0) {

							if (!instance.fillSelectWithPoints(value)) {
								infoMessage(
									_i18n('location', 'Atención'),
									_i18n('location', `No hay locaciones registradas en la(s) ciudad(es) seleccionada(s).`)
								)
							}

						} else {
							instance.fillSelectWithPoints(-1)
						}

					} else {

						if (value.trim().length > 0) {
							if (!instance.fillSelectWithPoints(value)) {
								infoMessage(
									_i18n('location', 'Atención'),
									_i18n('location', `No hay locaciones registradas en la(s) ciudad(es) seleccionada(s).`)
								)
							}
						} else {
							instance.fillSelectWithPoints(-1)
						}

					}

				}
				citiesSelect.off('change', eventHandler)
				citiesSelect.on('change', eventHandler)

			}

			if (typeof citiesSelect.attr('with-dropdown') == 'string') {

				citiesSelect.dropdown()

				if (!hasDefault) {

					instance.fillSelectWithPoints(null)

					if (lastCitiesSelected.length > 0 && state != null) {
						citiesSelect.dropdown('refresh')
						instance.fillSelectWithPoints(lastCitiesSelected)
					} else {
						citiesSelect.dropdown('clear')
					}

				} else {
					citiesSelect.dropdown('refresh')
				}

			}

		}

		return has
	}


	/**
	 * @method fillSelectWithPoints
	 * @description Rellena un select con los puntos de la ciudad provista
	 * @param {number} city El id del estado
	 * @returns {bool} true si hay, false si no
 	 */
	this.fillSelectWithPoints = (city) => {

		let points = []

		let pointsSelect = $(`[${selectAutoFilledPointAttr}]`)

		let has = true

		if (pointsSelect.length > 0) {

			if (city !== null || Array.isArray(city)) {

				city = Array.isArray(city) ? city : [city]

				for (let i of city) {

					let _points = this.getPoints(i)

					for (let j of _points) {
						points.push(j)
					}
				}

			}

			has = points.length > 0

			let firstOption = pointsSelect.find(`option[value=""]`)

			if (firstOption.length > 0) {
				firstOption = firstOption.get(0).outerHTML
			} else if (points.length > 0) {
				firstOption = `<option value="">${_i18n('location', 'Seleccione una opción')}</option>`
			} else {
				firstOption = null
			}

			pointsSelect.html('')

			if (firstOption != null) {
				pointsSelect.append(firstOption)
			}

			let hasDefault = false

			if (city !== null) {

				//Acciones cuando comienza con valores definidos

				let attrValue = pointsSelect.attr(selectAutoFilledPointAttr)
				let defaultValues = []
				if (typeof attrValue == 'string') {

					if (attrValue.trim().split(', ').length > 0) {

						defaultValues = attrValue.trim().split(', ').filter((i) => {
							return i.trim().length > 0
						})

						hasDefault = defaultValues.length > 0 && pointFirstTime

					}

				}

				pointFirstTime = false

				for (let point of points) {
					let option = document.createElement('option')
					option.value = point.id
					option.innerHTML = point.name
					if (hasDefault) {
						if (defaultValues.indexOf(point.id) !== -1) {
							option.setAttribute('selected', true)
						}
					} else {
						if (lastPointsSelected.indexOf(point.id) !== -1) {
							option.setAttribute('selected', true)
						}
					}
					pointsSelect.append(option)
				}

				lastPointsSelected = Array.isArray(pointsSelect.val()) ? pointsSelect.val() : [pointsSelect.val()]

				if (pointsSelect.parents('.ui.dropdown').length > 0) {
					let field = pointsSelect.parents('.field')
					let htmlSelect = pointsSelect.get(0).outerHTML
					pointsSelect.parents('.ui.dropdown').remove()
					field.append($(htmlSelect).get(0))
					pointsSelect = field.find('select')
				}

				//Acciones en eventos

				function eventHandler(e) {

					let that = $(e.currentTarget)
					let value = that.val()
					lastPointsSelected = Array.isArray(value) ? value : [value]

				}
				pointsSelect.off('change', eventHandler)
				pointsSelect.on('change', eventHandler)

			}

			if (typeof pointsSelect.attr('with-dropdown') == 'string') {

				pointsSelect.dropdown()

				if (!hasDefault) {

					if (lastPointsSelected.length > 0 && city != null) {
						pointsSelect.dropdown('refresh')
					} else {
						pointsSelect.dropdown('clear')
					}

				} else {
					pointsSelect.dropdown('refresh')
				}

			}

		}

		return has
	}

	/**
	 * @method getCountries
	 * @description Devuelve los países
	 * @returns {array}
 	 */
	this.getCountries = () => {
		let countries = []
		$.ajax({
			async: false,
			url: countriesURL,
			dataType: 'json',
		}).done(function (res) {
			countries = res
		})
		return countries
	}

	/**
	 * @method getStates
	 * @description Devuelve los estados del país provisto
	 * @param {number} country
	 * @returns {array}
 	 */
	this.getStates = (country) => {
		let states = []
		$.ajax({
			async: false,
			url: `${statesURL}?country=${country}`,
			dataType: 'json',
		}).done(function (res) {
			states = res
		})
		return states
	}

	/**
	 * @method getCities
	 * @description Devuelve las ciudades del estado provisto
	 * @param {number} state
	 * @returns {array}
 	 */
	this.getCities = (state) => {
		let cities = []
		$.ajax({
			async: false,
			url: `${citiesURL}?state=${state}`,
			dataType: 'json',
		}).done(function (res) {
			cities = res
		})
		return cities
	}

	/**
	 * @method getPoints
	 * @description Devuelve los puntos de la ciudad provista
	 * @param {number} city
	 * @returns {array}
 	 */
	this.getPoints = (city) => {
		let points = []
		$.ajax({
			async: false,
			url: `${pointsURL}?city=${city}`,
			dataType: 'json',
		}).done(function (res) {
			points = res
		})
		return points
	}

}

function MapBoxAdapter() {

	/**
	 * @typedef {Object} ResponseConfigurate
	 * @property {mapboxgl.Map} map 
	 * @property {mapboxgl.Marker} marker 
	 * @property {MapboxGeocoder} geocoder 
	 * @property {mapboxgl.GeolocateControl} geolocator 
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
	let instanceStyle = 'mapbox://styles/mapbox/streets-v10'

	/**
	 * @type {MapBoxAdapter} instance
	 */
	let instance = this

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
	 * @method configurate
	 * @description Configura los parámetros iniciales de MapBox
	 * @param {ConfigurationObject} configurations
	 * @param {ConfigurationMapBox} config
	 * @returns {Promise<ResponseConfigurate>}
	 */
	this.configurate = function (configurations = {}, config = {}) {

		return new Promise(function (resolve, reject) {

			let styleSheets = [
				'https://api.tiles.mapbox.com/mapbox-gl-js/v0.47.0/mapbox-gl.css',
				'https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v2.3.0/mapbox-gl-geocoder.css',
			]

			for (let styleSheet of styleSheets) {
				$('<link/>', {
					rel: 'stylesheet',
					type: 'text/css',
					href: styleSheet
				}).appendTo('head')
			}

			$.when(
				$.getScript("https://api.tiles.mapbox.com/mapbox-gl-js/v0.47.0/mapbox-gl.js"),
				$.getScript("https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v2.3.0/mapbox-gl-geocoder.min.js"),
				$.Deferred(function (deferred) {
					$(deferred.resolve);
				})
			).done(function () {

				let options = {
					withMarker: true,
					withGeolocator: true,
					zoom: 7,
				}

				let defaultLongitude = typeof configurations.defaultLongitude != 'undefined' ? configurations.defaultLongitude : null
				let defaultLatitude = typeof configurations.defaultLatitude != 'undefined' ? configurations.defaultLatitude : null
				let idContainer = typeof configurations.idMapContainer != 'undefined' ? configurations.idMapContainer : 'map'

				let container = $('#' + idContainer)

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

					let map = new mapboxgl.Map({
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
						unit: 'imperial'
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

					map.addControl(nav, 'top-left')
					map.addControl(scale)
					map.addControl(geocoder)
					if (options.withGeolocator) {
						map.addControl(geolocator)
					}
					map.addControl(fullscreen)

					map.setCenter([longitude, latitude])

					if (options.withMarker) {
						marker.setLngLat([longitude, latitude]).addTo(map)
					}

					map.on('load', (e) => {
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
						})
					}

					let returnObject = {}

					returnObject.map = map
					returnObject.marker = marker
					returnObject.geocoder = geocoder
					returnObject.geolocator = geolocator

					resolve(returnObject)
				}
			})
		})
	}

	/**
	 * @method configurateWhitForm
	 * @description Configura los parámetros iniciales de MapBox y asocia con entradas de un formulario
	 * que controla cambios dinámicos en el mapa
	 * @param {ConfigurationFormObject} controls
	 * @param {ConfigurationObject} defaultValues
	 * @param {ConfigurationMapBox} configMapBox
	 * @returns {void}
	 */
	this.configurateWhitForm = function (controls = {}, defaultValues = {}, configMapBox = {}) {

		let longitudeInput = $('.ELEMENT_NOT_EXISTS#ELEMENT_NOT_EXISTS')
		let latitudeInput = $('.ELEMENT_NOT_EXISTS#ELEMENT_NOT_EXISTS')
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

		config.then((res) => {

			let map = res.map
			let marker = res.marker
			let geocoder = res.geocoder
			let geolocator = res.geolocator

			if (triggerDrawView.length > 0 && triggerSatelitalView.length > 0) {

				triggerDrawView.click((e) => {
					e.preventDefault()
					map.setStyle(drawView)
				})

				triggerSatelitalView.click((e) => {
					e.preventDefault()
					map.setStyle(satelitalView)
				})

			}

			if (hasInputsControl) {

				let coords = marker.getLngLat()
				let lng = coords.lng
				let lat = coords.lat

				longitudeInput.val(lng)
				latitudeInput.val(lat)

				longitudeInput.change(function (e) {
					let lng = $(this).val()
					let lat = latitudeInput.val()
					map.setCenter([lng, lat])
					marker.setLngLat([lng, lat])
				})

				latitudeInput.change(function (e) {
					let lng = longitudeInput.val()
					let lat = $(this).val()
					map.setCenter([lng, lat])
					marker.setLngLat([lng, lat])
				})

			}

			triggerCenterView.click(function (e) {

				e.preventDefault()
				let coords = marker.getLngLat()

				let lng = coords.lng
				let lat = coords.lat
				map.setCenter([lng, lat])

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
					console.log(result)

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

						map.flyTo({
							center: [
								lng,
								lat
							]
						})

						marker.setLngLat([lng, lat])

						if (hasInputsControl) {
							longitudeInput.val(lng)
							latitudeInput.val(lat)
						}

					}
				})
			}

			map.on('dblclick', (e) => {

				let coords = e.lngLat

				let lng = coords.lng
				let lat = coords.lat

				marker.setLngLat([lng, lat])

				if (hasInputsControl) {
					longitudeInput.val(lng)
					latitudeInput.val(lat)
					longitudeInput.change()
					latitudeInput.change()
				}

			})

			marker.on('dragend', (e) => {

				let coords = marker.getLngLat()

				let lng = coords.lng
				let lat = coords.lat

				if (hasInputsControl) {
					longitudeInput.val(lng)
					latitudeInput.val(lat)
					longitudeInput.change()
					latitudeInput.change()
				}

			})

		})
	}

}
