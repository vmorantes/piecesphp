/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
window.addEventListener('load', function () {
	Persons.configPersonForm()
	window.dispatchEvent(new Event('canDeletePerson'))
})
