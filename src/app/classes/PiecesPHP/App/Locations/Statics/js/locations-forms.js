/// <reference path="../../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../../statics/core/js/helpers.js" />
window.addEventListener('load', function () {

	//La región de un país: se puede escribir una que no esté en la lista.
	if ($('.ui.dropdown.regions').length > 0) {
		configFomanticDropdown('.ui.dropdown.regions', {
			allowAdditions: true,
		})
	}

})
