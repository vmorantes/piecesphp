window.addEventListener('load', function () {

	let fileManagerSelector = '.filemanager-component'
	let fileManagerComponent = $(fileManagerSelector)
	const replaceLangs = function (lang) {
		//elFinder no usa el mismo código que la aplicación: esta lista traduce del uno al otro.
		const replaceList = {
			'pt': 'pt_BR',
		}
		return typeof replaceList[lang] !== 'undefined' ? replaceList[lang] : lang
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
		lang: replaceLangs(pcsphpGlobals.lang),
		//Hasta el pie de la ventana sin desplazar la página: elFinder fija la altura en línea (400 px por omisión) y el CSS
		//no puede ganarle. Se restan los rellenos, bordes y márgenes inferiores de los contenedores, medidos, no supuestos.
		height: (function (element) {
			let below = 0
			for (let node = element.parentElement; node !== null && node !== document.body; node = node.parentElement) {
				const style = getComputedStyle(node)
				below += parseFloat(style.paddingBottom) + parseFloat(style.borderBottomWidth) + parseFloat(style.marginBottom)
			}
			return Math.max(420, Math.floor(window.innerHeight - element.getBoundingClientRect().top - below))
		})(fileManagerComponent.get(0)),
	})

})
