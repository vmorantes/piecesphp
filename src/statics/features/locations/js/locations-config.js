window.addEventListener('load', function (e) {

	showGenericLoader('Maps')

	let locations = new LocationsAdapter()
	let mapBoxAdapter = new MapBoxAdapter()
	let dataElementLocation = $('[element-location-module-data]')

	if (dataElementLocation.length > 0) {

		let onlyCountries = dataElementLocation.data('only-countries')
		onlyCountries = typeof onlyCountries !== 'undefined' && onlyCountries.trim().length > 0 ? atob(onlyCountries).split(',') : []
		let onlyStates = dataElementLocation.data('only-states')
		onlyStates = typeof onlyStates !== 'undefined' && onlyStates.trim().length > 0 ? atob(onlyStates).split(',') : []
		let onlyCities = dataElementLocation.data('only-cities')
		onlyCities = typeof onlyCities !== 'undefined' && onlyCities.trim().length > 0 ? atob(onlyCities).split(',') : []

		LocationsAdapter.dataToFilter.onlyCountries = onlyCountries
		LocationsAdapter.dataToFilter.onlyStates = onlyStates
		LocationsAdapter.dataToFilter.onlyCities = onlyCities
	}

	locations.fillSelectWithCountries()

	let controlsMapBox = {
		latitudeInput: $(`[latitude-mapbox-handler]`),
		longitudeInput: $(`[longitude-mapbox-handler]`),
		selectCountry: $(`[locations-component-auto-filled-country]`),
		selectState: $(`[locations-component-auto-filled-state]`),
		selectCity: $(`[locations-component-auto-filled-city]`),
		triggerSatelitalView: $(`[set-satelital-view]`),
		triggerDrawView: $(`[set-draw-view]`),
		triggerCenterView: $(`[set-center-view]`),
	}

	//Sin sesión no hay ruta de la llave (`mapboxKeyURL` nulo): en el acceso, el `fetch` pedía la dirección «null».
	if (typeof pcsphpGlobals.globalAuthenticator !== 'undefined' && typeof pcsphpGlobals.mapboxKeyURL === 'string') {

		//verify() rechaza sin llamar a ninguno de sus dos callbacks si la petición falla o se aborta: el catch quita el cargador.
		pcsphpGlobals.globalAuthenticator.verify(function () {

			fetch(pcsphpGlobals.mapboxKeyURL)
				.then(response => response.text())
				.then(function (key) {
					mapBoxAdapter
						.setKey(key)
						.configurateWhitForm(
							controlsMapBox,
							{
								defaultLongitude: -74.8065913846496,
								defaultLatitude: 11.0021516003209,
							},
							{
								zoom: 3,
							}
						)
				}).finally(function () {
					removeGenericLoader('Maps')
				})

		}, function () {
			removeGenericLoader('Maps')
		}).catch(function () {
			removeGenericLoader('Maps')
		})

	} else {
		removeGenericLoader('Maps')
	}

})
