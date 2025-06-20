/// <reference path="../CustomNamespace.js" />
CustomNamespace.loader()
window.addEventListener(pcsphpGlobals.events.configurationsAndWindowLoad, function (e) {

	CustomNamespace.tabs('active')

	CustomNamespace.loader(null, false)

})
