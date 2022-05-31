/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
window.addEventListener('load', function () {
	NewsCategories.configNewsCategoryForm()
	window.dispatchEvent(new Event('canDeleteNewsCategory'))
})
