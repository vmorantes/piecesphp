/// <reference path="../CustomNamespace.js" />
CustomNamespace.loader()
window.addEventListener('load', function (e) {

	CustomNamespace.tabs('active')

	CustomNamespace.loader(null, false)
	
})
