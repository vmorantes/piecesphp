/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
window.addEventListener('load', function () {
	Documents.configDocumentForm()
	window.dispatchEvent(new Event('canDeleteDocument'))
})
