/// <reference path="../../../core/js/configurations.js" />
/// <reference path="../../../core/js/helpers.js" />
/// <reference path="../../../core/own-plugins/LocationsAdapter.js" />
/// <reference path="../../../core/own-plugins/MapBoxAdapter.js" />
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

	new Promise(function (resolve) {

		fetch('configurations/mapbox-key')
			.then(response => response.text())
			.then(key => resolve(key))

	}).then(function (key) {
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

})
