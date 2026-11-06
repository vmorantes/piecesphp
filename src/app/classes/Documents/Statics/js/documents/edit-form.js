window.addEventListener('load', function () {
	Documents.configDocumentForm()
	window.dispatchEvent(new Event('canDeleteDocument'))
})
