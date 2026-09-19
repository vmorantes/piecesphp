/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
window.addEventListener('load', function () {
	DocumentTypes.configDocumentTypeForm()
	window.dispatchEvent(new Event('canDeleteDocumentType'))
})
