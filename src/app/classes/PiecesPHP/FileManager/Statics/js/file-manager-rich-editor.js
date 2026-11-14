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

	//Textos que el idioma de elFinder trae mal o no trae (H216). Se ponen en i18load, después de cargar el idioma y antes
	//de que elFinder los use: el archivo del plugin no se toca, que se perdería al actualizarlo.
	const ownMessages = {
		es: {
			cmdhide: 'Ocultar (preferencia)',
			viewlist: 'Ver como lista',
		},
	}
	const applyOwnMessages = function (event, fm) {
		const own = ownMessages[fm.lang]
		if (typeof own !== 'undefined' && typeof fm.i18[fm.lang] === 'object' && fm.i18[fm.lang] !== null) {
			fm.i18[fm.lang].messages = Object.assign(fm.i18[fm.lang].messages || {}, own)
		}
	}

	//https://github.com/Studio-42/elFinder/wiki/Client-configuration-options
	$(fileManagerSelector).elfinder({
		handlers: { i18load: applyOwnMessages },
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
