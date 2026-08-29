/// <reference path="../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../statics/core/js/helpers.js" />
window.addEventListener('load', function () {
	
	let fileManagerSelector = '.filemanager-component'
	let fileManagerComponent = $(fileManagerSelector)
	const replaceLangs = function(lang){
		//elFinder no usa siempre el mismo código que la aplicación. Esta lista traduce del
		//uno al otro; con 'pt' retirado se queda vacía, y así se ve qué hay que mirar al
		//dar de alta un idioma. La receta completa está en `app/config/lang.php`.
		const replaceList = {
			//IDIOMA COMENTADO
			//'pt': 'pt_BR',
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
