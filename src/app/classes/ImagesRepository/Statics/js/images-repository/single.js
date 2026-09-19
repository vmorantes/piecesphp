/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
showGenericLoader('singleView')
window.addEventListener('load', function (e) {
	window.dispatchEvent(new Event('canDeleteImageRepository'))
	removeGenericLoader('singleView')
})
