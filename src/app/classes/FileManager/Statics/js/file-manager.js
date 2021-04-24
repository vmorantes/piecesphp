/// <reference path="../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../statics/core/js/helpers.js" />
window.addEventListener('load', function () {
	
	let fileManagerSelector = '.filemanager-component'
	let fileManagerComponent = $(fileManagerSelector)

	//https://github.com/Studio-42/elFinder/wiki/Client-configuration-options
	$(fileManagerSelector).elfinder({
		url: fileManagerComponent.data('route'),
		baseUrl: fileManagerComponent.data('base-url') + '/',
		lang: 'es',
	})

})
