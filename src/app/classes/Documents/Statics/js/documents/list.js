/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
window.addEventListener('load', function () {

	let tableSelector = "table[url]"
	let table = $(tableSelector)
	let tableURLAttr = "url"

	const dataTable = dataTableServerProccesing(table, table.attr(tableURLAttr), 20, {
		responsive: false,
		drawCallback: function () {
			window.dispatchEvent(new Event('canDeleteDocument'))
			configMirrorScrollX()
		},
	}).DataTable()

	let cropperPreviewImage = null
	let cropperPreviewImageDefaultSrc = null
	let form = Documents.configDocumentForm(function (formProcess) {
		return new Promise(function (resolve) {
			formProcess[0].reset()
			formProcess.find('.ui.dropdown').dropdown('clear')
			formProcess.find('.ui.dropdown').dropdown('refresh')

			if (cropperPreviewImage !== null) {
				cropperPreviewImage.attr('src', cropperPreviewImageDefaultSrc)
			}

			let overlayImageSimpleUploadPlaceholder = formProcess.find('.simple-upload-placeholder .overlay-element.image')
			overlayImageSimpleUploadPlaceholder.removeClass('image')
			overlayImageSimpleUploadPlaceholder.attr('style', '')
			dataTable.draw()
			resolve()
		})
	}, true, true)
	cropperPreviewImage = form.find('.ui.form.cropper-adapter .preview img')
	cropperPreviewImageDefaultSrc = cropperPreviewImage.attr('src')

})
