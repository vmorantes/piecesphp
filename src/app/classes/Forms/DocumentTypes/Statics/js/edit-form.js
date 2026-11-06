window.addEventListener('load', function () {
	DocumentTypes.configDocumentTypeForm()
	window.dispatchEvent(new Event('canDeleteDocumentType'))
})
