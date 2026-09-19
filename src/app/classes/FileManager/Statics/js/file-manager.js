/// <reference path="../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../statics/core/js/helpers.js" />
window.addEventListener('load', function () {
	
	let fileManagerSelector = '.filemanager-component'
	let fileManagerComponent = $(fileManagerSelector)
	const replaceLangs = function(lang){
		const replaceList = {
			'pt': 'pt_BR',
		}
		return typeof replaceList[lang] !== 'undefined' ? replaceList[lang] : lang
	}

	//https://github.com/Studio-42/elFinder/wiki/Client-configuration-options
	$(fileManagerSelector).elfinder({
		url: fileManagerComponent.data('route'),
		baseUrl: fileManagerComponent.data('base-url') + '/',
		lang: replaceLangs(pcsphpGlobals.lang),
	})

})
