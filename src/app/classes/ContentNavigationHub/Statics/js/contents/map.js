/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
/// <reference path="../../../../../../statics/core/own-plugins/MapBoxAdapter.js" />
window.addEventListener('load', function () {

	/* Acciones iniciales */

	//Ocultar barra lateral de menú por defecto
	if (pcsAdminSideBarIsOpen()) {
		pcsAdminSideBarToggle(true)
	}

	/* Variables y constantes */
	let currentMarkers = []
	let mapBoxAdapter = new MapBoxAdapter()
	let contentsGeoJsonRequestURL = new URL('admin/geojson-manager-routes/contents-geojson-features', document.baseURI)
	const formFilter = $('.ui.form.section.filters')

	/* Configuraciones */
	configureFiltersForm()

	//Configuración del mapa
	const loaderMap = generateUniqueID()
	showGenericLoader(loaderMap)
	return new Promise(function (resolve) {
		fetch('configurations/mapbox-key')
			.then(response => response.text())
			.then(key => resolve(key))
	}).then(function (key) {
		mapBoxAdapter.setKey(key).configurateWhitForm(
			{},
			{
				defaultLongitude: -74.8065913846496,
				defaultLatitude: 11.0021516003209,
				idMapContainer: 'map',
				ignoreDefaultCss: true,
			},
			{
				withMarker: false,
				withGeocoder: false,
				withFullscreen: false,
				withGeolocator: false,
				zoom: 1,
			}
		)
	}).finally(function () {
		removeGenericLoader(loaderMap)
	})

	/* Utilidades */

	/**
	 * Configuración de filtros
	 */
	function configureFiltersForm() {

		//Controles
		const controlSubmitButton = formFilter.find(`button[type="submit"]`)
		const controlSearchInput = formFilter.find(`[control-search]`)
		const featuresTypeDropdown = formFilter.find(`[control-features-type]`).dropdown({
			onChange: function (value) {
				configurateVisibility(value)
			}
		})
		const controlResearhAreasDropdown = formFilter.find(`[control-research-areas]`).dropdown({
			onAdd: function (addedValue, addedText, $addedChoice) {
				let maxTimes = 30
				let counterTry = 0
				const interval = setInterval(function () {
					const tag = controlResearhAreasDropdown.find(`.ui.label.visible[data-value=${addedValue}]`)
					const color = controlResearhAreasDropdown.find(`select option[value="${addedValue}"]`).data('color')
					tag.addClass('tag-area')
					tag.attr('style', `--tag-color: ${color};`)
					counterTry++
					if (counterTry >= maxTimes || tag.length > 0) {
						clearInterval(interval)
					}
				}, 200)
			}
		})
		const controlOrganizationsDropdown = formFilter.find(`[control-organizations ]`).dropdown()
		const controlContentTypeDropdown = formFilter.find(`[control-content-type]`).dropdown()
		const controlFinancingTypeDropdown = formFilter.find(`[control-financing-type ]`).dropdown()
		const controlStartDateInput = formFilter.find(`[control-start-date]`)
		const controlEndDateInput = formFilter.find(`[control-end-date]`)

		//Visibilidad de los controles según el tipo de feature seleccionado
		const controlsEnabledByFeatureType = {}
		controlsEnabledByFeatureType[FEATURE_TYPE_APPLICATION_CALLS] = [
			controlSearchInput,
			controlResearhAreasDropdown,
			controlOrganizationsDropdown,
			controlContentTypeDropdown,
			controlFinancingTypeDropdown,
			controlStartDateInput,
			controlEndDateInput,
			controlSubmitButton,
		]
		controlsEnabledByFeatureType[FEATURE_TYPE_PROFILES] = [
			controlSearchInput,
			controlResearhAreasDropdown,
			controlOrganizationsDropdown,
			controlSubmitButton,
		]

		//Configurar visibilidad
		configurateVisibility('NONE')

		//Objeto usado para filtrar la solicitud
		const objectQuery = {}

		//Deifinicón de obtención de valores según cada control
		const fieldsToObjectQuery = [
			{
				element: featuresTypeDropdown,
				getValue: function () {
					const value = this.element.dropdown('get value')
					return value
				},
			},
			{
				element: controlSearchInput,
				getValue: function () {
					const value = this.element.val()
					return value
				},
			},
			{
				element: controlResearhAreasDropdown,
				getValue: function () {
					const value = this.element.dropdown('get value')
					return value
				},
			},
			{
				element: controlOrganizationsDropdown,
				getValue: function () {
					const value = this.element.dropdown('get value')
					return value
				},
			},
			{
				element: controlContentTypeDropdown,
				getValue: function () {
					const value = this.element.dropdown('get value')
					return value
				},
			},
			{
				element: controlFinancingTypeDropdown,
				getValue: function () {
					const value = this.element.dropdown('get value')
					return value
				},
			},
			{
				element: controlStartDateInput,
				getValue: function () {
					const value = this.element.val()
					return value
				},
			},
			{
				element: controlEndDateInput,
				getValue: function () {
					const value = this.element.val()
					return value
				},
			},
		]

		//Asignación de entradas al objeto de filtro de solicitud
		fieldsToObjectQuery.map((e) => {
			let name = e.element.attr('name')
			if (e.element.hasClass('dropdown')) {
				name = e.element.find('select').attr('name')
			}
			objectQuery[name] = e
		})

		//Manejo de filtro
		formFilter.on('submit', function (e) {

			e.preventDefault()
			const formData = new FormData(formFilter.get(0))
			const uniqueKeys = Array.from(new Set(formData.keys()))

			/* Asignación de parámetros de solicitud */

			//Se eliminan los parámetros que estén de una anterior consulta
			Object.keys(objectQuery).map(e => contentsGeoJsonRequestURL.searchParams.delete(e))

			//Se asignan los valores del filtro y se rehace la consulta
			for (const key of uniqueKeys) {
				const keyHandler = objectQuery[key]
				const value = keyHandler.getValue()
				const isEnabled = keyHandler.element.is(':visible')
				const valid = (typeof value == 'string' && value.trim().length > 0) || Array.isArray(value)
				if (valid && isEnabled) {
					if (Array.isArray(value)) {
						value.map(function (i) {
							contentsGeoJsonRequestURL.searchParams.append(key, i)
						})
					} else {
						contentsGeoJsonRequestURL.searchParams.set(key, value)
					}
				}
			}

			//Se ejecuta la consulta
			const loaderNameMapPoints = generateUniqueID('loaderNameMapPoints_')
			showGenericLoader(loaderNameMapPoints)
			pcsphpGlobals.globalAuthenticator.get(contentsGeoJsonRequestURL)
				.then(res => res.json())
				.then(function (geojson) {
					addInteractivePoints(geojson, mapBoxAdapter.currentMapElements.map)
					removeGenericLoader(loaderNameMapPoints)
				})

		})

		//Utilidades

		/**
		 * Configura la visibilidad de los elementos según el tipo de feature
		 * @param {String} currentFeatureType
		 */
		function configurateVisibility(currentFeatureType) {

			//Variables
			const segments = formFilter.find('.segment')

			//Se oculta todo en primera instancia
			updateControlsVisibility(currentFeatureType)

			/**
			 * Actualiza la visibilidad de los segmentos del formulario
			 * 
			 * @description
			 * - Itera sobre cada segmento del formulario
			 * - Verifica si el segmento tiene elementos habilitados
			 * - Oculta el segmento si no tiene elementos habilitados
			 * - Muestra el segmento si tiene elementos habilitados
			 * - Actualiza el atributo data-enable del segmento según corresponda
			 */
			function updateSegmentsVisibility() {
				segments.map((index, element) => {
					//Elementos visibles sin contar el título
					const segment = $(element)
					const segmentEnabledElements = segment.find('[data-enable="yes"]')
					const segmentHasVisibleElements = segmentEnabledElements.length > 0
					if (!segmentHasVisibleElements) {
						segment.attr('data-enable', 'no')
						segment.hide()
						if (segment.is(':visible')) {
							segment.hide()
						}
					} else {
						segment.attr('data-enable', 'yes')
						if (!segment.is(':visible')) {
							segment.show()
						}
					}
				})
			}

			/**
			 * Actualiza la visibilidad de los controles del formulario según el tipo de feature seleccionado
			 * 
			 * @param {String} currentFeatureType - El tipo de feature actual seleccionado
			 * 
			 * @description
			 * - Crea objetos para almacenar los elementos a mostrar y ocultar
			 * - Determina qué elementos mostrar basado en el tipo de feature actual
			 * - Marca los elementos no seleccionados para ocultarlos
			 * - Oculta los elementos marcados para ocultar
			 * - Muestra los elementos marcados para mostrar
			 * - Actualiza la visibilidad de los segmentos del formulario
			 */
			function updateControlsVisibility(currentFeatureType) {

				const toHide = {}
				const toShow = {}

				//Seleccionar elementos por mostrar
				if (controlsEnabledByFeatureType.hasOwnProperty(currentFeatureType)) {
					toShow[currentFeatureType] = controlsEnabledByFeatureType[currentFeatureType]
				}

				//Agregar para ocultar los que no se seleccionaron para mostrar
				for (const featureType in controlsEnabledByFeatureType) {
					if (!toShow.hasOwnProperty(featureType)) {
						toHide[featureType] = controlsEnabledByFeatureType[featureType]
					}
				}

				//Ocultar
				for (const featureType in toHide) {
					const elements = toHide[featureType]
					for (const element of elements) {
						element.attr('data-enable', 'no')
						const field = element.closest('.field')
						field.hide()
					}
				}

				//Mostrar
				for (const featureType in toShow) {
					const elements = toShow[featureType]
					for (const element of elements) {
						element.attr('data-enable', 'yes')
						const field = element.closest('.field')
						field.show()
					}
				}

				//Actualizar visibilidad de segmentos
				updateSegmentsVisibility()

			}
		}
	}


	/**
	 * Función para agregar puntos interactivos al mapa
	 * @param {Object} geoJSON - Objeto GeoJSON con los puntos a mostrar
	 * @param {mapboxgl.Map} map
	 */
	function addInteractivePoints(geoJSON, map) {
		// Limpiar marcadores anteriores
		clearInteractivePoints()

		// Obtener la instancia del mapa
		const mapInstance = map
		if (!mapInstance) return

		// Procesar cada feature del GeoJSON
		const container = $('.module-view-container .main-container .column.map')
		const removePreviousCards = () => container.find('.custom-card').remove()
		removePreviousCards()
		geoJSON.features.forEach(feature => {
			if (feature.geometry.type === 'Point') {

				const coordinates = feature.geometry.coordinates
				const properties = feature.properties
				const cardHTML = $(properties.cardHTML)
				const pointHTML = $(properties.pointHTML)

				// Crear el marcador
				const marker = new mapboxgl.Marker(pointHTML.get(0))
					.setLngLat(coordinates)
					.addTo(mapInstance)

				pointHTML.on('click', (e) => {
					e.preventDefault()
					e.stopPropagation()
					removePreviousCards()
					cardHTML.on('click', function (e) {
						if ($(e.target).is('a') || $(e.target).closest('a').length > 0) {
							return true
						}
						e.preventDefault()
						e.stopPropagation()
					})
					container.append(cardHTML)
				})

				// Guardar referencia al marcador
				currentMarkers.push(marker)

			}
		})
		container.off('click').on('click', function (e) {
			removePreviousCards()
		})
	}

	/**
	 * Función para limpiar todos los puntos interactivos
	 */
	function clearInteractivePoints() {
		currentMarkers.forEach(marker => marker.remove())
		currentMarkers = []
	}

})
