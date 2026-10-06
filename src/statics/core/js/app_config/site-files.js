/// <reference path="../../js/helpers.js" />
showGenericLoader('site-files')
window.addEventListener('load', () => {

	genericFormHandler('form.site-files')

	//La configuración fija, plegada por omisión.
	$('.site-files-view .ui.accordion').accordion()

	removeGenericLoader('site-files')

})
