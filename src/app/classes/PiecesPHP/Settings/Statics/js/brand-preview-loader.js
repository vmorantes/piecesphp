//Dentro de la muestra del cargador de Imágenes de marca (inc/brand-previews.php): el cargador de verdad, con los
//data-loader-* del <html> de la muestra. GenericLoaderElement lee la base de pcsphpGlobals, que aquí no hay.
window.pcsphpGlobals = window.pcsphpGlobals || { baseURL: document.baseURI }
window.addEventListener('load', () => {
	if (typeof GenericLoaderElement === 'function') {
		GenericLoaderElement.show('brand-preview')
	}
})
