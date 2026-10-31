/// <reference path="../../js/helpers.js" />
/// <reference path="../../own-plugins/ContentImageEditor.js" />
showGenericLoader('backgrounds')
window.addEventListener('load', () => {

	new ContentImageEditor()

	removeGenericLoader('backgrounds')

})
