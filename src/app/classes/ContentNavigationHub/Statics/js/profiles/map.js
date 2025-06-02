/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
/// <reference path="../../../../../../statics/core/own-plugins/MapBoxAdapter.js" />
window.addEventListener('load', function () {

	let mapBoxAdapter = new MapBoxAdapter()

	const loaderMap = generateUniqueID()
	showGenericLoader(loaderMap)
	return new Promise(function (resolve) {
		fetch('configurations/mapbox-key')
			.then(response => response.text())
			.then(key => resolve(key))
	}).then(function (key) {
		mapBoxAdapter
			.setKey(key)
			.configurateWhitForm(
				{},
				{
					defaultLongitude: -74.8065913846496,
					defaultLatitude: 11.0021516003209,
					idMapContainer: 'map',
				},
				{
					withMarker: false,
					withGeocoder: false,
					zoom: 10,
				}
			)
	}).finally(function () {
		removeGenericLoader(loaderMap)
	})
})
