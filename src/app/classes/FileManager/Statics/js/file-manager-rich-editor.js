/// <reference path="../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../statics/core/js/helpers.js" />
window.addEventListener('load', function () {

	let fileManagerSelector = '.filemanager-component'
	let fileManagerComponent = $(fileManagerSelector)
	const parentWindow = window !== window.parent ? window.parent : window.opener
	let isExternal = parentWindow !== null
	let commandsOptions = {}

	if (isExternal) {
		commandsOptions.getFile = {
			onlyURL: true,
			multiple: false,
			oncomplete: 'close' // Cierra la ventana al seleccionar
		}
	}

	//https://github.com/Studio-42/elFinder/wiki/Client-configuration-options
	$(fileManagerSelector).elfinder({
		url: fileManagerComponent.data('route'),
		baseUrl: fileManagerComponent.data('base-url') + '/',
		lang: pcsphpGlobals.lang,
		height: window.innerHeight,
		commandsOptions: commandsOptions,
		getFileCallback: function (file) {
			const fileURL = typeof file == 'string' ? file : (
				typeof file == 'object' && typeof file.url == 'string' ?
					file.url :
					null
			)
			if (parentWindow !== null) {
				parentWindow.postMessage({ ckeditorSelection: true, fileURL: fileURL }, '*')
				window.close()
			}
		},
	})
})
