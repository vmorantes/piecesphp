/// <reference path="../CustomNamespace.js" />
CustomNamespace.loader()
window.addEventListener('loadApp', function (e) {
	CustomNamespace.loader(null, false)
})
